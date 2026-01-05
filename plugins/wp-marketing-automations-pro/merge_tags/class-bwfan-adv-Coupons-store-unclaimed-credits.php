<?php
if ( ! class_exists( 'BWFAN_ADV_Coupons_Store_Unclaimed_Credits' ) ) {
	class BWFAN_ADV_Coupons_Store_Unclaimed_Credits extends BWFAN_Merge_Tag {

		private static $instance = null;

		public function __construct() {
			$this->tag_name        = 'store_unclaimed_credits';
			$this->tag_description = __( 'Store Unclaimed Credits', 'wp-marketing-automations-pro' );

			add_shortcode( 'bwfan_store_unclaimed_credits', array( $this, 'parse_shortcode' ) );

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

			/** if user id available then fetch the unclaimed credits and return */
			$user_id = isset( $get_data['user_id'] ) ? $get_data['user_id'] : '';
			if ( ! empty( $user_id ) ) {
				return $this->parse_shortcode_output( $this->fetch_user_unclaimed_credit( $user_id ), $attr );
			}

			/** if cid available then fetch user id and then unclaimed credits and return */
			$cid = isset( $get_data['contact_id'] ) ? $get_data['contact_id'] : '';
			if ( ! empty( $cid ) ) {
				$contact = new BWFCRM_Contact( $cid );
				if ( $contact instanceof BWFCRM_Contact && $contact->is_contact_exists() && ! empty( $contact->contact->get_wpid() ) ) {
					return $this->parse_shortcode_output( $this->fetch_user_unclaimed_credit( $contact->contact->get_wpid() ), $attr );
				}
			}

			/** if email available then fetch user id and then unclaimed credits and return */
			$email = isset( $get_data['email'] ) ? $get_data['email'] : '';
			if ( ! empty( $email ) ) {
				$user = get_user_by( 'email', $email );
				if ( $user instanceof WP_USER ) {
					return $this->parse_shortcode_output( $this->fetch_user_unclaimed_credit( $user->ID ), $attr );
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

			/** if user id found in order then fetch and return the unclaimed credit */
			$user_id = $order->get_user_id();
			if ( ! empty( $user_id ) ) {
				return $this->parse_shortcode_output( $this->fetch_user_unclaimed_credit( $user_id ), $attr );
			}

			/** if email not found in order then return empty */
			$email = $order->get_billing_email();
			if ( empty( $email ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			/** if user not found by email then return empty */
			$user = get_user_by( 'email', $email );
			if ( $user instanceof WP_USER ) {
				return $this->parse_shortcode_output( $this->fetch_user_unclaimed_credit( $user->ID ), $attr );
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

		/**
		 *
		 * Get user unclaimed credits
		 *
		 * @param $user_id
		 *
		 * @return int|mixed
		 */
		public function fetch_user_unclaimed_credit( $user_id ) {
			$credits = BWFAN_ADV_Coupon_Source::get_user_credits( $user_id );

			$increase_credits = ! empty( $credits ) && isset( $credits['increase'] ) ? floatval( $credits['increase'] ) : 0;
			$decrease_credits = ! empty( $credits ) && isset( $credits['decrease'] ) ? floatval( $credits['decrease'] ) : 0;

			return $increase_credits === 0 || 0 > ( $increase_credits - $decrease_credits ) ? 0 : ( $increase_credits - $decrease_credits );
		}
	}

	/**
	 * Register this merge tag to a group.
	 */
	if ( bwfan_is_advanced_coupon_for_woocommerce_active() ) {
		if ( version_compare( BWFAN_VERSION, '3.5.4', '>=' ) ) {
			BWFAN_Merge_Tag_Loader::register( 'bwfan_adv_coupon', 'BWFAN_ADV_Coupons_Store_Unclaimed_Credits', null, __( 'Advanced Coupons', 'wp-marketing-automations-pro' ) );
		} else {
			BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_ADV_Coupons_Store_Unclaimed_Credits', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
		}
	}
}
