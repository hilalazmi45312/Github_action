<?php
/**
 * Stats of Order
 *
 * @author      StoreApps
 * @version     1.0.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Order_Stats' ) ) {

	/**
	 * Class to handler order's stats besed on smart coupon applied.
	 */
	class WC_SC_Order_Stats {

		/**
		 * Variable to hold instance of WC_SC_Order_Stats
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			add_filter( 'woocommerce_analytics_update_order_stats_data', array( $this, 'adjust_order_stats' ), 10, 2 );
		}

		/**
		 * Get single instance of WC_SC_Order_Stats
		 *
		 * @return WC_SC_Order_Stats Singleton object of WC_SC_Order_Stats
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
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
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
		 * Adjusts the net total in WooCommerce Analytics based on Smart Coupons used in the order.
		 *
		 * @param array    $data  The current data being processed for the order.
		 * @param WC_Order $order The order object being processed.
		 * @return array Modified data for the order, adjusted for Smart Coupons.
		 */
		public function adjust_order_stats( $data, $order ) {
			// Ensure the order object is valid and contains store credit, and the data array is not empty.
			if ( $order instanceof WC_Order && ! empty( $data ) && $this->is_order_contains_store_credit( $order ) && $data['net_total'] < 0 ) {

				// Get the original order total before discounts.
				$order_total_before_discount = (float) $this->get_original_order_total_before_discount( $order );

				// Initialize totals.
				$order_discount_total = 0.0;
				$order_discount_tax   = 0.0;

				// Retrieve coupon items.
				$coupons = $order->get_items( 'coupon' );

				if ( ! empty( $coupons ) ) {
					foreach ( $coupons as $item_id => $item ) {
						// Retrieve coupon code.
						$coupon_code = ( is_object( $item ) && is_callable( array( $item, 'get_name' ) ) ) ? $item->get_name() : trim( $item['name'] );

						if ( empty( $coupon_code ) ) {
							continue;
						}

						// Retrieve discount amount and tax.
						$discount_amount     = ( is_object( $item ) && is_callable( array( $item, 'get_discount' ) ) ) ? $item->get_discount() : $this->get_order_item_meta( $item_id, 'discount_amount', true, true );
						$discount_amount_tax = ( is_object( $item ) && is_callable( array( $item, 'get_discount_tax' ) ) ) ? $item->get_discount_tax() : $this->get_order_item_meta( $item_id, 'discount_amount_tax', true, true );

						// Ensure amounts are numeric and add to totals.
						$order_discount_total += (float) $discount_amount;
						$order_discount_tax   += (float) $discount_amount_tax;
					}

					// Adjust the net total.
					$data['net_total'] = $order_total_before_discount
						- (float) $order->get_shipping_total()
						- (float) $order->get_shipping_tax()
						- $order_discount_total
						- $order_discount_tax;

					if ( $data['net_total'] < 0 ) {
						$data['net_total'] = $order->get_total();
					}
				}
			}

			return $data;
		}
	}
}

WC_SC_Order_Stats::get_instance();
