<?php

class BWFCRM_API_Contact_Resubscribe extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::DELETABLE;
		$this->route        = '/contacts/(?P<contact_id>[\\d]+)/resubscribe';
		$this->request_args = array(
			'contact_id' => array(
				'description' => __( 'Contact ID to resubscribe contact', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => ''
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$contact    = new BWFCRM_Contact( absint( $contact_id ) );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact doesn\'t exist', 'wp-marketing-automations-pro' ) );
		}

		$unsubscribe_data = $contact->check_contact_unsubscribed( false );

		if ( empty( $unsubscribe_data ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact already subscribed', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $unsubscribe_data as $data ) {

			BWFAN_Model_Message_Unsubscribe::delete( $data['ID'] );
		}
		$contact->save_last_modified();
		$this->response_code = 200;

		return $this->success_response( [ 'contact_id' => $contact_id ], __( 'Contact subscribed', 'wp-marketing-automations-pro' ) );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Contact_Resubscribe' );
