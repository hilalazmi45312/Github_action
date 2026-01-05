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
            wp_logout();
            wp_safe_redirect(home_url('/?app=true'));
            exit;
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
}
