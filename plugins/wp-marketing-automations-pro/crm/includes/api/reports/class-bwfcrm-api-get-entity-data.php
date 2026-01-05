<?php

class BWFCRM_API_Get_Entity_Data extends BWFCRM_API_Base {
	public static $ins;

	public $total_count = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/analytics/entity/data';
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {
		$type    = ( isset( $this->args['type'] ) && '' !== $this->args['type'] ) ? $this->args['type'] : '';
		$oid     = ( isset( $this->args['oid'] ) && '' !== $this->args['oid'] ) ? $this->args['oid'] : 0;
		$object_title = BWFCRM_Reports::get_object_title( $oid, $type );

		$data = [
			'id'    => $oid,
			'title' => $object_title,
			'type'  => $type
		];

		return $this->success_response( $data );
	}

	public function prepare_item_for_response( $is_interval = '' ) {


	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Entity_Data' );
