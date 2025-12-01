<?php

class BWFAN_WCM_Member_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wcm_member_name';
		$this->tag_description = __( 'Member Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wcm_member_name', array( $this, 'parse_shortcode' ) );
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

		$user_membership_id = BWFAN_Merge_Tag_Loader::get_data( 'wc_user_membership_id' );

		$user_membership = $user_membership_id ? wc_memberships_get_user_membership( $user_membership_id ) : false;

		if ( ! $user_membership instanceof WC_Memberships_User_Membership ) {
			return;
		}

		$user = $user_membership->get_user();
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$user_display_name = trim( ucwords( $user->first_name ) . ' ' . ucwords( $user->last_name ) );

		return $this->parse_shortcode_output( $user_display_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'John Doe';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_membership', 'BWFAN_WCM_Member_Name', null, __( 'WooCommerce Membership', 'wp-marketing-automations-pro' ) );
}
