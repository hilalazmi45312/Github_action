<?php

class BWFCRM_Api_Delete_Bulk_Action extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::DELETABLE;
		$this->route  = '/bulk-actions/delete';
	}

	public function default_args_values() {
		return array(
			'ids' => [],
		);
	}

	public function process_api_call() {
		$ids = $this->args['ids'];

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return $this->error_response( __( 'Bulk actions ids are missing.', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** delete actions */
		$deleted = BWFAN_Model_Bulk_Action::delete_multiple_bulk_actions( $ids );

		if ( false === $deleted ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the bulk actions', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Bulk action deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Bulk_Action' );
