<?php

/**
 * Order Local Pickup.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Order_Local_Pickup' ) ) {

	/**
	 * Class.
	 */
	class DEY_Order_Local_Pickup extends DEY_Post {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE;

		/**
		 * Post Status.
		 *
		 * @var string
		 */
		protected $post_status = 'dey_pending_payment';

		/**
		 * Order ID.
		 *
		 * @var int
		 */
		protected $order_id;

		/**
		 * Created Date.
		 *
		 * @var string
		 */
		protected $created_date;

		/**
		 * Order.
		 *
		 * @var object
		 */
		protected $order;

		/**
		 * Pickup Location.
		 *
		 * @var object
		 */
		protected $pickup_location;

		/**
		 * Meta data keys.
		 */
		protected $meta_data_keys = array(
			'dey_scheduler_rule_id'          => '',
			'dey_pickup_location_id'         => '',
			'dey_pickup_address'             => array(),
			'dey_product_ids'                => '',
			'dey_pickup_charge'              => '',
			'dey_pickup_date'                => '',
			'dey_pickup_date_gmt'            => '',
			'dey_timezone'                   => '',
			'dey_pickup_time_mode'           => '',
			'dey_time_slot_from'             => '',
			'dey_time_slot_to'               => '',
			'dey_time_slot_id'               => '',
			'dey_time_slot_mode'             => '',
			'dey_special_day_id'             => '',
			'dey_special_day_mode'           => '',
			'dey_currency'                   => '',
			'dey_user_id'                    => '',
			'dey_user_name'                  => '',
			'dey_user_email'                 => '',
			'dey_pickup_email_reminder_sent' => '',
			'dey_pickup_charge_details'      => '',
		);

		/**
		 * Prepare extra post data.
		 */
		protected function load_extra_postdata() {
			$this->order_id     = $this->post->post_parent;
			$this->created_date = $this->post->post_date_gmt;
		}

		/**
		 * Get the order.
		 *
		 * @return object/bool
		 */
		public function get_order() {
			if ( isset( $this->order ) ) {
				return $this->order;
			}

			$this->order = wc_get_order( $this->get_order_id() );

			return $this->order;
		}

		/**
		 * Get the pickup location.
		 *
		 * @return object/bool
		 */
		public function get_pickup_location() {
			if ( isset( $this->pickup_location ) ) {
				return $this->pickup_location;
			}

			$this->pickup_location = dey_get_pickup_location( $this->get_pickup_location_id() );

			return $this->pickup_location;
		}

		/**
		 * Get the formatted address.
		 *
		 * @return string
		 */
		public function get_formatted_address() {
			if ( ! dey_check_is_array( $this->get_pickup_address() ) ) {
				return '';
			}

			return implode( ', ', array_filter( $this->get_pickup_address() ) );
		}

		/**
		 * Is time slot mode?.
		 *
		 * @return bool
		 */
		public function is_time_slot_mode() {
			if ( '3' != $this->get_pickup_time_mode() ) {
				return false;
			}

			return true;
		}

		/**
		 * Get the formatted created datetime.
		 *
		 * @return string
		 */
		public function get_formatted_created_date() {
			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() );
		}

		/**
		 * Get the formatted pickup date.
		 *
		 * @return string
		 */
		public function get_formatted_pickup_date() {
			return dey_format_delivery_date( $this->get_pickup_date(), $this->get_pickup_time_mode() );
		}

		/**
		 * Get the formatted time slot from.
		 *
		 * @return string
		 */
		public function get_formatted_time_slot_from() {
			return dey_format_pickup_time_slot( $this->get_time_slot_from() );
		}

		/**
		 * Get the formatted time slot to.
		 *
		 * @return string
		 */
		public function get_formatted_time_slot_to() {
			return dey_format_pickup_time_slot( $this->get_time_slot_to() );
		}

		/**
		 * Get the formatted time slots.
		 *
		 * @return string
		 */
		public function get_formatted_time_slots( $separator = ' - ' ) {
			$time_slots = '';
			if ( ! $this->is_time_slot_mode() ) {
				return $time_slots;
			}

			return dey_format_order_delivery_time_slots( $this->get_time_slot_from(), $this->get_time_slot_to(), $this->get_time_slot_id(), $separator );
		}

		/**
		 * Get the formatted pickup charge.
		 *
		 * @return string
		 */
		public function get_formatted_pickup_charge() {
			return dey_price( $this->get_pickup_charge(), array( 'currency' => $this->get_currency() ) );
		}

		/**
		 * Is as soon as possible time slot?.
		 *
		 * @since 3.7.0
		 * @return boolean
		 */
		public function is_as_soon_as_possible_time_slot() {
			return ( 'soon' === $this->get_time_slot_id() );
		}

		/**
		 * Get pickup date time from.
		 *
		 * @since 3.8.0
		 * @return string
		 */
		public function get_pickup_from_datetime() {
			switch ( $this->get_pickup_time_mode() ) {
				case '2': // Time selector.
					return $this->get_pickup_date();

				case '3': // Time slots.
					if ( ! empty( $this->get_time_slot_from() ) ) {
						return $this->get_pickup_date() . ' ' . $this->get_time_slot_from();
					}

					return $this->get_pickup_date() . ' 00:00:00';

				default:
					return $this->get_pickup_date() . ' 00:00:00';
			}
		}

		/**
		 * Is valid cancel order?
		 *
		 * @since 3.8.0
		 * @return bool
		 */
		public function is_valid_cancel_order() {
			$cutoff_time = dey_get_order_local_pickup_cancel_cutoff_time();
			if ( ! dey_check_is_array( $cutoff_time ) || empty( $cutoff_time['number'] ) ) {
				return true;
			}

			$scheduled_date = $this->get_pickup_from_datetime();
			if ( empty( $scheduled_date ) ) {
				return true;
			}

			$scheduled_date = DEY_Date_Time::get_date_time_object( $scheduled_date );
			// Subtract the cutoff hours/days from the scheduled date.
			$scheduled_date->modify( '-' . $cutoff_time['number'] . $cutoff_time['unit'] );

			// Return if current datetime is reached to cancel cutoff datetime.
			if ( DEY_Date_Time::get_date_time_object( 'now' ) >= $scheduled_date ) {
				return false;
			}

			return true;
		}

		/**
		 * ----------------------------------------------------------------
		 * Setters.
		 * ----------------------------------------------------------------
		 * Functions for setting order local pickup data.
		 */

		/**
		 * Set order ID
		 */
		public function set_order_id( $value ) {
			$this->order_id = $value;
		}

		/**
		 * Set created date.
		 */
		public function set_created_date( $value ) {
			$this->created_date = $value;
		}

		/**
		 * Set scheduler rule ID.
		 *
		 * @since 4.0.0
		 * @param int $value Scheduler rule ID.
		 * */
		public function set_scheduler_rule_id( $value ) {
			$this->set_prop( 'dey_scheduler_rule_id', $value );
		}

		/**
		 * Set pickup location ID.
		 * */
		public function set_pickup_location_id( $value ) {
			$this->set_prop( 'dey_pickup_location_id', $value );
		}

		/**
		 * Set pickup address.
		 * */
		public function set_pickup_address( $value ) {
			$this->set_prop( 'dey_pickup_address', $value );
		}

		/**
		 * Set product IDs.
		 * */
		public function set_product_ids( $value ) {
			$this->set_prop( 'dey_product_ids', $value );
		}

		/**
		 * Set pickup charge.
		 * */
		public function set_pickup_charge( $value ) {
			$this->set_prop( 'dey_pickup_charge', $value );
		}

		/**
		 * Set pickup charge details.
		 *
		 * @since 3.5.0
		 * @param array $price_details Price details.
		 * @return void
		 */
		public function set_pickup_charge_details( $price_details ) {
			$this->set_prop( 'dey_pickup_charge_details', $price_details );
		}

		/**
		 * Set pickup date.
		 */
		public function set_pickup_date( $value ) {
			$this->set_prop( 'dey_pickup_date', $value );
		}

		/**
		 * Set pickup date GMT.
		 */
		public function set_pickup_date_gmt( $value ) {
			$this->set_prop( 'dey_pickup_date_gmt', $value );
		}

		/**
		 * Set time zone.
		 */
		public function set_timezone( $value ) {
			$this->set_prop( 'dey_timezone', $value );
		}

		/**
		 * Set pickup time mode.
		 */
		public function set_pickup_time_mode( $value ) {
			$this->set_prop( 'dey_pickup_time_mode', $value );
		}

		/**
		 * Set time slot from.
		 */
		public function set_time_slot_from( $value ) {
			$this->set_prop( 'dey_time_slot_from', $value );
		}

		/**
		 * Set time slot to.
		 */
		public function set_time_slot_to( $value ) {
			$this->set_prop( 'dey_time_slot_to', $value );
		}

		/**
		 * Set time slot ID.
		 */
		public function set_time_slot_id( $value ) {
			$this->set_prop( 'dey_time_slot_id', $value );
		}

		/**
		 * Set time slot mode.
		 *
		 * @since 4.0.0
		 * @param string $value Time slot mode.
		 */
		public function set_time_slot_mode( $value ) {
			$this->set_prop( 'dey_time_slot_mode', $value );
		}

		/**
		 * Set special day ID.
		 */
		public function set_special_day_id( $value ) {
			$this->set_prop( 'dey_special_day_id', $value );
		}

		/**
		 * Set special day mode.
		 *
		 * @since 4.0.0
		 * @param string $value Special day mode.
		 */
		public function set_special_day_mode( $value ) {
			$this->set_prop( 'dey_special_day_mode', $value );
		}

		/**
		 * Set currency.
		 */
		public function set_currency( $value ) {
			$this->set_prop( 'dey_currency', $value );
		}

		/**
		 * Set user ID.
		 */
		public function set_user_id( $value ) {
			$this->set_prop( 'dey_user_id', $value );
		}

		/**
		 * Set user name.
		 */
		public function set_user_name( $value ) {
			$this->set_prop( 'dey_user_name', $value );
		}

		/**
		 * Set user email.
		 */
		public function set_user_email( $value ) {
			$this->set_prop( 'dey_user_email', $value );
		}

		/**
		 * Set pickup email reminder sent.
		 */
		public function set_pickup_email_reminder_sent( $value ) {
			$this->set_prop( 'dey_pickup_email_reminder_sent', $value );
		}

		/**
		 * ----------------------------------------------------------------
		 * Getters.
		 * ----------------------------------------------------------------
		 * Functions for getting order local pickup data.
		 */

		/**
		 * Get order ID.
		 */
		public function get_order_id() {
			return $this->order_id;
		}

		/**
		 * Get created date.
		 */
		public function get_created_date() {
			return $this->created_date;
		}

		/**
		 * Get scheduler rule ID.
		 *
		 * @since 4.0.0
		 * @return int
		 * */
		public function get_scheduler_rule_id() {
			return $this->get_prop( 'dey_scheduler_rule_id' );
		}

		/**
		 * Get pickup location ID.
		 */
		public function get_pickup_location_id() {
			return $this->get_prop( 'dey_pickup_location_id' );
		}

		/**
		 * Get pickup address.
		 */
		public function get_pickup_address() {
			return $this->get_prop( 'dey_pickup_address' );
		}

		/**
		 * Get product IDs.
		 */
		public function get_product_ids() {
			return $this->get_prop( 'dey_product_ids' );
		}

		/**
		 * Get pickup charge.
		 */
		public function get_pickup_charge() {
			return $this->get_prop( 'dey_pickup_charge' );
		}

		/**
		 * Get pickup charge details.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public function get_pickup_charge_details() {
			return $this->get_prop( 'dey_pickup_charge_details' );
		}

		/**
		 * Get pickup date.
		 */
		public function get_pickup_date() {
			return $this->get_prop( 'dey_pickup_date' );
		}

		/**
		 * Get pickup date GMT.
		 */
		public function get_pickup_date_gmt() {
			return $this->get_prop( 'dey_pickup_date_gmt' );
		}

		/**
		 * Get time zone.
		 */
		public function get_timezone() {
			return $this->get_prop( 'dey_timezone' );
		}

		/**
		 * Get pickup time mode.
		 */
		public function get_pickup_time_mode() {
			return $this->get_prop( 'dey_pickup_time_mode' );
		}

		/**
		 * Get time slot from.
		 */
		public function get_time_slot_from() {
			return $this->get_prop( 'dey_time_slot_from' );
		}

		/**
		 * Get time slot to.
		 */
		public function get_time_slot_to() {
			return $this->get_prop( 'dey_time_slot_to' );
		}

		/**
		 * Get time slot ID.
		 */
		public function get_time_slot_id() {
			return $this->get_prop( 'dey_time_slot_id' );
		}

		/**
		 * Get time slot mode.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_time_slot_mode() {
			return $this->get_prop( 'dey_time_slot_mode' );
		}

		/**
		 * Get special day ID.
		 */
		public function get_special_day_id() {
			return $this->get_prop( 'dey_special_day_id' );
		}

		/**
		 * Get special day mode.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_special_day_mode() {
			return $this->get_prop( 'dey_special_day_mode' );
		}

		/**
		 * Get currency.
		 */
		public function get_currency() {
			return $this->get_prop( 'dey_currency' );
		}

		/**
		 * Get user ID.
		 */
		public function get_user_id() {
			return $this->get_prop( 'dey_user_id' );
		}

		/**
		 * Get user name.
		 */
		public function get_user_name() {
			return $this->get_prop( 'dey_user_name' );
		}

		/**
		 * Get user email.
		 */
		public function get_user_email() {
			return $this->get_prop( 'dey_user_email' );
		}

		/**
		 * Get pickup email reminder sent.
		 */
		public function get_pickup_email_reminder_sent() {
			return $this->get_prop( 'dey_pickup_email_reminder_sent' );
		}
	}

}
