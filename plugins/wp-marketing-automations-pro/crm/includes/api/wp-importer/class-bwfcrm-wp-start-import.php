<?php

class BWFCRM_API_WP_Start_Import extends BWFCRM_API_Base {
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
		$this->route        = '/import/wp/status';
		$this->request_args = array(
			'import_id' => array(
				'description' => __( 'Get the import status, and Maybe Process this Import ID', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		/** @var BWFCRM_WP_Importer $importer */
		$importer = BWFCRM_Core()->importer->get_importer( 'wp' );
		if ( ! $importer instanceof BWFCRM_WP_Importer ) {
			$this->error_response( __( 'CSV Importer not found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$start_import = $this->get_sanitized_arg( 'start_import', 'bool' );
		$import_id        = $this->get_sanitized_arg( 'import_id', 'text_field' );
		$import_id        = empty( $import_id ) ? 0 : absint( $import_id );

		/** Get Status if not start import */
		if( ! $start_import ) {
			if( empty( $import_id ) ) {
				return $this->error_response( __( 'Invalid Import ID provided.', 'wp-marketing-automations-pro' ), null, 500 );
			}

			$stats = $importer->get_import_status( $import_id );
			return is_string($stats) ? $this->error_response( $stats, null, 500 ) : $this->success_response( $stats, '' );
		}

		/** Inputs */
		$roles            = isset( $this->args['roles'] ) && is_array( $this->args['roles'] ) ? $this->args['roles'] : [];
		$tags             = isset( $this->args['tags'] ) && is_array( $this->args['tags'] ) ? $this->args['tags'] : [];
		$lists            = isset( $this->args['lists'] ) && is_array( $this->args['lists'] ) ? $this->args['lists'] : [];
		$update_existing  = $this->get_sanitized_arg( 'update_existing', 'bool' );
		$marketing_status = $this->get_sanitized_arg( 'marketing_status', 'bool' );
		$disable_events   = $this->get_sanitized_arg( 'disable_events', 'bool' );
		$imported_contact_status   = $this->get_sanitized_arg( 'imported_contact_status', 'text_field' );

		/** Create Import & Start Importing */
		$import_id = $importer->create_import( $roles, $tags, $lists, $update_existing, $marketing_status, !$disable_events, $imported_contact_status );
		if( empty( $import_id ) ) {
			return $this->error_response( __( 'Unable to create WordPress Import', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** Get and Send status */
		$stats = $importer->get_import_status( $import_id );
		return is_string($stats) ? $this->error_response( $stats, null, 500 ) : $this->success_response( $stats, '' );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_WP_Start_Import' );
