<?php

/**
 * Time Slot.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Time_Slot' ) ) {

	/**
	 * DEY_Time_Slot Class.
	 */
	class DEY_Time_Slot extends DEY_Post {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::TIME_SLOTS_POSTTYPE;

		/**
		 * Post Status.
		 *
		 * @var string
		 */
		protected $post_status = 'publish';

		/**
		 * Name.
		 *
		 * @var string
		 */
		protected $name;

		/**
		 * Created Date.
		 *
		 * @var string
		 */
		protected $created_date;

		/**
		 * Meta data keys.
		 */
		protected $meta_data_keys = array(
			'dey_time_slot_schedule_type'        => 1,
			'dey_enable_week_days'               => '',
			'dey_week_days'                      => array(),
			'dey_cutoff_time'                    => '',
			'dey_time_slot_from'                 => '',
			'dey_time_slot_to'                   => '',
			'dey_order_count'                    => '',
			'dey_order_usage_count'              => array(),
			'dey_order_local_pickup_usage_count' => array(),
			'dey_price'                          => '',
		);

		/**
		 * Duplicate meta data keys.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		protected $duplicate_meta_keys = array(
			'dey_time_slot_schedule_type' => '',
			'dey_enable_week_days'        => '',
			'dey_week_days'               => array(),
			'dey_cutoff_time'             => '',
			'dey_time_slot_from'          => '',
			'dey_time_slot_to'            => '',
			'dey_order_count'             => '',
			'dey_price'                   => '',
		);

		/**
		 * Prepare extra post data.
		 */
		protected function load_extra_postdata() {
			$this->name         = $this->post->post_title;
			$this->created_date = $this->post->post_date_gmt;
		}

		/**
		 * Get the time slot label.
		 *
		 * @return string
		 */
		public function get_time_slot_label() {
			$price      = $this->get_price();
			$time_slots = dey_format_order_delivery_time_slots( $this->get_time_slot_from(), $this->get_time_slot_to() );

			if ( 'yes' === get_option( 'dey_order_delivery_calculate_tax' ) ) {
				$price = dey_get_cart_price_to_display( $price );
			}

			if ( empty( $price ) && 'yes' === get_option( 'dey_order_delivery_time_slot_hide_zero_price' ) ) {
				$label = $time_slots;
			} else {
				$label = $time_slots . '(' . dey_price( $price ) . ')';
			}

			/**
			 * This hook is used to alter the order delivery date time slot label.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_delivery_date_time_slot_label', $label, $this );
		}

		/**
		 * Get the time slot mode label.
		 *
		 * @since 2.5
		 *
		 * @return string
		 */
		public function get_time_slot_schedule_type_label() {
			switch ( $this->get_time_slot_schedule_type() ) {
				case '2':
					return __( 'Delivery', 'delivery-slots-for-woocommerce' );

				case '3':
					return __( 'Pick UP', 'delivery-slots-for-woocommerce' );

				default:
					return __( 'Both', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Get the formatted created datetime.
		 */
		public function get_formatted_created_date() {
			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() );
		}

		/**
		 * Display the time slots.
		 *
		 * @return string
		 */
		public function display_time_slots() {
			return dey_format_order_delivery_time_slots( $this->get_time_slot_from(), $this->get_time_slot_to() );
		}

		/**
		 * Order usage count exists for the date.
		 *
		 * @return string
		 */
		public function order_usage_count_exists( $date, $schedule_type ) {
			$order_count = $this->formatted_order_count( $schedule_type );
			if ( ! $order_count ) {
				return true;
			}

			$usage_count = 0;
			if ( isset( $schedule_type ) ) {
				if ( 'delivery' === $schedule_type ) {
					$usage_count_array = array_filter( (array) $this->get_order_delivery_usage_count() );
				} else {
					$usage_count_array = array_filter( (array) $this->get_order_local_pickup_usage_count() );
				}

				$usage_count = isset( $usage_count_array[ $date ] ) ? intval( $usage_count_array[ $date ] ) : 0;
			}

			if ( intval( $order_count ) <= $usage_count ) {
				return false;
			}

			return true;
		}

		/**
		 * Week day exists for the date.
		 *
		 * @return string
		 */
		public function week_day_exists( $week_day ) {
			if ( 'yes' !== $this->get_enable_week_days() ) {
				return true;
			}

			return in_array( $week_day, $this->get_week_days() );
		}

		/**
		 * Get the formatted order count.
		 *
		 * @return string
		 */
		public function formatted_order_count( $schedule_type ) {
			if ( ! $this->get_order_count() && $schedule_type ) {
				if ( 'delivery' === $schedule_type ) {
					$formatted_order_count = get_option( 'dey_order_delivery_time_slot_max' );
				} else {
					$formatted_order_count = get_option( 'dey_local_pickup_time_slot_max' );
				}
			} else {
				$formatted_order_count = $this->get_order_count();
			}

			return $formatted_order_count;
		}

		/**
		 * Is valid?.
		 *
		 * @return string
		 */
		public function is_valid( $date, $schedule_type ) {
			if ( ! $this->exists() ) {
				return false;
			}

			$date_object = DEY_Date_Time::get_date_time_object( DEY_Date_Time::get_date_time_object( $date )->format('Y-m-d') . ' ' . $this->get_time_slot_from() );

			// Return if the selected date is crossed the cutoff time.
			if ( $this->is_crossed_cutoff_time( $date_object ) ) {
				return false;
			}

			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			// Validate if the time slot crossed the current time.
			if ( $current_date_object >= $date_object ) {
				return false;
			}

			if ( ! $this->week_day_exists( $date_object->format( 'w' ) + 1 ) ) {
				return false;
			}

			if ( ! $this->order_usage_count_exists( $date_object->format( 'Y-m-d' ), $schedule_type ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Is crossed cutoff time?
		 *
		 * @since 3.8.0
		 * @param object $date_object Selected date object.
		 * @return bool
		 */
		public function is_crossed_cutoff_time( $date_object ) {
			if ( empty( $this->get_cutoff_time() ) ) {
				return false;
			}

			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			// Return if the selected date is not the current date.
			if ( $current_date_object->format( 'Y-m-d' ) !== $date_object->format( 'Y-m-d' ) ) {
				return false;
			}

			$cutoff_date_object = DEY_Date_Time::get_date_time_object( $current_date_object->format( 'Y-m-d' ) . ' ' . $this->get_cutoff_time() );

			// Return if the selected date is crossed the cutoff time.
			return $current_date_object >= $cutoff_date_object;
		}

		/**
		 * Setters and Getters.
		 * */

		/**
		 * Set name
		 */
		public function set_name( $value ) {
			$this->name = $value;
		}

		/**
		 * Set created date.
		 */
		public function set_created_date( $value ) {
			$this->created_date = $value;
		}

		/**
		 * Set enable week days.
		 * */
		public function set_enable_week_days( $value ) {
			$this->set_prop( 'dey_enable_week_days', $value );
		}

		/**
		 * Set week days.
		 * */
		public function set_week_days( $value ) {
			$this->set_prop( 'dey_week_days', $value );
		}

		/**
		 * Set time slot cutoff time.
		 *
		 * @since 3.8.0
		 * @param string $cutoff_time Cutoff time.
		 */
		public function set_cutoff_time( $cutoff_time ) {
			$this->set_prop( 'dey_cutoff_time', $cutoff_time );
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
		 * Set order count.
		 */
		public function set_order_count( $value ) {
			$this->set_prop( 'dey_order_count', $value );
		}

		/**
		 * Set order delivery usage count.
		 */
		public function set_order_delivery_usage_count( $value ) {
			$this->set_prop( 'dey_order_usage_count', $value );
		}

		/**
		 * Set order local pickup usage count.
		 *
		 * @since 3.1.0
		 * @param array $value
		 * @return void
		 */
		public function set_order_local_pickup_usage_count( $value ) {
			$this->set_prop( 'dey_order_local_pickup_usage_count', $value );
		}

		/**
		 * Set price.
		 */
		public function set_price( $value ) {
			$this->set_prop( 'dey_price', $value );
		}

		/**
		 * Set applicable for time slot
		 *
		 * @since 2.5
		 */
		public function set_time_slot_schedule_type( $value ) {
			$this->set_prop( 'dey_time_slot_schedule_type', $value );
		}

		/**
		 * Get name.
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * Get created date.
		 */
		public function get_created_date() {
			return $this->created_date;
		}

		/**
		 * Get enable week days.
		 */
		public function get_enable_week_days() {
			return $this->get_prop( 'dey_enable_week_days' );
		}

		/**
		 * Get week days.
		 */
		public function get_week_days() {
			return $this->get_prop( 'dey_week_days' );
		}

		/**
		 * Get time slot cutoff time.
		 *
		 * @since 3.8.0
		 * @return string
		 */
		public function get_cutoff_time() {
			return $this->get_prop( 'dey_cutoff_time' );
		}

		/**
		 * Get delivery date.
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
		 * Get order count.
		 */
		public function get_order_count() {
			return $this->get_prop( 'dey_order_count' );
		}

		/**
		 * Get order delivery usage count.
		 */
		public function get_order_delivery_usage_count() {
			return $this->get_prop( 'dey_order_usage_count' );
		}

		/**
		 * Get order local pickup usage count.
		 *
		 * @since 3.1.0
		 * @return array
		 */
		public function get_order_local_pickup_usage_count() {
			return $this->get_prop( 'dey_order_local_pickup_usage_count' );
		}

		/**
		 * Get price.
		 */
		public function get_price() {
			return $this->get_prop( 'dey_price' );
		}

		/**
		 * Get applicable for time slot
		 *
		 * @since 2.5
		 */
		public function get_time_slot_schedule_type() {
			return $this->get_prop( 'dey_time_slot_schedule_type' );
		}
	}

}
