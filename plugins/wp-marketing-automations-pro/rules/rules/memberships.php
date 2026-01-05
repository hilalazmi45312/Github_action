<?php

if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_membership_active() ) {
	class BWFAN_Rule_Membership_Has_Status extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'membership_has_status' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			$result = false;
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			/** @var WC_Memberships_User_Membership $membership */
			$membership = isset( $automation_data['global']['wc_user_membership_id'] ) && ! empty( $automation_data['global']['wc_user_membership_id'] ) ? wc_memberships_get_user_membership( $automation_data['global']['wc_user_membership_id'] ) : null;

			if ( ! $membership instanceof WC_Memberships_User_Membership ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$status = $membership->get_status();

			$selected_status = array_map( function ( $status ) {
				return $status['key'];
			}, $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case 'is':
					if ( is_array( $selected_status ) ) {
						$result = in_array( $status, $selected_status, true ) ? true : false;
					}
					break;
				case 'is_not':
					if ( is_array( $selected_status ) ) {
						$result = ! in_array( $status, $selected_status, true ) ? true : false;
					}
					break;
				default:
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		public function get_possible_rule_values() {
			$statuses           = wc_memberships_get_user_membership_statuses( false, false );
			$statuses_to_return = array();
			foreach ( $statuses as $status ) {
				$statuses_to_return[ $status ] = wc_memberships_get_user_membership_status_name( $status );
			}

			return $statuses_to_return;
		}

		public function is_match( $rule_data ) {
			$result = false;
			$status = BWFAN_Core()->rules->getRulesData( 'wc_user_membership_status' );
			if ( empty( $status ) ) {
				$membership_id = BWFAN_Core()->rules->getRulesData( 'wc_user_membership_id' );
				/** @var WC_Memberships_User_Membership $membership */
				$membership = ! empty( $membership_id ) ? wc_memberships_get_user_membership( $membership_id ) : '';
				if ( empty( $membership ) ) {
					return $this->return_is_match( $result, $rule_data );
				}

				$status = $membership->get_status();
			}

			$type = $rule_data['operator'];

			switch ( $type ) {
				case 'is':
					if ( is_array( $rule_data['condition'] ) ) {
						$result = in_array( $status, $rule_data['condition'], true ) ? true : false;
					}
					break;
				case 'is_not':
					if ( is_array( $rule_data['condition'] ) ) {
						$result = ! in_array( $status, $rule_data['condition'], true ) ? true : false;
					}
					break;
				default:
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Membership status ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition %>
			<?php
		}
	}

	class BWFAN_Rule_Active_Membership_Plans extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'active_membership_plans' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values( $term );
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			$result = false;
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			/** @var  $memberships get user all memberships instead of membership object with active status */
			$memberships = wc_memberships_get_user_memberships( $automation_data['global']['user_id'], array( 'status' => 'wcm-active' ) );

			/** empty $memberships then return false **/
			if ( empty( $memberships ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$plans = array();
			foreach ( $memberships as $membership ) {
				/** @var WC_Memberships_User_Membership $membership */
				if ( ! $membership instanceof WC_Memberships_User_Membership ) {
					continue;
				}
				$plan = $membership->get_plan();
				if ( ! $plan instanceof WC_Memberships_Membership_Plan ) {
					continue;
				}
				$plans[] = $plan->get_id();
			}

			/** empty $plans then return false **/
			if ( empty( $plans ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$selected_plans = array_map( function ( $plan ) {
				return absint( $plan['key'] );
			}, $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case 'is':
					if ( is_array( $selected_plans ) && is_array( $plans ) ) {
						$result = count( array_intersect( $plans, $selected_plans ) ) === count( $selected_plans );
					}
					break;
				case 'is_not':
					if ( is_array( $selected_plans ) && is_array( $plans ) ) {
						$result = count( array_intersect( $plans, $selected_plans ) ) === 0;
					}
					break;
				default:
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		public function get_possible_rule_values( $term = '' ) {
			$args = array();
			if ( ! empty( $term ) ) {
				$args['s'] = sanitize_text_field( $term );
			}
			$plans           = wc_memberships_get_membership_plans( $args );
			$plans_to_return = array();
			foreach ( $plans as $plan ) {
				$plans_to_return[ $plan->get_id() ] = $plan->get_name();
			}

			return $plans_to_return;
		}

		public function is_match( $rule_data ) {
			$result  = false;
			$plan_id = BWFAN_Core()->rules->getRulesData( 'wc_membership_plan_id' );
			$plan    = ! empty( $plan_id ) ? wc_memberships_get_membership_plan( $plan_id ) : '';
			if ( empty( $plan ) ) {
				$membership_id = BWFAN_Core()->rules->getRulesData( 'wc_user_membership_id' );
				/** @var WC_Memberships_User_Membership $membership */
				$membership = ! empty( $membership_id ) ? wc_memberships_get_user_membership( $membership_id ) : '';
				if ( empty( $membership ) ) {
					return $this->return_is_match( $result, $rule_data );
				}

				$plan = $membership->get_plan();
			}

			$plan = $plan->get_id();
			$type = $rule_data['operator'];

			switch ( $type ) {
				case 'is':
					if ( is_array( $rule_data['condition'] ) ) {
						$condition = array_map( 'absint', $rule_data['condition'] );
						$result    = in_array( absint( $plan ), $condition, true ) ? true : false;
					}
					break;
				case 'is_not':
					if ( is_array( $rule_data['condition'] ) ) {
						$result = ! in_array( $plan, $rule_data['condition'], true ) ? true : false;
					}
					break;
				default:
					break;

			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Membership plan ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition %>
			<?php
		}
	}
}
