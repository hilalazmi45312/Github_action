<?php

class BWFAN_LD_Group_Leaders extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_group_leaders';
		$this->tag_description = __( 'Group Leader(s) Names', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_group_leaders', array( $this, 'parse_shortcode' ) );
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
			$leader_names = $this->get_group_leaders( $group_id );
			$leaders      = is_array( $leader_names ) ? implode( ', ', $leader_names ) : '';

			return $this->parse_shortcode_output( $leaders, $attr );
		}

		$course_id = isset( $get_data['course_id'] ) ? intval( $get_data['course_id'] ) : '';
		if ( empty( $course_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$groups = learndash_get_course_groups( intval( $course_id ) );
		if ( empty( $groups ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$leader_names = $this->get_group_leaders( $groups );

		$leaders = is_array( $leader_names ) ? implode( ', ', $leader_names ) : '';

		return $this->parse_shortcode_output( $leaders, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'Leader Name';
	}

	/**
	 * fetching group leaders name
	 *
	 * @param $group_ids
	 *
	 * @return array
	 */
	public function get_group_leaders( $group_ids ) {
		$leaders = BWFAN_Learndash_Common::get_group_leaders_by_group_id( $group_ids );
		if ( empty( $leaders ) || ! is_array( $leaders ) ) {
			return [];
		}

		$names = array();
		foreach ( $leaders as $leader ) {
			$user_obj = get_user_by( 'ID', absint( $leader['user_id'] ) );
			if ( ! $user_obj instanceof WP_User ) {
				continue;
			}

			$names[] = $user_obj->display_name;
		}

		return $names;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_group', 'BWFAN_LD_Group_Leaders', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
