<?php

class BWFAN_WCM_Membership_Plan_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'membership_plan_name';
		$this->tag_description = __( 'Membership Plan Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_membership_plan_name', array( $this, 'parse_shortcode' ) );
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

		$plan      = BWFAN_Merge_Tag_Loader::get_data( 'wc_membership_plan_id' );
		$plan      = wc_memberships_get_membership_plan( $plan );
		$plan_name = '';
		if ( ! empty( $plan ) ) {
			$plan_name = $plan->get_name();
		} else {
			/**
			 * @var WC_Memberships_User_Membership $membership
			 */
			$membership = wc_memberships_get_user_membership( BWFAN_Merge_Tag_Loader::get_data( 'wc_user_membership_id' ) );
			if ( $membership instanceof WC_Memberships_User_Membership ) {
				$plan_name = $membership->get_plan()->get_name();
			}

		}

		return $this->parse_shortcode_output( esc_html( $plan_name ), $attr );
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
	BWFAN_Merge_Tag_Loader::register( 'wc_membership', 'BWFAN_WCM_Membership_Plan_Name' );
}
