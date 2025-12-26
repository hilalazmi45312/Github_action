<?php
/**
 * Plugin Name: Minimum Order Value for WooCommerce
 * Plugin URI: https://grid-developer.vercel.app/products/minimum-order-woocommerce
 * Description: Set a minimum order value for WooCommerce checkout.
 * Version: 1.0.1
 * Author: griddeveloper7
 * Author URI: https://grid-developer.vercel.app/
 * License: GPLv2 or later
 * Text Domain: minimum-order-value-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Since WordPress 4.6, language packs are auto-loaded from translate.wordpress.org
// when the text domain matches the plugin slug. No need to call load_plugin_textdomain().

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * 
 * This plugin is compatible with HPOS because it:
 * - Does not directly access order data
 * - Only works with cart data (WC()->cart)
 * - Uses standard WooCommerce hooks and functions
 * - Does not use custom post meta for orders
 */
add_action( 'before_woocommerce_init', 'wcmo_declare_hpos_compatibility' );

function wcmo_declare_hpos_compatibility() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}

/**
 * Enqueue scripts and styles for frontend
 */
add_action( 'wp_enqueue_scripts', 'wcmo_enqueue_frontend_assets' );

function wcmo_enqueue_frontend_assets() {
    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }

    // Enqueue jQuery (WooCommerce dependency)
    wp_enqueue_script( 'jquery' );

    // Add inline CSS for notices
    $css = '
        /* WooCommerce Blocks Notice Styling */
        .wcmo-info-notice {
            margin-bottom: 20px !important;
        }
        .wcmo-info-notice svg {
            flex-shrink: 0;
            margin-right: 12px;
            fill: #ffffff !important;
        }
        .wcmo-error-notice {
            animation: wcmo-shake 0.5s !important;
        }
        .wcmo-error-notice .wc-block-components-notice-banner > svg {
            fill: #ffffff !important;
        }
        .wcmo-cart-info-notice { 
            margin-bottom: 20px !important; 
        }
        
        /* Shake animation for error */
        @keyframes wcmo-shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    ';
    wp_add_inline_style( 'woocommerce-general', $css );
    
    // Check if we need to add checkout blocking script
    if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
        wcmo_maybe_add_checkout_script();
    }
    
    // Check if we need to add cart notice script
    if ( is_cart() ) {
        wcmo_maybe_add_cart_script();
    }
}

/**
 * Add checkout blocking script if minimum order is not met
 */
