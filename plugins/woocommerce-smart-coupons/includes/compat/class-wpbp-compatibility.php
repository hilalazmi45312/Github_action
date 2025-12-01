<?php
/**
 * Compatibility file for Woo Product Bundle
 *
 * @author      StoreApps
 * @since       9.15.0
 * @version     1.0.0
 * @package     WooCommerce Smart Coupons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WPBP_Compatibility' ) ) {

	/**
	 * Class for handling compatibility with WooCommerce Bundle
	 */
	class WPBP_Compatibility {

		/**
		 * Variable to hold instance of WPBP_Compatibility
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active( 'woo-product-bundle-premium/wpc-product-bundles.php' ) ) {
				add_filter( 'woocommerce_coupon_custom_discounts_array', array( $this, 'store_credit_discounts_array' ), 10, 2 );
			}

		}

		/**
		 * Get single instance of WPBP_Compatibility
		 *
		 * @return WPBP_Compatibility Singleton object of WPBP_Compatibility
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return mixed Result of function call.
		 */
		public function __call( $function_name, $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}
		}


		/**
		 * Discount details for store credit
		 *
		 * @param  array     $discounts The discount details.
		 * @param  WC_Coupon $coupon    The coupon object.
		 * @return array
		 */
		public function store_credit_discounts_array( $discounts = array(), $coupon = null ) {
			if ( ! $coupon instanceof WC_Coupon ) {
				return $discounts;
			}
			$discount_type = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_discount_type' ) ) ) ? $coupon->get_discount_type() : '';
			if ( 'smart_coupon' !== $discount_type ) {
				return $discounts;
			}
			$cart = ( isset( WC()->cart ) ) ? WC()->cart : '';
			if ( $cart instanceof WC_Cart ) {
				$cart_contents = ( is_object( WC()->cart ) && is_callable( array( WC()->cart, 'get_cart' ) ) ) ? WC()->cart->get_cart() : array();
				if ( ! empty( $cart_contents ) ) {
					$discount_type = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_discount_type' ) ) ) ? $coupon->get_discount_type() : '';

					if ( ! empty( $discounts ) ) {
						foreach ( $discounts as $item_key => $discount ) {
							$cart_item = $cart_contents[ $item_key ];
							if ( isset( $cart_item['woosb_ids'], $cart_item['woosb_price'] ) ) {
								$discounts[ $item_key ] = 0;
							}
						}
					}
				}
			}
			return $discounts;
		}



	}

}

WPBP_Compatibility::get_instance();
