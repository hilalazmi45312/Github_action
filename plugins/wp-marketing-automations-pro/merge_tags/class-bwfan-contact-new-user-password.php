<?php

class BWFAN_Contact_New_User_Password extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'new_user_password';
		$this->tag_description = __( 'Contact User Password', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_new_user_password', array( $this, 'parse_shortcode' ) );
		$this->priority = 33;
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data();
		if ( true === $get_data['is_preview'] ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
		}

		/** If Contact ID available */
		$cid = isset( $get_data['contact_id'] ) ? $get_data['contact_id'] : '';

		$order = isset( $get_data['wc_order'] ) ? $get_data['wc_order'] : '';
		if ( empty( $cid ) && bwfan_is_woocommerce_active() && $order instanceof WC_Order ) {
			$cid = BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_woofunnel_cid' );
		}

		$contact = null;
		if ( empty( $cid ) ) {
			$user_id = isset( $get_data['user_id'] ) ? $get_data['user_id'] : '';
			$email   = isset( $get_data['email'] ) ? $get_data['email'] : '';
			$contact = new WooFunnels_Contact( $user_id, $email );
		} else {
			$contact = new WooFunnels_Contact( '', '', '', absint( $cid ) );
		}

		if ( 0 === $contact->get_id() ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$password = $contact->get_meta( 'bwfan_userpassword' );

		/** Delete password if not empty */
		if ( ! empty( $password ) ) {
			$contact->delete_meta( 'bwfan_userpassword' );
		}

		return $this->parse_shortcode_output( $password, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return '';
	}


}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'bwfan_password', 'BWFAN_Contact_New_User_Password' );
