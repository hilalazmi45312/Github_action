<?php
/**
 * Order Tip List Table.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php' ;
}

if ( ! class_exists( 'DEY_Order_Tip_List_Table' ) ) {

	/**
	 * DEY_Order_Tip_List_Table Class.
	 * */
	class DEY_Order_Tip_List_Table extends DEY_Post_List_Table {

		/**
		 * Post Type.
		 * 
		 * @var String
		 */
		protected $post_type = DEY_Register_Post_Types::ORDER_TIP_POSTTYPE ;

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
		 * Define the which columns to show on this screen.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			if ( empty( $columns ) && ! is_array( $columns ) ) {
				$columns = array() ;
			}

			unset( $columns[ 'comments' ], $columns[ 'date' ], $columns[ 'title' ] ) ;

			$columns[ 'order_id' ]     = __( 'Order ID', 'delivery-slots-for-woocommerce' ) ;
			$columns[ 'user_details' ] = __( 'User Details', 'delivery-slots-for-woocommerce' ) ;
			$columns[ 'type' ]         = __( 'Type', 'delivery-slots-for-woocommerce' ) ;
			$columns[ 'amount' ]       = __( 'Amount', 'delivery-slots-for-woocommerce' ) ;
			$columns[ 'status' ]       = __( 'Status', 'delivery-slots-for-woocommerce' ) ;
			$columns[ 'created_date' ] = __( 'Date', 'delivery-slots-for-woocommerce' ) ;

			return $columns ;
		}

		/**
		 * Define primary column.
		 *
		 * @return array
		 */
		protected function get_primary_column() {
			return 'order_id' ;
		}

		/**
		 * Define which columns are sortable.
		 *
		 * @return array
		 */
		public function define_sortable_columns( $columns ) {
			$custom_columns = array(
				'order_id'     => array( 'post_parent', true ),
				'user_details' => array( 'user_details', true ),
				'amount'       => array( 'amount', true ),
				'status'       => array( 'post_status', true ),
				'created_date' => array( 'date', true ),
					) ;

			return wp_parse_args( $custom_columns, $columns ) ;
		}

		/**
		 * Define bulk actions.
		 * 
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {

			unset( $actions[ 'edit' ] ) ;
			unset( $actions[ 'trash' ] ) ;

			$actions[ 'delete' ] = __( 'Delete', 'delivery-slots-for-woocommerce' ) ;

			return $actions ;
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

			//Unset the Quick edit.
			unset( $actions[ 'inline hide-if-no-js' ] ) ;
			//Unset the edit.
			unset( $actions[ 'edit' ] ) ;
			//Unset the trash.
			unset( $actions[ 'trash' ] ) ;

			$actions[ 'delete' ] = sprintf( '<a class="dey-delete-post" href=%s>%s</a>', get_delete_post_link( $post->ID, '', true ), __( 'Delete Permanently', 'delivery-slots-for-woocommerce' ) ) ;

			return $actions ;
		}

		/**
		 * Render any custom filters and search inputs for the list table.
		 */
		protected function render_filters() {
			?>
			<select name="dey_delivery_day_filter" id="dey_delivery_day_filter">
				<option value=""><?php esc_html_e( 'Show all days', 'delivery-slots-for-woocommerce' ) ; ?></option>
				<?php
				$day_filters = dey_get_order_tip_day_filters() ;

				foreach ( $day_filters as $name => $label ) {
					echo '<option value="' . esc_attr( $name ) . '"' ;

					if ( isset( $_GET[ 'dey_delivery_day_filter' ] ) ) { // WPCS: input var ok.
						selected( $name, wc_clean( wp_unslash( $_GET[ 'dey_delivery_day_filter' ] ) ) ) ; // WPCS: input var ok, sanitization ok.
					}

					echo '>' . esc_html( $label ) . '</option>' ;
				}
				?>
			</select>

			<?php
			$from_date       = isset( $_GET[ 'dey_from_datetime_picker_value' ] ) ? wc_clean( wp_unslash( $_GET[ 'dey_from_datetime_picker_value' ] ) ) : '' ;
			$to_date         = isset( $_GET[ 'dey_to_datetime_picker_value' ] ) ? wc_clean( wp_unslash( $_GET[ 'dey_to_datetime_picker_value' ] ) ) : '' ;
			$from_date_label = ! empty( $from_date ) ? DEY_Date_Time::get_wp_format_datetime( $from_date ) : '' ;
			$to_date_label   = ! empty( $to_date ) ? DEY_Date_Time::get_wp_format_datetime( $to_date ) : '' ;
			?>
			<input type = "text" 
				   id="dey_from_datetime_picker"
				   value = "<?php echo esc_attr( $from_date_label ) ; ?>"
				   placeholder="<?php echo esc_attr( DEY_Date_Time::get_wp_datetime_format() ) ; ?>" 
				   />

			<input type = "hidden" 
				   id="dey_from_datetime_picker_value" 
				   name="dey_from_datetime_picker_value"
				   value = "<?php echo esc_attr( $from_date ) ; ?>"
				   />
			<input type = "text" 
				   id="dey_to_datetime_picker"
				   value = "<?php echo esc_attr( $to_date_label ) ; ?>"
				   placeholder="<?php echo esc_attr( DEY_Date_Time::get_wp_datetime_format() ) ; ?>" 
				   />

			<input type = "hidden" 
				   id="dey_to_datetime_picker_value" 
				   name="dey_to_datetime_picker_value"
				   value = "<?php echo esc_attr( $to_date ) ; ?>"
				   />
				   <?php
		}

			   /**
				* Render extra tablenav.
				*
				*/
		public function extra_tablenav( $which ) {
			if ( 'top' != $which ) {
				return ;
			}

			$export_url = add_query_arg( array( 'post_type' => $this->post_type, 'dey_export_csv' => 'order_tip' ), admin_url( 'edit.php' ) ) ;
			?>
			<a href="<?php echo esc_url( $export_url ) ; ?>" class="dey-export-csv button button-primary"><?php esc_html_e( 'Export CSV', 'delivery-slots-for-woocommerce' ) ; ?></a>
			<a href="#" data-type="order_tip" class="dey-print-data button button-primary"><?php esc_html_e( 'Print', 'delivery-slots-for-woocommerce' ) ; ?></a>
			<?php
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: order tip count */
				'updated' => _n( '%s order tip updated.', '%s order tips updated.', $bulk_counts[ 'updated' ], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: order tip count */
				'locked'  => _n( '%s order tip not updated, somebody is editing it.', '%s order tips not updated, somebody is editing them.', $bulk_counts[ 'locked' ], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: order tip count */
				'deleted' => _n( '%s order tip permanently deleted.', '%s order tips permanently deleted.', $bulk_counts[ 'deleted' ], 'delivery-slots-for-woocommerce' ),
					) ;

			return $bulk_messages ;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = dey_get_order_tip( $post_id ) ;
			}
		}

		/**
		 * Render the order id column.
		 *
		 * @return void
		 */
		public function render_order_id_column() {

			echo wp_kses_post( dey_get_edit_post_link( $this->object->get_order_id(), '#' . $this->object->get_order_id() ) ) ;
		}

		/**
		 * Render the user details column.
		 *
		 * @return void
		 */
		public function render_user_details_column() {
			echo wp_kses_post( $this->object->get_user_name() . ' (' . $this->object->get_user_email() . ')' ) ;
		}

		/**
		 * Render the type column.
		 *
		 * @return void
		 */
		public function render_type_column() {

			echo wp_kses_post( dey_order_tip_type_name( $this->object->get_type() ) ) ;
		}

		/**
		 * Render the amount column.
		 *
		 * @return void
		 */
		public function render_amount_column() {

			echo wp_kses_post( dey_price( $this->object->get_amount() ) ) ;
		}

		/**
		 * Render the status column.
		 *
		 * @return void
		 */
		public function render_status_column() {

			echo wp_kses_post( dey_display_post_status( $this->object->get_status() ) ) ;
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
		 * Get the search post IDs.
		 *
		 * @return array
		 */
		protected function get_search_post_ids( $terms ) {
			$post_ids = array() ;
			foreach ( $terms as $term ) {

				$term = $this->database->esc_like( ( $term ) ) ;

				$post_query = new DEY_Query( $this->database->prefix . 'posts', 'p' ) ;

				$post_query->select( 'DISTINCT `p`.ID' )
						->leftJoin( $this->database->prefix . 'postmeta', 'pm', '`p`.`ID` = `pm`.`post_id`' )
						->where( '`p`.post_type', $this->post_type )
						->whereIn( '`p`.post_status', dey_get_order_tip_statuses() )
						->whereIn( '`pm`.meta_key', array( 'dey_user_name', 'dey_user_email' ) )
						->whereLike( '`pm`.meta_value', '%' . $term . '%' ) ;

				$post_ids = $post_query->fetchCol( 'ID' ) ;
			}

			return array_merge( $post_ids, array( 0 ) ) ;
		}

		/**
		 * Handle any custom filters.
		 *
		 * @return array
		 */
		protected function query_filters( $query_vars ) {
			if ( isset( $_GET[ 'dey_delivery_day_filter' ] ) && ! empty( $_GET[ 'dey_delivery_day_filter' ] ) ) {
				$day_filter = wc_clean( wp_unslash( $_GET[ 'dey_delivery_day_filter' ] ) ) ;
				$from_date  = isset( $_GET[ 'dey_from_datetime_picker_value' ] ) ? wc_clean( wp_unslash( $_GET[ 'dey_from_datetime_picker_value' ] ) ) : '' ;
				$to_date    = isset( $_GET[ 'dey_to_datetime_picker_value' ] ) ? wc_clean( wp_unslash( $_GET[ 'dey_to_datetime_picker_value' ] ) ) : '' ;

				$query_vars[ 'date_query' ] = dey_get_order_tip_date_query_args( $day_filter, $from_date, $to_date ) ;
			}

			return $query_vars ;
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
				case 'user_details':
					$query_vars[ 'meta_key' ] = 'dey_user_name' ;
					$query_vars[ 'orderby' ]  = 'meta_value' ;
					break ;

				case 'amount':
					$query_vars[ 'meta_key' ] = 'dey_amount' ;
					$query_vars[ 'orderby' ]  = 'meta_value_num' ;
					break ;
			}

			return $query_vars ;
		}
	}

}
