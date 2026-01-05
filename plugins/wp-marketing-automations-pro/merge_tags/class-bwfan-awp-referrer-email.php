<?php

class BWFAN_AWP_Referrer_Email extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'awp_referrer_email';
		$this->tag_description = __( 'AffiliateWP Referrer Email', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_awp_referrer_email', array( $this, 'parse_shortcode' ) );
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

		$order_id = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
		$order_id = absint( $order_id ) > 0 ? $order_id : BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' );
		if ( empty( $order_id ) ) {
			return '';
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'affiliate_wp_referrals';
		$query      = $wpdb->prepare( "SELECT affiliate_id from {$table_name} where context = %s and reference = %d", 'woocommerce', $order_id );

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$affiliate_data = $wpdb->get_results( $query, ARRAY_A );
		if ( empty( $affiliate_data ) ) {
			return '';
		}

		$affiliate_id = isset( $affiliate_data[0]['affiliate_id'] ) ? $affiliate_data[0]['affiliate_id'] : "";
		if ( empty( $affiliate_id ) ) {
			return '';
		}

		$referrer_email = affwp_get_affiliate_email( $affiliate_id );

		return $this->parse_shortcode_output( $referrer_email, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return '11';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_aff_affiliate', 'BWFAN_AWP_Referrer_Email' );
}
