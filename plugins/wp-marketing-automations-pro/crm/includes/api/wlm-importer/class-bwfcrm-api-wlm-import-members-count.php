<?php

class BWFCRM_API_WLM_Import_Members_Count extends BWFCRM_API_Base {
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
		$this->route  = '/import/wlm/get-members-count';
	}

	public function process_api_call() {

		/** Process **/
		/** @var BWFCRM_WLM_Importer $importer */
		$importer = BWFCRM_Core()->importer->get_importer( 'wlm' );
		if ( ! $importer instanceof BWFCRM_WLM_Importer ) {
			return $this->error_response( __( 'WLM Importer not found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** Outputs **/
		$members_count = $importer->get_members_count();

		/** Mark import done, if count is 0 */
		if ( 0 === absint( $members_count ) ) {
			/** Create table to enable functionality */
			/** @var BWFCRM_Integration_Wishlist_Member $ins */
			$ins = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );
			$ins->maybe_create_db_table();

			$key               = 'bwfan_import_done';
			$imported          = get_option( $key, array() );
			$imported['wlm'] = 1;
			update_option( $key, $imported );
		}

		return $this->success_response( $members_count, __( 'Members fetched', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_WLM_Import_Members_Count' );
