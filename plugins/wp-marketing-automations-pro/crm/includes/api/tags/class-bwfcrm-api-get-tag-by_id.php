<?php

class BWFCRM_Api_Get_Tag_By_ID extends BWFCRM_API_Base {

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
		$this->route  = '/tags/(?P<tag_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'tag_id' => '' );
	}

	public function process_api_call() {
		$tag_id = $this->get_sanitized_arg( 'tag_id', 'key' );
		if ( empty( $tag_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Tag ID is missing ', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$tag_data = BWFAN_Model_Terms::get( absint( $tag_id ) );
		if ( empty( $tag_data ) ) {
			$this->response_code = 404;

			$response = __( 'No tag data found related with tag id:' . $tag_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Tag data found with tag id:' . $tag_id, 'wp-marketing-automations-pro' );

		return $this->success_response( $tag_data, $success_message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Tag_By_ID' );
