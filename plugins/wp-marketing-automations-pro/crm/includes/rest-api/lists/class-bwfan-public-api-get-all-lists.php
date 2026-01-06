<?php

/**
 * Class BWFAN_Rest_API_Get_All_Lists
 */

namespace BWFAN\Rest_API;
class Get_All_Lists extends \BWFAN_Rest_API_Base {

	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::READABLE;
		$this->route  = '/lists';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$limit  = ! empty( $this->args['limit'] ) ? absint( $this->args['limit'] ) : 30;
		$offset = ! empty( $this->args['offset'] ) ? absint( $this->args['offset'] ) : 0;

		$lists = \BWFCRM_Lists::get_lists( array(), '', $offset, $limit );

		if ( empty( $lists ) ) {
			$this->error_response( null, 404, 'data_not_found' );
		}

		// creating array of id and name to return in the api response
		if ( is_array( $lists ) && ! empty( $lists ) ) {
			$lists = array_map( function ( $list ) {
				return array( 'ID' => $list['ID'], 'name' => $list['name'] );
			}, $lists );
		}
		return $this->success_response( [ 'lists' => $lists ], 'Lists fetched successfully' );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Get_All_Lists' );
