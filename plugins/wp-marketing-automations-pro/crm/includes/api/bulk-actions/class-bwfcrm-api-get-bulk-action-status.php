<?php

/**
 * Get Bulk Action Status API
 */
class BWFCRM_Api_Get_Bulk_Action_Status extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/bulk-action/status/(?P<bulk_action_id>[\\d]+)';
		$this->request_args = array(
			'bulk_action_id' => array(
				'description' => __( 'Bulk Action ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Process API Call
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function process_api_call() {
		$id = $this->get_sanitized_arg( 'bulk_action_id' );
		if ( empty( $id ) ) {
			return $this->error_response( __( 'Invalid / Empty bulk action ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Check for bulk action entry */
		$data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $id );

		if ( empty( $data ) ) {
			return $this->error_response( __( 'Bulk Action not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Get bulk action status  */
		$action_status = BWFCRM_Core()->bulk_action->get_bulk_action_status( $id );

		if ( empty( $action_status ) ) {
			return $this->error_response( __( 'Bulk Action data to found with provided ID.', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$this->response_code = 200;

		return $this->success_response( $action_status );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Bulk_Action_Status' );
