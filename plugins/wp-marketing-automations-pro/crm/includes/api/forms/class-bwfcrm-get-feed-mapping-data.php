<?php

class BWFCRM_API_Get_Feed_Mapping_data extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/form-feeds/(?P<feed_id>[\\d]+)/mapping-data';
	}

	public function process_api_call() {
		$feed_id = $this->get_sanitized_arg( 'feed_id' );
		$feed    = new BWFCRM_Form_Feed( $feed_id );
		$form    = BWFCRM_Core()->forms->get_form( $feed->get_source() );
		if ( ! $feed->is_feed_exists() || empty( $feed->get_source() ) || empty( $form ) ) {
			return $this->error_response( __( 'No Feed / Form Source Found for Feed ID: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		$fields = $form->get_form_fields( $feed );
		if ( is_wp_error( $fields ) ) {
			return $this->error_response( '', $fields );
		}

		$contact_fields = BWFCRM_Fields::get_groups_with_fields( true, true, true, true );
		if ( empty( $contact_fields ) ) {
			return $this->error_response( __( 'Unable to get contact fields for Feed ID: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		$mapping_data = array(
			'headers' => $form->format_form_fields_for_mapping( $fields ),
			'fields'  => $contact_fields
		);

		return $this->success_response( $mapping_data );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Feed_Mapping_data' );
