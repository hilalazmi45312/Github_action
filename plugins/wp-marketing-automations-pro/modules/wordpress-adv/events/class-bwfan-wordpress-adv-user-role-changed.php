<?php

#[AllowDynamicProperties]
final class BWFAN_WP_User_Role_Changed extends BWFAN_Event {
	private static $instance = null;
	public $user_id = null;
	public $level_id = null;
	public $email = null;
	public $new_role = null;
	public $old_role = null;

	private function __construct() {
		$this->optgroup_label         = esc_html__( 'User', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'User Role Updated', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a user role is changed.', 'wp-marketing-automations-pro' );
		$this->event_merge_tag_groups = array( 'bwf_contact' );
		$this->event_rule_groups      = array(
			'wp_user',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);

		$this->priority   = 105.2;
		$this->support_v1 = false;
		$this->v2         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'set_user_role', [ $this, 'user_role_changed' ], 20, 3 );
	}

	public function user_role_changed( $user_id, $new_role, $old_role ) {
		$this->process( $user_id, $new_role, $old_role );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $user_id
	 * @param $new_role
	 * @param $old_role
	 *
	 * @return void
	 */
	public function process( $user_id, $new_role, $old_role ) {
		$data      = $this->get_default_data();
		$user_data = get_userdata( $user_id );
		if ( ! $user_data instanceof WP_User ) {
			return;
		}

		$data['user_id']  = $user_id;
		$data['new_role'] = strtolower( $new_role );
		$data['old_role'] = array_map( 'strtolower', $old_role );
		$data['email']    = $user_data->user_email;
		$this->send_async_call( $data );
	}

	/**
	 * fetching user roles
	 * @return array
	 */
	public function get_view_data() {
		global $wp_roles;
		$roles   = $wp_roles->get_names();
		$options = array();
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $key => $role ) {
				$options[ $key ] = $role;
			}
		}

		return $options;
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : null;
	}

	public function get_user_id_event() {
		return $this->user_id;
	}

	/**
	 * @return array
	 */
	public function get_event_data() {
		$data_to_send                       = [ 'global' => [] ];
		$data_to_send['global']['user_id']  = $this->user_id;
		$data_to_send['global']['new_role'] = $this->new_role;
		$data_to_send['global']['old_role'] = $this->old_role;
		$data_to_send['global']['email']    = $this->get_email_event();

		return $data_to_send;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		if ( ! isset( $automation_data['event_meta'] ) || empty( $automation_data['event_meta'] ) ) {
			return false;
		}

		$from_user_roles = $automation_data['event_meta']['from_user_roles'];
		$to_user_roles   = $automation_data['event_meta']['to_user_roles'];

		/** Status Any to Any case */
		if ( 'any' === $from_user_roles && 'any' === $to_user_roles ) {
			return true;
		}

		$old_role = $automation_data['old_role'];
		$new_role = $automation_data['new_role'];

		/** Status Any to User role case */
		if ( 'any' === $from_user_roles ) {
			return ( $new_role === $to_user_roles );
		}

		/** In case of new user created, old role will always be empty */
		/** In case old role and from role are no equal */
		if ( empty( $old_role ) || ! in_array( $from_user_roles, $old_role ) ) {
			return false;
		}

		/** Status (user role to Any case) OR (user role to user role case) */

		return ( 'any' === $to_user_roles || $new_role === $to_user_roles );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$user_id  = BWFAN_Common::$events_async_data['user_id'];
		$new_role = BWFAN_Common::$events_async_data['new_role'];
		$old_role = BWFAN_Common::$events_async_data['old_role'];

		$this->user_id  = $user_id;
		$this->email    = BWFAN_Common::$events_async_data['email'];
		$this->new_role = $new_role;
		$this->old_role = $old_role;

		$automation_data['user_id']  = $this->user_id;
		$automation_data['email']    = $this->email;
		$automation_data['new_role'] = $this->new_role;
		$automation_data['old_role'] = $this->old_role;

		return $automation_data;
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {
		$default = [
			'any' => 'Any'
		];

		$options = array_replace( $default, $this->get_view_data() );
		$options = BWFAN_PRO_Common::prepared_field_options( $options );

		return [
			[
				'id'          => 'from_user_roles',
				'type'        => 'wp_select',
				'label'       => __( 'From User Role', 'wp-marketing-automations-pro' ),
				'options'     => $options,
				'placeholder' => __( 'Select User Role', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
				"errorMsg"    => __( "User Role is required", 'wp-marketing-automations-pro' ),
			],
			[
				'id'          => 'to_user_roles',
				'type'        => 'wp_select',
				'label'       => __( 'To User Role', 'wp-marketing-automations-pro' ),
				'options'     => $options,
				'placeholder' => __( 'Select User Role', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
				"errorMsg"    => __( "User Role is required", 'wp-marketing-automations-pro' ),
			],
		];
	}

	/**
	 * Returns default values
	 *
	 * @return string[]
	 */
	public function get_default_values() {
		return [
			'from_user_roles' => 'any',
			'to_user_roles'   => 'any',
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
return 'BWFAN_WP_User_Role_Changed';
