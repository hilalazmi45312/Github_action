<?php

/**
 * Woodmart Rating Controller
 * 
 * Handles Woodmart rating display customizations to show zero star ratings
 * even when products have no reviews on single product pages
 */
class WoodmartRatingController
{
    /**
     * Initialize the controller
     */
    public static function init()
    {
        // Only initialize if we're using Woodmart themef
        if (!self::is_woodmart_theme()) {
            return;
        }

        // Override Woodmart rating functions
        self::init_rating_overrides();
        
        // Initialize hooks for single product page
        self::init_single_product_hooks();
        
        // Override the rating template for Elementor widgets
        self::init_elementor_overrides();
        
        // Add custom star rating styles
        self::init_custom_styles();
    }

    /**
     * Check if we're using a Woodmart theme
     */
    public static function is_woodmart_theme()
    {
        $theme = wp_get_theme();
        $theme_name = $theme->get('Name');
        $theme_template = $theme->get('Template');
        
        // Check if it's Woodmart or a Woodmart child theme
        return (
            stripos($theme_name, 'woodmart') !== false ||
            stripos($theme_template, 'woodmart') !== false ||
            function_exists('woodmart_get_theme_info')
        );
    }

    /**
     * Initialize rating overrides
     */
    public static function init_rating_overrides()
    {
        // Override the woodmart_get_product_rating function with higher priority
        add_filter('woocommerce_product_get_rating_html', [self::class, 'override_product_rating_html'], 20, 3);
        
        // Force show empty star rating on single product pages
        add_filter('pre_option_woocommerce_enable_review_rating', [self::class, 'force_enable_ratings']);
        
        // Override Woodmart's show_empty_star_rating option for single products
        add_filter('woodmart_get_opt_show_empty_star_rating', [self::class, 'force_show_empty_stars_on_single'], 10, 1);
    }

    /**
     * Initialize single product page hooks
     */
    public static function init_single_product_hooks()
    {
        // Remove default rating if it exists and replace with our custom one
        add_action('init', [self::class, 'remove_default_rating_hooks'], 15);
    }
    
    /**
     * Initialize Elementor widget overrides
     */
    public static function init_elementor_overrides()
    {
        // Hook into Elementor widget rendering
        add_action('elementor/widget/before_render_content', [self::class, 'before_elementor_widget_render'], 10, 1);
        add_action('elementor/widget/after_render_content', [self::class, 'after_elementor_widget_render'], 10, 1);
    }

    /**
     * Override product rating HTML to always show stars on single product pages
     */
    public static function override_product_rating_html($rating_html, $rating, $count)
    {
        // Only apply on single product pages
        if (!is_product()) {
            return $rating_html;
        }

        global $product;
        
        if (!$product) {
            return $rating_html;
        }

        // For products with ratings, generate custom HTML to avoid duplicates
        if ($rating > 0) {
            return self::generate_rating_html_with_count($rating, $count);
        }

        // Generate zero star rating HTML for products with no reviews
        return self::generate_zero_star_rating_html();
    }

