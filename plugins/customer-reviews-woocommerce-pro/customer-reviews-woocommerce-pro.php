<?php
/*
Plugin Name: Customer Reviews for WooCommerce PRO
Description: Supercharge <strong>Customer Reviews for WooCommerce</strong> plugin with additional features using this Pro add-on. Please make sure that both the main plugin and the Pro add-on are installed and activated together.
Plugin URI: https://www.cusrev.com/business/
Update URI: https://www.cusrev.com/download/info.json
Version: 1.12.4
Author: CusRev
Author URI: https://www.cusrev.com/business/
Text Domain: customer-reviews-woocommerce-pro
Domain Path: /languages
Requires at least: 4.5
WC requires at least: 3.6
WC tested up to: 10.0
License: GPLv3

Customer Reviews for WooCommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

Customer Reviews for WooCommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Customer Reviews for WooCommerce. If not, see https://www.gnu.org/licenses/gpl.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( __DIR__ . '/includes/class-crpro.php' );

$crpro_activated_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ||
 	( is_multisite() && isset( $crpro_activated_plugins['woocommerce/woocommerce.php'] ) ) ) {
	add_action('init', 'crpro_init', 10);

	function crpro_init() {
		load_plugin_textdomain( 'customer-reviews-woocommerce-pro', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
		$args = array(
			'slug' => plugin_basename( __DIR__ ),
			'plugin' => plugin_basename( __FILE__ )
		);
		new CRPRO( $args );
	}

	add_action( 'after_setup_theme', 'crpro_setup_theme', 1 );

	function crpro_setup_theme() {
		$crpro_local_forms = new CRPRO_Local_Forms();
	}

	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );

}

register_deactivation_hook( __FILE__, array( 'CRPRO', 'deactivation_cleanup' ) );
