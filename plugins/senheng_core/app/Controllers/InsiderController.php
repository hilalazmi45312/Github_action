<?php

/**
 * Enqueue Insider integration scripts
 */
function enqueue_insider_scripts()
{
    wp_enqueue_script(
        'insider-integration',
        SENHENG_CORE_ASSETS_URL . 'js/insider/integration.js',
        array('jquery'),
        null,
        true
    );

    // Get page type information
    $page_type = get_page_type();
    if (!is_array($page_type)) {
        $page_type = [];
    }

    //check if have utm_source
    if (isset($_GET['utm_source'])) {
        $utm_source = $_GET['utm_source'];
        $utm_medium = $_GET['utm_medium'] ?? null;
        $utm_campaign = $_GET['utm_campaign'] ?? null;
        $full_utm = $utm_source . ($utm_medium ? ' - ' . $utm_medium : '') . ($utm_campaign ? ' - ' . $utm_campaign : '');
        setcookie('utm_info', $full_utm, time() + 86400, "/"); // 1-day expiration
    }

    // Pass PHP variables to JavaScript
    $insider_config = insider_config();
    $insider_data = array(
        'partnerName' => $insider_config['partner_name'],
        'partnerId' => $insider_config['partner_id'],
        'channel' => $insider_config['channel'],
        'pageType' => $page_type ? $page_type['type'] : null,
        'categoryName' => $page_type ? ($page_type['category_name'] ?? null) : null,
        'userData' => is_user_logged_in() ? get_insider_user_data() : null,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'hasLoginCookie' => isset($_COOKIE['insider_login_event']),
        'hasRegisterCookie' => isset($_COOKIE['insider_register_event']),
        'registeredUserId' => isset($_COOKIE['insider_register_event']) ? intval($_COOKIE['insider_register_event']) : null
    );
    wp_localize_script('insider-integration', 'insiderData', $insider_data);
}
add_action('wp_enqueue_scripts', 'enqueue_insider_scripts');

/**
 * Get user data for Insider
 */
function get_insider_user_data()
{
    $insider_config = insider_config();
    $user = wp_get_current_user();
    $user_id = $user->ID;

    // Get first name and last name from user meta
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);

    $name = $first_name && $last_name ? "$first_name $last_name" : ($first_name ?: ($last_name ?: $user->display_name));

    // Get billing phone from user meta, default to empty string if not found
    $phone_number = get_user_meta($user_id, 'billing_phone', true) ?: '';

    return array(
        'user_id'    => get_user_meta($user_id, 'cust_idmapping', true) ?: null,
        'name'       => get_user_meta($user_id, 'cust_name', true) ?: $name,
        'email'      => get_user_meta($user_id, 'cust_email', true) ?: $user->user_email,
        'phone_number' => get_user_meta($user_id, 'cust_contact', true) ?: $phone_number,
        'channel'    => $insider_config['channel'] ?? 'WEB',
        'gdpr_optin' => true,
        'email_optin' => true,
        'p1_number_latest' => get_user_meta($user_id, 'cust_p1no', true) ?: ''
    );
}

/**
 * Get page type information
 */
function get_page_type()
{
    if (is_front_page() || is_home()) {

        //unset the cookie after use
        if (isset($_COOKIE['source_product_clicked'])) {
            unset($_COOKIE['source_product_clicked']);
            setcookie('source_product_clicked', '', time() - 3600, '/');
        }

        return ['type' => 'home'];
    } elseif (is_product_category()) {
        $category = get_queried_object();
        $ancestors = get_ancestors($category->term_id, 'product_cat');

        $ancestors = array_reverse($ancestors);

        $names = [];

        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            if (! is_wp_error($ancestor)) {
                $names[] = $ancestor->name;
            }
        }

        $names[] = $category->name;

        return [
            'type' => 'category',
            'category_hierarchy' => $names,
            'category_name'      => implode(', ', $names)
        ];
    }
    // elseif (is_product()) {
    // return ['type' => 'product'];
    // }
    elseif (is_cart()) {
        // return ['type' => 'cart'];
    } elseif (is_checkout() && !is_wc_endpoint_url('order-received')) {
        return ['type' => 'checkout'];
    }
    // elseif (is_wc_endpoint_url('order-received')) {
    //     return ['type' => 'purchase'];
    // } 
    // elseif (is_page()) {
    //     return ['type' => 'page'];
    // } elseif (is_tag()) {
    //     return ['type' => 'tag'];
    // } 
    elseif (is_search()) {
        return ['type' => 'search'];
    } elseif (is_404()) {
        return ['type' => '404'];
    } elseif (is_page()) {
        return ['type' => ''];
    }
    // return ['type' => 'other'];
    return ['type' => ''];
}

/************************************************/
/************ BELOW IS COMPILE CODE ************/
/***********************************************/

/******************************************/
/***** Define AJAX URL for JavaScript *****/
/******************************************/
function add_ajax_url_to_js()
{
?>
    <script>
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    </script>
    <?php
}
add_action('wp_head', 'add_ajax_url_to_js');

