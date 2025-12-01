<?php
/**
 * Updates functions.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'dey_get_scheduler_rule_update_data_keys' ) ) {

	/**
	 * Get the scheduler rule update data keys.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_scheduler_rule_update_data_keys() {
		return array(
			'dey_delivery_slot_mode'                 => 'dey_order_delivery_slot_mode',
			'dey_delivery_days_availability'         => 'dey_order_delivery_days_availablity',
			'dey_delivery_expected_date_from'        => 'dey_order_delivery_expected_date_from',
			'dey_delivery_expected_date_to'          => 'dey_order_delivery_expected_date_to',
			'dey_delivery_days'                      => 'dey_order_delivery_days',
			'dey_delivery_max_per_day'               => 'dey_order_delivery_max_per_day',
			'dey_delivery_calender_required'         => 'dey_order_delivery_calender_mandatory_field',
			'dey_delivery_time_mode'                 => 'dey_order_delivery_time_mode',
			'dey_delivery_available_time_from'       => 'dey_order_delivery_available_time_from',
			'dey_delivery_available_time_to'         => 'dey_order_delivery_available_time_to',
			'dey_delivery_time_slot_required'        => 'dey_order_delivery_time_slot_mandatory_field',
			'dey_delivery_as_soon_as_possible'       => 'dey_order_delivery_enable_as_soon_as_possible',
			'dey_delivery_first_available_time_slot' => 'dey_order_delivery_display_first_available_time_slot',
			'dey_delivery_time_slot_hide_zero_price' => 'dey_order_delivery_time_slot_hide_zero_price',
			'dey_delivery_time_slot_max'             => 'dey_order_delivery_time_slot_max',
			'dey_delivery_processing_time'           => 'dey_order_delivery_processing_time',
			'dey_delivery_same_day_cutoff_time'      => 'dey_order_delivery_cutoff_time',
			'dey_delivery_next_day_cutoff_time'      => 'dey_order_delivery_next_day_cutoff_time',
			'dey_delivery_weekdays_prices'           => 'dey_order_delivery_weekdays_prices',
			'dey_delivery_same_day_fee'              => 'dey_order_delivery_same_day_fee',
			'dey_delivery_next_day_fee'              => 'dey_order_delivery_next_day_fee',
			'dey_delivery_calculate_tax'             => 'dey_order_delivery_calculate_tax',
			'dey_pickup_days_availability'           => 'dey_local_pickup_days_availablity',
			'dey_pickup_days'                        => 'dey_local_pickup_days',
			'dey_pickup_max_per_day'                 => 'dey_local_pickup_max_per_day',
			'dey_pickup_location_required'           => 'dey_local_pickup_location_mandatory_field',
			'dey_pickup_calender_required'           => 'dey_local_pickup_calender_mandatory_field',
			'dey_pickup_time_mode'                   => 'dey_local_pickup_time_mode',
			'dey_pickup_time_slot_required'          => 'dey_local_pickup_time_slot_mandatory_field',
			'dey_pickup_as_soon_as_possible'         => 'dey_local_pickup_enable_as_soon_as_possible',
			'dey_pickup_first_available_time_slot'   => 'dey_local_pickup_display_first_available_time_slot',
			'dey_pickup_time_slot_hide_zero_price'   => 'dey_local_pickup_time_slot_hide_zero_price',
			'dey_pickup_available_time_from'         => 'dey_local_pickup_available_time_from',
			'dey_pickup_available_time_to'           => 'dey_local_pickup_available_time_to',
			'dey_pickup_time_slot_max'               => 'dey_local_pickup_time_slot_max',
			'dey_pickup_processing_time'             => 'dey_local_pickup_processing_time',
			'dey_pickup_same_day_cutoff_time'        => 'dey_local_pickup_cutoff_time',
			'dey_pickup_next_day_cutoff_time'        => 'dey_local_pickup_next_day_cutoff_time',
			'dey_pickup_weekdays_prices'             => 'dey_local_pickup_weekdays_prices',
			'dey_pickup_same_day_fee'                => 'dey_local_pickup_same_day_fee',
			'dey_pickup_next_day_fee'                => 'dey_local_pickup_next_day_fee',
			'dey_pickup_calculate_tax'               => 'dey_local_pickup_calculate_tax',
		);
	}
}
