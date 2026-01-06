<?php

class BWFCRM_API_Get_Export_Data extends BWFCRM_API_Base {
	public static $ins;
	private $export_id = null;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = 'contact/export/(?P<export_id>[\\d]+)';
		$this->request_args = array(
			'export_id' => array(
				'description' => __( 'Export ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$this->export_id = $this->get_sanitized_arg( 'export_id', 'text_field' );
		if ( empty( $this->export_id ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Unable to get export data with id', 'wp-marketing-automations-pro' ) . ' #' . $this->export_id );
		}

		$export_ins = new BWFCRM_Exporter();
		$export_ins->maybe_get_export( $this->export_id );
		$export = $this->get_export_data( $export_ins->db_export_row );

		/** Check if export is already running */
		if ( ! empty( $export_ins->is_running ) ) {
			return $this->success_response( $export, __( 'Export is already running.', 'wp-marketing-automations-pro' ) );
		}

		/** If export is completed */
		if ( BWFCRM_Exporter::$EXPORT_SUCCESS === intval( $export['status'] ) ) {
			return $this->success_response( $export, __( 'Export finished.', 'wp-marketing-automations-pro' ) );
		}

		/** Unscheduled the action if it is scheduled */
		if ( bwf_has_action_scheduled( BWFCRM_Exporter::$ACTION_HOOK, array( 'export_id' => $this->export_id ), 'bwfcrm' ) ) {
			bwf_unschedule_actions( BWFCRM_Exporter::$ACTION_HOOK, array( 'export_id' => $this->export_id ), 'bwfcrm' );
		}

		/** Schedule next occurrence */
		bwf_schedule_single_action( time() + 60, BWFCRM_Exporter::$ACTION_HOOK, array( 'export_id' => $this->export_id ), 'bwfcrm' );

		/** Run the export action directly */
		$export_ins->bwfcrm_export( $this->export_id );

		/** Fetch updated export data */
		$export = $this->get_export_data( [] );
		if ( empty( $export ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Unable to get export data', 'wp-marketing-automations-pro' ) . ' #' . $this->export_id );
		}

		if ( BWFCRM_Exporter::$EXPORT_SUCCESS === intval( $export['status'] ) ) {
			bwf_unschedule_actions( BWFCRM_Exporter::$ACTION_HOOK, array( 'export_id' => $this->export_id ), 'bwfcrm' );
		}

		return $this->success_response( $export, __( 'Successfully fetched export data with id ', 'wp-marketing-automations-pro' ) . '#' . $this->export_id );
	}

	public function get_export_data( $export ) {
		$export = empty( $export ) ? BWFAN_Model_Import_Export::get( $this->export_id ) : $export;
		if ( empty( $export ) ) {
			return false;
		}

		$temp = ! empty( $export['meta'] ) ? json_decode( $export['meta'], true ) : [];
		unset( $export['meta'] );
		if ( isset( $temp['file'] ) ) {
			$temp['filename'] = $temp['file'];
			if ( file_exists( BWFCRM_EXPORT_DIR . '/' . $temp['file'] ) ) {
				$temp['file'] = BWFCRM_EXPORT_URL . $temp['file'];
			} else if( file_exists( BWFCRM_EXPORT_DIR_BACKUP . '/' . $temp['file'] ) ) {
				$temp['file'] = BWFCRM_EXPORT_URL_BACKUP . $temp['file'];
			} else {
				$temp['file'] = false;
			}
		} else {
			$temp['file'] = false;
		}

		return array_merge( $export, $temp );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Export_Data' );
