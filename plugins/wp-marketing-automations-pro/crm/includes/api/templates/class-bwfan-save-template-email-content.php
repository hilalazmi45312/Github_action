<?php

class BWFCRM_API_Save_Template_Email_Content extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/template/(?P<template_id>[\\d]+)/save-email-content';
		$this->request_args = array(
			'template_id' => array(
				'description' => __( 'Template ID to update', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		$args = array(
			'template' => '',
			'data'     => [],
		);

		return $args;
	}

	public function process_api_call() {
		if ( isset( $this->args['content'] ) ) {
			$content    = BWFAN_Common::is_json( $this->args['content'] ) ? json_decode( $this->args['content'], true ) : [];
			$this->args = wp_parse_args( $content, $this->args );
		}
		$template_id = $this->get_sanitized_arg( 'template_id' );
		if ( empty( $template_id ) ) {
			return $this->error_response( __( 'Invalid / Empty template ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$template = $this->args['template'];
		$data     = $this->args['data'];
		$subject  = isset( $this->args['subject'] ) ? $this->args['subject'] : '';
		$mode     = $this->args['mode'];

		$exists = BWFAN_Model_Templates::bwfan_check_template_exists( 'ID', $template_id );
		if ( ! $exists ) {
			$this->response_code = 400;
			$response            = __( 'Template does not exists with id : ', 'wp-marketing-automations-pro' ) . $template_id;

			return $this->error_response( $response );
		}

		$create_time = current_time( 'mysql', 1 );

		$template_data = [
			'template'   => $template,
			'data'       => BWFAN_Common::is_json( $data ) ? $data : wp_json_encode( $data ),
			'mode'       => ! empty( $mode ) ? intval($mode):  4,
			'updated_at' => $create_time,
		];
		if ( ! empty( $subject ) ) {
			$template_data[ 'subject' ] = $subject;
		}

		$result = BWFAN_Model_Templates::bwfan_update_template( $template_id, $template_data );
		if ( ! $result ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to update template', 'wp-marketing-automations-pro' ) );
		}

		$response_data = BWFAN_Model_Templates::bwfan_get_template( $template_id );

		if ( ! empty( $response_data['data'] ) ) {
			$response_data['data'] = json_decode( $response_data['data'] );
		}

		return $this->success_response( $response_data, __( 'Template Updated Successfully', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Save_Template_Email_Content' );