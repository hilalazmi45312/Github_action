<?php
/**
 * Link Trigger Clone API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Link Trigger Clone API class
 */
class BWFCRM_API_Clone_Link_Trigger extends BWFCRM_API_Base {

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
		$this->route        = '/link-trigger/(?P<link_id>[\\d]+)/clone';
		$this->request_args = array(
			'link_id' => array(
				'description' => __( 'Link ID whose data to be cloned.', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'link_id' => 0,
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$link_id = $this->get_sanitized_arg( 'link_id', 'key' );

		if ( intval( $link_id ) > 0 ) {
			$link_data           = BWFAN_Model_Link_Triggers::clone_link_trigger( $link_id );
			$this->response_code = $link_data['status'];
			if ( $link_data['status'] === 200 ) {
				return $this->success_response( [], $link_data['message'] );
			} else {
				return $this->error_response( $link_data['message'] );
			}
		} else {
			$this->response_code = 404;

			return $this->error_response( __( 'Please provide a valid link trigger id.', 'wp-marketing-automations-pro' ) );
		}
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Clone_Link_Trigger' );
