<?php

class BWFAN_Contact_Order_Count extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'contact_order_count';
		$this->tag_description = __( 'Contact Order Count', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_customer_order_count', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_contact_order_count', array( $this, 'parse_shortcode' ) );
		$this->priority = 31;
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

		$user_id     = 0;
		$email       = '';
		$order_count = 0;

		// Get user ID and Email
		if ( isset( $get_data['user_id'] ) ) {
			$user_id = $get_data['user_id'];
		}
		if ( isset( $get_data['email'] ) ) {
			$email = $get_data['email'];
		}
		if ( ! $user_id || ! $email ) {
			$order = null;
			if ( isset( $get_data['wc_order'] ) ) {
				$order = $get_data['wc_order'];
			}
			if ( ! $order instanceof WC_Order && isset( $get_data['order_id'] ) ) {
				$order = wc_get_order( $get_data['order_id'] );
			}
			if ( $order instanceof WC_Order ) {
				if ( ! $user_id ) {
					$user_id = $order->get_user_id();
				}
				if ( ! $email ) {
					$email = $order->get_billing_email();
				}
			}
		}

		$customer = BWFAN_Common::get_bwf_customer( $email, $user_id );

		if ( $customer ) {
			$order_count = $customer->get_total_order_count();
		}

		return $this->parse_shortcode_output( $order_count, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		$order_count = 4;
		if ( ! method_exists( BWFAN_Merge_Tag::class, 'get_contact_data' ) ) {
			return $order_count;
		}

		$contact = $this->get_contact_data();

		/** checking contact instance and id */
		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			return $order_count;
		}

		$customer = BWFAN_Common::get_bwf_customer( $contact->get_email(), $contact->get_wpid() );

		if ( $customer ) {
			$order_count = $customer->get_total_order_count();
		}

		return $order_count;
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Contact_Order_Count' );
}
