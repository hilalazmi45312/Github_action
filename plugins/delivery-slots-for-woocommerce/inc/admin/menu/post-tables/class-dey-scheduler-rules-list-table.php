<?php
/**
 * Scheduler rules list table.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php';
}

if ( ! class_exists( 'DEY_Scheduler_Rule_List_Table' ) ) {

	/**
	 * Class.
	 *
	 * @since 4.0.0
	 * */
	class DEY_Scheduler_Rule_List_Table extends DEY_Post_List_Table {

		/**
		 * Post type.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::SCHEDULER_RULE_POSTTYPE;

		/**
		 * Plugin slug.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		protected $plugin_slug = 'dey';

		/**
		 * Class initialization.
		 *
		 * @since 4.0.0
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Add meta boxes for this post type.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function add_meta_boxes() {
			// Remove the publish meta box.
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
			// Status meta box.
			add_meta_box( 'dey-scheduler-rule-status-data', __( 'Status', 'delivery-slots-for-woocommerce' ), array( $this, 'render_status_meta_box' ), $this->post_type, 'side', 'high' );
			// General settings meta box.
			add_meta_box( 'dey-scheduler-rule-general-data', __( 'General', 'delivery-slots-for-woocommerce' ), array( $this, 'render_scheduler_rule_general_meta_box' ), $this->post_type, 'normal', 'high' );
			// Scheduler rule meta box.
			add_meta_box( 'dey-scheduler-rule-data', __( 'Settings', 'delivery-slots-for-woocommerce' ), array( $this, 'render_scheduler_rule_meta_box' ), $this->post_type, 'normal', 'high' );

			/**
			 * This hook is used to add custom meta boxes for scheduler rule post type.
			 *
			 * @since 4.0.0
			 */
			do_action( 'dey_add_meta_boxes_' . $this->post_type );
		}

		/**
		 * Define the which columns to show on this screen.
		 *
		 * @since 4.0.0
		 * @param array $columns Columns.
		 * @return array
		 */
		public function define_columns( $columns ) {
			if ( ! dey_check_is_array( $columns ) ) {
				$columns = array();
			}

			unset( $columns['comments'], $columns['date'] );

			$columns['description']     = __( 'Description', 'delivery-slots-for-woocommerce' );
			$columns['mode']            = __( 'Mode', 'delivery-slots-for-woocommerce' );
			$columns['shipping_method'] = __( 'Shipping Method', 'delivery-slots-for-woocommerce' );
			$columns['priority']        = __( 'Priority', 'delivery-slots-for-woocommerce' );
			$columns['status']          = __( 'Status', 'delivery-slots-for-woocommerce' );
			$columns['created_date']    = __( 'Created Date', 'delivery-slots-for-woocommerce' );
			$columns['actions']         = __( 'Actions', 'delivery-slots-for-woocommerce' );

			return $columns;
		}

		/**
		 * Define which columns are sortable.
		 *
		 * @since 4.0.0
		 * @param array $columns Columns.
		 * @return array
		 */
		public function define_sortable_columns( $columns ) {
			return wp_parse_args(
				array(
					'priority'     => array( 'priority', false ),
					'status'       => array( 'post_status', false ),
					'created_date' => array( 'date', false ),
				),
				$columns
			);
		}

		/**
		 * Define bulk actions.
		 *
		 * @since 4.0.0
		 * @param array $actions Bulk actions.
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {
			unset( $actions['edit'], $actions['trash'] );

			$actions['activate']   = __( 'Activate', 'delivery-slots-for-woocommerce' );
			$actions['deactivate'] = __( 'Deactivate', 'delivery-slots-for-woocommerce' );
			$actions['delete']     = __( 'Delete', 'delivery-slots-for-woocommerce' );

			return $actions;
		}

		/**
		 * Disable the month dropdown.
		 *
		 * @since 4.0.0
		 * @param bool   $bool Whether to disable or not.
		 * @param string $post_type Post type.
		 * @return bool
		 */
		public function disable_months_dropdown( $bool, $post_type ) {
			return true;
		}

		/**
		 * Render custom filters.
		 *
		 * @since 4.0.0
		 */
		protected function render_filters() {
			$selected_scheduler_type = isset( $_GET['dey_scheduler_type_filter'] ) && ! empty( $_GET['dey_scheduler_type_filter'] ) ? wc_clean( wp_unslash( $_GET['dey_scheduler_type_filter'] ) ) : '';
			?>
			<select name='dey_scheduler_type_filter' id='dey_scheduler_type_filter'>
				<option value=''><?php esc_html_e( 'All Schedulers', 'delivery-slots-for-woocommerce' ); ?></option>
				<?php
				foreach ( dey_get_order_scheduler_options() as $scheduler_key => $scheduler_label ) {
					echo '<option value="' . esc_attr( $scheduler_key ) . '"' . selected( $scheduler_key, $selected_scheduler_type ) . '>' . esc_html( $scheduler_label ) . '</option>';
				}
				?>
			</select>
			<?php
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @since 4.0.0
		 * @param array $messages Messages.
		 * @return array
		 */
		public function post_updated_messages( $messages ) {
			global $post;

			$messages[ $this->post_type ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Scheduler Rule Updated Successfully.', 'delivery-slots-for-woocommerce' ),
				3  => __( 'Scheduler Rule Deleted Successfully.', 'delivery-slots-for-woocommerce' ),
				4  => __( 'Scheduler Rule Updated Successfully.', 'delivery-slots-for-woocommerce' ),
				6  => __( 'Scheduler Rule Updated Successfully.', 'delivery-slots-for-woocommerce' ),
				7  => __( 'Scheduler Rule Saved.', 'delivery-slots-for-woocommerce' ),
				10 => __( 'Scheduler Rule Draft Updated.', 'delivery-slots-for-woocommerce' ),
			);

			return $messages;
		}

		/**
		 * Change messages when a post type is bulk updated.
		 *
		 * @since 4.0.0
		 * @param array $bulk_messages Bulk actions messages.
		 * @param array $bulk_counts Bulk action posts counts.
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: scheduler rule count */
				'updated' => _n( '%s scheduler rule updated.', '%s scheduler rules updated.', $bulk_counts['updated'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: scheduler rule count */
				'locked'  => _n( '%s scheduler rule not updated, somebody is editing it.', '%s scheduler rules not updated, somebody is editing them.', $bulk_counts['locked'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: scheduler rule count */
				'deleted' => _n( '%s scheduler rule permanently deleted.', '%s scheduler rules permanently deleted.', $bulk_counts['deleted'], 'delivery-slots-for-woocommerce' ),
			);

			return $bulk_messages;
		}

		/**
		 * Get row actions to show in the list table.
		 *
		 * @since 4.0.0
		 * @param array  $actions Row actions.
		 * @param object $post Post object.
		 * @return array
		 */
		protected function get_row_actions( $actions, $post ) {
			// Unset the Quick edit, trash.
			unset( $actions['inline hide-if-no-js'], $actions['edit'], $actions['trash'] );

			return $actions;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 * @return void
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = dey_get_scheduler_rule( $post_id );
			}
		}

		/**
		 * Render the description column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_description_column() {
			echo wp_kses_post( $this->object->get_description() );
		}

		/**
		 * Render the mode column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_mode_column() {
			$order_scheduler_types = dey_get_order_scheduler_options();
			if ( isset( $order_scheduler_types[ $this->object->get_scheduler_type() ] ) ) {
				echo esc_html( $order_scheduler_types[ $this->object->get_scheduler_type() ] );
			} else {
				esc_html_e( 'Both Pickup and Delivery', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Render the shipping method column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_shipping_method_column() {
			$shipping_methods = $this->object->get_formatted_shipping_method_labels();
			if ( dey_check_is_array( $shipping_methods ) ) {
				echo wp_kses_post( implode( ', ', $shipping_methods ) );
			} else {
				esc_html_e( 'All Shipping', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Render the shipping method column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_priority_column() {
			echo wp_kses_post( $this->object->get_priority() );
		}

		/**
		 * Render the status column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_status_column() {
			echo wp_kses_post( dey_display_post_status( $this->object->get_status() ) );
		}

		/**
		 * Render the created date column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_created_date_column() {
			echo wp_kses_post( $this->object->get_formatted_created_date() );
		}

		/**
		 * Render the actions column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_actions_column() {
			$actions                   = array();
			$link                      = add_query_arg( array( 'post_type' => $this->post_type ), admin_url( 'post.php' ) );
			$status_action             = ( $this->object->get_status() === 'dey_inactive' ) ? 'active' : 'inactive';
			$status_link               = add_query_arg(
				array(
					'action' => $this->object->has_status( 'dey_inactive' ) ? 'activate' : 'deactivate',
					'post'   => $this->object->get_id(),
				),
				admin_url( 'post.php' )
			);
			$actions['edit']           = dey_display_action( 'edit', $this->object->get_id(), $link, true );
			$actions[ $status_action ] = dey_display_action( $status_action, $this->object->get_id(), $status_link );
			$actions['duplicate']      = dey_display_action( 'duplicate', $this->object->get_id(), $link );
			$actions['delete']         = dey_display_action( 'delete', $this->object->get_id(), $link );

			echo wp_kses_post( implode( ' ', $actions ) );
		}

		/**
		 * Process duplicate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Scheduler rule ID.
		 * @return int|bool
		 */
		public function process_duplicate_post( $post_id ) {
			$scheduler_rule = dey_get_scheduler_rule( $post_id );

			return $scheduler_rule->exists() ? $scheduler_rule->duplicate() : false;
		}

		/**
		 * Process activate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 */
		public function process_activate_post( $post_id ) {
			$scheduler_rule = dey_get_scheduler_rule( $post_id );
			if ( ! $scheduler_rule->exists() ) {
				return;
			}

			// Update the scheduler rule status is active.
			dey_update_scheduler_rule( $post_id, array(), array( 'post_status' => 'dey_active' ) );
		}

		/**
		 * Process deactivate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 */
		public function process_deactivate_post( $post_id ) {
			$scheduler_rule = dey_get_scheduler_rule( $post_id );
			if ( ! $scheduler_rule->exists() ) {
				return;
			}

			// Update the scheduler rule status is In-active.
			dey_update_scheduler_rule( $post_id, array(), array( 'post_status' => 'dey_inactive' ) );
		}

		/**
		 * Handle custom filters.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		protected function query_filters( $query_vars ) {
			if ( isset( $_GET['dey_scheduler_type_filter'] ) && ! empty( $_GET['dey_scheduler_type_filter'] ) ) {
				$meta_query = array(
					array(
						'key'     => 'dey_scheduler_type',
						'value'   => wc_clean( wp_unslash( $_GET['dey_scheduler_type_filter'] ) ),
						'compare' => '==',
					),
				);

				$query_vars['meta_query'] = dey_check_is_array( $query_vars['meta_query'] ) ? array_merge( $query_vars['meta_query'], $meta_query ) : $meta_query;
			}

			return $query_vars;
		}

		/**
		 * Render the scheduler rule status meta box.
		 *
		 * @since 4.0.0
		 * @param object $post The post object.
		 * @global object $dey_scheduler_rule Scheduler rule object.
		 * @return void
		 */
		public function render_status_meta_box( $post ) {
			global $dey_scheduler_rule;

			$scheduler_rule_id  = isset( $post->ID ) ? $post->ID : '';
			$dey_scheduler_rule = dey_get_scheduler_rule( $scheduler_rule_id );

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-status.php';
		}

		/**
		 * Render the scheduler rule general settings meta box.
		 *
		 * @since 4.0.0
		 * @param object $post The post object.
		 * @global object $dey_scheduler_rule Scheduler rule object.
		 * @return void
		 */
		public function render_scheduler_rule_general_meta_box( $post ) {
			global $dey_scheduler_rule;

			$dey_scheduler_rule_id = isset( $post->ID ) ? $post->ID : '';
			$dey_scheduler_rule    = dey_get_scheduler_rule( $dey_scheduler_rule_id );

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-general-panels.php';
		}

		/**
		 * Render the scheduler rule meta box.
		 *
		 * @since 4.0.0
		 * @param object $post The post object.
		 * @global object $dey_scheduler_rule Scheduler rule object.
		 * @return void
		 */
		public function render_scheduler_rule_meta_box( $post ) {
			global $dey_scheduler_rule;

			$dey_scheduler_rule_id = isset( $post->ID ) ? $post->ID : '';
			$dey_scheduler_rule    = dey_get_scheduler_rule( $dey_scheduler_rule_id );

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-panels.php';
		}

		/**
		 * Render the panels content.
		 *
		 * @since 4.0.0
		 * @global object $dey_scheduler_rule Scheduler rule object.
		 * @return void
		 */
		private static function render_panels() {
			global $dey_scheduler_rule;

			$panels = array(
				'delivery',
				'local-pickup',
				'time-slots-general',
				'time-slots',
				'holiday',
				'special-day',
				'criteria',
			);

			$currency_symbol = ' (' . get_woocommerce_currency_symbol() . ')';

			// Render the panels content.
			foreach ( $panels as $panel ) {
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-' . $panel . '-panel.php';
			}
		}

		/**
		 * Get the panels.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		private static function get_panels() {
			/**
			 * This hook is used to alter the scheduler rule panels.
			 *
			 * @since 4.0.0
			 */
			$tabs = apply_filters(
				'dey_scheduler_rule_panels',
				array(
					'delivery'           => array(
						'label'      => __( 'Delivery', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_scheduler_rule_data_delivery',
						'class'      => array(),
						'priority'   => 10,
						'icon_class' => 'dashicons-admin-tools',
						'icon_url'   => DEY_PLUGIN_URL . '/assets/images/delivery.png',
					),
					'local_pickup'       => array(
						'label'    => __( 'Local Pickup', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_scheduler_rule_data_local_pickup',
						'class'    => array(),
						'priority' => 20,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/local-pickup.png',
					),
					'time_slots_general' => array(
						'label'    => __( 'Time Slots - General', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_scheduler_rule_data_time_slots_general',
						'class'    => array(),
						'priority' => 30,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/time-slot-general.png',
					),
					'time_slots'         => array(
						'label'    => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_scheduler_rule_data_time_slots',
						'class'    => array(),
						'priority' => 40,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/time-slot.png',
					),
					'holiday'            => array(
						'label'    => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_scheduler_rule_data_holiday',
						'class'    => array(),
						'priority' => 50,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/holiday.png',
					),
					'special_day'        => array(
						'label'    => __( 'Specific Dates', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_scheduler_rule_data_special_day',
						'class'    => array(),
						'priority' => 60,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/special-day.png',
					),
					'criteria'           => array(
						'label'      => __( 'Criteria', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_scheduler_rule_data_criteria',
						'class'      => array(),
						'priority'   => 70,
						'icon_class' => 'dashicons-admin-generic',
					),
				)
			);

			// Sort panels based on priority.
			uasort( $tabs, array( __CLASS__, 'sort_panels' ) );

			return $tabs;
		}

		/**
		 * Callback to sort panels on priority.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		private static function sort_panels( $a, $b ) {
			if ( ! isset( $a['priority'], $b['priority'] ) ) {
				return -1;
			}

			if ( $a['priority'] === $b['priority'] ) {
				return 0;
			}

			return $a['priority'] < $b['priority'] ? -1 : 1;
		}

		/**
		 * Save the meta box data.
		 *
		 * @since 4.0.0
		 * @param int    $post_id Post ID.
		 * @param object $post Post object.
		 * @return void
		 */
		public function save_current_meta_boxes( $post_id, $post ) {
			try {
				// Validate the scheduler rule data before save.
				$this->validate_scheduler_rule_before_save();

				$status    = isset( $_REQUEST['post_status'] ) ? wc_clean( wp_unslash( $_REQUEST['post_status'] ) ) : 'dey_inactive';
				$meta_args = array_merge(
					$this->prepare_general_panel_data(),
					$this->prepare_order_delivery_panel_data(),
					$this->prepare_order_pickup_panel_data(),
					$this->prepare_time_slots_panel_data( $post_id ),
					$this->prepare_holiday_panel_data(),
					$this->prepare_special_day_panel_data( $post_id ),
					$this->prepare_criteria_panel_data()
				);

				// Update the scheduler rule data.
				dey_update_scheduler_rule( $post_id, $meta_args, array( 'post_status' => $status ) );
			} catch ( Exception $ex ) {
				WC_Admin_Meta_Boxes::add_error( $ex->getMessage() );
			}
		}

		/**
		 * Validate the scheduler rule data before save.
		 *
		 * @since 4.2.0
		 * @throws Exception If it is not valid.
		 */
		public function validate_scheduler_rule_before_save() {
			$scheduler_type = isset( $_REQUEST['dey_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_type'] ) ) : '1';

			// Validate order delivery fields.
			if ( '2' !== $scheduler_type ) {
				$delivery_slot_mode = isset( $_REQUEST['dey_order_delivery_slot_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_slot_mode'] ) ) : '';

				switch ( $delivery_slot_mode ) {
					case '1': // Calendar mode.
						$delivery_days_availability = isset( $_REQUEST['dey_order_delivery_days_availability'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_days_availability'] ) ) : '';
						// Throw error, if the number of days availability is empty.
						if ( '' === $delivery_days_availability ) {
							throw new Exception( esc_html__( 'The Number of Days for Delivery Availability field must not be left blank.', 'delivery-slots-for-woocommerce' ) );
						}

						$delivery_time_mode = isset( $_REQUEST['dey_order_delivery_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_time_mode'] ) ) : '';
						switch ( $delivery_time_mode ) {
							case '2': // Time selectors.
								$delivery_available_time_from = isset( $_REQUEST['dey_order_delivery_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_available_time_from'] ) ) : '';
								$delivery_available_time_to   = isset( $_REQUEST['dey_order_delivery_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_available_time_to'] ) ) : '';
								// Throw error, if the time selector fields are empty.
								if ( '' === $delivery_available_time_from || '' === $delivery_available_time_to ) {
									throw new Exception( esc_html__( 'The Minimum or Maximum Time for the Order Delivery Time Selector must not be left blank.', 'delivery-slots-for-woocommerce' ) );
								}
								break;

							case '3': // Time slots.
								$time_slots_mode = isset( $_REQUEST['dey_scheduler_rule_time_slots_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_time_slots_mode'] ) ) : '';
								$time_slots      = isset( $_REQUEST['dey_scheduler_rule_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_time_slots'] ) ) : array();
								// Throw error, if the rule level time slots are empty.
								if ( '2' === $time_slots_mode && ! dey_check_is_array( $time_slots ) ) {
									throw new Exception( esc_html__( 'Time slots must not be left blank.', 'delivery-slots-for-woocommerce' ) );
								}
								break;
						}
						break;

					case '2': // Expected delivery mode.
						$delivery_expected_date_from = isset( $_REQUEST['dey_order_delivery_expected_date_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_expected_date_from'] ) ) : '';
						// Throw error, if the expected delivery date from field is empty.
						if ( '' === $delivery_expected_date_from ) {
							throw new Exception( esc_html__( 'Expected Delivery From field must not be left blank.', 'delivery-slots-for-woocommerce' ) );
						}

						$delivery_expected_date_to = isset( $_REQUEST['dey_order_delivery_expected_date_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_expected_date_to'] ) ) : '';
						// Throw error, if the expected delivery date to field is empty.
						if ( '' === $delivery_expected_date_to ) {
							throw new Exception( esc_html__( 'Expected Delivery To field must not be left blank.', 'delivery-slots-for-woocommerce' ) );
						}
						break;
				}
			}

			// Validate order local pickup fields.
			if ( '1' !== $scheduler_type ) {
				$pickup_days_availability = isset( $_REQUEST['dey_order_pickup_days_availability'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_days_availability'] ) ) : '';
				// Throw error, if the number of days availability is empty.
				if ( '' === $pickup_days_availability ) {
					throw new Exception( esc_html__( 'The Number of Days for Pickup Availability field must not be left blank.', 'delivery-slots-for-woocommerce' ) );
				}

				$pickup_time_mode = isset( $_REQUEST['dey_order_pickup_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_mode'] ) ) : '';
				switch ( $pickup_time_mode ) {
					case '2': // Time selectors.
						$pickup_available_time_from = isset( $_REQUEST['dey_order_pickup_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_available_time_from'] ) ) : '';
						$pickup_available_time_to   = isset( $_REQUEST['dey_order_pickup_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_available_time_to'] ) ) : '';
						// Throw error, if the time selector fields are empty.
						if ( '' === $pickup_available_time_from || '' === $pickup_available_time_to ) {
							throw new Exception( esc_html__( 'The Minimum or Maximum Time for the Order Pickup Time Selector must not be left blank.', 'delivery-slots-for-woocommerce' ) );
						}
						break;

					case '3': // Time slots.
						$time_slots_mode = isset( $_REQUEST['dey_scheduler_rule_time_slots_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_time_slots_mode'] ) ) : '';
						$time_slots      = isset( $_REQUEST['dey_scheduler_rule_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_time_slots'] ) ) : array();
						// Throw error, if the rule level time slots are empty.
						if ( '2' === $time_slots_mode && ! dey_check_is_array( $time_slots ) ) {
							throw new Exception( esc_html__( 'Time slots must not be left blank.', 'delivery-slots-for-woocommerce' ) );
						}
						break;
				}
			}
		}

		/**
		 * Prepare the scheduler rule general panel data.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function prepare_general_panel_data() {
			return array(
				'dey_scheduler_type'   => isset( $_REQUEST['dey_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_type'] ) ) : '1',
				'dey_shipping_methods' => isset( $_REQUEST['dey_order_delivery_shipping_methods'] ) ? array_filter( wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_shipping_methods'] ) ) ) : array(),
				'dey_start_date'       => isset( $_REQUEST['dey_scheduler_rule_start_date'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_start_date'] ) ) : '',
				'dey_end_date'         => isset( $_REQUEST['dey_scheduler_rule_end_date'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_end_date'] ) ) : '',
				'dey_priority'         => isset( $_REQUEST['dey_scheduler_rule_priority'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_priority'] ) ) : '',
				'dey_description'      => isset( $_REQUEST['dey_scheduler_rule_description'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_description'] ) ) : '',
			);
		}

		/**
		 * Prepare the order delivery panel data.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function prepare_order_delivery_panel_data() {
			return array(
				'dey_delivery_slot_mode'                 => isset( $_REQUEST['dey_order_delivery_slot_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_slot_mode'] ) ) : '',
				'dey_delivery_days_availability'         => isset( $_REQUEST['dey_order_delivery_days_availability'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_days_availability'] ) ) : '',
				'dey_delivery_expected_date_from'        => isset( $_REQUEST['dey_order_delivery_expected_date_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_expected_date_from'] ) ) : '',
				'dey_delivery_expected_date_to'          => isset( $_REQUEST['dey_order_delivery_expected_date_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_expected_date_to'] ) ) : '',
				'dey_delivery_days'                      => isset( $_REQUEST['dey_order_delivery_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_days'] ) ) : array(),
				'dey_delivery_max_per_day'               => isset( $_REQUEST['dey_order_delivery_max_per_day'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_max_per_day'] ) ) : '',
				'dey_delivery_calender_required'         => isset( $_REQUEST['dey_order_delivery_calender_required'] ) ? 'yes' : 'no',
				'dey_delivery_time_mode'                 => isset( $_REQUEST['dey_order_delivery_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_time_mode'] ) ) : '',
				'dey_delivery_available_time_from'       => isset( $_REQUEST['dey_order_delivery_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_available_time_from'] ) ) : '',
				'dey_delivery_available_time_to'         => isset( $_REQUEST['dey_order_delivery_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_available_time_to'] ) ) : '',
				'dey_delivery_time_slot_required'        => isset( $_REQUEST['dey_order_delivery_time_slot_required'] ) ? 'yes' : 'no',
				'dey_delivery_as_soon_as_possible'       => isset( $_REQUEST['dey_order_delivery_as_soon_as_possible'] ) ? 'yes' : 'no',
				'dey_delivery_first_available_time_slot' => isset( $_REQUEST['dey_order_delivery_first_available_time_slot'] ) ? 'yes' : 'no',
				'dey_delivery_time_slot_hide_zero_price' => isset( $_REQUEST['dey_order_delivery_time_slot_hide_zero_price'] ) ? 'yes' : 'no',
				'dey_delivery_time_slot_max'             => isset( $_REQUEST['dey_order_delivery_time_slot_max'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_time_slot_max'] ) ) : '',
				'dey_delivery_processing_time'           => isset( $_REQUEST['dey_order_delivery_processing_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_processing_time'] ) ) : array(
					'number' => '',
					'unit'   => 'hours',
				),
				'dey_delivery_same_day_cutoff_time'      => isset( $_REQUEST['dey_order_delivery_same_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_same_day_cutoff_time'] ) ) : '',
				'dey_delivery_next_day_cutoff_time'      => isset( $_REQUEST['dey_order_delivery_next_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_next_day_cutoff_time'] ) ) : '',
				'dey_delivery_weekdays_prices'           => $this->prepare_weekdays_prices( 'order_delivery' ),
				'dey_delivery_same_day_fee'              => isset( $_REQUEST['dey_order_delivery_same_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_same_day_fee'] ) ) : '',
				'dey_delivery_next_day_fee'              => isset( $_REQUEST['dey_order_delivery_next_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_next_day_fee'] ) ) : '',
				'dey_delivery_calculate_tax'             => isset( $_REQUEST['dey_order_delivery_calculate_tax'] ) ? 'yes' : 'no',
			);
		}

		/**
		 * Prepare the order local pickup panel data.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function prepare_order_pickup_panel_data() {
			return array(
				'dey_pickup_days_availability'         => isset( $_REQUEST['dey_order_pickup_days_availability'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_days_availability'] ) ) : '',
				'dey_pickup_days'                      => isset( $_REQUEST['dey_order_pickup_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_days'] ) ) : array(),
				'dey_pickup_max_per_day'               => isset( $_REQUEST['dey_order_pickup_max_per_day'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_max_per_day'] ) ) : '',
				'dey_pickup_location_required'         => isset( $_REQUEST['dey_order_pickup_location_required'] ) ? 'yes' : 'no',
				'dey_pickup_calender_required'         => isset( $_REQUEST['dey_order_pickup_calender_required'] ) ? 'yes' : 'no',
				'dey_pickup_time_mode'                 => isset( $_REQUEST['dey_order_pickup_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_mode'] ) ) : '',
				'dey_pickup_time_slot_required'        => isset( $_REQUEST['dey_order_pickup_time_slot_required'] ) ? 'yes' : 'no',
				'dey_pickup_as_soon_as_possible'       => isset( $_REQUEST['dey_order_pickup_as_soon_as_possible'] ) ? 'yes' : 'no',
				'dey_pickup_first_available_time_slot' => isset( $_REQUEST['dey_order_pickup_first_available_time_slot'] ) ? 'yes' : 'no',
				'dey_pickup_time_slot_hide_zero_price' => isset( $_REQUEST['dey_order_pickup_time_slot_hide_zero_price'] ) ? 'yes' : 'no',
				'dey_pickup_available_time_from'       => isset( $_REQUEST['dey_order_pickup_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_available_time_from'] ) ) : '',
				'dey_pickup_available_time_to'         => isset( $_REQUEST['dey_order_pickup_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_available_time_to'] ) ) : '',
				'dey_pickup_time_slot_max'             => isset( $_REQUEST['dey_order_pickup_time_slot_max'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_slot_max'] ) ) : '',
				'dey_pickup_processing_time'           => isset( $_REQUEST['dey_order_pickup_processing_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_processing_time'] ) ) : array(
					'number' => '',
					'unit'   => 'hours',
				),
				'dey_pickup_same_day_cutoff_time'      => isset( $_REQUEST['dey_order_pickup_same_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_same_day_cutoff_time'] ) ) : '',
				'dey_pickup_next_day_cutoff_time'      => isset( $_REQUEST['dey_order_pickup_next_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_next_day_cutoff_time'] ) ) : '',
				'dey_pickup_weekdays_prices'           => $this->prepare_weekdays_prices( 'order_pickup' ),
				'dey_pickup_same_day_fee'              => isset( $_REQUEST['dey_order_pickup_same_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_same_day_fee'] ) ) : '',
				'dey_pickup_next_day_fee'              => isset( $_REQUEST['dey_order_pickup_next_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_next_day_fee'] ) ) : '',
				'dey_pickup_calculate_tax'             => isset( $_REQUEST['dey_order_pickup_calculate_tax'] ) ? 'yes' : 'no',
			);
		}

		/**
		 * Prepare the weekdays prices
		 *
		 * @since 4.0.0
		 * @param string $order_scheduler_type Order scheduler type.
		 * @return array
		 */
		public function prepare_weekdays_prices( $order_scheduler_type ) {
			if ( 'order_pickup' === $order_scheduler_type ) {
				$weekdays_prices = isset( $_REQUEST['dey_order_pickup_weekdays_prices'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_weekdays_prices'] ) ) : array();
			} else {
				$weekdays_prices = isset( $_REQUEST['dey_order_delivery_weekdays_prices'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_weekdays_prices'] ) ) : array();
			}

			if ( ! dey_check_is_array( $weekdays_prices ) ) {
				return array();
			}

			$formatted_weekdays_prices = array();
			foreach ( $weekdays_prices as $key => $value ) {
				$formatted_weekdays_prices[ $key ] = wc_format_decimal( $value );
			}

			return $formatted_weekdays_prices;
		}

		/**
		 * Prepare the time slots panel data.
		 *
		 * @since 4.0.0
		 * @param int $scheduler_rule_id Scheduler rule ID.
		 * @return array
		 */
		public function prepare_time_slots_panel_data( $scheduler_rule_id ) {
			$existing_time_slots  = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_time_slots', true ) );
			$time_slots           = isset( $_REQUEST['dey_scheduler_rule_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_time_slots'] ) ) : array();
			$formatted_time_slots = array();
			if ( dey_check_is_array( $time_slots ) ) {
				foreach ( $time_slots as $key => $time_slot ) {
					if ( ! dey_check_is_array( $time_slot ) ) {
						continue;
					}

					$time_slot['week_days'] = isset( $time_slot['week_days'] ) ? $time_slot['week_days'] : array();
					$time_slot['price']     = isset( $time_slot['price'] ) ? wc_format_decimal( $time_slot['price'] ) : '';
					if ( isset( $existing_time_slots[ $key ] ) && isset( $existing_time_slots[ $key ]['used_order_count'] ) && dey_check_is_array( $existing_time_slots[ $key ]['used_order_count'] ) ) {
						$time_slot['used_order_count'] = $existing_time_slots[ $key ]['used_order_count'];
					} else {
						$time_slot['used_order_count'] = array();
					}

					$formatted_time_slots[ $key ] = $time_slot;
				}
			}

			return array(
				'dey_time_slots_mode' => isset( $_REQUEST['dey_scheduler_rule_time_slots_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_time_slots_mode'] ) ) : '',
				'dey_time_slots'      => $formatted_time_slots,
			);
		}

		/**
		 * Prepare the holiday panel data.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function prepare_holiday_panel_data() {
			$holidays           = isset( $_REQUEST['dey_scheduler_rule_holidays'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_holidays'] ) ) : array();
			$formatted_holidays = array();
			if ( dey_check_is_array( $holidays ) ) {
				foreach ( $holidays as $key => $holiday ) {
					if ( ! dey_check_is_array( $holiday ) ) {
						continue;
					}

					$holiday['recurring']       = ( isset( $holiday['recurring'] ) ) ? 'yes' : 'no';
					$formatted_holidays[ $key ] = $holiday;
				}
			}

			return array(
				'dey_holidays_mode' => isset( $_REQUEST['dey_scheduler_rule_holidays_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_holidays_mode'] ) ) : '',
				'dey_holidays'      => $formatted_holidays,
			);
		}

		/**
		 * Prepare the special day panel data.
		 *
		 * @since 4.0.0
		 * @param int $scheduler_rule_id Scheduler rule ID.
		 * @return array
		 */
		public function prepare_special_day_panel_data( $scheduler_rule_id ) {
			$existing_special_days  = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_special_days', true ) );
			$special_days           = isset( $_REQUEST['dey_special_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_special_days'] ) ) : array();
			$formatted_special_days = array();
			if ( dey_check_is_array( $special_days ) ) {
				foreach ( $special_days as $key => $special_day ) {
					if ( ! dey_check_is_array( $special_day ) ) {
						continue;
					}

					$special_day['price'] = ( isset( $special_day['price'] ) ) ? wc_format_decimal( $special_day['price'] ) : '';
					if ( isset( $existing_special_days[ $key ] ) && isset( $existing_special_days[ $key ]['used_order_count'] ) ) {
						$special_day['used_order_count'] = $existing_special_days[ $key ]['used_order_count'];
					} else {
						$special_day['used_order_count'] = 0;
					}

					$formatted_special_days[ $key ] = $special_day;
				}
			}

			return array(
				'dey_special_days_mode' => isset( $_REQUEST['dey_scheduler_rule_special_days_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_scheduler_rule_special_days_mode'] ) ) : '',
				'dey_special_days'      => $formatted_special_days,
			);
		}

		/**
		 * Prepare the criteria panel data.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function prepare_criteria_panel_data() {
			return array( 'dey_restriction_rule_groups' => isset( $_REQUEST['dey_restriction_rule_groups'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_restriction_rule_groups'] ) ) : array() );
		}

		/**
		 * Display the footer contents.
		 *
		 * @since 4.0.0
		 */
		public function footer_content() {
			require_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/popup/html-scheduler-rule.php';
		}
	}
}
