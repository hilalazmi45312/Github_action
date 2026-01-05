<?php

class BWFAN_LD_Course_Url extends BWFAN_Merge_Tag {

	private static $instance = null;
	protected $support_v2 = true;
	protected $support_v1 = false;

	public function __construct() {
		$this->tag_name        = 'ld_course_url';
		$this->tag_description = __( 'Course Url', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_course_url', array( $this, 'parse_shortcode' ) );
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

		$course_id = BWFAN_Merge_Tag_Loader::get_data( 'course_id' );

		if ( empty( $course_id ) ) {
			$lesson_id = BWFAN_Merge_Tag_Loader::get_data( 'lesson_id' );
			$course_id = ! empty( $lesson_id ) ? get_post_meta( $lesson_id, 'course_id', true ) : 0;
		}

		if ( empty( $course_id ) ) {
			$topic_id  = BWFAN_Merge_Tag_Loader::get_data( 'topic_id' );
			$course_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'course_id', true ) : 0;
		}

		if ( empty( $course_id ) ) {
			$quiz_id   = BWFAN_Merge_Tag_Loader::get_data( 'quiz_id' );
			$course_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'course_id', true ) : 0;
		}

		$course_url = ! empty( $course_id ) ? get_permalink( $course_id ) : '';

		return $this->parse_shortcode_output( $course_url, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Dummy LearnDash Course Url';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_course', 'BWFAN_LD_Course_Url', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
	BWFAN_Merge_Tag_Loader::register( 'learndash_lesson', 'BWFAN_LD_Course_Url', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
	BWFAN_Merge_Tag_Loader::register( 'learndash_topic', 'BWFAN_LD_Course_Url', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
	BWFAN_Merge_Tag_Loader::register( 'learndash_quiz', 'BWFAN_LD_Course_Url', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
