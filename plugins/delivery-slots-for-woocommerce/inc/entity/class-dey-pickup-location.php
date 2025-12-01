<?php
/**
 * Pickup Location.
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Pickup_Location' ) ) {

	/**
	 * Class.
	 */
	class DEY_Pickup_Location extends DEY_Post {

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::PICKUP_LOCATIONS_POSTTYPE;

		/**
		 * Post status.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected $post_status = 'dey_active';

		/**
		 * Name.
		 *
		 * @var string
		 */
		protected $name;

		/**
		 * Created date.
		 *
		 * @var string
		 */
		protected $created_date;

		/**
		 * Modified date.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		public $modified_date;

		/**
		 * Meta data keys.
		 */
		protected $meta_data_keys = array(
			'dey_pickup_mode'                      => '',
			'dey_address1'                         => '',
			'dey_address2'                         => '',
			'dey_city'                             => '',
			'dey_country'                          => '',
			'dey_pincode'                          => '',
			'dey_phone_number'                     => '',
			'dey_filter_groups'                    => '',
			'dey_email_lists'                      => '',
			'dey_pickup_days_availability'         => '',
			'dey_pickup_days'                      => array(),
			'dey_pickup_max_per_day'               => '',
			'dey_pickup_location_required'         => '',
			'dey_pickup_calender_required'         => '',
			'dey_pickup_time_mode'                 => '',
			'dey_pickup_time_slot_required'        => '',
			'dey_pickup_as_soon_as_possible'       => '',
			'dey_pickup_first_available_time_slot' => '',
			'dey_pickup_time_slot_hide_zero_price' => '',
			'dey_pickup_available_time_from'       => '',
			'dey_pickup_available_time_to'         => '',
			'dey_pickup_time_slot_max'             => '',
			'dey_pickup_processing_time'           => '',
			'dey_pickup_same_day_cutoff_time'      => '',
			'dey_pickup_next_day_cutoff_time'      => '',
			'dey_pickup_weekdays_prices'           => '',
			'dey_pickup_same_day_fee'              => '',
			'dey_pickup_next_day_fee'              => '',
			'dey_pickup_calculate_tax'             => '',
			'dey_time_slots_mode'                  => '',
			'dey_time_slots'                       => array(),
			'dey_holidays_mode'                    => '',
			'dey_holidays'                         => array(),
			'dey_special_days_mode'                => '',
			'dey_special_days'                     => array(),
			'dey_restriction_rule_groups'          => array(),
		);

		/**
		 * Duplicate meta data keys.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		protected $duplicate_meta_keys = array(
			'dey_pickup_mode'                      => '',
			'dey_address1'                         => '',
			'dey_address2'                         => '',
			'dey_city'                             => '',
			'dey_country'                          => '',
			'dey_pincode'                          => '',
			'dey_phone_number'                     => '',
			'dey_filter_groups'                    => '',
			'dey_email_lists'                      => '',
			'dey_pickup_days_availability'         => '',
			'dey_pickup_days'                      => array(),
			'dey_pickup_max_per_day'               => '',
			'dey_pickup_location_required'         => '',
			'dey_pickup_calender_required'         => '',
			'dey_pickup_time_mode'                 => '',
			'dey_pickup_time_slot_required'        => '',
			'dey_pickup_as_soon_as_possible'       => '',
			'dey_pickup_first_available_time_slot' => '',
			'dey_pickup_time_slot_hide_zero_price' => '',
			'dey_pickup_available_time_from'       => '',
			'dey_pickup_available_time_to'         => '',
			'dey_pickup_time_slot_max'             => '',
			'dey_pickup_processing_time'           => '',
			'dey_pickup_same_day_cutoff_time'      => '',
			'dey_pickup_next_day_cutoff_time'      => '',
			'dey_pickup_weekdays_prices'           => '',
			'dey_pickup_same_day_fee'              => '',
			'dey_pickup_next_day_fee'              => '',
			'dey_pickup_calculate_tax'             => '',
			'dey_time_slots_mode'                  => '',
			'dey_time_slots'                       => array(),
			'dey_holidays_mode'                    => '',
			'dey_holidays'                         => array(),
			'dey_special_days_mode'                => '',
			'dey_special_days'                     => array(),
			'dey_restriction_rule_groups'          => array(),
		);

		/**
		 * Prepare extra post data.
		 */
		protected function load_extra_postdata() {
			$this->name          = $this->post->post_title;
			$this->created_date  = $this->post->post_date_gmt;
			$this->modified_date = $this->post->post_modified_gmt;
		}

		/**
		 * Get the formatted created datetime.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_formatted_created_date() {
			return ! empty( $this->get_created_date() ) ? DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() ) : '';
		}

		/**
		 * Get the formatted modified date time.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_formatted_modified_date() {
			return ! empty( $this->get_modified_date() ) ? DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_modified_date() ) : '';
		}

		/**
		 * Get order local pickup time slots options labels.
		 *
		 * @since 4.0.0
		 * @param string $date Selected date.
		 * @return array
		 */
		public function get_order_local_pickup_time_slot_option_labels( $date ) {
			$time_slot_options = array();
			if ( 'yes' === $this->get_pickup_as_soon_as_possible() ) {
				$time_slot_options[] = array(
					'id'    => 'soon',
					'label' => dey_get_order_local_pickup_as_soon_as_possible_label(),
				);
			}

			if ( '2' === $this->get_time_slots_mode() ) { // Rule level.
				foreach ( $this->get_time_slots() as $key => $time_slot ) {
					if ( ! dey_check_is_array( $time_slot ) ) {
						continue;
					}

					if ( ! $this->is_valid_time_slot( $date, $time_slot ) ) {
						continue;
					}

					$time_slot_options[] = array(
						'id'    => $key,
						'label' => $this->get_time_slot_label( $time_slot ),
					);
				}
			} else { // Global level.
				$time_slot_ids = dey_get_order_pickup_time_slots();
				if ( ! dey_check_is_array( $time_slot_ids ) ) {
					return $time_slot_options;
				}

				foreach ( $time_slot_ids as $time_slot_id ) {
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( ! $time_slot->is_valid( $date, 'local_pickup' ) ) {
						continue;
					}

					$time_slot_options[] = array(
						'id'    => $time_slot_id,
						'label' => $time_slot->get_time_slot_label(),
					);
				}
			}

			return dey_check_is_array( $time_slot_options ) ? $time_slot_options : array(
				array(
					'id'    => 'none',
					'label' => dey_get_order_local_pickup_unavailable_time_slot_label(),
				),
			);
		}

		/**
		 * Get the rule level time slot label.
		 *
		 * @since 4.0.0
		 * @param array $time_slot Time slot options.
		 * @return string
		 */
		public function get_time_slot_label( $time_slot ) {
			$price      = $time_slot['price'];
			$time_slots = dey_get_formatted_order_time_slot_label( $time_slot['from_time'], $time_slot['to_time'] );

			if ( 'yes' === $this->get_pickup_calculate_tax() ) {
				$price = dey_get_cart_price_to_display( $price );
			}

			if ( empty( $price ) && 'yes' === $this->get_pickup_time_slot_hide_zero_price() ) {
				$label = $time_slots;
			} else {
				$label = $time_slots . '(' . dey_price( $price ) . ')';
			}

			/**
			 * This hook is used to alter the order pickup location time slot label.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_order_pickup_location_time_slot_label', $label, $this );
		}

		/**
		 * Is valid time slot?
		 *
		 * @since 4.0.0
		 * @param string $date Selected date.
		 * @param array  $time_slot Time slot.
		 * @return bool
		 */
		public function is_valid_time_slot( $date, $time_slot ) {
			$date_object         = DEY_Date_Time::get_date_time_object( $date . ' ' . $time_slot['from_time'] );
			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			// Validate if the time slot crossed the current time.
			if ( $current_date_object >= $date_object ) {
				return false;
			}

			// Check if the selected date has crossed the cutoff time for the current date.
			if ( ! empty( $time_slot['cutoff_time'] ) && ( $current_date_object->format( 'Y-m-d' ) === $date_object->format( 'Y-m-d' ) ) ) {
				$cutoff_date_object = DEY_Date_Time::get_date_time_object( $current_date_object->format( 'Y-m-d' ) . ' ' . $time_slot['cutoff_time'] );
				if ( $current_date_object >= $cutoff_date_object ) {
					return false;
				}
			}

			// Validate the time slot week days.
			if ( dey_check_is_array( $time_slot['week_days'] ) && ! in_array( $date_object->format( 'w' ) + 1, $time_slot['week_days'] ) ) {
				return false;
			}

			// Validate the time slot usage count.
			if ( ! empty( $time_slot['order_count'] ) ) {
				$used_count = isset( $time_slot['used_order_count'][ $date_object->format( 'Y-m-d' ) ] ) ? intval( $time_slot['used_order_count'][ $date_object->format( 'Y-m-d' ) ] ) : 0;
				// Return if the used order count is reached the maximum order count.
				if ( intval( $time_slot['order_count'] ) <= $used_count ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * ----------------------------------------------------------------
		 * Setters.
		 * ----------------------------------------------------------------
		 * Functions to set the pickup location data.
		 */


		/**
		 * Set name
		 */
		public function set_name( $value ) {
			$this->name = $value;
		}

		/**
		 * Set created date.
		 *
		 * @since 4.0.0
		 * @param string $value Created date.
		 */
		public function set_created_date( $value ) {
			$this->created_date = $value;
		}

		/**
		 * Set modified date.
		 *
		 * @since 4.0.0
		 * @param string $value Modified date.
		 */
		public function set_modified_date( $value ) {
			$this->modified_date = $value;
		}

		/**
		 * Set pickup based on.
		 *
		 * @since 4.0.0
		 * @param string $value Pickup based on.
		 * */
		public function set_pickup_mode( $value ) {
			$this->set_prop( 'dey_pickup_mode', $value );
		}

		/**
		 * Set address1.
		 * */
		public function set_address1( $value ) {
			$this->set_prop( 'dey_address1', $value );
		}

		/**
		 * Set address2.
		 * */
		public function set_address2( $value ) {
			$this->set_prop( 'dey_address2', $value );
		}

		/**
		 * Set city.
		 */
		public function set_city( $value ) {
			$this->set_prop( 'dey_city', $value );
		}

		/**
		 * Set country.
		 */
		public function set_country( $value ) {
			$this->set_prop( 'dey_country', $value );
		}

		/**
		 * Set pin code.
		 */
		public function set_pincode( $value ) {
			$this->set_prop( 'dey_pincode', $value );
		}

		/**
		 * Set phone number.
		 */
		public function set_phone_number( $value ) {
			$this->set_prop( 'dey_phone_number', $value );
		}

		/**
		 * Set filter groups.
		 */
		public function set_filter_groups( $value ) {
			$this->set_prop( 'dey_filter_groups', $value );
		}

		/**
		 * Set email lists.
		 */
		public function set_email_lists( $value ) {
			$this->set_prop( 'dey_email_lists', $value );
		}

		/**
		 * Get name.
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * Set the number of days available for pickup.
		 *
		 * @since 4.0.0
		 * @param int|string $days_availability Number of days availability.
		 * @return void
		 */
		public function set_pickup_days_availability( $days_availability ) {
			$this->set_prop( 'dey_pickup_days_availability', $days_availability );
		}

		/**
		 * Set the pickup days.
		 *
		 * @since 4.0.0
		 * @param array $pickup_days Pickup days.
		 */
		public function set_pickup_days( $pickup_days ) {
			$this->set_prop( 'dey_pickup_days', $pickup_days );
		}

		/**
		 * Set the maximum pickup's per day.
		 *
		 * @since 4.0.0
		 * @param int|string $max_per_day Maximum pickup's per day.
		 * @return void
		 */
		public function set_pickup_max_per_day( $max_per_day ) {
			$this->set_prop( 'dey_pickup_max_per_day', $max_per_day );
		}

		/**
		 * Set whether the pickup location field is mandatory or not.
		 *
		 * @since 4.0.0
		 * @param string $value Whether the pickup location field is mandatory or not.
		 * @return void
		 */
		public function set_pickup_location_required( $value ) {
			$this->set_prop( 'dey_pickup_location_required', $value );
		}

		/**
		 * Set whether the calendar is mandatory or not.
		 *
		 * @since 4.0.0
		 * @param string $value Whether the calendar is mandatory or not.
		 * @return void
		 */
		public function set_pickup_calender_required( $value ) {
			$this->set_prop( 'dey_pickup_calender_required', $value );
		}

		/**
		 * Set the pickup time mode.
		 *
		 * @since 4.0.0
		 * @param string $time_mode Time mode.
		 * @return void
		 */
		public function set_pickup_time_mode( $time_mode ) {
			$this->set_prop( 'dey_pickup_time_mode', $time_mode );
		}

		/**
		 * Set whether the time slot field is mandatory field or not.
		 *
		 * @since 4.0.0
		 * @param string $value Whether the time slot field is mandatory or not.
		 * @return void
		 */
		public function set_pickup_time_slot_required( $value ) {
			$this->set_prop( 'dey_pickup_time_slot_required', $value );
		}

		/**
		 * Set whether to enable or disable the as soon as possible.
		 *
		 * @since 4.0.0
		 * @param string $value Whether to enable or disable.
		 * @return void
		 */
		public function set_pickup_as_soon_as_possible( $value ) {
			$this->set_prop( 'dey_pickup_as_soon_as_possible', $value );
		}

		/**
		 * Set whether to enable or disable first available time slot.
		 *
		 * @since 4.0.0
		 * @param string $value Whether to enable or disable.
		 * @return void
		 */
		public function set_pickup_first_available_time_slot( $value ) {
			$this->set_prop( 'dey_pickup_first_available_time_slot', $value );
		}

		/**
		 * Set whether to hide or show time slots, if the price is zero.
		 *
		 * @since 4.0.0
		 * @param string $value Whether to hide or not.
		 * @return void
		 */
		public function set_pickup_time_slot_hide_zero_price( $value ) {
			$this->set_prop( 'dey_pickup_time_slot_hide_zero_price', $value );
		}

		/**
		 * Set the pickup available time from.
		 *
		 * @since 4.0.0
		 * @param string $from_time Available time from.
		 * @return void
		 */
		public function set_pickup_available_time_from( $from_time ) {
			$this->set_prop( 'dey_pickup_available_time_from', $from_time );
		}

		/**
		 * Set the delivery available time to.
		 *
		 * @since 4.0.0
		 * @param string $to_time Available time to.
		 * @return void
		 */
		public function set_pickup_available_time_to( $to_time ) {
			$this->set_prop( 'dey_pickup_available_time_to', $to_time );
		}

		/**
		 * Set maximum pickup's per day for globally.
		 *
		 * @since 4.0.0
		 * @param int|string $max_per_day Maximum pickup's per day.
		 * @return void
		 */
		public function set_pickup_time_slot_max( $max_per_day ) {
			$this->set_prop( 'dey_pickup_time_slot_max', $max_per_day );
		}

		/**
		 * Set the pickup processing time.
		 *
		 * @since 4.0.0
		 * @param array $processing_time Processing time.
		 * @return void
		 */
		public function set_pickup_processing_time( $processing_time ) {
			$this->set_prop( 'dey_pickup_processing_time', $processing_time );
		}

		/**
		 * Set the cutoff time for same day.
		 *
		 * @since 4.0.0
		 * @param string $same_day_cutoff_time Cutoff time for same day.
		 * @return void
		 */
		public function set_pickup_same_day_cutoff_time( $same_day_cutoff_time ) {
			$this->set_prop( 'dey_pickup_same_day_cutoff_time', $same_day_cutoff_time );
		}

		/**
		 * Set the cutoff time for next day.
		 *
		 * @since 4.0.0
		 * @param string $next_day_cutoff_time Cutoff time for next day.
		 * @return void
		 */
		public function set_pickup_next_day_cutoff_time( $next_day_cutoff_time ) {
			$this->set_prop( 'dey_pickup_next_day_cutoff_time', $next_day_cutoff_time );
		}

		/**
		 * Set the weekdays prices for pickup.
		 *
		 * @since 4.0.0
		 * @param array $weekdays_prices Weekdays prices for pickup.
		 * @return void
		 */
		public function set_pickup_weekdays_prices( $weekdays_prices ) {
			$this->set_prop( 'dey_pickup_weekdays_prices', $weekdays_prices );
		}

		/**
		 * Set the fee for same day.
		 *
		 * @since 4.0.0
		 * @param int|float $same_day_fee Same day fee.
		 * @return void
		 */
		public function set_pickup_same_day_fee( $same_day_fee ) {
			$this->set_prop( 'dey_pickup_same_day_fee', $same_day_fee );
		}

		/**
		 * Set the fee for next day.
		 *
		 * @since 4.0.0
		 * @param int|float $next_day_fee Next day fee.
		 * @return void
		 */
		public function set_pickup_next_day_fee( $next_day_fee ) {
			$this->set_prop( 'dey_pickup_next_day_fee', $next_day_fee );
		}

		/**
		 * Set whether to calculate tax or not.
		 *
		 * @since 4.0.0
		 * @param string $value Whether to calculate tax or not.
		 * @return void
		 */
		public function set_pickup_calculate_tax( $value ) {
			$this->set_prop( 'dey_pickup_calculate_tax', $value );
		}

		/**
		 * Set time slots based on.
		 *
		 * @since 4.0.0
		 * @param string $value Whether global level or rule level.
		 */
		public function set_time_slots_mode( $value ) {
			$this->set_pop( 'dey_time_slots_mode', $value );
		}

		/**
		 * Set time slots.
		 *
		 * @since 4.0.0
		 * @param string $value time slots data.
		 * @return void
		 */
		public function set_time_slots( $value ) {
			$this->set_prop( 'dey_time_slots', $value );
		}

		/**
		 * Set holidays based on.
		 *
		 * @since 4.0.0
		 * @param string $value Whether global level or rule level.
		 */
		public function set_holidays_mode( $value ) {
			$this->set_prop( 'dey_holidays_mode', $value );
		}

		/**
		 * Set holidays.
		 *
		 * @since 4.0.0
		 * @param array $holidays Holidays.
		 * @return void
		 */
		public function set_holidays( $holidays ) {
			$this->set_prop( 'dey_holidays', $holidays );
		}

		/**
		 * Set special days based on.
		 *
		 * @since 4.0.0
		 * @param array $value Whether global level or rule level.
		 */
		public function set_special_days_mode( $value ) {
			$this->set_prop( 'dey_special_days_mode', $value );
		}

		/**
		 * Set special days.
		 *
		 * @since 4.0.0
		 * @param array $special_days Special days.
		 * @return void
		 */
		public function set_special_days( $special_days ) {
			$this->set_prop( 'dey_special_days', $special_days );
		}

		/**
		 * Set restriction rule groups.
		 *
		 * @since 4.0.0
		 * @param array $rule_groups Restriction rule groups.
		 */
		public function set_restriction_rule_groups( $rule_groups ) {
			$this->set_prop( 'dey_restriction_rule_groups', $rule_groups );
		}

		/**
		 * ----------------------------------------------------------------
		 * Getters.
		 * ----------------------------------------------------------------
		 * Functions to get the pickup location data.
		 */

		/**
		 * Get created date.
		 *
		 * @return string
		 */
		public function get_created_date() {
			return $this->created_date;
		}

		/**
		 * Get modified date.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_modified_date() {
			return $this->modified_date;
		}

		/**
		 * Get pickup based on.
		 *
		 * @since 4.0.0
		 * */
		public function get_pickup_mode() {
			return $this->get_prop( 'dey_pickup_mode' );
		}

		/**
		 * Get address1.
		 */
		public function get_address1() {
			return $this->get_prop( 'dey_address1' );
		}

		/**
		 * Get address2.
		 */
		public function get_address2() {
			return $this->get_prop( 'dey_address2' );
		}

		/**
		 * Get city.
		 */
		public function get_city() {
			return $this->get_prop( 'dey_city' );
		}

		/**
		 * Get country.
		 */
		public function get_country() {
			return $this->get_prop( 'dey_country' );
		}

		/**
		 * Get pin code.
		 */
		public function get_pincode() {
			return $this->get_prop( 'dey_pincode' );
		}

		/**
		 * Get phone number.
		 */
		public function get_phone_number() {
			return $this->get_prop( 'dey_phone_number' );
		}

		/**
		 * Get filter groups.
		 */
		public function get_filter_groups() {
			return $this->get_prop( 'dey_filter_groups' );
		}

		/**
		 * Get email lists.
		 */
		public function get_email_lists() {
			return $this->get_prop( 'dey_email_lists' );
		}

		/**
		 * Get the number of days available for pickup.
		 *
		 * @since 4.0.0
		 * @return int|string
		 */
		public function get_pickup_days_availability() {
			return $this->get_prop( 'dey_pickup_days_availability' );
		}

		/**
		 * Get the pickup days.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function get_pickup_days() {
			return $this->get_prop( 'dey_pickup_days' );
		}

		/**
		 * Get the maximum pickup's per day.
		 *
		 * @since 4.0.0
		 * @return int|string
		 */
		public function get_pickup_max_per_day() {
			return $this->get_prop( 'dey_pickup_max_per_day' );
		}

		/**
		 * Get whether the pickup location field is mandatory or not.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_location_required() {
			return $this->get_prop( 'dey_pickup_location_required' );
		}

		/**
		 * Get whether the calendar is mandatory or not.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_calender_required() {
			return $this->get_prop( 'dey_pickup_calender_required' );
		}

		/**
		 * Get the pickup time mode.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_time_mode() {
			return $this->get_prop( 'dey_pickup_time_mode' );
		}

		/**
		 * Get whether the time slot field is mandatory field or not.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_time_slot_required() {
			return $this->get_prop( 'dey_pickup_time_slot_required' );
		}

		/**
		 * Get whether to enable or disable the as soon as possible.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_as_soon_as_possible() {
			return $this->get_prop( 'dey_pickup_as_soon_as_possible' );
		}

		/**
		 * Get whether to enable or disable first available time slot.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_first_available_time_slot() {
			return $this->get_prop( 'dey_pickup_first_available_time_slot' );
		}

		/**
		 * Get whether to hide or show time slots, if the price is zero.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_time_slot_hide_zero_price() {
			return $this->get_prop( 'dey_pickup_time_slot_hide_zero_price' );
		}

		/**
		 * Get the pickup available time from.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_available_time_from() {
			return $this->get_prop( 'dey_pickup_available_time_from' );
		}

		/**
		 * Get the delivery available time to.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_available_time_to() {
			return $this->get_prop( 'dey_pickup_available_time_to' );
		}

		/**
		 * Get maximum pickup's per day for globally.
		 *
		 * @since 4.0.0
		 * @return int|string
		 */
		public function get_pickup_time_slot_max() {
			return $this->get_prop( 'dey_pickup_time_slot_max' );
		}

		/**
		 * Get the pickup processing time.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function get_pickup_processing_time() {
			return $this->get_prop( 'dey_pickup_processing_time' );
		}

		/**
		 * Get the cutoff time for same day.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_same_day_cutoff_time() {
			return $this->get_prop( 'dey_pickup_same_day_cutoff_time' );
		}

		/**
		 * Get the cutoff time for next day.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_next_day_cutoff_time() {
			return $this->get_prop( 'dey_pickup_next_day_cutoff_time' );
		}

		/**
		 * Get the weekdays prices for pickup.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function get_pickup_weekdays_prices() {
			return $this->get_prop( 'dey_pickup_weekdays_prices' );
		}

		/**
		 * Get the fee for same day.
		 *
		 * @since 4.0.0
		 * @return int|float
		 */
		public function get_pickup_same_day_fee() {
			return $this->get_prop( 'dey_pickup_same_day_fee' );
		}

		/**
		 * Get the fee for next day.
		 *
		 * @since 4.0.0
		 * @return int|float
		 */
		public function get_pickup_next_day_fee() {
			return $this->get_prop( 'dey_pickup_next_day_fee' );
		}

		/**
		 * Get whether to calculate tax or not.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_pickup_calculate_tax() {
			return $this->get_prop( 'dey_pickup_calculate_tax' );
		}

		/**
		 * Get time slots based on.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_time_slots_mode() {
			return $this->get_prop( 'dey_time_slots_mode' );
		}

		/**
		 * Get time slots.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_time_slots() {
			return $this->get_prop( 'dey_time_slots' );
		}

		/**
		 * Get holidays based on.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_holidays_mode() {
			return $this->get_prop( 'dey_holidays_mode' );
		}

		/**
		 * Get holidays.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function get_holidays() {
			return $this->get_prop( 'dey_holidays' );
		}

		/**
		 * Get special days based on.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_special_days_mode() {
			return $this->get_prop( 'dey_special_days_mode' );
		}

		/**
		 * Get special days.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function get_special_days() {
			return $this->get_prop( 'dey_special_days' );
		}

		/**
		 * Get restriction rule groups.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function get_restriction_rule_groups() {
			return $this->get_prop( 'dey_restriction_rule_groups' );
		}
	}

}
