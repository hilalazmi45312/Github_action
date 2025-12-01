<?php

/**
 * Class BWFCRM_Api_Delete_Export
 */
class BWFCRM_Api_Delete_Export extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::DELETABLE;
		$this->route         = '/contact/export/delete/(?P<export_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'export_id' => ''
		);

		return $args;
	}

	public function process_api_call() {

		$export_id = $this->get_sanitized_arg( 'export_id', 'text_field' );

		$delete_export = BWFCRM_Core()->exporter->delete_export_entry( $export_id );

		if ( ! $delete_export ) {

			$this->response_code = 400;

			return $this->error_response( __( 'Unable to delete export with id', 'wp-marketing-automations-pro' ) .' #'. $export_id );
		}

		return $this->success_response( [], __( 'Deleted Successfully', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Export' );