<?php

/**
 * WooCommerce Template Controller
 *
 * Handles all WooCommerce template overrides and customizations
 * for the Senheng Core plugin
 */
class WooCommerceAddtoCartController
{
    /**
     * Track if fragment refresh is currently active
     */
    private static $fragment_refresh_active = false;

    /**
     * Track if AJAX handlers have been registered to prevent duplicates
     */
    private static $ajax_handlers_registered = false;

    /**
     * Track recent add to cart requests to prevent duplicates
     */
    private static $recent_requests = array();

    /**
     * Initialize the controller
     */
    public static function init()
    {
        // Only initialize if we're using Woodmart theme
        if (!self::is_woodmart_theme()) {
            return;
        }

        // Initialize template overrides
        self::init_template_overrides();

        // Initialize asset enqueuing
        self::init_assets();

        // Initialize product extras handling for all themes
        self::init_product_extras();

        // Initialize deposit functionality
        self::init_deposit_hooks();
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
     * Initialize template overrides
     */
    public static function init_template_overrides()
    {
        // Add template locate filter
        add_filter('woocommerce_locate_template', [self::class, 'locate_woodmart_template'], 5, 3);

        // Remove default WooCommerce simple add to cart action and replace with our custom one
        add_action('init', [self::class, 'remove_default_simple_add_to_cart'], 20);

        // Also add a fallback to ensure our custom add to cart is always available
        add_action('woocommerce_simple_add_to_cart', [self::class, 'custom_simple_add_to_cart'], 30);

        // Override variation display
        add_action('woocommerce_single_variation', [self::class, 'override_variation_display'], 5);

        // Override variation add to cart button
        add_action('woocommerce_single_variation_add_to_cart_button', [self::class, 'override_variation_add_to_cart_button'], 5);

        // Remove default WooCommerce variation display in mini cart to prevent duplicates
        add_action('init', [self::class, 'remove_default_mini_cart_variation_hooks'], 30);

        // Add WooCommerce stock validation hooks
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'validate_variation_stock'], 10, 5);

