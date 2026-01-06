<?php

class BWFAN_LD_User_Group_Leaders extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_user_group_leaders';
		$this->tag_description = __( 'User\'s All Groups Leader(s)\'s Names', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_user_group_leaders', array( $this, 'parse_shortcode' ) );
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

		$leaders = BWFAN_Learndash_Common::get_group_leaders_by_user_id( $user_id );

		$leader_names = array();
		if ( ! empty( $leaders ) && is_array( $leaders ) ) {
			foreach ( $leaders as $leader ) {
				$leader_obj = get_user_by( 'ID', absint( $leader['user_id'] ) );

				if ( $leader_obj instanceof WP_User ) {
					$leader_names[] = $leader_obj->display_name;
				}
			}
		}

		$leaders_emails_to = implode( ', ', $leader_names );

		return $this->parse_shortcode_output( $leaders_emails_to, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Leader Name 1, Leader Name 2';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_user', 'BWFAN_LD_User_Group_Leaders', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
