<?php
/**
 * Campaign Save Content API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Save Content API class
 */
class BWFCRM_API_Save_Content extends BWFCRM_API_Base {
	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/***
	 * Total count
	 *
	 * @return BWFCRM_API_Save_Content
	 * @var int
	 *
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
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/broadcast/(?P<broadcast_id>[\\d]+)/save-content';
	}

	/**
	 * Default args
	 */
	public function default_args_values() {
		return array(
			'broadcast_id' => 0,
			'content'      => array()
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$broadcast_id = $this->get_sanitized_arg( 'broadcast_id', 'key' );
		if ( empty( $broadcast_id ) ) {
			return $this->error_response( __( 'Unable to save broadcast, Broadcast ID missing', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Filter data */
		$data = isset( $this->args['content'] ) ? $this->args['content'] : [];
		$data = BWFAN_Common::is_json( $data ) ? json_decode( $data, true ) : $data;

		$result = BWFCRM_Core()->campaigns->save_broadcast_content( $broadcast_id, $data );
		if ( is_wp_error( $result ) ) {
			return $this->error_response( '', $result, $result->get_error_code() );
		}

		return $this->success_response( [], 'Broadcast Content Saved!' );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Save_Content' );
