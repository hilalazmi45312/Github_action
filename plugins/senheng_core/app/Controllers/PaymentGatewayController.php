<?php

/**
 * Payment Gateway Controller
 * 
 * Handles payment gateway admin pages and payment method registration
 * 
 * @package Senheng_Core
 * @version 1.0.0
 * @author Senheng Core Team
 */

class PaymentGatewayController
{
    /**
     * Initialize the payment gateway controller
     */
    public static function init()
    {
        // Admin assets
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        
        // Frontend assets
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
        
        // AJAX handlers for frontend
        add_action('wp_ajax_process_ipay88_payment', [self::class, 'handleProcessIPay88Payment']);
        add_action('wp_ajax_nopriv_process_ipay88_payment', [self::class, 'handleProcessIPay88Payment']);
        
        // Initialize payment gateway after plugins are loaded
        add_action('plugins_loaded', [self::class, 'initPaymentGateway'], 11);
    }

    /**
     * Initialize payment gateway after WooCommerce is loaded
     */
    public static function initPaymentGateway()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Load the iPay88 gateway class
        require_once plugin_dir_path(__FILE__) . '../Services/WC_Gateway_iPay88.php';
        
        // Register the payment gateway
        add_filter('woocommerce_payment_gateways', [self::class, 'addPaymentGateway']);
    }

    /**
     * Render the admin page
     */
    public static function adminIndex()
    {
        // Debug logging can be removed after testing

        // Handle form submission - simplified logic
        if (!empty($_POST) && isset($_POST['payment_gateway_nonce'])) {
            if (wp_verify_nonce($_POST['payment_gateway_nonce'], 'save_payment_gateway_settings')) {
                error_log('Form submission validated, calling handleFormSubmission');
                self::handleFormSubmission();
            } else {
                error_log('Nonce verification failed');
                set_transient('senheng_payment_gateway_error', 'Security check failed. Please try again.', 30);
            }
        }

        // Load the admin view
        $view = SENHENG_CORE_VIEW_PATH . 'payment-gateway/admin/admin.php';
        if (file_exists($view)) {
            include $view;
        }
    }

    /**
     * Handle admin form submission
     */
    public static function handleFormSubmission()
    {
        if (!current_user_can('manage_options')) {
            set_transient('senheng_payment_gateway_error', 'Insufficient permissions to save settings.', 30);
            return;
        }

        try {
            // Save iPay88 settings (no validation required for merchant fields)
            $ipay88_settings = [
                'enabled' => isset($_POST['ipay88_enabled']) ? 'yes' : 'no',
                'title' => 'iPay88',
                'description' => isset($_POST['ipay88_description']) ? sanitize_textarea_field($_POST['ipay88_description']) : 'Secure payment via iPay88. Pay with credit card, debit card, or online banking.',
                'instructions' => 'You will be redirected to iPay88 to complete your payment securely.',
                'merchant_key' => isset($_POST['ipay88_merchant_key']) ? sanitize_text_field($_POST['ipay88_merchant_key']) : '',
                'merchant_code' => isset($_POST['ipay88_merchant_code']) ? sanitize_text_field($_POST['ipay88_merchant_code']) : '',
                'environment' => isset($_POST['ipay88_environment']) ? sanitize_text_field($_POST['ipay88_environment']) : 'test',
                'response_url' => isset($_POST['ipay88_response_url']) ? sanitize_url($_POST['ipay88_response_url']) : home_url('/?wc-api=WC_Gateway_Ipay88'),
                'backend_url' => isset($_POST['ipay88_backend_url']) ? sanitize_url($_POST['ipay88_backend_url']) : home_url('/?wc-api=WC_Gateway_Ipay88')
            ];

            error_log('Saving settings: ' . print_r($ipay88_settings, true));
            
            // Check if option exists first
            $existing_option = get_option('senheng_ipay88_settings', null);
            error_log('Existing option: ' . ($existing_option === null ? 'DOES NOT EXIST' : 'EXISTS'));
            
            // Try to save
            $result = update_option('senheng_ipay88_settings', $ipay88_settings);
            error_log('Save result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
            
            // If update failed, try add_option instead
            if ($result === false) {
                error_log('Update failed, trying add_option');
                $add_result = add_option('senheng_ipay88_settings', $ipay88_settings);
                error_log('Add result: ' . ($add_result ? 'SUCCESS' : 'FAILED'));
                $result = $add_result;
            }

            // Store the result in a transient for the next page load
            // Note: update_option returns false if the new value is identical to the existing value
            // So we also check if the option was actually saved correctly
            $saved_option = get_option('senheng_ipay88_settings');
            $is_saved = ($result !== false) || ($saved_option == $ipay88_settings);
            
            if ($is_saved) {
                // Sync WooCommerce's native gateway option so Woo UI reflects our toggle
                $wc_option_key = 'woocommerce_ipay88_settings';
                $wc_settings = get_option($wc_option_key, []);
                if (!is_array($wc_settings)) {
                    $wc_settings = [];
                }
                $wc_settings['enabled'] = $ipay88_settings['enabled'];
                $wc_settings['title'] = isset($ipay88_settings['title']) ? $ipay88_settings['title'] : 'iPay88';
                $wc_settings['description'] = isset($ipay88_settings['description']) ? $ipay88_settings['description'] : '';
                // Keep these for completeness; WC core will ignore unknown keys, but it's harmless
                $wc_settings['merchant_key'] = isset($ipay88_settings['merchant_key']) ? $ipay88_settings['merchant_key'] : '';
                $wc_settings['merchant_code'] = isset($ipay88_settings['merchant_code']) ? $ipay88_settings['merchant_code'] : '';
                $wc_settings['environment'] = isset($ipay88_settings['environment']) ? $ipay88_settings['environment'] : 'test';
                update_option($wc_option_key, $wc_settings);

                $success_msg = 'Settings saved successfully!';
                if (empty($ipay88_settings['merchant_key']) || empty($ipay88_settings['merchant_code'])) {
                    $success_msg .= ' Note: Gateway is in test mode (merchant credentials are empty).';
                }
                error_log('Setting success transient: ' . $success_msg);
                set_transient('senheng_payment_gateway_success', $success_msg, 30);
            } else {
                error_log('Setting error transient - both update and verification failed');
                set_transient('senheng_payment_gateway_error', 'Failed to save settings. Please try again.', 30);
            }

        } catch (Exception $e) {
            set_transient('senheng_payment_gateway_error', 'Error saving settings: ' . $e->getMessage(), 30);
        }

        // Redirect to prevent form resubmission
        wp_redirect(add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']));
        exit;
    }

    /**
     * Handle AJAX request for iPay88 payment processing
     */
    public static function handleProcessIPay88Payment()
    {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'senheng_payment_nonce')) {
            wp_die('Security check failed');
        }

        parse_str($_POST['form_data'], $form_data);
        
        // Debug: Log received form data
        error_log('📋 Received form data: ' . print_r($form_data, true));
        
        try {
            // Validate WooCommerce is available
            if (!class_exists('WooCommerce') || !function_exists('wc')) {
                wp_send_json_error([
                    'message' => 'WooCommerce is not available'
                ]);
                return;
            }

            // Validate checkout data using WooCommerce (fallback for older WC versions)
            $errors = new WP_Error();
            
            // Try the function, with fallback for older WooCommerce versions
            if (function_exists('wc_checkout_validate_form_fields')) {
                wc_checkout_validate_form_fields($form_data, $errors);
            } else {
                // Manual validation for older WC versions
                if (empty($form_data['billing_first_name'])) {
                    $errors->add('billing_first_name', 'First name is required');
                }
                if (empty($form_data['billing_email']) || !is_email($form_data['billing_email'])) {
                    $errors->add('billing_email', 'Valid email is required');
                }
            }
            
            if ($errors->has_errors()) {
                wp_send_json_error([
                    'message' => 'Validation failed: ' . implode(', ', $errors->get_error_messages())
                ]);
                return;
            }

            // Create order from checkout data
            $order_id = wc_create_order();
            if (is_wp_error($order_id)) {
                wp_send_json_error([
                    'message' => 'Failed to create order'
                ]);
                return;
            }

            $order = wc_get_order($order_id);
            
            // Set billing address - handle multiple field name patterns
            $field_mappings = [
                'first_name' => ['wcmca_billing_first_name', 'billing_first_name', 'first_name'],
                'last_name' => ['wcmca_billing_last_name', 'billing_last_name', 'last_name'],
                'email' => ['wcmca_billing_email', 'billing_email', 'email'],
                'phone' => ['wcmca_billing_phone', 'billing_phone', 'phone'],
                'address_1' => ['wcmca_billing_address_1', 'billing_address_1', 'address_1', 'wcmca_billing_address_internal_name'],
                'city' => ['wcmca_billing_city', 'billing_city', 'city'],
                'postcode' => ['wcmca_billing_postcode', 'billing_postcode', 'postcode'],
                'country' => ['wcmca_billing_country', 'billing_country', 'country'],
                'state' => ['wcmca_billing_state', 'billing_state', 'state']
            ];
            
            // Also check standard field names as fallback
            $standard_fields = [
                'first_name' => 'billing_first_name',
                'last_name' => 'billing_last_name',
                'email' => 'billing_email',
                'phone' => 'billing_phone',
                'address_1' => 'billing_address_1',
                'city' => 'billing_city',
                'postcode' => 'billing_postcode',
                'country' => 'billing_country',
                'state' => 'billing_state'
            ];
            
            foreach ($field_mappings as $key => $possible_fields) {
                $value = '';
                
                // Try each possible field name until we find one with data
                foreach ($possible_fields as $field_name) {
                    if (isset($form_data[$field_name]) && !empty($form_data[$field_name])) {
                        $value = sanitize_text_field($form_data[$field_name]);
                        error_log("🎯 Found $key data in field: $field_name = $value");
                        break;
                    }
                }
                
                // Set the billing field if value exists
                if (!empty($value)) {
                    switch ($key) {
                        case 'first_name':
                            $order->set_billing_first_name($value);
                            break;
                        case 'last_name':
                            $order->set_billing_last_name($value);
                            break;
                        case 'email':
                            $order->set_billing_email($value);
                            break;
                        case 'phone':
                            $order->set_billing_phone($value);
                            break;
                        case 'address_1':
                            $order->set_billing_address_1($value);
                            break;
                        case 'city':
                            $order->set_billing_city($value);
                            break;
                        case 'postcode':
                            $order->set_billing_postcode($value);
                            break;
                        case 'country':
                            $order->set_billing_country($value);
                            break;
                        case 'state':
                            $order->set_billing_state($value);
                            break;
                    }
                } else {
                    error_log("⚠️ No data found for $key");
                }
            }
            
            // Debug: Log successfully mapped billing data
            error_log('✅ Mapped billing data: First=' . $order->get_billing_first_name() . 
                     ', Last=' . $order->get_billing_last_name() . 
                     ', Email=' . $order->get_billing_email() . 
                     ', Phone=' . $order->get_billing_phone());

            // Add cart items to order
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                $order->add_product($product, $quantity);
            }

            // Set payment method
            $order->set_payment_method('ipay88');
            $order->set_payment_method_title('iPay88');
            
            // Calculate totals
            $order->calculate_totals();
            $order->save();

            // Get iPay88 gateway instance
            // Check if gateway class exists before instantiation
            if (!class_exists('WC_Gateway_iPay88')) {
                wp_send_json_error([
                    'message' => 'iPay88 gateway class not found'
                ]);
                return;
            }
            
            $gateway = new WC_Gateway_iPay88();
            
            // Generate payment data
            $payment_data = [
                'payment_url' => '',
                'merchant_code' => $gateway->merchant_code,
                'payment_id' => '',
                'ref_no' => $order->get_order_number(),
                'amount' => number_format($order->get_total(), 2, '.', ''),
                'currency' => $order->get_currency(),
                'prod_desc' => 'Order #' . $order->get_order_number(),
                'user_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'user_email' => $order->get_billing_email(),
                'user_contact' => $order->get_billing_phone(),
                'response_url' => home_url('/?wc-api=WC_Gateway_Ipay88'),
                'backend_url' => home_url('/wc-api/wc_gateway_ipay88_backend'),
                'signature_type' => 'HMACSHA512',
                'signature' => '', // Generated below
                'lang' => 'UTF-8',
                'xfield1' => '' // Additional custom field for iPay88
            ];

            // Generate signature
            $merchant_key = !empty($gateway->merchant_key) ? $gateway->merchant_key : 'demo_key';

            $amount = preg_replace('/\D+/', '', (string) $payment_data['amount']); // adjust if your spec wants "100.00
            $xfield1 = isset($payment_data['xfield1']) ? (string) $payment_data['xfield1'] : '';

            $signature_string = (string) $payment_data['merchant_code'] . (string) $payment_data['ref_no'] . $amount . (string) $payment_data['currency'] . $xfield1;
            $payment_data['signature'] = base64_encode(hash_hmac('sha512', $signature_string, $merchant_key, true));

          
            // Debug signature generation for iPay88 request
            error_log('🔐 SIGNATURE GENERATED FOR iPay88 REQUEST:');
            error_log('📝 Generated signature: ' . $payment_data['signature']);
            error_log('📝 Components: Key=' . $merchant_key . ', Code=' . $payment_data['merchant_code'] . ', Ref=' . $payment_data['ref_no'] . ', Amount=' . $payment_data['amount'] . ', Curr=' . $payment_data['currency'] . ', Xfield=' . $payment_data['xfield1']);

            // Set payment URL based on environment
            $payment_data['payment_url'] = ($gateway->environment === 'live') ? 
                'https://payment.ipay88.com.my/epayment/entry.asp' : 
                'https://sandbox.ipay88.com.my/epayment/entry.asp';

            log_api_request(
                'iPay88 Payment Request',
                'POST',
                json_encode($payment_data),
                200,
                'Payment request sent to iPay88',
            );

            // Generate payment form HTML
            $form_html = self::generatePaymentFormHtml($payment_data);

            wp_send_json_success([
                'form' => $form_html,
                'order_id' => $order_id,
                'payment_data' => $payment_data
            ]);

        } catch (Exception $e) {
            error_log('❌ Payment processing exception: ' . $e->getMessage());
            error_log('❌ Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error([
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate payment form HTML for AJAX submission
     */
    private static function generatePaymentFormHtml($payment_data)
    {
        $form_html = '<form id="ipay88_payment_form" method="post" action="' . esc_url($payment_data['payment_url']) . '">';
        $form_html .= '<input type="hidden" name="MerchantCode" value="' . esc_attr($payment_data['merchant_code']) . '">';
        $form_html .= '<input type="hidden" name="PaymentId" value="' . esc_attr($payment_data['payment_id']) . '">';
        $form_html .= '<input type="hidden" name="RefNo" value="' . esc_attr($payment_data['ref_no']) . '">';
        $form_html .= '<input type="hidden" name="Amount" value="' . esc_attr($payment_data['amount']) . '">';
        $form_html .= '<input type="hidden" name="Currency" value="' . esc_attr($payment_data['currency']) . '">';
        $form_html .= '<input type="hidden" name="ProdDesc" value="' . esc_attr($payment_data['prod_desc']) . '">';
        $form_html .= '<input type="hidden" name="UserName" value="' . esc_attr($payment_data['user_name']) . '">';
        $form_html .= '<input type="hidden" name="UserEmail" value="' . esc_attr($payment_data['user_email']) . '">';
        $form_html .= '<input type="hidden" name="UserContact" value="' . esc_attr($payment_data['user_contact']) . '">';
        $form_html .= '<input type="hidden" name="ResponseURL" value="' . esc_url($payment_data['response_url']) . '">';
        $form_html .= '<input type="hidden" name="BackendURL" value="' . esc_url($payment_data['backend_url']) . '">';
        $form_html .= '<input type="hidden" name="Signature" value="' . esc_attr($payment_data['signature']) . '">';
        $form_html .= '<input type="hidden" name="SignatureType" value="' . esc_attr($payment_data['signature_type']) . '">';
        $form_html .= '<input type="hidden" name="Lang" value="' . esc_attr($payment_data['lang']) . '">';
        $form_html .= '<input type="hidden" name="Xfield1" value="' . esc_attr($payment_data['xfield1']) . '">';
        $form_html .= '<input type="submit" value="Proceed to iPay88" style="display:none;">';
        $form_html .= '</form>';
        
        return $form_html;
    }

    /**
     * Add payment gateway to WooCommerce
     */
    public static function addPaymentGateway($gateways)
    {
        $gateways[] = 'WC_Gateway_iPay88';
        return $gateways;
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueueAdminAssets($hook)
    {
        if ($hook !== 'senheng-core_page_payment-options') {
            return;
        }

        wp_enqueue_style(
            'senheng-payment-gateway-admin',
            SENHENG_CORE_URL . 'assets/css/payment-gateway/admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'senheng-payment-gateway-admin',
            SENHENG_CORE_URL . 'assets/js/payment-gateway/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueueFrontendAssets()
    {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'senheng-payment-gateway-frontend',
            SENHENG_CORE_URL . 'assets/css/payment-gateway/frontend.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'senheng-payment-gateway-frontend',
            SENHENG_CORE_URL . 'assets/js/payment-gateway/frontend.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize script with AJAX URL
        wp_localize_script('senheng-payment-gateway-frontend', 'senheng_payment_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('senheng_payment_nonce')
        ]);
    }

    public static function registerCallbackPage()
    {
        add_action('init', function () {
            add_rewrite_rule('^ipay88/backend/?$', 'index.php?ipay88_backend=1', 'top');
        });
        add_filter('query_vars', function ($vars) {
            $vars[] = 'ipay88_backend';
            return $vars;
        });
        add_action('template_redirect', function () {
            if (get_query_var('ipay88_backend')) {
                self::callback();
                exit;
            }
        });
    }

    public static function callback()
    {
        $merchantCode = $_POST['MerchantCode'] ?? '';
        $paymentId    = $_POST['PaymentId'] ?? '';
        $refNo        = $_POST['RefNo'] ?? '';
        $amount       = $_POST['Amount'] ?? '';
        $currency     = $_POST['Currency'] ?? '';
        $remark       = $_POST['Remark'] ?? '';
        $transId      = $_POST['TransId'] ?? '';
        $authCode     = $_POST['AuthCode'] ?? '';
        $status       = $_POST['Status'] ?? '';
        $errDesc      = $_POST['ErrDesc'] ?? '';
        $signature    = $_POST['Signature'] ?? '';
        
        log_api_request(
            'iPay88 Callback',
            'POST',
            json_encode($_POST),
        );

        $gateway = new WC_Gateway_iPay88();
        $merchantKey = $gateway->merchant_key;

        $verified = self::verify_ipay88_signature($merchantKey, $merchantCode, $refNo, $amount, $currency, $status, $signature);
        log_api_request(
            'iPay88 Signature Verification',
            'N/A',
            $verified ? 'VERIFIED' : 'FAILED'
        );
        if ($verified) {
            $order = wc_get_order($refNo);
            if ($order) {
                if ($status == '1') {
                    // Payment successful
                    $order->payment_complete($transId);
                    $order->add_order_note('iPay88 payment successful. Transaction ID: ' . $transId);
                    echo 'RECEIVEOK';
                } else {
                    // Payment failed
                    $order->update_status('failed', 'iPay88 payment failed. Error: ' . $errDesc);
                    echo 'RECEIVEOK';
                }
            } else {
                // Order not found
                error_log('iPay88 callback: Order not found - RefNo: ' . $refNo);
                echo 'RECEIVEOK';
            }
        } else {
            // Signature verification failed
            error_log('iPay88 callback: Signature verification failed for RefNo: ' . $refNo);
            echo 'RECEIVEOK';
        }
    }

    public function verify_ipay88_signature($merchantKey, $merchantCode, $refNo, $amount, $currency, $status, $signature)
    {
        // Amount format: remove commas and decimal points
        $amount = str_replace([',', '.'], '', $amount);

        $source =  $merchantCode . $refNo . $amount . $currency . $status;
        $localSignature = base64_encode(hash_hmac('sha512', $source, $merchantKey, true));
        // Concatenate and hash
        $source = $merchantKey . $merchantCode . $refNo . $amount . $currency . $status;
        $localSignature = base64_encode(hex2bin(sha1($source)));

        return $localSignature === $signature;
    }
}

// Initialize the controller
// PaymentGatewayController::init();
