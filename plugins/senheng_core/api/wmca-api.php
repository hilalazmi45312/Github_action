<?php

function get_address_book($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData = senhengallInfo();
    $user_id = $shData['user_id'];

    // WCMCA addresses
    $raw_addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
    $combined_addresses = [];

    if (is_array($raw_addresses)) {
        foreach ($raw_addresses as $index => $addr) {
            if (! is_array($addr)) {
                continue;
            }

            $type   = isset($addr['type']) ? $addr['type'] : 'billing';
            $prefix = $type . '_';

            $default_key = $prefix . 'is_default_address';
            $is_default  = ! empty($addr[$default_key]) && (int) $addr[$default_key] === 1;

            $id = ! empty($addr['address_id']) ? (string) $addr['address_id'] : (string) $index;
            $id = preg_replace('/^(billing_|shipping_)/', '', $id);

            // Basic mapped fields from WCMCA
            $mapped = [
                'first_name' => $addr[$prefix . 'first_name'] ?? '',
                'last_name'  => $addr[$prefix . 'last_name']  ?? '',
                'company'    => $addr[$prefix . 'company']    ?? '',
                'address_1'  => $addr[$prefix . 'address_1']  ?? '',
                'address_2'  => $addr[$prefix . 'address_2']  ?? '',
                'city'       => $addr[$prefix . 'city']       ?? '',
                'state'      => $addr[$prefix . 'state']      ?? '',
                'postcode'   => $addr[$prefix . 'postcode']   ?? '',
                'country'    => $addr[$prefix . 'country']    ?? '',
                'phone'      => $addr[$prefix . 'phone']      ?? '',
                'email'      => $addr[$prefix . 'email']      ?? '',
            ];

            // Init combined entry if not yet created
            if (! isset($combined_addresses[$id])) {
                $full_name = trim($mapped['first_name'] . ' ' . $mapped['last_name']);

                $combined_addresses[$id] = [
                    'id'              => is_numeric($id) ? (int) $id : $id,
                    'userId'          => $shData['idmapping'],
                    'receiveUserName' => $full_name ?: $mapped['first_name'],

                    'phone'           => null,
                    'mobile'          => $mapped['phone'],
                    'countryId'       => null,
                    'country'         => $mapped['country'],
                    'provinceId'      => null,
                    'province'        => $mapped['state'],
                    'cityId'          => null,
                    'city'            => $mapped['city'],
                    'regionId'        => null,
                    'region'          => null,
                    'streetId'        => null,
                    'street'          => '',
                    'detail'          => $mapped['address_1'] . ' ' . $mapped['address_2'],
                    'postcode'        => $mapped['postcode'],

                    // flags will be filled per type below
                    'isDefault'       => false,
                    'isBillingDefault' => false,

                    'longitude'       => null,
                    'latitude'        => null,
                    'extra'           => null,
                    'createdAt'       => null,
                    'updatedAt'       => null,
                    'isDelete'        => null,
                ];
            } else {
                // If we already have entry, optionally overwrite some fields
                // e.g. prefer shipping for "detail" if you want – up to you.
                $full_name = trim($mapped['first_name'] . ' ' . $mapped['last_name']);
                if ($full_name) {
                    $combined_addresses[$id]['receiveUserName'] = $full_name;
                }
                if (! empty($mapped['phone'])) {
                    $combined_addresses[$id]['mobile'] = $mapped['phone'];
                }
                if (! empty($mapped['country'])) {
                    $combined_addresses[$id]['country'] = $mapped['country'];
                }
                if (! empty($mapped['state'])) {
                    $combined_addresses[$id]['province'] = $mapped['state'];
                }
                if (! empty($mapped['city'])) {
                    $combined_addresses[$id]['city'] = $mapped['city'];
                }
                if (! empty($mapped['address_2'])) {
                    $combined_addresses[$id]['region'] = $mapped['address_2'];
                }
                if (! empty($mapped['address_1'])) {
                    $combined_addresses[$id]['detail'] = $mapped['address_1'];
                }
                if (! empty($mapped['postcode'])) {
                    $combined_addresses[$id]['postcode'] = $mapped['postcode'];
                }
            }

            // Set default flags based on type
            if ($type === 'billing' && $is_default) {
                $combined_addresses[$id]['isBillingDefault'] = true;
            }
            if ($type === 'shipping' && $is_default) {
                $combined_addresses[$id]['isDefault'] = true;
            }
        }
    }

    echo wp_json_encode([
        'data'    => array_values($combined_addresses),
        'success' => true,
    ]);
}

