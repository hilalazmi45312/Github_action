<?php
/**
 * Functions for WooCommerce Checkout Blocks
 * @since 3.19.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout Blocks do not trigger `woocommerce_checkout_order_processed`, so use `woocommerce_store_api_checkout_order_processed` instead
 * @since 3.19.0
 */
function pewc_create_product_extra_from_checkout_blocks( $order ){

	// call our existing function triggered by `woocommerce_checkout_order_processed`
	pewc_create_product_extra( $order->get_id() );

}
add_action( 'woocommerce_store_api_checkout_order_processed', 'pewc_create_product_extra_from_checkout_blocks', 10, 1 );
