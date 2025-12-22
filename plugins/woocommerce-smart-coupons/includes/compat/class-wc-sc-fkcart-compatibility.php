<?php
/**
 * Compatibility file for FunnelKit Cart for WooCommerce
 *
 * @author      StoreApps
 * @since       1.0.0
 * @version     1.0.0
 * @package     WooCommerce Smart Coupons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_FKCart_Compatibility' ) ) {

	/**
	 * Class for handling compatibility with FunnelKit Cart for WooCommerce
	 */
	class WC_SC_FKCart_Compatibility {

		/**
		 * Variable to hold instance of WC_SC_FKCart_Compatibility
		 *
		 * @var WC_SC_FKCart_Compatibility
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_filter( 'wc_sc_call_for_credit_product_id', array( $this, 'funnelkit_call_for_credit_product_id' ), 10, 2 );
		}

		/**
		 * Get single instance of WC_SC_FKCart_Compatibility
		 *
		 * @return WC_SC_FKCart_Compatibility
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments     Arguments to pass.
		 * @return mixed
		 */
		public function __call( $function_name, $arguments = array() ) {
			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			}

			return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
		}

		/**
		 * Get product ID for credit purchase when using FunnelKit Cart
		 *
		 * @param  int   $product_id The product ID.
		 * @param  array $args       Additional arguments.
		 * @return int
		 */
		public function funnelkit_call_for_credit_product_id( $product_id = 0, $args = array() ) {

			$action     = isset( $_REQUEST['wc-ajax'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['wc-ajax'] ) ) : ''; // phpcs:ignore
			$product_id = ( 'fkcart_add_item' === $action && empty( $product_id ) && isset( $_REQUEST['fkcart_product_id'] ) ) // phpcs:ignore
				? absint( wp_unslash( $_REQUEST['fkcart_product_id'] ) ) // phpcs:ignore
				: $product_id;

			return $product_id;

		}

	}

}

/**
 * Initialize the compatibility
 */
function initialize_funnelkit_cart_sc_compatibility() {
	WC_SC_FKCart_Compatibility::get_instance();
}
add_action( 'funnelkit_cart_loaded', 'initialize_funnelkit_cart_sc_compatibility' );
