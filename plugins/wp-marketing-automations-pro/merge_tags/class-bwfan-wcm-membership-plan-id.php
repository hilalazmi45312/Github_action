<?php

class BWFAN_WCM_Membership_Plan_Id extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'membership_plan_id';
		$this->tag_description = __( 'Membership Plan ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_membership_plan_id', array( $this, 'parse_shortcode' ) );
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

		$plan_id = BWFAN_Merge_Tag_Loader::get_data( 'wc_membership_plan_id' );
		if ( empty( $plan_id ) ) {
			$membership = wc_memberships_get_user_membership( BWFAN_Merge_Tag_Loader::get_data( 'wc_user_membership_id' ) );

			if ( ! $membership instanceof WC_Memberships_User_Membership ) {
				return;
			}

			$plan_id = $membership->get_plan_id();
		}

		return $this->parse_shortcode_output( $plan_id, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @param $parameters
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 123;
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_membership', 'BWFAN_WCM_Membership_Plan_Id', null, __( 'WooCommerce Membership', 'wp-marketing-automations-pro' ) );
}
