<?php

/**
 * Delete link trigger file
 */
class BWFCRM_Api_Delete_Link_Trigger extends BWFCRM_API_Base {

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
		$this->route  = '/link-triggers/delete';
	}

	public function default_args_values() {
		return array(
			'ids' => [],
		);
	}

	public function process_api_call() {
		$link_ids = $this->args['ids'];

		if ( empty( $link_ids ) || ! is_array( $link_ids ) ) {
			return $this->error_response( __( 'Links ids are missing.', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** delete links */
		$delete_link = BWFAN_Model_Link_Triggers::delete_multiple_links( $link_ids );

		if ( false === $delete_link ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the link triggers', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Link triggers deleted.', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Link_Trigger' );