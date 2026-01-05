<?php

class BWFCRM_Api_Create_Tag extends BWFCRM_API_Base {

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
		$this->route         = '/tags';
		$this->response_code = 200;
	}

	public function default_args_values() {
		return array( 'tags' => [] );
	}

	public function process_api_call() {
		$tags = $this->get_sanitized_arg( '', 'text_field', $this->args['tags'] );
		/** IN CASE TAGS PARAMS ARE MISSING **/
		if ( empty( $tags ) ) {
			$this->response_code = 400;
			$response            = __( 'Tag is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$tag_data = array();
		foreach ( $tags as $key => $tag ) {
			if ( ! isset( $tag_data[ $key ] ) ) {
				$tag_data[ $key ] = [];
			}
			$tag_data[ $key ]['id']    = 0;
			$tag_data[ $key ]['value'] = $tag;
		}
		$tags = BWFCRM_Term::get_or_create_terms( $tag_data, BWFCRM_Term_Type::$TAG, true, true );
		if ( is_wp_error( $tags ) ) {
			$this->response_code = 500;

			return $this->error_response( '', $tags );
		}

		if ( ! isset( $tags['existing'] ) || ! isset( $tags['created'] ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Some error occurred', 'wp-marketing-automations-pro' ) );
		}

		$existing_tags = BWFCRM_Term::get_collection_array( $tags['existing'] );
		$created_tags  = BWFCRM_Term::get_collection_array( $tags['created'] );
		$all_tags      = array_merge( $existing_tags, $created_tags );

		if ( empty( $created_tags ) ) {
			return $this->success_response( $all_tags, __( 'Provided tag already exists', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( $all_tags, __( 'Tag created', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Tag' );
