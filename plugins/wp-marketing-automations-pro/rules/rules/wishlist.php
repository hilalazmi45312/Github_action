<?php

if ( bwfan_is_wc_wishlist_active() && bwfan_is_woocommerce_active() ) {
	class BWFAN_Rule_Wishlist_Item extends BWFAN_Rule_Products {
		public $supports = array( 'cart', 'order' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'wishlist_item' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		/** v2 Methods: END */

		public function get_products( $automation_data = [] ) {
			if ( isset( $automation_data['global'] ) && is_array( $automation_data['global'] ) ) {
				$wishlist_items = isset( $automation_data['global']['wishlist_items'] ) ? $automation_data['global']['wishlist_items'] : [];
			} else {
				$wishlist_items = BWFAN_Core()->rules->getRulesData( 'wishlist_items' );
			}
			$result    = false;
			$found_ids = [];
			if ( empty( $wishlist_items ) ) {
				return [];
			}

			return $wishlist_items;
		}

		public function ui_view() {
			esc_html_e( 'Wishlist Items ', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %> <% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <%
            if(_.has(uiData, value)) {
            chosen.push(uiData[value]);
            }
            %>

            <% }); %>
            <%= chosen.join("/ ") %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'all'  => __( 'matches exactly', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}

	class BWFAN_Rule_Wishlist_Item_Category extends BWFAN_Rule_Term_Taxonomy {

		public $taxonomy_name = 'product_cat';

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'wishlist_item_category' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		/** v2 Methods: END */

		public function get_term_ids( $automation_data = [] ) {
			if ( isset( $automation_data['global'] ) && is_array( $automation_data['global'] ) ) {
				$wishlist_items = isset( $automation_data['global']['wishlist_items'] ) ? $automation_data['global']['wishlist_items'] : [];
			} else {
				$wishlist_items = BWFAN_Core()->rules->getRulesData( 'wishlist_items' );
			}

			if ( empty( $wishlist_items ) ) {
				return [];
			}
			$all_terms = [];
			foreach ( $wishlist_items as $item_id ) {
				$all_terms = $this->get_wishlist_item_terms( $all_terms, $item_id );
			}

			return $all_terms;
		}

		public function get_wishlist_item_terms( $all_terms, $item_id ) {

			$terms = wp_get_object_terms( $item_id, $this->taxonomy_name, array(
				'fields' => 'ids',
			) );

			$all_terms = array_merge( $all_terms, $terms );

			return $all_terms;
		}

		public function ui_view() {
			esc_html_e( 'Wishlist Item\'s category ', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %><% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <% chosen.push(uiData[value]); %>

            <% }); %>
            <%= chosen.join("/ ") %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}

	}
}