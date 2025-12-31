<?php

/**
 * Cart Controller
 * 
 * Handles cart page functionality including product extras display
 * and cart-specific customizations for the Senheng Core plugin
 */
class CartController
{
    /**
     * Initialize the cart controller
     */
    public static function init()
    {
        // Initialize cart page customizations
        self::init_cart_page_hooks();
        
        // Initialize cart styling
        self::init_cart_assets();
        
        // Initialize deposit functionality
        self::init_deposit_hooks();
    }

    /**
     * Initialize cart page hooks and filters
     */
    public static function init_cart_page_hooks()
    {
        // Modify cart item name to include promotion info
        add_filter('woocommerce_cart_item_name', [self::class, 'modify_cart_item_name'], 10, 3);
        
        // Modify cart item subtotal to include product extras for product type fields (cart page only)
        // Use a very high priority so this wins over other plugins' filters (e.g., 1200)
        add_filter('woocommerce_cart_item_subtotal', [self::class, 'modify_cart_item_subtotal_for_cart_page'], 2000, 3);
        
        // Add cart page specific styling
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_cart_assets']);
        
        // Add virtual/physical product validation for cart page
        add_action('woocommerce_check_cart_items', [self::class, 'validate_cart_items_on_cart_page']);
        add_action('wp', [self::class, 'restrict_virtual_and_physical_cart_on_cart_page']);
        
        // Add checkout button disable functionality for mixed products
        add_action('wp_footer', [self::class, 'disable_checkout_button_for_mixed_products']);
        
        // Add AJAX handlers for mixed product validation
        add_action('wc_ajax_check_mixed_products', [self::class, 'ajax_check_mixed_products']);
        add_action('wc_ajax_nopriv_check_mixed_products', [self::class, 'ajax_check_mixed_products']);

        // Add AJAX validation after cart item removal
        add_action('wp', [self::class, 'ajax_validate_cart_after_removal']);
        
        // Add validation after cart item restoration (undo)
        add_action('woocommerce_cart_item_restored', [self::class, 'validate_cart_after_item_restored'], 10, 2);

        // Remove AWCDP cart totals rows (Due Today / Future payments) via PHP on cart page
        add_action('wp', [self::class, 'remove_awcdp_cart_totals_rows_on_cart'], 20);

        add_filter('woocommerce_cart_get_total', [self::class, 'filter_cart_total_for_deposit_and_extras'], 1200, 1);

        // Restrict shipping methods when Trade-In is yes or deposit/partial payment selected on cart page
        add_filter('woocommerce_package_rates', [self::class, 'restrict_shipping_methods_for_tradein_or_deposit'], 100, 2);
        add_filter('woocommerce_shipping_chosen_method', [self::class, 'force_local_pickup_plus_when_restricted'], 10, 3);

        add_filter('woocommerce_cart_ready_to_calc_shipping', [self::class, 'disable_shipping_calc_on_cart'], 99);
        add_filter('woocommerce_cart_needs_shipping', [self::class, 'disable_needs_shipping_on_cart'], 99);

        // Display maintenance and security fees disclaimer
        add_action('woocommerce_cart_totals_before_order_total', [self::class, 'display_maintenance_fees_disclaimer'], 25);
    }

    /**
     * Initialize cart assets
     */
    public static function init_cart_assets()
    {
        // Assets will be enqueued via enqueue_cart_assets method
    }


    /**
     * Modify cart item name to include product extras and variation attributes
     */
    public static function modify_cart_item_name($product_name, $cart_item, $cart_item_key)
    {
        // Only modify on cart page
        if (!is_cart()) {
            return $product_name;
        }

        // Check if variation data is already displayed to prevent duplication
        if (strpos($product_name, 'cart-variation-data') !== false) {
            return $product_name;
        }

        // Add variation attributes display below product title
        $variation_html = self::get_formatted_variation_data($cart_item);
        if (!empty($variation_html)) {
            $product_name .= $variation_html;
        }

        // Add product extras display and meta
        $product_extras = !empty($cart_item['product_extras']) ? $cart_item['product_extras'] : array();

        // Gate Trade In and Deposit labels by AWCDP deposit eligibility
        $base_product_id = isset($cart_item['product_id']) ? (int)$cart_item['product_id'] : 0;
        $deposit_eligible = WooCommerceAddtoCartController::is_deposit_eligible_for_product_id($base_product_id);

        if ($deposit_eligible) {
            // Append Trade In directly under variations using top-level cart item value
            if (isset($cart_item['trade_in']) && $cart_item['trade_in'] !== '') {
                $trade_in_value = ($cart_item['trade_in'] === 'yes') ? __('Yes', 'senheng-core') : __('No', 'senheng-core');
                $product_name .= '<div class="cart-trade-in"><small>' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html($trade_in_value) . '</small></div>';
            }

            // Append Payment Option: show deposit label only when selected
            $is_deposit = false;
            if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] !== '') {
                $is_deposit = ($cart_item['awcdp_deposit_option'] === 'yes');
            } elseif (isset($cart_item['deposit_option'])) {
                $is_deposit = ($cart_item['deposit_option'] === 'deposit');
            }
            if ($is_deposit) {
                $product_name .= '<div class="cart-payment-option"><small>' . esc_html__('Deposit Payment', 'senheng-core') . '</small></div>';
            }

