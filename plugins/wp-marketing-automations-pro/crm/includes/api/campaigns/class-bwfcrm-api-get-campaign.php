<?php
/**
 * Campaign Get API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Get API class
 */
class BWFCRM_API_Get_Campaign extends BWFCRM_API_Base {

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
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/broadcast/(?P<campaign_id>[\\d]+)';
		$this->request_args = array(
			'campaign_id' => array(
				'description' => __( 'Broadcast ID to retrieve', 'wp-marketing-automations-pro' ),
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
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$campaign_id = $this->get_sanitized_arg( 'campaign_id', 'key' );
		$ab_mode     = $this->get_sanitized_arg( 'ab_mode', 'bool' );
		$ping_worker = $this->get_sanitized_arg( 'ping_worker', 'bool' );
		if ( true === $ping_worker ) {
			BWFCRM_Common::ping_woofunnels_worker();
		}

		if ( 0 === intval( $campaign_id ) ) {
			$this->response_code = 404;
			$this->error_response( __( 'Invalid broadcast ID given', 'wp-marketing-automations-pro' ) );
		}

		$campaign_data = BWFAN_Model_Broadcast::get_campaign( $campaign_id, ! $ab_mode, $ab_mode );

		/** Check for template stats */
		if ( ! empty( $campaign_data['data']['data']['smart_send_stat'] ) ) {
			$campaign_data['data']['data']['smart_send_stat'] = BWFAN_Model_Broadcast::get_formatted_smart_stat( $campaign_data['data']['data']['smart_send_stat'], true );
		}
		$this->response_code = $campaign_data['status'];

		if ( ! $ab_mode ) {
			$campaign_data['data']['count'] = BWFCRM_Campaigns::get_broadcast_updated_contact_count( $campaign_data['data'] );
		}


		if ( 200 === intval( $campaign_data['status'] ) ) {
			return $this->success_response( $campaign_data['data'], $campaign_data['message'] );
		}

		return $this->error_response( $campaign_data['message'] );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Campaign' );
