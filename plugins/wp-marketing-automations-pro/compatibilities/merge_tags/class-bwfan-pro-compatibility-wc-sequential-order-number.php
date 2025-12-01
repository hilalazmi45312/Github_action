<?php

class BWFAN_WC_Sequential_Order_Number extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wc_seq_order_number';
		$this->tag_description = __( 'WooCommerce Sequential Order Number', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wc_sequential_order_number', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_wc_seq_order_number', array( $this, 'parse_shortcode' ) );
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

		$order    = null;
		$order_id = absint( BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' ) );
		$order_id = 0 === $order_id ? absint( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) : $order_id;

		if ( 0 === $order_id ) {
			$order = BWFAN_Merge_Tag_Loader::get_data( 'wc_order' );
		} else {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order instanceof WC_Order ) {
			return '';
		}

		$order_number = $order->get_meta( '_order_number_formatted' );
		$order_number = empty( $order_number ) ? $order->get_meta( '_order_number' ) : $order_number;

		return $this->parse_shortcode_output( $order_number, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'WC-1234-2020';
	}
}

BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_WC_Sequential_Order_Number' );
