<?php

class LoginController
{
    public static function registerLoginRewrite()
    {
        // Add /login rewrite
        add_rewrite_rule('^login/?$', 'index.php?custom_login=1', 'top');

        // Register query var
        add_filter('query_vars', function ($vars) {
            $vars[] = 'custom_login';
            return $vars;
        });

        // Handle template redirect
        add_action('template_redirect', [self::class, 'handleTemplateRedirect']);

        // Override the document title
        add_filter('document_title_parts', [self::class, 'filterLoginTitle']);
    }

    public static function handleTemplateRedirect()
    {
        if (get_query_var('custom_login')) {
            ob_start();
            $view_file = SENHENG_CORE_VIEW_PATH . 'auth/login.php';
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                echo 'Login form view not found.';
            }
            echo ob_get_clean();
            exit;
        }
    }

    public static function filterLoginTitle($parts)
    {
        if (get_query_var('custom_login')) {
            // Full control over <title>
            return [
                'title' => get_bloginfo('name') . ' - Login'
            ];
        }
        return $parts;
    }

    public static function login()
    {
        $rules = [
            'otp'   => ['required'],
            'phone' => ['required'],
            'type'  => ['required']
        ];

        $errors = validate($_POST, $rules);
        if (!empty($errors)) {
            wp_send_json_error(['validation_errors' => $errors]);
            return;
        }

        $otp   = sanitize_text_field($_POST['otp']);
        $phone = sanitize_text_field($_POST['phone']);
        $tx_id = sanitize_text_field($_POST['tx_id']);
        $type  = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'CONTACT';

        $api = new MagentoAPI();
        $loginData = $api->login($tx_id, $phone, $otp, $type);

        if ($loginData['flag'] !== 1) {
            wp_send_json_error($loginData);
            return;
        }

        // Check if user already exists
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
        }

        $plan_slug = mapping_membership_SH($loginData['cust_cardtype']);
        $plan_id   = my_get_membership_plan_id_by_slug($plan_slug);
        if ($plan_id) {
            $assignMembership = my_assign_or_switch_membership( $user->ID, $plan_id, 'active', true );
        }

        // User meta fields to update
        $meta_fields = [
            'access_token'      => $loginData['access_token'],
            'expires_in'        => $loginData['expires_in'],
            'refresh_token'     => $loginData['refresh_token'],
            'idmapping'         => $loginData['idmapping'],
            'idsso'             => $loginData['idsso'],
            'is_pin'            => $loginData['is_pin'],
            'cust_allp1no'      => $loginData['cust_allp1no'],
            'cust_all_cardtype' => $loginData['cust_all_cardtype'],
            'cust_cardtype'     => $loginData['cust_cardtype'],
            'cust_contact'      => $loginData['cust_contact'],
            'cust_contact_key'  => $loginData['cust_contact_key'],
            'cust_country'      => $loginData['cust_country'],
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

        // Force login
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true); // true = remember me

        // Set Insider login event cookie
        setcookie('insider_login_event', $user->ID, time() + 300, "/");

        // Send success response
        wp_send_json_success([
            'message' => 'Login successful.',
            'redirect_url' => esc_url_raw($_SERVER['HTTP_REFERER']),
        ]);
    }

    public static function socialLogin()
    {
        $arr_post['email'] = sanitize_email($_POST['email']);
        $arr_post['uid'] = sanitize_text_field($_POST['uid']);
        $arr_post['access_token'] = sanitize_text_field($_POST['access_token']);
        $arr_post['name'] = sanitize_text_field($_POST['name']);
        $arr_post['provider'] = sanitize_text_field($_POST['provider']);
        $arr_post['photo_url'] = sanitize_text_field($_POST['photo_url']);

        $api = new MagentoAPI();
        $loginData = $api->socialLogin($arr_post);

        if ($loginData['flag'] !== 1) {
            wp_send_json_error($loginData);
            return;
        }

        // Check if user already exists
        $user = get_user_by('email', $loginData['cust_email']);

        if (!$user) {
            // Create new user since not found
            $username = sanitize_user(strtolower(str_replace(' ', '', $loginData['cust_name'])) . $loginData['idmapping']);
            $user_id = wp_create_user($username, '1234', $loginData['cust_email']);

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
        }

        $plan_slug = mapping_membership_SH($loginData['cust_cardtype']);
        $plan_id   = my_get_membership_plan_id_by_slug($plan_slug);
        if ($plan_id) {
            $assignMembership = my_assign_or_switch_membership( $user->ID, $plan_id, 'active', true );
        }

        // User meta fields to update
        $meta_fields = [
            'access_token'      => $loginData['access_token'],
            'expires_in'        => $loginData['expires_in'],
            'refresh_token'     => $loginData['refresh_token'],
            'idmapping'         => $loginData['idmapping'],
            'idsso'             => $loginData['idsso'],
            'is_pin'            => $loginData['is_pin'],
            'cust_allp1no'      => $loginData['cust_allp1no'],
            'cust_all_cardtype' => $loginData['cust_all_cardtype'],
            'cust_cardtype'     => $loginData['cust_cardtype'],
            'cust_contact'      => $loginData['cust_contact'],
            'cust_contact_key'  => $loginData['cust_contact_key'],
            'cust_country'      => $loginData['cust_country'],
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

        // Force login
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true); // true = remember me

        // Set Insider login event cookie
        setcookie('insider_login_event', $user->ID, time() + 300, "/");

        // Send success response
        wp_send_json_success([
            'message' => 'Login successful.',
            'redirect_url' => esc_url_raw($_SERVER['HTTP_REFERER']),
        ]);
    }

    public static function enqueueAssetsPopup()
    {
        //user not logged in
        if (!is_user_logged_in()) {
            include SENHENG_CORE_VIEW_PATH . 'auth/login-popup.php';
        }
    }
}
