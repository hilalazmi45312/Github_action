<?php

class BWFAN_Contact_Total_Spent extends BWFAN_Merge_Tag {

	private static $instance = null;


	public function __construct() {
		$this->tag_name        = 'contact_total_spent';
		$this->tag_description = __( 'Contact Total Spent', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_contact_total_spent', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_customer_total_spent', array( $this, 'parse_shortcode' ) );
		$this->support_fallback = false;
		$this->priority         = 35;
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
			return $this->parse_shortcode_output( $this->get_dummy_preview( $attr ), $attr );
		}

		$user_id     = 0;
		$email       = '';
		$total_spent = 0;

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
			$total_spent = $customer->get_total_order_value();
		}

		$formatting  = BWFAN_Common::get_formatting_for_wc_price( $attr, '' );
		$total_spent = BWFAN_Common::get_formatted_price_wc( $total_spent, $formatting['raw'], $formatting['currency'] );

		return $this->parse_shortcode_output( $total_spent, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview( $attr ) {

		$formatting = BWFAN_Common::get_formatting_for_wc_price( $attr, '' );

		$order_spent = 99;
		if ( ! method_exists( BWFAN_Merge_Tag::class, 'get_contact_data' ) ) {
			return BWFAN_Common::get_formatted_price_wc( $order_spent, $formatting['raw'], $formatting['currency'] );
		}

		$contact = $this->get_contact_data();

		/** checking contact instance and id */
		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			return BWFAN_Common::get_formatted_price_wc( $order_spent, $formatting['raw'], $formatting['currency'] );
		}

		$customer = BWFAN_Common::get_bwf_customer( $contact->get_email(), $contact->get_wpid() );

		if ( $customer ) {
			$order_spent = $customer->get_total_order_value();
		}

		return BWFAN_Common::get_formatted_price_wc( $order_spent, $formatting['raw'], $formatting['currency'] );
	}

	/**
	 * price formatting schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$options = [
			[
				'value' => 'raw',
				'label' => __( 'Raw', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'formatted',
				'label' => __( 'Formatted', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'formatted-currency',
				'label' => __( 'Formatted with currency', 'wp-marketing-automations-pro' ),
			],
		];

		return [
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Display', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Raw', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => ""
			],
		];
	}

}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Contact_Total_Spent', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
}
