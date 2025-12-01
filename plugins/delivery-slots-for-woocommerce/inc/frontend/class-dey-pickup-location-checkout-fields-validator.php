<?php
/**
 * Checkout fields validator - Pickup location.
 *
 * @since 4.0.0
 * */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Pickup_Location_Checkout_Fields_Validator' ) ) {

	/**
	 * Class.
	 *
	 * @since 4.0.0
	 * */
	class DEY_Pickup_Location_Checkout_Fields_Validator {

		/**
		 * Post data.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private $post_data;

		/**
		 * Errors
		 *
		 * @since 4.0.0
		 * @var object
		 */
		private $errors;

		/**
		 * Pickup location.
		 *
		 * @since 4.0.0
		 * @var object
		 */
		private $pickup_location;

		/**
		 * Class Initialization.
		 *
		 * @since 4.0.0
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
		 * @since 4.0.0
		 * @return object
		 */
		public function get_errors() {
			return $this->errors;
		}

		/**
		 * Validate the checkout fields post data.
		 *
		 * @since 3.7.0
		 */
		private function validate() {
			$pickup_location_id = isset( $this->post_data['dey_pickup_location'] ) ? wc_clean( wp_unslash( $this->post_data['dey_pickup_location'] ) ) : false;

			$this->pickup_location = dey_get_pickup_location( $pickup_location_id );
			if ( ! $this->pickup_location->exists() ) {
				return;
			}

			$this->validate_order_local_pickup_fields();
		}

		/**
		 * Validate the order local pickup fields.
		 *
		 * @since 4.0.0
		 */
		private function validate_order_local_pickup_fields() {
			$time_slot_id = isset( $this->post_data['dey_order_local_pickup_date_time_slots'] ) ? wc_clean( wp_unslash( $this->post_data['dey_order_local_pickup_date_time_slots'] ) ) : '';
			$pickup_date  = isset( $this->post_data['dey_local_pickup_date'] ) ? wc_clean( wp_unslash( $this->post_data['dey_local_pickup_date'] ) ) : '';

			// validate the field of order local pickup time slots.
			$time_slot_required = 'yes' === $this->pickup_location->get_pickup_time_slot_required();
			if ( '3' === $this->pickup_location->get_pickup_time_mode() && $time_slot_required && ( empty( $time_slot_id ) || 'none' === $time_slot_id ) ) {
				$this->errors->add( 'dey_pickup_location_time_slot_field_required', dey_get_order_local_pickup_time_slot_mandatory_msg() );
			}

			// Validate the pickup date.
			if ( empty( $pickup_date ) && 'yes' === $this->pickup_location->get_pickup_calender_required() ) {
				$this->errors->add( 'dey_pickup_date_field_required', dey_get_order_pickup_date_mandatory_message() );
			}

			// Validate the selected date is valid.
			$order_local_pickup_handler = new DEY_Pickup_Location_Order_Local_Pickup_Handler( $this->pickup_location );
			$available_dates            = $order_local_pickup_handler->get_available_dates();
			$date_object                = DEY_Date_Time::get_date_time_object( $pickup_date );
			$formatted_date             = $date_object->format( 'Y-m-d' );

			if ( ! empty( $pickup_date ) && ( ! dey_check_is_array( $available_dates ) || ! isset( $available_dates[ $formatted_date ] ) ) ) {
				$this->errors->add( 'dey_invalid_pickup_date', dey_get_order_pickup_date_incorrect_message() );
			} elseif ( isset( $available_dates[ $formatted_date ]['t'] ) ) {
				switch ( $available_dates[ $formatted_date ]['t'] ) {
					case 'fb':
						$this->errors->add( 'dey_invalid_pickup_date', dey_get_order_local_pickup_date_booked_message() );
						break;

					case 'hy':
						$this->errors->add( 'dey_invalid_pickup_date', dey_get_order_local_pickup_date_holiday_message() );
						break;
				}
			}

			// Validate the time slots.
			if ( ! empty( $pickup_date ) && ! empty( $time_slot_id ) && 'soon' !== $time_slot_id ) {
				if ( '1' === $this->pickup_location->get_time_slots_mode() ) { // Global level.
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( ! $time_slot->exists() || ! $time_slot->is_valid( $pickup_date, 'local_pickup' ) ) {
						$this->errors->add( 'dey_invalid_pickup_date_time_slot', dey_get_order_local_pickup_time_slot_incorrect_msg() );
					}
				} else { // Rule level.
					$time_slots = $this->pickup_location->get_time_slots();
					if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
						$this->errors->add( 'dey_invalid_pickup_date_time_slot', dey_get_order_local_pickup_time_slot_incorrect_msg() );
					}
				}
			}
		}
	}

}
