<?php

class BWFCRM_API_DELETE_Contact_Note extends BWFCRM_API_Base {
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
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/notes/(?P<note_id>[\\d]+)';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'note_id'    => 0,
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/

		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact id is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact instanceof BWFCRM_Contact || 0 === $contact->get_id() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}
		$note_id                    = $this->get_sanitized_arg( 'note_id', 'key' );
		$delete_contact_note_result = $contact->delete_notes( $note_id );

		if ( ! $delete_contact_note_result ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Unable to delete the note for contact #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$this->response_code = 200;
		$success_message     = __( 'Contact notes deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( array( 'contact_id' => $contact_id ), $success_message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_DELETE_Contact_Note' );
