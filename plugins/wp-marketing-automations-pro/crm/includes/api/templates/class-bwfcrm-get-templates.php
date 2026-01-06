<?php

class BWFCRM_Api_Get_Templates extends BWFCRM_API_Base {

	public static $ins;
	public $templates = array();
	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/templates';
		$this->public_api   = true;
		$this->request_args = array(
			'search' => array(
				'description' => __( 'Search from name', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'ids'    => array(
				'description' => __( 'Search from template ids', 'wp-marketing-automations-pro' ),
				'type'        => 'string'
			),
		);
	}

	public function default_args_values() {
		return array( 'ids' => '', 'search' => '' );
	}

	public function process_api_call() {
		/** if isset search param then get the tag by name **/
		$search       = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$mode         = ! empty( $this->get_sanitized_arg( 'mode', 'text_field' ) ) ? $this->get_sanitized_arg( 'mode', 'text_field' ) : '';
		$template_ids = ! empty( $this->args['ids'] ) ? explode( ',', $this->args['ids'] ) : array();
		$limit        = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 0;
		$offset       = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$get_template = isset( $this->args['get_template'] ) ? $this->get_sanitized_arg( 'get_template', 'bool' ) : true;

		$templates = BWFAN_Model_Templates::bwfan_get_templates( $offset, $limit, $search, $template_ids, $get_template, $mode );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		$templates = array_map( function ( $template ) {
			if ( ! empty( $template['data'] ) ) {
				$template['data'] = json_decode( $template['data'] );
			}
			if ( isset( $template['template'] ) ) {
				unset( $template['template'] );
			}

			return $template;
		}, $templates );

		$this->total_count = BWFAN_Model_Templates::bwfan_get_templates_count( $search, $template_ids, $mode );

		$this->response_code = 200;

		return $this->success_response( $templates );
	}

	/**
	 * Return template total count
	 *
	 * @return int
	 */
	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Templates' );