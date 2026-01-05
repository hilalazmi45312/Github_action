<?php

/**
 * Class BWFAN_Rest_API_Get_All_Tags
 */

namespace BWFAN\Rest_API;
class Get_All_Tags extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::READABLE;
		$this->route  = '/tags';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$limit  = ! empty( $this->args['limit'] ) ? $this->args['limit'] : 30;
		$offset = ! empty( $this->args['offset'] ) ? $this->args['offset'] : 0;

		$tags = \BWFCRM_Tag::get_tags( array(), '', $offset, $limit );

		if ( is_wp_error( $tags ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		if ( empty( $tags ) ) {
			$this->error_response( null, 404, 'data_not_found' );
		}

		// creating array of only id and name to return to api response
		if ( is_array( $tags ) ) {
			$tags = array_map( function ( $tag ) {
				return array( 'ID' => $tag['ID'], 'name' => $tag['name'] );
			}, $tags );
		}

		return $this->success_response( [ 'tags' => $tags ], __( 'Tag(s) Listed successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Get_All_Tags' );
