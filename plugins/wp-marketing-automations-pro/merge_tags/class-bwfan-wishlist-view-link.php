<?php

class BWFAN_Wishlist_View_Link extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wishlist_view_link';
		$this->tag_description = __( 'Wishlist View Link', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wishlist_view_link', array( $this, 'parse_shortcode' ) );
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

		$wishlist_id = BWFAN_Merge_Tag_Loader::get_data( 'wishlist_id' );

		if ( ! $wishlist_id ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$view_link = get_permalink( $wishlist_id );

		return $this->parse_shortcode_output( $view_link, $attr );
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
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_wc_wishlist_active' ) && bwfan_is_wc_wishlist_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_wishlist', 'BWFAN_Wishlist_View_Link', null, __( 'WooCommerce Wishlist', 'wp-marketing-automations-pro' ) );
}
