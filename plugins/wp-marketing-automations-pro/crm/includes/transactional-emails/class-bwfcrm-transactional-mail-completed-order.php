<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Completed_Order extends BWFCRM_Transactional_Mail_Base {

	/**
	 * Track which order IDs have been scheduled for email processing
	 *
	 * @var array
	 */
	private $scheduled_ids = [];

	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'completed_order';
		$this->name            = __( 'Completed Order', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Order complete emails are sent to customers when their orders are marked completed and usually indicate that their orders have been shipped.', 'wp-marketing-automations-pro' );
		$this->priority        = 8;
		$this->recipient       = 'customer';
		$this->subject         = __( 'Your', 'wp-marketing-automations-pro' ) . ' {{store_name}} ' . __( 'order is now complete', 'wp-marketing-automations-pro' );
		$this->merge_tag_group = [
			'wc_order',
		];
		$this->supported_block = [ 'order' ];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'customer_completed_order';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false' );

			/** Add action to send mail and set_data function hit */
			add_action( 'woocommerce_order_status_completed', array( $this, 'run_completed_order_mail' ), 30, 2 );
			add_action( 'bwfcrm_before_resend_order_email_customer_completed_order', array( $this, 'resend_order_emails' ) );
		}
	}

	/**
	 * Resend order emails for completed orders
	 *
	 * @param $order
	 *
	 * @return void
	 */
	public function resend_order_emails( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->run_completed_order_mail( $order->get_id() );
	}

	/**
	 * Run completed order mail in the shutdown hook
	 *
	 * @param int $order_id
	 * @param WC_Order $order Order object.
	 */
	public function run_completed_order_mail( $order_id, $order = null ) {
		$order_id = (int) $order_id;
		if ( isset( $this->scheduled_ids[ $order_id ] ) ) {
			return; // Prevent multiple scheduling
		}
		add_action( 'shutdown', function () use ( $order_id ) {
			$this->bwf_order_status_to_completed( $order_id );
		} );
		$this->scheduled_ids[ $order_id ] = true; // Mark as scheduled
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
	 * @param int $order_id
	 */
	public function bwf_order_status_to_completed( $order_id ) {
		/** Return if template id or order id is empty */
		if ( empty( $this->template_data['template_id'] ) || empty( $order_id ) ) {
			return;
		}

		/** Get order object */
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
