<?php

class BWFCRM_API_Get_Message_Daily_Limit extends BWFCRM_API_Base {
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
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/messages/daily-limit';
	}

	public function process_api_call() {
		$daily_limit = BWFCRM_Core()->campaigns->get_daily_limit_status_array();

		return $this->success_response( $daily_limit );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Message_Daily_Limit' );
