<?php

class BWFCRM_Api_Get_All_Fields extends BWFCRM_API_Base {

	public static $ins;
	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = '/all/fields';
		$this->response_code = 200;
	}

	public function process_api_call() {
		$all_fields = BWFCRM_Fields::get_fields( '', 1 );
		if ( isset( $all_fields['creation_date'] ) ) {
			unset( $all_fields['creation_date'] );
		}
		$fields        = $all_fields;
		$select_fields = [ 'timezone', 'status', 'country' ];

		$statuses = [];
		if ( method_exists( 'BWFAN_Common', 'get_contact_status_array_list' ) ) {
			$status = BWFAN_Common::get_contact_status_array_list();
			foreach ( $status as $key => $value ) {
				$statuses[ $key ] = $value['name'];
			}
		} else {
			$statuses = array(
				'0' => 'Unverified',
				'1' => 'Subscribed',
				'3' => 'Unsubscribed',
				'2' => 'Bounced',
			);
		}

		$all_fields = array_map( function ( $field ) use ( $fields, $select_fields, $statuses ) {
			if ( ! is_array( $field ) ) {
				$field_id = array_search( $field, $fields );
				$field    = [
					'group_id' => 0,
					'ID'       => $field_id,
					'type'     => in_array( $field_id, $select_fields ) ? 4 : 1,
					'name'     => $field,
					'meta'     => [],
				];

				if ( 'status' === $field_id ) {
					$field['meta']['options'] = $statuses;
				}
			}

			return $field;
		}, $all_fields );

		$this->total_count = count( $all_fields );

		return $this->success_response( $all_fields, __( empty( $all_fields ) ? 'No Fields found.' : 'Got all fields', 'wp-marketing-automations-pro' ) );
	}


	public function get_result_total_count() {
		return $this->total_count;
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_All_Fields' );