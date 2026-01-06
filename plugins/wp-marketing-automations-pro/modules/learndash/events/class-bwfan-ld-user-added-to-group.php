<?php

#[AllowDynamicProperties]
final class BWFAN_LD_User_Added_To_Group extends BWFAN_Event {
	private static $instance = null;
	public $user_id = 0;
	public $group_id = 0;
	public $email = '';
	public $group_categories = [];

	private function __construct() {
		$this->event_merge_tag_groups = array( 'learndash_course', 'learndash_user', 'learndash_group', 'bwf_contact' );
		$this->event_name             = __( 'User Added to Group', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs when the user is added to a group.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'learndash_group',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = __( 'LearnDash', 'wp-marketing-automations-pro' );
		$this->priority               = 80;
		$this->v2                     = true;
		$this->automation_add         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'ld_added_group_access', [ $this, 'process' ], 10, 2 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $user_id
	 * @param $group_id
	 *
	 * @return void
	 */
	public function process( $user_id, $group_id ) {
		$data             = $this->get_default_data();
		$data['user_id']  = $user_id;
		$data['group_id'] = $group_id;
		$user             = get_user_by( 'id', absint( $user_id ) );
		$data['email']    = $user instanceof WP_User && is_email( $user->user_email ) ? $user->user_email : '';

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->group_id, 'group_id' );
		BWFAN_Core()->rules->setRulesData( $this->group_categories, 'group_categories' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
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
		$data_to_send                               = [ 'global' => [] ];
		$data_to_send['global']['user_id']          = $this->user_id;
		$data_to_send['global']['group_id']         = $this->group_id;
		$data_to_send['global']['email']            = $this->email;
		$data_to_send['global']['group_categories'] = $this->group_categories;

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
		$user  = get_user_by( 'id', $global_data['user_id'] );
		$group = get_post( $global_data['group_id'] );
		?>
        <li>
            <strong><?php echo esc_html__( 'User:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . '?user-edit.php?user_id=' . $user->ID; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $user->user_nicename ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Assigned to Group:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . 'post.php?post=' . $group->ID . '&action=edit'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $group->post_title ); ?></a>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'group_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['group_id'] ) ) ) {
			$set_data = array(
				'user_id'  => intval( $task_meta['global']['user_id'] ),
				'group_id' => intval( $task_meta['global']['group_id'] ),
				'email'    => $task_meta['global']['email'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	public function get_email_event() {
		return $this->email;
	}

	public function get_user_id_event() {
		return $this->user_id;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->user_id    = BWFAN_Common::$events_async_data['user_id'];
		$this->group_id   = BWFAN_Common::$events_async_data['group_id'];
		$group_categories = get_the_terms( $this->group_id, 'ld_group_category' );
		$categories       = [];
		if ( is_array( $group_categories ) && count( $group_categories ) > 0 ) {
			foreach ( $group_categories as $group_category ) {
				$categories[] = $group_category->term_id;
			}
		}
		$this->group_categories = $categories;
		$this->email            = BWFAN_Common::$events_async_data['email'];

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
		/** check any group case */
		if ( ! isset( $automation_data['event_meta'] ) || ! isset( $automation_data['event_meta']['groups'] ) || 'any' === $automation_data['event_meta']['groups'] ) {
			return true;
		}


		/** check if specific group selected */
		if ( ! is_array( $automation_data['event_meta']['group_id'] ) || 0 === count( $automation_data['event_meta']['group_id'] ) ) {
			return false;
		}
		$ids = array_column( $automation_data['event_meta']['group_id'], 'id' );

		return in_array( intval( $automation_data['group_id'] ), array_map( 'intval', $ids ), true );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->user_id    = BWFAN_Common::$events_async_data['user_id'];
		$this->group_id   = BWFAN_Common::$events_async_data['group_id'];
		$group_categories = get_the_terms( $this->group_id, 'ld_group_category' );
		$categories       = [];
		if ( is_array( $group_categories ) && count( $group_categories ) > 0 ) {
			foreach ( $group_categories as $group_category ) {
				$categories[] = $group_category->term_id;
			}
		}
		$this->group_categories              = $categories;
		$this->email                         = BWFAN_Common::$events_async_data['email'];
		$automation_data['user_id']          = $this->user_id;
		$automation_data['group_id']         = $this->group_id;
		$automation_data['group_categories'] = $this->group_categories;
		$automation_data['email']            = $this->email;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'groups',
				'label'       => __( 'Groups', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any Group', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Groups', 'wp-marketing-automations-pro' ),
						'value' => 'selected_group'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => false,
				"description" => "",
			],
			[
				"id"                  => 'group_id',
				"label"               => __( 'Select Groups', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'ld_groups',
					'slug'      => 'ld_groups',
					'labelText' => 'group'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Group is required", 'wp-marketing-automations-pro' ),
				"multiple"            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'groups',
							'value' => 'selected_group',
						]
					]
				],
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return array( "groups" => "any" );
	}

	/**
	 * store data in normalize table or not
	 *
	 * @return bool
	 */
	public function is_db_normalize() {
		return true;
	}

	/**
	 * store data in normalized table
	 *
	 * @return void
	 */
	public function execute_normalization( $post_parameters ) {
		if ( ! class_exists( 'BWFCRM_DB_Normalization_Learndash' ) ) {
			return;
		}
		$db_ins = BWFCRM_DB_Normalization_Learndash::get_instance();
		$db_ins->add_group_access( $post_parameters['user_id'], $post_parameters['group_id'] );
	}

	/**
	 * get contact automation data
	 *
	 * @param $automation_data
	 * @param $cid
	 *
	 * @return array|null[]
	 */
	public function get_manually_added_contact_automation_data( $automation_data, $cid ) {
		$contact = new WooFunnels_Contact( '', '', '', $cid );

		/** Check if contact exists */
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return [ 'status' => 0, 'type' => 'contact_not_found' ];
		}

		if ( 0 === intval( $contact->get_wpid() ) ) {
			return [ 'status' => 0, 'type' => 'user_not_found' ];
		}

		$group_id = BWFAN_Learndash_Common::get_last_user_activity_by_type( $contact->get_wpid(), 'group_progress' );

		/** return if no group available as event is added to group */
		if ( empty( $group_id ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'User is not added to any group', 'wp-marketing-automations-pro' ) ];
		}

		/** handling if select_group is selected */
		if ( isset( $automation_data['event_meta']['groups'] ) && 'selected_group' === $automation_data['event_meta']['groups'] ) {
			$event_group_ids = array_column( $automation_data['event_meta']['group_id'], 'id' );

			if ( ! in_array( $group_id, $event_group_ids ) ) {
				return [ 'status' => 0, 'type' => '', 'message' => __( 'No groups are matching with event groups', 'wp-marketing-automations-pro' ) ];
			}
		}

		$this->email      = $contact->get_email();
		$this->contact_id = $cid;
		$this->user_id    = $contact->get_wpid();
		$this->group_id   = $group_id;
		$group_categories = get_the_terms( $this->group_id, 'ld_group_category' );
		$categories       = [];
		if ( is_array( $group_categories ) && count( $group_categories ) > 0 ) {
			foreach ( $group_categories as $group_category ) {
				$categories[] = $group_category->term_id;
			}
		}

		$this->group_categories = $categories;

		return array_merge( $automation_data, [
			'contact_id'       => $this->contact_id,
			'email'            => $this->email,
			'user_id'          => $this->user_id,
			'group_id'         => $this->group_id,
			'group_categories' => $this->group_categories
		] );
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_learndash_active() ) {
	return 'BWFAN_LD_User_Added_To_Group';
}
