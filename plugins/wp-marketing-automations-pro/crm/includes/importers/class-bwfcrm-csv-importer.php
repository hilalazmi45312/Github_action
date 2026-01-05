<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_CSV_Importer
 * @package Autonami CRM
 */
#[AllowDynamicProperties]
class BWFCRM_CSV_Importer {
	/**
	 * CSV importer action hook
	 *
	 * @var string
	 */
	private $action_hook = 'bwfcrm_csv_import';

	/**
	 * Working import ID
	 *
	 * @var int
	 */
	private $import_id = 0;

	/**
	 * Working import DB data
	 *
	 * @var array
	 */
	private $db_import_row = array();

	/**
	 * Working import meta data
	 *
	 * @var array
	 */
	private $import_meta = array();

	/**
	 * Import start time
	 *
	 * @var int
	 */
	private $start_import_time = 0;

	/**
	 * Flag for the iterated users from populated data
	 *
	 * @var int
	 */
	private $current_pos = 0;

	/**
	 * Mapped CSV field array
	 *
	 * @var array
	 */
	private $mapped_fields = array();

	/** Import Contacts Results */
	private $skipped = 0;
	private $failed = 0;
	private $succeed = 0;
	private $log_handle = null;

	private $newTags = [];
	private $newLists = [];
	public $log_file = null;

	/**
	 * Create Import
	 *
	 * @param $import_meta
	 *
	 * @return int
	 */
	public static function create_import( $import_meta ) {
		/** Adding CSV importer entry in DB */
		BWFAN_Model_Import_Export::insert( array(
			'offset'        => 0,
			'type'          => BWFCRM_Importer::$IMPORT,
			'status'        => BWFCRM_Importer::$IMPORT_IN_PROGRESS,
			'created_date'  => current_time( 'mysql', 1 ),
			'last_modified' => date( 'Y-m-d H:i:s', time() - 6 ),
			'meta'          => wp_json_encode( $import_meta )
		) );

		/** Fetch importer ID */
		return BWFAN_Model_Import_Export::insert_id();
	}

	/**
	 * Create import from CSV file
	 *
	 * @param $file
	 * @param string $delimiter
	 *
	 * @return array|string
	 */
	public static function create_import_from_csv( $file, $delimiter = ',' ) {
		$import_meta = array(
			'import_type' => 'csv',
			'delimiter'   => $delimiter
		);

		/** Check for import directory and create if not exists */
		if ( ! file_exists( BWFCRM_IMPORT_DIR . '/' ) ) {
			wp_mkdir_p( BWFCRM_IMPORT_DIR );
		}

		/** Move the file to directory */
		$new_file_name = md5( rand() . time() );
		$new_file      = BWFCRM_IMPORT_DIR . '/' . $new_file_name;
		$move_new_file = @move_uploaded_file( $file['tmp_name'], $new_file );

		/** Validating the file and delete importer entry if encountered error */
		if ( false === $move_new_file ) {
			return __( 'Invalid CSV file / Unable to upload file', 'wp-marketing-automations-pro' );
		}

		$log_file                = BWFCRM_Importer::get_log_file_name( 'csv' );
		$import_meta['file']     = $new_file;
		$import_meta['log_file'] = $log_file;

		/** Create import with meta */
		$import_id = self::create_import( $import_meta );


		$log_file_header = array( 'ID', 'Email', 'First Name', 'Last Name', 'Status', 'Message' );
		BWFCRM_Importer::create_importer_log_file( $log_file, $log_file_header );

		return array(
			'import_id' => $import_id,
			'file'      => $new_file,
		);
	}

