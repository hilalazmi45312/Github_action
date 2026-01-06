<?php

class BWFAN_Rule_Advanced_Shipping_Rate extends BWFAN_Dynamic_Option_Base {

	public function __construct() {
		parent::__construct( 'advanced_shipping_rate' );
	}

	public function get_condition_values_nice_names( $values ) {
		$return = [];
		if ( 0 === count( $values ) ) {
			return $return;
		}
		foreach ( $values as $shipping_rate_id ) {
			$return[ $shipping_rate_id ] = get_the_title( $shipping_rate_id );
		}

		return $return;
	}

	public function get_search_type_name() {
		return 'shipping_rate_rule';
	}

	public function get_search_results( $term ) {
		$array = array();

		if ( empty( $term ) ) {
			wp_send_json( array(
				'results' => $array,
			) );
		}

		$args = array(
			'post_type'   => 'was',
			'numberposts' => 5,
			'paged'       => 1,
			's'           => $term,
		);

		$posts = get_posts( $args );
		if ( is_array( $posts ) && count( $posts ) > 0 ) {
			foreach ( $posts as $post ) {
				if ( ! $post instanceof WP_Post ) {
					continue;
				}
				$array[] = array(
					'id'   => (string) $post->ID,
					'text' => $post->post_title,
				);
			}
		}

		wp_send_json( array(
			'results' => $array,
		) );
	}

	public function is_match( $rule_data ) {
		global $wpdb;
		$type  = $rule_data['operator'];
		$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
		if ( ! $order instanceof WC_Order ) {
			return $this->return_is_match( false, $rule_data );
		}

		$shipping = $order->get_items( 'shipping' );
		if ( empty( $shipping ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$shipping_ids = [];
		foreach ( $shipping as $item ) {
			$shipping_ids[] = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'was' AND post_status = 'publish' LIMIT 1;", $item->get_name() ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		$result = false;
		switch ( $type ) {
			case 'all':
				if ( is_array( $rule_data['condition'] ) && is_array( $shipping_ids ) ) {
					$result = count( array_intersect( $rule_data['condition'], $shipping_ids ) ) === count( $rule_data['condition'] );
				}
				break;
			case 'any':
				if ( is_array( $rule_data['condition'] ) && is_array( $shipping_ids ) ) {
					$result = count( array_intersect( $rule_data['condition'], $shipping_ids ) ) >= 1;
				}
				break;
			case 'none':
				if ( is_array( $rule_data['condition'] ) && is_array( $shipping_ids ) ) {
					$result = count( array_intersect( $rule_data['condition'], $shipping_ids ) ) === 0;
				}
				break;
			default:
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function ui_view() {
		esc_html_e( 'Order Shipping Rate', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

        <%= ops[operator] %> <% var chosen = []; %>
        <% _.each(condition, function( value, key ){ %>
        <% chosen.push(uiData[value]); %>

        <% }); %>
        <%= chosen.join("/ ") %>
		<?php
	}

	public function get_possible_rule_operators() {
		return array(
			'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
			'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
		);
	}
}
