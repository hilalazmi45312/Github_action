<?php

/**
 * Get all link trigger data API file
 */
class BWFCRM_Api_Get_Link_Triggers extends BWFCRM_API_Base {

	public static $ins;
	public $total_count = 0;
	public $count_data = [];
	public $extra_data = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/link-triggers';
	}

	public function default_args_values() {
		return array( 's' => '' );
	}

	public function process_api_call() {

		$search   = ! empty( $this->get_sanitized_arg( 'search', 'key' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$offset   = ! empty( $this->get_sanitized_arg( 'offset', 'key' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$limit    = ! empty( $this->get_sanitized_arg( 'limit', 'key' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 0;
		$status   = ! empty( $this->get_sanitized_arg( 'status', 'key' ) ) ? $this->get_sanitized_arg( 'status', 'text_field' ) : '';
		$link_ids = empty( $this->args['ids'] ) ? array() : explode( ',', $this->args['ids'] );
		/**
		 * Get links data
		 */
		$link_data                   = BWFAN_Model_Link_Triggers::get_link_triggers( $search, $status, $limit, $offset, true, $link_ids );
		$links                       = isset( $link_data['links'] ) ? $link_data['links'] : [];
		$this->total_count           = isset( $link_data['total'] ) ? intval( $link_data['total'] ) : 0;
		$this->count_data            = BWFAN_PRO_Common::get_link_triggers_data_count();
		$this->extra_data['actions'] = ( array ) BWFCRM_Core()->actions->get_all_action_list( 1 );
		$this->response_code         = 200;

		return $this->success_response( $links );
	}

	/**
	 * Returns total count
	 *
	 * @return int
	 */
	public function get_result_total_count() {
		return $this->total_count;
	}

	/**
	 * Returns links count based on status
	 *
	 * @return array|int
	 */
	public function get_result_count_data() {
		return $this->count_data;
	}

	/**
	 * Returns extra data
	 *
	 * @return array|int
	 */
	public function get_result_extra_data() {
		return $this->extra_data;
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Link_Triggers' );