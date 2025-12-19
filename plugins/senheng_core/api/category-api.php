<?php

function pampas_router($data = [])
{
    $pampasCall = isset($data['pampasCall']) ? sanitize_text_field($data['pampasCall']) : '';

    $consumer_key    = isset($data['consumer_key']) ? sanitize_text_field($data['consumer_key']) : '';
    $consumer_secret = isset($data['consumer_secret']) ? sanitize_text_field($data['consumer_secret']) : '';

    if (empty($consumer_key) || empty($consumer_secret)) {
        wp_send_json_error(['message' => 'Missing consumer_key or consumer_secret'], 401);
    }

    $user_id = validate_woocommerce_api_key($consumer_key, $consumer_secret);

    if (!$user_id) {
        wp_send_json_error(['message' => 'Invalid consumer_key or consumer_secret'], 401);
    }

    // Optional: set the current user for later capability checks
    wp_set_current_user($user_id);

    switch ($pampasCall) {
        case 'search.category.listing':
            get_category_listing($data);
            break;

        case 'senHeng.member.voucher':
            senheng_member_voucher($data);
            break;

        default:
            wp_send_json_error(['message' => 'Invalid pampasCall'], 400);
    }
    exit;
}

function get_category_listing($data = [])
{
    // if (! is_user_logged_in()) {
    //     wp_send_json_error(['message' => 'Not logged in'], 401);
    // }
    $jsonReq = $data['data'];
    $jsonReq = json_decode($jsonReq, true);
    // Params from request
    $frontCategoryId = isset($jsonReq['frontCategoryId']) ? (int) $jsonReq['frontCategoryId'] : 0;
    $page_size       = isset($jsonReq['pageSize']) ? max(1, (int) $jsonReq['pageSize']) : 20;
    $page_no         = isset($jsonReq['pageNo'])   ? max(1, (int) $jsonReq['pageNo'])   : 1;
    $in_stock        = isset($jsonReq['inStock'])  ? (int) $jsonReq['inStock']          : 1;
    $tenant_id       = isset($jsonReq['tenantId']) ? (int) $jsonReq['tenantId']         : 1;
    $pampasCall      = isset($data['pampasCall']) ? sanitize_text_field($data['pampasCall']) : '';

    if ($pampasCall !== 'search.category.listing') {
        wp_send_json_error(['message' => 'Invalid pampasCall'], 400);
    }

    // Get category term (assuming WooCommerce product_cat)
    $category = $frontCategoryId ? get_term($frontCategoryId, 'product_cat') : null;
    if (! $category || is_wp_error($category)) {
        echo wp_json_encode([
            'success' => false,
            'message' => 'Invalid frontCategoryId',
        ]);
        return;
    }

    // ==========================
    // Query products in category
    // ==========================
    $tax_query = [
        [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $frontCategoryId,
        ],
    ];

    $meta_query = [];
    if ($in_stock === 1) {
        $meta_query[] = [
            'key'   => '_stock_status',
            'value' => 'instock',
        ];
    }

    $query_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $page_size,
        'paged'          => $page_no,
        'tax_query'      => $tax_query,
        'meta_query'     => $meta_query,
    ];

    $q = new WP_Query($query_args);

    $total_products = (int) $q->found_posts;
    $products_data  = [];
    $product_ids    = [];

    // NEW: will hold brand facets in the same shape as your JSON
    // [ brand_term_id => [ 'key' => int, 'name' => string, 'count' => int, 'extra' => ['img' => url] ] ]
    $brands_map = [];

    $shop_name = get_bloginfo('name');
    $shop_url  = wc_get_page_permalink('shop');

    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $product_id = get_the_ID();
            $product_ids[] = $product_id;

            $product = wc_get_product($product_id);
            if (! $product) {
                continue;
            }

            // Basic fields
            $name   = $product->get_name();
            $slug   = $product->get_slug();
            $status = $product->get_status() === 'publish' ? 1 : 0;

            // Image
            $image_url = get_the_post_thumbnail_url($product_id, 'medium');
            if (! $image_url) {
                $image_url = wc_placeholder_img_src();
            }

            // Price -> cents style (e.g. 339900)
            $price_raw  = (float) wc_get_price_to_display($product);
            $price_int  = (int) round($price_raw * 100);
            $low_price  = $product->get_max_purchase_quantity();
            $high_price = $product->get_min_purchase_quantity();

            // Stock
            $in_stock_flag = $product->is_in_stock() ? 1 : 0;

            // Category tree list (IDs + names)
            $product_cats = get_the_terms($product_id, 'product_cat');
            $category_ids = [];
            $category_name_map = [];
            $second_level_name = '';

            if (! empty($product_cats) && ! is_wp_error($product_cats)) {
                // Just pick first category as "main"
                $main_cat = $product_cats[0];

                // Build ancestors + current
                $ancestors = array_reverse(get_ancestors($main_cat->term_id, 'product_cat'));
                $path_ids  = array_merge($ancestors, [$main_cat->term_id]);

                foreach ($path_ids as $cid) {
                    $t = get_term($cid, 'product_cat');
                    if ($t && ! is_wp_error($t)) {
                        $category_ids[] = (int) $t->term_id;
                        $category_name_map[$t->term_id] = $t->name;
                    }
                }

                // Use immediate category name as secondLevelCategoryName (or last in path)
                $second_level_name = $main_cat->name;
            }

            // ==========================
            // BRAND HANDLING (product_brand taxonomy)
            // ==========================
            $brand_name = 'NO BRAND';

            // Get full term objects so we can grab ID + name
            $brand_terms = wc_get_product_terms($product_id, 'product_brand', ['fields' => 'all']);

            if (! empty($brand_terms) && ! is_wp_error($brand_terms)) {
                // Assuming 1 brand per product – take the first
                $brand_term = $brand_terms[0];
                $brand_name = $brand_term->name;
                $brand_id   = (int) $brand_term->term_id;

                // If this brand not yet in map, initialize it
                if (! isset($brands_map[$brand_id])) {

                    // Try to get a brand image from term meta, e.g. WooCommerce brand thumbnail
                    $thumb_id = get_term_meta($brand_id, 'thumbnail_id', true);
                    $img_url  = $thumb_id ? wp_get_attachment_url($thumb_id) : '';

                    $brands_map[$brand_id] = [
                        'key'   => $brand_id,
                        'name'  => $brand_name,
                        // if you want it exactly like your sample (count: null), set this to null
                        // 'count' => null,
                        'count' => 0,
                        'extra' => [
                            'img' => $img_url,
                        ],
                    ];
                }

                // Increase count of products for this brand
                if ($brands_map[$brand_id]['count'] !== null) {
                    $brands_map[$brand_id]['count']++;
                }
            }


            // Build one product entry like your sample
            $products_data[] = [
                'id'                       => $product_id,
                'itemUrlCode'              => $slug,
                'name'                     => $name,
                'mainImage'                => $image_url,
                'status'                   => $status,
                'type'                     => 1,
                'businessType'             => 1,
                'highPrice'                => $high_price,
                'lowPrice'                 => $low_price,
                'inStock'                  => $in_stock_flag,
                'platformScoinPercentage'  => null,
                'merchantScoinPercentage'  => null,
                'secondLevelCategoryName'  => $second_level_name,
                'conditionJsonList'        => [],
                'shopName'                 => $shop_name,
                'brandName'                => $brand_name ?: 'NO BRAND',
                'categoryTreeList'         => $category_ids,
                'categoryTreeListName'     => $category_name_map,
                'tenantId'                 => $tenant_id,
                'shopId'                   => get_current_blog_id(),
                'productUrl'               => get_permalink($product_id),
                'shopUrl'                  => $shop_url,
                'saleQuantity'             => null,
            ];
        }
        wp_reset_postdata();
    }

    // ==========================
    // Build "attributes" facet like your response
    // (grouped by Woo product attributes)
    // ==========================
    $attribute_counts = []; // [ group => [ name => count ] ]

    if (! empty($product_ids)) {
        foreach ($product_ids as $pid) {
            $prod = wc_get_product($pid);
            if (! $prod) {
                continue;
            }
            $attrs = $prod->get_attributes();

            foreach ($attrs as $attr) {
                if (! $attr->get_taxonomy()) {
                    continue;
                }
                $taxonomy   = $attr->get_taxonomy();              // e.g. pa_color
                $group_name = wc_attribute_label($taxonomy);      // e.g. Color
                $terms      = wc_get_product_terms($pid, $taxonomy, ['fields' => 'names']);

                foreach ($terms as $term_name) {
                    if (! isset($attribute_counts[$group_name])) {
                        $attribute_counts[$group_name] = [];
                    }
                    if (! isset($attribute_counts[$group_name][$term_name])) {
                        $attribute_counts[$group_name][$term_name] = 0;
                    }
                    $attribute_counts[$group_name][$term_name]++;
                }
            }
        }
    }

    $attributes_arr = [];
    foreach ($attribute_counts as $group => $names) {
        $name_counts = [];
        foreach ($names as $name => $count) {
            $name_counts[] = [
                'name'  => $name,
                'count' => $count,
            ];
        }
        $attributes_arr[] = [
            'group'         => $group,
            'nameAndCounts' => $name_counts,
        ];
    }

    // ==========================
    // frontCategories: child categories under current category
    // ==========================
    $front_categories = [];
    $children = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $frontCategoryId,
    ]);

    if (! empty($children) && ! is_wp_error($children)) {
        foreach ($children as $child) {
            $front_categories[] = [
                'key'  => (int) $child->term_id,
                'name' => $child->name,
            ];
        }
    }

    // ==========================
    // breadCrumbs: ancestors + current category
    // ==========================
    $bread_crumbs = [];
    $ancestors = array_reverse(get_ancestors($category->term_id, 'product_cat'));
    foreach ($ancestors as $aid) {
        $t = get_term($aid, 'product_cat');
        if ($t && ! is_wp_error($t)) {
            $bread_crumbs[] = [
                'id'   => (int) $t->term_id,
                'name' => $t->name,
            ];
        }
    }
    // add current category
    $bread_crumbs[] = [
        'id'   => (int) $category->term_id,
        'name' => $category->name,
    ];

    // ==========================
    // chosen: current category
    // ==========================
    $chosen = [
        [
            'type' => 3,
            'key'  => (int) $category->term_id,
            'name' => $category->name,
        ],
    ];

    // Turn brands map into a simple indexed array
    $brands = array_values($brands_map);

    // ==========================
    // Final response – SAME SHAPE as your sample
    // ==========================
    $response = [
        'success' => true,
        'result'  => [
            'entities' => [
                'total' => $total_products,
                'data'  => $products_data,
                'empty' => ($total_products === 0),
            ],
            'attributes'      => $attributes_arr,
            'brands'          => $brands,           // << now populated like your JSON
            'frontCategories' => $front_categories,
            'breadCrumbs'     => $bread_crumbs,
            'chosen'          => $chosen,
        ],
    ];

    echo wp_json_encode($response);
}

