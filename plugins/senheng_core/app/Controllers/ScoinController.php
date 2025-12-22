<?php

class ScoinController
{
    public static function sh_enqueue_s_coin_assets()
    {
        // Legacy function kept for product loop display only
        // Single product S-coin display is now handled by the S-Coin Label Widget
        
        // Load on all pages where product loops might appear (shop, category, tag, homepage, etc.)
        // This ensures S-coin styling works with Elementor product widgets and other product displays

        wp_enqueue_style(
            's-coin-style',
            SENHENG_CORE_URL . 'assets/css/s-coin-placement.css'
        );

        wp_enqueue_script(
            's-coin-js',
            SENHENG_CORE_URL . 'assets/js/s-coin-placement.js',
            ['jquery'],
            null,
            true
        );

        // Minimal data for product loop display
        $icon_url = self::get_scoin_icon_url();

        wp_localize_script(
            's-coin-js',
            'sCoinData',
            [
                'icon_url' => $icon_url,
            ]
        );
    }

    /**
     * Get S-coin icon URL with fallback logic
     */
    public static function get_scoin_icon_url()
    {
        $default = plugins_url('senheng_core/assets/uploads/s-coin-label.png');
        $relative = '/assets/uploads/s-coin-label.png';

        // Resolve plugin base dir and url
        $base_dir = defined('SENHENG_CORE_PATH') ? rtrim(SENHENG_CORE_PATH, '/').'/' : rtrim(dirname(__DIR__, 1), '/').'/'; 
        $base_url = defined('SENHENG_CORE_URL') ? rtrim(SENHENG_CORE_URL, '/').'/' : plugins_url('/', dirname(__DIR__, 2));

        $path = $base_dir . $relative;
        $url  = $base_url . $relative;

        if (file_exists($path)) {
            return $url;
        }
        return $default;
    }
    
