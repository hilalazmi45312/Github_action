<?php

/**
 * Class BWFCRM_API_List_API_Keys_By_User_Id
 */

namespace BWFAN\Rest_API;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

class List_API_Keys_By_User_Id extends \BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::READABLE;
		$this->route  = 'get-api-keys-by-userid/(?P<user_id>[\\d]+)';
	}

	/**
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * processing the api request
	 */
	public function process_api_call() {
		$limit   = ! empty( $this->args['limit'] ) ? absint( $this->args['limit'] ) : 0;
		$offset  = ! empty( $this->args['offset'] ) ? absint( $this->args['offset'] ) : 0;
		$user_id = $this->get_sanitized_arg( 'user_id', 'text_field' );

		// checking for user existence
		$user_data = get_user_by( 'ID', $user_id );
		if ( empty( $user_id ) || empty( $user_data ) ) {
			return $this->error_response( __( 'Required parameter missing : No user id provided or user with this id does not exist', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data  = array( 'uid' => $user_id );
		$fetch = \BWFAN_Rest_API_DB_Model::fetch( array(), $data, $limit, $offset );

		if ( is_wp_error( $fetch ) ) {
			return $this->error_response( __( 'API key listing failed', 'wp-marketing-automations-pro' ), $fetch, 500 );
		}

		return $this->success_response( $fetch, __( 'API key listed successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFCRM_API_Loader::register( 'BWFAN\Rest_API\List_API_Keys_By_User_Id' );
