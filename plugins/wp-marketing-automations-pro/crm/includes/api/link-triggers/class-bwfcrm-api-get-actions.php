<?php

/**
 * Return action data file
 */
class BWFCRM_Api_Get_Actions extends BWFCRM_API_Base {

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
		$this->route  = '/link-triggers/actions';
	}

	public function process_api_call() {
		$response = [
			'actions'        => $this->format_action_array( BWFCRM_Core()->actions->get_all_action_list( 1 ) ),
			'actions_schema' => BWFCRM_Core()->actions->get_all_actions_schema_data(),
		];

		$this->response_code = 200;

		return $this->success_response( $response );
	}

	/**
	 * Format the action data
	 *
	 * @param $actions
	 *
	 * @return array|array[]
	 */
	public function format_action_array( $actions ) {

		if ( empty( $actions ) ) {
			return [];
		}

		return array_map( function ( $slug, $nice_name ) {
			return [
				'value' => $slug,
				'label' => $nice_name,
			];
		}, array_keys( $actions ), $actions );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Actions' );