<?php

#[AllowDynamicProperties]
final class BWFAN_WCS_Card_Expiry extends BWFAN_Event {
	private static $instance = null;
	public $expire_token_id = null;
	public $expire_token_user_id = null;
	public $expire_token_meta = [];
	private $data = [];

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wcs_card_expiry' );

		$this->event_rule_groups   = array(
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
		$this->event_name          = esc_html__( 'Customer Before Card Expiry', 'wp-marketing-automations-pro' );
		$this->event_desc          = esc_html__( 'This event runs every day and checks for user\'s saved cards which are about to expire.', 'wp-marketing-automations-pro' );
		$this->priority            = 55;
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
		add_action( 'bwfan_subscription_card_expiry_triggered', array( $this, 'bwfan_trigger_card_expiry' ), 10, 2 );
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
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	public function get_task_view( $global_data ) {
		$user_id = $global_data['expire_token_user_id'];
		$user    = get_user_by( 'ID', $user_id );

		if ( false === $user ) {
			return esc_html__( 'Customer not exists', 'wp-marketing-automations-pro' );
		}
		ob_start();
		?>
        <li>
            <strong><?php echo esc_html__( 'Name:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $user->display_name ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $user->user_email ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Card:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( 'xxxx-xxxx-xxxx-' . $global_data['expire_token_meta']['last4'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Expiry:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['expire_token_meta']['expiry_month'] . '/' . $global_data['expire_token_meta']['expiry_year'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            days_before=(_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'days_before')) ? data.eventSavedData.days_before : '15';
            hours_entered=(_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'hours')) ? data.eventSavedData.hours : 11;
            minutes_entered=(_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'minutes')) ? data.eventSavedData.minutes : '';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Days before card expiry', 'wp-marketing-automations-pro' ); ?></label>
                <input type="number" required id="" class="bwfan-input-wrapper" name="event_meta[days_before]" value="{{days_before}}">
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

	private function process( $expire_token, $automation ) {
		global $wpdb;

		$sql        = $wpdb->prepare( "select * from $wpdb->payment_tokenmeta where payment_token_id= %s;", $expire_token['token_id'] );
		$token_data = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $token_data ) ) {
			return;
		}

		$token_meta = [];
		foreach ( $token_data as $token ) {
			$meta_key                = $token['meta_key'];
			$meta_value              = $token['meta_value'];
			$token_meta[ $meta_key ] = $meta_value;
		}

		$this->expire_token_id      = $expire_token['token_id'];
		$this->expire_token_user_id = $expire_token['user_id'];
		$this->expire_token_meta    = $token_meta;

		if ( 2 === absint( $automation['v'] ) ) {
			if ( $this->may_be_running_v2_automation( $automation['ID'], $this->expire_token_user_id ) ) {
				return;
			}

			return $this->run_v2( $automation, $this->get_slug() );
		}

		$this->run_automations();
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
		$data_to_send                                   = [ 'global' => [] ];
		$data_to_send['global']['expire_token_id']      = $this->expire_token_id;
		$data_to_send['global']['expire_token_user_id'] = $this->expire_token_user_id;
		$data_to_send['global']['expire_token_meta']    = $this->expire_token_meta;
		$data_to_send['global']['user_id']              = $this->expire_token_user_id;

		return $data_to_send;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$meta    = $task_meta['global']['expire_token_meta'];
		$user_id = $task_meta['global']['expire_token_user_id'];
		$user    = get_user_by( 'ID', $user_id );

		if ( false === $user ) {
			return;
		}

		$set_data = array(
			'credit_card_type'         => $meta['card_type'],
			'credit_card_expiry_month' => $meta['expiry_month'],
			'credit_card_expiry_year'  => $meta['expiry_year'],
			'credit_card_last4'        => $meta['last4'],
			'email'                    => $user->user_email,
			'user_id'                  => $user_id,
		);

