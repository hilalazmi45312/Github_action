<?php
/**
 * Template Clone API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Template Clone API class
 */
class BWFCRM_API_Clone_Template extends BWFCRM_API_Base {

	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/**
	 * Return class instance
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/template/(?P<template_id>[\\d]+)/clone';
		$this->request_args = array(
			'template_id' => array(
				'description' => __( 'Template ID whose data to be cloned.', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'template_id' => 0,
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$template_id = $this->get_sanitized_arg( 'template_id', 'key' );

		if ( intval( $template_id ) > 0 ) {
			$template_data       = BWFAN_Model_Templates::clone_template( $template_id );
			$this->response_code = $template_data['status'];
			if ( $template_data['status'] === 200 ) {
				return $this->success_response( [], $template_data['message'] );
			} else {
				return $this->error_response( $template_data['message'] );
			}
		} else {
			$this->response_code = 404;

			return $this->error_response( __( 'Please provide a valid template id.', 'wp-marketing-automations-pro' ) );
		}
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Clone_Template' );
