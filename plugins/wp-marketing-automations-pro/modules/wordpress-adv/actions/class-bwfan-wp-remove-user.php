<?php

final class BWFAN_Wp_Remove_User extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Remove User Role', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action remove the role of user', 'wp-marketing-automations-pro' );
		$this->action_priority = 10;
		$this->required_fields = array( 'email', 'user_role' );
		$this->support_v2      = true;
		$this->support_v1      = false;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
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

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set              = array();
		$data_to_set['email']     = $automation_data['global']['email'];
		$data_to_set['user_id']   = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : '';
		$data_to_set['user_role'] = $step_data['user_role'];

		return $data_to_set;
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

		$user_role = $this->data['user_role'];

		/** get_editable_roles() not exists then include admin user.php file */
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/user.php' );
		}

		$editable_roles = get_editable_roles();
		if ( ! in_array( $user_role, array_keys( $editable_roles ), true ) ) {
			return $this->skipped_response( __( 'Invalid user role.', 'wp-marketing-automations-pro' ) );
		}
		if ( ! user_can( $user->ID, $user_role ) ) {
			return $this->success_message( __( 'User does not have the specified role.', 'wp-marketing-automations-pro' ) );
		}
		try {

			// Attempt to remove the user role
			$user->remove_role( $user_role );

			return $this->success_message( __( 'User role removed.', 'wp-marketing-automations-pro' ) );
		} catch ( Error $e ) {
			return $this->skipped_response( __( 'Error While removing user role ' . $e->getMessage(), 'wp-marketing-automations-pro' ) );
		}

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
		];
	}

}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_Wp_Remove_User';
