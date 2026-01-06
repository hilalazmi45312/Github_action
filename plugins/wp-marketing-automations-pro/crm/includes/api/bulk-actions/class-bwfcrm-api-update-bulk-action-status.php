<?php

/**
 * Update bulk action status API
 */
class BWFCRM_Api_Update_Bulk_Action_Status extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/bulk-action/(?P<bulk_action_id>[\\d]+)/update-status';
		$this->request_args  = array(
			'bulk_action_id' => array(
				'description' => __( 'Bulk Action ID to update', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
		$this->response_code = 200;
	}

	public function process_api_call() {
		$bulk_action_id = $this->get_sanitized_arg( 'bulk_action_id' );
		$status         = $this->get_sanitized_arg( 'status', 'text_field', $this->args['status'] );
		if ( empty( $bulk_action_id ) ) {
			return $this->error_response( __( 'Invalid / Empty bulk action ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $bulk_action_id, false );

		if ( empty( $data ) ) {
			return $this->error_response( __( 'Bulk Action not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data['updated_at'] = current_time( 'mysql', 1 );

		if ( isset( $data['status'] ) ) {
			$data['status'] = intval( $status );
		}

		$result = BWFAN_Model_Bulk_Action::update_bulk_action_data( $bulk_action_id, $data );

		if ( intval( $status ) === 3 && ! empty( $result ) ) {
			if ( bwf_has_action_scheduled( 'bwfcrm_bulk_action_process', array( 'bulk_action_id' => absint( $bulk_action_id ) ), 'bwfcrm' ) ) {
				bwf_unschedule_actions( 'bwfcrm_bulk_action_process', array( 'bulk_action_id' => absint( $bulk_action_id ) ), 'bwfcrm' );
			}
		}

		if ( intval( $status ) === 1 && ! empty( $result ) ) {
			BWFCRM_Core()->bulk_action->schedule_bulk_action( $bulk_action_id );
		}

		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to update bulk action status', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [ 'id' => $result ], __( 'Successfully updated bulk action status', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_Bulk_Action_Status' );
