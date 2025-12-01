<?php
/**
 * Product Local Pickup List Table.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php';
}

if ( ! class_exists( 'DEY_Product_Local_Pickup_List_Table' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.5.0
	 * */
	class DEY_Product_Local_Pickup_List_Table extends DEY_Post_List_Table {

		/**
		 * Post type.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE;

		/**
		 * Plugin slug.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		protected $plugin_slug = 'dey';

		/**
		 * Class constructor.
		 *
		 * @since 3.5.0
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Initialize the extra hooks.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		protected function init_extra_hooks() {
			// Mark as picked up.
			add_action( 'post_action_picked_up', array( $this, 'update_picked_up_status' ) );
			// Send manual email.
			add_action( 'post_action_send_email', array( $this, 'send_manual_email' ) );
		}

		/**
		 * Define the which columns to show on this screen.
		 *
		 * @since 3.5.0
		 * @param array $columns Columns to be displayed.
		 * @return array
		 */
		public function define_columns( $columns ) {
			if ( ! dey_check_is_array( $columns ) ) {
				$columns = array();
			}

			unset( $columns['comments'], $columns['date'], $columns['title'] );

			$columns['product_id']       = __( 'Product Name', 'delivery-slots-for-woocommerce' );
			$columns['order_id']         = __( 'Order ID', 'delivery-slots-for-woocommerce' );
			$columns['user_details']     = __( 'User Details', 'delivery-slots-for-woocommerce' );
			$columns['pickup_location']  = __( 'Pickup Location', 'delivery-slots-for-woocommerce' );
			$columns['pickup_date']      = __( 'Pickup Date', 'delivery-slots-for-woocommerce' );
			$columns['time_slots']       = __( 'Time Slots', 'delivery-slots-for-woocommerce' );
			$columns['product_quantity'] = __( 'Product Quantity', 'delivery-slots-for-woocommerce' );
			$columns['pickup_charge']    = __( 'Pickup Fee', 'delivery-slots-for-woocommerce' );
			$columns['status']           = __( 'Status', 'delivery-slots-for-woocommerce' );
			$columns['created_date']     = __( 'Date', 'delivery-slots-for-woocommerce' );
			$columns['actions']          = __( 'Actions', 'delivery-slots-for-woocommerce' );

			return $columns;
		}

		/**
		 * Define primary column.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		protected function get_primary_column() {
			return 'product_id';
		}

		/**
		 * Define which columns are sortable.
		 *
		 * @since 3.5.0
		 * @param array $columns Columns to sort.
		 * @return array
		 */
		public function define_sortable_columns( $columns ) {
			$custom_columns = array(
				'order_id'      => array( 'order_id', true ),
				'user_details'  => array( 'user_details', true ),
				'pickup_date'   => array( 'pickup_date', true ),
				'pickup_charge' => array( 'pickup_charge', true ),
				'status'        => array( 'post_status', true ),
				'created_date'  => array( 'date', true ),
			);

			return wp_parse_args( $custom_columns, $columns );
		}

		/**
		 * Define bulk actions.
		 *
		 * @since 3.5.0
		 * @param array $actions Bulk actions.
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {
			unset( $actions['edit'] );
			unset( $actions['trash'] );

			$actions['picked_up'] = __( 'Mark as Picked Up', 'delivery-slots-for-woocommerce' );
			$actions['delete']    = __( 'Delete', 'delivery-slots-for-woocommerce' );

			return $actions;
		}

		/**
		 * Disable the month dropdown.
		 *
		 * @since 3.5.0
		 * @param bool   $bool Whether to disable the months dropdown or not.
		 * @param string $post_type Post type.
		 * @return bool
		 */
		public function disable_months_dropdown( $bool, $post_type ) {
			return true;
		}

		/**
		 * Get row actions to show in the list table.
		 *
		 * @since 3.5.0
		 * @param array  $actions Actions.
		 * @param object $post Post object.
		 * @return array
		 */
		protected function get_row_actions( $actions, $post ) {
			// Unset the Quick edit.
			unset( $actions['inline hide-if-no-js'] );
			// Unset the edit.
			unset( $actions['edit'] );
			// Unset the trash.
			unset( $actions['trash'] );

			$actions['delete'] = sprintf( '<a class="dey-delete-post" href=%s>%s</a>', get_delete_post_link( $post->ID, '', true ), __( 'Delete Permanently', 'delivery-slots-for-woocommerce' ) );

			return $actions;
		}

		/**
		 * Render any custom filters and search inputs for the list table.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		protected function render_filters() {
			?>
			<select name="dey_delivery_day_filter" id="dey_delivery_day_filter">
				<option value=""><?php esc_html_e( 'Show all days', 'delivery-slots-for-woocommerce' ); ?></option>
				<?php
				foreach ( dey_get_delivery_day_filters() as $name => $label ) :
					echo '<option value="' . esc_attr( $name ) . '"';

					if ( isset( $_GET['dey_delivery_day_filter'] ) ) : // WPCS: input var ok.
						selected( $name, wc_clean( wp_unslash( $_GET['dey_delivery_day_filter'] ) ) ); // WPCS: input var ok, sanitization ok.
					endif;

					echo '>' . esc_html( $label ) . '</option>';
				endforeach;
				?>
			</select>

			<?php
			$from_date       = isset( $_GET['dey_from_datetime_picker_value'] ) ? wc_clean( wp_unslash( $_GET['dey_from_datetime_picker_value'] ) ) : '';
			$to_date         = isset( $_GET['dey_to_datetime_picker_value'] ) ? wc_clean( wp_unslash( $_GET['dey_to_datetime_picker_value'] ) ) : '';
			$from_date_label = ! empty( $from_date ) ? DEY_Date_Time::get_wp_format_datetime( $from_date ) : '';
			$to_date_label   = ! empty( $to_date ) ? DEY_Date_Time::get_wp_format_datetime( $to_date ) : '';
			?>
			<input type = "text" 
				id="dey_from_datetime_picker"
				value = "<?php echo esc_attr( $from_date_label ); ?>"
				placeholder="<?php echo esc_attr( DEY_Date_Time::get_wp_datetime_format() ); ?>" />

			<input type = "hidden" 
				id="dey_from_datetime_picker_value" 
				name="dey_from_datetime_picker_value"
				value = "<?php echo esc_attr( $from_date ); ?>"	/>

			<input type = "text" 
				id="dey_to_datetime_picker"
				value = "<?php echo esc_attr( $to_date_label ); ?>"
				placeholder="<?php echo esc_attr( DEY_Date_Time::get_wp_datetime_format() ); ?>" />

			<input type = "hidden" 
				id="dey_to_datetime_picker_value" 
				name="dey_to_datetime_picker_value"
				value = "<?php echo esc_attr( $to_date ); ?>" />
			<?php
		}

		/**
		 * Render extra tablenav.
		 *
		 * @since 3.5.0
		 * @param string $which Whether to render top or bottom.
		 * @return void
		 */
		public function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			$export_url = add_query_arg(
				array(
					'post_type'      => $this->post_type,
					'dey_export_csv' => 'product_pickup',
				),
				admin_url( 'edit.php' )
			);
			?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="dey-export-csv button button-primary"><?php esc_html_e( 'Export CSV', 'delivery-slots-for-woocommerce' ); ?></a>
			<a href="#" data-type="product_pickup" class="dey-print-data button button-primary"><?php esc_html_e( 'Print', 'delivery-slots-for-woocommerce' ); ?></a>
			<?php
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @since 3.5.0
		 * @param array $bulk_messages Bulk messages.
		 * @param array $bulk_counts Bulk count.
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: product pickup count */
				'updated' => _n( '%s product pickup updated.', '%s product deliveries updated.', $bulk_counts['updated'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: product pickup count */
				'locked'  => _n( '%s product pickup not updated, somebody is editing it.', '%s product deliveries not updated, somebody is editing them.', $bulk_counts['locked'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: product pickup count */
				'deleted' => _n( '%s product pickup permanently deleted.', '%s product deliveries permanently deleted.', $bulk_counts['deleted'], 'delivery-slots-for-woocommerce' ),
			);

			return $bulk_messages;
		}

		/**
		 * Mark as picked up.
		 *
		 * @since 3.5.0
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public function update_picked_up_status( $post_id ) {
			check_admin_referer( 'picked_up-post_' . $post_id );
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.' ) );
			}

			$product_pickup = dey_get_product_local_pickup( $post_id );
			if ( ! $product_pickup->exists() || ! $product_pickup->has_status( 'dey_upcoming' ) ) {
				return;
			}

			dey_update_product_local_pickup( $post_id, array(), array( 'post_status' => 'dey_picked_up' ) );
			$sendback = add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) );

			wp_safe_redirect( add_query_arg( 'picked_up', 1, $sendback ) );
			exit();
		}

		/**
		 * Handle the bulk actions.
		 *
		 * @since 3.5.0
		 * @param string $redirect_to Redirect url.
		 * @param string $action Action.
		 * @param array  $post_ids Post IDs.
		 * @return string
		 */
		public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
			switch ( $action ) {
				case 'picked_up':
					$picked_up = 0;
					$locked    = 0;

					foreach ( (array) $post_ids as $post_id ) {
						if ( ! current_user_can( 'delete_post', $post_id ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to move this item to the Trash.' ) );
						}

						if ( wp_check_post_lock( $post_id ) ) {
							++$locked;
							continue;
						}

						$product_pickup = dey_get_product_local_pickup( $post_id );
						if ( ! $product_pickup->exists() || ! $product_pickup->has_status( 'dey_upcoming' ) ) {
							continue;
						}

						dey_update_product_local_pickup( $post_id, array(), array( 'post_status' => 'dey_picked_up' ) );

						++$picked_up;
					}

					$redirect_to = add_query_arg(
						array(
							'picked_up' => $picked_up,
							'ids'       => join( ',', $post_ids ),
							'locked'    => $locked,
						),
						$redirect_to
					);
					break;
			}

			return esc_url_raw( $redirect_to );
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 *
		 * @since 3.5.0
		 * @param int $post_id Post ID.
		 * @return void
		 */
		protected function prepare_row_data( $post_id ) {
			if ( ! $this->object || $post_id !== $this->object->get_id() ) {
				$this->object = dey_get_product_local_pickup( $post_id );
			}
		}

		/**
		 * Render the order id column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_order_id_column() {
			echo wp_kses_post( dey_get_edit_post_link( $this->object->get_order_id(), '#' . $this->object->get_order_id() ) );
		}

		/**
		 * Render the user details column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_user_details_column() {
			echo wp_kses_post( $this->object->get_user_name() . ' (' . $this->object->get_user_email() . ')' );
		}

		/**
		 * Render the product ID column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_product_id_column() {
			echo wp_kses_post( dey_get_products_link( array( $this->object->get_product_id() ) ) );
		}

		/**
		 * Render the product quantity column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_product_quantity_column() {
			echo wp_kses_post( $this->object->get_order_product_quantity() );
		}

		/**
		 * Render the pickup location column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_pickup_location_column() {
			if ( $this->object->get_formatted_pickup_address() ) {
				$name    = '<b>' . __( 'Pickup Location', 'delivery-slots-for-woocommerce' ) . ' : </b>' . $this->object->get_pickup_location_name();
				$address = '<b>' . __( 'Pickup Address', 'delivery-slots-for-woocommerce' ) . ' : </b>' . $this->object->get_formatted_pickup_address();

				echo wp_kses_post( $name . '</br>' . $address );
			} else {
				echo esc_html_e( 'None', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Render the pickup date column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_pickup_date_column() {
			echo wp_kses_post( $this->object->get_formatted_pickup_date() );
		}

		/**
		 * Render the time slots column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_time_slots_column() {
			echo wp_kses_post( $this->object->get_formatted_time_slots() );
		}

		/**
		 * Render the pickup charge column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_pickup_charge_column() {
			echo wp_kses_post( $this->object->get_formatted_pickup_charge() );
		}

		/**
		 * Render the status column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_status_column() {
			echo wp_kses_post( dey_display_post_status( $this->object->get_status() ) );
		}

		/**
		 * Render the created date column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_created_date_column() {
			echo wp_kses_post( $this->object->get_formatted_created_date() );
		}

		/**
		 * Render the actions column.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_actions_column() {
			$actions = '';
			if ( $this->object->has_status( 'dey_upcoming' ) ) {
				$link      = add_query_arg(
					array(
						'action' => 'picked_up',
						'post'   => $this->object->get_id(),
					),
					admin_url( 'post.php' )
				);
				$actions   = sprintf( '<a class="dey-delivered-post button" href=%s>%s</a>', wp_nonce_url( $link, 'picked_up-post_' . $this->object->get_id() ), __( 'Mark as Picked Up', 'delivery-slots-for-woocommerce' ) );
				$email_url = add_query_arg(
					array(
						'action' => 'send_email',
						'post'   => $this->object->get_id(),
					),
					admin_url( 'post.php' )
				);
				$actions  .= sprintf( '<a class="dey-send-email button" href=%s>%s</a>', wp_nonce_url( $email_url, 'manual-email-post_' . $this->object->get_id() ), __( 'Send Email', 'delivery-slots-for-woocommerce' ) );
			}

			echo wp_kses_post( $actions );
		}

		/**
		 * Handle custom filters.
		 *
		 * @since 3.5.0
		 * @param array $query_vars Query vars.
		 * @return array
		 */
		protected function query_filters( $query_vars ) {
			if ( isset( $_GET['dey_delivery_day_filter'] ) && ! empty( $_GET['dey_delivery_day_filter'] ) ) {
				$day_filter = wc_clean( wp_unslash( $_GET['dey_delivery_day_filter'] ) );
				$from_date  = isset( $_GET['dey_from_datetime_picker_value'] ) ? wc_clean( wp_unslash( $_GET['dey_from_datetime_picker_value'] ) ) : '';
				$to_date    = isset( $_GET['dey_to_datetime_picker_value'] ) ? wc_clean( wp_unslash( $_GET['dey_to_datetime_picker_value'] ) ) : '';

				$query_vars['meta_query'] = dey_get_pickup_meta_query_args( $day_filter, $from_date, $to_date );
			}

			return $query_vars;
		}

		/**
		 * Get the search post IDs.
		 *
		 * @since 3.5.0
		 * @param array $terms Search terms.
		 * @return array
		 */
		protected function get_search_post_ids( $terms ) {
			$post_ids = array();
			foreach ( $terms as $term ) {
				$term       = $this->database->esc_like( ( $term ) );
				$post_query = new DEY_Query( $this->database->prefix . 'posts', 'p' );

				$post_query->select( 'DISTINCT `p`.ID' )
					->leftJoin( $this->database->prefix . 'postmeta', 'pm', '`p`.`ID` = `pm`.`post_id`' )
					->where( '`p`.post_type', DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE )
					->whereIn( '`p`.post_status', dey_get_product_local_pickup_statuses() )
					->whereIn( '`pm`.meta_key', array( 'dey_user_name', 'dey_user_email', 'dey_order_id' ) )
					->whereLike( '`pm`.meta_value', '%' . $term . '%' );

				$post_ids = $post_query->fetchCol( 'ID' );
			}

			return array_merge( $post_ids, array( 0 ) );
		}

		/**
		 * Send manual email.
		 *
		 * @since 3.5.0
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public function send_manual_email( $post_id ) {
			check_admin_referer( 'manual-email-post_' . $post_id );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.' ) );
			}

			$product_pickup = dey_get_product_local_pickup( $post_id );
			if ( ! $product_pickup->exists() || ! $product_pickup->has_status( 'dey_upcoming' ) ) {
				return;
			}

			/**
			 * This hook is used to do extra product pickup manual email.
			 *
			 * @since 3.5.0
			 */
			do_action( 'dey_product_pickup_manual_email', $product_pickup );

			$sendback = add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) );
			wp_safe_redirect( add_query_arg( 'send_email', 1, $sendback ) );
			exit();
		}

		/**
		 * Handle orderby filters.
		 *
		 * @since 3.5.0
		 * @param array $query_vars Query vars.
		 * @return array
		 */
		protected function orderby_filters( $query_vars ) {
			// return if the order by key is not exists.
			if ( ! isset( $query_vars['orderby'] ) ) {
				return $query_vars;
			}

			switch ( strtolower( $query_vars['orderby'] ) ) {
				case 'user_details':
					$query_vars['meta_key'] = 'dey_user_name';
					$query_vars['orderby']  = 'meta_value';
					break;

				case 'pickup_charge':
					$query_vars['meta_key'] = 'dey_pickup_charge';
					$query_vars['orderby']  = 'meta_value_num';
					break;

				case 'order_id':
					$query_vars['meta_key'] = 'dey_order_id';
					$query_vars['orderby']  = 'meta_value_num';
					break;

				case 'pickup_date':
					$query_vars['meta_key']  = 'dey_pickup_date';
					$query_vars['meta_type'] = 'DATETIME';
					$query_vars['orderby']   = 'meta_value';
					break;
			}

			return $query_vars;
		}
	}

}
