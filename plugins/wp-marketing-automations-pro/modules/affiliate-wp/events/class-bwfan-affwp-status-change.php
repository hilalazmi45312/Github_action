<?php

#[AllowDynamicProperties]
final class BWFAN_AFFWP_Status_Change extends BWFAN_Event {
	private static $instance = null;
	public $affiliate_id = null;
	public $from_status = null;
	public $to_status = null;
	public $user_id = null;

	private function __construct() {
		$this->optgroup_label         = esc_html__( 'AffiliateWP', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Affiliate Status Change', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after an affiliate status is changed.', 'wp-marketing-automations-pro' );
		$this->event_merge_tag_groups = array( 'aff_affiliate', 'bwf_contact' );
		$this->event_rule_groups      = array(
			'affiliatewp',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority               = 15.6;
		$this->support_lang           = true;
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'affwp_set_affiliate_status', array( $this, 'process' ), 11, 3 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( false === BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			return;
		}
		$wc_affiliate_statuses = $this->get_view_data();

		BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'from_options', $wc_affiliate_statuses );
		BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'to_options', $wc_affiliate_statuses );
	}

	public function get_view_data() {
		$all_status = affwp_get_affiliate_statuses();

		return $all_status;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <#
            selected_from_status = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'from')) ? data.eventSavedData.from : '';
            selected_to_status = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'to')) ? data.eventSavedData.to : '';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-6 bwfan-pl-0">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'From Status', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[from]">
                    <option value="any"><?php esc_html_e( 'Any', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'from_options') && _.isObject(data.eventFieldsOptions.from_options) ) {
                    _.each( data.eventFieldsOptions.from_options, function( value, key ){
                    selected = (key == selected_from_status) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
            <div class="bwfan-col-sm-6 bwfan-pr-0">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'To Status', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[to]">
                    <option value="any"><?php esc_html_e( 'Any', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'to_options') && _.isObject(data.eventFieldsOptions.to_options) ) {
                    _.each( data.eventFieldsOptions.to_options, function( value, key ){
                    selected = (key == selected_to_status) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
        </script>
		<?php
	}

	public function get_email_event() {
		$user = ! empty( $this->user_id ) ? get_user_by( 'id', absint( $this->user_id ) ) : false;

		return ( $user instanceof WP_User ) ? $user->user_email : false;
	}

	public function get_user_id_event() {
		return ! empty( $this->user_id ) ? absint( $this->user_id ) : false;
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->affiliate_id, 'affiliate_id' );
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
	}

	public function handle_single_automation_run( $value1, $automation_id ) {
		$is_register_task = false;
		$to_status        = $this->to_status;
		$from_status      = $this->from_status;
		$event_meta       = $value1['event_meta'];
		$from             = $event_meta['from'];
		$to               = $event_meta['to'];

		if ( 'any' === $from && 'any' === $to ) {
			$is_register_task = true;
		} elseif ( 'any' === $from && $to_status === $to ) {
			$is_register_task = true;
		} elseif ( $from_status === $from && 'any' === $to ) {
			$is_register_task = true;
		} elseif ( $from_status === $from && $to_status === $to ) {
			$is_register_task = true;
		}

		if ( $is_register_task ) {
			$all_statuses   = affwp_get_affiliate_statuses();
			$value1['from'] = $all_statuses[ $from_status ];
			$value1['to']   = $all_statuses[ $to_status ];

			return parent::handle_single_automation_run( $value1, $automation_id );
		}

		return '';
	}

	/**
	 * Check if the data provided in the Event is valid or not.
	 *
	 * @param $task_details
	 *
	 * @return array|mixed
	 */
	public function validate_event( $task_details ) {
		$result        = [];
		$task_event    = $task_details['event_data']['event_slug'];
		$automation_id = $task_details['processed_data']['automation_id'];

		$automation_details            = BWFAN_Model_Automations::get_automation_with_data( $automation_id );
		$current_automation_event      = $automation_details['event'];
		$current_automation_event_meta = $automation_details['meta']['event_meta'];

		/** Current automation event does not match with the event of task when the task was made */
		if ( $task_event !== $current_automation_event ) {
			$result = $this->get_automation_event_status();

			return $result;
		}

		$current_automation_status_from = $current_automation_event_meta['from'];
		$current_automation_status_to   = $current_automation_event_meta['to'];

		/** Status Any to Any case */
		if ( 'any' === $current_automation_status_from && 'any' === $current_automation_status_to ) {
			$result = $this->get_automation_event_success();

			return $result;
		}

		$affiliate_from_status = strtolower( $task_details['processed_data']['from'] );
		$affiliate_to_status   = strtolower( $task_details['processed_data']['to'] );

		if ( $affiliate_from_status === $current_automation_status_from && $affiliate_to_status === $current_automation_status_to ) {
			$result = $this->get_automation_event_success();

			return $result;
		}

		if ( 'any' === $current_automation_status_from && $affiliate_to_status === $current_automation_status_to ) {
			$result = $this->get_automation_event_success();

			return $result;
		}

		if ( $affiliate_from_status === $current_automation_status_from && 'any' === $current_automation_status_to ) {
			$result = $this->get_automation_event_success();

			return $result;
		}

		$result['status']  = 4;
		$result['message'] = __( 'Affiliate Status not validated', 'wp-marketing-automations-pro' );

		return $result;
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $affiliate_id
	 * @param $from_status
	 * @param $to_status
	 */
	public function process( $affiliate_id, $to_status, $from_status ) {
		$data                 = $this->get_default_data();
		$data['affiliate_id'] = $affiliate_id;
		$data['from_status']  = $from_status;
		$data['to_status']    = $to_status;

		$this->send_async_call( $data );
	}

	/**
	 * Returns the current event settings set in the automation at the time of task creation.
	 *
	 * @param $value
	 *
	 * @return array
	 */
	public function get_automation_event_data( $value ) {
		$event_meta = $value['event_meta'];
		$event_data = [
			'event_source'   => $value['source'],
			'event_slug'     => $value['event'],
			'validate_event' => 1,
			'from_status'    => isset( $event_meta['from'] ) ? $event_meta['from'] : '',
			'to_status'      => isset( $event_meta['to'] ) ? $event_meta['to'] : '',
			'from'           => isset( $value['from'] ) ? $value['from'] : '',
			'to'             => isset( $value['to'] ) ? $value['to'] : '',
		];

		return $event_data;
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $actions : after processing events data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $actions, $event_data ) {
		if ( ! is_array( $actions ) ) {
			return;
		}
		$data_to_send = $this->get_event_data( $event_data );
		$this->create_tasks( $automation_id, $actions, $event_data, $data_to_send );
	}

	public function get_event_data( $event_data = array() ) {
		$data_to_send                           = [ 'global' => [] ];
		$data_to_send['global']['affiliate_id'] = $this->affiliate_id;
		$data_to_send['global']['from']         = isset( $event_data['from_status'] ) ? $event_data['from_status'] : '';
		$data_to_send['global']['to']           = isset( $event_data['to_status'] ) ? $event_data['to_status'] : '';
		$data_to_send['global']['email']        = affwp_get_affiliate_email( $this->affiliate_id );
		$data_to_send['global']['user_id']      = affwp_get_affiliate_user_id( $this->affiliate_id );

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
		$affiliate           = affwp_get_affiliate( $global_data['affiliate_id'] );
		$args                = array(
			'affiliate_id' => $global_data['affiliate_id'],
			'page'         => 'affiliate-wp-affiliates',
			'action'       => 'edit_affiliate'
		);
		$affiliate_edit_link = add_query_arg( $args, admin_url() . 'admin.php' );
		?>
        <li>
            <strong><?php esc_html_e( 'Affiliate ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo $affiliate_edit_link; //phpcs:ignore WordPress.Security.EscapeOutput
			?>"><?php echo '#' . esc_html( $global_data['affiliate_id'] . ' ' . affwp_get_affiliate_name( $affiliate ) ); ?></a>
        </li>
        <li>
            <strong><?php esc_html_e( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php esc_html_e( $global_data['email'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'From Status:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php esc_html_e( $global_data['from'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'To Status:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php esc_html_e( $global_data['to'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_affiliate( $data );
	}

	public function validate_affiliate( $data ) {
		if ( ! isset( $data['affiliate_id'] ) ) {
			return false;
		}

		$affiliate = affwp_get_affiliate( $data['affiliate_id'] );

		if ( $affiliate instanceof \AffWP\Affiliate ) {
			return true;
		}

		return false;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'affiliate_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['affiliate_id'] ) ) ) {
			$set_data = array(
				'affiliate_id' => intval( $task_meta['global']['affiliate_id'] ),
				'user_id'      => intval( $task_meta['global']['user_id'] ),
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$affiliate_id       = BWFAN_Common::$events_async_data['affiliate_id'];
		$from_status        = BWFAN_Common::$events_async_data['from_status'];
		$to_status          = BWFAN_Common::$events_async_data['to_status'];
		$this->affiliate_id = $affiliate_id;
		$this->from_status  = $from_status;
		$this->to_status    = $to_status;
		$this->user_id      = affwp_get_affiliate_user_id( $this->affiliate_id );

		return $this->run_automations();
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		if ( ! isset( $automation_data['event_meta'] ) || empty( $automation_data['event_meta'] ) ) {
			return false;
		}

		$current_automation_status_from = $automation_data['event_meta']['from'];
		$current_automation_status_to   = $automation_data['event_meta']['to'];

		/** Status Any to Any case */
		if ( 'any' === $current_automation_status_from && 'any' === $current_automation_status_to ) {
			return true;
		}

		$affiliate_from_status = strtolower( $automation_data['from_status'] );
		$affiliate_to_status   = strtolower( $automation_data['to_status'] );

		if ( $affiliate_from_status === $current_automation_status_from && $affiliate_to_status === $current_automation_status_to ) {
			return true;
		}

		if ( 'any' === $current_automation_status_from && $affiliate_to_status === $current_automation_status_to ) {
			return true;
		}

		if ( $affiliate_from_status === $current_automation_status_from && 'any' === $current_automation_status_to ) {
			return true;
		}

		return false;
	}

	/**
	 * Capture the async v2 data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$affiliate_id                    = BWFAN_Common::$events_async_data['affiliate_id'];
		$from_status                     = BWFAN_Common::$events_async_data['from_status'];
		$to_status                       = BWFAN_Common::$events_async_data['to_status'];
		$this->affiliate_id              = $affiliate_id;
		$this->from_status               = $from_status;
		$this->to_status                 = $to_status;
		$this->user_id                   = affwp_get_affiliate_user_id( $this->affiliate_id );
		$automation_data['from_status']  = $this->from_status;
		$automation_data['affiliate_id'] = $this->affiliate_id;
		$automation_data['to_status']    = $this->to_status;
		$automation_data['user_id']      = $this->user_id;

		return $automation_data;
	}

	/**
	 * v2 Method: Get field schema
	 * @return array
	 */
	public function get_fields_schema() {
		$status = array_replace( [ 'any' => 'Any' ], $this->get_view_data() );
		$status = BWFAN_PRO_Common::prepared_field_options( $status );

		return [
			[
				'id'          => 'from',
				'type'        => 'wp_select',
				'label'       => __( 'From status', 'wp-marketing-automations-pro' ),
				'options'     => $status,
				'placeholder' => __( "Choose Status", 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
			[
				'id'          => 'to',
				'type'        => 'wp_select',
				'label'       => __( 'To status', 'wp-marketing-automations-pro' ),
				'options'     => $status,
				'placeholder' => __( "Choose Status", 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
		];
	}

	public function get_default_values() {
		return [
			'from' => 'any',
			'to'   => 'any',
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_affiliatewp_active() ) {
	return 'BWFAN_AFFWP_Status_Change';
}