/************************************************/
/***** Get WooCommerce Product Data by AJAX *****/
/************************************************/
function get_wc_products()
{
    if (isset($_POST['remove_item'])) {
        $cart_item_key = $_POST['remove_item'];
        $cart_item = get_transient('removed_cart_item_' . $cart_item_key);
        if ($cart_item) {
            if (isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0) {
                $product_id = $cart_item['variation_id'];
            } else {
                $product_id = $cart_item['product_id'];
            }
            $quantity = (int) $cart_item['quantity'];

            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(['message' => 'Invalid product']);
                return;
            }

            $product_data = extract_product_data($product);
            $product_data['quantity'] = $quantity;
            wp_send_json_success($product_data);
            return;
        } else {
            // If transient data is not available, send a success response with minimal data
            // This prevents the AJAX call from failing and interfering with cart removal
            wp_send_json_success(['message' => 'Cart item data not available']);
            return;
        }
    }


    // Handle cart updates (increase or decrease quantity)
    if (isset($_POST['cart_key'])) {

        $cart_key = sanitize_text_field($_POST['cart_key']);
        $cart = WC()->cart->get_cart();

        if (!isset($cart[$cart_key])) {
            wp_send_json_error(['message' => 'Cart item not found']);
            return;
        }

        $item = $cart[$cart_key];
        $product_id = $item['variation_id'] ?: $item['product_id'];
        $quantity   = (int) $item['quantity'];

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Invalid product']);
            return;
        }

        $product_data = extract_product_data($product);
        $product_data['quantity'] = $quantity;

        wp_send_json_success($product_data);
        return;
    }

    $product_id = $_POST['product_id'] ?? wc_get_product_id_by_sku($_POST['sku_id'] ?? '');
    $product_id = intval($product_id);

    $variation_id = intval($_POST['variation_id'] ?? 0);
    $product = wc_get_product($variation_id ?: $product_id);

    if (!$product) {
        wp_send_json_error(['message' => 'Invalid product']);
    }

    $product_data = extract_product_data($product);
    wp_send_json_success($product_data);
}
add_action('wp_ajax_get_wc_products', 'get_wc_products');
add_action('wp_ajax_nopriv_get_wc_products', 'get_wc_products'); // Allow guests

/*********************************/
/***** Extract Product Data *****/
/********************************/
function extract_product_data($product)
{
    $variant = [];
    $sku_id = $product->get_sku();
    $product_id = $product->get_id();
    $product_name = $product->get_name();
    $parent_id = $product->get_parent_id();

    //calculation s-coin
    $s_coin_cashback = get_field('s_coin_value', $product_id);
    if (empty($s_coin_cashback) && $parent_id) {
        $s_coin_cashback = get_field('s_coin_value', $parent_id);
    }
    $user_id = get_current_user_id();
    // $memberships = wc_memberships_get_user_memberships($user_id);
    // $has_gold_membership = false;
    // foreach ($memberships as $membership) {
    //     $plan_slug = $membership->get_plan()->get_slug();
    //     if ($plan_slug === 'p1_membership_gold') {
    //         $has_gold_membership = true;
    //         break;
    //     }
    // }

    if ($parent_id) {
        $product_categories = wp_get_post_terms($parent_id, 'product_cat', array('fields' => 'names')) ?: [];
    } else {
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')) ?: [];
    }
    $product_price = $product->get_regular_price();
    if (!$product_price) {
        $product_price = $product->get_price();
    }
    $product_sale_price = $product->get_sale_price();
    if (!$product_sale_price) {
        $product_sale_price = $product_price;
    }

    //close due to cancel task on this
    // if ($has_gold_membership) {
    //     $s_coin_cashback = (float) $s_coin_cashback;
    //     $s_coin_cashback = $s_coin_cashback + (0.02 * (float) $product_sale_price); // 2% off when membership gold
    // }

    $s_coin_cashback = round((float) $s_coin_cashback, 2);

    $product_url = get_permalink($product_id);
    $product_image_url = $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '';
    $product_stock = $product->get_stock_quantity() !== null ? 1 : 0;
    $product_currency = get_woocommerce_currency();
    $average_rating = (float) $product->get_average_rating();
    $total_reviews = (int) $product->get_review_count();
    $product_color = $parent_id ? $product->get_attribute('pa_color') : '';
    $product_size = $product->get_attribute('pa_size');
    $variant_attributes = [];
    if ($product->is_type('variable')) {
        $default_attributes = $product->get_default_attributes();
        $variant_attributes = $default_attributes;
    } else if ($product->is_type('variation')) {
        $variant_attributes = $product->get_attributes();
    }
    // normalize keys (remove pa_, replace - with space, capitalize words)
    $variant_attributes = array_combine(
        array_map(
            fn($key) => ucwords(str_replace('-', ' ', preg_replace('/^pa_/', '', $key))),
            array_keys($variant_attributes)
        ),
        array_map(
            fn($value) => ucwords(str_replace('-', ' ', (string) $value)),
            array_values($variant_attributes)
        )
    );

    // convert into ["Key: Value", ...]
    $variant_attributes = array_map(
        fn($key, $value) => $key . ': ' . $value,
        array_keys($variant_attributes),
        $variant_attributes
    );

    //product description short
    // $product_description = wp_strip_all_tags($product->get_description());
    $product_description = wp_trim_words(wp_strip_all_tags($product->get_description()), 30, '...');

    $currency = get_woocommerce_currency();
    $wishlist = 0;
    $quantity = 1;
    $add_to_cart_id = $product_id;
    $source_product_clicked = isset($_COOKIE['source_product_clicked']) ? $_COOKIE['source_product_clicked'] : null;
    $source = $_COOKIE['globalPrevPage'] ?? null;

    $full_product_data = [
        'sku_id' => (string) $sku_id,
        'product_id' => (string) $product_id,
        'product_name' => (string) $product_name,
        'product_price' => (float) $product_price,
        'product_sale_price' => (float) $product_sale_price,
        'product_url' => (string) $product_url,
        'product_image_url' => (string) $product_image_url,
        'product_stock' => (string) $product_stock,
        'product_currency' => (string) $product_currency,
        'product_description' => (string) $product_description,
        'category_name' => $product_categories,
        // 'variant' => $variant,
        'coin_cashback' => 0,
        'channel' => get_userAgent(),
        'store_name' => get_store_name(),
        'source_1' => (string) $source_product_clicked,
        'source_2' => null,
        'source_3' => null,
        'currency' => $currency,
        'wishlist' => $wishlist,
        'quantity' => $quantity,
        'add_to_cart_id' => $add_to_cart_id,
        'average_rating' => $average_rating,
        'total_reviews' => $total_reviews,
        'product_color' => $product_color,
        'product_size' => $product_size,
        's_coin_cashback' => $s_coin_cashback,
        'src' => '',
        'variant' => !empty($variant_attributes) ? $variant_attributes : '',
        'source' => (string) $source,
    ];

    return $full_product_data;
}

function get_store_name()
{
    return get_bloginfo('name');
}

