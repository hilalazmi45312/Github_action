<?php

class BWFAN_WCM_Start_Date extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'membership_start_date';
		$this->tag_description = __( 'Membership Start Date', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_membership_start_date', array( $this, 'parse_shortcode' ) );
		$this->support_date = true;
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
		$parameters           = [];
		$parameters['format'] = isset( $attr['format'] ) ? $attr['format'] : 'F j';
		if ( isset( $attr['modify'] ) ) {
			$parameters['modify'] = $attr['modify'];
		}
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview( $parameters );
		}

		$membership = wc_memberships_get_user_membership( BWFAN_Merge_Tag_Loader::get_data( 'wc_user_membership_id' ) );
		$date       = '';
		if ( $membership instanceof WC_Memberships_User_Membership ) {
			$date = $membership->get_start_date( $parameters['format'] );

			$date = $this->format_datetime( $date, $parameters );
		}


		return $this->parse_shortcode_output( $date, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @param $parameters
	 *
	 * @return string
	 */
	public function get_dummy_preview( $parameters ) {
		return $this->format_datetime( '2018-12-07 13:25:39', $parameters );
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_membership', 'BWFAN_WCM_Start_Date', null, __( 'WooCommerce Membership', 'wp-marketing-automations-pro' ) );
}
