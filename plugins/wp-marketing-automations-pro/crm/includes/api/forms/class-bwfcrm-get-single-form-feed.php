<?php

class BWFCRM_API_Get_Single_Form_Feed extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/form-feeds/(?P<feed_id>[\\d]+)';
		$this->request_args = array(
			'feed_id' => array(
				'description' => __( 'Form Feed ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$feed_id = $this->get_sanitized_arg( 'feed_id' );
		if ( empty( $feed_id ) ) {
			return $this->error_response( __( 'Invalid / Empty Feed ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$feed = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return $this->error_response( __( 'No Feed Exists / Invalid feed in DB', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( $feed->get_array() );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Single_Form_Feed' );
