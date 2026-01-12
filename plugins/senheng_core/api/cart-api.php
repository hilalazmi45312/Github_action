<?php

function get_cart($data = [])
{
    // if (!is_user_logged_in()) {
    //     wp_send_json_error(['message' => 'Not logged in'], 401);
    // }

    $shData  = senhengallInfo();
    // $user_id = $shData['user_id'];

    $cartType  = isset($data['cartType']) ? max(1, (int) $data['cartType']) : null;
    $clientType    = isset($data['clientType'])   ? max(1, (int) $data['clientType'])   : null;
    $divisionIds = isset($data['divisionIds']) ? (int) $data['divisionIds'] : null;

    $cart = WC()->cart;
    $cart->calculate_totals();
    $cart_items = $cart->get_cart();
    // Group by shop (vendor) – for now WordPress does not support vendor, so shopId is null
    $shops = [];

    $summary_original = 0;
    $summary_real = 0;
    $summary_scoin = 0;
    $checked_items = 0;

    foreach ($cart_items as $cart_item_key => $cart_item) {

        $product_id   = (int) $cart_item['product_id'];
        $variation_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;
        $qty          = (int) $cart_item['quantity'];

        $wc_product_id = $variation_id ?: $product_id;
        $product       = wc_get_product($wc_product_id);
        $brand = get_the_terms($product_id, 'product_brand');
        $categoryTree = get_the_terms($product_id, 'product_cat');
        $categoryTreeList = [];
        $categoryTreeListName = [];
        if ($categoryTree && !is_wp_error($categoryTree)) {
            foreach ($categoryTree as $cat) {
                $categoryTreeList[] = $cat->term_id;
                $categoryTreeListName[$cat->term_id] = $cat->name;
            }
        }
        $category_ids = wp_get_post_terms($variation_id > 0 ? $variation_id : $product_id, 'product_cat', ['fields' => 'ids']);
        $categoryListString = "[" . implode(",", $category_ids) . "]";


        if (!$product) continue;

        $skuAttributes = [];
        $attributesList = [];

        if ($variation_id > 0) {
            $variation = wc_get_product($variation_id);

            // WC variation attributes (slug => value)
            $variation_attributes = $variation->get_attributes();

            foreach ($variation_attributes as $attr_slug => $attr_value_slug) {

                // Convert `pa_color` → `Color`
                $attr_name = wc_attribute_label($attr_slug);
                // Convert slug value → real value, e.g. "space-grey" → "Space Grey"
                $attr_value = wc_get_product_terms($variation_id, $attr_slug, ['fields' => 'names']);

                $attr_value = !empty($attr_value) ? $attr_value[0] : $attr_value_slug;

                // Build skuAttributes
                $skuAttributes[$attr_name] = $attr_value;

                // Build attributes array block
                $attributesList[] = [
                    "tenantId" => null,
                    "tenantIdLong" => null,
                    "extra" => null,
                    "id" => null,
                    "attrKey" => $attr_name,
                    "attrVal" => $attr_value,
                    "unit" => null,
                    "showImage" => false,
                    "thumbnail" => null,
                    "image" => null,
                ];
            }
        } else {
            // Parent product OR no variation
            $skuAttributes = [];
            $attributesList = [];
        }

        $price = (float) wc_get_price_to_display($product);
        $line_total = $price * $qty;

        // S-Coin logic (same as original)
        $s_coin_value = (float)(
            ($variation_id ? get_field('s_coin_value', $variation_id) : 0)
            ?: get_field('s_coin_value', $product_id)
        );

        $item_scoin = 0;
        if ($s_coin_value && $price > 0) {
            $raw_price = (float) $product->get_price();
            if ($raw_price > 0) {
                $item_scoin = $s_coin_value * $raw_price * $qty;
                $item_scoin = round($item_scoin, 0, PHP_ROUND_HALF_UP);
            }
        }

        $summary_original += $line_total;
        $summary_real     += $line_total;
        $summary_scoin    += $item_scoin;
        $checked_items++;

        // GROUPING (shop) – WordPress doesn't provide vendor → set null
        $shop_id = null;

        if (!isset($shops[$shop_id])) {
            $shops[$shop_id] = [
                "groupType" => "SHOP",
                "childGroups" => [],
                "totalLines" => 0,
                "checkedLines" => 0,
                "totalItems" => 0,
                "checkedItems" => 0,
                "cartShop" => [
                    "shopId" => null,
                    "sellerId" => null,
                    "shopImage" => null,
                    "shopName" => null,
                    "tags" => null,
                    "extra" => [
                        "logo" => null,
                        "contacts" => "[{}]"
                    ]
                ],
                "shopCouponList" => null
            ];
        }

        // BUILD CART LINE (matches EXACT structure)
        $line_data = [
            "groupType" => "LINE",
            "childGroups" => [],
            "totalLines" => 1,
            "checkedLines" => 1,
            "totalItems" => $qty,
            "checkedItems" => $qty,
            "cartLine" => [
                "tenantId" => null,
                "cartLineId" => $cart_item_key,
                "bizCode" => null,
                "buyerId" => $shData['idmapping'],
                "shopId" => null,
                "itemId" => $product_id,
                "itemName" => $product->get_name(),
                "esdItem" => $product->is_virtual(),
                "skuId" => $variation_id ? $variation_id : $product_id,
                "skuCode" => $product->get_sku(),
                "quantity" => $qty,
                "qtyLocked" => null,
                "checkBoxLocked" => null,
                "price" => [
                    "unitSnapshotPrice" => $price,
                    "unitOriginalPrice" => $price,
                    "unitDiscountPrice" => 0,
                    "unitRealPrice" => $price,
                    "summaryOriginalPrice" => $line_total,
                    "summaryRedemptionValue" => null,
                    "summaryEarnSCoinValue" => $item_scoin,
                    "summaryDiscountPrice" => 0,
                    "summaryRealPrice" => $line_total
                ],
                "buyLimit" => [
                    "max" => $product->get_max_purchase_quantity(),
                    "min" => $product->get_min_purchase_quantity()
                ],
                "createdAt" => null,
                "updatedAt" => null,
                "checked" => true,
                "itemStatus" => "VALID",
                "title" => $product->get_name(),
                "brandId" => $brand && !is_wp_error($brand) ? $brand[0]->term_id : null,
                "brandName" => $brand && !is_wp_error($brand) ? $brand[0]->name : null,
                "categoryTreeList" => $categoryTreeList,
                "categoryTreeListName" => $categoryTreeListName,
                "totalSCoinPercentage" => $s_coin_value,
                "skuImageUrl" => wp_get_attachment_image_url($product->get_image_id(), 'full'),
                "itemAttributes" => [
                    "itemBusinessType" => null,
                    "categoryList" => $categoryListString,
                    "productType" => null,
                ],
                "skuAttributes" => $skuAttributes,
                "attributes" => $attributesList,
                "itemType" => 1,
                "extra" => null,
                "itemPromotion" => null,
                "candidateShopPromotions" => [],
                "isTradeIn" => isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes',
                "isPartialPayment" => isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit',
                "depositInfo" => $cart_item['awcdp_deposit'] ?? null,
                "isProductWarranty" => product_has_warranty($product->get_name()),
                "isStorePickUp" => null
            ]
        ];

        $shops[$shop_id]["childGroups"][] = $line_data;
        $shops[$shop_id]["totalLines"]++;
        $shops[$shop_id]["totalItems"] += $qty;
        $shops[$shop_id]["checkedLines"]++;
        $shops[$shop_id]["checkedItems"] += $qty;
    }

    // BUILD FULL RESPONSE (MATCHES EXACT JSON)
    $response = [
        "success" => true,
        "data" => [
            "extra" => [
                "mergeReport_determine_needMerge" => "false"
            ],
            "cartGroup" => [
                "groupType" => "CART",
                "childGroups" => [
                    [
                        "groupType" => "VALID",
                        "childGroups" => array_values($shops),
                        "totalLines" => count($cart_items),
                        "checkedLines" => count($cart_items),
                        "totalItems" => count($cart_items),
                        "checkedItems" => count($cart_items)
                    ],
                    [
                        "groupType" => "INVALID",
                        "childGroups" => [],
                        "totalLines" => 0,
                        "checkedLines" => 0,
                        "totalItems" => 0,
                        "checkedItems" => 0
                    ]
                ],
                "totalLines" => count($cart_items),
                "checkedLines" => count($cart_items),
                "totalItems" => count($cart_items),
                "checkedItems" => count($cart_items),
                "validLines" => count($cart_items),
                "validItems" => count($cart_items),
                "cartActivities" => []
            ],
            "cartSummary" => [
                "tenantId" => null,
                "tenantIdLong" => null,
                "extra" => [
                    "hasSelectedGiftLineInventoryShortage" => "false"
                ],
                "summaryOriginalPrice" => $summary_original,
                "summaryRedemptionValue" => 0,
                "summaryEarnSCoinValue" => $summary_scoin,
                "summaryRealPrice" => $summary_real,
                "summaryItemDiscountPrice" => 0,
                "summaryShopDiscountPrice" => 0,
                "checkedItems" => $checked_items,
                "footerCheckoutHintList" => [
                    [
                        "tenantId" => null,
                        "tenantIdLong" => null,
                        "extra" => null,
                        "clickable" => true,
                        "checkoutGroupType" => "normal",
                        "buttonText" => "去结算",
                        "summaryRealPrice" => $summary_real,
                        "effectCheckedItems" => $checked_items
                    ]
                ],
                "summaryTipInfo" => null,
                "totalShippingFee" => null
            ]
        ]
    ];

    wp_send_json($response);
}

