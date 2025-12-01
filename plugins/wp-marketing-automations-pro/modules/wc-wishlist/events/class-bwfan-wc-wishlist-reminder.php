<?php

#[AllowDynamicProperties]
final class BWFAN_WC_Wishlist_Reminder extends BWFAN_Event {
	private static $instance = null;
	public $wishlist = null;
	public $wishlist_id = null;
	public $email = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_wishlist' );

		$this->event_name          = esc_html__( 'Wishlist Reminder', 'wp-marketing-automations-pro' );
		$this->event_desc          = esc_html__( 'This event runs for wishlist reminder.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups   = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label      = esc_html__( 'WooCommerce Wishlist', 'wp-marketing-automations-pro' );
		$this->support_lang        = true;
		$this->priority            = 25.3;
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
		add_action( 'bwfan_wishlist_reminder_triggered', array( $this, 'capture_wishlist' ), 10, 2 );
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
            run_once = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'run_once')) ? 'checked' : '';
            hours_entered = (_.has(data, 'eventSavedData') && _.has(data.eventSavedData, 'hours')) ? data.eventSavedData.hours : 11;
            minutes_entered = (_.has(data, 'eventSavedData') && _.has(data.eventSavedData, 'minutes')) ? data.eventSavedData.minutes :'';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <label class="bwfan-label-title">Run Once a Customer</label>
                <div>
                    <input type="checkbox" name="event_meta[run_once]" id="run-once-customer" value="1" {{run_once}}/>
                    <label for="run-once-customer" class="bwfan-checkbox-label"><?php esc_html_e( 'Enable Run Once a Customer' ) ?></label>
                </div>
            </div>
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
	 * This is a time independent event. A cron is run once a day and it makes all the tasks for the current event.
	 *
	 * @param $automation_id
	 * @param $automation_details
	 *
	 * @throws Exception
	 */
	public function make_task_data( $automation_id, $automation_details ) {

		$run_once    = isset( $automation_details['meta']['event_meta']['run_once'] ) ? $automation_details['meta']['event_meta']['run_once'] : '';
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

		$query = new WP_Query( [
			'post_type'      => 'wishlist',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		] );

		if ( ! is_array( $query->posts ) || count( $query->posts ) === 0 ) {
			return;
		}

		$automation_version = isset( $automation_details['v'] ) ? intval( $automation_details['v'] ) : 1;

		foreach ( $query->posts as $wishlist ) {

			$email      = get_post_meta( $wishlist->ID, '_wishlist_email', true );
			$contact_id = BWFAN_PRO_Common::get_cid_from_contact( $email );

			if ( 1 === $automation_version && BWFAN_Common::is_automation_v1_active() ) {
				$run_count = BWFAN_Model_Contact_Automations::get_contact_automations_run_count( $contact_id, $automation_id );
			} else {
				$run_count = BWFAN_PRO_Common::get_v2_automation_contact_run_count( $contact_id, $automation_id );
			}

			if ( ! empty( $run_once ) && $run_count > 0 ) {
				continue;
			}

			if ( empty( $email ) ) {
				continue;
			}

			$wishlist_items = get_post_meta( $wishlist->ID, '_wishlist_items', true );
			do_action( 'bwfan_wishlist_reminder_triggered', $wishlist->ID, $wishlist_items );
		}
	}

	public function capture_wishlist( $wishlist_id, $wishlist_item ) {
		$this->wishlist_id   = $wishlist_id;
		$this->wishlist      = new WC_Wishlists_Wishlist( $wishlist_id );
		$this->wishlist_item = $wishlist_item;

		$contact_data_v2 = array(
			'wishlist_id'   => $this->wishlist_id,
			'wishlist'      => $this->wishlist,
			'wishlist_item' => $this->wishlist_item,
			'email'         => get_post_meta( $wishlist_id, '_wishlist_email', true ),
			'event'         => $this->get_slug(),
			'version'       => 2
		);

		BWFAN_Common::maybe_run_v2_automations( $this->get_slug(), $contact_data_v2 );
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
		$data_to_send                          = [ 'global' => [] ];
		$data_to_send['global']['wishlist_id'] = $this->wishlist_id;
		$data_to_send['global']['email']       = get_post_meta( $this->wishlist_id, '_wishlist_email', true );
		$data_to_send['global']['user_id']     = is_object( $this->wishlist ) ? $this->get_user_id_event() : '';

		return $data_to_send;
	}

	public function get_user_id_event() {
		return $this->wishlist->get_wishlist_owner();
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
            <strong><?php echo esc_html__( 'Wishlist ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wishlist_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['wishlist_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'wishlist_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['wishlist_id'] ) ) ) {
			$set_data = array(
				'wishlist_id' => intval( $task_meta['global']['wishlist_id'] ),
				'email'       => $task_meta['global']['email'],
				'user_id'     => $task_meta['global']['user_id']
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
		BWFAN_Core()->rules->setRulesData( $this->wishlist_id, 'wishlist_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->get_email_event(), $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_email_event() {
		return get_post_meta( $this->wishlist_id, '_wishlist_email', true );
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'bwfan-run-once-customer',
				'type'        => 'checkbox',
				'label'       => 'Run Once a Customer',
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
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_wc_wishlist_active' ) && bwfan_is_wc_wishlist_active() ) {
	return 'BWFAN_WC_Wishlist_Reminder';
}
