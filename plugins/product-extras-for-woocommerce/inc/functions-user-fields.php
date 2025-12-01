<?php
/**
 * Functions for populating options with user fields
 * @since 3.20.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pewc_enable_user_fields() {
	return get_option( 'pewc_enable_user_fields', 'no' ) == 'yes' ? true : false;
}

/**
 * Add the field param
 * @since 3.20.0
 */
function pewc_add_user_field_id( $params ) {

	if( pewc_enable_user_fields() ) {
		$params[] = 'field_user_field_id';
	}

	return $params;

}
add_filter( 'pewc_item_params', 'pewc_add_user_field_id', 10, 2 );

/**
 * Add user meta field data as field options
 * @since 3.20.0
 */
function pewc_add_user_meta_as_options( $item, $group, $group_id, $post_id ) {

	if( ! empty( $item['field_options'] ) && ! empty( $item['field_options'][0]['value'] ) && str_contains( $item['field_options'][0]['value'], '{user_field' ) ) {

		$options = array();
		
		if( is_user_logged_in() ) {

			// Get the user field we want to pull values from
			$value = explode( ':', $item['field_options'][0]['value'] );

			if( ! empty( $value[1] ) ) {

				// Get the user field ID
				$user_field = str_replace( array( ' ', '}' ), '', $value[1] );
				if( $user_field ) {
					$user_id = get_current_user_id();
					$user_field_value = get_user_meta( $user_id, $user_field, true );
					if( is_array( $user_field_value ) ) {

						// Populate the add-on field options with our user meta
						foreach( $user_field_value as $val ){
							$options[] = array(
								'image' => '',
								'value' => esc_attr( $val ),
								'price' => ''
							);
						}

					} else {

						$options[] = array(
							'image' => '',
							'value' => esc_attr( $user_field_value ),
							'price' => ''
						);

					}
					
				}

			}

		}

    	$item['field_options'] = $options;

    }

    return $item;

}
add_filter( 'pewc_filter_item_start_list', 'pewc_add_user_meta_as_options', 10, 4 );

function pewc_add_new_user_field_data( $cart_item_data, $item, $group_id, $field_id ) {

	if( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		return;
	}

	// Check if this field updates user meta
	if( ! empty( $item['field_user_field_id'] ) ) {

		$user_field_id = $item['field_user_field_id'];
		$id = $item['id'];
		// Get the value of the field
		$new_value = ! empty( $_POST[$id] ) ? $_POST[$id] : false;
		if( $new_value ) {

			// Update the user meta
			$user_meta = get_user_meta( $user_id, $user_field_id, true );
			if( ! $user_meta || ! is_array( $user_meta ) ) {
				$user_meta = array( $user_meta );
			}
			$user_meta[] = $new_value;
			
			// Ensure there are no duplicate values
			$user_meta = array_unique( $user_meta );

			update_user_meta( $user_id, $user_field_id, $user_meta );

		}

	}

}
add_action( 'pewc_end_add_cart_item_data', 'pewc_add_new_user_field_data', 10, 4 );