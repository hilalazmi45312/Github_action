<?php

class BWFAN_Upsell_Offer_Transaction_ID extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->support_v1      = false;
		$this->tag_name        = 'upsell_offer_transaction_id';
		$this->tag_description = __( 'Offer Transaction ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_upsell_offer_transaction_id', array( $this, 'parse_shortcode' ) );
		$this->priority = 16;
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
	 * @return int|mixed|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$offer_id = BWFAN_Merge_Tag_Loader::get_data( 'offer_id' );
		$order_id = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
		if ( empty( $order_id ) || empty( $offer_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$transaction_id = $this->get_transaction_id( $offer_id, $order_id );

		return $this->parse_shortcode_output( $transaction_id, $attr );
	}

	/**
	 * @param $offer_id
	 * @param $order_id
	 *
	 * @return string|null
	 */
	public function get_transaction_id( $offer_id, $order_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT events_meta.meta_value AS 'meta_value' FROM " . $wpdb->prefix . "wfocu_event AS events JOIN " . $wpdb->prefix . "wfocu_session AS session ON ( events.sess_id = session.id ) JOIN " . $wpdb->prefix . "wfocu_event_meta AS events_meta ON ( events.ID = events_meta.event_id ) WHERE ( ( events_meta.meta_key = %s AND events_meta.meta_value != '' )) AND events.action_type_id = %d AND events.object_id = %d AND session.order_id = %d ", '_transaction_id', 4, $offer_id, $order_id );

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $query );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview() {
		return 10000000;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_offer', 'BWFAN_Upsell_Offer_Transaction_ID', null, __( 'Upsell', 'wp-marketing-automations-pro' ) );
}
