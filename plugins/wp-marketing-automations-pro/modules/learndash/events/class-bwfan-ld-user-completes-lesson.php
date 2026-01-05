<?php

#[AllowDynamicProperties]
final class BWFAN_LD_User_Completes_Lesson extends BWFAN_Event {
	private static $instance = null;
	/** @var WP_User $user */
	public $user = null;
	/** @var WP_Post $course */
	public $course = null;
	/** @var WP_Post $lesson */
	public $lesson = null;
	public $progress = null;
	public $email = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'learndash_course', 'learndash_user', 'learndash_lesson', 'bwf_contact' );
		$this->event_name             = __( 'User Completes a Lesson', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs when the user completes a lesson.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'learndash_lesson',
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
		$this->priority               = 40;
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
		add_action( 'learndash_lesson_completed', [ $this, 'process' ] );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $lesson : is an array with 4 keys
	 *                 1) user: user object
	 *                 2) course: course object
	 *                 3) lesson: lesson object
	 *                 4) progress: course progress array
	 */
	public function process( $lesson ) {
		$data              = $this->get_default_data();
		$data['user_id']   = $lesson['user']->ID;
		$data['course_id'] = $lesson['course']->ID;
		$data['lesson_id'] = $lesson['lesson']->ID;
		$data['progress']  = $lesson['progress'];

		$user          = get_user_by( 'id', absint( $lesson['user']->ID ) );
		$data['email'] = $user instanceof WP_User && is_email( $user->user_email ) ? $user->user_email : '';

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user->ID, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->user, 'user' );
		BWFAN_Core()->rules->setRulesData( $this->course, 'course' );
		BWFAN_Core()->rules->setRulesData( $this->course->ID, 'course_id' );
		BWFAN_Core()->rules->setRulesData( $this->lesson, 'lesson' );
		BWFAN_Core()->rules->setRulesData( $this->lesson->ID, 'lesson_id' );
		BWFAN_Core()->rules->setRulesData( $this->progress, 'progress' );
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
		$data_to_send                        = [ 'global' => [] ];
		$data_to_send['global']['user_id']   = $this->user->ID;
		$data_to_send['global']['user']      = $this->user;
		$data_to_send['global']['course_id'] = $this->course->ID;
		$data_to_send['global']['course']    = $this->course;
		$data_to_send['global']['lesson_id'] = $this->lesson->ID;
		$data_to_send['global']['lesson']    = $this->lesson;
		$data_to_send['global']['progress']  = $this->progress;
		$data_to_send['global']['email']     = $this->email;

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
		$user   = get_user_by( 'ID', $global_data['user_id'] );
		$course = get_post( $global_data['course_id'] );
		$lesson = get_post( $global_data['lesson_id'] );
		ob_start();
		?>
        <li>
            <strong><?php echo esc_html__( 'User:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . '?user-edit.php?user_id=' . $user->ID; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $user->user_nicename ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Course:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . 'post.php?post=' . $course->ID . '&action=edit'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $course->post_title ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Lesson:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . 'post.php?post=' . $lesson->ID . '&action=edit'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $lesson->post_title ); ?></a>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'lesson' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['lesson'] ) ) ) {
			$set_data = array(
				'user_id'   => $task_meta['global']['user_id'],
				'email'     => $task_meta['global']['email'],
				'user'      => $task_meta['global']['user'],
				'course'    => $task_meta['global']['course'],
				'course_id' => $task_meta['global']['course_id'],
				'lesson'    => $task_meta['global']['lesson'],
				'lesson_id' => $task_meta['global']['lesson_id'],
				'progress'  => $task_meta['global']['progress'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	public function get_email_event() {
		return $this->email;
	}

	public function get_user_id_event() {
		return $this->user->ID;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->user     = get_user_by( 'ID', BWFAN_Common::$events_async_data['user_id'] );
		$this->course   = get_post( BWFAN_Common::$events_async_data['course_id'] );
		$this->lesson   = get_post( BWFAN_Common::$events_async_data['lesson_id'] );
		$this->progress = BWFAN_Common::$events_async_data['progress'];
		$this->email    = BWFAN_Common::$events_async_data['email'];

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
		/** check any lesson case */
		if ( ! isset( $automation_data['event_meta'] ) || ! isset( $automation_data['event_meta']['lessons'] ) || 'any' === $automation_data['event_meta']['lessons'] ) {
			return true;
		}

		/** check if specific any selected */
		if ( ! is_array( $automation_data['event_meta']['lesson_id'] ) || 0 === count( $automation_data['event_meta']['lesson_id'] ) ) {
			return false;
		}
		$ids = array_column( $automation_data['event_meta']['lesson_id'], 'id' );

		return in_array( intval( $automation_data['lesson_id'] ), array_map( 'intval', $ids ), true );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->user     = get_user_by( 'ID', BWFAN_Common::$events_async_data['user_id'] );
		$this->course   = get_post( BWFAN_Common::$events_async_data['course_id'] );
		$this->lesson   = get_post( BWFAN_Common::$events_async_data['lesson_id'] );
		$this->progress = BWFAN_Common::$events_async_data['progress'];
		$this->email    = BWFAN_Common::$events_async_data['email'];

		$automation_data['user']     = $this->user;
		$automation_data['course']   = $this->course;
		$automation_data['lesson']   = $this->lesson;
		$automation_data['progress'] = $this->progress;
		$automation_data['email']    = $this->email;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'lessons',
				'label'       => __( 'Lessons', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any Lesson', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Lessons', 'wp-marketing-automations-pro' ),
						'value' => 'selected_lesson'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => false,
				"description" => "",
			],
			[
				"id"                  => 'lesson_id',
				"label"               => __( 'Select Lesson', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'ld_lessons',
					'slug'      => 'ld_lessons',
					'labelText' => 'lesson'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Lesson is required", 'wp-marketing-automations-pro' ),
				"multiple"            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'lessons',
							'value' => 'selected_lesson',
						]
					]
				],
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return array( "lessons" => "any" );
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
		$db_ins->add_lesson_completed( $post_parameters['user_id'], $post_parameters['lesson_id'] );
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

		/** fetching last lesson id */
		$lesson_id = BWFAN_Learndash_Common::get_last_user_activity_by_type( $contact->get_wpid(), 'lesson' );

		/** return if no lesson available as event is completed a lesson */
		if ( empty( $lesson_id ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'User has not completed any lesson', 'wp-marketing-automations-pro' ) ];
		}

		/** handling if selected_lesson is selected */
		if ( isset( $automation_data['event_meta']['lessons'] ) && 'selected_lesson' === $automation_data['event_meta']['lessons'] ) {
			$event_lesson_ids = array_column( $automation_data['event_meta']['lesson_id'], 'id' );

			if ( ! in_array( $lesson_id, $event_lesson_ids ) ) {
				return [ 'status' => 0, 'type' => '', 'message' => __( 'No lessons are matching with event lessons', 'wp-marketing-automations-pro' ) ];
			}
		}

		$this->email      = $contact->get_email();
		$this->contact_id = $cid;
		$this->user_id    = $contact->get_wpid();
		$this->user       = get_user_by( 'ID', $contact->get_wpid() );
		$this->lesson_id  = $lesson_id;
		$this->lesson     = get_post( $lesson_id );
		$this->course_id  = learndash_get_course_id( $lesson_id );
		$this->course     = get_post( $this->course_id );
		$course_progress  = learndash_user_get_course_progress( $contact->get_wpid(), $this->course_id, 'legacy' );
		$this->progress   = array( $this->course_id => $course_progress );

		return array_merge( $automation_data, [
			'contact_id' => $this->contact_id,
			'email'      => $this->email,
			'user_id'    => $this->user_id,
			'user'       => $this->user,
			'lesson_id'  => $this->lesson_id,
			'lesson'     => $this->lesson,
			'course_id'  => $this->course_id,
			'course'     => $this->course,
			'progress'   => $this->progress
		] );
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_learndash_active() ) {
	return 'BWFAN_LD_User_Completes_Lesson';
}