    /**
     * Return S-Coin cashback percentage for current single product context.
     * Assumes s_coin_value already contains the percentage.
     */
    public static function get_single_product_cashback_percent(): int
    {
        global $product;
        if (!$product) {
            return 0;
        }

        $product_id = $product->get_id();

        // For variable products, prefer variation S-Coin value if set
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $v_s_coin_value = (int) get_field('s_coin_value', $variation_id);
                if ($v_s_coin_value > 0) {
                    return $v_s_coin_value;
                }
            }
        }

        // Fallback to product-level S-Coin value
        $s_coin_cashback = (int) get_field('s_coin_value', $product_id);

        return max(0, $s_coin_cashback);
    }

    /**
     * Get S-Coin data for variable products, including variation-specific S-Coin values.
     */
    public static function get_product_scoin_data(): array
    {
        global $product;

        if (!$product) {
            return [
                'show_icon' => false,
                'has_parent_scoin' => false,
                'variations' => [],
            ];
        }

        $product_id = $product->get_id();
        $parent_s_coin_value = (float) get_field('s_coin_value', $product_id);
        $has_parent_scoin = $parent_s_coin_value > 0;

        $data = [
            'show_icon' => $has_parent_scoin,
            'has_parent_scoin' => $has_parent_scoin,
            'parent_percent' => $parent_s_coin_value,
            'variations' => [],
        ];

        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            $variation_scoins = [];
            $first_nonzero = null;
            $has_variation_scoin = false;

            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $v_s_coin_value = (float) get_field('s_coin_value', $variation_id);

                // Store the actual S-Coin percent (0 if not set)
                $variation_scoins[$variation_id] = max(0, $v_s_coin_value);
                
                //if variation is zero but parent no zero then all variation follow parent
                if($v_s_coin_value == 0 && $has_parent_scoin){
                    $variation_scoins[$variation_id] = max(0, $parent_s_coin_value);
                }

                if ($v_s_coin_value > 0) {
                    $has_variation_scoin = true;
                    if ($first_nonzero === null) {
                        $first_nonzero = $v_s_coin_value;
                    }
                }
            }

            $data['variations'] = $variation_scoins;

            // Determine whether to show icon
            // Show if parent has S-Coin OR at least one variation has S-Coin
            if (!$has_parent_scoin) {
                $data['show_icon'] = in_array(true, array_map(fn($v) => $v > 0, $variation_scoins), true);
            }

            // Parent percent is zero → take first non-zero variation percent (if exists)
            if ($data['parent_percent'] <= 0 && $first_nonzero !== null) {
                $data['parent_percent'] = $first_nonzero;
                $data['has_parent_scoin'] = true;
            }
        }
        return $data;
    }

    // public static function sort_by_s_coin_cashback($args)
    // {
    //     if (isset($_GET['orderby']) && $_GET['orderby'] === 's_coin_cashback') {
    //         $args['orderby'] = 'meta_value_num';
    //         $args['order'] = 'DESC'; // or 'ASC' for lowest to highest
    //         $args['meta_key'] = 's_coin_cashback';
    //     }
    //     return $args;
    // }

    // public static function add_s_coin_sorting_option($options)
    // {
    //     $options['s_coin_cashback'] = 'Sort by S-Coin Cashback';
    //     return $options;
    // }

    public static function scoin_display_on_cart_item($name, $cart_item, $cart_item_key)
    {
        if (isset($cart_item['warranty_for'])) {
            return $name;
        }

        $product_id   = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
        $variation_id = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;
        $qty          = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

        $s_coin_value = (float) (
            get_field('s_coin_value', $variation_id) ?: get_field('s_coin_value', $product_id)
        );

        $price = isset($cart_item['data']) ? (float) $cart_item['data']->get_price() : 0;

        // Skip invalid or zero-value items
        if ($price <= 0 || $s_coin_value <= 0) {
            return $name;
        }

        // Raw calculation
        $item_scoin = (($s_coin_value) * $price) * $qty;

        // 🔥 Apply 0.5 rule
        $item_scoin = round($item_scoin, 0, PHP_ROUND_HALF_UP);

        $s_coin_formatted = number_format(($item_scoin), 0);
        $rm_value         = $item_scoin / 100;
        $rm_formatted     = number_format($rm_value, 2);

        $button_html = sprintf(
            '<div class="scoin-cart-row" style="display:block;">
                <button type="button" class="scoin-cont" style="pointer-events:none;cursor:default;">
                    <span>Earn</span> 
                    <img src="%s" alt="S-Coin" style="vertical-align:middle;margin:0 2px;"> 
                    %s S-Coin (Worth RM%s)
                </button>
            </div>',
            esc_url(SENHENG_CORE_ASSETS_URL . 'uploads/s-coin-nobg.png'),
            $s_coin_formatted,
            $rm_formatted
        );

        return sprintf('%s %s', $name, $button_html);
    }

    public static function sh_get_cart_total_scoins(): float
    {
        if (! WC()->cart) {
            return 0;
        }
        $total_scoins = 0;

        foreach (WC()->cart->get_cart() as $cart_item) {

            // Use variation ID when available, else the product ID
            $product_id   = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
            $variation_id = ! empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;

            $s_coin_value = (float) (
                get_field('s_coin_value', $variation_id) ?: get_field('s_coin_value', $product_id)
            );

            // Cast s_coin_value to float to ensure proper arithmetic operations
            $s_coin_value = (float) $s_coin_value;

            // If no S-Coin set or no price, skip
            $price = isset($cart_item['data']) ? (float) $cart_item['data']->get_price() : 0;
            if (! $s_coin_value || $price <= 0) {
                continue;
            }

            $qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;
            
            // Raw calculation
            $item_scoin = (($s_coin_value) * $price) * $qty;

            // 🔥 Apply 0.5 rule
            $item_scoin = round($item_scoin, 0, PHP_ROUND_HALF_UP);

            $total_scoins += $item_scoin;
        }

        return (float) $total_scoins;
    }

    /**
     * Add S-Coin Value field to WooCommerce variation settings
     * Hooks into woocommerce_variation_options_pricing to display the field
     */
    public static function add_variation_scoin_field($loop, $variation_data, $variation)
    {
        $variation_id = $variation->ID;
        $s_coin_value = get_field('s_coin_value', $variation_id);
        
        woocommerce_wp_text_input([
            'id'            => "s_coin_value_{$loop}",
            'name'          => "s_coin_value[{$loop}]",
            'value'         => $s_coin_value !== false ? $s_coin_value : '',
            'label'         => __('S-Coin Value (%)', 'senheng'),
            'desc_tip'      => true,
            'description'   => __('Enter the S-Coin cashback percentage for this variation. Note: if parent product has S-Coin value, it will inherit from parent.', 'senheng'),
            'type'          => 'number',
            'custom_attributes' => [
                'step' => '0.01',
                'min'  => '0',
            ],
            'wrapper_class' => 'form-row',
        ]);
    }

    /**
     * Save S-Coin Value field for variations
     * Hooks into woocommerce_save_product_variation to save the ACF field
     */
    public static function save_variation_scoin_field($variation_id, $loop)
    {
        if (isset($_POST['s_coin_value'][$loop])) {
            $s_coin_value = wc_clean($_POST['s_coin_value'][$loop]);
            
            if ($s_coin_value !== '' && is_numeric($s_coin_value)) {
                update_field('s_coin_value', floatval($s_coin_value), $variation_id);
            } else {
                // Delete the field if empty (allows inheriting from parent)
                delete_field('s_coin_value', $variation_id);
            }
        }
    }

    public static function sh_cart_totals_scoin_row()
    {
        // Use WooCommerce conditional functions for cart/checkout detection (more reliable, works with AJAX)
        $is_cart = function_exists('is_cart') && is_cart();
        $is_checkout = function_exists('is_checkout') && is_checkout();

        $total_scoins = self::sh_get_cart_total_scoins();
        if ($total_scoins <= 0) {
            return;
        }

        $s_coin_formatted = number_format($total_scoins, 0);
        $rm_formatted     = number_format($total_scoins / 100, 2);

        if ($is_checkout) {
            $s_coin_formatted = 'Earn total ' . $s_coin_formatted . ' S-Coin (worth RM' . $rm_formatted . ')';
        }

        $icon_url = esc_url(SENHENG_CORE_ASSETS_URL . 'uploads/s-coin-nobg.png');

        echo '<tr class="scoin-total-row">';
        echo '  <th>' . esc_html__('S-Coin Earn', 'senheng') . '</th>';
        echo '  <td data-title="' . esc_attr__('S-Coin earn', 'senheng') . '">';
        echo '      <button type="button" style="pointer-events:none;cursor:default;" class="scoin-cont-total"><img src="' . $icon_url . '" alt="S-Coin"> ' . esc_html($s_coin_formatted) . '</button>';
        echo '  </td>';
        echo '</tr>';
    }
}
