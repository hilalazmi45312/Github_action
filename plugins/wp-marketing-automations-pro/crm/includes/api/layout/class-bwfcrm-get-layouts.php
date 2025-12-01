<?php

class BWFCRM_Api_Get_Layouts extends BWFCRM_API_Base {

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
		$this->public_api   = true;
		$this->route        = '/layouts';
	}

	public function process_api_call() {
		$search     = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$ids        = ! empty( $this->args['ids'] ) ? explode( ',', $this->args['ids'] ) : array();
		$limit      = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 0;
		$offset     = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$layouts    = BWFAN_Model_Templates::bwfan_get_layouts( $offset, $limit, $search, $ids );
		if ( ! is_array( $layouts ) ) {
			$layouts = array();
		}

		$layouts = array_map( function ( $layout ) {
			$data = [];
			if(! isset($layout['ID'])){
				return ;
			}
			if ( ! empty( $layout['data'] ) ) {
				$layout['data'] = json_decode( $layout['data'], true );
			}
			$data['ID'] = $layout['ID'];
			$data['title'] = isset($layout['title']) ? $layout['title'] : '';
			$data['content'] = isset($layout['template']) ? $layout['template'] : '';
			$data['categories'] = isset($layout['data']['categories']) ? $layout['data']['categories'] : [];
			$data['description'] = isset($layout['data']['desc']) ? $layout['data']['desc'] : '';
			return $data;
		}, $layouts );

		$this->total_count = BWFAN_Model_Templates::bwfan_get_layouts_count( $search, $ids );

		$this->response_code = 200;

		return $this->success_response( $layouts );
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

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Layouts' );