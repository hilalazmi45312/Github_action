<?php

class BWFAN_Wishlist_Title extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wishlist_title';
		$this->tag_description = __( 'Wishlist Title', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wishlist_title', array( $this, 'parse_shortcode' ) );
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

		$wishlist = get_post( $wishlist_id );

		if ( ! $wishlist instanceof WP_Post ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$title = $wishlist->post_title;

		return $this->parse_shortcode_output( $title, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Test';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_wc_wishlist_active' ) && bwfan_is_wc_wishlist_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_wishlist', 'BWFAN_Wishlist_Title', null, __( 'WooCommerce Wishlist', 'wp-marketing-automations-pro' ) );
}
