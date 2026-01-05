<?php

class BWFCRM_API_Add_Export extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = 'contact/export/add';
	}

	public function process_api_call() {
		$title   = isset( $this->args['title'] ) ? $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] ) : '';
		$fields  = isset( $this->args['fields'] ) ? $this->args['fields'] : [];
		$filters = isset( $this->args['filters'] ) ? $this->args['filters'] : [];
		$count   = isset( $this->args['count'] ) ? intval( $this->args['count'] ) : 0;

		if ( empty( $fields ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Export fields are missing', 'wp-marketing-automations-pro' ) );
		}

		$response = BWFCRM_Core()->exporter->bwfcrm_add_export_action( $title, $fields, $filters, $count );
		if ( ! $response['status'] ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Error in exporting contacts.', 'wp-marketing-automations-pro' ) );
		}
		if ( 404 === $response['status'] ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Unable to create a file, permission issues, contact host.', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( $response, __( 'Export Added to Queue', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Add_Export' );