function product_has_warranty($product_name)
{
    $cart = WC()->cart;
    foreach ($cart->get_fees() as $fee) {
        if (
            str_contains($fee->name, 'Product Warranty') &&
            str_contains($fee->name, $product_name)
        ) {
            return true;
        }
    }
    return false;
}

function get_cart_count($data = [])
{
    // if (!is_user_logged_in()) {
    //     wp_send_json_error(['message' => 'Not logged in'], 401);
    // }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    $cartType  = isset($data['cartType']) ? max(1, (int) $data['cartType']) : null;
    $clientType    = isset($data['clientType'])   ? max(1, (int) $data['clientType'])   : null;

    $cart_count = WC()->cart->get_cart_contents_count();
    $response = [
        'data' => [
            'extra' => [
                'mergeReport_determine_needMerge' => 'false'
            ],
            'quantity' => $cart_count
        ],
        'success' => true
    ];

    wp_send_json($response);
}

function add_cart_item($data = [])
{
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $variation_id = isset($data['variation_id']) ? (int)$data['variation_id'] : 0;
    $qty = isset($data['quantity']) ? (int)$data['quantity'] : 1;
    $variation = (isset($data['variation']) && is_array($data['variation'])) ? $data['variation'] : [];
    $is_warranty99 = isset($data['is_warranty99']) ? (bool) $data['is_warranty99'] : true;
    $is_trade_in = isset($data['is_trade_in']) && $data['is_trade_in'] === true ? 'yes' : 'no';
    $awcdp_deposit_option = isset($data['awcdp_deposit_option']) && $data['awcdp_deposit_option'] === true ? 'yes' : 'no';

    $cart_item_data = [];
    $cart_item_data['trade_in'] = $is_trade_in;
    $cart_item_data['awcdp_deposit_option'] = $awcdp_deposit_option;

    // 🔥 Make AWCDP see it
    if ($awcdp_deposit_option === 'yes') {
        $_REQUEST['awcdp_deposit_option'] = 'yes';
        $_REQUEST['data']['awcdp_deposit_option'] = 'yes';
        $cart_item_data['deposit_option'] = 'deposit';
    }

    $_REQUEST['trade_in'] = $is_trade_in;
    $_REQUEST['data']['trade_in'] = $is_trade_in;


    if (is_user_logged_in()) {
        $is_warranty99 = false;
    }

    $wc_product_id = $variation_id > 0 ? $variation_id : $product_id;
    $product = wc_get_product($wc_product_id);

    $is_deposit = ($awcdp_deposit_option === 'yes');
    $validation_error = validate_mixed_cart_rules(
        WC()->cart->get_cart(),
        [
            'product' => $product,
            'is_deposit' => $is_deposit
        ]
    );

    if ($validation_error) {
        wp_send_json_error(['message' => $validation_error], 400);
    }


    if (!$product) {
        wp_send_json_error(['message' => 'Product not found'], 404);
    }

    $cart = WC()->cart;

    $item_key = $cart->add_to_cart($product_id, $qty, $variation_id, $variation, $cart_item_data);
    if (! $item_key) {
        $notices = wc_get_notices('error');
        if (! empty($notices)) {
            wp_send_json_error(['message' => wc_notice_to_plain_text($notices[0]['notice'])]);
        }
        wp_send_json_error(['message' => 'Failed to add to cart']);
    }

    $cart_item = $cart->get_cart_item($item_key);

    if (!$cart_item) {
        wp_send_json_error('Invalid cart item');
    }

    $cart_item['is_warranty99'] = $is_warranty99;
    $cart->cart_contents[$item_key] = $cart_item;

    $cart->calculate_totals();

    $cart_item = $cart->get_cart_item($item_key);

    $cart_totals = (float) ($cart->get_subtotal() + $cart->get_fee_total() + $cart->get_discount_total());
    if (!empty($cart_item['awcdp_deposit']) && is_array($cart_item['awcdp_deposit'])) {
        $cart_totals = $cart_item['awcdp_deposit']['deposit'] + $cart->get_fee_total();
    }

    wp_send_json_success([
        'item_key'       => $item_key,
        'cart_count'     => $cart->get_cart_contents_count(),
        // 'subtotal'       => (float) $cart->get_subtotal(),
        // 'fees_total'     => (float) $cart->get_fee_total(),
        // 'shipping_total' => (float) $cart->get_shipping_total(),
        // 'tax_total'      => (float) $cart->get_total_tax(),
        // 'total'          => (float) $cart->get_total('edit'),
        'cart_total'     => $cart_totals,
    ]);
}

