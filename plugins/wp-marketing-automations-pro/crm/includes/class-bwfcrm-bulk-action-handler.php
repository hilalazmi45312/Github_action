<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Bulk Action handler
 */
#[AllowDynamicProperties]
class BWFCRM_Bulk_Action_Handler {
	/**
	 * Class Instance
	 *
	 * @var null
	 */
	private static $ins = null;

	private $action_hook = 'bwfcrm_bulk_action_process';

	/**
	 * Working Action ID
	 * @var int
	 */
	private $action_id = 0;

	/**
	 * Working Bulk Action data
	 *
	 * @var array
	 */
	private $bulk_action_data = [];

	private $current_pos = 0;
	private $start_time = 0;
	private $last_processed = 0;
	private $contacts = [];
	private $ended = false;

	private $logs = array();

	/**
	 * Class instance
	 *
	 * @return BWFCRM_Bulk_Action_Handler|null
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( $this->action_hook, [ $this, 'process_bulk_action' ] );
	}

	/**
	 * Process bulk action
	 *
	 * @param $id
	 */
	public function process_bulk_action( $id ) {

		/** Fetch Bulk Action data */
		$data = $this->get_row_data( $id );

		/** Setting working action id */
		$this->action_id = $id;

		$this->current_pos    = absint( $this->bulk_action_data['offset'] );
		$this->last_processed = absint( $this->bulk_action_data['processed'] );

		/** End action scheduler if status is not ongoing */
		if ( ! $data || ( isset( $data['actions'] ) && empty( $data['actions'] ) ) ) {
			$this->end_bulk_action();

			return;
		}

		/** Open file */
		$file = fopen( BWFCRM_BULK_ACTION_LOG_DIR . '/' . $this->bulk_action_data['log_file'], "a" );

		/** Maybe don't run automations */
		if ( ! isset( $this->bulk_action_data['enable_automation_run'] ) || false === $this->bulk_action_data['enable_automation_run'] ) {
			$this->maybe_not_run_automations( $this->bulk_action_data['actions'] );
		} else {
			/** Validate automation basic setting to avoid checking again */
			$this->maybe_filter_automations( $this->bulk_action_data['actions'] );

			/** Don't run automation immediately if bulk action processed */
			BWFAN_PRO_Common::disable_run_v2_automation_immediately();
		}

		$this->start_time = time();

		$run_time = $this->get_per_call_time();
		$this->set_log( 'Bulk action started' );
		while ( ( ( time() - $this->start_time ) < $run_time ) && ! BWFCRM_Common::memory_exceeded() ) {
			/** Populate contacts */
			$this->populate_contacts();

			/** Check if contacts are empty */
			if ( empty( $this->contacts['contacts'] ) ) {
				$this->end_bulk_action();

				return;
			}

			/** Start execution */
			$this->process( $file );
		}

		/** Closing file */
		if ( ! empty( $file ) ) {
			fclose( $file );
		}

		if ( $this->get_percent_completed() >= 100 ) {
			$this->end_bulk_action();
		}

		/** Clear cached data */
		$this->maybe_clear_trigger_automations();
		$this->set_log( 'Bulk action execution ended' );
	}

	public function populate_contacts() {
		/** Fetch contacts */
		$additional_info = [
			'order_by' => 'id',
			'order'    => "ASC"
		];
		if ( isset( $this->bulk_action_data['exclude_ids'] ) && ! empty( $this->bulk_action_data['exclude_ids'] ) ) {
			$additional_info['exclude_ids'] = $this->bulk_action_data['exclude_ids'];
		}
		if ( isset( $this->bulk_action_data['include_ids'] ) && ! empty( $this->bulk_action_data['include_ids'] ) ) {
			$additional_info['include_ids'] = $this->bulk_action_data['include_ids'];
		}
		$filters = [];
		if ( $this->last_processed > 0 ) {
			$filters['contact_id_more_than'] = $this->last_processed;
		}

		$filters        = ! empty( $this->bulk_action_data['contactFilters'] ) ? array_merge( $this->bulk_action_data['contactFilters'], $filters ) : $filters;
		$this->contacts = BWFCRM_Contact::get_contacts( '', 0, 20, $filters, $additional_info, OBJECT, true );

		$this->set_log( 'Contacts fetched: ' . count( $this->contacts['contacts'] ) );
	}

