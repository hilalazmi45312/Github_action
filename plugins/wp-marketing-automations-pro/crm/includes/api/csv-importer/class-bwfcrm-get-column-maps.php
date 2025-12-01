<?php

class BWFCRM_API_CSV_Get_Column_Maps extends BWFCRM_API_Base {
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
		$this->route =  version_compare( BWFAN_VERSION, '3.4.0', '>' ) ? '/import/csv-upload': '/import/csv/upload';
	}

	public function process_api_call() {
		$files = $this->args['files'];
		if ( empty( $files ) || ! is_array( $files['csv'] ) || ! is_uploaded_file( $files['csv']['tmp_name'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Not any file found for import', 'wp-marketing-automations-pro' ) );
		}

		$delimiter = isset( $this->args['$delimiter'] ) && ! empty( $this->args['$delimiter'] ) ? $this->args['$delimiter'] : ',';

		$import_res = BWFCRM_CSV_Importer::create_import_from_csv( $files['csv'], $delimiter );
		if ( ! is_array( $import_res ) ) {
			return $this->error_response( is_string( $import_res ) ? $import_res : __( 'Unknown error occurred', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$options = BWFCRM_CSV_Importer::get_mapping_options_from_csv( $import_res['file'], $delimiter );
		if ( ! is_array( $options ) ) {
			return $this->error_response( is_string( $options ) ? $options : __( 'Unknown error occurred', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$result = array(
			'import_id' => $import_res['import_id'],
			'headers'   => $options['headers'],
			'fields'    => $options['fields'],
		);

		return $this->success_response( $result, __( 'Headers Data', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_CSV_Get_Column_Maps' );
