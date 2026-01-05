<?php
if ( bwfan_is_woocommerce_active() && bwfan_is_ti_wc_wishlist_active() ) {
	class BWFAN_Rule_Ti_Wc_wishlist_Item extends BWFAN_Rule_Products {
		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;

			parent::__construct( 'ti_wc_wishlist_item' );
		}

		/**
		 * Common function for v1 and v2 to define the rule operators
		 * @return array
		 */
		public function get_possible_rule_operators() {
			return array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);
		}

		/**
		 * @param $term
		 *
		 * Getting the field options for the view
		 *
		 * @return array
		 */
		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		/**
		 * @param $automation_data
		 * @param $rule_data
		 *
		 * Processing the conditions for v2
		 *
		 * @return bool
		 */
		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$data = $rule_data['data'];
			if ( ! is_array( $data ) && empty( $data ) ) {
				return false;
			}

			$wishlist_id = isset( $automation_data['global']['wishlist_id'] ) ? $automation_data['global']['wishlist_id'] : '';
			if ( empty( $wishlist_id ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			// Fetch wishlist data using the function
			$wishlist_data = BWFAN_PRO_Common::get_ti_wc_wishlist_data_by_id( $wishlist_id );
			if ( ! $wishlist_data ) {
				return $this->return_is_match( false, $rule_data );
			}

			$wishlist_product = isset( $wishlist_data['product_ids'] ) ? $wishlist_data['product_ids'] : [];

			if ( empty( $wishlist_product ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$saved_ids = array_column( $rule_data['data'], 'key' );
			$result    = $this->validate_set( $saved_ids, $wishlist_product, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		public function validate_set( $saved_products, $product_found, $operator ) {
			$result = false;
			switch ( $operator ) {
				case 'any':
					$result = count( array_intersect( $saved_products, $product_found ) ) > 0;
					break;
				case 'all':
					$result = count( array_intersect( $saved_products, $product_found ) ) === count( $saved_products );
					break;
				case 'none':
					$result = count( array_intersect( $saved_products, $product_found ) ) === 0;
					break;
			}

			return $result;
		}
	}

	class BWFAN_Rule_Ti_Wc_wishlist_Item_Count extends BWFAN_Rule_Products {
		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;

			parent::__construct( 'ti_wc_wishlist_item_count' );
		}

		/**
		 * Common function for v1 and v2 to define the rule operators
		 * @return array
		 */
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

		/**
		 * @param $term
		 *
		 * Getting the field options for the view
		 *
		 * @return array
		 */
		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Number';
		}

		/**
		 * @param $automation_data
		 * @param $rule_data
		 *
		 * Processing the conditions for v2
		 *
		 * @return bool
		 */
		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}
			$data = $rule_data['data'];
			if ( ! is_array( $data ) && empty( $data ) ) {
				return false;
			}

			$wishlist_id = isset( $automation_data['global']['wishlist_id'] ) ? $automation_data['global']['wishlist_id'] : '';
			if ( empty( $wishlist_id ) ) {
				return $this->return_is_match( false, $rule_data );
			}
			$wishlist_data = BWFAN_PRO_Common::get_ti_wc_wishlist_data_by_id( $wishlist_id );
			if ( ! $wishlist_data ) {
				return $this->return_is_match( false, $rule_data );
			}
			$product_ids = isset( $wishlist_data['product_ids'] ) ? $wishlist_data['product_ids'] : [];
			$count       = is_array( $product_ids ) ? count( $product_ids ) : 0;
			$value       = absint( $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $count === $value;
					break;
				case '!=':
					$result = $count !== $value;
					break;
				case '>':
					$result = $count > $value;
					break;
				case '<':
					$result = $count < $value;
					break;
				case '>=':
					$result = $count >= $value;
					break;
				case '<=':
					$result = $count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_condition_input_type() {
			return 'Text';
		}
	}
}
