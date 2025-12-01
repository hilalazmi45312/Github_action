<?php
/**
 * Abstract WC_SC_Background_Process class.
 * Uses https://actionscheduler.org/api/ to handle process in background.
 *
 * @package     woocommerce-smart-coupons/includes/abstracts
 * @since       9.8.0
 * @version     1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_Background_Process' ) ) {

	/**
	 * WC_SC_Background_Process class.
	 */
	abstract class WC_SC_Background_Process {

		/**
		 * Variable to hold the start time of a batch.
		 *
		 * @var int $start_time
		 */
		private $start_time = 0;

		/**
		 * Variable to hold limit per each batch.
		 *
		 * @var int $batch_limit
		 */
		protected $batch_limit = 5;

		/**
		 * Variable to hold time limit for each batch in second.
		 *
		 * @var int $time_limit
		 */
		protected $time_limit = 20;

		/**
		 * Variable to hold the action name.
		 *
		 * @var string $action
		 */
		protected $action = '';

		/**
		 * Variable to hold the group name.
		 *
		 * @var string $group
		 */
		protected $group = 'woocommerce-smart-coupons';

		/**
		 * Constructor
		 */
		public function __construct() {
			if ( ! empty( $this->action ) ) {
				// Register the action callback.
				add_action( $this->action, array( $this, 'do_task' ) );
				// Handle failed action.
				add_action( 'action_scheduler_failed_action', array( $this, 'restart_failed_action' ) );

				// Handle a failed action when the 'action_scheduler_failed_action' hook does not re-schedule the failed cron job.
				add_action( 'action_scheduler_failed_execution', array( $this, 'reset_failed_progress' ), 10, 3 );
			}
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return mixed of function call
		 */
		public function __call( $function_name = '', $arguments = array() ) {

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
		 * Initialize the process.
		 */
		public function init() {
			if ( empty( $this->action )
				|| ! function_exists( 'as_has_scheduled_action' )
				|| as_has_scheduled_action( $this->action )
			) {
				return;
			}
			// Trigger to start the process if not already scheduled.
			$this->start_process();
		}

		/**
		 * Start the process.
		 */
		private function start_process() {

			if ( ! class_exists( 'WC_SC_Background_Upgrade' ) ) {
				include_once '../class-wc-sc-background-upgrade.php';
			}

			$wcsc_db = WC_SC_Background_Upgrade::get_instance();

			if ( ! $this->is_enabled() || ! function_exists( 'as_enqueue_async_action' ) ) {
				return;
			}
			// Set status to processing.
			$this->set_status( 'processing' );

			// Enqueue the action.
			as_enqueue_async_action( $this->action, array( 'version' => $wcsc_db->get_current_update_version() ), $this->group );
		}

		/**
		 * Callback method to execute the process for each batch.
		 */
		public function do_task() {
			// Check if the status is 'processing'; if not, return.
			if ( 'processing' !== $this->get_status() ) {
				return;
			}

			$items = $this->get_remaining_items();

			if ( empty( $items ) ) {
				$this->completed();
				return;
			}

			$this->start_time = time();

			try {
				$this->task( $items );
			} catch ( Exception $e ) {
				$this->log(
					'error',
					sprintf(
						/* translators: 1: Action name 2: Error message */
						_x( 'Failed batch action: %1$s. Error: %2$s', 'Batch failed error message', 'woocommerce-smart-coupons' ),
						$this->action,
						is_callable( array( $e, 'getMessage' ) ) ? $e->getMessage() : ''
					)
				);
			}

			// Handle re-starting the process.
			$this->start_process();
		}

		/**
		 * Fetch the items to run the task.
		 */
		abstract public function get_remaining_items();

		/**
		 * Register the actual task.
		 */
		abstract public function task();

		/**
		 * Set the status of the background process.
		 *
		 * @param string $status Status of the process.
		 *
		 * @return bool Whether the status is updated successfully.
		 */
		public function set_status( $status = '' ) {
			if ( empty( $this->action ) || empty( $status ) ) {
				return false;
			}
			return update_option( $this->action . '_running_status', $status, 'no' );
		}

		/**
		 * Get the status of the background process.
		 *
		 * @return string|bool The status of the task.
		 */
		public function get_status() {
			if ( empty( $this->action ) ) {
				return false;
			}
			return get_option( $this->action . '_running_status', false );
		}

		/**
		 * Check if health status is good or not.
		 *
		 * @return bool Return true if good otherwise false.
		 */
		public function health_status() {
			return ! ( $this->time_exceeded() // Return false if time limit is exceeded.
					|| $this->memory_exceeded() ); // Return false if memory limit is exceeded.
		}

		/**
		 * Check if memory usage has exceeded the limit.
		 *
		 * @return bool Whether the memory usage has exceeded the limit.
		 */
		public function memory_exceeded() {
			$memory_limit = $this->get_memory_limit();

			if ( empty( $memory_limit ) ) {
				return false; // Return false if there is no memory limit defined.
			}
			$memory_limit   = intval( $memory_limit ) * 0.9; // 90% of max memory
			$current_memory = memory_get_usage( true );

			return $current_memory >= $memory_limit;
		}

		/**
		 * Check if the execution time has exceeded the limit.
		 *
		 * @return bool Whether the execution time has exceeded the limit.
		 */
		public function time_exceeded() {
			if ( empty( $this->start_time ) ) {
				return false;
			}

			$limit = $this->get_time_limit();

			// Return if no time limit is defined.
			if ( empty( $limit ) ) {
				return false;
			}

			return ( time() - intval( $this->start_time ) ) >= intval( $limit );
		}

		/**
		 * Get the batch limit.
		 *
		 * @return int Return batch limit.
		 */
		public function get_batch_limit() {
			return apply_filters( $this->action . '_batch_limit', $this->batch_limit, array( 'source' => $this ) );
		}

		/**
		 * Get the time limit for each batch.
		 *
		 * @return int Return time limit in second.
		 */
		public function get_time_limit() {
			return apply_filters( $this->action . '_batch_time_limit', $this->time_limit, array( 'source' => $this ) );
		}

		/**
		 * Get the memory limit.
		 *
		 * @return int Memory limit.
		 */
		public function get_memory_limit() {
			$memory_limit = ini_get( 'memory_limit' );

			if ( empty( $memory_limit ) || -1 === intval( $memory_limit ) ) {
				$memory_limit = '128M'; // Sensible default.
			}

			return wp_convert_hr_to_bytes( $memory_limit );
		}

		/**
		 * Restart the process again if it fails anyway.
		 *
		 * @param  int $action_id Id of the failed action.
		 *
		 * @return void.
		 */
		public function restart_failed_action( $action_id = 0 ) {
			if ( empty( $action_id ) || ! is_callable( array( 'ActionScheduler', 'store' ) ) ) {
				return;
			}

			$scheduler = ActionScheduler::store();

			if ( ! is_callable( array( $scheduler, 'fetch_action' ) ) ) {
				return;
			}

			$action = $scheduler->fetch_action( $action_id );

			if ( empty( $action ) || ! is_object( $action ) || ! is_callable( array( $action, 'get_hook' ) ) ) {
				return;
			}
			$action_hook = $action->get_hook();

			if ( empty( $action_hook ) ) {
				return;
			}
			// Restart the task if the failed action ID matches with current action.
			if ( $action_hook === $this->action ) {
				$this->start_process();
			}
		}

		/**
		 * Complete the process process.
		 *
		 * @return void.
		 */
		public function completed() {
			$this->set_status( 'completed' );
			do_action( $this->action . '_process_completed' );
		}

		/**
		 * Check whether the process is enabled.
		 * Child class can extend this to pass the value.
		 *
		 * @return bool Return true if process is enabled otherwise false.
		 */
		public function is_enabled() {
			return true;
		}

		/**
		 * Resets the status of a failed database update process associated with a specific Action Scheduler action.
		 *
		 * This method checks whether a specified Action Scheduler action has failed, and if so, updates the
		 * `sc_wc_db_update_status` option to mark the associated update version as "failed".
		 *
		 * @param int          $action_id The ID of the Action Scheduler action to process.
		 * @param Exception    $e         The exception object associated with the failure (unused in this method).
		 * @param array|string $context   Additional context about the failure (unused in this method).
		 *
		 * @return void
		 */
		public function reset_failed_progress( $action_id, $e, $context ) {
			if ( empty( $action_id ) || ! is_callable( array( 'ActionScheduler', 'store' ) ) ) {
				return;
			}

			$scheduler = ActionScheduler::store();

			if ( ! is_callable( array( $scheduler, 'fetch_action' ) ) ) {
				return;
			}

			$action = $scheduler->fetch_action( $action_id );

			if ( empty( $action ) || ! is_object( $action ) || ! is_callable( array( $action, 'get_hook' ) ) || ! is_callable( array( $action, 'get_args' ) ) || ! is_callable( array( $action, 'get_group' ) ) ) {
				return;
			}
			$action_hook = $action->get_hook();
			$action_args = $action->get_args();

			if ( empty( $action_hook ) || ! function_exists( 'as_has_scheduled_action' )
			|| as_has_scheduled_action( $action_hook ) || $this->group !== $action->get_group() || empty( $action_args ) || ! key_exists( 'version', $action_args ) ) {
				return;
			}

			$db_updates = get_option( 'sc_wc_db_update_status', true );

			if ( ! empty( $db_updates ) ) {
				if ( 'processing' === $db_updates[ $action_args['version'] ] ) {
					$db_updates[ $action_args['version'] ] = 'failed';
					update_option( 'sc_wc_db_update_status', $db_updates );
				}
			}
		}
	}
}

