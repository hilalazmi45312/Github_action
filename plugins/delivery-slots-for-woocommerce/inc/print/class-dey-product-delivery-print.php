<?php

/**
 * Handles the product delivery print.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Product_Delivery_Print' ) ) {

	/**
	 * Class.
	 */
	class DEY_Product_Delivery_Print {

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
				'product_name'     => __( 'Product Name', 'delivery-slots-for-woocommerce' ),
				'product_quantity' => __( 'Product Quantity', 'delivery-slots-for-woocommerce' ),
				'order_id'         => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
				'username'         => __( 'Username', 'delivery-slots-for-woocommerce' ),
				'user_email'       => __( 'User Email', 'delivery-slots-for-woocommerce' ),
				'delivery_date'    => __( 'Delivery Date', 'delivery-slots-for-woocommerce' ),
				'mode'             => __( 'Mode', 'delivery-slots-for-woocommerce' ),
				'time_slots'       => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
				'currency'         => __( 'Currency', 'delivery-slots-for-woocommerce' ),
				'delivery_fee'     => __( 'Delivery Fee', 'delivery-slots-for-woocommerce' ),
				'status'           => __( 'Status', 'delivery-slots-for-woocommerce' ),
				'created_date'     => __( 'Created Date', 'delivery-slots-for-woocommerce' ),
					) ;

			/**
			 * This hook is used to alter the product delivery heading.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_product_delivery_export_heading', $headings ) ;
		}

		/**
		 * Get the data to print.
		 * 
		 * @return array.
		 */
		public function get_data_to_print() {
			$row_data             = array() ;
			$product_delivery_ids = $this->get_product_delivery_ids() ;

			foreach ( $product_delivery_ids as $product_delivery_id ) {

				$product_delivery = dey_get_product_delivery( $product_delivery_id ) ;
				if ( ! is_object( $product_delivery ) ) {
					continue ;
				}

				$row_data[] = self::generate_row_data( $product_delivery ) ;
			}

			return $row_data ;
		}

		/**
		 * Get the product delivery data.
		 * 
		 * @return array
		 */
		protected function generate_row_data( $product_delivery ) {

			$row = array(
				'product_name'     => dey_get_products_link( array( $product_delivery->get_product_id() ), false ),
				'product_quantity' => $product_delivery->get_order_product_quantity(),
				'order_id'         => '#' . $product_delivery->get_order_id(),
				'username'         => $product_delivery->get_user_name(),
				'user_email'       => $product_delivery->get_user_email(),
				'delivery_date'    => $product_delivery->get_formatted_delivery_date(),
				'mode'             => dey_display_delivery_mode( $product_delivery->get_delivery_mode() ),
				'time_slots'       => $product_delivery->get_formatted_time_slots(),
				'currency'         => $product_delivery->get_currency(),
				'delivery_fee'     => $product_delivery->get_delivery_charge(),
				'status'           => dey_display_post_status( $product_delivery->get_status(), false ),
				'created_date'     => $product_delivery->get_formatted_created_date(),
					) ;

			/**
			 * This hook is used to alter the product delivery row data.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_product_delivery_export_row_data', $row ) ;
		}

		/**
		 * Get the product delivery IDs.
		 * 
		 * @return array
		 */
		protected function get_product_delivery_ids() {
			$args = array(
				'post_type'   => DEY_Register_Post_Types::PRODUCT_DELIVERY_POSTTYPE,
				'post_status' => dey_get_product_delivery_statuses(),
				'fields'      => 'ids',
				'numberposts' => '-1',
					) ;

			$args[ 'meta_query' ] = dey_get_delivery_meta_query_args( $this->get_day_filter(), $this->get_from_date(), $this->get_to_date() ) ;

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
