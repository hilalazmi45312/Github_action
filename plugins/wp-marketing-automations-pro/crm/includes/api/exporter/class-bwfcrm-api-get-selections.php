<?php

class BWFCRM_API_Get_Export_Selections extends BWFCRM_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = 'contact/export/custom-selections';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
        /**
         * Format:
         * $selections = [
         *      '{group-slug}': [
         *          'name': '{group-name}',
         *          'selections': [
         *              '{slug}': [
         *                  'slug': '{slug}',
         *                  'name': '{name}'
         *              ]
         *          ]
         *      ] 
         * ]
         */
		$selections = apply_filters( 'bwfcrm_get_export_custom_selections', array() );
		return $this->success_response( $selections );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Export_Selections' );
