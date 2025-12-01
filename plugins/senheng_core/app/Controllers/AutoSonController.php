<?php

class AutoSonController
{
    protected static $channelName = 'SHWEB';

    public static function includeUserMeta($response, $object, $request)
    {
        // Get the customer ID from the order
        $customer_id = $object->get_customer_id();
        // If a customer exists
        if ($customer_id) {
            $meta_keys = [
                'cust_icno',
                'cust_id',
                'cust_name',
                'cust_p1no',
                'idsso',
                'cust_cardtype',
                'cust_email'
            ];

            foreach ($meta_keys as $key) {
                $response->data[$key] = get_user_meta($customer_id, $key, true);
            }
        }
        return $response;
    }

    public static function shweb_validate_woocommerce_auth(WP_REST_Request $request)
    {
        $consumer_key    = $request->get_param('consumer_key');
        $consumer_secret = $request->get_param('consumer_secret');

        if ($consumer_key && $consumer_secret) {
            global $wpdb;

            $key = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT key_id, user_id, permissions FROM {$wpdb->prefix}woocommerce_api_keys WHERE consumer_key = %s",
                    wc_api_hash($consumer_key)
                )
            );

            if ($key && $key->permissions === 'read' || $key->permissions === 'read_write') {
                // Check secret
                $stored_secret = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT consumer_secret FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d",
                        $key->key_id
                    )
                );

                if (hash_equals($stored_secret, $consumer_secret)) {
                    wp_set_current_user($key->user_id);
                    return true;
                }
            }

            return new WP_Error('rest_forbidden', 'Invalid consumer key/secret.', ['status' => 401]);
        }

        // fallback to WooCommerce normal auth (Basic Auth)
        if (get_current_user_id() === 0) {
            return new WP_Error('rest_forbidden', 'Invalid consumer key/secret.', ['status' => 401]);
        }

        return true;
    }

    public static function get_paid_orders(WP_REST_Request $request)
    {
        $data = [];
        $start_date = $request->get_param('start_date');
        $end_date   = $request->get_param('end_date');

        $args = [
            'status' => ['processing', 'completed'],
        ];

        if (!empty($start_date) && !empty($end_date)) {
            $args['date_created'] = gmdate('Y-m-d 00:00:00', strtotime($start_date)) . '...' . gmdate('Y-m-d 23:59:59', strtotime($end_date));
        } else if (!empty($start_date)) {
            $args['date_created'] = '>=' . gmdate('Y-m-d 00:00:00', strtotime($start_date));
        } else if (!empty($end_date)) {
            $args['date_created'] = '<=' . gmdate('Y-m-d 23:59:59', strtotime($end_date));
        }

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            if (!$order->get_date_paid() || $order->get_payment_method() === 'cod') {
                continue;
            }

            $customer_id = $order->get_customer_id();
            // if (!get_user_meta($customer_id, 'idsso', true)) {
            //     continue;
            // }
            $shop_id = '0001';
            $data[] = self::map_order($order, $customer_id, $shop_id);
        }

        return [
            'code'    => 'X0000',
            'success' => true,
            'result'  => [
                'total' => count($data),
                'data'  => $data,
                'empty' => empty($data)
            ]
        ];
    }

    private static function map_order($order, $customer_id, $shop_id)
    {
        $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $isProductWarranty = false;
        $hasVirtualProduct = false;
        $total_scoin = (float) get_post_meta($order->get_id(), '_s_coin_total', true);
        $lines = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->is_virtual()) {
                $hasVirtualProduct = true;
            }
            $line = self::map_order_line($item, $order, $isProductWarranty, $shop_id);
            $lines[] = $line;
        }
        $transaction_id = get_post_meta($order->get_id(), '_ipay88_transaction_id', true);
        $authentication_code = get_post_meta($order->get_id(), '_ipay88_auth_code', true);
        $credit_card_no = get_post_meta($order->get_id(), '_ipay88_cc_no', true);
        $installment_term = get_post_meta($order->get_id(), '_ipay88_payment_plan', true);
        $admin_fee = get_post_meta($order->get_id(), '_ipay88_admin_fee', true);

        return [
            'channel_file_name'  => self::$channelName,
            'orderId'            => $order->get_id(),
            // 'bizCode'            => null, #tobecheck
            'buyer'              => self::map_buyer($order, $customer_id, $full_name),
            // 'shopInfo'           => ['id' => $shop_id, 'name' => 'Senheng Official'],
            // 'orderStatus'        => self::map_order_status(),
            'operateTime'        => self::map_operate_time($order),
            'priceInfo'          => self::map_price_info($order),
            'total_scoin'        => $total_scoin,
            'shippingAddress'    => self::map_shipping($order),
            'billingAddress'     => self::map_billing($order),
            'remark'             => ['buyerRemark' => $order->get_customer_note(), 'sellerRemark' => ''],
            // 'extras'             => self::map_extras($order),
            'orderLines'         => $lines,
            'paymentOrderInfos'  => [self::map_payment_info($order)],
            // 'packageOrderInfos'  => [],
            'einvoiceInfo'       => self::map_einvoice($order, $customer_id, $full_name),
            'creditCardNo'       => $credit_card_no ?: '',
            'transactionNo'      => $transaction_id ?: '',
            'authenticationCode' => $authentication_code ?: '',
            'instalmentTerm'     => $installment_term ? (int) $installment_term : 0,
            'adminFee'           => $admin_fee ?: '',
            'isProductWarranty'  => $isProductWarranty,
            // 'adminFeeWaiver'     => '',
            'voucherIDType'      => '',
            'voucherIDNo'        => '',
            'voucherEmail'       => '',
            'esdEmail'           => $hasVirtualProduct ? $order->get_billing_email() : '',
            // 'shippingFeescoin'   => 0,
            // 'scoinRedemption'    => 0,
            'isAdminFeeWaive'    => false
        ];
    }

    private static function map_buyer($order, $customer_id, $full_name)
    {
        return [
            'userId'     => get_user_meta($customer_id, 'cust_id', true),
            'name'       => get_user_meta($customer_id, 'cust_name', true) ?: $full_name,
            'mobile'     => get_user_meta($customer_id, 'cust_contact', true) ?: $order->get_billing_phone(),
            'email'      => get_user_meta($customer_id, 'cust_email', true) ?: $order->get_billing_email(),
            'cardNo'     => get_user_meta($customer_id, 'cust_p1no', true),
            'icNo'       => get_user_meta($customer_id, 'cust_icno', true) ? get_user_meta($customer_id, 'cust_icno', true) : $order->get_meta('_cust_icno'),
            // 'channelName' => self::$channelName,
            // 'scoinAccNo' => '',
        ];
    }

    private static function map_shipping($order)
    {
        $map_billing = self::map_billing($order);
        return [
            // 'id'                => 30902,
            'receiveUserName'   => !empty($order->get_shipping_first_name()) ? $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() : $map_billing['receiveUserName'],
            'mobile'            => $order->get_billing_phone(),
            // 'provinceId'        => 1600000,
            'province'          => !empty($order->get_shipping_state()) ? $order->get_shipping_state() : $map_billing['province'],
            // 'cityId'            => 1613000,
            'city'              => !empty($order->get_shipping_city()) ? $order->get_shipping_city() : $map_billing['city'],
            // 'streetId'          => 1613278,
            'detail'            => !empty($order->get_shipping_address_1()) ? $order->get_shipping_address_1() : $map_billing['detail'],
            'postalCode'        => !empty($order->get_shipping_postcode()) ? $order->get_shipping_postcode() : $map_billing['postcode'],
            'fullAddressDetail' => !empty($order->get_shipping_state()) ? $order->get_shipping_state() . $order->get_shipping_city() . $order->get_shipping_address_1() : $map_billing['billingFullAddressDetail']
        ];
    }

    private static function map_billing($order)
    {
        return [
            // 'id'                       => 30902,
            'receiveUserName'          => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'mobile'                   => $order->get_billing_phone(),
            // 'provinceId'               => 1600000,
            'province'                 => $order->get_billing_state(),
            // 'cityId'                   => 1613000,
            'city'                     => $order->get_billing_city(),
            // 'streetId'                 => 1613278,
            'detail'                   => $order->get_billing_address_1(),
            'postcode'                 => $order->get_billing_postcode(),
            'billingFullAddressDetail' => $order->get_billing_state() . $order->get_billing_city() . $order->get_billing_address_1()
        ];
    }

    private static function map_order_line($item, $order, &$isProductWarranty, $shop_id)
    {
        $product = $item->get_product();
        $warranty = $item->get_meta('_warranty_selected');
        if ($warranty === 'yes') {
            $isProductWarranty = true;
        }
        $s_coin_value  = (float) $item->get_meta('_s_coin_value');
        // $categories = self::mapCategories($product);

        return [
            'orderLineId' => $item->get_id(),
            // 'bizCode'     => '',
            'sku'         => [
                // 'skuId'   => $product ? $product->get_id() : 0,
                'skuCode' => $product ? $product->get_sku() : '',
                // 'item'    => [
                //     'id'     => $product ? $product->get_id() : 0,
                //     'shopId' => $product ? $product->get_sku() : '',
                //     'name'   => $item->get_name(),
                //     'supportVAT' => false
                // ],
                'skuName' => $item->get_name(),
                // 'shopId'  => $shop_id,
                // 'image'   => $product ? wp_get_attachment_url($product->get_image_id()) : '',
                // 'attributes' => $product ? self::map_product_attributes($product) : [],
                // 'extraPrice' => [],
                'skuExtra'  =>
                [
                    'extraMap' =>
                    [
                        'selling_price' => (string) round(wc_get_price_excluding_tax($product) * $item->get_quantity() * 100),
                        // 'categoryIds' => $categories['categoryIds'],
                        // 'unitQuantity' => (string) $item->get_quantity(),
                        // 'categoryIdListName' => $categories['categoryIdListName'],
                        // 'itemMd5' => md5($product ? $product->get_id() : 0),
                        // 'isVirtual' => $product ? $product->is_virtual() : false,
                        // 'businessType' => $product ? $product->get_type() : '',
                        // 'tax_rate' => $product ? $product->get_tax_class() : '',
                    ]
                ],
                // 'deliveryFeeName' => 'SENHENG OFFICIAL- OWN FLEET (HD)',
            ],
            'quantity' => $item->get_quantity(),
            // 'orderLineStatus' => self::map_order_status(),
            // 'warehouseCodePlan' => '',
            // 'warehouseCodeActual' => '',
            // 'enableStatus' => '',
            // 'deviceSource' => '',
            // 'masterId' => '',
            'price'    => [
                'skuOriginTotalAmount'  => (int) round(wc_get_price_excluding_tax($product) * $item->get_quantity() * 100),
                // 'skuAdjustAmount' => 0,
                'shipFeeOriginAmount'   => (int) round($order->get_shipping_total() * 100),
                // 'shipFeeAdjustAmount' => 0,
                // 'taxFeeOriginAmount' => 0,
                // 'taxFeeAdjustAmount' => 0,
                // 'paidAmount'            => (int) round($item->get_total() * 100),
                'skuDiscountTotalAmount' => (int) round(($item->get_subtotal() - $item->get_total()) * 100),
                // 'skuLevelDiscountAmount' => 0,
                // 'shopLevelDiscountAmount' => 0,
                // 'platformLevelDiscountAmount' => 0,
                // 'shipFeeDiscountTotalAmount' => 0,
                // 'taxFeeDiscountTotalAmount' => 0,
                // 'skuOriginalAmount'     => (int) round($item->get_subtotal() * 100),
            ],
            // 'operateTime' => self::map_operate_time($order),
            // 'extras' => self::map_extras($order),
            'discounts' => self::map_vouchers($order),
            's_coin_value' => $s_coin_value
        ];
    }

    private static function map_product_attributes($product)
    {
        $attributes = [];

        if ($product) {
            // Handle variation attributes if product is a variation
            if ($product->is_type('variation')) {
                $variation_attributes = $product->get_attributes();
                foreach ($variation_attributes as $name => $value) {
                    // $name is like 'attribute_pa_color'
                    $attr_label = wc_attribute_label(str_replace('attribute_', '', $name));
                    $attributes[] = [
                        'name'  => $attr_label,
                        'value' => is_array($value) ? implode(', ', $value) : $value
                    ];
                }
            }

            // Handle normal product attributes
            $product_attributes = $product->get_attributes();
            foreach ($product_attributes as $attribute) {
                if ($attribute instanceof WC_Product_Attribute) {
                    if ($attribute->is_taxonomy()) {
                        $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                        if (!empty($terms)) {
                            $attributes[] = [
                                'name'  => wc_attribute_label($attribute->get_name()),
                                'value' => implode(', ', $terms)
                            ];
                        }
                    } else {
                        $options = $attribute->get_options();
                        $attributes[] = [
                            'name'  => wc_attribute_label($attribute->get_name()),
                            'value' => is_array($options) ? implode(', ', $options) : $options
                        ];
                    }
                }
            }
        }

        return $attributes;
    }

    private static function mapCategories($product)
    {
        $category_ids = $product ? $product->get_category_ids() : [];

        $category_paths = [];
        $all_names = [];
        if ($product && ! empty($category_ids)) {
            foreach ($category_ids as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && ! is_wp_error($term)) {
                    $ancestors = get_ancestors($cat_id, 'product_cat');
                    $ancestors = array_reverse($ancestors);
                    $names     = [];

                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_term($ancestor_id, 'product_cat');
                        if ($ancestor && ! is_wp_error($ancestor)) {
                            $names[] = $ancestor->name;
                            $all_names[] = $ancestor->name;
                        }
                    }
                    $names[] = $term->name;
                    $all_names[] = $term->name;
                    $category_paths[] = implode(' > ', $names);
                }
            }
        }

        $data = [
            'categoryIds'        => $category_ids,
            'categoryIdListName' => implode(',', $all_names),
            'categoryHierarchy'  => implode(',', $category_paths),
        ];

        return $data;
    }

    private static function map_order_status()
    {
        return [
            'payStatus'      => 'PAY_STATUS_PAID',
            'deliveryStatus' => 'DELIVERY_STATUS_TO_PACK',
            'receiveStatus'  => 'RECEIVE_STATUS_UN_RECEIVED',
            'reverseStatus'  => 'REVERSE_STATUS_INIT'
        ];
    }

    private static function map_operate_time($order)
    {
        return [
            'createdAt' => strtotime($order->get_date_created()) * 1000,
            'updatedAt' => strtotime($order->get_date_modified()) * 1000,
            'payAt'     => strtotime($order->get_date_paid()) * 1000
        ];
    }

    private static function map_price_info($order)
    {
        // Determine if any order item used Trade-In and compute pricing fields
        $trade_in_code = '';
        $sku_origin_total_cents = 0;
        $partial_payment_total_cents = 0;

        foreach ($order->get_items() as $item) {
            // Trade-in detection
            $trade_in_opt = $item->get_meta('_trade_in_option');
            if ($trade_in_opt === 'yes') {
                $trade_in_code = get_option('senheng_trade_in_code', '');
            }

            // Origin total should reflect full product price, not deposit
            $product = $item->get_product();
            $qty = (int) $item->get_quantity();
            $base_price = 0.0;
            if ($product) {
                // Use current product price (includes sale pricing) as origin per unit
                $base_price = floatval($product->get_price());
                // Fallback to line subtotal per unit if product price unavailable
                if ($base_price <= 0) {
                    $line_subtotal = floatval($item->get_subtotal());
                    $base_price = $qty > 0 ? ($line_subtotal / $qty) : 0.0;
                }
            } else {
                // Fallback: compute from line subtotal
                $line_subtotal = floatval($item->get_subtotal());
                $base_price = $qty > 0 ? ($line_subtotal / $qty) : 0.0;
            }
            $sku_origin_total_cents += (int) round($base_price * $qty * 100);

            // Partial payment (deposit) sum from item meta
            $deposit = $item->get_meta('_deposit_amount');
            if (is_numeric($deposit)) {
                $partial_payment_total_cents += (int) round(floatval($deposit) * 100);
            }
            // Also include plugin's deposit meta if present
            $plugin_deposit = $item->get_meta('_awcdp_deposits_deposit_amount');
            if (is_numeric($plugin_deposit)) {
                $partial_payment_total_cents += (int) round(floatval($plugin_deposit) * 100);
            }
        }

        return [
            'skuOriginTotalAmount'       => $sku_origin_total_cents,
            'shipFeeOriginAmount'        => (int) round($order->get_shipping_total() * 100),
            'paidAmount'                 => (int) round($order->get_total() * 100),
            'partialPaymentTotalAmount'  => $partial_payment_total_cents,
            'skuDiscountTotalAmount'     => (int) round(($order->get_subtotal() - $order->get_total_discount()) * 100), #tobecheck
            'shipFeeDiscountTotalAmount' => 0,
            'tradeInAmount'              => 0, #tobecheck
            'tradeInCode'                => $trade_in_code,
        ];
    }

    private static function map_payment_info($order)
    {
        $payment_method = get_post_meta($order->get_id(), '_ipay88_payment_type_name', true);
        return [
            'paidAmount'      => (int) round($order->get_total() * 100),
            'originAmount'    => (int) round($order->get_total() * 100),
            // 'totalStages'     => 1,
            // 'stage'           => 1,
            // 'account'         => 'default',
            // 'payAt'           => strtotime($order->get_date_paid()) * 1000,
            'paymentMethod'   => $payment_method ?: 'iPay88',
            // 'payChannel'      => 'IPay88',
            'status'          => 'PAY_SUCCESS',
            // 'externalTradeNo' => $order->get_transaction_id()
        ];
    }

    private static function map_extras($order)
    {
        return [
            'extraMap' => [
                'buyer_phone' => $order->get_billing_phone(),
                'isEVoucher'  => "0" #tobecheck
            ]
        ];
    }

    private static function map_einvoice($order, $customer_id, $full_name)
    {
        if (!$order->get_meta('einvoiceName')) {
            return [];
        }

        return [
            'einvoiceName'      => $order->get_meta('einvoiceName'),
            'einvoiceIDType'    => $order->get_meta('einvoiceIDType'),
            'einvoiceIDNo'      => $order->get_meta('einvoiceIDNo'),
            'einvoiceTinNo'     => $order->get_meta('einvoiceTinNo'),
            'einvoiceSSTNo'     => $order->get_meta('einvoiceSSTNo'),
            'einvoiceContactNo' => $order->get_meta('einvoiceContactNo'),
            'einvoiceEmail'     => $order->get_meta('einvoiceEmail'),
            'einvoiceCity'      => $order->get_meta('einvoiceCity'),
            'einvoiceStateCode' => $order->get_meta('einvoiceStateCode'),
            'einvoiceCountry'   => '14',
            'einvoiceStreetAdd' => $order->get_meta('einvoiceStreetAdd')
        ];
    }

    private static function map_vouchers($order)
    {
        $vouchers = [];
        foreach ($order->get_items('coupon') as $item) {
            $vouchers[] = [
                'activityId'       => $item->get_code(),
                'occupiedAmount'   => (int) round($item->get_discount() * 100),
                'activityIdWithOrderId' => $item->get_code() . '_' . $order->get_id()
            ];
        }
        return $vouchers;
    }
}
