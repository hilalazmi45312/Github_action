<?php
/**
 * Class to handle coupon expiry reminders and scheduling.
 *
 * @package     woocommerce-smart-coupons/includes/
 * @since       9.16.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_Coupon_Expiry_Reminder' ) ) {

	/**
	 * WC_SC_Coupon_Expiry_Reminder class.
	 */
	class WC_SC_Coupon_Expiry_Reminder {

		/**
		 * Singleton instance.
		 *
		 * @var WC_SC_Coupon_Expiry_Reminder|null
		 */
		private static $instance = null;

		/**
		 * Expiry reminder action hook name.
		 *
		 * @var string
		 */
		public $action = 'wc_sc_send_coupon_expiry_reminder';

		/**
		 * Array of scheduled actions for coupons.
		 *
		 * @var array
		 */
		public $scheduled_actions = array();

		/**
		 * Email reminder feature enabled status.
		 *
		 * @var bool
		 */
		public $email_enabled = false;

		/**
		 * Days before coupon expiry to send the reminder.
		 *
		 * @var int
		 */
		public $coupon_reminder_days = 0;

		/**
		 * Constructor to initialize the class and hook into necessary actions.
		 */
		public function __construct() {
			// Get the email settings option for coupon expiry reminders.
			$email_settings = get_option( 'woocommerce_wc_sc_expiry_reminder_email_settings', array() );

			if ( ! empty( $email_settings ) ) {
				$this->email_enabled        = isset( $email_settings['enabled'] ) && 'yes' === $email_settings['enabled'];
				$this->coupon_reminder_days = isset( $email_settings['scheduled_days_before_expiry'] ) ? intval( $email_settings['scheduled_days_before_expiry'] ) : 0;
			}

			// Hook into actions for coupon creation, trash, restoration from trash, and deletion.
			add_action( 'woocommerce_coupon_options_save', array( $this, 'schedule_reminder_for_coupon' ), 10, 2 );
			add_action( 'untrashed_post', array( $this, 'schedule_reminder_for_coupon' ) );
			add_action( 'wp_trash_post', array( $this, 'cancel_coupon_reminder_on_trash' ) );
			add_action( 'before_delete_post', array( $this, 'cancel_coupon_reminder_on_trash' ) );

			// Cron callback.
			add_action( $this->action, array( $this, 'wc_sc_send_coupon_expiry_reminder' ) );

			add_action( 'admin_notices', array( $this, 'show_expiry_reminder_admin_notice' ) );
		}

		/**
		 * Get single instance of WC_SC_Coupon_Expiry_Reminder.
		 *
		 * @return WC_SC_Coupon_Expiry_Reminder Singleton instance.
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
		 * Schedule a reminder for the coupon based on its expiry date.
		 *
		 * @param int            $coupon_id The ID of the coupon.
		 * @param WC_Coupon|null $coupon    The coupon object (optional).
		 * @param bool           $notice    Show admin notice true/false.
		 */
		public function schedule_reminder_for_coupon( $coupon_id = 0, $coupon = null, $notice = true ) {
			global $wpdb;
			$coupon_id = (int) $coupon_id;
			if ( empty( $coupon_id ) || ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) {
				return;
			}

			$coupon = $coupon ?? new WC_Coupon( $coupon_id );

			if ( ! $coupon instanceof WC_Coupon || ! $this->email_enabled || empty( $coupon->get_email_restrictions() ) ) {
				return;
			}
			// phpcs:disable
			// Get the expiration date from custom table.
			$expiration_date = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT UNIX_TIMESTAMP(date_expires) FROM {$wpdb->prefix}wc_smart_coupons WHERE id = %d",
					$coupon_id
				)
			);
			// phpcs:enable

			if ( $expiration_date ) {
				$reminder_time = (int) $expiration_date - ( $this->coupon_reminder_days * DAY_IN_SECONDS );

				// Check if the reminder time is in the future.
				if ( $reminder_time > time() && $this->is_coupon_already_scheduled( $coupon_id, $reminder_time ) ) {
					// Cancel any existing reminder before scheduling a new one.
					$this->cancel_coupon_reminder( $coupon_id );

					// Schedule the reminder action.
					as_schedule_single_action( $reminder_time, $this->action, array( $coupon_id ), 'woocommerce-smart-coupons' );

					if ( $notice ) {
						set_transient( 'wc_sc_coupon_expiry_reminder_notice', $coupon_id, 60 );
					}
				}
			}
		}

		/**
		 * Check if a reminder for the coupon is already scheduled.
		 *
		 * @param int $coupon_id The ID of the coupon.
		 * @param int $timestamp The reminder time (optional).
		 * @return bool True if no matching action is scheduled, false otherwise.
		 */
		public function is_coupon_already_scheduled( $coupon_id, $timestamp = null ) {
			$this->scheduled_actions[ $coupon_id ] = as_get_scheduled_actions(
				array(
					'hook'   => $this->action,
					'args'   => array( $coupon_id ),
					'status' => 'pending',
				)
			);

			if ( $timestamp ) {
				return empty(
					array_filter(
						$this->scheduled_actions[ $coupon_id ],
						function( $action ) use ( $timestamp ) {
							return $action->get_schedule()->get_date()->getTimestamp() === $timestamp;
						}
					)
				);
			}

			return false;
		}

		/**
		 * Cancel scheduled reminders when a coupon is moved to trash.
		 *
		 * @param int $post_id The ID of the post being trashed.
		 */
		public function cancel_coupon_reminder_on_trash( $post_id ) {
			if ( 'shop_coupon' === get_post_type( $post_id ) ) {
				$this->cancel_coupon_reminder( $post_id );
			}
		}

		/**
		 * Cancel all scheduled reminders for a coupon.
		 *
		 * @param int $coupon_id The ID of the coupon.
		 */
		public function cancel_coupon_reminder( $coupon_id ) {
			as_unschedule_all_actions( $this->action, array( $coupon_id ) );
		}

		/**
		 * Trigger the email notification for the coupon expiry reminder.
		 *
		 * @param int $coupon_id The ID of the coupon.
		 */
		public function wc_sc_send_coupon_expiry_reminder( $coupon_id ) {
			if ( $this->email_enabled && $coupon_id ) {
				if ( ! has_action( 'wc_sc_expiry_reminder_email_notification' ) ) {
					WC()->mailer();
				}
				do_action( 'wc_sc_expiry_reminder_email_notification', $coupon_id );
			}
		}

		/**
		 * Display an admin notice for scheduled coupon expiry reminders.
		 *
		 * @return void
		 */
		public function show_expiry_reminder_admin_notice() {
			$action_id = get_transient( 'wc_sc_coupon_expiry_reminder_notice' );
			if ( $action_id ) {
				$actions_url = add_query_arg(
					array(
						'page'   => 'wc-status',
						'tab'    => 'action-scheduler',
						's'      => $action_id,
						'status' => 'pending',
					),
					admin_url( 'admin.php' )
				);

				// Define the main message and link text separately.
				$message_text = _x( 'Expiry reminder scheduled for this coupon.', 'Admin notice message displayed after scheduling a coupon expiry reminder', 'woocommerce-smart-coupons' );
				$link_text    = _x( 'View scheduled action', 'Link text to view scheduled actions in admin', 'woocommerce-smart-coupons' );

				// Output the formatted message.
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
					esc_html( $message_text ),
					esc_url( $actions_url ),
					esc_html( $link_text )
				);

				delete_transient( 'wc_sc_coupon_expiry_reminder_notice' );
			}
		}
	}

	// Initialize the class.
	WC_SC_Coupon_Expiry_Reminder::get_instance();
}
