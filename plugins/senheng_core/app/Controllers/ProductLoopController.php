<?php

namespace SenhengCore\Controllers;

use WC_Product;
use WC_Product_Variable;
use SenhengCore\Controllers\ScoinController;

class ProductLoopController
{
    public static $enable_swatches = false;
    public static $enable_swatch_image = false;
    public static $defer_custom_price = true;
    public static $defer_custom_rating = true;
    public static $lightweight_variable = true;
    
    /**
     * Per-request cache for WCPB badges to avoid N+1 queries
     */
    private static $wcpb_badges_cache = null;
    private static $wcpb_badges_meta_cache = [];
    /**
     * Initialize the controller
     */
    public static function init()
    {
        // High priority template override hooks to ensure our templates are used
        add_filter('wc_get_template', [self::class, 'locate_custom_template'], 5, 3);
        add_filter('woocommerce_locate_template', [self::class, 'locate_custom_template'], 5, 3);
        add_filter('wc_get_template_part', [self::class, 'override_content_product'], 5, 3);        
         
        // Template override filters registered
         
        // Enqueue custom styles and scripts
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_styles']);
        
        // Ensure assets are available for AJAX requests
        add_action('wp_ajax_woodmart_get_products_shortcode', [self::class, 'enqueue_styles'], 1);
        add_action('wp_ajax_nopriv_woodmart_get_products_shortcode', [self::class, 'enqueue_styles'], 1);
        // add_action('wp_ajax_woodmart_get_products_tab_shortcode', [self::class, 'enqueue_styles'], 1);
        // add_action('wp_ajax_nopriv_woodmart_get_products_tab_shortcode', [self::class, 'enqueue_styles'], 1);

        // AJAX handlers
        add_action('wp_ajax_get_variation_image', [self::class, 'ajax_get_variation_image']);
        add_action('wp_ajax_nopriv_get_variation_image', [self::class, 'ajax_get_variation_image']);
        
        // Note: CSS and JS for AJAX requests are now handled directly in the template file
        
        // Custom price and rating display
        add_filter('woocommerce_get_price_html', [self::class, 'custom_price_display'], 10, 2);
        add_filter('woocommerce_product_get_rating_html', [self::class, 'custom_rating_display'], 10, 3);
        
        // Hook into WooCommerce product loop to ensure proper display order
        add_action('woocommerce_after_shop_loop_item_title', [self::class, 'ensure_proper_display_order'], 5);
        
        // WoodMart specific hooks
        add_action('after_setup_theme', [self::class, 'setup_woodmart_compatibility'], 15);
        
        // Filter WoodMart product labels to hide when WCPB badge is present
        add_filter('woodmart_product_label_output', [self::class, 'filter_woodmart_product_labels'], 20);
                
        // Posts slider override for WoodMart compatibility
        add_action('init', [self::class, 'setup_posts_slider_override'], 20);
        add_action('wp_footer', [self::class, 'print_defer_scripts']);
    }
    
    /**
     * Setup posts slider override for WoodMart compatibility
     */
    public static function setup_posts_slider_override()
    {
        // This method ensures compatibility with WoodMart's posts slider functionality
        // while maintaining our template override system
        if (function_exists('woodmart_get_theme_info')) {
            // Add any specific posts slider overrides here if needed
        }
    }

    /**
     * Locate custom templates in Senheng Core plugin
     * This ensures our WoodMart-based template overrides both theme and WooCommerce defaults
     */
    public static function locate_custom_template($template, $template_name, $template_path)
    {
        // Priority 1: Check for Senheng Core WoodMart-specific template
        $custom_template = SENHENG_CORE_PATH . 'app/Views/woodmart/woocommerce/' . $template_name;
        
        if (file_exists($custom_template)) {
            return $custom_template;
        }
        
        // Priority 2: Check for general Senheng Core template
        $general_template = SENHENG_CORE_PATH . 'app/Views/woocommerce/' . $template_name;
        
        if (file_exists($general_template)) {
            return $general_template;
        }
        
        return $template;
    }

