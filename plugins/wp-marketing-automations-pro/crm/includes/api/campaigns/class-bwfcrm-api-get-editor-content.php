<?php
/**
 * Broadcast Editor Content API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Get Broadcast Editor Content API class
 */
class BWFCRM_API_Get_Editor_Content extends BWFCRM_API_Base {

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
		$this->route        = '/broadcast/(?P<broadcast_id>[\\d]+)/content/(?P<content_id>[\\d]+)';
		$this->request_args = array(
			'broadcast_id' => array(
				'description' => __( 'Broadcast ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'content_id'   => array(
				'description' => __( 'A/B Content index\'s data to retreive', 'wp-marketing-automations-pro' ),
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
		$content_id   = $this->get_sanitized_arg( 'content_id', 'key' );
		if ( empty( $broadcast_id ) || ( empty( $content_id ) && 0 !== absint( $content_id ) ) ) {
			return $this->error_response( __( 'Invalid Broadcast ID / Content Index given', 'wp-marketing-automations-pro' ) );
		}

		$content = BWFCRM_Core()->campaigns->get_editor_content( $broadcast_id, $content_id );
		if ( ! is_array( $content ) || empty( $content ) ) {
			$content = [];
		}

		return $this->success_response( $content );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Editor_Content' );
