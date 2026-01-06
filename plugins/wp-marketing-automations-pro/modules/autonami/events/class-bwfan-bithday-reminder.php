<?php

#[AllowDynamicProperties]
final class BWFAN_Birthday_Reminder extends BWFAN_Event {
	private static $instance = null;

	public $contact_id = null;

	/** @var WooFunnels_Contact $contact */
	public $contact = null;
	public $birthday = null;
	public $user_id = null;
	public $email = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'wc_customer', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Birthday Reminder', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs every day and checks for contact birth date which are about to come.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_field',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->support_lang           = true;
		$this->priority               = 40;
		$this->is_time_independent    = true;
		$this->v2                     = true;
		$this->support_v1             = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfan_birthday_triggered', array( $this, 'trigger_event' ), 10, 2 );
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
			$where = array(
				'bwfan_automation_id' => $automation_id,
				'meta_key'            => 'last_run',
			);
			$data  = array(
				'meta_value' => $current_day,
			);

			BWFAN_Model_Automationmeta::update( $data, $where );
		} else {
			$meta = array(
				'bwfan_automation_id' => $automation_id,
				'meta_key'            => 'last_run',
				'meta_value'          => $current_day,
			);
			BWFAN_Model_Automationmeta::insert( $meta );
		}

		$field_id = BWFCRM_Fields::get_field_id_by_slug( 'dob' );
		if ( empty( $field_id ) ) {
			return;
		}

		/** Check if active v2 automations found */
		BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );
		if ( ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) ) {
			return;
		}

		$days_before          = isset( $automation_details['meta']['event_meta']['days_before'] ) ? $automation_details['meta']['event_meta']['days_before'] : 0;
		$days_before_birthday = isset( $automation_details['meta']['event_meta']['birthday_option'] ) && 'on_birthday' === $automation_details['meta']['event_meta']['birthday_option'] ? 0 : $days_before;
		$date                 = new DateTime();
		BWFAN_Common::convert_from_gmt( $date );
		$date->modify( "+$days_before_birthday days" );

		$dob = $date->format( 'm-d' );

		$query    = "SELECT `cid`, f{$field_id} as `dob` FROM `{$wpdb->prefix}bwf_contact_fields` WHERE DATE_FORMAT(`f{$field_id}`, '%m-%d') = '$dob'";
		$contacts = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $contacts ) ) {
			return;
		}

		/**
		 * recurring action schedule 2 mins interval
		 * event slug as option key - bwf_async_event_{slug} = birthdays
		 */
		$key  = 'bwf_async_event_' . $this->get_slug() . '_' . $automation_id;
		$args = [ 'key' => $key, 'aid' => $automation_id ];
		if ( ! bwf_has_action_scheduled( 'bwfan_birthday_triggered', $args ) ) {
			bwf_schedule_recurring_action( time(), 120, 'bwfan_birthday_triggered', $args );
			bwf_options_update( $key, $contacts );
		}
	}

	/**
	 * Birthday reminder event callback
	 */
	public function trigger_event( $option_key, $automation_id ) {
		$contacts = bwf_options_get( $option_key );
		if ( empty( $contacts ) ) {
			bwf_options_delete( $option_key );
			bwf_unschedule_actions( 'bwfan_birthday_triggered', [ 'key' => $option_key, 'aid' => $automation_id ] );

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
			bwf_options_delete( $option_key );
			bwf_unschedule_actions( 'bwfan_birthday_triggered', [ 'key' => $option_key, 'aid' => $automation_id ] );

			return;
		}

		$updated_contacts = $contacts;
		$start_time       = time();

		foreach ( $contacts as $index => $contact ) {
			if ( ! is_array( $contact ) || ! isset( $contact['cid'] ) ) {
				continue;
			}

			/** Checking 10 seconds of processing */
			if ( ( time() - $start_time ) > 10 ) {
				return;
			}

			$this->process( $contact, $automation );
			unset( $updated_contacts[ $index ] );
			bwf_options_update( $option_key, $updated_contacts );
		}
	}

	private function process( $contacts, $automation ) {
		$this->birthday   = $contacts['dob'];
		$this->contact_id = $contacts['cid'];

		$contact = new WooFunnels_Contact( '', '', '', $this->contact_id );

		$this->email   = $contact->get_email();
		$this->user_id = $contact->get_wpid();

		return $this->run_v2( $automation, $this->get_slug() );
	}

	public function get_event_data() {
		$data_to_send                         = array();
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['birthday']   = $this->birthday;
		$data_to_send['global']['email']      = $this->email;
		$data_to_send['global']['user_id']    = $this->user_id;

		return $data_to_send;
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->contact_id, 'contact_id' );
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	public function get_user_id_event() {
		return ! empty( intval( $this->user_id ) ) ? intval( $this->user_id ) : false;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'birthday_option',
				'label'       => __( 'Runs', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( "Before contact's Birthday", 'wp-marketing-automations-pro' ),
						'value' => 'before_birthday'
					],
					[
						'label' => __( "On contact's Birthday", 'wp-marketing-automations-pro' ),
						'value' => 'on_birthday'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => true,
				"description" => ""
			],
			[
				'id'          => 'days_before',
				'type'        => 'number',
				'value'       => "15",
				"min"         => '0',
				'label'       => __( 'Days before birthday', 'wp-marketing-automations-pro' ),
				"description" => "",
				'toggler'     => [
					'fields' => [
						[
							'id'    => 'birthday_option',
							'value' => 'before_birthday',
						]
					]
				],
			],
			[
				'id'          => 'scheduled-everyday-at',
				'type'        => 'expression',
				'expression'  => " {{hours/}} {{minutes /}}",
				'label'       => __( 'Schedule this automation to run everyday at', 'wp-marketing-automations-pro' ),
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

	/** Set default values */
	public function get_default_values() {
		return [
			'birthday_option' => 'before_birthday',
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
return 'BWFAN_Birthday_Reminder';
