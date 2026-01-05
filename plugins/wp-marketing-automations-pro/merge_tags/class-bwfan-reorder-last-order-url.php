<?php

class BWFAN_REORDER_LAST_ORDER_URL extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'reorder_last_order_url';
		$this->tag_description = __( 'Reorder Last Order URL', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_reorder_last_order_url', array( $this, 'parse_shortcode' ) );
		$this->priority = 26;
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
		$email    = BWFAN_Merge_Tag_Loader::get_data( 'email' );
		$order_id = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
		if ( ! empty( $order_id ) ) {
			$order = wc_get_order( $order_id );
			$cid   = BWF_WC_Compatibility::get_order_meta( $order, '_woofunnel_cid' );
		}
		if ( empty( $cid ) && is_email( $email ) ) {
			$cid = BWFAN_PRO_Common::get_cid_from_contact( $email );
		}

		$last_order_id = $this->get_last_valid_order( $cid );
		if ( empty( $last_order_id ) ) {
			return $this->parse_shortcode_output( home_url(), $attr );
		}

		$order = wc_get_order( $last_order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->parse_shortcode_output( home_url(), $attr );
		}

		$reorder_url = add_query_arg( 'bwfan-order-again', $order->get_order_key(), wc_get_cart_url() );

		return $this->parse_shortcode_output( $reorder_url, $attr );
	}

	public static function get_last_valid_order( $cid ) {
		global $wpdb;

		$args                = [ $cid ];
		$order_status        = array( 'wc-on-hold', 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed' );
		$unpaid_status       = apply_filters( 'bwfan_exclude_reorder_status', $order_status );
		$status_placeholders = implode( ', ', array_fill( 0, count( $unpaid_status ), '%s' ) );
		$args                = array_merge( $args, $unpaid_status );

		if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
			$query = $wpdb->prepare( "SELECT orders.ID FROM {$wpdb->prefix}wc_orders AS orders
            LEFT JOIN {$wpdb->prefix}wc_orders_meta AS wc_orders_meta ON orders.ID = wc_orders_meta.order_id AND wc_orders_meta.meta_key = '_woofunnel_cid'
            WHERE wc_orders_meta.meta_value = %s
            AND orders.status NOT IN ($status_placeholders)
            ORDER BY orders.ID DESC
            LIMIT 1", $args );

			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$order_id = $wpdb->get_var( $query );

			return intval( $order_id );
		}

		$query = $wpdb->prepare( "SELECT posts.ID FROM {$wpdb->posts} AS posts
		LEFT JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_woofunnel_cid'
		WHERE postmeta.meta_value = %s
		AND posts.post_status NOT IN ($status_placeholders)
		ORDER BY posts.ID DESC
		LIMIT 1", $args  // Use only the first element of the $args array
		);

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$order_id = $wpdb->get_var( $query );

		return intval( $order_id );
	}

	/**
	 * Show dummy value
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
if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_REORDER_LAST_ORDER_URL', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
}
