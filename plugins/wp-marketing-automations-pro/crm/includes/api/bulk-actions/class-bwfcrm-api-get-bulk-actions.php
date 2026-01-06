<?php

/**
 * Get bulk action API
 */
class BWFCRM_Api_Get_Bulk_Actions extends BWFCRM_API_Base {

	public static $ins;
	public $total_count = 0;
	public $count_data = [];
	public $extra_data = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/bulk-actions';
	}

	/**
	 * Set default Values
	 *
	 * @return string[]
	 */
	public function default_args_values() {
		return array( 's' => '' );
	}

	/**
	 * API call handler
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function process_api_call() {
		$search = ! empty( $this->get_sanitized_arg( 'search', 'key' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$offset = ! empty( $this->get_sanitized_arg( 'offset', 'key' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$limit  = ! empty( $this->get_sanitized_arg( 'limit', 'key' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 0;
		$status = ! empty( $this->get_sanitized_arg( 'status', 'key' ) ) ? $this->get_sanitized_arg( 'status', 'text_field' ) : '';
		$ids    = empty( $this->args['ids'] ) ? array() : explode( ',', $this->args['ids'] );
		/**
		 * Get bulk action data
		 */
		$bulk_action_data  = BWFAN_Model_Bulk_Action::get_bulk_actions( $search, $status, $limit, $offset, true, $ids );
		$bulk_actions      = isset( $bulk_action_data['list'] ) ? $bulk_action_data['list'] : [];
		$this->total_count = isset( $bulk_action_data['total'] ) ? intval( $bulk_action_data['total'] ) : 0;

		$this->extra_data['total']   = BWFAN_PRO_Common::get_bulk_actions_data_count();
		$this->extra_data['actions'] = ( array ) BWFCRM_Core()->actions->get_all_action_list( 2 );
		$this->count_data            = BWFAN_PRO_Common::get_contact_data_counts();
		$this->response_code         = 200;

		return $this->success_response( $bulk_actions );
	}

	/**
	 * Returns total count
	 *
	 * @return int
	 */
	public function get_result_total_count() {
		return $this->total_count;
	}

	/**
	 * Returns extra data
	 *
	 * @return array|int
	 */
	public function get_result_extra_data() {
		return $this->extra_data;
	}

	/**
	 * Returns bulk action count based on status
	 *
	 * @return array|int
	 */
	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Bulk_Actions' );