/**************************************/
/***** Check Product Wishlist Fx ******/
/*************************************/
function check_product_in_wishlist($product_id)
{
    global $wpdb;

    // Get the current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false; // User not logged in
    }

    // Query to check if the product exists in the wishlist
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT count(*)
        FROM {$wpdb->prefix}users a
        LEFT JOIN {$wpdb->prefix}woodmart_wishlists b ON b.user_id = a.ID
        LEFT JOIN {$wpdb->prefix}woodmart_wishlist_products c ON c.wishlist_id = b.ID
        WHERE a.ID = %d",
        $user_id
    ));
    return ($exists > 0);
}

/**************************************/
/***** Get WooCommerce Cart Data *****/
/*************************************/
function get_wc_cart_data()
{
    $cart = WC()->cart->get_cart();
    $sub_total = WC()->cart->get_cart_contents_total();
    $shipping_total = WC()->cart->get_shipping_total();
    $total_price = $sub_total + $shipping_total;
    $currency = get_woocommerce_currency();
    $coupons = WC()->cart->get_applied_coupons();
    $is_coupon_applied = !empty($coupons) ? 1 : 0;
    $products = [];
    foreach ($cart as $cart_item_key => $cart_item) {
        $sku_id = $cart_item['data']->get_sku();
        $product_id = wc_get_product_id_by_sku($sku_id);
        if (!$product_id) {
            $product_id = $cart_item['product_id'];
        }
        $quantity = $cart_item['quantity'];
        $product = wc_get_product($product_id);

        if ($product) {
            $product_data = extract_product_data($product);

            $products[] = array(
                'id' => $product_data['product_id'],
                'name' => $product_data['product_name'],
                'taxonomy' => $product_data['category_name'],
                'unit_price' => $product_data['product_price'],
                'unit_sale_price' => $product_data['product_sale_price'],
                'quantity' => $quantity,
                'url' => $product_data['product_url'],
                'product_image_url' => $product_data['product_image_url'],
                'custom' => [
                    'add_to_cart_id' => $product_data['product_id'],
                    'sku_id' => $product_data['sku_id'],
                    'channel' => $product_data['channel'],
                    'voucher_redemption' => $is_coupon_applied,
                    'store_name' => $product_data['store_name'],
                    'variant' => $product_data['variant'],
                    'coin_cashback' => $product_data['coin_cashback'],
                    'source' => $product_data['source'],
                ]
            );
        }
    }
    $response = [
        'products' => $products,
        'total_price' => $total_price,
        'currency' => $currency,
    ];
    wp_send_json_success($response);
}
add_action('wp_ajax_get_wc_cart_data', 'get_wc_cart_data');
add_action('wp_ajax_nopriv_get_wc_cart_data', 'get_wc_cart_data'); // Allow guests

/******************************************/
/***** Get Last Visited Category URL *****/
/*****************************************/
function track_last_category_cookie()
{
    if (is_product_category()) {
        $category_viewed_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        setcookie('category_viewed_url', $category_viewed_url, time() + 86400, "/"); // 1-day expiration
    }
}
add_action('template_redirect', 'track_last_category_cookie');

/********************************************/
/***** Get Last Viewed Category Cookie *****/
/*******************************************/
function get_last_viewed_category_cookie()
{
    if (!empty($_COOKIE['category_viewed_url'])) {
        return $_COOKIE['category_viewed_url'];
    }
    return null;
}





/* Start of Event Tracking */