            // Actual product price below payment option (only when deposit is selected)
            if ($is_deposit) {
                $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
                $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
                $product_obj = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                if ($product_obj) {
                    $base_price = $product_obj->get_price();
                    $product_name .= '<div class="cart-actual-price"><small>' . esc_html__('Actual Price', 'senheng-core') . ': ' . wc_price($base_price) . '</small></div>';
                }
            }
        }

        // Render extras section after meta lines (if any)
        if (!empty($product_extras)) {
            $product_name .= self::render_product_extras_html($cart_item);
        }

        return $product_name;
    }

    /**
     * Enqueue cart page specific assets
     */
    public static function enqueue_cart_assets()
    {
        // Only enqueue on cart page
        if (!is_cart()) {
            return;
        }

        // Enqueue cart-specific CSS
        wp_enqueue_style(
            'senheng-cart-extras',
            plugin_dir_url(__FILE__) . '../../assets/css/cart/cart-extras.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue mixed products notice CSS
        wp_enqueue_style(
            'senheng-mixed-products-notice',
            plugin_dir_url(__FILE__) . '../../assets/css/cart/mixed-products-notice.css',
            array(),
            '1.0.0'
        );

        // Initialize wpmDataLayer early to prevent TypeError from woocommerce-google-adwords-conversion-tracking-tag plugin
        // The plugin's woocommerce_after_cart_item_name hook expects wpmDataLayer to exist before cart items render
        wp_add_inline_script(
            'jquery',
            'window.wpmDataLayer = window.wpmDataLayer || {}; window.wpmDataLayer.cart_item_keys = window.wpmDataLayer.cart_item_keys || {};',
            'before'
        );
    }

    /**
     * Get formatted product extras for display
     */
    public static function get_formatted_product_extras($cart_item)
    {
        if (empty($cart_item['product_extras'])) {
            return array();
        }

        $formatted_extras = array();
        $product_extras = $cart_item['product_extras'];

        // Format product extras
        if (!empty($product_extras['products'])) {
            foreach ($product_extras['products'] as $extra_key => $extra_product) {
                if (is_object($extra_product)) {
                    $formatted_extras['products'][] = array(
                        'id' => $extra_product->get_id(),
                        'name' => $extra_product->get_name(),
                        'price' => $extra_product->get_price(),
                        'image_url' => wp_get_attachment_image_url($extra_product->get_image_id(), 'thumbnail'),
                        'permalink' => $extra_product->get_permalink()
                    );
                }
            }
        }

        // Format additional information
        if (!empty($product_extras['info'])) {
            foreach ($product_extras['info'] as $info_key => $info_data) {
                if (is_array($info_data)) {
                    $formatted_extras['info'][] = array(
                        'key' => $info_key,
                        'name' => isset($info_data['name']) ? $info_data['name'] : '',
                        'value' => isset($info_data['value']) ? $info_data['value'] : '',
                        'price' => isset($info_data['price']) ? $info_data['price'] : 0
                    );
                }
            }
        }

        return $formatted_extras;
    }

    /**
     * AJAX handler for updating cart extras (if needed for future functionality)
     */
    public static function ajax_update_cart_extras()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'senheng_cart_nonce')) {
            wp_die('Security check failed');
        }

        // Get cart item key
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        
        if (empty($cart_item_key)) {
            wp_send_json_error('Invalid cart item key');
        }

        // Process cart extras update logic here if needed
        
        wp_send_json_success(array(
            'message' => __('Cart extras updated successfully', 'senheng-core')
        ));
    }

    /**
     * Remove AWCDP "Due Today" and "Future payments" rows from the cart totals using PHP.
     * This unhooks AWCDP_Front_End::awcdp_cart_totals_after_order_total on the cart page only.
     */
    public static function remove_awcdp_cart_totals_rows_on_cart()
    {
        if (!function_exists('is_cart') || !is_cart()) {
            return;
        }

        $hook = 'woocommerce_cart_totals_after_order_total';
        global $wp_filter;
        if (!isset($wp_filter[$hook]) || !is_object($wp_filter[$hook])) {
            return;
        }

        // Iterate callbacks and remove the AWCDP front-end method
        $callbacks = $wp_filter[$hook]->callbacks;
        foreach ($callbacks as $priority => $items) {
            foreach ($items as $id => $item) {
                $fn = isset($item['function']) ? $item['function'] : null;
                if (is_array($fn) && isset($fn[0], $fn[1]) && is_object($fn[0])) {
                    if (get_class($fn[0]) === 'AWCDP_Front_End' && $fn[1] === 'awcdp_cart_totals_after_order_total') {
                        remove_action($hook, $fn, $priority);
                    }
                }
            }
        }
    }

    /**
     * Render product extras HTML for cart display (both product and info types)
     */
    public static function render_product_extras_html($cart_item)
    {
        if (empty($cart_item['product_extras'])) {
            return '';
        }

        $product_extras = $cart_item['product_extras'];

        // Check if there are any extras to display
        if (empty($product_extras['selected_products']) && empty($product_extras['selected_info'])) {
            return '';
        }

        $html = '<div class="cart-product-extras">';

        $products_group = '';
        $info_group = '';

        // Display extra products
        if (!empty($product_extras['selected_products'])) {
            $products_group .= '<div class="sh-extras-section">';
            
            // Get field label from the first product item, or use default
            $field_label = '';
            if (!empty($product_extras['selected_products'][0]['fieldLabel'])) {
                $field_label = $product_extras['selected_products'][0]['fieldLabel'];
            }
            
            $products_group .= '<div class="sh-extras-header">' . esc_html($field_label) . '</div>';
                    
            foreach ($product_extras['selected_products'] as $extra_product_data) {
                if (is_array($extra_product_data)) {
                    $product_id = isset($extra_product_data['productId']) ? $extra_product_data['productId'] : '';
                    $product_name = isset($extra_product_data['title']) ? $extra_product_data['title'] : '';
                    $product_quantity = isset($extra_product_data['quantity']) ? $extra_product_data['quantity'] : 1;
                    
                    // Get fresh product price and calculate total based on current quantity
                    $unit_price = 0;
                    $unit_regular = null;
                    if ($product_id) {
                        $variation_id = isset($extra_product_data['variationId']) ? $extra_product_data['variationId'] : '';
                        $product_obj = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                        if ($product_obj) {
                            $unit_price = (float) $product_obj->get_price();
                            $unit_regular = (float) $product_obj->get_regular_price();
                        }
                    }
                    
                    // Fallback to stored price if product price is 0
                    if ($unit_price <= 0) {
                        $unit_price = isset($extra_product_data['price']) ? (float) $extra_product_data['price'] : 0;
                    }

                    // Apply discount if available
                    $child_discount = isset($extra_product_data['childDiscount']) ? floatval($extra_product_data['childDiscount']) : 0;
                    $discount_type = isset($extra_product_data['discountType']) ? $extra_product_data['discountType'] : '';
                    
                    if ($unit_price > 0 && $child_discount > 0) {
                         if ($discount_type === 'percent') {
                             $unit_price = $unit_price - ($unit_price * ($child_discount / 100));
                         } elseif ($discount_type === 'fixed') {
                             $unit_price = max(0, $unit_price - $child_discount);
                         }
                    }

                    // Fallback to stored original price field from widget
                    if (is_null($unit_regular) || $unit_regular <= 0) {
                        if (isset($extra_product_data['originalPrice']) && is_numeric($extra_product_data['originalPrice'])) {
                            $unit_regular = (float) $extra_product_data['originalPrice'];
                        } elseif (isset($extra_product_data['original']) && is_numeric($extra_product_data['original'])) {
                            $unit_regular = (float) $extra_product_data['original'];
                        }
                    }
                    
                    // Calculate total price based on current quantity
                    $product_price = $unit_price * $product_quantity;
                    $product_regular_total = $unit_regular ? ($unit_regular * $product_quantity) : null;
                    
                    if ($product_name) {
                        // Get product image
                        $image_url = '';
                        if ($product_id) {
                            $variation_id = isset($extra_product_data['variationId']) ? $extra_product_data['variationId'] : '';
                            $product_for_image = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                            if ($product_for_image) {
                                $image_id = $product_for_image->get_image_id();
                                if ($image_id) {
                                    $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                                }
                            }
                        }
                        
                        // Fallback to placeholder if no image
                        if (empty($image_url)) {
                            $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                        }
                        
                        $products_group .= '<div class="sh-extras-item">';
                        
                        if ($image_url) {
                            $products_group .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_name) . '" class="sh-extras-image">';
                        }
                        
                        $products_group .= '<div class="sh-extras-content">';
                        
                        // Left side: Title and variation
                        $products_group .= '<div class="sh-extras-left">';
                        $products_group .= '<div class="sh-extras-title">' . esc_html($product_name) . '</div>';
                        
                        // Add variation attributes for extra products
                        $variation_html = self::get_formatted_variation_data_for_extra($extra_product_data);
                        if (!empty($variation_html)) {
                            $products_group .= $variation_html;
                        }
                        $products_group .= '</div>';
                        
                        // Right side: Prices and quantity
                        $products_group .= '<div class="sh-extras-right">';
                        $products_group .= '<div class="sh-extras-meta">';
                        $products_group .= '<span class="sh-extras-price">' . ($product_price ? wc_price($product_price) : wc_price(0)) . '</span>';
                        if (!is_null($product_regular_total) && $product_regular_total > $product_price) {
                            $products_group .= '<span class="sh-price-original">' . wc_price($product_regular_total) . '</span>';
                        }
                        $products_group .= '<span class="sh-extras-qty">' . esc_html__('Qty:', 'senheng-core') . ' ' . intval($product_quantity) . '</span>';
                        $products_group .= '</div>';
                        $products_group .= '</div>';
                        
                        $products_group .= '</div>';
                        
                        $products_group .= '</div>';
                    }
                }
            }
            $products_group .= '</div>';
        }

        // Display extra information
        if (!empty($product_extras['selected_info'])) {
            $info_group .= '<div class="sh-extras-section">';
            
            // Get field label from the first info item, or use default
            $field_label = '';
            if (!empty($product_extras['selected_info'][0]['fieldLabel'])) {
                $field_label = $product_extras['selected_info'][0]['fieldLabel'];
            }
            
            $info_group .= '<div class="sh-extras-header">' . esc_html($field_label) . '</div>';
            foreach ($product_extras['selected_info'] as $info_data) {
                if (is_array($info_data)) {
                    $info_label = isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '';
                    $info_price = isset($info_data['infoPrice']) ? $info_data['infoPrice'] : '';
                    $info_image = isset($info_data['imageUrl']) ? $info_data['imageUrl'] : '';
                    
                    if ($info_label) {
                        $info_group .= '<div class="sh-extras-item">';
                        
                        // Always show image - use stored URL or placeholder
                        if ($info_image && $info_image !== wc_placeholder_img_src('woocommerce_thumbnail')) {
                            $info_group .= '<img src="' . esc_url($info_image) . '" alt="' . esc_attr($info_label) . '" class="sh-extras-image">';
                        } else {
                            $info_group .= '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($info_label) . '" class="sh-extras-image">';
                        }
                        
                        $info_group .= '<div class="sh-extras-content">';
                        
                        // Left side: Title
                        $info_group .= '<div class="sh-extras-left">';
                        $info_group .= '<div class="sh-extras-title">' . esc_html($info_label) . '</div>';
                        $info_group .= '</div>';
                        
                        // Right side: Prices and quantity
                        $info_group .= '<div class="sh-extras-right">';
                        $info_group .= '<div class="sh-extras-meta">';
                        // Follow ProductExtrasWidget pattern: Sales price RM 0.00, Original price with strikethrough
                        $info_group .= '<span class="sh-extras-price">' . wc_price(0) . '</span>'; // Always show RM 0.00 as sales price
                        // Detect original price field variants from widget payload
                        $original_price = null;
                        if (isset($info_data['infoOriginalPrice']) && is_numeric($info_data['infoOriginalPrice'])) {
                            $original_price = (float) $info_data['infoOriginalPrice'];
                        } elseif (isset($info_data['originalPrice']) && is_numeric($info_data['originalPrice'])) {
                            $original_price = (float) $info_data['originalPrice'];
                        } elseif (isset($info_data['original']) && is_numeric($info_data['original'])) {
                            $original_price = (float) $info_data['original'];
                        }
                        if (!is_null($original_price) && $original_price > 0) {
                            $info_group .= '<span class="sh-price-original">' . wc_price($original_price) . '</span>';
                        }
                        $info_group .= '<span class="sh-extras-qty">' . esc_html__('Qty:', 'senheng-core') . ' 1</span>';
                        $info_group .= '</div>';
                        $info_group .= '</div>';
                        
                        $info_group .= '</div>';
                        
                        $info_group .= '</div>';
                    }
                }
            }
            $info_group .= '</div>';
        }

        $html .= $info_group . $products_group . '</div>';

        return $html;
    }

    /**
     * Determine if any cart item has Trade-In = yes or deposit/partial payment selected
     */
    private static function has_trade_in_or_deposit_selected()
    {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        // Per-request cache keyed by cart hash to avoid repeated loops during AJAX updates
        static $cached_result = null;
        static $cached_hash = null;
        $current_hash = method_exists(WC()->cart, 'get_cart_hash') ? WC()->cart->get_cart_hash() : null;
        if ($cached_hash !== null && $current_hash !== null && $cached_hash === $current_hash && $cached_result !== null) {
            return $cached_result;
        }

        $result = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $trade_in_yes = isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes';
            $deposit_selected = (
                (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') ||
                (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit')
            );
            if ($trade_in_yes || $deposit_selected) {
                $result = true;
                break;
            }
        }

        $cached_hash = $current_hash;
        $cached_result = $result;
        return $result;
    }

    /**
     * Restrict available shipping methods to Local Pickup Plus when trade-in or deposit is selected (cart page)
     */
    public static function restrict_shipping_methods_for_tradein_or_deposit($rates, $package)
    {
        // Only apply on cart page
        if (!function_exists('is_cart') || !is_cart()) {
            return $rates;
        }

        if (!self::has_trade_in_or_deposit_selected()) {
            return $rates;
        }

        $filtered = array();
        foreach ($rates as $rate_id => $rate) {
            // Ensure method_id matches Local Pickup Plus
            if (isset($rate->method_id) && $rate->method_id === 'local_pickup_plus') {
                $filtered[$rate_id] = $rate;
            }
        }

        if (empty($filtered)) {
            // If no Local Pickup Plus rate is configured, silently block other methods
            return $filtered; // empty array => no shipping methods available
        }

        // Silently enforce Local Pickup Plus without adding notices
        return $filtered;
    }

    /**
     * Force chosen shipping method to Local Pickup Plus when restricted (cart page)
     */
    public static function force_local_pickup_plus_when_restricted($method, $rates, $package)
    {
        // Only apply on cart page
        if (!function_exists('is_cart') || !is_cart()) {
            return $method;
        }

        if (!self::has_trade_in_or_deposit_selected()) {
            return $method;
        }

        foreach ($rates as $rate_id => $rate) {
            if (isset($rate->method_id) && $rate->method_id === 'local_pickup_plus') {
                return $rate_id; // Force choice
            }
        }
        return $method;
    }

    /**
     * Get formatted variation data for extra products
     * 
     * @param array $extra_product_data The extra product data
     * @return string HTML formatted variation data
     */
    public static function get_formatted_variation_data_for_extra($extra_product_data)
    {
        // Check if variation data exists
        if (empty($extra_product_data['variationData']) || !is_array($extra_product_data['variationData'])) {
            return '';
        }

        $variation_data = $extra_product_data['variationData'];
        $variation_id = isset($extra_product_data['variationId']) ? $extra_product_data['variationId'] : '';
        
        // Get the variation product for attribute labels
        $variation_product = null;
        if ($variation_id) {
            $variation_product = wc_get_product($variation_id);
        }

        $item_data = array();

        foreach ($variation_data as $name => $value) {
            if ('' === $value) {
                continue;
            }

            // Remove 'attribute_' prefix if present
            $attribute_key = str_replace('attribute_', '', $name);
            
            // Get attribute label
            $label = $attribute_key;
            if ($variation_product) {
                $label = wc_attribute_label($attribute_key, $variation_product);
            } else {
                // Fallback: try to get label from taxonomy
                $label = wc_attribute_label($attribute_key);
            }

            // Format the value
            $formatted_value = $value;
            
            // If it's a taxonomy attribute, get the term name
            if (taxonomy_exists($attribute_key)) {
                $term = get_term_by('slug', $value, $attribute_key);
                if ($term && !is_wp_error($term)) {
                    $formatted_value = $term->name;
                }
            }

            $item_data[] = array(
                'key'   => $label,
                'value' => $formatted_value,
            );
        }

        if (empty($item_data)) {
            return '';
        }

        // Format as HTML with cart-variation-data class for styling
        $html = '<div class="cart-variation-data">';
        $total_items = count($item_data);
        $current_index = 0;
        
        foreach ($item_data as $data) {
            $current_index++;
            $html .= '<div class="variation-item ' . sanitize_html_class('variation-' . $data['key']) . '">';
            // $html .= '<span class="variation-label">' . wp_kses_post($data['key']) . ':</span> ';
            $html .= '<span class="variation-value">' . wp_kses_post($data['value']) . '</span>';
            
            // Add comma if not the last item
            if ($current_index < $total_items) {
                $html .= '<span class="variation-separator">, </span>';
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Check if cart has any product extras (both product and info types)
     */
    public static function cart_has_extras()
    {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!empty($cart_item['product_extras'])) {
                $product_extras = $cart_item['product_extras'];
                // Check for both product and info type extras
                if (!empty($product_extras['selected_products']) || !empty($product_extras['selected_info'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get total count of product extras in cart (both product and info types)
     */
    public static function get_cart_extras_count()
    {
        $count = 0;

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!empty($cart_item['product_extras'])) {
                $product_extras = $cart_item['product_extras'];

                // Count selected products
                if (!empty($product_extras['selected_products'])) {
                    $count += count($product_extras['selected_products']);
                }

                // Count selected info items
                if (!empty($product_extras['selected_info'])) {
                    $count += count($product_extras['selected_info']);
                }
            }
        }

        return $count;
    }

    /**
     * Calculate total price of product extras for product type fields only
     */
    public static function calculate_product_extras_total($cart_item)
    {
        $extras_total = 0;

        if (empty($cart_item['product_extras'])) {
            return $extras_total;
        }

        $product_extras = $cart_item['product_extras'];

        // Only include selected_products (product type fields), not selected_info
        if (!empty($product_extras['selected_products'])) {
            foreach ($product_extras['selected_products'] as $extra_product_data) {
                if (is_array($extra_product_data)) {
                    $product_price = isset($extra_product_data['price']) ? floatval($extra_product_data['price']) : 0;
                    $product_quantity = isset($extra_product_data['quantity']) ? intval($extra_product_data['quantity']) : 1;
                    
                    // Apply discount if available
                    $child_discount = isset($extra_product_data['childDiscount']) ? floatval($extra_product_data['childDiscount']) : 0;
                    $discount_type = isset($extra_product_data['discountType']) ? $extra_product_data['discountType'] : '';
                    
                    if ($product_price > 0 && $child_discount > 0) {
                        if ($discount_type === 'percent') {
                            $product_price = $product_price - ($product_price * ($child_discount / 100));
                        } elseif ($discount_type === 'fixed') {
                            $product_price = max(0, $product_price - $child_discount);
                        }
                    }
                    
                    $extras_total += $product_price * $product_quantity;
                }
            }
        }

        return $extras_total;
    }

    /**
     * Modify cart item subtotal to include product extras for product type fields (cart page only)
     */
    public static function modify_cart_item_subtotal_for_cart_page($subtotal_html, $cart_item, $cart_item_key)
    {
        // Only apply on cart page, not mini cart or other areas
        if (!is_cart()) {
            return $subtotal_html;
        }

        // Only modify if this cart item has product extras OR trade-in
        $has_extras = !empty($cart_item['product_extras']) && !empty($cart_item['product_extras']['selected_products']);
        $has_trade_in = isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes';

        if (!$has_extras && !$has_trade_in) {
            return $subtotal_html;
        }

        // Get the base product price and quantity
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        $quantity = $cart_item['quantity'];
        
        if ($variation_id) {
            $product = wc_get_product($variation_id);
        } else {
            $product = wc_get_product($product_id);
        }
        
        if (!$product) {
            return $subtotal_html;
        }
        
        $use_deposit = false;
        $deposit_value = 0.0;
        if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
            $use_deposit = true;
            $deposit_value = floatval($cart_item['deposit_amount']);
        } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable']) && $cart_item['awcdp_deposit']['enable'] == 1 && isset($cart_item['awcdp_deposit']['deposit'])) {
            $use_deposit = true;
            $deposit_value = floatval($cart_item['awcdp_deposit']['deposit']);
        } elseif ((isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') || (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit')) {
            $use_deposit = true;
            $base_price = $product->get_price();
            $meta_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
            $meta_type = get_post_meta($product_id, '_awcdp_deposit_type', true);
            if (!empty($meta_amount)) {
                if ($meta_type === 'percent') {
                    $deposit_value = floatval($base_price) * (floatval($meta_amount) / 100);
                } else {
                    $deposit_value = floatval($meta_amount);
                }
            } else {
                $deposit_value = floatval($base_price);
            }
        }
        
        if (!$use_deposit) {
            $base_price = $product->get_price();

            // Apply Trade-In Discount
            if ($has_trade_in) {
                $discount = TradeInController::get_trade_in_discount($cart_item['product_id']);
                $base_price = max(0, floatval($base_price) - $discount);
            }
        }
        
        $product_part = $use_deposit ? ($deposit_value * $quantity) : ($base_price * $quantity);
        
        // Calculate product extras total (product type fields only)
        $extras_total = self::calculate_product_extras_total($cart_item);
        
        // Calculate total subtotal including product extras
        $total_subtotal = $product_part + ($extras_total * $quantity);
        
        // Format and return the new subtotal
        $total_subtotal_html = wc_price($total_subtotal);
                
        return $total_subtotal_html;
    }

    public static function filter_cart_total_for_deposit_and_extras($total)
    {
        if (!function_exists('is_cart') || !is_cart() || !function_exists('WC') || !WC()->cart) {
            return $total;
        }

        // Skip during coupon apply/remove operations to prevent bottleneck with Smart Coupons auto-apply
        if (doing_action('woocommerce_applied_coupon') || 
            doing_action('woocommerce_removed_coupon') ||
            doing_action('woocommerce_coupon_applied') ||
            doing_action('wc_ajax_apply_coupon') ||
            doing_action('wp_ajax_woocommerce_apply_coupon') ||
            doing_action('wp_ajax_nopriv_woocommerce_apply_coupon')) {
            return $total;
        }

        $cart = WC()->cart;
        $has_deposits = false;
        $has_trade_in = false;
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposits = true;
            }
            if (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable']) && $cart_item['awcdp_deposit']['enable'] == 1 && isset($cart_item['awcdp_deposit']['deposit'])) {
                $has_deposits = true;
            }
            if (isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes') {
                $has_trade_in = true;
            }
        }

        if (!$has_deposits && !$has_trade_in) {
            return $total;
        }

        $deposit_contents_total = 0.0;
        $extras_total = 0.0;
        foreach ($cart->get_cart() as $cart_item) {
            $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $deposit_contents_total += floatval($cart_item['deposit_amount']) * $qty;
            } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable']) && $cart_item['awcdp_deposit']['enable'] == 1 && isset($cart_item['awcdp_deposit']['deposit'])) {
                $deposit_contents_total += floatval($cart_item['awcdp_deposit']['deposit']) * $qty;
            } else {
                $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                
                // Apply Trade-In Discount for full payment items
                if (isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes') {
                    $discount = TradeInController::get_trade_in_discount($cart_item['product_id']);
                    $price = max(0, $price - $discount);
                }
                
                $deposit_contents_total += $price * $qty;
            }

            $extras_total += self::calculate_product_extras_total($cart_item) * $qty;
        }

        $fee_total = $cart->get_fee_total();
        $shipping_total = $cart->get_shipping_total();
        $tax_total = $cart->get_total_tax();
        $discount_total = $cart->get_discount_total();

        $correct_total = $deposit_contents_total + $extras_total + $fee_total + $shipping_total + $tax_total - $discount_total;
        return $correct_total;
    }

    /**
     * Format variation attributes for display in cart/mini cart
     * 
     * @param array $cart_item Cart item data
     * @return string Formatted variation HTML
     */
    public static function get_formatted_variation_data($cart_item)
    {
        if (empty($cart_item['data']) || !$cart_item['data']->is_type('variation') || empty($cart_item['variation'])) {
            return '';
        }

        $item_data = array();
        
        foreach ($cart_item['variation'] as $name => $value) {
            $taxonomy = wc_attribute_taxonomy_name(str_replace('attribute_pa_', '', urldecode($name)));

            if (taxonomy_exists($taxonomy)) {
                // If this is a term slug, get the term's nice name
                $term = get_term_by('slug', $value, $taxonomy);
                if (!is_wp_error($term) && $term && $term->name) {
                    $value = $term->name;
                }
                $label = wc_attribute_label($taxonomy);
            } else {
                // If this is a custom option slug, get the options name
                $value = apply_filters('woocommerce_variation_option_name', $value, null, $taxonomy, $cart_item['data']);
                $label = wc_attribute_label(str_replace('attribute_', '', $name), $cart_item['data']);
            }

            // Skip only if value is empty (remove the product name check since we want to show variations even if they're in the name)
            if ('' === $value) {
                continue;
            }

            $item_data[] = array(
                'key'   => $label,
                'value' => $value,
            );
        }

        if (empty($item_data)) {
            return '';
        }

        // Format as HTML with cart-variation-data class for styling consistency
        $html = '<div class="cart-variation-data">';
        $total_items = count($item_data);
        $current_index = 0;
        
        foreach ($item_data as $data) {
            $current_index++;
            $html .= '<span class="variation-value">' . wp_kses_post($data['value']) . '</span>';
            
            // Add comma if not the last item
            if ($current_index < $total_items) {
                $html .= '<span class="variation-separator">, </span>';
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Restrict mixing virtual and physical products in cart
     * This function validates cart contents on the cart page to prevent mixing product types
     */
    public static function restrict_virtual_and_physical_cart_on_cart_page()
    {
        // Only run on cart page
        if (!is_cart()) {
            return;
        }

        // Check if cart is available
        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        $has_virtual = $has_physical = false;
        $has_deposit = false;
        $has_full_payment = false;

        // Check all cart items for product types
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['data']->is_virtual()) {
                $has_virtual = true;
            } else {
                $has_physical = true;
            }

            // Detect partial payment (deposit) selection
            $is_deposit = false;
            if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
                $is_deposit = true;
            } elseif (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                $is_deposit = true;
            } elseif (isset($cart_item['deposit_amount']) && floatval($cart_item['deposit_amount']) > 0) {
                $is_deposit = true;
            }

            if ($is_deposit) {
                $has_deposit = true;
            } else {
                $has_full_payment = true;
            }

            // Defer notice rendering until after loop; we'll show a single, most relevant message
        }
        // Show a single consolidated notice based on final cart state
        if ($has_deposit && $has_virtual) {
            $combined_msg = __("You cannot combine partial payment items with ESD products. Please adjust your cart to proceed.", "woocommerce");
            if (!function_exists('wc_has_notice') || !wc_has_notice($combined_msg, 'error')) {
                wc_add_notice($combined_msg, 'error');
            }
        } elseif ($has_virtual && $has_physical) {
            $mixed_msg = __("You cannot combine normal and ESD products together. Please adjust your cart to proceed.", "woocommerce");
            if (!function_exists('wc_has_notice') || !wc_has_notice($mixed_msg, 'error')) {
                wc_add_notice($mixed_msg, 'error');
            }
        } elseif ($has_deposit && $has_full_payment) {
            $deposit_mixed_msg = __("You cannot combine partial payment items with normal products. Please adjust your cart to proceed.", "woocommerce");
            if (!function_exists('wc_has_notice') || !wc_has_notice($deposit_mixed_msg, 'error')) {
                wc_add_notice($deposit_mixed_msg, 'error');
            }
        }
    }

    /**
     * Check cart items for virtual/physical conflicts and display notices
     * This runs during cart updates and page loads
     */
    public static function validate_cart_items_on_cart_page()
    {
        // Only run on cart page
        if (!is_cart()) {
            return;
        }

        self::restrict_virtual_and_physical_cart_on_cart_page();
    }

    /**
     * Check if cart has both virtual and physical products
     * Returns true if cart contains both types, false otherwise
     */
    public static function cart_has_mixed_product_types()
    {
        // Check if cart is available
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        // Per-request cache keyed by cart hash
        static $cached_result = null;
        static $cached_hash = null;
        $current_hash = method_exists(WC()->cart, 'get_cart_hash') ? WC()->cart->get_cart_hash() : null;
        if ($cached_hash !== null && $current_hash !== null && $cached_hash === $current_hash && $cached_result !== null) {
            return $cached_result;
        }

        $has_virtual = false;
        $has_physical = false;

        // Check all cart items for product types
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['data']->is_virtual()) {
                $has_virtual = true;
            } else {
                $has_physical = true;
            }

            // If we have both types, return true immediately
            if ($has_virtual && $has_physical) {
                $cached_hash = $current_hash;
                $cached_result = true;
                return true;
            }
        }

        $cached_hash = $current_hash;
        $cached_result = false;
        return false;
    }

    /**
     * Check if cart has both deposit-selected items and full payment items
     * Returns true if cart contains a mix of partial payment and normal products
     */
    public static function cart_has_deposit_mixed_products()
    {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        // Per-request cache keyed by cart hash
        static $cached_result = null;
        static $cached_hash = null;
        $current_hash = method_exists(WC()->cart, 'get_cart_hash') ? WC()->cart->get_cart_hash() : null;
        if ($cached_hash !== null && $current_hash !== null && $cached_hash === $current_hash && $cached_result !== null) {
            return $cached_result;
        }

        $has_deposit = false;
        $has_full_payment = false;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $is_deposit = false;
            if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
                $is_deposit = true;
            } elseif (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                $is_deposit = true;
            } elseif (isset($cart_item['deposit_amount']) && floatval($cart_item['deposit_amount']) > 0) {
                $is_deposit = true;
            }

            if ($is_deposit) {
                $has_deposit = true;
            } else {
                $has_full_payment = true;
            }

            if ($has_deposit && $has_full_payment) {
                $cached_hash = $current_hash;
                $cached_result = true;
                return true;
            }
        }

        $cached_hash = $current_hash;
        $cached_result = false;
        return false;
    }

    /**
     * Disable checkout button when cart has mixed product types
     * This function adds JavaScript to disable the checkout button on cart page
     */
    public static function disable_checkout_button_for_mixed_products()
    {
        // Only run on cart page
        if (!is_cart()) {
            return;
        }

        // Check if cart has mixed product types or deposit mixing
        if (self::cart_has_mixed_product_types() || self::cart_has_deposit_mixed_products()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Disable checkout button and add visual indication
                var checkoutButton = $('.wc-proceed-to-checkout .checkout-button, .cart-totals .checkout-button, a[href*="checkout"]').filter(function() {
                    return $(this).attr('href') && $(this).attr('href').indexOf('checkout') !== -1;
                });
                
                if (checkoutButton.length > 0) {
                    checkoutButton.addClass('disabled').attr('disabled', true);
                    checkoutButton.css({
                        'opacity': '0.5',
                        'cursor': 'not-allowed',
                        'pointer-events': 'none'
                    });
                    
                    // Add tooltip or message
                    checkoutButton.attr('title', 'Please remove either virtual/ESD mixing or partial payment mixing to proceed');
                }
            });
            </script>
            <?php
        }
    }

    /**
     * AJAX endpoint to check for mixed products in cart
     * For WooCommerce native AJAX (wc_ajax)
     */
    public static function ajax_check_mixed_products()
    {
        try {
            // Ensure WooCommerce is loaded
            if (!function_exists('WC') || !WC()->cart) {
                wp_send_json_error(array(
                    'message' => 'Cart not available',
                    'debug' => 'WC or cart not loaded'
                ));
                return;
            }

            $has_mixed_products = self::cart_has_mixed_product_types();
            $has_deposit_mixed = self::cart_has_deposit_mixed_products();
            // Detect if cart has both any ESD (virtual) product and any deposit-selected item
            $any_virtual = false;
            $any_deposit = false;
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!$any_virtual && isset($cart_item['data']) && is_object($cart_item['data']) && method_exists($cart_item['data'], 'is_virtual') && $cart_item['data']->is_virtual()) {
                    $any_virtual = true;
                }
                if (!$any_deposit) {
                    $is_deposit = false;
                    if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
                        $is_deposit = true;
                    } elseif (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                        $is_deposit = true;
                    } elseif (isset($cart_item['deposit_amount']) && floatval($cart_item['deposit_amount']) > 0) {
                        $is_deposit = true;
                    }
                    if ($is_deposit) {
                        $any_deposit = true;
                    }
                }
                if ($any_virtual && $any_deposit) {
                    break;
                }
            }
            $has_deposit_esd = ($any_virtual && $any_deposit);
            // Provide cart item count for UI logic in side cart
            $cart_count = WC()->cart->get_cart_contents_count();
            
            // Check if there's a mixed product conflict from session
            $has_conflict = WC()->session->get('mixed_product_conflict', false);
            
            // If we have mixed products or a conflict, clear the conflict flag
            if ($has_mixed_products || $has_deposit_mixed || $has_conflict) {
                WC()->session->set('mixed_product_conflict', false);
            }
            
            wp_send_json_success(array(
                // Only report virtual/physical mixing here; deposit mixing is separate
                'has_mixed_products' => $has_mixed_products,
                'has_deposit_mixed' => $has_deposit_mixed,
                'has_deposit_esd' => $has_deposit_esd,
                'cart_count' => $cart_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error checking cart',
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for cart item removal validation
     * This checks for mixed products after item removal and updates checkout button state
     */
    public static function ajax_validate_cart_after_removal()
    {
        // Only run on cart page
        if (!is_cart()) {
            return;
        }

        // Add comprehensive cart validation script
        add_action('wp_footer', function() {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Debounce helper
                function debounce(fn, wait) {
                    var t;
                    return function() {
                        var ctx = this, args = arguments;
                        clearTimeout(t);
                        t = setTimeout(function(){ fn.apply(ctx, args); }, wait || 120);
                    };
                }

                // Function to check and update checkout button state
                var ajaxUrl = (window.wc_add_to_cart_params && wc_add_to_cart_params.wc_ajax_url)
                    ? wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'check_mixed_products')
                    : null;

                function updateCheckoutButtonState() {
                    if (!ajaxUrl) { return; }

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {},  // No need for action or security with wc_ajax
                        success: function(response) {
                                // Only proceed if we have a valid response
                                if (!response || !response.hasOwnProperty('success')) {
                                    return; // Don't change button state if response is invalid
                                }
                                // More comprehensive checkout button selector
                                var checkoutButton = $('.wc-proceed-to-checkout .checkout-button, .cart-totals .checkout-button, .wc-proceed-to-checkout a, .checkout-button, a.checkout-button, .wc-forward, a[href*="checkout"]');
                                
                                // Remove any existing mixed product notices
                                $('.mixed-products-notice').remove();
                                
                                if (response.success && response.data && response.data.has_mixed_products) {
                                    // Disable checkout button
                                    checkoutButton.addClass('disabled').attr('disabled', true);
                                    checkoutButton.css({
                                        'opacity': '0.5',
                                        'cursor': 'not-allowed',
                                        'pointer-events': 'none'
                                    });
                                    checkoutButton.attr('title', 'Please remove either virtual/ESD mixing or partial payment mixing to proceed');
                                } else if (response.success && response.data && !response.data.has_mixed_products) {
                                    // Only enable if we explicitly know there are no mixed products
                                    checkoutButton.removeClass('disabled').removeAttr('disabled');
                                    checkoutButton.css({
                                        'opacity': '1',
                                        'cursor': 'pointer',
                                        'pointer-events': 'auto'
                                    });
                                    checkoutButton.removeAttr('title');
                                }
                            },
                            error: function(xhr, status, error) {
                                // Maintain current button state on error
                            }
                    });
                }

                // Listen for WooCommerce cart updated event
                $(document.body).off('updated_cart_totals.sh').on('updated_cart_totals.sh', debounce(function() {
                    updateCheckoutButtonState();
                }, 150));

                // Initial check on page load - commented out as mentioned in original
                // updateCheckoutButtonState();
            });
            </script>
            <?php
        });
    }

    /**
     * Validate cart after item restoration (undo)
     * This method is called when a cart item is restored via the undo functionality
     * 
     * @param string $cart_item_key The cart item key that was restored
     * @param WC_Cart $cart_instance The cart instance
     */
    public static function validate_cart_after_item_restored($cart_item_key, $cart_instance)
    {
        // Only run on cart page
        if (!is_cart()) {
            return;
        }

        // Check if cart has mixed product types after restoration
        if (self::cart_has_mixed_product_types()) {
            // Add JavaScript to trigger frontend validation
            add_action('wp_footer', function() {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Single validation check after undo
                    if (typeof updateCheckoutButtonState === 'function') {
                        updateCheckoutButtonState();
                    }
                });
                </script>
                <?php
            }, 5);
        }
    }

    /**
     * Initialize deposit functionality hooks
     */
    public static function init_deposit_hooks()
    {
        // Cart item data modification for deposits
        add_filter('woocommerce_get_cart_item_from_session', array(__CLASS__, 'get_cart_item_from_session'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array(__CLASS__, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_cart_item_name', array(__CLASS__, 'cart_item_name'), 10, 3);
        add_filter('woocommerce_cart_item_subtotal', array(__CLASS__, 'cart_item_subtotal'), 10, 3);
        add_action('woocommerce_cart_calculate_fees', array(__CLASS__, 'calculate_deposit_fees'));
        add_filter('woocommerce_cart_totals_coupon_html', array(__CLASS__, 'cart_totals_coupon_html'), 10, 3);
    }

    /**
     * Get cart item from session with deposit data
     */
    public static function get_cart_item_from_session($cart_item, $values, $key)
    {
        if (isset($values['deposit_option'])) {
            $cart_item['deposit_option'] = $values['deposit_option'];
        }
        if (isset($values['deposit_amount'])) {
            $cart_item['deposit_amount'] = $values['deposit_amount'];
        }
        if (isset($values['deposit_percentage'])) {
            $cart_item['deposit_percentage'] = $values['deposit_percentage'];
        }
        return $cart_item;
    }

    /**
     * Add deposit data to cart item
     */
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        if (isset($_POST['deposit_option'])) {
            $cart_item_data['deposit_option'] = sanitize_text_field($_POST['deposit_option']);
            
            if ($_POST['deposit_option'] === 'deposit') {
                $product = wc_get_product($product_id);
                $deposit_type = get_post_meta($product_id, '_deposit_type', true);
                $deposit_amount = get_post_meta($product_id, '_deposit_amount', true);
                
                if ($deposit_type === 'fixed') {
                    $cart_item_data['deposit_amount'] = floatval($deposit_amount);
                } elseif ($deposit_type === 'percentage') {
                    $cart_item_data['deposit_percentage'] = floatval($deposit_amount);
                    $cart_item_data['deposit_amount'] = ($product->get_price() * $deposit_amount) / 100;
                }
            }
        } elseif (isset($_POST['awcdp_deposit_option'])) {
            // Map AWCDP deposit option to our legacy deposit_option to ensure unique cart IDs
            $awcdp_option = sanitize_text_field($_POST['awcdp_deposit_option']);
            $cart_item_data['deposit_option'] = ($awcdp_option === 'yes') ? 'deposit' : 'full';

            if ($awcdp_option === 'yes') {
                // Compute deposit amount using AWCDP meta settings
                $base_product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                $base_price = $base_product ? floatval($base_product->get_price()) : 0;

                $deposit_amount_meta = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
                $deposit_type_meta = get_post_meta($product_id, '_awcdp_deposit_type', true);

                if (!empty($deposit_amount_meta)) {
                    if ($deposit_type_meta === 'percent') {
                        $cart_item_data['deposit_percentage'] = floatval($deposit_amount_meta);
                        $cart_item_data['deposit_amount'] = ($base_price * floatval($deposit_amount_meta)) / 100;
                    } else {
                        // Default to fixed deposit amount
                        $cart_item_data['deposit_amount'] = floatval($deposit_amount_meta);
                    }
                }
            }
        }

        // Include trade-in in cart item data to differentiate cart IDs when needed
        if (isset($_POST['trade_in'])) {
            $cart_item_data['trade_in'] = sanitize_text_field($_POST['trade_in']);
        }
        return $cart_item_data;
    }

    /**
     * Modify cart item name to show deposit info
     * Note: Trade-in and payment option info is now handled by display_trade_in_info_universal
     * to prevent duplication and ensure proper positioning below variations
     */
    public static function cart_item_name($name, $cart_item, $cart_item_key)
    {
        // Do not append deposit info on cart page (handled by modify_cart_item_name)
        if (function_exists('is_cart') && is_cart()) {
            return $name;
        }

        // Remove deposit small tags from side cart / mini-cart and checkout
        // Side cart and checkout will present deposit info via other dedicated flows
        if ((function_exists('is_cart') && !is_cart()) && (function_exists('is_checkout') && !is_checkout())) {
            return $name;
        }

        // Fallback: no change
        return $name;
    }

    /**
     * Modify cart item subtotal for deposits
     */
    public static function cart_item_subtotal($subtotal, $cart_item, $cart_item_key)
    {
        // Only modify subtotal on the cart page; let mini cart show full product subtotal via WooCommerceAddtoCartController
        if (function_exists('is_cart') && !is_cart()) {
            return $subtotal;
        }
        if (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
            if (isset($cart_item['deposit_amount'])) {
                $deposit_amount = $cart_item['deposit_amount'] * $cart_item['quantity'];
                $subtotal = wc_price($deposit_amount);
            }
        }
        return $subtotal;
    }

    /**
     * Calculate deposit fees for cart
     */
    public static function calculate_deposit_fees()
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Do not add any deposit fee lines on the cart page
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        $cart = WC()->cart;
        $has_deposits = false;

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                $has_deposits = true;
                break;
            }
        }

        // No fee line is added here; deposit and remaining are rendered as display-only rows in checkout totals
    }

    /**
     * Modify coupon display for deposit orders
     */
    public static function cart_totals_coupon_html($coupon_html, $coupon, $discount_amount_html)
    {
        // Check if cart has deposits and modify coupon display accordingly
        $cart = WC()->cart;
        $has_deposits = false;

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                $has_deposits = true;
                break;
            }
        }

        if ($has_deposits) {
            $coupon_html .= ' <small>(' . __('Applied to deposit amount', 'senheng-core') . ')</small>';
        }

        return $coupon_html;
    }

    public static function disable_shipping_calc_on_cart($enabled)
    {
        if (!function_exists('is_cart') || !is_cart()) {
            return $enabled;
        }
        return false;
    }

    public static function disable_needs_shipping_on_cart($needs)
    {
        if (!function_exists('is_cart') || !is_cart()) {
            return $needs;
        }
        return false;
    }

    /**
     * Display maintenance and security fees disclaimer before order total
     */
    public static function display_maintenance_fees_disclaimer()
    {
        echo '<tr class="sh-maintenance-fees-disclaimer">
            <td colspan="2" style="text-align: right; font-size: 14px; color: #666; font-family: var(--wd-text-font);">
                <p style="margin-bottom: 0 !important;">* Subject to 5% maintenance and security fees</p>
            </td>
        </tr>';
    }
}
