<?php

final class BWFAN_LD_Reset_Course_Progress extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Reset Course Progress', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action resets the course progress of a user', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'user_id', 'course_id' );
		$this->action_priority = 30;
		$this->support_v2      = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'courses', $data );
		}
	}

	public function get_view_data() {
		if ( ! function_exists( 'learndash_get_courses_count' ) ) {
			return array();
		}

		/** @var WP_Query $courses */
		$courses      = learndash_get_courses_count( array(), '' );
		$course_array = [];
		foreach ( $courses->posts as $course_id ) {
			$course_array[ $course_id ] = get_the_title( $course_id );
		}

		return $course_array;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_course = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'course')) ? data.actionSavedData.data.course : '';
            #>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?>">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Course', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][course]">
                    <option value=""><?php echo esc_html__( 'Choose any Course', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'courses') && _.isObject(data.actionFieldsOptions.courses) ) {
                    _.each( data.actionFieldsOptions.courses, function( value, key ){
                    selected = (key == selected_course) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
                <div class="clearfix bwfan_field_desc bwfan-mb20">Select the course which you want to reset the progress from</div>
            </div>
        </script>
		<?php
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set              = array();
		$data_to_set['user_id']   = $task_meta['global']['user_id'];
		$data_to_set['course_id'] = $task_meta['data']['course'];

		if ( empty( $data_to_set['user_id'] ) ) {
			$email                  = ( isset( $task_meta['global']['email'] ) && is_email( $task_meta['global']['email'] ) ) ? $task_meta['global']['email'] : '';
			$user                   = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$data_to_set['user_id'] = $user instanceof WP_User ? $user->ID : 0;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set              = array();
		$data_to_set['user_id']   = isset( $step_data['user_id'] ) ? $step_data['user_id'] : 0;
		$data_to_set['course_id'] = isset( $step_data['course'][0]['id'] ) ? $step_data['course'][0]['id'] : 0;

		if ( empty( $data_to_set['user_id'] ) ) {
			$email                  = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
			$user                   = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$data_to_set['user_id'] = $user instanceof WP_User ? $user->ID : 0;
		}

		return $data_to_set;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );
		$result = $this->process();

		if ( true === $result ) {
			return array(
				'status'  => 3,
				'message' => 'Course Progress Reset Done'
			);
		}

		return $result;
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$course_id = absint( $this->data['course_id'] );
		if ( empty( $course_id ) ) {
			return array(
				'status'  => 4,
				'message' => __( 'Course was not selected', 'wp-marketing-automations-pro' ),
			);
		}

		$user_id = $this->data['user_id'];
		$user    = get_userdata( $user_id );
		if ( false === $user ) {
			return array(
				'status'  => 4,
				'message' => __( 'User does not exists', 'wp-marketing-automations-pro' ),
			);
		}

		learndash_delete_course_progress( $course_id, $user_id );

		return true;
	}

	public function process_v2() {
		$course_id = absint( $this->data['course_id'] );
		if ( empty( $course_id ) ) {
			return $this->skipped_response( __( 'Course was not selected', 'wp-marketing-automations-pro' ) );
		}

		$user_id = $this->data['user_id'];
		$user    = get_userdata( $user_id );
		if ( false === $user ) {
			return $this->skipped_response( __( 'User does not exists', 'wp-marketing-automations-pro' ) );
		}

		learndash_delete_course_progress( $course_id, $user_id );

		return $this->success_message( __( 'Course reset successfully.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				"id"                  => 'course',
				"label"               => __( 'Select Course', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'ld_courses',
					'slug'      => 'ld_courses',
					'labelText' => 'plan'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Course is required", 'wp-marketing-automations-pro' ),
				"multiple"            => false
			],
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['courses'] ) || empty( $data['courses'] ) ) {
			return '';
		}
		$courses = [];
		foreach ( $data['courses'] as $course ) {
			if ( ! isset( $course['name'] ) || empty( $course['name'] ) ) {
				continue;
			}
			$courses[] = $course['name'];
		}

		return $courses;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_LD_Reset_Course_Progress';