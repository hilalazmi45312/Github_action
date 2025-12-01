<?php

class BWFAN_Rule_Credit_Amount extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'credit_amount' );
	}

	/** v2 Methods: START */

	public function get_rule_type() {
		return 'Number';
	}

	/** v2 Methods: START */

	public function is_match_v2( $automation_data, $rule_data ) {
		$total_credits = isset( $automation_data['global']['amount'] ) ? $automation_data['global']['amount'] : null;
		$value         = (float) $rule_data['data'];

		/** If total credits amount is set using Adv coupons events */
		if ( ! is_null( $total_credits ) ) {
			$result = $this->quick_match( $total_credits, $value, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		/** If user id */
		$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : '';
		if ( ! empty( $user_id ) ) {
			$total_credits = BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user_id );
			$result        = $this->quick_match( $total_credits, $value, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		/** If CID */
		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : '';
		if ( ! empty( $cid ) ) {
			$contact = new BWFCRM_Contact( $cid );
			if ( $contact instanceof BWFCRM_Contact && $contact->is_contact_exists() && ! empty( $contact->contact->get_wpid() ) ) {
				$total_credits = BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $contact->contact->get_wpid() );
				$result        = $this->quick_match( $total_credits, $value, $rule_data['rule'] );

				return $this->return_is_match( $result, $rule_data );
			}
		}

		/** If email */
		$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		if ( ! empty( $email ) && is_email( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user instanceof WP_USER ) {
				$total_credits = BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user->ID );
				$result        = $this->quick_match( $total_credits, $value, $rule_data['rule'] );

				return $this->return_is_match( $result, $rule_data );
			}
		}

		/** WC order cases */
		$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : '';
		if ( empty( $order_id ) ) {
			return $this->return_is_match( 0, $rule_data );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->return_is_match( 0, $rule_data );
		}

		$user_id = $order->get_user_id();
		if ( ! empty( $user_id ) ) {
			$total_credits = BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user_id );
			$result        = $this->quick_match( $total_credits, $value, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		$email = $order->get_billing_email();
		if ( ! empty( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user instanceof WP_USER ) {
				$total_credits = BWFAN_ADV_Coupon_Source::fetch_user_total_credit( $user_id );
				$result        = $this->quick_match( $total_credits, $value, $rule_data['rule'] );

				return $this->return_is_match( $result, $rule_data );
			}
		}

		return $this->return_is_match( 0, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
		);
	}

	protected function quick_match( $amount, $value, $operator ) {
		$amount = empty( $amount ) ? 0 : $amount;
		$amount = (float) $amount;

		$result = false;
		switch ( $operator ) {
			case '==':
				$result = $amount === $value;
				break;
			case '!=':
				$result = $amount !== $value;
				break;
			case '>':
				$result = $amount > $value;
				break;
			case '<':
				$result = $amount < $value;
				break;
			case '>=':
				$result = $amount >= $value;
				break;
			case '<=':
				$result = $amount <= $value;
				break;
			default:
				break;
		}

		return $result;
	}
}

if ( bwfan_is_loyalty_program_for_woocommerce_active() ) {
	class BWFAN_Rule_Loyalty_Points extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'loyalty_points' );
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		/** v2 Methods: START */

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$saved_value = (float) $rule_data['data'];

			/** If user id */
			$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			if ( ! empty( $user_id ) ) {
				$result = $this->quick_match( $user_id, $saved_value, $rule_data['rule'] );

				return $this->return_is_match( $result, $rule_data );
			}

			/** If CID */
			$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : '';
			if ( ! empty( $cid ) ) {
				$contact = new BWFCRM_Contact( $cid );
				if ( $contact instanceof BWFCRM_Contact && $contact->is_contact_exists() && ! empty( $contact->contact->get_wpid() ) ) {
					$result = $this->quick_match( $contact->contact->get_wpid(), $saved_value, $rule_data['rule'] );

					return $this->return_is_match( $result, $rule_data );
				}
			}

			/** If email */
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
			if ( ! empty( $email ) && is_email( $email ) ) {
				$user = get_user_by( 'email', $email );
				if ( $user instanceof WP_USER ) {
					$result = $this->quick_match( $user->ID, $saved_value, $rule_data['rule'] );

					return $this->return_is_match( $result, $rule_data );
				}
			}

			/** WC order cases */
			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : '';
			if ( empty( $order_id ) ) {
				return $this->return_is_match( 0, $rule_data );
			}

			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return $this->return_is_match( 0, $rule_data );
			}

			$user_id = $order->get_user_id();
			if ( ! empty( $user_id ) ) {
				$result = $this->quick_match( $user_id, $saved_value, $rule_data['rule'] );

				return $this->return_is_match( $result, $rule_data );
			}

			$email = $order->get_billing_email();
			if ( ! empty( $email ) ) {
				$user = get_user_by( 'email', $email );
				if ( $user instanceof WP_USER ) {
					$result = $this->quick_match( $user->ID, $saved_value, $rule_data['rule'] );

					return $this->return_is_match( $result, $rule_data );
				}
			}

			return $this->return_is_match( 0, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}

		protected function quick_match( $user_id, $value, $operator ) {
			if ( empty( $user_id ) ) {
				return false;
			}

			$points = get_user_meta( $user_id, '_acfw_loyalprog_user_total_points', true );
			$points = (float) $points;

			$result = false;
			switch ( $operator ) {
				case '==':
					$result = $points === $value;
					break;
				case '!=':
					$result = $points !== $value;
					break;
				case '>':
					$result = $points > $value;
					break;
				case '<':
					$result = $points < $value;
					break;
				case '>=':
					$result = $points >= $value;
					break;
				case '<=':
					$result = $points <= $value;
					break;
				default:
					break;
			}

			return $result;
		}
	}
}
