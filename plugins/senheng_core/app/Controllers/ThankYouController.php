<?php

defined('ABSPATH') || exit;

class ThankYouController
{
    public static function init()
    {
        // Override the shortcode from Funnel Builder
        add_action('wp', [__CLASS__, 'override_wfty_shortcode'], 20);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets()
    {
        if (is_order_received_page()) {
            wp_enqueue_style(
                'senheng-thank-you-css',
                SENHENG_CORE_ASSETS_URL . 'css/thank-you.css',
                [],
                filemtime(SENHENG_CORE_PATH . 'assets/css/thank-you.css')
            );
        }
    }

    public static function override_wfty_shortcode()
    {
        if (shortcode_exists('wfty_order_details')) {
            remove_shortcode('wfty_order_details');
            add_shortcode('wfty_order_details', [__CLASS__, 'render_custom_thankyou_order_table']);
        }
    }

    public static function render_custom_thankyou_order_table($atts)
    {
        $order_id = self::get_order_id();
        if (!$order_id) {
            return '';
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return '';
        }

        ob_start();

        // 1. Add Heading
        $order_details_heading = __('Order Details', 'woocommerce');
        // Try to get from Funnel Builder options if possible, otherwise default
        // Note: Accessing WFFN_Core() might be possible if the plugin is active.
        if (function_exists('WFFN_Core') && isset(WFFN_Core()->thank_you_pages)) {
             $heading = WFFN_Core()->thank_you_pages->get_optionsShortCode('order_details_heading', $order_id);
             if ($heading) {
                 $order_details_heading = $heading;
             }
        }
        
        echo '<div class="wfty_box wfty_order_details">'; // Wrapper from Funnel Builder
        echo '<div class="wfty-order-details-heading wfty_title">' . esc_html($order_details_heading) . '</div>';

        // Output inside a div to match WFTY container style if needed, but we follow custom style
        echo '<div class="sh-custom-shop-table">';

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            
            // Get product name from item even if product is missing
            $product_name = $item->get_name();
            $qty = $item->get_quantity();
            
            // Image Logic (Robust) - handle null product
            $image_url = '';
            
            if ($product) {
                // If it's a variation, try to get variation image first, fallback to parent
                if ($product->is_type('variation')) {
                    $variation_image_id = $product->get_image_id();
                    if ($variation_image_id) {
                        $image_url = wp_get_attachment_image_url($variation_image_id, 'woocommerce_thumbnail');
                    } else {
                        $parent_product = wc_get_product($product->get_parent_id());
                        if ($parent_product) {
                            $parent_image_id = $parent_product->get_image_id();
                            if ($parent_image_id) {
                                $image_url = wp_get_attachment_image_url($parent_image_id, 'woocommerce_thumbnail');
                            }
                        }
                    }
                } else {
                    $image_id = $product->get_image_id();
                    if ($image_id) {
                        $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                    }
                }
            }

            if (empty($image_url)) {
                $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
            }

            $product_image = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_name) . '">';

            // Price Calculation Logic - handle null product
            $base_price = 0;
            if ($product) {
                $base_price = $product->get_regular_price();
                if (!$base_price) $base_price = $product->get_price();
            }
            $regular_line_total = floatval($base_price) * $qty;

            // For normal payment display, we want the actual transaction amount (what user paid)
            // get_total() returns line total excluding tax.
            $transaction_line_total = floatval($item->get_total()) + floatval($item->get_total_tax());

            // Deposit logic
            $is_deposit = false;
            $deposit_subtotal = null;
            
            // Check meta for deposit
            $deposit_option = $item->get_meta('_deposit_option');
            $awcdp_deposit = $item->get_meta('_awcdp_deposit_option');
            
            if ($awcdp_deposit === 'yes' || $deposit_option === 'deposit') {
                $is_deposit = true;
                $deposit_amount_meta = $item->get_meta('_deposit_amount');
                
                if (is_numeric($deposit_amount_meta)) {
                     // CheckoutController uses unit price for deposit_amount usually
                     $deposit_subtotal = floatval($deposit_amount_meta) * $qty;
                } else {
                    // Fallback
                    $deposit_subtotal = $transaction_line_total;
                }
            }

            echo '<div class="sh-checkout-cart-item">';
            
            // Header
            echo '<div class="sh-cart-item-header">';
            echo '<div class="sh-item-image-wrapper">';
            echo '<div class="sh-item-image">' . wp_kses_post($product_image) . '</div>';
            echo '<div class="sh-item-qty-badge">' . esc_html($qty) . '</div>';
            echo '</div>'; // wrapper

            echo '<div class="sh-item-details">';
            echo '<h4 class="sh-item-name">' . esc_html($product_name) . '</h4>';
            
            // Meta
            echo '<div class="sh-item-meta">';
            
            // Variations
            $formatted_meta = $item->get_formatted_meta_data('_');
            if ($formatted_meta) {
                $variation_values = [];
                foreach ($formatted_meta as $meta) {
                     // Skip internal/custom meta
                     if (in_array($meta->key, ['Trade In', 'Payment Option', 'Deposit Payment', 'Deposit Amount', 'deposit_amount'], true)) continue; 
                     // Also skip extras meta
                     if (in_array($meta->key, ['_product_extras_products', '_product_extras_info'], true)) continue;
                     
                     $variation_values[] = '<span class="variation-value">' . wp_strip_all_tags($meta->display_value) . '</span>';
                }
                if (!empty($variation_values)) {
                    echo '<div>' . wp_kses_post(implode(', ', $variation_values)) . '</div>';
                }
            }

            // Trade In
            $trade_in = $item->get_meta('_trade_in_option');
            if (!$trade_in) {
                $trade_in_raw = $item->get_meta('Trade In');
                if ($trade_in_raw) {
                    $trade_in = strtolower($trade_in_raw);
                }
            }

            if ($trade_in === 'yes' || $trade_in === 'no') {
                $trade_in_text = ($trade_in === 'yes') ? __('Yes', 'senheng-core') : __('No', 'senheng-core');
                echo '<div><strong>' . esc_html__('Trade In:', 'senheng-core') . '</strong> ' . esc_html($trade_in_text) . '</div>';
            }
            
            // Deposit
            if ($is_deposit) {
                echo '<div><strong>' . esc_html__('Deposit Payment', 'senheng-core') . '</strong></div>';
                echo '<div><strong>' . esc_html__('Actual Price:', 'senheng-core') . '</strong> ' . wp_kses_post(wc_price($regular_line_total)) . '</div>';
            }
            
            echo '</div>'; // .sh-item-meta
            echo '</div>'; // .sh-item-details

            // Price
            echo '<div class="sh-item-price">' . wp_kses_post(wc_price(($is_deposit && $deposit_subtotal !== null) ? $deposit_subtotal : $transaction_line_total)) . '</div>';
            echo '</div>'; // .sh-cart-item-header

            // Added Extras Badge (Warranty)
            $extras_products = $item->get_meta('_product_extras_products');
            if (!empty($extras_products) && is_array($extras_products)) {
                foreach ($extras_products as $extra) {
                     if (!empty($extra['productLabel']) && (stripos($extra['productLabel'], 'warranty') !== false || stripos($extra['productLabel'], 'product') !== false)) {
                        echo '<div class="sh-added-badge">ADDED: ' . esc_html($extra['productLabel']) . '</div>';
                        break;
                    }
                }
            }

            // Extras Section
            $extras_info = $item->get_meta('_product_extras_info');
            if ((!empty($extras_products) && is_array($extras_products)) || (!empty($extras_info) && is_array($extras_info))) {
                echo '<div class="sh-extras-section">';
                echo wp_kses_post(self::render_order_extras_enhanced($item));
                echo '</div>';
            }

            echo '</div>'; // .sh-checkout-cart-item
        }

