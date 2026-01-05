<?php
#[AllowDynamicProperties]
abstract class BWFCRM_API_Start_Import_Base extends BWFCRM_API_Base {

	public $importer_slug = '';
	public $importer_name = '';

	protected function before_create_import( $importer ) {
		return;
	}

	public function process_api_call() {
		if ( empty( $this->importer_slug ) || empty( $this->importer_name ) ) {
			return $this->error_response( __( 'Invalid Importer name or slug', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** @var BWFCRM_Importer_Base $importer */
		$importer = BWFCRM_Core()->importer->get_importer( $this->importer_slug );
		if ( ! $importer instanceof BWFCRM_Importer_Base ) {
			$this->error_response( $this->importer_name . __( 'Importer not found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$start_import = $this->get_sanitized_arg( 'start_import', 'bool' );
		$import_id    = $this->get_sanitized_arg( 'import_id', 'text_field' );
		$import_id    = empty( $import_id ) ? 0 : absint( $import_id );

		/** Get Status if not start import */
		if ( ! $start_import ) {
			if ( empty( $import_id ) ) {
				return $this->error_response( __( 'Invalid Import ID provided.', 'wp-marketing-automations-pro' ), null, 500 );
			}

			$stats = $importer->get_import_status( $import_id );
			return is_string( $stats ) ? $this->error_response( $stats, null, 500 ) : $this->success_response( $stats, '' );
		}

		/** Inputs */
		$tags                    = isset( $this->args['tags'] ) && is_array( $this->args['tags'] ) ? $this->args['tags'] : array();
		$lists                   = isset( $this->args['lists'] ) && is_array( $this->args['lists'] ) ? $this->args['lists'] : array();
		$update_existing         = $this->get_sanitized_arg( 'update_existing', 'bool' );
		$marketing_status        = $this->get_sanitized_arg( 'marketing_status', 'bool' );
		$disable_events          = $this->get_sanitized_arg( 'disable_events', 'bool' );
		$imported_contact_status = $this->get_sanitized_arg( 'imported_contact_status', 'text_field' );

		/** Before Creating Import */
		$this->before_create_import( $importer );

		/** Create Import & Start Importing */
		$import_id = $importer->create_import( $tags, $lists, $update_existing, $marketing_status, ! $disable_events, $imported_contact_status );
		if ( empty( $import_id ) ) {
			return $this->error_response( __( 'Unable to create Import for: ', 'wp-marketing-automations-pro' ) . $this->importer_name, null, 500 );
		}

		/** Get and Send status */
		$stats = $importer->get_import_status( $import_id );
		return is_string( $stats ) ? $this->error_response( $stats, null, 500 ) : $this->success_response( $stats, '' );
	}

}
