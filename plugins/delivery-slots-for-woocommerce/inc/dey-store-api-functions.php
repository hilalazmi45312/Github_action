<?php

/**
 * Store API functions.
 * 
 * @since 3.7.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!function_exists('dey_is_block_cart')) {

	/**
	 * Is a block cart page?.
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	function dey_is_block_cart() {
		static $is_block_cart;
		if (isset($is_block_cart)) {
			return $is_block_cart;
		}

		global $post;
		$is_singular = true;
		if (!is_a($post, 'WP_Post')) {
			$is_singular = false;
		}

		// Consider as block cart while the request call via Store API.
		if (isset($GLOBALS['wp']->query_vars['rest_route']) && false !== strpos($GLOBALS['wp']->query_vars['rest_route'], '/wc/store/v1')) {
			return true;
		}

		$is_block_cart = $is_singular && has_block('woocommerce/cart', $post);

		return $is_block_cart;
	}

}

if (!function_exists('dey_is_block_checkout')) {

	/**
	 * Is a block checkout page?.
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	function dey_is_block_checkout() {
		static $is_block_checkout;
		if (isset($is_block_checkout)) {
			return $is_block_checkout;
		}

		global $post;
		$is_singular = true;
		if (!is_a($post, 'WP_Post')) {
			$is_singular = false;
		}

		// Consider as block checkout while the request call via Store API.
		if (isset($GLOBALS['wp']->query_vars['rest_route']) && false !== strpos($GLOBALS['wp']->query_vars['rest_route'], '/wc/store/v1')) {
			return true;
		}

		$is_block_checkout = $is_singular && has_block('woocommerce/checkout', $post);

		return $is_block_checkout;
	}

}

if (!function_exists('dey_get_cart_block_order_tip_html')) {

	/**
	 * Get the cart block of order tip HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	function dey_get_cart_block_order_tip_html() {
		// Return if the order tip is not valid to display.
		if (!can_render_order_tip_in_cart()) {
			return '';
		}

		/**
		 * This hook is used to alter the order tip wrapper file name in cart block.
		 *
		 * @since 3.7.0
		 */
		$file_name = apply_filters('dey_cart_block_order_tip_file_name', 'blocks/cart-order-tip.php');

		return dey_get_template_html($file_name);
	}

}

if (!function_exists('dey_get_checkout_block_order_tip_html')) {

	/**
	 * Get the checkout block of order tip HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	function dey_get_checkout_block_order_tip_html() {
		// Return if the order tip is not valid to display.
		if (!can_render_order_tip_in_checkout()) {
			return '';
		}

		/**
		 * This hook is used to alter the order tip wrapper file name in checkout block.
		 *
		 * @since 3.7.0
		 */
		$file_name = apply_filters('dey_checkout_block_order_tip_file_name', 'blocks/checkout-order-tip.php');

		return dey_get_template_html($file_name);
	}

}

if ( ! function_exists( 'dey_get_block_tip_fee_html' ) ) {

	/**
	 * Get the block tip fee HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	function dey_get_block_tip_fee_html() {
		// Return if the fees does not exists.
		$fees = WC()->cart->get_fees();
		if ( ! dey_check_is_array( $fees ) ) {
			return;
		}
		
		// Return if order tip fee does not exists.
		if ( ! array_key_exists( DEY()->order_tip_fee_name(), $fees ) ) {
			return;
		}

		return dey_get_template_html( 'blocks/fee-wrapper.php', array( 'fee' => $fees[DEY()->order_tip_fee_name()] ));
	}

}
