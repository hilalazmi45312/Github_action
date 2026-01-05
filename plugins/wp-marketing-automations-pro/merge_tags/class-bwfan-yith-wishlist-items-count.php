<?php

class BWFAN_Yith_Wishlist_Items_Count extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'yith_wishlist_items_count';
		$this->tag_description = __( 'Wishlist Items Count', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_yith_wishlist_items_count', array( $this, 'parse_shortcode' ) );
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
		global $wpdb;

		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$wishlist_id = BWFAN_Merge_Tag_Loader::get_data( 'wishlist_id' );
		if ( empty( $wishlist_id ) ) {
			return $this->parse_shortcode_output( 0, $attr );
		}

		$query = $wpdb->prepare( "SELECT `prod_id` FROM {$wpdb->prefix}yith_wcwl WHERE `wishlist_id` = %d", $wishlist_id );

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pro_ids = $wpdb->get_col( $query );

		$count_pro_ids = isset( $pro_ids ) && is_array( $pro_ids ) ? count( $pro_ids ) : 0;

		return $this->parse_shortcode_output( $count_pro_ids, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return '0';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_yith_wishlist_active' ) && bwfan_is_yith_wishlist_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'yith_wishlist', 'BWFAN_Yith_Wishlist_Items_Count', null, __( 'Yith Wishlist', 'wp-marketing-automations-pro' ) );
}
