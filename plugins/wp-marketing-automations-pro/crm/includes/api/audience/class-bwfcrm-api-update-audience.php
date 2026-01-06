<?php

class BWFCRM_Api_Update_Audience extends BWFCRM_API_Base {

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
		$this->route  = '/audience/(?P<audience_id>[\\d]+)';
	}

	public function default_args_values() {
		$args = array(
			'audience_id' => '',
			'name'        => '',
			'data'        => ''
		);

		return $args;
	}

	public function process_api_call() {

		$audience_id = $this->get_sanitized_arg( 'audience_id', 'text_field' );

		if ( empty( $audience_id ) ) {
			$this->response_code = 404;
			$response            = __( "Audience Id is mandatory", 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$name = $this->get_sanitized_arg( 'name', 'text_field' );

		if ( empty( $name ) ) {
			$this->response_code = 404;
			$response            = __( "Audience name is Mandatory", 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** if the provided id is audience id or not $data */
		$audience = new BWFCRM_Audience( absint( $audience_id ) );
		if ( ! $audience->is_audience_exists() ) {
			$this->response_code = 404;
			$response            = __( "Audience not exist with given ID #" . $audience_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}
		$updated_time = current_time( 'mysql', 1 );
		$audience->set_audience( $audience_id, $name, $this->args['data'], '', $updated_time );
		$saved = $audience->save();


		if ( 0 === $saved ) {
			$response            = __( 'Unable to update audience with id ' . $audience_id, 'wp-marketing-automations-pro' );
			$this->response_code = 400;

			return $this->error_response( $response );
		}

		$response = __( 'Audience updated', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $response );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_Audience' );