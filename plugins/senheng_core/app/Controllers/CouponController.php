<?php

/**
 * Coupon Controller
 *
 * Extends WooCommerce Smart Coupons with additional restrictions:
 * - Payment Type: Full Payment Only / Deposit Payment Only
 * 
 * Works with the TradeInWidget deposit/full payment selection.
 */
class CouponController
{
    /**
     * Meta key for payment type restriction
     */
    const META_PAYMENT_TYPE = 'senheng_coupon_payment_type';

    /**
     * Initialize the controller
     */
    public static function init()
    {
        // Add field to coupon editor (Usage restriction tab)
        add_action('woocommerce_coupon_options_usage_restriction', [self::class, 'add_payment_type_field'], 15, 2);
        
        // Save the field
        add_action('woocommerce_coupon_options_save', [self::class, 'save_payment_type_field'], 10, 2);
        
        // Validate coupon based on payment type
        add_filter('woocommerce_coupon_is_valid', [self::class, 'validate_coupon_payment_type'], 12, 3);
        
        // Add export support (for Smart Coupons CSV export)
        add_filter('wc_smart_coupons_export_headers', [self::class, 'add_export_header']);
        add_filter('wc_sc_export_coupon_meta', [self::class, 'export_coupon_meta'], 10, 2);
        
        // Add import support
        add_filter('smart_coupons_parser_postmeta_defaults', [self::class, 'import_postmeta_defaults']);
        add_filter('sc_generate_coupon_meta', [self::class, 'generate_coupon_meta'], 10, 2);
    }

