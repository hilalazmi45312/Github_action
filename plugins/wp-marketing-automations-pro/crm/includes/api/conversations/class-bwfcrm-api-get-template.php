<?php

class BWFCRM_API_Get_Conversation_Template extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/templates/(?P<template_id>[\\d]+)';
		$this->request_args = array(
			'template_id' => array(
				'description' => __( 'Template ID', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		);
	}

	public function default_args_values() {
		return array(
			'template_id' => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params **/
		$template_id = $this->get_sanitized_arg( 'template_id', 'key' );
		if ( empty( $template_id ) ) {
			return $this->error_response( __( 'Invalid template ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$template = BWFCRM_Core()->conversation->get_conversation_template_array( $template_id );
		if ( ! is_array( $template ) || empty( $template ) ) {
			return $this->error_response( __( 'Template doesn\'t exists', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( $template );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Conversation_Template' );