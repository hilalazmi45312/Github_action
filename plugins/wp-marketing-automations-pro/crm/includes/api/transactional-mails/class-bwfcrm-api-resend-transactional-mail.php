<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_API_Resend_Transactional_Email
 */
class BWFCRM_API_Resend_Transactional_Email extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/transactional-mail/(?P<eid>[\\d]+)/resend';
	}

	public function process_api_call() {
		$eid = $this->get_sanitized_arg( 'eid' );
		if ( empty( $eid ) ) {
			return $this->error_response( __( 'Invalid or empty engagement id.', 'wp-marketing-automations-pro' ), null, 400 );
		}
		$status = BWFCRM_Core()->transactional_mails->resend_conversation_to_contact( $eid );
		if ( ! $status ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to send transactional mail', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [], __( 'Transactional mail sent successfully', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Resend_Transactional_Email' );