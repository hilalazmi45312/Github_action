<?php

class BWFCRM_API_Get_Contact_Note extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/contacts/(?P<contact_id>[\\d]+)/notes';
		$this->pagination->limit  = 25;
		$this->pagination->offset = 0;
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$offset     = ! empty( $this->get_sanitized_arg( 'offset', 'key' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : $this->pagination->offset;
		$limit      = ! empty( $this->get_sanitized_arg( 'limit', 'key' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : $this->pagination->limit;

		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with given id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$contact_notes = $contact->get_contact_notes_array( $offset, $limit );

		if ( empty( $contact_notes ) ) {
			$this->response_code = 200;

			return $this->success_response( [], __( 'No contact notes found related with contact id :' . $contact_id, 'wp-marketing-automations-pro' ) );
		}
		$all_contact_notes   = $contact->get_contact_notes_array( 0, 0 );
		$this->total_count   = count( $all_contact_notes );
		$this->response_code = 200;
		$success_message     = __( 'Contact notes reated to contact id :' . $contact_id, 'wp-marketing-automations-pro' );

		return $this->success_response( $contact_notes, $success_message );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Note' );
