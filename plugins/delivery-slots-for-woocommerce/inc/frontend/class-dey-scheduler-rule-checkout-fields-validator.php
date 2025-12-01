<?php
/**
 * Checkout fields validator - Scheduler rule.
 *
 * @since 4.0.0
 * */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Scheduler_Rule_Checkout_Fields_Validator' ) ) {

	/**
	 * Class.
	 *
	 * @since 4.0.0
	 * */
	class DEY_Scheduler_Rule_Checkout_Fields_Validator {

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
		 * Scheduler type.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		private $scheduler_type;

		/**
		 * Class Initialization.
		 *
		 * @since 3.7.0
		 */
		public function __construct( &$post_data, $scheduler_type ) {
			$this->post_data      = $post_data;
			$this->scheduler_type = $scheduler_type;
			$this->errors         = new \WP_Error();

			$this->validate();
		}

		/**
		 * Get the errors.
		 *
		 * @since 3.7.0
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
			$this->scheduler_rule = dey_get_scheduler_rule( $this->post_data['dey_scheduler_rule_id'] );
			if ( ! $this->scheduler_rule->exists() ) {
				return;
			}

			switch ( $this->scheduler_type ) {
				case 'scheduler_rule_order_local_pickup':
					$this->validate_order_local_pickup_fields();
					break;

				case 'scheduler_rule_order_delivery':
					$this->validate_order_delivery_fields();
					break;
			}
		}

		/**
		 * Validate the order delivery fields.
		 *
		 * @since 3.7.0
		 */
		private function validate_order_delivery_fields() {
			$delivery_date = isset( $this->post_data['dey_delivery_date'] ) ? wc_clean( wp_unslash( $this->post_data['dey_delivery_date'] ) ) : '';
			$time_slot_id  = isset( $this->post_data['dey_order_delivery_date_time_slots'] ) ? wc_clean( wp_unslash( $this->post_data['dey_order_delivery_date_time_slots'] ) ) : '';
			$date_object   = DEY_Date_Time::get_date_time_object( $delivery_date );

			// Validate the field of order delivery date.
			$required = 'no' !== $this->scheduler_rule->get_delivery_calender_required();
			if ( '1' === $this->scheduler_rule->get_delivery_slot_mode() && $required && empty( $delivery_date ) ) {
				$this->errors->add( 'dey_order_delivery_field_required', dey_get_order_delivery_date_mandatory_message() );
			}

			// validate the field of order delivery time slots.
			$time_slot_required = 'no' !== $this->scheduler_rule->get_delivery_time_slot_required();
			$time_mode          = $this->scheduler_rule->get_delivery_time_mode();
			if ( '3' === $time_mode && $time_slot_required && ( empty( $time_slot_id ) || 'none' === $time_slot_id ) ) {
				$this->errors->add( 'dey_order_delivery_time_slot_field_required', dey_get_order_delivery_time_slot_mandatory_message() );
			}

			// Validate the selected date is valid.
			$order_delivery  = new DEY_Order_Delivery_Handler( $this->scheduler_rule );
			$available_dates = $order_delivery->get_available_dates();
			$formatted_date  = $date_object->format( 'Y-m-d' );

			if ( ! empty( $delivery_date ) && ( ! dey_check_is_array( $available_dates ) || ! isset( $available_dates[ $formatted_date ] ) ) ) {
				$this->errors->add( 'dey_invalid_order_delivery_date', dey_get_order_delivery_date_incorrect_message() );
			} elseif ( isset( $available_dates[ $formatted_date ]['t'] ) ) {
				switch ( $available_dates[ $formatted_date ]['t'] ) {
					case 'fb':
						$this->errors->add( 'dey_invalid_order_delivery_date', dey_get_order_delivery_date_booked_message() );
						break;

					case 'hy':
						$this->errors->add( 'dey_invalid_order_delivery_date', dey_get_order_delivery_date_holiday_message() );
						break;
				}
			}

			// Validate the time slots.
			if ( ! empty( $delivery_date ) && ! empty( $time_slot_id ) && 'soon' !== $time_slot_id ) {
				if ( '1' === $this->scheduler_rule->get_time_slots_mode() ) { // Global level.
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( ! $time_slot->exists() || ! $time_slot->is_valid( $delivery_date, 'delivery' ) ) {
						$this->errors->add( 'dey_invalid_order_delivery_time_slot', dey_get_order_delivery_time_slot_incorrect_message() );
					}
				} else { // Rule level.
					$time_slots = $this->scheduler_rule->get_time_slots();
					if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
						$this->errors->add( 'dey_invalid_order_delivery_time_slot', dey_get_order_delivery_time_slot_incorrect_message() );
					}
				}
			}
		}

		/**
		 * Validate the order local pickup fields.
		 *
		 * @since 3.7.0
		 */
		private function validate_order_local_pickup_fields() {
			$time_slot_id    = isset( $this->post_data['dey_order_local_pickup_date_time_slots'] ) ? wc_clean( wp_unslash( $this->post_data['dey_order_local_pickup_date_time_slots'] ) ) : '';
			$pickup_location = isset( $this->post_data['dey_pickup_location'] ) ? wc_clean( wp_unslash( $this->post_data['dey_pickup_location'] ) ) : '';
			$pickup_date     = isset( $this->post_data['dey_local_pickup_date'] ) ? wc_clean( wp_unslash( $this->post_data['dey_local_pickup_date'] ) ) : '';

			// Validate the pickup location.
			if ( empty( $pickup_location ) && 'yes' === $this->scheduler_rule->get_pickup_location_required() ) {
				$this->errors->add( 'dey_pickup_location_field_required', dey_get_order_pickup_location_mandatory_message() );
			}

			// validate the field of order local pickup time slots.
			$time_slot_required = 'yes' === $this->scheduler_rule->get_pickup_time_slot_required();
			$time_mode          = $this->scheduler_rule->get_pickup_time_mode();
			if ( '3' === $time_mode && $time_slot_required && ( empty( $time_slot_id ) || 'none' === $time_slot_id ) ) {
				$this->errors->add( 'dey_pickup_location_time_slot_field_required', dey_get_order_local_pickup_time_slot_mandatory_msg() );
			}

			// Validate the pickup date.
			if ( empty( $pickup_date ) && 'yes' === $this->scheduler_rule->get_pickup_calender_required() ) {
				$this->errors->add( 'dey_pickup_date_field_required', dey_get_order_pickup_date_mandatory_message() );
			}

			// Validate the selected date is valid.
			$order_local_pickup_handler = new DEY_Scheduler_Rule_Order_Local_Pickup_Handler( $this->scheduler_rule );
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
				if ( '1' === $this->scheduler_rule->get_time_slots_mode() ) { // Global level.
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( ! $time_slot->exists() || ! $time_slot->is_valid( $pickup_date, 'local_pickup' ) ) {
						$this->errors->add( 'dey_invalid_pickup_date_time_slot', dey_get_order_local_pickup_time_slot_incorrect_msg() );
					}
				} else { // Rule level.
					$time_slots = $this->scheduler_rule->get_time_slots();
					if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
						$this->errors->add( 'dey_invalid_pickup_date_time_slot', dey_get_order_local_pickup_time_slot_incorrect_msg() );
					}
				}
			}
		}
	}

}
