<?php

function get_cart($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    $cartType  = isset($data['cartType']) ? max(1, (int) $data['cartType']) : null;
    $clientType    = isset($data['clientType'])   ? max(1, (int) $data['clientType'])   : null;
    $divisionIds = isset($data['divisionIds']) ? (int) $data['divisionIds'] : null;

    $cart_items = WC()->cart->get_cart();

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
                "esdItem" => null,
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
                "isTradeIn" => null,
                "isPartialPayment" => null,
                "isProductWarranty" => null,
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

function get_cart_count($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

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
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $variation_id = isset($data['variation_id']) ? (int)$data['variation_id'] : 0;
    $qty = isset($data['quantity']) ? (int)$data['quantity'] : 1;
    $variation = (isset($data['variation']) && is_array($data['variation'])) ? $data['variation'] : [];

    if ($user_id <= 0 || $product_id <= 0 || $qty <= 0) {
        echo wp_json_encode([
            'success' => false,
            'message' => 'Invalid user_id, product_id, or quantity',
        ]);
        return;
    }

    wp_set_current_user($user_id);

    // Validate product exists
    $wc_product_id = $variation_id > 0 ? $variation_id : $product_id;
    $product = wc_get_product($wc_product_id);

    if (!$product) {
        echo wp_json_encode([
            'success' => false,
            'message' => 'Product not found',
        ]);
        return;
    }

    $cart = sh_init_wc_cart_for_user($user_id);

    $item_key = $cart->add_to_cart($product_id, $qty, $variation_id, $variation);

    if (! $item_key) {
        wp_send_json_error(['message' => 'Failed to add to cart']);
    }

    $cart->calculate_totals();

    wp_send_json_success([
        'user_id'         => $user_id,
        'item_key'        => $item_key,
        'cart_count'      => $cart->get_cart_contents_count(),
        'cart_total'      => strip_tags($cart->get_cart_total()),
    ]);
}


function update_cart_item($data = [])
{
    $user_id  = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $item_key = isset($data['item_key']) ? sanitize_text_field($data['item_key']) : '';
    $qty      = isset($data['quantity']) ? (int)$data['quantity'] : null;

    if ($user_id <= 0 || $item_key === '' || $qty === null) {
        echo wp_json_encode([
            'success' => false,
            'message' => 'Invalid user_id, item_key or quantity',
        ]);
        return;
    }

    if ($item_key === '') {
        wp_send_json_error(['message' => 'Missing cart item key']);
    }

    $cart = sh_init_wc_cart_for_user($user_id);
    if (is_wp_error($cart)) {
        wp_send_json_error(['message' => $cart->get_error_message()]);
    }

    if ($qty <= 0) {
        $cart->remove_cart_item($item_key);
    } else {
        $cart->set_quantity($item_key, $qty);
    }

    $cart->calculate_totals();

    wp_send_json_success([
        'user_id'         => $user_id,
        'cart_count'      => $cart->get_cart_contents_count(),
        'cart_total'      => strip_tags($cart->get_cart_total()),
    ]);
}

function delete_cart_items($data = [])
{
    $user_id   = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $item_keys = isset($data['item_keys']) && is_array($data['item_keys'])
        ? array_map('sanitize_text_field', $data['item_keys'])
        : [];

    if ($user_id <= 0 || empty($item_keys)) {
        echo wp_json_encode([
            'success' => false,
            'message' => 'Invalid user_id or item_keys',
        ]);
        return;
    }

    if (empty($item_keys)) {
        wp_send_json_error(['message' => 'No cart item keys provided']);
    }

    $cart = sh_init_wc_cart_for_user($user_id);
    if (is_wp_error($cart)) {
        wp_send_json_error(['message' => $cart->get_error_message()]);
    }

    $deleted = 0;
    foreach ($item_keys as $key) {
        if ($cart->remove_cart_item($key)) {
            $deleted++;
        }
    }

    $cart->calculate_totals();

    wp_send_json_success([
        'user_id'         => $user_id,
        'deleted'         => $deleted,
        'cart_count'      => $cart->get_cart_contents_count(),
        'cart_total'      => strip_tags($cart->get_cart_total()),
    ]);
}

/**
 * Bootstraps WooCommerce session, customer, and cart for a given user.
 * Returns WC_Cart instance ready to use OR WP_Error.
 */
function sh_init_wc_cart_for_user($user_id)
{
    if ($user_id <= 0 || ! get_user_by('id', $user_id)) {
        return new WP_Error('invalid_user', 'Invalid user ID');
    }

    // Set user + login (for this request)
    wp_set_current_user($user_id);
    // wp_set_auth_cookie($user_id, true);

    if (! did_action('init')) {
        do_action('init');
    }

    if (function_exists('WC')) {
        WC()->frontend_includes();

        // Session
        if (null === WC()->session || ! WC()->session instanceof WC_Session_Handler) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        // Customer
        if (null === WC()->customer) {
            WC()->customer = new WC_Customer($user_id, true);
        }

        // Cart
        if (null === WC()->cart) {
            WC()->cart = new WC_Cart();
        }

        // Load from session
        WC()->cart->get_cart_from_session();

        return WC()->cart;
    }

    return new WP_Error('no_wc', 'WooCommerce not loaded');
}