	/**
	 * Schedule AS action for bulk action execution
	 *
	 * @param $id
	 *
	 * @return void
	 */
	public function schedule_bulk_action( $id ) {
		/** Check if action is already scheduled */
		if ( bwf_has_action_scheduled( $this->action_hook, array( 'bulk_action_id' => absint( $id ) ), 'bwfcrm' ) ) {
			return;
		}
		bwf_schedule_recurring_action( time(), 60, $this->action_hook, array( 'bulk_action_id' => absint( $id ) ), 'bwfcrm' );

		/** Ping worker */
		BWFCRM_Common::ping_woofunnels_worker();
	}

	/**
	 * Un-schedule the action if scheduled and mark bulk action 'end'
	 *
	 * @return void
	 */
	public function end_bulk_action() {
		if ( true === $this->ended ) {
			return;
		}

		/** Check if action is already scheduled */
		if ( bwf_has_action_scheduled( $this->action_hook, array( 'bulk_action_id' => absint( $this->action_id ) ), 'bwfcrm' ) ) {
			bwf_unschedule_actions( $this->action_hook, array( 'bulk_action_id' => absint( $this->action_id ) ), 'bwfcrm' );
		}

		/** Change bulk action status to completed */
		BWFAN_Model_Bulk_Action::update( array( 'status' => 2, 'updated_at' => current_time( 'mysql', 1 ) ), array( 'id' => absint( $this->action_id ) ) );
		$this->set_log( 'Bulk action completed' );
		$this->log();
		$this->ended = true;
	}

	/**
	 * Get Bulk Action data by ID primary key
	 *
	 * @param $id
	 *
	 * @return array|false|mixed
	 */
	public function get_row_data( $id ) {
		/** Check action id */
		if ( empty( $id ) ) {
			return false;
		}

		/** Fetch Bulk Action data */
		$data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $id );

		/** Setting working bulk action data */
		$this->bulk_action_data = $data;

