<?php

/**
 * Handles the order local pickup print.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Order_Local_Pickup_Print' ) ) {

	/**
	 * Class.
	 */
	class DEY_Order_Local_Pickup_Print {

		/**
		 * From date.
		 * 
		 * @var string 
		 */
		private $from_date ;

		/**
		 * To date.
		 * 
		 * @var string 
		 */
		private $to_date ;

		/**
		 * Day filter.
		 * 
		 * @var string 
		 */
		private $day_filter ;

		/**
		 * Set the from date.
		 */
		public function set_from_date( $date ) {
			$this->from_date = $date ;
		}

		/**
		 * Set the to date.
		 */
		public function set_to_date( $date ) {
			$this->to_date = $date ;
		}

		/**
		 * Set the day filter.
		 */
		public function set_day_filter( $day_filter ) {
			$this->day_filter = $day_filter ;
		}

		/**
		 * Get the from date.
		 * 
		 * @return string
		 */
		public function get_from_date() {
			return $this->from_date ;
		}

		/**
		 * Get the to date.
		 * 
		 * @return string
		 */
		public function get_to_date() {
			return $this->to_date ;
		}

		/**
		 * Get the day filter.
		 * 
		 * @return string
		 */
		public function get_day_filter() {
			return $this->day_filter ;
		}

		/**
		 * Return default columns.
		 * 
		 * @return array
		 */
		public function get_default_column_names() {
			$headings = array(
				'order_id'               => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
				'username'               => __( 'Username', 'delivery-slots-for-woocommerce' ),
				'user_email'             => __( 'User Email', 'delivery-slots-for-woocommerce' ),
				'product_name'           => __( 'Products', 'delivery-slots-for-woocommerce' ),
				'pickup_location_name'   => __( 'Pickup Location Name', 'delivery-slots-for-woocommerce' ),
				'pickup_location_adress' => __( 'Pickup Location Address', 'delivery-slots-for-woocommerce' ),
				'pickup_date'            => __( 'Pickup Date', 'delivery-slots-for-woocommerce' ),
				'time_slots'             => __( 'Pickup Time Slots', 'delivery-slots-for-woocommerce' ),
				'currency'               => __( 'Currency', 'delivery-slots-for-woocommerce' ),
				'pickup_fee'             => __( 'Pickup Fee', 'delivery-slots-for-woocommerce' ),
				'status'                 => __( 'Status', 'delivery-slots-for-woocommerce' ),
				'created_date'           => __( 'Created Date', 'delivery-slots-for-woocommerce' ),
					) ;
			/**
			 * This hook is used to alter the order local pickup heading.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_local_pickup_print_heading', $headings ) ;
		}

		/**
		 * Get the data to print.
		 * 
		 * @return array.
		 */
		public function get_data_to_print() {
			$row_data               = array() ;
			$order_local_pickup_ids = $this->get_order_local_pickup_ids() ;

			foreach ( $order_local_pickup_ids as $order_local_pickup_id ) {

				$order_local_pickup = dey_get_order_local_pickup( $order_local_pickup_id ) ;
				if ( ! is_object( $order_local_pickup ) ) {
					continue ;
				}

				$row_data[] = self::generate_row_data( $order_local_pickup ) ;
			}

			return $row_data ;
		}

		/**
		 * Get the order local pickup data.
		 * 
		 * @return array
		 */
		protected function generate_row_data( $order_local_pickup ) {
			$row = array(
				'order_id'                => '#' . $order_local_pickup->get_order_id(),
				'username'                => $order_local_pickup->get_user_name(),
				'user_email'              => $order_local_pickup->get_user_email(),
				'product_name'            => dey_get_order_products_link( $order_local_pickup->get_product_ids(), false ),
				'pickup_location_name'    => $order_local_pickup->get_pickup_location()->get_name(),
				'pickup_location_address' => $order_local_pickup->get_formatted_address(),
				'pickup_date'             => $order_local_pickup->get_formatted_pickup_date(),
				'time_slots'              => $order_local_pickup->get_formatted_time_slots(),
				'currency'                => $order_local_pickup->get_currency(),
				'pickup_fee'              => $order_local_pickup->get_pickup_charge(),
				'status'                  => dey_display_post_status( $order_local_pickup->get_status(), false ),
				'created_date'            => $order_local_pickup->get_formatted_created_date(),
					) ;
			/**
			 * This hook is used to alter the order local pickup row data.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_local_pickup_print_row_data', $row ) ;
		}

		/**
		 * Get the order local pickup IDs.
		 * 
		 * @return array
		 */
		protected function get_order_local_pickup_ids() {
			$args = array(
				'post_type'   => DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE,
				'post_status' => dey_get_order_local_pickup_statuses(),
				'fields'      => 'ids',
				'numberposts' => '-1',
					) ;

			$args[ 'meta_query' ] = dey_get_pickup_meta_query_args( $this->get_day_filter(), $this->get_from_date(), $this->get_to_date() ) ;

			return get_posts( $args ) ;
		}

		/**
		 * Do the print.
		 * */
		public function print_data() {
			$colum_names = $this->get_default_column_names() ;
			$row_data    = $this->get_data_to_print() ;

			include_once DEY_ABSPATH . 'inc/admin/menu/views/html-print-page.php' ;
		}
	}

}