/***************************************/
/***** Track Product Viewed Event *****/
/**************************************/
function product_viewed()
{
    if (is_product()) {

        $product_id = get_the_ID();
        $product = wc_get_product($product_id);

        if ($product && $product->is_type('simple')) {
            $product_data = extract_product_data($product);
            if (isset($_COOKIE['utm_info'])) {
                $utm_info = $_COOKIE['utm_info'];
                $utm_info = explode(' - ', $utm_info);
                $product_data['source_1'] = $utm_info[0];
                if (isset($utm_info[1])) {
                    $product_data['source_2'] = $utm_info[1];
                }
                if (isset($utm_info[2])) {
                    $product_data['source_3'] = $utm_info[2];
                }
            }

    ?>
            <script>
                window.InsiderQueue = window.InsiderQueue || [];
                let productViewed = {
                    type: 'product',
                    value: {
                        "id": "<?php echo esc_js($product_data['sku_id']); ?>",
                        "name": "<?php echo esc_js($product_data['product_name']); ?>",
                        "taxonomy": <?php echo json_encode($product_data['category_name']); ?>,
                        "unit_price": <?php echo (float) $product_data['product_price']; ?>,
                        "unit_sale_price": <?php echo (float) $product_data['product_sale_price']; ?>,
                        "url": "<?php echo esc_url($product_data['product_url']); ?>",
                        "product_image_url": "<?php echo esc_url($product_data['product_image_url']); ?>",
                        // "quantity": 1,
                        // "stock": <?php echo (int) $product_data['product_stock']; ?>,
                        "custom": {
                            // "sku_id": "<?php echo esc_js($product_data['sku_id']); ?>",
                            "channel": "<?php echo esc_js($product_data['channel']); ?>",
                            "store_name": "<?php echo esc_js($product_data['store_name']); ?>",
                            "variant": <?php echo json_encode($product_data['variant']); ?>,
                            "s_coin_cashback": <?php echo json_encode($product_data['s_coin_cashback']); ?>,
                            "source": "<?php echo esc_js($product_data['source']); ?>",
                        }
                    }
                };
                console.log('📡 Product Viewed Event Fired:', productViewed);
                window.InsiderQueue.push(productViewed);
                window.InsiderQueue.push({
                    type: 'currency',
                    value: "<?php echo esc_js($product_data['currency']); ?>"
                });
                window.InsiderQueue.push({
                    type: 'init'
                });

                // unset the cookie after use
                if (document.cookie.indexOf('source_product_clicked') !== -1) {
                    document.cookie = 'source_product_clicked=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                }
            </script>
<?php
        }
    }
}
add_action('wp_footer', 'product_viewed');

function get_product_sourced()
{
    // Detect from Brands page
    if (is_page('brands')) {
        $source_product_clicked = 'Landing Page - Brands';
        setcookie('source_product_clicked', $source_product_clicked, time() + 86400, "/");
    }

    // Detect from product category page
    if (is_product_category()) {
        $category = get_queried_object();
        if ($category) {
            $category_name = $category->name;
            if (isset($_COOKIE['from_home_page'])) {
                $category_name = 'Home Page - ' . $category_name;
                unset($_COOKIE['from_home_page']);
                setcookie('from_home_page', '', time() - 3600, '/');
            } else {
                $category_name = 'Landing Page - ' . $category_name . ' Supplies';
            }
            $source_product_clicked = $category_name;
            setcookie('source_product_clicked', $source_product_clicked, time() + 86400, "/");
        }
    }

    // FOR BEST SELLING PRODUCTS
    echo "<script>
    jQuery(function($) {
        // Use event delegation for dynamically-loaded tab content
        $(document).on('click', 'div[data-atts][data-atts*=\"bestselling\"] .product, div[data-atts][data-atts*=\"bestselling\"] .woocommerce-loop-product__link', function () {
            Cookies.set('source_product_clicked', 'Homepage - Best Seller Products', { expires: 1, path: '/' });
        });
    });
    </script>";


    // FOR TOP SELLING AND FEATURED PRODUCTS
    echo "<script>
    jQuery(function($) {
        // Loop through all Elementor-wrapped widget sections
        $('.elementor-widget-wrap.elementor-element-populated').each(function () {
            const \$widget = $(this);
            
            // Get the title from inside this section
            const title = \$widget.find('.woodmart-title-container.title.wd-fontsize-l').text().toLowerCase().trim();
            let widgetType = '';
            if (title.includes('top selling')) {
                widgetType = 'Top Selling Products';
            } else if (title.includes('featured')) {
                widgetType = 'Featured Products';
            }
    
            // Only continue if we recognize the widget
            if (widgetType !== '') {
                // Attach click tracking to products in this widget
                \$widget.find('.product_list_widget li a').on('click', function () {
                    Cookies.set('source_product_clicked', 'Homepage - ' + widgetType, { expires: 1, path: '/' });
                });
            }
        });
    });
    </script>";
}
add_action('wp_footer', 'get_product_sourced');

/*******************************************************/
/***** Capture Product Removed from Cart Function *****/
/*****************************************************/
function capture_removed_cart_item_data($cart_item_key, $cart)
{
    // Get the cart item data
    $cart_item = $cart->get_cart_item($cart_item_key);

    if ($cart_item) {
        // Store the cart item data in a transient (or session)
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'] ?? 0;
        $quantity = $cart_item['quantity'];

        $product_data = [
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $quantity,
        ];

        set_transient('removed_cart_item_' . $cart_item_key, $product_data, 60); // Expires in 60 seconds
    }
}
add_action('woocommerce_remove_cart_item', 'capture_removed_cart_item_data', 10, 2);

/******************************************/
/***** Track Checkout Completed Event *****/
/******************************************/
function checkout_completed()
{
    if (!is_order_received_page()) {
        return;
    }
    // // Get the order ID from the query parameter
    $order_id = get_query_var('order-received');

    if (!$order_id) {
        return;
    }

    // Get the order object
    $order = wc_get_order($order_id);
    $order_data = $order->get_data();
    // Ensure order exists
    if (!$order) {
        return;
    }

    if (! $order->has_status(array('processing', 'completed'))) {
        return;
    }

    // Get applied coupons
    $coupons = $order->get_used_coupons();
    $is_coupon_applied = !empty($coupons) ? true : false;
    $discount_amount = $order->get_discount_total();

    $sub_total = $order->get_subtotal();
    $shipping_total = $order->get_shipping_total();
    $total = $order->get_total();

    $category_viewed_url = get_last_viewed_category_cookie() ?: '';

    $purchase_data = [
        'type'  => 'purchase',
        'value' => [
            'order_id' => (string) $order->get_id(),
            'sub_total' => (float) $sub_total,
            'shipping_total' => (float) $shipping_total,
            'total'    => (float) $total,
            'items'    => []
        ]
    ];

    foreach ($order_data['line_items'] as $item) {
        // $product = extract_product_data(wc_get_product($item['product_id']));
        $product_obj = $item->get_product(); // Gets variation or simple product object
        if (!$product_obj) {
            continue;
        }
        $product = extract_product_data($product_obj);
        $is_wishlist = check_product_in_wishlist($product['product_id']);
        if ($product) {
            $purchase_data['value']['items'][] = [
                'id' => (string) $product['product_id'],
                'name' => $product['product_name'],
                'taxonomy' => $product['category_name'],
                'unit_price' => (float) $product['product_price'],
                'unit_sale_price' => (float) $product['product_sale_price'],
                'quantity' => $item['quantity'],
                'url' => $product['product_url'],
                'product_image_url' => $product['product_image_url'],
                'custom' => [
                    'add_to_cart_id' => (string) $product['product_id'],
                    'sku_id' => $product['sku_id'],
                    'channel' => $product['channel'],
                    'store_name' => $product['store_name'],
                    'variant' => $product['variant'],
                    's_coin_cashback' => $product['s_coin_cashback'],
                    'source' => $product['source'],
                    'category_viewed_url' => $category_viewed_url,
                    'order_amount' => (float) $total,
                    'payment_method' => $order_data['payment_method_title'],
                    'checkout_voucher' => $is_coupon_applied,
                    'voucher_value' => (float) $discount_amount,
                    'voucher_type' => 'voucher_code',
                    'number_of_products' => count($order_data['line_items']),
                    'wishlist' => $is_wishlist,
                ]
            ];
        }
    }
    // error_log('Purchase Data: ' . json_encode($purchase_data));

    echo "
    <script>
        window.InsiderQueue = window.InsiderQueue || [];
        let purchaseData = " . json_encode($purchase_data) . ";
        let productPurchaseEach = purchaseData.value.items;

        //FOR PURCHASE
        let purchase_items = [];
        productPurchaseEach.forEach(function(productLoop) {
            purchase_items.push({
                'id': productLoop.id,
                'name': productLoop.name,
                'taxonomy': productLoop.taxonomy,
                'unit_price': productLoop.unit_price,
                'unit_sale_price': productLoop.unit_sale_price,
                'url': productLoop.url,
                'product_image_url': productLoop.product_image_url,
                'quantity': productLoop.quantity,
                'custom': {
                    'channel': productLoop.custom.channel,
                    'store_name': productLoop.custom.store_name,
                    'variant': productLoop.custom.variant,
                    's_coin_cashback': productLoop.custom.s_coin_cashback,
                    'source': productLoop.custom.source
                }
            });
        });
        let purchase = {
            type: 'purchase',
            value: {
                'order_id': purchaseData.value.order_id,
                'total': purchaseData.value.total,
                'items': purchase_items
            }
        }
        setTimeout(() => {
            window.InsiderQueue.push(purchase);
            window.InsiderQueue.push({
                type: 'currency',
                value: '" . esc_js(get_woocommerce_currency()) . "'
            });
            window.InsiderQueue.push({
                type: 'init'
            });
            console.log('📡 Purchase Event Fired :', purchase);
        }, 3000); // Delay 10s for purchase event
    </script>
    ";
}
add_action('woocommerce_thankyou', 'checkout_completed', 20);

/**********************************/
/***** Track Cart Page Event *****/
/*********************************/
function track_cart_page()
{
    if (is_cart()) {
        $cart = WC()->cart->get_cart();
        $sub_total = WC()->cart->get_cart_contents_total();
        $shipping_total = WC()->cart->get_shipping_total();
        $total = $sub_total + $shipping_total;
        $cart_items = [];
        foreach ($cart as $cart_item_key => $cart_item) {
            $sku_id = $cart_item['data']->get_sku();
            $product_id = wc_get_product_id_by_sku($sku_id);
            if (!$product_id) {
                $product_id = $cart_item['product_id'];
            }
            $quantity = $cart_item['quantity'];
            $product = wc_get_product($product_id);

            if ($product) {
                $product_data = extract_product_data($product);
                // error_log('Product data: ' . json_encode($product_data));
                $cart_items[] = array(
                    'id' => (string) $product_data['sku_id'],
                    'name' => (string) $product_data['product_name'],
                    'taxonomy' => $product_data['category_name'],
                    'unit_price' => (float) $product_data['product_price'],
                    'unit_sale_price' => (float) $product_data['product_sale_price'],
                    'quantity' => $quantity,
                    'url' => (string) $product_data['product_url'],
                    'product_image_url' => (string) $product_data['product_image_url'],
                    'custom' => [
                        'channel' => (string) $product_data['channel'],
                        'store_name' => (string) $product_data['store_name'],
                        'variant' => $product_data['variant'],
                        's_coin_cashback' => $product_data['s_coin_cashback'],
                        'source' => $product_data['source'],
                    ]
                );
            }
        }

        echo "
        <script>
            window.InsiderQueue = window.InsiderQueue || [];
            let cartData = {
                type: 'cart',
                value: {
                    total: " . (float) $total . ",
                    shipping_cost: " . (float) $shipping_total . ",
                    items: " . json_encode($cart_items) . "
                }
            };
            window.InsiderQueue.push(cartData);
            window.InsiderQueue.push({
                type: 'currency',
                value: '" . esc_js(get_woocommerce_currency()) . "'
            });
            window.InsiderQueue.push({
                type: 'init'
            });
            console.log('Cart data sent to Insider:', cartData);
        </script>
        ";
    }
}
add_action('wp_footer', 'track_cart_page');

/*******************************/
/***** Wishlist Page Event *****/
/******************************/
function my_wishlist_event()
{
    global $wpdb;
    if (is_page('wishlist')) {
        $wishlist = [];
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $wishlist_products = $wpdb->get_col($wpdb->prepare(
            "SELECT c.product_id
            FROM {$wpdb->prefix}users a
            LEFT JOIN {$wpdb->prefix}woodmart_wishlists b ON b.user_id = a.ID
            LEFT JOIN {$wpdb->prefix}woodmart_wishlist_products c ON c.wishlist_id = b.ID
            WHERE a.ID = %d",
            $user_id
        ));

        if (!empty($wishlist_products)) {
            foreach ($wishlist_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $product_data = extract_product_data($product);
                    $wishlist[] = [
                        'category_name' => !empty($product_data['category_name'])
                            ? (is_array($product_data['category_name'])
                                ? end($product_data['category_name'])
                                : (string) $product_data['category_name'][0])
                            : '',
                        'product_image_url' => $product_data['product_image_url'],
                        'product_name' => $product_data['product_name'],
                        'product_price' => $product_data['product_price'],
                        'product_url' => $product_data['product_url'],
                        'store_name' => $product_data['store_name']
                    ];
                }
            }

            // Generate JS with individual product events and 1-second delay
            echo "<script>
                let wishlist = " . json_encode($wishlist) . ";
                window.InsiderQueue = window.InsiderQueue || [];

                wishlist.forEach((product, index) => {
                    setTimeout(() => {
                        let eventData = {
                            type: 'custom_event',
                            value: [{
                                event_name: 'my_wishlist',
                                event_parameters: wishlist[index]
                            }]
                        };
                        window.InsiderQueue.push(eventData);
                        console.log('📡 Wishlist Product Event Fired:', eventData);
                    }, index === 0 ? 5000 : index * 3000); // First delay 5s, others follow index * 3000
                });

                window.InsiderQueue.push({
                    type: 'currency',
                    value: '" . esc_js(get_woocommerce_currency()) . "'
                });

                window.InsiderQueue.push({
                    type: 'init'
                });
            </script>";
        }
    }
}
add_action('wp_footer', 'my_wishlist_event');

