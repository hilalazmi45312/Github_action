<?php

class BWFCRM_Api_Delete_List extends BWFCRM_API_Base {

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
		$this->route  = '/lists/(?P<list_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'list_id' => '' );
	}

	public function process_api_call() {

		$list_id = $this->get_sanitized_arg( 'list_id', 'key' );

		if ( empty( $list_id ) ) {
			$this->response_code = 404;
			$response            = __( 'List ID is mandatory ', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** @var checking if the provided id is list id or not $data */

		$check_list = BWFCRM_Lists::get_terms( BWFCRM_Term_Type::$LIST, array( $list_id ) );

		if ( empty( $check_list ) ) {
			$this->response_code = 404;
			$response            = __( "List not exist with ID #" . $list_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$delete_list = BWFCRM_Lists::delete_list( absint( $list_id ) );

		if ( false === $delete_list ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the list with ID #' . $list_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'List deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_List' );