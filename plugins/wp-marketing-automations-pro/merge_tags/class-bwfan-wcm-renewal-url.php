<?php

class BWFAN_WCM_Renewal_URL extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'user_membership_renewal_url';
		$this->tag_description = __( 'Membership Renewal URL', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_user_membership_renewal_url', array( $this, 'parse_shortcode' ) );
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

		$next_bill_link  = '';
		$user_membership = wc_memberships_get_user_membership( BWFAN_Merge_Tag_Loader::get_data( 'wc_user_membership_id' ) );

		if ( $user_membership instanceof WC_Memberships_User_Membership ) {
			$user_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );

			$next_bill_date = $user_membership->get_next_bill_on_local_date( wc_date_format() );
			if ( ! empty( $next_bill_date ) ) {
				$next_bill_link = $user_membership->get_renew_membership_url();
			}
		}

		return $this->parse_shortcode_output( $next_bill_link, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @param $parameters
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return esc_url( home_url() );
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_membership', 'BWFAN_WCM_Renewal_URL' );
}
