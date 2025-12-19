<?php
/**
 * Class to handle unused coupon reminder logic and validation.
 *
 * @package     woocommerce-smart-coupons/includes/
 * @since       9.57.0
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_Coupon_Unused_Reminder' ) ) {

	/**
	 * WC_SC_Coupon_Unused_Reminder class.
	 */
	class WC_SC_Coupon_Unused_Reminder {

		/**
		 * Singleton instance.
		 *
		 * @var WC_SC_Coupon_Unused_Reminder|null
		 */
		private static $instance = null;

		/**
		 * Expiry email reminder feature enabled status.
		 *
		 * @var bool
		 */
		public $expiry_reminder_email_enabled = false;

		/**
		 * Email reminder feature enabled status.
		 *
		 * @var bool
		 */
		public $email_enabled = false;

		/**
		 * Inactivity period threshold in days.
		 *
		 * @var int
		 */
		public $inactivity_days_threshold = 7;

		/**
		 * Maximum reminders per coupon.
		 *
		 * @var int
		 */
		public $max_reminders = 3;

		/**
		 * Constructor to initialize the class.
		 */
		public function __construct() {
			// Load settings from database.
			$this->load_settings();
		}

		/**
		 * Get single instance of WC_SC_Coupon_Unused_Reminder.
		 *
		 * @return WC_SC_Coupon_Unused_Reminder Singleton instance.
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
		 * Load settings from database.
		 */
		private function load_settings() {
			global $woocommerce_smart_coupon;
			try {
				$email_settings                 = get_option( 'woocommerce_wc_sc_unused_reminder_email_settings', array() );
				$expiry_reminder_email_settings = get_option( 'woocommerce_wc_sc_expiry_reminder_email_settings', array() );

				if ( ! empty( $email_settings ) ) {
					$this->inactivity_days_threshold = isset( $email_settings['inactivity_days_threshold'] ) ? intval( $email_settings['inactivity_days_threshold'] ) : 7;
					$this->max_reminders             = isset( $email_settings['max_reminders_per_coupon'] ) ? intval( $email_settings['max_reminders_per_coupon'] ) : 3;
					$this->email_enabled             = isset( $email_settings['enabled'] ) && 'yes' === $email_settings['enabled'];
				}

				if ( ! empty( $expiry_reminder_email_settings ) ) {
					$this->expiry_reminder_email_enabled = isset( $expiry_reminder_email_settings['enabled'] ) && 'yes' === $expiry_reminder_email_settings['enabled'];
				}
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Increment reminder sent count for coupon.
		 *
		 * @param int $coupon_id Coupon ID.
		 */
		public function increment_reminder_count( $coupon_id ) {
			global $woocommerce_smart_coupon;
			try {
				$current_count = get_post_meta( $coupon_id, 'sc_unused_reminder_sent_count', true );
				$new_count     = intval( $current_count ) + 1;
				update_post_meta( $coupon_id, 'sc_unused_reminder_sent_count', $new_count );
				update_post_meta( $coupon_id, 'sc_unused_reminder_last_sent', current_time( 'timestamp' ) ); // phpcs:ignore
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Trigger the email notification for the coupon unused reminder.
		 *
		 * @param int $coupon_id The ID of the coupon.
		 */
		public function wc_sc_send_unused_coupon_reminder( $coupon_id ) {
			if ( $this->email_enabled && $coupon_id ) {
				if ( ! has_action( 'wc_sc_unused_coupon_reminder_email_notification' ) ) {
					WC()->mailer();
				}
				do_action( 'wc_sc_unused_coupon_reminder_email_notification', $coupon_id );
			}
		}
	}
}