    /**
 * Enqueue custom styles and scripts for product display
 */
public static function enqueue_styles()
{
    static $enqueued = false;
    
    // Prevent duplicate enqueuing
    if ($enqueued) {
        return;
    }
    $enqueued = true;
    
    // Use file modification time for cache busting
    $css_file = SENHENG_CORE_PATH . 'assets/css/product-loop.css';
    $js_file = SENHENG_CORE_PATH . 'assets/js/product-loop.js';
    $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';
    
    // Enqueue CSS
    wp_enqueue_style(
        'senheng-product-loop',
        SENHENG_CORE_URL . 'assets/css/product-loop.css',
        [],
        $css_version
    );

    // Enqueue JS with defer strategy for better performance
    wp_enqueue_script(
        'senheng-product-loop',
        SENHENG_CORE_URL . 'assets/js/product-loop.js',
        ['jquery', 'wc-add-to-cart'],
        $js_version,
        ['in_footer' => true, 'strategy' => 'defer']
    );

    // Enqueue WoodMart extras on relevant pages
    $is_ajax_request = function_exists('woodmart_is_woo_ajax') ? woodmart_is_woo_ajax() : false;
    $is_doing_ajax = wp_doing_ajax();
    
    if (is_woocommerce() || $is_ajax_request || is_search() || $is_doing_ajax) {
        // WoodMart extras
        if (function_exists('woodmart_enqueue_js_script')) {
            woodmart_enqueue_js_script('swatches-on-grid');
            woodmart_enqueue_js_script('swatches-variations');
        }

        if (function_exists('woodmart_enqueue_inline_style')) {
            woodmart_enqueue_inline_style('woo-mod-swatches-base');
        }
    }
}



    /**
     * Custom product thumbnails gallery with variation images
     */
    public static function custom_loop_product_thumbnails_gallery()
    {
        global $product;
        
        if (!$product instanceof WC_Product) {
            return;
        }

        if ($product instanceof WC_Product_Variable && !self::$lightweight_variable) {
            $variations = $product->get_available_variations();
            if (!empty($variations)) {
                $first_variation = reset($variations);
                if (!empty($first_variation['image']['src'])) {
                    echo '<div class="wd-product-image-wrap">';
                    echo '<img src="' . esc_url($first_variation['image']['src']) . '" alt="' . esc_attr($product->get_name()) . '" class="wd-product-image" />';
                    echo '</div>';
                    return;
                }
            }
        }
        
        // Fallback to default gallery
        woodmart_template_loop_product_thumbnails_gallery();
    }

    /**
     * Render color swatches for variations (color attributes only)
     * 
     */
    public static function render_color_swatches($product)
    {
        if (!self::$enable_swatches) {
            return;
        }
        if (!$product instanceof WC_Product_Variable) {
            return;
        }

        $attributes = $product->get_variation_attributes();
        
        foreach ($attributes as $attribute_name => $options) {
            // Only show swatches for color attributes
            if (strpos(strtolower($attribute_name), 'color') === false && 
                strpos(strtolower($attribute_name), 'colour') === false) {
                continue;
            }

            echo '<div class="senheng-color-swatches" data-attribute="' . esc_attr($attribute_name) . '">';
            
            foreach ($options as $option) {
                $term = get_term_by('slug', $option, $attribute_name);
                if ($term) {
                    $color_value = get_term_meta($term->term_id, 'color', true);
                    $image_value = get_term_meta($term->term_id, 'image', true);
                    
                    echo '<span class="swatch-item" data-value="' . esc_attr($option) . '" title="' . esc_attr($term->name) . '">';
                    
                    if ($image_value) {
                        echo '<img src="' . esc_url($image_value) . '" alt="' . esc_attr($term->name) . '" />';
                    } elseif ($color_value) {
                        echo '<span class="color-swatch" style="background-color: ' . esc_attr($color_value) . ';"></span>';
                    } else {
                        echo '<span class="text-swatch">' . esc_html($term->name) . '</span>';
                    }
                    
                    echo '</span>';
                }
            }
            
            echo '</div>';
        }
    }

