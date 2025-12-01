<?php
/**
 * Compatibility - Multi-Step Checkout for WooCommerce (Pro) Plugin.
 * Tested upto: 2.1.4
 *
 * @since 3.9.1
 * @link https://themehigh.com/product/woocommerce-multistep-checkout
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_WC_Multi_Step_Checkout_Compatibility' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.9.1
	 * */
	class DEY_WC_Multi_Step_Checkout_Compatibility extends DEY_Compatibility {

		/**
		 * Class constructor.
		 *
		 * @since 3.9.1
		 */
		public function __construct() {
			$this->id = 'wc_multi_step_checkout';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 3.9.1
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists( 'THWMSC' );
		}

		/**
		 * Actions.
		 *
		 * @since 3.9.1
		 */
		public function frontend_action() {
			add_filter( 'dey_validate_checkout_fields', array( __CLASS__, 'validate_multi_checkout_fields' ), 10, 3 );
		}

		/**
		 * Validate Multi step checkout fields.
		 *
		 * @since 3.9.1
		 * @param bool   $bool Can validate or not.
		 * @param object $errors WP_Error.
		 * @param array  $post_data Post data.
		 * @return bool
		 */
		public static function validate_multi_checkout_fields( $bool, $errors = array(), $post_data = array() ) {
			if ( ! isset( $post_data['dey_delivery_date'] ) && ! isset( $post_data['dey_local_pickup_date'] ) ) {
				return false;
			}

			$post_errors = DEY_Checkout_Fields_Validator::validate_post_data( $post_data );
			// Return if no errors exists.
			if ( ! is_object( $post_errors ) || ! $post_errors->has_errors() ) {
				return false;
			}

			foreach ( $post_errors->get_error_messages() as $error_message ) {
				$errors->add( 'delivery_slots_fields', $error_message );
			}

			return false;
		}
	}
}
