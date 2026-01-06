<?php

class BWFCRM_API_Delete_Contacts extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}


	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::DELETABLE;
		$this->route  = '/contacts';
	}

	public function process_api_call() {

		$contact_ids = $this->args['contacts'];
		if ( empty( $contact_ids ) || ! is_array( $contact_ids ) ) {
			return $this->error_response( __( 'Contact ids are missing.', 'wp-marketing-automations-pro' ), null, 500 );
		}

		BWFCRM_Model_Contact::delete_multiple_contacts( $contact_ids );

		$this->response_code = 200;

		return $this->success_response( array( 'contacts' => $contact_ids ), __( 'Contacts deleted!', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Delete_Contacts' );
