<?php

class BWFCRM_API_CSV_Start_Import extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/import/csv/status/(?P<import_id>[\\d]+)';
		$this->request_args = array(
			'import_id' => array(
				'description' => __( 'Get the import status, and Maybe Process this Import ID', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$start_import = $this->get_sanitized_arg( 'start_import', 'bool' );

		$import_id = $this->get_sanitized_arg( 'import_id', 'text_field' );
		if ( empty( absint( $import_id ) ) ) {
			return $this->error_response( __( 'Import ID not found', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** @var BWFCRM_CSV_Importer $csv_importer */
		$csv_importer = BWFCRM_Core()->importer->get_importer( 'csv' );
		if ( ! $csv_importer instanceof BWFCRM_CSV_Importer ) {
			$this->error_response( __( 'CSV Importer not found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$import_status = $csv_importer->get_import_status( $import_id );

		/** Send import status, if import initialization is not required */
		if ( ! $start_import ) {
			return $this->success_response( $import_status, '' );
		}

		/** Prepare data for starting CSV import */
		$mapped_fields = isset( $this->args['map'] ) && is_array( $this->args['map'] ) ? $this->args['map'] : [];
		if ( count( $mapped_fields ) > 0 && ! in_array( 'email', $mapped_fields, true ) ) {
			return $this->error_response( __( 'Email mapping is required', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$tags                     = isset( $this->args['tags'] ) && is_array( $this->args['tags'] ) ? $this->args['tags'] : [];
		$lists                    = isset( $this->args['lists'] ) && is_array( $this->args['lists'] ) ? $this->args['lists'] : [];
		$marketing_status         = $this->get_sanitized_arg( 'marketing_status', 'bool' );
		$update_existing          = $this->get_sanitized_arg( 'update_existing', 'bool' );
		$trigger_events           = $this->get_sanitized_arg( 'trigger_events', 'bool' );
		$imported_contact_status  = $this->get_sanitized_arg( 'imported_contact_status', 'text_field' );
		$dont_update_blank_values = $this->get_sanitized_arg( 'dont_update_blank', 'bool' );

		/** Start Import */
		$imported = $csv_importer->start_import( $import_id, $mapped_fields, $tags, $lists, $update_existing, $marketing_status, ! $trigger_events, $imported_contact_status, $dont_update_blank_values );
		if ( is_string( $imported ) ) {
			return $this->error_response( $imported, null, 500 );
		}

		/** If imported, send import status */
		return $this->success_response( $import_status, '' );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_CSV_Start_Import' );
