<?php

class RegisterController
{
    public static function registerRegisterRewrite()
    {
        // Add /register rewrite
        add_rewrite_rule('^register/?$', 'index.php?custom_register=1', 'top');

        // Register query var
        add_filter('query_vars', function ($vars) {
            $vars[] = 'custom_register';
            return $vars;
        });

        // Handle template redirect
        add_action('template_redirect', [self::class, 'handleTemplateRedirect']);
    }

    public static function handleTemplateRedirect()
    {
        if (is_account_page() && isset($_GET['action']) && $_GET['action'] === 'register' && !is_user_logged_in()) {
            wp_redirect(home_url('/register'));
            exit;
        }

        // Render signup form if on the correct path
        if (get_query_var('custom_register')) {
            ob_start();
            $view_file = SENHENG_CORE_VIEW_PATH . 'auth/register-stepper.php';
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                echo 'Signup form view not found.';
            }
            echo ob_get_clean();
            exit;
        }
    }

    public static function checkingPhone()
    {
        $phone = sanitize_text_field($_POST['phone']);
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'CONTACT';
        $api = new MagentoAPI();
        $response = $api->checkPhone($phone, $type);

        wp_send_json_success($response);
    }

    public static function requestOtp()
    {
        $phone = sanitize_text_field($_POST['phone']);
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'CONTACT';
        $api = new MagentoAPI();
        $otp_request = $api->requestOtp($phone, $type);

        wp_send_json_success($otp_request);

        wp_send_json_error([
            'phone' => $phone,
            'message' => 'Phone number already registered.',
            'status'  => 'error',
            'success' => false
        ]);
    }

    public static function verifyOtp()
    {
        $otp = sanitize_text_field($_POST['otp']);
        $phone = sanitize_text_field($_POST['phone']);
        $tx_id = sanitize_text_field($_POST['tx_id']);
        $api = new MagentoAPI();
        $verification = $api->validateOtp($tx_id, $phone, $otp);

        wp_send_json_success($verification);
    }

    public static function createUser()
    {

        $rules = [
            'full_name'          => ['required'],
            'email'         => ['required', 'email'],
            'phone'         => ['required', 'phone'],
            'ic_number'     => ['required', 'numeric'],
            'password'      => ['required'],
        ];

        $errors = validate($_POST, $rules);
        if (!empty($errors)) {
            wp_send_json_error(['validation_errors' => $errors]);
            return;
        }

        $name = sanitize_text_field($_POST['full_name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $ic = sanitize_text_field($_POST['ic_number']);
        $password = $_POST['password'];

        // if (empty($_POST['terms'])) {
        //     echo json_encode(['success' => false, 'message' => 'You must accept the terms']);
        //     exit;
        // }

        $data = [
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'ic_number' => $ic,
            'password' => $password
        ];

        $api = new MagentoAPI();
        $registerData = $api->registerUser($data);
        if ($registerData['flag'] !== 1) {
            wp_send_json_error($registerData);
            return;
        }

        // Check if user already exists
        $user = get_user_by('email', $registerData['cust_email']);

        if (!$user) {
            // Create new user since not found
            $username = sanitize_user(strtolower(str_replace(' ', '', $registerData['cust_name'])) . $registerData['idmapping']);
            $user_id = wp_create_user($username, $password, $registerData['cust_email']);

            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => $user_id->get_error_message()]);
                return;
            }

            $user = new WP_User($user_id);
            $user->set_role('customer');

            wp_update_user([
                'ID' => $user_id,
                'display_name' => $registerData['cust_name']
            ]);
        }

        $plan_slug = mapping_membership_SH($registerData['cust_cardtype']);
        $plan_id   = my_get_membership_plan_id_by_slug($plan_slug);
        if ($plan_id) {
            $assignMembership = my_assign_or_switch_membership( $user->ID, $plan_id, 'active', true );
        }

        // User meta fields to update
        $meta_fields = [
            'access_token'      => $registerData['access_token'],
            'expires_in'        => $registerData['expires_in'],
            'refresh_token'     => $registerData['refresh_token'],
            'idmapping'         => $registerData['idmapping'],
            'idsso'             => $registerData['idsso'],
            'is_pin'            => $registerData['is_pin'],
            'cust_allp1no'      => $registerData['cust_allp1no'],
            'cust_all_cardtype' => $registerData['cust_all_cardtype'],
            'cust_cardtype'     => $registerData['cust_cardtype'],
            'cust_contact'      => $registerData['cust_contact'],
            'cust_contact_key'  => $registerData['cust_contact_key'],
            'cust_country'      => $registerData['cust_country'],
            'cust_email'        => $registerData['cust_email'],
            'cust_icno'         => $registerData['cust_icno'],
            'cust_id'           => $registerData['cust_id'],
            'cust_idmapping'    => $registerData['cust_idmapping'],
            'cust_name'         => $registerData['cust_name'],
            'cust_p1no'         => $registerData['cust_p1no']
        ];

        foreach ($meta_fields as $key => $value) {
            update_user_meta($user->ID, $key, $value);
        }

        // Force login
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true); // true = remember me

        // Set Insider registration event cookie
        setcookie('insider_register_event', $user->ID, time() + 300, "/");

        // Send success response
        wp_send_json_success([
            'message' => 'Registration successful.',
            'redirect_url' => esc_url_raw($_SERVER['HTTP_REFERER']),
        ]);
    }
}
