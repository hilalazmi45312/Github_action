<?php

class BWFCRM_Api_Delete_Audience extends BWFCRM_API_Base {

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
		$this->route  = '/audience/(?P<audience_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'audience_id' => '' );
	}

	public function process_api_call() {

		$audience_id = $this->get_sanitized_arg( 'audience_id', 'key' );

		if ( empty( $audience_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Audience ID is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** if the provided id is tag id or not $data */

		$audience = new BWFCRM_Audience( $audience_id );
		$audience->is_audience_exists();

		if ( ! $audience->is_audience_exists() ) {
			$this->response_code = 404;
			$response            = __( "Audience not exist with given ID #" . $audience_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$delete_audince = BWFAN_Model_Terms::delete_term( $audience_id );
		if ( false === $delete_audince ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the Audience', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Audience deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Audience' );