/*******************************************/
/***** Track Multiple Wishlist Remove *****/
/******************************************/
function track_multiple_wishlist_remove()
{
    if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'woodmart_remove_from_wishlist') {
        return;
    }
    if (isset($_GET['product_id']) && is_array($_GET['product_id'])) {
        error_log('Product IDs: ' . json_encode($_GET['product_id']));
        $product_ids = array_map('intval', $_GET['product_id']);
        $wishlist_items = [];

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product_data = extract_product_data($product);
                $wishlist_items[] = $product_data;
                // $wishlist_items[] = [
                //     'id' => (string) $product_data['product_id'],
                //     'name' => (string) $product_data['product_name'],
                //     'taxonomy' => $product_data['category_name'],
                //     'unit_price' => (float) $product_data['product_price'],
                //     'unit_sale_price' => (float) $product_data['product_sale_price'],
                //     'url' => (string) $product_data['product_url'],
                //     'product_image_url' => (string) $product_data['product_image_url'],
                //     'custom' => [
                //         'sku_id' => (string) $product_data['sku_id'],
                //         'channel' => (string) $product_data['channel'],
                //         'variant' => $product_data['variant'],
                //         'store_name' => (string) $product_data['store_name'],
                //         'coin_cashback' => $product_data['coin_cashback'],
                //     ]
                // ];
            }
        }

        add_filter('woodmart_get_update_wishlist_fragments', function ($response) use ($wishlist_items) {
            // Add our custom fragment with a unique key
            $response['wishlist_tracking_data'] = json_encode([
                'removed_items' => $wishlist_items,
                'timestamp' => time()
            ]);

            return $response;
        });
    }
}
// For AJAX requests
add_action('wp_ajax_woodmart_remove_from_wishlist', 'track_multiple_wishlist_remove', 5);
add_action('wp_ajax_nopriv_woodmart_remove_from_wishlist', 'track_multiple_wishlist_remove', 5);


