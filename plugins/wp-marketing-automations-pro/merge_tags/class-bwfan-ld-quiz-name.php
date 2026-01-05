<?php

class BWFAN_LD_Quiz_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_quiz_name';
		$this->tag_description = __( 'Quiz Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_quiz_name', array( $this, 'parse_shortcode' ) );
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

		$quiz_id   = BWFAN_Merge_Tag_Loader::get_data( 'quiz_id' );
		$quiz_name = ! empty( $quiz_id ) ? get_the_title( $quiz_id ) : '';

		return $this->parse_shortcode_output( $quiz_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Dummy LearnDash Quiz Name';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_quiz', 'BWFAN_LD_Quiz_Name', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
