<?php
/**
 * Campaign Get API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Get API class
 */
class BWFCRM_API_Get_Broadcast_Overview_Stats extends BWFCRM_API_Base {

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
		$this->route        = '/broadcast/(?P<broadcast_id>[\\d]+)/overview-stats';
		$this->request_args = array(
			'broadcast_id' => array(
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
			'broadcast_id' => 0,
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$broadcast_id = $this->get_sanitized_arg( 'broadcast_id', 'key' );
		if ( empty( $broadcast_id ) ) {
			return $this->error_response( __( 'Broadcast not found', 'wp-marketing-automations-pro' ), null, 404 );
		}

		$broadcast     = BWFAN_Model_Broadcast::get_broadcast_basic_stats( $broadcast_id );
		// $last_modified = isset( $broadcast['last_modified'] ) && ! empty( $broadcast['last_modified'] ) ? strtotime( $broadcast['last_modified'] ) : false;

		/** Ping worker if last modified is greater than 5 seconds */
		// if ( ! empty( $last_modified ) && ( time() - $last_modified ) > 5 ) {
		// 	BWFCRM_Common::reschedule_broadcast_action();
		// }

		return $this->success_response( $broadcast );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Broadcast_Overview_Stats' );