function delete_address_book($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    // Base id from router (/delete/{base_id})
    $base_id = isset($data['address_id']) ? sanitize_text_field($data['address_id']) : '';

    if ($user_id <= 0 || $base_id === '') {
        wp_send_json_error(['message' => 'Invalid user or address id'], 400);
    }

    $addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
    if (!is_array($addresses)) {
        $addresses = [];
    }

    // Count distinct base_ids (strip billing_/shipping_ prefix)
    $base_ids = [];
    foreach ($addresses as $addr) {
        if (!empty($addr['address_id'])) {
            $stored_raw_id  = (string) $addr['address_id'];          // e.g. billing_173482
            $stored_base_id = preg_replace('/^(billing_|shipping_)/', '', $stored_raw_id);
            $base_ids[$stored_base_id] = true;
        }
    }

    // If there is only one base_id and it's the one we're trying to delete -> block
    if (count($base_ids) <= 1 && isset($base_ids[$base_id])) {
        wp_send_json_error([
            'message' => 'At least one address is required',
        ], 400);
    }

    // Remove both billing+shipping rows for this base id
    foreach ($addresses as $k => $addr) {
        if (empty($addr['address_id'])) {
            continue;
        }

        $stored_raw_id  = (string) $addr['address_id'];
        $stored_base_id = preg_replace('/^(billing_|shipping_)/', '', $stored_raw_id);

        if ($stored_base_id === $base_id) {
            unset($addresses[$k]);
        }
    }

    $addresses = array_values($addresses);
    update_user_meta($user_id, '_wcmca_additional_addresses', $addresses);

    wp_send_json([
        'data'    => true,
        'success' => true,
    ]);
}


function add_address_book($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    // Optional label (fallback "Address")
    $label = ! empty($data['label'])
        ? sanitize_text_field($data['label'])
        : 'Address';

    $is_default_shipping = ! empty($data['isDefault']);
    $is_default_billing  = ! empty($data['isBillingDefault']);

    // Existing WCMCA addresses
    $addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
    if (! is_array($addresses)) {
        $addresses = [];
    }

    $field_map = [
        'first_name',
        'last_name',
        'company',
        'country',
        'state',
        'address_1',
        'address_2',
        'city',
        'postcode',
        'phone',
        'email',
    ];

    /**
     * Convert API payload -> internal address fields
     * so it's the "reverse" of your get_address_book mapping.
     */
    $receive_name = trim($data['receiveUserName'] ?? '');
    $first_name   = $receive_name;
    $last_name    = '';

    // Simple split "First Last"
    if (strpos($receive_name, ' ') !== false) {
        $parts      = explode(' ', $receive_name, 2);
        $first_name = $parts[0];
        $last_name  = $parts[1];
    }

    $mapped_from_api = [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'company'    => '', // no company in payload
        'country'    => $data['country']  ?? '',
        'state'      => $data['province'] ?? '',
        'address_1'  => $data['detail']   ?? '',
        'address_2'  => '', // you could map region/street here if you want
        'city'       => $data['city']     ?? '',
        'postcode'   => $data['postcode'] ?? '',
        'phone'      => $data['mobile']   ?? '',
        'email'      => $shData['cust_email'] ?? '',
    ];

    // Clear existing defaults if this new one will be default
    $clear_defaults_for_type = function (&$addresses, $type) {
        $prefix      = $type . '_';
        $default_key = $prefix . 'is_default_address';

        foreach ($addresses as &$addr) {
            if (isset($addr['type']) && $addr['type'] === $type) {
                $addr[$default_key] = 0;
            }
        }
        unset($addr);
    };

    if ($is_default_billing) {
        $clear_defaults_for_type($addresses, 'billing');
    }
    if ($is_default_shipping) {
        $clear_defaults_for_type($addresses, 'shipping');
    }

    // One base id shared by billing+shipping rows (this is what you'll return in "data")
    $base_id = uniqid();

    $build_entry = function ($type, $label, $make_default, $base_id) use ($field_map, $mapped_from_api, $data) {
        $prefix = $type . '_';

        $entry = [
            'type'                  => $type,
            'address_id'            => $type . '_' . $base_id,
            'address_internal_name' => $label,
        ];

        foreach ($field_map as $field) {
            // Prefer mapped_from_api, fallback to direct field (for backward compatibility)
            $value = $mapped_from_api[$field] ?? ($data[$field] ?? '');

            $entry[$prefix . $field] = sanitize_text_field($value);
        }

        $default_key         = $prefix . 'is_default_address';
        $entry[$default_key] = $make_default ? 1 : 0;

        return $entry;
    };

    // Build both rows from the same API payload
    $billing_entry  = $build_entry('billing',  $label, $is_default_billing,  $base_id);
    $shipping_entry = $build_entry('shipping', $label, $is_default_shipping, $base_id);

    $addresses[] = $billing_entry;
    $addresses[] = $shipping_entry;

    update_user_meta($user_id, '_wcmca_additional_addresses', $addresses);

    // Match the desired response:
    // { "data": <id>, "success": true }
    wp_send_json([
        'data'    => $base_id,
        'success' => true,
    ]);
}


