<?php

class BWFCRM_API_Contact_Listing extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;
	public $count_data = [];

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/contacts/listing';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;

	}

	public function process_api_call() {
		/** checking if search present in params */
		$search             = $this->get_sanitized_arg( 'search', 'text_field' );
		$filters_collection = empty( $this->args['filters'] ) ? array() : $this->args['filters'];

		$get_wc_data          = $this->get_sanitized_arg( 'get_wc', 'bool' );
		$grab_totals          = $this->get_sanitized_arg( 'grab_totals', 'bool' );
		$only_count           = $this->get_sanitized_arg( 'only_count', 'bool' );
		$contact_mode         = $this->get_sanitized_arg( 'fetch_base', 'text_field' );
		$exclude_unsubs       = $this->get_sanitized_arg( 'exclude_unsubs', 'bool' );
		$exclude_unsubs_lists = $this->get_sanitized_arg( 'exclude_unsubs_lists', 'bool' );
		$grab_custom_fields   = $this->get_sanitized_arg( 'grab_custom_fields', 'bool' );
		$order                = $this->get_sanitized_arg( 'order', 'text_field' );
		$order_by             = $this->get_sanitized_arg( 'order_by', 'text_field' );
		$additional_info      = array(
			'grab_totals'          => $grab_totals,
			'only_count'           => $only_count,
			'fetch_base'           => $contact_mode,
			'exclude_unsubs'       => $exclude_unsubs,
			'exclude_unsubs_lists' => $exclude_unsubs_lists,
			'grab_custom_fields'   => $grab_custom_fields,
		);

		if ( ! empty( $order ) && ! empty( $order_by ) ) {
			$additional_info['order']    = $order;
			$additional_info['order_by'] = $order_by;
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$additional_info['customer_data'] = $get_wc_data;
		}

		$filter_match       = isset( $filters_collection['match'] ) && ! empty( $filters_collection['match'] ) ? $filters_collection['match'] : 'all';
		$filter_match       = ( 'any' === $filter_match ? ' OR ' : ' AND ' );
		$normalized_filters = BWFCRM_Filters::_normalize_input_filters( $filters_collection );

		// $contacts = BWFCRM_Contact::get_contacts( $search, $this->pagination->offset, $this->pagination->limit, $filters_collection, $additional_info );
		$contacts = BWFCRM_Model_Contact::get_contact_listing( $search, $this->pagination->limit, $this->pagination->offset, $normalized_filters, $additional_info, $filter_match );

		$this->count_data  = BWFAN_PRO_Common::get_contact_data_counts();
		$this->total_count = $contacts['total'];

		$this->response_code = 200;

		return $this->success_response( $contacts['contacts'] );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Contact_Listing' );
