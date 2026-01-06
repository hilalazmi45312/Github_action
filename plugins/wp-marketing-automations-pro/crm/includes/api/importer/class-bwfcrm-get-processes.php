<?php

class BWFCRM_API_Get_Import_Processes extends BWFCRM_API_Base {
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
		$this->route  = '/import/processes';
	}

	public function process_api_call() {
		/** Outputs **/
		$processes = BWFCRM_Core()->importer->get_existing_processes();
		if ( is_string( $processes ) ) {
			return $this->error_response( $processes, null, 500 );
		}

		if ( is_wp_error( $processes ) ) {
			return $this->error_response( '', $processes, 500 );
		}

		return $this->success_response( array( 'imports' => $processes ), '' );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Import_Processes' );
