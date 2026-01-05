<?php

class BWFCRM_API_Get_Indexing_Status extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/contacts/indexing';
	}

	public function default_args_values() {
		return array(
			'search'  => '',
			'filters' => array(),
		);
	}

	public function process_api_call() {
		/** Indexing status */
		$status = BWFCRM_WC_Importer::get_indexing_status();
		if ( ! empty( $status ) || BWFCRM_WC_Importer::$INDEXING_COMPLETE === $status ) {
			return $this->success_response( array(
				'indexing_required' => true,
				'indexing_status'   => $status
			) );
		}

		/** checking if search present in params **/
		$search             = $this->get_sanitized_arg( 'search', 'text_field' );
		$filters_collection = empty( $this->args['filters'] ) ? array() : $this->args['filters'];

		$get_wc_data     = $this->get_sanitized_arg( 'get_wc', 'bool' );
		$grab_totals     = $this->get_sanitized_arg( 'grab_totals', 'bool' );
		$additional_info = class_exists( 'WooCommerce' ) ? [
			'customer_data' => $get_wc_data,
			'grab_totals'   => $grab_totals
		] : [];


		$filters  = $this->get_sanitized_arg( '', 'text_field', $filters_collection );
		$contacts = BWFCRM_Contact::get_contacts( $search, $this->pagination->offset, $this->pagination->limit, $filters, $additional_info );
		if ( ! is_array( $contacts ) ) {
			$this->response_code = 500;

			return $this->error_response( is_string( $contacts ) ? $contacts : __( 'Unknown Error', 'wp-marketing-automations-pro' ) );
		}

		if ( ! isset( $contacts['contacts'] ) || empty( $contacts['contacts'] ) ) {
			return $this->success_response( [] );
		}

		if ( isset( $contacts['total_count'] ) ) {
			$this->total_count = absint( $contacts['total_count'] );
		}

		$this->response_code = 200;

		return $this->success_response( $contacts['contacts'] );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Indexing_Status' );
