<?php

class BWFCRM_API_AFFWP_Import_Affiliates_Count extends BWFCRM_API_Base {
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
		$this->route  = '/import/affwp/get-affiliate-count';
	}

	public function process_api_call() {
		/** Process */
		/** @var BWFCRM_AFFWP_Importer $importer */
		$importer = BWFCRM_Core()->importer->get_importer( 'affwp' );
		if ( ! $importer instanceof BWFCRM_AFFWP_Importer ) {
			return $this->error_response( __( 'affwp Importer not found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** Outputs */
		$affiliates_count = $importer->get_affiliates_count();

		/** Mark import done, if count is 0 */
		if ( 0 === absint( $affiliates_count ) ) {
			$key               = 'bwfan_import_done';
			$imported          = get_option( $key, array() );
			$imported['affwp'] = 1;
			update_option( $key, $imported );
		}

		return $this->success_response( $affiliates_count, __( 'Affiliates fetched', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_AFFWP_Import_Affiliates_Count' );
