<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_Importer
 *
 * @package Autonami CRM
 */
abstract class BWFCRM_Importer_Base {

	protected $log_file = null;

	public function get_import_status( $import_id ) {
		return array();
	}

	public function create_import( $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = true, $imported_contact_status = 1 ) {
		return 0;
	}
}
