<?php
/**
 * Fee Report Post Table
 *
 * @package Extra Fees for WooCommerce/Admin/WPListTable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'EFW_Fee_Report_Post_Table' ) ) {

	/**
	 * EFW_Fee_Report_Post_Table Class.
	 * */
	class EFW_Fee_Report_Post_Table extends WP_List_Table {

		/**
		 * Total Count of Table
		 *
		 * @var integer
		 * */
		private $total_items;

		/**
		 * Per page count
		 *
		 * @var integer
		 * */
		private $perpage;

		/**
		 * Offset
		 *
		 * @var integer
		 * */
		private $offset;

		/**
		 * Order BY
		 *
		 * @var string
		 * */
		private $orderby = 'ORDER BY ID DESC';

		/**
		 * Post type
		 *
		 * @var string
		 * */
		private $post_type = EFW_Register_Post_Type::FEES_POSTTYPE;

		/**
		 * Base URL
		 *
		 * @var string
		 * */
		private $base_url;

		/**
		 * Database
		 *
		 * @var string
		 * */
		private $database;

		/**
		 * Current URL
		 *
		 * @var string
		 * */
		private $current_url;

		/**
		 * Table Slug
		 *
		 * @var string
		 * */
		private $table_slug = 'efw';

		/**
		 * Prepare the table Data to display table based on pagination.
		 * */
		public function prepare_items() {
			$this->base_url = add_query_arg(
				array(
					'page'    => 'efw_settings',
					'tab'     => 'reports',
				),
				admin_url( 'admin.php' )
			);

			global $wpdb;
			$this->database = &$wpdb;

			add_filter( $this->table_slug . '_query_where', array( $this, 'custom_search' ), 10, 1 );

			$this->prepare_current_url();
			$this->get_perpage_count();
			$this->get_current_pagenum();
			$this->get_current_page_items();
			$this->prepare_pagination_args();
			$this->prepare_column_headers();
		}

		/**
		 * Get per page count
		 * */
		private function get_perpage_count() {

			$this->perpage = 20;
		}

		/**
		 * Prepare pagination
		 * */
		private function prepare_pagination_args() {

			$this->set_pagination_args(
				array(
					'total_items' => $this->total_items,
					'per_page'    => $this->perpage,
				)
			);
		}

		/**
		 * Get current page number
		 * */
		private function get_current_pagenum() {
			$this->offset = $this->perpage * ( $this->get_pagenum() - 1 );
		}

		/**
		 * Prepare header columns
		 * */
		private function prepare_column_headers() {
			$columns               = $this->get_columns();
			$hidden                = $this->get_hidden_columns();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
		}

		/**
		 * Initialize the columns
		 * */
		public function get_columns() {
			$columns = array(
				'efw_order_id'    => esc_html__('Order ID', 'extra-fees-for-woocommerce'),
				'efw_product_fee' => esc_html__('Product Fee', 'extra-fees-for-woocommerce'),
				'efw_gateway_fee' => esc_html__('Payment Gateway Fee', 'extra-fees-for-woocommerce'),
				'efw_order_fee'   => esc_html__('Order Fee', 'extra-fees-for-woocommerce'),
				'efw_shipping_fee'=> esc_html__('Shipping Fee', 'extra-fees-for-woocommerce'),
				'efw_total_fee'   => esc_html__('Total Fee', 'extra-fees-for-woocommerce'),
			);

			return $columns;
		}

		/**
		 * Initialize the hidden columns
		 * */
		public function get_hidden_columns() {
			return array();
		}

		/**
		 * Get current url
		 * */
		private function prepare_current_url() {
			$args['paged'] = $this->get_pagenum();
			$url           = add_query_arg( $args, $this->base_url );

			$this->current_url = $url;
		}

		/**
		 * Prepare each column data
		 *
		 * @param object $item WC Linked Coupon Object.
		 * @param string $column_name Column.
		 * */
		protected function column_default( $item, $column_name ) {

			$order_id = $item->get_order_id();

			switch ( $column_name ) {
				case 'efw_order_id':
					$order = wc_get_order( $order_id );
					if ( $order ) {
						echo wp_kses_post('<a href="' . esc_url( $order->get_edit_order_url() ) . '" target="_blank">#' . $order_id . '</a>');
					} else {
						echo esc_html( '#' . $order_id );
					}
					break;
				case 'efw_product_fee':
					$product_fee = $this->get_product_fee( $order_id );
					$product_fee = empty($product_fee) ? '-' : wc_price($product_fee);
					echo wp_kses_post($product_fee);
					break;
				case 'efw_gateway_fee':
					$gateway_fee = $this->get_gateway_fee( $order_id );
					$gateway_fee = empty($gateway_fee) ? '-' : wc_price($gateway_fee);
					echo wp_kses_post($gateway_fee);
					break;
				case 'efw_order_fee':
					$order_fee = $this->get_order_fee( $order_id );
					$order_fee = empty($order_fee) ? '-' : wc_price($order_fee);
					echo wp_kses_post($order_fee);
					break;
				case 'efw_shipping_fee':
					$shipping_fee = $this->get_shipping_fee( $order_id );
					$shipping_fee = empty($shipping_fee) ? '-' : wc_price($shipping_fee);
					echo wp_kses_post($shipping_fee);
					break;
				case 'efw_total_fee':
					$total = $this->get_product_fee($order_id) + $this->get_gateway_fee($order_id) + $this->get_order_fee($order_id) + $this->get_shipping_fee($order_id);
					echo wp_kses_post(wc_price($total));
					break;
			}
		}

		/**
		 * Get Product Fee
		 * */
		private function get_product_fee( $order_id ) {
			$prepare_query = $this->database->prepare( 
				'SELECT SUM(meta1.meta_value) FROM ' . $this->database->posts . ' as p 
				INNER JOIN ' . $this->database->postmeta . ' as meta ON p.ID=meta.post_id
				INNER JOIN ' . $this->database->postmeta . " as meta1 ON p.ID=meta1.post_id 
				where p.post_type='" . $this->post_type . "' and p.post_status IN('publish')
				AND meta.meta_key = 'efw_order_id' AND meta.meta_value = %d
				AND meta1.meta_key = 'efw_product_fee'
				AND meta1.meta_value != '0'
				GROUP BY meta.meta_value", $order_id
			);

			$product_fee = $this->database->get_col( $prepare_query );
			
			return efw_check_is_array($product_fee) ? array_sum($product_fee) : 0;
		}

		/**
		 * Get Order Fee
		 * */
		private function get_order_fee( $order_id ) {
			$prepare_query = $this->database->prepare( 
				'SELECT SUM(meta1.meta_value) FROM ' . $this->database->posts . ' as p 
				INNER JOIN ' . $this->database->postmeta . ' as meta ON p.ID=meta.post_id
				INNER JOIN ' . $this->database->postmeta . " as meta1 ON p.ID=meta1.post_id 
				where p.post_type='" . $this->post_type . "' and p.post_status IN('publish')
				AND meta.meta_key = 'efw_order_id' AND meta.meta_value = %d
				AND meta1.meta_key = 'efw_order_fee'
				AND meta1.meta_value != '0'
				GROUP BY meta.meta_value", $order_id
			);

			$order_fee = $this->database->get_col( $prepare_query );
			
			return efw_check_is_array($order_fee) ? array_sum($order_fee) : 0;
		}

		/**
		 * Get Gateway Fee
		 * */
		private function get_gateway_fee( $order_id ) {
			$prepare_query = $this->database->prepare( 
				'SELECT SUM(meta1.meta_value) FROM ' . $this->database->posts . ' as p 
				INNER JOIN ' . $this->database->postmeta . ' as meta ON p.ID=meta.post_id
				INNER JOIN ' . $this->database->postmeta . " as meta1 ON p.ID=meta1.post_id 
				where p.post_type='" . $this->post_type . "' and p.post_status IN('publish')
				AND meta.meta_key = 'efw_order_id' AND meta.meta_value = %d
				AND meta1.meta_key = 'efw_gateway_fee'
				AND meta1.meta_value != '0'
				GROUP BY meta.meta_value", $order_id
			);

			$gateway_fee = $this->database->get_col( $prepare_query );
			
			return efw_check_is_array($gateway_fee) ? array_sum($gateway_fee) : 0;
		}

		/**
		 * Get Shipping Fee
		 * */
		private function get_shipping_fee( $order_id ) {
			$prepare_query = $this->database->prepare( 
				'SELECT SUM(meta1.meta_value) FROM ' . $this->database->posts . ' as p 
				INNER JOIN ' . $this->database->postmeta . ' as meta ON p.ID=meta.post_id
				INNER JOIN ' . $this->database->postmeta . " as meta1 ON p.ID=meta1.post_id 
				where p.post_type='" . $this->post_type . "' and p.post_status IN('publish')
				AND meta.meta_key = 'efw_order_id' AND meta.meta_value = %d
				AND meta1.meta_key = 'efw_shipping_fee'
				AND meta1.meta_value != '0'
				GROUP BY meta.meta_value", $order_id
			);

			$shipping_fee = $this->database->get_col( $prepare_query );
			
			return efw_check_is_array($shipping_fee) ? array_sum($shipping_fee) : 0;
		}

		/**
		 * Initialize the columns
		 * */
		private function get_current_page_items() {
			$where = " where p.post_type='" . $this->post_type . "' and p.post_status IN('publish')" ;
			/**
			 * This hook is used to alter the where statement.
			 *
			 * @param string $where Where Statement.
			 * @since 6.9.0
			 */
			$where = apply_filters( $this->table_slug . '_query_where', $where );

			/**
			 * This hook is used to alter the query limit.
			 *
			 * @param int $this->perpage Perpage Count.
			 * @since 6.9.0
			 */
			$limit = apply_filters( $this->table_slug . '_query_limit', $this->perpage );

			/**
			 * This hook is used to alter the query offset.
			 *
			 * @param int $this->offset Query Offset.
			 * @since 6.9.0
			 */
			$offset = apply_filters( $this->table_slug . '_query_offset', $this->offset );

			/**
			 * This hook is used to alter the query orderby.
			 *
			 * @param int $this->orderby Query OrderBy.
			 * @since 6.9.0
			 */
			$orderby = apply_filters( $this->table_slug . '_query_orderby', $this->orderby );

			$total_count = $this->database->get_results( 
				'SELECT DISTINCT ID FROM ' . $this->database->posts . ' as p 
				INNER JOIN ' . $this->database->postmeta . ' as meta ON p.ID=meta.post_id
				INNER JOIN ' . $this->database->postmeta . " as meta1 ON p.ID=meta1.post_id 
				$where GROUP BY meta.meta_value $orderby" 
			);

			$this->total_items = count( $total_count );

			$prepare_query = $this->database->prepare( 
				'SELECT DISTINCT ID FROM ' . $this->database->posts . ' as p 
				INNER JOIN ' . $this->database->postmeta . ' as meta ON p.ID=meta.post_id
				INNER JOIN ' . $this->database->postmeta . " as meta1 ON p.ID=meta1.post_id 
				$where GROUP BY meta.meta_value $orderby LIMIT %d,%d", $offset, $limit 
			);

			$items = $this->database->get_results( $prepare_query, ARRAY_A );

			$this->prepare_item_object( $items );
		}

		/**
		 * Prepare item Object
		 *
		 * @param object $items Referral Object.
		 * */
		private function prepare_item_object( $items ) {
			$prepare_items = array();
			$fee_details = array();
			if ( efw_check_is_array( $items ) ) {
				foreach ( $items as $item ) {
					$prepare_items[] = efw_get_fees( $item['ID'] );
				}
			}

			$this->items = $prepare_items;
		}

		/**
		 * Search Functionality
		 * */
		public function custom_search( $where ) {
			$where .= " AND meta.meta_key = 'efw_order_id' AND meta.meta_value != '0'
			AND meta1.meta_key IN ('efw_product_fee','efw_gateway_fee','efw_shipping_fee', 'efw_order_fee')
			AND meta1.meta_value NOT IN('0','0','0','0')";

			if ( isset( $_REQUEST['s'] ) ) {
				$search_ids = array();
				$terms      = explode( ',', wc_clean( $_REQUEST['s'] ) );

				foreach ( $terms as $term ) {
					$term = $this->database->esc_like( $term );

					$search_ids = $this->database->get_col(
						$this->database->prepare(
							"SELECT DISTINCT ID FROM {$this->database->posts} as p "
									. "INNER JOIN {$this->database->postmeta} as pm ON p.ID = pm.post_id "
									. 'WHERE p.post_type=%s AND (('
									. "pm.meta_key IN ('efw_order_id') "
									. 'AND pm.meta_value LIKE %s))',
							$this->post_type,
							'%' . $term . '%'
						)
					);
				}

				$search_ids = array_filter( array_unique( array_map( 'absint', $search_ids ) ) );

				$search_ids = efw_check_is_array( $search_ids ) ? $search_ids : array( 0 );

				$where .= ' AND (p.ID IN (' . implode( ',', $search_ids ) . '))';
			}

			return $where;
		}
	}

}
