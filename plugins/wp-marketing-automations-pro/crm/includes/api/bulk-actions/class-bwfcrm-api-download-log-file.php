<?php

class BWFCRM_API_Download_Log_File extends BWFCRM_API_Base {
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
		$this->route  = 'bulk-action/download/(?P<bulk_action_id>[\\d]+)';
	}

	public function process_api_call() {
		$bulk_action_id = absint( $this->get_sanitized_arg( 'bulk_action_id' ) );
		if ( empty( $bulk_action_id ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Invalid Bulk Action ID', 'wp-marketing-automations-pro' ) );
		}

		$action_data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $bulk_action_id );

		if ( ! isset( $action_data['log_file'] ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Log file url is missing', 'wp-marketing-automations-pro' ) );
		}

		$filename = BWFCRM_BULK_ACTION_LOG_DIR . '/' . $action_data['log_file'];
		if ( ! file_exists( $filename ) ) {
			$filename = BWFCRM_BULK_ACTION_LOG_DIR_BACKUP . '/' . $action_data['log_file'];
		}
		if ( ! file_exists( $filename ) ) {
			wp_die();
		}

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

BWFCRM_API_Loader::register( 'BWFCRM_API_Download_Log_File' );
