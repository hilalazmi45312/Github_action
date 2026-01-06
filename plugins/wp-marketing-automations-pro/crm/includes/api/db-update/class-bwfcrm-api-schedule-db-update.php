<?php

class BWFCRM_API_Schedule_Db_Update extends BWFCRM_API_Base {
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
		$this->route  = '/db-update';
	}

	/**
	 * Process API call
	 *
	 * @return mixed
	 */
	public function process_api_call() {
		$status   = isset( $this->args['status'] ) ? $this->get_sanitized_arg( 'status', 'text_field', $this->args['status'] ) : 1;
		$response = false;

		$ins = BWFAN_Pro_DB_Update::get_instance();
		switch ( intval( $status ) ) {
			case 2:
				$response = $ins->start_db_update();

				BWFCRM_Common::ping_woofunnels_worker();
				break;
			case 0:
				$response = $ins->dismiss_db_update();
				break;
			default:
				break;
		}

		/** Error */
		if ( ! $response ) {
			$this->response_code = 404;
			$response            = __( "Unable to schedule action.", 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** Success **/
		return $this->success_response( [ 'status' => $response ], '' );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Schedule_Db_Update' );