        echo '</div>'; // .sh-custom-shop-table

        // 2. Add Footer (Totals) matching Funnel Builder structure but inside our wrapper if desired, 
        // or just as a table. Funnel Builder uses a table for footer.
        echo '<table class="sh-custom-order-totals">';
        echo '<tfoot>';
        $item_total = $order->get_order_item_totals();
        $shipping_option = get_option( 'woocommerce_ship_to_countries' );
        if ( 'disabled' === $shipping_option && isset($item_total['shipping']) ) {
            unset( $item_total['shipping'] );
        }
        
        if ($item_total) {
            foreach ( $item_total as $total ) {
                echo '<tr>';
                echo '<th scope="row">' . esc_html( str_replace( ':', '', $total['label'] ) ) . '</th>';
                echo '<td>' . wp_kses_post( $total['value'] ) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tfoot>';
        echo '</table>';

        echo '</div>'; // .wfty_box wfty_order_details

        return ob_get_clean();
    }

    // Helper to get order ID
    private static function get_order_id() {
        global $wp;
        $order_id = 0;
        
        // Use $_GET instead of $_REQUEST to avoid processing potential POST data without nonce
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['order_id'] ) && absint($_GET['order_id']) > 0 ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $order_id = absint( $_GET['order_id'] );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $_GET['order'] ) && absint($_GET['order']) > 0 ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $order_id = absint( $_GET['order'] );
        } elseif ( isset( $wp->query_vars['order-received'] ) ) {
            $order_id = absint( $wp->query_vars['order-received'] );
        }
        return $order_id;
    }

    // Enhanced extras renderer adapted for Order Item
    private static function render_order_extras_enhanced($item)
    {
        $extras_products = $item->get_meta('_product_extras_products');
        $extras_info = $item->get_meta('_product_extras_info');
        
        if (empty($extras_products) && empty($extras_info)) {
            return '';
        }
        
        $out = '';

        // Selected information (free items with original price shown)
        if (!empty($extras_info) && is_array($extras_info)) {
            $label_info = !empty($extras_info[0]['fieldLabel']) ? $extras_info[0]['fieldLabel'] : __('Gift', 'senheng-core');
            $out .= '<div class="sh-extras-header">' . esc_html($label_info) . '</div>';
            foreach ($extras_info as $info_data) {
                $info_label = isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '';
                $qty = isset($info_data['quantity']) ? intval($info_data['quantity']) : 1;
                
                $original = null;
                if (isset($info_data['infoOriginalPrice']) && is_numeric($info_data['infoOriginalPrice'])) {
                    $original = floatval($info_data['infoOriginalPrice']);
                } elseif (isset($info_data['originalPrice']) && is_numeric($info_data['originalPrice'])) {
                    $original = floatval($info_data['originalPrice']);
                } elseif (isset($info_data['original']) && is_numeric($info_data['original'])) {
                    $original = floatval($info_data['original']);
                }
                
                $total_original = !is_null($original) ? ($original * $qty) : null;

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
                    $out .= '<span class="sh-extras-price">' . wp_kses_post(wc_price(0)) . '</span>';
                    
                    if (!is_null($total_original) && $total_original > 0) {
                        $out .= '<span class="sh-price-original">' . wp_kses_post(wc_price($total_original)) . '</span>';
                    }
                    
                    $out .= '<span class="sh-extras-qty">Qty: ' . esc_html($qty) . '</span>';
                    $out .= '</div>';
                    
                    $out .= '</div>';
                }
            }
        }

        // Selected products (add-ons with price)
        if (!empty($extras_products) && is_array($extras_products)) {
            $label = !empty($extras_products[0]['fieldLabel']) ? $extras_products[0]['fieldLabel'] : __('Add on Deal', 'senheng-core');
            
            $out .= '<div class="sh-extras-header">' . esc_html($label) . '</div>';

            foreach ($extras_products as $extra_product_data) {
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
                    
                    $image_url = '';
                    if (!empty($extra_product_data['productId'])) {
                        $product_id = $extra_product_data['productId'];
                        $variation_id = isset($extra_product_data['variationId']) ? $extra_product_data['variationId'] : '';
                        
                        $product_for_image = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                        if ($product_for_image) {
                            $image_id = $product_for_image->get_image_id();
                            if ($image_id) {
                                $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                            }
                        }
                    }

                    if (empty($image_url)) {
                        $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                    }

                    $out .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '">';
                    $out .= '</div>';
                    
                    $out .= '<div class="sh-extras-content">';
                    $out .= '<div class="sh-extras-title">' . esc_html($name) . '</div>';
                    $out .= '</div>';
                    
                    $out .= '<div class="sh-extras-pricing">';
                    $out .= '<span class="sh-extras-price">' . wp_kses_post(wc_price($total_price)) . '</span>';
                    
                    if (!is_null($total_original) && $total_original > $total_price) {
                        $out .= '<span class="sh-price-original">' . wp_kses_post(wc_price($total_original)) . '</span>';
                    }
                    
                    $out .= '<span class="sh-extras-qty">Qty: ' . esc_html(intval($qty)) . '</span>';
                    $out .= '</div>';
                    
                    $out .= '</div>';
                }
            }
        }

        return $out;
    }
}
