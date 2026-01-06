<?php

class BWFAN_LD_Candidate_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_candidate_name';
		$this->tag_description = __( 'Candidate Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_candidate_name', array( $this, 'parse_shortcode' ) );
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

		$email   = BWFAN_Merge_Tag_Loader::get_data( 'email' );
		$user_id = BWFAN_Merge_Tag_Loader::get_data( 'user_id' );

		$user = ( ! empty( $email ) && is_email( $email ) ) ? get_user_by_email( $email ) : get_user_by( 'ID', $user_id );

		$candidate_name = ( ! empty( $user->first_name ) || ! empty( $user->last_name ) ) ? "$user->first_name $user->last_name" : $user->display_name;

		return $this->parse_shortcode_output( $candidate_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Candidate Name';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_user', 'BWFAN_LD_Candidate_Name', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
