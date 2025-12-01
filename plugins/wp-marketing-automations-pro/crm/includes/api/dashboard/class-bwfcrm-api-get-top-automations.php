<?php

class BWFCRM_API_Get_Top_Automations extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;
	public $total_count;

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/dashboard/top-automations';
		$this->pagination->limit  = 5;
		$this->pagination->offset = 0;
	}

	public function default_args_values() {
	}

	public function process_api_call() {

		$top_automations = BWFCRM_Automations::get_top_automations();

		if ( ! isset( $top_automations['top_automations'] ) || empty( $top_automations['top_automations'] ) ) {
			return $this->success_response( [] );
		}

		$this->response_code = 200;
		$this->total_count   = count( $top_automations['top_automations'] );

		return $this->success_response( $top_automations['top_automations'] );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Top_Automations' );