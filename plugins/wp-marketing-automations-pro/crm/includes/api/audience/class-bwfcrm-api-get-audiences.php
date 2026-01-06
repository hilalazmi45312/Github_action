<?php

class BWFCRM_Api_Get_Audiences extends BWFCRM_API_Base {

	public static $ins;
	public $audiences = array();
	public $total_count = 0;
	public $count_data = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/audiences';
		$this->request_args = array(
			'search' => array(
				'description' => __( 'Search from name', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'ids'    => array(
				'description' => __( 'Search from audience ids', 'wp-marketing-automations-pro' ),
				'type'        => 'string'
			),
		);
	}

	public function default_args_values() {
		return array( 'ids' => '', 'search' => '' );
	}

	public function process_api_call() {
		/** if isset search param then get the tag by name **/
		$search       = $this->get_sanitized_arg( 'search', 'text_field' );
		$audience_ids = empty( $this->args['ids'] ) ? array() : explode( ',', $this->args['ids'] );
		$limit        = $this->get_sanitized_arg( 'limit', 'text_field' );
		$offset       = $this->get_sanitized_arg( 'offset', 'text_field' );

		$audiences = BWFAN_Model_Terms::get_terms( 3, $offset, $limit, $search, $audience_ids );
		if ( ! is_array( $audiences ) ) {
			$audiences = array();
		}

		$audiences = array_map( function ( $audience ) {
			$audience['data'] = json_decode( $audience['data'] );

			return $audience;
		}, $audiences );
		$count     = BWFAN_Model_Terms::get_terms_count( 3, $search, $audience_ids );

		$this->total_count   = $count;
		$this->count_data    = BWFAN_PRO_Common::get_contact_data_counts();
		$this->response_code = 200;

		return $this->success_response( $audiences );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Audiences' );