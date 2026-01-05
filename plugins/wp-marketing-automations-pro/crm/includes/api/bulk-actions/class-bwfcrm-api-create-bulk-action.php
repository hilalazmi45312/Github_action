<?php

/**
 * Create bulk action API
 */
class BWFCRM_Api_Create_Bulk_Action extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/bulk-action';
		$this->response_code = 200;
	}

	public function default_args_values() {
		return array(
			'title' => '',
		);
	}

	public function process_api_call() {
		$title       = $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] );
		$only_action = isset( $this->args['only_action'] ) && boolval( $this->args['only_action'] );
		$include_ids = isset( $this->args['include_ids'] ) && ! empty( $this->args['include_ids'] ) ? $this->args['include_ids'] : [];
		$actions     = isset( $this->args['actions'] ) && ! empty( $this->args['actions'] ) ? $this->args['actions'] : [];

		if ( empty( $title ) && ! $only_action ) {
			$this->response_code = 400;
			$response            = __( 'Oops Title not entered, enter title to create bulk action', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$already_exists = ! $only_action ? BWFAN_Model_Bulk_Action::check_bulk_action_exists_with( 'title', $title ) : false;

		if ( $already_exists ) {
			$this->response_code = 400;
			$response            = __( 'Bulk Action already exists with title : ', 'wp-marketing-automations-pro' ) . $title;

			return $this->error_response( $response );
		}

		$utc_time   = current_time( 'mysql', 1 );
		$store_time = current_time( 'mysql' );

		$data = [
			'title'      => $only_action ? __( 'Contacts Bulk Action ', 'wp-marketing-automation-pro' ) . '(' . $store_time . ')' : $title,
			'status'     => 0,
			'created_by' => get_current_user_id(),
			'created_at' => $utc_time,
			'updated_at' => $utc_time,
		];

		if ( $only_action ) {
			if ( empty( $actions ) ) {
				$this->response_code = 400;
				$response            = __( 'Oops Actions not entered, enter actions to create bulk action', 'wp-marketing-automations-pro' );

				return $this->error_response( $response );
			}

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
			$enable_automation = isset( $this->args['enable_automation'] ) && boolval( $this->args['enable_automation'] );

			$data['actions'] = json_encode( $this->add_new_list_and_tags( $actions ) );
			$data['status']  = 1;
			$data['count']   = count( $include_ids );
			// Set meta data
			$data['meta']['include_ids']           = $include_ids;
			$data['meta']['log_file']              = $file_name;
			$data['meta']['enable_automation_run'] = $enable_automation;
			$data['meta']                          = json_encode( $data['meta'] );

		}

		$result = BWFAN_Model_Bulk_Action::bwfan_create_new_bulk_action( $data );
		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to create bulk action', 'wp-marketing-automations-pro' ) );
		}

		if ( $only_action ) {
			BWFCRM_Core()->bulk_action->schedule_bulk_action( $result );
		}

		return $this->success_response( [ 'id' => $result ], __( 'Bulk action created', 'wp-marketing-automations-pro' ) );
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

BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Bulk_Action' );
