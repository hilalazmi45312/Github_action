<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Order_Details extends BWFCRM_Transactional_Mail_Base {
	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'order_details';
		$this->name            = __( 'Order Details', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Order detail emails can be sent to customers containing their order information and payment links.', 'wp-marketing-automations-pro' );
		$this->priority        = 12;
		$this->recipient       = 'customer';
		$this->subject         = __( 'Detail for order', 'wp-marketing-automations-pro' ) . ' #{{order_id}} ' . __( 'on', 'wp-marketing-automations-pro' ) . ' {{store_name}}';
		$this->merge_tag_group = [
			'wc_order',
		];
		$this->supported_block = [ 'order' ];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'customer_invoice';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_recipient_customer_invoice', array( $this, 'remove_recipients_form_order_invoice' ) );

			add_action( 'bwfcrm_before_resend_order_email_customer_invoice', array( $this, 'resend_order_emails' ) );
		}
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
	 * Remove recipients form order invoice
	 *
	 * @param string $recipient
	 *
	 * @return string
	 */
	public function remove_recipients_form_order_invoice( $recipient ) {
		return '';
	}

	/**
	 * Set data for mail
	 *
	 * @param int $order
	 */
	public function resend_order_emails( $order ) {
		/** Return if template id or order id is empty */
		if ( empty( $this->template_data['template_id'] ) ) {
			return;
		}

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
			'order_id'   => $order->get_id(),
			'oid'        => $order->get_id(),
			'email'      => BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_billing_email' ),
			'wc_order'   => $order,
			'contact_id' => BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_woofunnel_cid' ),
		);

		/** Create engagement tracking */
		$this->create_engagement_tracking( $template_id, $this->get_api_data(), $set_data );
	}
}
