<?php
/**
 * Class to handle scheduling and batch processing of unused coupon reminders
 *
 * @package     woocommerce-smart-coupons/includes/batches/
 * @since       9.57.0
 * @version     1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_Background_Process', false ) ) {
	if ( file_exists( WC_SC_PLUGIN_DIRPATH . 'includes/abstracts/class-wc-sc-background-process.php' ) ) {
		include_once WC_SC_PLUGIN_DIRPATH . 'includes/abstracts/class-wc-sc-background-process.php';
	}
}

if ( ! class_exists( 'WC_SC_Coupons_Unused_Reminder_Scheduler' ) && class_exists( 'WC_SC_Background_Process' ) ) {

	/**
	 * WC_SC_Coupons_Unused_Reminder_Scheduler class.
	 */
	class WC_SC_Coupons_Unused_Reminder_Scheduler extends WC_SC_Background_Process {

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Variable to hold instance of WC_SC_Coupon_Unused_Reminder class.
		 *
		 * @var WC_SC_Coupon_Unused_Reminder
		 */
		private $wc_sc_coupon_unused_reminder_class = null;

		/**
		 * Get the single instance of this class
		 *
		 * @return WC_SC_Coupons_Unused_Reminder_Scheduler
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
			$this->batch_limit = 100;

			// Set the action name.
			$this->action = 'wc_sc_send_unused_coupon_reminder';

			$this->wc_sc_coupon_unused_reminder_class = WC_SC_Coupon_unused_Reminder::get_instance();

			// Initialize the parent class to execute background process.
			parent::__construct();

			// Add manual trigger action.
			add_action( 'admin_init', array( $this, 'manual_trigger_reminders' ) );

			add_action( $this->action . '_process_completed', array( $this, 'finalize' ) );
			add_action( 'admin_notices', array( $this, 'wc_sc_schedule_unused_coupon_reminder_notice' ) );
		}

		/**
		 * Get remaining items to process — single-query implementation (returns coupon IDs)
		 *
		 * @return int[] Array of coupon IDs
		 */
		public function get_remaining_items() {
			global $woocommerce_smart_coupon, $wpdb;
			try {
				$this->set_process_status( 'processing' );

				$inactivity_days = (int) $this->wc_sc_coupon_unused_reminder_class->inactivity_days_threshold;
				$max_reminders   = (int) $this->wc_sc_coupon_unused_reminder_class->max_reminders;
				$batch_limit     = (int) $this->batch_limit;

				// If your class exposes this flag, use it; otherwise default to true (skip coupons with expiry).
				$expiry_reminder_enabled = isset( $this->wc_sc_coupon_unused_reminder_class->expiry_reminder_email_enabled )
					? (bool) $this->wc_sc_coupon_unused_reminder_class->expiry_reminder_email_enabled
					: true;

				// Cutoff datetime: coupon must be published at least $inactivity_days ago.
				$inactivity_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$inactivity_days} days", time() ) );

				// start of today timestamp (site timezone). last_sent must be < this to be eligible.
				$today_start_ts = strtotime( 'today', time() );

				// prepare expiry SQL fragment — require no expiry meta OR empty.
				$sql_expiry_cond = "AND ( pm_ex.meta_value IS NULL OR pm_ex.meta_value = '' OR pm_ex.meta_value > NOW() )";

				// phpcs:disable
				// Single SQL: join postmeta for keys we need.
				$sql = $wpdb->prepare(
					"
					SELECT DISTINCT p.ID
					FROM {$wpdb->posts} p
					LEFT JOIN {$wpdb->postmeta} pm_email ON ( p.ID = pm_email.post_id AND pm_email.meta_key = %s )
					LEFT JOIN {$wpdb->postmeta} pm_usage ON ( p.ID = pm_usage.post_id AND pm_usage.meta_key = %s )
					LEFT JOIN {$wpdb->postmeta} pm_count ON ( p.ID = pm_count.post_id AND pm_count.meta_key = %s )
					LEFT JOIN {$wpdb->postmeta} pm_last ON ( p.ID = pm_last.post_id AND pm_last.meta_key = %s )
					LEFT JOIN {$wpdb->postmeta} pm_ex ON ( p.ID = pm_ex.post_id AND pm_ex.meta_key = %s )
					WHERE
						p.post_type = %s
						AND p.post_status = 'publish'
						AND p.post_date <= %s
						-- customer_email must exist and not empty
						AND pm_email.meta_value IS NOT NULL AND pm_email.meta_value != ''
						-- usage_count missing OR 0 is allowed
						AND ( pm_usage.meta_value IS NULL OR pm_usage.meta_value = '0' )
						-- expiry check: if meta exists it should be in future (DATETIME); we'll assume DATETIME here
						{$sql_expiry_cond}
						-- reminder count less than max or not set
						AND ( pm_count.meta_value IS NULL OR CAST(pm_count.meta_value AS UNSIGNED) < %d )
						-- last_sent not today or not set (we require last_sent < $today_start_ts OR NULL)
						AND ( pm_last.meta_value IS NULL OR CAST(pm_last.meta_value AS UNSIGNED) < %d )
					ORDER BY p.post_date ASC
					LIMIT %d
					",
					// placeholders.
					'customer_email',                  // pm_email.meta_key.
					'usage_count',                     // pm_usage.meta_key.
					'sc_unused_reminder_sent_count',   // pm_count.meta_key.
					'sc_unused_reminder_last_sent',    // pm_last.meta_key.
					'date_expires',                    // pm_ex.meta_key.
					'shop_coupon',                     // post_type.
					$inactivity_threshold,             // post_date cutoff.
					$max_reminders,                    // numeric threshold for count.
					$today_start_ts,                   // numeric threshold for last_sent.
					$batch_limit                       // LIMIT.
				);

				$ids = $wpdb->get_col( $sql );
				// phpcs:enable

				/**
				 * Filter to modify the coupon IDs before processing unused coupon reminders.
				 *
				 * @since 9.62.0
				 *
				 * @param array $ids Array of coupon IDs to be processed.
				 * @return array Modified array of coupon IDs.
				 */
				$ids = apply_filters( 'wc_sc_unused_coupon_reminder_coupon_ids', $ids );

				return ! empty( $ids ) ? $ids : array();
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
				return array();
			}
		}

		/**
		 * Execute the task for each batch
		 *
		 * @param array $coupon_ids The coupon ids to process.
		 *
		 * @throws Exception If any problem during the process.
		 */
		public function task( $coupon_ids = array() ) {

			if ( empty( $coupon_ids ) ) {
				throw new Exception( _x( 'No coupon ids passed.', 'No coupon ids error message', 'woocommerce-smart-coupons' ) );
			}

			// Check if email reminders are still enabled before processing.
			if ( ! $this->wc_sc_coupon_unused_reminder_class->email_enabled ) {
				$this->set_process_status( 'cancelled' );
				throw new Exception( _x( 'Unused coupon reminders are disabled.', 'Unused coupon reminders disabled error message', 'woocommerce-smart-coupons' ) );
			}

			foreach ( $coupon_ids as $coupon_id ) {
				$coupon_id = (int) $coupon_id;
				// Trigger email.
				$this->wc_sc_coupon_unused_reminder_class->wc_sc_send_unused_coupon_reminder( $coupon_id );

				// Increment reminder counts.
				$this->wc_sc_coupon_unused_reminder_class->increment_reminder_count( $coupon_id );
			}

			// Check process health before continuing.
			if ( ! $this->health_status() ) {
				throw new Exception( _x( 'Unused coupons batch stopped due to health status in task.', 'Batch stopped error message', 'woocommerce-smart-coupons' ) );
			}
		}

		/**
		 * Schedule reminder based on admin settings
		 *
		 * @param string $schedule Schedule type (daily, weekly, monthly).
		 */
		public function schedule_reminder_based_on_settings( $schedule = null ) {
			global $woocommerce_smart_coupon;
			try {
				// Get schedule from settings if not provided.
				if ( null === $schedule ) {
					$email_settings = get_option( 'woocommerce_wc_sc_unused_reminder_email_settings', array() );
					$schedule       = isset( $email_settings['reminder_schedule'] ) ? $email_settings['reminder_schedule'] : 'weekly';
				}

				// Cancel any existing scheduled reminders.
				$this->cancel_all_scheduled_reminders();

				// Calculate next run time.
				$next_run = $this->calculate_next_run_time( $schedule );

				// Schedule the reminder.
				$scheduled = as_schedule_single_action( $next_run, $this->action, array(), 'woocommerce-smart-coupons' );

				if ( $scheduled ) {
					$this->set_status( 'processing' );
					$this->set_process_status( '' );
				}
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Calculate next run time based on schedule
		 *
		 * @param string $schedule Schedule type.
		 * @return int Next run timestamp
		 */
		private function calculate_next_run_time( $schedule ) {
			switch ( $schedule ) {
				case 'daily':
					return strtotime( '+1 day' );
				case 'weekly':
					return strtotime( '+1 week' );
				case 'monthly':
					return strtotime( '+1 month' );
				default:
					return strtotime( '+1 week' );
			}
		}

		/**
		 * Cancel all scheduled reminders
		 */
		public function cancel_all_scheduled_reminders() {
			if ( get_transient( 'wc_sc_send_unused_coupon_reminder_process_status' ) === 'processing' ) {
				set_transient( 'wc_sc_send_unused_coupon_reminder_process_status', 'cancelled' );
			}
			as_unschedule_all_actions( $this->action, array(), 'woocommerce-smart-coupons' );
		}

		/**
		 * Check if feature is enabled
		 *
		 * @return bool True if enabled
		 */
		public function is_enabled() {
			$email_settings = get_option( 'woocommerce_wc_sc_unused_reminder_email_settings', array() );
			return isset( $email_settings['enabled'] ) && 'yes' === $email_settings['enabled'];
		}

		/**
		 * Manual trigger for admin "Send All Now" button
		 */
		public function manual_trigger_reminders() {
			global $woocommerce_smart_coupon;
			try {
				// Check for manual trigger action.
				if ( isset( $_GET['action'] ) && 'manual_trigger_unused_coupon_reminder' === $_GET['action'] ) { // phpcs:ignore
					if ( ! current_user_can( 'manage_woocommerce' ) ) {
						$message = esc_html__( 'You do not have sufficient permissions to access this page.', 'woocommerce-smart-coupons' );
						WC_SC_Admin_Notifications::show_notice( 'info', '', $message, '', true );
						return;
					}

					$this->set_process_status( 'queue' );

					// Schedule the action to run immediately.
					as_schedule_single_action( time(), $this->action, array(), $this->group );

					// Redirect back to the settings page with a success message.
					$redirect_url = add_query_arg(
						array(
							'page'    => 'wc-settings',
							'tab'     => 'email',
							'section' => 'wc_sc_unused_reminder_email',
						),
						admin_url( 'admin.php' )
					);
					wp_safe_redirect( $redirect_url );
					exit;
				}
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Update option or perform cleanup once the process completes.
		 */
		public function finalize() {
			// Schedule next reminder.
			$this->schedule_reminder_based_on_settings();
			$this->set_process_status( 'completed' );
			$this->set_status( 'processing' );
		}
		/**
		 * Update the status of the background process using a transient.
		 *
		 * This method is used to set the current process status to be retrieved later
		 * by other parts of the plugin. The status will automatically expire after the
		 * specified duration.
		 *
		 * @param string $status     The status to set (e.g., 'queue','processing', 'completed', 'cancelled').
		 * @return bool              True if the transient was set successfully, false otherwise.
		 */
		public function set_process_status( $status ) {
			return set_transient( 'wc_sc_send_unused_coupon_reminder_process_status', $status );
		}

		/**
		 * Show notice for scheduling unused reminder processing.
		 */
		public function processing_notice() {

			/* translators: %s: Plugin name */
			$message = sprintf( _x( '%s is running unused coupon reminders in the background. This may take a few moments. To cancel the process, simply disable the feature in settings.', 'running unused coupon reminders info message', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );

			$action_button = sprintf( '<a href="%1$s" class="button button-secondary">%2$s</a>', esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_sc_unused_reminder_email' ) ), _x( 'Go to settings', 'button text', 'woocommerce-smart-coupons' ) );

			WC_SC_Admin_Notifications::show_notice( 'info', '', $message, $action_button, true );
		}

		/**
		 * Show notice for completed unused reminder scheduling.
		 */
		public function completed_notice() {
			/* translators: %s: Plugin name */
			$message = sprintf( _x( '%s Unused Coupon Reminder background processing completed.', 'Unused coupon reminder background processing complete info message', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			WC_SC_Admin_Notifications::show_notice( 'success', '', $message, '', true, $this->action );
		}

		/**
		 * Show notice for cancelled unused reminder scheduling.
		 */
		public function cancelled_notice() {
			/* translators: %s: Plugin name */
			$message = sprintf( _x( '%s Unused Coupon Reminder background processing was cancelled.', 'Unused coupon reminder background processing cancelled info message', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			WC_SC_Admin_Notifications::show_notice( 'success', '', $message, '', true, $this->action );
		}

		/**
		 * Show notice for queued unused reminder scheduling.
		 */
		public function queue_notice() {

			$action_button = sprintf( '<a href="%1$s" class="button button-secondary">%2$s</a>', esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_sc_unused_reminder_email' ) ), _x( 'Go to settings', 'button text', 'woocommerce-smart-coupons' ) );

			/* translators: %s: Plugin name */
			$message = sprintf( _x( '%s background processing for Unused Coupon Reminders will begin shortly once the current queue is cleared. This may take a few moments, you can cancel the process by disabling the feature in settings.', 'Unused coupon reminder background processing queued info message', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			WC_SC_Admin_Notifications::show_notice( 'info', '', $message, $action_button, true, $this->action );
		}

		/**
		 * Display the appropriate notice based on process status.
		 */
		public function wc_sc_schedule_unused_coupon_reminder_notice() {
			
			// Get the current process status.
			$status = get_transient( 'wc_sc_send_unused_coupon_reminder_process_status' );
			switch ( $status ) {
				case 'processing':
					$this->processing_notice();
					break;
				case 'completed':
					$this->completed_notice();
					break;
				case 'cancelled':
					$this->cancelled_notice();
					break;
				case 'queue':
					$this->queue_notice();
					break;
			}
		}
	}

}

// Initialize the scheduler class instance.
WC_SC_Coupons_Unused_Reminder_Scheduler::get_instance();
