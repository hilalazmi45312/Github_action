<?php
/**
 * Plugin Name: WooCommerce Google Product Feed
 * Plugin URI: https://woocommerce.com/products/google-product-feed/
 * Description: WooCommerce extension that allows you to more easily populate advanced attributes into the Google Merchant Centre feed
 * Author: Ademti Software Ltd.
 * Version: 11.0.20
 * Woo: 18619:d55b4f852872025741312839f142447e
 * WC requires at least: 9.4
 * WC tested up to: 9.9
 * Requires PHP: 7.4.0
 * Requires at least: 6.2
 * Tested up to: 6.6
 * Author URI: https://www.ademti-software.co.uk/
 * License: GPLv3
 *
 * @package woocommerce-gpf
 */

defined( 'ABSPATH' ) || exit;

// The current DB schema version.
const WOOCOMMERCE_GPF_DB_VERSION = 17;

// The current version.
const WOOCOMMERCE_GPF_VERSION = '11.0.20';

$woocommerce_gpf_dirname = __DIR__ . '/';

require_once $woocommerce_gpf_dirname . 'vendor/autoload.php';
require_once $woocommerce_gpf_dirname . 'woocommerce-product-feeds-install.php';
require_once $woocommerce_gpf_dirname . 'woocommerce-product-feeds-template-functions.php';
require_once $woocommerce_gpf_dirname . 'woocommerce-product-feeds-bootstrap.php';

register_activation_hook( __FILE__, 'woocommerce_gpf_install' );

/**
 * Run the plugin.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return;
		}
		global $woocommerce_product_feeds_di;
		$woocommerce_product_feeds_main = $woocommerce_product_feeds_di['Main'];
		$woocommerce_product_feeds_main->run();
	},
	1,
	0
);

/**
 * Declare support for WooCommerce features.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);
