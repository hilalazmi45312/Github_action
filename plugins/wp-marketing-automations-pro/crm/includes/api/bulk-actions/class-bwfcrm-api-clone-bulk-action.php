<?php
/**
 * Bulk Action Clone API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Bulk Action Clone API class
 */
class BWFCRM_API_Clone_Bulk_Action extends BWFCRM_API_Base {

	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

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
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/bulk-action/(?P<id>[\\d]+)/clone';
		$this->request_args = array(
			'id' => array(
				'description' => __( 'Bulk action ID whose data to be cloned.', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'id' => 0,
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$id = $this->get_sanitized_arg( 'id', 'key' );
		if ( intval( $id ) > 0 ) {
			$data                = BWFAN_Model_Bulk_Action::clone_bulk_action( $id );
			$this->response_code = $data['status'];
			if ( $data['status'] === 200 ) {
				return $this->success_response( [], $data['message'] );
			}

			return $this->error_response( $data['message'] );
		}

		$this->response_code = 404;

		return $this->error_response( __( 'Please provide a valid bulk action id.', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Clone_Bulk_Action' );
