<?php

class BWFAN_AFFWP_Affiliate_Coupon extends BWFAN_Merge_Tag {

	private static $instance = null;
	protected $support_v1 = false;

	public function __construct() {
		$this->tag_name        = 'affwp_affiliate_coupon';
		$this->tag_description = __( 'Affiliate Coupon', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_affiliate_coupon', array( $this, 'parse_shortcode' ) );
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

		$affiliate_id = BWFAN_Merge_Tag_Loader::get_data( 'affiliate_id' );
		if ( empty( $affiliate_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$coupon_code = $this->get_affiliate_coupon_code( $affiliate_id );

		return $this->parse_shortcode_output( $coupon_code, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'Affiliate Coupon';
	}

	public static function get_affiliate_coupon_code( $affiliate_id ) {
		global $wpdb;

		$coupon_code = '';

		try {
			$query = $wpdb->prepare( "SELECT `coupon_code` FROM `{$wpdb->prefix}affiliate_wp_coupons` WHERE `affiliate_id` = %d", $affiliate_id );

			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$coupon_code = $wpdb->get_var( $query );
		} catch ( Error $e ) {
			BWFAN_Common::log_test_data( 'Error in getting affiliate coupon code: ' . $e->getMessage(), 'affwp_affiliate_coupon', true );
		}

		return $coupon_code;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() && bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_affiliate', 'BWFAN_AFFWP_Affiliate_Coupon' );
}
