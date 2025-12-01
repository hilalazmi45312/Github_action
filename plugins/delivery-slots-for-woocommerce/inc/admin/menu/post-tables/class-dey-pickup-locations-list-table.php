<?php
/**
 * Pickup locations list table.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-post-list-table.php';
}

if ( ! class_exists( 'DEY_Pickup_Locations_List_Table' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Pickup_Locations_List_Table extends DEY_Post_List_Table {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::PICKUP_LOCATIONS_POSTTYPE;

		/**
		 * Plugin Slug.
		 *
		 * @var string
		 */
		protected $plugin_slug = 'dey';

		/**
		 * Class initialization.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Add meta boxes for this post type.
		 *
		 * @return void
		 */
		public function add_meta_boxes() {
			// Remove the publish meta box.
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
			// Scheduler rule status meta box.
			add_meta_box( 'dey-pickup-location-status-data', __( 'Status', 'delivery-slots-for-woocommerce' ), array( $this, 'render_status_meta_box' ), $this->post_type, 'side', 'high' );
			// Pickup locations data meta box.
			add_meta_box( 'dey-pickup-locations-data', __( 'Settings', 'delivery-slots-for-woocommerce' ), array( $this, 'render_pickup_location_general_meta_box' ), $this->post_type, 'normal', 'high' );
			// Order pickup scheduler data meta box.
			add_meta_box( 'dey-order-pickup-scheduler-data', __( 'Pickup Scheduler', 'delivery-slots-for-woocommerce' ), array( $this, 'render_pickup_location_meta_box' ), $this->post_type, 'normal', 'high' );

			/**
			 * This hook is used to add custom meta boxes for pickup locations post type.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_add_meta_boxes_' . $this->post_type );
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

			$columns['cb']           = '<input type="checkbox" />';
			$columns['title']        = __( 'Name', 'delivery-slots-for-woocommerce' );
			$columns['email_list']   = __( 'Email List', 'delivery-slots-for-woocommerce' );
			$columns['country']      = __( 'Country', 'delivery-slots-for-woocommerce' );
			$columns['address']      = __( 'Address', 'delivery-slots-for-woocommerce' );
			$columns['city']         = __( 'City', 'delivery-slots-for-woocommerce' );
			$columns['pincode']      = __( 'Pincode', 'delivery-slots-for-woocommerce' );
			$columns['phone_number'] = __( 'Phone Number', 'delivery-slots-for-woocommerce' );
			$columns['pickup_mode']  = __( 'Scheduler Consideration Mode', 'delivery-slots-for-woocommerce' );
			$columns['status']       = __( 'Status', 'delivery-slots-for-woocommerce' );
			$columns['created_date'] = __( 'Date', 'delivery-slots-for-woocommerce' );
			$columns['actions']      = __( 'Actions', 'delivery-slots-for-woocommerce' );

			return $columns;
		}

		/**
		 * Define primary column.
		 *
		 * @return array
		 */
		protected function get_primary_column() {
			return 'title';
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

			$actions['activate']   = __( 'Activate', 'delivery-slots-for-woocommerce' );
			$actions['deactivate'] = __( 'Deactivate', 'delivery-slots-for-woocommerce' );
			$actions['delete']     = __( 'Delete', 'delivery-slots-for-woocommerce' );

			return $actions;
		}

		/**
		 * Handle the bulk actions.
		 *
		 * @since 4.0.0
		 * @param string $redirect_to Redirect to URL.
		 * @param string $action Action to be executed.
		 * @param array  $post_ids Pickup location IDs.
		 * @return string|URL
		 */
		public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
			switch ( $action ) {
				case 'activate':
					$activated = 0;
					$locked    = 0;

					foreach ( (array) $post_ids as $post_id ) {
						if ( ! current_user_can( 'delete_post', $post_id ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to move this item to the Trash.' ) );
						}

						if ( wp_check_post_lock( $post_id ) ) {
							++$locked;
							continue;
						}

						dey_update_pickup_location( $post_id, array(), array( 'post_status' => 'dey_active' ) );

						++$activated;
					}

					$redirect_to = add_query_arg(
						array(
							'activated' => $activated,
							'ids'       => join( ',', $post_ids ),
							'locked'    => $locked,
						),
						$redirect_to
					);
					break;

				case 'deactivate':
					$deactivated = 0;
					$locked      = 0;

					foreach ( (array) $post_ids as $post_id ) {
						if ( ! current_user_can( 'delete_post', $post_id ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to move this item to the Trash.' ) );
						}

						if ( wp_check_post_lock( $post_id ) ) {
							++$locked;
							continue;
						}

						dey_update_pickup_location( $post_id, array(), array( 'post_status' => 'dey_inactive' ) );

						++$deactivated;
					}

					$redirect_to = add_query_arg(
						array(
							'deactivated' => $deactivated,
							'ids'         => join( ',', $post_ids ),
							'locked'      => $locked,
						),
						$redirect_to
					);
					break;
			}

			return esc_url_raw( $redirect_to );
		}

		/**
		 * Disable the month drop down.
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
			global $post;

			$messages[ $this->post_type ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Pickup Location updated.', 'delivery-slots-for-woocommerce' ),
				4  => __( 'Pickup Location updated.', 'delivery-slots-for-woocommerce' ),
				6  => __( 'Pickup Location published.', 'delivery-slots-for-woocommerce' ),
				7  => __( 'Pickup Location saved.', 'delivery-slots-for-woocommerce' ),
				10 => __( 'Pickup Location draft updated.', 'delivery-slots-for-woocommerce' ),
			);

			return $messages;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ $this->post_type ] = array(
				/* translators: %s: pickup location count */
				'updated' => _n( '%s pickup location updated.', '%s pickup locations updated.', $bulk_counts['updated'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: pickup location count */
				'locked'  => _n( '%s pickup location not updated, somebody is editing it.', '%s pickup locations not updated, somebody is editing them.', $bulk_counts['locked'], 'delivery-slots-for-woocommerce' ),
				/* translators: %s: pickup location count */
				'deleted' => _n( '%s pickup location permanently deleted.', '%s pickup locations permanently deleted.', $bulk_counts['deleted'], 'delivery-slots-for-woocommerce' ),
			);

			return $bulk_messages;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = dey_get_pickup_location( $post_id );
			}
		}

		/**
		 * Render the email list column.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_email_list_column() {
			echo wp_kses_post( $this->object->get_email_lists() );
		}

		/**
		 * Render the country column.
		 *
		 * @return void
		 */
		public function render_country_column() {
			echo wp_kses_post( $this->object->get_country() );
		}

		/**
		 * Render the address column.
		 *
		 * @return void
		 */
		public function render_address_column() {
			$address  = '<address>' . $this->object->get_address1() . '<br>';
			$address .= $this->object->get_address2() . '<br>';
			$address .= '</address>';

			echo wp_kses_post( $address );
		}

		/**
		 * Render the city column.
		 *
		 * @return void
		 */
		public function render_city_column() {
			echo wp_kses_post( $this->object->get_city() );
		}

		/**
		 * Render the pin code column.
		 *
		 * @return void
		 */
		public function render_pincode_column() {
			echo wp_kses_post( $this->object->get_pincode() );
		}

		/**
		 * Render the phone number column.
		 *
		 * @return void
		 */
		public function render_phone_number_column() {
			echo wp_kses_post( $this->object->get_phone_number() );
		}

		/**
		 * Render the scheduler consideration mode.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function render_pickup_mode_column() {
			if ( '2' === $this->object->get_pickup_mode() ) {
				esc_html_e( 'Pickup Location', 'delivery-slots-for-woocommerce' );
			} else {
				esc_html_e( 'Scheduler Rule', 'delivery-slots-for-woocommerce' );
			}
		}

		/**
		 * Render the status column.
		 *
		 * @since 1.0.0
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
		 * Duplicate the pickup location.
		 *
		 * @since 4.0.0
		 * @param int $post_id Pickup location ID.
		 * @return int|bool
		 */
		public function process_duplicate_post( $post_id ) {
			$pickup_location = dey_get_pickup_location( $post_id );

			return $pickup_location->exists() ? $pickup_location->duplicate() : false;
		}

		/**
		 * Activate the pickup location.
		 *
		 * @since 4.0.0
		 * @param int $post_id Pickup location ID.
		 * @return void
		 */
		public function process_activate_post( $post_id ) {
			$pickup_location = dey_get_pickup_location( $post_id );
			if ( ! $pickup_location->exists() ) {
				return;
			}

			// Update the pickup location status is active.
			dey_update_pickup_location( $post_id, array(), array( 'post_status' => 'dey_active' ) );
		}

		/**
		 * Deactivate the pickup location.
		 *
		 * @since 4.0.0
		 * @param int $post_id Pickup location ID.
		 * @return void
		 */
		public function process_deactivate_post( $post_id ) {
			$pickup_location = dey_get_pickup_location( $post_id );
			if ( ! $pickup_location->exists() ) {
				return;
			}

			// Update the pickup location status is Inactive.
			dey_update_pickup_location( $post_id, array(), array( 'post_status' => 'dey_inactive' ) );
		}

		/**
		 * Render the pickup location status meta box.
		 *
		 * @since 4.0.0
		 * @param object $post The post object.
		 * @global object $dey_pickup_location Pickup location object.
		 * @return void
		 */
		public function render_status_meta_box( $post ) {
			global $dey_pickup_location;

			$pickup_location_id  = ( isset( $post->ID ) ) ? $post->ID : '';
			$dey_pickup_location = dey_get_pickup_location( $pickup_location_id );

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-status.php';
		}

		/**
		 * Render the pickup location general settings meta box.
		 *
		 * @return void
		 */
		public function render_pickup_location_general_meta_box( $post ) {
			global $dey_pickup_location;

			$pickup_location_id  = ( isset( $post->ID ) ) ? $post->ID : '';
			$dey_pickup_location = dey_get_pickup_location( $pickup_location_id );

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-general-panels.php';
		}

		/**
		 * Render the pickup location meta box.
		 *
		 * @since 4.0.0
		 * @param object $post Post object.
		 * @return void
		 */
		public function render_pickup_location_meta_box( $post ) {
			global $dey_pickup_location;

			$pickup_location_id  = ( isset( $post->ID ) ) ? $post->ID : '';
			$dey_pickup_location = dey_get_pickup_location( $pickup_location_id );

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-panels.php';
		}

		/**
		 * Render the panels content.
		 */
		private static function render_general_panels() {
			global $dey_pickup_location;

			$panels = array(
				'general',
				'criteria',
				'email-lists',
			);

			// Render the panels content.
			foreach ( $panels as $panel ) {
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-' . $panel . '-panel.php';
			}
		}

		/**
		 * Render the order pickup location panels content.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		private static function render_panels() {
			global $dey_pickup_location;

			$panels = array(
				'local-pickup',
				'time-slots',
				'holiday',
				'special-day',
			);

			$currency_symbol = ' (' . get_woocommerce_currency_symbol() . ')';

			// Render the order pickup scheduler panels content.
			foreach ( $panels as $panel ) {
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-' . $panel . '-panel.php';
			}
		}

		/**
		 * Get the panels.
		 *
		 * @return array
		 */
		private static function get_settings_panels() {
			/**
			 * This hook is used to alter the pickup locations panels.
			 *
			 * @since 1.0
			 */
			$tabs = apply_filters(
				'dey_pickup_locations_panels',
				array(
					'general'     => array(
						'label'      => __( 'General', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_pickup_location_data_general',
						'class'      => array(),
						'priority'   => 10,
						'icon_class' => 'dashicons-admin-tools',
					),
					'criteria'    => array(
						'label'      => __( 'Criteria', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_pickup_location_data_criteria',
						'class'      => array(),
						'priority'   => 20,
						'icon_class' => 'dashicons-editor-table',
					),
					'email_lists' => array(
						'label'      => __( 'Email Lists', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_pickup_location_data_email_lists',
						'class'      => array(),
						'priority'   => 30,
						'icon_class' => 'dashicons-editor-table',
					),
				)
			);

			// Sort panels based on priority.
			uasort( $tabs, array( __CLASS__, 'sort_panels' ) );

			return $tabs;
		}

		/**
		 * Get the order pickup scheduler panels.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		private static function get_order_pickup_scheduler_panels() {
			/**
			 * This hook is used to alter the order pickup scheduler panels.
			 *
			 * @since 4.0.0
			 */
			$tabs = apply_filters(
				'dey_order_pickup_scheduler_panels',
				array(
					'local_pickup' => array(
						'label'    => __( 'Local Pickup', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_order_pickup_scheduler_data_local_pickup',
						'class'    => array(),
						'priority' => 10,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/local-pickup.png',
					),
					'time_slots'   => array(
						'label'    => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_order_pickup_scheduler_data_time_slots',
						'class'    => array(),
						'priority' => 20,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/time-slot.png',
					),
					'holiday'      => array(
						'label'    => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_order_pickup_scheduler_data_holiday',
						'class'    => array(),
						'priority' => 30,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/holiday.png',
					),
					'special_day'  => array(
						'label'    => __( 'Special day', 'delivery-slots-for-woocommerce' ),
						'target'   => 'dey_order_pickup_scheduler_data_special_day',
						'class'    => array(),
						'priority' => 40,
						'icon_url' => DEY_PLUGIN_URL . '/assets/images/special-day.png',
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
		 * @return void
		 */
		public function save_current_meta_boxes( $post_id, $post ) {
			try {
				// Validate the pickup location data before save.
				$this->validate_pickup_location_before_save();

				$status    = isset( $_REQUEST['post_status'] ) ? wc_clean( wp_unslash( $_REQUEST['post_status'] ) ) : 'dey_inactive';
				$meta_args = array_merge(
					array( 'dey_pickup_mode' => isset( $_REQUEST['dey_pickup_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_mode'] ) ) : '1' ),
					$this->prepare_general_panel_data(),
					$this->prepare_filters_panel_data(),
					$this->prepare_email_lists_panel_data(),
					$this->prepare_order_pickup_panel_data(),
					$this->prepare_time_slots_panel_data( $post_id ),
					$this->prepare_holiday_panel_data(),
					$this->prepare_special_day_panel_data( $post_id ),
					$this->prepare_criteria_panel_data()
				);

				// Update the pickup location data.
				dey_update_pickup_location( $post_id, $meta_args, array( 'post_status' => $status ) );
			} catch ( Exception $ex ) {
				WC_Admin_Meta_Boxes::add_error( $ex->getMessage() );
			}
		}

		/**
		 * Validate the pickup location data before save.
		 *
		 * @since 4.2.0
		 * @throws Exception If it is not valid.
		 */
		public function validate_pickup_location_before_save() {
			$pickup_mode = isset( $_REQUEST['dey_pickup_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_mode'] ) ) : '1';
			// Return if it is global level pickup date consideration type.
			if ( '2' !== $pickup_mode ) {
				return;
			}

			$pickup_days_availability = isset( $_REQUEST['dey_order_pickup_days_availability'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_days_availability'] ) ) : '';
			// Throw error, if the number of days availability is empty.
			if ( '' === $pickup_days_availability ) {
				throw new Exception( esc_html__( 'The Number of Days for Pickup Availability field must not be left blank.', 'delivery-slots-for-woocommerce' ) );
			}

			$pickup_time_mode           = isset( $_REQUEST['dey_order_pickup_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_mode'] ) ) : '';
			$pickup_available_time_from = isset( $_REQUEST['dey_order_pickup_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_available_time_from'] ) ) : '';
			$pickup_available_time_to   = isset( $_REQUEST['dey_order_pickup_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_available_time_to'] ) ) : '';

			// Throw error, if the time selector fields are empty.
			if ( '2' === $pickup_time_mode && ( '' === $pickup_available_time_from || '' === $pickup_available_time_to ) ) {
				throw new Exception( esc_html__( 'The Minimum or Maximum Time for the Pickup Time Selector must not be left blank.', 'delivery-slots-for-woocommerce' ) );
			}

			$time_slots_mode = isset( $_REQUEST['dey_order_pickup_time_slots_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_slots_mode'] ) ) : '';
			$time_slots      = isset( $_REQUEST['dey_order_pickup_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_slots'] ) ) : array();

			// Throw error, if the rule level time slots are empty.
			if ( '3' === $pickup_time_mode && '2' === $time_slots_mode && ! dey_check_is_array( $time_slots ) ) {
				throw new Exception( esc_html__( 'Time slots must not be left blank.', 'delivery-slots-for-woocommerce' ) );
			}
		}

		/**
		 * Prepare the general panel data.
		 *
		 * @return array
		 */
		private function prepare_general_panel_data() {
			$address1     = isset( $_REQUEST['dey_address1'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_address1'] ) ) : '';
			$address2     = isset( $_REQUEST['dey_address2'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_address2'] ) ) : '';
			$city         = isset( $_REQUEST['dey_city'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_city'] ) ) : '';
			$country      = isset( $_REQUEST['dey_country'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_country'] ) ) : '';
			$pincode      = isset( $_REQUEST['dey_pincode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pincode'] ) ) : '';
			$phone_number = isset( $_REQUEST['dey_phone_number'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_phone_number'] ) ) : '';

			return array(
				'dey_address1'     => $address1,
				'dey_address2'     => $address2,
				'dey_city'         => $city,
				'dey_country'      => $country,
				'dey_pincode'      => $pincode,
				'dey_phone_number' => $phone_number,
			);
		}

		/**
		 * Prepare the filters panel data.
		 *
		 * @return array
		 */
		private function prepare_filters_panel_data() {
			$formatted_rule_group = array();
			$rule_group           = isset( $_REQUEST['dey_filter_groups'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_filter_groups'] ) ) : array();
			if ( dey_check_is_array( $rule_group ) ) {
				foreach ( $rule_group as $rules ) {
					if ( ! dey_check_is_array( $rules ) ) {
						continue;
					}

					$formatted_rule_group[] = array_merge( $rules );
				}
			}

			return array( 'dey_filter_groups' => $formatted_rule_group );
		}

		/**
		 * Prepare the email lists panel data.
		 *
		 * @return array
		 */
		private function prepare_email_lists_panel_data() {
			$email_lists = isset( $_REQUEST['dey_email_lists'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_email_lists'] ) ) : '';

			return array( 'dey_email_lists' => $email_lists );
		}

		/**
		 * Prepare the order pickup panel data.
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
				'dey_pickup_weekdays_prices'           => $this->prepare_weekdays_prices(),
				'dey_pickup_same_day_fee'              => isset( $_REQUEST['dey_order_pickup_same_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_same_day_fee'] ) ) : '',
				'dey_pickup_next_day_fee'              => isset( $_REQUEST['dey_order_pickup_next_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_next_day_fee'] ) ) : '',
				'dey_pickup_calculate_tax'             => isset( $_REQUEST['dey_order_pickup_calculate_tax'] ) ? 'yes' : 'no',
			);
		}

		/**
		 * Prepare the weekdays prices
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public function prepare_weekdays_prices() {
			$weekdays_prices = isset( $_REQUEST['dey_order_pickup_weekdays_prices'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_weekdays_prices'] ) ) : array();
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
		 * @param int $pickup_location_id Pickup location ID.
		 * @return array
		 */
		public function prepare_time_slots_panel_data( $pickup_location_id ) {
			$existing_time_slots  = array_filter( (array) get_post_meta( $pickup_location_id, 'dey_time_slots', true ) );
			$time_slots           = isset( $_REQUEST['dey_order_pickup_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_slots'] ) ) : array();
			$formatted_time_slots = array();
			if ( dey_check_is_array( $time_slots ) ) {
				foreach ( $time_slots as $key => $time_slot ) {
					if ( ! dey_check_is_array( $time_slot ) ) {
						continue;
					}

					$time_slot['week_days'] = ( isset( $time_slot['week_days'] ) ) ? $time_slot['week_days'] : array();
					$time_slot['price']     = ( isset( $time_slot['price'] ) ) ? wc_format_decimal( $time_slot['price'] ) : '';
					if ( isset( $existing_time_slots[ $key ] ) && isset( $existing_time_slots[ $key ]['used_order_count'] ) && dey_check_is_array( $existing_time_slots[ $key ]['used_order_count'] ) ) {
						$time_slot['used_order_count'] = $existing_time_slots[ $key ]['used_order_count'];
					} else {
						$time_slot['used_order_count'] = array();
					}

					$formatted_time_slots[ $key ] = $time_slot;
				}
			}

			return array(
				'dey_time_slots_mode' => isset( $_REQUEST['dey_order_pickup_time_slots_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_time_slots_mode'] ) ) : '',
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
			$holidays = isset( $_REQUEST['dey_order_pickup_holidays'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_holidays'] ) ) : array();
			if ( ! dey_check_is_array( $holidays ) ) {
				return array();
			}

			$formatted_holidays = array();
			foreach ( $holidays as $key => $holiday ) {
				if ( ! dey_check_is_array( $holiday ) ) {
					continue;
				}

				$holiday['recurring']       = ( isset( $holiday['recurring'] ) ) ? 'yes' : 'no';
				$formatted_holidays[ $key ] = $holiday;
			}

			return array(
				'dey_holidays_mode' => isset( $_REQUEST['dey_order_pickup_holidays_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_holidays_mode'] ) ) : '',
				'dey_holidays'      => $formatted_holidays,
			);
		}

		/**
		 * Prepare the special day panel data.
		 *
		 * @since 4.0.0
		 * @param int $pickup_location_id Pickup location ID.
		 * @return array
		 */
		public function prepare_special_day_panel_data( $pickup_location_id ) {
			$existing_special_days  = array_filter( (array) get_post_meta( $pickup_location_id, 'dey_special_days', true ) );
			$special_days           = isset( $_REQUEST['dey_order_pickup_special_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_special_days'] ) ) : array();
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
				'dey_special_days_mode' => isset( $_REQUEST['dey_order_pickup_special_days_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_pickup_special_days_mode'] ) ) : '',
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
	}

}
