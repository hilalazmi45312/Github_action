<?php

class BWFCRM_API_WC_Import_Orders extends BWFCRM_API_Base {
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
		$this->route  = '/import/wc/get-orders';
	}

	public function process_api_call() {

		/** Process **/
		/** @var BWFCRM_WC_Importer $importer */
		$importer = BWFCRM_Core()->importer->get_importer( 'wc' );
		if ( ! $importer instanceof BWFCRM_WC_Importer ) {
			return $this->error_response( __( 'WC Importer not found', 'wp-marketing-automations-pro' ), null, 500 );
		}

		/** Outputs **/
		$order_count = $importer->get_orders_count();

		return $this->success_response( $order_count, __('Order fetched', 'wp-marketing-automations-pro'));
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_WC_Import_Orders' );