function senheng_member_voucher($data = [])
{
    // if (! is_user_logged_in()) {
    //     wp_send_json_error(['message' => 'Not logged in'], 401);
    // }

    // Decode inner JSON
    $jsonReqRaw = isset($data['data']) ? $data['data'] : '{}';
    $jsonReq    = json_decode($jsonReqRaw, true);

    if (! is_array($jsonReq)) {
        wp_send_json_error(['message' => 'Invalid data payload'], 400);
    }

    $email      = isset($jsonReq['email'])     ? sanitize_email($jsonReq['email'])               : '';
    $event_id   = isset($jsonReq['eventId'])   ? sanitize_text_field($jsonReq['eventId'])        : '';
    $full_name  = isset($jsonReq['fullName'])  ? sanitize_text_field($jsonReq['fullName'])       : '';
    $mobile     = isset($jsonReq['mobile'])    ? sanitize_text_field($jsonReq['mobile'])         : '';
    $senheng_id = isset($jsonReq['senhengId']) ? (int) $jsonReq['senhengId']                     : 0;

    if (empty($email) || empty($event_id)) {
        wp_send_json_error(['success' => false, 'code' => "X0007"], 400);
    }

    echo wp_json_encode([
        'success' => true,
        'code'  => "X0000",
    ]);
}

function validate_woocommerce_api_key($consumer_key, $consumer_secret)
{
    global $wpdb;

    if (empty($consumer_key) || empty($consumer_secret)) {
        return false;
    }

    // Fetch the key row INCLUDING consumer_secret (only one query needed)
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT key_id, user_id, permissions, consumer_secret
            FROM {$wpdb->prefix}woocommerce_api_keys
            WHERE consumer_key = %s
            LIMIT 1
            ",
            wc_api_hash($consumer_key)
        )
    );

    // No key found
    if (! $row) {
        return false;
    }

    // Check permissions
    if (! in_array($row->permissions, ['read', 'write', 'read_write'], true)) {
        return false;
    }

    // Check consumer secret (stored as plain text, e.g. cs_xxxxxxx)
    if (! hash_equals($row->consumer_secret, $consumer_secret)) {
        return false;
    }

    // Return the user_id for this key
    return (int) $row->user_id;
}