		BWFAN_Merge_Tag_Loader::set_data( $set_data );
	}

	public function pre_executable_actions( $value ) {

	}

	public function get_email_event() {
		if ( ! empty( absint( $this->expire_token_user_id ) ) ) {
			$user = get_user_by( 'id', absint( $this->expire_token_user_id ) );

			return ( $user instanceof WP_User ) ? $user->user_email : false;
		}

		return false;
	}

	public function get_user_id_event() {
		return ! empty( absint( $this->expire_token_user_id ) ) ? absint( $this->expire_token_user_id ) : false;
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
		global $wpdb;

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

		$days_before = isset( $automation_details['meta']['event_meta']['days_before'] ) ? $automation_details['meta']['event_meta']['days_before'] : 0;
		$date        = new DateTime();
		BWFAN_Common::convert_from_gmt( $date ); // get cards based on the sites timezone
		$date->modify( "+$days_before days" );
		$key = $date->format( 'Y' ) . '-' . $date->format( 'm' );

		if ( isset( $this->data[ $key ] ) ) {
			return;
		}

		$sql = "SELECT token_id,user_id FROM {$wpdb->prefix}woocommerce_payment_tokens as tokens
			LEFT JOIN {$wpdb->payment_tokenmeta} AS m1 ON tokens.token_id = m1.payment_token_id
			LEFT JOIN {$wpdb->payment_tokenmeta} AS m2 ON tokens.token_id = m2.payment_token_id
			WHERE tokens.type = 'CC'
			AND m1.meta_key = 'expiry_year'
			AND m1.meta_value = '{$date->format('Y')}'
			AND m2.meta_key = 'expiry_month'
			AND m2.meta_value = '{$date->format('m')}'
			AND tokens.token !=''
		";

		$this->data[ $key ] = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $this->data[ $key ] ) ) {
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
		$option_key = 'bwf_async_event_' . $this->get_slug() . '_' . time();
		$args       = [ 'key' => $option_key, 'aid' => $automation_id ];
		if ( ! bwf_has_action_scheduled( 'bwfan_subscription_card_expiry_triggered', $args ) ) {
			bwf_schedule_recurring_action( time(), 120, 'bwfan_subscription_card_expiry_triggered', $args );
			update_option( $option_key, $this->data[ $key ], false );
		}

	}


	/**
	 * trigger card expiry
	 */
	public function bwfan_trigger_card_expiry( $option_key, $automation_id ) {
		$card_token = get_option( $option_key, [] );

		if ( empty( $card_token ) ) {
			delete_option( $option_key );
			bwf_unschedule_actions( 'bwfan_subscription_card_expiry_triggered', [ 'key' => $option_key, 'aid' => $automation_id ] );

			return;
		}

		/** Validate automation */
		$automation = BWFAN_Model_Automations::get_automation_with_data( $automation_id );

		if ( isset( $automation['meta'] ) ) {
			$meta = $automation['meta'];
			unset( $automation['meta'] );
			$automation = array_merge( $automation, $meta );
		}

		if ( ! function_exists( 'wcs_user_has_subscription' ) || 1 !== intval( $automation['status'] ) || ( 0 === absint( $automation['start'] ) && 2 === absint( $automation['v'] ) ) ) {
			delete_option( $option_key );
			bwf_unschedule_actions( 'bwfan_subscription_card_expiry_triggered', [ 'key' => $option_key, 'aid' => $automation_id ] );

			return;
		}

		$updated_card_token = $card_token;
		$start_time         = time();

		foreach ( $card_token as $token_key => $token ) {
			if ( ! is_array( $token ) || ! isset( $token['user_id'] ) || $this->did_ran_for_user( $token['user_id'], $automation_id ) ) {
				unset( $updated_card_token[ $token_key ] );
				continue;
			}

			/** If subscription is not active for user */
			if ( ! wcs_user_has_subscription( $token['user_id'], '', 'active' ) ) {
				unset( $updated_card_token[ $token_key ] );
				continue;
			}

			/** Checking 10 seconds of processing */
			if ( ( time() - $start_time ) > 10 ) {
				return;
			}

			$this->process( $token, $automation );
			unset( $updated_card_token[ $token_key ] );
		}
		update_option( $option_key, $updated_card_token, false );
	}

	public function did_ran_for_user( $user_id, $automation_id ) {
		if ( empty( $user_id ) || empty( $automation_id ) ) {
			return false;
		}
		$user_data = get_userdata( $user_id );
		$email     = $user_data instanceof WP_USER ? $user_data->user_email : '';
		/** @var WooFunnels_Contact $bwf_contact */
		$bwf_contact = bwf_get_contact( absint( $user_id ), $email );
		if ( 0 === absint( $bwf_contact->get_id() ) ) {
			return false;
		}
		$contact_id         = absint( $bwf_contact->get_id() );
		$automation_details = BWFAN_Core()->automations->get_automation_data_meta( $contact_id );
		$automation_version = isset( $automation_details['v'] ) ? intval( $automation_details['v'] ) : 1;

		/** If automation is v1 */
		if ( 1 === $automation_version && BWFAN_Common::is_automation_v1_active() ) {
			$run_count = BWFAN_Model_Contact_Automations::get_contact_automations_run_count( $contact_id, $automation_id );
		} else {
			$run_count = BWFAN_PRO_Common::get_v2_automation_contact_run_count( $contact_id, $automation_id );
		}


		return ( $run_count > 0 );
	}

	/**
	 * checking if automation is running or has run in 30 days
	 *
	 * @param $automation_id
	 * @param $user_id
	 *
	 * @return bool
	 */
	public static function may_be_running_v2_automation( $automation_id = '', $user_id = '' ) {
		if ( empty( $automation_id ) || empty( $user_id ) ) {
			return false;
		}

		$contact_id = BWFAN_PRO_Common::get_cid_from_contact( '', $user_id );
		if ( empty( $contact_id ) ) {
			return false;
		}

		global $wpdb;
		$automation_contact_table          = $wpdb->prefix . 'bwfan_automation_contact';
		$automation_contact_complete_table = $wpdb->prefix . 'bwfan_automation_complete_contact';

		$query = $wpdb->prepare( "SELECT count(*) as count FROM {$automation_contact_table} WHERE `cid` = %d AND `aid` = %d", $contact_id, $automation_id ); //phpcs:ignore WordPress.DB.PreparedSQL
		$count = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( absint( $count ) > 0 ) {
			return true;
		}

		$query = $wpdb->prepare( "SELECT count(*) as count FROM {$automation_contact_complete_table} WHERE `cid` = %d AND `aid` = %d AND DATE_FORMAT(c_date, '%%Y-%%m-%%d') >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE_FORMAT(c_date, '%%Y-%%m-%%d') <= CURDATE()", $contact_id, $automation_id ); //phpcs:ignore WordPress.DB.PreparedSQL
		$count = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ( absint( $count ) > 0 );
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
				"min"         => '0',
				'value'       => "15",
				'label'       => 'Days before subscription expiry',
				"description" => ""
			],
			[
				'id'          => 'scheduled-everyday-at',
				'type'        => 'expression',
				'expression'  => " {{hours/}} {{minutes /}}",
				'label'       => 'Schedule this automation to run everyday at',
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
	return 'BWFAN_WCS_Card_Expiry';
}
