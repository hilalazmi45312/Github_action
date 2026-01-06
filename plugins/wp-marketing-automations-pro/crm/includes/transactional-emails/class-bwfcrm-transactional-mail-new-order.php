<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_New_Order extends BWFCRM_Transactional_Mail_Base {

	/**
	 * Track which order IDs have been scheduled for email processing
	 *
	 * @var array
	 */
	private $scheduled_ids = [];

	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'new_order';
		$this->name            = __( 'New Order', 'wp-marketing-automations-pro' );
		$this->description     = __( 'New order emails are sent to chosen recipient(s) when a new order is received.', 'wp-marketing-automations-pro' );
		$this->priority        = 20;
		$this->recipient       = 'admin';
		$this->subject         = '{{store_name}}: ' . __( 'New order', 'wp-marketing-automations-pro' ) . ' #{{order_id}}';
		$this->merge_tag_group = [
			'wc_order',
		];
		$this->supported_block = [ 'order' ];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'new_order';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_new_order', '__return_false' );

			/** Add action to send mail and set_data function hit */
			add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_pending_to_completed', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_pending_to_on-hold', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_failed_to_processing', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_failed_to_completed', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_failed_to_on-hold', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_cancelled_to_processing', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_cancelled_to_completed', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );
			add_action( 'woocommerce_order_status_cancelled_to_on-hold', array( $this, 'bwf_new_order_mail_admin' ), 1, 2 );

			add_action( 'bwfcrm_before_resend_order_email_new_order', array( $this, 'resend_order_emails' ) );
		}
	}

	/**
	 * Resend order emails for new orders
	 *
	 * @param $order
	 *
	 * @return void
	 */
	public function resend_order_emails( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->bwf_new_order_mail_admin( $order->get_id() );
	}

	/**
	 * Run new order mail in the shutdown hook
	 *
	 * @param int $order_id
	 * @param WC_Order $order Order object.
	 */
	public function bwf_new_order_mail_admin( $order_id, $order = null ) {
		$order_id = (int) $order_id;
		if ( isset( $this->scheduled_ids[ $order_id ] ) ) {
			return; // Prevent multiple scheduling
		}

		// if the order is not passed, get it from order ID
		if ( ! $order instanceof WC_Order && ! empty( $order_id ) ) {
			$order = wc_get_order( $order_id );
		}

		// If the order is still not valid, return
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$email_already_sent = $order->get_new_order_email_sent();
		if ( $email_already_sent && ! apply_filters( 'woocommerce_new_order_email_allows_resend', true ) ) {
			return;
		}
		add_action( 'shutdown', function () use ( $order_id, $order ) {
			$this->bwf_new_order_mail( $order_id, $order );
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
	 * @param WC_Order|false $order Order object.
	 *
	 * @return void
	 */
	public function bwf_new_order_mail( $order_id, $order ) {
		/** Return if template id or order id is empty */
		if ( empty( $this->template_data['template_id'] ) || empty( $order_id ) ) {
			return;
		}

		/** Get order object if not passed */
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/** Get template id */
		$template_id = intval( $this->template_data['template_id'] );

		$email_already_sent = $order->get_new_order_email_sent();
		if ( $email_already_sent && ! apply_filters( 'woocommerce_new_order_email_allows_resend', true ) ) {
			return;
		}

		$order_language = BWFAN_Common::get_order_language( $order );
		if ( ! empty( $order_language ) && isset( $this->template_data['lang'][ $order_language ] ) ) {
			$template_id = $this->template_data['lang'][ $order_language ];
		}

		/** Set data for merge tag */
		$set_data = array(
			'order_id'   => $order_id,
			'oid'        => $order_id,
			'email'      => BWFAN_Common::$admin_email,
			'wc_order'   => $order,
			'contact_id' => 0,
		);

		/** Create engagement tracking */
		$status = $this->create_engagement_tracking( $template_id, $this->get_api_data(), $set_data );
		if ( $status ) {
			$order->update_meta_data( '_new_order_email_sent', 'true' );
			$order->save();
		}
	}
}
