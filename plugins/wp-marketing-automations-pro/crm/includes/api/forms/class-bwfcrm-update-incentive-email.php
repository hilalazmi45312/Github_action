<?php

class BWFCRM_API_Update_Incentive_Email extends BWFCRM_API_Base {

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
		$this->route         = '/form-feeds/(?P<feed_id>[\\d]+)/save-email-content';
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
		$content = isset( $this->args['content'] ) ? $this->args['content'] : [];
		$content = BWFAN_Common::is_json( $content ) ? json_decode( $content, true ) : $content;

		$content = is_array( $content ) ? $content : [];
		if ( 0 === count( $content ) ) {
			return $this->error_response( __( 'Incentive Email Required', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$saved_email = $feed->get_data( 'incentive_email' );
		if ( ! is_array( $saved_email ) ) {
			$saved_email = [];
		}

		$saved_email['content'] = ! empty( $content ) ? $content : array();
		$feed->set_data( 'incentive_email', $saved_email );

		$feed->save();

		return $this->success_response( $feed->get_array(), __( 'Form updated', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_API_Update_Incentive_Email' );