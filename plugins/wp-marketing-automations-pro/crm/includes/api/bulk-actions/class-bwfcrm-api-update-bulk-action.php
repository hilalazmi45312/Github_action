<?php

/**
 * Update bulk action API
 */
class BWFCRM_Api_Update_Bulk_Action extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::EDITABLE;
		$this->route         = '/bulk-action/(?P<bulk_action_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		return array(
			'bulk_action_id' => '',
			'count'          => 0,
		);
	}

	public function process_api_call() {
		$bulk_action_id = $this->get_sanitized_arg( 'bulk_action_id', 'key' );
		$title          = $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] );
		$created_at     = isset( $this->args['created_at'] ) ? $this->args['created_at'] : '';

		if ( empty( $created_at ) ) {
			/** Title update call */
			BWFAN_Model_Bulk_Action::update_bulk_action_data( $bulk_action_id, [ 'title' => $title ] );

			$data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $bulk_action_id );

			return $this->success_response( $data, __( 'Bulk action updated', 'wp-marketing-automations-pro' ) );
		}

		$count = $this->get_sanitized_arg( 'count', 'text_field', $this->args['count'] );

		$actions               = isset( $this->args['actions'] ) && ! empty( $this->args['actions'] ) ? $this->args['actions'] : [];
		$contact_filters       = isset( $this->args['contactFilters'] ) && ! empty( $this->args['contactFilters'] ) ? $this->args['contactFilters'] : [];
		$exclude_ids           = isset( $this->args['exclude_ids'] ) && ! empty( $this->args['exclude_ids'] ) ? $this->args['exclude_ids'] : [];
		$include_ids           = isset( $this->args['include_ids'] ) && ! empty( $this->args['include_ids'] ) ? $this->args['include_ids'] : [];
		$start_bulk_action     = isset( $this->args['start_bulk_action'] ) && boolval( $this->args['start_bulk_action'] );
		$stop_bulk_action      = isset( $this->args['stop_bulk_action'] ) && boolval( $this->args['stop_bulk_action'] );
		$file_name             = isset( $this->args['log_file'] ) && boolval( $this->args['log_file'] );
		$enable_automation_run = isset( $this->args['enable_automation_run'] ) && boolval( $this->args['enable_automation_run'] );
		$meta                  = [];

		if ( empty( $title ) ) {
			$this->response_code = 400;
			$response            = __( 'Oops Title not entered, enter title to save bulk action', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$already_exists = BWFAN_Model_Bulk_Action::check_bulk_action_exists_with( 'ID', $bulk_action_id );

		if ( ! $already_exists ) {
			$this->response_code = 400;
			$response            = __( "Bulk Action doesn't exists with id : ", 'wp-marketing-automations-pro' ) . $bulk_action_id;

			return $this->error_response( $response );
		}

		$update_time = current_time( 'mysql', 1 );

		$meta['contactFilters']        = $contact_filters;
		$meta['exclude_ids']           = $exclude_ids;
		$meta['include_ids']           = $include_ids;
		$meta['enable_automation_run'] = $enable_automation_run;
		$actions                       = $this->add_new_list_and_tags( $actions );

		$link_data = [
			'title'      => $title,
			'count'      => intval( $count ) > 0 ? $count : 0,
			'actions'    => json_encode( $actions ),
			'updated_at' => $update_time,
		];

		if ( $start_bulk_action ) {
			$link_data['status'] = 1;
			/** Check if file already exists */
			if ( empty( $file_name ) ) {
				/** Check for directory exists */
				if ( ! file_exists( BWFCRM_BULK_ACTION_LOG_DIR . '/' ) ) {
					wp_mkdir_p( BWFCRM_BULK_ACTION_LOG_DIR );
				}

				/** Create log file */
				$file_name = 'FKA-Bulk-Action-' . time() . '-' . wp_generate_password( 5, false ) . '-log.csv';

				/** Open log file */
				$file = fopen( BWFCRM_BULK_ACTION_LOG_DIR . '/' . $file_name, "wb" );
				if ( ! empty( $file ) ) {
					/** Updating headers in log file */
					fputcsv( $file, array_merge( [ 'Contact ID' ], array_keys( $actions ) ) );

					/** Close log file */
					fclose( $file );
				}
			}

			$meta['log_file'] = $file_name;
		}

		if ( $stop_bulk_action ) {
			$link_data['status'] = 3;
			if ( bwf_has_action_scheduled( 'bwfcrm_bulk_action_process', array( 'bulk_action_id' => absint( $bulk_action_id ) ), 'bwfcrm' ) ) {
				bwf_unschedule_actions( 'bwfcrm_bulk_action_process', array( 'bulk_action_id' => absint( $bulk_action_id ) ), 'bwfcrm' );
			}
		}

		$link_data['meta'] = json_encode( $meta );

		/** Updating db entry for bulk action */
		$result = BWFAN_Model_Bulk_Action::update_bulk_action_data( $bulk_action_id, $link_data );

		/** Error handle */
		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to update bulk action', 'wp-marketing-automations-pro' ) );
		}

		/** Schedule action */
		if ( $start_bulk_action ) {
			BWFCRM_Core()->bulk_action->schedule_bulk_action( $bulk_action_id );
		}

		/** Get bulk action data to return */
		$data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $bulk_action_id );

		if ( empty( $data ) ) {
			return $this->error_response( __( 'Bulk Action not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Set success response */
		return $this->success_response( $data, __( 'Bulk action updated', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Create tags and list
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	public function add_new_list_and_tags( $actions ) {
		foreach ( $actions as $action_key => $action_val ) {
			$terms     = [];
			$term_type = 0;
			if ( $action_key === 'add_tags' ) {
				$term_type = BWFCRM_Term_Type::$TAG;
				$terms     = $action_val;
			} else if ( $action_key === 'add_to_lists' ) {
				$term_type = BWFCRM_Term_Type::$LIST;
				$terms     = $action_val;
			}

			if ( is_array( $terms ) && ! empty( $terms ) && method_exists( 'BWFAN_PRO_Common', 'get_or_create_terms' ) ) {
				$terms                  = BWFAN_PRO_Common::get_or_create_terms( $terms, $term_type );
				$actions[ $action_key ] = $terms;
			}
		}

		return $actions;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_Bulk_Action' );