	/**
	 * Get mapping headers from uploaded CSV and CRM fields from DB
	 *
	 * @param string $csv_file
	 * @param string $delimiter
	 *
	 * @return array|string
	 */
	public static function get_mapping_options_from_csv( $csv_file, $delimiter ) {
		$handle = fopen( $csv_file, 'r' );
		/** Fetching CSV header */
		$headers = false !== $handle ? fgetcsv( $handle, 0, $delimiter ) : false;
		if ( ! is_array( $headers ) ) {
			return __( 'Unable to read file', 'wp-marketing-automations-pro' );
		}

		if ( isset( $headers[0] ) ) {
			$headers[0] = self::remove_utf8_bom( $headers[0] );
		}
		/** Formatting CSV header for mapping */
		foreach ( $headers as $index => $header ) {
			$headers[ $index ] = array( 'index' => $index, 'header' => $header );
		}

		$extra_data = [
			'id'     => 0,
			'name'   => 'Map Tags and Lists',
			'fields' => [
				[
					'id'   => 'tags',
					'name' => 'Tags',
				],
				[
					'id'   => 'lists',
					'name' => 'Lists',
				]
			]
		];

		/** Get contact  fields */
		$fields = BWFCRM_Fields::get_groups_with_fields( true, true, true, true );

		$fields[] = $extra_data;

		return array(
			'headers' => $headers,
			'fields'  => $fields,
		);
	}

	/**
	 * Remove UTF8_bom
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private static function remove_utf8_bom( $string ) {
		if ( 'efbbbf' === substr( bin2hex( $string ), 0, 6 ) ) {
			$string = substr( $string, 3 );
		}

		return $string;
	}

	/**
	 * Start the Import
	 *
	 * @param int $import_id
	 * @param array $mapped_fields
	 * @param array $tags
	 * @param array $lists
	 * @param bool $update_existing
	 * @param bool $marketing_status
	 * @param int $imported_contact_status
	 *
	 * @return string|true
	 */
	public function start_import( $import_id, $mapped_fields = array(), $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = false, $imported_contact_status = 1, $dont_update_blank_values = true ) {
		/** Check for import record exists */
		if ( ! $this->maybe_get_import( $import_id ) ) {
			return __( 'Import record not found', 'wp-marketing-automations-pro' );
		}

		return $this->maybe_initiate_import( $import_id, $mapped_fields, $tags, $lists, $update_existing, $marketing_status, $disable_events, $imported_contact_status, $dont_update_blank_values );
	}

	/**
	 * Get Import Status or Start Import
	 *
	 * @param $import_id
	 *
	 * @return array|string
	 */
	public function get_import_status( $import_id ) {
		/** Check for import record exists */
		if ( ! $this->maybe_get_import( $import_id ) ) {
			return __( 'Import record not found', 'wp-marketing-automations-pro' );
		}

		/** Get percent completed */
		$percent = $this->get_percent_completed();

		/** End import if completed */
		if ( $percent >= 100 ) {
			$this->end_import();
		}

		$status = absint( $this->db_import_row['status'] );
		if ( BWFCRM_Importer::$IMPORT_IN_PROGRESS === $status && bwf_has_action_scheduled( $this->action_hook, array( 'import_id' => absint( $import_id ) ) && empty( $this->db_import_row['meta']['is_running'] ) ) ) {
			bwf_unschedule_actions( $this->action_hook, array( 'import_id' => absint( $import_id ) ) );

			add_filter( 'bwfan_as_per_call_time', function () {
				return 10;
			} );

			$this->import( $import_id );

			/** If still in progress, return the status */
			$this->db_import_row = null;
			$this->maybe_get_import( $import_id );

			/** Get percent completed */
			$percent = $this->get_percent_completed();

			$status = absint( $this->db_import_row['status'] );

			/** End import if completed */
			if ( $percent >= 100 ) {
				$this->end_import();
			}
		}

		$data = array(
			'percent' => $percent,
			'status'  => BWFCRM_Importer::get_status_text( $status ),
			'log'     => $this->get_import_meta( 'log' )
		);

		/** If import completed 100% and import has log file */
		if ( 100 === $percent && ! empty( $this->import_meta['log_file'] ) ) {
			$data['has_log_file'] = true;
		}

		return $data;
	}

