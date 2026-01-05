<?php

if ( ! class_exists( 'BWFAN_Rule_Stripe_offers' ) ) {
	class BWFAN_Rule_Stripe_offers extends BWFAN_Rule_Base {


		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;
			parent::__construct( 'stripe_offers' );
		}

		/** v2 Methods: START */

		public function get_options( $search = '' ) {

			return BWFAN_PRO_Common::get_offers( $search );
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$offer_id        = isset( $automation_data['global']['object_id'] ) ? $automation_data['global']['object_id'] : 0;
			$offer_ids       = array( $offer_id );
			$selected_offers = array_map( function ( $offer ) {
				return $offer['key'];
			}, $rule_data['data'] );

			$result = false;
			switch ( $rule_data['rule'] ) {
				case 'any':
					if ( is_array( $selected_offers ) && is_array( $offer_ids ) ) {
						$result = count( array_intersect( $selected_offers, $offer_ids ) ) >= 1;
					}
					break;

				case 'none':
					if ( is_array( $selected_offers ) && is_array( $offer_ids ) ) {
						$result = count( array_intersect( $selected_offers, $offer_ids ) ) === 0;
					}
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_possible_rule_operators() {
			return array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);
		}

	}
}
if ( ! class_exists( 'BWFAN_Stripe_Bumps' ) ) {
	class BWFAN_Stripe_Bumps extends BWFAN_Rule_Base {


		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;
			parent::__construct( 'stripe_bumps' );
		}

		/** v2 Methods: START */

		public function get_options( $search = '' ) {

			return BWFAN_PRO_Common::get_bumps( $search );
		}

		public function get_rule_type() {
			return 'Search';
		}


		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$bump_id        = isset( $automation_data['global']['object_id'] ) ? $automation_data['global']['object_id'] : 0;
			$bump_ids       = array( $bump_id );
			$selected_bumps = array_map( function ( $offer ) {
				return $offer['key'];
			}, $rule_data['data'] );

			$result = false;
			switch ( $rule_data['rule'] ) {
				case 'any':
					if ( is_array( $selected_bumps ) && is_array( $bump_ids ) ) {
						$result = count( array_intersect( $selected_bumps, $bump_ids ) ) >= 1;
					}
					break;

				case 'none':
					if ( is_array( $selected_bumps ) && is_array( $bump_ids ) ) {
						$result = count( array_intersect( $selected_bumps, $bump_ids ) ) === 0;
					}
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_possible_rule_operators() {
			return array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);
		}

	}
}
