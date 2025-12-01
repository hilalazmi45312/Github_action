<?php
/**
 * Plugin Name: WooCommerce Smart Coupons
 * Plugin URI: https://woocommerce.com/products/smart-coupons/
 * Description: <strong>WooCommerce Smart Coupons</strong> lets customers buy gift certificates, store credits or coupons easily. They can use purchased credits themselves or gift to someone else.
 * Version: 9.39.0
 * Author: StoreApps
 * Author URI: https://www.storeapps.org/
 * Developer: StoreApps
 * Developer URI: https://www.storeapps.org/
 * Requires PHP: 5.6
 * Requires at least: 4.4
 * Tested up to: 6.8.1
 * WC requires at least: 3.0.0
 * WC tested up to: 9.8.5
 * Requires Plugins: woocommerce
 * Text Domain: woocommerce-smart-coupons
 * Domain Path: /languages/
 * Copyright (c) 2014-2025 WooCommerce, StoreApps All rights reserved.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package woocommerce-smart-coupons
 * Woo: 18729:05c45f2aa466106a466de4402fff9dde

 */

/**
 * Include class having function to execute during activation & deactivation of plugin
 */
require_once 'includes/class-wc-sc-act-deact.php';

/**
 * On activation
 */
register_activation_hook( __FILE__, array( 'WC_SC_Act_Deact', 'smart_coupon_activate' ) );

/**
 * On deactivation
 */
register_deactivation_hook( __FILE__, array( 'WC_SC_Act_Deact', 'smart_coupon_deactivate' ) );


/**
 * WooCommerce fallback notice.
 *
 * @since 1.0.0
 */
function smart_coupons_woocommerce_missing_wc_notice() {
	$install_url = wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'install-plugin',
				'plugin' => 'woocommerce',
			),
			admin_url( 'update.php' )
		),
		'install-plugin_woocommerce'
	);

	$admin_notice_content = sprintf(
		// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org, 5$-6$: opening and closing link tags, leads to plugins.php in admin.
		esc_html__( '%1$sWooCommerce Smart Coupons is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for the Smart Coupons to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'woocommerce-smart-coupons' ),
		'<strong>',
		'</strong>',
		'<a href="http://wordpress.org/extend/plugins/woocommerce/">',
		'</a>',
		'<a href="' . esc_url( $install_url ) . '">',
		'</a>'
	);

	echo '<div class="error">';
	echo '<p>' . wp_kses_post( $admin_notice_content ) . '</p>';
	echo '</div>';
}

if ( ! defined( 'WC_SC_PLUGIN_FILE' ) ) {
	define( 'WC_SC_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WC_SC_PLUGIN_DIRNAME' ) ) {
	define( 'WC_SC_PLUGIN_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
}

add_action(
	'plugins_loaded',
	function() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', 'smart_coupons_woocommerce_missing_wc_notice' );
			return;
		}

		include_once 'includes/class-wc-smart-coupons.php';
		$GLOBALS['woocommerce_smart_coupon'] = WC_Smart_Coupons::get_instance();

		include_once 'blocks/blocks.php';
	}
);// End woocommerce active check.
