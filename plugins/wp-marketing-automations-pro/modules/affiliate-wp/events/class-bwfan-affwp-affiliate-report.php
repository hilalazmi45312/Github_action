<?php

#[AllowDynamicProperties]
final class BWFAN_AFFWP_Affiliate_Report extends BWFAN_Event {
	private static $instance = null;
	public $affiliate_id = false;
	public $referral_count = 0;
	public $visits = 0;
	public $commissions = 0;
	public $from = false;
	public $to = false;
	public $user_id = 0;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'aff_affiliate', 'aff_report', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Affiliate Digests', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs per affiliate and gives their report for the selected date range.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'affiliatewp',
			'affiliate_report',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'AffiliateWP', 'wp-marketing-automations-pro' );
		$this->priority               = 60;
		$this->is_time_independent    = true;
		$this->customer_email_tag     = '{{affwp_affiliate_email}}';
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfan_send_affiliate_insights', array( $this, 'send_affiliate_insights' ), 10, 2 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'days_options', $data['days'] );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'dates_options', $data['dates'] );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'last_run', $data['last_run'] );
		}
	}

	public function get_view_data() {
		$days  = [
			'0' => __( 'Sunday', 'wp-marketing-automations-pro' ),
			'1' => __( 'Monday', 'wp-marketing-automations-pro' ),
			'2' => __( 'Tuesday', 'wp-marketing-automations-pro' ),
			'3' => __( 'Wednesday', 'wp-marketing-automations-pro' ),
			'4' => __( 'Thursday', 'wp-marketing-automations-pro' ),
			'5' => __( 'Friday', 'wp-marketing-automations-pro' ),
			'6' => __( 'Saturday', 'wp-marketing-automations-pro' ),
		];
		$dates = [];
		for ( $i = 1; $i <= 28; $i ++ ) {
			$dates[ $i ] = $i;
		}
		$dates['end_of_month'] = __( 'End Of Month', 'wp-marketing-automations-pro' );

		$last_run = '';
		if ( isset( $_GET['edit'] ) ) {
			$last_run = BWFAN_Model_Automationmeta::get_meta( sanitize_text_field( $_GET['edit'] ), 'last_run' );
			if ( false !== $last_run ) {
				$last_run = date( get_option( 'date_format' ), strtotime( $last_run ) );
			}
		}

		return [
			'days'     => $days,
			'dates'    => $dates,
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
            selected_day = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'run_day')) ? data.eventSavedData.run_day : '';
            selected_date = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'run_date')) ? data.eventSavedData.run_date : '';
            selected_run_type = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'run_weekly_monthly')) ? data.eventSavedData.run_weekly_monthly : '';
            selected_earning_weekly = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'earning_weekly')) ? data.eventSavedData.earning_weekly : '';
            selected_earning_monthly = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'earning_monthly')) ? data.eventSavedData.earning_monthly : '';

            if(''  == selected_run_type){
            selected_run_type = "weekly";
            }

            if('' == selected_earning_weekly){
            selected_earning_weekly = "last_week";
            }

            if('' == selected_earning_monthly){
            selected_earning_monthly = "last_month";
            }

            run_weekly = "weekly" === selected_run_type?'checked':'';
            run_monthly = "monthly" === selected_run_type?'checked':'';

            run_earning_weekly = "last_week" ===selected_earning_weekly?'checked':'';
            run_earning_7_days = "last_7_days" ===selected_earning_weekly?'checked':'';
            run_earning_monthly = "last_month" ===selected_earning_monthly?'checked':'';
            run_earning_30_days = "last_30_days" ===selected_earning_monthly?'checked':'';
            #>
            <div class="bwfan-col-sm-12 bwfan-p-0 bwfan_mt15">
                <label for="bwfan-run_weekly_monthly" class="bwfan-label-title"><?php echo esc_html__( 'Frequency', 'wp-marketing-automations-pro' ); ?></label>

                <input type="radio" name="event_meta[run_weekly_monthly]" id="bwfan-run_weekly_monthly" class="bwfan-input-wrapper" value="weekly" {{run_weekly}}> <?php echo esc_html__( 'Weekly', 'wp-marketing-automations-pro' ); ?>
                <input type="radio" name="event_meta[run_weekly_monthly]" id="bwfan-run_weekly_monthly" class="bwfan-input-wrapper" value="monthly" {{run_monthly}}> <?php echo esc_html__( 'Monthly', 'wp-marketing-automations-pro' ); ?>
            </div>
            <# hide_run_day = "monthly" === selected_run_type?"bwfan-display-none":'';  #>
            <div class="bwfan-col-sm-6 bwfan-pl-0 bwfan_mt15 bwfan-run-day {{hide_run_day}}">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Day', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[run_day]">
                    <#
                    if(_.has(data.eventFieldsOptions, 'days_options') && _.isObject(data.eventFieldsOptions.days_options) ) {
                    _.each( data.eventFieldsOptions.days_options, function( value, key ){
                    selected = (key == selected_day) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    } #>
                </select>
            </div>
            <# hide_run_date = "weekly" === selected_run_type?"bwfan-display-none":'';  #>
            <div class="bwfan-col-sm-6 bwfan-pl-0 bwfan_mt15 bwfan-run-date {{hide_run_date}}">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Date', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper " name="event_meta[run_date]">
                    <#
                    if(_.has(data.eventFieldsOptions, 'dates_options') && _.isObject(data.eventFieldsOptions.dates_options) ) {
                    _.each( data.eventFieldsOptions.dates_options, function( value, key ){
                    selected = (key == selected_date) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    } #>
                </select>
            </div>
            <# hide_earning_weekly = "monthly" === selected_run_type?"bwfan-display-none":'';  #>
            <div class="bwfan-col-sm-12 bwfan-p-0 bwfan_mt15 bwfan-earning-weekly {{hide_earning_weekly}}">
                <label for="bwfan-run_weekly_monthly" class="bwfan-label-title"><?php echo esc_html__( 'Calculate Metrics', 'wp-marketing-automations-pro' ); ?></label>
                <input type="radio" name="event_meta[earning_weekly]" id="bwfan_earning_period_earning_weekly" class="bwfan-input-wrapper" value="last_week" {{run_earning_weekly}}> <?php echo esc_html__( 'Last Week (Mon-Sun)', 'wp-marketing-automations-pro' ); ?>
                <input type="radio" name="event_meta[earning_weekly]" id="bwfan_earning_period_earning_weekly" class="bwfan-input-wrapper" value="last_7_days" {{run_earning_7_days}}> <?php echo esc_html__( 'Last 7 days', 'wp-marketing-automations-pro' ); ?>
            </div>
            <# hide_earning_monthly = "weekly" === selected_run_type?"bwfan-display-none":'';  #>
            <div class="bwfan-col-sm-12 bwfan-p-0 bwfan_mt15 bwfan-earning-monthly {{hide_earning_monthly}}">
                <label for="bwfan-run_weekly_monthly" class="bwfan-label-title"><?php echo esc_html__( 'Calculate Metrics', 'wp-marketing-automations-pro' ); ?></label>
                <input type="radio" name="event_meta[earning_monthly]" id="bwfan_earning_period_monthly" class="bwfan-input-wrapper" value="last_month" {{run_earning_monthly}}> <?php echo esc_html__( 'Last Month', 'wp-marketing-automations-pro' ); ?>
                <input type="radio" name="event_meta[earning_monthly]" id="bwfan_earning_period_monthly" class="bwfan-input-wrapper" value="last_30_days" {{run_earning_30_days}}> <?php echo esc_html__( 'Last 30 days', 'wp-marketing-automations-pro' ); ?>
            </div>
            <# if( _.has(data.eventFieldsOptions, 'last_run') && '' != data.eventFieldsOptions.last_run ) { #>
            <div class="bwfan-clear"></div>
            <div class="bwfan-input-form bwfan-row-sep bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <div class="clearfix bwfan_field_desc"><?php echo esc_html__( 'This automation last ran on ', 'wp-marketing-automations-pro' ); ?>{{data.eventFieldsOptions.last_run}}</div>
            </div>
            <# } #>
        </script>
        <script>
            jQuery(document).on('change', '#bwfan-run_weekly_monthly', function () {
                var selected_run = jQuery(this).val();
                if ("weekly" === selected_run) {
                    jQuery(".bwfan-run-day").removeClass("bwfan-display-none");
                    jQuery(".bwfan-run-date").addClass("bwfan-display-none");
                    jQuery(".bwfan-earning-weekly").removeClass("bwfan-display-none");
                    jQuery(".bwfan-earning-monthly").addClass("bwfan-display-none");
                } else {
                    jQuery(".bwfan-run-day").addClass("bwfan-display-none");
                    jQuery(".bwfan-run-date").removeClass("bwfan-display-none");
                    jQuery(".bwfan-earning-weekly").addClass("bwfan-display-none");
                    jQuery(".bwfan-earning-monthly").removeClass("bwfan-display-none");
                }
            });
        </script>
		<?php
	}

	public function process( $affiliate_id, $referrals_count, $visits, $commissions, $from, $to ) {
		$data                   = $this->get_default_data();
		$data['affiliate_id']   = $affiliate_id;
		$data['referral_count'] = $referrals_count;
		$data['visits']         = $visits;
		$data['commissions']    = $commissions;
		$data['from']           = $from;
		$data['to']             = $to;

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->affiliate_id, 'affiliate_id' );
		BWFAN_Core()->rules->setRulesData( $this->referral_count, 'referral_count' );
		BWFAN_Core()->rules->setRulesData( $this->visits, 'visits' );
		BWFAN_Core()->rules->setRulesData( $this->commissions, 'commissions' );
		BWFAN_Core()->rules->setRulesData( $this->from, 'from' );
		BWFAN_Core()->rules->setRulesData( $this->to, 'to' );
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
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
		$data_to_send                             = [ 'global' => [] ];
		$data_to_send['global']['affiliate_id']   = $this->affiliate_id;
		$data_to_send['global']['referral_count'] = $this->referral_count;
		$data_to_send['global']['visits']         = $this->visits;
		$data_to_send['global']['commissions']    = $this->commissions;
		$data_to_send['global']['from']           = $this->from;
		$data_to_send['global']['to']             = $this->to;
		$data_to_send['global']['user_id']        = $this->user_id;

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

		$format = get_option( 'date_format' );
		$format = str_replace( 'Y', '', $format );
		$format = str_replace( ',', '', $format );

		$from         = date( $format, strtotime( $global_data['from'] ) );
		$to           = date( $format, strtotime( $global_data['to'] ) );
		$affiliate_id = isset( $global_data['affiliate_id'] ) ? $global_data['affiliate_id'] : 0;
		?>
        <li>
            <strong><?php echo esc_html__( 'Range:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $from ) . ' - ' . esc_html__( $to ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Affiliate ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url( 'admin.php' ) . '?page=affiliate-wp-affiliates&affiliate_id=' . $affiliate_id . '&action=edit_affiliate'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $affiliate_id ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Referral Count:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['referral_count'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Referral Commissions:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['commissions'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Visits:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['visits'] ); ?>
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
				'affiliate_id'   => intval( $task_meta['global']['affiliate_id'] ),
				'referral_count' => intval( $task_meta['global']['referral_count'] ),
				'visits'         => intval( $task_meta['global']['visits'] ),
				'commissions'    => $task_meta['global']['commissions'],
				'from'           => $task_meta['global']['from'],
				'to'             => $task_meta['global']['to'],
				'user_id'        => $task_meta['global']['user_id'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->affiliate_id   = BWFAN_Common::$events_async_data['affiliate_id'];
		$this->referral_count = BWFAN_Common::$events_async_data['referral_count'];
		$this->visits         = BWFAN_Common::$events_async_data['visits'];
		$this->commissions    = BWFAN_Common::$events_async_data['commissions'];
		$this->from           = BWFAN_Common::$events_async_data['from'];
		$this->to             = BWFAN_Common::$events_async_data['to'];
		$this->user_id        = affwp_get_affiliate_user_id( $this->affiliate_id );

		return $this->run_automations();
	}

	public function get_email_event() {
		if ( ! empty( absint( $this->user_id ) ) ) {
			$user = get_user_by( 'id', absint( $this->user_id ) );

			return ( $user instanceof WP_User ) ? $user->user_email : false;
		}

		return false;
	}

	public function get_user_id_event() {
		return ! empty( absint( $this->user_id ) ) ? absint( $this->user_id ) : false;
	}

	/**
	 * This is a time independent event. A cron is run once a day and it makes all the tasks for the current event.
	 *
	 * @param $automation_id
	 * @param $automation_details
	 */
	public function make_task_data( $automation_id, $automation_details ) {
		$affiliates = BWFAN_PRO_Common::get_all_active_affiliates();

		if ( empty( $affiliates ) ) {
			return;
		}

		$run_weekly = $automation_details['meta']['event_meta']['run_weekly_monthly'];

		if ( 'weekly' === $run_weekly ) {
			$this->run_weekly_automation( $automation_id, $automation_details['meta']['event_meta'], $affiliates );

			return;
		}

		$this->run_monthly_automation( $automation_id, $automation_details['meta']['event_meta'], $affiliates );
	}

	private function run_weekly_automation( $automation_id, $event_meta, $affiliates ) {
		$selected_day = intval( $event_meta['run_day'] );
		$timestamp    = current_time( 'timestamp' );
		$date_time    = new DateTime();
		$date_time->setTimestamp( $timestamp );
		$day_today = intval( $date_time->format( 'w' ) );

		if ( $selected_day !== $day_today ) {
			return;
		}

		$date_time->setTime( 00, 00, 00 );
		$current_day = $date_time->format( 'Y-m-d' );
		$last_run    = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'last_run' );

		if ( false !== $last_run ) {
			$last_run_obj = new DateTime( $last_run );
			$diff         = $date_time->diff( $last_run_obj );

			if ( 7 > intval( $diff->days ) ) {
				return;
			}

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

		if ( 'last_week' === $event_meta['earning_weekly'] ) {
			$to        = date( 'Y-m-d', strtotime( 'last sunday' ) );
			$last_week = new DateTime( $to );
			$last_week->modify( '-6days' );
			$from = date( 'Y-m-d', $last_week->getTimestamp() );
		} else {
			$date_time->modify( '-1day' );
			$to = date( 'Y-m-d', $date_time->getTimestamp() );
			$date_time->modify( '-6day' );
			$from = date( 'Y-m-d', $date_time->getTimestamp() );
		}

		$affiliate_batches = array_chunk( $affiliates, 100 );

		if ( count( $affiliate_batches ) === 0 ) {
			return;
		}

		/** Check if automations v1 or v2 exists */
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );
		BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );

		if ( ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 ) && ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) ) {
			return;
		}
		/**
		 * recurring action schedule 2 mins interval
		 * event slug as option key - bwf_async_event_{slug} = subscription_ids_array
		 * after running capture_subscription method, update the subscription id value in the option key
		 */

		$final_batches = [];
		foreach ( $affiliate_batches as $ids ) {
			$final_batches[] = [
				'ids'  => $ids,
				'from' => $from,
				'to'   => $to,
			];
		}

		$key  = 'bwf_async_event_' . $this->get_slug() . '_' . time();
		$args = [ 'key' => $key, 'aid' => $automation_id ];
		if ( ! bwf_has_action_scheduled( 'bwfan_send_affiliate_insights', $args ) ) {
			bwf_schedule_recurring_action( time(), 120, 'bwfan_send_affiliate_insights', $args );
			update_option( $key, $final_batches, false );
		}
	}

	private function run_monthly_automation( $automation_id, $event_meta, $affiliates ) {
		$selected_date = $event_meta['run_date'];
		$timestamp     = current_time( 'timestamp' );
		$date_time     = new DateTime();
		$date_time->setTimestamp( $timestamp );
		$date_today = intval( $date_time->format( 'd' ) );

		if ( 'end_of_month' === $selected_date ) {
			$end_date = intval( $date_time->format( 't' ) );
			if ( $end_date !== $date_today ) {
				return;
			}
		} elseif ( intval( $selected_date ) !== $date_today ) {
			return;
		}

		$date_time->setTime( 00, 00, 00 );
		$current_day = $date_time->format( 'Y-m-d' );
		$last_run    = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'last_run' );

		if ( false !== $last_run ) {
			$previous_month = clone $date_time;
			$previous_month->modify( '-1month' );
			$last_run_obj = new DateTime( $last_run );
			$diff         = $previous_month->diff( $last_run_obj );

			if ( 0 === intval( $diff->invert ) && 0 !== intval( $diff->days ) ) {
				return;
			}

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

		if ( 'last_month' === $event_meta['earning_monthly'] ) {
			$from = date( 'Y-m-d', strtotime( 'first day of last month' ) );
			$to   = date( 'Y-m-d', strtotime( 'last day of last month' ) );
		} else {
			$date_time->modify( '-1day' );
			$to = date( 'Y-m-d', $date_time->getTimestamp() );
			$date_time->modify( '+1day' );
			$date_time->modify( '-1month' );
			$from = date( 'Y-m-d', $date_time->getTimestamp() );
		}

		$affiliate_batches = array_chunk( $affiliates, 100 );

		if ( count( $affiliate_batches ) === 0 ) {
			return;
		}

		/** Check if automations v1 or v2 exists */
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );
		BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );

		if ( ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 ) && ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) ) {
			return;
		}

		/**
		 * recurring action schedule 2 mins interval
		 * event slug as option key - bwf_async_event_{slug} = subscription_ids_array
		 * after running capture_subscription method, update the subscription id value in the option key
		 */

		$final_batches = [];
		foreach ( $affiliate_batches as $ids ) {
			$final_batches[] = [
				'ids'  => $ids,
				'from' => $from,
				'to'   => $to,
			];
		}
		$key  = 'bwf_async_event_' . $this->get_slug() . '_' . time();
		$args = [ 'key' => $key, 'aid' => $automation_id ];
		if ( ! bwf_has_action_scheduled( 'bwfan_send_affiliate_insights', $args ) ) {
			bwf_schedule_recurring_action( time(), 120, 'bwfan_send_affiliate_insights', $args );
			update_option( $key, $final_batches, false );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {

		$this->affiliate_id   = BWFAN_Common::$events_async_data['affiliate_id'];
		$this->referral_count = BWFAN_Common::$events_async_data['referral_count'];
		$this->visits         = BWFAN_Common::$events_async_data['visits'];
		$this->commissions    = BWFAN_Common::$events_async_data['commissions'];
		$this->from           = BWFAN_Common::$events_async_data['from'];
		$this->to             = BWFAN_Common::$events_async_data['to'];
		$this->user_id        = affwp_get_affiliate_user_id( $this->affiliate_id );

		$automation_data['affiliate_id']   = $this->affiliate_id;
		$automation_data['referral_count'] = $this->referral_count;
		$automation_data['visits']         = $this->visits;
		$automation_data['commissions']    = $this->commissions;
		$automation_data['from']           = $this->from;
		$automation_data['to']             = $this->to;
		$automation_data['user_id']        = $this->user_id;

		return $automation_data;
	}

	/** trigger affiliate report automations */
	public function send_affiliate_insights( $option_key, $automation_id ) {
		$affiliates = get_option( $option_key, [] );

		if ( empty( $affiliates ) ) {
			delete_option( $option_key );
			bwf_unschedule_actions( 'bwfan_send_affiliate_insights', [ 'key' => $option_key, 'aid' => $automation_id ] );

			return;
		}

		/** Validate automation */
		$automation = BWFAN_Model_Automations::get_automation_with_data( $automation_id );

		if ( isset( $automation['meta'] ) ) {
			$meta = $automation['meta'];
			unset( $automation['meta'] );
			$automation = array_merge( $automation, $meta );
		}
		if ( 1 !== intval( $automation['status'] ) || ( 0 === absint( $automation['start'] ) && 2 === absint( $automation['v'] ) ) ) {
			delete_option( $option_key );
			bwf_unschedule_actions( 'bwfan_send_affiliate_insights', [ 'key' => $option_key, 'aid' => $automation_id ] );

			return;
		}

		$updated_affiliates = $affiliates;
		$start_time         = time();
		foreach ( $affiliates as $aff_key => $affiliate_ids ) {

			/**checking 10 seconds of processing */
			if ( ( time() - $start_time ) > 10 ) {
				return;
			}

			foreach ( $affiliate_ids['ids'] as $affiliate_id ) {
				$referrals_count = BWFAN_PRO_Common::get_referrals_count_from_period( $affiliate_id, $affiliate_ids['from'], $affiliate_ids['to'] );
				$visits          = BWFAN_PRO_Common::get_visits_from_period( $affiliate_id, $affiliate_ids['from'], $affiliate_ids['to'] );
				$commissions     = BWFAN_PRO_Common::get_commissions_from_period( $affiliate_id, $affiliate_ids['from'], $affiliate_ids['to'] );

				$this->process( $affiliate_id, $referrals_count, $visits, $commissions, $affiliate_ids['from'], $affiliate_ids['to'] );
			}

			unset( $updated_affiliates[ $aff_key ] );
			update_option( $option_key, $updated_affiliates, false );
		}
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {
		$data  = $this->get_view_data();
		$days  = isset( $data['days'] ) ? BWFAN_PRO_Common::prepared_field_options( $data['days'] ) : [];
		$dates = isset( $data['dates'] ) ? BWFAN_PRO_Common::prepared_field_options( $data['dates'] ) : [];

		return [
			[
				'id'      => 'run_weekly_monthly',
				'label'   => __( "Frequency", 'wp-marketing-automations-pro' ),
				'type'    => 'radio',
				'options' => [
					[
						'label' => __( "Weekly", 'wp-marketing-automations-pro' ),
						'value' => 'weekly',
					],
					[
						'label' => __( "Monthly", 'wp-marketing-automations-pro' ),
						'value' => 'monthly',
					]
				],
			],
			[
				'id'          => 'run_day',
				'label'       => __( 'Select Day', 'wp-marketing-automations-pro' ),
				'type'        => 'wp_select',
				'options'     => $days,
				'class'       => 'bwfan-input-wrapper',
				'description' => '',
				'required'    => false,
				'toggler'     => [
					'fields'   => [
						[
							'id'    => 'run_weekly_monthly',
							'value' => 'weekly',
						],
					],
					'relation' => 'AND',
				],
			],
			[
				'id'          => 'run_date',
				'label'       => __( 'Select Date', 'wp-marketing-automations-pro' ),
				'type'        => 'wp_select',
				'options'     => $dates,
				'class'       => 'bwfan-input-wrapper',
				'description' => '',
				'required'    => false,
				'toggler'     => [
					'fields'   => [
						[
							'id'    => 'run_weekly_monthly',
							'value' => 'monthly',
						],
					],
					'relation' => 'AND',
				],
			],
			[
				'id'      => 'earning_weekly',
				'label'   => __( "Calculate Metrics", 'wp-marketing-automations-pro' ),
				'type'    => 'radio',
				'options' => [
					[
						'label' => __( "Last Week (Mon-Sun)", 'wp-marketing-automations-pro' ),
						'value' => 'last_week',
					],
					[
						'label' => __( "Last 7 days", 'wp-marketing-automations-pro' ),
						'value' => 'last_7_days',
					]
				],
				'toggler' => [
					'fields'   => [
						[
							'id'    => 'run_weekly_monthly',
							'value' => 'weekly',
						],
					],
					'relation' => 'AND',
				],
			],
			[
				'id'      => 'earning_monthly',
				'label'   => __( "Calculate Metrics", 'wp-marketing-automations-pro' ),
				'type'    => 'radio',
				'options' => [
					[
						'label' => __( "Last Month", 'wp-marketing-automations-pro' ),
						'value' => 'last_month',
					],
					[
						'label' => __( "Last 30 days", 'wp-marketing-automations-pro' ),
						'value' => 'last_30_days',
					]
				],
				'toggler' => [
					'fields'   => [
						[
							'id'    => 'run_weekly_monthly',
							'value' => 'monthly',
						],
					],
					'relation' => 'AND',
				],
			],
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_affiliatewp_active() ) {
	return 'BWFAN_AFFWP_Affiliate_Report';
}
