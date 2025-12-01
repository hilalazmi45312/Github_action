<?php

class BWFCRM_API_Download_Importer_Log_File extends BWFCRM_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = 'importer-log/download/(?P<impoter_id>[\\d]+)';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$importer_id = absint( $this->get_sanitized_arg( 'impoter_id' ) );
		if ( empty( $importer_id ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Invalid Importer ID', 'wp-marketing-automations-pro' ) );
		}


		$import_row = BWFAN_Model_Import_Export::get( $importer_id );
		$import_meta   = ! empty( $import_row['meta'] ) ? json_decode( $import_row['meta'], true ) : array();

		if ( ! isset( $import_meta['log_file'] ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Log file url is missing', 'wp-marketing-automations-pro' ) );
		}

		$file_url = explode( '/', $import_meta['log_file'] );
		$filename = BWFCRM_IMPORT_DIR . '/' . end( $file_url );
		if ( ! file_exists( $filename ) ) {
			$filename = BWFCRM_IMPORT_DIR_BACKUP . '/' . end( $file_url );
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

BWFCRM_API_Loader::register( 'BWFCRM_API_Download_Importer_Log_File' );
