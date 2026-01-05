<?php

class BWFAN_LD_Quiz_Points extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_quiz_points';
		$this->tag_description = __( 'Quiz Points', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_quiz_points', array( $this, 'parse_shortcode' ) );
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

		$quiz_data  = BWFAN_Merge_Tag_Loader::get_data( 'quiz_data' );
		$quiz_score = isset( $quiz_data['points'] ) && ! empty( $quiz_data['points'] ) ? absint( $quiz_data['points'] ) : 0;

		return $this->parse_shortcode_output( $quiz_score, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 1;
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_quiz', 'BWFAN_LD_Quiz_Points', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
