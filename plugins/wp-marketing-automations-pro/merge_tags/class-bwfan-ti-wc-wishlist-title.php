<?php

class BWFAN_Ti_Wc_Wishlist_Title extends BWFAN_Merge_Tag {
	protected $support_v2 = true;
	protected $support_v1 = false;
	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ti_wc_wishlist_title';
		$this->tag_description = __( 'Wishlist Title', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ti_wc_wishlist_title', array( $this, 'parse_shortcode' ) );
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
		if ( empty( $wishlist_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$wishlist_data = BWFAN_PRO_Common::get_ti_wc_wishlist_data_by_id( $wishlist_id );
		if ( ! $wishlist_data ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$title = isset( $wishlist_data['title'] ) ? $wishlist_data['title'] : '';

		return $this->parse_shortcode_output( $title, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'Ti WC Wishlist Title';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_ti_wc_wishlist_active' ) && bwfan_is_ti_wc_wishlist_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'ti_wc_wishlist', 'BWFAN_Ti_Wc_Wishlist_Title', null, __( 'TI WooCommerce Wishlist', 'wp-marketing-automations-pro' ) );
}
