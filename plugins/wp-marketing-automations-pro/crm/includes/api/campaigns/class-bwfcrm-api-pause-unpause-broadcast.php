<?php
/**
 * Campaign Test Mail API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Test Mail API class
 */
class BWFCRM_API_Pause_Unpause_Broadcast extends BWFCRM_API_Base {

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
		$this->route        = '/broadcast/(?P<campaign_id>[\\d]+)/pause-unpause';
		$this->request_args = array(
			'campaign_id' => array(
				'description' => __( 'Broadcast ID whose state has to be changes.', 'wp-marketing-automations-pro' ),
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
			'status'      => ''
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$campaign_id   = $this->get_sanitized_arg( 'campaign_id', 'key' );
		$status        = 0;
		$campaign_data = false;
		if ( ! empty( $this->args['status'] ) ) {
			$status = intval( $this->args['status'] );
		}

		$this->response_code = 404;
		if ( intval( $campaign_id ) > 0 ) {
			if ( 3 === $status ) {
				$campaign_data = BWFAN_Model_Broadcast::update_status_to_ongoing( $campaign_id );
			} elseif ( 6 === $status ) {
				$campaign_data = BWFAN_Model_Broadcast::update_status_to_pause( $campaign_id );
				BWFCRM_Broadcast_Processing::action_schedule_save_last_sent( $campaign_id, false );
			} elseif ( 1 === $status ) {
				$campaign_data = BWFAN_Model_Broadcast::update_status_to_draft( $campaign_id );
			} elseif ( 7 === $status ) {
				$campaign_data = BWFAN_Model_Broadcast::update_status_to_cancel( $campaign_id );
			}

			if ( $campaign_data ) {
				$this->response_code = 200;

				return $this->success_response( '', __( 'Successfully updated broadcast status', 'wp-marketing-automations-pro' ) );
			}

			return $this->error_response( __( 'Unable to set broadcast status.', 'wp-marketing-automations-pro' ) );
		}

		return $this->error_response( __( 'Invalid broadcast ID provided', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Pause_Unpause_Broadcast' );
