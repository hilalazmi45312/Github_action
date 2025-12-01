<?php
/**
 * Compatibility file for WooPayments Currency
 *
 * @author      StoreApps
 * @since       8.18.0
 * @version     1.0.0
 * @package     woocommerce-smart-coupons/includes/compat/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WCPay\MultiCurrency\MultiCurrency;

if ( ! class_exists( 'WC_SC_WooPayments_Compatibility' ) ) {

	/**
	 * Class for handling compatibility with WooPayments
	 */
	class WC_SC_WooPayments_Compatibility {
		/**
		 * Variable to hold instance of WC_SC_WooPayments_Compatibility
		 *
		 * @var $instance
		 */
		private static $instance = null;
		/**
		 * Constructor
		 */
		public function __construct() {
			add_filter( 'wcpay_multi_currency_should_convert_product_price', array( $this, 'should_convert_product_price' ), 10, 2 );
		}
		/**
		 * Get single instance of WC_SC_WooPayments_Compatibility
		 *
		 * @return WC_SC_WooPayments_Compatibility Singleton object of WC_SC_WooPayments_Compatibility
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
		 * Check & convert price
		 *
		 * @param float  $price The price need to be converted.
		 * @param string $to_currency The price will be converted to this currency.
		 * @param string $from_currency The price will be converted from this currency.
		 * @return float
		 */
		public function convert_price( $price = 0, $to_currency = null, $from_currency = null ) {

			$multicurency = MultiCurrency::instance();
			if ( is_callable( array( $multicurency, 'get_raw_conversion' ) ) ) {

				if ( ! is_float( $price ) ) {
					$price = (float) $price;
				}

				if ( $from_currency !== $to_currency ) {

					$price = $multicurency->get_raw_conversion( $price, $to_currency, $from_currency );
					if ( ! empty( $price ) && is_float( $price ) ) {
						$price = wc_round_tax_total( $price, 4 );
					}
				}
			}
			return $price;
		}
		/**
		 * Check if product price should be convert or not
		 *
		 * @param bool   $should_convert should convert or not.
		 * @param object $product instance of the product.
		 * @return float
		 */
		public function should_convert_product_price( $should_convert = true, $product = null ) {

			global $woocommerce_smart_coupon;
			if ( ! $product instanceof WC_Product ) {
				return $should_convert;
			}

			$coupons = $woocommerce_smart_coupon->get_coupon_titles( array( 'product_object' => $product ) );
			if ( ! empty( $coupons ) && $woocommerce_smart_coupon->is_coupon_amount_pick_from_product_price( $coupons ) ) {

				foreach ( $coupons as $coupon_title ) {
					$coupon_of_product        = new WC_Coupon( $coupon_title );
					$discount_type_of_product = ( is_object( $coupon_of_product ) && is_callable( array( $coupon_of_product, 'get_discount_type' ) ) ) ? $coupon_of_product->get_discount_type() : '';
					if ( 'smart_coupon' === $discount_type_of_product ) {
						return false;
					}
				}
			}

			return $should_convert;
		}


	}

}

WC_SC_WooPayments_Compatibility::get_instance();