function wcmo_maybe_add_checkout_script() {
    $minimum = get_option( 'woocommerce_min_order_value', '' );
    
    if ( empty( $minimum ) || $minimum <= 0 ) {
        return;
    }
    
    if ( ! WC()->cart ) {
        return;
    }
    
    $subtotal = wcmo_calculate_cart_subtotal();
    
    // If subtotal is 0 or negative, all products are excluded - don't show notice or block
    if ( $subtotal <= 0 ) {
        return;
    }
    
    if ( $subtotal < $minimum ) {
        // Build an error message for checkout
        $checkout_message_template = get_option( 
            'woocommerce_min_order_message_checkout',
            __( 'Your order total must be at least {minimum_amount}.', 'minimum-order-value-for-woocommerce' )
        );
        $remaining = $minimum - $subtotal;
        $checkout_message = wcmo_replace_placeholders( $checkout_message_template, $minimum, $remaining );
        $checkout_message_plain = wp_strip_all_tags( $checkout_message );
        
        // Add JavaScript inline to handle checkout blocking
        $script = '
        jQuery(document).ready(function($) {
            var errorIcon = \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg>\';
            var dismissIcon = \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>\';
            var errorNoticeHtml = \'<div class="wc-block-components-notices wcmo-error-notice"><div class="wc-block-store-notice wc-block-components-notice-banner is-error is-dismissible">\' + errorIcon + \'<div class="wc-block-components-notice-banner__content"><div>' . esc_js( $checkout_message_plain ) . '</div></div><button class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained" aria-label="Dismiss this notice" type="button">\' + dismissIcon + \'</button></div></div>\';
            
            function showErrorNotice() {
                $(\'.wcmo-error-notice\').remove();
                if ($(\'form.checkout\').length) {
                    $(\'form.checkout\').prepend(errorNoticeHtml);
                } else if ($(\'.wp-block-woocommerce-checkout\').length) {
                    $(\'.wp-block-woocommerce-checkout\').prepend(errorNoticeHtml);
                } else if ($(\'.woocommerce-checkout\').length) {
                    $(\'.woocommerce-checkout\').prepend(errorNoticeHtml);
                } else if ($(\'.woocommerce\').length) {
                    $(\'.woocommerce\').prepend(errorNoticeHtml);
                } else if ($(\'#main\').length) {
                    $(\'#main\').prepend(errorNoticeHtml);
                }
                $(\'.wcmo-error-notice .wc-block-components-notice-banner__dismiss\').on(\'click\', function() {
                    $(\'.wcmo-error-notice\').fadeOut(300, function() { $(this).remove(); });
                });
                setTimeout(function() {
                    var errorElement = $(\'.wcmo-error-notice\');
                    if (errorElement.length) {
                        $(\'html, body\').animate({ scrollTop: errorElement.offset().top - 100 }, 300);
                    }
                }, 100);
            }
            
            showErrorNotice();
            window.wcmo_checkout_blocked = true;
            
            $(document.body).on(\'checkout_place_order\', function(e) { e.preventDefault(); showErrorNotice(); return false; });
            $(document).on(\'submit\', \'form.checkout, form[name="checkout"], .woocommerce-checkout form\', function(e) { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); showErrorNotice(); return false; });
            
            var blockButtons = \'#place_order, button[name="woocommerce_checkout_place_order"], .wc-block-components-checkout-place-order-button, input[name="woocommerce_checkout_place_order"], button[type="submit"]\';
            $(document).on(\'click\', blockButtons, function(e) {
                if ($(this).closest(\'form.checkout, .woocommerce-checkout\').length) {
                    e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); showErrorNotice(); return false;
                }
            });
            $(document).on(\'mousedown\', blockButtons, function(e) {
                if ($(this).closest(\'form.checkout, .woocommerce-checkout\').length) {
                    e.preventDefault(); e.stopPropagation(); showErrorNotice(); return false;
                }
            });
            $(document.body).on(\'checkout_error\', function() { showErrorNotice(); });
            $(document.body).on(\'checkout_place_order_before_processing\', function() { showErrorNotice(); return false; });
            
            setTimeout(function() {
                $(blockButtons).each(function() {
                    if ($(this).closest(\'form.checkout, .woocommerce-checkout\').length) {
                        var $btn = $(this);
                        $btn.off(\'click\');
                        $btn.on(\'click\', function(e) { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); showErrorNotice(); return false; });
                    }
                });
            }, 1000);
        });
        ';
        
        wp_add_inline_script( 'jquery', $script );
    }
}

/**
 * Add cart notice script if minimum order is not met
 */
function wcmo_maybe_add_cart_script() {
    $minimum = get_option( 'woocommerce_min_order_value', '' );
    if ( empty( $minimum ) || $minimum <= 0 ) {
        return;
    }
    if ( ! WC()->cart ) {
        return;
    }

    $subtotal = wcmo_calculate_cart_subtotal();
    if ( $subtotal <= 0 ) {
        return; // all items excluded
    }

    if ( $subtotal < $minimum ) {
        $message_template = get_option(
            'woocommerce_min_order_message_cart',
            __( 'You need {remaining_amount} more to reach the minimum order value of {minimum_amount}.', 'minimum-order-value-for-woocommerce' )
        );
        $remaining = $minimum - $subtotal;
        $message   = wcmo_replace_placeholders( $message_template, $minimum, $remaining );
        $message_plain = wp_strip_all_tags( $message );
        
        // Add cart JavaScript inline
        $cart_script = '
        jQuery(document).ready(function($) {
            var infoIcon = \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg>\';
            var infoNoticeHtml = \'<div class="wc-block-components-notice-banner is-info wcmo-cart-info-notice wcmo-info-notice">\' + infoIcon + \'<div class="wc-block-components-notice-banner__content"><div>' . esc_js( $message_plain ) . '</div></div></div>\';
            if ($(\'.wp-block-woocommerce-cart\').length) {
                $(\'.wcmo-info-notice\').remove();
                $(\'.wp-block-woocommerce-cart\').prepend(infoNoticeHtml);
            }
        });
        ';
        
        wp_add_inline_script( 'jquery', $cart_script );
    }
}

/**
 * Add setting fields in WooCommerce > Settings > General
 */
add_filter( 'woocommerce_general_settings', 'wcmo_add_setting_field' );
function wcmo_add_setting_field( $settings ) {
    $updated_settings = [];
    foreach ( $settings as $section ) {
        $updated_settings[] = $section;
        if ( isset( $section['id'] ) && 'general_options' === $section['id'] && 'sectionend' === $section['type'] ) {
            
            // Section Title
            $updated_settings[] = array(
                'title' => __( 'Minimum Order Settings', 'minimum-order-value-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => __( 'Configure minimum order requirements for your store.', 'minimum-order-value-for-woocommerce' ),
                'id'    => 'wcmo_minimum_order_section',
            );
            
            // Minimum Order Value
            $updated_settings[] = array(
                'title'    => __( 'Minimum Order Value', 'minimum-order-value-for-woocommerce' ),
                'desc'     => __( 'Set a minimum order total required for checkout. Leave empty to disable.', 'minimum-order-value-for-woocommerce' ),
                'id'       => 'woocommerce_min_order_value',
                'default'  => '',
                'type'     => 'number',
                'desc_tip' => true,
                'autoload' => false,
                'custom_attributes' => array(
                    'min'  => 0,
                    'step' => 0.01,
                ),
            );
            
            // Section End
            $updated_settings[] = array(
                'type' => 'sectionend',
                'id'   => 'wcmo_minimum_order_section',
            );
            
            // Message Templates Section
            $updated_settings[] = array(
                'title' => __( 'Customer Messages', 'minimum-order-value-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => __( 'Customize the messages shown to customers. Use {minimum_amount} and {remaining_amount} as placeholders.', 'minimum-order-value-for-woocommerce' ),
                'id'    => 'wcmo_messages_section',
            );
            
            // Cart Message Template
            $updated_settings[] = array(
                'title'    => __( 'Cart Page Message', 'minimum-order-value-for-woocommerce' ),
                'desc'     => __( 'Message displayed on the cart page when the minimum order value is not met.', 'minimum-order-value-for-woocommerce' ),
                'id'       => 'woocommerce_min_order_message_cart',
                'default'  => __( 'You need {remaining_amount} more to reach the minimum order value of {minimum_amount}.', 'minimum-order-value-for-woocommerce' ),
                'type'     => 'textarea',
                'desc_tip' => true,
                'autoload' => false,
                'css'      => 'width: 100%; min-height: 80px;',
            );
            
            // Checkout Error Message Template
            $updated_settings[] = array(
                'title'    => __( 'Checkout Error Message', 'minimum-order-value-for-woocommerce' ),
                'desc'     => __( 'Error message displayed during checkout when the minimum order value is not met.', 'minimum-order-value-for-woocommerce' ),
                'id'       => 'woocommerce_min_order_message_checkout',
                'default'  => __( 'Your order total must be at least {minimum_amount}.', 'minimum-order-value-for-woocommerce' ),
                'type'     => 'textarea',
                'desc_tip' => true,
                'autoload' => false,
                'css'      => 'width: 100%; min-height: 80px;',
            );
            
            // Section End
            $updated_settings[] = array(
                'type' => 'sectionend',
                'id'   => 'wcmo_messages_section',
            );
            
            // Exclusions Section
            $updated_settings[] = array(
                'title' => __( 'Product & Category Exclusions', 'minimum-order-value-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => __( 'Exclude specific products or categories from the minimum order calculation. Excluded items will not count toward the minimum.', 'minimum-order-value-for-woocommerce' ),
                'id'    => 'wcmo_exclusions_section',
            );
            
            // Exclude Products
            $updated_settings[] = array(
                'title'    => __( 'Exclude Products', 'minimum-order-value-for-woocommerce' ),
                'desc'     => __( 'Select products to exclude from minimum order calculation.', 'minimum-order-value-for-woocommerce' ),
                'id'       => 'woocommerce_min_order_exclude_products',
                'default'  => array(),
                'type'     => 'multiselect',
                'class'    => 'wc-enhanced-select',
                'desc_tip' => true,
                'autoload' => false,
                'options'  => wcmo_get_products_list(),
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Search and select products...', 'minimum-order-value-for-woocommerce' ),
                ),
            );
            
            // Exclude Categories
            $updated_settings[] = array(
                'title'    => __( 'Exclude Categories', 'minimum-order-value-for-woocommerce' ),
                'desc'     => __( 'Select categories to exclude from minimum order calculation. All products in these categories will be excluded.', 'minimum-order-value-for-woocommerce' ),
                'id'       => 'woocommerce_min_order_exclude_categories',
                'default'  => array(),
                'type'     => 'multiselect',
                'class'    => 'wc-enhanced-select',
                'desc_tip' => true,
                'autoload' => false,
                'options'  => wcmo_get_categories_list(),
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Search and select categories...', 'minimum-order-value-for-woocommerce' ),
                ),
            );
            
            // Section End
            $updated_settings[] = array(
                'type' => 'sectionend',
                'id'   => 'wcmo_exclusions_section',
            );
        }
    }
    return $updated_settings;
}

/**
 * Get list of products for multiselect field
 * 
 * @return array Array of product ID => product name
 */
function wcmo_get_products_list() {
    $products = array();
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    
    $query = new WP_Query( $args );
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $products[ get_the_ID() ] = get_the_title();
        }
        wp_reset_postdata();
    }
    
    return $products;
}

/**
 * Get list of product categories for multiselect field
 * 
 * @return array Array of category ID => category name
 */
function wcmo_get_categories_list() {
    $categories = array();
    $terms = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $categories[ $term->term_id ] = $term->name;
        }
    }
    
    return $categories;
}

/**
 * Validate minimum order amount on checkout
 * Prevents checkout if cart subtotal is below minimum order value
 * 
 * Theme Compatibility:
 * - Uses standard WooCommerce hook: woocommerce_checkout_process
 * - Uses wc_add_notice() for proper theme compatibility
 * - Also blocks WooCommerce Blocks checkout via store API
 * - Tested with: Storefront, Astra, Elementor Hello, Divi, Twenty Twenty-Five
 * - Works with any theme that follows WooCommerce standards
 */
add_action( 'woocommerce_checkout_process', 'wcmo_validate_minimum_order_on_checkout', 1 );
add_action( 'woocommerce_after_checkout_validation', 'wcmo_validate_minimum_order_on_checkout', 1, 2 );
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'wcmo_validate_minimum_order_on_checkout', 1, 2 );

function wcmo_validate_minimum_order_on_checkout( $data = null, $errors = null ) {
    $minimum = get_option( 'woocommerce_min_order_value', '' );
    
    // If empty or not set, feature is disabled
    if ( empty( $minimum ) || $minimum <= 0 ) {
        return;
    }

    // Check if cart exists
    if ( ! WC()->cart ) {
        return;
    }

    // Get cart subtotal (excluding specified products/categories)
    $subtotal = wcmo_calculate_cart_subtotal();
    
    // If subtotal is 0 or negative, it means ALL products in cart are excluded
    // In this case, allow checkout (don't enforce minimum on excluded products only)
    if ( $subtotal <= 0 ) {
        return;
    }

    // Validate subtotal against minimum (only if there are non-excluded products)
    if ( $subtotal < $minimum ) {
        // Get custom message template
        $message_template = get_option( 
            'woocommerce_min_order_message_checkout', 
            __( 'Your order total must be at least {minimum_amount}.', 'minimum-order-value-for-woocommerce' )
        );
        
        // Calculate remaining amount
        $remaining = $minimum - $subtotal;
        
        // Replace placeholders
        $message = wcmo_replace_placeholders( $message_template, $minimum, $remaining );
        
        // Add error notice - this will block checkout
        wc_add_notice( $message, 'error' );
        
        // If $errors object is available (from woocommerce_after_checkout_validation), add error there too
        if ( $errors && is_wp_error( $errors ) ) {
            $errors->add( 'minimum_order_amount', $message );
        }
    }
}

/**
 * Display helpful message on cart and checkout pages
 * Shows remaining amount needed to reach minimum order value
 * 
 * Theme Compatibility:
 * - Uses wc_print_notice() for immediate display on checkout
 * - Uses multiple hooks to ensure visibility
 * - JavaScript fallback for themes with custom checkout layouts (like Twenty Twenty-Five)
 * - Works with any theme that follows WooCommerce standards
 */
// Show guidance notice only on the cart page
add_action( 'woocommerce_before_cart', 'wcmo_display_minimum_notice' );
// Extra hook to improve compatibility with some classic cart templates
add_action( 'woocommerce_before_cart_table', 'wcmo_display_minimum_notice' );

function wcmo_display_minimum_notice() {
    static $wcmo_cart_notice_printed = false;
    if ( $wcmo_cart_notice_printed ) {
        return;
    }
    $minimum = get_option( 'woocommerce_min_order_value', '' );
    
    // If empty or not set, feature is disabled
    if ( empty( $minimum ) || $minimum <= 0 ) {
        return;
    }

    // Check if cart exists
    if ( ! WC()->cart ) {
        return;
    }

    // Get cart subtotal (excluding specified products/categories)
    $subtotal = wcmo_calculate_cart_subtotal();

    // If subtotal is 0 or negative, all products are excluded - don't show notice
    if ( $subtotal <= 0 ) {
        return;
    }

    // Show notice if below minimum
    if ( $subtotal < $minimum ) {
        // Get custom message template
        $message_template = get_option( 
            'woocommerce_min_order_message_cart', 
            __( 'You need {remaining_amount} more to reach the minimum order value of {minimum_amount}.', 'minimum-order-value-for-woocommerce' )
        );
        
        // Calculate remaining amount needed
        $remaining = $minimum - $subtotal;
        
        // Replace placeholders
        $message = wcmo_replace_placeholders( $message_template, $minimum, $remaining );
        
    // Try standard WooCommerce notice first
        wc_print_notice( $message, 'notice' );
        
        // FALLBACK: If theme doesn't render notices properly, output directly
        // This ensures notice is visible even on non-standard themes
        echo '<div class="woocommerce-info wcmo-direct-notice" style="margin: 20px 0; padding: 15px; background: #e5f5ff; border-left: 4px solid #2196F3; color: #0d5a8f; font-size: 14px; line-height: 1.5;">';
    echo '<strong>' . esc_html__( 'Notice:', 'minimum-order-value-for-woocommerce' ) . '</strong> ';
        echo wp_kses_post( $message );
        echo '</div>';

        // Prevent duplicate printing if this function is hooked multiple times
        $wcmo_cart_notice_printed = true;
    }
}

/**
 * Calculate cart subtotal excluding specified products and categories
 * 
 * @return float Cart subtotal excluding specified products/categories
 */
function wcmo_calculate_cart_subtotal() {
    if ( ! WC()->cart ) {
        return 0;
    }
    
    $subtotal = 0;
    $excluded_products = get_option( 'woocommerce_min_order_exclude_products', array() );
    $excluded_categories = get_option( 'woocommerce_min_order_exclude_categories', array() );
    
    // Ensure arrays
    $excluded_products = is_array( $excluded_products ) ? $excluded_products : array();
    $excluded_categories = is_array( $excluded_categories ) ? $excluded_categories : array();
    
    // Convert excluded products to integers for proper comparison
    $excluded_products = array_map( 'intval', $excluded_products );
    $excluded_categories = array_map( 'intval', $excluded_categories );
    
    // Loop through cart items
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        $line_subtotal = $cart_item['line_subtotal'];
        
        // Use variation ID if it's a variable product, otherwise use product ID
        $check_id = $variation_id > 0 ? $variation_id : $product_id;
        
        // Check if product (or variation) is excluded
        if ( in_array( $product_id, $excluded_products ) || in_array( $check_id, $excluded_products ) ) {
            continue; // Skip this product - don't add to subtotal
        }
        
        // Check if product belongs to excluded category
        $product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
        $is_excluded_category = false;
        
        if ( ! empty( $product_categories ) && ! empty( $excluded_categories ) ) {
            foreach ( $product_categories as $cat_id ) {
                if ( in_array( $cat_id, $excluded_categories ) ) {
                    $is_excluded_category = true;
                    break;
                }
            }
        }
        
        // Only add to subtotal if NOT excluded by category
        if ( ! $is_excluded_category ) {
            $subtotal += $line_subtotal;
        }
    }
    
    return $subtotal;
}

/**
 * Replace placeholders in message templates
 * 
 * @param string $template Message template with placeholders
 * @param float $minimum Minimum order amount
 * @param float $remaining Remaining amount to reach minimum
 * @return string Message with placeholders replaced
 */
function wcmo_replace_placeholders( $template, $minimum, $remaining ) {
    $placeholders = array(
        '{minimum_amount}'   => wc_price( $minimum ),
        '{remaining_amount}' => wc_price( $remaining ),
    );
    
    return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
}

/**
 * Debug function - Display current plugin status
 * Add ?wcmo_debug=1 to cart or checkout URL to see debug info
 * Example: yourdomain.com/checkout/?wcmo_debug=1
 */
add_action( 'wp_footer', 'wcmo_debug_info' );

function wcmo_debug_info() {
    // Only show if debug parameter with valid nonce is set and user can manage options
    $debug_flag  = isset( $_GET['wcmo_debug'] ) ? sanitize_text_field( wp_unslash( $_GET['wcmo_debug'] ) ) : '';
    $debug_nonce = isset( $_GET['wcmo_debug_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['wcmo_debug_nonce'] ) ) : '';
    if ( '1' !== $debug_flag ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( empty( $debug_nonce ) || ! wp_verify_nonce( $debug_nonce, 'wcmo_debug' ) ) {
        return;
    }
    
    // Only show on cart or checkout pages
    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }
    
    $minimum = get_option( 'woocommerce_min_order_value', '' );
    $cart_message = get_option( 'woocommerce_min_order_message_cart', '' );
    $checkout_message = get_option( 'woocommerce_min_order_message_checkout', '' );
    $excluded_products = get_option( 'woocommerce_min_order_exclude_products', array() );
    $excluded_categories = get_option( 'woocommerce_min_order_exclude_categories', array() );
    
    $subtotal = 0;
    $cart_total = 0;
    if ( WC()->cart ) {
        $subtotal = wcmo_calculate_cart_subtotal();
        $cart_total = WC()->cart->subtotal;
    }
    
    echo '<div style="position: fixed; bottom: 0; left: 0; right: 0; background: #000; color: #0f0; padding: 20px; font-family: monospace; font-size: 12px; z-index: 999999; max-height: 300px; overflow-y: auto;">';
    echo '<h3 style="color: #0f0; margin-top: 0;">WC Minimum Order - Debug Info</h3>';
    echo '<strong>Plugin Status:</strong> ' . esc_html__( 'Active', 'minimum-order-value-for-woocommerce' ) . '<br>';
    $min_display = empty( $minimum )
        ? esc_html__( 'NOT SET (Feature Disabled)', 'minimum-order-value-for-woocommerce' )
        : wp_kses_post( wc_price( $minimum ) );
    echo '<strong>Minimum Order Value:</strong> ' . wp_kses_post( $min_display ) . '<br>';
    echo '<strong>Cart Subtotal (WooCommerce):</strong> ' . wp_kses_post( wc_price( $cart_total ) ) . '<br>';
    echo '<strong>Calculated Subtotal (with exclusions):</strong> ' . wp_kses_post( wc_price( $subtotal ) ) . '<br>';
    echo '<strong>Excluded Products:</strong> ' . esc_html( empty( $excluded_products ) ? 'None' : ( (int) count( $excluded_products ) . ' products' ) ) . '<br>';
    echo '<strong>Excluded Categories:</strong> ' . esc_html( empty( $excluded_categories ) ? 'None' : ( (int) count( $excluded_categories ) . ' categories' ) ) . '<br>';
    echo '<strong>Should Show Notice:</strong> ' . esc_html( ( ! empty( $minimum ) && $minimum > 0 && $subtotal < $minimum ) ? 'YES' : 'NO' ) . '<br>';
    echo '<strong>Should Block Checkout:</strong> ' . esc_html( ( ! empty( $minimum ) && $minimum > 0 && $subtotal < $minimum ) ? 'YES' : 'NO' ) . '<br>';
    
    if ( ! empty( $minimum ) && $minimum > 0 && $subtotal < $minimum ) {
        $remaining = $minimum - $subtotal;
    echo '<strong style="color: #ff0;">Remaining Amount Needed:</strong> ' . wp_kses_post( wc_price( $remaining ) ) . '<br>';
    }
    
    // Show cart items breakdown
    if ( WC()->cart && ! WC()->cart->is_empty() ) {
        echo '<hr style="border-color: #0f0;">';
        echo '<strong>Cart Items Breakdown:</strong><br>';
        
        $excluded_products = get_option( 'woocommerce_min_order_exclude_products', array() );
        $excluded_categories = get_option( 'woocommerce_min_order_exclude_categories', array() );
        $excluded_products = array_map( 'intval', is_array( $excluded_products ) ? $excluded_products : array() );
        $excluded_categories = array_map( 'intval', is_array( $excluded_categories ) ? $excluded_categories : array() );
        
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];
            $check_id = $variation_id > 0 ? $variation_id : $product_id;
            $line_subtotal = $cart_item['line_subtotal'];
            
            // Check if excluded by product
            $excluded_by_product = in_array( $product_id, $excluded_products ) || in_array( $check_id, $excluded_products );
            
            // Check if excluded by category
            $product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            $excluded_by_category = false;
            if ( ! empty( $product_categories ) && ! empty( $excluded_categories ) ) {
                foreach ( $product_categories as $cat_id ) {
                    if ( in_array( $cat_id, $excluded_categories ) ) {
                        $excluded_by_category = true;
                        break;
                    }
                }
            }
            
            $status = 'COUNTED';
            $color = '#0f0';
            if ( $excluded_by_product ) {
                $status = 'EXCLUDED (Product)';
                $color = '#f00';
            } elseif ( $excluded_by_category ) {
                $status = 'EXCLUDED (Category)';
                $color = '#f00';
            }
            
            echo '<span style="color: ' . esc_attr( $color ) . ';">• ' . esc_html( $product->get_name() ) . ' (ID: ' . absint( $product_id ) . ') - ' . wp_kses_post( wc_price( $line_subtotal ) ) . ' - ' . esc_html( $status ) . '</span><br>';
        }
    }
    
    echo '<hr style="border-color: #0f0;">';
    echo '<strong>Cart Message Template:</strong><br>' . esc_html( $cart_message ) . '<br><br>';
    echo '<strong>Checkout Message Template:</strong><br>' . esc_html( $checkout_message ) . '<br>';
    echo '<hr style="border-color: #0f0;">';
    echo '<small>Remove ?wcmo_debug=1 from URL to hide this panel</small>';
    echo '</div>';
}