    /**
     * Generate rating HTML with count for products with reviews
     */
    public static function generate_rating_html_with_count($rating, $count)
    {
        global $product;
        
        $review_count = $product->get_review_count();
        $star_rating_html = self::get_star_rating_html($rating);
        
        ob_start();
        ?>
        <?php if (woodmart_get_opt('show_reviews_count') && wc_reviews_enabled()) : ?>
            <div class="wd-star-rating">
        <?php endif; ?>

        <div class="star-rating" role="img" aria-label="<?php echo esc_attr(sprintf(__('Rated %s out of 5', 'woocommerce'), $rating)); ?>">
            <?php echo wp_kses($star_rating_html, true); ?>
        </div>

        <?php if ($review_count > 0 && woodmart_get_opt('show_reviews_count') && wc_reviews_enabled()) : ?>
            <a href="#reviews" class="woocommerce-review-link" rel="nofollow">
                (<?php echo esc_html($review_count); ?>)
            </a>
        <?php endif; ?>

        <?php if (woodmart_get_opt('show_reviews_count') && wc_reviews_enabled()) : ?>
            </div>
        <?php endif; ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Generate zero star rating HTML
     */
    public static function generate_zero_star_rating_html()
    {
        $rating = 0;
        $star_rating_html = self::get_star_rating_html($rating);
        
        ob_start();
        ?>
        <?php if (woodmart_get_opt('show_reviews_count') && wc_reviews_enabled()) : ?>
            <div class="wd-star-rating">
        <?php endif; ?>

        <div class="star-rating" role="img" aria-label="<?php echo esc_attr(sprintf(__('Rated %s out of 5', 'woocommerce'), $rating)); ?>">
            <?php echo wp_kses($star_rating_html, true); ?>
        </div>

        <?php self::show_reviews_count_zero(); ?>

        <?php if (woodmart_get_opt('show_reviews_count') && wc_reviews_enabled()) : ?>
            </div>
        <?php endif; ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Get star rating HTML for any rating
     */
    public static function get_star_rating_html($rating)
    {
        $percentage = ($rating / 5) * 100;
        
        return sprintf(
            '<span style="width:%s%%">%s</span>',
            $percentage,
            sprintf(
                __('%1$s out of %2$s', 'woodmart'),
                '<strong class="rating">' . esc_html($rating) . '</strong>',
                '<span>5</span>'
            )
        );
    }

    /**
     * Show reviews count without customer review text
     */
    public static function show_reviews_count_zero()
    {
        global $product;
        
        if (!woodmart_get_opt('show_reviews_count') || !wc_reviews_enabled()) {
            return;
        }

        $count = $product->get_review_count();
        
        if ($count === 0) {
            echo '<a href="#reviews" class="woocommerce-review-link" rel="nofollow">(<span class="count">0</span>)</a>';
        }
    }

    /**
     * Force enable ratings
     */
    public static function force_enable_ratings($value)
    {
        if (is_product()) {
            return 'yes';
        }
        return $value;
    }

    /**
     * Force show empty stars on single product pages
     */
    public static function force_show_empty_stars_on_single($value)
    {
        if (is_product()) {
            return true;
        }
        return $value;
    }

    /**
     * Remove default rating hooks to prevent conflicts
     */
    public static function remove_default_rating_hooks()
    {
        // Remove WooCommerce default rating display
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
        
        // Remove Woodmart rating if it exists
        if (function_exists('woodmart_single_product_rating')) {
            remove_action('woocommerce_single_product_summary', 'woodmart_single_product_rating', 10);
        }
    }

    /**
     * Before Elementor widget render - setup hooks for rating widget
     */
    public static function before_elementor_widget_render($widget)
    {
        // Check if this is the rating widget
        if ($widget->get_name() === 'wd_single_product_rating') {
            // Override the rating template temporarily
            add_filter('wc_get_template', [self::class, 'override_rating_template_for_widget'], 10, 5);
        }
    }
    
    /**
     * After Elementor widget render - cleanup hooks
     */
    public static function after_elementor_widget_render($widget)
    {
        // Check if this is the rating widget
        if ($widget->get_name() === 'wd_single_product_rating') {
            // Remove the override
            remove_filter('wc_get_template', [self::class, 'override_rating_template_for_widget'], 10);
        }
    }
    
    /**
     * Override the rating template specifically for the Elementor widget
     */
    public static function override_rating_template_for_widget($template, $template_name, $args, $template_path, $default_path)
    {
        // Only override the rating template
        if ($template_name === 'single-product/rating.php' && is_product()) {
            global $product;
            
            if ($product) {
                // Return our custom template path for all products to ensure consistent "customer review" text
                return plugin_dir_path(__FILE__) . '../Views/woodmart/woocommerce/single-product-rating.php';
            }
        }
        
        return $template;
    }
    
    /**
     * Initialize custom star rating styles
     */
    public static function init_custom_styles()
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_custom_star_rating_styles'], 20);
    }
    
    /**
     * Enqueue custom CSS to change star rating color to black
     */
    public static function enqueue_custom_star_rating_styles()
    {
        $custom_css = '
            .star-rating,
            .star-rating:before,
            .elementor-star-rating i:before,
            .woocommerce .star-rating:before,
            .woocommerce .star-rating span:before,
            .wd-star-rating .star-rating:before,
            .wd-star-rating .star-rating span:before {
                color: #000000 !important;
            }
            
            .elementor-star-rating i {
                color: #cccccc !important;
            }
            
            .elementor-star-rating i:before {
                color: #000000 !important;
            }
        ';
        
        wp_add_inline_style('woodmart-style', $custom_css);
    }
}