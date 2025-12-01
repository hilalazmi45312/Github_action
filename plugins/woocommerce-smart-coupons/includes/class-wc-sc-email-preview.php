<?php
/**
 * Class to handle email preview for Smart Coupons
 *
 * @author      StoreApps
 * @category    Admin
 * @package     woocommerce-smart-coupons/includes
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Email_Preview' ) ) {

	/**
	 * Class WC_SC_Email_Preview
	 */
	class WC_SC_Email_Preview {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'modify_email_preview_object' ) );
		}

		/**
		 * Get single instance of this class
		 *
		 * @return WC_SC_Email_Preview Singleton object of this class
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
		 * Modify email object for Smart Coupons email preview.
		 *
		 * @param WC_Email|mixed $email The email object.
		 * @return WC_Email Modified email object.
		 */
		public function modify_email_preview_object( $email ) {
			if ( isset( $email->id ) && strpos( $email->id, 'wc_sc_' ) !== false ) {
				$order        = $email->object;
				$dummy_coupon = $this->get_sc_dummy_coupon();

				$email->email_args['is_gift']                       = 'yes';
				$email->email_args['email']                         = $order->get_billing_email();
				$email->email_args['receiver_name']                 = $order->get_billing_first_name() . '' . $order->get_billing_last_name();
				$email->email_args['message_from_sender']           = '';
				$email->email_args['gift_certificate_sender_name']  = $order->get_billing_first_name() . '' . $order->get_billing_last_name();
				$email->email_args['gift_certificate_sender_email'] = $order->get_billing_email();
				$email->email_args['coupon']['code']                = $dummy_coupon->get_code();
				$email->email_args['coupon']['amount']              = $dummy_coupon->get_amount();
				$email->email_args['coupon']['discount_type']       = $dummy_coupon->get_discount_type();

				switch ( $email->id ) {
					case 'wc_sc_expiry_reminder_email':
						$email->set_object( $dummy_coupon );
						break;

					case 'wc_sc_combined_email_coupon':
						$email->email_args['receiver_details'] = array(
							array(
								'code'    => $dummy_coupon->get_code(),
								'message' => 'This is a dummy Smart Coupons coupon for email previews.',
							),
						);
						break;
					case 'wc_sc_acknowledgement_email':
						$email->email_args['receivers_detail'] = array(
							'code'    => $dummy_coupon->get_code(),
							'message' => 'This is a dummy Smart Coupons coupon for email previews.',
						);
						break;
				}
			}
			return $email;
		}

		/**
		 * Generate a dummy WooCommerce coupon for Smart Coupons email previews.
		 *
		 * This function creates a sample WC_Coupon object with predefined values
		 * to be used in email previews for the WooCommerce Smart Coupons plugin.
		 * It allows testing the display of coupon-related emails without requiring
		 * real coupon data.
		 *
		 * @return WC_Coupon A dummy WooCommerce coupon object.
		 *
		 * @since 9.6.0
		 */
		public function get_sc_dummy_coupon() {
			$coupon = new WC_Coupon();

			// Set dummy coupon properties.
			$coupon->set_id( 99999 );
			$coupon->set_code( 'SC_DUMMY_COUPON' );
			$coupon->set_discount_type( 'smart_coupon' );
			$coupon->set_amount( 15 );
			$coupon->set_date_created( time() );
			$coupon->set_date_expires( strtotime( '+7 days' ) );
			$coupon->set_usage_limit( 0 );
			$coupon->set_usage_limit_per_user( 1 );
			$coupon->set_description( _x( 'This is a dummy Smart Coupons coupon for email previews.', 'Coupon description', 'woocommerce-smart-coupons' ) );

			/**
			 * Filter to modify the dummy coupon used in Smart Coupons email previews.
			 *
			 * @param WC_Coupon $coupon The dummy coupon object.
			 */
			return apply_filters( 'sc_email_preview_dummy_coupon', $coupon );
		}

	}

	// Initialize the class.
	WC_SC_Email_Preview::get_instance();
}
