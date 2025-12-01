<?php

class BWFCRM_Api_Get_Audience_By_ID extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/audience/(?P<audience_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'audience_id' => '' );
	}

	public function process_api_call() {

		$audience_id = $this->get_sanitized_arg( 'audience_id', 'key' );

		if ( empty( $audience_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Audience ID is mandatory ', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$audience = new BWFCRM_Audience( absint( $audience_id ) );
		if ( ! $audience->is_audience_exists() ) {
			$this->response_code = 404;
			$response            = __( "Audience not exist with given ID #" . $audience_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$audience_data = $audience->get_array();

		if ( empty( $audience_data ) ) {
			$this->response_code = 404;

			$response = __( 'No audience data found related with audience id:' . $audience_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}
		$audience_data['data'] = json_decode( $audience_data['data'] );
		$this->response_code   = 200;
		$success_message       = __( 'Audience data found with audience id:' . $audience_id, 'wp-marketing-automations-pro' );

		return $this->success_response( $audience_data, $success_message );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Audience_By_ID' );