<?php

/**
 * Class BWFCRM_API_Update_API_Keys
 */

namespace BWFAN\Rest_API;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

class Update_API_Keys extends \BWFCRM_API_Base {
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
		$this->route  = '/update-api-key/(?P<api_key_id>[\\d]+)';
	}

	/**
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * processing the api request
	 */
	public function process_api_call() {
		$api_key_id     = $this->get_sanitized_arg( 'api_key_id', 'text_field' );
		$permission     = $this->get_sanitized_arg( 'permission', 'text_field' );
		$description    = $this->get_sanitized_arg( 'description', 'text_field' );
		$status         = $this->get_sanitized_arg( 'status', 'text_field' );
		$update_api_key = $this->get_sanitized_arg( 'update_api_key', 'bool' );

		if ( empty( $api_key_id ) ) {
			return $this->error_response( __( 'Required parameter missing : No api key ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		if ( strlen( $description ) > 200 ) {
			return $this->error_response( __( 'Description must be no longer than 200 characters', 'wp-marketing-automations-pro' ), null, 400 );
		}

		// checking for api key existence
		$api_key_exists = \BWFAN_Rest_API_DB_Model::fetch( array(), array( 'id' => $api_key_id ) );
		if ( empty( $api_key_exists ) ) {
			return $this->error_response( __( 'API key does not exist with ID #' . $api_key_id, 'wp-marketing-automations-pro' ), null, 400 );
		}

		$where = array( 'id' => $api_key_id );
		$data  = [
			'created_at' => current_time( 'mysql', 1 ),
		];

		// generate new api key, if opted to change the api key
		if ( ! empty( $update_api_key ) && true === $update_api_key ) {
			$data['api_key'] = md5( $api_key_exists[0]['uid'] . time() );
		}

		if ( ! empty( $permission ) ) {
			$data['permission'] = $permission;
		}

		if ( ! empty( $description ) ) {
			$data['description'] = $description;
		}
		if ( ! empty( $status ) ) {
			$data['status'] = $status;
		}

		$update = \BWFAN_Rest_API_DB_Model::update( $data, $where );

		if ( is_wp_error( $update ) ) {
			return $this->error_response( __( 'API key update failed', 'wp-marketing-automations-pro' ), $update, 500 );
		}

		return $this->success_response( $data, __( 'API key updated successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFCRM_API_Loader::register( 'BWFAN\Rest_API\Update_API_Keys' );
