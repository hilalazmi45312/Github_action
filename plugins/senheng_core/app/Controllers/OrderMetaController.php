<?php

class OrderMetaController
{
    /**
     * Initialize order meta hooks
     */
    public static function init()
    {
        // Rename meta keys in admin order view
        add_filter('woocommerce_order_item_display_meta_key', [self::class, 'rename_order_item_meta_keys'], 10, 3);

        // Display product extras as inner product child
        add_action('woocommerce_after_order_itemmeta', [self::class, 'display_product_extras_in_admin'], 10, 3);
        
        // Hide the raw JSON meta data for product extras
        add_filter('woocommerce_order_item_get_formatted_meta_data', [self::class, 'hide_product_extras_raw_meta'], 10, 2);
    }

    /**
     * Rename specific meta keys for better readability
     */
    public static function rename_order_item_meta_keys($display_key, $meta, $item)
    {
        // Payment Option
        if ($meta->key === '_deposit_option' || $meta->key === 'payment_option') {
            return __('Payment Option', 'senheng-core');
        }

        // Trade In
        if ($meta->key === '_trade_in_option' || $meta->key === 'trade_in') {
            return __('Trade In', 'senheng-core');
        }

        // Warranty Selected
        if ($meta->key === 'warranty_selected' || $meta->key === '_warranty_selected') {
            return __('Warranty Selected', 'senheng-core');
        }

        // S-Coin Value
        if ($meta->key === '_s_coin_value' || $meta->key === 's_coin_value') {
            return __('S-Coin Earned', 'senheng-core');
        }

        // Deposit Amount
        if ($meta->key === '_deposit_amount' || $meta->key === 'deposit_amount') {
            return __('Deposit Amount', 'senheng-core');
        }

        // Remaining Balance
        if ($meta->key === '_remaining_balance' || $meta->key === 'remaining_balance') {
            return __('Remaining Balance', 'senheng-core');
        }

        return $display_key;
    }

    /**
     * Hide raw product extra meta data from default display
     */
    public static function hide_product_extras_raw_meta($formatted_meta, $item)
    {
        $hidden_keys = [
            '_product_extras_products',
            '_product_extras_info',
            '_product_extra_', // partial match logic needed if we want to hide these too, but loop below handles exact keys
            '_trade_in_option', // Hide raw key, keep 'Trade In'
            '_deposit_option',  // Hide raw key, keep 'Payment Option'
            '_actual_price',    // Hide raw key, we display it formatted in display_product_extras_in_admin
        ];

        $filtered_meta = [];
        foreach ($formatted_meta as $key => $meta) {
            if (in_array($meta->key, $hidden_keys)) {
                continue;
            }
            // Hide individual numbered extras if we are displaying them nicely
            if (strpos($meta->key, '_product_extra_') === 0 || strpos($meta->key, '_product_info_') === 0) {
                continue;
            }
            $filtered_meta[$key] = $meta;
        }

        return $filtered_meta;
    }

