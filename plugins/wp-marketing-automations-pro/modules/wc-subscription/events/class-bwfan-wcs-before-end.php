<?php

#[AllowDynamicProperties]
final class BWFAN_WCS_Before_End extends BWFAN_Event {
	private static $instance = null;
	public $subscription = null;
	public $subscription_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_subscription' );

		$this->event_name          = esc_html__( 'Subscriptions Before End', 'wp-marketing-automations-pro' );
		$this->event_desc          = esc_html__( 'This event runs every day and checks for the subscriptions which are about to expire/ end.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups   = array(
			'wc_subscription',
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
		$this->optgroup_label      = esc_html__( 'Subscription', 'wp-marketing-automations-pro' );
		$this->support_lang        = true;
		$this->priority            = 25.4;
		$this->is_time_independent = true;
		$this->v2                  = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfan_subscription_before_end_triggered', array( $this, 'bwfan_trigger_before_end' ), 10, 2 );
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
            days_entered = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'days_before')) ? data.eventSavedData.days_before : '15';
            hours_entered = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'hours')) ? data.eventSavedData.hours : 11;
            minutes_entered = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'minutes')) ? data.eventSavedData.minutes : '';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <label for="bwfan-days_before" class="bwfan-label-title"><?php echo esc_html__( 'Days before subscription  end', 'wp-marketing-automations-pro' ); ?></label>
                <input required type="number" name="event_meta[days_before]" id="bwfan-days_before" class="bwfan-input-wrapper" value="{{days_entered}}"/>
            </div>
            <div class="bwfan-col-sm-12 bwfan_mt15 bwfan-p-0">
                <label for="bwfan-hours" class="bwfan-label-title"><?php echo esc_html__( 'Run at following (Store) Time of a Day', 'wp-marketing-automations-pro' ); ?></label>
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
	 * This is a time independent event. A cron is run once a day and it makes all the tasks for the current event.
	 *
	 * @param $automation_id
	 * @param $automation_details
	 *
	 * @throws Exception
	 */
	public function make_task_data( $automation_id, $automation_details ) {

		$date_time   = new DateTime();
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

		/** @todo check the below date logic */
		$days_before_end = $automation_details['meta']['event_meta']['days_before'];
		$date            = new \DateTime();
		$date->modify( '+' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' ); // get site time
		$date->modify( "+$days_before_end days" );

		$day_start = clone $date;
		$day_end   = clone $date;
		$day_start->setTime( 00, 00, 01 );
		$day_end->setTime( 23, 59, 59 );

		$day_start->modify( '-' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );
		$day_end->modify( '-' . BWFAN_Common::get_timezone_offset() * HOUR_IN_SECONDS . ' seconds' );
		$statuses   = apply_filters( 'bwfan_event_wcs_before_end_statuses', array( 'wc-active' ) );
		$start_date = $day_start->format( 'Y-m-d H:i:s' );
		$end_date   = $day_end->format( 'Y-m-d H:i:s' );

		if ( method_exists( 'BWF_WC_Compatibility', 'is_hpos_enabled' ) && BWF_WC_Compatibility::is_hpos_enabled() ) {
			global $wpdb;
			$status_placeholders = array_fill( 0, count( $statuses ), '%s' );
			$status_placeholders = implode( ', ', $status_placeholders );
			$args                = [ $start_date, $end_date ];
			$args                = array_merge( $args, $statuses );

			$query = "SELECT orders.id FROM {$wpdb->prefix}wc_orders AS orders INNER JOIN {$wpdb->prefix}wc_orders_meta AS meta ON orders.id = meta.order_id WHERE 1=1 AND ( meta.meta_key = '_schedule_end' AND meta.meta_value > %s ) AND ( meta.meta_key = '_schedule_end' AND meta.meta_value < %s ) AND orders.type = 'shop_subscription' AND orders.status IN ( $status_placeholders ) GROUP BY orders.id ORDER BY orders.date_created_gmt DESC";
			$query = $wpdb->prepare( $query, $args );
			$posts = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$posts = ! empty( $posts ) ? array_column( $posts, 'id' ) : [];
		} else {
			$query = new \WP_Query( [
				'post_type'      => 'shop_subscription',
				'post_status'    => $statuses,
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'no_found_rows'  => true,
				'meta_query'     => [
					[
						'key'     => '_schedule_end',
						'compare' => '>',
						'value'   => $start_date,
					],
					[
						'key'     => '_schedule_end',
						'compare' => '<',
						'value'   => $end_date,
					],
				],
			] );
			$posts = $query->posts;
		}

		if ( ! is_array( $posts ) || count( $posts ) === 0 ) {
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
		$key  = 'bwf_async_event_' . $this->get_slug() . '_' . time();
		$args = [ 'key' => $key, 'aid' => $automation_id ];
		if ( ! bwf_has_action_scheduled( 'bwfan_subscription_before_end_triggered', $args ) ) {
			bwf_schedule_recurring_action( time(), 120, 'bwfan_subscription_before_end_triggered', $args );
			update_option( $key, $posts, false );
		}
	}

	/**
	 * trigger subscription before end automations
	 */
	public function bwfan_trigger_before_end( $option_key, $automation_id ) {
		$subscriptions = get_option( $option_key, [] );

		if ( empty( $subscriptions ) ) {
			delete_option( $option_key );
			bwf_unschedule_actions( 'bwfan_subscription_before_end_triggered', [ 'key' => $option_key, 'aid' => $automation_id ] );

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
			bwf_unschedule_actions( 'bwfan_subscription_before_end_triggered', [ 'key' => $option_key, 'aid' => $automation_id ] );

			return;
		}

		$updated_subscriptions = $subscriptions;
		$start_time            = time();

		foreach ( $subscriptions as $key => $subscription_id ) {

			/**checking 10 seconds of processing */
			if ( ( time() - $start_time ) > 10 ) {
				return;
			}

			$this->capture_subscription( $subscription_id, $automation );
			unset( $updated_subscriptions[ $key ] );
			update_option( $option_key, $updated_subscriptions, false );

		}

	}

	public function capture_subscription( $subscription_id, $automation ) {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return;
		}

		/** v2 event execution start */
		$this->subscription_id = $subscription_id;
		$this->subscription    = wcs_get_subscription( $subscription_id );

		if ( 2 === absint( $automation['v'] ) ) {
			return $this->run_v2( $automation, $this->get_slug() );
		}
		/** v2 event execution end */
		$this->run_automations();
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_subscription( $data );
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		return $this->validate_subscription( $automation_data );
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
		$user_id = $this->get_user_id_event();
		$email   = ! empty( $user_id ) ? BWFAN_PRO_Common::get_contact_email( $user_id ) : '';
		if ( empty( $email ) && is_object( $this->subscription ) ) {
			$email = $this->subscription->get_billing_email();
		}

		$data_to_send                                 = [ 'global' => [] ];
		$data_to_send['global']['wc_subscription_id'] = is_object( $this->subscription ) ? $this->subscription->get_id() : '';
		$data_to_send['global']['wc_subscription']    = is_object( $this->subscription ) ? $this->subscription : '';
		$data_to_send['global']['email']              = $email;

		if ( intval( $user_id ) > 0 ) {
			$data_to_send['global']['user_id'] = $user_id;
		}

		return $data_to_send;
	}

	public function get_user_id_event() {
		if ( $this->subscription instanceof WC_Subscription ) {
			return $this->subscription->get_user_id();
		}

		return 0;
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
            <strong><?php echo esc_html__( 'Subscription ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wc_subscription_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['wc_subscription_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Subscription Email:', 'wp-marketing-automations-pro' ); ?> </strong>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'wc_subscription_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['wc_subscription_id'] ) ) && function_exists( 'wcs_get_subscription' ) ) {
			$set_data = array(
				'wc_subscription_id' => intval( $task_meta['global']['wc_subscription_id'] ),
				'email'              => $task_meta['global']['email'],
				'user_id'            => $task_meta['global']['user_id'],
				'wc_subscription'    => $task_meta['global']['wc_subscription'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->subscription, 'wc_subscription' );
		BWFAN_Core()->rules->setRulesData( $this->event_automation_id, 'automation_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->get_email_event(), $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_email_event() {
		return $this->subscription->get_billing_email();
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'days_before',
				'type'        => 'number',
				'value'       => "15",
				"min"         => '0',
				'label'       => __( 'Days before subscription end', 'wp-marketing-automations-pro' ),
				"description" => ""
			],
			[
				'id'          => 'scheduled-everyday-at',
				'type'        => 'expression',
				'expression'  => " {{hours /}} {{minutes /}}",
				'label'       => 'Run at following (Store) Time of a Day',
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

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	return 'BWFAN_WCS_Before_End';
}
