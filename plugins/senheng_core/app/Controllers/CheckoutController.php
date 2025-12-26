<?php

class CheckoutController
{
    /**
     * Get product by ID
     * 
     * @param int $product_id Product or variation ID
     * @return WC_Product|false
     */
    private static function get_product($product_id)
    {
        if (!$product_id) {
            return false;
        }
        
        return wc_get_product((int) $product_id);
    }

    /**
     * Check if cart contains only virtual products
     * 
     * @return bool
     */
    private static function is_cart_only_virtual()
    {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!$cart_item['data']->is_virtual()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get deposit metadata for a product
     * 
     * @param int $product_id Product ID
     * @return array{amount: mixed, type: mixed}
     */
    private static function get_deposit_meta($product_id)
    {
        $product_id = (int) $product_id;
        return [
            'amount' => get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true),
            'type' => get_post_meta($product_id, '_awcdp_deposit_type', true),
        ];
    }

    /**
     * Get waived brands from database
     * 
     * @return array List of brand slugs that have admin fee waived
     */
    private static function get_waived_brands()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'c_admin_fee_waivers';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name from $wpdb->prefix (safe)
        $rows = $wpdb->get_col("SELECT brand_slug FROM {$table_name}");
        return array_map('strtolower', array_map('trim', (array)$rows));
    }

    /**
     * Calculate all cart-related data in a single loop
     * This consolidates what was previously 5-6 separate cart iterations.
     * 
     * @return array{base_cart_total: float, extras_total: float, has_deposits: bool, deposit_total: float, full_total: float, remaining_amount: float, cart_brands: array}
     */
    private static function calculate_cart_data()
    {
        $cart = WC()->cart;
        $data = [
            'base_cart_total' => 0.0,
            'extras_total' => 0.0,
            'has_deposits' => false,
            'deposit_total' => 0.0,
            'full_total' => 0.0,
            'remaining_amount' => 0.0,
            'cart_brands' => [],
        ];

        if (!$cart || $cart->is_empty()) {
            return $data;
        }

        foreach ($cart->get_cart() as $cart_item) {
            $qty = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;
            $product_id = isset($cart_item['product_id']) ? (int)$cart_item['product_id'] : 0;
            $variation_id = isset($cart_item['variation_id']) ? (int)$cart_item['variation_id'] : 0;
            $pid = $variation_id ? $variation_id : $product_id;
            $product = self::get_product($pid);

            if (!$product) continue;

            // 1. Calculate base cart total (regular price)
            $regular_price = $product->get_regular_price();
            if ($regular_price === '' || $regular_price === null) {
                $regular_price = $product->get_price();
            }
            $data['base_cart_total'] += floatval($regular_price) * $qty;

            // 2. Calculate extras total
            if (isset($cart_item['product_extras']) && !empty($cart_item['product_extras']['selected_products'])) {
                foreach ($cart_item['product_extras']['selected_products'] as $extra) {
                    $extra_price = isset($extra['price']) ? floatval($extra['price']) : 0;
                    $extra_qty = isset($extra['quantity']) ? intval($extra['quantity']) : 1;
                    
                    // Apply discount if available
                    $child_discount = isset($extra['childDiscount']) ? floatval($extra['childDiscount']) : 0;
                    $discount_type = isset($extra['discountType']) ? $extra['discountType'] : '';
                    
                    if ($extra_price > 0 && $child_discount > 0) {
                        if ($discount_type === 'percent') {
                            $extra_price = $extra_price - ($extra_price * ($child_discount / 100));
                        } elseif ($discount_type === 'fixed') {
                            $extra_price = max(0, $extra_price - $child_discount);
                        }
                    }
                    
                    $data['extras_total'] += $extra_price * $extra_qty * $qty;
                }
            }

            // 3. Check for deposits and calculate deposit amounts
            $is_deposit = (
                (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') ||
                (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') ||
                (isset($cart_item['_final_tradein_price']))
            );

            if ($is_deposit) {
                $data['has_deposits'] = true;

                // Calculate deposit amount
                if (isset($cart_item['deposit_amount']) && is_numeric($cart_item['deposit_amount'])) {
                    $data['deposit_total'] += floatval($cart_item['deposit_amount']) * $qty;
                } elseif (isset($cart_item['_final_tradein_price']) && is_numeric($cart_item['_final_tradein_price'])) {
                    $data['deposit_total'] += floatval($cart_item['_final_tradein_price']) * $qty;
                } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable']) && $cart_item['awcdp_deposit']['enable'] == 1 && isset($cart_item['awcdp_deposit']['deposit'])) {
                    $data['deposit_total'] += floatval($cart_item['awcdp_deposit']['deposit']) * $qty;
                } else {
                    // Fallback: compute from product meta
                    $meta = self::get_deposit_meta($product_id);
                    $base_price = floatval($product->get_price());
                    if ($base_price === 0.0) {
                        $base_price = floatval($product->get_regular_price());
                    }
                    if ($meta['amount'] !== '' && $meta['amount'] !== null) {
                        if ($meta['type'] === 'percent') {
                            $data['deposit_total'] += ($base_price * (floatval($meta['amount']) / 100)) * $qty;
                        } else {
                            $data['deposit_total'] += floatval($meta['amount']) * $qty;
                        }
                    } else {
                        $data['deposit_total'] += $base_price * $qty;
                    }
                }

                // Calculate full total for deposit items
                $base_price = $product->get_price();
                if ($base_price === '' || $base_price === null) {
                    $base_price = $product->get_regular_price();
                }
                
                // Apply Trade-In Discount
                if (isset($cart_item['trade_in']) && $cart_item['trade_in'] === 'yes' && class_exists('TradeInController')) {
                    $discount = TradeInController::get_trade_in_discount($product_id);
                    $base_price = max(0, floatval($base_price) - $discount);
                }
                
                $data['full_total'] += floatval($base_price) * $qty;
            }

            // 4. Collect cart brands for admin fee waiver check
            if ($product_id > 0) {
                $brands = wp_get_post_terms($product_id, 'product_brand', ['fields' => 'slugs']);
                if (!is_wp_error($brands) && !empty($brands)) {
                    $data['cart_brands'][] = strtolower($brands[0]);
                }
            }
        }

        // Calculate remaining amount for deposits
        $data['remaining_amount'] = max($data['full_total'] - $data['deposit_total'], 0.0);

        return $data;
    }

    /**
     * Initialize checkout page hooks and filters
     */
    public static function init_checkout_page_hooks()
    {
        // Unhook tracking plugins during checkout AJAX to improve update_order_review performance
        add_action('woocommerce_checkout_update_order_review', [self::class, 'unhook_tracking_plugins_during_ajax'], 1);

        // Modify checkout item name to include variation attributes
        add_filter('woocommerce_cart_item_name', [self::class, 'modify_checkout_item_name'], 10, 3);

        // Render quantity as plain text on checkout (no editable selector)
        // add_filter('woocommerce_checkout_cart_item_quantity', [self::class, 'render_checkout_item_quantity_plain'], 99, 3);

        // Enqueue checkout-specific assets
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_checkout_assets']);

        // Restrict shipping methods when Trade-In is yes or deposit/partial payment selected
        add_filter('woocommerce_package_rates', [self::class, 'restrict_shipping_methods_for_tradein_or_deposit'], 100, 2);
        add_filter('woocommerce_shipping_chosen_method', [self::class, 'force_local_pickup_plus_when_restricted'], 10, 3);

        // Debug hook to inject console logs into AJAX response
        add_action('woocommerce_review_order_before_order_total', [self::class, 'render_debug_logs']);
        add_filter('woocommerce_update_order_review_fragments', [self::class, 'ensure_fragments_total_consistency'], PHP_INT_MAX);

        // Ensure coupon UI strings render correctly on initial page load
        add_filter('woocommerce_checkout_coupon_message', [self::class, 'filter_checkout_coupon_message']);
        add_filter('gettext', [self::class, 'translate_coupon_code_to_discount_code'], 10, 3);

        // Initialize deposit functionality
        self::init_deposit_hooks();

        // Filter cart total on checkout to ensure fees are included
        add_filter('woocommerce_cart_get_total', [self::class, 'filter_checkout_cart_total'], PHP_INT_MAX, 1);

        // self::ensure_checkout_totals_include_extras();
        // add_action('woocommerce_after_calculate_totals', [self::class, 'ensure_checkout_totals_include_extras'], PHP_INT_MAX);
        
        // Render a custom shop table row after cart contents on checkout
        add_action('woocommerce_review_order_after_cart_contents', [self::class, 'render_custom_checkout_cart_table'], 10);

        // Handle iPay88 cancellation error notice
        if ( isset( $_GET['ipay88_error'] ) && $_GET['ipay88_error'] === 'cancelled' ) {
            wc_add_notice(
                __( 'Payment was cancelled or failed. Please try again.', 'wc_ipay88' ),
                'error'
            );
        }
    }

    /**
     * Initialize deposit functionality hooks for checkout
     */
    public static function init_deposit_hooks()
    {
        // Checkout deposit handling
        add_action('woocommerce_checkout_order_processed', [self::class, 'process_deposit_order'], 10, 3);
        // Render Deposit Payment inside order totals so WFACP shows it in summary
        add_action('woocommerce_review_order_before_order_total', [self::class, 'render_deposit_payment_row'], 11);
        // Render Remaining Balance inside order totals so WFACP shows it in summary
        add_action('woocommerce_review_order_before_order_total', [self::class, 'render_remaining_balance_row']);
        add_filter('woocommerce_order_item_name', [self::class, 'checkout_order_item_name'], 10, 3);

    }

    /**
     * Unhook tracking plugins during checkout AJAX to improve performance
     * 
     * The Pixel Manager for WooCommerce plugin injects inline <script> tags for each cart item
     * via the woocommerce_after_cart_item_name hook. This causes significant performance overhead
     * during update_order_review AJAX calls. This method removes those hooks during AJAX.
     * 
     * @param string $post_data The posted data from checkout form
     */
    public static function unhook_tracking_plugins_during_ajax($post_data = '')
    {
        // Only apply during AJAX requests
        if (!wp_doing_ajax()) {
            return;
        }

        global $wp_filter;
        
        $hooks_to_clean = [
            'woocommerce_after_cart_item_name',
            'woocommerce_after_mini_cart_item_name',
        ];
        
        foreach ($hooks_to_clean as $hook) {
            if (!isset($wp_filter[$hook]) || !is_object($wp_filter[$hook])) {
                continue;
            }
            
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $id => $callback) {
                    // Check if this is the Pixel Manager callback
                    if (is_array($callback['function']) && 
                        isset($callback['function'][0]) && 
                        is_object($callback['function'][0])) {
                        $class_name = get_class($callback['function'][0]);
                        // Match Pixel Manager class names
                        if (strpos($class_name, 'Pixel_Manager') !== false || 
                            strpos($class_name, 'PMW') !== false ||
                            strpos($class_name, 'wpm') !== false) {
                            remove_action($hook, $callback['function'], $priority);
                        }
                    }
                }
            }
        }
    }

    /**
     * Modify checkout item name to include variation attributes
     * Note: Trade-in and payment option info is now handled by display_trade_in_info_universal
     * to prevent duplication and ensure proper positioning below variations
     */
    public static function modify_checkout_item_name($product_name, $cart_item, $cart_item_key)
    {
        // Only modify on checkout page
        if (!is_checkout()) {
            return $product_name;
        }

        // Check if variation data is already displayed to prevent duplication
        if (strpos($product_name, 'cart-variation-data') !== false || strpos($product_name, 'checkout-variation-data') !== false) {
            return $product_name;
        }

        // Add variation attributes display below product title
        $variation_html = self::get_formatted_variation_data($cart_item);
        if (!empty($variation_html)) {
            $product_name .= $variation_html;
        }

        // Append Trade In and Payment Option information below the product title using top-level values
        $product_extras = !empty($cart_item['product_extras']) ? $cart_item['product_extras'] : array();

        // Gate Trade In and Deposit labels by AWCDP deposit eligibility
        $base_product_id = isset($cart_item['product_id']) ? (int)$cart_item['product_id'] : 0;
        $deposit_eligible = WooCommerceAddtoCartController::is_deposit_eligible_for_product_id($base_product_id);

        if ($deposit_eligible) {
            // Trade In from top-level cart item
            if (isset($cart_item['trade_in']) && $cart_item['trade_in'] !== '') {
                $trade_in_value = ($cart_item['trade_in'] === 'yes') ? __('Yes', 'senheng-core') : __('No', 'senheng-core');
                $product_name .= '<div class="checkout-trade-in"><small>' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html($trade_in_value) . '</small></div>';
            }

            // Payment Option: show deposit line only when selected and add Actual Price below
            $is_deposit = false;
            if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] !== '') {
                $is_deposit = ($cart_item['awcdp_deposit_option'] === 'yes');
            } elseif (isset($cart_item['deposit_option'])) {
                $is_deposit = ($cart_item['deposit_option'] === 'deposit');
            }

            if ($is_deposit) {
                // Always show a simple label without amount
                $product_name .= '<div class="checkout-payment-option"><small>' . esc_html__('Deposit Payment', 'senheng-core') . '</small></div>';

                // Actual product price below payment option
                $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
                $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
                $product_obj = self::get_product($variation_id ? $variation_id : $product_id);
                if ($product_obj) {
                    $base_price = $product_obj->get_price();
                    $product_name .= '<div class="checkout-actual-price"><small>' . esc_html__('Actual Price', 'senheng-core') . ': ' . wc_price($base_price) . '</small></div>';
                }
            }
        }

        return $product_name;
    }

    /**
     * Render checkout item quantity as plain text (no selector/input)
     */
    public static function render_checkout_item_quantity_plain($quantity_html, $cart_item, $cart_item_key)
    {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return $quantity_html;
        }
        $qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;
        return '<span class="checkout-qty">Qty: &times; ' . $qty . '</span>';
    }

    // START - E-Invoice Fields
    public static function register_einvoice_fields($fields)
    {
        $users = is_user_logged_in() ? wp_get_current_user() : null;
        $woocommerce_states = WC()->countries->get_states();

        //check if total cart amount is above 10000 then need_einvoice is required
        $cart_total_raw = WC()->cart->total;
        $cart_total = (float) $cart_total_raw;
        $need_einvoice_required = $cart_total >= 10000 ? true : false;

        $fields['einvoice'] = [
            'need_einvoice' => [
                'type'     => 'checkbox',
                'label'    => __('Do you need an E-invoice? (for government tax submission)', 'textdomain'),
                'required' => $need_einvoice_required,
                'priority' => 120,
                'class'    => ['need-einvoice-inline'],
                'default'  => $need_einvoice_required ? 1 : 0,
            ],
            'einvoiceName' => [
                'type'     => 'text',
                'label'    => __('Full Name (as per IC)', 'textdomain'),
                'required' => false,
                'priority' => 121,
                'class'    => ['einvoice-field'],
                'default'  => $users ? get_user_meta($users->ID, 'cust_name', true) : '',
            ],
            'einvoiceIDType' => [
                'type'     => 'select',
                'label'    => __('ID Type', 'textdomain'),
                'required' => false,
                'priority' => 122,
                'options'  => [
                    'BRN'      => __('Business Registration Number (BRN)', 'textdomain'),
                    'NRIC'     => __('National ID (NRIC)', 'textdomain'),
                    'Passport' => __('Passport', 'textdomain'),
                    'Army'     => __('Army', 'textdomain'),
                ],
                'class'    => ['einvoice-field'],
                'default'  => 'NRIC',
            ],
            'einvoiceIDNo' => [
                'type'     => 'text',
                'label'    => __('Registration / Identification / Passport Number', 'textdomain'),
                'required' => false,
                'priority' => 123,
                'class'    => ['einvoice-field'],
            ],
            'einvoiceTinNo' => [
                'type'     => 'text',
                'label'    => __('TIN No', 'textdomain'),
                'placeholder' => __('TIN No E.g. E100000000020', 'textdomain'),
                'required' => false,
                'priority' => 124,
                'class'    => ['einvoice-field', 'optional'],
            ],
            'einvoiceSSTNo' => [
                'type'     => 'text',
                'label'    => __('SST Number (N/A if not applicable)', 'textdomain'),
                'required' => false,
                'priority' => 125,
                'class'    => ['einvoice-field', 'optional'],
            ],
            'einvoiceEmail' => [
                'type'     => 'email',
                'label'    => __('Email address', 'textdomain'),
                'required' => false,
                'priority' => 126,
                'class'    => ['einvoice-field'],
                'default'  => $users ? $users->user_email : '',
            ],
            'einvoiceContactNo' => [
                'type'     => 'text',
                'label'    => __('Contact Number', 'textdomain'),
                'required' => false,
                'priority' => 127,
                'class'    => ['einvoice-field'],
                'default'  => $users ? get_user_meta($users->ID, 'cust_contact', true) : '',
            ],
            'einvoiceStreetAdd' => [
                'type'     => 'text',
                'label'    => __('Address', 'textdomain'),
                'required' => false,
                'priority' => 128,
                'class'    => ['einvoice-field'],
                'default'  => $users ? get_user_meta($users->ID, 'billing_address_1', true) : '',
            ],
            'einvoiceCity' => [
                'type'     => 'text',
                'label'    => __('City', 'textdomain'),
                'required' => false,
                'priority' => 129,
                'class'    => ['einvoice-field'],
                'default'  => $users ? get_user_meta($users->ID, 'billing_city', true) : '',
            ],
            'einvoiceStateCode' => [
                'type'     => 'text',
                'label'    => __('Postcode', 'textdomain'),
                'required' => false,
                'priority' => 130,
                'class'    => ['einvoice-field'],
                'default'  => $users ? get_user_meta($users->ID, 'billing_postcode', true) : '',
            ],
            'einvoiceState' => [
                'type'     => 'select',
                'label'    => __('State', 'textdomain'),
                'required' => false,
                'priority' => 131,
                'options'  => $woocommerce_states[WC()->countries->get_base_country()],
                'class'    => ['einvoice-field'],
                'default'  => $users ? get_user_meta($users->ID, 'billing_state', true) : '',
            ],
            'einvoice_confirm' => [
                'type'     => 'checkbox',
                'label'    => __('I confirm that all the information entered is correct and understand that any errors may cause difficulties in processing my e-invoice request.', 'textdomain'),
                'required' => false,
                'priority' => 132,
                'class'    => ['einvoice-field'],
            ],
        ];

        // foreach ($extra_fields as $key => $field) {
        //     $fields['billing'][$key] = $field;
        // }

        return $fields;
    }

    // Custom Validation for E-Invoice Fields
    public static function validate_einvoice_fields($data, $errors)
    {
        if (!empty($data['need_einvoice'])) {
            $required_fields = [
                'einvoiceName' => __('Full Name (as per IC)', 'textdomain'),
                'einvoiceIDType' => __('ID Type', 'textdomain'),
                'einvoiceIDNo' => __('Registration / Identification / Passport Number', 'textdomain'),
                'einvoiceEmail' => __('Email address', 'textdomain'),
                'einvoiceContactNo' => __('Contact Number', 'textdomain'),
                'einvoiceStreetAdd' => __('Address', 'textdomain'),
                'einvoiceCity' => __('City', 'textdomain'),
                'einvoiceStateCode' => __('Postcode', 'textdomain'),
                'einvoiceState' => __('State', 'textdomain'),
                'einvoice_confirm' => __('Confirmation of e-invoice details', 'textdomain'),
            ];

            foreach ($required_fields as $field_key => $field_label) {
                if (empty($data[$field_key])) {
                    $errors->add('einvoice_validation', sprintf(__('Please enter a valid %s.', 'textdomain'), $field_label));
                }
            }

            if (!empty($data['einvoiceIDNo']) && !preg_match('/^[0-9a-zA-Z]+$/', $data['einvoiceIDNo'])) {
                $errors->add('einvoice_validation', __('The Registration / Identification / Passport Number must contain only letters and numbers.', 'textdomain'));
            }

            if (!empty($data['einvoiceEmail']) && !is_email($data['einvoiceEmail'])) {
                $errors->add('einvoice_validation', __('Please enter a valid email address for e-invoice.', 'textdomain'));
            }

            if (empty($data['einvoice_confirm'])) {
                $errors->add('einvoice_validation', __('Please confirm the e-invoice details.', 'textdomain'));
            }
        }
    }

    // Save E-Invoice Fields to Order
    public static function save_einvoice_fields($order, $data)
    {
        if (!empty($data['need_einvoice'])) {
            $fields = [
                'einvoiceName',
                'einvoiceIDType',
                'einvoiceIDNo',
                'einvoiceTinNo',
                'einvoiceSSTNo',
                'einvoiceEmail',
                'einvoiceContactNo',
                'einvoiceStreetAdd',
                'einvoiceCity',
                'einvoiceStateCode',
                'einvoiceState',
                'einvoice_confirm',
            ];

            foreach ($fields as $key) {
                if (!empty($data[$key])) {
                    $order->update_meta_data($key, sanitize_text_field($data[$key]));
                }
            }
        }
    }

    // Show/Hide E-Invoice Fields with JavaScript and Add Asterisk
    public static function show_hide_e_invoice_fields()
    {
        if (is_checkout()) {
?>
            <script type="text/javascript">
                jQuery(function($) {
                    //remove all optional class in form
                    $('.form-row').find('label .optional').remove();

                    // Debounce helper to avoid excessive work on rapid updated_checkout events
                    function debounce(fn, wait) {
                        var t;
                        return function() {
                            var ctx = this, args = arguments;
                            clearTimeout(t);
                            t = setTimeout(function(){ fn.apply(ctx, args); }, wait || 100);
                        };
                    }

                    // Update coupon label/placeholder in WFACP checkout layout
                    function updateCouponText() {
                        var $root = $('.wfacp_layout_shopcheckout');
                        if (!$root.length) { return; }

                        // Update label and placeholder inside coupon form
                        $root.find('form.checkout_coupon.woocommerce-form-coupon').each(function() {
                            var $form = $(this);
                            $form.find('label[for="coupon_code"]').text('Discount Code');
                            $form.find('input[name="coupon_code"]').attr('placeholder', 'Discount Code');
                        });

                        // Tweak toggle message, if present (e.g., "Have a coupon?")
                        $('.woocommerce-form-coupon-toggle .woocommerce-info').each(function(){
                            var html = $(this).html();
                            if (html && /coupon/i.test(html)) {
                                $(this).html(html.replace(/coupon/gi, 'discount code'));
                            }
                        });
                    }

                    function toggleEinvoiceFields() {
                        // List of fields that should have an asterisk when required
                        const requiredFields = [
                            'einvoiceName',
                            'einvoiceIDType',
                            'einvoiceIDNo',
                            'einvoiceEmail',
                            'einvoiceContactNo',
                            'einvoiceStreetAdd',
                            'einvoiceCity',
                            'einvoiceStateCode',
                            'einvoiceState',
                            'einvoice_confirm'
                        ];

                        if ($('#need_einvoice').is(':checked')) {
                            $('.einvoice-field').closest('.form-row').show();
                            // Add required attribute and asterisk to required fields
                            requiredFields.forEach(function(fieldId) {
                                const $field = $('#' + fieldId + '_field');
                                const $label = $field.find('label');
                                // Add required attribute
                                $field.find('input, select').prop('required', true);
                                // Add asterisk if not already present
                                if (!$label.text().includes('*')) {
                                    $label.append('<span class="required">*</span>');
                                }
                            });
                        } else {
                            $('.einvoice-field').closest('.form-row').hide();
                            // Remove required attribute and asterisk
                            $('.einvoice-field').closest('.form-row').find('input, select').prop('required', false);
                            $('.einvoice-field').closest('.form-row').find('label .required').remove();
                        }
                    }

                    // function toggleCustIcField() {
                    //     if ($('.wty-checkbox').is(':checked')) {
                    //         $('#cust_icno_for_warranty').closest('.form-row').show(); // show field
                    //     } else {
                    //         $('#cust_icno_for_warranty').closest('.form-row').hide(); // hide field
                    //     }
                    // }

                    function bindWarrantyICFilter() {
                        var $fields = $('#cust_icno_for_warranty, #einvoiceIDNo');
                        // Prevent duplicate input handlers across multiple checkout updates
                        $fields.off('input.einvoice');
                        $fields.on('input.einvoice', function() {
                            $(this).val($(this).val().replace(/[^0-9a-zA-Z]/g, ''));
                        });
                    }


                    // Run on page load
                    toggleEinvoiceFields();
                    bindWarrantyICFilter();
                    updateCouponText();
                    // toggleCustIcField();

                    // Run on checkbox change (namespaced)
                    $(document).off('change.einvoice', '#need_einvoice').on('change.einvoice', '#need_einvoice', toggleEinvoiceFields);
                    // $(document).on('change', '.wty-checkbox', toggleCustIcField);

                    // Run after WooCommerce updates checkout with a single debounced handler
                    var runUpdated = function(){
                        toggleEinvoiceFields();
                        bindWarrantyICFilter();
                        updateCouponText();
                    };
                    $(document.body).off('updated_checkout.einvoice').on('updated_checkout.einvoice', debounce(runUpdated, 120));
                    // Ensure coupon text and UI tweaks persist through fragment refreshes
                    $(document.body).off('wc_fragments_refreshed.einvoice').on('wc_fragments_refreshed.einvoice', debounce(runUpdated, 120));
                    $(document.body).off('wc_fragments_loaded.einvoice').on('wc_fragments_loaded.einvoice', debounce(runUpdated, 120));
                    // $(document.body).on('updated_checkout', toggleCustIcField);

                    // Debounced totals check handler to avoid repeated work
                    $(document.body).off('updated_checkout.einvoice_total').on('updated_checkout.einvoice_total', debounce(function() {
                        let totalText = $('.order-total .amount').text().trim();
                        let numericString = totalText.replace(/[^\d.]/g, '');
                        let total = parseFloat(numericString);

                        if (total >= 10000) {
                            $('#need_einvoice').prop('checked', true);
                        } else {
                            $('#need_einvoice').prop('checked', false);
                        }
                        toggleEinvoiceFields();
                    }, 150));



                    //LOGIN POPUP IN CHECKOUT
                    let createAccountToggle = $('.woocommerce-account-fields');
                    createAccountToggle.hide();
                    let loginToggle = $('.woocommerce-form-login-toggle');
                    if (loginToggle.length) {
                        loginToggle.after('<div class="login-overlay"></div>');
                        jQuery('.login-overlay').css('background', 'rgba(0, 0, 0, 0.5)');
                    }
                    let loginNewDiv = `
                    <div class="woocommerce-form-login-toggle">
                      <section class="account-cta-wrapper">
                            <p class="account-cta-text">
                                <b class="js-open-login">Sign in</b> or
                                <b class="js-open-register">register an account</b>
                                to unlock your S-Coin rewards and exclusive member savings!
                            </p>
                            <div class="account-cta-buttons">
                            <button class="btn btn-outline-red" id="checkout-login-button">Sign In</button>
                            <button class="btn btn-black" id="checkout-register-button">Register Account</button>
                            </div>
                        </section>
                    </div>
                    `;
                    loginToggle.html(loginNewDiv);

                    jQuery(document).ready(() => {
                        let popupVisible = false;
                        const checkoutLoginTriggers    = jQuery('#checkout-login-button, .js-open-login');
                        const checkoutRegisterTriggers = jQuery('#checkout-register-button, .js-open-register');

                        checkoutLoginTriggers.on('click', function(e) {
                            e.preventDefault();
                            const popup = jQuery('.login-container');

                            if (popupVisible) {
                                popup.removeClass('active');
                                jQuery('.login-overlay').fadeOut(300);
                                setTimeout(() => {
                                    popup.css('display', 'none');
                                }, 400);
                                popupVisible = false;
                                return;
                            }

                            if (!popupVisible) {
                                popup.css('display', 'block');
                                setTimeout(() => {
                                    popup.addClass('active');
                                    jQuery('.login-overlay').fadeIn(300);

                                    // Load the Turnstile only now
                                    renderLoginTurnstile();
                                }, 0);

                                popupVisible = true;
                                return;
                            }


                            registerCard.style.display = 'none';
                            loginCard.style.display = 'block';
                            renderLoginTurnstile();
                        });

                        checkoutRegisterTriggers.on('click', function(e) {
                            e.preventDefault();
                            const popup = jQuery('.login-container');

                            registerCard.style.display = 'none';
                            loginCard.style.display = 'block';

                            if (popupVisible) {
                                popup.removeClass('active');
                                jQuery('.login-overlay').fadeOut(300);
                                setTimeout(() => {
                                    popup.css('display', 'none');
                                }, 400);
                                popupVisible = false;
                                return;
                            }

                            popup.css('display', 'block');
                            setTimeout(() => {
                                popup.addClass('active');
                                jQuery('.login-overlay').fadeIn(300);
                            }, 0);
                            popupVisible = true;

                            loginCard.style.display = 'none';
                            registerCard.style.display = 'block';
                            registerPhone.style.display = 'block';
                            enterTacRegister.style.display = 'none';
                            enterDetailsRegister.style.display = 'none';
                            otpInputsRegister.forEach(input => input.value = '');
                            fullNameReg.value = '';
                            emailReg.value = '';
                            icNumberReg.value = '';
                            phoneNumberReg.value = '';
                            passwordReg.value = '';
                            cPasswordReg.value = '';
                            renderRegisterTurnstile();
                        });

                        jQuery(document).on('click', '.login-overlay', function() {
                            jQuery('.login-container').removeClass('active');
                            jQuery(this).fadeOut(300);
                            setTimeout(() => {
                                jQuery('.login-container').css('display', 'none');
                            }, 400);
                            popupVisible = false;

                            destroyTurnstile(); // 🔥 Stop Turnstile
                        });
                    });

                });
            </script>
            <style>
                .account-cta-wrapper {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: flex-start;
                    gap: 1rem 1.25rem;
                    max-width: 600px;
                    padding-bottom: 30px !important;
                }

                .account-cta-text {
                    font-size: 15px;
                    font-weight: 500;
                    color: #000;
                }

                .account-cta-text a {
                    color: #e4002b;
                    text-decoration: none;
                    font-weight: 600;
                }

                .account-cta-text a:hover {
                    text-decoration: underline;
                }

                .account-cta-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.75rem;
                }

                /* Button base */
                .account-cta-wrapper .btn {
                    font-size: 14px;
                    font-weight: 600;
                    border-radius: 4px;
                    padding: 0.9rem 2rem;
                    line-height: 1.1;
                    cursor: pointer;
                    border: 2px solid transparent;
                    background: transparent;
                    color: inherit;
                    min-width: 140px;
                    text-align: center;
                }

                /* Outline red button */
                .account-cta-wrapper .btn-outline-red {
                    border-color: #e4002b;
                    color: #e4002b;
                    background-color: #fff;
                }

                .account-cta-wrapper .btn-outline-red:hover {
                    background-color: #ffeef1;
                }

                /* Solid black button */
                .account-cta-wrapper .btn-black {
                    background-color: #000;
                    color: #fff;
                    border-color: #000;
                }

                .account-cta-wrapper .btn-black:hover {
                    filter: brightness(1.1);
                }

                /* stack on very small screens */
                @media (max-width: 480px) {
                    .account-cta-wrapper {
                        flex-direction: column;
                        align-items: flex-start;
                    }
                }


                /* Center login popup only on checkout page */
                .woocommerce-checkout .login-container {
                    right: auto !important;
                    left: 50% !important;
                    top: 50% !important;
                    transform: translate(-50%, -50%);
                    width: 420px;
                    /* adjust as needed */
                    max-height: 90vh;
                    border-radius: 20px;
                    transition: opacity 0.4s ease;
                }

                /* When popup is active */
                .woocommerce-checkout .login-container.active {
                    opacity: 1;
                }

                /* hidden state initially */
                .woocommerce-checkout .login-container {
                    opacity: 0;
                    display: none;
                }

                .account-cta-text .js-open-login,
                .account-cta-text .js-open-register {
                    cursor: pointer;
                }

            </style>
        <?php
        }
    }

    // Filter to Remove "(optional)" from Labels
    public static function remove_optional_label($field, $key, $args, $value)
    {
        if (strpos($key, 'einvoice') !== false && !empty($args['label'])) {
            $field = str_replace(' <span class="optional">(optional)</span>', '', $field);
        }

        // Force coupon field to use "Discount code" label/placeholder on page load
        if (function_exists('is_checkout') && is_checkout() && $key === 'coupon_code') {
            // Replace label if present
            $field = preg_replace('/(<label[^>]*for="coupon_code"[^>]*>)\s*.*?(<\/label>)/i', '<label for="coupon_code">Discount code</label>', $field);
            // Replace placeholder in input
            $field = preg_replace('/(name="coupon_code"[^>]*\bplaceholder=")[^"]*(")/i', '$1Discount code$2', $field);
            // If no placeholder attribute exists, inject it
            if (strpos($field, 'name="coupon_code"') !== false && !preg_match('/name="coupon_code"[^>]*placeholder="/i', $field)) {
                $field = preg_replace('/(<input[^>]*name="coupon_code"[^>]*)(>)/i', '$1 placeholder="Discount code"$2', $field);
            }
        }
        return $field;
    }

    /**
     * Replace checkout coupon toggle message to use "discount code"
     */
    public static function filter_checkout_coupon_message($message)
    {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return $message;
        }
        return preg_replace('/\bcoupon\b/i', 'discount code', $message);
    }

    /**
     * Translate "Coupon code" and related strings to "Discount code" on checkout
     */
    public static function translate_coupon_code_to_discount_code($translated, $text, $domain)
    {
        // Early exit: strings too short or too long to contain "coupon"
        $len = strlen($text);
        if ($len < 6 || $len > 100) {
            return $translated;
        }

        // Cache is_checkout() check per-request to avoid repeated function calls
        static $is_checkout = null;
        if ($is_checkout === null) {
            $is_checkout = function_exists('is_checkout') && is_checkout();
        }
        if (!$is_checkout) {
            return $translated;
        }

        // Quick check: if "coupon" not in text, skip regex entirely
        if (stripos($text, 'coupon') === false) {
            return $translated;
        }

        // Exact label match
        if (strcasecmp($text, 'Coupon code') === 0) {
            return 'Discount code';
        }
        // Replace "Coupon code" as a phrase first (case insensitive) to prevent "Discount Code code"
        if (preg_match('/\bcoupon\s+code\b/i', $translated)) {
            return preg_replace('/\bcoupon\s+code\b/i', 'Discount code', $translated);
        }
        // General replacement for standalone "coupon" (e.g., Have a coupon?)
        if (preg_match('/\bcoupon\b/i', $text)) {
            return preg_replace('/\bcoupon\b/i', 'discount code', $translated);
        }
        return $translated;
    }
    // END - E-Invoice Fields

    /**
     * Determine if any cart item has Trade-In = yes or deposit/partial payment selected
     */
    private static function has_trade_in_or_deposit_selected()
    {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        // Per-request cache keyed by cart hash to avoid repeated loops during AJAX refreshes
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
     * Restrict available shipping methods to Local Pickup Plus when trade-in or deposit is selected
     */
    public static function restrict_shipping_methods_for_tradein_or_deposit($rates, $package)
    {
        // Only apply on checkout page
        if (!function_exists('is_checkout') || !is_checkout()) {
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
     * Force chosen shipping method to Local Pickup Plus when restricted
     */
    public static function force_local_pickup_plus_when_restricted($method, $rates, $package)
    {
        // Only apply on checkout page
        if (!function_exists('is_checkout') || !is_checkout()) {
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


    // START - Custom Virtual Checkout Fields
    public static function custom_virtual_checkout_fields($fields)
    {
        // If only virtual products, unset all default checkout fields
        if (self::is_cart_only_virtual()) {
            $fields = array();

            // Add custom fields
            $fields['billing']['id_type'] = array(
                'type'        => 'select',
                'label'       => __('ID Type', 'woocommerce'),
                'options'     => array(
                    ''          => __('Select an ID Type', 'woocommerce'),
                    'nric'      => __('NRIC', 'woocommerce'),
                    'passport'  => __('Passport', 'woocommerce'),
                ),
                'required'    => true,
                'class'       => array('form-row-wide'),
                'priority'    => 10,
            );

            $fields['billing']['id_number'] = array(
                'type'        => 'text',
                'label'       => __('ID Number', 'woocommerce'),
                'placeholder' => __('Enter your identification number', 'woocommerce'),
                'required'    => true,
                'class'       => array('form-row-wide'),
                'priority'    => 20,
            );

            $fields['billing']['email'] = array(
                'type'        => 'email',
                'label'       => __('Please enter your email', 'woocommerce'),
                'placeholder' => __('Please enter your email', 'woocommerce'),
                'required'    => true,
                'class'       => array('form-row-wide'),
                'priority'    => 30,
            );

            $fields['billing']['confirm_id_number'] = array(
                'type'        => 'text',
                'label'       => __('Please confirm your ID NO', 'woocommerce'),
                'placeholder' => __('Please confirm your ID NO', 'woocommerce'),
                'required'    => true,
                'class'       => array('form-row-wide'),
                'priority'    => 40,
            );
        }

        return $fields;
    }

    public static function validate_custom_virtual_fields()
    {
        if (self::is_cart_only_virtual()) {
            if ($_POST['id_number'] !== $_POST['confirm_id_number']) {
                wc_add_notice(__('ID Number and Confirm ID Number do not match.'), 'error');
            }
        }
    }

    public static function save_custom_virtual_fields($order_id)
    {
        if (!empty($_POST['id_type'])) {
            update_post_meta($order_id, 'ID Type', sanitize_text_field($_POST['id_type']));
        }
        if (!empty($_POST['id_number'])) {
            update_post_meta($order_id, 'ID Number', sanitize_text_field($_POST['id_number']));
        }
        if (!empty($_POST['email'])) {
            update_post_meta($order_id, 'Email', sanitize_email($_POST['email']));
        }
    }

    public static function restrict_virtual_and_physical_cart($passed, $product_id, $quantity)
    {

        // Check if cart is available
        if (!WC()->cart) {
            return $passed;
        }

        // Get the product being added
        $product = self::get_product($product_id);
        if (!$product) {
            return $passed;
        }

        $product_being_added_is_virtual = $product->is_virtual();

        // Check if cart is empty - if so, allow any product type
        if (WC()->cart->is_empty()) {
            return $passed;
        }

        // Check existing cart items for conflicts
        $has_conflict = false;
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $existing_product_is_virtual = $cart_item['data']->is_virtual();

            if ($product_being_added_is_virtual !== $existing_product_is_virtual) {
                $has_conflict = true;
                break;
            }
        }

        if ($has_conflict) {
            // Store the conflict in session for side cart to detect
            WC()->session->set('mixed_product_conflict', true);

            // Allow the product to be added to cart (return true)
            // The side cart will handle the notice and checkout prevention
            return $passed;
        }

        return $passed;
    }

    public static function filter_wc_check_cart_items()
    {
        $cart = WC()->cart;
        $cart_items = $cart->get_cart();
        $has_virtual = $has_physical = false;

        // Loop though cart items
        foreach (WC()->cart->get_cart() as $cart_item) {
            // Check for specific product categories
            if ($cart_item['data']->is_virtual()) {
                $has_virtual = true;
            } else {
                $has_physical = true;
            }
        }

        if ($has_virtual && $has_physical) {
            // Display an error notice (and avoid checkout)
            wc_add_notice(__("You can't combine physical and virtual products together.", "woocommerce"), 'error');
        }
    }
    // END - Custom Virtual Checkout Fields

    // START S-COIN SAVE
    public static function add_scoin_to_order_item($item, $cart_item_key, $values, $order)
    {
        $product_id   = $values['product_id'];
        $variation_id = ! empty($values['variation_id']) ? $values['variation_id'] : 0;

        // Get S-Coin percentage value (variation > parent)
        $s_coin_percent = (float) (
            get_field('s_coin_value', $variation_id) ?: get_field('s_coin_value', $product_id)
        );

        // Get the product price
        $price = (float) $values['data']->get_price();

        // Skip if no S-Coin percentage or no price
        if ($s_coin_percent <= 0 || $price <= 0) {
            return;
        }

        // Calculate S-Coin value: (percentage/100 * price) * 100
        // - First divide percentage by 100 to get decimal (e.g., 1% → 0.01)
        // - Multiply by price to get RM cashback value
        // - Divide by 100 because 100 S-Coin = RM1
        $s_coin_value = ($s_coin_percent / 100 * $price) * 100;

        // Multiply by quantity
        $qty = isset($values['quantity']) ? (int) $values['quantity'] : 1;
        $s_coin_total_item = $s_coin_value * $qty;

        // Apply 0.5 rounding rule (consistent with ScoinController)
        $s_coin_total_item = round($s_coin_total_item, 0, PHP_ROUND_HALF_UP);

        // Save as order item meta
        $item->add_meta_data('_s_coin_value', $s_coin_total_item);
    }

    public static function update_order_meta_with_scoin($order_id, $data)
    {
        $order = wc_get_order($order_id);

        $total_scoins = 0;
        foreach ($order->get_items() as $item) {
            $s_coin_value = (float) $item->get_meta('_s_coin_value');
            $total_scoins += $s_coin_value;
        }

        // Save as order meta
        update_post_meta($order_id, '_s_coin_total', $total_scoins);
    }

    public static function capture_raw_checkout_post($order_id, $data)
    {
        $order = wc_get_order($order_id);
        $amount = number_format($order->get_total(), 2, '.', '');
        $payment_type = $_POST['ipay88_payment_type'] ?? '';
        $types_mapping = ipay88_types_mapping();
        $payment_plan = $_POST['ipay88_payment_plan' . $payment_type] ?? '';
        $admin_fee    = $_POST['ipay88_admin_fee' . $payment_type] ?? '';
        $adminFeeDB = PaymentMethod::getAdminFeePaymentMethods($payment_type, $payment_plan);

        // Persist immediately
        if ($payment_type) {
            $payment_name = $types_mapping['name'][$payment_type] ?? '';
            update_post_meta($order_id, '_ipay88_payment_type_name', sanitize_text_field($payment_name));
            update_post_meta($order_id, '_ipay88_payment_type', sanitize_text_field($payment_type));
            update_post_meta($order_id, '_ipay88_payment_plan', sanitize_text_field($payment_plan));
            update_post_meta($order_id, '_ipay88_admin_fee', sanitize_text_field($admin_fee));
            update_post_meta(
                $order_id,
                '_ipay88_merchant_mode',
                in_array($payment_type, ['523','891'], true) && $payment_plan > 0
                    ? 'second'
                    : 'primary'
            );
            update_post_meta($order_id, '_ipay88_amount', $amount);

        }
    }

    /**
     * Display product extras in checkout page with cart-like layout
     */
    public static function display_product_extras_in_checkout($item_data, $cart_item)
    {
        if (empty($cart_item['product_extras'])) {
            return $item_data;
        }

        $product_extras = $cart_item['product_extras'];

        // Check if there are any extras to display
        if (empty($product_extras['selected_products']) && empty($product_extras['selected_info'])) {
            return $item_data;
        }

        // Start the product extras container
        $extras_html = '<div class="checkout-product-extras">';
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
                        $product_obj = self::get_product($variation_id ? $variation_id : $product_id);
                        if ($product_obj) {
                            $unit_price = (float) $product_obj->get_price();
                            $unit_regular = (float) $product_obj->get_regular_price();
                        }
                    }

                    // Fallback to stored price if product price is 0
                    if ($unit_price <= 0) {
                        $unit_price = isset($extra_product_data['price']) ? (float) $extra_product_data['price'] : 0;
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
                            $product_for_image = self::get_product($variation_id ? $variation_id : $product_id);
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

                        $display_value = '<div class="sh-extras-item">';

                        // Left: Product Image
                        if ($image_url) {
                            $display_value .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_name) . '" class="sh-extras-image">';
                        }

                        $display_value .= '<div class="sh-extras-content">';

                        // Left side: Title and variation
                        $display_value .= '<div class="sh-extras-left">';
                        $display_value .= '<div class="sh-extras-title">' . esc_html($product_name) . '</div>';

                        // Add variation attributes for extra products
                        $variation_html = self::get_formatted_variation_data_for_extra($extra_product_data);
                        if (!empty($variation_html)) {
                            $display_value .= $variation_html;
                        }
                        $display_value .= '</div>';

                        // Right side: Prices and quantity
                        $display_value .= '<div class="sh-extras-right">';
                        $display_value .= '<div class="sh-extras-meta">';
                        $display_value .= '<span class="sh-extras-price">' . ($product_price ? wc_price($product_price) : wc_price(0)) . '</span>';
                        if (!is_null($product_regular_total) && $product_regular_total > $product_price) {
                            $display_value .= '<span class="sh-price-original">' . wc_price($product_regular_total) . '</span>';
                        }
                        $display_value .= '<span class="sh-extras-qty">' . esc_html__('Qty:', 'senheng-core') . ' ' . intval($product_quantity) . '</span>';
                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $products_group .= $display_value;
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
                        $display_value = '<div class="sh-extras-item">';

                        if ($info_image && $info_image !== wc_placeholder_img_src('woocommerce_thumbnail')) {
                            $display_value .= '<img src="' . esc_url($info_image) . '" alt="' . esc_attr($info_label) . '" class="sh-extras-image">';
                        } else {
                            $display_value .= '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($info_label) . '" class="sh-extras-image">';
                        }

                        $display_value .= '<div class="sh-extras-content">';

                        // Left side: Title
                        $display_value .= '<div class="sh-extras-left">';
                        $display_value .= '<div class="sh-extras-title">' . esc_html($info_label) . '</div>';
                        $display_value .= '</div>';

                        // Right side: Prices and quantity
                        $display_value .= '<div class="sh-extras-right">';
                        $display_value .= '<div class="sh-extras-meta">';
                        // Follow ProductExtrasWidget pattern: Sales price RM 0.00, Original price with strikethrough
                        $display_value .= '<span class="sh-extras-price">' . wc_price(0) . '</span>'; // Always show RM 0.00 as sales price
                        // Optional original price (strikethrough) if provided by widget data
                        $original_price = null;
                        if (isset($info_data['infoOriginalPrice']) && is_numeric($info_data['infoOriginalPrice'])) {
                            $original_price = (float) $info_data['infoOriginalPrice'];
                        } elseif (isset($info_data['originalPrice']) && is_numeric($info_data['originalPrice'])) {
                            $original_price = (float) $info_data['originalPrice'];
                        } elseif (isset($info_data['original']) && is_numeric($info_data['original'])) {
                            $original_price = (float) $info_data['original'];
                        }
                        if (!is_null($original_price) && $original_price > 0) {
                            $display_value .= '<span class="sh-price-original">' . wc_price($original_price) . '</span>';
                        }
                        $display_value .= '<span class="sh-extras-qty">' . esc_html__('Qty:', 'senheng-core') . ' 1</span>';
                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $info_group .= $display_value;
                    }
                }
            }
            $info_group .= '</div>';
        }

        // Close the product extras container and add to item_data
        $extras_html .= $info_group . $products_group . '</div>';

        $item_data[] = array(
            'key'   => '<span class="checkout-extras-hidden-key" style="display:none;">extras</span>',
            'value' => $extras_html,
        );

        return $item_data;
    }

    /**
     * Display product extras in checkout after quantity (separate section)
     */
    public static function display_product_extras_in_checkout_after_quantity($product_quantity, $cart_item, $cart_item_key)
    {
        if (empty($cart_item['product_extras'])) {
            return;
        }

        $product_extras = $cart_item['product_extras'];

        // Check if there are any extras to display
        if (empty($product_extras['selected_products']) && empty($product_extras['selected_info'])) {
            return;
        }
        // Start the product extras container
        echo '<div class="checkout-product-extras">';

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
                    if ($product_id) {
                        $variation_id = isset($extra_product_data['variationId']) ? $extra_product_data['variationId'] : '';
                        $product_obj = self::get_product($variation_id ? $variation_id : $product_id);
                        if ($product_obj) {
                            $unit_price = (float) $product_obj->get_price();
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

                    // Calculate total price based on current quantity
                    $product_price = $unit_price * $product_quantity;

                    if ($product_name) {
                        // Get product image
                        $image_url = '';
                        if ($product_id) {
                            $variation_id = isset($extra_product_data['variationId']) ? $extra_product_data['variationId'] : '';
                            $product_for_image = self::get_product($variation_id ? $variation_id : $product_id);
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

                        $display_value = '<div class="sh-extras-item">';

                        // Left: Product Image
                        if ($image_url) {
                            $display_value .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_name) . '" class="sh-extras-image">';
                        }

                        $display_value .= '<div class="sh-extras-content">';

                        // Left side: Title and variation
                        $display_value .= '<div class="sh-extras-left">';
                        $display_value .= '<div class="sh-extras-title">' . esc_html($product_name) . '</div>';

                        // Add variation attributes for extra products
                        $variation_html = self::get_formatted_variation_data_for_extra($extra_product_data);
                        if (!empty($variation_html)) {
                            $display_value .= $variation_html;
                        }
                        $display_value .= '</div>';

                        // Right side: Prices and quantity
                        $display_value .= '<div class="sh-extras-right">';
                        $display_value .= '<div class="sh-extras-meta">';
                        $display_value .= '<span class="sh-extras-price">' . ($product_price ? wc_price($product_price) : wc_price(0)) . '</span>';

                        // Add original price if available
                        $product_regular_total = null;
                        if (isset($extra_product_data['originalPrice']) && is_numeric($extra_product_data['originalPrice'])) {
                            $product_regular_total = (float) $extra_product_data['originalPrice'] * $product_quantity;
                        }
                        if (!is_null($product_regular_total) && $product_regular_total > $product_price) {
                            $display_value .= '<span class="sh-price-original">' . wc_price($product_regular_total) . '</span>';
                        }

                        $display_value .= '<span class="sh-extras-qty">' . esc_html__('Qty:', 'senheng-core') . ' ' . intval($product_quantity) . '</span>';
                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $products_group .= $display_value;
                    }
                }
            }
            $products_group .= '</div>';
        }

        // Display extra info
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
                    $info_name = isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '';
                    $info_price = isset($info_data['infoPrice']) ? $info_data['infoPrice'] : '';
                    $info_image = isset($info_data['imageUrl']) ? $info_data['imageUrl'] : '';

                    if ($info_name) {
                        $display_value = '<div class="sh-extras-item">';

                        if ($info_image && $info_image !== wc_placeholder_img_src('woocommerce_thumbnail')) {
                            $display_value .= '<img src="' . esc_url($info_image) . '" alt="' . esc_attr($info_name) . '" class="sh-extras-image">';
                        } else {
                            $display_value .= '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($info_name) . '" class="sh-extras-image">';
                        }

                        $display_value .= '<div class="sh-extras-content">';

                        // Left side: Title
                        $display_value .= '<div class="sh-extras-left">';
                        $display_value .= '<div class="sh-extras-title">' . esc_html($info_name) . '</div>';
                        $display_value .= '</div>';

                        // Right side: Prices and quantity
                        $display_value .= '<div class="sh-extras-right">';
                        $display_value .= '<div class="sh-extras-meta">';
                        // Follow ProductExtrasWidget pattern: Sales price RM 0.00, Original price with strikethrough
                        $display_value .= '<span class="sh-extras-price">' . wc_price(0) . '</span>'; // Always show RM 0.00 as sales price
                        // Optional original price (strikethrough) if provided by widget data
                        $original_price = null;
                        if (isset($info_data['infoOriginalPrice']) && is_numeric($info_data['infoOriginalPrice'])) {
                            $original_price = (float) $info_data['infoOriginalPrice'];
                        } elseif (isset($info_data['originalPrice']) && is_numeric($info_data['originalPrice'])) {
                            $original_price = (float) $info_data['originalPrice'];
                        } elseif (isset($info_data['original']) && is_numeric($info_data['original'])) {
                            $original_price = (float) $info_data['original'];
                        }
                        if (!is_null($original_price) && $original_price > 0) {
                            $display_value .= '<span class="sh-price-original">' . wc_price($original_price) . '</span>';
                        }
                        $display_value .= '<span class="sh-extras-qty">' . esc_html__('Qty:', 'senheng-core') . ' 1</span>';
                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $display_value .= '</div>';
                        $display_value .= '</div>';

                        $info_group .= $display_value;
                    }
                }
            }
            $info_group .= '</div>';
        }

        // Output the combined groups
        echo $info_group . $products_group;
        echo '</div>';
    }

    /**
     * Save product extras to order item meta
     */
    public static function save_product_extras_to_order_item($item, $cart_item_key, $values, $order)
    {
        if (isset($values['product_extras'])) {
            $product_extras = $values['product_extras'];

            // Save selected products to order item meta
            if (isset($product_extras['selected_products']) && !empty($product_extras['selected_products'])) {
                $item->add_meta_data('_product_extras_products', $product_extras['selected_products']);

                // Also save individual product details for easier access
                foreach ($product_extras['selected_products'] as $index => $product) {
                    $meta_key = '_product_extra_' . ($index + 1);
                    $meta_value = $product['title'];
                    if (!empty($product['quantity']) && $product['quantity'] > 1) {
                        $meta_value .= ' (Qty: ' . $product['quantity'] . ')';
                    }
                    $item->add_meta_data($meta_key, $meta_value);
                }
            }

            // Save selected info to order item meta
            if (isset($product_extras['selected_info']) && !empty($product_extras['selected_info'])) {
                $item->add_meta_data('_product_extras_info', $product_extras['selected_info']);

                // Also save individual info details for easier access
                foreach ($product_extras['selected_info'] as $index => $info) {
                    $meta_key = '_product_info_' . ($index + 1);
                    $meta_value = $info['infoLabel'];
                    if (!empty($info['infoPrice'])) {
                        $meta_value .= ' - ' . $info['infoPrice'];
                    }
                    $item->add_meta_data($meta_key, $meta_value);
                }
            }

            // Save trade-in and deposit option data to order item meta (top-level values)
            if (isset($values['trade_in']) && $values['trade_in'] !== '') {
                $item->add_meta_data('_trade_in_option', $values['trade_in']);
                $item->add_meta_data('Trade In', ($values['trade_in'] === 'yes') ? 'Yes' : 'No');
            }

            // Deposit option: prefer awcdp_deposit_option, fallback to legacy deposit_option
            $deposit_opt = null; // normalized to 'yes'/'no'
            if (isset($values['awcdp_deposit_option']) && $values['awcdp_deposit_option'] !== '') {
                $deposit_opt = $values['awcdp_deposit_option'] === 'yes' ? 'yes' : 'no';
            } elseif (isset($values['deposit_option'])) {
                $deposit_opt = ($values['deposit_option'] === 'deposit') ? 'yes' : 'no';
            }

            if (!is_null($deposit_opt)) {
                // Store legacy-consistent meta for downstream consumers: 'deposit' or 'full'
                $deposit_indicator = ($deposit_opt === 'yes') ? 'deposit' : 'full';
                $item->add_meta_data('_deposit_option', $deposit_indicator);

                if ($deposit_indicator === 'deposit') {
                    $item->add_meta_data('Payment Option', 'Deposit Payment');
                    
                    // Get deposit amount - prioritize actual deposit_amount over _final_tradein_price
                    // Note: _final_tradein_price is the full product price, NOT the deposit amount
                    if (isset($values['deposit_amount']) && is_numeric($values['deposit_amount'])) {
                        $item->add_meta_data('_deposit_amount', $values['deposit_amount']);
                    } elseif (isset($values['product_extras']['deposit_amount'])) {
                        $item->add_meta_data('_deposit_amount', $values['product_extras']['deposit_amount']);
                    }
                    
                    // Save the actual price at order time for consistent display in admin
                    // This ensures the Actual Price reflects the price at time of order
                    $product = $item->get_product();
                    if ($product) {
                        // For trade-in orders, _final_tradein_price contains the full product price
                        // Otherwise, get the current price from product
                        if (isset($values['_final_tradein_price']) && is_numeric($values['_final_tradein_price'])) {
                            $actual_price = $values['_final_tradein_price'];
                        } else {
                            $actual_price = $product->get_price();
                        }
                        
                        if ($actual_price) {
                            $item->add_meta_data('_actual_price', $actual_price);
                        }
                    }
                } else {
                    $item->add_meta_data('Payment Option', 'Full Payment');
                }
            }
        }
    }

    /**
     * Enqueue checkout-specific assets
     */
    public static function enqueue_checkout_assets()
    {
        // Only enqueue on checkout page
        if (!is_checkout()) {
            return;
        }

        // Fix for ReferenceError: $container is not defined in deposits-partial-payments-for-woocommerce
        // This ensures $container is defined in global scope before the plugin's JS runs
        wp_add_inline_script('jquery', 'var $container;');

        // Enqueue checkout-specific CSS
        wp_enqueue_style(
            'senheng-checkout-extras',
            plugin_dir_url(__FILE__) . '../../assets/css/cart/checkout-extra.css',
            array(),
            '1.0.0'
        );

        // Enqueue checkout animation JS for Funnel Builder compatibility
        wp_enqueue_script(
            'senheng-checkout-animation',
            plugin_dir_url(__FILE__) . '../../assets/js/checkout-animation.js',
            array('jquery'),
            '1.0.1',
            true
        );
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
            $variation_product = self::get_product($variation_id);
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

        // Format as HTML with checkout-variation-data class for styling
        $html = '<div class="checkout-variation-data">';
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
     * Modify checkout item subtotal to include product extras pricing
     */
    public static function modify_checkout_item_subtotal($subtotal_html, $cart_item, $cart_item_key)
    {
        $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
        $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
        $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;

        $product = self::get_product($variation_id ? $variation_id : $product_id);
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
        }

        $product_part = $use_deposit ? ($deposit_value * $quantity) : ($base_price * $quantity);

        $extras_total = self::calculate_product_extras_total($cart_item);
        $total_subtotal = $product_part + ($extras_total * $quantity);
        return wc_price($total_subtotal);
    }

    /**
     * Filter cart total on checkout to ensure fees (admin fee) are included
     * and deposit amounts are correctly reflected in the order total.
     * 
     * @param float $total The current cart total
     * @return float The corrected total including fees
     */
    public static function filter_checkout_cart_total($total)
    {
        // Only apply on checkout page 
        if (!function_exists('is_checkout') || !is_checkout()) {
            return $total;
        }

        // Avoid running during admin (non-AJAX)
        if (is_admin() && !defined('DOING_AJAX')) {
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
        if (!$cart || $cart->is_empty()) {
            return $total;
        }

        // Calculate cart data to check for deposits
        $cart_data = self::calculate_cart_data();
        
        // Get fee and shipping totals
        $fee_total = floatval($cart->get_fee_total());
        $shipping_total = floatval($cart->get_shipping_total());
        $tax_total = floatval($cart->get_total_tax());
        // $discount_total = floatval($cart->get_discount_total());

        // If cart has deposits, use the deposit total + extras as the base
        if ($cart_data['has_deposits']) {
            // Custom calculation → coupon NOT included
            $contents_total = $cart_data['deposit_total'] + $cart_data['extras_total'];
            $discount_total = floatval($cart->get_discount_total());
        } else {
            // WooCommerce calculation → coupon ALREADY included
            $contents_total = floatval($cart->get_cart_contents_total());
            $discount_total = 0;
        }

        // Calculate expected total
        $expected_total = $contents_total + $fee_total + $shipping_total + $tax_total - $discount_total;

        // Always return the expected total when we have deposits to ensure correct pricing
        if ($cart_data['has_deposits']) {
            return max(0, $expected_total);
        }

        // Only override if there's a significant difference (fee not included)
        if (abs(floatval($total) - $expected_total) > 0.01 && $fee_total > 0) {
            return $expected_total;
        }

        return $total;
    }

    /**
     * Ensure checkout totals include product extras
     * This method ensures that the checkout page displays the correct total including product extras
     */
    public static function ensure_checkout_totals_include_extras()
    {
        // Prevent infinite loops
        static $processing = false;
        if ($processing || (is_admin() && !defined('DOING_AJAX'))) {
            return;
        }

        // Skip during coupon apply/remove operations to prevent bottleneck with Smart Coupons auto-apply
        if (doing_action('woocommerce_applied_coupon') || 
            doing_action('woocommerce_removed_coupon') ||
            doing_action('woocommerce_coupon_applied') ||
            doing_action('wc_ajax_apply_coupon') ||
            doing_action('wp_ajax_woocommerce_apply_coupon') ||
            doing_action('wp_ajax_nopriv_woocommerce_apply_coupon')) {
            return;
        }

        // Only run on checkout page
        if (!is_checkout()) {
            return;
        }

        // Get cart object
        $cart_object = WC()->cart;
        if (!$cart_object || $cart_object->is_empty()) {
            return;
        }

        $processing = true;

        // Use consolidated cart data calculation (single loop, cached per-request)
        $cart_data = self::calculate_cart_data();
        $base_cart_total = $cart_data['base_cart_total'];
        $total_extras_price = $cart_data['extras_total'];

        // Calculate what the totals should be
        $expected_cart_contents_total = $base_cart_total + $total_extras_price;

        // 1. Update Cart Contents Total (Only if extras exist and mismatch)
        if ($total_extras_price > 0 && abs($cart_object->cart_contents_total - $expected_cart_contents_total) > 0.01) {
            $cart_object->subtotal = $expected_cart_contents_total;
            $cart_object->cart_contents_total = $expected_cart_contents_total;
        }

        // 2. Update Final Total (Always check if components don't sum up to current total)
        // This ensures fees (like admin fee) that might be missed by other overrides are included
        $shipping_total = $cart_object->get_shipping_total();
        $tax_total = $cart_object->get_total_tax();
        $discount_total = $cart_object->get_discount_total();
        $fee_total = method_exists($cart_object, 'get_fee_total') ? $cart_object->get_fee_total() : 0;

        // Use the current cart_contents_total (which might have been updated above or by other plugins)
        $current_contents_total = $cart_object->cart_contents_total;

        $expected_final_total = $current_contents_total + $fee_total + $shipping_total + $tax_total - $discount_total;
        sh_logs('CheckoutController::ensure_checkout_totals_include_extras - Expected Final Total: ' . $expected_final_total . ', Current Total: ' . $cart_object->get_total('edit'));

        if (abs($cart_object->get_total('edit') - $expected_final_total) > 0.01) {
             // Use set_total() to properly update the internal totals array that get_total() reads from
             $cart_object->set_total($expected_final_total);
        }

        $processing = false;
    }

    /**
     * Ensure total is consistent before fragments generation
     * This counters any resets by other plugins (like Funnel Builder)
     * AND overwrites the fragments with the corrected values.
     */
    public static function ensure_fragments_total_consistency($fragments) {
        $cart_object = WC()->cart;
        
        // Use consolidated cart data calculation (single loop, cached per-request)
        $cart_data = self::calculate_cart_data();
        $base_cart_total = $cart_data['base_cart_total'];
        $total_extras_price = $cart_data['extras_total'];
        
        $current_contents_total = $base_cart_total + $total_extras_price;
        
        $shipping_total = $cart_object->get_shipping_total();
        $tax_total = $cart_object->get_total_tax();
        $discount_total = $cart_object->get_discount_total();
        $fee_total = method_exists($cart_object, 'get_fee_total') ? $cart_object->get_fee_total() : 0;

        $calc_contents_total = $current_contents_total;
        $expected_final_total = $calc_contents_total + $fee_total + $shipping_total + $tax_total - $discount_total;
        
        $current_total = $cart_object->get_total('edit');
        
        if (abs($current_total - $expected_final_total) > 0.01) {
             // Use set_total() to properly update the internal totals array
             $cart_object->set_total($expected_final_total);
             if (abs($cart_object->get_cart_contents_total() - $calc_contents_total) > 0.01 && $total_extras_price > 0) {
                 $cart_object->set_cart_contents_total($calc_contents_total);
             }
             
             // FORCE UPDATE FRAGMENTS
             // Funnel Builder keys
             if (isset($fragments['.cart_total'])) {
                 $fragments['.cart_total'] = $cart_object->get_total('edit');
             }
             if (isset($fragments['.wfacp_order_total'])) {
                 // Regenerate the HTML for order total
                 // Assuming standard Woo function is reliable once cart->total is fixed
                 $fragments['.wfacp_order_total'] = wc_cart_totals_order_total_html();
             }

        } else {
             // Even if correct, ensure fragment reflects it (paranoid mode)
             if (isset($fragments['.cart_total']) && $fragments['.cart_total'] != $expected_final_total) {
                  $fragments['.cart_total'] = $expected_final_total;
             }
        }
        
        return $fragments;
    }

    /**
     * Format variation attributes for display in checkout
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

        // Format as HTML with checkout-variation-data class for styling consistency
        $html = '<div class="checkout-variation-data">';
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

    public static function update_ipay88_admin_fee_callback()
    {
        // Check if WooCommerce session is available
        if (!WC()->session) {
            wp_send_json_error(['message' => 'WooCommerce session not found']);
            return;
        }

        $admin_fee = isset($_POST['admin_fee']) ? floatval($_POST['admin_fee']) : 0;
        $payment_plan = isset($_POST['payment_plan']) ? sanitize_text_field($_POST['payment_plan']) : '';
        $months = isset($_POST['months']) ? sanitize_text_field($_POST['months']) : '';

        // Store admin fee and payment plan details in session
        WC()->session->set('ipay88_admin_fee', $admin_fee);
        WC()->session->set('ipay88_payment_plan', $payment_plan);
        WC()->session->set('ipay88_months', $months);

        wp_send_json_success(['admin_fee' => $admin_fee]);
    }

    public static function add_ipay88_admin_fee_to_cart($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $admin_fee = WC()->session->get('ipay88_admin_fee', 0);

        // Only proceed if admin fee exists
        if ($admin_fee > 0) {

            // Get waived brands from DB (cached per-request)
            $waived_brands = self::get_waived_brands();

            // Get cart brands from consolidated cart data (cached per-request)
            $cart_data = self::calculate_cart_data();
            $cart_brands = $cart_data['cart_brands'];

            if (empty($cart_brands)) {
                $cart->add_fee(__('Admin Fee', 'woocommerce'), $admin_fee);
                return;
            }

            $unique_brands = array_unique($cart_brands);

            if (count($unique_brands) === 1 && in_array($unique_brands[0], $waived_brands, true)) {
                // All items same brand and it's waived → skip fee
                $cart->add_fee(__('Admin Fee', 'woocommerce'), $admin_fee);
                $brand = ucfirst($unique_brands[0]);
                // $cart->add_fee(__("{$brand} Fee", 'woocommerce'), -$admin_fee);
                $cart->add_fee(__("Admin Waiver", 'woocommerce'), -$admin_fee);
            } else {
                // Mixed brands → charge normal fee
                $cart->add_fee(__('Admin Fee', 'woocommerce'), $admin_fee);
            }
        }
    }

    public static function display_ipay88_admin_fee_note()
    {
        $admin_fee = WC()->session->get('ipay88_admin_fee', 0);
        if ($admin_fee > 0) {
            echo '<tr class="ipay88-admin-fee-note">';
            echo '<th>' . __('Admin Fee', 'woocommerce') . '</th>';
            echo '<td>' . wc_price($admin_fee) . '</td>';
            echo '</tr>';
        }
    }

    public static function clear_ipay88_admin_fee()
    {
        if (WC()->session) {
            WC()->session->set('ipay88_admin_fee', 0);
            WC()->session->set('ipay88_payment_plan', '');
            WC()->session->set('ipay88_months', '');
        }
    }

    /**
     * Process deposit order after checkout
     */
    public static function process_deposit_order($order_id, $posted_data, $order)
    {
        $has_deposits = false;

        foreach ($order->get_items() as $item_id => $item) {
            $cart_item_key = $item->get_meta('_cart_item_key');
            $cart = WC()->cart->get_cart();

            if (isset($cart[$cart_item_key])) {
                $cart_item = $cart[$cart_item_key];
                $is_deposit = false;

                if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
                    $is_deposit = true;
                } elseif (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                    $is_deposit = true;
                } elseif (isset($cart_item['_final_tradein_price'])) {
                    // Trade-in deposit flow stores final price on cart item
                    $is_deposit = true;
                }

                if ($is_deposit) {
                    $has_deposits = true;

                    // Add deposit metadata to order item (legacy-consistent)
                    $item->add_meta_data('_deposit_option', 'deposit');

                    // Get deposit amount from cart item meta (set by WooCommerceAddtoCartController)
                    $deposit_amount = 0;
                    if (isset($cart_item['deposit_amount']) && is_numeric($cart_item['deposit_amount'])) {
                        // Primary: use calculated deposit amount from cart item
                        $deposit_amount = floatval($cart_item['deposit_amount']);
                        $item->add_meta_data('_deposit_amount', $deposit_amount);
                    } elseif (isset($cart_item['product_extras']['deposit_amount'])) {
                        $deposit_amount = floatval($cart_item['product_extras']['deposit_amount']);
                        $item->add_meta_data('_deposit_amount', $deposit_amount);
                    } else {
                        // Fallback: calculate deposit from product meta
                        $product_id = $cart_item['product_id'] ?? 0;
                        $meta_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
                        $meta_type = get_post_meta($product_id, '_awcdp_deposit_type', true);
                        $product = $item->get_product();
                        if ($product && !empty($meta_amount)) {
                            $base_price = floatval($product->get_price());
                            if ($meta_type === 'percent') {
                                $deposit_amount = $base_price * (floatval($meta_amount) / 100);
                            } else {
                                $deposit_amount = floatval($meta_amount);
                            }
                            $item->add_meta_data('_deposit_amount', $deposit_amount);
                        }
                    }

                    // Calculate and store remaining balance per item
                    // deposit_amount is per-unit, so remaining = (price - deposit) * qty
                    $product = $item->get_product();
                    if ($product && $deposit_amount > 0) {
                        $quantity = $item->get_quantity();
                        $unit_price = floatval($product->get_price());
                        // Remaining balance = (full unit price - deposit per unit) * quantity
                        $remaining_per_unit = max(0, $unit_price - $deposit_amount);
                        $total_remaining = $remaining_per_unit * $quantity;
                        $item->add_meta_data('_remaining_balance', $total_remaining);
                    }

                    if (isset($cart_item['deposit_percentage'])) {
                        $item->add_meta_data('_deposit_percentage', $cart_item['deposit_percentage']);
                    }
                    $item->save();
                }
            }
        }

        if ($has_deposits) {
            // Mark order as partial payment
            $order->update_meta_data('_has_deposit', 'yes');
            $order->update_meta_data('_deposit_paid', 'no');
            $order->save();
        }
    }

    /**
     * Output Remaining Balance as a fee-like row in the checkout totals table.
     * This displays inside the WFACP order summary container and updates with fragments.
     */
    public static function render_remaining_balance_row()
    {
        $totals = self::calculate_deposit_totals();
        if (!$totals['has_deposits']) {
            return;
        }

        // Render as a totals table row (does not affect totals)
        // Wrap in <span> tags for WFACP shimmer animation compatibility
        echo '<tr class="fee remaining-balance-row">'
            . '<th><span>' . esc_html__('Remaining Balance', 'senheng-core') . '</span></th>'
            . '<td><span class="woocommerce-Price-amount amount">' . wc_price($totals['remaining_amount']) . '</span></td>'
            . '</tr>';
    }

    /**
     * Output Deposit Payment (Due Today) as a fee-like row in the checkout totals table.
     * This displays inside the WFACP order summary container and updates with fragments.
     */
    public static function render_deposit_payment_row()
    {
        $totals = self::calculate_deposit_totals();
        if (!$totals['has_deposits']) {
            return;
        }

        // Wrap in <span> tags for WFACP shimmer animation compatibility
        echo '<tr class="fee deposit-payment-row order-paid">'
            . '<th><span>' . esc_html__('Deposit Payment', 'senheng-core') . '</span></th>'
            . '<td><span class="woocommerce-Price-amount amount">' . wc_price($totals['deposit_total']) . '</span></td>'
            . '</tr>';
    }

    /**
     * Calculate deposit totals using original product prices (regular price) to avoid
     * interference from deposit-modified cart item prices.
     *
     * @return array{has_deposits:bool, deposit_total:float, full_total:float, remaining_amount:float}
     */
    private static function calculate_deposit_totals()
    {
        // Use consolidated cart data calculation (already has deposit data)
        $cart_data = self::calculate_cart_data();

        return [
            'has_deposits' => $cart_data['has_deposits'],
            'deposit_total' => $cart_data['deposit_total'],
            'full_total' => $cart_data['full_total'],
            'remaining_amount' => $cart_data['remaining_amount'],
        ];
    }

    /**
     * Modify order item name for deposits
     */
    public static function checkout_order_item_name($name, $item, $is_visible)
    {
        if ($item->get_meta('_deposit_option') === 'deposit') {
            $name .= ' <small>(' . __('Deposit Payment', 'senheng-core') . ')</small>';
        }
        return $name;
    }

    /**
     * Filter payment gateways for deposit orders
     */
    public static function filter_payment_gateways_for_deposits($gateways)
    {
        $cart = WC()->cart;
        $has_deposits = false;

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                $has_deposits = true;
                break;
            }
        }

        if ($has_deposits) {
            // Allow specific payment methods for deposits
            $allowed_gateways = apply_filters('senheng_deposit_allowed_gateways', ['bacs', 'cheque', 'cod', 'ipay88']);

            foreach ($gateways as $gateway_id => $gateway) {
                if (!in_array($gateway_id, $allowed_gateways)) {
                    unset($gateways[$gateway_id]);
                }
            }
        }

        return $gateways;
    }
    
    /**
     * Render a custom shop table under the order review that shows
     * per-item product extras, trade-in selection, and deposit details.
     * Hooked at: woocommerce_review_order_after_cart_contents
     */
    public static function render_custom_checkout_cart_table()
    {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        // Output inside a single table row to keep markup valid under Woo table structure
        echo '<tr class="sh-custom-shop-table-row"><td colspan="100%">';
        // Add hidden container to satisfy deposits-partial-payments-for-woocommerce JS
        echo '<div class="awcdp-deposits-wrapper" style="display:none;"></div>';
        echo '<div class="sh-custom-shop-table">';

        $item_number = 0;
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $item_number++;
            $product_id   = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
            $variation_id = isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;
            $product_obj  = self::get_product($variation_id ? $variation_id : $product_id);
            $qty          = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

            if (!$product_obj) continue;

            $product_name = $product_obj->get_name();
            $product_image = $product_obj->get_image('thumbnail');
            
            // Calculate price (actual/base)
            $base_price = $product_obj->get_price();
            $line_total = floatval($base_price) * $qty;

            // Calculate deposit subtotal when applicable
            $is_deposit = false;
            $deposit_subtotal = null;
            if (isset($cart_item['deposit_amount']) && is_numeric($cart_item['deposit_amount'])) {
                $is_deposit = true;
                $deposit_subtotal = floatval($cart_item['deposit_amount']) * $qty;
            } elseif (isset($cart_item['awcdp_deposit'], $cart_item['awcdp_deposit']['enable']) && $cart_item['awcdp_deposit']['enable'] == 1 && isset($cart_item['awcdp_deposit']['deposit'])) {
                $is_deposit = true;
                $deposit_subtotal = floatval($cart_item['awcdp_deposit']['deposit']) * $qty;
            } elseif ((isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') || (isset($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit')) {
                $is_deposit = true;
                $meta_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
                $meta_type = get_post_meta($product_id, '_awcdp_deposit_type', true);
                if (!empty($meta_amount)) {
                    if ($meta_type === 'percent') {
                        $deposit_subtotal = floatval($base_price) * (floatval($meta_amount) / 100) * $qty;
                    } else {
                        $deposit_subtotal = floatval($meta_amount) * $qty;
                    }
                } else {
                    $deposit_subtotal = floatval($base_price) * $qty;
                }
            }

            echo '<div class="sh-checkout-cart-item">';
            
            // Remove button
            //echo '<button class="sh-remove-item" onclick="location.href=\'' . esc_url(wc_get_cart_remove_url($cart_item_key)) . '\'" title="' . esc_attr__('Remove', 'senheng-core') . '">×</button>';
            
            // Header section with image and details
            echo '<div class="sh-cart-item-header">';
            echo '<div class="sh-item-image-wrapper">';
            echo '<div class="sh-item-image">' . $product_image . '</div>';
            echo '<div class="sh-item-qty-badge">' . $qty . '</div>';
            echo '</div>';
            
            echo '<div class="sh-item-details">';
            echo '<h4 class="sh-item-name">' . esc_html($product_name) . '</h4>';
            
            // Meta information
            echo '<div class="sh-item-meta" data-cart-key="' . esc_attr($cart_item['key']) . '">';
            
            // Show variations
            $variation_html = self::get_formatted_variation_data($cart_item);
            if (!empty($variation_html)) {
                echo '<div>' . wp_kses_post(strip_tags($variation_html, '<br><strong><em>')) . '</div>';
            }
            
            // Trade-in status
            $trade_in_raw = isset($cart_item['trade_in']) ? $cart_item['trade_in'] : '';
            if ($trade_in_raw === 'yes' || $trade_in_raw === 'no') {
                $trade_in_text = ($trade_in_raw === 'yes') ? __('Yes', 'senheng-core') : __('No', 'senheng-core');
                echo '<div><strong>' . esc_html__('Trade In:', 'senheng-core') . '</strong> ' . esc_html($trade_in_text) . '</div>';
            }
            
            // Payment type and details
            $is_deposit = false;
            if (isset($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] !== '') {
                $is_deposit = ($cart_item['awcdp_deposit_option'] === 'yes');
            } elseif (isset($cart_item['deposit_option'])) {
                $is_deposit = ($cart_item['deposit_option'] === 'deposit');
            }
            
            if ($is_deposit) {
                echo '<div><strong>' . esc_html__('Deposit Payment', 'senheng-core') . '</strong></div>';
                echo '<div><strong>' . esc_html__('Actual Price:', 'senheng-core') . '</strong> ' . wc_price($line_total) . '</div>';
            }
            
            echo '</div>'; // .sh-item-meta
            echo '</div>'; // .sh-item-details
            
            echo '<div class="sh-item-price">' . wc_price(($is_deposit && $deposit_subtotal !== null) ? $deposit_subtotal : $line_total) . '</div>';
            echo '</div>'; // .sh-cart-item-header

            // Check if there are any added extras with "warranty" or similar
            $has_warranty_added = false;
            if (!empty($cart_item['product_extras']['selected_products'])) {
                foreach ($cart_item['product_extras']['selected_products'] as $extra) {
                    if (!empty($extra['productLabel']) && (stripos($extra['productLabel'], 'warranty') !== false || stripos($extra['productLabel'], 'product') !== false)) {
                        echo '<div class="sh-added-badge">ADDED: ' . esc_html($extra['productLabel']) . '</div>';
                        $has_warranty_added = true;
                        break;
                    }
                }
            }

            // Product extras section
            if (!empty($cart_item['product_extras'])) {
                echo '<div class="sh-extras-section">';
                echo self::render_checkout_extras_enhanced($cart_item);
                echo '</div>';
            }

            echo '</div>'; // .sh-checkout-cart-item
        }

        echo '</div>'; // .sh-custom-shop-table
        echo '</td></tr>';
    }

    /**
     * Enhanced extras renderer matching the exact design
     */
    private static function render_checkout_extras_enhanced($cart_item)
    {
        if (empty($cart_item['product_extras'])) {
            return '';
        }
        
        $product_extras = $cart_item['product_extras'];
        $out = '';

        // Selected information (free items with original price shown)
        if (!empty($product_extras['selected_info']) && is_array($product_extras['selected_info'])) {
            $label_info = !empty($product_extras['selected_info'][0]['fieldLabel']) ? $product_extras['selected_info'][0]['fieldLabel'] : __('Gift', 'senheng-core');
            $out .= '<div class="sh-extras-header">' . esc_html($label_info) . '</div>';
            foreach ($product_extras['selected_info'] as $info_data) {
                $info_label = isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '';
                
                $original = null;
                if (isset($info_data['infoOriginalPrice']) && is_numeric($info_data['infoOriginalPrice'])) {
                    $original = floatval($info_data['infoOriginalPrice']);
                } elseif (isset($info_data['originalPrice']) && is_numeric($info_data['originalPrice'])) {
                    $original = floatval($info_data['originalPrice']);
                } elseif (isset($info_data['original']) && is_numeric($info_data['original'])) {
                    $original = floatval($info_data['original']);
                }
                
                if (!empty($info_label)) {
                    $out .= '<div class="sh-extras-item">';
                    $out .= '<div class="sh-extras-image">';
                    $info_image = isset($info_data['imageUrl']) ? $info_data['imageUrl'] : '';
                    if (!empty($info_image) && $info_image !== wc_placeholder_img_src('woocommerce_thumbnail')) {
                        $out .= '<img src="' . esc_url($info_image) . '" alt="' . esc_attr($info_label) . '">';
                    } else {
                        $out .= '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($info_label) . '">';
                    }
                    $out .= '</div>';
                    $out .= '<div class="sh-extras-content">';
                    $out .= '<div class="sh-extras-title">' . esc_html($info_label) . '</div>';
                    $out .= '</div>';
                    
                    $out .= '<div class="sh-extras-pricing">';
                    $out .= '<span class="sh-extras-price">' . wc_price(0) . '</span>';
                    
                    if (!is_null($original) && $original > 0) {
                        $out .= '<span class="sh-price-original">' . wc_price($original) . '</span>';
                    }
                    
                    $out .= '<span class="sh-extras-qty">Qty: 1</span>';
                    $out .= '</div>';
                    
                    $out .= '</div>';
                }
            }
        }

        // Selected products (add-ons with price)
        if (!empty($product_extras['selected_products']) && is_array($product_extras['selected_products'])) {
            $label = !empty($product_extras['selected_products'][0]['fieldLabel']) ? $product_extras['selected_products'][0]['fieldLabel'] : __('Add on Deal', 'senheng-core');
            
            $out .= '<div class="sh-extras-header">' . esc_html($label) . '</div>';

            foreach ($product_extras['selected_products'] as $extra_product_data) {
                $name = isset($extra_product_data['title']) ? $extra_product_data['title'] : (isset($extra_product_data['product_cart_label']) ? $extra_product_data['product_cart_label'] : (isset($extra_product_data['productLabel']) ? $extra_product_data['productLabel'] : ''));
                $qty  = isset($extra_product_data['quantity']) ? intval($extra_product_data['quantity']) : 1;
                $price_unit = 0.0;
                if (isset($extra_product_data['price']) && is_numeric($extra_product_data['price'])) {
                    $price_unit = floatval($extra_product_data['price']);
                } elseif (isset($extra_product_data['productPrice']) && is_numeric($extra_product_data['productPrice'])) {
                    $price_unit = floatval($extra_product_data['productPrice']);
                }

                // Apply discount if available
                $child_discount = isset($extra_product_data['childDiscount']) ? floatval($extra_product_data['childDiscount']) : 0;
                $discount_type = isset($extra_product_data['discountType']) ? $extra_product_data['discountType'] : '';
                
                if ($price_unit > 0 && $child_discount > 0) {
                     if ($discount_type === 'percent') {
                         $price_unit = $price_unit - ($price_unit * ($child_discount / 100));
                     } elseif ($discount_type === 'fixed') {
                         $price_unit = max(0, $price_unit - $child_discount);
                     }
                }
                
                $original_unit = null;
                if (isset($extra_product_data['originalPrice']) && is_numeric($extra_product_data['originalPrice'])) {
                    $original_unit = floatval($extra_product_data['originalPrice']);
                } elseif (isset($extra_product_data['original']) && is_numeric($extra_product_data['original'])) {
                    $original_unit = floatval($extra_product_data['original']);
                }
                
                $total_price = $price_unit * max($qty, 1);
                $total_original = !is_null($original_unit) ? ($original_unit * max($qty, 1)) : null;

                if (!empty($name)) {
                    $out .= '<div class="sh-extras-item">';
                    $out .= '<div class="sh-extras-image">';
                    if (!empty($extra_product_data['productId'])) {
                        $extra_product = self::get_product($extra_product_data['productId']);
                        if ($extra_product) {
                            $out .= $extra_product->get_image('thumbnail');
                        }
                    }
                    $out .= '</div>';
                    
                    $out .= '<div class="sh-extras-content">';
                    $out .= '<div class="sh-extras-title">' . esc_html($name) . '</div>';
                    $out .= '</div>';
                    
                    $out .= '<div class="sh-extras-pricing">';
                    $out .= '<span class="sh-extras-price">' . wc_price($total_price) . '</span>';
                    
                    if (!is_null($total_original) && $total_original > $total_price) {
                        $out .= '<span class="sh-price-original">' . wc_price($total_original) . '</span>';
                    }
                    
                    $out .= '<span class="sh-extras-qty">Qty: ' . intval($qty) . '</span>';
                    $out .= '</div>';
                    
                    $out .= '</div>';
                }
            }
        }

        return $out;
    }

    /**
     * Helper to log to browser console
     */
    public static function console_log($data = null, $label = '') {
        // Collect logs in a static array
        static $logs = [];
        // If data is provided, add to log
        if ($data !== null) {
            $logs[] = ['label' => $label, 'data' => $data];
        }
        // Always return logs (for retrieval)
        return $logs;
    }

    /**
     * Render collected logs as script tag
     */
    public static function render_debug_logs() {
        // Retrieve logs without adding new one
        $logs = self::console_log(null); 
        
        if (empty($logs)) return;

        echo '<script>';
        foreach ($logs as $log) {
            $json = json_encode($log['data']);
            $label = $log['label'] ? "{$log['label']}: " : '';
            // Sanitize script output to avoid breaking JS
            echo "console.log('PHP DEBUG: " . esc_js($label) . "', " . $json . ");";
        }
        echo '</script>';
    }
}

