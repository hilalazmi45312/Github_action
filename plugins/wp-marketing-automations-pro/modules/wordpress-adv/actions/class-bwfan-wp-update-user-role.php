<?php

final class BWFAN_WP_Update_User_Role extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Update User Role', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action updates the role of user', 'wp-marketing-automations-pro' );
		$this->action_priority = 10;

		$this->required_fields = array( 'email', 'user_role' );
		$this->support_v2      = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'user_roles_options', $data );
		}
	}

	public function get_view_data() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		$roles = get_editable_roles();
		if ( empty( $roles ) ) {
			return array();
		}

		$roles = array_map( function ( $role ) {
			return $role['name'];
		}, $roles );

		return $roles;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <#
            selected_user_role = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'user_role')) ? data.actionSavedData.data.user_role : '';
            selected_user_role_action = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'user_role_action')) ? data.actionSavedData.data.user_role_action : 'update';
            #>
            <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Select User Role', 'wp-marketing-automations-pro' ); ?></label>
            <select required id="" class="bwfan-input-wrapper bwfan-single-select" name="bwfan[{{data.action_id}}][data][user_role]">
                <option value=""><?php echo esc_html__( 'Choose a User Role', 'wp-marketing-automations-pro' ); ?></option>
                <#
                if(_.has(data.actionFieldsOptions, 'user_roles_options') && _.isObject(data.actionFieldsOptions.user_roles_options) ) {
                _.each( data.actionFieldsOptions.user_roles_options, function( value, key ){
                selected = (key == selected_user_role) ? 'selected' : '';
                #>
                <option value="{{key}}" {{selected}}>{{value}}</option>
                <# })
                }
                #>
            </select>
            <label for="" class="bwfan-label-title">
				<?php echo esc_html__( 'Action', 'wp-marketing-automations-pro' ); ?>
            </label>
            <#
            actionUpdate = ( selected_user_role_action === "update" ) ? 'checked' : '';
            actionAssign = ( selected_user_role_action === "assign" ) ? 'checked' : '';
            #>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-pt-5 bwfan-mb10">
                <label for="bwfan_user_role_update" style="padding-right:15px;">
                    <input type="radio" name="bwfan[{{data.action_id}}][data][user_role_action]" id="bwfan_user_role_update" value="update" {{actionUpdate}}/>
					<?php esc_html_e( 'Update Existing', 'wp-marketing-automations-pro' ); ?>
                </label>
                <label for="bwfan_user_role_assign">
                    <input type="radio" name="bwfan[{{data.action_id}}][data][user_role_action]" id="bwfan_user_role_assign" value="assign" {{actionAssign}}/>
					<?php
					esc_html_e( 'Assign New', 'wp-marketing-automations-pro' );
					?>
                </label>
            </div>
            <div class="clearfix bwfan_field_desc bwfan-pt-5 bwfan-mb10">
                Note: This action won't update the role of Administrator.
            </div>
        </script>
		<?php
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set                     = array();
		$data_to_set['email']            = $task_meta['global']['email'];
		$data_to_set['user_role']        = $task_meta['data']['user_role'];
		$data_to_set['user_role_action'] = $task_meta['data']['user_role_action'];

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                     = array();
		$data_to_set['email']            = $automation_data['global']['email'];
		$data_to_set['user_id']          = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : '';
		$data_to_set['user_role']        = $step_data['user_role'];
		$data_to_set['user_role_action'] = $step_data['user_role_action'];

		return $data_to_set;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );

		$result = $this->process();
		if ( true === $result['status'] ) {
			return array(
				'status'  => 3,
				'message' => isset( $result['message'] ) ? $result['message'] : '',
			);
		}

		if ( is_array( $result ) ) {
			return array(
				'status'  => 4,
				'message' => isset( $result['message'] ) ? $result['message'] : ( isset( $result['bwfan_response'] ) ? $result['bwfan_response'] : __( 'Unknown Error Occurred', 'wp-marketing-automations-pro' ) ),
			);
		}
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$user_email = $this->data['email'];
		$user       = get_user_by( 'email', $user_email );
		if ( ! $user instanceof WP_User ) {
			return array(
				'message' => 'User does not exists'
			);
		}

		/** Case when no action was passed, older scheduled tasks */
		$this->data['user_role_action'] = ( ! isset( $this->data['user_role_action'] ) || empty( $this->data['user_role_action'] ) ) ? 'update' : $this->data['user_role_action'];

		if ( in_array( 'administrator', $user->roles, true ) && 'update' === $this->data['user_role_action'] ) {
			return array(
				'message' => 'User is administrator, can\'t update its role'
			);
		}

		$user_role = $this->data['user_role'];

		/** get_editable_roles() not exists then include admin user.php file */
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/user.php' );
		}

		$editable_roles = get_editable_roles();

		if ( ! in_array( $user_role, array_keys( $editable_roles ), true ) ) {
			return array(
				'message' => 'Invalid user role'
			);
		}

		if ( 'assign' === $this->data['user_role_action'] ) {
			$user->add_role( $user_role );

			return array(
				'status'  => true,
				'message' => 'Role `' . $user_role . '` is assigned to the user'
			);
		}

		$user->set_role( $user_role );

		return array(
			'status'  => true,
			'message' => 'Role `' . $user_role . '` is updated to the user'
		);
	}

	public function process_v2() {
		$user_email = $this->data['email'];
		$user_id    = $this->data['user_id'];
		$user       = get_user_by( 'email', $user_email );

		if ( ! $user instanceof WP_User && isset( $user_id ) ) {
			$user = get_user_by( 'ID', $user_id );
		}

		if ( ! $user instanceof WP_User ) {
			return $this->skipped_response( __( 'User doesn\'t exists', 'wp-marketing-automations-pro' ) );
		}

		/** Case when no action was passed, older scheduled tasks */
		$this->data['user_role_action'] = ( ! isset( $this->data['user_role_action'] ) || empty( $this->data['user_role_action'] ) ) ? 'update' : $this->data['user_role_action'];

		if ( in_array( 'administrator', $user->roles, true ) && 'update' === $this->data['user_role_action'] ) {
			return $this->skipped_response( __( 'User is administrator, can\'t update its role', 'wp-marketing-automations-pro' ) );
		}

		$user_role = $this->data['user_role'];

		/** get_editable_roles() not exists then include admin user.php file */
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/user.php' );
		}

		$editable_roles = get_editable_roles();
		if ( ! in_array( $user_role, array_keys( $editable_roles ), true ) ) {
			return $this->skipped_response( __( 'Invalid user role', 'wp-marketing-automations-pro' ) );
		}

		if ( 'assign' === $this->data['user_role_action'] ) {
			$user->add_role( $user_role );

			return $this->success_message( __( 'Role `' . $user_role . '` is assigned to the user', 'wp-marketing-automations-pro' ) );
		}

		$user->set_role( $user_role );

		return $this->success_message( __( 'User role updated.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		global $wp_roles;
		$roles  = $wp_roles->roles;
		$data   = [];
		$data[] = [
			'label' => __( 'Select', 'wp-marketing-automations-pro' ),
			'value' => ''
		];
		foreach ( $roles as $key => $role ) {
			$data[] = [
				'label' => $role['name'],
				'value' => $key
			];
		}

		return [
			[
				"id"          => 'user_role',
				"label"       => __( 'Select User Role', 'wp-marketing-automations-pro' ),
				"type"        => 'wp_select',
				"options"     => $data,
				"placeholder" => "Choose role",
				"class"       => '',
				"tip"         => '',
				"description" => "",
				"required"    => true,
			],
			[
				'id'          => 'user_role_action',
				'label'       => __( "Action", 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( "Update Existing Role", 'wp-marketing-automations-pro' ),
						'value' => 'update'
					],
					[
						'label'   => __( "Assign New Role", 'wp-marketing-automations-pro' ),
						'value'   => 'assign',
						'tooltip' => __( "This will attach the image of highest price product of a cart/ order in the message.", 'wp-marketing-automations-pro' )
					]
				],
				"description" => __( "Note: This action won't update the role of Administrator.", 'wp-marketing-automations-pro' ),
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['user_role'] ) || empty( $data['user_role'] ) ) {
			return '';
		}
		global $wp_roles;
		$roles = $wp_roles->roles;

		return $roles[ $data['user_role'] ]['name'];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WP_Update_User_Role';
