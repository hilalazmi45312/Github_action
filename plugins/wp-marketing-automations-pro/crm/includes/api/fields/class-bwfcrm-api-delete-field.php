<?php

class BWFCRM_Api_Delete_Field extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::DELETABLE;
		$this->route         = '/fields/(?P<field_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'field_id' => ''
		);

		return $args;
	}

	public function process_api_call() {

		$field_id = $this->get_sanitized_arg( 'field_id', 'text_field' );

		$delete_field = BWFCRM_Fields::delete_field( $field_id );

		if ( 0 === $delete_field ) {

			$this->response_code = 400;

			return $this->error_response( __( 'Unable to delete field with id #' . $field_id, 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( __( 'Field deleted', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Field' );