<?php

/**
 * Handles the order tip exports.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_CSV_Exporter', false ) ) {
	require_once WC_ABSPATH . 'includes/export/abstract-wc-csv-exporter.php' ;
}

if ( ! class_exists( 'DEY_Order_Tip_Export_CSV' ) ) {

	/**
	 * DEY_Order_Tip_Export_CSV.
	 */
	class DEY_Order_Tip_Export_CSV extends WC_CSV_Exporter {

		/**
		 * Type of export used in filter names.
		 *
		 * @var string
		 */
		protected $export_type = 'order_tip' ;

		/**
		 * Filename to export to.
		 *
		 * @var string
		 */
		protected $filename = 'order-tip.csv' ;

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
			return apply_filters( 'dey_order_tip_export_heading', $headings ) ;
		}

		/**
		 * Prepare data that will be exported.
		 * 
		 * @return void.
		 */
		public function prepare_data_to_export() {

			// Prepare column names.
			$this->column_names = $this->get_default_column_names() ;

			$order_tip_ids = $this->get_order_tip_ids() ;

			foreach ( $order_tip_ids as $order_tip_id ) {

				$order_tip = dey_get_order_tip( $order_tip_id ) ;
				if ( ! is_object( $order_tip ) ) {
					continue ;
				}

				$this->row_data[] = self::generate_row_data( $order_tip ) ;
			}
		}

		/**
		 * Get the order tip data.
		 * 
		 * @return array
		 */
		protected function generate_row_data( $order_delivery ) {

			$row = array(
				'order_id'     => '#' . $order_delivery->get_order_id(),
				'username'     => $order_delivery->get_user_name(),
				'user_email'   => $order_delivery->get_user_email(),
				'currency'     => $order_delivery->get_currency(),
				'amount'       => $order_delivery->get_amount(),
				'status'       => dey_display_post_status( $order_delivery->get_status(), false ),
				'created_date' => $order_delivery->get_formatted_created_date(),
					) ;
			/**
			 * This hook is used to alter the order tip row data.
			 * 
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_tip_export_row_data', $row ) ;
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
	}

}