/**************************/
/***** SEARCHED EVENT *****/
/**************************/
function track_search_term()
{
    if (is_search()) {
        $search_term = get_search_query();
        $search_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        echo "
        <script>
            window.InsiderQueue = window.InsiderQueue || [];
            let searchData = {
                type: 'custom_event',
                value: [{
                    event_name: 'searched',
                    event_parameters: {
                        search_term: '" . esc_js($search_term) . "',
                        searched_url: '" . esc_js($search_url) . "'
                    }
                }]
            };

            window.InsiderQueue.push(searchData);
            window.InsiderQueue.push({
                type: 'init'
            });

            console.log('📡 Search Event Fired:', searchData);
        </script>
        ";
    }
}
add_action('wp_footer', 'track_search_term');

/******************************/
/*****  BRAND STORE VIEW  ****/
/****************************/
function track_brand_store_view()
{
    if (is_tax('product_brand')) {
        $brand = get_queried_object();
        if ($brand) {
            $brand_name = $brand->name;

            echo "
            <script>
                window.InsiderQueue = window.InsiderQueue || [];
                let brandStoreViewData = {
                    type: 'custom_event',
                    value: [{
                        event_name: 'brand_store_viewed',
                        event_parameters: {
                            store_name: '" . esc_js($brand_name) . "',
                            channel: '" . esc_js(get_userAgent()) . "',
                        }
                    }]
                };

                window.InsiderQueue.push(brandStoreViewData);
                window.InsiderQueue.push({
                    type: 'init'
                });

                console.log('📡 Brand Store View Event Fired:', brandStoreViewData);
            </script>
            ";
        }
    }
}
add_action('wp_footer', 'track_brand_store_view');


/******************************/
/*****  COUPON EVENT      ****/
/****************************/
function my_coupon_check_validity()
{
    $code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
    if (!$code) {
        wp_send_json_error('No coupon code provided.');
    }

    $coupon = new WC_Coupon($code);
    if (!$coupon->get_id()) {
        wp_send_json_error('Coupon not found.');
    }

    // Check if coupon is still valid (not expired & published)
    $now   = current_time('timestamp');
    $expiry = $coupon->get_date_expires();

    // Build response with key details
    $data = array(
        'code'        => $code,
        'name'        => $coupon->get_description() ?: $code, // coupon title/description
        'amount'      => $coupon->get_amount(),
        'discount_type' => $coupon->get_discount_type(),
        'expiry_date'  => $expiry ? $expiry->date('d-M-y') : ''
    );

    wp_send_json_success($data);
}
add_action('wp_ajax_check_coupon_validity', 'my_coupon_check_validity');
add_action('wp_ajax_nopriv_check_coupon_validity', 'my_coupon_check_validity');



/***************************************/
/***** INSIDER BULK FEED ENDPOINT *****/
/**************************************/
add_action('rest_api_init', function () {
    register_rest_route('custom-api/v1', '/insider-bulk-feed', [
        'methods' => 'GET',
        'callback' => 'custom_insider_bulk_feed_handler_url',
        'args' => [
            'mode' => [
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return true;
                },
            ],
        ],
    ]);
});

function custom_insider_bulk_feed_handler_url(WP_REST_Request $request)
{
    $mode = $request->get_param('mode') ?: 'all';
    $limit = (int) ($request->get_param('limit') ?: 500);

    return custom_insider_bulk_feed_handler($mode, $limit);
}

