<?php

/**
 * Class BWFAN_Rest_API_Get_All_Fields
 */

namespace BWFAN\Rest_API;
class Get_All_Fields extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::READABLE;
		$this->route  = '/fields';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		// Explicitly set type to 1 for contact fields if not specified
		$type = ! empty( $this->args['type'] ) ? $this->args['type'] : null;

		$fields = \BWFCRM_Fields::get_custom_fields( '', 1, null, false, null, $type );

		if ( empty( $fields ) ) {
			$this->error_response( null, '404', 'data_not_found' );
		}

		return $this->success_response( array( 'fields' => $fields ), __( 'Fields listed successfully', 'wp-marketing-automations-pro' ) );
	}

}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Get_All_Fields' );
