<?php

class BWFCRM_API_WLM_Get_Levels extends BWFCRM_API_Base {
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
		$this->route  = '/wlm/levels';
	}

	public function process_api_call() {
		/** @var BWFCRM_Integration_Wishlist_Member $ins */
		$ins = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );
		$levels = $ins->get_levels();

		return $this->success_response( $levels );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_WLM_Get_Levels' );
