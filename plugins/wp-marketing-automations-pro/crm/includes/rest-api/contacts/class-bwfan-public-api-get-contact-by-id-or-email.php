<?php

/**
 * Class BWFAN_Rest_API_Get_Contact_By_Id_Or_Email
 */

namespace BWFAN\Rest_API;
class Get_Contact_By_Id_Or_Email extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::READABLE;
		$this->route    = '/contact';
		$this->validate = [ 'contact_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * /**
	 * @param \BWFCRM_Contact $contact
	 *
	 * @return mixed
	 */
	public function process_api_call( $contact = '' ) {
		$id    = $this->get_sanitized_arg( 'id', 'text_field' );
		$email = $this->get_sanitized_arg( 'email', 'email' );

		$search_by = empty( $id ) ? $email : $id;
		// either id or email should be present
		if ( empty( $search_by ) ) {
			$this->error_response( null, 400, 'required_fields_missing' );
		}
		return $this->success_response( [ 'contact' => \BWFAN_Rest_API_Common::get_formatted_contact_data( $contact ) ], __( 'Contact fetched successfully', 'wp-marketing-automations-pro' ) );	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Get_Contact_By_Id_Or_Email' );
