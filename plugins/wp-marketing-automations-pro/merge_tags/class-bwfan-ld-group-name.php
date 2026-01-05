<?php

class BWFAN_LD_Group_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_group_name';
		$this->tag_description = __( 'Group Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_group_name', array( $this, 'parse_shortcode' ) );
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data();
		if ( true === $get_data['is_preview'] ) {
			return $this->get_dummy_preview();
		}

		$group_id = isset( $get_data['group_id'] ) ? intval( $get_data['group_id'] ) : '';
		if ( ! empty( $group_id ) ) {
			$group_name = get_the_title( $group_id );

			return $this->parse_shortcode_output( $group_name, $attr );
		}

		$course_id = isset( $get_data['course_id'] ) ? intval( $get_data['course_id'] ) : '';
		if ( empty( $course_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$groups = learndash_get_course_groups( $course_id );
		if ( empty( $groups ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$group_names = [];
		foreach ( $groups as $id ) {
			$group_names[] = get_the_title( $id );
		}

		$group_names = implode( ', ', $group_names );

		return $this->parse_shortcode_output( $group_names, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'Group Name';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_group', 'BWFAN_LD_Group_Name', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
