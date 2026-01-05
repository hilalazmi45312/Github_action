<?php

class BWFCRM_API_Get_Contact_Tasks extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/tasks';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params **/
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );

		/** contact id missing than return  */
		if ( empty( $contact_id ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with given id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}


		$contact_tasks = $contact->get_tasks_array();
		if ( empty( $contact_tasks ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No tasks available for the contact', 'wp-marketing-automations-pro' ) );
		}
		$this->response_code = 200;
		$success_message     = __( 'Got all contact tasks', 'wp-marketing-automations-pro' );

		return $this->success_response( $contact_tasks, $success_message );
	}

}

if ( function_exists( 'BWFAN_Core' ) ) {
	BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Tasks' );
}