    /**
     * Display product extras as inner items in admin order view
     */
    public static function display_product_extras_in_admin($item_id, $item, $product)
    {
        // Only for line items
        if ($item->get_type() !== 'line_item') {
            return;
        }

        // Display Actual Price for Deposit Orders
        $deposit_option = $item->get_meta('_deposit_option');
        if ($deposit_option === 'deposit') {
            // First, try to get the actual price saved at order time
            $actual_price = $item->get_meta('_actual_price');
            
            // Fallback to product's regular price if _actual_price not saved
            if (empty($actual_price)) {
                $product_obj = $item->get_product();
                if ($product_obj) {
                    $actual_price = $product_obj->get_regular_price();
                }
            }
            
            if (!empty($actual_price)) {
                echo '<div class="sh-actual-price" style="margin-top:5px; font-size:0.9em;">';
                echo '<strong>' . __('Actual Price:', 'senheng-core') . '</strong> ' . wc_price($actual_price);
                echo '</div>';
            }
        }

        // Retrieve the stored extras
        $extras_products = $item->get_meta('_product_extras_products');
        $extras_info = $item->get_meta('_product_extras_info');

        if (empty($extras_products) && empty($extras_info)) {
            return;
        }

        echo '<div class="sh-order-extras" style="margin-top: 10px; border-top: 1px dashed #eee; padding-top: 10px;">';

        // Display Selected Products grouped by field label
        if (!empty($extras_products) && is_array($extras_products)) {
            // Group products by field label
            $grouped_products = [];
            foreach ($extras_products as $extra) {
                $field_label = isset($extra['fieldLabel']) ? $extra['fieldLabel'] : (isset($extra['field_label']) ? $extra['field_label'] : '');
                if (empty($field_label)) {
                    $field_label = __('Product Extras', 'senheng-core');
                }
                if (!isset($grouped_products[$field_label])) {
                    $grouped_products[$field_label] = [];
                }
                $grouped_products[$field_label][] = $extra;
            }

            foreach ($grouped_products as $field_label => $products) {
                // Display field label similar to ProductExtrasWidget
                echo '<h4 class="sh-product-extras-field-label" style="font-size: 13px; font-weight: 600; margin: 10px 0 5px 0; color: #333;">' . esc_html($field_label) . '</h4>';
                
                foreach ($products as $extra) {
                    $title = isset($extra['title']) ? $extra['title'] : __('Product Extra', 'senheng-core');
                    $price = isset($extra['price']) ? $extra['price'] : 0;
                    $qty = isset($extra['quantity']) ? $extra['quantity'] : 1;
                    $image_url = '';
                    
                    // Try to get image from variation or product ID
                    $p_id = isset($extra['productId']) ? $extra['productId'] : 0;
                    $v_id = isset($extra['variationId']) ? $extra['variationId'] : 0;
                    $p_obj = $v_id ? wc_get_product($v_id) : ($p_id ? wc_get_product($p_id) : null);
                    
                    if ($p_obj) {
                        $image_id = $p_obj->get_image_id();
                        if ($image_id) {
                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                        }
                    }

                    if (empty($image_url)) {
                        $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                    }

                    self::render_extra_item_row($title, $image_url, $price, $qty);
                }
            }
        }

        // Display Selected Info grouped by field label
        if (!empty($extras_info) && is_array($extras_info)) {
            // Group info by field label
            $grouped_info = [];
            foreach ($extras_info as $info) {
                $field_label = isset($info['fieldLabel']) ? $info['fieldLabel'] : (isset($info['field_label']) ? $info['field_label'] : '');
                if (empty($field_label)) {
                    $field_label = __('Extra Info', 'senheng-core');
                }
                if (!isset($grouped_info[$field_label])) {
                    $grouped_info[$field_label] = [];
                }
                $grouped_info[$field_label][] = $info;
            }

            foreach ($grouped_info as $field_label => $info_items) {
                // Display field label similar to ProductExtrasWidget
                echo '<h4 class="sh-product-extras-field-label" style="font-size: 13px; font-weight: 600; margin: 10px 0 5px 0; color: #333;">' . esc_html($field_label) . '</h4>';
                
                foreach ($info_items as $info) {
                    $label = isset($info['infoLabel']) ? $info['infoLabel'] : __('Extra Info', 'senheng-core');
                    $price = isset($info['infoPrice']) ? $info['infoPrice'] : 0;
                    $image_url = isset($info['imageUrl']) ? $info['imageUrl'] : '';
                    
                    if (empty($image_url)) {
                        $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                    }

                    self::render_extra_item_row($label, $image_url, $price, 1, true);
                }
            }
        }

        echo '</div>';
    }

    private static function render_extra_item_row($name, $image, $price, $qty, $is_info = false)
    {
        // Format price
        $price_html = '';
        if (is_numeric($price)) {
            $price_html = wc_price($price);
        } else {
            $price_html = esc_html($price); // Handle string prices like "RM 10.00"
        }
        ?>
        <div class="sh-extra-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px; font-size: 0.9em;">
            <div class="sh-extra-img" style="width: 40px; height: 40px; flex-shrink: 0;">
                <img src="<?php echo esc_url($image); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
            </div>
            <div class="sh-extra-details" style="flex-grow: 1;">
                <div class="sh-extra-name"><?php echo esc_html($name); ?></div>
                <div class="sh-extra-meta" style="color: #777; font-size: 0.85em;">
                    <?php echo $price_html; ?>
                    <?php if (!$is_info && $qty > 1): ?>
                         &times; <?php echo intval($qty); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
