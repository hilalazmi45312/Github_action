<?php

class BWFCRM_Api_Update_Tag extends BWFCRM_API_Base {

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
		$this->route  = '/tags/(?P<tag_id>[\\d]+)';
	}

	public function default_args_values() {
		return array(
			'tag_id'   => '',
			'tag_name' => '',
		);
	}

	public function process_api_call() {
		$tag_id = $this->get_sanitized_arg( 'tag_id', 'text_field' );
		if ( empty( $tag_id ) ) {
			$this->response_code = 404;
			$response            = __( "Tag ID is missing", 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$tag_name = $this->get_sanitized_arg( 'tag_name', 'text_field' );
		if ( empty( $tag_name ) ) {
			$this->response_code = 404;
			$response            = __( "Tag name is required", 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$tag = new BWFCRM_Tag( intval( $tag_id ) );
		if ( ! $tag->is_exists() ) {
			$this->response_code = 404;
			$response            = __( 'Tag doesn\'t exists', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$current_name = $tag->get_name();
		if ( $tag_name !== $current_name ) {
			$already_exists = BWFCRM_Tag::get_terms( BWFCRM_Term_Type::$TAG, [], $tag_name, 0, 0, ARRAY_A, 'exact' );
			if ( ! empty( $already_exists ) ) {
				return $this->error_response( __( "Tag already exists with name: " . $tag_name, 'wp-marketing-automations-pro' ), null, 404 );
			}
			$tag->set_name( $tag_name );
		}

		if ( empty( $tag->save() ) ) {
			return $this->error_response( __( 'Unable to update the Tag', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( __( 'Tag updated', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_Tag' );