        add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );
    }

    /**
     * Initialize asset enqueuing
     */
    public static function init_assets()
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_woodmart_assets'], 100);
    }

    /**
     * Locate Woodmart templates in senheng_core plugin
     */
    public static function locate_woodmart_template($template, $template_name, $template_path)
    {
        // Only process if we're in a Woodmart theme
        if (!self::is_woodmart_theme()) {
            return $template;
        }

        // Check if we have a custom template in senheng_core
        $senheng_template = self::get_template_path($template_name, $template_path);

        if ($senheng_template && file_exists($senheng_template)) {
            return $senheng_template;
        }

        return $template;
    }

    /**
     * Get the path to a template in senheng_core plugin
     */
    public static function get_template_path($template_name, $template_path = '')
    {
        $senheng_base_path = SENHENG_CORE_VIEW_PATH . 'woodmart/woocommerce/';

        // For variation-add-to-cart-button.php, we need to handle it specially
        if ($template_name === 'single-product/add-to-cart/variation-add-to-cart-button.php') {
            return $senheng_base_path . 'single-product/add-to-cart/variation-add-to-cart-button.php';
        }

        // If template_path is provided, use it to determine the subdirectory
        if ($template_path) {
            // Extract the subdirectory from the template path
            $path_parts = explode('/', trim($template_path, '/'));
            $subdir = implode('/', $path_parts);

            return $senheng_base_path . $subdir . '/' . $template_name;
        }

        // Default to the root woodmart directory
        return $senheng_base_path . $template_name;
    }

    /**
     * Remove default WooCommerce simple add to cart action and replace with our custom one
     */
    public static function remove_default_simple_add_to_cart()
    {
        // Always try to remove the default action regardless of theme
        remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
        remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart');

        // Add our custom simple add to cart action
        add_action('woocommerce_simple_add_to_cart', [self::class, 'custom_simple_add_to_cart'], 30);
    }

    /**
     * Custom simple page and simple add to cart function
     */
    public static function custom_simple_add_to_cart()
    {
        // Always try to use our custom template first
        $template_path = SENHENG_CORE_VIEW_PATH . 'woodmart/woocommerce/single-product/add-to-cart/simple.php';
        if (file_exists($template_path)) {
            include $template_path;
            return;
        }

        // Fallback to default WooCommerce template if our template doesn't exist
        wc_get_template('single-product/add-to-cart/simple.php');
    }

    /**
     * Override the variation part display
     */
    public static function override_variation_display()
    {
        if (self::is_woodmart_theme()) {
            $template_path = SENHENG_CORE_VIEW_PATH . 'woodmart/woocommerce/single-product/add-to-cart/variation.php';
            if (file_exists($template_path)) {
                include $template_path;
                return;
            }
        }

        // Fallback to default WooCommerce template
        wc_get_template('single-product/add-to-cart/variation.php');
    }

    /**
     * Override the variation add-to-cart button
     */
    public static function override_variation_add_to_cart_button()
    {
        if (self::is_woodmart_theme()) {
            $template_path = SENHENG_CORE_VIEW_PATH . 'woodmart/woocommerce/single-product/add-to-cart/variation-add-to-cart-button.php';
            if (file_exists($template_path)) {
                include $template_path;
                return;
            }
        }

        // Fallback to default WooCommerce template
        wc_get_template('single-product/add-to-cart/variation-add-to-cart-button.php');
    }

    /**
     * Enqueue custom add-to-cart assets for Woodmart theme
     */
    public static function enqueue_woodmart_assets()
    {
        // Only enqueue if we're using Woodmart theme
        if (!self::is_woodmart_theme()) {
            return;
        }

        // Only enqueue on product pages
        wp_enqueue_style(
            'senheng-add-to-cart-css',
            SENHENG_CORE_ASSETS_URL . 'css/add-to-cart.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_style(
            'senheng-side-cart-css',
            SENHENG_CORE_ASSETS_URL . 'css/side-cart.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'senheng-add-to-cart-js',
            SENHENG_CORE_ASSETS_URL . 'js/add-to-cart.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Sticky variation sync - overrides WoodMart's wd-enabled functionality
        if (is_product()) {
            wp_enqueue_script(
                'sticky-variation-sync',
                SENHENG_CORE_ASSETS_URL . 'js/sticky-variation-sync.js',
                array('jquery'),
                '1.0.0',
                true
            );
        }

        // Localize script with optimized parameters for faster performance
        wp_localize_script('senheng-add-to-cart-js', 'wc_add_to_cart_params', array(
            'wc_ajax_url' => home_url('/?wc-ajax=%%endpoint%%'), // Use only WooCommerce AJAX endpoint
            'cart_hash_key' => apply_filters('woocommerce_cart_hash_key', 'wc_cart_hash_' . md5(get_current_blog_id() . '_' . get_site_url(get_current_blog_id(), '/') . get_template())),
            'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : '',
            'cart_redirect_after_add' => get_option('woocommerce_cart_redirect_after_add'),
            'enable_ajax_add_to_cart' => true,
            'i18n_view_cart' => esc_attr__('View cart', 'woocommerce'),
            'cart_url' => apply_filters('woocommerce_add_to_cart_redirect', wc_get_cart_url(), null),
        ));

    }



    /**
     * Validate variation stock before adding to cart
     */
    public static function validate_variation_stock($passed, $product_id, $quantity, $variation_id = 0, $variations = array())
    {
        // Check if the product has custom sale quantity handling - if so, let that system handle ALL validation
        $product = wc_get_product($variation_id ?: $product_id);
        if ($product) {
            $product_key = $variation_id ?: $product_id;
            $sale_quantity_meta = get_post_meta($product_key, '_sales_quantity', true);

            if ($sale_quantity_meta !== '' && $sale_quantity_meta !== false) {
                return $passed; // Skip this validation entirely
            }
        }
    }

    /**
     * Remove default WooCommerce variation display hooks in mini cart to prevent duplicates
     */
    public static function remove_default_mini_cart_variation_hooks()
    {
        // Don't remove WooCommerce's default variation display for regular products
        // Only our custom handler will be active for products with extras
        // This allows normal variation display for products without extras

        // Only remove theme-specific variation display hooks that might conflict
        if (function_exists('woodmart_get_opt')) {
            // Remove WoodMart's variation display if it exists
            remove_action('woocommerce_get_item_data', 'woodmart_display_item_meta', 10);
        }
    }

    /**
     * Initialize product extras handling
     */
    public static function init_product_extras()
    {
        // Core functionality - always needed
        add_action('woocommerce_add_to_cart', [self::class, 'handle_add_to_cart'], 10, 6);
        add_filter('woocommerce_add_cart_item_data', [self::class, 'add_product_extras_to_cart_item_data'], 10, 3);
        add_filter('woocommerce_add_to_cart_fragments', [self::class, 'refresh_cart_fragments_for_addons'], 10, 1);

        add_action( 'woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 10 );

        // Single pricing hook - consolidates all pricing operations
        add_action('woocommerce_before_calculate_totals', [self::class, 'handle_cart_pricing'], 20, 1);
        add_action('woocommerce_calculate_totals', [self::class, 'add_extra_prices_to_cart_totals'], 999, 1);

        // Display hooks - only when needed
        add_action('woocommerce_mini_cart_contents', [self::class, 'display_product_extras_as_mini_cart_items']);
        // Use high priorities so our output wins over other plugins/themes
        add_filter('woocommerce_cart_item_price', [self::class, 'filter_cart_item_price_display'], 1200, 3);
        add_filter('woocommerce_cart_item_subtotal', [self::class, 'filter_cart_item_subtotal_display'], 1200, 3);
        
        add_filter('woocommerce_get_item_data', [self::class, 'remove_default_variation_display'], 5, 2);
        add_filter('woocommerce_get_item_data', [self::class, 'add_variation_data_to_mini_cart'], 10, 2);
        add_filter('woocommerce_get_item_data', [self::class, 'display_trade_in_info_universal'], 15, 2);
        // Remove deposit-specific variation entries added by AWCDP in mini-cart only
        add_filter('woocommerce_get_item_data', [self::class, 'remove_deposit_item_data_from_mini_cart'], 1200, 2);

        // Override Woodmart header subtotal fragment to be deposit-aware
        add_filter('woocommerce_add_to_cart_fragments', [self::class, 'override_header_cart_subtotal_fragment'], 1200, 1);

        // AJAX handlers - minimal set
        add_action('wp_loaded', [self::class, 'register_ajax_handlers'], 999);

        // Cache management
        add_action('woocommerce_cart_item_removed', [self::class, 'clear_price_cache_for_item'], 10, 2);

        // Handle cart item quantity updates for products with extras
        add_action('woocommerce_after_cart_item_quantity_update', [self::class, 'update_cart_item_extras_quantity'], 10, 4);

        // Conditional initialization - only when cart has items
        add_action('wp', [self::class, 'init_cart_dependent_features']);

        // Initialize side cart mixed product validation
        // add_action('wp_footer', [self::class, 'init_side_cart_mixed_product_validation']);

        // Override mini-cart subtotal output to be deposit-aware
        add_action('init', [self::class, 'override_mini_cart_subtotal_output'], 30);
    }

    /**
     * Initialize cart-dependent features only when cart has items
     * This reduces overhead on pages where cart is empty
     */
    public static function init_cart_dependent_features()
    {
        // Only initialize if WooCommerce is available and cart has items
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        // Check if cart has product extras or deposits - only then add heavy operations
        $has_extras = false;
        $has_deposits = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!empty($cart_item['product_extras'])) {
                $has_extras = true;
            }
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposits = true;
            }
        }

        if ($has_extras || $has_deposits) {
            // Add these hooks when we have product extras or deposits
            add_action('wp_head', [self::class, 'ensure_immediate_cart_display'], 1);
            add_filter('woocommerce_cart_get_total', [self::class, 'filter_cart_total'], 10, 1);
            add_filter('woocommerce_cart_contents_total', [self::class, 'filter_cart_contents_total'], 10, 1);
            // Ensure cart "Subtotal" row sums deposit-based subtotals on the cart page
            add_filter('woocommerce_cart_subtotal', [self::class, 'filter_cart_subtotal'], 1200, 3);

            // Apply pricing immediately since we know we have extras or deposits
            if ($has_extras) {
                self::handle_cart_pricing(WC()->cart);
            }
        }

        // Always ensure cart totals are calculated on cart/checkout pages
        if (is_cart() || is_checkout()) {
            add_action('woocommerce_before_cart', [self::class, 'ensure_cart_totals_on_page_load'], 5);
        }
    }

    /**
     * Handle add to cart action with product extras
     */
    public static function handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        // Check if we have product extras data in the request
        $product_extras = self::get_product_extras_from_request();
        $options = self::get_trade_deposit_options_from_request();

        if (!empty($product_extras)) {
            // Store product extras in cart item meta
            WC()->cart->cart_contents[$cart_item_key]['product_extras'] = $product_extras;

            // Store extras hash for comparison
            if (isset($cart_item_data['extras_hash'])) {
                WC()->cart->cart_contents[$cart_item_key]['extras_hash'] = $cart_item_data['extras_hash'];
            } else {
                $hash_input = $product_extras;
                if ($options['trade_in'] !== '') {
                    $hash_input['trade_in'] = $options['trade_in'];
                }
                $hash_input['awcdp_deposit_option'] = $options['awcdp_deposit_option'];
                WC()->cart->cart_contents[$cart_item_key]['extras_hash'] = self::generate_extras_hash($hash_input);
            }
        }

        // Store trade-in/deposit at top level for pricing & display
        if ($options['trade_in'] !== '') {
            WC()->cart->cart_contents[$cart_item_key]['trade_in'] = $options['trade_in'];
        }
        WC()->cart->cart_contents[$cart_item_key]['awcdp_deposit_option'] = $options['awcdp_deposit_option'];
    }

    /**
     * Add product extras to cart item data during AJAX requests
     * Fixed to properly handle trade-in and deposit options
     */
    public static function add_product_extras_to_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        // Check if we have product extras data in the request
        $product_extras = self::get_product_extras_from_request();
        // Capture trade-in & deposit options separately (not inside product_extras)
        $options = self::get_trade_deposit_options_from_request();

        // Prepare combined data for hashing only when extras or options are present
        $hash_input = !empty($product_extras) && is_array($product_extras) ? $product_extras : array();
        if ($options['trade_in'] !== '') {
            $hash_input['trade_in'] = $options['trade_in'];
        }
        if ($options['awcdp_deposit_option'] === 'yes') {
            $hash_input['awcdp_deposit_option'] = 'yes';
        }

        if (!empty($hash_input)) {
            $cart_item_data['extras_hash'] = self::generate_extras_hash($hash_input);
        }

        // Check if there's already a cart item with the same product and extras+options
        if (self::find_existing_cart_item_with_extras($product_id, $variation_id, $hash_input, $options)) {
            // Returning with the same extras_hash allows WooCommerce to merge identical items
            // while ensuring different trade/deposit combinations remain separate
            // No early return is needed; WooCommerce will handle merge by cart ID
        }

        // Add product extras (without trade/deposit) if present
        if (!empty($product_extras)) {
            $cart_item_data['product_extras'] = $product_extras;
            // Explicit flag to differentiate keys when extras exist
            $cart_item_data['has_product_extras'] = '1';
        }

        // Store trade-in & deposit options at top level for pricing/display (only when present)
        if ($options['trade_in'] !== '') {
            $cart_item_data['trade_in'] = $options['trade_in'];
        }
        if ($options['awcdp_deposit_option'] !== '') {
            $cart_item_data['awcdp_deposit_option'] = $options['awcdp_deposit_option'];
        }

        return $cart_item_data;
    }


    /**
     * Display product extras as separate mini-cart items
     */
    public static function display_product_extras_as_mini_cart_items()
    {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (!isset($cart_item['product_extras'])) {
                continue;
            }

            $product_extras = $cart_item['product_extras'];

            // Display selected information first (Gift section)
            if (isset($product_extras['selected_info']) && !empty($product_extras['selected_info'])) {
                // Get field label from the first info item, or use default
                $field_label = '';
                if (!empty($product_extras['selected_info'][0]['fieldLabel'])) {
                    $field_label = $product_extras['selected_info'][0]['fieldLabel'];
                } else {
                    $field_label = 'Gift'; // Default label
                }

                // Display section header
                echo '<li class="wd-extra-section-header">';
                echo '<div class="sh-extras-section-title">' . esc_html($field_label) . '</div>';
                echo '</li>';

                foreach ($product_extras['selected_info'] as $index => $info) {
                    $extra_key = $cart_item_key . '_extra_info_' . $index;
                    self::render_extra_mini_cart_item($extra_key, $info, 'info', $cart_item_key);
                }
            }

            // Display selected products second (Add on Deal section)
            if (isset($product_extras['selected_products']) && !empty($product_extras['selected_products'])) {
                // Get field label from the first product item, or use default
                $field_label = '';
                if (!empty($product_extras['selected_products'][0]['fieldLabel'])) {
                    $field_label = $product_extras['selected_products'][0]['fieldLabel'];
                } else {
                    $field_label = 'Add on Deal'; // Default label
                }

                // Display section header
                echo '<li class="wd-extra-section-header">';
                echo '<div class="sh-extras-section-title">' . esc_html($field_label) . '</div>';
                echo '</li>';

                foreach ($product_extras['selected_products'] as $index => $product) {
                    $extra_key = $cart_item_key . '_extra_product_' . $index;
                    self::render_extra_mini_cart_item($extra_key, $product, 'product', $cart_item_key);
                }
            }
        }

        // Add JavaScript to reposition extra items after their parent products
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Function to reposition extra cart items and their section headers
            function repositionExtraItems() {
                // Group extra items by parent key
                var extraGroups = {};

                $('.wd-extra-cart-item').each(function() {
                    var $extraItem = $(this);
                    var parentKey = $extraItem.data('parent-key');

                    if (!extraGroups[parentKey]) {
                        extraGroups[parentKey] = [];
                    }
                    extraGroups[parentKey].push($extraItem);
                });

                // For each parent, find all related extra items and section headers
                Object.keys(extraGroups).forEach(function(parentKey) {
                    var $parentItem = $('.woocommerce-mini-cart-item[data-key="' + parentKey + '"]');
                    if ($parentItem.length) {
                        // Find all section headers and extra items for this parent
                        var $allExtraElements = $('.wd-extra-section-header, .wd-extra-cart-item').filter(function() {
                            var $el = $(this);
                            if ($el.hasClass('wd-extra-section-header')) {
                                // Check if this section header is followed by items for this parent
                                var $nextItems = $el.nextUntil('.woocommerce-mini-cart-item, .wd-extra-section-header').filter('.wd-extra-cart-item[data-parent-key="' + parentKey + '"]');
                                return $nextItems.length > 0;
                            } else {
                                return $el.data('parent-key') === parentKey;
                            }
                        });

                        // Move all related elements after the parent
                        if ($allExtraElements.length > 0 && !$allExtraElements.first().hasClass('repositioned')) {
                            $allExtraElements.addClass('repositioned');
                            $parentItem.after($allExtraElements);
                        }
                    }
                });
            }

            // Function to add border-bottom to the last item in each group based on cart hash
            function addBorderToLastItems() {
                // Remove existing border classes
                $('.mini_cart_item, .wd-extra-cart-item').removeClass('has-border-bottom');

                // Find all cart items in order
                var allCartItems = [];

                // Get all items in the mini cart container in DOM order
                $('.woocommerce-mini-cart li, .widget_shopping_cart li').each(function() {
                    var $item = $(this);
                    if ($item.hasClass('mini_cart_item') || $item.hasClass('wd-extra-cart-item')) {
                        allCartItems.push($item);
                    }
                });

                if (allCartItems.length === 0) {
                    return;
                }

                // Group items by parent key
                var itemGroups = {};
                var currentGroup = [];
                var currentParentKey = null;

                allCartItems.forEach(function($item) {
                    if ($item.hasClass('mini_cart_item')) {
                        // This is a parent item
                        var itemKey = $item.data('key');
                        if (itemKey) {
                            // Save previous group if it exists
                            if (currentParentKey && currentGroup.length > 0) {
                                itemGroups[currentParentKey] = currentGroup.slice();
                            }

                            // Start new group
                            currentParentKey = itemKey;
                            currentGroup = [$item];
                        }
                    } else if ($item.hasClass('wd-extra-cart-item')) {
                        // This is an extra item
                        var parentKey = $item.data('parent-key');
                        if (parentKey === currentParentKey) {
                            currentGroup.push($item);
                        }
                    }
                });

                // Save the last group
                if (currentParentKey && currentGroup.length > 0) {
                    itemGroups[currentParentKey] = currentGroup.slice();
                }

                // Add border to the last item in each group
                Object.keys(itemGroups).forEach(function(parentKey) {
                    var group = itemGroups[parentKey];
                    if (group.length > 0) {
                        // Find the last extra item in the group (not the parent)
                        var lastExtraItem = null;
                        for (var i = group.length - 1; i >= 0; i--) {
                            if (group[i].hasClass('wd-extra-cart-item')) {
                                lastExtraItem = group[i];
                                break;
                            }
                        }

                        if (lastExtraItem) {
                            // Add border to the last extra item
                            lastExtraItem.addClass('has-border-bottom');
                        } else {
                            // If no extra items, add border to the parent item
                            var parentItem = group[0];
                            if (parentItem.hasClass('mini_cart_item')) {
                                parentItem.addClass('has-border-bottom');
                            }
                        }
                    }
                });

                // Also add border to the very last item in the entire cart
                if (allCartItems.length > 0) {
                    var veryLastItem = allCartItems[allCartItems.length - 1];
                    if (!veryLastItem.hasClass('has-border-bottom')) {
                        veryLastItem.addClass('has-border-bottom');
                    }
                }
            }

            // Initial positioning and border setup
            repositionExtraItems();
            addBorderToLastItems();

            // Strict single-execution mechanism for fragment refreshes
            window.fragmentRefreshControl = window.fragmentRefreshControl || {
                hasExecuted: false,
                executionId: null,
                reset: function() {
                    this.hasExecuted = false;
                    this.executionId = null;
                }
            };

            // Single-execution fragment refresh handler
            function handleFragmentRefresh(event) {
                const control = window.fragmentRefreshControl;
                const currentId = Date.now() + Math.random();

                // Strict single execution - only allow one execution per add-to-cart action
                if (control.hasExecuted && control.executionId) {
                    return;
                }

                control.hasExecuted = true;
                control.executionId = currentId;

                // Reposition extra items after fragment refresh
                setTimeout(repositionExtraItems, 100);

                // Add borders to last items after repositioning
                setTimeout(addBorderToLastItems, 150);

                // Force refresh of extra item prices to ensure they reflect current quantities
                setTimeout(function() {
                    $('.wd-extra-cart-item').each(function() {
                        var $extraItem = $(this);
                        var $priceElement = $extraItem.find('.wd-extra-item-price');
                        var $quantityElement = $extraItem.find('.wd-extra-item-quantity');

                        // Trigger a visual update to ensure prices are current
                        if ($priceElement.length) {
                            $priceElement.addClass('price-updated');
                            setTimeout(function() {
                                $priceElement.removeClass('price-updated');
                            }, 500);
                        }
                    });
                }, 200);

                // Reset after 2 seconds to allow next action
                setTimeout(function() {
                    control.reset();
                }, 2000);
            }

            // Remove any existing handlers first
            $(document.body).off('wc_fragments_refreshed.custom wc_fragments_loaded.custom');

            // Bind with namespace to prevent duplicates
            $(document.body).on('wc_fragments_refreshed.custom', handleFragmentRefresh);

            // Also trigger on cart updates
            $(document.body).on('updated_wc_div', function() {
                setTimeout(addBorderToLastItems, 100);
            });
        });
        </script>
        <?php
    }

    /**
     * Render a single extra item as a mini-cart item
     */
    private static function render_extra_mini_cart_item($extra_key, $extra_data, $type, $parent_cart_key)
    {
        $is_product = ($type === 'product');
        $title = $is_product ? $extra_data['title'] : $extra_data['infoLabel'];
        $quantity = $is_product ? (int)$extra_data['quantity'] : 1;
        $image_url = '';
        $price = '';

        if ($is_product) {
            // For products, use stored image URL if available, otherwise get from WooCommerce product
            $image_url = '';

            // Always get fresh image from WooCommerce product to avoid caching issues
            $product_id = isset($extra_data['variationId']) ? $extra_data['variationId'] : $extra_data['productId'];
            $product = wc_get_product($product_id);
            if ($product) {
                // Get product image with proper fallback logic (matching ProductExtrasWidget)
                if ($product->is_type('variation')) {
                    // For variation products, get the variation image first, then fall back to parent
                    $variation_image_id = $product->get_image_id();
                    if ($variation_image_id) {
                        $image_url = wp_get_attachment_image_url($variation_image_id, 'woocommerce_thumbnail');
                    } else {
                        // Fall back to parent product image
                        $parent_product = wc_get_product($product->get_parent_id());
                        if ($parent_product) {
                            $parent_image_id = $parent_product->get_image_id();
                            if ($parent_image_id) {
                                $image_url = wp_get_attachment_image_url($parent_image_id, 'woocommerce_thumbnail');
                            }
                        }
                    }
                } else {
                    // For regular products, get the product image
                    $image_id = $product->get_image_id();
                    if ($image_id) {
                        $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                    }
                }
            }

            // Final fallback to placeholder
            if (empty($image_url)) {
                $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
            }


            // Calculate price based on quantity - always get fresh price from product
            $product_id = isset($extra_data['variationId']) ? $extra_data['variationId'] : $extra_data['productId'];
            $product = wc_get_product($product_id);
            if ($product) {
                $unit_price = floatval($product->get_price());
                $unit_regular = floatval($product->get_regular_price());

                // If we have stored price data, use it as fallback but prefer fresh product price
                if (isset($extra_data['price']) && $unit_price <= 0) {
                    $unit_price = floatval($extra_data['price']);
                }

                // Get original price from stored data if available
                if (isset($extra_data['originalPrice']) && is_numeric($extra_data['originalPrice'])) {
                    $unit_regular = floatval($extra_data['originalPrice']);
                } elseif (isset($extra_data['original']) && is_numeric($extra_data['original'])) {
                    $unit_regular = floatval($extra_data['original']);
                }

                // Always calculate total price based on current quantity
                $total_price = $unit_price * $quantity;
                $total_regular = $unit_regular * $quantity;

                // Format price with original price if different
                $price = wc_price($total_price);
                if ($total_regular > $total_price && $total_regular > 0) {
                    $price .= ' <span class="sh-price-original">' . wc_price($total_regular) . '</span>';
                }
            }
        } else {
            // For info items, show RM 0.00 as current price and original price if available
            $current_price = 0;
            $original_price = 0;

            // Get original price from stored data
            if (isset($extra_data['infoOriginalPrice']) && is_numeric($extra_data['infoOriginalPrice'])) {
                $original_price = floatval($extra_data['infoOriginalPrice']);
            } elseif (isset($extra_data['originalPrice']) && is_numeric($extra_data['originalPrice'])) {
                $original_price = floatval($extra_data['originalPrice']);
            } elseif (isset($extra_data['original']) && is_numeric($extra_data['original'])) {
                $original_price = floatval($extra_data['original']);
            }

            // Format price with original price if available
            $price = wc_price($current_price);
            if ($original_price > 0) {
                $price .= ' <span class="sh-price-original">' . wc_price($original_price) . '</span>';
            }

            $image_url = '';
            if (!empty($extra_data['imageUrl']) && $extra_data['imageUrl'] !== wc_placeholder_img_src('woocommerce_thumbnail')) {
                $image_url = $extra_data['imageUrl'];
            } else {
                $product_id = isset($extra_data['productId']) ? $extra_data['productId'] : '';
                if ($product_id) {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $image_id = $product->get_image_id();
                        if ($image_id) {
                            $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                        }
                    }
                }
                if (empty($image_url)) {
                    $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                }
            }
        }

        ?>
        <li class="wd-extra-cart-item" data-key="<?php echo esc_attr($extra_key); ?>" data-parent-key="<?php echo esc_attr($parent_cart_key); ?>">
            <div class="sh-extras-item">
                <?php if (!empty($image_url)): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" class="sh-extras-image">
                <?php endif; ?>

                <div class="sh-extras-content">
                    <div class="sh-extras-left">
                        <div class="sh-extras-title"><?php echo esc_html($title); ?></div>

                        <?php
                        // Display variation data for product extras
                        if ($is_product && !empty($extra_data['variationData'])) {
                            echo self::get_formatted_variation_data_for_mini_cart($extra_data);
                        }
                        ?>
                    </div>

                    <div class="sh-extras-right">
                        <div class="sh-extras-meta">
                            <?php if (!empty($price)): ?>
                                <span class="sh-extras-price"><?php echo wp_kses_post($price); ?></span>
                            <?php endif; ?>
                            <span class="sh-extras-qty"><?php echo esc_html__('Qty:', 'senheng-core'); ?> <?php echo esc_html($quantity); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <?php
    }

    /**
     * Display product extras in cart and checkout (for checkout page)
     */
    public static function display_product_extras_in_cart($item_data, $cart_item)
    {
        if (isset($cart_item['product_extras'])) {
            $product_extras = $cart_item['product_extras'];

            // Display selected products with exact parent product styling
            if (isset($product_extras['selected_products']) && !empty($product_extras['selected_products'])) {
                foreach ($product_extras['selected_products'] as $product) {
                    // Match the exact structure of parent product: span.wd-entities-title for main title
                    $display_value = '<span class="wd-entities-title">';
                    $display_value .= esc_html($product['title']);
                    if (!empty($product['quantity']) && $product['quantity'] > 1) {
                        $display_value .= ' (Qty: ' . esc_html($product['quantity']) . ')';
                    }
                    $display_value .= '</span>';

                    $item_data[] = array(
                        'key'   => '',
                        'value' => $display_value,
                    );
                }
            }

            // Display selected information with exact parent product styling
            if (isset($product_extras['selected_info']) && !empty($product_extras['selected_info'])) {
                foreach ($product_extras['selected_info'] as $info) {
                    // Match the exact structure of parent product: span.wd-entities-title for main title
                    $display_value = '<span class="wd-entities-title">';

                    // Always get fresh image from WooCommerce product - no caching
                    $image_url = '';

                    // Get fresh image from WooCommerce product to ensure latest image is displayed
                    $product_id = isset($info['productId']) ? $info['productId'] : '';
                    if ($product_id) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $image_id = $product->get_image_id();
                            if ($image_id) {
                                $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                            }
                        }
                    }

                    // Fallback to placeholder if no image found
                    if (empty($image_url)) {
                        $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                    }

                    $display_value .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($info['infoLabel']) . '" class="wd-extra-item-image">';
                }

                $display_value .= esc_html($info['infoLabel']);

                // Add price if available
                if (!empty($info['infoPrice'])) {
                    $display_value .= ' - ' . esc_html($info['infoPrice']);
                }

                $display_value .= '</span>';

                $item_data[] = array(
                    'key'   => '',
                    'value' => $display_value,
                );
            }
        }

        return $item_data;
    }

    /**
     * Get product extras data from the current request
     * Safely handles cases where product extras are missing or incomplete
     */
    public static function get_product_extras_from_request()
    {
        $product_extras = array();

        // If no product extras flag is set, still collect any legacy or
        // compatibility fields but do NOT return early. This ensures
        // trade-in and deposit options are captured for cart item uniqueness.
        if (!isset($_POST['has_product_extras']) || $_POST['has_product_extras'] !== '1') {
            if (isset($_POST['product_extras']) && is_array($_POST['product_extras'])) {
                $product_extras = $_POST['product_extras'];
            } else {
                // Check for PEWC compatibility fields
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'pewc_') === 0) {
                        $product_extras[$key] = sanitize_text_field($value);
                    }
                }
            }
        }

        // Check for new format: JSON-encoded product extras data from widget
        if (isset($_POST['has_product_extras']) && $_POST['has_product_extras'] === '1') {
            // Get selected products data (new format)
            if (isset($_POST['product_extras_products'])) {
                $products_data = json_decode(urldecode($_POST['product_extras_products']), true);
                if (is_array($products_data)) {
                    // Normalize and enrich each product entry with price info
                    $normalized = array();
                    foreach ($products_data as $entry) {
                        $pid = isset($entry['productId']) ? intval($entry['productId']) : (isset($entry['id']) ? intval($entry['id']) : 0);
                        if (!$pid) { continue; }

                        $qty = isset($entry['quantity']) ? intval($entry['quantity']) : 1;
                        $ptype = isset($entry['productType']) ? $entry['productType'] : 'simple';
                        $variationId = isset($entry['variationId']) ? intval($entry['variationId']) : 0;

                        // Compute price and originalPrice if missing or zero
                        $price = isset($entry['price']) ? floatval($entry['price']) : 0.0;
                        $originalPrice = isset($entry['originalPrice']) ? floatval($entry['originalPrice']) : 0.0;

                        if ($variationId) {
                            $var_product = wc_get_product($variationId);
                            if ($var_product) {
                                if ($price <= 0) { $price = floatval($var_product->get_price()); }
                                if ($originalPrice <= 0) { $originalPrice = floatval($var_product->get_regular_price()); }
                            }
                        } else {
                            $prod = wc_get_product($pid);
                            if ($prod) {
                                if ($price <= 0) { $price = floatval($prod->get_price()); }
                                if ($originalPrice <= 0) { $originalPrice = floatval($prod->get_regular_price()); }
                            }
                        }

                        $normalized[] = array(
                            'productId' => $pid,
                            'quantity' => $qty,
                            'productType' => $ptype,
                            'variationData' => isset($entry['variationData']) && is_array($entry['variationData']) ? $entry['variationData'] : array(),
                            'variationId' => $variationId ?: null,
                            'title' => isset($entry['title']) ? $entry['title'] : '',
                            'price' => $price,
                            'originalPrice' => $originalPrice,
                            'imageUrl' => ''
                        );
                    }

                    if (!empty($normalized)) {
                        $product_extras['selected_products'] = $normalized;
                    }
                }
            }

            // Get selected info data (new format)
            if (isset($_POST['product_extras_info'])) {
                $info_data = json_decode(urldecode($_POST['product_extras_info']), true);
                if (is_array($info_data)) {
                    $product_extras['selected_info'] = $info_data;
                }
            }

            // Handle the new individual field format for information fields
            if (isset($_POST['extra_info_ids']) && is_array($_POST['extra_info_ids'])) {
                $selected_info = array();

                foreach ($_POST['extra_info_ids'] as $info_id) {
                    $info_data = array(
                        'infoId' => $info_id,
                        'infoLabel' => isset($_POST['extra_info_labels'][$info_id]) ? $_POST['extra_info_labels'][$info_id] : '',
                        'infoPrice' => isset($_POST['extra_info_prices'][$info_id]) ? $_POST['extra_info_prices'][$info_id] : '',
                        'infoOriginalPrice' => isset($_POST['extra_info_original_prices'][$info_id]) ? $_POST['extra_info_original_prices'][$info_id] : '',
                        'imageUrl' => isset($_POST['extra_info_images'][$info_id]) ? $_POST['extra_info_images'][$info_id] : '',
                        'fieldLabel' => isset($_POST['extra_info_field_labels'][$info_id]) ? $_POST['extra_info_field_labels'][$info_id] : '',
                    );

                    $selected_info[] = $info_data;
                }

                if (!empty($selected_info)) {
                    $product_extras['selected_info'] = $selected_info;
                }
            }

            // Handle the actual format being sent by the frontend
            if (isset($_POST['extra_product_ids']) && is_array($_POST['extra_product_ids'])) {
                $selected_products = array();

                foreach ($_POST['extra_product_ids'] as $product_id) {
                    $quantity = isset($_POST['extra_product_qty'][$product_id]) ? intval($_POST['extra_product_qty'][$product_id]) : 1;

                    $product_data = array(
                        'id' => $product_id,
                        'productId' => $product_id, // Keep for backward compatibility
                        'quantity' => $quantity,
                        'productType' => isset($_POST['extra_product_type'][$product_id]) ? $_POST['extra_product_type'][$product_id] : 'simple',
                    );

                    // Add variation data if it's a variable product
                    if (isset($_POST['extra_product_variation'][$product_id])) {
                        $product_data['variationData'] = $_POST['extra_product_variation'][$product_id];
                    }

                    // Add variation ID if available
                    if (isset($_POST['extra_product_variation_id'][$product_id])) {
                        $product_data['variationId'] = $_POST['extra_product_variation_id'][$product_id];
                    }

                    // Add field label if available
                    if (isset($_POST['extra_product_field_labels'][$product_id])) {
                        $product_data['fieldLabel'] = $_POST['extra_product_field_labels'][$product_id];
                    }

                    // Get product title and price for display and calculation
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $product_data['title'] = $product->get_name();

                        // For variable products, get price from the specific variation
                        if (isset($product_data['variationId']) && $product_data['variationId']) {
                            $variation_product = wc_get_product($product_data['variationId']);
                            if ($variation_product) {
                                $product_data['price'] = floatval($variation_product->get_price());
                                $product_data['originalPrice'] = floatval($variation_product->get_regular_price());
                            } else {
                                $product_data['price'] = floatval($product->get_price());
                                $product_data['originalPrice'] = floatval($product->get_regular_price());
                            }
                        } else {
                            $product_data['price'] = floatval($product->get_price());
                            $product_data['originalPrice'] = floatval($product->get_regular_price());
                        }
                    }

                    $selected_products[] = $product_data;
                }

                if (!empty($selected_products)) {
                    $product_extras['selected_products'] = $selected_products;
                }
            }
        }

        // Check for legacy format: product extras in POST data
        if (isset($_POST['product_extras']) && is_array($_POST['product_extras'])) {
            $product_extras = array_merge($product_extras, $_POST['product_extras']);
        }

        // Check for individual product extra fields (PEWC compatibility)
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'pewc_') === 0) {
                $product_extras[$key] = sanitize_text_field($value);
            }
        }

        return $product_extras;
    }

    /**
     * Get trade-in and deposit options from the current request as top-level options
     * Normalizes to 'yes'/'no' values and handles legacy mapping
     */
    public static function get_trade_deposit_options_from_request()
    {
        // Helper to normalize truthy values
        $is_truthy = function($value) {
            $v = strtolower(trim($value));
            return in_array($v, ['yes', 'true', '1', 'on'], true);
        };

        // Trade-in option (accept yes/true/1/on)
        $trade_in = '';
        if (isset($_POST['trade_in'])) {
            $trade_in = $is_truthy(sanitize_text_field($_POST['trade_in'])) ? 'yes' : 'no';
        }

        // Deposit option: prefer AWCDP format (accept yes/true/1/on), fallback to legacy deposit_option
        $deposit = '';
        if (isset($_POST['awcdp_deposit_option'])) {
            $deposit = $is_truthy(sanitize_text_field($_POST['awcdp_deposit_option'])) ? 'yes' : 'no';
        } elseif (isset($_POST['deposit_option'])) {
            // Legacy mapping: only set when 'deposit' explicitly chosen
            $deposit = (strtolower(sanitize_text_field($_POST['deposit_option'])) === 'deposit') ? 'yes' : '';
        }

        return array(
            'trade_in' => $trade_in,
            'awcdp_deposit_option' => $deposit,
        );
    }

    /**
     * Handle both sale quantity pricing and product extras pricing in one function
     * This prevents conflicts between multiple woocommerce_before_calculate_totals hooks
     *
     * @param WC_Cart $cart
     */
    public static function handle_cart_pricing($cart) {
        // Prevent infinite loops
        static $processing = false;
        if ($processing) {
            return;
        }

        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $processing = true;

        // Check if we're in a fragment refresh context
        $fragment_refresh_context = self::$fragment_refresh_active;

        // Initialize price cache from session
        $price_cache = WC()->session ? WC()->session->get('price_cache', array()) : array();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
            $unique_key = $product_id . '_' . $variation_id;

            // Use a more persistent flag that includes product info
            $price_calculated_key = '_price_calculated_' . $unique_key;

            // STEP 1: Handle sale quantity pricing (force regular price logic)
            if (!empty($cart_item['force_regular_price'])) {
                $regular_price = $cart_item['data']->get_regular_price();
                $cart_item['data']->set_price($regular_price);
            }

            // STEP 2: Handle product extras pricing (simplified without caching)

            // Check if we have product extras and haven't already calculated the price
            if (isset($cart_item['product_extras']) &&
                !empty($cart_item['product_extras']['selected_products']) &&
                !isset($cart_item[$price_calculated_key])) {

                $product_extras = $cart_item['product_extras'];

                // Get the original product price (not the modified cart price)
                if ($variation_id) {
                    $original_product = wc_get_product($variation_id);
                } else {
                    $original_product = wc_get_product($product_id);
                }

                $base_price = 0;
                if ($original_product) {
                    // Check if force_regular_price is set (for sale quantity pricing)
                    if (!empty($cart_item['force_regular_price'])) {
                        $base_price = floatval($original_product->get_regular_price());
                    } else {
                        $base_price = floatval($original_product->get_price());
                    }
                }

                // Final validation - if base price is still 0, this might be a free product
                if ($base_price == 0) {
                    // Base price is 0 - could be a free product
                }

                $extras_total = 0;

                foreach ($product_extras['selected_products'] as $index => $extra_product) {
                    $extra_price = isset($extra_product['price']) ? floatval($extra_product['price']) : 0;
                    $extra_quantity = isset($extra_product['quantity']) ? intval($extra_product['quantity']) : 1;
                    $extra_total = $extra_price * $extra_quantity;

                    $extras_total += $extra_total;
                }

                if ($extras_total > 0) {
                    // Always set parent product price to base price only (extras are displayed separately)
                    $new_price = $base_price;

                    // During fragment refresh, ensure we're not accidentally including extras
                    if ($fragment_refresh_context) {
                        // Fragment refresh: Setting parent product price to base price only
                    } else {
                        // Setting parent product price to base price
                    }

                    $cart_item['data']->set_price($new_price);

                    // Mark that price has been calculated with a more persistent flag
                    $cart->cart_contents[$cart_item_key][$price_calculated_key] = true;
                    $cart->cart_contents[$cart_item_key]['_final_price'] = $new_price;

                    // Store in session cache only if price is valid
                    if ($new_price > 0) {
                        $price_cache[$cache_key] = $new_price;
                    }
                }
            }

            // STEP 3: Handle trade-in pricing (should override product extras pricing)
            if (isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes' && isset($cart_item['awcdp_deposit_option'])) {
                $payment_option = $cart_item['awcdp_deposit_option'];

                // Check if we need to recalculate (either not calculated yet or price doesn't match expected)
                $needs_calculation = !isset($cart_item[$price_calculated_key . '_tradein']);
                if (!$needs_calculation && isset($cart_item['_final_tradein_price'])) {
                    $current_price = floatval($cart_item['data']->get_price());
                    $expected_price = floatval($cart_item['_final_tradein_price']);
                    $needs_calculation = abs($current_price - $expected_price) > 0.01;
                }

                if ($needs_calculation) {
                    // Get the original product
                    if ($variation_id) {
                        $original_product = wc_get_product($variation_id);
                    } else {
                        $original_product = wc_get_product($product_id);
                    }

                    if ($original_product) {
                        $base_price = 0;

                        // Check if force_regular_price is set (for sale quantity pricing)
                        if (!empty($cart_item['force_regular_price'])) {
                            $base_price = floatval($original_product->get_regular_price());
                        } else {
                            $base_price = floatval($original_product->get_price());
                        }

                        $final_price = $base_price;

                        // Apply deposit pricing if deposit option is selected
                        if ($payment_option === 'yes') {
                            // Get deposit settings
                            $deposit_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
                            $deposit_type = get_post_meta($product_id, '_awcdp_deposit_type', true);

                            if (!empty($deposit_amount)) {
                                if ($deposit_type === 'percent') {
                                    // Percentage deposit
                                    $final_price = $base_price * (floatval($deposit_amount) / 100);
                                } elseif ($deposit_type === 'fixed') {
                                    // Fixed deposit amount
                                    $final_price = floatval($deposit_amount);
                                } else {
                                    // Default to fixed amount if type is not specified
                                    $final_price = floatval($deposit_amount);
                                }
                            }
                        }
                        // If payment_option is 'no', use full price (already set as $base_price)

                        // Set the calculated price (this will override any previous pricing)
                        $cart_item['data']->set_price($final_price);

                        // Mark that trade-in price has been calculated
                        $cart->cart_contents[$cart_item_key][$price_calculated_key . '_tradein'] = true;
                        $cart->cart_contents[$cart_item_key]['_final_tradein_price'] = $final_price;

                        // Store in session cache
                        $price_cache[$unique_key . '_tradein'] = $final_price;
                    }
                }
            } elseif (isset($cart_item['_final_tradein_price']) && isset($cart_item[$price_calculated_key . '_tradein'])) {
                // If we've already calculated the trade-in price, just set it to prevent resets
                $final_price = floatval($cart_item['_final_tradein_price']);
                $cart_item['data']->set_price($final_price);

                // Store in session cache
                $price_cache[$unique_key . '_tradein'] = $final_price;
            } elseif (isset($cart_item['_final_price']) && isset($cart_item[$price_calculated_key])) {
                // If we've already calculated the price, just set it to prevent resets
                $final_price = floatval($cart_item['_final_price']);
                $cart_item['data']->set_price($final_price);

                // Store in session cache
                $price_cache[$cache_key] = $final_price;
            }
        }

        // Save updated price cache to session
        WC()->session->set('price_cache', $price_cache);

        $processing = false;
    }

    /**
     * Add extra product prices to cart subtotal calculation
     * This ensures the cart subtotal includes both base product prices and extra product prices
     *
     * @param WC_Cart $cart_object
     */
    public static function add_extra_prices_to_cart_totals($cart_object) {
        // Prevent infinite loops and admin interference
        static $processing = false;
        if ($processing || (is_admin() && !defined('DOING_AJAX'))) {
            return;
        }

        // Now handles both cart and checkout pages since CheckoutController conflicts were removed

        $processing = true;

        $total_extras_price = 0;

        // Calculate total price of all extra products across all cart items
        foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
            if (!isset($cart_item['product_extras']) || empty($cart_item['product_extras']['selected_products'])) {
                continue;
            }

            $product_extras = $cart_item['product_extras'];
            $cart_item_quantity = $cart_item['quantity'];

            // Calculate extras total for this cart item
            foreach ($product_extras['selected_products'] as $extra_product) {
                $extra_price = isset($extra_product['price']) ? floatval($extra_product['price']) : 0;
                $extra_quantity = isset($extra_product['quantity']) ? intval($extra_product['quantity']) : 1;

                // Multiply by cart item quantity since extras are per parent product
                $extra_total = $extra_price * $extra_quantity * $cart_item_quantity;
                $total_extras_price += $extra_total;
            }
        }

        // Add extra product prices to cart subtotal and total
        if ($total_extras_price > 0) {
            $original_subtotal = $cart_object->subtotal;
            $original_total = $cart_object->total;
            $original_cart_contents_total = $cart_object->cart_contents_total;
            $shipping_total = $cart_object->get_shipping_total();
            $tax_total = $cart_object->get_total_tax();
            $discount_total = $cart_object->get_discount_total();

            // Update subtotal and cart contents total
            $cart_object->subtotal += $total_extras_price;
            $cart_object->cart_contents_total += $total_extras_price;

            // Calculate total properly: cart_contents_total + shipping + taxes - discounts
            $cart_object->total = $cart_object->cart_contents_total + $shipping_total + $tax_total - $discount_total;
        }

        $processing = false;
    }



    /**
     * Ensure cart totals are calculated when cart page loads
     */
    public static function ensure_cart_totals_on_page_load()
    {
        if (WC()->cart && !WC()->cart->is_empty()) {
            // Use debounced calculation for better performance
            self::debounced_calculate_totals();
        }
    }

    /**
     * Filter cart total to ensure correct value is always returned
     */
    public static function filter_cart_total($total)
    {
        // Prevent infinite loops by checking if we're already processing
        static $processing = false;
        if ($processing) {
            return $total;
        }

        // Apply on both cart and checkout pages
        if ((!is_cart() && !is_checkout()) || !WC()->cart) {
            return $total;
        }

        if (function_exists('is_checkout') && is_checkout()) {
            $processing = true;
            $cart_object = WC()->cart;

            $deposit_total = 0.0;
            $extras_total = 0.0;

            foreach ($cart_object->get_cart() as $cart_item) {
                $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;
                if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                    $deposit_total += floatval($cart_item['deposit_amount']) * $qty;
                } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable'])
                    && $cart_item['awcdp_deposit']['enable'] == 1
                    && isset($cart_item['awcdp_deposit']['deposit'])) {
                    $deposit_total += floatval($cart_item['awcdp_deposit']['deposit']) * $qty;
                } else {
                    $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                    $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                    $deposit_total += $price * $qty;
                }

                $extras_total += (class_exists('CartController') ? \CartController::calculate_product_extras_total($cart_item) : 0) * $qty;
            }

            $warranty_total = 0.0;
            foreach ($cart_object->get_fees() as $fee) {
                $name = isset($fee->name) ? (string)$fee->name : '';
                if ($name !== '' && (stripos($name, 'warranty') !== false || stripos($name, '9.9 Product Warranty') !== false)) {
                    $warranty_total += floatval($fee->amount);
                }
            }

            $shipping_total = $cart_object->get_shipping_total();
            $tax_total = $cart_object->get_total_tax();
            $discount_total = $cart_object->get_discount_total();

            $final_total = $deposit_total + $extras_total + $warranty_total + $shipping_total + $tax_total - $discount_total;

            $processing = false;
            return $final_total;
        }

        $processing = true;
        $cart_object = WC()->cart;

        // Check if there are any deposit items or product extras in the cart
        $has_extras = false;
        $has_deposits = false;
        foreach ($cart_object->get_cart() as $cart_item) {
            if (isset($cart_item['product_extras']) && !empty($cart_item['product_extras']['selected_products'])) {
                $has_extras = true;
            }
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposits = true;
            }
        }

        // Only intervene if there are product extras or deposits in the cart
        if (!$has_extras && !$has_deposits) {
            $processing = false;
            return $total;
        }

        // If we have deposits, calculate deposit-based total
        if ($has_deposits) {
            $deposit_total = 0;
            foreach ($cart_object->get_cart() as $cart_item) {
                if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                    $deposit_total += $cart_item['deposit_amount'] * $cart_item['quantity'];
                } else {
                    // For non-deposit items, use regular pricing
                    $product = $cart_item['data'];
                    $deposit_total += $product->get_price() * $cart_item['quantity'];
                }
            }

            // Add shipping, tax, and subtract discounts
            $correct_total = $deposit_total + $cart_object->get_shipping_total() + $cart_object->get_total_tax() - $cart_object->get_discount_total();

            $processing = false;
            return $correct_total;
        }

        // Calculate the correct total using the formula for product extras:
        // product extra total = product extra price * product extra quantity
        // parent product total = parent product price * parent product quantity
        // subtotal = product extra total + parent product total
        // total = subtotal + shipping + tax - discount

        $correct_total = $cart_object->cart_contents_total + $cart_object->get_shipping_total() + $cart_object->get_total_tax() - $cart_object->get_discount_total();

        // Get the current total as a float for comparison
        $current_total = floatval(str_replace(',', '', strip_tags($total)));

        // Only return the corrected total if there's a significant difference (more than 1 cent)
        if (abs($current_total - $correct_total) > 0.01) {
            $processing = false;
            // Return the numeric value directly to avoid triggering wc_price() which causes infinite loop
            return $correct_total;
        }

        $processing = false;
        return $total;
    }

    /**
     * Filter cart contents total to handle deposit pricing
     */
    public static function filter_cart_contents_total($contents_total)
    {
        // Prevent infinite loops by checking if we're already processing
        static $processing = false;
        if ($processing) {
            return $contents_total;
        }

        // Apply on both cart and checkout pages
        if ((!is_cart() && !is_checkout()) || !WC()->cart) {
            return $contents_total;
        }

        if (function_exists('is_checkout') && is_checkout()) {
            $processing = true;
            $cart_object = WC()->cart;

            $deposit_contents_total = 0.0;
            $extras_total = 0.0;

            foreach ($cart_object->get_cart() as $cart_item) {
                $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;
                if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                    $deposit_contents_total += floatval($cart_item['deposit_amount']) * $qty;
                } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable'])
                    && $cart_item['awcdp_deposit']['enable'] == 1
                    && isset($cart_item['awcdp_deposit']['deposit'])) {
                    $deposit_contents_total += floatval($cart_item['awcdp_deposit']['deposit']) * $qty;
                } else {
                    $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                    $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                    $deposit_contents_total += $price * $qty;
                }

                $extras_total += (class_exists('CartController') ? \CartController::calculate_product_extras_total($cart_item) : 0) * $qty;
            }

            $processing = false;
            return $extras_total > 0 ? ($deposit_contents_total + $extras_total) : $deposit_contents_total;
        }

        $processing = true;
        $cart_object = WC()->cart;

        // Check if there are any deposit items in the cart
        $has_deposits = false;
        foreach ($cart_object->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposits = true;
                break;
            }
        }

        // Only intervene if there are deposits in the cart
        if (!$has_deposits) {
            $processing = false;
            return $contents_total;
        }

        // Calculate deposit-based contents total
        $deposit_contents_total = 0;
        foreach ($cart_object->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $deposit_contents_total += floatval($cart_item['deposit_amount']) * (isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1);
            } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable'])
                && $cart_item['awcdp_deposit']['enable'] == 1
                && isset($cart_item['awcdp_deposit']['deposit'])) {
                // Fallback to AWCDP per-item deposit value
                $deposit_contents_total += floatval($cart_item['awcdp_deposit']['deposit']) * (isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1);
            } else {
                // For non-deposit items, use regular pricing
                $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                $deposit_contents_total += $price * (isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1);
            }
        }

        $processing = false;
        return $deposit_contents_total;
    }

    /**
     * Filter cart subtotal display to be deposit-aware in all contexts.
     * On first render, sums deposit amounts for deposit items and uses regular prices for others.
     */
    public static function filter_cart_subtotal($cart_subtotal, $compound, $cart)
    {
        // Require a valid cart object from WooCommerce
        if (!is_object($cart) || !method_exists($cart, 'get_cart')) {
            return $cart_subtotal;
        }

        // Detect if any cart item has a deposit selected
        $has_deposits = false;
        foreach ($cart->get_cart() as $cart_item) {
            if ((isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount']))
                || (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable'])
                    && $cart_item['awcdp_deposit']['enable'] == 1
                    && isset($cart_item['awcdp_deposit']['deposit']))) {
                $has_deposits = true;
                break;
            }
        }

        // If there are no deposits, keep the original WooCommerce subtotal
        if (!$has_deposits) {
            return $cart_subtotal;
        }

        $deposit_subtotal = 0.0;
        $extras_subtotal = 0.0;
        foreach ($cart->get_cart() as $cart_item) {
            $qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $deposit_subtotal += floatval($cart_item['deposit_amount']) * $qty;
            } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable'])
                && $cart_item['awcdp_deposit']['enable'] == 1
                && isset($cart_item['awcdp_deposit']['deposit'])) {
                // Fallback to AWCDP per-item deposit value
                $deposit_subtotal += floatval($cart_item['awcdp_deposit']['deposit']) * $qty;
            } else {
                // For non-deposit items, use current product price
                $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                $deposit_subtotal += $price * $qty;
            }

            $extras_subtotal += (class_exists('CartController') ? CartController::calculate_product_extras_total($cart_item) : 0) * $qty;
        }

        return wc_price($deposit_subtotal + $extras_subtotal);
    }

    /**
     * Consolidated pricing function - handles all pricing scenarios efficiently
     * Replaces multiple separate pricing functions for better performance
     */
    public static function ensure_pricing_for_context($context = 'general')
    {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        // Set context flags
        if ($context === 'ajax_refresh') {
            self::$fragment_refresh_active = true;
        }

        // Check if we need cache clearing (only for AJAX refresh)
        if ($context === 'ajax_refresh') {
            $needs_cache_clear = false;
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (isset($cart_item['product_extras']) && !empty($cart_item['product_extras']['selected_products'])) {
                    $needs_cache_clear = true;
                    break;
                }
            }

            if ($needs_cache_clear && WC()->session) {
                $price_cache = WC()->session->get('price_cache', array());
                // Only clear stale cache entries, not all
                foreach ($price_cache as $key => $value) {
                    if (strpos($key, '_stale') !== false) {
                        unset($price_cache[$key]);
                    }
                }
                WC()->session->set('price_cache', $price_cache);
            }
        }

        // Apply pricing calculation
        self::handle_cart_pricing(WC()->cart);

        // Reset context flags
        if ($context === 'ajax_refresh') {
            self::$fragment_refresh_active = false;
        }
    }

    /**
     * Refresh cart fragments to include product extras data
     */
    public static function refresh_cart_fragments_for_addons($fragments)
    {
        // Use transient-based debouncing to prevent excessive fragment refreshes
        $refresh_key = 'cart_fragment_refresh_' . get_current_user_id();
        $current_time = microtime(true);

        // Check if refresh is already in progress or happened recently
        $last_refresh = get_transient($refresh_key);
        if ($last_refresh && ($current_time - $last_refresh < 0.1)) {
            return $fragments;
        }

        // Set refresh in progress transient
        set_transient($refresh_key, $current_time, 1);

        // Only refresh if we have product extras and fragment isn't already set
        if (WC()->cart && !WC()->cart->is_empty() && !isset($fragments['div.widget_shopping_cart_content'])) {
            $has_extras = false;

            // Quick check for product extras
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (isset($cart_item['product_extras'])) {
                    $has_extras = true;
                    break;
                }
            }

            if ($has_extras) {
                try {
                    // Set fragment refresh context
                    self::$fragment_refresh_active = true;

                    // Apply pricing without clearing cache (more efficient)
                    self::handle_cart_pricing(WC()->cart);

                    // Generate mini cart content efficiently
                    ob_start();
                    woocommerce_mini_cart();
                    $mini_cart = ob_get_clean();

                    // Reset fragment refresh context
                    self::$fragment_refresh_active = false;

                    if ($mini_cart) {
                        $fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>';

                        // Update cart count
                        $fragments['span.cart-count'] = '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
                    }
                } catch (Exception $e) {
                    self::$fragment_refresh_active = false;
                }
            }
        }

        return $fragments;
    }

    /**
     * Generate a unique hash for product extras
     */
    private static function generate_extras_hash($product_extras) {
        if (empty($product_extras)) {
            // Normalize empty extras to a stable state that includes defaults
            $product_extras = array();
        }

        // Explicitly normalize trade-in and deposit options for hashing
        $trade_in = isset($product_extras['trade_in']) ? strtolower($product_extras['trade_in']) : 'no';
        $deposit = isset($product_extras['awcdp_deposit_option']) ? strtolower($product_extras['awcdp_deposit_option']) : 'no';
        $product_extras['trade_in'] = ($trade_in === 'yes') ? 'yes' : 'no';
        $product_extras['awcdp_deposit_option'] = ($deposit === 'yes') ? 'yes' : 'no';

        // Normalize selected products to a canonical minimal structure for stable hashing
        if (isset($product_extras['selected_products']) && is_array($product_extras['selected_products'])) {
            $normalized = array();
            foreach ($product_extras['selected_products'] as $entry) {
                $pid = isset($entry['productId']) ? intval($entry['productId']) : (isset($entry['id']) ? intval($entry['id']) : 0);
                $vid = isset($entry['variationId']) ? intval($entry['variationId']) : 0;
                $qty = isset($entry['quantity']) ? intval($entry['quantity']) : 1;
                $ptype = isset($entry['productType']) ? (string)$entry['productType'] : '';
                $normalized[] = array(
                    'productId' => $pid,
                    'variationId' => $vid,
                    'quantity' => $qty,
                    'productType' => $ptype,
                );
            }
            // Sort by productId, then variationId to avoid order-based differences
            usort($normalized, function($a, $b) {
                if ($a['productId'] === $b['productId']) {
                    return $a['variationId'] <=> $b['variationId'];
                }
                return $a['productId'] <=> $b['productId'];
            });
            $product_extras['selected_products_normalized'] = $normalized;
            $product_extras['selected_products_count'] = count($normalized);
            // Remove verbose keys from hash input to avoid cosmetic differences causing new keys
            unset($product_extras['selected_products']);
        }

        // Sort top-level keys to ensure stable hashing regardless of insertion order
        if (is_array($product_extras)) {
            ksort($product_extras);
        }

        // Create a hash based on the normalized extras data
        $extras_string = json_encode($product_extras);
        return md5($extras_string);
    }

    /**
     * Find existing cart item with the same product and extras
     * Fixed to properly distinguish between different trade-in/deposit combinations
     */
    private static function find_existing_cart_item_with_extras($product_id, $variation_id, $product_extras, $options = null) {
        if (!WC()->cart) {
            return false;
        }

        // Get options from parameter if provided, otherwise from request
        if ($options === null) {
            $options = self::get_trade_deposit_options_from_request();
        }

        // Compute normalized hash for new item using combined extras + options from request
        $new_hash_input = $product_extras;
        $new_hash_input['trade_in'] = $options['trade_in'];
        $new_hash_input['awcdp_deposit_option'] = $options['awcdp_deposit_option'];
        $new_extras_hash = self::generate_extras_hash($new_hash_input);

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            // Check if product ID matches
            if ($cart_item['product_id'] != $product_id) {
                continue;
            }

            // Check if variation ID matches (if applicable)
            if ($variation_id && (!isset($cart_item['variation_id']) || $cart_item['variation_id'] != $variation_id)) {
                continue;
            }

            // Normalize existing extras and incorporate top-level options for reliable comparison
            $existing_extras = isset($cart_item['product_extras']) ? $cart_item['product_extras'] : array();

            // CRITICAL FIX: Use top-level trade_in and awcdp_deposit_option from cart item
            // This ensures we compare the actual stored values, not defaults
            $existing_trade = isset($cart_item['trade_in']) ? strtolower($cart_item['trade_in']) : 'no';
            $existing_extras['trade_in'] = ($existing_trade === 'yes') ? 'yes' : 'no';

            // Use top-level awcdp_deposit_option if present, fallback to legacy deposit_option
            if (isset($cart_item['awcdp_deposit_option'])) {
                $existing_deposit = strtolower($cart_item['awcdp_deposit_option']) === 'yes' ? 'yes' : 'no';
            } elseif (isset($cart_item['deposit_option'])) {
                // Legacy mapping: 'deposit' means yes, anything else treated as no
                $existing_deposit = ($cart_item['deposit_option'] === 'deposit') ? 'yes' : 'no';
            } else {
                $existing_deposit = 'no';
            }
            $existing_extras['awcdp_deposit_option'] = $existing_deposit;

            $existing_extras_hash = self::generate_extras_hash($existing_extras);

            // If hashes match, the extras are identical - return TRUE so we merge quantities
            if ($new_extras_hash === $existing_extras_hash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear price cache for removed cart item
     */
    public static function clear_price_cache_for_item($cart_item_key, $cart) {
        if (WC()->session) {
            $price_cache = WC()->session->get('price_cache', array());

            // Remove all cache entries that start with this cart item key
            foreach ($price_cache as $cache_key => $price) {
                if (strpos($cache_key, $cart_item_key) === 0) {
                    unset($price_cache[$cache_key]);
                }
            }

            WC()->session->set('price_cache', $price_cache);
        }
    }

    /**
     * Update cart item extras quantity when cart item quantity changes
     */
    public static function update_cart_item_extras_quantity($cart_item_key, $quantity, $old_quantity, $cart) {
        // Get the actual cart item data from the cart
        $cart_item = WC()->cart->get_cart_item($cart_item_key);

        // Only process if the cart item has product extras
        if (!$cart_item || !isset($cart_item['product_extras']) || empty($cart_item['product_extras']['selected_products'])) {
            return;
        }

        // Calculate the quantity multiplier (how much the quantity increased/decreased)
        $quantity_multiplier = $quantity / max(1, $old_quantity);

        // Update each extra product's quantity to maintain the same ratio
        foreach ($cart_item['product_extras']['selected_products'] as $index => $extra_product) {
            if (isset($extra_product['quantity'])) {
                $original_extra_quantity = intval($extra_product['quantity']);
                $new_extra_quantity = max(1, round($original_extra_quantity * $quantity_multiplier));

                // Update the extra product quantity in the cart
                WC()->cart->cart_contents[$cart_item_key]['product_extras']['selected_products'][$index]['quantity'] = $new_extra_quantity;
            }
        }

        // Clear price cache to force recalculation
        if (WC()->session) {
            $price_cache = WC()->session->get('price_cache', array());

            // Remove all cache entries that start with this cart item key
            foreach ($price_cache as $cache_key => $price) {
                if (strpos($cache_key, $cart_item_key) === 0) {
                    unset($price_cache[$cache_key]);
                }
            }

            WC()->session->set('price_cache', $price_cache);
        }
    }

    /**
     * Clear all price cache entries
     */
    public static function clear_all_price_cache() {
        if (WC()->session) {
            WC()->session->set('price_cache', array());
        }

        // Also clear any WooCommerce product price caches
        if (function_exists('wc_delete_product_transients')) {
            // Clear product transients for all products in cart
            if (WC()->cart && !WC()->cart->is_empty()) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    if (isset($cart_item['product_id'])) {
                        wc_delete_product_transients($cart_item['product_id']);
                        if (isset($cart_item['variation_id']) && $cart_item['variation_id']) {
                            wc_delete_product_transients($cart_item['variation_id']);
                        }
                    }
                }
            }
        }
    }


    /**
     * Refresh product extras pricing to ensure quantities and prices are properly calculated
     */
    public static function refresh_product_extras_pricing() {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (!isset($cart_item['product_extras']) || empty($cart_item['product_extras']['selected_products'])) {
                continue;
            }

            $product_extras = $cart_item['product_extras'];

            // Update pricing for selected products
            if (isset($product_extras['selected_products']) && !empty($product_extras['selected_products'])) {
                foreach ($product_extras['selected_products'] as $index => $product_data) {
                    $product_id = isset($product_data['variationId']) ? $product_data['variationId'] : $product_data['productId'];
                    $product = wc_get_product($product_id);

                    if ($product) {
                        // Get fresh price from product and update the stored data
                        $fresh_price = floatval($product->get_price());
                        WC()->cart->cart_contents[$cart_item_key]['product_extras']['selected_products'][$index]['price'] = $fresh_price;

                        // Ensure quantity is properly set
                        if (!isset($product_data['quantity']) || $product_data['quantity'] < 1) {
                            WC()->cart->cart_contents[$cart_item_key]['product_extras']['selected_products'][$index]['quantity'] = 1;
                        }
                    }
                }
            }
        }
    }

    /**
     * Debounced calculate totals to prevent excessive calls
     */
    public static function debounced_calculate_totals() {
        static $last_calculate_time = 0;
        static $calculate_scheduled = false;

        $current_time = microtime(true);

        // If we just calculated totals within the last 200ms, schedule a delayed calculation
        if ($current_time - $last_calculate_time < 0.2) {
            if (!$calculate_scheduled) {
                $calculate_scheduled = true;
                wp_schedule_single_event(time() + 1, 'delayed_calculate_totals');
            }
            return;
        }

        $last_calculate_time = $current_time;
        $calculate_scheduled = false;

        if (WC()->cart && !WC()->cart->totals_calculated) {
            WC()->cart->calculate_totals();
        }
    }



    /**
     * Register AJAX handlers for add to cart functionality
     */
    public static function register_ajax_handlers()
    {
        // Prevent duplicate registrations
        if (self::$ajax_handlers_registered) {
            return;
        }

        // Remove WooCommerce's default handler and add our custom one
        // This ensures we can handle product extras properly
        remove_action('wc_ajax_add_to_cart', array('WC_AJAX', 'add_to_cart'));
        add_action('wc_ajax_add_to_cart', [self::class, 'wc_ajax_add_to_cart_handler'], 1);

        // Add AJAX handler for AJAX refresh context
        add_action('wc_ajax_get_refreshed_fragments', function() {
            self::ensure_pricing_for_context('ajax_refresh');
        }, 5);

        self::$ajax_handlers_registered = true;
    }

    /**
     * Custom AJAX add to cart handler that supports product extras
     * Based on WooCommerce's default handler but with product extras support
     */
    public static function wc_ajax_add_to_cart_handler()
    {
        ob_start();

        // Check if WooCommerce is properly loaded
        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(array('error' => true, 'message' => 'WooCommerce not available'));
            return;
        }

        // Check for required product_id (same as WooCommerce's default handler)
        if (!isset($_POST['product_id'])) {
            wp_send_json_error(array('error' => true, 'message' => 'Product ID required'));
            return;
        }

        // Enhanced duplicate request prevention for dual AJAX pattern (Woodmart + WooCommerce)
        $current_time = microtime(true);

        // Early performance optimization: Skip expensive operations if request is duplicate
        // Include timestamp to prevent blocking legitimate requests after navigation
        $quick_hash = md5(
            $_POST['product_id'] . '_' .
            ($_POST['variation_id'] ?? '') . '_' .
            ($_POST['quantity'] ?? '') . '_' .
            // Include trade-in and deposit to avoid false duplicate detection
            (isset($_POST['trade_in']) ? $_POST['trade_in'] : '') . '_' .
            (isset($_POST['awcdp_deposit_option']) ? $_POST['awcdp_deposit_option'] : '') . '_' .
            get_current_user_id() . '_' .
            floor($current_time)
        );
        $quick_transient_key = 'ajax_cart_quick_' . $quick_hash;

        if (get_transient($quick_transient_key)) {
            // For duplicate requests, return WooCommerce-compatible response with empty fragments
            wp_send_json(array(
                'fragments' => array(), // Empty fragments to prevent refresh
                'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : ''
            ));
            return;
        }

        // Set quick duplicate prevention transient for 1 second (reduced from 2 seconds)
        set_transient($quick_transient_key, $current_time, 1);

        // Create a more specific hash for deduplication
        $request_data = array(
            'product_id' => isset($_POST['product_id']) ? $_POST['product_id'] : (isset($_POST['add-to-cart']) ? $_POST['add-to-cart'] : ''),
            'variation_id' => isset($_POST['variation_id']) ? $_POST['variation_id'] : '',
            'quantity' => isset($_POST['quantity']) ? $_POST['quantity'] : '',
            'action' => isset($_POST['action']) ? $_POST['action'] : '',
            'user_id' => get_current_user_id(),
            'session_id' => WC()->session ? WC()->session->get_customer_id() : '',
            // Align with front-end: include trade-in and deposit option
            'trade_in' => isset($_POST['trade_in']) ? sanitize_text_field($_POST['trade_in']) : '',
            'awcdp_deposit_option' => isset($_POST['awcdp_deposit_option']) ? sanitize_text_field($_POST['awcdp_deposit_option']) : ''
        );

        // Include product extras in hash if present
        if (isset($_POST['extra_info_ids']) && is_array($_POST['extra_info_ids'])) {
            $request_data['extras'] = $_POST['extra_info_ids'];
        }

        // Include extra products and their variations
        if (isset($_POST['extra_product_ids']) && is_array($_POST['extra_product_ids'])) {
            $request_data['extra_products'] = $_POST['extra_product_ids'];

            // Include extra product quantities and variations
            $extra_product_data = array();
            foreach ($_POST['extra_product_ids'] as $product_id) {
                $qty = isset($_POST['extra_product_qty'][$product_id]) ? $_POST['extra_product_qty'][$product_id] : '1';
                $variation_id = isset($_POST['extra_product_variation_id'][$product_id]) ? $_POST['extra_product_variation_id'][$product_id] : '';
                $extra_product_data[] = $product_id . ':' . $qty . ':' . $variation_id;
            }
            if (!empty($extra_product_data)) {
                sort($extra_product_data);
                $request_data['extra_product_data'] = implode('|', $extra_product_data);
            }
        }

        // // Include child products if present
        // if (isset($_POST['26155_26156_child_product']) && is_array($_POST['26155_26156_child_product'])) {
        //     $request_data['child_products'] = $_POST['26155_26156_child_product'];
        // }

        $request_hash = md5(serialize($request_data));
        $transient_key = 'ajax_cart_' . $request_hash;

        // Check if this exact request was processed recently (within 1 second - reduced from 3 seconds)
        if (get_transient($transient_key)) {
            // For duplicate requests, return WooCommerce-compatible response with empty fragments
            wp_send_json(array(
                'fragments' => array(), // Empty fragments to prevent refresh
                'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : ''
            ));
            return;
        }

        // Mark this request as processed with 1-second transient (reduced from 3 seconds)
        set_transient($transient_key, $current_time, 1);

        // Get and validate data
        $product_id = absint($_POST['product_id']);
        $quantity = absint($_POST['quantity']);
        $variation_id = absint($_POST['variation_id']);

        if (!$product_id || !$quantity) {
            wp_send_json_error(array('error' => true, 'message' => 'Invalid product data'));
            return;
        }

        // Early validation with caching for better performance
        static $product_cache = array();
        $cache_key = $variation_id ?: $product_id;

        if (!isset($product_cache[$cache_key])) {
            $product = wc_get_product($cache_key);
            $product_cache[$cache_key] = $product;
        } else {
            $product = $product_cache[$cache_key];
        }

        if (!$product || !$product->is_purchasable()) {
            wp_send_json_error(array('error' => true, 'message' => 'Product is not available for purchase'));
            return;
        }

        // Quick stock check before processing
        if (!$product->is_in_stock()) {
            wp_send_json_error(array('error' => true, 'message' => 'Product is out of stock'));
            return;
        }

        // Only check stock quantity if stock management is enabled
        if ($product->managing_stock() && $product->get_stock_quantity() !== null && $product->get_stock_quantity() < $quantity) {
            wp_send_json_error(array('error' => true, 'message' => 'Insufficient stock available'));
            return;
        }

        // Get variation data efficiently
        $variation = array();
        if ($variation_id) {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'attribute_') === 0) {
                    $variation[$key] = sanitize_text_field($value);
                }
            }
        }

        // Get product extras (simplified)
        $cart_item_data = array();
        $product_extras = self::get_product_extras_from_request();
        if (!empty($product_extras)) {
            $cart_item_data['product_extras'] = $product_extras;
        }

        try {
            // Check cart availability before adding
            if (!WC()->cart) {
                wp_send_json_error(array('error' => true, 'message' => 'Cart not available'));
                return;
            }

            // Add to cart using WooCommerce's optimized method
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);

            if ($cart_item_key) {
                do_action('woocommerce_ajax_added_to_cart', $product_id);

                if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
                    wc_add_to_cart_message(array($product_id => $quantity), true);
                }

                $options = self::get_trade_deposit_options_from_request();
                $has_extras = !empty($product_extras);
                $should_refresh = $has_extras || ($options['awcdp_deposit_option'] === 'yes') || ($options['trade_in'] !== '');

                if ($should_refresh) {
                    self::ensure_pricing_for_context('ajax_refresh');
                    $fragments = apply_filters('woocommerce_add_to_cart_fragments', array());
                    ob_start();
                    woocommerce_mini_cart();
                    $mini_cart_content = ob_get_clean();
                    $fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . $mini_cart_content . '</div>';
                    wp_send_json(array(
                        'fragments' => $fragments,
                        'cart_hash' => WC()->cart->get_cart_hash()
                    ));
                    return;
                } else {
                    $data = array(
                        'fragments' => array(),
                        'cart_hash' => WC()->cart->get_cart_hash(),
                    );
                    wp_send_json($data);
                    return;
                }
            } else {
                // If custom validator handled the add and signaled via session, treat as success
                $handled_flag = (function_exists('WC') && WC()->session) ? WC()->session->get('senheng_handled_add') : null;
                if ($handled_flag) {
                    if (function_exists('WC') && WC()->session) {
                        WC()->session->set('senheng_handled_add', null);
                    }
                    $fragments = apply_filters('woocommerce_add_to_cart_fragments', array());
                    ob_start();
                    woocommerce_mini_cart();
                    $mini_cart_content = ob_get_clean();
                    $fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . $mini_cart_content . '</div>';
                    wp_send_json_success(array(
                        'fragments' => $fragments,
                        'cart_hash' => WC()->cart->get_cart_hash()
                    ));
                    return;
                }
                // Removed cart-count heuristic. Rely on explicit session flag only.

                // Check for WooCommerce notices/errors
                $notices = wc_get_notices('error');
                $error_message = 'Failed to add product to cart';

                if (!empty($notices)) {
                    $error_messages = array();
                    foreach ($notices as $notice) {
                        $error_messages[] = $notice['notice'];
                    }
                    $error_message = implode(', ', $error_messages);
                    wc_clear_notices(); // Clear notices after logging
                }

                // Check stock status
                $product = wc_get_product($variation_id ?: $product_id);
                if ($product && !$product->is_in_stock()) {
                    $error_message = 'Product is out of stock';
                }

                wp_send_json_error(array(
                    'error' => true,
                    'message' => $error_message,
                    'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
                ));
            }
        } catch (Exception $e) {
            // Handle any unexpected errors
            wp_send_json_error(array(
                'error' => true,
                'message' => 'An error occurred while adding to cart'
            ));
        }
    }

    /**
     * Legacy AJAX add to cart handler for backward compatibility
     * Uses admin-ajax.php (slower but more compatible)
     */
    public static function ajax_add_to_cart_handler()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'wc_add_to_cart_nonce')) {
            wp_die('Security check failed');
        }

        $product_id = absint($_POST['product_id']);
        $quantity = absint($_POST['quantity']);
        $variation_id = absint($_POST['variation_id']);
        $variation = array();

        // Get variation data
        if ($variation_id) {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'attribute_') === 0) {
                    $variation[$key] = sanitize_text_field($value);
                }
            }
        }

        // Get product extras
        $product_extras = self::get_product_extras_from_request();

        // Add cart item data for product extras
        $cart_item_data = array();
        if (!empty($product_extras)) {
            $cart_item_data['product_extras'] = $product_extras;
        }

        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);

        if ($cart_item_key) {
            // Return success response with cart fragments
            $data = array(
                'error' => false,
                'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id),
                'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array())
            );
        } else {
            // Return error response
            $data = array(
                'error' => true,
                'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
            );
        }

        wp_send_json($data);
    }

    /**
     * Filter cart item price display to show only base price (not including extras)
     */
    public static function filter_cart_item_price_display($price_html, $cart_item, $cart_item_key) {
        // Check if this cart item has deposit pricing
        if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
            // Non-cart/checkout (mini-cart): always show deposit amount when selected
            if (!is_cart() && !is_checkout()) {
                return wc_price($cart_item['deposit_amount']);
            }
            // Cart/checkout pages: show deposit amount
            return wc_price($cart_item['deposit_amount']);
        }

        // Only filter if this cart item has product extras
        if (!isset($cart_item['product_extras']) || empty($cart_item['product_extras']['selected_products'])) {
            return $price_html;
        }

        // Get the base product price (without extras)
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];

        if ($variation_id) {
            $product = wc_get_product($variation_id);
        } else {
            $product = wc_get_product($product_id);
        }

        if (!$product) {
            return $price_html;
        }

        // Check if force_regular_price is set (for sale quantity pricing)
        if (!empty($cart_item['force_regular_price'])) {
            $base_price = $product->get_regular_price();
        } else {
            $base_price = $product->get_price();
        }

        // Format the base price for display
        $base_price_html = wc_price($base_price);

        return $base_price_html;
    }

    /**
     * Filter cart item subtotal display to show only base price subtotal (not including extras)
     */
    public static function filter_cart_item_subtotal_display($subtotal_html, $cart_item, $cart_item_key) {
        // Mini cart: include extras in subtotal and honor deposit selection
        if (!is_cart() && !is_checkout()) {
            $quantity = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;

            // Determine deposit subtotal when selected (supports AWCDP fallback)
            $has_deposit = false;
            $deposit_unit = 0.0;
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposit = true;
                $deposit_unit = floatval($cart_item['deposit_amount']);
            } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable'])
                && $cart_item['awcdp_deposit']['enable'] == 1
                && isset($cart_item['awcdp_deposit']['deposit'])) {
                $has_deposit = true;
                $deposit_unit = floatval($cart_item['awcdp_deposit']['deposit']);
            }

            // Compute base product unit price when full payment
            $base_unit = 0.0;
            if (!$has_deposit) {
                $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
                $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
                $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                if ($product) {
                    $base_unit = !empty($cart_item['force_regular_price']) ? floatval($product->get_regular_price()) : floatval($product->get_price());
                }
            }

            // Extras total for product-type fields
            $extras_unit_total = (class_exists('CartController') ? \CartController::calculate_product_extras_total($cart_item) : 0.0);

            // Subtotal = (deposit or base)*qty + (extras_total*qty)
            $product_part = $has_deposit ? ($deposit_unit * $quantity) : ($base_unit * $quantity);
            $extras_part = $extras_unit_total * $quantity;
            return wc_price($product_part + $extras_part);
        }

        // Cart/checkout contexts: keep existing behavior (no extras blended here)
        // Fall back to original subtotal_html when not in mini cart
        return $subtotal_html;
    }

    /**
     * Optimized immediate cart display - only runs when actually needed
     */
    public static function ensure_immediate_cart_display()
    {
        // Only run on frontend and if we have product extras
        if (is_admin() || wp_doing_ajax() || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        // Quick check for product extras - exit early if none
        $has_extras = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!empty($cart_item['product_extras'])) {
                $has_extras = true;
                break;
            }
        }

        if (!$has_extras) {
            return;
        }

        // Apply pricing and generate fragments efficiently
        self::ensure_pricing_for_context('immediate_display');
        $fragments = apply_filters('woocommerce_add_to_cart_fragments', array());

        if (!empty($fragments)) {
            ?>
            <script type="text/javascript">
            window.wc_cart_fragments_ready = true;
            window.wc_cart_fragments = <?php echo json_encode($fragments); ?>;

            (function() {
                function initializeFragments() {
                    if (typeof jQuery === 'undefined') {
                        setTimeout(initializeFragments, 50);
                        return;
                    }

                    jQuery(document).ready(function($) {
                        if (window.wc_cart_fragments && Object.keys(window.wc_cart_fragments).length > 0) {
                            $.each(window.wc_cart_fragments, function(key, value) {
                                if ($(key).length) {
                                    $(key).replaceWith(value);
                                }
                            });

                            // Trigger events efficiently
                            if (window.fragmentRefreshControl) {
                                window.fragmentRefreshControl.reset();
                            }
                            $(document.body).trigger('wc_fragments_loaded');
                        }
                    });
                }
                initializeFragments();
            })();
            </script>
            <?php
        }
    }

    public static function remove_default_variation_display($item_data, $cart_item) {
        // Only remove default variation data when our custom product extras are present
        $has_extras = isset($cart_item['product_extras']) && !empty($cart_item['product_extras']);
        if ($has_extras && isset($cart_item['variation']) && !empty($cart_item['variation'])) {
            $item_data = array();
        }
        
        return $item_data;
    }


    /**
     * Add variation data to mini cart items
     *
     * @param array $item_data The existing item data
     * @param array $cart_item The cart item
     * @return array Modified item data with variation attributes
     */
    public static function add_variation_data_to_mini_cart($item_data, $cart_item)
    {
        // // Do not display variation attributes for items with our product extras
        // if (isset($cart_item['product_extras']) && !empty($cart_item['product_extras'])) {
        //     return $item_data;
        // }

        // For any variation product, add a single combined attribute line
        if (isset($cart_item['variation']) && !empty($cart_item['variation'])) {
            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if ($product && $product->is_type('variation')) {
                $variation_data = $cart_item['variation'];
                $variation_values = array();

                foreach ($variation_data as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }

                    $attribute_key = str_replace('attribute_', '', $key);
                    $formatted_value = $value;

                    if (taxonomy_exists($attribute_key)) {
                        $term = get_term_by('slug', $value, $attribute_key);
                        if ($term && !is_wp_error($term)) {
                            $formatted_value = $term->name;
                        }
                    } else {
                        $formatted_value = apply_filters('woocommerce_variation_option_name', $value, null, $attribute_key, $product);
                    }

                    $variation_values[] = $formatted_value;
                }

                if (!empty($variation_values)) {
                    $item_data[] = array(
                        'name'  => 'variation',
                        'value' => implode(', ', $variation_values),
                        'display' => ''
                    );
                }
            }
        }

        return $item_data;
    }

    /**
     * Get formatted variation data for extra products in mini cart
     *
     * @param array $extra_product_data The extra product data
     * @return string HTML formatted variation data
     */
    private static function get_formatted_variation_data_for_mini_cart($extra_product_data)
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

        $variation_values = array();

        foreach ($variation_data as $name => $value) {
            if ('' === $value) {
                continue;
            }

            // Remove 'attribute_' prefix if present
            $attribute_key = str_replace('attribute_', '', $name);

            // Format the value
            $formatted_value = $value;

            // If it's a taxonomy attribute, get the term name
            if (taxonomy_exists($attribute_key)) {
                $term = get_term_by('slug', $value, $attribute_key);
                if ($term && !is_wp_error($term)) {
                    $formatted_value = $term->name;
                }
            }

            $variation_values[] = $formatted_value;
        }

        if (empty($variation_values)) {
            return '';
        }

        // Format as HTML with just the values, comma-separated
        $html = '<div class="cart-variation-data mini-cart-variation">';
        $html .= '<span class="variation-values">' . wp_kses_post(implode(', ', $variation_values)) . '</span>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Display trade-in information universally (cart, checkout, mini cart)
     */
    public static function display_trade_in_info_universal($item_data, $cart_item)
    {
        // Avoid duplicating on cart and checkout pages where we render inside item name
        if (function_exists('is_cart') && function_exists('is_checkout')) {
            if (is_cart() || is_checkout()) {
                return $item_data;
            }
        }
        // Gate labels by deposit eligibility on the product
        $base_product_id = isset($cart_item['product_id']) ? (int)$cart_item['product_id'] : 0;
        if (!self::is_deposit_eligible_for_product_id($base_product_id)) {
            return $item_data;
        }
        // Display trade-in information using top-level cart item value
        if (isset($cart_item['trade_in']) && $cart_item['trade_in'] !== '') {
            $trade_in_value = ($cart_item['trade_in'] === 'yes') ? 'Yes' : 'No';

            $item_data[] = array(
                'key'   => 'Trade In',
                'value' => $trade_in_value,
            );
        }

        // Add Payment option (Deposit/Full) to mini-cart item meta
        $payment_label = '';
        // Prefer our normalized top-level flag from add-to-cart flow
        if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
            $payment_label = 'Deposit';
        } elseif (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
            $payment_label = 'Deposit';
        } elseif (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
            $payment_label = 'Deposit';
        }

        if ($payment_label !== '') {
            // Optionally append amount when deposit is selected and present
            if ($payment_label === 'Deposit' && isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $item_data[] = array(
                    'key'   => 'Payment',
                    'value' => $payment_label . ' (' . wc_price($cart_item['deposit_amount']) . ')',
                );
            } else {
                $item_data[] = array(
                    'key'   => 'Payment',
                    'value' => $payment_label,
                );
            }
        }

        return $item_data;
    }

    /**
     * Remove AWCDP deposit variation entries from mini-cart item data
     * This targets the two lines that render with classes
     *  - variation-Depositamount
     *  - variation-Futurepayments
     * and keeps cart/checkout pages unchanged.
     */
    public static function remove_deposit_item_data_from_mini_cart($item_data, $cart_item)
    {
        if ((function_exists('is_cart') && is_cart()) || (function_exists('is_checkout') && is_checkout())) {
            return $item_data;
        }
        if (empty($item_data) || !is_array($item_data)) {
            return $item_data;
        }
        $is_deposit = false;
        if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
            $is_deposit = true;
        } elseif (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
            $is_deposit = true;
        } elseif (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
            $is_deposit = true;
        }
        if (!$is_deposit) {
            return $item_data;
        }
        $filtered = array();
        foreach ($item_data as $entry) {
            $value_key = isset($entry['value']) ? $entry['value'] : '';
            $name = isset($entry['name']) ? $entry['name'] : '';
            $name_compact = strtolower(preg_replace('/\s+/', '', $name));
            $is_awcdp_deposit_line = (
                $value_key === 'wc_deposit_amount' ||
                $value_key === 'wc_deposit_future_payments_amount' ||
                $name_compact === 'depositamount' ||
                $name_compact === 'futurepayments'
            );
            if ($is_awcdp_deposit_line) {
                continue;
            }
            $filtered[] = $entry;
        }
        return $filtered;
    }

    /**
     * Replace the default mini-cart subtotal output with a deposit-aware subtotal
     * Ensures side cart shows deposit-based subtotal when deposit is selected.
     */
    public static function override_mini_cart_subtotal_output()
    {
        // Remove WooCommerce default subtotal output in the mini-cart footer
        remove_action('woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10);
        // Add our customized subtotal output
        add_action('woocommerce_widget_shopping_cart_total', [self::class, 'widget_cart_subtotal_deposit_aware'], 10);
    }

    /**
     * Echo deposit-aware mini-cart subtotal.
     * - If any items have a deposit selected, subtotal sums deposit amounts for those items.
     * - Non-deposit items use their current product price.
     * - Only affects the mini-cart footer subtotal; cart/checkout totals remain handled separately.
     */
    public static function widget_cart_subtotal_deposit_aware()
    {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            echo '<strong>' . esc_html__('Subtotal:', 'woocommerce') . '</strong> ' . wc_price(0);
            return;
        }

        $has_deposits = false;
        $has_extras = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposits = true;
            }
            if (isset($cart_item['product_extras']) && !empty($cart_item['product_extras']['selected_products'])) {
                $has_extras = true;
            }
        }

        // When we have deposits or extras, compute subtotal explicitly to be consistent

        // Compute deposit-based subtotal across cart items
        $deposit_subtotal = 0.0;
        $extras_total = 0.0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;

            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $deposit_subtotal += floatval($cart_item['deposit_amount']) * $qty;
            } else {
                // Use current product price for non-deposit items
                $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                $deposit_subtotal += $price * $qty;
            }

            // Add product extras total per parent quantity
            $extras_total += (class_exists('CartController') ? \CartController::calculate_product_extras_total($cart_item) : 0.0) * $qty;
        }

        echo '<strong>' . esc_html__('Subtotal:', 'woocommerce') . '</strong> ' . wc_price($deposit_subtotal + $extras_total);
    }

    /**
     * Override Woodmart header cart subtotal fragment (wd-cart-subtotal) with deposit-aware total.
     * - When any cart item has a deposit selected, sum deposit amounts for those items.
     * - Non-deposit items contribute their regular product price.
     * - Updates both selectors used by Woodmart fragments: with and without the `_wd` suffix.
     */
    public static function override_header_cart_subtotal_fragment($fragments)
    {
        if (!function_exists('WC') || !WC()->cart) {
            return $fragments;
        }

        $cart = WC()->cart;
        if ($cart->is_empty()) {
            // Ensure empty cart still renders a zeroed subtotal span
            $old_classes = function_exists('woodmart_get_old_classes') ? woodmart_get_old_classes(' woodmart-cart-subtotal') : '';
            $html = '<span class="wd-cart-subtotal' . $old_classes . '">' . wc_price(0) . '</span>';
            $fragments['span.wd-cart-subtotal_wd'] = $html;
            $fragments['span.wd-cart-subtotal'] = $html;
            return $fragments;
        }

        // Detect if deposits or extras are present
        $has_deposits = false;
        $has_extras = false;
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $has_deposits = true;
            }
            if (isset($cart_item['product_extras']) && !empty($cart_item['product_extras']['selected_products'])) {
                $has_extras = true;
            }
        }

        // If neither deposits nor extras exist, let the theme’s default subtotal stand
        if (!$has_deposits && !$has_extras) {
            return $fragments;
        }

        // Compute subtotal consistent with mini-cart footer: deposit/base + extras
        $deposit_subtotal = 0.0;
        $extras_total = 0.0;
        foreach ($cart->get_cart() as $cart_item) {
            $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;

            if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount'])) {
                $deposit_subtotal += floatval($cart_item['deposit_amount']) * $qty;
            } else {
                $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                $price = ($product && method_exists($product, 'get_price')) ? floatval($product->get_price()) : 0.0;
                $deposit_subtotal += $price * $qty;
            }

            $extras_total += (class_exists('CartController') ? \CartController::calculate_product_extras_total($cart_item) : 0.0) * $qty;
        }

        $old_classes = function_exists('woodmart_get_old_classes') ? woodmart_get_old_classes(' woodmart-cart-subtotal') : '';
        $html = '<span class="wd-cart-subtotal' . $old_classes . '">' . wc_price($deposit_subtotal + $extras_total) . '</span>';

        // Update both fragment selectors for compatibility
        $fragments['span.wd-cart-subtotal_wd'] = $html;
        $fragments['span.wd-cart-subtotal'] = $html;

        return $fragments;
    }

    /**
     * Initialize side cart mixed product validation
     * Provides user-friendly feedback for mixed product restrictions
     */
    public static function init_side_cart_mixed_product_validation()
    {
        // Only initialize on frontend and if WooCommerce is available
        if (is_admin() || !function_exists('WC')) {
            return;
        }

        // Enqueue CSS for mixed product validation styling
        wp_enqueue_style(
            'sh-side-cart-mixed-products',
            plugin_dir_url(__FILE__) . '../../assets/css/side-cart.css',
            array(),
            '1.0.0'
        );

        // Enqueue JavaScript for mixed product validation
        wp_enqueue_script(
            'sh-side-cart-mixed-products-js',
            plugin_dir_url(__FILE__) . '../../assets/js/side-cart-mixed-product.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }

    /**
     * Get product type information for validation
     */
    public static function get_cart_product_types()
    {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return array('virtual' => 0, 'physical' => 0, 'mixed' => false);
        }

        $virtual_count = 0;
        $physical_count = 0;

        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['data']->is_virtual()) {
                $virtual_count++;
            } else {
                $physical_count++;
            }
        }

        return array(
            'virtual' => $virtual_count,
            'physical' => $physical_count,
            'mixed' => ($virtual_count > 0 && $physical_count > 0),
            'total' => $virtual_count + $physical_count
        );
    }

    /**
     * Initialize deposit functionality hooks
     */
    public static function init_deposit_hooks()
    {
        // Add deposit container before add to cart button
        add_action('woocommerce_before_add_to_cart_button', [self::class, 'display_deposit_container']);

        // Enqueue deposit scripts
        

        // Handle deposit form submission
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'validate_deposit_selection'], 10, 5);

        // Add deposit toggle UI to mini cart contents
        add_action('woocommerce_mini_cart_contents', [self::class, 'display_mini_cart_deposit_options'], 12);
    }

    /**
     * Display deposit container on product page
     */
    public static function display_deposit_container()
    {
        global $product;

        if (!$product || !self::is_deposit_enabled_for_product($product)) {
            return;
        }

        $deposit_type = get_post_meta($product->get_id(), '_deposit_type', true);
        $deposit_amount = get_post_meta($product->get_id(), '_deposit_amount', true);

        if (!$deposit_type || !$deposit_amount) {
            return;
        }

        $product_price = $product->get_price();

        if ($deposit_type === 'fixed') {
            $deposit_value = floatval($deposit_amount);
        } else {
            $deposit_value = ($product_price * floatval($deposit_amount)) / 100;
        }

        $remaining_amount = $product_price - $deposit_value;

        ?>
        <div class="senheng-deposit-container" style="margin: 20px 0;">
            <h4><?php _e('Payment Options', 'senheng-core'); ?></h4>
            <div class="deposit-options">
                <label class="deposit-option">
                    <input type="radio" name="deposit_option" value="full" checked>
                    <span><?php printf(__('Pay Full Amount: %s', 'senheng-core'), wc_price($product_price)); ?></span>
                </label>
                <label class="deposit-option">
                    <input type="radio" name="deposit_option" value="deposit">
                    <span><?php printf(__('Pay Deposit: %s', 'senheng-core'), wc_price($deposit_value)); ?></span>
                    <small class="deposit-details" style="display: none;">
                        <?php printf(__('Remaining balance: %s', 'senheng-core'), wc_price($remaining_amount)); ?>
                    </small>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Display deposit/full payment toggle inside the mini cart for eligible items.
     */
    public static function display_mini_cart_deposit_options()
    {
        if (!function_exists('WC') || !WC()->cart) {
            return;
        }

        $cart = WC()->cart;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Skip extras and non-main products
            if (!empty($cart_item['is_extra_product'])) {
                continue;
            }

            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if (!$product || !method_exists($product, 'get_id')) {
                continue;
            }

            $product_id = $product->get_id();

            // Determine if product supports deposit
            $supports_deposit = false;
            $deposit_type = get_post_meta($product_id, '_deposit_type', true);
            $deposit_amount_meta = get_post_meta($product_id, '_deposit_amount', true);
            if (!empty($deposit_type) && $deposit_amount_meta !== '') {
                $supports_deposit = true;
            } else {
                // Fallback to AWCDP meta
                $aw_type = get_post_meta($product_id, '_awcdp_deposit_type', true);
                $aw_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
                if (!empty($aw_type) && $aw_amount !== '') {
                    $supports_deposit = true;
                    $deposit_type = $aw_type === 'percent' ? 'percentage' : $aw_type; // normalize
                    $deposit_amount_meta = $aw_amount;
                }
            }

            if (!$supports_deposit) {
                continue;
            }

            $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;
            $base_price = method_exists($product, 'get_price') ? floatval($product->get_price()) : 0.0;
            $current_selection = 'full';
            if ((isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') || (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') || (isset($cart_item['deposit_amount']) && floatval($cart_item['deposit_amount']) > 0)) {
                $current_selection = 'deposit';
            }

            // Compute deposit value
            $deposit_value = 0.0;
            if ($deposit_type === 'percentage') {
                $percentage = floatval($deposit_amount_meta);
                $deposit_value = ($base_price * $percentage / 100.0);
            } else {
                $deposit_value = floatval($deposit_amount_meta);
            }

            // Total figures for display
            $full_total = $base_price * $qty;
            $deposit_total = $deposit_value * $qty;
            $remaining_total = max(0.0, $full_total - $deposit_total);

            echo '<li class="wd-deposit-cart-item" data-parent-key="' . esc_attr($cart_item_key) . '">';
            echo '<div class="sh-deposit-mini">';
            echo '<div class="sh-deposit-title">' . esc_html__('Payment Options', 'senheng-core') . '</div>';

            echo '<div class="sh-deposit-options">';
            // Full payment radio
            $full_checked = $current_selection === 'full' ? 'checked' : '';
            echo '<label class="sh-deposit-option">';
            echo '<input type="radio" class="sh-deposit-radio" name="deposit_option_' . esc_attr($cart_item_key) . '" value="full" ' . $full_checked . ' /> ';
            echo esc_html__('Full Payment', 'senheng-core') . ': ' . wc_price($full_total);
            echo '</label>';

            // Deposit payment radio
            $dep_checked = $current_selection === 'deposit' ? 'checked' : '';
            echo '<label class="sh-deposit-option">';
            echo '<input type="radio" class="sh-deposit-radio" name="deposit_option_' . esc_attr($cart_item_key) . '" value="deposit" ' . $dep_checked . ' /> ';
            echo esc_html__('Deposit', 'senheng-core') . ': ' . wc_price($deposit_total);
            echo '</label>';

            // Remaining balance note
            echo '<div class="sh-deposit-remaining" ' . ($current_selection === 'deposit' ? '' : 'style=\"display:none\"') . '>';
            echo esc_html__('Remaining balance at or before fulfillment:', 'senheng-core') . ' ' . wc_price($remaining_total);
            echo '</div>';

            echo '</div>'; // options
            echo '</div>'; // wrapper
            echo '</li>';
        }
    }

    /**
     * Check if deposits are enabled for a product
     */
    public static function is_deposit_enabled_for_product($product)
    {
        if (!$product) {
            return false;
        }

        // Skip for certain product types
        if (in_array($product->get_type(), ['grouped', 'external'])) {
            return false;
        }

        // Robust check: honor AWCDP meta keys if present, fallback to legacy
        $product_id = $product->get_id();

        // Prefer AWCDP constant if defined
        $meta_keys = [];
        if (defined('AWCDP_DEPOSITS_META_KEY')) {
            $meta_keys[] = AWCDP_DEPOSITS_META_KEY;
        }
        // Known AWCDP keys and legacy
        $meta_keys = array_merge($meta_keys, [
            '_awcdp_deposit_enabled',
            '_awcdp_deposits_enabled',
            '_awcdp_enable_deposit',
            '_deposit_enabled',
        ]);

        $enabled = false;
        foreach ($meta_keys as $key) {
            $val = get_post_meta($product_id, $key, true);
            if ($val === 'yes' || $val === '1' || $val === 1 || $val === true) {
                $enabled = true;
                break;
            }
        }

        return $enabled;
    }

    /**
     * Check if deposits are eligible for a product (enabled AND amount > 0)
     */
    public static function is_deposit_eligible_for_product_id($product_id)
    {
        if (!$product_id) {
            return false;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        // Enabled?
        if (!self::is_deposit_enabled_for_product($product)) {
            return false;
        }

        // Has positive deposit amount?
        $deposit_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
        $deposit_type = get_post_meta($product_id, '_awcdp_deposit_type', true);

        // Normalize
        $amount = floatval($deposit_amount);
        if ($deposit_type !== 'percentage' && $deposit_type !== 'fixed') {
            // Unknown type: treat as amount check only
            $deposit_type = 'fixed';
        }

        // Any positive amount qualifies (percentage or fixed)
        return $amount > 0;
    }


    /**
     * Validate deposit selection
     */
    public static function validate_deposit_selection($passed, $product_id, $quantity, $variation_id = '', $variations = array())
    {
        // Validation is handled in CartController::add_cart_item_data
        return $passed;
    }
}
