<?php

class BWFCRM_Api_Create_Audience extends BWFCRM_API_Base {

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
		$this->route         = '/audience';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'name' => '',
			'data' => [],
		);

		return $args;
	}

	public function process_api_call() {
		$name = $this->get_sanitized_arg( 'name', 'text_field', $this->args['name'] );

		if ( empty( $name ) ) {
			$this->response_code = 400;
			$response            = __( 'Name is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		if ( empty( $this->args['data'] ) || ( is_array( $this->args['data'] ) && empty( $this->args['data']['filters'] ) ) ) {
			$this->response_code = 400;
			$response            = __( 'Filters are mandatory.', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$audience       = new BWFCRM_Audience();
		$already_exists = $audience->check_audience_is_exists( $name );
		if ( absint( $already_exists ) > 0 ) {
			$name = "$name - $already_exists";
		}

		$create_time = current_time( 'mysql', 1 );
		$audience->set_audience( 0, $name, $this->args['data'], $create_time, $create_time );
		$audience->save();
		if ( empty( $audience->get_id() ) ) {
			$this->response_code = 500;

			return $this->error_response( '', $audience );
		}
		$audience_data = $audience->get_array();


		return $this->success_response( $audience_data, __( 'Audience created', 'wp-marketing-automations-pro' ) );

	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Audience' );