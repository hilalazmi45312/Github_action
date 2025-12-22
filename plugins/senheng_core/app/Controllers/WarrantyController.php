<?php

class WarrantyController
{
    // --- SESSION HELPERS ---
    protected static function getSessionSelections(): array
    {
        $sel = WC()->session ? WC()->session->get('warranty_selected_keys', []) : [];
        return is_array($sel) ? $sel : [];
    }

    protected static function setSessionSelections(array $keys): void
    {
        if (WC()->session) WC()->session->set('warranty_selected_keys', array_values(array_unique($keys)));
    }

    protected static function toggleSelection(string $cart_key, bool $on): array
    {
        $sel = self::getSessionSelections();
        if ($on) {
            if (!in_array($cart_key, $sel, true)) $sel[] = $cart_key;
        } else {
            $sel = array_values(array_diff($sel, [$cart_key]));
        }
        self::setSessionSelections($sel);
        return $sel;
    }

    private static function getOffKeysFromCookie(): array
    {
        $raw = $_COOKIE['wty_off_keys'] ?? '[]';
        $arr = json_decode(stripslashes($raw), true);
        return is_array($arr) ? array_values(array_unique(array_map('strval', $arr))) : [];
    }

    private static function isEligibleProduct($product_id): bool
    {
        return function_exists('get_field') && $product_id && get_field('warranty_enabled_sh', $product_id);
    }


    // Ajax: set/unset a selection
    public static function ajaxSetWarrantySelection()
    {
        $cart_key = sanitize_text_field($_POST['cart_key'] ?? '');
        $selected = isset($_POST['selected']) && $_POST['selected'] === '1';

        if (!$cart_key || !WC()->cart) wp_send_json_error(['message' => 'Bad request']);

        // Also validate the key still exists in cart
        if (!array_key_exists($cart_key, WC()->cart->get_cart())) {
            // If not in cart, remove from session quietly
            self::toggleSelection($cart_key, false);
            wp_send_json_success(['selections' => self::getSessionSelections()]);
        }

        $selections = self::toggleSelection($cart_key, $selected);
        wp_send_json_success(['selections' => $selections]);
    }

    // Add fees for selected items
    public static function applyWarrantyFees(WC_Cart $cart)
    {
        // (Optional) membership gate
        $users = is_user_logged_in() ? wp_get_current_user() : null;
        $membershipType = $users ? get_user_meta($users->ID, 'cust_cardtype', true) : '';

        if (is_user_logged_in() && $membershipType !== 'eBSC') {
            // Logged in but NOT eBSC → stop
            self::forceRemoveAllWarranties();
            return;
        }

        if (is_admin() && !defined('DOING_AJAX')) return;

        $off = self::getOffKeysFromCookie();
        $amount  = 9.90;
        $taxable = false; // set true + tax class if needed

        foreach (WC()->cart->get_cart() as $key => $item) {
            // skip warranty items themselves
            if (!empty($item['warranty_for'])) continue;

            $pid = (int)($item['product_id'] ?? 0);
            if (!$pid || !self::isEligibleProduct($pid)) continue;

            $qty = !empty($item['quantity']) ? (int)$item['quantity'] : 1;

            // If this key NOT in off list → add fee
            if (!in_array((string)$key, $off, true)) {
                $fee_total = $amount * $qty;
                $label = __('9.9 Product Warranty', 'woocommerce') . ' — ' . wp_html_excerpt($item['data']->get_name(), 70);
                $cart->add_fee($label, $fee_total, $taxable);
            }
        }
    }


    // Decorate name: add data attributes + status hint
    public static function decorateCartItemName($name, $item, $key)
    {
        $is_warranty = isset($item['warranty_for']);
        $pid = (int)($item['product_id'] ?? 0);
        $eligible = !$is_warranty && self::isEligibleProduct($pid);

        $off = self::getOffKeysFromCookie();
        $is_off = in_array((string)$key, $off, true);
        $is_selected = $eligible && !$is_off;

        if ($is_selected) {
            $name .= '<br><small style="color:#d00;font-weight:600;" class="9-9-warranty-added">' .

                '</small>';
        }

        return '<span class="product-name-wrapper"
        data-cart_item_key="' . esc_attr($key) . '"
        data-is-warranty="' . ($is_warranty ? '1' : '0') . '"
        data-warranty-eligible="' . ($eligible ? '1' : '0') . '"
        data-warranty-selected="' . ($is_selected ? '1' : '0') . '">' . $name . '</span>';
    }

    // CART injector
    public static function injectCartCheckboxScript()
    {
        if (!(is_cart())) return;

        // Optional membership gate (same as fees)
        $users = is_user_logged_in() ? wp_get_current_user() : null;
        $membershipType = $users ? get_user_meta($users->ID, 'cust_cardtype', true) : '';

        if (is_user_logged_in() && $membershipType !== 'eBSC') {
            // Logged in but NOT eBSC → stop
            return;
        }

        include SENHENG_CORE_VIEW_PATH . 'warranty/inject-cart-session.js.php';
    }

    // CHECKOUT injector (FunnelKit-safe)
    public static function injectCheckoutCheckboxScript()
    {
        if (!is_checkout()) return;

        $users = is_user_logged_in() ? wp_get_current_user() : null;
        $membershipType = $users ? get_user_meta($users->ID, 'cust_cardtype', true) : '';

        if (is_user_logged_in() && $membershipType !== 'eBSC') {
            // Logged in but NOT eBSC → stop
            return;
        }

        include SENHENG_CORE_VIEW_PATH . 'warranty/inject-checkout-session.js.php';
    }


    public static function store_warranty_in_order_item($item, $cart_item_key, $values, $order)
    {
        // skip warranty products themselves
        if (!empty($values['warranty_for'])) return;

        // Only store warranty status for eligible products
        $pid = (int)($values['product_id'] ?? 0);
        if (!$pid || !self::isEligibleProduct($pid)) return;

        $off = WarrantyController::getOffKeysFromCookie();
        $is_off = in_array((string)$cart_item_key, $off, true);
        $has_warranty = !$is_off;

        // save to order item
        $item->add_meta_data('_warranty_selected', $has_warranty ? 'yes' : 'no', true);
    }

    public static function forceRemoveAllWarranties()
    {
        if (!WC()->session) return;

        // Clear session selections
        WC()->session->set('warranty_selected_keys', []);

        // Mark all cart items as OFF
        $offKeys = [];

        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $key => $item) {
                $offKeys[] = (string) $key;
            }
        }

        // Update cookie
        wc_setcookie(
            'wty_off_keys',
            wp_json_encode(array_unique($offKeys)),
            time() + DAY_IN_SECONDS
        );
    }
}