function update_cart_item($data = [])
{
    $item_key = isset($data['cartLineId']) ? sanitize_text_field($data['cartLineId']) : '';
    $qty      = isset($data['quantity']) ? (int) $data['quantity'] : null;
    $is_warranty99 = isset($data['is_warranty99']) ? (bool) $data['is_warranty99'] : true;
    if (is_user_logged_in()) {
        $is_warranty99 = false;
    }

    if ($item_key === '') {
        wp_send_json_error(['message' => 'Missing cart item key']);
    }

    $cart = WC()->cart;
    $cart_item = $cart->get_cart_item($item_key);

    if (!$cart_item) {
        wp_send_json_error('Invalid cart item');
    }

    if (!empty($cart_item['awcdp_deposit']) && is_array($cart_item['awcdp_deposit'])) {
        $_REQUEST['awcdp_deposit'] = $cart_item['awcdp_deposit'];
        $_REQUEST['data']['awcdp_deposit'] = $cart_item['awcdp_deposit'];
    }

    if (!empty($cart_item['trade_in'])) {
        $_REQUEST['trade_in'] = $cart_item['trade_in'];
        $_REQUEST['data']['trade_in'] = $cart_item['trade_in'];
    }

    $cart_item['is_warranty99'] = $is_warranty99;
    $cart->cart_contents[$item_key] = $cart_item;

    if ($qty <= 0) {
        $cart->remove_cart_item($item_key);
    } else {
        $cart->set_quantity($item_key, $qty, false);
    }

    $cart->calculate_totals();

    if (!empty($cart_item['awcdp_deposit']) && is_array($cart_item['awcdp_deposit'])) {
        $cart_totals = $cart_item['awcdp_deposit']['deposit'] + $cart->get_fee_total();
    } else {
        $cart_totals = (float) ($cart->get_subtotal() + $cart->get_fee_total() + $cart->get_discount_total());
    }


    wp_send_json_success([
        'item_key'       => $item_key,
        'cart_count'     => $cart->get_cart_contents_count(),
        // 'subtotal'       => (float) $cart->get_subtotal(),
        // 'fees_total'     => (float) $cart->get_fee_total(),
        // 'shipping_total' => (float) $cart->get_shipping_total(),
        // 'tax_total'      => (float) $cart->get_total_tax(),
        // 'total'          => (float) $cart->get_total('edit'),
        'cart_total'     => $cart_totals,
    ]);
}

