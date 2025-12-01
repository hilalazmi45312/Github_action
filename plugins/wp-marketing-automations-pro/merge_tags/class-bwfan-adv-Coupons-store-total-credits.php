<?php
if ( ! class_exists( 'BWFAN_ADV_Coupons_Store_Total_Credits' ) ) {
	class BWFAN_ADV_Coupons_Store_Total_Credits extends BWFAN_Merge_Tag {

		private static $instance = null;

		public function __construct() {
			$this->tag_name        = 'store_total_credits';
			$this->tag_description = __( 'Store Total Credits', 'wp-marketing-automations-pro' );

			add_shortcode( 'bwfan_store_total_credits', array( $this, 'parse_shortcode' ) );

			$this->support_fallback = false;
			$this->priority         = 2;
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
		 * @return int|mixed|void
		 */
		public function parse_shortcode( $attr ) {
			$get_data = BWFAN_Merge_Tag_Loader::get_data();
			if ( true === $get_data['is_preview'] ) {
				return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
			}

			/** if total_credit available then return */
			$total_credit = isset( $get_data['amount'] ) ? $get_data['amount'] : null;
			if ( ! is_null( $total_credit ) ) {
				return $this->parse_shortcode_output( $total_credit, $attr );
			}

			/** if user id available then fetch the credits and return */
			$user_id = isset( $get_data['user_id'] ) ? $get_data['user_id'] : '';
			if ( ! empty( $user_id ) ) {
				return $this->parse_shortcode_output( BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user_id ), $attr );
			}

			/** if cid available then fetch user id and then credits and return */
			$cid = isset( $get_data['contact_id'] ) ? $get_data['contact_id'] : '';
			if ( ! empty( $cid ) ) {
				$contact = new BWFCRM_Contact( $cid );
				if ( $contact instanceof BWFCRM_Contact && $contact->is_contact_exists() && ! empty( $contact->contact->get_wpid() ) ) {
					return $this->parse_shortcode_output( BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $contact->contact->get_wpid() ), $attr );
				}
			}

			/** if email available then fetch user id and then credits and return */
			$email = isset( $get_data['email'] ) ? $get_data['email'] : '';
			if ( ! empty( $email ) ) {
				$user = get_user_by( 'email', $email );
				if ( $user instanceof WP_USER ) {
					return $this->parse_shortcode_output( BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user->ID ), $attr );
				}
			}

			/** handling by order start */
			/**  if order id not available then return empty */
			$order_id = isset( $get_data['order_id'] ) ? $get_data['order_id'] : '';
			if ( empty( $order_id ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			/** if not the instance of order then return empty */
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			/** if user id found in order then fetch and return the credit */
			$user_id = $order->get_user_id();
			if ( ! empty( $user_id ) ) {
				return $this->parse_shortcode_output( BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user_id ), $attr );
			}

			/** if email not found in order then return empty */
			$email = $order->get_billing_email();
			if ( empty( $email ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			/** if user not found by email then return empty */
			$user = get_user_by( 'email', $email );
			if ( $user instanceof WP_USER ) {
				return $this->parse_shortcode_output( BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user->ID ), $attr );
			}

			return $this->parse_shortcode_output( '', $attr );
		}

		/**
		 * Show dummy value
		 *
		 * @return integer
		 */
		public function get_dummy_preview() {
			return '10';
		}
	}

	/**
	 * Register this merge tag to a group.
	 */
	if ( bwfan_is_advanced_coupon_for_woocommerce_active() ) {
		if ( version_compare( BWFAN_VERSION, '3.5.4', '>=' ) ) {
			BWFAN_Merge_Tag_Loader::register( 'bwfan_adv_coupon', 'BWFAN_ADV_Coupons_Store_Total_Credits', null, __( 'Advanced Coupons', 'wp-marketing-automations-pro' ) );
		} else {
			BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_ADV_Coupons_Store_Total_Credits', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
		}
	}
}
