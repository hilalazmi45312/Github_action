<?php

class BWFCRM_API_Enagements_Search extends BWFCRM_API_Base {
	public static $ins;

	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/analytics/engagements/search';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;
		$this->request_args       = [
			'offset' => array(
				'description' => __( 'Templates list Offset', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'limit'  => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		];
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {
		$search  = ( isset( $this->args['search'] ) && '' !== $this->args['search'] ) ? $this->args['search'] : '';
		$type    = ( isset( $this->args['type'] ) && '' !== $this->args['type'] ) ? $this->args['type'] : 1;
		$mode    = ( isset( $this->args['mode'] ) && '' !== $this->args['mode'] ) ? $this->args['mode'] : 1;
		$version = ( isset( $this->args['version'] ) && '' !== $this->args['version'] ) ? $this->args['version'] : 2;

		$engagements = BWFCRM_Reports::get_engagements( $search, intval( $type ), $mode, $version );
		if ( empty( $engagements ) ) {
			return $this->success_response( [], __( 'Record not found', 'wp-marketing-automations-pro' ) );
		}

		if ( count( $engagements ) > 0 ) {
			$this->total_count = count( $engagements );
		}

		return $this->success_response( $engagements, __( 'Got all engagements', 'wp-marketing-automations-pro' ) );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Enagements_Search' );
