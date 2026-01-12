<?php

function get_payment_method_list($data = [])
{
    $auth = $data['_headers']['Authorization'];
    if (stripos($auth, 'basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6));
        [$consumer_key, $consumer_secret] = array_pad(explode(':', $decoded, 2), 2, null);
    }

    if (empty($consumer_key) || empty($consumer_secret)) {
        wp_send_json_error(['message' => 'Missing consumer_key or consumer_secret'], 401);
    }

    $user_id = validate_woocommerce_api_key($consumer_key, $consumer_secret);

    if (!$user_id) {
        wp_send_json_error(['message' => 'Invalid consumer_key or consumer_secret'], 401);
    }

    $gateways = WC()->payment_gateways()->payment_gateways();

    if (isset($gateways['ipay88'])) {
        $ipay88 = $gateways['ipay88'];

        $paymenttype_options = $ipay88->types_mapping;

        // Example
        $result = $paymenttype_options ?? '';

        wp_send_json_success($result);
    } else {
        wp_send_json_error(['message' => 'iPay88 gateway not found'], 404);
    }
}
