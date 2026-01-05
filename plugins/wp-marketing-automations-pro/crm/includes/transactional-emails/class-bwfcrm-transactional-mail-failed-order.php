<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Failed_Order extends BWFCRM_Transactional_Mail_Base {
	/**
	 * Track which order IDs have been scheduled for email processing
	 *
	 * @var array
	 */
	private $scheduled_ids = [];

	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'failed_order';
		$this->name            = __( 'Failed Order', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Failed order emails are sent to chosen recipient(s) when orders have been marked failed (if they were previously pending or on-hold).', 'wp-marketing-automations-pro' );
		$this->priority        = 22;
		$this->subject         = '{{store_name}}: ' . __( 'Order', 'wp-marketing-automations-pro' ) . ' #{{order_id}}' . __( ' has failed', 'wp-marketing-automations-pro' );
		$this->recipient       = 'admin';
		$this->merge_tag_group = [
			'wc_order',
		];
		$this->supported_block = [ 'order' ];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'failed_order';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_failed_order', '__return_false' );

			/** Add action to send mail and set_data function hit */
			add_action( 'woocommerce_order_status_pending_to_failed', array( $this, 'run_failed_order_mail' ), 30, 2 );
			add_action( 'woocommerce_order_status_on-hold_to_failed', array( $this, 'run_failed_order_mail' ), 30, 2 );
		}
	}

	/**
	 * Run failed order mail in the shutdown hook
	 *
	 * @param int $order_id
	 * @param WC_Order $order Order object.
	 */
	public function run_failed_order_mail( $order_id, $order ) {
		$order_id = (int) $order_id;
		if ( isset( $this->scheduled_ids[ $order_id ] ) ) {
			return; // Prevent multiple scheduling
		}
		add_action( 'shutdown', function () use ( $order_id ) {
			$this->bwf_failed_order_mail( $order_id );
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
	 */
	public function bwf_failed_order_mail( $order_id ) {
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
			'email'      => BWFAN_Common::$admin_email,
			'wc_order'   => $order,
			'contact_id' => 0,
		);

		/** Create engagement tracking */
		$this->create_engagement_tracking( $template_id, $this->get_api_data(), $set_data );
	}
}
