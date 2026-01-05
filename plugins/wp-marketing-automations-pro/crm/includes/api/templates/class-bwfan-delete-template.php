<?php

class BWFCRM_Api_Delete_Template extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::DELETABLE;
		$this->route  = '/template/(?P<template_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'template_id' => '' );
	}

	public function process_api_call() {

		$template_id = $this->get_sanitized_arg( 'template_id', 'key' );

		if ( empty( $template_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Template ID is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** if the provided id doesn't exists */
		$already_exists = BWFAN_Model_Templates::bwfan_check_template_exists( 'ID', $template_id );

		if ( ! $already_exists ) {
			$this->response_code = 400;
			$response            = __( "Template doesn't exists with ID : ", 'wp-marketing-automations-pro' ) . $template_id;

			return $this->error_response( $response );
		}

		$delete_template = BWFAN_Model_Templates::bwf_delete_template( $template_id );
		if ( false === $delete_template ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the Template', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Template deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Template' );