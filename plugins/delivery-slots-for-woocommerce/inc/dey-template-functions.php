<?php

/**
 * Template functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'dey_get_template' ) ) {

	/**
	 *  Get the other templates from the themes/WordPress.
	 *
	 * @return void
	 */
	function dey_get_template( $template_name, $args = array() ) {
		wc_get_template( $template_name, $args, DEY_FOLDER_NAME . '/', DEY()->templates() );
	}
}

if ( ! function_exists( 'dey_get_template_html' ) ) {

	/**
	 *  Like dey_get_template, but returns the HTML instead of outputting.
	 *
	 *  @return mixed
	 */
	function dey_get_template_html( $template_name, $args = array() ) {

		ob_start();
		dey_get_template( $template_name, $args );
		return ob_get_clean();
	}
}

if ( ! function_exists( 'dey_get_order_delivery_date_field_label' ) ) {

	/**
	 * Get the label for order delivery date field.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_date_field_label() {
		/**
		 * This hook is used to alter the order delivery date field label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_date_field_label', get_option( 'dey_order_delivery_date_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_field_label' ) ) {

	/**
	 * Get the label for order pickup location field.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_location_field_label() {
		/**
		 * This hook is used to alter the order pickup location field label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_location_field_label', get_option( 'dey_local_pickup_location_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_pickup_location_default_option_label' ) ) {

	/**
	 * Get the label for pickup location default option.
	 *
	 * @return string.
	 * */
	function dey_get_pickup_location_default_option_label() {
		/**
		 * This hook is used to alter the pickup location default option label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_pickup_location_default_option_label', get_option( 'dey_local_pickup_default_pickup_location_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_date_field_label' ) ) {

	/**
	 * Get the label for order local pickup date field.
	 *
	 * @return string.
	 * */
	function dey_get_order_local_pickup_date_field_label() {
		/**
		 * This hook is used to alter the order local pickup date field label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_local_pickup_date_field_label', get_option( 'dey_local_pickup_date_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_time_slots_field_label' ) ) {

	/**
	 * Get the label for order delivery time slots field.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_time_slots_field_label() {
		/**
		 * This hook is used to alter the order delivery time slots field label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_time_slots_field_label', get_option( 'dey_order_delivery_time_slots_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_time_slots_field_label' ) ) {

	/**
	 * Get the label for order local pickup time slots field.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_time_slots_field_label() {
		/**
		 * This hook is used to alter the order local pickup time slots field label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_time_slots_field_label', get_option( 'dey_local_pickup_time_slots_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_default_time_slot_label' ) ) {

	/**
	 * Get the label for order delivery default time slot.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_default_time_slot_label() {
		/**
		 * This hook is used to alter the order delivery default time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_default_time_slot_label', get_option( 'dey_order_delivery_default_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_default_time_slot_label' ) ) {

	/**
	 * Get the label for order local pickup default time slot.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_default_time_slot_label() {
		/**
		 * This hook is used to alter the order local pickup default time slot label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_default_time_slot_label', get_option( 'dey_local_pickup_default_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_unavailable_time_slot_label' ) ) {

	/**
	 * Get the label for order delivery unavailable time slot.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_unavailable_time_slot_label() {
		/**
		 * This hook is used to alter the order delivery unavailable time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_unavailable_time_slot_label', get_option( 'dey_order_delivery_unavailable_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_unavailable_time_slot_label' ) ) {

	/**
	 * Get the label for order local pickup unavailable time slot.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_unavailable_time_slot_label() {
		/**
		 * This hook is used to alter the order local pickuo unavailable time slot label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_unavailable_time_slot_label', get_option( 'dey_local_pickup_unavailable_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_as_soon_as_possible_label' ) ) {

	/**
	 * Get the label for order delivery as soon as possible.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_as_soon_as_possible_label() {
		/**
		 * This hook is used to alter the order delivery as soon as possible label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_as_soon_as_possible_label', get_option( 'dey_order_delivery_as_soon_as_possible_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_as_soon_as_possible_label' ) ) {

	/**
	 * Get the label for order local pickup as soon as possible.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_as_soon_as_possible_label() {
		/**
		 * This hook is used to alter the order local pickup as soon as possible label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_as_soon_as_possible_label', get_option( 'dey_local_pickup_as_soon_as_possible_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_fee_label' ) ) {

	/**
	 * Get the label for order delivery fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_fee_label() {
		/**
		 * This hook is used to alter the order delivery fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_fee_label', get_option( 'dey_order_delivery_fee_label' ) );
	}
}
if ( ! function_exists( 'dey_get_order_same_day_delivery_fee_label' ) ) {

	/**
	 * Get the label for order delivery fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_same_day_delivery_fee_label() {
		/**
		 * This hook is used to alter the order same day delivery fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_same_day_delivery_fee_label', get_option( 'dey_order_delivery_sameday_fee_label' ) );
	}
}
if ( ! function_exists( 'dey_get_order_next_day_delivery_fee_label' ) ) {

	/**
	 * Get the label for order delivery fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_next_day_delivery_fee_label() {
		/**
		 * This hook is used to alter the order next day delivery fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_next_day_delivery_fee_label', get_option( 'dey_order_delivery_nextday_fee_label' ) );
	}
}
if ( ! function_exists( 'dey_get_order_pickup_fee_label' ) ) {

	/**
	 * Get the label for order pickup fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_fee_label() {
		/**
		 * This hook is used to alter the order pickup fee label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_fee_label', get_option( 'dey_local_pickup_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_same_day_pickup_fee_label' ) ) {

	/**
	 * Get the label for pickup fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_same_day_pickup_fee_label() {
		/**
		 * This hook is used to alter the order same day pickup fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_same_day_pickup_fee_label', get_option( 'dey_local_pickup_sameday_fee_label' ) );
	}
}
if ( ! function_exists( 'dey_get_order_next_day_pickup_fee_label' ) ) {

	/**
	 * Get the label for pickup fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_next_day_pickup_fee_label() {
		/**
		 * This hook is used to alter the order next day delivery fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_next_day_pickup_fee_label', get_option( 'dey_local_pickup_nextday_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_date_label' ) ) {

	/**
	 * Get the label for order delivery date.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_date_label() {
		/**
		 * This hook is used to alter the order delivery date label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_date_label', get_option( 'dey_order_delivery_date_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_label' ) ) {

	/**
	 * Get the label for order pickup location.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_location_label() {
		/**
		 * This hook is used to alter the order pickup location label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_location_label', get_option( 'dey_local_pickup_location_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_date_label' ) ) {

	/**
	 * Get the label for order pickup date.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_date_label() {
		/**
		 * This hook is used to alter the order pickup date label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_date_label', get_option( 'dey_local_pickup_date_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_time_slot_label' ) ) {

	/**
	 * Get the label for order delivery time slot.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_time_slot_label() {
		/**
		 * This hook is used to alter the order delivery time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_time_slot_label', get_option( 'dey_order_delivery_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_time_slot_label' ) ) {

	/**
	 * Get the label for order local pickup time slot.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_time_slot_label() {
		/**
		 * This hook is used to alter the order local pickup time slot label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_time_slot_label', get_option( 'dey_local_pickup_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_expected_info_label' ) ) {

	/**
	 * Get the label for order delivery expected info.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_expected_info_label() {
		/**
		 * This hook is used to alter the order expected info label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_expected_info_label', get_option( 'dey_order_delivery_expected_info_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_products_label' ) ) {

	/**
	 * Get the label for order delivery products.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_products_label() {
		/**
		 * This hook is used to alter the order delivery products label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_delivery_products_label', get_option( 'dey_order_delivery_products_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_products_label' ) ) {

	/**
	 * Get the label for order pickup products.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_products_label() {
		/**
		 * This hook is used to alter the order pickup products label.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_products_label', get_option( 'dey_local_pickup_products_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_partially_booked_label' ) ) {

	/**
	 * Get the label for order delivery partially booked.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_partially_booked_label() {
		/**
		 * This hook is used to alter the order partially booked label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_partially_booked_label', get_option( 'dey_order_delivery_partially_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_partially_booked_label' ) ) {

	/**
	 * Get the label for order local pickup partially booked.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_partially_booked_label() {
		/**
		 * This hook is used to alter the order local pickup partially booked label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_partially_booked_label', get_option( 'dey_local_pickup_partially_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_remaining_count_label' ) ) {

	/**
	 * Get the label for order delivery remaining count.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_remaining_count_label() {
		/**
		 * This hook is used to alter the order delivery remaining count label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_remaining_count_label', get_option( 'dey_order_delivery_remaining_count_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_remaining_count_label' ) ) {

	/**
	 * Get the label for order local pickup remaining count.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_remaining_count_label() {
		/**
		 * This hook is used to alter the order local pickup remaining count label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_remaining_count_label', get_option( 'dey_local_pickup_remaining_count_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_booked_label' ) ) {

	/**
	 * Get the label for order delivery booked.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_booked_label() {
		/**
		 * This hook is used to alter the order delivery booked label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_booked_label', get_option( 'dey_order_delivery_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_booked_label' ) ) {

	/**
	 * Get the label for order local pickup booked.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_booked_label() {
		/**
		 * This hook is used to alter the order local pickup booked label.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_booked_label', get_option( 'dey_local_pickup_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_myaccount_order_delivery_date_label' ) ) {

	/**
	 * Get the label for My account order table delivery date column.
	 *
	 * @since 2.3
	 *
	 * @return string.
	 * */
	function dey_get_myaccount_order_delivery_date_label() {
		/**
		 * This hook is used to alter the My account order table delivery date column label.
		 *
		 * @since 2.3
		 */
		return apply_filters( 'dey_myaccount_order_delivery_date_label', get_option( 'dey_order_delivery_myaccount_order_delivery_date_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_expected_message' ) ) {

	/**
	 * Get the message for order delivery expected info.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_expected_message() {
		/**
		 * This hook is used to alter the order delivery expected message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_expected_message', get_option( 'dey_order_delivery_expected_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_date_mandatory_message' ) ) {

	/**
	 * Get the message for order delivery date mandatory.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_date_mandatory_message() {
		/**
		 * This hook is used to alter the order delivery mandatory message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_date_mandatory_message', get_option( 'dey_order_delivery_date_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_mandatory_message' ) ) {

	/**
	 * Get the message for order pickup location mandatory.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_location_mandatory_message() {
		/**
		 * This hook is used to alter the order pickup location mandatory message.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_location_mandatory_message', get_option( 'dey_local_pickup_location_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_date_mandatory_message' ) ) {

	/**
	 * Get the message for order pickup date mandatory.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_date_mandatory_message() {
		/**
		 * This hook is used to alter the order pickup mandatory message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_pickup_date_mandatory_message', get_option( 'dey_local_pickup_date_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_time_slot_mandatory_message' ) ) {

	/**
	 * Get the message for order delivery time slot mandatory.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_time_slot_mandatory_message() {
		/**
		 * This hook is used to alter the order delivery time slot mandatory message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_time_slot_mandatory_message', get_option( 'dey_order_delivery_time_slot_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_time_slot_mandatory_msg' ) ) {

	/**
	 * Get the message for order local pickup time slot mandatory.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_time_slot_mandatory_msg() {
		/**
		 * This hook is used to alter the order local pickup time slot mandatory message.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_time_slot_mandatory_message', get_option( 'dey_local_pickup_time_slot_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_date_incorrect_message' ) ) {

	/**
	 * Get the message for order delivery date incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_date_incorrect_message() {
		/**
		 * This hook is used to alter the order delivery date incorrect message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_date_incorrect_message', get_option( 'dey_order_delivery_date_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_pickup_date_incorrect_message' ) ) {

	/**
	 * Get the message for order pickup date incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_order_pickup_date_incorrect_message() {
		/**
		 * This hook is used to alter the order pickup date incorrect message.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_pickup_date_incorrect_message', get_option( 'dey_local_pickup_date_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_time_slot_incorrect_message' ) ) {

	/**
	 * Get the message for order delivery time slot incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_time_slot_incorrect_message() {
		/**
		 * This hook is used to alter the order delivery time slot incorrect message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_time_slot_incorrect_message', get_option( 'dey_order_delivery_time_slot_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_time_slot_incorrect_msg' ) ) {

	/**
	 * Get the message for order local pickup time slot incorrect.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_time_slot_incorrect_msg() {
		/**
		 * This hook is used to alter the order local pickup time slot incorrect message.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_time_slot_incorrect_message', get_option( 'dey_local_pickup_time_slot_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_date_booked_message' ) ) {

	/**
	 * Get the message for order delivery booked.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_date_booked_message() {
		/**
		 * This hook is used to alter the order delivery date booked message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_date_booked_message', get_option( 'dey_order_delivery_date_booked_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_date_booked_message' ) ) {

	/**
	 * Get the message for order local pickup booked.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_date_booked_message() {
		/**
		 * This hook is used to alter the order local pickup date booked message.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_date_booked_message', get_option( 'dey_local_pickup_date_booked_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_delivery_date_holiday_message' ) ) {

	/**
	 * Get the message for order delivery holiday.
	 *
	 * @return string.
	 * */
	function dey_get_order_delivery_date_holiday_message() {
		/**
		 * This hook is used to alter the order delivery holiday message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_date_holiday_message', get_option( 'dey_order_delivery_date_holiday_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_date_holiday_message' ) ) {

	/**
	 * Get the message for order local pickup holiday.
	 *
	 * @since 3.0.0
	 * @return string.
	 * */
	function dey_get_order_local_pickup_date_holiday_message() {
		/**
		 * This hook is used to alter the order pickup holiday message.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( 'dey_order_local_pickup_date_holiday_message', get_option( 'dey_local_pickup_date_holiday_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_field_label' ) ) {

	/**
	 * Get the label for product delivery date field.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_field_label() {
		/**
		 * This hook is used to alter the product delivery date field label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_field_label', get_option( 'dey_product_delivery_date_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_time_slots_field_label' ) ) {

	/**
	 * Get the label for product delivery time slots field.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_time_slots_field_label() {
		/**
		 * This hook is used to alter the product delivery time slots field label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_time_slots_field_label', get_option( 'dey_product_delivery_time_slots_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_default_time_slot_label' ) ) {

	/**
	 * Get the label for product delivery default time slot.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_default_time_slot_label() {
		/**
		 * This hook is used to alter the product delivery default time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_default_time_slot_label', get_option( 'dey_product_delivery_default_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_unavailable_time_slot_label' ) ) {

	/**
	 * Get the label for product delivery unavailable time slot.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_unavailable_time_slot_label() {
		/**
		 * This hook is used to alter the product delivery unavailable time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_unavailable_time_slot_label', get_option( 'dey_product_delivery_unavailable_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_unavailable_time_slot_label' ) ) {

	/**
	 * Get the label for product pickup unavailable time slot.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_unavailable_time_slot_label() {
		/**
		 * This hook is used to alter the product pickup unavailable time slot label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_unavailable_time_slot_label', get_option( 'dey_product_local_pickup_unavailable_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_as_soon_as_possible_label' ) ) {

	/**
	 * Get the label for product delivery as soon as possible.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_as_soon_as_possible_label() {
		/**
		 * This hook is used to alter the product delivery as soon as possible label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_as_soon_as_possible_label', get_option( 'dey_product_delivery_as_soon_as_possible_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_as_soon_as_possible_label' ) ) {

	/**
	 * Get the label for product pickup as soon as possible.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_as_soon_as_possible_label() {
		/**
		 * This hook is used to alter the product pickup as soon as possible label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_as_soon_as_possible_label', get_option( 'dey_product_local_pickup_as_soon_as_possible_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_fee_label' ) ) {

	/**
	 * Get the label for product delivery fee.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_fee_label() {
		/**
		 * This hook is used to alter the product delivery fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_fee_label', get_option( 'dey_product_delivery_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_same_day_fee_label' ) ) {

	/**
	 * Get the label for product delivery fee for same day.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_same_day_fee_label() {
		/**
		 * This hook is used to alter the product delivery fee label for same day.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_fee_label_for_same_day', get_option( 'dey_product_delivery_same_day_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_next_day_fee_label' ) ) {

	/**
	 * Get the label for product delivery fee for next day.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_next_day_fee_label() {
		/**
		 * This hook is used to alter the product delivery fee label for next day.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_fee_label_for_next_day', get_option( 'dey_product_delivery_next_day_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_label' ) ) {

	/**
	 * Get the label for product delivery date.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_label() {
		/**
		 * This hook is used to alter the product delivery date label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_label', get_option( 'dey_product_delivery_date_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_time_slot_label' ) ) {

	/**
	 * Get the label for product delivery time slot.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_time_slot_label() {
		/**
		 * This hook is used to alter the product delivery time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_time_slot_label', get_option( 'dey_product_delivery_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_expected_info_label' ) ) {

	/**
	 * Get the label for product delivery expected info.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_expected_info_label() {
		/**
		 * This hook is used to alter the product delivery expected info label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_expected_info_label', get_option( 'dey_product_delivery_expected_info_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_partially_booked_label' ) ) {

	/**
	 * Get the label for product delivery partially booked.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_partially_booked_label() {
		/**
		 * This hook is used to alter the product delivery partially booked label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_partially_booked_label', get_option( 'dey_product_delivery_partially_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_remaining_count_label' ) ) {

	/**
	 * Get the label for product delivery remaining count.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_remaining_count_label() {
		/**
		 * This hook is used to alter the product delivery remaining count label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_remaining_count_label', get_option( 'dey_product_delivery_remaining_count_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_booked_label' ) ) {

	/**
	 * Get the label for product delivery booked.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_booked_label() {
		/**
		 * This hook is used to alter the product delivery booked label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_booked_label', get_option( 'dey_product_delivery_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_field_label' ) ) {

	/**
	 * Get the label for product pickup date field.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_field_label() {
		/**
		 * This hook is used to alter the product local pickup date field label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_field_label', get_option( 'dey_product_local_pickup_date_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_location_field_label' ) ) {

	/**
	 * Get the label for product pickup date field.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_location_field_label() {
		/**
		 * This hook is used to alter the product local pickup location field label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_location_field_label', get_option( 'dey_product_local_pickup_location_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_time_slots_field_label' ) ) {

	/**
	 * Get the label for product pickup time slots field.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_time_slots_field_label() {
		/**
		 * This hook is used to alter the product pickup time slots field label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_time_slots_field_label', get_option( 'dey_product_local_pickup_time_slots_field_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_default_time_slot_label' ) ) {

	/**
	 * Get the label for product pickup default time slot.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_default_time_slot_label() {
		/**
		 * This hook is used to alter the product local pickup default time slot label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_default_time_slot_label', get_option( 'dey_product_local_pickup_default_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_fee_label' ) ) {
	/**
	 * Get the label for product pickup fee.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_fee_label() {
		/**
		 * This hook is used to alter the product pickup fee label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_fee_label', get_option( 'dey_product_local_pickup_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_label' ) ) {

	/**
	 * Get the label for product pickup date.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_label() {
		/**
		 * This hook is used to alter the product pickup date label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_label', get_option( 'dey_product_local_pickup_date_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_time_slot_label' ) ) {
	/**
	 * Get the label for product pickup time slot.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_time_slot_label() {
		/**
		 * This hook is used to alter the product pickup time slot label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_time_slot_label', get_option( 'dey_product_local_pickup_time_slot_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_partially_booked_label' ) ) {
	/**
	 * Get the label for product pickup partially booked.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_partially_booked_label() {
		/**
		 * This hook is used to alter the product pickup partially booked label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_partially_booked_label', get_option( 'dey_product_local_pickup_partially_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_remaining_count_label' ) ) {
	/**
	 * Get the label for product pickup remaining count.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_remaining_count_label() {
		/**
		 * This hook is used to alter the product pickup remaining count label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_remaining_count_label', get_option( 'dey_product_local_pickup_remaining_count_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_booked_label' ) ) {
	/**
	 * Get the label for product pickup booked.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_booked_label() {
		/**
		 * This hook is used to alter the product pickup booked label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_booked_label', get_option( 'dey_product_local_pickup_booked_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_location_mandatory_message' ) ) {

	/**
	 * Get the message for product pickup location mandatory.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_location_mandatory_message() {
		/**
		 * This hook is used to alter the product pickup location mandatory message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_location_mandatory_message', get_option( 'dey_product_local_pickup_location_mandatory_msg', __( 'Please select a pickup location.', 'delivery-slots-for-woocommerce' ) ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_mandatory_message' ) ) {

	/**
	 * Get the message for product pickup date mandatory.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_mandatory_message() {
		/**
		 * This hook is used to alter the product pickup date mandatory message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_mandatory_message', get_option( 'dey_product_local_pickup_date_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_time_slot_mandatory_message' ) ) {
	/**
	 * Get the message for product pickup time slot mandatory.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_time_slot_mandatory_message() {
		/**
		 * This hook is used to alter the product pickup time slot mandatory message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_time_slot_mandatory_message', get_option( 'dey_product_local_pickup_time_slot_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_incorrect_message' ) ) {
	/**
	 * Get the message for product pickup date incorrect.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_incorrect_message() {
		/**
		 * This hook is used to alter the product pickup date incorrect message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_incorrect_message', get_option( 'dey_product_local_pickup_date_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_booked_message' ) ) {
	/**
	 * Get the message for product pickup booked.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_booked_message() {
		/**
		 * This hook is used to alter the product pickup date booked message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_booked_message', get_option( 'dey_product_local_pickup_date_booked_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_holiday_message' ) ) {
	/**
	 * Get the message for product pickup holiday.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_holiday_message() {
		/**
		 * This hook is used to alter the product pickup holiday message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_holiday_message', get_option( 'dey_product_local_pickup_date_holiday_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_time_slot_incorrect_message' ) ) {
	/**
	 * Get the message for product pickup time slot incorrect.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_time_slot_incorrect_message() {
		/**
		 * This hook is used to alter the product pickup time slot incorrect message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_time_slot_incorrect_message', get_option( 'dey_product_local_pickup_time_slot_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_same_day_fee_label' ) ) {

	/**
	 * Get the label for product pickup fee for same day.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_same_day_fee_label() {

		/**
		 * This hook is used to alter the product pickup fee label for same day.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_fee_label_for_same_day', get_option( 'dey_product_local_pickup_same_day_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_next_day_fee_label' ) ) {

	/**
	 * Get the label for product pickup fee for next day.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_next_day_fee_label() {

		/**
		 * This hook is used to alter the product pickup fee label for next day.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_fee_label_for_next_day', get_option( 'dey_product_local_pickup_next_day_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_expected_message' ) ) {

	/**
	 * Get the message for product delivery expected info.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_expected_message() {
		/**
		 * This hook is used to alter the product delivery expected message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_expected_message', get_option( 'dey_product_delivery_expected_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_mandatory_message' ) ) {

	/**
	 * Get the message for product delivery date mandatory.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_mandatory_message() {
		/**
		 * This hook is used to alter the product delivery date mandatory message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_mandatory_message', get_option( 'dey_product_delivery_date_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_time_slot_mandatory_message' ) ) {

	/**
	 * Get the message for product delivery time slot mandatory.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_time_slot_mandatory_message() {
		/**
		 * This hook is used to alter the product delivery time slot mandatory message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_time_slot_mandatory_message', get_option( 'dey_product_delivery_time_slot_mandatory_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_incorrect_message' ) ) {

	/**
	 * Get the message for product delivery date incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_incorrect_message() {
		/**
		 * This hook is used to alter the product delivery date incorrect message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_incorrect_message', get_option( 'dey_product_delivery_date_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_time_slot_incorrect_message' ) ) {

	/**
	 * Get the message for product delivery time slot incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_time_slot_incorrect_message() {
		/**
		 * This hook is used to alter the product delivery time slot incorrect message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_time_slot_incorrect_message', get_option( 'dey_product_delivery_time_slot_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_booked_message' ) ) {

	/**
	 * Get the message for product delivery booked.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_booked_message() {
		/**
		 * This hook is used to alter the product delivery date booked message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_booked_message', get_option( 'dey_product_delivery_date_booked_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_holiday_message' ) ) {

	/**
	 * Get the message for product delivery holiday.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_holiday_message() {
		/**
		 * This hook is used to alter the product delivery holiday message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_holiday_message', get_option( 'dey_product_delivery_date_holiday_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_disabled_cart_message' ) ) {

	/**
	 * Get the message for cart product delivery disabled.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_disabled_cart_message() {
		/**
		 * This hook is used to alter the product delivery disabled cart message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_disabled_cart_message', get_option( 'dey_product_delivery_disabled_cart_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_disabled_cart_message' ) ) {
	/**
	 * Get the message for cart product pickup disabled.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_disabled_cart_message() {
		/**
		 * This hook is used to alter the product pickup disabled cart message.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_disabled_cart_message', get_option( 'dey_product_local_pickup_disabled_cart_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_cart_incorrect_message' ) ) {

	/**
	 * Get the message for cart product delivery date incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_cart_incorrect_message() {
		/**
		 * This hook is used to alter the product delivery date incorrect message in the cart.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_cart_incorrect_message', get_option( 'dey_product_delivery_date_cart_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_cart_incorrect_message' ) ) {
	/**
	 * Get the message for cart product delivery date incorrect.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_cart_incorrect_message() {
		/**
		 * This hook is used to alter the product pickup date incorrect message in the cart.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_cart_incorrect_message', get_option( 'dey_product_local_pickup_date_cart_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_time_slot_cart_incorrect_message' ) ) {

	/**
	 * Get the message for cart product delivery time slot incorrect.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_time_slot_cart_incorrect_message() {
		/**
		 * This hook is used to alter the product delivery time slot incorrect message in the cart.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_time_slot_cart_incorrect_message', get_option( 'dey_product_delivery_time_slot_cart_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_time_slot_cart_incorrect_message' ) ) {
	/**
	 * Get the message for cart product pickup time slot incorrect.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_time_slot_cart_incorrect_message() {
		/**
		 * This hook is used to alter the product pickup time slot incorrect message in the cart.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_time_slot_cart_incorrect_message', get_option( 'dey_product_local_pickup_time_slot_cart_incorrect_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_cart_booked_message' ) ) {

	/**
	 * Get the message for cart product delivery date booked.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_cart_booked_message() {
		/**
		 * This hook is used to alter the product delivery date booked message in the cart.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_cart_booked_message', get_option( 'dey_product_delivery_date_cart_booked_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_cart_booked_message' ) ) {
	/**
	 * Get the message for cart product pickup date booked.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_cart_booked_message() {
		/**
		 * This hook is used to alter the product pickup date booked message in the cart.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_cart_booked_message', get_option( 'dey_product_local_pickup_date_cart_booked_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_date_cart_holiday_message' ) ) {

	/**
	 * Get the message for cart product delivery date holiday.
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_date_cart_holiday_message() {
		/**
		 * This hook is used to alter the product delivery holiday message in the cart.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_cart_holiday_message', get_option( 'dey_product_delivery_date_cart_holiday_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_date_cart_holiday_message' ) ) {
	/**
	 * Get the message for cart product pickup date holiday.
	 *
	 * @since 3.5.0
	 * @return string
	 * */
	function dey_get_product_pickup_date_cart_holiday_message() {
		/**
		 * This hook is used to alter the product pickup holiday message in the cart.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_cart_holiday_message', get_option( 'dey_product_local_pickup_date_cart_holiday_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_product_delivery_virtual_product_removed_cart_message' ) ) {

	/**
	 * Get the message for virtual product removed from the cart.
	 *
	 * @since 2.4
	 *
	 * @return string.
	 * */
	function dey_get_product_delivery_virtual_product_removed_cart_message() {
		/**
		 * This hook is used to alter the product delivery virtual product removed from the cart message.
		 *
		 * @since 2.4
		 */
		return apply_filters( 'dey_product_delivery_virtual_product_removed_cart_message', get_option( 'dey_product_delivery_virtual_product_removed_cart_msg' ) );
	}
}

if ( ! function_exists( 'dey_show_product_delivery_total_payable' ) ) {

	/**
	 * Show the product delivery total payable.
	 *
	 * @return string.
	 * */
	function dey_show_product_delivery_total_payable( $product_id ) {
		/**
		 * This hook is used to alter the product delivery total payable label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_show_product_delivery_total_payable', 'yes' !== get_post_meta( $product_id, 'dey_delivery_disable_total_payable', true ), $product_id );
	}
}

if ( ! function_exists( 'dey_show_product_pickup_total_payable' ) ) {

	/**
	 * Show the product delivery total payable.
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	function dey_show_product_pickup_total_payable( $product_id ) {
		/**
		 * This hook is used to alter the product pickup total payable label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_show_product_pickup_total_payable', 'yes' !== get_post_meta( $product_id, 'dey_pickup_disable_total_payable', true ), $product_id );
	}
}

if ( ! function_exists( 'dey_get_order_tip_title_label' ) ) {

	/**
	 * Get the label for order tip title.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_title_label() {
		/**
		 * This hook is used to alter the order tip title label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_title_label', get_option( 'dey_order_tip_title_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_tip_custom_button_label' ) ) {

	/**
	 * Get the label for order tip custom button.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_custom_button_label() {
		/**
		 * This hook is used to alter the order tip custom button label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_custom_button_label', get_option( 'dey_order_tip_custom_button_label' ) );
	}
}

if ( ! function_exists( 'dey_get_custom_tip_field_placeholder' ) ) {

	/**
	 * Get the label for custom tip field placeholder.
	 *
	 * @return string.
	 * */
	function dey_get_custom_tip_field_placeholder() {
		/**
		 * This hook is used to alter the order tip custom tip field placeholder label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_custom_tip_field_placeholder', get_option( 'dey_order_tip_custom_field_placeholder_label' ) );
	}
}

if ( ! function_exists( 'dey_get_custom_add_tip_button_label' ) ) {

	/**
	 * Get the label for custom add tip button.
	 *
	 * @return string.
	 * */
	function dey_get_custom_add_tip_button_label() {
		/**
		 * This hook is used to alter the order tip add custom tip button label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_custom_add_tip_button_label', get_option( 'dey_order_tip_custom_add_tip_button_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_tip_fee_label' ) ) {

	/**
	 * Get the label for order tip fee.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_fee_label() {
		/**
		 * This hook is used to alter the order tip fee label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_fee_label', get_option( 'dey_order_tip_fee_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_tip_fee_remove_label' ) ) {

	/**
	 * Get the label for order tip fee remove.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_fee_remove_label() {
		/**
		 * This hook is used to alter the order tip fee remove label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_fee_remove_label', get_option( 'dey_order_tip_fee_remove_label' ) );
	}
}

if ( ! function_exists( 'dey_get_order_tip_description_message' ) ) {

	/**
	 * Get the message for order tip description.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_description_message() {
		/**
		 * This hook is used to alter the order tip description message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_description_message', get_option( 'dey_order_tip_description_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_tip_fee_added_message' ) ) {

	/**
	 * Get the message for order tip fee added.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_fee_added_message() {
		/**
		 * This hook is used to alter the order tip fee added message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_fee_added_message', get_option( 'dey_order_tip_added_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_order_tip_fee_removed_message' ) ) {

	/**
	 * Get the message for order tip fee removed.
	 *
	 * @return string.
	 * */
	function dey_get_order_tip_fee_removed_message() {
		/**
		 * This hook is used to alter the order tip fee removed message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_fee_removed_message', get_option( 'dey_order_tip_removed_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_custom_tip_empty_message' ) ) {

	/**
	 * Get the message for custom tip empty.
	 *
	 * @return string.
	 * */
	function dey_get_custom_tip_empty_message() {
		/**
		 * This hook is used to alter the order custom tip empty message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_custom_tip_empty_message', get_option( 'dey_order_tip_custom_empty_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_valid_custom_tip_message' ) ) {

	/**
	 * Get the message for valid custom tip.
	 *
	 * @return string.
	 * */
	function dey_get_valid_custom_tip_message() {
		/**
		 * This hook is used to alter the order custom tip valid message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_valid_custom_tip_message', get_option( 'dey_order_tip_custom_valid_msg' ) );
	}
}

if ( ! function_exists( 'dey_get_custom_tip_minimum_message' ) ) {

	/**
	 * Get the message for custom tip minimum amount.
	 *
	 * @return string.
	 * */
	function dey_get_custom_tip_minimum_message( $amount ) {
		$message = str_replace( '{min_amount}', dey_price( $amount ), get_option( 'dey_order_tip_custom_minimum_msg' ) );
		/**
		 * This hook is used to alter the order custom tip minimum message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_custom_tip_minimum_message', $message );
	}
}

if ( ! function_exists( 'dey_get_custom_tip_maximum_message' ) ) {

	/**
	 * Get the message for custom tip maximum amount.
	 *
	 * @return string.
	 * */
	function dey_get_custom_tip_maximum_message( $amount ) {
		$message = str_replace( '{max_amount}', dey_price( $amount ), get_option( 'dey_order_tip_custom_maximum_msg' ) );
		/**
		 * This hook is used to alter the order custom tip maximum message.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_custom_tip_maximum_message', $message );
	}
}

if ( ! function_exists( 'dey_get_checkout_order_tip_wrapper_classes' ) ) {

	/**
	 * Get the classes for checkout order tip wrapper.
	 *
	 * @return array.
	 * */
	function dey_get_checkout_order_tip_wrapper_classes() {
		$classes = array( 'dey-order-tip-wrapper', 'dey-order-tip-checkout-wrapper' );

		// Add the hide class when the custom order tip is only enabled and order tip is added in the cart.
		if ( dey_is_custom_order_tip_type() && dey_get_selected_order_tip() ) {
			$classes[] = 'dey-hide';
		}
		/**
		 * This hook is used to alter the order tip wrapper classes in the checkout.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_checkout_order_tip_wrapper_classes', $classes );
	}
}

if ( ! function_exists( 'dey_get_cart_order_tip_wrapper_classes' ) ) {

	/**
	 * Get the classes for cart order tip wrapper.
	 *
	 * @return array.
	 * */
	function dey_get_cart_order_tip_wrapper_classes() {
		$classes = array( 'dey-order-tip-wrapper', 'dey-order-tip-cart-wrapper' );

		// Add the hide class when the custom order tip is only enabled and order tip is added in the cart.
		if ( dey_is_custom_order_tip_type() && dey_get_selected_order_tip() ) {
			$classes[] = 'dey-hide';
		}
		/**
		 * This hook is used to alter the order tip wrapper classes in the cart.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_cart_order_tip_wrapper_classes', $classes );
	}
}

if ( ! function_exists( 'dey_get_order_tip_button_classes' ) ) {

	/**
	 * Get the classes for order tip button.
	 *
	 * @return array.
	 * */
	function dey_get_order_tip_button_classes( $value, $current ) {
		$classes = array( 'dey-order-tip-button', 'dey-order-tip-predefined-button', 'dey-order-tip-predefined-button-' . $value );
		if ( 'custom' === $value ) {
			$classes[] = 'dey-order-tip-custom-button';
		}

		if ( $current === $value ) {
			$classes[] = 'dey-active';
		}
		/**
		 * This hook is used to alter the order tip button classes.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_button_classes', $classes );
	}
}

if ( ! function_exists( 'dey_get_selected_order_tip' ) ) {

	/**
	 * Get the selected order tip.
	 *
	 * @return array.
	 * */
	function dey_get_selected_order_tip() {
		$session_data = dey_get_order_tip_session_data();
		if ( ! dey_check_is_array( $session_data ) ) {
			return '';
		}

		$order_tip_values = dey_get_order_tip_buttons();
		if ( 'custom' === $session_data['type'] ) {
			return 'custom';
		}

		return $session_data['value'];
	}
}

if ( ! function_exists( 'dey_get_order_tip_custom_amount_actions_classes' ) ) {

	/**
	 * Get the classes for order tip custom amount actions.
	 *
	 * @return array.
	 * */
	function dey_get_order_tip_custom_amount_actions_classes() {
		$classes = array( 'dey-order-tip-custom-amount-actions' );
		// Hide the custom tip fields when the mixed order tip type is enabled.
		if ( dey_is_mixed_order_tip_type() ) {
			$classes[] = 'dey-hide';
		}
		/**
		 * This hook is used to alter the order tip custom amount button classes.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_button_classes', $classes );
	}
}

if ( ! function_exists( 'dey_get_order_calender_colors_labels' ) ) {

	/**
	 * Get the order calendar colors labels.
	 *
	 * @since 3.9.0
	 * @static array $colors_labels
	 * @return array
	 * */
	function dey_get_order_calender_colors_labels() {
		static $colors_labels;
		if ( isset( $colors_labels ) ) {
			return $colors_labels;
		}

		$colors_labels = array(
			'available'      => __( 'Available', 'delivery-slots-for-woocommerce' ),
			'booked'         => __( 'Booked', 'delivery-slots-for-woocommerce' ),
			'partial-booked' => __( 'Partially Booked', 'delivery-slots-for-woocommerce' ),
			'holiday'        => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
		);

		/**
		 * This hook is used to alter the order calendar colors labels.
		 *
		 * @since 3.9.0
		 * @param array $colors_labels
		 */
		return apply_filters( 'dey_order_calender_colors_labels', $colors_labels );
	}
}
