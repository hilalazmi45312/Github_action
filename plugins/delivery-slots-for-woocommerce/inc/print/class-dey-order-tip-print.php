<?php

/**
 * Handles the order tip print.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Order_Tip_Print' ) ) {

	/**
	 * Class.
	 */
	class DEY_Order_Tip_Print {

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
				'order_id'     => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
				'username'     => __( 'Username', 'delivery-slots-for-woocommerce' ),
				'user_email'   => __( 'User Email', 'delivery-slots-for-woocommerce' ),
				'currency'     => __( 'Currency', 'delivery-slots-for-woocommerce' ),
				'amount'       => __( 'Amount', 'delivery-slots-for-woocommerce' ),
				'status'       => __( 'Status', 'delivery-slots-for-woocommerce' ),
				'created_date' => __( 'Created Date', 'delivery-slots-for-woocommerce' ),
					) ;
			/**
			 * This hook is used to alter the order tip heading.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_tip_print_heading', $headings ) ;
		}

		/**
		 * Get the data to print.
		 * 
		 * @return array.
		 */
		public function get_data_to_print() {
			$row_data      = array() ;
			$order_tip_ids = $this->get_order_tip_ids() ;

			foreach ( $order_tip_ids as $order_tip_id ) {

				$order_tip = dey_get_order_tip( $order_tip_id ) ;
				if ( ! is_object( $order_tip ) ) {
					continue ;
				}

				$row_data[] = self::generate_row_data( $order_tip ) ;
			}

			return $row_data ;
		}

		/**
		 * Get the order tip data.
		 * 
		 * @return array
		 */
		protected function generate_row_data( $order_tip ) {

			$row = array(
				'order_id'     => '#' . $order_tip->get_order_id(),
				'username'     => $order_tip->get_user_name(),
				'user_email'   => $order_tip->get_user_email(),
				'currency'     => $order_tip->get_currency(),
				'amount'       => $order_tip->get_amount(),
				'status'       => dey_display_post_status( $order_tip->get_status(), false ),
				'created_date' => $order_tip->get_formatted_created_date(),
					) ;
			/**
			 * This hook is used to alter the order tip row data.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_tip_print_row_data', $row ) ;
		}

		/**
		 * Get the order tip IDs.
		 * 
		 * @return array
		 */
		protected function get_order_tip_ids() {
			$args = array(
				'post_type'   => DEY_Register_Post_Types::ORDER_TIP_POSTTYPE,
				'post_status' => dey_get_order_tip_statuses(),
				'fields'      => 'ids',
				'numberposts' => '-1',
					) ;

			$args[ 'date_query' ] = dey_get_order_tip_date_query_args( $this->get_day_filter(), $this->get_from_date(), $this->get_to_date() ) ;

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
