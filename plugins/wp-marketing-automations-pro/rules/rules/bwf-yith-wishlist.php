<?php
if ( bwfan_is_woocommerce_active() && bwfan_is_yith_wishlist_active() ) {
	class BWFAN_Rule_Yith_wishlist_Item extends BWFAN_Rule_Products {
		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;

			parent::__construct( 'yith_wishlist_item' );
		}

		/**
		 * common function for v1 and v2 to define the rule operators
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
		 * getting the field options for the view
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
		 * processing the conditions for v2
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

			global $wpdb;

			$table_name     = $wpdb->prefix . 'yith_wcwl';
			$query          = $wpdb->prepare( "SELECT * FROM $table_name WHERE wishlist_id = %d", $wishlist_id );
			$wishlist_items = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $wishlist_items ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$wishlist_product = array_map( function ( $item ) {
				return $item->prod_id;
			}, $wishlist_items );

			if ( empty( $wishlist_product ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$saved_ids = array_column( $rule_data['data'], 'key' );
			$result    = $this->validate_set( $saved_ids, $wishlist_product, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		public function validate_set( $saved_products, $product_found, $operator ) {
			$result = false;

			/** Get product ids with parent */
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
}