function custom_insider_bulk_feed_handler($mode, $limit = 500)
{
    $config = insider_config();
    // if ($request->get_param('token') !== INSIDER_BULK_FEED_TOKEN) {
    //     return new WP_REST_Response(['error' => 'Invalid token'], 403);
    // }

    // $product_ids = $request->get_param('product_ids');
    // $mode  = $request->get_param('mode') ?: 'all';
    // $limit = (int) ($request->get_param('limit') ?: 200);
    // $limit = max(1, min($limit, 1000));
    $locale = insiderLocale();

    global $wpdb;

    $create_ids = [];
    $update_ids = [];

    // Normalize product_ids if passed
    $where_ids = '';
    if (!empty($product_ids) && is_array($product_ids)) {
        $product_ids = array_map('absint', $product_ids);
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
        $where_ids = $wpdb->prepare(" AND p.ID IN ($placeholders)", ...$product_ids);
    }

    if ($mode === 'create') {
        $sql_create = $wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} AS p
        LEFT JOIN {$wpdb->postmeta} AS m
            ON m.post_id = p.ID
           AND m.meta_key = %s
        WHERE p.post_type = 'product'
          AND m.meta_value IS NULL
          {$where_ids}
        LIMIT %d
    ", 'last_synced_insider', $limit);

        $create_ids = $wpdb->get_col($sql_create);
    }

    if ($mode === 'update') {
        $sql_update = $wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} AS p
        INNER JOIN {$wpdb->postmeta} AS m
            ON m.post_id = p.ID
           AND m.meta_key = %s
        WHERE p.post_type = 'product'
          AND p.post_modified > m.meta_value
          {$where_ids}
        LIMIT %d
    ", 'last_synced_insider', $limit);

        $update_ids = $wpdb->get_col($sql_update);
    }

    if ($mode === 'all') {
        $sql_all = $wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} AS p
        WHERE p.post_type = 'product'
          {$where_ids}
        LIMIT %d
    ", $limit);
        $all_ids = $wpdb->get_col($sql_all);
        $create_ids = $all_ids;
        $update_ids = [];
    }

    $payloads = [];
    $payloads_create = [];
    $payloads_update = [];
    foreach ($create_ids as $key => $pid) {
        $product = wc_get_product($pid);
        if (! $product) {
            unset($create_ids[$key]);
            continue;
        }
        $payloads_create = array_merge($payloads_create, build_insider_payload($product, $locale));
    }
    foreach ($update_ids as $pid) {
        $product = wc_get_product($pid);
        if (! $product) {
            unset($update_ids[$key]);
            continue;
        }
        $payloads_update = array_merge($payloads_update, build_insider_payload($product, $locale));
    }

    $action = ($mode === 'update') ? 'update' : 'ingest';
    $payloads = ($mode === 'all') ? array_merge($payloads_create, $payloads_update) : ($mode === 'create' ? $payloads_create : $payloads_update);

    wp_reset_postdata();
    if (empty($payloads)) {
        return new WP_REST_Response(['message' => 'No valid products found'], 200);
    }
    // echo 'Total payloads to send: ' . count($payloads) . PHP_EOL;
    // echo 'Action: ' . $action . PHP_EOL;
    // echo json_encode(array_slice($payloads, 0, 2), JSON_PRETTY_PRINT) . PHP_EOL; // Show first 2 payloads for reference
    // echo json_encode($payloads, JSON_PRETTY_PRINT) . PHP_EOL; // Show config for reference
    // die;
    $api_url = 'https://catalog.api.useinsider.com/v2/' . $action;
    $headers = [
        'X-PARTNER-NAME' => $config['partner_name'],
        'X-REQUEST-TOKEN' => $config['catalog_token'],
        'Content-Type'    => 'application/json',
        'Accept'          => 'application/json',
    ];

    $response = wp_remote_post($api_url, [
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => json_encode($payloads),
    ]);

    $response_code = wp_remote_retrieve_response_code($response);

    log_api_request(
        $api_url,
        'POST',
        json_encode($payloads),
        $response_code,
        is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response),
    );

    if (is_wp_error($response) || $response_code !== 200) {
        $error_message = is_wp_error($response) ? $response->get_error_message() : 'Unexpected response code: ' . $response_code;
        return new WP_REST_Response([
            'success' => false,
            'error'   => $error_message,
            'response_code' => $response_code,
            'response_body' => is_wp_error($response) ? null : json_decode(wp_remote_retrieve_body($response), true),
        ], 500);
    }

    insider_mark_synced_many(array_merge($create_ids, $update_ids));

    return new WP_REST_Response([
        'success'       => true,
        'count'         => count($payloads),
        'response_code' => $response_code,
        'response_body' => json_decode(wp_remote_retrieve_body($response), true),
    ]);
}

function insider_mark_synced_many(array $post_ids)
{
    // Set timezone to Asia/Kuala_Lumpur for timestamp
    $dt = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $now = $dt->format('Y-m-d H:i:s');
    foreach ($post_ids as $id) {
        update_post_meta($id, 'last_synced_insider', $now);
    }
}

function build_insider_payload($product, $locale = 'en_MY:2')
{
    $payload = [];

    $build = function ($product, $groupcode = null) use ($locale) {
        $data = extract_product_feed_data($product);
        if (!$data) return null;

        $brand = get_product_brand($product);
        $inventory = $product->is_visible() && $product->is_in_stock() ? 1 : 0;

        $item = [
            'item_id'    => $data['sku_id'],
            'locale'     => $locale,
            'name'       => $data['product_name'],
            'url'        => $data['product_url'],
            'image_url'  => $data['product_image_url'],
            'category'   => $data['category_name'],
            'price'      => [$data['product_currency'] => (float) $data['product_sale_price']],
            'original_price' => [$data['product_currency'] => (float) $data['product_price']],
            'in_stock'   => $inventory,
            'brand'      => $brand,
            'description' => (string) json_encode($data['product_description_json']),
            'rating'     => $data['average_rating'],
            'product_attributes' => [
                'shop_name'       => (string) 'Senheng Official',
                'shop_id'         => (string) '1200189001',
                'quantity_sold'   => (string) $product->get_total_sales(),
                'item_code'       => (string) $data['sku_id'],
                'scoin'           => (float) $data['s_coin_cashback'],
                'variant_key'     => !empty($data['variant_key_values']) ? implode(', ', array_keys($data['variant_key_values'])) : '',
                'variant_value'   => !empty($data['variant_key_values']) ? implode(', ', array_values($data['variant_key_values'])) : '',
                'v_stock_qty'     => (float) $product->get_stock_quantity(),
                'v_price'         => (string) $data['product_price'],
                'v_selling_price' => (string) $data['product_sale_price'],
                'v_event_price_tag' => (string) '',
                'v_event_slash_price_tag' => (string) '',
                'v_event_channel' => (string) $data['channel'],
                'v_event_start_at' => (string) ($data['product_sale_start_date'] ?: ''),
                'v_event_expired_at' => (string) ($data['product_sale_end_date'] ?: ''),
                'sku_id'          =>  $data['sku_list_id'],
                'sku_list'        =>  $data['sku_list'],
                'category_path_id' => [$data['category_path_id']],
                'shop_url'        => (string) $data['store_url'],
                'product_url'     => (string) $data['product_url'],
                'channel'         => (string) $data['channel'],
                'total_rating'    => (float) $data['total_reviews'],
                // 'v_scrc_price_tag' => (float) 0,
                'scoin_boolean'   => (bool) $data['s_coin_cashback'] > 0,
                'product_status'  => (string) get_product_status_label($product),
            ],
        ];

        if ($groupcode) {
            $item['groupcode'] = (string) $groupcode;
        }
        return is_valid_product_payload($item) ? $item : null;
    };

    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;
            if ($child = $build($variation, $product->get_id())) {
                $payload[] = $child;
            }
        }
    } else {
        if ($single = $build($product)) {
            $payload[] = $single;
        }
    }

    return $payload;
}

