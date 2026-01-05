<?php

/**
 * Class BWFAN_Rest_API_Delete_Field
 */

namespace BWFAN\Rest_API;
class Delete_Field extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::DELETABLE;
		$this->route    = '/field/(?P<id>[\\d]+)';
		$this->required = [ 'id' ];
		$this->validate = [ 'field_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$field_id = $this->get_sanitized_arg( 'id', 'text_field' );

		// fetching field detail before deleting to send it in api response
		$deleting_field = \BWFAN_Model_Fields::get_field_by_id( $field_id );

		$delete_field = \BWFCRM_Fields::delete_field( absint( $field_id ) );

		if ( false === $delete_field ) {
			$this->error_response( '', 422, 'unknown_error' );
		}

		$field = [
			"ID"   => $field_id,
			"name" => $deleting_field['name']
		];

		return $this->success_response( [ 'fields' => $field ], __( 'Field deleted successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Delete_Field' );
