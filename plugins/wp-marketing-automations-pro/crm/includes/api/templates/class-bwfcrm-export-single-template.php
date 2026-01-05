<?php

class BWFCRM_Api_Export_Single_Template extends BWFCRM_API_Base {

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
		$this->route         = '/templates/(?P<template_id>[\\d]+)/export/';
		$this->response_code = 200;
	}

	public function process_api_call() {
		$template_id = ( isset( $this->args['template_id'] ) && '' !== $this->args['template_id'] ) ? $this->args['template_id'] : 0;
		$data = BWFCRM_Templates::get_json( $template_id );

		$this->response_code = 200;

		return $this->success_response( $data, __( 'Template exported', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Export_Single_Template' );