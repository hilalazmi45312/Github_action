<?php

/**
 * Class BWFAN_Rest_API_Add_Tags
 */

namespace BWFAN\Rest_API;
class Add_Tags extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::CREATABLE;
		$this->route    = '/tag/add';
		$this->required = [ 'tags' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		// tags can be an array of tag name or a string name
		$tags = isset( $this->args['tags'] ) ? $this->args['tags'] : array();

		// associating id with tag name
		$tag_data = array();
		if ( is_array( $tags ) ) {
			$tag_data = array_map( function ( $tag ) {
				return array( 'id' => 0, 'value' => $tag );
			}, $tags );
		} else {
			$tag_data['id']    = 0;
			$tag_data['value'] = $tags;
			$tag_data          = array( $tag_data );
		}

		$tags = \BWFCRM_Term::get_or_create_terms( $tag_data, \BWFCRM_Term_Type::$TAG, true, true );

		if ( is_wp_error( $tags ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		// return if tags not created or not existing
		if ( ! isset( $tags['existing'] ) || ! isset( $tags['created'] ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		//$existing_tags = \BWFCRM_Term::get_collection_array( $tags['existing'] );
		$created_tags  = \BWFCRM_Term::get_collection_array( $tags['created'] );
		//$all_tags      = array_merge( $created_tags, $existing_tags );

		// return if tags not created, already existing
		if ( empty( $created_tags ) ) {
			$this->error_response( null, 422, 'already_exists' );
		}

		return $this->success_response( [ 'tags' => $created_tags ], __( 'Tag(s) created successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Add_Tags' );
