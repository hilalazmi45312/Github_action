<?php

class LimitedTimeOfferController
{
    public static function init()
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_shortcode('limited_time_special_sh', [self::class, 'shortcode']);
    }

    public static function enqueueAssets()
    {
        wp_enqueue_style('custom-countdown-shortcode', SENHENG_CORE_URL . 'assets/css/countdown-shortcode.css');
        wp_enqueue_script('custom-countdown-shortcode', SENHENG_CORE_URL . 'assets/js/countdown-shortcode.js', ['jquery'], null, true);
    }

    public static function render()
    {
        global $product;
        if (!$product) {
            return;
        }

        $offer = '';
        $end_date = '';
        $visible = false;
        $is_variable = $product->is_type('variable');
        
        if ($is_variable) {
            // For variable products, don't display initially - will be shown via JS when variation is selected
            $visible = false;
        } elseif ($product->is_on_sale()) {
            $offer = $product->get_regular_price() - $product->get_sale_price();
            $end_date = $product->get_date_on_sale_to();
            $visible = true;
        }

        if ($end_date instanceof WC_DateTime) {
            $end_date_iso = $end_date->date('c');
        } else {
            $end_date_iso = $end_date ? date('c', strtotime($end_date)) : '';
        }

        // For simple products: if not on sale or no end date, don't render anything
        if (!$is_variable && (!$visible || empty($end_date_iso))) {
            return;
        }

        $variation_sale_data = [];
        if ($is_variable) {
            $variations = $product->get_children();
            $has_any_sale = false;
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $variation_sale_data[$variation_id] = [
                    'discount' => 0,
                    'end_date' => null,
                    'display_price' => $variation ? ($variation->get_regular_price() ?: $variation->get_sale_price()) : 0,
                ];
                if ($variation && $variation->is_on_sale()) {
                    $var_end_date = $variation->get_date_on_sale_to();
                    if ($var_end_date) {
                        $has_any_sale = true;
                    }
                    $variation_sale_data[$variation_id] = [
                        'discount' => $variation->get_regular_price() - $variation->get_sale_price(),
                        'end_date' => $var_end_date ? $var_end_date->date('c') : null,
                        'display_price' => $variation->get_sale_price() ?: $variation->get_regular_price(),
                    ];
                }
            }
            
            // If no variation has a sale with end date, don't render anything
            if (!$has_any_sale) {
                return;
            }
        }

        $boxStyle = $visible ? '' : ' style="display:none"';
        echo '<div class="custom-countdown-box"' . $boxStyle . '>';
        echo '<h3 class="custom-heading">Limited Time Specials</h3>';
        if (!empty($offer)) {
            echo '<p class="custom-subheading">RM ' . esc_html($offer) . ' OFF</p>';
        }
        echo '<p class="ends-in">Ends in</p>';
        $now_ts = time();
        $end_ts = $end_date_iso ? strtotime($end_date_iso) : 0;
        $distance = ($visible && $end_ts && $end_ts > $now_ts) ? ($end_ts - $now_ts) : 0;
        $days = (int) floor($distance / 86400);
        $hours = (int) floor(($distance % 86400) / 3600);
        $minutes = (int) floor(($distance % 3600) / 60);
        $seconds = (int) ($distance % 60);
        echo '<div class="custom-countdown" data-end-date="' . esc_attr($end_date_iso) . '">';
        echo '<div class="time-part"><span class="number days">' . $days . '</span><span class="label">days</span></div>';
        echo '<div class="time-part"><span class="number hours">' . $hours . '</span><span class="label">h</span></div>';
        echo '<div class="time-part"><span class="number minutes">' . $minutes . '</span><span class="label">min</span></div>';
        echo '<div class="time-part"><span class="number seconds">' . $seconds . '</span><span class="label">sec</span></div>';
        echo '</div>';
        echo '<span class="clock-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="white"><path d="M12 8v5h4v-2h-2V8h-2zm0-6C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg></span>';
        echo '</div>';

        echo '<script type="application/json" id="variation-sale-data">' . wp_json_encode($variation_sale_data) . '</script>';
    }

    public static function shortcode()
    {
        global $post;
        $product = ($post && isset($post->ID)) ? wc_get_product($post->ID) : null;
        if (!$product) {
            return '';
        }

        $offer = '';
        $end_date = '';
        $visible = false;
        $is_variable = $product->is_type('variable');
        
        if ($is_variable) {
            // For variable products, don't display initially - will be shown via JS when variation is selected
            $visible = false;
        } elseif ($product->is_on_sale()) {
            $offer = $product->get_regular_price() - $product->get_sale_price();
            $end_date = $product->get_date_on_sale_to();
            $visible = true;
        }

        if ($end_date instanceof \WC_DateTime) {
            $end_date_iso = $end_date->date('c');
        } else {
            $end_date_iso = $end_date ? date('c', strtotime($end_date)) : '';
        }

        // For simple products: if not on sale or no end date, don't render anything
        if (!$is_variable && (!$visible || empty($end_date_iso))) {
            return '';
        }

        $variation_sale_data = [];
        if ($is_variable) {
            $variations = $product->get_children();
            $has_any_sale = false;
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $variation_sale_data[$variation_id] = [
                    'discount' => 0,
                    'end_date' => null,
                    'display_price' => $variation ? ($variation->get_regular_price() ?: $variation->get_sale_price()) : 0,
                ];
                if ($variation && $variation->is_on_sale()) {
                    $var_end_date = $variation->get_date_on_sale_to();
                    if ($var_end_date) {
                        $has_any_sale = true;
                    }
                    $variation_sale_data[$variation_id] = [
                        'discount' => $variation->get_regular_price() - $variation->get_sale_price(),
                        'end_date' => $var_end_date ? $var_end_date->date('c') : null,
                        'display_price' => $variation->get_sale_price() ?: $variation->get_regular_price(),
                    ];
                }
            }
            
            // If no variation has a sale with end date, don't render anything
            if (!$has_any_sale) {
                return '';
            }
        }

        ob_start();
        $boxStyle = $visible ? '' : ' style="display:none"';
        echo '<div class="custom-countdown-box"' . $boxStyle . '>';
        echo '<h3 class="custom-heading">Limited Time Specials</h3>';
        if (!empty($offer)) {
            echo '<p class="custom-subheading">RM ' . esc_html($offer) . ' OFF</p>';
        }
        echo '<p class="ends-in">Ends in</p>';
        $now_ts = time();
        $end_ts = $end_date_iso ? strtotime($end_date_iso) : 0;
        $distance = ($visible && $end_ts && $end_ts > $now_ts) ? ($end_ts - $now_ts) : 0;
        $days = (int) floor($distance / 86400);
        $hours = (int) floor(($distance % 86400) / 3600);
        $minutes = (int) floor(($distance % 3600) / 60);
        $seconds = (int) ($distance % 60);
        echo '<div class="custom-countdown" data-end-date="' . esc_attr($end_date_iso) . '">';
        echo '<div class="time-part"><span class="number days">' . $days . '</span><span class="label">days</span></div>';
        echo '<div class="time-part"><span class="number hours">' . $hours . '</span><span class="label">h</span></div>';
        echo '<div class="time-part"><span class="number minutes">' . $minutes . '</span><span class="label">min</span></div>';
        echo '<div class="time-part"><span class="number seconds">' . $seconds . '</span><span class="label">sec</span></div>';
        echo '</div>';
        echo '<span class="clock-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="white"><path d="M12 8v5h4v-2h-2V8h-2zm0-6C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg></span>';
        echo '</div>';
        echo '<script type="application/json" id="variation-sale-data">' . wp_json_encode($variation_sale_data) . '</script>';
        return ob_get_clean();
    }
}
