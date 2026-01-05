<?php

#[AllowDynamicProperties]
final class BWFAN_WCM_Membership_Created extends BWFAN_Event {

	private static $instance = null;

	/** @var WC_Memberships_Membership_Plan $membership_plan */
	public $membership_plan = null;
	/** @var WC_Memberships_User_Membership $user_membership */
	public $user_membership = null;

	public $admin_membership = null;

	public function __construct() {
		$this->event_name             = esc_html__( 'Membership Created', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a WooCommerce membership is created.', 'wp-marketing-automations-pro' );
		$this->optgroup_label         = esc_html__( 'Membership', 'wp-marketing-automations-pro' );
		$this->support_lang           = true;
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
		/** allow  */
		add_action( 'wc_memberships_user_membership_created', [ $this, 'membership_created_triggered' ], 20, 2 );
		add_filter( 'bwfan_before_making_logs', array( $this, 'check_if_bulk_process_executing' ), 10, 1 );

		/** for checking if the membership created via admin */
		if ( is_admin() ) {
			add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 50, 3 );
		}
	}

	/** for handling membership created from the admin screen
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param \WP_Post $post
	 */
	function transition_post_status( $new_status, $old_status, $post ) {
		if ( $old_status === 'auto-draft' && $post->post_type === 'wc_user_membership' ) {
			// don't trigger now as post transition happens before data is saved
			$this->admin_membership = $post->ID;
			add_action( 'wc_memberships_user_membership_saved', [ $this, 'membership_created_via_admin' ], 100, 2 );
		}
	}

	/**
	 * @param \WC_Memberships_Membership_Plan $plan
	 * @param $args
	 */
	function membership_created_via_admin( $membership_plan, $user_membership_data ) {
		// check the created membership is a match
		if ( $this->admin_membership == $user_membership_data['user_membership_id'] ) {
			$this->membership_created_triggered( $membership_plan, $user_membership_data );
		}
	}

	public function membership_created_triggered( $membership_plan, $user_membership_data ) {
		/** checked if user id available */
		if ( empty( $user_membership_data['user_id'] ) ) {
			return;
		}

		/** check if membership plan available */
		if ( ! $membership_plan instanceof WC_Memberships_Membership_Plan ) {
			return;
		}

		$membership_plan_id = $membership_plan->get_id();

		/** if the membership update then return */
		if ( false === $user_membership_data['is_update'] || ! empty( $this->admin_membership ) ) {
			$user_membership_id = $user_membership_data['user_membership_id'];
			$this->process( $membership_plan_id, $user_membership_id );
			$this->admin_membership = null;
		}

	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $membership_plan_id
	 * @param $user_membership_id
	 */
	public function process( $membership_plan_id, $user_membership_id ) {
		$data                       = $this->get_default_data();
		$data['membership_plan_id'] = $membership_plan_id;
		$data['user_membership_id'] = $user_membership_id;

		$this->send_async_call( $data );
	}

	public function pre_executable_actions( $automation_data ) {
		if ( $this->user_membership instanceof WC_Memberships_User_Membership ) {
			BWFAN_Core()->rules->setRulesData( $this->user_membership->get_id(), 'wc_user_membership_id' );
			BWFAN_Core()->rules->setRulesData( $this->user_membership->get_user()->get( 'user_email' ), 'email' );
			BWFAN_Core()->rules->setRulesData( $this->user_membership->get_user_id(), 'user_id' );
		}

		if ( $this->membership_plan instanceof WC_Memberships_Membership_Plan ) {
			BWFAN_Core()->rules->setRulesData( $this->membership_plan->get_id(), 'wc_membership_plan_id' );
		}
	}

	/**
	 * Capture the async data for thes if the task has to be executed or not.
	 *
	 * @return array|bool
	 */
	public function capture_async_data() {
		if ( ! function_exists( 'wc_memberships_get_membership_plan' ) || ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return false;
		}

		$membership_plan_id    = BWFAN_Common::$events_async_data['membership_plan_id'];
		$user_membership_id    = BWFAN_Common::$events_async_data['user_membership_id'];
		$membership_plan       = wc_memberships_get_membership_plan( $membership_plan_id );
		$user_membership       = wc_memberships_get_user_membership( $user_membership_id );
		$this->membership_plan = $membership_plan;
		$this->user_membership = $user_membership;

		$this->run_automations();
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
	 * Recalculate action's execution time with respect to order date.
	 * eg.
	 * today is 22 jan.
	 * order was placed on 17 jan.
	 * user set an email to send after 10 days of order placing.
	 * user setup the sync process.
	 * email should be sent on 27 Jan as the order date was 17 jan.
	 *
	 * @param $actions
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function recalculate_actions_time( $actions ) {
		$membership_date = $this->user_membership->get_start_date();
		$membership_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $membership_date );
		$actions         = $this->calculate_actions_time( $actions, $membership_date );

		return $actions;
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
		$data_to_send                                    = [ 'global' => [] ];
		$data_to_send['global']['wc_user_membership_id'] = is_object( $this->user_membership ) ? $this->user_membership->get_id() : '';
		$data_to_send['global']['wc_membership_plan_id'] = is_object( $this->membership_plan ) ? $this->membership_plan->get_id() : '';
		$data_to_send['global']['email']                 = is_object( $this->user_membership ) ? $this->user_membership->get_user()->get( 'user_email' ) : '';
		$data_to_send['global']['user_id']               = is_object( $this->user_membership ) ? $this->user_membership->get_user_id() : '';

		return $data_to_send;
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
				'wc_user_membership_id' => $task_meta['global']['wc_user_membership_id'],
				'wc_membership_plan_id' => $task_meta['global']['wc_membership_plan_id'],
				'email'                 => $task_meta['global']['email'],
				'user_id'               => $task_meta['global']['user_id'],
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
            <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wc_user_membership_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['wc_user_membership_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Selected Membership Plan ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wc_membership_plan_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['wc_membership_plan_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Membership User Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	public function validate_v2_event_settings( $automation_data ) {
		$current_automation_event_meta = $automation_data['event_meta'];
		$current_membership_contains   = isset( $current_automation_event_meta['membership-contains'] ) ? $current_automation_event_meta['membership-contains'] : 'any';

		if ( 'any' === $current_membership_contains ) {
			return true;
		}

		/** Specific membership case */
		$selected_memberships = is_array( $automation_data['event_meta']['memberships'] ) ? array_map( 'intval', array_column( $automation_data['event_meta']['memberships'], 'id' ) ) : [];

		return ! empty( $selected_memberships ) && in_array( intval( $automation_data['membership_plan_id'] ), $selected_memberships, true );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		if ( ! function_exists( 'wc_memberships_get_membership_plan' ) || ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return false;
		}

		$this->membership_plan_id = BWFAN_Common::$events_async_data['membership_plan_id'];
		$this->user_membership_id = BWFAN_Common::$events_async_data['user_membership_id'];
		$this->membership_plan    = wc_memberships_get_membership_plan( $this->membership_plan_id );
		$this->user_membership    = wc_memberships_get_user_membership( $this->user_membership_id );

		$automation_data['membership_plan']       = $this->membership_plan;
		$automation_data['user_membership']       = $this->user_membership;
		$automation_data['wc_user_membership_id'] = $this->user_membership_id;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'membership-contains',
				'label'       => __( 'Membership Contains', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any Membership', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Memberships', 'wp-marketing-automations-pro' ),
						'value' => 'selected_membership'
					]
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => true,
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
			'membership-contains' => 'any',
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() ) {
	return 'BWFAN_WCM_Membership_Created';
}
