<?php
/**
 * Campaign Clone API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Clone API class
 */
class BWFCRM_API_Clone_Campaign extends BWFCRM_API_Base {

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
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/broadcast/(?P<campaign_id>[\\d]+)/clone';
		$this->request_args = array(
			'campaign_id' => array(
				'description' => __( 'Broadcast ID whose data to be cloned.', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'created_by'  => array(
				'description' => __( 'User ID who is cloning contact.', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'campaign_id' => 0,
			'created_by'  => '',
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$campaign_id    = $this->get_sanitized_arg( 'campaign_id', 'key' );
		$send_to_unopen = $this->get_sanitized_arg( 'send_to_unopen', 'bool' );
		$created_by     = $this->get_sanitized_arg( 'created_by', 'key' );
		$created_by     = ! empty( $created_by ) ? $created_by : '';
		$user_exists    = (bool) get_users(
			array(
				'include' => $created_by,
				'fields'  => 'ID',
			)
		);

		if ( ! $user_exists ) {
			$this->response_code = 404;
			return $this->error_response( __( 'User does not exists with provided ID', 'wp-marketing-automations-pro' ) );
		}

		if ( intval( $campaign_id ) > 0 ) {
			$campaign_data       = BWFAN_Model_Broadcast::clone_campaign( $campaign_id, $created_by, $send_to_unopen );
			$this->response_code = $campaign_data['status'];
			if ( $campaign_data['status'] === 200 ) {
				return $this->success_response( $campaign_data['data'], $campaign_data['message'] );
			} else {
				return $this->error_response( $campaign_data['message'] );
			}
		} else {
			$this->response_code = 404;
			return $this->error_response( __( 'Please provide a valid broadcast id.', 'wp-marketing-automations-pro' ) );
		}
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Clone_Campaign' );
