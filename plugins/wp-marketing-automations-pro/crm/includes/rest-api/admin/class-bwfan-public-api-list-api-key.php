<?php

/**
 * Class BWFCRM_API_List_API_Keys
 */

namespace BWFAN\Rest_API;
class List_API_Keys extends \BWFCRM_API_Base {
	public static $ins;
	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::READABLE;
		$this->route  = '/get-api-keys';
	}

	/**
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 * prcessing the api request
	 */
	public function process_api_call() {
		$limit  = ! empty( $this->args['limit'] ) ? absint( $this->args['limit'] ) : 30;
		$offset = ! empty( $this->args['offset'] ) ? absint( $this->args['offset'] ) : 0;
		$search = ! empty( $this->args['search'] ) ? $this->args['search'] : '';

		$fetch = \BWFAN_Rest_API_DB_Model::fetch( array(), array(), $limit, $offset, $search, true );

		if ( is_wp_error( $fetch ) ) {
			return $this->error_response( __( 'API key could not be listed', 'wp-marketing-automations-pro' ), $fetch, 500 );
		}


		$this->total_count = isset( $fetch['total'] ) ? intval( $fetch['total'] ) : 0;
		$data              = isset( $fetch['data'] ) ? $fetch['data'] : [];

		// formatted returned data
		$data = array_map( function ( $data ) {
			if ( isset( $data['api_key'] ) ) {
				$data['api_key'] = 'xxxx' . substr( $data['api_key'], - 6 );
			}

			return $data;
		}, $data );

		return $this->success_response( $data, __( 'API key listed successful', 'wp-marketing-automations-pro' ) );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

\BWFCRM_API_Loader::register( 'BWFAN\Rest_API\List_API_Keys' );
