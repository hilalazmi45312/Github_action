<?php
/**
 * Functions for setting product weights using calculations
 * @since 3.9.5
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the product weight based on the value of a field
 * @since 3.9.5
 */
function pewc_set_product_dimensions_meta( $cart_item_data, $item, $group_id, $field_id, $value ) {

	$item_weight = ! empty( $cart_item_data['product_extras']['weight'] ) ? $cart_item_data['product_extras']['weight'] : 0;
	if( $item['field_type'] == 'calculation' && ( ! empty( $item['formula_action'] ) && $item['formula_action'] == 'weight' ) ) {
		$cart_item_data['product_extras']['weight'] = $item_weight + $value;
	}

	$item_length = ! empty( $cart_item_data['product_extras']['length'] ) ? $cart_item_data['product_extras']['length'] : 0;
	if( $item['field_type'] == 'calculation' && ( ! empty( $item['formula_action'] ) && $item['formula_action'] == 'length' ) ) {
		$cart_item_data['product_extras']['length'] = $item_length + $value;
	}

	$item_width = ! empty( $cart_item_data['product_extras']['width'] ) ? $cart_item_data['product_extras']['width'] : 0;
	if( $item['field_type'] == 'calculation' && ( ! empty( $item['formula_action'] ) && $item['formula_action'] == 'width' ) ) {
		$cart_item_data['product_extras']['width'] = $item_width + $value;
	}

	$item_height = ! empty( $cart_item_data['product_extras']['height'] ) ? $cart_item_data['product_extras']['height'] : 0;
	if( $item['field_type'] == 'calculation' && ( ! empty( $item['formula_action'] ) && $item['formula_action'] == 'height' ) ) {
		$cart_item_data['product_extras']['height'] = $item_height + $value;
	}

	return $cart_item_data;

}
add_filter( 'pewc_filter_end_add_cart_item_data', 'pewc_set_product_dimensions_meta', 10, 5 );

/**
 * Update the product weight
 * @since	3.9.5
 * @version 3.13.7
 */
function pewc_set_product_dimensions( $cart ) {

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	$did_filter = function_exists( 'did_filter' ) ? did_filter( 'woocommerce_cart_shipping_packages' ) : 0; // Backwards compatibility since did_filter is introduced in WP 6.1

	$filter_limit = apply_filters( 'pewc_set_product_weight_filter_limit', 2 ); // 3.17.2, for compatibility issues with other weight plugins

	if (
		( did_action( 'woocommerce_before_calculate_totals' ) + did_action( 'woocommerce_checkout_update_order_review' ) + $did_filter ) >= $filter_limit
	) {
		return;
	}

	if ( ! ( $cart instanceof WC_Cart ) ) {
		// woocommerce_checkout_update_order_review passes string ($_POST['post_data']) not the cart
		$cart = WC()->cart;
	}

	foreach( $cart->get_cart() as $cart_item ) {
		$item_weight = floatval( $cart_item['data']->get_weight() );
		if ( ! empty( $cart_item['product_extras']['weight'] ) ) {
			$item_weight += $cart_item['product_extras']['weight'];
			$cart_item['data']->set_weight( $item_weight );
		}

		$item_length = floatval( $cart_item['data']->get_length() );
		if ( ! empty( $cart_item['product_extras']['length'] ) ) {
			$item_length += $cart_item['product_extras']['length'];
			$cart_item['data']->set_length( $item_length );
		}

		$item_width = floatval( $cart_item['data']->get_width() );
		if ( ! empty( $cart_item['product_extras']['width'] ) ) {
			$item_width += $cart_item['product_extras']['width'];
			$cart_item['data']->set_width( $item_width );
		}

		$item_height = floatval( $cart_item['data']->get_height() );
		if ( ! empty( $cart_item['product_extras']['height'] ) ) {
			$item_height += $cart_item['product_extras']['height'];
			$cart_item['data']->set_height( $item_height );
		}
	}

}
add_filter( 'woocommerce_before_calculate_totals', 'pewc_set_product_dimensions', 100 );
add_filter( 'woocommerce_checkout_update_order_review', 'pewc_set_product_dimensions', 100 );
