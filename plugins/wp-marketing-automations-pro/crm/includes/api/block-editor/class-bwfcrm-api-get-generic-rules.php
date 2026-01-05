<?php

class BWFCRM_Api_Get_Generic_rules extends BWFCRM_API_Base {

	public static $ins;
	public $total_count = 0;
	public $count_data = [];
	protected $stock_status = '';
	protected array $categories = [];
	protected $serach_title = '';

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/generic-rules';
	}


	public function process_api_call() {
		$rules = BWFAN_PRO_Common::get_generic_rules();

		return $this->success_response( $rules );
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Generic_rules' );