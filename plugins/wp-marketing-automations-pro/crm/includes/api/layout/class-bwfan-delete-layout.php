<?php

class BWFCRM_Api_Delete_Layout extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/layout/(?P<layout_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'layout_id' => '' );
	}

	public function process_api_call() {

		$layout_id = ( int ) $this->get_sanitized_arg( 'layout_id', 'key' );

		if ( empty( $layout_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Layout ID is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** if the provided id doesn't exist */
		$already_exists = BWFAN_Model_Templates::bwfan_check_layout_exists( 'ID', $layout_id );
		if ( ! $already_exists ) {
			$this->response_code = 400;
			$response            = __( "Layout doesn't exists with ID : ", 'wp-marketing-automations-pro' ) . $layout_id;

			return $this->error_response( $response );
		}

		$delete_layout = BWFAN_Model_Templates::bwf_delete_layout( $layout_id );
		if ( false === $delete_layout ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the Layout', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Layout deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Layout' );