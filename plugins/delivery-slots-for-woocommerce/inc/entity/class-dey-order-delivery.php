<?php
/**
 * Order Delivery.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Order_Delivery' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 */
	class DEY_Order_Delivery extends DEY_Post {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::ORDER_DELIVERY_POSTTYPE;

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
		 * Meta data keys.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected $meta_data_keys = array(
			'dey_scheduler_rule_id'            => '',
			'dey_product_ids'                  => '',
			'dey_delivery_charge'              => '',
			'dey_delivery_date'                => '',
			'dey_delivery_date_gmt'            => '',
			'dey_delivery_last_date'           => '',
			'dey_delivery_last_date_gmt'       => '',
			'dey_timezone'                     => '',
			'dey_delivery_mode'                => '',
			'dey_delivery_time_mode'           => '',
			'dey_time_slot_from'               => '',
			'dey_time_slot_to'                 => '',
			'dey_time_slot_id'                 => '',
			'dey_time_slot_mode'               => '',
			'dey_special_day_id'               => '',
			'dey_special_day_mode'             => '',
			'dey_currency'                     => '',
			'dey_user_id'                      => '',
			'dey_user_name'                    => '',
			'dey_user_email'                   => '',
			'dey_delivery_email_reminder_sent' => '',
			'dey_delivery_charge_details'      => array(),
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
		 * Is time slot mode?.
		 *
		 * @return bool
		 */
		public function is_time_slot_mode() {
			if ( '2' == $this->get_delivery_mode() ) {
				return false;
			}

			if ( '3' != $this->get_delivery_time_mode() ) {
				return false;
			}

			return true;
		}

		/**
		 * Get the formatted created datetime.
		 */
		public function get_formatted_created_date() {
			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() );
		}

		/**
		 * Get the formatted delivery date.
		 */
		public function get_formatted_delivery_date() {
			switch ( $this->get_delivery_mode() ) {
				case '2':
					$formatted_delivery_date = $this->get_formatted_expected_delivery_date_msg();
					break;

				default:
					$formatted_delivery_date = dey_format_delivery_date( $this->get_delivery_date(), $this->get_delivery_time_mode() );
					break;
			}

			return $formatted_delivery_date;
		}

		/**
		 * Get the formatted time slot from.
		 */
		public function get_formatted_time_slot_from() {
			return dey_format_delivery_time_slot( $this->get_time_slot_from() );
		}

		/**
		 * Get the formatted time slot to.
		 */
		public function get_formatted_time_slot_to() {
			return dey_format_delivery_time_slot( $this->get_time_slot_to() );
		}

		/**
		 * Get the formatted time slots.
		 *
		 * @return string
		 */
		public function get_formatted_time_slots( $separator = ' - ' ) {
			if ( ! $this->is_time_slot_mode() ) {
				return '';
			}

			return dey_format_order_delivery_time_slots( $this->get_time_slot_from(), $this->get_time_slot_to(), $this->get_time_slot_id(), $separator );
		}

		/**
		 * Get the formatted expected delivery date message.
		 *
		 * @return string
		 */
		public function get_formatted_expected_delivery_date_msg() {
			return dey_get_expected_order_delivery_date_message( $this->get_delivery_date(), $this->get_delivery_last_date() );
		}

		/**
		 * Get the formatted delivery charge.
		 *
		 * @return bool
		 */
		public function get_formatted_delivery_charge() {
			return dey_price( $this->get_delivery_charge(), array( 'currency' => $this->get_currency() ) );
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
		 * Get delivery date time from.
		 *
		 * @since 3.8.0
		 * @return string
		 */
		public function get_delivery_from_datetime() {
			switch ( $this->get_delivery_time_mode() ) {
				case '2': // Time selector.
					return $this->get_delivery_date();

				case '3': // Time slots.
					if ( ! empty( $this->get_time_slot_from() ) ) {
						return $this->get_delivery_date() . ' ' . $this->get_time_slot_from();
					}

					return $this->get_delivery_date() . ' 00:00:00';

				default:
					return $this->get_delivery_date() . ' 00:00:00';
			}
		}

		/**
		 * Is valid cancel order?
		 *
		 * @since 3.8.0
		 * @return bool
		 */
		public function is_valid_cancel_order() {
			$cutoff_time = dey_get_order_delivery_cancel_cutoff_time();
			if ( ! dey_check_is_array( $cutoff_time ) || empty( $cutoff_time['number'] ) ) {
				return true;
			}

			$scheduled_date = $this->get_delivery_from_datetime();
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
		 * Function for setting the order delivery data.
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
		 * Set product IDs.
		 * */
		public function set_product_ids( $value ) {
			$this->set_prop( 'dey_product_ids', $value );
		}

		/**
		 * Set delivery charge.
		 * */
		public function set_delivery_charge( $value ) {
			$this->set_prop( 'dey_delivery_charge', $value );
		}

		/**
		 * Set delivery charge details.
		 *
		 * @since 3.5.0
		 * @param array $price_details Price details.
		 * @return void
		 */
		public function set_delivery_charge_details( $price_details ) {
			$this->set_prop( 'dey_delivery_charge_details', $price_details );
		}

		/**
		 * Set delivery date.
		 */
		public function set_delivery_date( $value ) {
			$this->set_prop( 'dey_delivery_date', $value );
		}

		/**
		 * Set delivery date GMT.
		 */
		public function set_delivery_date_gmt( $value ) {
			$this->set_prop( 'dey_delivery_date_gmt', $value );
		}

		/**
		 * Set delivery last date.
		 */
		public function set_delivery_last_date( $value ) {
			$this->set_prop( 'dey_delivery_last_date', $value );
		}

		/**
		 * Set delivery last date GMT.
		 */
		public function set_delivery_last_date_gmt( $value ) {
			$this->set_prop( 'dey_delivery_last_date_gmt', $value );
		}

		/**
		 * Set time zone.
		 */
		public function set_timezone( $value ) {
			$this->set_prop( 'dey_timezone', $value );
		}

		/**
		 * Set delivery mode.
		 */
		public function set_delivery_mode( $value ) {
			$this->set_prop( 'dey_delivery_mode', $value );
		}

		/**
		 * Set delivery time mode.
		 */
		public function set_delivery_time_mode( $value ) {
			$this->set_prop( 'dey_delivery_time_mode', $value );
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
		 * Set delivery email reminder sent.
		 */
		public function set_delivery_email_reminder_sent( $value ) {
			$this->set_prop( 'dey_delivery_email_reminder_sent', $value );
		}

		/**
		 * ----------------------------------------------------------------
		 * Getters.
		 * ----------------------------------------------------------------
		 * Functions for getting the order delivery data.
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
		 * Get product IDs.
		 */
		public function get_product_ids() {
			return $this->get_prop( 'dey_product_ids' );
		}

		/**
		 * Get delivery charge.
		 */
		public function get_delivery_charge() {
			return $this->get_prop( 'dey_delivery_charge' );
		}

		/**
		 * Get delivery charge details.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public function get_delivery_charge_details() {
			return $this->get_prop( 'dey_delivery_charge_details' );
		}

		/**
		 * Get delivery date.
		 */
		public function get_delivery_date() {
			return $this->get_prop( 'dey_delivery_date' );
		}

		/**
		 * Get delivery date GMT.
		 */
		public function get_delivery_date_gmt() {
			return $this->get_prop( 'dey_delivery_date_gmt' );
		}

		/**
		 * Get delivery last date.
		 */
		public function get_delivery_last_date() {
			return $this->get_prop( 'dey_delivery_last_date' );
		}

		/**
		 * Get delivery last date GMT.
		 */
		public function get_delivery_last_date_gmt() {
			return $this->get_prop( 'dey_delivery_last_date_gmt' );
		}

		/**
		 * Get time zone.
		 */
		public function get_timezone() {
			return $this->get_prop( 'dey_timezone' );
		}

		/**
		 * Get delivery mode.
		 */
		public function get_delivery_mode() {
			return $this->get_prop( 'dey_delivery_mode' );
		}

		/**
		 * Get delivery time mode.
		 */
		public function get_delivery_time_mode() {
			return $this->get_prop( 'dey_delivery_time_mode' );
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
		 * Get delivery email reminder sent.
		 */
		public function get_delivery_email_reminder_sent() {
			return $this->get_prop( 'dey_delivery_email_reminder_sent' );
		}
	}

}
