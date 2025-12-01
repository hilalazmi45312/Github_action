<?php

class BWFCRM_Api_Get_Tag_Contacts_Count extends BWFCRM_API_Base {

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
		$this->route  = '/tags/contacts';
	}

	public function default_args_values() {
		return array( 'tag_ids' => [] );
	}

	public function process_api_call() {
		$tag_ids = $this->get_sanitized_arg( '', 'text_field', $this->args['tag_ids'] );

		$this->response_code = 200;

		/** In case empty **/
		if ( empty( $tag_ids ) || ! is_array( $tag_ids ) ) {
			return $this->success_response( [] );
		}

		$data = [];
		foreach ( $tag_ids as $tag_id ) {
			$contacts_count    = $this->get_contacts_count( $tag_id );
			$subscribers_count = $this->get_contacts_count( $tag_id, true );

			if ( ! isset( $data[ $tag_id ] ) ) {
				$data[ $tag_id ] = [];
			}

			$data[ $tag_id ]['contact_count']     = is_array( $contacts_count ) && isset( $contacts_count['total_count'] ) ? absint( $contacts_count['total_count'] ) : 0;
			$data[ $tag_id ]['subscribers_count'] = is_array( $subscribers_count ) && isset( $subscribers_count['total_count'] ) ? absint( $subscribers_count['total_count'] ) : 0;
		}

		return $this->success_response( $data );
	}

	public function get_contacts_count( $tag_id, $exclude_unsubscribers = false ) {
		$filters = [
			'tags_any' => [ $tag_id ],
		];

		$additional_info = [
			'grab_totals'   => true,
			'only_count'    => true,
			'customer_data' => false,
		];

		if ( $exclude_unsubscribers ) {
			$filters['status_is'] = 1;
		}

		return BWFCRM_Contact::get_contacts( '', 0, 5, $filters, $additional_info );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Tag_Contacts_Count' );
