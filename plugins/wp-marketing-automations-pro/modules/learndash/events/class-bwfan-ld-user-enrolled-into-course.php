<?php

#[AllowDynamicProperties]
final class BWFAN_LD_User_Enrolled_Into_Course extends BWFAN_Event {
	private static $instance = null;
	public $user_id = 0;
	public $course_id = 0;
	public $access_list = 0;
	public $email = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'learndash_course', 'learndash_user', 'learndash_course', 'learndash_group', 'bwf_contact' );
		$this->event_name             = __( 'User Enrolled into a Course', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs when the user is enrolled into a course.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'learndash_course',
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
		$this->priority               = 10;
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
		add_action( 'learndash_update_course_access', [ $this, 'process' ], 10, 4 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $user_id
	 * @param $course_id
	 * @param $access_list
	 * @param $remove
	 */
	public function process( $user_id, $course_id, $access_list, $remove ) {
		if ( false !== $remove ) {
			return;
		}

		$data                = $this->get_default_data();
		$data['user_id']     = $user_id;
		$data['course_id']   = $course_id;
		$data['access_list'] = $access_list ? $access_list : '';

		$user          = get_user_by( 'id', absint( $user_id ) );
		$data['email'] = $user instanceof WP_User && is_email( $user->user_email ) ? $user->user_email : '';

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->course_id, 'course_id' );
		BWFAN_Core()->rules->setRulesData( $this->access_list, 'access_list' );
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
		$data_to_send                          = [ 'global' => [] ];
		$data_to_send['global']['user_id']     = $this->user_id;
		$data_to_send['global']['course_id']   = $this->course_id;
		$data_to_send['global']['access_list'] = $this->access_list;
		$data_to_send['global']['email']       = $this->email;

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
		$user   = get_user_by( 'id', $global_data['user_id'] );
		$course = get_post( $global_data['course_id'] );
		?>
        <li>
            <strong><?php echo esc_html__( 'User:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . '?user-edit.php?user_id=' . $user->ID; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $user->user_nicename ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Course:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . 'post.php?post=' . $course->ID . '&action=edit'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $course->post_title ); ?></a>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'course_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['course_id'] ) ) ) {
			$set_data = array(
				'user_id'     => intval( $task_meta['global']['user_id'] ),
				'course_id'   => intval( $task_meta['global']['course_id'] ),
				'access_list' => $task_meta['global']['access_list'],
				'email'       => $task_meta['global']['email'],
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
		$this->user_id     = BWFAN_Common::$events_async_data['user_id'];
		$this->course_id   = BWFAN_Common::$events_async_data['course_id'];
		$this->access_list = BWFAN_Common::$events_async_data['access_list'];
		$this->email       = BWFAN_Common::$events_async_data['email'];

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
		/** check any course case */
		if ( ! isset( $automation_data['event_meta'] ) || ! isset( $automation_data['event_meta']['courses'] ) || 'any' === $automation_data['event_meta']['courses'] ) {
			return true;
		}

		/** check if specific course selected */
		if ( ! is_array( $automation_data['event_meta']['course_id'] ) || 0 === count( $automation_data['event_meta']['course_id'] ) ) {
			return false;
		}

		$ids = array_column( $automation_data['event_meta']['course_id'], 'id' );

		return in_array( intval( $automation_data['course_id'] ), array_map( 'intval', $ids ), true );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->user_id                  = BWFAN_Common::$events_async_data['user_id'];
		$this->course_id                = BWFAN_Common::$events_async_data['course_id'];
		$this->access_list              = BWFAN_Common::$events_async_data['access_list'];
		$this->email                    = BWFAN_Common::$events_async_data['email'];
		$automation_data['user_id']     = $this->user_id;
		$automation_data['course_id']   = $this->course_id;
		$automation_data['email']       = $this->email;
		$automation_data['access_list'] = $this->access_list;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'courses',
				'label'       => __( 'Courses', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any Course', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Courses', 'wp-marketing-automations-pro' ),
						'value' => 'selected_course'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => false,
				"description" => "",
			],
			[
				"id"                  => 'course_id',
				"label"               => __( 'Select Course', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'ld_courses',
					'slug'      => 'ld_courses',
					'labelText' => 'course'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Course is required", 'wp-marketing-automations-pro' ),
				"multiple"            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'courses',
							'value' => 'selected_course',
						]
					]
				],
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return array( "courses" => "any" );
	}

	/**
	 * store data in normalize table
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
		$db_ins->add_course_enrolled( $post_parameters['user_id'], $post_parameters['course_id'] );
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

		/** fetching last course id */
		$course_id = BWFAN_Learndash_Common::get_last_user_activity_by_type( $contact->get_wpid(), 'access' );

		/** return if no course available as event is enrolled to course */
		if ( empty( $course_id ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'User is not enrolled to any course', 'wp-marketing-automations-pro' ) ];
		}

		/** handling if selected_course is selected */
		if ( isset( $automation_data['event_meta']['courses'] ) && 'selected_course' === $automation_data['event_meta']['courses'] ) {
			$event_course_ids = array_column( $automation_data['event_meta']['course_id'], 'id' );

			if ( ! in_array( $course_id, $event_course_ids ) ) {
				return [ 'status' => 0, 'type' => '', 'message' => __( 'No courses are matching with event courses', 'wp-marketing-automations-pro' ) ];
			}
		}

		$this->email       = $contact->get_email();
		$this->contact_id  = $cid;
		$this->user_id     = $contact->get_wpid();
		$this->course_id   = $course_id;
		$this->access_list = BWFAN_Learndash_Common::get_course_access_lists( $course_id );

		return array_merge( $automation_data, [
			'contact_id'  => $this->contact_id,
			'email'       => $this->email,
			'user_id'     => $this->user_id,
			'course_id'   => $this->course_id,
			'access_list' => $this->access_list
		] );
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_learndash_active() ) {
	return 'BWFAN_LD_User_Enrolled_Into_Course';
}