	/**
	 * Get importer data
	 *
	 * @param $import_id
	 *
	 * @return bool
	 */
	public function maybe_get_import( $import_id ) {
		/** Check if import data already fetched */
		if ( is_array( $this->db_import_row ) && ! empty( $this->db_import_row ) && absint( $this->db_import_row['id'] ) === absint( $import_id ) ) {
			return true;
		}

		$this->import_id = absint( $import_id );

		/** Get import data from DB */
		$this->db_import_row = BWFAN_Model_Import_Export::get( $this->import_id );
		$this->import_meta   = ! empty( $this->db_import_row['meta'] ) ? json_decode( $this->db_import_row['meta'], true ) : array();

		/** Set log data */
		if ( isset( $this->import_meta['log'] ) && is_array( $this->import_meta['log'] ) ) {
			$this->skipped = isset( $this->import_meta['log']['skipped'] ) && empty( $this->skipped ) ? absint( $this->import_meta['log']['skipped'] ) : $this->skipped;
			$this->succeed = isset( $this->import_meta['log']['succeed'] ) && empty( $this->succeed ) ? absint( $this->import_meta['log']['succeed'] ) : $this->succeed;
			$this->failed  = isset( $this->import_meta['log']['failed'] ) && empty( $this->failed ) ? absint( $this->import_meta['log']['failed'] ) : $this->failed;
		}
		if ( isset( $this->import_meta['log_file'] ) ) {
			$this->log_file = $this->import_meta['log_file'];
		}

		return is_array( $this->db_import_row ) && ! empty( $this->db_import_row );
	}

	/**
	 * Check if import action is run less than 5 seconds ago
	 *
	 * @return bool
	 */
	public function is_recently_imported() {
		$last_modified_seconds = time() - strtotime( $this->db_import_row['last_modified'] );

		return $last_modified_seconds <= 5;
	}

	/**
	 * Get Import Status or Start Import
	 *
	 * @param $import_id
	 * @param array $mapped_fields
	 * @param array $tags
	 * @param array $lists
	 * @param false $update_existing
	 * @param false $marketing_status
	 * @param false $disable_events
	 * @param int $imported_contact_status
	 */
	public function maybe_initiate_import( $import_id, $mapped_fields = array(), $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = false, $imported_contact_status = 1, $dont_update_blank_values = true ) {
		$start_pos = absint( $this->db_import_row['offset'] );

		/** Returns if mapped field not found */
		if ( empty( $mapped_fields ) || ! is_array( $mapped_fields ) ) {
			return __( 'Mapped Fields are required', 'wp-marketing-automations-pro' );
		}

		/** Set importer meta data */
		$meta = wp_json_encode( array(
			'mapped_fields'           => $mapped_fields,
			'tags'                    => $tags,
			'lists'                   => $lists,
			'update_existing'         => $update_existing,
			'marketing_status'        => $marketing_status,
			'delimiter'               => $this->get_import_meta( 'delimiter' ),
			'file'                    => $this->get_import_meta( 'file' ),
			'disable_events'          => $disable_events,
			'imported_contact_status' => $imported_contact_status,
			'dont_update_blank'       => $dont_update_blank_values,
			'log_file'                => $this->log_file,
		) );

		/** Update importer entry */
		BWFAN_Model_Import_Export::update( array( "offset" => $start_pos, "meta" => $meta ), array( 'id' => absint( $import_id ) ) );

		/** Check if import action is scheduled and status is in progress */
		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );

