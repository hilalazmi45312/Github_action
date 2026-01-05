<?php

class BWFCRM_API_Get_Export_List extends BWFCRM_API_Base {
	public static $ins;
	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = 'contact/export';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$limit  = ! empty( $this->get_sanitized_arg( 'limit', 'text_field' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 0;
		$offset = ! empty( $this->get_sanitized_arg( 'offset', 'text_field' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$data   = BWFAN_Model_Import_Export::get_export_import( 2, $limit, $offset );

		$exports           = isset( $data['exports'] ) && is_array( $data['exports'] ) ? $data['exports'] : [];
		$this->total_count = isset( $data['total_count'] ) && ! empty( $data['total_count'] ) ? $data['total_count'] : 0;
		$exports           = array_map( function ( $export ) {
			$temp = ! empty( $export['meta'] ) ? json_decode( $export['meta'], true ) : [];
			unset( $export['meta'] );
			if ( isset( $temp['file'] ) ) {
				if ( file_exists( BWFCRM_EXPORT_DIR . '/' . $temp['file'] ) || file_exists( BWFCRM_EXPORT_DIR_BACKUP . '/' . $temp['file'] ) ) {
					$temp['file'] = true;
				} else {
					$temp['file'] = false;
				}
			}

			return array_merge( $export, $temp );
		}, $exports );

		return $this->success_response( $exports, __( 'Got Export data', 'wp-marketing-automations-pro' ) );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Export_List' );
