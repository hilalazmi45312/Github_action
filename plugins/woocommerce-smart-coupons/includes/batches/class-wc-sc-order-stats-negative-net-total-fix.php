<?php
/**
 * Class to generate available coupon data in DB
 *
 * @package     woocommerce-smart-coupons/includes/
 * @since       9.6.0
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_Background_Process', false ) ) {
	if ( file_exists( trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/abstracts/class-wc-sc-background-process.php' ) ) {
		include_once trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/abstracts/class-wc-sc-background-process.php';
	}
}

if ( ! class_exists( 'WC_SC_Order_Stats_Negative_Net_Total_Fix' ) && class_exists( 'WC_SC_Background_Process' ) ) {

	/**
	 * WC_SC_Background_Process class.
	 */
	class WC_SC_Order_Stats_Negative_Net_Total_Fix extends WC_SC_Background_Process {

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get the single instance of this class
		 *
		 * @return WC_SC_Order_Stats_Negative_Net_Total_Fix
		 */
		public static function get_instance() {
			// Check if the instance already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {

			// Set the batch limit.
			$this->batch_limit = 1;

			// Set the action name.
			$this->action = 'wc_sc_order_stats_negative_net_total_fix';

			// Initialize the parent class to execute background process.
			parent::__construct();

			add_action( $this->action . '_process_completed', array( $this, 'finalize' ) );
		}

		/**
		 * Execute the task for each batch.
		 *
		 * @param array $order_ids The order ids's to process.
		 *
		 * @throws Exception If any problem during the process.
		 */
		public function task( $order_ids = array() ) {
			if ( empty( $order_ids ) ) {
				delete_transient( 'sc_get_wc_negative_order_stats_status' );
				throw new Exception(
					sprintf(
						/* translators: 1: Current task name */
						_x( 'No order id\'s passed', 'Error message for fixing order stats negative net value', 'woocommerce-smart-coupons' )
					),
					__CLASS__
				);
			}

			foreach ( $order_ids as $order_id ) {
				$this->sc_fix_negative_order_net_total( $order_id );
			}

			// Check process health before continuing.
			if ( ! $this->health_status() ) {
				throw new Exception(
					sprintf(
						/* translators: 1: The task class name */
						_x( 'Batch stopped due to health status in task: %s', 'Logger for stopped batch process due to health status', 'woocommerce-smart-coupons' ),
						__CLASS__
					)
				);
			}
		}

		/**
		 * Get the remaining items for doing the action.
		 *
		 * @return array The function that needs to be processed.
		 */
		public function get_remaining_items() {
			global $wpdb;

			// Apply filter for limit.
			$limit = apply_filters( 'wc_sc_limit_for_get_orders_with_negative_value', 10 );

			$order_dates = apply_filters(
				'wc_sc_date_for_get_orders_with_negative_value',
				array(
					'start_date' => '2024-01-01 00:00:00',
					'end_date'   => current_time( 'mysql' ),
				)
			);
			// phpcs:disable
			$like_clause = '%' . $wpdb->esc_like('"sc_negative_net_total_stats_fix_status";s:3:"yes";') . '%';
			$query = $wpdb->prepare(
				"SELECT stats.order_id
				FROM {$wpdb->prefix}wc_order_stats AS stats
				INNER JOIN {$wpdb->prefix}wc_orders_meta AS meta ON stats.order_id = meta.order_id
				WHERE stats.net_total < %d
				AND meta.meta_key = %s
				AND meta.meta_value != %s -- Exclude empty serialized array.
				AND stats.date_created BETWEEN %s AND %s
				AND (
					-- Check in wc_orders_meta
					NOT EXISTS (
						SELECT 1
						FROM {$wpdb->prefix}wc_orders_meta AS wc_orders_meta
						WHERE wc_orders_meta.order_id = stats.order_id
						AND wc_orders_meta.meta_key = %s
						AND wc_orders_meta.meta_value != %s
					)
					OR
					-- Check in postmeta
            		NOT EXISTS (
						SELECT 1
						FROM {$wpdb->prefix}postmeta AS env_meta
						WHERE env_meta.post_id = stats.order_id
						AND env_meta.meta_key = %s
						AND env_meta.meta_value LIKE %s -- Exclude serialized 'yes' for 'sc_negative_net_total_stats_fix_status'
					)
				)
				LIMIT %d",
				0,  // net_total check.
				'smart_coupons_contribution',  // Meta key for coupons.
				'a:0:{}',  // Exclude empty serialized array.
				$order_dates['start_date'],
				$order_dates['end_date'],
				'wc_sc_environment',  // wc_orders_meta meta key for environment.
				$like_clause,  // Use esc_like for LIKE clause.
				'wc_sc_environment',  // postmeta meta key for environment.
				$like_clause,  // Use esc_like for LIKE clause.
				$limit
			);

			$order_ids = $wpdb->get_col( $query ) ?: array();
			// phpcs:enable

			return $order_ids;
		}

		/**
		 * Handle order fix by recalculating and updating the net total for an order that contains a Smart Coupon.
		 *
		 * @param int $order_id The ID of the order to be fixed.
		 * @throws Exception If the order is not found, does not contain a Smart Coupon, or no coupons are found.
		 */
		public function sc_fix_negative_order_net_total( $order_id ) {
			global $woocommerce_smart_coupon, $wpdb;

			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				throw new Exception(
					sprintf(
						/* translators: 1: Order ID */
						_x( 'Order not found. Order ID: %s', 'Error message for missing order', 'woocommerce-smart-coupons' ),
						$order_id
					)
				);
			}

			$this->update_wc_sc_environment_meta( $order_id );

			if ( $order->get_status() === 'refunded' || ! $woocommerce_smart_coupon->is_order_contains_store_credit( $order ) ) {
				throw new Exception(
					sprintf(
						/* translators: 1: Order ID */
						_x( 'Smart Coupon not used. Order ID: %s', 'Error message for orders without Smart Coupon', 'woocommerce-smart-coupons' ),
						$order_id
					)
				);
			}

			$order_total_before_discount = (float) $woocommerce_smart_coupon->get_original_order_total_before_discount( $order );
			$coupons                     = $order->get_items( 'coupon' );

			if ( empty( $coupons ) ) {
				throw new Exception(
					sprintf(
						/* translators: 1: Order ID */
						_x( 'No coupons found in the order. Order ID: %s', 'Error message for missing coupons', 'woocommerce-smart-coupons' ),
						$order_id
					)
				);
			}

			$order_discount_total = 0.0;
			$order_discount_tax   = 0.0;

			foreach ( $coupons as $item ) {
				$order_discount_total += (float) $item->get_discount();
				$order_discount_tax   += (float) $item->get_discount_tax();
			}

			$new_net_total = $order_total_before_discount
				- (float) $order->get_shipping_total()
				- (float) $order->get_shipping_tax()
				- $order_discount_total
				- $order_discount_tax;

			if ( $new_net_total < 0 ) {
				$new_net_total = $order->get_total();
			}

			// phpcs:disable
			$result = $wpdb->update(
				"{$wpdb->prefix}wc_order_stats",
				array( 'net_total' => $new_net_total ),
				array( 'order_id' => $order_id ),
				array( '%f' ),
				array( '%d' )
			);
			// phpcs:enable

			if ( false === $result ) {
				throw new Exception(
					sprintf(
						/* translators: 1: Order ID */
						_x( 'Failed to update net total for Order ID: %s', 'Error message for failed net total update', 'woocommerce-smart-coupons' ),
						$order_id
					)
				);
			}

		}

		/**
		 * Update or add order meta for wc_sc_environment in both wc_orders_meta and postmeta tables.
		 *
		 * @param int $order_id The order ID.
		 */
		public function update_wc_sc_environment_meta( $order_id ) {
			global $wpdb;

			// Meta key to update.
			$meta_key = 'wc_sc_environment';
			// phpcs:disable
			// Fetch meta from wc_orders_meta table.
			$meta_from_wc_orders = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value 
					FROM {$wpdb->prefix}wc_orders_meta 
					WHERE order_id = %d AND meta_key = %s",
					$order_id,
					$meta_key
				)
			);
			// phpcs:enable
			// Fetch meta from postmeta table.
			$meta_from_postmeta = get_post_meta( $order_id, $meta_key, true );

			// Deserialize and combine meta from both sources.
			$meta_from_wc_orders = maybe_unserialize( $meta_from_wc_orders );
			$meta_from_postmeta  = maybe_unserialize( $meta_from_postmeta );

			// Initialize final meta array.
			$combined_meta = array();

			if ( is_array( $meta_from_wc_orders ) ) {
				$combined_meta = array_merge( $combined_meta, $meta_from_wc_orders );
			}

			if ( is_array( $meta_from_postmeta ) ) {
				$combined_meta = array_merge( $combined_meta, $meta_from_postmeta );
			}

			// Ensure required key exists in the combined meta.
			$combined_meta['sc_negative_net_total_stats_fix_status'] = 'yes';
			// phpcs:disable
			// Update wc_orders_meta table.
			$wpdb->replace(
				$wpdb->prefix . 'wc_orders_meta',
				array(
					'order_id'   => $order_id,
					'meta_key'   => $meta_key,
					'meta_value' => maybe_serialize( $combined_meta ),
				),
				array( '%d', '%s', '%s' )
			);
			// phpcs:enable

			// Update postmeta table.
			update_post_meta( $order_id, $meta_key, $combined_meta );
		}

		/**
		 * Update option once migration complete.
		 */
		public function finalize() {
			delete_transient( 'sc_get_wc_negative_order_stats_status' );
		}

	}
}

WC_SC_Order_Stats_Negative_Net_Total_Fix::get_instance();
