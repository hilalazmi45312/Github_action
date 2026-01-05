<?php

/**
 * Class BWFAN_Rest_API_Delete_Contact
 */

namespace BWFAN\Rest_API;
class Delete_Contact extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::DELETABLE;
		$this->route    = '/contact/(?P<id>[\\d]+)';
		$this->required = [ 'id' ];
		$this->validate = [ 'contact_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$id = $this->get_sanitized_arg( 'id', 'text_field' );

		$delete_contact = \BWFCRM_Model_Contact::delete_contact( $id );
		if ( ! $delete_contact ) {
			$this->error_response( '', 422, 'unknown_error' );
		}

		return $this->success_response( [], __( 'Contact deleted successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Delete_Contact' );