		/** End action scheduler if status is not ongoing */
		if ( empty( $data ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Get Bulk Action status
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public function get_bulk_action_status( $id = 0 ) {
		/** Fetch Bulk Action data */
		$data = $this->get_row_data( $id );

		/** End action scheduler if status is not ongoing */
		if ( ! $data ) {
			$this->action_id = $id;
			$this->end_bulk_action();

			return [];
		}

		/**
		 * Get percent completed
		 */
		$percent = $this->get_percent_completed();
		$status  = absint( $data['status'] );
		$offset  = absint( $data['offset'] );

		return array(
			'bulk_action_id' => $id,
			'percent'        => $percent,
			'status'         => $status,
			'offset'         => $offset,
			'log'            => isset( $data['log'] ) ? $data['log'] : []
		);
	}

	/**
	 * Return percentage completed
	 *
	 * @return int
	 */
	public function get_percent_completed() {
		$count  = isset( $this->bulk_action_data['count'] ) && ! empty( intval( $this->bulk_action_data['count'] ) ) ? intval( $this->bulk_action_data['count'] ) : 0;
		$offset = isset( $this->bulk_action_data['offset'] ) && ! empty( intval( $this->bulk_action_data['offset'] ) ) ? intval( $this->bulk_action_data['offset'] ) : 0;

		if ( 0 === $count ) {
			return 100;
		}
		if ( 0 === $offset ) {
			return 0;
		}

		/** In case processed count is greater than total count */
		if ( $offset > $count ) {
			BWFAN_Model_Bulk_Action::update( array( 'count' => $offset, 'updated_at' => current_time( 'mysql', 1 ) ), array( 'id' => absint( $this->bulk_action_data['ID'] ) ) );

			return 100;
		}

		return absint( min( ( ( $offset / $count ) * 100 ), 100 ) );
	}

	/**
	 * Remove the already running action and Re-schedule new one,
	 * And ping WooFunnels worker to run immediately
	 *
	 * @param int $id
	 */
	public function reschedule_background_action( $id ) {
		$this->action_id = $id;

		/** Check if action is already scheduled and end it id exist  */
		$this->end_bulk_action();

		/** Schedule bulk action */
		$this->schedule_bulk_action( $id );
	}

	/**
	 * Process Bulk actions on contacts
	 *
	 * @param $file
	 *
	 * @return void
	 */
	public function process( $file ) {
		$this->bulk_action_data['actions']['id'] = $this->action_id;
		$count                                   = 0;
		foreach ( $this->contacts['contacts'] as $contact ) {

			if ( ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() ) {
				continue;
			}

			/** Perform Actions */
			$res  = BWFCRM_Core()->actions->process_all_actions( $this->bulk_action_data['actions'], $contact );
			$data = [ $contact->get_id() ];
			foreach ( $res as $key => $value ) {
				if ( ! isset( $value['status'] ) ) {
					array_push( $data, 'no' );
					continue;
				}
				switch ( intval( $value['status'] ) ) {
					case 2 :
						array_push( $data, 'yes' );
						break;
					case 3:
						array_push( $data, 'skip' . ( isset( $value['message'] ) ? ' ( ' . $value['message'] . ' )' : '' ) );
						break;
					default:
						array_push( $data, 'no' . ( isset( $value['message'] ) ? ' ( ' . $value['message'] . ' )' : '' ) );
						break;
				}
			}
			$this->set_log( 'processing single contact. ID: ' . implode( ',', $data ) );

			/** Updating log file */
			if ( ! empty( $file ) ) {
				fputcsv( $file, $data );
			}

			$this->last_processed = $contact->get_id();
			$count ++;
		}

		$this->current_pos                += $count;
		$this->bulk_action_data['offset'] = $this->current_pos;

		$this->update_offset();
	}

	/**
	 * Update the offset and processed columns values in the DB
	 * @return void
	 */
	public function update_offset() {
		$data = array( 'offset' => $this->current_pos, 'processed' => $this->last_processed, 'updated_at' => current_time( 'mysql', 1 ) );
		BWFAN_Model_Bulk_Action::update( $data, array( 'id' => absint( $this->action_id ) ) );
	}

	/**
	 * Disallow automations to run, so set blank cache
	 *
	 * @param $actions
	 *
	 * @return void
	 */
	protected function maybe_not_run_automations( $actions ) {
		if ( empty( $actions ) ) {
			return;
		}
		$action_slugs = array_keys( $actions );
		$action_slugs = array_unique( $action_slugs );
		sort( $action_slugs );

		/** Cache obj instance */
		$WooFunnels_Cache_obj = WooFunnels_Cache::get_instance();

		$ins = BWFCRM_Actions_Handler::get_instance();
		foreach ( $action_slugs as $slug ) {
			$bulk_action_ins = $ins->get_action_by_slug( $slug );
			if ( is_null( $bulk_action_ins ) ) {
				continue;
			}
			$event_slug = $bulk_action_ins->get_action_event_slug();
			if ( empty( $event_slug ) ) {
				continue;
			}
			$key = 'bwfan_active_automations_v2_' . $event_slug;
			$WooFunnels_Cache_obj->set_cache( $key, [], 'autonami' );

			$key = 'bwfan_active_automations_' . $event_slug;
			$WooFunnels_Cache_obj->set_cache( $key, [], 'autonami' );
		}
	}

	/**
	 * Clear cache object instance
	 *
	 * @return void
	 */
	protected function maybe_clear_trigger_automations() {
		$WooFunnels_Cache_obj = WooFunnels_Cache::get_instance();
		$WooFunnels_Cache_obj->reset_cache( 'autonami' );
	}

	/**
	 * Filter automations by checking their settings to avoid checking again
	 *
	 * @param $actions
	 *
	 * @return void
	 */
	protected function maybe_filter_automations( $actions ) {
		if ( empty( $actions ) ) {
			return;
		}
		$action_slugs = array_keys( $actions );
		$action_slugs = array_unique( $action_slugs );
		sort( $action_slugs );

		/** Cache obj instance */
		$WooFunnels_Cache_obj = WooFunnels_Cache::get_instance();

		$ins = BWFCRM_Actions_Handler::get_instance();

		foreach ( $action_slugs as $slug ) {
			$bulk_action_ins = $ins->get_action_by_slug( $slug );
			if ( is_null( $bulk_action_ins ) ) {
				continue;
			}
			$event_slug = $bulk_action_ins->get_action_event_slug();
			if ( empty( $event_slug ) ) {
				continue;
			}

			$event = BWFAN_Core()->sources->get_event( $event_slug );
			/** v1 checking */
			$v1_automations = BWFAN_Core()->automations->get_active_automations( 1, $event_slug );
			if ( ! empty( $v1_automations ) && count( $v1_automations ) > 0 ) {
				$invalid_v1_automations = [];
				foreach ( $v1_automations as $a_id => $automation_data ) {
					$res = $event->validate_bulk_action_event_settings( $actions[ $slug ], $automation_data, 1 );
					if ( false === $res ) {
						$invalid_v1_automations[] = $a_id;
					}
				}

				$key1                  = 'bwfan_active_automations_' . $event_slug;
				$v1_active_automations = $WooFunnels_Cache_obj->get_cache( $key1, 'autonami' );
				$v1_active_automations = array_values( array_filter( $v1_active_automations, function ( $v1_a_a ) use ( $invalid_v1_automations ) {
					return ! in_array( $v1_a_a['ID'], $invalid_v1_automations );
				} ) );

				$v1_active_automations = empty( $v1_active_automations ) ? [] : $v1_active_automations;
				$WooFunnels_Cache_obj->set_cache( $key1, $v1_active_automations, 'autonami' );
			}

			/** v2 checking */
			$v2_automations = BWFAN_Core()->automations->get_active_automations( 2, $event_slug );
			if ( ! empty( $v2_automations ) && count( $v2_automations ) > 0 ) {
				$invalid_v2_automations = [];
				foreach ( $v2_automations as $v2_a_id => $_v2_automation_data ) {
					$res = $event->validate_bulk_action_event_settings( $actions[ $slug ], $_v2_automation_data, 2 );
					if ( false === $res ) {
						$invalid_v2_automations[] = $v2_a_id;
					}
				}

				$key2 = 'bwfan_active_automations_v2_' . $event_slug;

				$v2_active_automations = $WooFunnels_Cache_obj->get_cache( $key2, 'autonami' );
				$v2_active_automations = array_values( array_filter( $v2_active_automations, function ( $v2_a_a ) use ( $invalid_v2_automations ) {
					return ! in_array( $v2_a_a['ID'], $invalid_v2_automations );
				} ) );

				$v2_active_automations = empty( $v2_active_automations ) ? [] : $v2_active_automations;
				$WooFunnels_Cache_obj->set_cache( $key2, $v2_active_automations, 'autonami' );
			}
		}
	}

	public function get_per_call_time() {
		if ( defined( 'BWFCRM_BULK_ACTION_AS_CALL_SECONDS' ) ) {
			return absint( BWFCRM_BULK_ACTION_AS_CALL_SECONDS );
		}

		return apply_filters( 'bwfan_as_per_call_time', 30 );
	}

	public function set_log( $log ) {
		if ( empty( $log ) ) {
			return;
		}
		$this->logs[] = array(
			't' => microtime( true ),
			'm' => $log,
		);
	}

	protected function log() {
		if ( ! is_array( $this->logs ) || 0 === count( $this->logs ) ) {
			return;
		}

		if ( false === apply_filters( 'bwfan_allow_bulk_action_logging', BWFAN_PRO_Common::is_log_enabled( 'bwfan_bulk_action_logging' ) ) ) {
			return;
		}
		add_filter( 'bwfan_before_making_logs', '__return_true' );
		BWFAN_Core()->logger->log( print_r( $this->logs, true ), 'fka-bulk-action' );
		$this->logs = [];
	}
}

if ( class_exists( 'BWFCRM_Bulk_Action_Handler' ) ) {
	BWFCRM_Core::register( 'bulk_action', 'BWFCRM_Bulk_Action_Handler' );
}
