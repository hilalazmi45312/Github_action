<?php

/**
 * Class BWFCRM_API_Delete_API_Keys
 */

namespace BWFAN\Rest_API;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

class Delete_API_Keys extends \BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::DELETABLE;
		$this->route  = '/delete-api-key/(?P<api_key_id>[\\d]+)';
	}

	/**
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * processing api request
	 */
	public function process_api_call() {
		$api_key_id = $this->get_sanitized_arg( 'api_key_id', 'text_field' );

		if ( empty( $api_key_id ) ) {
			return $this->error_response( __( 'Required parameter missing : No API key ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data = [
			'id' => $api_key_id,
		];

		// checking for api key existence by api key id
		$api_key_exists = \BWFAN_Rest_API_DB_Model::fetch( array(), array( 'id' => $api_key_id ) );

		if ( empty( $api_key_exists ) ) {
			return $this->error_response( __( 'API key does not exist with ID #' . $api_key_id, 'wp-marketing-automations-pro' ), null, 400 );
		}

		$delete = \BWFAN_Rest_API_DB_Model::delete( $data );

		if ( is_wp_error( $delete ) ) {
			return $this->error_response( __( 'API key could not be deleted', 'wp-marketing-automations-pro' ), $delete, 500 );
		}

		return $this->success_response( $data, __( 'API key deleted successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFCRM_API_Loader::register( 'BWFAN\Rest_API\Delete_API_Keys' );
