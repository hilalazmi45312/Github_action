<?php
/**
 * Campaign Delete API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Delete API class
 */
class BWFCRM_API_Delete_Campaign extends BWFCRM_API_Base {

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
		$this->method       = WP_REST_Server::DELETABLE;
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

		if ( intval( $campaign_id ) > 0 ) {
			$campaign_data       = BWFAN_Model_Broadcast::delete_campaign( $campaign_id );
			$this->response_code = $campaign_data['status'];
			if ( $campaign_data['status'] === 200 ) {
				return $this->success_response( $campaign_data['data'], $campaign_data['message'] );
			} else {
				return $this->error_response( $campaign_data['message'] );
			}
		} else {
			$this->response_code = 404;
			return $this->error_response( __( 'Unable to delete a broadcast, invalid ID', 'wp-marketing-automations-pro' ) );

		}
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Delete_Campaign' );
