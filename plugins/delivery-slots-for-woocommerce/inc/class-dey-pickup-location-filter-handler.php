<?php

/**
 *  Handles the pickup location filter.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Pickup_Location_Filter_Handler' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Pickup_Location_Filter_Handler {

		/**
		 * Pickup Location.
		 *
		 * @var object
		 */
		public $pickup_location;

		/**
		 * Filters.
		 *
		 * @var array
		 */
		protected $filters;

		/**
		 * Filter.
		 *
		 * @var object
		 */
		protected $filter;

		/**
		 * Class Initialization.
		 */
		public function __construct( &$pickup_location ) {
			$this->pickup_location = $pickup_location;
		}

		/**
		 * Is valid filter?.
		 *
		 * @return bool
		 */
		public static function valid( &$pickup_location ) {
			$filter = new self( $pickup_location );

			return $filter->validate_filter_group();
		}

		/**
		 * Validate the filter group.
		 *
		 * @return bool
		 */
		public function validate_filter_group() {
			// Return true if the filters are not configured.
			$filters_groups = $this->pickup_location->get_filter_groups();
			if ( ! dey_check_is_array( $filters_groups ) ) {
				return true;
			}

			$return = false;
			foreach ( $filters_groups as $filters ) {
				if ( ! dey_check_is_array( $filters ) ) {
					continue;
				}

				$this->filters = $filters;
				if ( ! $this->validate_filters() ) {
					continue;
				}

				$return = true;
				break;
			}

			/**
			 * This hook is used to validate the pickup location filter groups.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_validate_pickup_location_filter_group', $return, $this->filter, $this->pickup_location );
		}

		/**
		 * Validate the filters.
		 *
		 * @return bool
		 */
		public function validate_filters() {
			$return = true;

			foreach ( $this->filters as $filter ) {
				if ( ! dey_check_is_array( $filter ) ) {
					continue;
				}

				$filter       = dey_get_filter_rule_default_data( $filter );
				$this->filter = (object) $filter;
				if ( $this->validate_rule() ) {
					continue;
				}

				$return = false;
				break;
			}

			/**
			 * This hook is used to validate the pickup location filters.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_validate_pickup_location_filters', $return, $this->filter, $this->pickup_location );
		}

		/**
		 * Validate the rule.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_rule() {
			$return = true;

			switch ( $this->filter->rule_type ) {
				case '2':
					$return = $this->validate_shipping_method();
					break;

				default:
					$return = $this->validate_product_type();
					break;
			}

			/**
			 * This hook is used to check if the rule is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_pickup_location_rule', $return, $this->rule, $this );
		}

		/**
		 * Validate the shipping method.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function validate_shipping_method() {
			$return                    = true;
			$chosen_shipping_method_id = dey_get_chosen_shipping_method();
			if ( $chosen_shipping_method_id && dey_check_is_array( $this->filter->shipping_methods ) ) {
				if ( ! in_array( $chosen_shipping_method_id, $this->filter->shipping_methods ) ) {
					$return = false;
				}
			}

			/**
			 * This hook is used to check if the shipping method is valid.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_validate_pickup_location_shipping_method', $return, $this->rule, $this );
		}

		/**
		 * Validate the Products/Categories.
		 *
		 * @return bool
		 */
		public function validate_product_type() {
			// Return true if the cart object is not initialized.
			if ( ! is_object( WC()->cart ) ) {
				return true;
			}

			$cart_contents = WC()->cart->get_cart();
			if ( ! dey_check_is_array( $cart_contents ) ) {
				return true;
			}

			$return = false;
			foreach ( $cart_contents as $cart_content ) {

				switch ( $this->filter->product_type ) {
					case '1':
						// Return true if any selected products in the cart.
						if ( in_array( $cart_content['product_id'], $this->filter->products ) || in_array( $cart_content['variation_id'], $this->filter->products ) ) {
							return true;
						}

						break;
					case '2':
						$return = true;
						// Return false if any selected products in the cart.
						if ( in_array( $cart_content['product_id'], $this->filter->products ) || in_array( $cart_content['variation_id'], $this->filter->products ) ) {
							return false;
						}
						break;
					case '3':
						// Included categories.
						$product_categories = get_the_terms( $cart_content['product_id'], 'product_cat' );

						if ( dey_check_is_array( $product_categories ) ) {
							foreach ( $product_categories as $product_category ) {
								$category_ids[] = $product_category->term_id;
								// Return true if any selected products of category in the cart.
								if ( in_array( $product_category->term_id, $this->filter->categories ) ) {
									return true;
								}
							}
						}
						break;
					case '4':
						// Excluded categories.
						$return             = true;
						$product_categories = get_the_terms( $cart_content['product_id'], 'product_cat' );
						if ( dey_check_is_array( $product_categories ) ) {
							foreach ( $product_categories as $product_category ) {
								// Return false if any selected products of category in the cart.
								if ( in_array( $product_category->term_id, $this->filter->categories ) ) {
									return false;
								}
							}
						}
						break;
					case '5':
						// Return true if any selected product types is exists.
						if ( in_array( $cart_content['data']->get_type(), $this->filter->product_types ) || in_array( $cart_content['data']->get_type(), $this->filter->product_types ) ) {
							return true;
						}

						break;
					case '6':
						$return = true;
						// Return false if any selected types is exists.
						if ( in_array( $cart_content['data']->get_type(), $this->filter->product_types ) || in_array( $cart_content['data']->get_type(), $this->filter->product_types ) ) {
							return false;
						}
						break;
				}
			}
			/**
			 * This hook is used to validate the pickup location filter product category.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_validate_filter_product_category', $return, $this->filter, $this->pickup_location );
		}
	}

}
