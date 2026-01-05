<?php
/**
 * Broadcast Lookup API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Broadcast Lookup API class
 */
class BWFCRM_API_Broadcast_Lookup extends BWFCRM_API_Base {

	/**
	 * BWFCRM_API_Broadcast_Lookup obj
	 *
	 * @var BWFCRM_API_Broadcast_Lookup
	 */
	public static $ins;

	/***
	 * Total count
	 *
	 * @var int
	 */
	public $total_count = 0;

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
		$this->route              = '/broadcasts-lookup';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 25;
		$this->request_args       = array(
			'search' => array(
				'description' => __( 'Search from Broadcast name', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'offset' => array(
				'description' => __( 'Broadcast Offset', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'limit'  => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'ids'    => array(
				'description' => __( 'Broadcast IDs', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'type'   => array(
				'description' => __( 'Broadcast type filter', 'wp-marketing-automations-pro' ),
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
		$ids    = ! empty( $this->get_sanitized_arg( 'ids', 'text_field' ) ) ? $this->get_sanitized_arg( 'ids', 'text_field' ) : '';
		$type   = ! empty( $this->get_sanitized_arg( 'type', 'key' ) ) ? $this->get_sanitized_arg( 'type', 'text_field' ) : '';

		$ids = ! empty( $ids ) ? explode( ',', $ids ) : array();

		$broadcasts = BWFCRM_Core()->campaigns->get_broadcast_lookup(
			array(
				'search' => $search,
				'offset' => $offset,
				'limit'  => $limit,
				'ids'    => $ids,
				'type'   => $type,
			)
		);

		$this->total_count = count( $broadcasts );

		return $this->success_response( $broadcasts );
	}

	/**
	 * Get total count
	 */
	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Broadcast_Lookup' );
