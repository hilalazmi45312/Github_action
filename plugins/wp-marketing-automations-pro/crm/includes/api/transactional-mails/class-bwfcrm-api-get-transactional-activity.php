<?php

class BWFCRM_API_Get_Transactional_Activity extends BWFCRM_API_Base {

	public static $ins;
	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method            = WP_REST_Server::READABLE;
		$this->route             = '/transactional-activity';
	}


	public function process_api_call() {
		$search  = $this->get_sanitized_arg( 'search', 'text_field' ) ?? '';
		$limit    = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 25;
		$offset   = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;

		$activity_data = $this->get_trasnsactinal_activity( $search, $offset, $limit );
		$this->response_code = 200;
		$this->total_count   = 0;
		if ( empty( $activity_data[ 'activities' ] ) ) {
			return $this->success_response( [],__( 'No transactional activity found', 'wp-marketing-automations-pro' ) );
		}
		$this->total_count = $activity_data[ 'total_count' ];

		return $this->success_response( $activity_data[ 'activities' ], __( 'Transactional activity fetched successfully', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Get transactional activity
	 *
	 * @param string $search
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get_trasnsactinal_activity( $search = '', $offset = 0, $limit = 25 ) {
		$activity_data = [];
		// Load all transactional mails
		BWFCRM_Core()->transactional_mails->load_all_files();
		// Get transactional activity list
		$activity_data[ 'activities' ] = BWFCRM_Core()->transactional_mails->get_transactional_activity( $search, $offset, $limit );
		// Get total count of transactional activity
		$activity_data[ 'total_count' ] = BWFCRM_Core()->transactional_mails->get_transactional_activity( $search, $offset, $limit, [], [], true );

		return $activity_data;
	}


	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Transactional_Activity' );