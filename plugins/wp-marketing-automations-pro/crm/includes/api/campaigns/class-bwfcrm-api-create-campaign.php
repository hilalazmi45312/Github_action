<?php
/**
 * Camapign Create API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Create API class
 */
class BWFCRM_API_Create_Campaign extends BWFCRM_API_Base {

	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/**
	 * Return class instance
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/broadcast';
	}

	/**
	 * Default campaign args.
	 */
	public function default_args_values() {
		return array(
			'title'       => '',
			'description' => '',
			'type'        => '',
			'created_by'  => '',
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$title      = ! empty( $this->get_sanitized_arg( 'title', 'text_field' ) ) ? $this->get_sanitized_arg( 'title', 'text_field' ) : '';
		$type       = ! empty( $this->get_sanitized_arg( 'type', 'key' ) ) ? $this->get_sanitized_arg( 'type', 'key' ) : 0;
//		$created_by = ! empty( $this->get_sanitized_arg( 'created_by', 'key' ) ) ? $this->get_sanitized_arg( 'created_by', 'key' ) : '';

		/** Create send to unopen broadcast */
		$createUnopen = $this->get_sanitized_arg( 'createUnopen', 'key' );
		if ( 1 !== absint( $type ) ) {
			$createUnopen = false;
		}

		if ( empty( $title ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Title is a mandatory field', 'wp-marketing-automations-pro' ) );
		}

		if ( intval( $type ) <= 0 || ! in_array( intval( $type ), array( 1, 2, 3 ), true ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Please select a type', 'wp-marketing-automations-pro' ) );
		}

		$created_by = get_current_user_id();

		if ( 0 === $created_by ) {
			$this->response_code = 404;

			return $this->error_response( __( 'User is not logged in.', 'wp-marketing-automations-pro' ) );
		}

		$description = ! empty( $this->get_sanitized_arg( 'description', 'text_field' ) ) ? $this->get_sanitized_arg( 'description', 'text_field' ) : '';

		$campaign_data = BWFAN_Model_Broadcast::create_campaign( $title, $description, $type, $created_by, $createUnopen );

		$this->response_code = $campaign_data['status'];

		if ( $campaign_data['status'] === 200 ) {
			return $this->success_response( $campaign_data['data'], __( 'Broadcast created', 'wp-marketing-automations-pro' ) );
		} else {
			return $this->error_response( __( 'Error occurred in creating a broadcast', 'wp-marketing-automations-pro' ), null, 500 );
		}
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Create_Campaign' );
