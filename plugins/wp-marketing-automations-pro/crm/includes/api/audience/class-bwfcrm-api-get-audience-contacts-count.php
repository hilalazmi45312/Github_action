<?php

class BWFCRM_Api_Get_Audience_Contacts_Count extends BWFCRM_API_Base {

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
		$this->route  = '/audiences/contacts';
	}

	public function default_args_values() {
		$args = [
			'audience_ids' => []
		];

		return $args;
	}

	public function process_api_call() {

		$audience_ids = $this->get_sanitized_arg( '', 'text_field', $this->args['audience_ids'] );
		$data         = [];
		if ( $audience_ids ) {
			foreach ( $audience_ids as $audience_id ) {
				$audience = new BWFCRM_Audience( $audience_id );
				if ( empty( $audience ) ) {
					continue;
				}
				$audience_data = $audience->get_data();
				$audience_data = json_decode( $audience_data, true );
				$filters       = $audience_data['filters'];

				$contacts_count    = $this->get_contacts_count( $filters );
				$subscribers_count = $this->get_contacts_count( $filters, true );

				if ( ! isset( $data[ $audience_id ] ) ) {
					$data[ $audience_id ] = [];
				}

				$data[ $audience_id ]['contact_count']     = is_array( $contacts_count ) && isset( $contacts_count['total_count'] ) ? absint( $contacts_count['total_count'] ) : 0;
				$data[ $audience_id ]['subscribers_count'] = is_array( $subscribers_count ) && isset( $subscribers_count['total_count'] ) ? absint( $subscribers_count['total_count'] ) : 0;
			}
		}

		$this->response_code = 200;

		return $this->success_response( $data );
	}

	public function get_contacts_count( $filters, $exclude_unsubscribers = false ) {
		$unsubscribed_status = [ 0, 2, 3, 4, 5 ];

		/**
		 * if unverified(0), bounced(2), unsubscribed(3), soft bounced(4) and complaint(5) status available in filter then
		 * no need to get subscribers count
		 */
		if ( $exclude_unsubscribers && isset( $filters['status_is'] ) && in_array( absint( $filters['status_is'] ), $unsubscribed_status, true ) ) {
			return false;
		}

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

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Audience_Contacts_Count' );