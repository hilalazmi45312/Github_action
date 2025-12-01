<?php

/**
 * Holiday.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Holiday' ) ) {

	/**
	 * DEY_Holiday Class.
	 */
	class DEY_Holiday extends DEY_Post {

		/**
		 * Post Type.
		 * 
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::HOLIDAY_POSTTYPE ;

		/**
		 * Post Status.
		 * 
		 * @var string
		 */
		protected $post_status = 'publish' ;

		/**
		 * Name.
		 * 
		 * @var string
		 */
		protected $name ;

		/**
		 * Created Date.
		 * 
		 * @var string
		 */
		protected $created_date ;

		/**
		 * Meta data keys.
		 */
		protected $meta_data_keys = array(
			'dey_from_date' => '',
			'dey_to_date'   => '',
			'dey_recurring' => '',
			'dey_holiday_schedule_type' => 1,
		);

		/**
		 * Duplicate meta data keys.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		protected $duplicate_meta_keys = array(
			'dey_from_date'             => '',
			'dey_to_date'               => '',
			'dey_recurring'             => '',
			'dey_holiday_schedule_type' => 1,
		);

		/**
		 * Prepare extra post data.
		 */
		protected function load_extra_postdata() {
			$this->name         = $this->post->post_title ;
			$this->created_date = $this->post->post_date_gmt ;
		}

		/**
		 * Get the formatted created datetime.
		 */
		public function get_formatted_created_date() {

			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() ) ;
		}

		/**
		 * Get the holiday mode label.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public function get_holiday_schedule_type_label() {
			switch ( $this->get_holiday_schedule_type() ) {
				case '2':
					return __( 'Delivery', 'delivery-slots-for-woocommerce' );

				case '3':
					return __( 'Pick UP', 'delivery-slots-for-woocommerce' );

				default:
					return __( 'Both', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Setters and Getters.
		 * */

		/**
		 * Set name
		 */
		public function set_name( $value ) {
			$this->name = $value ;
		}

		/**
		 * Set created date.
		 */
		public function set_created_date( $value ) {
			$this->created_date = $value ;
		}

		/**
		 * Set from date.
		 * */
		public function set_from_date( $value ) {
			$this->set_prop( 'dey_from_date' , $value ) ;
		}

		/**
		 * Set to date.
		 * */
		public function set_to_date( $value ) {
			$this->set_prop( 'dey_to_date' , $value ) ;
		}

		/**
		 * Set recurring.
		 */
		public function set_recurring( $value ) {
			$this->set_prop( 'dey_recurring' , $value ) ;
		}

		/**
		 * Set holiday schedule type.
		 *
		 * @since 3.0.0
		 * @param string $value
		 */
		public function set_holiday_schedule_type( $value ) {
			$this->set_prop('dey_holiday_schedule_type', $value );
		}

		/**
		 * Get name.
		 */
		public function get_name() {
			return $this->name ;
		}

		/**
		 * Get created date.
		 */
		public function get_created_date() {
			return $this->created_date ;
		}

		/**
		 * Get from date.
		 */
		public function get_from_date() {
			return $this->get_prop( 'dey_from_date' ) ;
		}

		/**
		 * Get to date.
		 */
		public function get_to_date() {
			return $this->get_prop( 'dey_to_date' ) ;
		}

		/**
		 * Get recurring.
		 */
		public function get_recurring() {
			return $this->get_prop( 'dey_recurring' ) ;
		}

		/**
		 * Get holiday schedule type.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public function get_holiday_schedule_type() {
			return $this->get_prop('dey_holiday_schedule_type');
		}
	}

}
