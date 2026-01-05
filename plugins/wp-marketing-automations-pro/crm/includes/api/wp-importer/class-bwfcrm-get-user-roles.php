<?php

class BWFCRM_API_WP_Get_User_Roles extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/import/wp/roles';
	}

	public function process_api_call() {
		$roles = BWFCRM_WP_Importer::get_wp_roles();
		if ( ! is_array( $roles ) ) {
			return $this->error_response( is_string( $roles ) ? $roles : __( 'Unable to fetch roles', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( $roles, __( 'Roles', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_WP_Get_User_Roles' );
