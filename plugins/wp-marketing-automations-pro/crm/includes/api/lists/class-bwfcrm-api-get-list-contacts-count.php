<?php

class BWFCRM_Api_Get_List_Contacts_Count extends BWFCRM_API_Base {

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
		$this->route  = '/lists/contacts';
	}

	public function default_args_values() {
		$args = [
			'list_ids' => []
		];

		return $args;
	}

	public function process_api_call() {

		$list_ids =  $this->get_sanitized_arg( '', 'text_field', $this->args['list_ids'] );
		$data = [];
		if ( $list_ids ) {
			foreach ( $list_ids as $list_id ) {
				$contacts_count            = $this->get_contacts_count( $list_id );
				$subscribers_count         = $this->get_contacts_count( $list_id, true );

				if ( ! isset( $data[$list_id] ) ) {
					$data[$list_id] = [];
				}

				$data[$list_id]['contact_count']     = is_array( $contacts_count ) && isset( $contacts_count['total_count'] ) ? absint( $contacts_count['total_count'] ) : 0;
				$data[$list_id]['subscribers_count'] = is_array( $subscribers_count ) && isset( $subscribers_count['total_count'] ) ? absint( $subscribers_count['total_count'] ) : 0;
			}
		}

		$this->response_code = 200;

		return $this->success_response( $data );
	}

	public function get_contacts_count( $list_id, $exclude_unsubscribers = false ) {

		$filters = [
			'lists_any' => [ $list_id ],
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

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_List_Contacts_Count' );