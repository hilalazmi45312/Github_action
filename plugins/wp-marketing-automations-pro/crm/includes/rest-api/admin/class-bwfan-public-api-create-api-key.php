<?php

/**
 * Class BWFCRM_API_Create_API_Keys
 */

namespace BWFAN\Rest_API;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

class Create_API_Keys extends \BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::CREATABLE;
		$this->route  = 'create-api-key/(?P<user_id>[\\d]+)';
	}

	/**
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * processing the api request
	 */
	public function process_api_call() {
		$user_id     = $this->get_sanitized_arg( 'user_id', 'text_field' );
		$permission  = $this->get_sanitized_arg( 'permission', 'text_field' );
		$description = $this->get_sanitized_arg( 'description', 'text_field' );
		$status      = $this->get_sanitized_arg( 'status', 'text_field' );
		$user_data   = get_user_by( 'ID', $user_id );

		if ( empty( $user_id ) || empty( $user_data ) ) {
			return $this->error_response( __( 'Required Parameter Missing : User ID not provided or user with this id does not exist', 'wp-marketing-automations-pro' ), null, 400 );
		}

		if ( empty( $permission ) ) {
			return $this->error_response( __( 'Required Parameter Missing : No permission provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		if ( strlen( $description ) > 200 ) {
			return $this->error_response( __( 'Description must be no longer than 200 characters', 'wp-marketing-automations-pro' ), null, 400 );
		}

		// creating array of data
		$data = [
			'uid'        => $user_id,
			'api_key'    => md5( $user_id . time() ),
			'created_by' => get_current_user_id() ? get_current_user_id() : 0,
			'created_at' => current_time( 'mysql', 1 ),
			'permission' => $permission,
		];

		// storing description in the array if provided
		if ( ! empty( $description ) ) {
			$data['description'] = $description;
		}

		// storing status in the array if provided : 1 - active | 2 - revoked, default - 1
		if ( ! empty( $status ) ) {
			$data['status'] = $status;
		}

		$insert = \BWFAN_Rest_API_DB_Model::insert( $data );

		if ( is_wp_error( $insert ) ) {
			return $this->error_response( __( 'API key not created', 'wp-marketing-automations-pro' ), $insert, 500 );
		}

		return $this->success_response( $data, __( 'API key created successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFCRM_API_Loader::register( 'BWFAN\Rest_API\Create_API_Keys' );
