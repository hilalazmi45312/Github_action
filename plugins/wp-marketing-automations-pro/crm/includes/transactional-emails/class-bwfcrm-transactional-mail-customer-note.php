<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Customer_Note extends BWFCRM_Transactional_Mail_Base {
	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'customer_note';
		$this->name            = __( 'Customer Note Added', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Customer note emails are sent when you add a note to an order.', 'wp-marketing-automations-pro' );
		$this->priority        = 14;
		$this->subject         = __( 'Note added to your', 'wp-marketing-automations-pro' ) . ' {{store_name}} ' . __( 'order from ', 'wp-marketing-automations-pro' ) . "{{order_date format='j M Y'}}";
		$this->recipient       = 'customer';
		$this->merge_tag_group = [
			'wc_order',
		];
		$this->supported_block = [ 'order' ];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'customer_note';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_customer_note', '__return_false' );

			/** Add action to send mail and set_data for mail */
			add_action( 'woocommerce_new_customer_note', [ $this, 'bwf_new_customer_note_added' ] );
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
	 * Set data for mail
	 *
	 * @param array $args - Arguments order_id, customer_note
	 */
	public function bwf_new_customer_note_added( $args = [] ) {
		/** Return if template id or order id is empty */
		if ( empty( $this->template_data['template_id'] ) || empty( $args['order_id'] ) ) {
			return;
		}

		/** Get order id */
		$order_id = intval( $args['order_id'] );
		if ( empty( $order_id ) ) {
			return;
		}

		/** Get order */
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
			'order_id'           => $order_id,
			'oid'                => $order_id,
			'email'              => BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_billing_email' ),
			'wc_order'           => $order,
			'contact_id'         => BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_woofunnel_cid' ),
			'current_order_note' => isset( $args['customer_note'] ) ? $args['customer_note'] : '',
		);

		/** Create engagement tracking */
		$this->create_engagement_tracking( $template_id, $this->get_api_data(), $set_data );
	}
}
