<?php

class BWFCRM_API_Get_Recent_Contacts extends BWFCRM_API_Base {
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
		$this->route              = '/dashboard/recent-contacts';
		$this->pagination->limit  = 5;
		$this->pagination->offset = 0;
	}

	public function default_args_values() {
	}

	public function process_api_call() {
		/** checking if id or email present in params **/ //		$additional_filter = array(
//			'exclude_unsubscribe' => true,
//			'customer_data'       => true,
//		);

//		$contacts = BWFCRM_Contact::get_recent_contacts( 0, $this->pagination->limit,     $additional_filter );
		$contacts = BWFAN_Dashboards::get_recent_contacts();
		if ( ! is_array( $contacts ) ) {
			$this->response_code = 500;

			return $this->error_response( is_string( $contacts ) ? $contacts : __( 'Unknown Error', 'wp-marketing-automations-pro' ) );
		}

		if ( ! isset( $contacts ) || empty( $contacts ) ) {
			return $this->success_response( [] );
		}

		$this->response_code = 200;
		$this->total_count   = count( $contacts );

		return $this->success_response( $contacts );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Recent_Contacts' );
