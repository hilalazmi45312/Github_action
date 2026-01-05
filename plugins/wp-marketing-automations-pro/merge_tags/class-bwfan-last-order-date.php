<?php

class BWFAN_WC_Last_Order_Date extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wc_last_order_date';
		$this->tag_description = __( 'Last Order Date', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wc_last_order_date', [ $this, 'parse_shortcode' ] );
		$this->support_date     = true;
		$this->priority         = 26;
		$this->is_crm_broadcast = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function parse_shortcode( $attr ) {
		$parameters['format'] = apply_filters( 'bwfan_wc_last_order_date_format', $attr['format'] ?? get_option( 'date_format' ) );

		if ( BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview( $parameters ), $attr );
		}

		$get_data = BWFAN_Merge_Tag_Loader::get_data();
		$user_id  = $get_data['user_id'] ?? 0;
		$email    = $get_data['email'] ?? '';

		if ( empty( $user_id ) && empty( $email ) ) {
			$order = $this->get_order_from_data( $get_data );
			if ( $order instanceof WC_Order ) {
				$user_id = $order->get_user_id();
				$email   = $order->get_billing_email();
			}
		}

		$contact_id = $get_data['contact_id'] ?? 0;
		if ( empty( $email ) && ! empty( $contact_id ) ) {
			$contact = new BWFCRM_Contact( $contact_id );
			if ( $contact->is_contact_exists() ) {
				$email = $contact->contact->get_email();
			}
		}

		$customer        = BWFAN_Common::get_bwf_customer( $email, $user_id );
		$last_order_date = ( $customer instanceof WooFunnels_Customer ) ? $customer->get_l_order_date() : '';

		if ( empty( $last_order_date ) || $last_order_date === '0000-00-00' ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		return $this->parse_shortcode_output( $this->format_datetime( $last_order_date, $parameters, true ), $attr );
	}

	private function get_order_from_data( $get_data ) {
		if ( isset( $get_data['wc_order'] ) ) {
			return $get_data['wc_order'];
		}
		if ( isset( $get_data['order_id'] ) ) {
			return wc_get_order( $get_data['order_id'] );
		}

		return null;
	}

	public function get_dummy_preview( $parameters = [] ) {
		return $this->format_datetime( date( 'Y-m-d' ), $parameters );
	}

	public function get_setting_schema() {
		$date_formats = BWFAN_PRO_Common::get_date_formats();
		$date_formats = array_map( function ( $data ) {
			return [
				'value' => $data['format'],
				'label' => date( $data['format'] ),
			];
		}, $date_formats );

		return [
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $date_formats,
				'label'       => __( 'Select Date Format', 'wp-marketing-automations-pro' ),
				'class'       => 'bwfan-input-wrapper',
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				'required'    => false,
				'description' => '',
			],
		];
	}
}

if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_WC_Last_Order_Date', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
}
