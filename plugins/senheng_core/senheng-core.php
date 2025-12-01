<?php

/**
 * Plugin Name: Senheng Core
 * Description: Custom integrations and overrides for Senheng WooCommerce setup.
 * Version: 1.0.0
 * Author: Cloone Corporation Sdn Bhd
 */

require_once plugin_dir_path(__FILE__) . 'bootstrap.php';
require_once plugin_dir_path(__FILE__) . 'env.php';
require_once __DIR__ . '/app/Services/ProductImportImageBypassService.php';
ProductImportImageBypassService::register();

add_action('admin_menu', function () {

    $icon_url = plugins_url('assets/uploads/site-icon.png', __FILE__);

    // Main menu
    add_menu_page(
        'Senheng Core',
        'Senheng Core',
        'manage_options',
        'senheng-settings',
        null,
        $icon_url,
        3
    );

    add_submenu_page(
        'senheng-settings',
        'Installment Settings',
        'Installment Settings',
        'manage_options',
        'senheng-payment-settings',
        ['InstallmentController', 'index'],
    );

    add_submenu_page(
        'senheng-settings',
        'Installment Plans',
        'Installment Plans',
        'manage_options',
        'senheng-payment-plans',
        ['InstallmentController', 'plans']
    );

    add_submenu_page(
        'senheng-settings',
        'Handle Payment Actions',
        'Handle Payment Actions',
        'manage_options',
        'senheng-payment-settings-action',
        ['InstallmentController', 'payment_method_settings']
    );

    add_submenu_page(
        'senheng-settings',
        'Handle Payment Plan Actions',
        'Handle Payment Plan Actions',
        'manage_options',
        'senheng-payment-plan-settings-action',
        ['InstallmentController', 'payment_plan_method_settings']
    );

    add_submenu_page(
        'senheng-settings',
        'Product Import',
        'Product Import',
        'manage_options',
        'product-import',
        ['ProductImportController', 'renderPage']
    );

    // Optional: Separate Cron Setup page (can be removed since URL is shown on main import page)
    // add_submenu_page(
    //     'senheng-settings',
    //     'Cron Setup',
    //     'Cron Setup',
    //     'manage_options',
    //     'product-import-cron',
    //     ['ProductImportController', 'renderCronSetup']
    // );

    add_submenu_page(
        'senheng-settings',
        'Benefit Box Settings',
        'Benefit Box Settings',
        'manage_options',
        'senheng-benefit-box-settings',
        ['BenefitBoxController', 'adminIndex']
    );

    // Trade-In Settings
    add_submenu_page(
        'senheng-settings',
        'AutoSON Settings',
        'AutoSON Settings',
        'manage_options',
        'senheng-tradein-settings',
        ['TradeInController', 'index']
    );

    // add_submenu_page(
    //     'senheng-settings',
    //     'Payment Gateway',
    //     'Payment Gateway',
    //     'manage_options',
    //     'payment-options',
    //     ['PaymentGatewayController', 'adminIndex']
    // );

    remove_submenu_page('senheng-settings', 'senheng-payment-plans');
    remove_submenu_page('senheng-settings', 'senheng-payment-settings-action');
    remove_submenu_page('senheng-settings', 'senheng-payment-plan-settings-action');
});

// add_filter('woocommerce_available_payment_gateways', function($gws){
// 	error_log('GW keys: '.implode(',', array_keys($gws)));
// 	if (isset($gws['ipay88'])) error_log('iPay88 enabled='.$gws['ipay88']->enabled);
// 	return $gws;
// });

// Style the menu icon
add_action('admin_enqueue_scripts', function () {
    echo '<style>
        #toplevel_page_senheng-settings .wp-menu-image img {
            width: 20px !important;
            height: 20px !important;
        }
    </style>';
});
