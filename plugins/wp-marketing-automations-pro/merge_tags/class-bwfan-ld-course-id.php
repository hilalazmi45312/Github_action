<?php
if ( ! class_exists( 'BWFAN_LD_Enrolled_Course_Ids' ) ) {
	class BWFAN_LD_Enrolled_Course_Ids extends BWFAN_Merge_Tag {

		private static $instance = null;

		public function __construct() {
			$this->tag_name        = 'ld_enrolled_course_ids';
			$this->tag_description = __( 'Enrolled Course IDs', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_ld_enrolled_course_ids', array( $this, 'parse_shortcode' ) );
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Parse the merge tag and return its value.
		 *
		 * @param $attr
		 *
		 * @return mixed|string|void
		 */
		public function parse_shortcode( $attr ) {
			if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
				return $this->get_dummy_preview();
			}
			$user_id      = BWFAN_Merge_Tag_Loader::get_data( 'user_id' );
			$user_courses = array();
			if ( ! empty( $user_id ) ) {
				$user_courses = ld_get_mycourses( $user_id );
			}
			if ( empty( $user_courses ) ) {
				$lesson_id    = BWFAN_Merge_Tag_Loader::get_data( 'lesson_id' );
				$user_courses = learndash_get_course_id( $lesson_id );
			}
			if ( empty( $user_courses ) ) {
				$topic_id     = BWFAN_Merge_Tag_Loader::get_data( 'topic' );
				$user_courses = learndash_get_course_id( $topic_id );
			}
			if ( empty( $user_courses ) ) {
				$quiz_id      = BWFAN_Merge_Tag_Loader::get_data( 'quiz_id' );
				$user_courses = learndash_get_course_id( $quiz_id );
			}
			if ( empty( $user_courses ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}
			$course_id_separator = apply_filters( 'bwfan_course_id_separator', ', ' );
			$course_ids          = implode( $course_id_separator, $user_courses );

			return $this->parse_shortcode_output( $course_ids, $attr );
		}

		/**
		 * Show dummy value of the current merge tag.
		 *
		 * @return string
		 *
		 * @todo:Hard values shouldn't be passed
		 */
		public function get_dummy_preview() {
			return '12 , 123 , 124';
		}


	}

	/**
	 * Register this merge tag to a group.
	 */
	if ( bwfan_is_learndash_active() ) {
		BWFAN_Merge_Tag_Loader::register( 'learndash_course', 'BWFAN_LD_Enrolled_Course_Ids', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
	}
}
