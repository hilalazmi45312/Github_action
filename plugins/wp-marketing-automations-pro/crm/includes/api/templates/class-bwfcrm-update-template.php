<?php

class BWFCRM_Api_Update_Template extends BWFCRM_API_Base {

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
		$this->route        = '/template/(?P<template_id>[\\d]+)';
		$this->request_args = array(
			'template_id' => array(
				'description' => __( 'Template ID to update', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		$args = array(
			'title'    => '',
			'subject'  => '',
			'template' => '',
			'type'     => '',
			'mode'     => '',
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

		$title   = $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] );
		$subject = $this->get_sanitized_arg( 'subject', 'text_field', $this->args['subject'] );
		$onlytitle = isset( $this->args['onlytitle'] ) ? $this->get_sanitized_arg( 'onlytitle', 'text_field', $this->args['onlytitle'] ) : false;

		$template = $this->args['template'];

		$type = $this->get_sanitized_arg( 'type', 'text_field', $this->args['type'] );
		$mode = $this->get_sanitized_arg( 'mode', 'text_field', $this->args['mode'] );
		$data = $this->args['data'];

		if ( empty( $title ) ) {
			$this->response_code = 400;
			$response            = __( 'Oops Title not entered, enter title to save template', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		if ( empty( $subject ) && ! $onlytitle ) {
			$this->response_code = 400;
			$response            = __( 'Oops Subject not entered, enter subject to save template', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		if ( empty( $template ) && ! $onlytitle ) {
			$this->response_code = 400;
			$response            = __( 'Oops Template is empty, enter content to save template', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$exists = BWFAN_Model_Templates::bwfan_check_template_exists( 'ID', $template_id );
		if ( ! $exists ) {
			$this->response_code = 400;
			$response            = __( 'Template does not exists with id : ', 'wp-marketing-automations-pro' ) . $template_id;

			return $this->error_response( $response );
		}

		$create_time = current_time( 'mysql', 1 );

		if( $onlytitle ) {
			$template_data = [
				'title'      => $title,
				'updated_at' => $create_time,
			];
		} else {
			$template_data = [
				'title'      => $title,
				'template'   => $template,
				'subject'    => $subject,
				'type'       => intval( $type ) > 0 ? intval( $type ) : 1,
				'mode'       => intval( $mode ) > 0 ? intval( $mode ) : 1,
				'canned'     => 1,
				'updated_at' => $create_time,
				'data'       => BWFAN_Common::is_json( $data ) ? $data : wp_json_encode( $data ),
			];
		}


		$result = BWFAN_Model_Templates::bwfan_update_template( $template_id, $template_data );
		if ( ! $result ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to update template', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [], __( 'Template Updated', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_Template' );