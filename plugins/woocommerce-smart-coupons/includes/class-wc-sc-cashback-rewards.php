<?php
/**
 * Smart Coupons Cashback Rewards
 *
 * @author      StoreApps
 * @since       9.61.0
 * @version     1.0.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Cashback_Rewards' ) ) {

	/**
	 * Class for handling cashback rewards functionality
	 */
	class WC_SC_Cashback_Rewards {

		/**
		 * Variable to hold instance of WC_SC_Cashback_Rewards
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			// Hook into order status changes.
			add_action( 'woocommerce_order_status_changed', array( $this, 'process_cashback_on_order_status_change' ), 10, 4 );

			// Hook into order creation to mark eligible orders.
			add_action( 'woocommerce_new_order', array( $this, 'mark_order_cashback_eligible' ), 999, 2 );
		}

		/**
		 * Get single instance of WC_SC_Cashback_Rewards
		 *
		 * @return WC_SC_Cashback_Rewards Singleton object of WC_SC_Cashback_Rewards
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
		 * @return mixed of function call
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
		 * Process cashback when order status changes
		 *
		 * @param int      $order_id Order ID.
		 * @param string   $old_status Old order status.
		 * @param string   $new_status New order status.
		 * @param WC_Order $order Order object.
		 */
		public function process_cashback_on_order_status_change( $order_id, $old_status, $new_status, $order ) {
			try {

				// Ensure we have a WC_Order object. Some WooCommerce versions/actions pass only the order ID.
				if ( ! $order || ! $order instanceof WC_Order ) {
					$order = wc_get_order( $order_id );
					if ( ! $order || ! $order instanceof WC_Order ) {
						return;
					}
				}

				// Get valid order statuses for coupon auto generation.
				$valid_order_statuses = get_option( 'wc_sc_valid_order_statuses_for_coupon_auto_generation', wc_get_is_paid_statuses() );

				// Check if new status is in valid statuses.
				if ( ! in_array( $new_status, $valid_order_statuses, true ) ) {
					return;
				}

				// Check if order is eligible for cashback (single meta check).
				if ( ! $this->is_order_cashback_eligible( $order_id ) ) {
					return;
				}

				// Process cashback and remove eligibility meta.
				$this->process_cashback_for_order( $order );
				$this->remove_cashback_eligibility( $order );

			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Process cashback for a specific order
		 *
		 * @param WC_Order $order Order object.
		 */
		public function process_cashback_for_order( $order ) {
			try {
				// Get cashback settings.
				$cashback_settings = get_option( 'wc_sc_cashback', array() );

				if ( empty( $cashback_settings[1] ) || empty( $cashback_settings[1]['enabled'] ) ) {
					return;
				}

				$cashback_rule = $cashback_settings[1];

				// Check if cashback is enabled.
				if ( true !== (bool) $cashback_rule['enabled'] ) {
					return;
				}

				// Get order total.
				$order_total     = $order->get_total();
				$min_order_value = isset( $cashback_rule['min_order_value'] ) ? floatval( $cashback_rule['min_order_value'] ) : 0;

				// Check minimum order value.
				if ( $order_total < $min_order_value ) {
					return;
				}

				// Calculate cashback amount.
				$cashback_amount = $this->calculate_cashback_amount( $order_total, $cashback_rule );

				if ( $cashback_amount <= 0 ) {
					return;
				}

				// Create store credit coupon.
				$this->create_cashback_store_credit( $order, $cashback_amount, $cashback_rule );

			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Calculate cashback amount based on order total and cashback rule
		 *
		 * @param float $order_total Order total.
		 * @param array $cashback_rule Cashback rule settings.
		 * @return float Calculated cashback amount.
		 */
		public function calculate_cashback_amount( $order_total, $cashback_rule ) {
			$amount = isset( $cashback_rule['amount'] ) ? floatval( $cashback_rule['amount'] ) : 0;
			$type   = isset( $cashback_rule['type'] ) ? $cashback_rule['type'] : 'fixed';

			if ( 'percentage' === $type ) {
				// Calculate percentage of order total.
				return ( $order_total * $amount ) / 100;
			} else {
				// Fixed amount.
				return $amount;
			}
		}

		/**
		 * Create store credit coupon for cashback
		 *
		 * @param WC_Order $order Order object.
		 * @param float    $cashback_amount Cashback amount.
		 * @param array    $cashback_rule Cashback rule settings.
		 */
		public function create_cashback_store_credit( $order, $cashback_amount, $cashback_rule ) {
			try {
				global $woocommerce_smart_coupon;

				// Ensure plugin helper exists before generating coupon.
				if ( ! is_object( $woocommerce_smart_coupon ) || ! method_exists( $woocommerce_smart_coupon, 'generate_smart_coupon' ) ) {
					return;
				}

				// Get customer email.
				$customer_email = $order->get_billing_email();
				if ( empty( $customer_email ) ) {
					return;
				}

				// Get template coupon if specified.
				$template_coupon_id = isset( $cashback_rule['template_coupon_id'] ) ? $cashback_rule['template_coupon_id'] : '';

				$coupon = new WC_Coupon( $template_coupon_id );

				if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
					$coupon = null; // No valid template coupon.
				}

				// Generate store credit coupon.
				$woocommerce_smart_coupon->generate_smart_coupon(
					$customer_email,
					$cashback_amount,
					$order->get_id(),
					$coupon,
					'smart_coupon',
					'', // gift_certificate_receiver_name.
					'', // message_from_sender.
					'', // gift_certificate_sender_name.
					'', // gift_certificate_sender_email.
					''  // sending_timestamp.
				);
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Mark order as eligible for cashback when created
		 * This single meta field approach prevents old orders from getting cashback
		 *
		 * @param int           $order_id Order ID.
		 * @param WC_Order|null $order Order object (optional).
		 */
		public function mark_order_cashback_eligible( $order_id, $order = null ) {
			try {
				// Get cashback settings.
				$cashback_settings = get_option( 'wc_sc_cashback', array() );
				$enabled = $cashback_settings[1]['enabled'] ?? false;
				$cashback_enabled = (bool) $enabled === true;

				// Only mark as eligible if cashback is enabled.
				if ( $cashback_enabled ) {
					// Try to ensure we have a WC_Order object.
					if ( ! $order || ! $order instanceof WC_Order ) {
						$order = wc_get_order( $order_id );
					}

					if ( $order && $order instanceof WC_Order ) {
						$order->add_meta_data( 'wc_sc_cashback_eligible', 'yes' );
						$order->save_meta_data();
					} else {
						// Fallback: store as post meta to avoid fatal if order object is not available.
						update_post_meta( $order_id, 'wc_sc_cashback_eligible', 'yes' );
					}
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Check if order is eligible for cashback
		 *
		 * @param int $order_id Order ID.
		 * @return bool True if eligible, false otherwise.
		 */
		public function is_order_cashback_eligible( $order_id ) {
			return 'yes' === get_post_meta( $order_id, 'wc_sc_cashback_eligible', true );
		}

		/**
		 * Remove cashback eligibility from order (after processing)
		 *
		 * @param WC_Order $order Order object.
		 */
		public function remove_cashback_eligibility( $order ) {
			if ( $order instanceof WC_Order ) {
				$order->delete_meta_data( 'wc_sc_cashback_eligible' );
				$order->save_meta_data();
			}
		}

	}

}

// Initialize the class.
WC_SC_Cashback_Rewards::get_instance();
