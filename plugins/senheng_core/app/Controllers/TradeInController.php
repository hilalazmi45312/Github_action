<?php

class TradeInController
{
    const CATEGORY_DISCOUNT_KEY = 'senheng_trade_in_discount';

    /**
     * Initialize hooks
     */
    public static function init()
    {
        // Add fields to product category forms
        add_action('product_cat_add_form_fields', [self::class, 'add_category_fields']);
        add_action('product_cat_edit_form_fields', [self::class, 'edit_category_fields']);

        // Save fields
        add_action('created_product_cat', [self::class, 'save_category_fields']);
        add_action('edited_product_cat', [self::class, 'save_category_fields']);

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets()
    {
        // Only enqueue on product category pages
        $screen = get_current_screen();
        if ($screen && $screen->taxonomy === 'product_cat') {
            wp_enqueue_style(
                'sh-trade-in-admin',
                plugin_dir_url(__FILE__) . '../../assets/css/trade-in/admin.css',
                array(),
                '1.0.0'
            );
        }
    }

    /**
     * Add field to category add screen
     */
    public static function add_category_fields()
    {
        ?>
        <div class="form-field term-trade-in-discount-wrap">
            <label for="trade_in_discount"><?php _e('Trade-In Discount (RM)', 'senheng-core'); ?></label>
            <input type="number" name="trade_in_discount" id="trade_in_discount" value="" step="0.01" min="0" class="sh-trade-in-input">
            <p class="description"><?php _e('Enter the trade-in discount amount for this category. This will be deducted from the product price when Trade-In is selected.', 'senheng-core'); ?></p>
        </div>
        <?php
    }

    /**
     * Add field to category edit screen
     */
    public static function edit_category_fields($term)
    {
        $discount = get_term_meta($term->term_id, self::CATEGORY_DISCOUNT_KEY, true);
        ?>
        <tr class="form-field term-trade-in-discount-wrap">
            <th scope="row"><label for="trade_in_discount"><?php _e('Trade-In Discount (RM)', 'senheng-core'); ?></label></th>
            <td>
                <input type="number" name="trade_in_discount" id="trade_in_discount" value="<?php echo esc_attr($discount); ?>" step="0.01" min="0" class="sh-trade-in-input">
                <p class="description"><?php _e('Enter the trade-in discount amount for this category. This will be deducted from the product price when Trade-In is selected.', 'senheng-core'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save category fields
     */
    public static function save_category_fields($term_id)
    {
        if (isset($_POST['trade_in_discount'])) {
            update_term_meta($term_id, self::CATEGORY_DISCOUNT_KEY, sanitize_text_field($_POST['trade_in_discount']));
        }
    }

    /**
     * Get trade-in discount for a product
     * 
     * @param int $product_id
     * @return float
     */
    public static function get_trade_in_discount($product_id)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return 0.0;
        }

        // If it's a variation, switch to parent ID
        if ($product->is_type('variation')) {
            $product_id = $product->get_parent_id();
        }

        // Use wc_get_product_term_ids for robustness
        $term_ids = wc_get_product_term_ids($product_id, 'product_cat');
        
        if (empty($term_ids)) {
            return 0.0;
        }

        $max_discount = 0.0;
        foreach ($term_ids as $term_id) {
            $discount = get_term_meta($term_id, self::CATEGORY_DISCOUNT_KEY, true);
            if ($discount !== '' && is_numeric($discount)) {
                $max_discount = max($max_discount, floatval($discount));
            }
        }
        return $max_discount;
    }
}