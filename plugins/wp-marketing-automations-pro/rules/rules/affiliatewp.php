<?php

if ( bwfan_is_affiliatewp_active() ) {

	class BWFAN_Rule_Affiliate_Unpaid_Amount extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'affiliate_unpaid_amount' );
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result       = false;
			$affiliate_id = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : 0;
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return 0;
			}

			$total_earnings = 0;
			if ( ! empty( $affiliate->unpaid_earnings ) ) {
				$decimal        = apply_filters( 'bwfan_get_decimal_values', 2 );
				$total_earnings = round( $affiliate->unpaid_earnings, $decimal );
			}

			$value = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $total_earnings === $value;
					break;
				case '!=':
					$result = $total_earnings !== $value;
					break;
				case '>':
					$result = $total_earnings > $value;
					break;
				case '<':
					$result = $total_earnings < $value;
					break;
				case '>=':
					$result = $total_earnings >= $value;
					break;
				case '<=':
					$result = $total_earnings <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function is_match( $rule_data ) {
			$earnings = (float) $this->get_unpaid_amount();
			$value    = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $earnings === $value;
					break;
				case '!=':
					$result = $earnings !== $value;
					break;
				case '>':
					$result = $earnings > $value;
					break;
				case '<':
					$result = $earnings < $value;
					break;
				case '>=':
					$result = $earnings >= $value;
					break;
				case '<=':
					$result = $earnings <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_unpaid_amount() {
			$affiliate_id = BWFAN_Core()->rules->getRulesData( 'affiliate_id' );
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return 0;
			}

			$total_earnings = 0;
			if ( ! empty( $affiliate->unpaid_earnings ) ) {
				$decimal        = apply_filters( 'bwfan_get_decimal_values', 2 );
				$total_earnings = round( $affiliate->unpaid_earnings, $decimal );
			}

			return $total_earnings;
		}

		public function ui_view() {
			?>
            Affiliate Unpaid Earnings
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %>
            <%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

	class BWFAN_Rule_Affiliate_Total_Earnings extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'affiliate_total_earnings' );
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result       = false;
			$affiliate_id = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : 0;
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return 0;
			}

			$total_earnings = 0;
			$earnings       = $affiliate->unpaid_earnings + $affiliate->earnings;
			if ( ! empty( $earnings ) ) {
				$decimal        = apply_filters( 'bwfan_get_decimal_values', 2 );
				$total_earnings = round( $earnings, $decimal );
			}

			$value = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $total_earnings === $value;
					break;
				case '!=':
					$result = $total_earnings !== $value;
					break;
				case '>':
					$result = $total_earnings > $value;
					break;
				case '<':
					$result = $total_earnings < $value;
					break;
				case '>=':
					$result = $total_earnings >= $value;
					break;
				case '<=':
					$result = $total_earnings <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function is_match( $rule_data ) {
			$earnings = (float) $this->get_total_earnings();
			$value    = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $earnings === $value;
					break;
				case '!=':
					$result = $earnings !== $value;
					break;
				case '>':
					$result = $earnings > $value;
					break;
				case '<':
					$result = $earnings < $value;
					break;
				case '>=':
					$result = $earnings >= $value;
					break;
				case '<=':
					$result = $earnings <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_total_earnings() {
			$affiliate_id = BWFAN_Core()->rules->getRulesData( 'affiliate_id' );
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return 0;
			}

			$total_earnings = 0;
			$earnings       = $affiliate->unpaid_earnings + $affiliate->earnings;
			if ( ! empty( $earnings ) ) {
				$decimal        = apply_filters( 'bwfan_get_decimal_values', 2 );
				$total_earnings = round( $earnings, $decimal );
			}

			return $total_earnings;
		}

		public function ui_view() {
			?>
            Affiliate Total Earnings
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %>
            <%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

	class BWFAN_Rule_Affiliate_Total_Visits extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'affiliate_total_visits' );
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result       = false;
			$affiliate_id = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : 0;
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return 0;
			}

			$visits = (int) $affiliate->visits;
			$value  = (int) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $visits === $value;
					break;
				case '!=':
					$result = $visits !== $value;
					break;
				case '>':
					$result = $visits > $value;
					break;
				case '<':
					$result = $visits < $value;
					break;
				case '>=':
					$result = $visits >= $value;
					break;
				case '<=':
					$result = $visits <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match( $rule_data ) {
			$visits = (int) $this->get_visits();
			$value  = (int) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $visits === $value;
					break;
				case '!=':
					$result = $visits !== $value;
					break;
				case '>':
					$result = $visits > $value;
					break;
				case '<':
					$result = $visits < $value;
					break;
				case '>=':
					$result = $visits >= $value;
					break;
				case '<=':
					$result = $visits <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_visits() {
			$affiliate_id = BWFAN_Core()->rules->getRulesData( 'affiliate_id' );
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return 0;
			}

			return $affiliate->visits;
		}

		public function ui_view() {
			?>
            Affiliate Total Visits
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

	class BWFAN_Rule_Selected_Range_Referrals_Count extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'selected_range_referrals_count' );
			$this->description = __( 'is equal to', 'wp-marketing-automations-pro' );
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$referrals_count = (int) isset( $automation_data['global']['referral_count'] ) ? $automation_data['global']['referral_count'] : 0;
			$value           = (int) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $referrals_count === $value;
					break;
				case '!=':
					$result = $referrals_count !== $value;
					break;
				case '>':
					$result = $referrals_count > $value;
					break;
				case '<':
					$result = $referrals_count < $value;
					break;
				case '>=':
					$result = $referrals_count >= $value;
					break;
				case '<=':
					$result = $referrals_count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match( $rule_data ) {
			$referrals_count = (int) BWFAN_Core()->rules->getRulesData( 'referral_count' );
			$value           = (int) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $referrals_count === $value;
					break;
				case '!=':
					$result = $referrals_count !== $value;
					break;
				case '>':
					$result = $referrals_count > $value;
					break;
				case '<':
					$result = $referrals_count < $value;
					break;
				case '>=':
					$result = $referrals_count >= $value;
					break;
				case '<=':
					$result = $referrals_count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			?>
            Referral Count (Selected Frequency)
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %>
            <%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

	class BWFAN_Rule_Selected_Range_Visits extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'selected_range_visits' );
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$visits = (int) isset( $automation_data['global']['visits'] ) ? $automation_data['global']['visits'] : 0;
			$value  = (int) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $visits === $value;
					break;
				case '!=':
					$result = $visits !== $value;
					break;
				case '>':
					$result = $visits > $value;
					break;
				case '<':
					$result = $visits < $value;
					break;
				case '>=':
					$result = $visits >= $value;
					break;
				case '<=':
					$result = $visits <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match( $rule_data ) {
			$visits = (int) BWFAN_Core()->rules->getRulesData( 'visits' );
			$value  = (int) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $visits === $value;
					break;
				case '!=':
					$result = $visits !== $value;
					break;
				case '>':
					$result = $visits > $value;
					break;
				case '<':
					$result = $visits < $value;
					break;
				case '>=':
					$result = $visits >= $value;
					break;
				case '<=':
					$result = $visits <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			?>
            Referral Visits (Selected Frequency)
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %>
            <%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

	class BWFAN_Rule_Affiliate_Rate extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'affiliate_rate' );
			$this->description = 'Rate Type Percentage (%) only';
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$affiliate_id = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : 0;

			if ( empty( $affiliate_id ) ) {
				return false;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return $this->return_is_match( false, $rule_data );
			}

			$rate_type = affwp_get_affiliate_rate_type( $affiliate_id );
			if ( 'percentage' !== $rate_type ) {
				return $this->return_is_match( false, $rule_data );
			}

			$affiliate_rate = affwp_get_affiliate_rate( $affiliate );
			if ( ! empty( $affiliate_rate ) ) {
				$rate = $affiliate_rate * 100;
			}

			$value = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $rate === $value;
					break;
				case '!=':
					$result = $rate !== $value;
					break;
				case '>':
					$result = $rate > $value;
					break;
				case '<':
					$result = $rate < $value;
					break;
				case '>=':
					$result = $rate >= $value;
					break;
				case '<=':
					$result = $rate <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function is_match( $rule_data ) {
			$rate = (float) $this->get_affiliate_rate();
			if ( false === $rate ) {
				return false;
			}

			$value = (float) $rule_data['condition'];
			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $rate === $value;
					break;
				case '!=':
					$result = $rate !== $value;
					break;
				case '>':
					$result = $rate > $value;
					break;
				case '<':
					$result = $rate < $value;
					break;
				case '>=':
					$result = $rate >= $value;
					break;
				case '<=':
					$result = $rate <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_affiliate_rate() {
			$affiliate_id = BWFAN_Core()->rules->getRulesData( 'affiliate_id' );
			if ( empty( $affiliate_id ) ) {
				return false;
			}

			$affiliate = affwp_get_affiliate( $affiliate_id );
			if ( false === $affiliate ) {
				return false;
			}

			$rate_type = affwp_get_affiliate_rate_type( $affiliate_id );
			if ( 'percentage' !== $rate_type ) {
				return false;
			}

			$affiliate_rate = affwp_get_affiliate_rate( $affiliate );
			if ( ! empty( $affiliate_rate ) ) {
				return $affiliate_rate * 100;
			}

			return false;
		}

		public function ui_view() {
			?>
            Affiliate Rate
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %>
            <%= condition %>%
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

}
