<?php

/**
 * iPay88 Payment Gateway for WooCommerce
 * 
 * @package Senheng_Core
 * @version 1.0.0
 * @author Senheng Core Team
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only load if WooCommerce is active and payment gateway class exists
if (!class_exists('WC_Payment_Gateway')) {
    return;
}

class WC_Gateway_iPay88 extends WC_Payment_Gateway
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'ipay88';
        $this->icon = defined('SENHENG_CORE_URL') ? SENHENG_CORE_URL . 'assets/uploads/ipay88.webp' : '';
        // Show a payment details box on checkout (like Direct bank transfer)
        $this->has_fields = true;
        $this->method_title = 'iPay88';
        $this->method_description = 'Accept payments through iPay88 payment gateway';

        // Load settings from our custom admin page
        $custom_settings = get_option('senheng_ipay88_settings', []);
        
        // Define properties from custom settings
        $this->title = isset($custom_settings['title']) ? $custom_settings['title'] : 'iPay88';
        $this->description = isset($custom_settings['description']) ? $custom_settings['description'] : 'Secure payment via iPay88. Pay with credit card, debit card, or online banking.';
        $this->enabled = isset($custom_settings['enabled']) ? $custom_settings['enabled'] : 'yes'; // Default to enabled for testing
        $this->merchant_key = isset($custom_settings['merchant_key']) ? $custom_settings['merchant_key'] : 'demo_key';
        $this->merchant_code = isset($custom_settings['merchant_code']) ? $custom_settings['merchant_code'] : 'demo_code';
        $this->environment = isset($custom_settings['environment']) ? $custom_settings['environment'] : 'test';

        // Still initialize form fields for WooCommerce compatibility
        $this->init_form_fields();
        $this->init_settings();

        // URLs
        $this->live_url = 'https://payment.ipay88.com.my/epayment/entry.asp';
        $this->test_url = 'https://sandbox.ipay88.com.my/epayment/entry.asp';

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_wc_gateway_ipay88', [$this, 'handle_ipay88_response']);
        add_action('woocommerce_api_wc_gateway_ipay88_backend', [$this, 'handle_ipay88_backend']);
        add_action('woocommerce_api_ipay88_payment_redirect', [$this, 'handle_payment_redirect']);
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable iPay88 Payment Gateway',
                'default' => 'no'
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'description' => 'Payment method title that the customer sees during checkout.',
                'default' => 'iPay88 Payment Gateway',
                'desc_tip' => true,
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Payment method description that the customer sees during checkout.',
                'default' => 'Pay securely using iPay88 payment gateway.',
                'desc_tip' => true,
            ],
            'merchant_key' => [
                'title' => 'Merchant Key',
                'type' => 'text',
                'description' => 'Enter your iPay88 Merchant Key',
                'default' => '',
                'desc_tip' => true,
            ],
            'merchant_code' => [
                'title' => 'Merchant Code',
                'type' => 'text',
                'description' => 'Enter your iPay88 Merchant Code',
                'default' => '',
                'desc_tip' => true,
            ],
            'environment' => [
                'title' => 'Environment',
                'type' => 'select',
                'description' => 'Select the environment',
                'default' => 'test',
                'options' => [
                    'test' => 'Test/Sandbox',
                    'live' => 'Live/Production'
                ],
                'desc_tip' => true,
            ]
        ];
    }

    /**
     * Check if gateway is available
     */
    public function is_available()
    {
        // Check if gateway is enabled
        if ($this->enabled !== 'yes') {
            return false;
        }

        // For testing purposes, allow demo credentials
        // if (empty($this->merchant_key) || empty($this->merchant_code)) {
        //     // If no real credentials, only allow if using demo values
        //     if ($this->merchant_key !== 'demo_key' && $this->merchant_code !== 'demo_code') {
        //         return false;
        //     }
        // }

        return parent::is_available();
    }

    /**
     * Output payment fields on the checkout page (description/instructions box)
     * This mirrors how gateways like Direct bank transfer display their details.
     */
    public function payment_fields()
    {
        $description = $this->get_description();
        if (empty($description)) {
            $description = 'Secure payment via iPay88. You will be redirected to complete your payment.';
        }

        echo wpautop(wp_kses_post($description));
    }

    /**
     * Process payment
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return [
                'result' => 'failure',
                'messages' => 'Order not found.'
            ];
        }

        // Check if we're in test mode without credentials
        if (empty($this->merchant_key) || empty($this->merchant_code)) {
            // For testing purposes, we'll simulate the payment process
            $order->add_order_note('iPay88 payment attempted in test mode without credentials.');
            wc_add_notice('Test Mode: iPay88 payment would be processed here. Please configure merchant credentials for live payments.', 'notice');
            
            // Mark order as pending payment
            $order->update_status('pending', 'iPay88 test mode - payment simulation');
            
            // Redirect to order received page for testing
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }

        // Mark order as pending payment
        $order->update_status('pending', 'Awaiting iPay88 payment...');

        // Generate payment form and redirect URL
        $payment_url = $this->get_payment_url($order);

        return [
            'result' => 'success',
            'redirect' => $payment_url
        ];
    }

    /**
     * Get payment URL - creates a temporary page that auto-submits to iPay88
     */
    public function get_payment_url($order)
    {
        // Store order data in transient for the payment redirect page
        $payment_data = $this->prepare_payment_data($order);
        $transient_key = 'ipay88_payment_' . $order->get_id() . '_' . time();
        set_transient($transient_key, $payment_data, 300); // 5 minutes

        // Return URL to our payment redirect endpoint
        return add_query_arg([
            'wc-api' => 'ipay88_payment_redirect',
            'order_id' => $order->get_id(),
            'key' => $transient_key
        ], home_url('/'));
    }

    /**
     * Prepare payment data
     */
    private function prepare_payment_data($order)
    {
        $merchant_key = !empty($this->merchant_key) ? $this->merchant_key : 'demo_key';
        $merchant_code = !empty($this->merchant_code) ? $this->merchant_code : 'demo_code';
        $ref_no = $order->get_order_number();
        $amount = number_format($order->get_total(), 2, '.', '');
        $currency = $order->get_currency();
        $prod_desc = 'Order #' . $order->get_order_number();
        $user_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $user_email = $order->get_billing_email();
        $user_contact = $order->get_billing_phone();

        // Response and backend URLs
        $response_url = home_url('/wc-api/wc_gateway_ipay88');
        $backend_url = home_url('/wc-api/wc_gateway_ipay88_backend');

        // Payment URL
        $payment_url = ($this->environment === 'live') ? $this->live_url : $this->test_url;

        // Generate signature
        $signature = $this->generate_signature($merchant_key, $merchant_code, $ref_no, $amount, $currency, '');

        return [
            'payment_url' => $payment_url,
            'merchant_code' => $merchant_code,
            'payment_id' => '',
            'ref_no' => $ref_no,
            'amount' => $amount,
            'currency' => $currency,
            'prod_desc' => $prod_desc,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_contact' => $user_contact,
            'response_url' => $response_url,
            'backend_url' => $backend_url,
            'signature' => $signature,
            'lang' => 'UTF-8'
        ];
    }

    /**
     * Generate payment form
     */
    private function generate_payment_form($order)
    {
        $merchant_key = $this->merchant_key;
        $merchant_code = $this->merchant_code;
        $ref_no = $order->get_order_number();
        $amount = number_format($order->get_total(), 2, '.', '');
        $currency = $order->get_currency();
        $prod_desc = 'Order #' . $order->get_order_number();
        $user_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $user_email = $order->get_billing_email();
        $user_contact = $order->get_billing_phone();

        // Response and backend URLs
        $response_url = home_url('/wc-api/wc_gateway_ipay88');
        $backend_url = home_url('/wc-api/wc_gateway_ipay88_backend');

        // Payment URL
        $payment_url = ($this->environment === 'live') ? $this->live_url : $this->test_url;

        // Generate signature
        $signature = $this->generate_signature($merchant_key, $merchant_code, $ref_no, $amount, $currency, '');

        // Build form
        $form = '<form id="ipay88_payment_form" method="post" action="' . esc_url($payment_url) . '">';
        $form .= '<input type="hidden" name="MerchantCode" value="' . esc_attr($merchant_code) . '">';
        $form .= '<input type="hidden" name="PaymentId" value="">';
        $form .= '<input type="hidden" name="RefNo" value="' . esc_attr($ref_no) . '">';
        $form .= '<input type="hidden" name="Amount" value="' . esc_attr($amount) . '">';
        $form .= '<input type="hidden" name="Currency" value="' . esc_attr($currency) . '">';
        $form .= '<input type="hidden" name="ProdDesc" value="' . esc_attr($prod_desc) . '">';
        $form .= '<input type="hidden" name="UserName" value="' . esc_attr($user_name) . '">';
        $form .= '<input type="hidden" name="UserEmail" value="' . esc_attr($user_email) . '">';
        $form .= '<input type="hidden" name="UserContact" value="' . esc_attr($user_contact) . '">';
        $form .= '<input type="hidden" name="ResponseURL" value="' . esc_url($response_url) . '">';
        $form .= '<input type="hidden" name="BackendURL" value="' . esc_url($backend_url) . '">';
        $form .= '<input type="hidden" name="Signature" value="' . esc_attr($signature) . '">';
        $form .= '<input type="hidden" name="Lang" value="UTF-8">';
        $form .= '</form>';

        return $form;
    }

    /**
     * Generate signature according to iPay88 v3.1 API documentation
     * Signature = SHA1(MerchantKey + MerchantCode + RefNo + Amount + Currency + Xfield1)
     */
    private function generate_signature($merchant_key, $merchant_code, $ref_no, $amount, $currency, $xfield1 = '')
    {
        $string = $merchant_key . $merchant_code . $ref_no . $amount . $currency . $xfield1;
        return base64_encode(hash_hmac('sha512', $string, $merchant_key, true));
    }

    /**
     * Handle iPay88 response
     */
    public function handle_ipay88_response()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die('Invalid request method');
        }

        $merchant_code = sanitize_text_field($_POST['MerchantCode']);
        $payment_id = sanitize_text_field($_POST['PaymentId']);
        $ref_no = sanitize_text_field($_POST['RefNo']);
        $amount = sanitize_text_field($_POST['Amount']);
        $currency = sanitize_text_field($_POST['Currency']);
        $remark = sanitize_text_field($_POST['Remark']);
        $trans_id = sanitize_text_field($_POST['TransId']);
        $auth_code = sanitize_text_field($_POST['AuthCode']);
        $status = sanitize_text_field($_POST['Status']);
        $err_desc = sanitize_text_field($_POST['ErrDesc']);
        $signature = sanitize_text_field($_POST['Signature']);

        // Verify signature
        $expected_signature = $this->generate_response_signature($merchant_code, $payment_id, $ref_no, $amount, $currency, $status);

        if ($signature !== $expected_signature) {
            // Debug signature mismatch
            error_log('🚫 SIGNATURE VALIDATION FAILED');
            error_log('📝 Received signature: ' . $signature);
            error_log('📝 Expected signature: ' . $expected_signature);
            error_log('📝 Signature components:');
            error_log('  - Merchant Code: ' . $merchant_code);
            error_log('  - Payment ID: ' . $payment_id);
            error_log('  - Ref No: ' . $ref_no);
            error_log('  - Amount: ' . $amount);
            error_log('  - Currency: ' . $currency);
            error_log('  - Status: ' . $status);
            
            wp_die('Invalid signature. Check logs for details.');
        }

        // Get order
        $order = wc_get_order($ref_no);

        if (!$order) {
            wp_die('Order not found');
        }

        // Process response
        if ($status === '1') {
            // Payment successful
            $order->payment_complete($trans_id);
            $order->add_order_note(sprintf('iPay88 payment completed. Transaction ID: %s, Auth Code: %s', $trans_id, $auth_code));
            
            // Redirect to thank you page
            wp_redirect($this->get_return_url($order));
            exit;
        } else {
            // Payment failed
            $order->update_status('failed', sprintf('iPay88 payment failed. Error: %s', $err_desc));
            
            // Redirect to checkout with error
            wc_add_notice('Payment failed: ' . $err_desc, 'error');
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }

    /**
     * Handle iPay88 backend response
     */
    public function handle_ipay88_backend()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die('Invalid request method');
        }

        // Similar to handle_ipay88_response but without redirects
        $merchant_code = sanitize_text_field($_POST['MerchantCode']);
        $payment_id = sanitize_text_field($_POST['PaymentId']);
        $ref_no = sanitize_text_field($_POST['RefNo']);
        $amount = sanitize_text_field($_POST['Amount']);
        $currency = sanitize_text_field($_POST['Currency']);
        $status = sanitize_text_field($_POST['Status']);
        $signature = sanitize_text_field($_POST['Signature']);

        // Verify signature
        $expected_signature = $this->generate_response_signature($merchant_code, $payment_id, $ref_no, $amount, $currency, $status);

        if ($signature !== $expected_signature) {
            // Debug signature mismatch
            error_log('🚫 SIGNATURE VALIDATION FAILED');
            error_log('📝 Received signature: ' . $signature);
            error_log('📝 Expected signature: ' . $expected_signature);
            error_log('📝 Signature components:');
            error_log('  - Merchant Code: ' . $merchant_code);
            error_log('  - Payment ID: ' . $payment_id);
            error_log('  - Ref No: ' . $ref_no);
            error_log('  - Amount: ' . $amount);
            error_log('  - Currency: ' . $currency);
            error_log('  - Status: ' . $status);
            
            wp_die('Invalid signature. Check logs for details.');
        }

        // Get order
        $order = wc_get_order($ref_no);

        if (!$order) {
            wp_die('Order not found');
        }

        // Process response
        if ($status === '1') {
            $order->payment_complete();
            $order->add_order_note('iPay88 backend payment confirmation received.');
        } else {
            $order->update_status('failed', 'iPay88 backend payment failed.');
        }

        // Return OK to iPay88
        echo 'OK';
        exit;
    }

    /**
     * Generate response signature using HMAC-SHA512
     */
    private function generate_response_signature($merchant_code, $payment_id, $ref_no, $amount, $currency, $status)
    {
        $merchant_key = !empty($this->merchant_key) ? $this->merchant_key : 'demo_key';
        // iPay88 v3.2 API: Response signature uses 7 fields (different from request)
        // MerchantKey + MerchantCode + PaymentId + RefNo + Amount + Currency + Status
        $string = $merchant_key . $merchant_code . $payment_id . $ref_no . $amount . $currency . $status;
        return base64_encode(hash_hmac('sha512', $string, $merchant_key, true));
    }

    /**
     * Handle payment redirect - displays auto-submit form to iPay88
     */
    public function handle_payment_redirect()
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        if (!$order_id || !$key) {
            wp_die('Invalid payment request.');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_die('Order not found.');
        }

        // Get payment data from transient
        $payment_data = get_transient($key);
        if (!$payment_data) {
            wp_die('Payment session expired. Please try again.');
        }

        // Delete the transient as it's one-time use
        delete_transient($key);

        // Display auto-submit form
        $this->display_payment_form($payment_data);
        exit;
    }

    /**
     * Display payment form that auto-submits to iPay88
     */
    private function display_payment_form($payment_data)
    {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Redirecting to iPay88...</title>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .loading { display: inline-block; margin: 20px 0; }
                .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin: 0 auto; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        </head>
        <body>
            <h2>Redirecting to iPay88 Payment Gateway...</h2>
            <div class="loading">
                <div class="spinner"></div>
            </div>
            <p>Please wait while we redirect you to complete your payment securely.</p>
            
            <form id="ipay88_payment_form" method="post" action="<?php echo esc_url($payment_data['payment_url']); ?>">
                <input type="hidden" name="MerchantCode" value="<?php echo esc_attr($payment_data['merchant_code']); ?>">
                <input type="hidden" name="PaymentId" value="<?php echo esc_attr($payment_data['payment_id']); ?>">
                <input type="hidden" name="RefNo" value="<?php echo esc_attr($payment_data['ref_no']); ?>">
                <input type="hidden" name="Amount" value="<?php echo esc_attr($payment_data['amount']); ?>">
                <input type="hidden" name="Currency" value="<?php echo esc_attr($payment_data['currency']); ?>">
                <input type="hidden" name="ProdDesc" value="<?php echo esc_attr($payment_data['prod_desc']); ?>">
                <input type="hidden" name="UserName" value="<?php echo esc_attr($payment_data['user_name']); ?>">
                <input type="hidden" name="UserEmail" value="<?php echo esc_attr($payment_data['user_email']); ?>">
                <input type="hidden" name="UserContact" value="<?php echo esc_attr($payment_data['user_contact']); ?>">
                <input type="hidden" name="ResponseURL" value="<?php echo esc_url($payment_data['response_url']); ?>">
                <input type="hidden" name="BackendURL" value="<?php echo esc_url($payment_data['backend_url']); ?>">
                <input type="hidden" name="Signature" value="<?php echo esc_attr($payment_data['signature']); ?>">
                <input type="hidden" name="Lang" value="<?php echo esc_attr($payment_data['lang']); ?>">
                
                <noscript>
                    <input type="submit" value="Click here if you are not redirected automatically">
                </noscript>
            </form>

            <script>
                // Auto-submit the form after a short delay
                setTimeout(function() {
                    document.getElementById('ipay88_payment_form').submit();
                }, 1000);
            </script>
        </body>
        </html>
        <?php
    }
}
