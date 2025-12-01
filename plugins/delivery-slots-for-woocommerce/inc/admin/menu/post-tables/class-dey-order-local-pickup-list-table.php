<?php
/**
 * Order Delivery List Table.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php';
}

if ( ! class_exists( 'DEY_Order_Local_Pickup_List_Table' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Order_Local_Pickup_List_Table extends DEY_Post_List_Table {

		/**
		 * Post Type.
		 *
		 * @var String
		 */
		protected $post_type = DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE;

		/**
		 * Plugin Slug.
		 *
		 * @var String
		 */
		protected $plugin_slug = 'dey';

		/**
		 * Class initialization.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Initialize the extra hooks.
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
		 * @return array
		 */
		public function define_columns( $columns ) {
			if ( ! dey_check_is_array( $columns ) ) {
				$columns = array();
			}

			unset( $columns['comments'], $columns['date'], $columns['title'] );

			$columns['order_id']        = __( 'Order ID', 'delivery-slots-for-woocommerce' );
			$columns['user_details']    = __( 'User Details', 'delivery-slots-for-woocommerce' );
			$columns['product_ids']     = __( 'Products', 'delivery-slots-for-woocommerce' );
			$columns['pickup_location'] = __( 'Pickup Location', 'delivery-slots-for-woocommerce' );
			$columns['pickup_date']     = __( 'Pickup Date', 'delivery-slots-for-woocommerce' );
			$columns['time_slots']      = __( 'Pickup Time Slots', 'delivery-slots-for-woocommerce' );
			$columns['pickup_charge']   = __( 'Pickup Fee', 'delivery-slots-for-woocommerce' );
			$columns['status']          = __( 'Status', 'delivery-slots-for-woocommerce' );
			$columns['created_date']    = __( 'Date', 'delivery-slots-for-woocommerce' );
			$columns['actions']         = __( 'Actions', 'delivery-slots-for-woocommerce' );

			return $columns;
		}

		/**
		 * Define primary column.
		 *
		 * @return array
		 */
		protected function get_primary_column() {
			return 'order_id';
		}

		/**
		 * Define which columns are sortable.
		 *
		 * @return array
		 */
		public function define_sortable_columns( $columns ) {
			$custom_columns = array(
				'order_id'      => array( 'post_parent', true ),
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
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {

			unset( $actions['edit'] );
			unset( $actions['trash'] );

			$actions['picked_up'] = __( 'Mark as Picked up', 'delivery-slots-for-woocommerce' );
			$actions['delete']    = __( 'Delete', 'delivery-slots-for-woocommerce' );

			return $actions;
		}

		/**
		 * Disable the month dropdown.
		 *
		 * @return bool
		 */
		public function disable_months_dropdown( $bool, $post_type ) {
			return true;
		}

		/**
		 * Get row actions to show in the list table.
		 *
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
		 */
		protected function render_filters() {
			?>
			<select name="dey_delivery_day_filter" id="dey_delivery_day_filter">
				<option value=""><?php esc_html_e( 'Show all days', 'delivery-slots-for-woocommerce' ); ?></option>
				<?php
				$day_filters = dey_get_delivery_day_filters();

				foreach ( $day_filters as $name => $label ) {
					echo '<option value="' . esc_attr( $name ) . '"';

					if ( isset( $_GET['dey_delivery_day_filter'] ) ) { // WPCS: input var ok.
						selected( $name, wc_clean( wp_unslash( $_GET['dey_delivery_day_filter'] ) ) ); // WPCS: input var ok, sanitization ok.
					}

					echo '>' . esc_html( $label ) . '</option>';
				}
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
					placeholder="<?php echo esc_attr( DEY_Date_Time::get_wp_datetime_format() ); ?>" 
					/>

			<input type = "hidden" 
					id="dey_from_datetime_picker_value" 
					name="dey_from_datetime_picker_value"
					value = "<?php echo esc_attr( $from_date ); ?>"
					/>
			<input type = "text" 
					id="dey_to_datetime_picker"
					value = "<?php echo esc_attr( $to_date_label ); ?>"
					placeholder="<?php echo esc_attr( DEY_Date_Time::get_wp_datetime_format() ); ?>" 
					/>

			<input type = "hidden" 
					id="dey_to_datetime_picker_value" 
					name="dey_to_datetime_picker_value"
					value = "<?php echo esc_attr( $to_date ); ?>"
					/>
					<?php
		}

		/**
		 * Render extra tablenav.
		 */
		public function extra_tablenav( $which ) {
			if ( 'top' != $which ) {
				return;
			}

			$export_url = add_query_arg(
				array(
					'post_type'      => $this->post_type,
					'dey_export_csv' => 'order_local_pickup',
				),
				admin_url( 'edit.php' )
			);
			?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="dey-export-csv button button-primary"><?php esc_html_e( 'Export CSV', 'delivery-slots-for-woocommerce' ); ?></a>
			<a href="#" data-type="order_local_pickup" class="dey-print-data button button-primary"><?php esc_html_e( 'Print', 'delivery-slots-for-woocommerce' ); ?></a>
			<?php
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: order local pickup count */
				'updated' => _n( '%s order local pickup updated.', '%s order local pickups updated.', $bulk_counts['updated'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: order local pickup count */
				'locked'  => _n( '%s order local pickup not updated, somebody is editing it.', '%s order local pickups not updated, somebody is editing them.', $bulk_counts['locked'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: order local pickup count */
				'deleted' => _n( '%s order local pickup permanently deleted.', '%s order local pickups permanently deleted.', $bulk_counts['deleted'], 'delivery-slots-for-woocommerce' ),
			);

			return $bulk_messages;
		}

		/**
		 * Mark as picked up.
		 *
		 * @return void
		 */
		public function update_picked_up_status( $post_id ) {
			check_admin_referer( 'picked-up-post_' . $post_id );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.' ) );
			}

			$order_delivery = dey_get_order_local_pickup( $post_id );
			if ( ! $order_delivery->exists() || ! $order_delivery->has_status( 'dey_upcoming' ) ) {
				return;
			}

			dey_update_order_local_pickup( $post_id, array(), array( 'post_status' => 'dey_picked_up' ) );

			$sendback = add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) );

			wp_safe_redirect( add_query_arg( 'picked_up', 1, $sendback ) );
			exit();
		}

		/**
		 * Handle the bulk actions.
		 *
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

						$order_delivery = dey_get_order_delivery( $post_id );
						if ( ! $order_delivery->exists() || ! $order_delivery->has_status( 'dey_upcoming' ) ) {
							continue;
						}

						dey_update_order_local_pickup( $post_id, array(), array( 'post_status' => 'dey_picked_up' ) );

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
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = dey_get_order_local_pickup( $post_id );
			}
		}

		/**
		 * Render the order id column.
		 *
		 * @return void
		 */
		public function render_order_id_column() {
			echo wp_kses_post( dey_get_edit_post_link( $this->object->get_order_id(), '#' . $this->object->get_order_id() ) );
		}

		/**
		 * Render the user details column.
		 *
		 * @return void
		 */
		public function render_user_details_column() {
			echo wp_kses_post( $this->object->get_user_name() . ' (' . $this->object->get_user_email() . ')' );
		}

		/**
		 * Render the product IDs column.
		 *
		 * @return void
		 */
		public function render_product_ids_column() {
			echo wp_kses_post( dey_get_order_products_link( $this->object->get_product_ids() ) );
		}

		/**
		 * Render the pickup location column.
		 *
		 * @return void
		 */
		public function render_pickup_location_column() {
			if ( $this->object->get_pickup_location_id() ) {
				$name    = '<b>' . __( 'Pickup Location', 'delivery-slots-for-woocommerce' ) . ' : </b>' . dey_get_edit_post_link( $this->object->get_pickup_location_id(), '#' . $this->object->get_pickup_location()->get_name() );
				$address = '<b>' . __( 'Pickup Address', 'delivery-slots-for-woocommerce' ) . ' : </b>' . $this->object->get_formatted_address();

				echo wp_kses_post( $name . '</br>' . $address );
			} else {
				echo esc_html_e( 'None', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Render the pickup date column.
		 *
		 * @return void
		 */
		public function render_pickup_date_column() {
			echo wp_kses_post( $this->object->get_formatted_pickup_date() );
		}

		/**
		 * Render the time slots column.
		 *
		 * @return void
		 */
		public function render_time_slots_column() {
			echo wp_kses_post( $this->object->get_formatted_time_slots() );
		}

		/**
		 * Render the pickup charge column.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function render_pickup_charge_column() {
			echo wp_kses_post( $this->object->get_formatted_pickup_charge() );
		}

		/**
		 * Render the status column.
		 *
		 * @return void
		 */
		public function render_status_column() {
			echo wp_kses_post( dey_display_post_status( $this->object->get_status() ) );
		}

		/**
		 * Render the created date column.
		 *
		 * @return void
		 */
		public function render_created_date_column() {
			echo wp_kses_post( $this->object->get_formatted_created_date() );
		}

		/**
		 * Render the actions column.
		 *
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
				$actions   = sprintf( '<a class="dey-delivered-post button" href=%s>%s</a>', wp_nonce_url( $link, 'picked-up-post_' . $this->object->get_id() ), __( 'Mark as Picked up', 'delivery-slots-for-woocommerce' ) );
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
		 * Get the search post IDs.
		 *
		 * @return array
		 */
		protected function get_search_post_ids( $terms ) {
			$post_ids = array();
			foreach ( $terms as $term ) {

				$term = $this->database->esc_like( ( $term ) );

				$post_query = new DEY_Query( $this->database->prefix . 'posts', 'p' );

				$post_query->select( 'DISTINCT `p`.ID' )
						->leftJoin( $this->database->prefix . 'postmeta', 'pm', '`p`.`ID` = `pm`.`post_id`' )
						->where( '`p`.post_type', DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE )
						->whereIn( '`p`.post_status', dey_get_order_delivery_statuses() )
						->whereIn( '`pm`.meta_key', array( 'dey_user_name', 'dey_user_email' ) )
						->whereLike( '`pm`.meta_value', '%' . $term . '%' )
						->where( 'p.post_parent', intval( $term ), 'OR' );

				$post_ids = $post_query->fetchCol( 'ID' );
			}

			return array_merge( $post_ids, array( 0 ) );
		}

		/**
		 * Handle any custom filters.
		 *
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
		 * Handle order by filters.
		 *
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

				case 'pickup_date':
					$query_vars['meta_key']  = 'dey_pickup_date';
					$query_vars['meta_type'] = 'DATETIME';
					$query_vars['orderby']   = 'meta_value';
					break;
			}
			return $query_vars;
		}

		/**
		 * Send manual email
		 *
		 * @param int $post_id
		 * @since 2.6
		 *
		 * @return void
		 */
		public function send_manual_email( $post_id ) {
			check_admin_referer( 'manual-email-post_' . $post_id );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.' ) );
			}

			$order_local_pickup = dey_get_order_local_pickup( $post_id );
			if ( ! $order_local_pickup->exists() || ! $order_local_pickup->has_status( 'dey_upcoming' ) ) {
				return;
			}

			/**
			 * This hook is used to do extra order local pickup manual email.
			 *
			 * @since 2.6
			 */
			do_action( 'dey_order_local_pickup_manual_email', $order_local_pickup );

			$sendback = add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) );
			wp_safe_redirect( add_query_arg( 'send_email', 1, $sendback ) );
			exit();
		}
	}

}
