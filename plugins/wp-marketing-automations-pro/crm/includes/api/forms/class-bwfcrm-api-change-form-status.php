<?php

class BWFCRM_API_Change_Form_Status extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::EDITABLE;
		$this->route         = '/form-feeds/(?P<feed_id>[\\d]+)/status';
		$this->response_code = 200;
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

		$status = $this->get_sanitized_arg( 'status' );
		if ( ! BWFCRM_Core()->forms->is_valid_status( $status ) ) {
			return $this->error_response( __( 'Invalid status', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$feed->set_status( $status );
		$feed->save();

		return $this->success_response( $feed->get_array(), __( 'Form Status Changed', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_API_Change_Form_Status' );