    /**
     * Custom price display - show lowest price with strikethrough original
     */
    public static function custom_price_display($price_html, $product)
    {
        // Handle simple products (use get_type() method instead of instanceof)
        if ($product->get_type() === 'simple') {
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $current_price = $product->get_price();
            
            if (self::$defer_custom_price) {
                $price_html = '<span class="senheng-price-wrapper" data-defer="true" data-type="simple" data-regular="' . esc_attr(wc_price($regular_price)) . '" data-sale="' . esc_attr($sale_price ? wc_price($sale_price) : '') . '" data-current="' . esc_attr(wc_price($current_price)) . '"><span class="skeleton-loader skeleton-price"></span></span>';
                return $price_html;
            }

            $price_html = '<span class="senheng-price-wrapper">';
            
            if ($sale_price && $sale_price < $regular_price) {
                $price_html .= '<span class="price">';
                $price_html .= '<ins class="sale-price">' . wc_price($sale_price) . '</ins>';
                $price_html .= '</span>';
                $price_html .= '<span class="original-price-row">';
                $price_html .= '<del class="original-price">' . wc_price($regular_price) . '</del>';
                $price_html .= '</span>';
            } else {
                $price_html .= '<span class="price">' . wc_price($current_price) . '</span>';
            }
            
            $price_html .= '</span>';
        } else if ($product->get_type() === 'variable') {
            $prices = $product->get_variation_prices(true);
            
            if (empty($prices['price']) || empty($prices['regular_price'])) {
                return $price_html;
            }

            // Get the lowest prices directly from the price arrays.
            $lowest_price = min($prices['price']);
            $lowest_regular_price = min($prices['regular_price']);
            
            // Check if there's a sale price lower than regular.
            $has_sale = false;
            $lowest_sale_price = null;
            
            if (!empty($prices['sale_price'])) {
                $lowest_sale_price = min($prices['sale_price']);
                // Only consider it a sale if sale price is lower than regular price.
                if ($lowest_sale_price < $lowest_regular_price) {
                    $has_sale = true;
                }
            }

            if ($lowest_price !== null) {
                if (self::$defer_custom_price) {
                    $price_html = '<span class="senheng-price-wrapper" data-defer="true" data-type="variable" data-lowest="' . esc_attr(wc_price($lowest_price)) . '" data-lowest-regular="' . esc_attr($lowest_regular_price ? wc_price($lowest_regular_price) : '') . '" data-lowest-sale="' . esc_attr(($has_sale && $lowest_sale_price) ? wc_price($lowest_sale_price) : '') . '"><span class="skeleton-loader skeleton-price"></span></span>';
                    return $price_html;
                }

                $price_html = '<span class="senheng-price-wrapper">';
                
                if ($has_sale && $lowest_sale_price < $lowest_regular_price) {
                    $price_html .= '<span class="price">';
                    $price_html .= '<span class="price-from">From </span>';
                    $price_html .= '<ins class="sale-price">' . wc_price($lowest_sale_price) . '</ins>';
                    $price_html .= '</span>';
                    $price_html .= '<span class="original-price-row">';
                    $price_html .= '<del class="original-price">' . wc_price($lowest_regular_price) . '</del>';
                    $price_html .= '</span>';
                } else {
                    $price_html .= '<span class="price"><span class="price-from">From </span>' . wc_price($lowest_price) . '</span>';
                }
                
                $price_html .= '</span>';
            }
        }

        return $price_html;
    }

    /**
     * Custom rating display - show lowest rating or zero rating with count
     */
    public static function custom_rating_display($rating_html, $rating, $count)
    {
        global $product;
        
        // Handle all products, not just variable products
        $display_rating = $rating;
        $display_count = $count;
        
        if ($product instanceof WC_Product_Variable) {
            $variation_ids = $product->get_children();

            if (!empty($variation_ids)) {
                $lowest_rating = null;
                $lowest_count = 0;
                $lowest_variation_id = null;

                // Prime the meta cache for all variations in a single query
                // This uses WordPress API and leverages object cache
                update_meta_cache('post', $variation_ids);
                
                // Now get ratings using cached meta data
                foreach ($variation_ids as $variation_id) {
                    $variation_rating = (float) get_post_meta($variation_id, '_wc_average_rating', true);
                    
                    if ($variation_rating > 0) {
                        if ($lowest_rating === null || $variation_rating < $lowest_rating) {
                            $lowest_rating = $variation_rating;
                            $lowest_variation_id = $variation_id;
                        }
                    }
                }
                
                if ($lowest_variation_id !== null) {
                    // Get the count for this specific variation (already cached)
                    $lowest_count = (int) get_post_meta($lowest_variation_id, '_wc_review_count', true);
                }

                // If no variation ratings found, use parent product rating
                if ($lowest_rating !== null && $lowest_rating > 0) {
                    $display_rating = $lowest_rating;
                    $display_count = $lowest_count;
                } else {
                    $display_rating = $product->get_average_rating();
                    $display_count = $product->get_rating_count();
                }
            }
        }

        if (self::$defer_custom_rating) {
            $rating_html = '<div class="senheng-rating-wrapper" data-defer="true" data-rating="' . esc_attr($display_rating) . '" data-count="' . esc_attr($display_count) . '"><span class="skeleton-loader skeleton-rating"></span></div>';
            return $rating_html;
        }
        $rating_html = '<div class="star-rating" title="' . sprintf(__('Rated %s out of 5', 'woocommerce'), $display_rating) . '">';
        $rating_html .= '<span style="width:' . (($display_rating / 5) * 100) . '%">';
        $rating_html .= '<strong class="rating">' . $display_rating . '</strong> ' . __('out of 5', 'woocommerce');
        $rating_html .= '</span>';
        $rating_html .= '</div>';
        $rating_html .= '<span class="rating-count">(' . $display_count . ')</span>';
        return $rating_html;
    }

