<?php

class BWFCRM_Api_Get_Template extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/template/(?P<template_id>[\\d]+)';
		$this->request_args = array(
			'template_id' => array(
				'description' => __( 'Template ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);

	}

	public function process_api_call() {
		$template_id = $this->get_sanitized_arg( 'template_id' );
		if ( empty( $template_id ) ) {
			return $this->error_response( __( 'Invalid / Empty template ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$template = BWFAN_Model_Templates::bwfan_get_template( $template_id );

		if ( empty( $template ) ) {
			return $this->error_response( __( 'Template not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		}

		if ( ! empty( $template['data'] ) ) {
			$template['data'] = json_decode( $template['data'] );
		}

		$this->response_code = 200;

		return $this->success_response( $template );
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Template' );