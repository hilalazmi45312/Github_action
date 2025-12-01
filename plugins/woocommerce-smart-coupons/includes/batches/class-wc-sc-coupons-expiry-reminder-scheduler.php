<?php
/**
 * Class to schedule and send coupon expiry reminders
 *
 * @package     woocommerce-smart-coupons/includes/
 * @since       9.17.0
 * @version     1.1.0
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

if ( ! class_exists( 'WC_SC_Coupons_Expiry_Reminder_Scheduler' ) && class_exists( 'WC_SC_Background_Process' ) ) {

	/**
	 * WC_SC_Coupons_Expiry_Reminder_Scheduler class.
	 * Schedules and sends coupon expiry reminders using Action Scheduler.
	 */
	class WC_SC_Coupons_Expiry_Reminder_Scheduler extends WC_SC_Background_Process {

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var WC_SC_Coupons_Expiry_Reminder_Scheduler
		 */
		private static $instance = null;

		/**
		 * Variable to hold instance of WC_SC_Coupon_Expiry_Reminder class.
		 *
		 * @var WC_SC_Coupon_Expiry_Reminder
		 */
		private $wc_sc_coupon_expiry_reminder_class = null;

		/**
		 * Get the single instance of this class.
		 *
		 * @return WC_SC_Coupons_Expiry_Reminder_Scheduler
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
			$this->batch_limit = 50;

			// Set the action name.
			$this->action = 'wc_sc_schedule_coupon_expiry_reminder';

			$this->wc_sc_coupon_expiry_reminder_class = WC_SC_Coupon_Expiry_Reminder::get_instance();

			// Initialize the parent class to execute background process.
			parent::__construct();

			add_action( $this->action . '_process_completed', array( $this, 'finalize' ) );

			add_action( 'admin_notices', array( $this, 'wc_sc_schedule_coupon_expiry_reminder_notice' ) );
		}

		/**
		 * Schedule reminder emails for coupons nearing expiration.
		 *
		 * @param array $coupon_ids The coupon ids to process.
		 *
		 * @throws Exception If any problem during the process.
		 */
		public function task( $coupon_ids = array() ) {
			if ( empty( $coupon_ids ) ) {
				throw new Exception( _x( 'No coupon ids passed.', 'No coupon ids error message', 'woocommerce-smart-coupons' ) );
			}

			foreach ( $coupon_ids as $coupon_id ) {
				$this->wc_sc_coupon_expiry_reminder_class->schedule_reminder_for_coupon( $coupon_id, null, false );
			}

			// Check process health before continuing.
			if ( ! $this->health_status() ) {
				throw new Exception( _x( 'Batch stopped due to health status in task.', 'Batch stopped error message', 'woocommerce-smart-coupons' ) );
			}
		}

		/**
		 * Get the remaining coupons that need expiry reminders.
		 *
		 * @return array The coupon ids to process.
		 */
		public function get_remaining_items() {
			global $wpdb;

			// Get the current timestamp.
			$current_timestamp = time();

			// Subtract days from the current timestamp for the comparison.
			$days_to_subtract = $this->wc_sc_coupon_expiry_reminder_class->coupon_reminder_days;

			// phpcs:disable
			// Prepare the SQL query to fetch coupon IDs.
			$query = $wpdb->prepare(
				"
				SELECT 
					wcsc.id 
				FROM 
					{$wpdb->prefix}wc_smart_coupons AS wcsc 
				WHERE wcsc.date_expires IS NOT NULL
            		AND wcsc.date_expires > FROM_UNIXTIME(%d) + INTERVAL %d DAY
					AND wcsc.customer_email IS NOT NULL 
					AND wcsc.customer_email != '' 
					AND wcsc.customer_email != 'a:0:{}' 
					AND NOT EXISTS (
						SELECT 1 
						FROM {$wpdb->prefix}actionscheduler_actions AS a 
						WHERE 
							a.args LIKE CONCAT('%', wcsc.id, '%') 
							AND a.hook = 'wc_sc_send_coupon_expiry_reminder' 
							AND a.status IN ('pending', 'in-progress')
					)
				LIMIT %d
				",
				$current_timestamp, // %d for the current timestamp minus the days.
				$days_to_subtract, // %d for the days to subtract.
				apply_filters( 'wc_sc_batch_size_for_coupons_for_reminder', $this->batch_limit, array( 'source' => $this ) ) // %d for the limit.
			);
			// Execute the query and fetch the coupon IDs.
			$coupon_ids = $wpdb->get_col( $query ) ?: array();
			// phpcs:enable

			return $coupon_ids;
		}

		/**
		 * Update option or perform cleanup once the process completes.
		 */
		public function finalize() {
			// Cleanup or updates after process completion.
			set_transient( 'wc_sc_coupons_expiry_reminder_status', 'completed', 30 );
		}

		/**
		 * Show notice for scheduling expiry reminder processing.
		 */
		public function processing_notice() {
			// Check if the current screen is a WooCommerce admin page.
			$screen = get_current_screen();

			if ( ! $screen || false === strpos( $screen->parent_base, 'woocommerce' ) || ( isset( $_GET['tab'] ) && 'action-scheduler' === $_GET['tab'] ) ) { // phpcs:ignore
				return; // Exit early if not on a WooCommerce admin page.
			}

			$actions_url   = add_query_arg(
				array(
					'page'   => 'wc-status',
					'tab'    => 'action-scheduler',
					's'      => $this->action,
					'status' => 'pending',
				),
				admin_url( 'admin.php' )
			);
			$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
			/* translators: %s: Plugin name */
			$message = sprintf( _x( '%s is scheduling coupon expiry reminders in the background. This may take a few moments, so please be patient.', 'scheduling coupon expiry info message', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			if ( true === $cron_disabled ) {
				$message .= '<br>' . _x( 'Note: WP CRON has been disabled on your install which may prevent this update from completing.', 'WP CRON has been disabled warning message', 'woocommerce-smart-coupons' );
			}
			$action_button = sprintf( '<a href="%1$s" class="button button-secondary">%2$s</a>', esc_url( $actions_url ), _x( 'View status', 'button text', 'woocommerce-smart-coupons' ) );

			WC_SC_Admin_Notifications::show_notice( 'info', '', $message, $action_button, true );
		}

		/**
		 * Show notice for completed expiry reminder scheduling.
		 */
		public function completed_notice() {
			/* translators: %s: Plugin name */
			$message = sprintf( _x( '%s Expiry Reminder background processing complete. This message will automatically disappear but after few seconds upon refresh.', 'Expiry reminder background processing complete info message', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			WC_SC_Admin_Notifications::show_notice( 'success', '', $message, '', true );
		}

		/**
		 * Display the appropriate notice based on process status.
		 */
		public function wc_sc_schedule_coupon_expiry_reminder_notice() {
			$status = get_transient( 'wc_sc_coupons_expiry_reminder_status' );

			if ( 'in-progress' === $status ) {
				$this->processing_notice();
			}

			if ( 'completed' === $status ) {
				$this->completed_notice();
			}
		}
	}

}

// Initialize the scheduler class instance.
WC_SC_Coupons_Expiry_Reminder_Scheduler::get_instance();