    /**
     * Get variation image by color attribute with fallback handling
     */
    public static function get_variation_image_by_color($product, $color_slug)
    {
        if (!self::$enable_swatch_image) {
            return null;
        }
        if (!$product instanceof WC_Product_Variable) {
            return null;
        }

        $variations = $product->get_available_variations();
        
        foreach ($variations as $variation_data) {
            $attributes = $variation_data['attributes'];
            
            foreach ($attributes as $attr_name => $attr_value) {
                if (strpos($attr_name, 'color') !== false && $attr_value === $color_slug) {
                    $image_data = $variation_data['image'];
                    
                    // Validate image exists and is accessible
                    if (self::validate_image_url($image_data['src'])) {
                        return $image_data;
                    }
                    
                    // Try alternative image sources
                    $alt_sources = [
                        $image_data['url'] ?? null,
                        $image_data['full_src'] ?? null,
                        $image_data['thumb_src'] ?? null
                    ];
                    
                    foreach ($alt_sources as $alt_src) {
                        if ($alt_src && self::validate_image_url($alt_src)) {
                            return [
                                'src' => $alt_src,
                                'alt' => $image_data['alt'] ?? $product->get_name(),
                                'url' => $alt_src,
                                'full_src' => $alt_src,
                                'thumb_src' => $alt_src
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate if image URL is accessible (optimized for performance)
     */
    private static function validate_image_url($url)
    {
        if (empty($url)) {
            return false;
        }
        
        // Check if it's a local WordPress image
        if (strpos($url, home_url()) === 0) {
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
            return file_exists($file_path);
        }
        
        // For external URLs, assume they exist to avoid HTTP requests
        // This prevents slow page loading due to image validation
        return true;
    }

    /**
     * AJAX handler for getting variation image by color
     */
    public static function ajax_get_variation_image()
    {
        check_ajax_referer('get_variation_image', 'nonce', true);

        if (!isset($_POST['product_id']) || !is_numeric(wp_unslash($_POST['product_id']))) {
            wp_send_json_error('Missing product_id');
        }
        $product_id = (int) wp_unslash($_POST['product_id']);

        if (!isset($_POST['color_value'])) {
            wp_send_json_error('Missing color_value');
        }
        $color_value = sanitize_key(wp_unslash($_POST['color_value']));

        $product = wc_get_product($product_id);
        if (!$product || !$product instanceof WC_Product_Variable) {
            wp_send_json_error('Invalid product');
        }

        $image_data = self::get_variation_image_by_color($product, $color_value);
        
        if ($image_data) {
            wp_send_json_success([
                'image_url' => $image_data['src'],
                'image_alt' => $image_data['alt'] ?? $product->get_name()
            ]);
        } else {
            wp_send_json_error('No image found for this color');
        }
    }
    
    /**
     * Override the content-product template part
     */
    public static function override_content_product($template, $slug, $name)
    {
        // Check if we're dealing with a product post type using proper WordPress methods
        $post_type = get_post_type();
        
        if ($slug === 'content' && ($name === 'product' || $post_type === 'product')) {
            $custom_template = SENHENG_CORE_PATH . 'app/Views/woodmart/woocommerce/content-product.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
     

    
    /**
     * Setup WoodMart theme compatibility
     */
    public static function setup_woodmart_compatibility()
    {
        // Ensure WoodMart functions are available
        if (function_exists('woodmart_get_opt')) {
            // Add any WoodMart specific customizations here
            add_action('wp_head', [self::class, 'add_woodmart_compatibility_styles']);
        }
    }
    
    /**
     * Add compatibility styles for WoodMart
     */
    public static function add_woodmart_compatibility_styles()
    {
        echo '<style>
        /* Ensure Senheng product cards work with WoodMart */
        .wd-product.senheng-product-card .product-wrapper {
            position: relative;
        }
        .wd-product.senheng-product-card .color-swatches {
            position: relative;
            z-index: 1;
        }
        </style>';
    }
    public static function print_defer_scripts()
    {
        if (!self::$defer_custom_price && !self::$defer_custom_rating) {
            return;
        }
        ?>
            <script>
            (function(){
                // Main render function
                window.senhengRenderDeferred = function(){
                    var priceEls = document.querySelectorAll('.senheng-price-wrapper[data-defer="true"]');
                    for (var i = 0; i < priceEls.length; i++) {
                        var el = priceEls[i];
                        el.removeAttribute('data-defer'); // Prevent re-processing
                        var t = el.getAttribute('data-type');
                        if (t === 'simple') {
                            var sale = el.getAttribute('data-sale');
                            var regular = el.getAttribute('data-regular');
                            var current = el.getAttribute('data-current');
                            var h = '';
                            if (sale && regular && sale !== regular) {
                                h = '<span class="price"><ins class="sale-price">' + sale + '</ins></span><span class="original-price-row"><del class="original-price">' + regular + '</del></span>';
                            } else {
                                h = '<span class="price">' + current + '</span>';
                            }
                            el.innerHTML = h;
                        } else if (t === 'variable') {
                            var lowest = el.getAttribute('data-lowest');
                            var lowestRegular = el.getAttribute('data-lowest-regular');
                            var lowestSale = el.getAttribute('data-lowest-sale');
                            var h2 = '';
                            if (lowestSale && lowestRegular && lowestSale !== lowestRegular) {
                                h2 = '<span class="price"><span class="price-from">From </span><ins class="sale-price">' + lowestSale + '</ins></span><span class="original-price-row"><del class="original-price">' + lowestRegular + '</del></span>';
                            } else {
                                h2 = '<span class="price"><span class="price-from">From </span>' + lowest + '</span>';
                            }
                            el.innerHTML = h2;
                        }
                    }

                    var ratingEls = document.querySelectorAll('.senheng-rating-wrapper[data-defer="true"]');
                    for (var j = 0; j < ratingEls.length; j++) {
                        var el2 = ratingEls[j];
                        el2.removeAttribute('data-defer'); // Prevent re-processing
                        var rating = parseFloat(el2.getAttribute('data-rating') || '0');
                        var count = parseInt(el2.getAttribute('data-count') || '0', 10);
                        var w = Math.max(0, Math.min(100, (rating / 5) * 100));
                        el2.innerHTML = '<div class="star-rating" title="Rated ' + rating + ' out of 5"><span style="width:' + w + '%"><strong class="rating">' + rating + '</strong> out of 5</span></div><span class="rating-count">(' + count + ')</span>';
                    }
                };

                // Initial run
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    window.senhengRenderDeferred();
                } else {
                    document.addEventListener('DOMContentLoaded', window.senhengRenderDeferred);
                }

                // Mutation Observer for robust AJAX handling
                if (typeof MutationObserver !== 'undefined') {
                    var observer = new MutationObserver(function(mutations) {
                        var shouldRender = false;
                        for (var i = 0; i < mutations.length; i++) {
                            if (mutations[i].addedNodes.length) {
                                // Check if any added node is relevant or contains relevant elements
                                // Simple check: if new nodes are added, just try to render. 
                                // Optimization: we could check classLists but for now safety first.
                                shouldRender = true;
                                break;
                            }
                        }
                        if (shouldRender) {
                            // Debounce slightly
                            if (window.senhengRenderTimeout) clearTimeout(window.senhengRenderTimeout);
                            window.senhengRenderTimeout = setTimeout(window.senhengRenderDeferred, 50);
                        }
                    });
                    
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }

                // Handle AJAX requests (jQuery fallback)
                if (typeof jQuery !== 'undefined') {
                    jQuery(document).on('ajaxComplete', function() {
                        setTimeout(window.senhengRenderDeferred, 50);
                    });
                    
                    // WoodMart specific events
                    jQuery(document).on('woodmart-layout-updated wdProductsTabsLoaded', function() {
                        setTimeout(window.senhengRenderDeferred, 50);
                    });
                    jQuery(document).on('pjax:complete', function() {
                        setTimeout(window.senhengRenderDeferred, 50);
                    });
                    // Elementor Popup
                    jQuery(document).on('elementor/popup/show', function() {
                        setTimeout(window.senhengRenderDeferred, 50);
                    });
                }

                // Elementor Editor
                window.addEventListener('elementor/frontend/init', function() {
                    if (typeof elementorFrontend !== 'undefined') {
                        elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {
                            window.senhengRenderDeferred();
                        });
                    }
                });
            })();
            </script>
        <?php
    }
    
    /**
     * Get product data for loop display (optimized for performance)
     * Returns essential product data needed for the product loop template
     */
    public static function get_product_data($product_id)
    {
        // Use static cache to avoid repeated database queries
        static $product_data_cache = [];
        
        if (isset($product_data_cache[$product_id])) {
            return $product_data_cache[$product_id];
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            $result = [
                'color_variations' => [],
                'scoin_cashback' => 0,
                'brand_name' => '',
                'fallback_image' => self::get_placeholder_image_url()
            ];
            $product_data_cache[$product_id] = $result;
            return $result;
        }

        // Get color variations for variable products with proper variation images
        $color_variations = [];
        if (self::$enable_swatches && $product instanceof WC_Product_Variable) {
            $attributes = $product->get_variation_attributes();
            foreach ($attributes as $attribute_name => $options) {
                $al = strtolower($attribute_name);
                if (strpos($al, 'color') !== false || strpos($al, 'colour') !== false) {
                    $limited_options = array_slice($options, 0, 5);
                    foreach ($limited_options as $option) {
                        $term = get_term_by('slug', $option, $attribute_name);
                        if ($term) {
                            $image_meta = get_term_meta($term->term_id, 'image', true);
                            $image_url = '';
                            if (is_array($image_meta) && isset($image_meta['id'])) {
                                $image_url = wp_get_attachment_image_url($image_meta['id'], 'woocommerce_thumbnail');
                            } elseif (!is_array($image_meta)) {
                                $image_url = $image_meta;
                            }
                            $color_variations[] = [
                                'slug' => $option,
                                'name' => $term->name,
                                'color' => get_term_meta($term->term_id, 'color', true),
                                'image' => $image_url,
                                'variation_id' => null,
                                'variation_image' => ''
                            ];
                        }
                    }
                    break;
                }
            }
        }

        // Simplified S-Coin calculation (avoid membership checks for performance)
        $price = (float) $product->get_price();
        $s_coin_value = (float) get_field('s_coin_value', $product_id);

        // Simplified brand name retrieval
        $brand_name = $product->get_attribute('pa_brand') ?: '';

        // Get fallback image for the product
        $fallback_image = self::get_product_fallback_image($product);

        $result = [
            'color_variations' => $color_variations,
            'scoin_cashback' => (float) $s_coin_value,
            'brand_name' => (string) $brand_name,
            'fallback_image' => $fallback_image
        ];
        
        // Cache the result
        $product_data_cache[$product_id] = $result;
        return $result;
    }

    /**
     * Get fallback image for a product (optimized for performance)
     */
    private static function get_product_fallback_image($product)
    {
        // Try to get the main product image (no file validation for speed)
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $main_image_url = wp_get_attachment_image_url($main_image_id, 'woocommerce_thumbnail');
            if ($main_image_url) {
                return $main_image_url;
            }
        }

        // Try to get the first gallery image (no file validation for speed)
        $gallery_image_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_image_ids)) {
            $gallery_image_url = wp_get_attachment_image_url($gallery_image_ids[0], 'woocommerce_thumbnail');
            if ($gallery_image_url) {
                return $gallery_image_url;
            }
        }

        // Return placeholder image
        return self::get_placeholder_image_url();
    }

    /**
     * Get placeholder image URL (optimized for performance)
     */
    private static function get_placeholder_image_url()
    {
        // Return a simple data URI placeholder without database checks
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjZjhmOWZhIi8+CjxwYXRoIGQ9Ik0xNTAgNTBDMTE3Ljg2IDUwIDkwIDc3Ljg2IDkwIDExMEM5MCAxNDIuMTQgMTE3Ljg2IDE3MCAxNTAgMTcwQzE4Mi4xNCAxNzAgMjEwIDE0Mi4xNCAyMTAgMTEwQzIxMCA3Ny44NiAxODIuMTQgNTAgMTUwIDUwWiIgZmlsbD0iI2RkZCIvPgo8cGF0aCBkPSJNMTUwIDcwQzEyNi45MSA3MCAxMTAgODYuOTEgMTEwIDExMEMxMTAgMTMzLjA5IDEyNi45MSAxNTAgMTUwIDE1MEMxNzMuMDkgMTUwIDE5MCAxMzMuMDkgMTkwIDExMEMxOTAgODYuOTEgMTczLjA5IDcwIDE1MCA3MFoiIGZpbGw9IiNhYWEiLz4KPHBhdGggZD0iTTE1MCA5MEMxMzUuNjQgOTAgMTMwIDEwNS42NCAxMzAgMTIwQzEzMCAxMzQuMzYgMTM1LjY0IDE1MCAxNTAgMTUwQzE2NC4zNiAxNTAgMTcwIDEzNC4zNiAxNzAgMTIwQzE3MCAxMDUuNjQgMTY0LjM2IDkwIDE1MCA5MFoiIGZpbGw9IiM5OTkiLz4KPC9zdmc+Cg==';
    }

    /**
     * Display S-coin badge in product loop
     * Moved from ScoinController to consolidate product loop logic
     */
    public static function scoin_display_on_product_loop()
    {
        global $product;
        if (!$product) {
            return;
        }

        // Skip S-coin badge if product has WCPB badge
        if (self::product_has_wcpb_badge()) {
            return;
        }

        $product_id = $product->get_id();
        $price = (float) $product->get_price();
        
        $s_coin_cashback = (float) get_field('s_coin_value', $product_id);

        // Only display if there's cashback
        if ($s_coin_cashback > 0) {
            // Get S-coin icon URL
             if (class_exists('SenhengCore\Controllers\ScoinController') && method_exists('SenhengCore\Controllers\ScoinController', 'get_scoin_icon_url')) {
                 $icon_url = ScoinController::get_scoin_icon_url();
             } else {
                 $icon_url = defined('SENHENG_CORE_ASSETS_URL') ? SENHENG_CORE_ASSETS_URL . 'uploads/s-coin-label-1.webp' : '';
             }

            $s_coin_display = ((float) $s_coin_cashback == floor((float) $s_coin_cashback)) ? (string) (int) $s_coin_cashback : number_format((float) $s_coin_cashback, 1);
            $is_decimal = (float) $s_coin_cashback != floor((float) $s_coin_cashback);
            $is_decimal_two = $is_decimal && ((int) $s_coin_cashback >= 0 && (int) $s_coin_cashback < 10);
            $is_decimal_three = $is_decimal && ((int) $s_coin_cashback >= 10);
            $decimal_two_attr = $is_decimal_two ? ' data-decimal-two-digit="true"' : '';
            $decimal_three_attr = $is_decimal_three ? ' data-decimal-three-digit="true"' : '';
            echo '<div class="scoin-badge">';
            if ($s_coin_cashback) {
                // Check if it's a single-digit percentage
                $is_single_digit = $s_coin_cashback < 10;
                $single_digit_attr = $is_single_digit ? ' data-single-digit="true"' : '';
                $is_triple_digit = is_numeric($s_coin_cashback) && $s_coin_cashback >= 100;
                $triple_digit_attr = $is_triple_digit ? ' data-triple-digit="true"' : '';
                
                if ($icon_url) {
                    echo '<div class="scoin-container">';
                    echo '<img src="' . esc_url($icon_url) . '" alt="S-Coin Cashback" class="scoin-icon">';
                    echo '<span class="scoin-text"><span class="scoin-number"' . $single_digit_attr . $triple_digit_attr . $decimal_two_attr . $decimal_three_attr . '>' . esc_html($s_coin_display) . '</span><span class="scoin-percent">%</span></span>';
                    echo '</div>';
                } else {
                    echo '<span class="scoin-text"><span class="scoin-number"' . $single_digit_attr . $triple_digit_attr . $decimal_two_attr . $decimal_three_attr . '>' . esc_html($s_coin_display) . '</span><span class="scoin-percent">%</span></span>';
                }
            }
            echo '</div>';
        }
    }

    /**
     * Ensure proper display order for product elements
     * This helps maintain the layout: image -> swatches -> product info -> rating
     */
    public static function ensure_proper_display_order()
    {
        // This function ensures our template structure is maintained
        // The actual layout is handled by our custom content-product.php template
        // and the CSS positioning rules
    }

    /**
     * Custom swatches list that only shows color variations
     * This prevents non-color dropdown variations from appearing
     */
    public static function custom_swatches_list()
    {
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return '';
        }
        
        // Get product attributes
        $attributes = $product->get_variation_attributes();
        
        // Check if product has color attributes
        foreach ($attributes as $attribute_name => $options) {
            if (stripos($attribute_name, 'color') !== false || stripos($attribute_name, 'colour') !== false) {
                // Product has color attributes, don't show any dropdown variations
                // Color swatches are handled by our custom template
                return '';
            }
        }
        
        // For products without color attributes, hide all dropdown variations
        // This ensures no dropdown variations appear in the product loop
        return '';
    }

    /**
     * Check if a product has a WCPB Product Badge
     * Uses the same logic as the WCPB plugin to determine if a badge would display
     * 
     * @param int|null $product_id The product ID. If null, uses global $product
     * @return bool True if product has at least one WCPB badge
     */
    public static function product_has_wcpb_badge($product_id = null)
    {
        // Check if WCPB plugin is active
        if (!class_exists('WCPB_Product_Badges')) {
            return false;
        }

        global $product;
        
        if ($product_id) {
            $check_product = wc_get_product($product_id);
        } else {
            $check_product = $product;
        }
        
        if (!$check_product) {
            return false;
        }

        $product_id = $check_product->get_id();
        $product_category_ids = $check_product->get_category_ids();
        $product_tag_ids = $check_product->get_tag_ids();
        $product_shipping_class_id = $check_product->get_shipping_class_id();
        $product_is_on_sale = $check_product->is_on_sale();
        $product_is_on_backorder = $check_product->is_on_backorder();
        $product_stock_status = $check_product->get_stock_status();
        $product_featured = $check_product->is_featured();
        
        // Low stock calculation
        $low_stock_amount = get_option('woocommerce_notify_low_stock_amount');
        $product_low_stock_amount = $check_product->get_low_stock_amount();
        $product_low_stock_threshold = !empty($product_low_stock_amount) ? $product_low_stock_amount : $low_stock_amount;
        $product_has_low_stock = $check_product->managing_stock() && $check_product->get_stock_quantity() !== null && $check_product->get_stock_quantity() <= $product_low_stock_threshold && $check_product->get_stock_quantity() > 0;

        // Get all WCPB badges using per-request cache to avoid N+1 queries
        $badges = self::get_cached_wcpb_badges();

        if (empty($badges)) {
            return false;
        }

        foreach ($badges as $badge_id) {
            $visibility = self::get_cached_badge_meta($badge_id, '_wcpb_product_badges_display_visibility');
            $products_setting = self::get_cached_badge_meta($badge_id, '_wcpb_product_badges_display_products');
            
            // Check visibility - for product loop we check 'all' or 'product_loops'
            if ($visibility !== 'all' && $visibility !== 'product_loops') {
                continue;
            }

            $display = false;

            if ($products_setting === 'specific') {
                $products_specific_categories = self::get_cached_badge_meta($badge_id, '_wcpb_product_badges_display_products_specific_categories');
                $products_specific_tags = self::get_cached_badge_meta($badge_id, '_wcpb_product_badges_display_products_specific_tags');
                $products_specific_products = self::get_cached_badge_meta($badge_id, '_wcpb_product_badges_display_products_specific_products');
                $products_specific_shipping_classes = self::get_cached_badge_meta($badge_id, '_wcpb_product_badges_display_products_specific_shipping_classes');

                // Check categories
                if (!empty($products_specific_categories)) {
                    foreach ($products_specific_categories as $cat_id) {
                        if (in_array($cat_id, $product_category_ids)) {
                            $display = true;
                            break;
                        }
                    }
                }

                // Check tags
                if (!$display && !empty($products_specific_tags)) {
                    foreach ($products_specific_tags as $tag_id) {
                        if (in_array($tag_id, $product_tag_ids)) {
                            $display = true;
                            break;
                        }
                    }
                }

                // Check specific products
                if (!$display && !empty($products_specific_products)) {
                    if (in_array($product_id, $products_specific_products)) {
                        $display = true;
                    }
                }

                // Check shipping classes
                if (!$display && !empty($products_specific_shipping_classes)) {
                    if (in_array($product_shipping_class_id, $products_specific_shipping_classes)) {
                        $display = true;
                    }
                }
            } elseif ($products_setting === 'sale') {
                if ($product_is_on_sale) {
                    $display = true;
                }
            } elseif ($products_setting === 'non_sale') {
                if (!$product_is_on_sale) {
                    $display = true;
                }
            } elseif ($products_setting === 'out_of_stock') {
                if ($product_stock_status === 'outofstock') {
                    $display = true;
                }
            } elseif ($products_setting === 'low_stock') {
                if ($product_has_low_stock) {
                    $display = true;
                }
            } elseif ($products_setting === 'on_backorder') {
                if ($product_is_on_backorder) {
                    $display = true;
                }
            } elseif ($products_setting === 'featured') {
                if ($product_featured) {
                    $display = true;
                }
            } elseif ($products_setting === 'custom_rules') {
                // Check custom rules filter
                if (apply_filters('wcpb_product_badges_custom_rules_display', false, $check_product, $badge_id)) {
                    $display = true;
                }
            } else {
                // Default case - show for all products
                $display = true;
            }

            if ($display) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cached WCPB badges (per-request cache)
     * Loads all badges once per request to avoid N+1 queries in product loops
     * 
     * @return array Array of badge IDs
     */
    private static function get_cached_wcpb_badges()
    {
        if (self::$wcpb_badges_cache === null) {
            self::$wcpb_badges_cache = get_posts([
                'numberposts' => -1,
                'post_type' => 'wcpb_product_badge',
                'post_status' => 'publish',
                'fields' => 'ids',
            ]);
            
            // Pre-load all badge meta data in a single query
            if (!empty(self::$wcpb_badges_cache)) {
                update_meta_cache('post', self::$wcpb_badges_cache);
            }
        }
        
        return self::$wcpb_badges_cache;
    }

    /**
     * Get cached badge meta (uses WordPress object cache via update_meta_cache)
     * 
     * @param int $badge_id The badge post ID
     * @param string $meta_key The meta key to retrieve
     * @return mixed The meta value
     */
    private static function get_cached_badge_meta($badge_id, $meta_key)
    {
        // Since we called update_meta_cache() in get_cached_wcpb_badges(),
        // get_post_meta() will now hit the object cache instead of the database
        return get_post_meta($badge_id, $meta_key, true);
    }

    /**
     * Filter WoodMart product labels to hide when WCPB badge is present
     * This prevents duplicate badges from appearing on products
     * 
     * @param array $output The array of product labels
     * @return array Empty array if WCPB badge present, otherwise unmodified
     */
    public static function filter_woodmart_product_labels($output)
    {
        if (self::product_has_wcpb_badge()) {
            return []; // Return empty array to hide all WoodMart product labels
        }
        return $output;
    }
}