function get_product_brand($product)
{
    $brand = get_the_terms($product->get_id(), 'product_brand');
    $brand_name = (!empty($brand) && !is_wp_error($brand)) ? $brand[0]->name : '';

    if (empty($brand_name) && $product->get_parent_id()) {
        $parent_brand = get_the_terms($product->get_parent_id(), 'product_brand');
        $brand_name = (!empty($parent_brand) && !is_wp_error($parent_brand)) ? $parent_brand[0]->name : '';
    }

    return $brand_name;
}

function extract_product_feed_data($product)
{
    $product_id = $product->get_id();
    $parent_id = $product->get_parent_id();
    $sku_id = $product->get_sku();
    $product_name = $product->get_name();

    $sku_list = [];
    $sku_list_id = [];
    if ($parent_id) {
        $parent_product = wc_get_product($parent_id);
        if ($parent_product && $parent_product->is_type('variable')) {
            foreach ($parent_product->get_children() as $child_id) {
                $child_product = wc_get_product($child_id);
                if ($child_product && $child_product->get_sku()) {
                    $sku_list[] = $child_product->get_sku();
                    $sku_list_id[] = $child_id;
                }
            }
        }
    } elseif ($sku_id) {
        $sku_list[] = $sku_id;
        $sku_list_id[] = $product_id;
    }

    $s_coin_cashback = (float) get_field('s_coin_value', $product_id) ?: get_field('s_coin_value', $parent_id) ?: 0;
    $product_categories = wp_get_post_terms($parent_id ?: $product_id, 'product_cat', ['fields' => 'names']) ?: [];
    $category_path_id = get_product_category_path_ids($parent_id ?: $product_id);

    $product_price = (float) ($product->get_regular_price() ?: $product->get_price());
    $product_sale_price = (float) ($product->get_sale_price() ?: $product_price);

    $product_url = get_permalink($product_id);
    $product_image_url = $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '';
    $product_currency = get_woocommerce_currency();
    $average_rating = (float) ($parent_id ? wc_get_product($parent_id)->get_average_rating() : $product->get_average_rating());
    $total_reviews = (int) ($parent_id ? wc_get_product($parent_id)->get_review_count() : $product->get_review_count());

    $variant_attributes = $product->is_type('variation') ? $product->get_attributes() : ($product->is_type('variable') ? $product->get_default_attributes() : []);
    $variant_key_values = array_combine(
        array_map(fn($key) => ucwords(str_replace('-', ' ', preg_replace('/^pa_/', '', $key))), array_keys($variant_attributes)),
        array_map(fn($value) => ucwords(str_replace('-', ' ', (string) $value)), array_values($variant_attributes))
    );

    $product_description = wp_trim_words(wp_strip_all_tags($product->get_description()), 30, '...');
    $product_description_json = [[
        'title'   => $product_name,
        'content' => $product_description
    ]];
    $start_sale_date = $product->get_date_on_sale_from();
    // Format start_sale_date to "Fri May 16 17:52:30 CST 2025"
    $start_sale_date_formatted = '';
    if ($start_sale_date instanceof WC_DateTime) {
        $dt = $start_sale_date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        $start_sale_date_formatted = $dt->format('D M d H:i:s \C\S\T Y');
    }
    $end_sale_date = $product->get_date_on_sale_to();
    $end_sale_date_formatted = '';
    if ($end_sale_date instanceof WC_DateTime) {
        $dt = $end_sale_date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        $end_sale_date_formatted = $dt->format('D M d H:i:s \C\S\T Y');
    }

    return [
        'sku_id' => (string) $sku_id,
        'sku_list' => $sku_list,
        'sku_list_id' => $sku_list_id,
        'product_id' => (string) $product_id,
        'product_name' => (string) $product_name,
        'product_price' => $product_price,
        'product_sale_price' => $product_sale_price,
        'product_url' => $product_url,
        'product_image_url' => $product_image_url,
        'product_stock' => (string) ($product->get_stock_quantity() !== null ? 1 : 0),
        'product_currency' => $product_currency,
        'product_description' => $product_description,
        'product_description_json' => $product_description_json,
        'category_name' => $product_categories,
        'coin_cashback' => 0,
        'channel' => get_userAgent('product_feed'),
        'store_name' => get_store_name(),
        'source_1' => (string) ($_COOKIE['source_product_clicked'] ?? ''),
        'source_2' => null,
        'source_3' => null,
        'currency' => $product_currency,
        'wishlist' => 0,
        'quantity' => 1,
        'add_to_cart_id' => $product_id,
        'average_rating' => $average_rating,
        'total_reviews' => $total_reviews,
        'product_color' => $parent_id ? $product->get_attribute('pa_color') : '',
        'product_size' => $product->get_attribute('pa_size'),
        's_coin_cashback' => round($s_coin_cashback, 2),
        'src' => '',
        'variant' => $variant_key_values ? array_map(fn($k, $v) => "$k: $v", array_keys($variant_key_values), $variant_key_values) : '',
        'variant_key_values' => $variant_key_values,
        'source' => (string) ($_COOKIE['globalPrevPage'] ?? ''),
        'category_path_id' => $category_path_id,
        'store_url' => get_home_url(),
        'product_sale_start_date' => $start_sale_date_formatted,
        'product_sale_end_date' => $end_sale_date_formatted
    ];
}
