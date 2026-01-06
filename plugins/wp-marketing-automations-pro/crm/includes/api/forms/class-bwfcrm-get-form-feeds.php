<?php

class BWFCRM_API_Get_Form_Feeds extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total = 0;
	public $count_data = [];

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/form-feeds';
	}

	public function process_api_call() {
		$args = array(
			'offset'      => $this->pagination->offset,
			'limit'       => $this->pagination->limit,
			'grab_totals' => $this->get_sanitized_arg( 'grab_totals', 'bool' ),
            'status'      => $this->get_sanitized_arg( 'status', 'text_field' ),
            'search'      => $this->get_sanitized_arg( 'search', 'text_field' ),
		);

		$feeds            = BWFCRM_Core()->forms->get_feeds( $args );
		$this->total      = absint( $feeds['total'] );
		$this->count_data = BWFAN_PRO_Common::get_forms_data_count();

		return $this->success_response( $feeds['feeds'] );
	}

	public function get_result_total_count() {
		return $this->total;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Form_Feeds' );