function delete_cart_items($data = [])
{
    $item_keys = isset($data['cartLineId']) && is_array($data['cartLineId'])
        ? array_map('sanitize_text_field', $data['cartLineId'])
        : [];

    if (empty($item_keys)) {
        wp_send_json_error(['message' => 'No cart item keys provided']);
    }

    $cart = WC()->cart;
    if (is_wp_error($cart)) {
        wp_send_json_error(['message' => $cart->get_error_message()]);
    }

    $deleted = 0;
    foreach ($item_keys as $key) {
        if ($cart->remove_cart_item($key)) {
            $deleted++;
        }
    }

    if ($deleted === 0) {
        wp_send_json_error(['message' => 'No cart items were deleted'], 400);
    }

    $cart->calculate_totals();

    wp_send_json_success([
        'cart_count'     => $cart->get_cart_contents_count(),
        // 'subtotal'       => (float) $cart->get_subtotal(),
        // 'fees_total'     => (float) $cart->get_fee_total(),
        // 'shipping_total' => (float) $cart->get_shipping_total(),
        // 'tax_total'      => (float) $cart->get_total_tax(),
        // 'total'          => (float) $cart->get_total('edit'),
        'cart_total'     => (float) ($cart->get_subtotal() + $cart->get_fee_total() + $cart->get_discount_total()),
    ]);
}

