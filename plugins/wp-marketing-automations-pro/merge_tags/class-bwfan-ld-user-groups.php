<?php

class BWFAN_LD_User_Groups extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_user_groups';
		$this->tag_description = __( 'User\'s Groups', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_user_groups', array( $this, 'parse_shortcode' ) );
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

		$email = BWFAN_Merge_Tag_Loader::get_data( 'email' );
		$email = ( ! empty( $email ) && is_email( $email ) ) ? $email : '';

		$user    = ! empty( $email ) ? get_user_by_email( $email ) : '';
		$user_id = ! empty( $user ) ? $user->ID : BWFAN_Merge_Tag_Loader::get_data( 'user_id' );

		$groups = BWFAN_Learndash_Common::get_groups_by_user_id( $user_id );

		$group_names = array();
		if ( ! empty( $groups ) && is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				$group_names[] = get_the_title( $group['group'] );
			}
		}

		$group_names = implode( ', ', $group_names );

		return $this->parse_shortcode_output( $group_names, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Group 1, Group 2';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_user', 'BWFAN_LD_User_Groups' );
}
