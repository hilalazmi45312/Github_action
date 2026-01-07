<?php

class PwaSessionController
{
    public static function shweb_auto_login_from_token()
    {
        if (isset($_GET['access_token']) && !empty($_GET['access_token'])) {

            $token = sanitize_text_field($_GET['access_token']);
            $current_url = self::current_url();
            $clean_url = remove_query_arg('access_token', $current_url);

            // if (is_user_logged_in()) {
            //     wp_redirect($clean_url);
            //     exit;
            // }

            $magento_api = new MagentoAPI();
            $user_data = $magento_api->sso_get_user_info($token);

            if (isset($user_data['flag']) && $user_data['flag'] === 1) {
                if (is_user_logged_in()) {
                    wp_redirect($clean_url);
                    exit;
                } else {
                    self::shweb_force_wp_login($user_data, $clean_url);
                }
            } else {
                wp_logout();
                wp_redirect(home_url('/?app=true'));
                exit;
            }
        }
    }

    public static function shweb_force_wp_login($user_data, $redirect_url)
    {
        $user = get_user_by('email', $user_data['user_info']['email']);
        if ($user) {
            // User exists, log them in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);

            // ✅ Fire wp_login with both params
            do_action('wp_login', $user->user_login, $user);

            wp_safe_redirect($redirect_url);
            exit;
        } else {
            $magento_api = new MagentoAPI();

            $user_infos = $magento_api->getAllCardInfo($user_data['user_info']['username'], $user_data['user_info']['email']);
            $card = $user_infos['card_info'][0] ?? [];
            $loginData = self::buildLoginData($user_data, $user_infos, $card);
            self::createUserSession($loginData, $redirect_url);

            // wp_logout();
            // wp_safe_redirect(home_url('/?app=true'));
            // exit;
        }
    }


    private static function current_url(): string
    {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? parse_url(home_url(), PHP_URL_HOST);
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }

    public static function hideHeaderFooter()
    {
        $isApp = false;
        $userAgent = get_userAgent();
        // Detect param once
        if (isset($_GET['app']) && in_array(strtolower($_GET['app']), ['1', 'true', 'yes'], true)) {
            $isApp = true;
            // Save state in cookie (30 days)
            setcookie('is_app', '1', time() + 3600 * 24 * 30, '/');
            $_COOKIE['is_app'] = '1'; // immediate availability
        }

        // Or use cookie if param not present
        // if (isset($_COOKIE['is_app']) && $_COOKIE['is_app'] === '1') {
        //     $isApp = true;
        // }

        // if ($userAgent === 'SRC') {
        //     $isApp = true;
        // }

        if ($isApp) {
            echo '
            <style>
                // .whb-header { display: none !important; }
                .whb-main-header { display: none !important; }
                .wd-header-my-account { display: none !important; }
                //.wd-toolbar { display: none !important; }
                //.mobile-nav { display: none !important; }
                // .wd-footer { display: none !important; }
                .customer-logout { display: none !important; }
            </style>

            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            const startTime = performance.now();

            document.addEventListener("DOMContentLoaded", function() {
                // Calculate load time
                const endTime = performance.now();
                const loadTime = (endTime - startTime) / 1000; // Convert to seconds

                // Display load time using SweetAlert2
                // Swal.fire({
                //     title: "Page Load Time",
                //     text: `The page took ${loadTime.toFixed(3)} seconds to fully load.`,
                //     icon: "info",
                //     confirmButtonText: "OK",
                //     position: "top-end",
                //     toast: true,
                //     showConfirmButton: true,
                //     timer: 5000,
                //     timerProgressBar: true
                // });

                let links = document.querySelectorAll("a[href]");
                links.forEach(function(link) {
                    try {
                        let href = link.getAttribute("href");
                        // Skip if href contains appRedirect
                        if (href && href.includes("appRedirect")) {
                            return;
                        }
                        let url = new URL(href, window.location.origin);

                        // skip if it already has ?app=...
                        if (!url.searchParams.has("app")) {
                            url.searchParams.set("app", "true");
                            link.setAttribute("href", url.toString());
                        }
                    } catch (e) {
                        // ignore invalid or anchor-only links
                    }
                });
                
                window.addEventListener("load", function() {
                    const finalLoadTime = (performance.now() - startTime) / 1000;
                    console.log(`Total page load time: ${finalLoadTime.toFixed(3)} seconds`);
                });
            });
            </script>
            ';
        }
    }

    public static function createUserSession($loginData, $redirect_url)
    {
        $user = get_user_by('email', $loginData['cust_email']);
        if (!$user) {
            // Create new user since not found
            $username = sanitize_user(strtolower(str_replace(' ', '', $loginData['cust_name'])) . $loginData['idmapping']);
            $user_id = wp_create_user($username, $otp, $loginData['cust_email']);

            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => $user_id->get_error_message()]);
                return;
            }

            $user = new WP_User($user_id);
            $user->set_role('customer');

            wp_update_user([
                'ID' => $user_id,
                'display_name' => $loginData['cust_name']
            ]);

            // Retrieve ambassador ID once at login
            $config = woo_authorization_salt();
            $idsso  = $loginData['idsso'];

            $ambassador_id = ImpactController::get_ambassador_id($user->ID, $config, $idsso);

            // If ambassador found, mark user as signed up automatically
            if ($ambassador_id) {
                update_user_meta($user->ID, 'impact_ambassador_sign_up', true);
            }

            // User meta fields to update
            $meta_fields = [
                'idmapping'         => $loginData['idmapping'],
                'idsso'             => $loginData['idsso'],
                'cust_allp1no'      => $loginData['cust_allp1no'],
                'cust_all_cardtype' => $loginData['cust_all_cardtype'],
                'cust_cardtype'     => $loginData['cust_cardtype'],
                'cust_contact'      => $loginData['cust_contact'],
                'cust_email'        => $loginData['cust_email'],
                'cust_icno'         => $loginData['cust_icno'],
                'cust_id'           => $loginData['cust_id'],
                'cust_idmapping'    => $loginData['cust_idmapping'],
                'cust_name'         => $loginData['cust_name'],
                'cust_p1no'         => $loginData['cust_p1no']
            ];

            foreach ($meta_fields as $key => $value) {
                update_user_meta($user->ID, $key, $value);
            }

            // Clean (to ensure no cache plugins interfere)
            wp_cache_delete($user->ID, 'users');
            wp_cache_delete($user->user_login, 'userlogins');

            // User exists, log them in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);

            // ✅ Fire wp_login with both params
            do_action('wp_login', $user->user_login, $user);

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    private static function buildLoginData($user_data, $user_infos, $card)
    {
        $cust_allp1no = implode(',', array_column($user_infos['card_info'], 'CARD_NO'));
        $cust_all_cardtype = implode(',', array_column($user_infos['card_info'], 'CARD_TYPE'));
        return [
            'idmapping'         => $card['ID'] ?? null,
            'idsso'             => $user_data['user_info']['username'],
            'cust_allp1no'      => $cust_allp1no,
            'cust_all_cardtype' => $cust_all_cardtype,
            'cust_cardtype'     => $card['CARD_TYPE'] ?? null,
            'cust_contact'      => $card['CONTACT']   ?? null,
            'cust_email'        => $user_data['user_info']['email'],
            'cust_icno'         => $card['ICNO']      ?? null,
            'cust_id'           => $card['MEMBER_ID']        ?? null,
            'cust_idmapping'    => $card['ID'] ?? null,
            'cust_name'         => $user_data['user_info']['firstname'],
            'cust_p1no'         => $card['CARD_NO']   ?? null,
        ];
    }
}
