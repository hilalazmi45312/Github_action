<?php

/**
 * Order Tip.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Order_Tip' ) ) {

	/**
	 * DEY_Order_Tip Class.
	 */
	class DEY_Order_Tip extends DEY_Post {

		/**
		 * Post Type.
		 * 
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::ORDER_TIP_POSTTYPE ;

		/**
		 * Post Status.
		 * 
		 * @var string
		 */
		protected $post_status = 'dey_pending_payment' ;

		/**
		 * Order ID.
		 * 
		 * @var int
		 */
		protected $order_id ;

		/**
		 * Created Date.
		 * 
		 * @var string
		 */
		protected $created_date ;

		/**
		 * Order.
		 * 
		 * @var object
		 */
		protected $order ;

		/**
		 * Meta data keys.
		 */
		protected $meta_data_keys = array(
			'dey_amount'     => '',
			'dey_mode'       => '',
			'dey_type'       => '',
			'dey_currency'   => '',
			'dey_user_id'    => '',
			'dey_user_name'  => '',
			'dey_user_email' => '',
				) ;

		/**
		 * Prepare extra post data.
		 */
		protected function load_extra_postdata() {
			$this->order_id     = $this->post->post_parent ;
			$this->created_date = $this->post->post_date_gmt ;
		}

		/**
		 * Get the order.
		 * 
		 * @return object/bool
		 */
		public function get_order() {

			if ( isset( $this->order ) ) {
				return $this->order ;
			}

			$this->order = wc_get_order( $this->get_order_id() ) ;

			return $this->order ;
		}

		/**
		 * Get the formatted created date time.
		 * 
		 * @retrun string
		 */
		public function get_formatted_created_date() {
			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() ) ;
		}

		/**
		 * Setters and Getters.
		 * */

		/**
		 * Set order ID
		 */
		public function set_order_id( $value ) {
			$this->order_id = $value ;
		}

		/**
		 * Set created date.
		 */
		public function set_created_date( $value ) {
			$this->created_date = $value ;
		}

		/**
		 * Set amount.
		 * */
		public function set_amount( $value ) {
			$this->set_prop( 'dey_amount', $value ) ;
		}

		/**
		 * Set type.
		 * */
		public function set_type( $value ) {
			$this->set_prop( 'dey_type', $value ) ;
		}

		/**
		 * Set mode.
		 * */
		public function set_mode( $value ) {
			$this->set_prop( 'dey_mode', $value ) ;
		}

		/**
		 * Set currency.
		 */
		public function set_currency( $value ) {
			$this->set_prop( 'dey_currency', $value ) ;
		}

		/**
		 * Set user ID.
		 */
		public function set_user_id( $value ) {
			$this->set_prop( 'dey_user_id', $value ) ;
		}

		/**
		 * Set user name.
		 */
		public function set_user_name( $value ) {
			$this->set_prop( 'dey_user_name', $value ) ;
		}

		/**
		 * Set user email.
		 */
		public function set_user_email( $value ) {
			$this->set_prop( 'dey_user_email', $value ) ;
		}

		/**
		 * Get order ID.
		 */
		public function get_order_id() {
			return $this->order_id ;
		}

		/**
		 * Get created date.
		 */
		public function get_created_date() {
			return $this->created_date ;
		}

		/**
		 * Get amount.
		 */
		public function get_amount() {
			return $this->get_prop( 'dey_amount' ) ;
		}

		/**
		 * Get type.
		 */
		public function get_type() {
			return $this->get_prop( 'dey_type' ) ;
		}

		/**
		 * Get mode.
		 */
		public function get_mode() {
			return $this->get_prop( 'dey_mode' ) ;
		}

		/**
		 * Get currency.
		 */
		public function get_currency() {
			return $this->get_prop( 'dey_currency' ) ;
		}

		/**
		 * Get user ID.
		 */
		public function get_user_id() {
			return $this->get_prop( 'dey_user_id' ) ;
		}

		/**
		 * Get user name.
		 */
		public function get_user_name() {
			return $this->get_prop( 'dey_user_name' ) ;
		}

		/**
		 * Get user email.
		 */
		public function get_user_email() {
			return $this->get_prop( 'dey_user_email' ) ;
		}
	}

}
