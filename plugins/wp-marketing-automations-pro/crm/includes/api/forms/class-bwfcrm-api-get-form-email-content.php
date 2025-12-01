<?php
/**
 * Broadcast Editor Content API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Get Broadcast Editor Content API class
 */
class BWFCRM_API_Get_Form_Email_Content extends BWFCRM_API_Base {

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
		$this->route        = '/form-feeds/(?P<feed_id>[\\d]+)/get-email-content';
		$this->request_args = array(
			'feed_id' => array(
				'description' => __( 'Form Feed ID to retrieve', 'wp-marketing-automations-pro' ),
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
		$feed_id = $this->get_sanitized_arg( 'feed_id' );
		if ( empty( $feed_id ) ) {
			return $this->error_response( __( 'Invalid / Empty Feed ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$feed = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return $this->error_response( __( 'No Feed Exists / Invalid feed in DB', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$email = $feed->get_data( 'incentive_email' );
		if ( ! is_array( $email ) || ! isset( $email['content'] ) || empty( $email['content'] ) ) {
			return $this->error_response( __( 'No Incentive Email found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$content        = is_array( $email['content'] ) && ! empty( $email['content'][0] ) ? $email['content'][0] : [];
		$editor_content = ! is_array( $content ) || empty( $content['editor'] ) || ! is_array( $content['editor'] ) ? [] : $content['editor'];

		return $this->success_response( $editor_content );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Form_Email_Content' );
