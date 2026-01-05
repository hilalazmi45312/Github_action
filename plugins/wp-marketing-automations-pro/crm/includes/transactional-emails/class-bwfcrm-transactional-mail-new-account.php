<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_New_Account extends BWFCRM_Transactional_Mail_Base {

	public $customer_created = [];

	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'new_account';
		$this->name            = __( 'New Account', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Customer "new account" emails are sent to the customer when a customer signs up via checkout or account pages.', 'wp-marketing-automations-pro' );
		$this->priority        = 16;
		$this->subject         = __( 'Your', 'wp-marketing-automations-pro' ) . ' {{store_name}}: ' . __( 'account has been created!', 'wp-marketing-automations-pro' );
		$this->recipient       = 'customer';
		$this->merge_tag_group = [
			'bwf_contact',
			'bwf_user',
		];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'customer_new_account';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );

			/** Add action to send mail and set_data function hit */
			add_action( 'woocommerce_created_customer', array( $this, 'bwf_new_account_mail' ), 30, 3 );

			/** Hook in below to access the order data */
			add_action( 'shutdown', array( $this, 'wc_order_processed' ) );
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
	 * Customer new account welcome email.
	 *
	 * @param int $customer_id Customer ID.
	 * @param array $new_customer_data New customer data.
	 * @param bool $password_generated If password is generated.
	 */
	public function bwf_new_account_mail( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		/** Return if template id or user id is empty */
		if ( empty( $this->template_data['template_id'] ) || ! $customer_id ) {
			return;
		}

		$email = isset( $new_customer_data['user_email'] ) ? $new_customer_data['user_email'] : '';
		if ( empty( $email ) ) {
			$user  = get_user_by( 'id', $customer_id );
			$email = $user instanceof WP_User ? $user->user_email : $email;
		}

		$set_data = array(
			'email'   => $email,
			'user_id' => $customer_id,
			'oid'     => $customer_id
		);

		$this->customer_created = $set_data;
	}

	public function wc_order_processed() {
		if ( empty( $this->customer_created ) ) {
			return;
		}

		/** Get template id */
		$template_id = intval( $this->template_data['template_id'] );

		/** Check for users last order */
		$order = $this->get_user_last_order( $this->customer_created['user_id'] );
		if ( $order instanceof WC_Order ) {
			$order_language = BWFAN_Common::get_order_language( $order );
			if ( ! empty( $order_language ) && isset( $this->template_data['lang'][ $order_language ] ) ) {
				$template_id = $this->template_data['lang'][ $order_language ];
			}

			$this->customer_created['order_id'] = $order->get_id();
			$this->customer_created['wc_order'] = $order;
			$this->customer_created['wc_order'] = BWFAN_Woocommerce_Compatibility::get_order_data( $order, '_woofunnel_cid' );
		}

		/** Create engagement tracking */
		$this->create_engagement_tracking( $template_id, $this->get_api_data(), $this->customer_created );
	}

	/**
	 * Get user last order
	 *
	 * @param $user_id
	 *
	 * @return mixed|WC_Order|null
	 */
	protected function get_user_last_order( $user_id ) {
		$args = array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing', 'on-hold' ), // You might adjust statuses
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		);

		$orders = wc_get_orders( $args );
		if ( empty( $orders ) ) {
			return false; // No orders found for the user
		}

		return $orders[0]; // Return the most recent order
	}
}
