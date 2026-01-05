<?php

class BWFCRM_API_Get_Contact_Conversations extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;
	public $mode = null;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/contacts/(?P<contact_id>[\\d]+)/engagements';
		$this->request_args = array(
			'contact_id' => array(
				'description' => __( 'Contact ID for which engagements to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'mode'       => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params **/
		$id         = $this->get_sanitized_arg( 'contact_id', 'key' );
		$this->mode = $this->get_sanitized_arg( 'mode', 'key' );
		$limit      = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 25;
		$offset     = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;

		if ( ! class_exists( 'BWFAN_Email_Conversations' ) || ! isset( BWFAN_Core()->conversations ) || ! BWFAN_Core()->conversations instanceof BWFAN_Email_Conversations ) {
			return $this->error_response( __( 'Unable to find conversations module', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$this->contact = new BWFCRM_Contact( absint( $id ) );
		if ( ! $this->contact instanceof BWFCRM_Contact || ! $this->contact->is_contact_exists() ) {
			return $this->error_response( __( 'Contact doesn\'t exists', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$conversation = $this->contact->get_conversations( $this->mode, $offset, $limit );
		if ( $conversation instanceof WP_Error ) {
			return $this->error_response( '', $conversation, 500 );
		}

		return $this->success_response( $conversation );
	}

	public function get_result_total_count() {
		return $this->contact instanceof BWFCRM_Contact && $this->contact->is_contact_exists() ? $this->contact->get_conversations_total( $this->mode ) : 0;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Conversations' );