function edit_address_book($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    // Base ID coming from frontend (same "data" you returned in add_address_book)
    $base_id = ! empty($data['id'])
        ? sanitize_text_field($data['id'])
        : '';

    if ($base_id === '') {
        wp_send_json_error(['message' => 'Invalid address id'], 400);
    }

    // Optional label (fallback "Address")
    $label = ! empty($data['label'])
        ? sanitize_text_field($data['label'])
        : 'Address';

    $is_default_shipping = ! empty($data['isDefault']);
    $is_default_billing  = ! empty($data['isBillingDefault']);

    // Existing WCMCA addresses
    $addresses = get_user_meta($user_id, '_wcmca_additional_addresses', true);
    if (! is_array($addresses)) {
        wp_send_json_error(['message' => 'No addresses found'], 404);
    }

    $field_map = [
        'first_name',
        'last_name',
        'company',
        'country',
        'state',
        'address_1',
        'address_2',
        'city',
        'postcode',
        'phone',
        'email',
    ];

    /**
     * Convert API payload -> internal address fields
     * (same logic as add_address_book)
     */
    $receive_name = trim($data['receiveUserName'] ?? '');
    $first_name   = $receive_name;
    $last_name    = '';

    // Simple split "First Last"
    if (strpos($receive_name, ' ') !== false) {
        $parts      = explode(' ', $receive_name, 2);
        $first_name = $parts[0];
        $last_name  = $parts[1];
    }

    $mapped_from_api = [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'company'    => '', // no company in payload
        'country'    => $data['country']  ?? '',
        'state'      => $data['province'] ?? '',
        'address_1'  => $data['detail']   ?? '',
        'address_2'  => '', // you could map region/street here if you want
        'city'       => $data['city']     ?? '',
        'postcode'   => $data['postcode'] ?? '',
        'phone'      => $data['mobile']   ?? '',
        'email'      => $shData['cust_email'] ?? '',
    ];

    // Clear existing defaults if this one will become default
    $clear_defaults_for_type = function (&$addresses, $type) {
        $prefix      = $type . '_';
        $default_key = $prefix . 'is_default_address';

        foreach ($addresses as &$addr) {
            if (isset($addr['type']) && $addr['type'] === $type) {
                $addr[$default_key] = 0;
            }
        }
        unset($addr);
    };

    if ($is_default_billing) {
        $clear_defaults_for_type($addresses, 'billing');
    }
    if ($is_default_shipping) {
        $clear_defaults_for_type($addresses, 'shipping');
    }

    // Update both billing + shipping rows that share the same base id
    foreach ($addresses as &$addr) {
        if (empty($addr['address_id'])) {
            continue;
        }

        // Stored ID looks like "billing_<base_id>" or "shipping_<base_id>"
        $stored_raw_id  = (string) $addr['address_id'];
        $stored_base_id = preg_replace('/^(billing_|shipping_)/', '', $stored_raw_id);

        if ($stored_base_id !== $base_id) {
            continue;
        }

        $type   = isset($addr['type']) ? $addr['type'] : 'billing';
        $prefix = $type . '_';

        // Keep the prefix-based id structure
        $addr['address_id']            = $type . '_' . $base_id;
        $addr['address_internal_name'] = $label;

        foreach ($field_map as $field) {
            /**
             * Prefer new mapped_from_api value.
             * If you want to avoid wiping fields when you don't send them,
             * we can also fall back to old stored value.
             */
            $new_value = $mapped_from_api[$field] ?? '';

            // If completely empty and you want to preserve old value:
            if ($new_value === '' && isset($addr[$prefix . $field])) {
                $new_value = $addr[$prefix . $field];
            }

            $addr[$prefix . $field] = sanitize_text_field($new_value);
        }

        $default_key = $prefix . 'is_default_address';

        if ($type === 'billing') {
            $addr[$default_key] = $is_default_billing ? 1 : (isset($addr[$default_key]) ? $addr[$default_key] : 0);
        }

        if ($type === 'shipping') {
            $addr[$default_key] = $is_default_shipping ? 1 : (isset($addr[$default_key]) ? $addr[$default_key] : 0);
        }
    }
    unset($addr);

    update_user_meta($user_id, '_wcmca_additional_addresses', $addresses);

    wp_send_json([
        'data'    => true,
        'success' => true,
    ]);
}

/**
 * Normalise address data into a consistent array for the API.
 *
 * @param string $id
 * @param string $type        'billing' or 'shipping'
 * @param string $label       Human readable label (eg. "Home", "Office")
 * @param bool   $is_default
 * @param array  $raw         Raw address fields from WC / WCMCA
 *
 * @return array
 */
function format_single_address_for_api($id, $type, $label, $is_default, $raw)
{
    return [
        'id'         => $id,
        'type'       => $type,
        'label'      => $label,
        'is_default' => (bool) $is_default,
        'first_name' => $raw['first_name'] ?? '',
        'last_name'  => $raw['last_name'] ?? '',
        'company'    => $raw['company'] ?? '',
        'phone'      => $raw['phone'] ?? '',
        'email'      => $raw['email'] ?? '',
        'address_1'  => $raw['address_1'] ?? '',
        'address_2'  => $raw['address_2'] ?? '',
        'city'       => $raw['city'] ?? '',
        'state'      => $raw['state'] ?? '',
        'postcode'   => $raw['postcode'] ?? '',
        'country'    => $raw['country'] ?? '',
    ];
}
