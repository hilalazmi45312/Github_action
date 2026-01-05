<?php

final class BWFAN_WC_Customer_Win_Back extends BWFAN_Event {
	private static $instance = null;
	public $user_id = 0;
	public $email = '';
	public $first_name = '';
	public $last_name = '';
	protected $min = 30;
	protected $max_gap = 15;
	private $action_hook = 'bwfan_winback_one_minute_action';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact' );

		$this->event_name             = esc_html__( 'Customer Win Back', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs once per customers for selected period based on Last Ordered Date.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'wc_customer',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Customer', 'wp-marketing-automations-pro' );
		$this->source_type            = 'wc';
		$this->priority               = 60;
		$this->is_time_independent    = true;
		$this->v2                     = true;
		$this->optgroup_priority      = 20;
		$this->disable_default_fields = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfan_sync_call_delete_tasks', array( $this, 'delete_schedule_tasks' ), 11, 2 );
		add_action( $this->action_hook, array( $this, 'do_winback_schedule_work' ), 10, 3 );
	}

	/**
	 * Delete already schedule tasks by email or phone of current event for v1 automation
	 *
	 * @param $email
	 * @param $phone
	 *
	 * @return void
	 */
	public function delete_schedule_tasks( $email, $phone = '' ) {
		if ( empty( $email ) && empty( $phone ) ) {
			return;
		}

		$active_automations = BWFAN_Core()->automations->get_active_automations( 1, $this->get_slug() );
		if ( empty( $active_automations ) ) {
			return;
		}
		$win_back_automations = array_keys( $active_automations );

		/** get schedule task by email */
		$schedule_tasks = [];
		if ( ! empty( $email ) ) {
			$schedule_tasks_email = BWFAN_Common::get_schedule_task_by_email( $win_back_automations, $email );

			$schedule_tasks = array_merge( $schedule_tasks, $schedule_tasks_email );
		}
		if ( ! empty( $phone ) ) {
			$schedule_tasks_phone = BWFAN_Common::get_schedule_task_by_phone( $win_back_automations, $phone );

			$schedule_tasks = array_merge( $schedule_tasks, $schedule_tasks_phone );
		}

		$schedule_tasks = array_filter( $schedule_tasks );

		if ( 0 === count( $schedule_tasks ) ) {
			return;
		}

		$schedule_tasks = array_unique( $schedule_tasks );

		$delete_tasks = array();
		foreach ( $schedule_tasks as $automation_id => $tasks ) {
			if ( empty( $tasks ) ) {
				continue;
			}
			foreach ( $tasks as $task ) {
				$delete_tasks[] = $task['ID'];
			}
			BWFAN_Core()->tasks->delete_tasks( $delete_tasks );
		}
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'last_run', $data['last_run'] );
		}
	}

	public function get_view_data() {
		$last_run = '';
		if ( isset( $_GET['edit'] ) ) {
			$last_run = BWFAN_Model_Automationmeta::get_meta( sanitize_text_field( $_GET['edit'] ), 'last_run' );
			if ( false !== $last_run ) {
				$last_run = date( get_option( 'date_format' ), strtotime( $last_run ) );
			}
		}

		return [
			'last_run' => $last_run,
		];
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            min_days_entered = (_.has(data, 'eventSavedData') && _.has(data.eventSavedData, 'days_min')) ? data.eventSavedData.days_min : '';
            min_days_entered = ('' == min_days_entered) ? '<?php esc_attr_e( $this->min ) ?>' : min_days_entered;
            max_days_entered = (_.has(data, 'eventSavedData') && _.has(data.eventSavedData, 'days_max')) ? data.eventSavedData.days_max : '';
            max_days_entered = ('' == max_days_entered) ? '<?php esc_attr_e( $this->min + $this->max_gap ) ?>' : max_days_entered;
            hours_entered = (_.has(data, 'eventSavedData') && _.has(data.eventSavedData, 'hours')) ? data.eventSavedData.hours : 11;
            minutes_entered = (_.has(data, 'eventSavedData') && _.has(data.eventSavedData, 'minutes')) ? data.eventSavedData.minutes :'';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <label for="bwfan-days_before" class="bwfan-label-title"><?php echo esc_html__( 'Customer Last Ordered Period', 'wp-marketing-automations-pro' ); ?></label>
                Over <input required type="number" name="event_meta[days_min]" id="bwfan-days_before" placeholder="30" class="bwfan-input-wrapper bwfan-input-s" value="{{min_days_entered}}"/> days ago
                AND Under <input required type="number" name="event_meta[days_max]" id="bwfan-days_before" placeholder="45" class="bwfan-input-wrapper bwfan-input-s" value="{{max_days_entered}}"/>
                days ago
            </div>
            <div class="clearfix bwfan_field_desc"><?php echo esc_html__( 'Runs once per customer for the selected period', 'wp-marketing-automations-pro' ); ?></div>
            <div class="bwfan-clear"></div>
            <div class="bwfan-input-form bwfan-row-sep bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <label for="bwfan-hours" class="bwfan-label-title"><?php echo esc_html__( 'Schedule this automation to run everyday at', 'wp-marketing-automations-pro' ); ?></label>
                <input max="23" min="0" type="number" name="event_meta[hours]" id="bwfan-hours" class="bwfan-input-wrapper bwfan-input-inline" value="{{hours_entered}}" placeholder="<?php echo esc_html__( 'HH', 'wp-marketing-automations-pro' ); ?>"/>
                :
                <input max="59" min="0" type="number" name="event_meta[minutes]" id="bwfan-minutes" class="bwfan-input-wrapper bwfan-input-inline" value="{{minutes_entered}}" placeholder="<?php echo esc_html__( 'MM', 'wp-marketing-automations-pro' ); ?>"/>
                <# if( _.has(data.eventFieldsOptions, 'last_run') && '' != data.eventFieldsOptions.last_run ) { #>
                <div class="clearfix bwfan_field_desc"><?php echo esc_html__( 'This automation last ran on ', 'wp-marketing-automations-pro' ); ?>{{data.eventFieldsOptions.last_run}}</div>
                <# } #>
            </div>
        </script>
		<?php
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->first_name, 'first_name' );
		BWFAN_Core()->rules->setRulesData( $this->last_name, 'last_name' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->email, $this->user_id ), 'bwf_customer' );
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['user_id']    = $this->user_id;
		$data_to_send['global']['email']      = $this->email;
		$data_to_send['global']['first_name'] = $this->first_name;
		$data_to_send['global']['last_name']  = $this->last_name;

		return $data_to_send;
	}

	/**
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	public function get_task_view( $global_data ) {
		ob_start();
		?>
        <li>
            <strong><?php echo esc_html__( 'Name:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['first_name'] ) . esc_html__( $global_data['last_name'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'User ID:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['user_id'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'referral_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['referral_id'] ) ) ) {
			$set_data = array(
				'user_id'    => $task_meta['global']['user_id'],
				'email'      => $task_meta['global']['email'],
				'first_name' => $task_meta['global']['first_name'],
				'last_name'  => $task_meta['global']['last_name'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->user_id    = BWFAN_Common::$events_async_data['user_id'];
		$this->email      = BWFAN_Common::$events_async_data['email'];
		$this->first_name = BWFAN_Common::$events_async_data['first_name'];
		$this->last_name  = BWFAN_Common::$events_async_data['last_name'];

		return $this->run_automations();
	}

	/**
	 * This is a time independent event. A cron is run once a day and it makes all the tasks for the current event.
	 *
	 * @param $automation_id
	 * @param $automation_details
	 */
	public function make_task_data( $automation_id, $automation_details ) {
		global $wpdb;

		$date_time = new DateTime();
		$date_time->setTime( 00, 00, 00 );
		$current_day = $date_time->format( 'Y-m-d' );
		$last_run    = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'last_run' );

		if ( false !== $last_run ) {

			$where = [
				'bwfan_automation_id' => $automation_id,
				'meta_key'            => 'last_run',
			];
			$data  = [
				'meta_value' => $current_day,
			];

			BWFAN_Model_Automationmeta::update( $data, $where );
		} else {

			$meta = [
				'bwfan_automation_id' => $automation_id,
				'meta_key'            => 'last_run',
				'meta_value'          => $current_day,
			];

			BWFAN_Model_Automationmeta::insert( $meta );
		}

		$days_min = isset( $automation_details['meta']['event_meta']['last-ordered']['days_min'] ) ? $automation_details['meta']['event_meta']['last-ordered']['days_min'] : $automation_details['meta']['event_meta']['days_min'];
		$days_max = isset( $automation_details['meta']['event_meta']['last-ordered']['days_max'] ) ? $automation_details['meta']['event_meta']['last-ordered']['days_max'] : $automation_details['meta']['event_meta']['days_max'];

		$days_min = ( empty( $days_min ) ) ? $this->min : $days_min;
		$days_max = ( empty( $days_max ) ) ? ( $this->min + $this->max_gap ) : $days_max;

		$min_date = new DateTime();
		$min_date->modify( '+' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' ); // get site time
		$max_date = clone $min_date;

		$min_date->modify( "-$days_min days" );
		$max_date->modify( "-$days_max days" );

		$max_date->setTime( 00, 00, 01 );
		$min_date->setTime( 23, 59, 59 );
		$max_date->modify( '-' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );
		$min_date->modify( '-' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );

		$start_date = $max_date->format( 'Y-m-d H:i:s' );
		$end_date   = $min_date->format( 'Y-m-d H:i:s' );

		$start_date_timestamp = $max_date->getTimestamp();

		/** query always from older date (start) to new date (end) */

		/** Get all the customers of selected range in the automations */
		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$customers = $wpdb->get_results( $wpdb->prepare( "
		                                        SELECT c.wpid as id, c.email as email, c.f_name as first_name, c.l_name as last_name, c.id as bwf_cid
		                                        FROM {$wpdb->prefix}bwf_contact as c
		                                        LEFT JOIN {$wpdb->prefix}bwf_wc_customers as wc
		                                        ON c.id = wc.cid
		                                        WHERE c.email !='' 
		                                        AND wc.l_order_date > %s
		                                        AND wc.l_order_date < %s
		                                        ", $start_date, $end_date ), ARRAY_A );

		if ( empty( $customers ) ) {
			return;
		}

		/** Check if automations v1 or v2 exists */
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );
		BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );

		if ( ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 ) && ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) ) {
			return;
		}

		if ( count( $this->automations_arr ) > 0 ) {
			/** Get all the customers where automations already ran for the given automation range */
			$query = $wpdb->prepare( "SELECT DISTINCT `contact_id` FROM `{$wpdb->prefix}bwfan_contact_automations` WHERE `automation_id` = %d AND `time` > %d", $automation_id, $start_date_timestamp );

			$ran_customers = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		} else {

			$contacts = BWFAN_Model_Automation_Contact::get_contacts_automation( $automation_id, $start_date );

			$complete_contacts = BWFAN_Model_Automation_Complete_Contact::get_contacts_automation( $automation_id, $start_date );

			$ran_customers = array_merge( $contacts, $complete_contacts );
		}

		/** filter customer */
		if ( is_array( $ran_customers ) && count( $ran_customers ) > 0 ) {
			$ran_customers = array_column( $ran_customers, 'contact_id' );

			$filtered_customers = array_column( array_filter( $customers, function ( $customer ) use ( $ran_customers ) {

				if ( ! in_array( $customer['bwf_cid'], $ran_customers, true ) ) {
					return $customer['bwf_cid'];
				}

			} ), 'bwf_cid' );

			$customers = $filtered_customers;
		} else {
			$customers = array_column( $customers, 'bwf_cid' );
		}

		if ( empty( $customers ) ) {
			if ( bwf_has_action_scheduled( $this->action_hook ) ) {
				bwf_unschedule_actions( $this->action_hook, array(), 'woofunnels' );
			}

			return;
		}

		$key  = 'bwf_async_event_' . $this->get_slug() . '_' . time();
		$args = [ 'key' => $key, 'aid' => $automation_id, 'new' => 1 ];
		if ( ! bwf_has_action_scheduled( $this->action_hook ) ) {
			bwf_schedule_recurring_action( time(), 60, $this->action_hook, $args, 'woofunnels' );
			bwf_options_update( $key, $customers );
		}

	}

	public function do_winback_schedule_work( $option_key, $aid, $new = 0 ) {
		$customers = [];
		if ( 0 === $new ) {
			/** Un-schedule old scheduled hook */
			bwf_unschedule_actions( $this->action_hook, [ 'key' => $option_key, 'aid' => $aid ], 'woofunnels' );

			$customers = get_option( $option_key, [] );
			delete_option( $option_key );

			$args = [ 'key' => $option_key, 'aid' => $aid, 'new' => 1 ];
			bwf_schedule_recurring_action( time(), 60, $this->action_hook, $args, 'woofunnels' );
		}

		$customers = empty( $customers ) ? bwf_options_get( $option_key ) : $customers;
		if ( empty( $customers ) ) {
			bwf_options_delete( $option_key );
			bwf_unschedule_actions( $this->action_hook, [ 'key' => $option_key, 'aid' => $aid, 'new' => 1 ], 'woofunnels' );
		}

		global $wpdb;
		$updated_customers = $customers;
		$start_time        = time();
		$batch_size        = 10;
		while ( ( time() - $start_time ) < 15 ) {
			/** Get first 10 ids */
			$customers          = ( count( $customers ) > $batch_size ) ? array_slice( $customers, 0, $batch_size ) : $customers;
			$selected_customers = implode( ', ', $customers );

			$query              = "SELECT c.wpid as id,c.id as cid, c.email as email, c.f_name as first_name, c.l_name as last_name FROM {$wpdb->prefix}bwf_contact as c where c.id IN ($selected_customers)";
			$selected_customers = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			foreach ( $selected_customers as $customer ) {
				$this->process( $customer['id'], $customer['email'], $customer['first_name'], $customer['last_name'] );
			}

			/** Remove customer ids from data after automation process */
			$updated_customers = array_diff( $updated_customers, $customers );
			if ( empty( $updated_customers ) ) {
				bwf_options_delete( $option_key );
				bwf_unschedule_actions( $this->action_hook, [ 'key' => $option_key, 'aid' => $aid, 'new' => 1 ], 'woofunnels' );

				return;
			}
			sort( $updated_customers );
			bwf_options_update( $option_key, $updated_customers );

			/** Assign updated customers */
			$customers = $updated_customers;
		}
	}

	public function update_scheduler_offset( $offset, $aid ) {
		BWFAN_Model_Automationmeta::update( [
			'meta_value' => $offset,
		], [
			'bwfan_automation_id' => $aid,
			'meta_key'            => 'winback_schedule_offset',
		] );
	}

	public function process( $user_id, $email, $first_name, $last_name ) {
		$this->user_id    = $user_id;
		$this->email      = $email;
		$this->first_name = $first_name;
		$this->last_name  = $last_name;

		/** v2 event execution start */
		$contact_data_v2 = array(
			'user_id'    => $user_id,
			'email'      => $email,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);

		BWFAN_Common::maybe_run_v2_automations( $this->get_slug(), $contact_data_v2 );
		/** v2 event execution end */

		$this->run_automations();
	}

	public function get_email_event() {
		return $this->email;
	}

	/**
	 * @param $task_details
	 *
	 * @return array
	 */
	public function validate_event( $task_details ) {
		$result                        = [];
		$automation_id                 = $task_details['processed_data']['automation_id'];
		$current_automation_event_meta = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'event_meta' );

		$days_min = $current_automation_event_meta['days_min'];
		$days_min = ( empty( $days_min ) ) ? $this->min : $days_min;
		$days_max = $current_automation_event_meta['days_max'];
		$days_max = ( empty( $days_max ) ) ? ( $this->min + $this->max_gap ) : $days_max;

		$winback_timestamp = $this->get_winback_start_end_timestamp( $days_min, $days_max );

		$processed_email = $task_details['processed_data']['email'];
		$processed_wpid  = isset( $task_details['processed_data']['user_id`'] ) ? $task_details['processed_data']['user_id`'] : 0;

		$customer = BWFAN_Common::get_bwf_customer( $processed_email, $processed_wpid );

		$order_last_date = '';
		if ( $customer ) {
			$order_last_date = $customer->get_l_order_date();
		}

		if ( empty( $order_last_date ) ) {
			return $this->get_automation_event_success();
		}

		$order_last_date_timestamp = strtotime( $order_last_date );
		if ( ( $order_last_date_timestamp > $winback_timestamp['start_date_timestamp'] ) && ( $order_last_date_timestamp < $winback_timestamp['end_date_timestamp'] ) ) {
			return $this->get_automation_event_success();
		}

		$order_last_order_date = date( 'Y-m-d h:i:s', $order_last_date_timestamp );

		$result['status']  = 4;
		$result['message'] = esc_html__( "Customer Last Order date ( $order_last_order_date ) doesn't lies in this period: $days_min - $days_max days", 'wp-marketing-automations-pro' );

		return $result;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		$days_min = $automation_data['event_meta']['last-ordered']['days_min'];
		$days_min = ( empty( $days_min ) ) ? $this->min : $days_min;
		$days_max = $automation_data['event_meta']['last-ordered']['days_max'];
		$days_max = ( empty( $days_max ) ) ? ( $this->min + $this->max_gap ) : $days_max;

		$winback_timestamp = $this->get_winback_start_end_timestamp( $days_min, $days_max );

		$processed_email = $automation_data['email'];
		$processed_wpid  = isset( $automation_data['user_id`'] ) ? $automation_data['user_id`'] : 0;

		$customer = BWFAN_Common::get_bwf_customer( $processed_email, $processed_wpid );

		$order_last_date = '';
		if ( $customer ) {
			$order_last_date = $customer->get_l_order_date();
		}

		if ( empty( $order_last_date ) ) {
			return true;
		}

		$order_last_date_timestamp = strtotime( $order_last_date );
		if ( ( $order_last_date_timestamp > $winback_timestamp['start_date_timestamp'] ) && ( $order_last_date_timestamp < $winback_timestamp['end_date_timestamp'] ) ) {
			return true;
		}

		return false;
	}

	/** getting the start , end timestamp of winback event
	 *
	 * @param $days_min
	 * @param $days_max
	 *
	 * @return mixed
	 */
	public function get_winback_start_end_timestamp( $days_min, $days_max ) {
		$min_date = new DateTime();
		$min_date->modify( '+' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' ); // get site time
		$max_date = clone $min_date;

		$min_date->modify( "-$days_min days" );
		$max_date->modify( "-$days_max days" );

		$max_date->setTime( 00, 00, 01 );
		$min_date->setTime( 23, 59, 59 );
		$max_date->modify( '-' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );
		$min_date->modify( '-' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );

		$timestamp['start_date_timestamp'] = $max_date->getTimestamp();
		$timestamp['end_date_timestamp']   = $min_date->getTimestamp();

		return $timestamp;
	}

	public function get_fields_schema() {
		return [
			[
				'id'         => 'last-ordered',
				'type'       => 'expression',
				'expression' => __( 'Over', 'wp-marketing-automations-pro' ) . ' {{days_min/}} ' . __( 'days ago AND Under', 'wp-marketing-automations-pro' ) . ' {{days_max /}} ' . __( 'days ago', 'wp-marketing-automations-pro' ),
				'label'      => __( 'Customer Last Ordered Period', 'wp-marketing-automations-pro' ),
				'fields'     => array(
					array(
						"id"          => 'days_min',
						"label"       => '',
						"type"        => 'number',
						"min"         => '0',
						"placeholder" => "30",
						"hint"        => "",
						"nested"      => '',
						"required"    => false,
					),
					array(
						"id"          => 'days_max',
						"label"       => '',
						"type"        => 'number',
						"min"         => '0',
						"placeholder" => "45",
						"hint"        => "",
						"nested"      => '',
						"required"    => false,
					)
				),
				"hint"       => __( 'Runs once per customer for the selected period.', 'wp-marketing-automations-pro' ),
			],
			[
				'id'          => 'scheduled-everyday-at',
				'type'        => 'expression',
				'expression'  => " {{hours /}} {{minutes /}}",
				'label'       => __( 'Schedule this automation to run everyday at (24 Hours)', 'wp-marketing-automations-pro' ),
				'fields'      => [
					[
						"id"          => 'hours',
						"label"       => '',
						"type"        => 'number',
						"max"         => '23',
						"min"         => '0',
						"class"       => 'bwfan-input-wrapper bwfan-input-s',
						"placeholder" => "HH",
						"description" => "",
						"required"    => false,
					],
					[
						"id"          => 'minutes',
						"label"       => '',
						"type"        => 'number',
						"max"         => '59',
						"min"         => '0',
						"class"       => 'bwfan-input-wrapper bwfan-input-s',
						"placeholder" => "MM",
						"description" => "",
						"required"    => false,
					]
				],
				"description" => ""
			],
		];
	}

	/** v2 Methods: END */

	/** set default values */
	public function get_default_values() {
		return [
			'last-ordered'          => [
				'days_min' => '60',
				'days_max' => '90',
			],
			'scheduled-everyday-at' => [
				'hours'   => '7',
				'minutes' => '00',
			],
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() ) {
	return 'BWFAN_WC_Customer_Win_Back';
}
