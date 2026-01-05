<?php

class BWFCRM_API_Get_Popular_Emails extends BWFCRM_API_Base {
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
		$this->route              = '/dashboard/popular-emails';
		$this->pagination->limit  = 5;
		$this->pagination->offset = 0;
	}

	public function default_args_values() {
	}

	public function process_api_call() {

		$popular_emails = BWFCRM_Conversation::get_popular_emails();

		if ( ! isset( $popular_emails['popular_emails'] ) || empty( $popular_emails['popular_emails'] ) ) {
			return $this->success_response( [] );
		}

		$this->response_code = 200;
		$this->total_count   = count( $popular_emails['popular_emails'] );

		return $this->success_response( $popular_emails['popular_emails'] );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Popular_Emails' );