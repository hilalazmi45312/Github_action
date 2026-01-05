<?php

class BWFCRM_API_Get_Contact_Subscriptions extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;
	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/subscriptions';

		$this->pagination->limit  = 10;
		$this->pagination->offset = 0;
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

			return $this->error_response( __( 'Contact id is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found related with contact id :' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$limit = $this->get_sanitized_arg( 'limit', 'text_field' );
		$limit = ! empty( $limit ) ? $limit : $this->pagination->limit;

		$offset = $this->get_sanitized_arg( 'offset', 'text_field' );
		$offset = ! empty( $offset ) ? $offset : 0;

		$contact_orders = $contact->get_subscriptions_array( $offset, $limit );
		if ( empty( $contact_orders['subscriptions'] ) ) {
			$this->response_code = 200;

			return $this->success_response( [], __( 'No contact subscriptions found related with contact id :' . $contact_id, 'wp-marketing-automations-pro' ) );
		}
		$total_contact_subscriptions = $contact->get_total_subscriptions();
		$this->total_count           = empty( $total_contact_subscriptions ) ? 0 : $total_contact_subscriptions;
		$this->response_code         = 200;
		$success_message             = __( 'Got all contact subscriptions', 'wp-marketing-automations-pro' );

		return $this->success_response( $contact_orders, $success_message );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

}

if ( class_exists( 'WC_Subscriptions' ) ) {
	BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Subscriptions' );
}