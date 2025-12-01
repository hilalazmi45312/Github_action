<?php
/**
 * Compatibility - Advanced Cancel Order Plugin.
 * Tested upto: 2.1.0
 *
 * @since 3.8.0
 * @link https://woocommerce.com/products/advanced-cancel-order-for-woocommerce/
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Advanced_Cancel_Order_Compatibility' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.8.0
	 * */
	class DEY_Advanced_Cancel_Order_Compatibility extends DEY_Compatibility {

		/**
		 * Class constructor.
		 *
		 * @since 3.8.0
		 */
		public function __construct() {
			$this->id = 'fp_advanced_cancel_order';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 3.8.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists( 'OCN_Order_Cancel' );
		}

		/**
		 * Actions.
		 *
		 * @since 3.8.0
		 */
		public function frontend_action() {
			add_filter( 'ocn_is_valid_cancel_order', array( __CLASS__, 'maybe_hide_cancel_order_button' ), 10, 2 );
		}

		/**
		 * Maybe hide cancel order button.
		 *
		 * @since 3.8.0
		 * @param bool   $is_valid Is valid to cancel order or not.
		 * @param object $order Order object.
		 * @return bool
		 */
		public static function maybe_hide_cancel_order_button( $is_valid, $order ) {
			// Return if the object is not order object.
			if ( ! is_object( $order ) ) {
				return $is_valid;
			}

			if ( ! dey_is_valid_to_display_cancel_order( $order ) ) {
				return false;
			}

			return $is_valid;
		}
	}
}
