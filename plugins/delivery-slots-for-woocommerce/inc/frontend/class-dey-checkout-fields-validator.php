<?php

/**
 * Checkout fields validator.
 *
 * @since 3.7.0
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Checkout_Fields_Validator' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.7.0
	 * */
	class DEY_Checkout_Fields_Validator {

		/**
		 * Post data.
		 *
		 * @since 3.7.0
		 * @var array
		 */
		private $post_data;

		/**
		 * Errors
		 *
		 * @since 3.7.0
		 * @var object
		 */
		private $errors;

		/**
		 * Scheduler rule.
		 *
		 * @since 4.0.0
		 * @var object
		 */
		private $scheduler_rule;

		/**
		 * Class Initialization.
		 *
		 * @since 3.7.0
		 * @param array $post_data Post data.
		 */
		public function __construct( &$post_data ) {
			$this->post_data = $post_data;
			$this->errors    = new \WP_Error();

			$this->validate();
		}

		/**
		 * Get the errors.
		 *
		 * @since 3.7.0
		 * @return object
		 */
		private function get_errors() {
			return $this->errors;
		}

		/**
		 * Validate post data.
		 *
		 * @since 3.7.0
		 * @param array $post_data Order scheduler data.
		 * @return object
		 */
		public static function validate_post_data( &$post_data ) {
			$validator = new self( $post_data );

			return $validator->get_errors();
		}

		/**
		 * Validate the checkout fields post data.
		 *
		 * @since 3.7.0
		 */
		private function validate() {
			// Return if the cart contains only virtual products.
			if ( ! dey_order_allow_virtual_products_delivery() ) {
				return;
			}

			// Return if the cart contains product scheduler only.
			if ( dey_is_cart_contains_product_scheduler_only() ) {
				return;
			}

			if ( ! isset( $this->post_data['dey_scheduler_rule_id'] ) ) {
				return;
			}

			$this->scheduler_rule = dey_get_scheduler_rule( $this->post_data['dey_scheduler_rule_id'] );
			if ( ! $this->scheduler_rule->exists() ) {
				return;
			}

			$scheduler_type = $this->get_current_scheduler_type();
			switch ( $scheduler_type ) {
				case 'pickup_location_order_local_pickup':
					$validator    = new DEY_Pickup_Location_Checkout_Fields_Validator( $this->post_data );
					$this->errors = $validator->get_errors();
					break;

				case 'scheduler_rule_order_local_pickup':
				case 'scheduler_rule_order_delivery':
					$validator    = new DEY_Scheduler_Rule_Checkout_Fields_Validator( $this->post_data, $scheduler_type );
					$this->errors = $validator->get_errors();
					break;
			}
		}

		/**
		 * Get the current scheduler type.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		private function get_current_scheduler_type() {
			if ( $this->scheduler_rule->is_order_scheduler() ) {
				if ( ! isset( $this->post_data['dey_order_scheduler_type'] ) ) {
					return '';
				}

				if ( 'order-local-pickup' === $this->post_data['dey_order_scheduler_type'] ) {
					$pickup_location_id = isset( $this->post_data['dey_pickup_location'] ) ? wc_clean( wp_unslash( $this->post_data['dey_pickup_location'] ) ) : false;
					$pickup_location    = dey_get_pickup_location( $pickup_location_id );

					return is_object( $pickup_location ) && '2' === $pickup_location->get_pickup_mode() ? 'pickup_location_order_local_pickup' : 'scheduler_rule_order_local_pickup';
				}

				return 'scheduler_rule_order_delivery';
			} elseif ( $this->scheduler_rule->is_order_delivery() ) {
				return 'scheduler_rule_order_delivery';
			} elseif ( $this->scheduler_rule->is_order_local_pickup() ) {
				$pickup_location_id = isset( $this->post_data['dey_pickup_location'] ) ? wc_clean( wp_unslash( $this->post_data['dey_pickup_location'] ) ) : false;
				$pickup_location    = dey_get_pickup_location( $pickup_location_id );

				return is_object( $pickup_location ) && '2' === $pickup_location->get_pickup_mode() ? 'pickup_location_order_local_pickup' : 'scheduler_rule_order_local_pickup';
			}

			return '';
		}
	}

}
