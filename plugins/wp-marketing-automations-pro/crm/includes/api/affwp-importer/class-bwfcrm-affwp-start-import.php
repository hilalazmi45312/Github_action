<?php

class BWFCRM_API_AFFWP_Start_Import extends BWFCRM_API_Start_Import_Base {
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
		$this->route        = '/import/affwp/status';
		$this->request_args = array(
			'import_id' => array(
				'description' => __( 'Get the import status, and Maybe Process this Import ID', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);

		$this->importer_slug = 'affwp';
		$this->importer_name = 'AffiliateWP';
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_AFFWP_Start_Import' );
