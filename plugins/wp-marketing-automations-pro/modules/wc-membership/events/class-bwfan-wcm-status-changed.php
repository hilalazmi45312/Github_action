<?php

#[AllowDynamicProperties]
final class BWFAN_WCM_Status_Changed extends BWFAN_Event {

	private static $instance = null;
	/** @var WC_Memberships_User_Membership $user_membership */
	public $user_membership = null;
	/** @var string $user_membership */
	public $to_status = null;
	public $from_status = null;
	public $plan_id = null;

	public function __construct() {
		$this->event_name             = esc_html__( 'Membership Status Changed', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a WooCommerce membership status is changed.', 'wp-marketing-automations-pro' );
		$this->optgroup_label         = esc_html__( 'Membership', 'wp-marketing-automations-pro' );
		$this->priority               = 25;
		$this->event_merge_tag_groups = [ 'wc_membership', 'bwf_contact' ];
		$this->event_rule_groups      = array(
			'wc_member',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wc_memberships_user_membership_status_changed', [ $this, 'membership_status_changed_triggered' ], 20, 3 );
	}

	public function get_view_data() {
		$statuses = wc_memberships_get_user_membership_statuses( false, false );

		$statuses_to_return = array_map( function ( $status ) {
			return [ $status => wc_memberships_get_user_membership_status_name( $status ) ];
		}, $statuses );

		return array_merge( ...$statuses_to_return );
	}

	/**
	 * Membership status changed triggered
	 *
	 * @param WC_Memberships_User_Membership $user_membership
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function membership_status_changed_triggered( $user_membership, $old_status, $new_status ) {
		if ( ! $user_membership instanceof WC_Memberships_User_Membership ) {
			return;
		}

		$plan_id       = $user_membership->plan_id;
		$membership_id = $user_membership->get_id();
		$to            = $new_status;
		$from_status   = $old_status;

		$this->process( $plan_id, $membership_id, $to, $from_status );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $plan_id
	 * @param int $membership_id
	 * @param $to_status
	 * @param $from_status
	 */
	public function process( $plan_id, $membership_id, $to_status, $from_status ) {
		$data                       = $this->get_default_data();
		$data['plan_id']            = $plan_id;
		$data['user_membership_id'] = $membership_id;
		$data['to_status']          = $to_status;
		$data['from_status']        = $from_status;

		$this->send_async_call( $data );
	}

	/**
	 * Capture the async data for these if the task has to be executed or not.
	 *
	 * @return array|bool
	 */
	public function capture_async_data() {
		if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return false;
		}
		$plan_id         = BWFAN_Common::$events_async_data['plan_id'];
		$membership_id   = BWFAN_Common::$events_async_data['user_membership_id'];
		$to_status       = BWFAN_Common::$events_async_data['to_status'];
		$from_status     = BWFAN_Common::$events_async_data['from_status'];
		$user_membership = wc_memberships_get_user_membership( $membership_id );

		$this->plan_id         = $plan_id;
		$this->user_membership = $user_membership;
		$this->to_status       = $to_status;
		$this->from_status     = $from_status;

		return $this->run_automations();
	}

	public function get_email_event() {
		if ( $this->user_membership instanceof WC_Memberships_User_Membership ) {
			$user = $this->user_membership->get_user();

			return ! empty( $user ) ? $user->user_email : false;
		}

		return false;
	}

	public function get_user_id_event() {
		return $this->user_membership instanceof WC_Memberships_User_Membership ? $this->user_membership->get_user_id() : false;
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
		$data_to_send = [ 'global' => [] ];

		$data_to_send['global']['wc_user_membership_id']     = $this->user_membership instanceof WC_Memberships_User_Membership ? $this->user_membership->get_id() : '';
		$data_to_send['global']['email']                     = $this->user_membership instanceof WC_Memberships_User_Membership ? $this->user_membership->get_user()->get( 'user_email' ) : '';
		$data_to_send['global']['user_id']                   = $this->user_membership instanceof WC_Memberships_User_Membership ? $this->user_membership->get_user_id() : '';
		$data_to_send['global']['wc_user_membership_status'] = ! empty( $this->status ) ? $this->status : '';

		return $data_to_send;
	}

	public function pre_executable_actions( $automation_data ) {
		if ( function_exists( 'wc_memberships_get_user_membership' ) && $this->user_membership instanceof WC_Memberships_User_Membership ) {
			BWFAN_Core()->rules->setRulesData( $this->user_membership->get_id(), 'wc_user_membership_id' );
			BWFAN_Core()->rules->setRulesData( $this->user_membership->get_user()->get( 'user_email' ), 'email' );
			BWFAN_Core()->rules->setRulesData( $this->user_membership->get_user_id(), 'user_id' );
		}

		if ( ! empty( $this->status ) ) {
			BWFAN_Core()->rules->setRulesData( $this->status, 'wc_user_membership_status' );
		}
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'wc_user_membership_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['wc_user_membership_id'] ) ) && function_exists( 'wc_memberships_get_user_membership' ) ) {
			$set_data = array(
				'wc_user_membership_id'     => $task_meta['global']['wc_user_membership_id'],
				'email'                     => $task_meta['global']['email'],
				'user_id'                   => $task_meta['global']['user_id'],
				'wc_user_membership_status' => $task_meta['global']['wc_user_membership_status'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
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
            <strong><?php echo esc_html__( 'Membership ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank"
               href="<?php echo get_edit_post_link( $global_data['wc_user_membership_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput
			   ?>"><?php echo esc_html__( '#' . $global_data['wc_user_membership_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'New Status:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['wc_user_membership_status'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Membership User Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate v2 event settings
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		$current_automation_event_meta = $automation_data['event_meta'];
		$current_status_to             = isset( $current_automation_event_meta['to'] ) ? $current_automation_event_meta['to'] : 'any';
		$current_status_from           = isset( $current_automation_event_meta['from'] ) ? $current_automation_event_meta['from'] : 'any';
		$current_membership_contains   = isset( $current_automation_event_meta['membership-contains'] ) ? $current_automation_event_meta['membership-contains'] : 'any';

		/** from any to any status any membership */
		if ( ( 'any' === $current_status_from && 'any' === $current_status_to ) && ( empty( $current_membership_contains ) || 'any' === $current_membership_contains ) ) {
			return true;
		}

		/** Specific membership case */
		if ( 'selected_membership' === $current_membership_contains ) {
			$get_selected_memberships = $automation_data['event_meta']['memberships'];
			$memberships_selected     = array_column( $get_selected_memberships, 'id' );
			if ( empty( $memberships_selected ) || ! in_array( $automation_data['plan_id'], $memberships_selected, true ) ) {
				return false;
			}
		}

		/** if from status is any */
		if ( 'any' === $current_status_from ) {
			return ( 'any' === $current_status_to || ( $automation_data['to_status'] === $current_status_to ) );
		}

		/** if to status is any */
		if ( 'any' === $current_status_to ) {
			return ( $automation_data['from_status'] === $current_status_from );
		}

		/** selected from status to selected to status */
		return ( ( $automation_data['from_status'] === $current_status_from ) && ( $automation_data['to_status'] === $current_status_to ) );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return false;
		}
		$this->user_membership_id = BWFAN_Common::$events_async_data['user_membership_id'];
		$to_status                = BWFAN_Common::$events_async_data['to_status'];
		$from_status              = BWFAN_Common::$events_async_data['from_status'];
		$user_membership          = wc_memberships_get_user_membership( $this->user_membership_id );
		$this->user_membership    = $user_membership;
		$this->to_status          = $to_status;
		$this->from_status        = $from_status;

		$automation_data['user_membership']       = $this->user_membership;
		$automation_data['to_status']             = $this->to_status;
		$automation_data['from_status']           = $this->from_status;
		$automation_data['wc_user_membership_id'] = $this->user_membership_id;

		return $automation_data;
	}

	public function get_fields_schema() {
		$default = [
			'any' => 'Any'
		];
		$status  = array_replace( $default, $this->get_view_data() );
		$status  = BWFAN_Common::prepared_field_options( $status );

		return [
			[
				'id'          => 'from',
				'type'        => 'wp_select',
				'options'     => $status,
				'placeholder' => __( 'Select Status', 'wp-marketing-automations-pro' ),
				'label'       => __( 'From Status', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwf-3-col-item',
				"required"    => false,
				"description" => ""
			],
			[
				'id'          => 'to',
				'type'        => 'wp_select',
				'options'     => $status,
				'label'       => __( 'To Status', 'wp-marketing-automations-pro' ),
				'placeholder' => __( 'Select Status', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwf-3-col-item',
				"required"    => false,
				"description" => ""
			],
			[
				'id'          => 'membership-contains',
				'label'       => __( 'Memberships Contains', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Specific Memberships', 'wp-marketing-automations-pro' ),
						'value' => 'selected_membership'
					],
					[
						'label' => __( 'Any Memberships', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => false,
				"description" => ""
			],
			[
				'id'                  => 'memberships',
				'label'               => __( 'Select Memberships', 'wp-marketing-automations-pro' ),
				'type'                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wcm_plans',
					'slug'      => 'wcm_plans',
					'labelText' => 'wcm_plans'
				],
				'class'               => '',
				'placeholder'         => '',
				'required'            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'membership-contains',
							'value' => 'selected_membership',
						]
					]
				],
			],
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'from'                => 'any',
			'to'                  => 'any',
			'membership-contains' => 'selected_membership',
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */

if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() ) {
	return 'BWFAN_WCM_Status_Changed';
}
