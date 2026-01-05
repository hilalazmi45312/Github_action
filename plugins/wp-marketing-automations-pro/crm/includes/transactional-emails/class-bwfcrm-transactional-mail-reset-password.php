<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Reset_Password extends BWFCRM_Transactional_Mail_Base {
	public function __construct( $load_hooks = false, $data = [] ) {
		$this->slug            = 'reset_password';
		$this->name            = __( 'Reset Password', 'wp-marketing-automations-pro' );
		$this->description     = __( 'Customer "reset password" emails are sent when customers reset their passwords.', 'wp-marketing-automations-pro' );
		$this->priority        = 18;
		$this->recipient       = 'customer';
		$this->subject         = __( 'Password Reset Request for ', 'wp-marketing-automations-pro' ) . ' {{store_name}}';
		$this->merge_tag_group = [
			'bwf_contact',
			'bwf_user',
		];
		$this->template_data   = $data;
		$this->wc_mail_id      = 'customer_reset_password';
		$this->set_data_by_template_data();
		if ( $load_hooks && self::is_valid() ) {
			/** Add restriction hook to stop default mail */
			add_filter( 'woocommerce_email_enabled_customer_reset_password', '__return_false' );

			/** Add action to send mail and set_data function hit */
			add_action( 'woocommerce_reset_password_notification', array( $this, 'bwf_reset_password_mail' ), 10, 2 );
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
	 * Customer reset account email.
	 *
	 * @param string $user_login User login.
	 * @param string $reset_key Password reset key.
	 */
	public function bwf_reset_password_mail( $user_login = '', $reset_key = '' ) {
		/** Return if template id or order id is empty */
		if ( empty( $this->template_data['template_id'] ) || ! $user_login ) {
			return;
		}

		$user = get_user_by( 'login', $user_login );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$email = $user->user_email;

		$set_data = array(
			'email'          => $email,
			'user_id'        => $user->ID,
			'oid'            => $user->ID,
			'user_reset_key' => $reset_key,
		);

		/** Get template id */
		$template_id = intval( $this->template_data['template_id'] );

		/** Check for users last order */
		$order = $this->get_user_last_order( $user->ID );
		if ( $order instanceof WC_Order ) {
			$order_language = BWFAN_Common::get_order_language( $order );
			if ( ! empty( $order_language ) && isset( $this->template_data['lang'][ $order_language ] ) ) {
				$template_id = $this->template_data['lang'][ $order_language ];
			}

			$set_data['order_id'] = $order->get_id();
			$set_data['wc_order'] = $order;
		}

		/** Create engagement tracking */
		$this->create_engagement_tracking( $template_id, $this->get_api_data(), $set_data );
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
