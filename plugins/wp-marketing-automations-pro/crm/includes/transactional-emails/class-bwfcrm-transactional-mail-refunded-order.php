<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Refunded_Order extends BWFCRM_Transactional_Mail_Base {
	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'refunded_order';
		$this->name            = __( 'Refunded Order', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Order refunded emails are sent to customers when their orders are refunded.', 'wp-marketing-automations-pro' );
		$this->priority        = 10;
		$this->recipient       = 'customer';
		$this->subject         = __( 'Your', 'wp-marketing-automations-pro' ) . ' {{store_name}}: ' . __( 'order', 'wp-marketing-automations-pro' ) . ' #{{order_id}} ' . __( 'has been refunded', 'wp-marketing-automations-pro' );
		$this->merge_tag_group = [
			'wc_order',
		];
		$this->supported_block = [ 'order' ];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'customer_refunded_order';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_customer_refunded_order', '__return_false' );

			/** Add action to send mail and set_data function hit */
			add_action( 'woocommerce_order_fully_refunded', array( $this, 'bwf_order_status_trigger_full' ), 10, 2 );
			add_action( 'woocommerce_order_partially_refunded', array( $this, 'bwf_order_status_trigger_partial' ), 10, 2 );

			add_action( 'bwfcrm_before_resend_order_email_refunded_order', array( $this, 'resend_order_emails' ) );
		}
	}

	/**
	 * Resend order emails for refunded orders
	 *
	 * @param $order
	 *
	 * @return void
	 */
	public function resend_order_emails( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->bwf_order_status_to_refunded( $order->get_id() );
	}

	/**
	 * Check if mail is valid with dependency
	 *
	 * @return bool
	 */
	public function is_valid() {
		return bwfan_is_woocommerce_active();
	}

	/**
	 * Full refund notification.
	 *
	 * @param int $order_id Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function bwf_order_status_trigger_full( $order_id, $refund_id = null ) {
		$this->bwf_order_status_to_refunded( $order_id, $refund_id, false );
	}

	/**
	 * Partial refund notification.
	 *
	 * @param int $order_id Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function bwf_order_status_trigger_partial( $order_id, $refund_id = null ) {
		$this->bwf_order_status_to_refunded( $order_id, $refund_id, true );
	}

	/**
	 * Set data for mail
	 *
	 * @param int $order_id
	 * @param int|null $refund_id
	 * @param bool $partial
	 */
	public function bwf_order_status_to_refunded( $order_id, $refund_id = null, $partial = false ) {
		/** Return if template id or order id is empty */
		if ( empty( $this->template_data['template_id'] ) || empty( $order_id ) ) {
			return;
		}

		/** Get order object if not passed */
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/** Get template id */
		$template_id = intval( $this->template_data['template_id'] );

		$order_language = BWFAN_Common::get_order_language( $order );
		if ( ! empty( $order_language ) && isset( $this->template_data['lang'][ $order_language ] ) ) {
			$template_id = $this->template_data['lang'][ $order_language ];
		}

		/** Set data for merge tag */
		$set_data = array(
			'order_id'   => $order_id,
			'oid'        => $order_id,
			'email'      => BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_billing_email' ),
			'wc_order'   => $order,
			'contact_id' => BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_woofunnel_cid' ),
		);

		/** Create engagement tracking */
		$this->create_engagement_tracking( $template_id, $this->get_api_data(), $set_data );
	}
}
