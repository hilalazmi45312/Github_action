<?php
/**
 * Smart Coupons Block
 *
 * @author      StoreApps
 * @since       8.7.0
 * @version     1.2.0
 *
 * @package     woocommerce-smart-coupons/blocks/
 */

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action(
	'woocommerce_blocks_loaded',
	function() {
		require_once __DIR__ . '/class-wc-sc-blocks-integration.php';
		add_action(
			'woocommerce_blocks_cart_block_registration',
			function( $integration_registry ) {
				if ( is_object( $integration_registry ) && is_callable( array( $integration_registry, 'register' ) ) && class_exists( 'WC_SC_Blocks_Integration' ) ) {
					$integration_registry->register( WC_SC_Blocks_Integration::get_instance() );
				}
			}
		);
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				if ( is_object( $integration_registry ) && is_callable( array( $integration_registry, 'register' ) ) && class_exists( 'WC_SC_Blocks_Integration' ) ) {
					$integration_registry->register( WC_SC_Blocks_Integration::get_instance() );
				}
			}
		);
	}
);

add_action(
	'woocommerce_blocks_loaded',
	function() {
		require_once __DIR__ . '/class-sc-actiontab-extend-store-endpoint.php';

		$extend = StoreApi::container()->get( ExtendSchema::class );
		SC_ActionTab_Extend_Store_Endpoint::init( $extend );
	}
);

/**
 * Registers the slug as a block category with WordPress.
 *
 * @param array $categories List of existing categories.
 * @return array
 */
function register_woocommerce_smart_coupons_block_category( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'woocommerce-smart-coupons',
				'title' => _x( 'WooCommerce Smart Coupons', 'Block editor', 'woocommerce-smart-coupons' ),
			),
		)
	);
}
add_action( 'block_categories_all', 'register_woocommerce_smart_coupons_block_category', 10, 2 );
