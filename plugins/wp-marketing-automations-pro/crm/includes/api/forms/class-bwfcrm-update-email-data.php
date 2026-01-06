<?php

class BWFCRM_API_Save_Email_Data extends BWFCRM_API_Base {

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
		$this->route         = '/form-feeds/(?P<feed_id>[\\d]+)/save-email-data';
		$this->response_code = 200;
	}

	public function process_api_call() {
		if ( isset( $this->args['content'] ) ) {
			$content    = BWFAN_Common::is_json( $this->args['content'] ) ? json_decode( $this->args['content'], true ) : [];
			$this->args = wp_parse_args( $content, $this->args );
		}

		$feed_id = $this->get_sanitized_arg( 'feed_id' );
		if ( empty( $feed_id ) ) {
			return $this->error_response( __( 'Invalid / Empty Feed ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$feed = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return $this->error_response( __( 'No Feed Exists / Invalid feed in DB', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$incentivize_email = $this->get_sanitized_arg( 'incentivize_email', 'bool' );

		$incentive_email = isset( $this->args['incentive_email'] ) ? $this->args['incentive_email'] : [];
		$incentive_email = is_array( $incentive_email ) ? $incentive_email : [];
		if ( true === $incentivize_email && 0 === count( $incentive_email ) ) {
			return $this->error_response( __( 'Incentive Email Required', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$marketing_status = $this->get_sanitized_arg( 'marketing_status', 'bool' );
		$redirect_url     = $this->get_sanitized_arg( 'redirect_url', 'text_field' );
		if ( empty( $redirect_url ) ) {
			$redirect_url = esc_url_raw( home_url( '/' ) );
		}
		$redirect_mode = $this->get_sanitized_arg( 'redirect_mode', 'key' );
		if ( empty( $redirect_mode ) ) {
			$redirect_mode = 'url';
		}

		$add_tag_enable = $this->get_sanitized_arg( 'add_tag_enable', 'bool' );

		$tag_to_add = isset( $this->args['tag_to_add'] ) ? $this->args['tag_to_add'] : [];
		$tag_to_add = is_array( $tag_to_add ) ? $tag_to_add : [];

		if ( is_array( $tag_to_add ) && ! empty( $tag_to_add ) ) {
			
			$tag_to_add = BWFCRM_Term::get_or_create_terms( $tag_to_add, BWFCRM_Term_Type::$TAG, true );

			/**Preparing tags */
			$tag_to_add = array_map( function ( $tag ) {
				$tag_data          = [];
				$tag_data['id']    = $tag->get_id();
				$tag_data['value'] = $tag->get_name();

				return $tag_data;
			}, $tag_to_add );
		}
		
		$status = $this->get_sanitized_arg( 'status' );
		if ( BWFCRM_Core()->forms->is_valid_status( $status ) ) {
			$feed->set_status( $status );
		}

		$not_send_to_subscribed = $this->get_sanitized_arg( 'not_send_to_subscribed', 'bool' );

		$feed->set_data( 'marketing_status', $marketing_status );
		$feed->set_data( 'incentivize_email', $incentivize_email );
		$feed->set_data( 'redirect_url', $redirect_url );
		$feed->set_data( 'redirect_mode', $redirect_mode );
		$feed->set_data( 'add_tag_enable', $add_tag_enable );
		$feed->set_data( 'tag_to_add', $tag_to_add );
		$feed->set_data( 'not_send_to_subscribed', $not_send_to_subscribed );

		if ( true === $incentivize_email ) {
			$feed->set_data( 'incentive_email', $incentive_email );
		}

		$feed->save();

		return $this->success_response( $feed->get_array(), __( 'Form updated', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_API_Save_Email_Data' );