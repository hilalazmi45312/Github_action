<?php

class BWFCRM_API_Create_Contact_Note extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/notes';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'notes'      => array(),
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		if ( empty( $contact_id ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$notes = $this->args['notes'];
		if ( empty( $notes ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Note is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with given id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$note_added = $contact->add_note_to_contact( $notes );
		if ( false === $note_added || is_wp_error( $note_added ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Unable to add note to contact', 'wp-marketing-automations-pro' ) );
		}

		$this->response_code = 200;

		return $this->success_response( [], __( 'Contact note added', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Create_Contact_Note' );
