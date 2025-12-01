<?php
/**
 * Plugin Name: Force Default Variant for WooCommerce
 * Plugin URI: http://www.happykite.co.uk
 * Description: Removes the standard WooCommerce 'Select an Option' from variant Drop Downs and the option to Clear Selection.
 * Author: HappyKite
 * Author URI: http://www.happykite.co.uk/
 * Text Domain: force-default-variant-for-woocommerce
 * Version: 1.8.3
 * WC requires at least: 2.4
 * WC tested up to: 10.2
 */

/*
 * This file is part of force-default-variant-for-woocommerce.
 * force-default-variant-for-woocommerce is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * force-default-variant-for-woocommerce is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with force-default-variant-for-woocommerce.  If not, see <http://www.gnu.org/licenses/>.
 */

/***************************
 * includes
 ***************************/
require __DIR__ . '/functions.php'; // Load Additional Functions.
require __DIR__ . '/includes/variations.php'; // Variant code.
require __DIR__ . '/includes/settings.php'; // Settings Area.
require __DIR__ . '/includes/clear-removal.php'; // Remove Clear Selection Text.


/***************************
 * Get Current WC Version.
 ***************************/
function hpy_check_wc_version() {
	// Checking if get_plugins is available.
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Adding required variables
	$woo_folder = get_plugins( '/woocommerce' );
	$woo_file   = 'woocommerce.php';

	// Get WooCommerce Version.
	if ( isset( $woo_folder[ $woo_file ] ) && isset( $woo_folder[ $woo_file ]['Version'] ) ) {
		return $woo_folder[ $woo_file ]['Version'];
	} else {
		return false;
	}
}

/****************************
 * Declare HPOS Compatibility
 */

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);


/***************************
* Activation Notice
*/
$woo_version      = hpy_check_wc_version();
$required_version = '2.4';

if ( ! hpy_fdv_wc_version_check( $required_version ) ) {
	register_activation_hook( __FILE__, 'hpy_plugin_activation' );
	function hpy_plugin_activation() {
		$url       = admin_url( 'tools.php?page=uuc-options' );
		$notices   = get_option( 'hpy_plugin_deferred_admin_notices', array() );
		$notices[] = 'Attention: Force Default Variant for WooCommerce requires at least WooCommerce Version 2.5, you currently have ' . hpy_check_wc_version() . '. Please update WooCommerce before activating this plugin.';
		update_option( 'hpy_plugin_deferred_admin_notices', $notices );
	}

	add_action( 'admin_notices', 'hpy_plugin_admin_notices' );
	function hpy_plugin_admin_notices() {
		$notices = get_option( 'hpy_plugin_deferred_admin_notices' );
		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				echo wp_kses(
					"<div id='message' class='error'><p>$notice</p></div>",
					array(
						'div' => array(
							'id'    => true,
							'class' => true,
						),
						'p'   => array(),
					)
				);
			}
			delete_option( 'hpy_plugin_deferred_admin_notices' );
		}
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}

register_deactivation_hook( __FILE__, 'hpy_plugin_deactivation' );
function hpy_plugin_deactivation() {
	delete_option( 'hpy_plugin_deferred_admin_notices' );
}


/***************************
 * Adding Plugin Settings Link
 ***************************/
function hpy_fdv_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=wc-settings&tab=products&section=hpy_variants">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

$fdv_plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$fdv_plugin", 'hpy_fdv_settings_link' );


/**
 * Load plugin textdomain.
 */
function hpy_fdv_load_textdomain() {
	load_plugin_textdomain( 'force-default-variant-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'hpy_fdv_load_textdomain' );

/**
 * Check for WooCommerce before we do anything else.
 *
 * @since 1.7
 *
 * @return void
 */
function hpy_fdv_check_for_wc_on_activation(): void {
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'Please install and Activate WooCommerce.', 'force-default-variant-for-woocommerce' ), 'Plugin dependency check', array( 'back_link' => true ) );
	}
}
register_activation_hook( __FILE__, 'hpy_fdv_check_for_wc_on_activation', 1 );
