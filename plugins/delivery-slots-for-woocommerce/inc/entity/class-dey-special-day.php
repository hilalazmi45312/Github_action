<?php
/**
 * Special day.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Special_Day' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 */
	class DEY_Special_Day extends DEY_Post {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::SPECIAL_DAYS_POSTTYPE;

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
			'dey_date'                      => '',
			'dey_order_count'               => '',
			'dey_order_usage_count'         => '',
			'dey_price'                     => '',
			'dey_special_day_schedule_type' => 1,
		);

		/**
		 * Duplicate meta data keys.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		protected $duplicate_meta_keys = array(
			'dey_date'                      => '',
			'dey_order_count'               => '',
			'dey_price'                     => '',
			'dey_special_day_schedule_type' => 1,
		);

		/**
		 * Prepare extra post data.
		 */
		protected function load_extra_postdata() {
			$this->name         = $this->post->post_title;
			$this->created_date = $this->post->post_date_gmt;
		}

		/**
		 * Get the formatted created datetime.
		 */
		public function get_formatted_created_date() {
			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() );
		}

		/**
		 * Get the formatted date.
		 */
		public function get_formatted_date() {
			return DEY_Date_Time::get_wp_format_datetime( $this->get_date(), 'date' );
		}

		/**
		 * Get the special day mode label.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public function get_special_day_schedule_type_label() {
			switch ( $this->get_special_day_schedule_type() ) {
				case '2':
					return __( 'Delivery', 'delivery-slots-for-woocommerce' );

				case '3':
					return __( 'Pick UP', 'delivery-slots-for-woocommerce' );

				default:
					return __( 'Both', 'delivery-slots-for-woocommerce' );
			}
		}

		/****************************
		 * Setters and Getters.
		 ****************************/

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
		 * Set date.
		 * */
		public function set_date( $value ) {
			$this->set_prop( 'dey_date', $value );
		}

		/**
		 * Set order count.
		 */
		public function set_order_count( $value ) {
			$this->set_prop( 'dey_order_count', $value );
		}

		/**
		 * Set order usage count.
		 *
		 * @since 1.0.0
		 * @param int $value Usage count.
		 */
		public function set_order_usage_count( $value ) {
			$this->set_prop( 'dey_order_usage_count', $value );
		}

		/**
		 * Set price.
		 */
		public function set_price( $value ) {
			$this->set_prop( 'dey_price', $value );
		}

		/**
		 * Set schedule type for special day.
		 *
		 * @param string $value
		 * @since 3.0.0
		 */
		public function set_special_day_schedule_type( $value ) {
			$this->set_prop( 'dey_special_day_schedule_type', $value );
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
		 * Get date.
		 */
		public function get_date() {
			return $this->get_prop( 'dey_date' );
		}

		/**
		 * Get order count.
		 */
		public function get_order_count() {
			return $this->get_prop( 'dey_order_count' );
		}

		/**
		 * Get order usage count.
		 *
		 * @since 1.0.0
		 * @return int
		 */
		public function get_order_usage_count() {
			return $this->get_prop( 'dey_order_usage_count' );
		}

		/**
		 * Get price.
		 */
		public function get_price() {
			return $this->get_prop( 'dey_price' );
		}

		/**
		 * Get schedule type for special day .
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public function get_special_day_schedule_type() {
			return $this->get_prop( 'dey_special_day_schedule_type' );
		}
	}

}
