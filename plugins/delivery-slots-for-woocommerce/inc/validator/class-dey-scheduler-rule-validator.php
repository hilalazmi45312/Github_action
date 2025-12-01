<?php
/**
 * Validator - Scheduler rule.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Scheduler_Rule_Validator' ) ) {

	/**
	 * Class.
	 *
	 * @since 4.0.0
	 */
	class DEY_Scheduler_Rule_Validator {

		/**
		 * Rules group.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private $rules_group;

		/**
		 * Rules.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private $rules;

		/**
		 * Rule.
		 *
		 * @since 4.0.0
		 * @var object
		 */
		private $rule;

		/**
		 * Scheduler rule.
		 *
		 * @since 4.0.0
		 * @var object
		 */
		private $scheduler_rule;

		/**
		 * Class initialization.
		 *
		 * @since 4.0.0
		 * @param object $scheduler_rule Scheduler rule object.
		 */
		public function __construct( $scheduler_rule ) {
			$this->scheduler_rule = ! is_object( $scheduler_rule ) ? dey_get_scheduler_rule( $scheduler_rule ) : $scheduler_rule;

			$this->rules_group = array_filter( (array) $this->scheduler_rule->get_restriction_rule_groups() );
		}

		/**
		 * Is valid?
		 *
		 * @since 4.0.0
		 * @param object $scheduler_rule Scheduler rule object.
		 * @return boolean
		 */
		public static function is_valid( $scheduler_rule ) {
			$self = new self( $scheduler_rule );

			return $self->validate();
		}

		/**
		 * Validate the scheduler rule.
		 *
		 * @since 4.0.0
		 * @return boolean
		 */
		private function validate() {
			// Return if the end date or rule start date is started.
			if ( $this->get_start_date() && DEY_Date_Time::get_date_time_object( 'now' )->format( 'Y-m-d' ) < $this->get_start_date()->format( 'Y-m-d' ) ) {
				return false;
			}

			// Return if the end date or rule expiry date is expired.
			if ( $this->get_end_date() && DEY_Date_Time::get_date_time_object( 'now' )->format( 'Y-m-d' ) > $this->get_end_date()->format( 'Y-m-d' ) ) {
				return false;
			}

			// Omit the validation if the rules are not configured.
			if ( ! dey_check_is_array( $this->rules_group ) ) {
				return true;
			}

			$return = true;
			foreach ( $this->rules_group as $rules ) {
				if ( ! dey_check_is_array( $rules ) ) {
					continue;
				}

				$this->rules = $rules;
				if ( $this->validate_rules() ) {
					continue;
				}

				$return = false;
				break;
			}

			/**
			 * This hook is used to validate the scheduler rule groups.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_scheduler_rules_group', $return, $this );
		}

		/**
		 * Get the rule start date.
		 *
		 * @since 4.0.0
		 * @return object
		 */
		public function get_start_date() {
			return ! empty( $this->scheduler_rule->get_start_date() ) ? DEY_Date_Time::get_date_time_object( $this->scheduler_rule->get_start_date() ) : false;
		}

		/**
		 * Get the rule end date.
		 *
		 * @since 4.0.0
		 * @return object
		 */
		public function get_end_date() {
			return ! empty( $this->scheduler_rule->get_end_date() ) ? DEY_Date_Time::get_date_time_object( $this->scheduler_rule->get_end_date() ) : false;
		}

		/**
		 * Validate the rules.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_rules() {
			$return = true;
			foreach ( $this->rules as $rule ) {
				if ( ! dey_check_is_array( $rule ) ) {
					continue;
				}

				$rule       = dey_format_restriction_rule_data( $rule );
				$this->rule = (object) $rule;
				if ( $this->validate_rule() ) {
					continue;
				}

				$return = false;
				break;
			}

			/**
			 * This hook is used to check if the rules is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_scheduler_rules', $return, $this->rules, $this );
		}

		/**
		 * Validate the rule.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_rule() {
			switch ( $this->rule->rule_type ) {
				case '2': // User.
					$return = $this->validate_user_type();
					break;

				case '3': // Order.
					$return = $this->validate_order_type();
					break;

				default: // Product.
					$return = $this->validate_product_type();
					break;
			}

			/**
			 * This hook is used to check if the rule is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_scheduler_rule', $return, $this->rule, $this );
		}

		/**
		 * Validate the product type.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_product_type() {
			/**
			 * This hook is used to check if the rule product type is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_scheduler_rule_product_type', $this->validate_product_type_rule(), $this->rule, $this );
		}

		/**
		 * Validate the product type rule.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_product_type_rule() {
			$cart_contents = is_object( WC()->cart ) ? WC()->cart->get_cart() : array();
			if ( ! dey_check_is_array( $cart_contents ) ) {
				return false;
			}

			$is_valid = false;
			foreach ( $cart_contents as $cart_content ) {
				switch ( $this->rule->product_type ) {
					// Included products.
					case '1':
						if ( in_array( $cart_content['variation_id'], $this->rule->products ) || in_array( $cart_content['product_id'], $this->rule->products ) ) {
							return true;
						}
						break;

					// Excluded products.
					case '2':
						$is_valid = true;
						if ( in_array( $cart_content['variation_id'], $this->rule->products ) || in_array( $cart_content['product_id'], $this->rule->products ) ) {
							return false;
						}
						break;

					// Included categories.
					case '3':
						$product_categories = get_the_terms( $cart_content['product_id'], 'product_cat' );
						if ( dey_check_is_array( $product_categories ) ) {
							foreach ( $product_categories as $product_category ) {
								if ( in_array( $product_category->term_id, $this->rule->categories ) ) {
									return true;
								}
							}
						}
						break;

					// Excluded categories.
					case '4':
						$is_valid           = true;
						$product_categories = get_the_terms( $cart_content['product_id'], 'product_cat' );
						if ( dey_check_is_array( $product_categories ) ) {
							foreach ( $product_categories as $product_category ) {
								if ( in_array( $product_category->term_id, $this->rule->categories ) ) {
									return false;
								}
							}
						}
						break;

					// Included product types.
					case '5':
						if ( in_array( $cart_content['data']->get_type(), $this->rule->product_types ) ) {
							return true;
						}
						break;

					// Excluded product types.
					case '6':
						$is_valid = true;
						if ( in_array( $cart_content['data']->get_type(), $this->rule->product_types ) ) {
							return false;
						}
						break;

					// Included product tags.
					case '7':
						if ( has_term( $this->rule->tags, 'product_tag', $cart_content['product_id'] ) ) {
							return true;
						}
						break;

					// Excluded product tags.
					case '8':
						if ( has_term( $this->rule->tags, 'product_tag', $cart_content['product_id'] ) ) {
							return false;
						}
						break;
				}
			}

			return $is_valid;
		}

		/**
		 * Validate the user type.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_user_type() {
			$return          = false;
			$current_country = dey_get_current_user_country();
			switch ( $this->rule->user_type ) {
				case '1':
					if ( in_array( $current_country, $this->rule->countries ) ) {
						$return = true;
					}
					break;

				case '2':
					$return = true;
					if ( in_array( $current_country, $this->rule->countries ) ) {
						$return = false;
					}
					break;
			}

			/**
			 * This hook is used to check if the rule user type is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_scheduler_rule_user_type', $return, $this->rule, $this );
		}

		/**
		 * Validate the order type.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_order_type() {
			$return = false;
			switch ( $this->rule->order_type ) {
				case '1': // Cart subtotal is less than or equal to.
					if ( $this->rule->price && floatval( $this->rule->price ) >= dey_get_wc_cart_subtotal() ) {
						$return = true;
					}
					break;

				case '2': // Cart subtotal is greater than or equal to.
					if ( $this->rule->price && floatval( $this->rule->price ) <= dey_get_wc_cart_subtotal() ) {
						$return = true;
					}
					break;

				case '3': // Order total is less than or equal to.
					if ( $this->rule->price && floatval( $this->rule->price ) >= dey_get_wc_cart_total() ) {
						$return = true;
					}
					break;

				case '4': // Order total is greater than or equal to.
					if ( $this->rule->price && floatval( $this->rule->price ) <= dey_get_wc_cart_total() ) {
						$return = true;
					}
					break;

				case '5': // Number of products in cart less than or equal to.
					if ( $this->rule->count && $this->rule->count >= dey_get_cart_item_count() ) {
						$return = true;
					}
					break;

				case '6': // Number of products in cart greater than or equal to.
					if ( $this->rule->count && $this->rule->count <= dey_get_cart_item_count() ) {
						$return = true;
					}
					break;
			}

			/**
			 * This hook is used to check if the rule order type is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_scheduler_rule_order_type', $return, $this->rule, $this );
		}
	}
}
