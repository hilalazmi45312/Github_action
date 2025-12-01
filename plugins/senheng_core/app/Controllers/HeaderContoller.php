<?php

class HeaderController
{

    public static function renderPWAHeader()
    {
        $userAgent = get_userAgent();
        // Only render for mobile
        if (! wp_is_mobile()) {
            return;
        }

        $isApp = (
            (isset($_GET['app']) && in_array(strtolower($_GET['app']), ['1', 'true', 'yes'], true)) ||
            // (isset($_COOKIE['is_app']) && $_COOKIE['is_app'] === '1') ||
            ($userAgent === 'SRC')
        );

        if ($isApp) {
            if (function_exists('is_cart') && is_cart()) {
                return include SENHENG_CORE_VIEW_PATH . '/header/pwa-header-cart.php';
            }
            if (function_exists('is_checkout') && is_checkout()) {
                return include SENHENG_CORE_VIEW_PATH . '/header/pwa-header-checkout.php';
            }
            if (function_exists('is_product') && is_product()) {
                return include SENHENG_CORE_VIEW_PATH . '/header/pwa-header-product.php';
            }

            //if home page or front page
            if (is_front_page() || is_home()) {
                return include SENHENG_CORE_VIEW_PATH . '/header/pwa-header-home.php';
            }
            
            return include SENHENG_CORE_VIEW_PATH . '/header/pwa-header.php';
        }
    }
}
