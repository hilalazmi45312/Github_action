<?php

namespace BWFAN\Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Templates extends Base {

	public function __construct( $type = 'templates' ) {
		$this->type = $type;
	}

	/**
	 * Get file name
	 *
	 * @return string
	 */
	public function get_file_name() {
		$file_name = 'template-export-' . time() . '-';
		if ( class_exists( '\BWFAN_Common' ) && method_exists( '\BWFAN_Common', 'create_token' ) ) {
			$file_name .= \BWFAN_Common::create_token( 5 );
		} else {
			$file_name .= wp_generate_password( 5, false );
		}
		$file_name .= '.json';

		return $file_name;
	}

	/**
	 * Get total count
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function get_total_count( $data = [] ) {
		global $wpdb;

		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}bwfan_templates WHERE canned = 1";

		if ( ! empty( $data['search'] ) ) {
			$query .= $wpdb->prepare( " AND title LIKE %s", '%' . esc_sql( $data['search'] ) . '%' );
		}

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

	}

	/**
	 * Handle export
	 *
	 * @param int $user_id
	 * @param int $export_id
	 *
	 * return void
	 */
	public function handle_export( $user_id = 0, $export_id = 0 ) {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		WP_Filesystem();

		if ( empty( $export_id ) && ! empty( $user_id ) ) {
			$this->handle_export_legacy( $user_id );

			return;
		} else if ( empty( $user_id ) ) {
			return;
		}

		// Fetch export data row and decode meta
		$db_export_row = \BWFAN_Model_Import_Export::get( $export_id );
		if ( empty( $db_export_row ) ) {
			$this->end_user_export( $user_id, $export_id, '', self::$EXPORTER_FAILED, __( 'Export data not found', 'wp-marketing-automations-pro' ) );
		}
		$export_meta = ! empty( $db_export_row['meta'] ) ? json_decode( $db_export_row['meta'], true ) : [];

		if ( ! class_exists( '\BWFCRM_Templates' ) || empty( $export_meta ) ) {
			$this->end_user_export( $user_id, $export_id, '', self::$EXPORTER_FAILED, __( 'Unable to find BWFCRM_Templates class.', 'wp-marketing-automations-pro' ) );

			return;
		}

		$search              = ! empty( $export_meta['search'] ) ? $export_meta['search'] : '';
		$current_pos         = absint( $db_export_row['offset'] );
		$processed_count     = absint( $db_export_row['processed'] );
		$start_time          = time();
		$current_total_count = $this->get_total_count( [ 'search' => $search ] );
		$file_name           = $export_meta['file'];
		$file_path           = self::$export_folder . '/' . $file_name;

		// Initialize or load existing data
		$existing_data = [];
		if ( file_exists( $file_path ) ) {
			$existing_data = $wp_filesystem->get_contents( $file_path );
			$existing_data = json_decode( $existing_data, true );
			if ( ! is_array( $existing_data ) ) {
				$existing_data = [];
			}
		}
		$batch_limit = 10;
		while ( ( time() - $start_time ) < 30 && ! \BWFCRM_Common::memory_exceeded() ) {
			$templates = \BWFCRM_Templates::get_json( '', $current_pos, $batch_limit, $search );
			if ( empty( $templates ) ) {
				break;
			}

			$batch_data = json_decode( $templates, true );
			if ( ! is_array( $batch_data ) && ! empty( $batch_data ) ) {
				break;
			}

			$existing_data   = array_merge( $existing_data, $batch_data );
			$batch_count     = count( $batch_data );
			$current_pos     += $batch_count;
			$processed_count += $batch_count;
		}
		$wp_filesystem->put_contents( $file_path, wp_json_encode( $existing_data ), FS_CHMOD_FILE );
		$this->update_export_offset( $export_id, $current_pos, $processed_count, $current_total_count );

		/** Check if export is complete */
		if ( $current_pos >= $db_export_row['count'] ) {
			$this->end_user_export( $user_id, $export_id, $file_name, self::$EXPORTER_SUCCESS, __( 'Export completed successfully', 'wp-marketing-automations-pro' ) );
		}
	}

	/**
	 * Handle export for legacy
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	private function handle_export_legacy( $user_id = 0 ) {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		WP_Filesystem();

		$status_data = [
			'status' => self::$EXPORTER_FAILED,
			'msg'    => [
				__( 'Unable to create export file.', 'wp-marketing-automations-pro' )
			]
		];

		if ( ! class_exists( '\BWFCRM_Templates' ) ) {
			$status_data = [
				'status' => self::$EXPORTER_FAILED,
				'msg'    => [
					__( 'Unable to get template export file data.', 'wp-marketing-automations-pro' ),
					__( 'Unable to find BWFCRM_Templates class.', 'wp-marketing-automations-pro' )
				]
			];
		} else {
			$get_export_templates_data = \BWFCRM_Templates::get_json();
			$filename                  = 'template-export-' . time() . '.json';

			if ( ! file_exists( self::$export_folder . '/' ) ) {
				wp_mkdir_p( self::$export_folder );
			}

			$res = $wp_filesystem->put_contents( self::$export_folder . '/' . $filename, $get_export_templates_data, FS_CHMOD_FILE );
			if ( $res ) {
				$status_data = [
					'status' => self::$EXPORTER_SUCCESS,
					'url'    => self::$export_folder . '/' . $filename,
					'msg'    => [
						__( 'File created successfully', 'wp-marketing-automations-pro' )
					]
				];
			}
		}

		$user_data = get_user_meta( $user_id, 'bwfan_single_export_status', true );
		if ( empty( $user_data ) ) {
			$status_data = [
				'status' => self::$EXPORTER_FAILED,
				'msg'    => [
					__( 'Unable to get the user meta.', 'wp-marketing-automations-pro' ),
				]
			];
		}
		$user_data[ $this->type ] = $status_data;
		update_user_meta( $user_id, 'bwfan_single_export_status', $user_data );

		BWFAN_Core()->exporter->unschedule_export_action( [
			'type'      => $this->type,
			'user_id'   => $user_id,
			'export_id' => 0
		] );
	}
}

BWFAN_Core()->exporter->register_exporter( 'templates', 'BWFAN\Exporter\Templates' );