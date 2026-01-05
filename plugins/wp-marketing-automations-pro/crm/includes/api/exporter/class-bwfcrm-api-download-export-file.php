<?php

class BWFCRM_API_Download_Export_File extends BWFCRM_API_Base {
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
		$this->route  = 'contact/export/download/(?P<export_id>[\\d]+)';
	}

	public function process_api_call() {
		$export_id = absint( $this->get_sanitized_arg( 'export_id' ) );
		if ( empty( $export_id ) ) {
			$this->response_code = 404;
			return $this->error_response( __( 'Invalid Export ID', 'wp-marketing-automations-pro' ) );
		}

		$export = BWFAN_Model_Import_Export::get( $export_id );
		if ( empty( $export['meta'] ) ) {
			$this->response_code = 404;
			return $this->error_response( __( 'Export file is not ready yet', 'wp-marketing-automations-pro' ) );
		}

		$export_meta = json_decode( $export['meta'], true );
		if ( ! is_array( $export_meta ) || ! isset( $export_meta['file'] ) ) {
			$this->response_code = 404;
			return $this->error_response( __( 'Export file url is missing', 'wp-marketing-automations-pro' ) );
		}

		$filename = BWFCRM_EXPORT_DIR . '/' . $export_meta['file'];
		if ( ! file_exists( $filename ) ) {
			$filename = BWFCRM_EXPORT_DIR_BACKUP . '/' . $export_meta['file'];
		}
		if ( file_exists( $filename ) ) {
			// Define header information
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Expires: 0' );
			header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
			header( 'Content-Length: ' . filesize( $filename ) );
			header( 'Pragma: public' );

			// Clear system output buffer
			flush();

			// Read the size of the file
			readfile( $filename );
			exit;
		}
		wp_die();
	}

	/**
	 * Rest api permission callback
	 *
	 * @return bool
	 */
	public function rest_permission_callback( WP_REST_Request $request ) {
		$query_params = $request->get_query_params();
		if ( isset( $query_params['bwf-nonce'] ) && $query_params['bwf-nonce'] === get_option( 'bwfan_unique_secret', '' ) ) {
			return true;
		}

		return false;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Download_Export_File' );
