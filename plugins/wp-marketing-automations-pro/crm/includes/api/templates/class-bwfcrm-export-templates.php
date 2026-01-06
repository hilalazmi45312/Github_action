<?php

class BWFCRM_Api_Export_Templates extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = '/templates/export/';
		$this->response_code = 200;
	}

	public function process_api_call() {
		$ids = isset( $this->args['ids'] ) ? explode(',', $this->args['ids'] ) : [];
		$data = BWFCRM_Templates::get_json( $ids );

		$this->response_code = 200;

		return $this->success_response( $data, __( 'Templates exported', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Export_Templates' );