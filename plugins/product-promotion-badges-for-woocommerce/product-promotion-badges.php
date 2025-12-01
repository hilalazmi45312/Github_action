<?php
/**
 * Plugin Name: Product Promotion Badges
 * Plugin URI: https://gutenmate.com/
 * Description: Add custom badges to WooCommerce products to highlight promotions.
 * Version: 1.2.0
 * Author: Gutenmate
 * Author URI: https://gutenmate.com/
 * Developer: Gutenmate
 * Developer URI: https://gutenmate.com/
 * Text Domain: product-promotion-badge
 * Domain Path: /languages
 *
 * Woo: 18734003825502:0f0832899df01d8af03f49ce927aff1d
 * WC requires at least: 6.8
 * WC tested up to: 9.4.2
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

defined( 'GTMPB' ) || define( 'GTMPB', true );
defined( 'GTMPB_LOCATION_LOOP' ) || define( 'GTMPB_LOCATION_LOOP', 'loop' );
defined( 'GTMPB_LOCATION_SINGLE' ) || define( 'GTMPB_LOCATION_SINGLE', 'single' );
defined( 'GTMPB_LOCATION_PRODUCT_IMAGE_BLOCK' ) || define( 'GTMPB_LOCATION_PRODUCT_IMAGE_BLOCK', 'product_image_block' );
defined( 'GTMPB_LOCATION_SLOT' ) || define( 'GTMPB_LOCATION_SLOT', 'slot' );
defined( 'GTMPB_BADGE_PRESET_URI' ) || define( 'GTMPB_BADGE_PRESET_URI', esc_url( plugin_dir_url( __FILE__ ) . 'assets/badge-presets/' ) );
defined( 'GTMPB_NO_IMG_ALIGMENT_BLOCKS' ) || define( 'GTMPB_NO_IMG_ALIGMENT_BLOCKS', array( 'core/group' ) );

/**
 * Load when WooCommerce is activated
 */
add_action( 'woocommerce_init', 'gtmpb_woocommerce_init' );
function gtmpb_woocommerce_init() {
	require_once 'load.php';
}

/**
 * Declaring extension compatibility with HPOS
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
