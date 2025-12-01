<?php

/**
 * Holiday List Table.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php' ;
}

if ( ! class_exists( 'DEY_Holiday_List_Table' ) ) {

	/**
	 * DEY_Holiday_List_Table Class.
	 * */
	class DEY_Holiday_List_Table extends DEY_Post_List_Table {

		/**
		 * Post Type.
		 * 
		 * @var String
		 */
		protected $post_type = DEY_Register_Post_Types::HOLIDAY_POSTTYPE ;

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
		 * Initialize the extra hooks.
		 *
		 * @since 4.0.0
		 */
		protected function init_extra_hooks() {
			// Duplicate the holiday.
			add_action( 'post_action_duplicate', array( $this, 'duplicate_holiday' ) );
		}

		/**
		 * Add meta boxes for this post type.
		 * 
		 * @return void
		 */
		public function add_meta_boxes() {
			// Holiday data meta box.
			add_meta_box( 'dey-holiday-data', __( 'Settings', 'delivery-slots-for-woocommerce' ), array( $this, 'render_holiday_meta_box' ), $this->post_type, 'normal', 'high' ) ;
			/**
			 * This hook is used to add custom meta boxes for holiday post type.
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

			unset( $columns['comments'], $columns['date'], $columns['title'] );

			$columns['cb']            = '<input type="checkbox" />';
			$columns['title']         = __( 'Label', 'delivery-slots-for-woocommerce' );
			$columns['from_date']     = __( 'From Date', 'delivery-slots-for-woocommerce' );
			$columns['to_date']       = __( 'To Date', 'delivery-slots-for-woocommerce' );
			$columns['schedule_type'] = __( 'Applicable Mode', 'delivery-slots-for-woocommerce');
			$columns['recurring']     = __( 'Is Recurring', 'delivery-slots-for-woocommerce' ) ;
			$columns['status']        = __( 'Status', 'delivery-slots-for-woocommerce' );
			$columns['created_date']  = __( 'Date', 'delivery-slots-for-woocommerce' );
			$columns['actions']       = __( 'Actions', 'delivery-slots-for-woocommerce' );

			return $columns;
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
					'status'       => array( 'post_status', true ),
					'created_date' => array( 'date', true ),
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
				1  => __( 'Holiday updated.', 'delivery-slots-for-woocommerce' ),
				4  => __( 'Holiday updated.', 'delivery-slots-for-woocommerce' ),
				6  => __( 'Holiday published.', 'delivery-slots-for-woocommerce' ),
				7  => __( 'Holiday saved.', 'delivery-slots-for-woocommerce' ),
				10 => __( 'Holiday draft updated.', 'delivery-slots-for-woocommerce' ),
					) ;

			return $messages ;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: holiday count */
				'updated' => _n( '%s holiday updated.', '%s holidays updated.', $bulk_counts[ 'updated' ], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: holiday count */
				'locked'  => _n( '%s holiday not updated, somebody is editing it.', '%s holidays not updated, somebody is editing them.', $bulk_counts[ 'locked' ], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: holiday count */
				'deleted' => _n( '%s holiday permanently deleted.', '%s holidays permanently deleted.', $bulk_counts[ 'deleted' ], 'delivery-slots-for-woocommerce' ),
					) ;

			return $bulk_messages ;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = dey_get_holiday( $post_id ) ;
			}
		}

		/**
		 * Render the holiday mode column.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function render_schedule_type_column() {
			echo wp_kses_post( $this->object->get_holiday_schedule_type_label() ) ;
		}

		/**
		 * Render the from date column.
		 *
		 * @return void
		 */
		public function render_from_date_column() {
			echo esc_html( $this->object->get_from_date() ) ;
		}

		/**
		 * Render the to date column.
		 *
		 * @return void
		 */
		public function render_to_date_column() {
			echo esc_html( $this->object->get_to_date() ) ;
		}

		/**
		 * Render the recurring column.
		 *
		 * @return void
		 */
		public function render_recurring_column() {
			if ( 'yes' == $this->object->get_recurring() ) {
				$recurring = __( 'Yes', 'delivery-slots-for-woocommerce' ) ;
			} else {
				$recurring = __( 'No', 'delivery-slots-for-woocommerce' ) ;
			}

			echo wp_kses_post( $recurring ) ;
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
		 * Duplicate the holiday.
		 *
		 * @since 4.0.0
		 * @param int $post_id Holiday ID.
		 * @return int|bool
		 */
		public function process_duplicate_post( $post_id ) {
			$holiday = dey_get_holiday( $post_id );

			return $holiday->exists() ? $holiday->duplicate() : false;
		}

		/**
		 * Render the holiday settings meta box.
		 *
		 * @return void
		 */
		public function render_holiday_meta_box( $post ) {
			global $dey_holiday ;

			$holiday_id  = ( isset( $post->ID ) ) ? $post->ID : '' ;
			$dey_holiday = dey_get_holiday( $holiday_id ) ;

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/html-holiday-data.php' ;
		}

		/**
		 * Save the meta box data.
		 *
		 * @return void
		 */
		public function save_current_meta_boxes( $post_id, $post ) {
			try {
				$from_date = isset( $_REQUEST[ 'dey_from_date' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'dey_from_date' ] ) ) : '' ;
				$to_date   = isset( $_REQUEST[ 'dey_to_date' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'dey_to_date' ] ) ) : '' ;
				$recurring = isset( $_REQUEST[ 'dey_recurring' ] ) ? 'yes' : 'no' ;
				$schedule_type = isset( $_REQUEST['dey_holiday_schedule_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_holiday_schedule_type'] ) ) : '' ;

				$meta_args = array(
					'dey_from_date' => $from_date,
					'dey_to_date'   => $to_date,
					'dey_recurring' => $recurring,
					'dey_holiday_schedule_type' => $schedule_type,
						) ;

				// Update the holiday data.
				dey_update_holiday( $post_id, $meta_args ) ;
			} catch ( Exception $ex ) {
				WC_Admin_Meta_Boxes::add_error( $ex->getMessage() ) ;
			}
		}
	}

}
