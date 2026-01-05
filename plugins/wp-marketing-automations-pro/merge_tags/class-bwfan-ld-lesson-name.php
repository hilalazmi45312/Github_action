<?php

class BWFAN_LD_Lesson_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_lesson_name';
		$this->tag_description = __( 'Lesson Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_lesson_name', array( $this, 'parse_shortcode' ) );
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

		$lesson_id = BWFAN_Merge_Tag_Loader::get_data( 'lesson_id' );

		if ( empty( $lesson_id ) ) {
			$topic_id  = BWFAN_Merge_Tag_Loader::get_data( 'topic_id' );
			$lesson_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'lesson_id', true ) : 0;
		}

		if ( empty( $lesson_id ) ) {
			$quiz_id   = BWFAN_Merge_Tag_Loader::get_data( 'quiz_id' );
			$topic_id  = ! empty( $quiz_id ) ? learndash_get_lesson_id( $quiz_id ) : 0;
			$lesson_id = ! empty( $topic_id ) ? learndash_get_lesson_id( $topic_id ) : 0;
		}

		$lesson_name = ! empty( $lesson_id ) ? get_the_title( $lesson_id ) : '';

		return $this->parse_shortcode_output( $lesson_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Dummy LearnDash Lesson Name';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_lesson', 'BWFAN_LD_Lesson_Name', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
	BWFAN_Merge_Tag_Loader::register( 'learndash_topic', 'BWFAN_LD_Lesson_Name', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
	BWFAN_Merge_Tag_Loader::register( 'learndash_quiz', 'BWFAN_LD_Lesson_Name', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
