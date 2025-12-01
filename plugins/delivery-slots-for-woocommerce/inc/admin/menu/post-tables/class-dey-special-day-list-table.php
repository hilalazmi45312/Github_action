<?php

/**
 * Special Day List Table.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php' ;
}

if ( ! class_exists( 'DEY_Special_Day_List_Table' ) ) {

	/**
	 * DEY_Special_Day_List_Table Class.
	 * */
	class DEY_Special_Day_List_Table extends DEY_Post_List_Table {

		/**
		 * Post Type.
		 * 
		 * @var String
		 */
		protected $post_type = DEY_Register_Post_Types::SPECIAL_DAYS_POSTTYPE ;

		/**
		 * Plugin Slug.
		 * 
		 * @var String
		 */
		protected $plugin_slug = 'dey' ;

		/**
		 * Class initialization.
		 */
		public function __construct() {

			parent::__construct() ;
		}

		/**
		 * Add meta boxes for this post type.
		 * 
		 * @return void
		 */
		public function add_meta_boxes() {
			// Special day data meta box.
			add_meta_box( 'dey-special-day-data', __( 'Settings', 'delivery-slots-for-woocommerce' ), array( $this, 'render_special_day_meta_box' ), $this->post_type, 'normal', 'high' ) ;
			/**
			 * This hook is used to add custom meta boxes for special day post type.
			 * 
			 * @since 1.0
			 */
			do_action( 'dey_add_meta_boxes_' . $this->post_type ) ;
		}

		/**
		 * Define the which columns to show on this screen.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			if ( ! dey_check_is_array( $columns ) ) {
				$columns = array() ;
			}

			unset( $columns['comments'], $columns['date'], $columns['title'] ) ;

			$columns['cb']                = '<input type="checkbox" />' ;
			$columns['title']             = __( 'Label', 'delivery-slots-for-woocommerce' ) ;
			$columns['special_date']      = __( 'Date', 'delivery-slots-for-woocommerce' ) ;
			$columns['schedule_type']     = __( 'Applicable Mode', 'delivery-slots-for-woocommerce' ) ;
			$columns['order_count']       = __( 'Maximum Orders', 'delivery-slots-for-woocommerce' ) ;
			$columns['usage_order_count'] = __( 'Placed Orders', 'delivery-slots-for-woocommerce' ) ;
			$columns['price']             = __( 'Delivery Fee', 'delivery-slots-for-woocommerce' ) ;
			$columns['status']            = __( 'Status', 'delivery-slots-for-woocommerce' ) ;
			$columns['created_date']      = __( 'Date', 'delivery-slots-for-woocommerce' ) ;
			$columns['actions']           = __( 'Actions', 'delivery-slots-for-woocommerce' );

			return $columns ;
		}

		/**
		 * Define primary column.
		 *
		 * @return array
		 */
		protected function get_primary_column() {
			return 'title' ;
		}

		/**
		 * Define which columns are sortable.
		 *
		 * @return array
		 */
		public function define_sortable_columns( $columns ) {
			return wp_parse_args(
				array(
					'order_count'       => array( 'order_count', true ),
					'usage_order_count' => array( 'usage_order_count', true ),
					'price'             => array( 'price', true ),
					'status'            => array( 'post_status', true ),
					'created_date'      => array( 'date', true ),
				),
				$columns
			);
		}

		/**
		 * Define bulk actions.
		 * 
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {
			unset( $actions['edit'], $actions['trash'] );

			$actions['delete'] = __( 'Delete', 'delivery-slots-for-woocommerce' );

			return $actions;
		}

		/**
		 * Disable the month dropdown.
		 * 
		 * @return bool
		 */
		public function disable_months_dropdown( $bool, $post_type ) {
			return true ;
		}

		/**
		 * Get row actions to show in the list table.
		 *
		 * @return array
		 */
		protected function get_row_actions( $actions, $post ) {
			// Unset the Quick edit, trash.
			unset( $actions['inline hide-if-no-js'], $actions['edit'], $actions['trash'] );

			return $actions;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function post_updated_messages( $messages ) {
			global $post ;

			$messages[ $this->post_type ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Special Day updated.', 'delivery-slots-for-woocommerce' ),
				4  => __( 'Special Day updated.', 'delivery-slots-for-woocommerce' ),
				6  => __( 'Special Day published.', 'delivery-slots-for-woocommerce' ),
				7  => __( 'Special Day saved.', 'delivery-slots-for-woocommerce' ),
				10 => __( 'Special Day draft updated.', 'delivery-slots-for-woocommerce' ),
			);

			return $messages ;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: special day count */
				'updated' => _n( '%s special day updated.', '%s special days updated.', $bulk_counts[ 'updated' ], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: special day count */
				'locked'  => _n( '%s special day not updated, somebody is editing it.', '%s special days not updated, somebody is editing them.', $bulk_counts[ 'locked' ], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: special day count */
				'deleted' => _n( '%s special day permanently deleted.', '%s special days permanently deleted.', $bulk_counts[ 'deleted' ], 'delivery-slots-for-woocommerce' ),
					) ;

			return $bulk_messages ;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = dey_get_special_day( $post_id ) ;
			}
		}

		/**
		 * Render the special date column.
		 *
		 * @return void
		 */
		public function render_special_date_column() {
			echo wp_kses_post( $this->object->get_formatted_date() ) ;
		}

		/**
		 * Render the special day mode column.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function render_schedule_type_column() {
			echo wp_kses_post($this->object->get_special_day_schedule_type_label()) ;
		}

		/**
		 * Render the usage order count column.
		 *
		 * @return void
		 */
		public function render_usage_order_count_column() {
			echo esc_html( intval( $this->object->get_order_usage_count() ) );
		}

		/**
		 * Render the order count column.
		 *
		 * @return void
		 */
		public function render_order_count_column() {
			echo esc_html( floatval( $this->object->get_order_count() ) ) ;
		}

		/**
		 * Render the price column.
		 *
		 * @return void
		 */
		public function render_price_column() {
			echo wp_kses_post( dey_price( $this->object->get_price() ) ) ;
		}

		/**
		 * Render the status column.
		 *
		 * @return void
		 */
		public function render_status_column() {
			echo wp_kses_post( $this->object->get_status() ) ;
		}

		/**
		 * Render the created date column.
		 *
		 * @return void
		 */
		public function render_created_date_column() {
			echo wp_kses_post( $this->object->get_formatted_created_date() ) ;
		}

		/**
		 * Render the actions column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_actions_column() {
			$actions = array();
			$link    = add_query_arg( array( 'post_type' => $this->post_type ), admin_url( 'post.php' ) );

			$actions['edit']      = dey_display_action( 'edit', $this->object->get_id(), $link, true );
			$actions['duplicate'] = dey_display_action( 'duplicate', $this->object->get_id(), $link );
			$actions['delete']    = dey_display_action( 'delete', $this->object->get_id(), $link );

			echo wp_kses_post( implode( ' ', $actions ) );
		}

		/**
		 * Duplicate the special day.
		 *
		 * @since 4.0.0
		 * @param int $post_id Special day ID.
		 * @return int|bool
		 */
		public function process_duplicate_post( $post_id ) {
			$special_day = dey_get_special_day( $post_id );

			return $special_day->exists() ? $special_day->duplicate() : false;
		}

		/**
		 * Render the special day settings meta box.
		 *
		 * @return void
		 */
		public function render_special_day_meta_box( $post ) {
			global $dey_special_day ;

			$special_day_id  = ( isset( $post->ID ) ) ? $post->ID : '' ;
			$dey_special_day = dey_get_special_day( $special_day_id ) ;

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/html-special-day-data.php' ;
		}

		/**
		 * Save the meta box data.
		 *
		 * @return void
		 */
		public function save_current_meta_boxes( $post_id, $post ) {
			try {
				$date        = isset( $_REQUEST[ 'dey_date' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'dey_date' ] ) ) : '' ;
				$price       = isset( $_REQUEST[ 'dey_price' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'dey_price' ] ) ) : '' ;
				$order_count = isset( $_REQUEST[ 'dey_order_count' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'dey_order_count' ] ) ) : '' ;
				$schedule_type = isset( $_REQUEST['dey_special_day_schedule_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_special_day_schedule_type'] ) ) : '';
				
				$meta_args = array(
					'dey_date'        => $date,
					'dey_price'       => wc_format_decimal( $price ),
					'dey_order_count' => $order_count,
					'dey_special_day_schedule_type' => $schedule_type,
						) ;

				// Update the special day data.
				dey_update_special_day( $post_id, $meta_args ) ;
			} catch ( Exception $ex ) {
				WC_Admin_Meta_Boxes::add_error( $ex->getMessage() ) ;
			}
		}

		/**
		 * Handle order by filters.
		 *
		 * @return array
		 */
		protected function orderby_filters( $query_vars ) {
			// return if the order by key is not exists.
			if ( ! isset( $query_vars[ 'orderby' ] ) ) {
				return $query_vars ;
			}

			switch ( strtolower( $query_vars[ 'orderby' ] ) ) {
				case 'order_count':
					$query_vars[ 'meta_key' ] = 'dey_order_count' ;
					$query_vars[ 'orderby' ]  = 'meta_value_num' ;
					break ;

				case 'usage_order_count':
					$query_vars[ 'meta_key' ] = 'dey_order_usage_count' ;
					$query_vars[ 'orderby' ]  = 'meta_value_num' ;
					break ;

				case 'price':
					$query_vars[ 'meta_key' ] = 'dey_price' ;
					$query_vars[ 'orderby' ]  = 'meta_value_num' ;
					break ;
			}


			return $query_vars ;
		}
	}

}