    /**
     * Add Payment Type field to coupon editor
     *
     * @param int $coupon_id The coupon post ID
     * @param WC_Coupon $coupon The coupon object
     */
    public static function add_payment_type_field($coupon_id = 0, $coupon = null)
    {
        $payment_type = '';
        if (!empty($coupon_id)) {
            $payment_type = get_post_meta($coupon_id, self::META_PAYMENT_TYPE, true);
        }
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="<?php echo esc_attr(self::META_PAYMENT_TYPE); ?>">
                    <?php esc_html_e('Payment Type', 'senheng-core'); ?>
                </label>
                <select id="<?php echo esc_attr(self::META_PAYMENT_TYPE); ?>" 
                        name="<?php echo esc_attr(self::META_PAYMENT_TYPE); ?>" 
                        style="width: 50%;" 
                        class="wc-enhanced-select"
                        data-placeholder="<?php esc_attr_e('No payment type restriction', 'senheng-core'); ?>">
                    <option value=""></option>
                    <option value="full" <?php selected($payment_type, 'full'); ?>>
                        <?php esc_html_e('Full Payment Only', 'senheng-core'); ?>
                    </option>
                    <option value="deposit" <?php selected($payment_type, 'deposit'); ?>>
                        <?php esc_html_e('Deposit Payment Only', 'senheng-core'); ?>
                    </option>
                </select>
                <?php 
                $tooltip = esc_html__('Restrict this coupon to be valid only for full payment or deposit payment orders.', 'senheng-core');
                echo wc_help_tip($tooltip);
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save Payment Type field
     *
     * @param int $post_id The coupon post ID
     * @param WC_Coupon $coupon The coupon object
     */
    public static function save_payment_type_field($post_id = 0, $coupon = null)
    {
        if (empty($post_id)) {
            return;
        }

        $payment_type = isset($_POST[self::META_PAYMENT_TYPE]) 
            ? sanitize_text_field(wp_unslash($_POST[self::META_PAYMENT_TYPE])) 
            : '';

        update_post_meta($post_id, self::META_PAYMENT_TYPE, $payment_type);
    }

    /**
     * Validate coupon based on payment type restriction
     *
     * @param bool $valid Is valid or not
     * @param WC_Coupon $coupon The coupon object
     * @param WC_Discounts $discounts The discount object
     * @return bool
     * @throws Exception If validation fails
     */
    public static function validate_coupon_payment_type($valid = false, $coupon = null, $discounts = null)
    {
        // If already invalid, skip
        if (!$valid) {
            return $valid;
        }

        if (!$coupon instanceof WC_Coupon) {
            return $valid;
        }

        $coupon_id = $coupon->get_id();
        if (empty($coupon_id)) {
            return $valid;
        }

        // Get the payment type restriction
        $required_payment_type = get_post_meta($coupon_id, self::META_PAYMENT_TYPE, true);
        
        // If no restriction, coupon is valid
        if (empty($required_payment_type)) {
            return $valid;
        }

        // Check if cart has deposit payments
        $has_deposit = self::cart_has_deposit_payment();
        $coupon_code = $coupon->get_code();

        // Validate based on required payment type
        if ($required_payment_type === 'deposit' && !$has_deposit) {
            throw new Exception(
                sprintf(
                    /* translators: %s: coupon code */
                    __('Coupon "%s" is only valid for deposit payment orders. Please select deposit payment to use this coupon.', 'senheng-core'),
                    $coupon_code
                )
            );
        }

        if ($required_payment_type === 'full' && $has_deposit) {
            throw new Exception(
                sprintf(
                    /* translators: %s: coupon code */
                    __('Coupon "%s" is only valid for full payment orders. Please select full payment to use this coupon.', 'senheng-core'),
                    $coupon_code
                )
            );
        }

        return $valid;
    }

    /**
     * Check if cart has any deposit payment items
     *
     * @return bool True if any cart item is a deposit payment
     */
    public static function cart_has_deposit_payment()
    {
        if (!WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            // Check for AWCDP deposit plugin format
            if (!empty($cart_item['awcdp_deposit_option']) && $cart_item['awcdp_deposit_option'] === 'yes') {
                return true;
            }
            
            // Check for custom deposit format
            if (!empty($cart_item['deposit_option']) && $cart_item['deposit_option'] === 'deposit') {
                return true;
            }

            // Check for WooCommerce Deposits plugin format
            if (!empty($cart_item['deposit']) && isset($cart_item['deposit']['enable']) && $cart_item['deposit']['enable'] === 'yes') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if cart has only full payment items (no deposits)
     *
     * @return bool True if all cart items are full payment
     */
    public static function cart_has_full_payment()
    {
        return !self::cart_has_deposit_payment();
    }

    /**
     * Add export header for Smart Coupons
     *
     * @param array $headers Existing headers
     * @return array Modified headers
     */
    public static function add_export_header($headers = [])
    {
        $headers[self::META_PAYMENT_TYPE] = __('Payment Type (Senheng)', 'senheng-core');
        return $headers;
    }

    /**
     * Export coupon meta data
     *
     * @param mixed $meta_value The meta value
     * @param array $args Additional arguments
     * @return mixed Processed meta value
     */
    public static function export_coupon_meta($meta_value = '', $args = [])
    {
        if (!empty($args['meta_key']) && self::META_PAYMENT_TYPE === $args['meta_key']) {
            if (isset($args['meta_value']) && !empty($args['meta_value'])) {
                $meta_value = $args['meta_value'];
            }
        }
        return $meta_value;
    }

    /**
     * Add default value for import
     *
     * @param array $defaults Existing defaults
     * @return array Modified defaults
     */
    public static function import_postmeta_defaults($defaults = [])
    {
        $defaults[self::META_PAYMENT_TYPE] = '';
        return $defaults;
    }

    /**
     * Generate coupon meta for import
     *
     * @param array $data The row data
     * @param array $post The POST values
     * @return array Modified data
     */
    public static function generate_coupon_meta($data = [], $post = [])
    {
        $payment_type = '';
        
        if (!empty($post[self::META_PAYMENT_TYPE])) {
            $payment_type = sanitize_text_field($post[self::META_PAYMENT_TYPE]);
        }

        $data[self::META_PAYMENT_TYPE] = $payment_type;
        return $data;
    }

    /**
     * Get human-readable payment type label
     *
     * @param string $type The payment type (full, deposit, or empty)
     * @return string Human-readable label
     */
    public static function get_payment_type_label($type)
    {
        $labels = [
            '' => __('Any', 'senheng-core'),
            'full' => __('Full Payment Only', 'senheng-core'),
            'deposit' => __('Deposit Payment Only', 'senheng-core'),
        ];

        return isset($labels[$type]) ? $labels[$type] : $labels[''];
    }
}