function apply_cart_coupon($data = [])
{
    $coupon_code = isset($data['coupon_code']) ? sanitize_text_field($data['coupon_code']) : '';

    if ($data['_method'] === 'DELETE') {
        //remove coupon
        $cart = WC()->cart;
        $cart->remove_coupon($coupon_code);
        $cart->calculate_totals();

        wp_send_json_success([
            'cart_count'     => $cart->get_cart_contents_count(),
            // 'subtotal'       => (float) $cart->get_subtotal(),
            // 'fees_total'     => (float) $cart->get_fee_total(),
            // 'shipping_total' => (float) $cart->get_shipping_total(),
            // 'tax_total'      => (float) $cart->get_total_tax(),
            // 'total'          => (float) $cart->get_total('edit'),
            'cart_total'     => (float) ($cart->get_subtotal() + $cart->get_fee_total() + $cart->get_discount_total()),
        ]);
    }

    if ($coupon_code === '') {
        wp_send_json_error(['message' => 'No coupon code provided']);
    }

    $cart = WC()->cart;

    if (! $cart->apply_coupon($coupon_code)) {
        $notices = wc_get_notices('error');
        if (! empty($notices)) {
            wp_send_json_error(['message' => wc_notice_to_plain_text($notices[0]['notice'])]);
        }
    }

    $cart->calculate_totals();

    wp_send_json_success([
        'cart_count'     => $cart->get_cart_contents_count(),
        // 'subtotal'       => (float) $cart->get_subtotal(),
        // 'fees_total'     => (float) $cart->get_fee_total(),
        // 'shipping_total' => (float) $cart->get_shipping_total(),
        // 'tax_total'      => (float) $cart->get_total_tax(),
        // 'total'          => (float) $cart->get_total('edit'),
        'cart_total'     => (float) ($cart->get_subtotal() + $cart->get_fee_total() + $cart->get_discount_total()),
    ]);
}

function validate_mixed_cart_rules($existing_cart, $new_item)
{
    $has_virtual = $has_physical = false;
    $has_deposit = $has_full = false;

    foreach ($existing_cart as $item) {
        $product = $item['data'];

        if ($product->is_virtual()) $has_virtual = true;
        else $has_physical = true;

        $is_deposit = (
            (!empty($item['awcdp_deposit_option']) && $item['awcdp_deposit_option'] === 'yes') ||
            (!empty($item['deposit_option']) && $item['deposit_option'] === 'deposit') ||
            (!empty($item['awcdp_deposit']))
        );

        if ($is_deposit) $has_deposit = true;
        else $has_full = true;
    }

    // Add incoming product
    if ($new_item['product']->is_virtual()) $has_virtual = true;
    else $has_physical = true;

    if ($new_item['is_deposit']) $has_deposit = true;
    else $has_full = true;

    // Final rules
    if ($has_virtual && $has_deposit) {
        return __("You cannot combine partial payment items with ESD products. Please adjust your cart to proceed.", "woocommerce");
    }

    if ($has_virtual && $has_physical) {
        return __("You cannot combine normal and ESD products together. Please adjust your cart to proceed.", "woocommerce");
    }

    if ($has_deposit && $has_full) {
        return __("You cannot combine partial payment items with normal products. Please adjust your cart to proceed.", "woocommerce");
    }

    return false;
}
