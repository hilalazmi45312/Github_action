<?php

class BWFCRM_API_Update_Form_Feed extends BWFCRM_API_Base {

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
		$this->route         = '/form-feeds/(?P<feed_id>[\\d]+)';
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

		$mapped_fields = isset( $this->args['map'] ) && is_array( $this->args['map'] ) ? $this->args['map'] : [];
		if ( count( $mapped_fields ) > 0 && ! in_array( 'email', $mapped_fields, true ) ) {
			return $this->error_response( __( 'Email mapping is required', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$title = isset( $this->args['title'] ) && ! empty( $this->args['title'] ) ? $this->get_sanitized_arg( 'title', 'text_field' ) : '';

		$tags = isset( $this->args['tags'] ) && is_array( $this->args['tags'] ) ? $this->args['tags'] : [];
		$tags = ( empty( $tags ) && isset( $this->args['data'] ) && isset( $this->args['data']['tags'] ) ) ? $this->args['data']['tags'] : $tags;

		$lists = isset( $this->args['lists'] ) && is_array( $this->args['lists'] ) ? $this->args['lists'] : [];
		$lists = ( empty( $lists ) && isset( $this->args['data'] ) && isset( $this->args['data']['lists'] ) ) ? $this->args['data']['lists'] : $lists;

		$update_existing          = $this->get_sanitized_arg( 'update_existing', 'bool' );
		$trigger_events           = $this->get_sanitized_arg( 'trigger_events', 'bool' );
		$dont_update_blank_values = $this->get_sanitized_arg( 'dont_update_blank', 'bool' );

		$update_existing = ( isset( $this->args['data'] ) && isset( $this->args['data']['update_existing'] ) ) ? $this->args['data']['update_existing'] : $update_existing;
		$trigger_events  = ( isset( $this->args['data'] ) && isset( $this->args['data']['trigger_events'] ) ) ? $this->args['data']['trigger_events'] : $trigger_events;

		$dont_update_blank_values = ( isset( $this->args['data'] ) && isset( $this->args['data']['dont_update_blank'] ) ) ? $this->args['data']['dont_update_blank'] : $dont_update_blank_values;

		/** Create Tags if not exists */
		if ( is_array( $tags ) && ! empty( $tags ) && method_exists( 'BWFAN_PRO_Common', 'get_or_create_terms' ) ) {
			$tags = BWFAN_PRO_Common::get_or_create_terms( $tags, BWFCRM_Term_Type::$TAG );
			/** Prepared Tags */
			$tags = array_map( function ( $tag ) {
				$tag['value'] = isset( $tag['name'] ) ? $tag['name'] : '';
				unset( $tag['name'] );

				return $tag;
			}, $tags );
		}

		/** Create Lists if not exists */
		if ( is_array( $lists ) && ! empty( $lists ) && method_exists( 'BWFAN_PRO_Common', 'get_or_create_terms' ) ) {
			$lists = BWFAN_PRO_Common::get_or_create_terms( $lists, BWFCRM_Term_Type::$LIST );
			/** Prepared List */
			$lists = array_map( function ( $list ) {
				$list['value'] = isset( $list['name'] ) ? $list['name'] : '';
				unset( $list['name'] );

				return $list;
			}, $lists );
		}

		$status = $this->get_sanitized_arg( 'status' );
		if ( BWFCRM_Core()->forms->is_valid_status( $status ) ) {
			$feed->set_status( $status );
		}
		! empty( $title ) && $feed->set_title( $title );
		! empty( $mapped_fields ) && $feed->set_data( 'mapped_fields', $mapped_fields );
		$feed->set_data( 'tags', ! empty( $tags ) ? $tags : [] );
		$feed->set_data( 'lists', ! empty( $lists ) ? $lists : [] );
		$feed->set_data( 'update_existing', $update_existing );
		$feed->set_data( 'trigger_events', $trigger_events );
		$feed->set_data( 'update_blank', $dont_update_blank_values );

		$feed->save();

		return $this->success_response( $feed->get_array(), __( 'Form updated', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_API_Update_Form_Feed' );