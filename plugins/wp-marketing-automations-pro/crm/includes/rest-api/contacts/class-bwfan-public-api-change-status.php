<?php

/**
 * Class BWFAN_Rest_API_Change_Status
 */

namespace BWFAN\Rest_API;
class Change_Status extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/change-status/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'status' ];
		$this->validate = [ 'contact_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param \BWFCRM_Contact $contact
	 *
	 * @return mixed|void
	 */
	public function process_api_call( $contact = '' ) {
		$status = $this->get_sanitized_arg( 'status', 'text_field' );
		$status = ! empty( $status ) ? strtolower( $status ) : $status;
		$allowed_statuses = [ 'unverified', 'subscribed', 'bounced', 'unsubscribed', 'softbounced', 'complaint' ];

		/** Validate the status */
		if ( ! in_array( $status, $allowed_statuses ) ) {
			$this->error_response( sprintf( __( 'Invalid status. Allowed values are: %s', 'wp-marketing-automations-pro' ), implode( ', ', $allowed_statuses ) ), 422, 'invalid_status' );
		}

		$result = \BWFAN_Rest_API_Common::change_contact_status( $contact, $status );

		if ( ! $result ) {
			$this->error_response( '', 422, 'unknown_error' );
		}

		return $this->success_response( [ 'contact' => \BWFAN_Rest_API_Common::get_formatted_contact_data( $contact ) ], __( "Status changed successfully", "autonami-automations-pro" ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Change_Status' );
