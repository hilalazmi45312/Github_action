<?php

class BWFCRM_API_Get_Contact_Conversation extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/contacts/(?P<contact_id>[\\d]+)/engagement/(?P<engagement_id>[\\d]+)';
		$this->request_args = array(
			'contact_id'    => array(
				'description' => __( 'Contact ID', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'engagement_id' => array(
				'description' => __( 'Engagement ID', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params */
		$id            = $this->get_sanitized_arg( 'contact_id', 'key' );
		$engagement_id = $this->get_sanitized_arg( 'engagement_id', 'key' );
		if ( ! class_exists( 'BWFAN_Email_Conversations' ) || ! isset( BWFAN_Core()->conversations ) || ! BWFAN_Core()->conversations instanceof BWFAN_Email_Conversations ) {
			return $this->error_response( __( 'Unable to find Funnelkit Automations message tracking module', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$this->contact = new BWFCRM_Contact( absint( $id ) );
		if ( ! $this->contact instanceof BWFCRM_Contact || ! $this->contact->is_contact_exists() ) {
			return $this->error_response( __( 'No contact found with given ID #' . $id, 'wp-marketing-automations-pro' ), null, 500 );
		}

		$conversation = $this->contact->get_conversation_email( $engagement_id );
		if ( $conversation instanceof WP_Error || ( is_array( $conversation ) && isset( $conversation['error'] ) ) ) {
			return $this->error_response( ! is_array( $conversation ) ? __( 'Unknown error occurred', 'wp-marketing-automations-pro' ) : $conversation['error'], $conversation instanceof WP_Error ? $conversation : null, 500 );
		}

		return $this->success_response( $conversation );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Conversation' );
