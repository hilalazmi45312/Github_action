<?php

class BWFCRM_Api_Delete_Tag extends BWFCRM_API_Base {

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
		$this->route  = '/tags/(?P<tag_id>[\\d]+)';
	}

	public function default_args_values() {
		return array( 'tag_id' => '' );
	}

	public function process_api_call() {
		$tag_id = $this->get_sanitized_arg( 'tag_id', 'key' );
		if ( empty( $tag_id ) ) {
			$this->response_code = 404;
			$response            = __( 'Tag ID is missing', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		/** Checking if the provided id is tag id or not $data */
		$check_tag = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$TAG, array( $tag_id ) );
		if ( empty( $check_tag ) ) {
			$this->response_code = 404;
			$response            = __( "Tag not exist with given ID #" . $tag_id, 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$delete_tag = BWFCRM_Tag::delete_tag( absint( $tag_id ) );
		if ( false === $delete_tag ) {
			$this->response_code = 404;

			$response = __( 'Unable to delete the tag', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$this->response_code = 200;
		$success_message     = __( 'Tag deleted', 'wp-marketing-automations-pro' );

		return $this->success_response( [], $success_message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Tag' );
