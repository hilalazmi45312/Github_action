<?php

class BWFCRM_API_AFFWP_Get_Referrals extends BWFCRM_API_Base {
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
		$this->route  = '/affwp/contact/(?P<contact_id>[\\d]+)';
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		if ( empty( $contact_id ) ) {
			return $this->error_response_200( __( 'No Contact ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$offset = $this->pagination->offset;
		$limit  = $this->pagination->limit;

		/** @var BWFCRM_Integration_Affiliate $ins */
		$ins     = BWFCRM_Core()->integrations->get_integration( 'affwp' );
		$details = $ins->get_contact_referrals( absint( $contact_id ), $offset, $limit );

		return $this->success_response( $details );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_AFFWP_Get_Referrals' );
