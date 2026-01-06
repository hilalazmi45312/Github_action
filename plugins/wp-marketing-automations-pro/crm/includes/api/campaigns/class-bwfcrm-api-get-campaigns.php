<?php
/**
 * Campaign Get API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Get API class
 */
class BWFCRM_API_Get_Campaigns extends BWFCRM_API_Base {

	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/***
	 * Total count
	 *
	 * @var int
	 */
	public $total_count = 0;

	/***
	 * Count Data
	 *
	 * @var array
	 */
	public $count_data = [];

	/**
	 * Return class instance
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/broadcasts';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 25;
		$this->request_args       = array(
			'search'   => array(
				'description' => __( 'Search from campaign name', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'offset'   => array(
				'description' => __( 'Broadcast list Offset', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'limit'    => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'order'    => array(
				'description' => __( 'Order of the campaign list', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'order_by' => array(
				'description' => __( 'Order campaign list according to fields', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'fields'   => array(
				'description' => __( 'Comma separated fields to get', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'type'     => array(
				'description' => __( 'Broadcast type filter', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'status'   => array(
				'description' => __( 'Broadcast status filter', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$search = ! empty( $this->get_sanitized_arg( 'search', 'key' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$offset = ! empty( $this->get_sanitized_arg( 'offset', 'key' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : $this->pagination->offset;
		$limit  = ! empty( $this->get_sanitized_arg( 'limit', 'key' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : $this->pagination->limit;
		$order  = ! empty( $this->get_sanitized_arg( 'order', 'key' ) ) ? $this->get_sanitized_arg( 'order', 'text_field' ) : 'DESC';
		$type   = ! empty( $this->get_sanitized_arg( 'type', 'key' ) ) ? $this->get_sanitized_arg( 'type', 'text_field' ) : '';
		$status = ! empty( $this->get_sanitized_arg( 'status', 'key' ) ) ? $this->get_sanitized_arg( 'status', 'text_field' ) : '';

		try {
			$campaign_data = BWFAN_Model_Broadcast::get_campaigns( $search, $limit, $offset, $order, $type, $status );

			$this->count_data    = BWFAN_PRO_Common::get_broadcast_data_count( $type );
			$this->response_code = 200;
			$this->total_count   = intval( $campaign_data['total'] );

			/** Check if worker call is late */
			$last_run       = bwf_options_get( 'fk_core_worker_let' );
			$threshold_time = method_exists( 'BWFAN_Common', 'get_worker_delay_timestamp' ) ? BWFAN_Common::get_worker_delay_timestamp() : 300;
			if ( '' !== $last_run && ( ( time() - $last_run ) > $threshold_time ) ) {
				/** Worker is running late */
				$campaign_data['data']['worker_delayed'] = time() - $last_run;
			}

			/** Check basic worker last run time and status code check */
			$resp = method_exists( 'BWFAN_Common', 'validate_core_worker' ) ? BWFAN_Common::validate_core_worker() : [];
			if ( isset( $resp['response_code'] ) ) {
				$campaign_data['data']['response_code'] = $resp['response_code'];
			}

			return $this->success_response( $campaign_data['data'], $campaign_data['message'] );
		} catch ( Error $e ) {
			$this->response_code = 404;

			return $this->error_response( $e['message'] );
		}
	}

	/**
	 * Get total count
	 */
	public function get_result_total_count() {
		return $this->total_count;
	}

	public function get_result_count_data() {
		return $this->count_data;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Campaigns' );
