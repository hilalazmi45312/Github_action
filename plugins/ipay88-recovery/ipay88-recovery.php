<?php

/**
 * Plugin Name: iPay88 Manual Recovery Tool
 * Description: Recover WooCommerce orders using iPay88 enquiry API
 * Version: 1.0.0
 * Author: Ilias Cloone
 */

if (! defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        'iPay88 Recovery',
        'iPay88 Recovery',
        'manage_woocommerce',
        'ipay88-recovery',
        'ipay88_recovery_page'
    );
});

function ipay88_recovery_page()
{
?>
    <div class="wrap">
        <h1>iPay88 Manual Recovery</h1>

        <textarea id="ipay88_json" rows="15" style="width:100%;" placeholder="Paste iPay88 response JSON here"></textarea>

        <br><br>

        <button id="ipay88-recover-btn" class="button button-primary">
            Recover Order
        </button>

        <span id="ipay88-loading" style="display:none; margin-left:10px;">
            ⏳ Processing...
        </span>

        <div id="ipay88-result" style="margin-top:15px;"></div>
    </div>

    <script type="text/javascript">
        jQuery(function($) {

            $('#ipay88-recover-btn').on('click', function(e) {
                e.preventDefault();

                const jsonData = $('#ipay88_json').val();
                const resultBox = $('#ipay88-result');

                resultBox.html('');
                $('#ipay88-loading').show();
                $('#ipay88-recover-btn').prop('disabled', true);

                $.post(ajaxurl, {
                        action: 'ipay88_manual_recovery',
                        security: '<?php echo wp_create_nonce('ipay88_recovery_nonce'); ?>',
                        ipay88_json: jsonData
                    })
                    .done(function(response) {
                        if (response.success) {
                            resultBox.html(
                                '<div class="notice notice-success"><p>' + response.data + '</p></div>'
                            );
                        } else {
                            resultBox.html(
                                '<div class="notice notice-error"><p>' + response.data + '</p></div>'
                            );
                        }
                    })
                    .fail(function() {
                        resultBox.html(
                            '<div class="notice notice-error"><p>AJAX request failed.</p></div>'
                        );
                    })
                    .always(function() {
                        $('#ipay88-loading').hide();
                        $('#ipay88-recover-btn').prop('disabled', false);
                    });
            });

        });
    </script>
<?php
}

function ipay88_enquiry_check($merchant_code, $refno, $amount)
{
    $endpoint = 'https://payment.ipay88.com.my/epayment/enquiry.asp';

    $body = [
        'MerchantCode' => $merchant_code,
        'RefNo'        => $refno,
        'Amount'       => $amount,
    ];

    $response = wp_remote_post($endpoint, [
        'timeout' => 30,
        'body'    => $body,
    ]);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'error'   => $response->get_error_message(),
        ];
    }

    $raw = trim(wp_remote_retrieve_body($response));

    // 🚨 Handle plain status responses (MOST COMMON)
    if (preg_match('/^(00|01|02)$/', $raw)) {
        return [
            'success' => true,
            'raw'     => $raw,
            'parsed'  => [],
            'status'  => $raw,
        ];
    }

    // 🧪 Handle key=value response
    parse_str($raw, $parsed);

    if (isset($parsed['Status'])) {
        return [
            'success' => true,
            'raw'     => $raw,
            'parsed'  => $parsed,
            'status'  => $parsed['Status'],
        ];
    }

    // ❌ Unknown / HTML response
    return [
        'success' => false,
        'error'   => 'Unexpected enquiry response',
        'raw'     => $raw,
    ];
}

function manual_ipay88_fix_order_with_enquiry(array $posted)
{

    if (empty($posted['RefNo']) || empty($posted['Amount'])) {
        return 'Missing RefNo or Amount';
    }

    // Normalize RefNo
    $refno_raw = $posted['RefNo'];
    $refno_clean = normalize_refno($refno_raw);

    $order_id = (int) $refno_clean;
    $order = wc_get_order($order_id);

    if (! $order) {
        return 'Order not found for RefNo: ' . $refno_raw;
    }

    // Prevent double processing
    if (in_array($order->get_status(), ['processing', 'completed'], true)) {
        return 'Order already processed';
    }

    // 🔎 Enquiry check FIRST
    $enquiry = ipay88_enquiry_check(
        $posted['MerchantCode'],
        $refno_raw,
        $posted['Amount']
    );

    if (! $enquiry['success']) {
        return 'Enquiry API error: ' . $enquiry['error'];
    }

    if ($enquiry['status'] !== '00') {
        return 'Enquiry status not successful: ' . $enquiry['status'];
    }

    // ✅ Enquiry SUCCESS → proceed
    update_post_meta($order_id, '_ipay88_transaction_id', sanitize_text_field($posted['TransId'] ?? ''));
    update_post_meta($order_id, '_ipay88_auth_code', sanitize_text_field($posted['AuthCode'] ?? ''));
    update_post_meta($order_id, '_ipay88_cc_no', sanitize_text_field($posted['CCNo'] ?? ''));
    update_post_meta($order_id, '_ipay88_response_json', json_encode($posted));

    $order->add_order_note(
        'Manual iPay88 recovery via ENQUIRY SUCCESS. RefNo: ' . $refno_raw
    );

    $order->payment_complete($posted['TransId'] ?? '');

    return 'Order #' . $order_id . ' successfully recovered via enquiry';
}

add_action('wp_ajax_ipay88_manual_recovery', 'ipay88_manual_recovery_ajax');

function ipay88_manual_recovery_ajax()
{

    // 🔐 Security
    if (
        ! isset($_POST['security']) ||
        ! wp_verify_nonce($_POST['security'], 'ipay88_recovery_nonce')
    ) {
        wp_send_json_error('Invalid security token');
    }

    if (empty($_POST['ipay88_json'])) {
        wp_send_json_error('Empty JSON payload');
    }

    $json = json_decode(wp_unslash($_POST['ipay88_json']), true);

    if (! is_array($json)) {
        wp_send_json_error('Invalid JSON format');
    }

    // Run recovery
    $result = manual_ipay88_fix_order_with_enquiry($json);

    // Decide success vs error
    if (strpos($result, 'successfully') !== false) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

function normalize_refno($refno)
{
    // Take digits from the START only
    if (preg_match('/^(\d+)/', $refno, $m)) {
        return (int) $m[1];
    }
    return 0;
}