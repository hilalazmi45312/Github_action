<?php
/**
 * Handles the product pickup print.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Product_Local_Pickup_Print' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.5.0
	 */
	class DEY_Product_Local_Pickup_Print {

		/**
		 * From date.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		private $from_date;

		/**
		 * To date.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		private $to_date;

		/**
		 * Day filter.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		private $day_filter;

		/**
		 * Set the from date.
		 *
		 * @since 3.5.0
		 * @param string $date From date.
		 * @return void
		 */
		public function set_from_date( $date ) {
			$this->from_date = $date;
		}

		/**
		 * Set the to date.
		 *
		 * @since 3.5.0
		 * @param string $date To date.
		 * @return void
		 */
		public function set_to_date( $date ) {
			$this->to_date = $date;
		}

		/**
		 * Set the day filter.
		 *
		 * @since 3.5.0
		 * @param string $day_filter Day filter.
		 * @return void
		 */
		public function set_day_filter( $day_filter ) {
			$this->day_filter = $day_filter;
		}

		/**
		 * Get the from date.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_from_date() {
			return $this->from_date;
		}

		/**
		 * Get the to date.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_to_date() {
			return $this->to_date;
		}

		/**
		 * Get the day filter.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_day_filter() {
			return $this->day_filter;
		}

		/**
		 * Return default columns.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public function get_default_column_names() {
			$headings = array(
				'product_name'     => __( 'Product Name', 'delivery-slots-for-woocommerce' ),
				'product_quantity' => __( 'Product Quantity', 'delivery-slots-for-woocommerce' ),
				'order_id'         => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
				'username'         => __( 'Username', 'delivery-slots-for-woocommerce' ),
				'user_email'       => __( 'User Email', 'delivery-slots-for-woocommerce' ),
				'pickup_date'      => __( 'Pickup Date', 'delivery-slots-for-woocommerce' ),
				'time_slots'       => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
				'currency'         => __( 'Currency', 'delivery-slots-for-woocommerce' ),
				'pickup_fee'       => __( 'Pickup Fee', 'delivery-slots-for-woocommerce' ),
				'status'           => __( 'Status', 'delivery-slots-for-woocommerce' ),
				'created_date'     => __( 'Created Date', 'delivery-slots-for-woocommerce' ),
			);

			/**
			 * This hook is used to alter the product pickup print heading.
			 *
			 * @since 3.5.0
			 */
			return apply_filters( 'dey_product_pickup_print_heading', $headings );
		}

		/**
		 * Get the data to print.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public function get_data_to_print() {
			$row_data = array();

			foreach ( $this->get_product_pickup_ids() as $product_pickup_id ) {
				$product_pickup = dey_get_product_local_pickup( $product_pickup_id );
				if ( ! is_object( $product_pickup ) ) {
					continue;
				}

				$row_data[] = self::generate_row_data( $product_pickup );
			}

			return $row_data;
		}

		/**
		 * Get the product pickup data.
		 *
		 * @since 3.5.0
		 * @param object $product_pickup Product pickup object.
		 * @return array
		 */
		protected function generate_row_data( $product_pickup ) {
			$row = array(
				'product_name'     => dey_get_products_link( array( $product_pickup->get_product_id() ), false ),
				'product_quantity' => $product_pickup->get_order_product_quantity(),
				'order_id'         => '#' . $product_pickup->get_order_id(),
				'username'         => $product_pickup->get_user_name(),
				'user_email'       => $product_pickup->get_user_email(),
				'pickup_date'      => $product_pickup->get_formatted_pickup_date(),
				'time_slots'       => $product_pickup->get_formatted_time_slots(),
				'currency'         => $product_pickup->get_currency(),
				'pickup_fee'       => $product_pickup->get_pickup_charge(),
				'status'           => dey_display_post_status( $product_pickup->get_status(), false ),
				'created_date'     => $product_pickup->get_formatted_created_date(),
			);

			/**
			 * This hook is used to alter the product pickup row data.
			 *
			 * @since 3.5.0
			 */
			return apply_filters( 'dey_product_pickup_print_row_data', $row );
		}

		/**
		 * Get the product pickup IDs.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		protected function get_product_pickup_ids() {
			$args = array(
				'post_type'   => DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE,
				'post_status' => dey_get_product_local_pickup_statuses(),
				'fields'      => 'ids',
				'numberposts' => '-1',
			);

			$args['meta_query'] = dey_get_pickup_meta_query_args( $this->get_day_filter(), $this->get_from_date(), $this->get_to_date() );

			return get_posts( $args );
		}

		/**
		 * Print the data.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function print_data() {
			$colum_names = $this->get_default_column_names();
			$row_data    = $this->get_data_to_print();

			include_once DEY_ABSPATH . 'inc/admin/menu/views/html-print-page.php';
		}
	}

}
