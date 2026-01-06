<?php

class BWFCRM_Api_Import_Templates extends BWFCRM_API_Base {

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
		$this->route         = '/templates/import/';
		$this->response_code = 200;
	}

	public function process_api_call() {

		$files = $this->args['files'];

		$this->response_code = 404;
		if ( empty( $files ) ) {
			return $this->error_response( [], __( 'Import File missing.', 'wp-marketing-automations-pro' ) );
		}

		$file_data = file_get_contents( $files['files']['tmp_name'] );

		$import_file_data = json_decode( $file_data, true );
		if ( empty( $import_file_data ) && ! is_array( $import_file_data ) ) {

			return $this->error_response( [], __( 'Import file data missing', 'wp-marketing-automations-pro' ) );
		}

		$template_id = BWFCRM_Templates::import( $import_file_data );
		if ( empty( $template_id ) ) {

			return $this->error_response( __( 'Invalid json file', 'wp-marketing-automations-pro' ) );
		}

		$this->response_code = 200;

		return $this->success_response( [ 'template_id' => $template_id ], __( 'Templates imported', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Import_Templates' );