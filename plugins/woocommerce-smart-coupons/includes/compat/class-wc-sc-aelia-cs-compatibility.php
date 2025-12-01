<?php
/**
 * Compatibility file for WooCommerce Aelia Currency Switcher
 *
 * @author      StoreApps
 * @since       6.1.0
 * @version     1.2.0
 *
 * @package     woocommerce-smart-coupons/includes/compat/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Aelia_CS_Compatibility' ) ) {

	/**
	 * Class for handling compatibility with WooCommerce Aelia Currency Switcher
	 */
	class WC_SC_Aelia_CS_Compatibility {

		/**
		 * Variable to hold instance of WC_SC_Aelia_CS_Compatibility
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_filter( 'wc_aelia_cs_coupon_types_to_convert', array( $this, 'add_smart_coupon' ) );

			add_action( 'updated_order_meta', array( $this, 'update_order_credit_meta_on_base_currency_change' ), 10, 4 );

			// Add the coupon data filter.
			add_filter( 'woo_sc_modify_parsed_coupon_data', array( $this, 'modify_parsed_coupon_data' ), 10, 3 );
		}

		/**
		 * Get single instance of WC_SC_Aelia_CS_Compatibility
		 *
		 * @return WC_SC_Aelia_CS_Compatibility Singleton object of WC_SC_Aelia_CS_Compatibility
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add discount type 'smart_coupon' in Aelia Currency Switcher's framework
		 *
		 * @param array $coupon_types Existing coupon types.
		 * @return array $coupon_types
		 */
		public function add_smart_coupon( $coupon_types = array() ) {
			if ( empty( $coupon_types ) || ! is_array( $coupon_types ) ) {
				return $coupon_types;
			}
			if ( ! in_array( 'smart_coupon', $coupon_types, true ) ) {
				$coupon_types[] = 'smart_coupon';
			}
			return $coupon_types;
		}

		/**
		 * Modify parsed coupon data for multi-currency support (Aelia Currency Switcher)
		 *
		 * @param array $parsed_data Parsed coupon data.
		 * @param array $posted_data Posted CSV data.
		 * @param array $result      Result from parsing.
		 * @return array Modified coupon data.
		 */
		public function modify_parsed_coupon_data( $parsed_data, $posted_data, $result ) {
			if ( ! class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
				return $parsed_data;
			}

			if ( ! empty( $posted_data['_coupon_currency_data'] ) ) {
				$parsed_data['_coupon_currency_data'] = $posted_data['_coupon_currency_data'];
			}

			return $parsed_data;
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
			if ( empty( $from_currency ) ) {
				$from_currency = get_option( 'woocommerce_currency' ); // Shop base currency.
			}
			if ( empty( $to_currency ) ) {
				$to_currency = get_woocommerce_currency(); // Active currency.
			}
			return apply_filters( 'wc_aelia_cs_convert', $price, $from_currency, $to_currency );
		}

		/**
		 * Updates order credit metadata when the base currency of the order total changes.
		 *
		 * @param int    $meta_id   The meta ID.
		 * @param int    $order_id  The order ID.
		 * @param string $meta_key  The meta key being updated.
		 * @param mixed  $meta_value The new meta value.
		 */
		public function update_order_credit_meta_on_base_currency_change( $meta_id, $order_id, $meta_key, $meta_value ) {
			// Ensure we are targeting the correct meta key.
			if ( '_order_total_base_currency' !== $meta_key ) {
				return;
			}

			// Get the order object.
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			// Ensure WC_SC_Purchase_Credit class exists before using it.
			if ( ! class_exists( 'WC_SC_Purchase_Credit' ) ) {
				return;
			}

			$purchase_credit = WC_SC_Purchase_Credit::get_instance();

			// Loop through order items and update credit details.
			foreach ( $order->get_items() as $item_id => $item ) {
				$purchase_credit->save_called_credit_details_in_order_item_meta( $item_id, $item );
			}
		}

	}

}

WC_SC_Aelia_CS_Compatibility::get_instance();
