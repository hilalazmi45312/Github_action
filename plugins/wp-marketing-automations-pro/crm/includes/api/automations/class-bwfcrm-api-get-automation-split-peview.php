<?php

class BWFCRM_API_Get_Automation_split_Preview extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = 'automation/(?P<automation_id>[\\d]+)/split/(?P<split_id>[\\d]+)/preview';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation id to get path stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'split_id'      => array(
				'description' => __( 'split id to get preview', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );
		$split_id      = $this->get_sanitized_arg( 'split_id' );

		if ( empty( $automation_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}
		$split_data          = BWFAN_Model_Automation_Step::get_step_data( $split_id );
		$split_status        = isset( $split_data['status'] ) ? intval( $split_data['status'] ) : '';
		$this->response_code = 200;
		/** Split step is archive */
		if ( 4 === $split_status ) {
			$data = isset( $split_data['data']['sidebarData']['preview_data'] ) ? $split_data['data']['sidebarData']['preview_data'] : [];

			return $this->success_response( $data, __( 'Split step preview', 'wp-marketing-automations-pro' ) );
		}

		$data = BWFCRM_Automations::get_split_preview_data( $automation_id, $split_id, $automation_obj );

		return $this->success_response( $data, __( 'Split step preview', 'wp-marketing-automations-pro' ) );
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Automation_split_Preview' );
