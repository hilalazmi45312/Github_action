<?php

class BWFCRM_API_Upload_Image extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/upload-image';
	}

	public function process_api_call() {
		$files = $this->args['files'];
		if ( empty( $files ) || ! is_array( $files['image'] ) || ! is_uploaded_file( $files['image']['tmp_name'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Not any image file found for import', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$url = BWFCRM_Core()->email_editor->upload_media( $files['image'] );
		if ( empty( $url ) || is_wp_error( $url ) ) {
			return $this->error_response( __( 'Unable to upload image file', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( $url, __( 'Headers Data', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Upload_Image' );
