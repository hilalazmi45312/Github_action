<?php

/**
 * Class BWFAN_Rest_API_Update_Email
 */

namespace BWFAN\Rest_API;
class Update_Email extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/update-email/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'email' ];
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
	 * @return mixed
	 */
	public function process_api_call( $contact = '' ) {
		$id    = $this->get_sanitized_arg( 'id', 'text_field' );
		$email = $this->get_sanitized_arg( 'email', 'email' );

		if ( ! is_email( $email ) ) {
			$this->error_response( null, 422, ' email_invalid' );
		}

		// checking if contact already exits
		if ( ! empty( \BWFAN_Rest_API_Common::is_contact_exists( $email ) ) ) {
			$this->error_response( null, 422, 'already_exists' );
		}

		// updating email by contact id
		$update_contact = $contact->set_data( array( 'email' => $email ) );
		if ( is_wp_error( $update_contact ) ) {
			$this->error_response( $update_contact->get_error_messages(), 422, 'unknown_error' );
		}

		$contact->save();

		/** Re-fetching contact obj after updating */
		$contact = new \BWFCRM_Contact( $contact->get_id() );

		return $this->success_response( [ 'contact' => \BWFAN_Rest_API_Common::get_formatted_contact_data( $contact ) ], __( 'Email updated successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Update_Email' );