		return true;
	}

	/**
	 * Returns the progress of importer
	 *
	 * @return int
	 */
	public function get_percent_completed() {
		$meta = json_decode( $this->db_import_row['meta'], true );
		/** Because file gets deleted after import is completed, so returning 100% */
		if ( ! isset( $meta['file'] ) || ! file_exists( $meta['file'] ) || empty( filesize( $meta['file'] ) ) ) {
			return 100;
		}

		$start_pos = isset( $this->db_import_row['offset'] ) && ! empty( absint( $this->db_import_row['offset'] ) ) ? absint( $this->db_import_row['offset'] ) : 1;

		return absint( min( ( ( $start_pos / filesize( $meta['file'] ) ) * 100 ), 100 ) );
	}

	/**
	 * Update last_modified to inform subsequent requests
	 */
	public function update_last_modified( $minus = 0 ) {
		$last_modified_time = time() - $minus;
		$last_modified_time = date( 'Y-m-d H:i:s', $last_modified_time );
		BWFAN_Model_Import_Export::update( array(
			'last_modified' => $last_modified_time,
		), array( 'id' => absint( $this->import_id ) ) );

		$this->db_import_row['last_modified'] = $last_modified_time;
	}

	/**
	 * Action Scheduler Contact Import
	 *
	 * @param $import_id
	 */
	public function import( $import_id ) {
		/** End import when import data is not found */
		if ( ! $this->maybe_get_import( $import_id ) ) {
			$this->end_import( 2, 'Unable to get Import ID: ' . $import_id );

			return;
		}

		if ( $this->is_recently_imported() || ! empty( $this->db_import_row['meta']['is_running'] ) ) {
			return;
		}

		/** Update last_modified to inform subsequent requests */
		$this->update_last_modified();

		$delimiter = $this->get_import_meta( 'delimiter' );
		$delimiter = ( empty( $delimiter ) ? ',' : $delimiter );

		/** Get File handle */
		$file   = $this->get_import_meta( 'file' );
		$handle = fopen( $file, 'r' );
		if ( false === $handle ) {
			$this->end_import( 2, 'Unable to make handle for File: ' . $file );

			return;
		}

		/** Get File (CSV) Headers */
		$headers = fgetcsv( $handle, 0, $delimiter );
		if ( ! is_array( $headers ) ) {
			$this->end_import( 2, 'Unable to get headers: ' . $file );

			return;
		}

		if ( isset( $headers[0] ) ) {
			$headers[0] = self::remove_utf8_bom( $headers[0] );
		}

		/** Get Mapped Data */
		$this->mapped_fields = $this->get_import_meta( 'mapped_fields' );
		if ( ! is_array( $this->mapped_fields ) || empty( $this->mapped_fields ) ) {
			$this->end_import( 2, 'Mapped Fields Empty' );

			return;
		}

		/** Set Import to running */
		$this->update_status_to_running();

		/** Seek file cursor to current location */
		$this->current_pos = absint( $this->db_import_row['offset'] );
		if ( 0 !== $this->current_pos ) {
			fseek( $handle, $this->current_pos );
		} else {
			$this->db_import_row['offset'] = ftell( $handle );
			$this->current_pos             = $this->db_import_row['offset'];
		}

		$this->start_import_time = time();

		$this->log_handle = fopen( $this->log_file, 'a' );

		$run_time = BWFCRM_Common::get_contact_export_per_call_time();

		if ( ! empty( $this->import_meta['tags'] ) ) {
			$this->newTags = BWFAN_Model_Terms::get_crm_term_ids( $this->import_meta['tags'], BWFCRM_Term_Type::$TAG );
		}

		if ( ! empty( $this->import_meta['lists'] ) ) {
			$this->newLists = BWFAN_Model_Terms::get_crm_term_ids( $this->import_meta['lists'], BWFCRM_Term_Type::$LIST );
		}

		while ( ( ( time() - $this->start_import_time ) < $run_time ) && ! BWFCRM_Common::memory_exceeded() ) {
			$row = fgetcsv( $handle, 0, $delimiter );
			if ( false === $row ) {
				break;
			}

			$this->current_pos = ftell( $handle );

			/** Import contact from CSV row data */
			$this->import_contact( $row );

			/** Update offset & log */
			$this->update_offset_and_log();
		}

		if ( ! empty( $this->log_handle ) ) {
			fclose( $this->log_handle );
		}


		/** Remove the import running status */
		$this->update_status_to_running( true );

		/** End import if completed */
		if ( $this->get_percent_completed() >= 100 ) {
			$this->end_import();

			return;
		}

		/** Reschedule action for immediate run */
		$this->update_last_modified( 7 );
		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );
	}

	/**
	 * Returns import meta data
	 *
	 * @param string $key
	 *
	 * @return mixed|string
	 */
	public function get_import_meta( $key = '' ) {
		return ! empty( $key ) && isset( $this->import_meta[ $key ] ) ? $this->import_meta[ $key ] : '';
	}

	/**
	 * Import Contact from CSV row data
	 *
	 * @param $csv_row
	 */
	public function import_contact( $csv_row ) {
		/** Format contact data form CSV row data */
		$contact_data = $this->prepare_contact_data( $csv_row );
		if ( false === $contact_data ) {
			return;
		}

		/** Disable Events */
		$disable_events = $this->get_import_meta( 'disable_events' );
		if ( true === $disable_events ) {
			$contact_data['data']['disable_events'] = true;
		}

		/** Setting Unsubscribe data before initializing contact, because if contact is new then need to update the status */
		$do_unsubscribe = false;
		if ( 3 === absint( $contact_data['data']['status'] ) ) {
			$contact_data['data']['status'] = 1;
			$do_unsubscribe                 = true;
		}

		/** Get or Create contact and updated status accordingly */
		try {
			$contact = new BWFCRM_Contact( $contact_data['email'], true, $contact_data['data'] );
		} catch ( Exception $e ) {
			/** If failed add failed count and set next user to process */
			$this->failed ++;
			$error_msg = $e->getMessage();
			$this->prepare_log_data( $contact_data['email'], 'failed', $error_msg, '', $contact_data['data'] );

			return;
		}

		/** Check if contact need to update if existing */
		$update_existing = $this->get_import_meta( 'update_existing' );

		/** If contact already exists (old) and Update Existing flag is off, then unset status */
		if ( $contact->already_exists && ! $update_existing ) {
			unset( $contact_data['data']['status'] );
		}

		/** Update contact if update_existing (setting) = true & contact already exists */
		$fields_updated = false;
		try {

			/** If needed to do unsubscribe */
			if ( ( $do_unsubscribe && $update_existing ) || ( $do_unsubscribe && ! $contact->already_exists ) ) {
				$contact->unsubscribe( $disable_events );
			}

			/** If contact was unsubscribed, and new status is not unsubscribed, then remove the entries from Unsubscriber Table */
			if ( ! $do_unsubscribe && $update_existing ) {
				/** NOTE: Checking for: "If the contact was unsubscribed" is already in this below function */
				$contact->remove_unsubscribe_status();
			}

			if ( $contact->already_exists ) {
				// unset creation date if it is already present
				if ( isset( $contact_data['data']['creation_date'] ) && ! empty( $contact->contact->get_creation_date() ) ) {
					unset( $contact_data['data']['creation_date'] );
				}

				/** Disable Events */
				$dont_update_blank = $this->get_import_meta( 'dont_update_blank' );

				// unset fields for which values are blank
				if ( true === $dont_update_blank ) {
					$contact_cols = array( 'email', 'f_name', 'l_name', 'state', 'country', 'contact_no', 'timezone', 'creation_date', 'gender', 'company', 'dob' );
					foreach ( $contact_cols as $col ) {
						if ( ! isset( $contact_data['data'][ $col ] ) || ! empty( trim( $contact_data['data'][ $col ] ) ) ) {
							continue;
						}

						unset( $contact_data['data'][ $col ] );
					}
				}

				$result         = $contact->set_data( $contact_data['data'] );
				$fields_updated = $result['fields_changed'];
			}

			$tags_to_add = $this->newTags;
			/** Tags column in CSV */
			if ( isset( $contact_data['data']['tags'] ) && ! empty( $contact_data['data']['tags'] ) ) {
				$csv_tag     = explode( ',', $contact_data['data']['tags'] );
				$column_tags = array_filter( array_map( function ( $tag ) {
					return ! empty( $tag ) ? array(
						'id'   => 0,
						'name' => trim( $tag ),
					) : [];
				}, $csv_tag ) );

				$tags_to_add = array_unique( array_merge( $tags_to_add, BWFAN_Model_Terms::get_crm_term_ids( $column_tags, BWFCRM_Term_Type::$TAG ) ) );
			}

			$lists_to_add = $this->newLists;
			/** Lists column in CSV */
			if ( isset( $contact_data['data']['lists'] ) && ! empty( $contact_data['data']['lists'] ) ) {
				$csv_list     = explode( ',', $contact_data['data']['lists'] );
				$column_lists = array_filter( array_map( function ( $list ) {
					return ! empty( $list ) ? array(
						'id'   => 0,
						'name' => trim( $list ),
					) : [];
				}, $csv_list ) );

				$lists_to_add = array_unique( array_merge( $lists_to_add, BWFAN_Model_Terms::get_crm_term_ids( $column_lists, BWFCRM_Term_Type::$LIST ) ) );
			}

			/** Apply tags */
			if ( ! empty( $tags_to_add ) ) {
				$contact->set_tags_v2( $tags_to_add, $disable_events );
			}

			/** Apply lists */
			if ( ! empty( $lists_to_add ) ) {
				$contact->set_lists_v2( $lists_to_add, $disable_events );
			}
		} catch ( Exception $e ) {
			$this->failed ++;
			$error_msg = $e->getMessage();
			$this->prepare_log_data( $contact_data['email'], 'failed', $error_msg, $contact, $contact_data['data'] );

			return;
		}

		if ( $fields_updated ) {
			$contact->save_fields();
		}

		$contact->save();

		/** Contact Imported, increase success count */
		$this->succeed ++;
	}

	/**
	 * Returns formatted contact data
	 *
	 * @param $csv_row
	 *
	 * @return array|false
	 */
	public function prepare_contact_data( $csv_row ) {
		/** Get marketing status */
		$marketing_status = $this->get_import_meta( 'marketing_status' );
		$contact_data     = array( 'status' => empty( $marketing_status ) ? 0 : $marketing_status );

		if ( isset( $this->import_meta['imported_contact_status'] ) ) {
			$contact_data['status'] = 0;
		}

		$import_status = $this->get_import_meta( 'imported_contact_status' );

		if ( ! empty( $import_status ) ) {
			$contact_data['status'] = intval( $import_status );
		}

		/** Formatting CSV row data */
		$email  = '';
		$use_mb = function_exists( 'mb_convert_encoding' );

		$error     = false;
		$error_msg = '';
		foreach ( $csv_row as $index => $csv_col ) {
			$csv_col_val = trim( $csv_col );
			if ( ! isset( $this->mapped_fields[ $index ] ) ) {
				continue;
			}

			$field_id = $this->mapped_fields[ $index ];

			if ( $use_mb ) {
				$encoding = mb_detect_encoding( $csv_col_val, mb_detect_order(), true );
				if ( $encoding ) {
					$csv_col_val = mb_convert_encoding( $csv_col_val, 'UTF-8', $encoding );
				} else {
					$csv_col_val = mb_convert_encoding( $csv_col_val, 'UTF-8', 'UTF-8' );
				}
			} else {
				$csv_col_val = wp_check_invalid_utf8( $csv_col_val, true );
			}

			/** Email */
			if ( 'email' === $field_id ) {
				if ( ! is_email( $csv_col_val ) ) {
					$error = true;
					$this->failed ++;
					$error_msg = __( 'Email is not valid.', 'wp-marketing-automations-pro' );
				}
				$email = $csv_col_val;
				continue;
			}

			$contact_data[ $field_id ] = 'country' === $field_id ? BWFAN_PRO_Common::get_country_iso_code( $csv_col_val ) : $csv_col_val;
		}

		if ( empty( $email ) || empty( $contact_data ) ) {
			$this->failed ++;
			$error_msg = __( 'Email is empty.', 'wp-marketing-automations-pro' );
			$this->prepare_log_data( '', 'failed', $error_msg, '', $contact_data );
		}

		if ( true === $error ) {
			$this->prepare_log_data( $email, 'failed', $error_msg, '', $contact_data );

			return false;
		}
		$contact_data['source'] = 'csv';

		return array(
			'email' => $email,
			'data'  => $contact_data,
		);
	}

	public function update_status_to_running( $remove = false ) {
		if ( $remove && isset( $this->import_meta['is_running'] ) ) {
			unset( $this->import_meta['is_running'] );
		} else {
			$this->import_meta['is_running'] = true;
		}
		$import_meta = wp_json_encode( $this->import_meta );

		/** Update status to running */
		BWFAN_Model_Import_Export::update( array( 'meta' => $import_meta ), array( 'id' => absint( $this->import_id ) ) );
		$this->db_import_row['meta'] = $import_meta;
	}

	/**
	 * Updated importer log
	 */
	public function update_offset_and_log() {
		$this->import_meta['log'] = array(
			'succeed' => $this->succeed,
			'failed'  => $this->failed,
			'skipped' => $this->skipped,
		);
		$import_meta              = wp_json_encode( $this->import_meta );

		BWFAN_Model_Import_Export::update( array(
			'offset'        => $this->current_pos,
			'meta'          => $import_meta,
			'last_modified' => current_time( 'mysql', 1 ),
		), array( 'id' => absint( $this->import_id ) ) );

		$this->db_import_row['offset'] = $this->current_pos;
		$this->db_import_row['meta']   = $import_meta;
	}

	/**
	 * Stop importer running
	 *
	 * @param int $status
	 * @param string $status_message
	 */
	public function end_import( $status = 3, $status_message = '' ) {
		/** Checks importer entry exists */
		if ( empty( $this->import_id ) ) {
			return;
		}

		/** Check if import action is scheduled */
		if ( bwf_has_action_scheduled( $this->action_hook ) ) {
			bwf_unschedule_actions( $this->action_hook, array( 'import_id' => absint( $this->import_id ) ), 'bwfcrm' );
		}

		if ( ! empty( $status_message ) ) {
			/** Adding log message */
			BWFAN_Core()->logger->log( $status_message, 'import_contacts_crm' );
		} else if ( 3 === $status ) {
			$status_message = 'Contacts imported. Import ID: ' . $this->import_id;
			if ( isset( $this->import_meta['file'] ) && file_exists( $this->import_meta['file'] ) ) {

				/** Checking csv log file is empty */
				$file_data = ! empty( $this->log_file ) ? file( $this->log_file ) : [];
				if ( ! empty( $this->log_file ) && ! is_array( $file_data ) && empty( $file_data ) ) {
					wp_delete_file( $this->log_file );
				}

				wp_delete_file( $this->import_meta['file'] );
				unset( $this->import_meta['file'] );
			}
		}

		$this->db_import_row['status']   = $status;
		$this->import_meta['status_msg'] = $status_message;
		if ( isset( $this->import_meta['is_running'] ) ) {
			unset( $this->import_meta['is_running'] );
		}
		/** Updating importer data in DB */
		BWFAN_Model_Import_Export::update( array( "status" => $status, "meta" => wp_json_encode( $this->import_meta ) ), array( 'id' => absint( $this->import_id ) ) );
	}

	/**
	 * Prepare log data and append to log file
	 *
	 * @param $email
	 * @param $status
	 * @param $err_msg
	 * @param $contact
	 */
	public function prepare_log_data( $email, $status, $err_msg, $contact = '', $data = [] ) {
		$data = array(
			$contact instanceof BWFCRM_Contact ? $contact->get_id() : 0,
			$email,
			isset( $data['f_name'] ) ? $data['f_name'] : '',
			isset( $data['l_name'] ) ? $data['l_name'] : '',
			$status,
			$err_msg,
		);

		if ( ! empty( $this->log_handle ) ) {
			fputcsv( $this->log_handle, $data );
		}
	}
}

BWFCRM_Core()->importer->register( 'csv', 'BWFCRM_CSV_Importer' );
