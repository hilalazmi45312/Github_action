<?php

final class BWFAN_LD_Enroll_User_Into_Course extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Enroll User into Course(s)', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action enrolls a user into a selected courses', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'user_id', 'courses' );
		$this->action_priority = 5;
		$this->support_v2      = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script>
            jQuery(document).ready(function ($) {
                $('body').on('select2:select', '.bwfan-course-search', function (e) {
                    var temp_courses = {};
                    var course_data = $(this).select2('data');
                    course_data.forEach(function (item) {
                        temp_courses[item.id] = item.text;
                    });
                    $(this).parent().find('.bwfan_searched_course_name').val(JSON.stringify(temp_courses));
                });
            });
        </script>
        <script type="text/html" id="tmpl-action-<?php esc_attr_e( $this->get_slug() ); ?>">
            <#
            searched_courses = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'searched_courses')) ? data.actionSavedData.data.searched_courses : '';
            selected_courses = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'courses')) ? data.actionSavedData.data.courses : {};
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Courses', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" data-search="sfwd-courses" data-search-text="<?php esc_attr_e( 'Select Course', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-course-search bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][courses][]" multiple>
                    <#
                    if(_.size(searched_courses) >0) {
                    temp_selected_course = JSON.parse(searched_courses);
                    _.each( selected_courses, function( v ){
                    #>
                    <option value="{{v}}" selected>{{temp_selected_course[v]}}</option>
                    <#
                    })
                    }
                    #>
                </select>
                <input type="hidden" class="bwfan_searched_course_name" name="bwfan[{{data.action_id}}][data][searched_courses]" value="{{searched_courses}}"/>
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
		$data_to_set = array(
			'courses' => $task_meta['data']['courses'],
			'user_id' => absint( $task_meta['global']['user_id'] ),
		);

		if ( empty( $data_to_set['user_id'] ) ) {
			$email                  = ( isset( $task_meta['global']['email'] ) && is_email( $task_meta['global']['email'] ) ) ? $task_meta['global']['email'] : '';
			$user                   = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$data_to_set['user_id'] = $user instanceof WP_User ? $user->ID : 0;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set            = array();
		$data_to_set['user_id'] = isset( $step_data['user_id'] ) ? $step_data['user_id'] : 0;
		$data_to_set['courses'] = isset( $step_data['courses'] ) && is_array( $step_data['courses'] ) ? array_column( $step_data['courses'], 'id' ) : [];
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
				'status' => 3,
			);
		}

		return array(
			'status'  => 4,
			'message' => ( is_array( $result ) && isset( $result['bwfan_response'] ) ) ? $result['bwfan_response'] : __( 'User could not be enrolled in the course.', 'wp-marketing-automations-pro' ),
		);
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

		foreach ( $this->data['courses'] as $course_id ) {
			ld_update_course_access( $this->data['user_id'], $course_id );
		}

		return true;
	}

	public function process_v2() {
		if ( empty( $this->data['courses'] ) && ! is_array( $this->data['courses'] ) ) {
			return $this->skipped_response( __( 'Courses not selected', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $this->data['courses'] as $course_id ) {
			ld_update_course_access( $this->data['user_id'], $course_id );
		}

		return $this->success_message( 'User added in Courses.' );
	}

	public function get_fields_schema() {
		return [
			[
				"id"                  => 'courses',
				"label"               => __( 'Select Courses', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'ld_courses',
					'slug'      => 'ld_courses',
					'labelText' => 'plan'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Course is required", 'wp-marketing-automations-pro' ),
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
return 'BWFAN_LD_Enroll_User_Into_Course';
