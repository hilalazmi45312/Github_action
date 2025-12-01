<?php
/**
 * A select field template
 * @since 2.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

// echo pewc_field_label( $item, $id );

if( isset( $item['field_options'] ) ) {

	$index = 0;
	$first_option = ! empty( $item['first_field_empty'] ) ? true : false;
	$option_count = 0;

	// 3.26.5
	$attributes = apply_filters( 'pewc_filter_select_field_attributes', array( 'aria-label' => strip_tags( $item['field_label'] ) ), $item );
	$attribute_string = '';
	if( ! empty( $attributes ) ) {
		foreach( $attributes as $attribute=>$attr_value ) {
			$attribute_string .= $attribute . '="' . esc_attr( $attr_value ) . '" ';
		}
	}

	printf(
		'%s<select class="pewc-form-field pewc-select-box" data-select-id="%s" id="%s" name="%s" %s style="display: none">',
		$open_td,
		esc_attr( $id ),
		esc_attr( $id ) . '_select_box',
		esc_attr( $id ),
		$attribute_string,
	);

	$all_options = array();
	$option_class = array();

	// 3.13.7, check if filter is enabled, to be used later
	$add_on_image_action = pewc_get_add_on_image_action( $product->get_id() );

	foreach( $item['field_options'] as $key=>$option_value ) {

		$src = pewc_get_swatch_image_url( $option_value, $item );

		if ( $add_on_image_action && ! empty( $option_value['image'] ) ) {
			// 3.13.7, retrieve full sized images so that they can be used to replace the main image
			// $option_value['image'] = attachment_id
			$src_full = wp_get_attachment_image_src( $option_value['image'], apply_filters( 'woocommerce_gallery_full_size', 'full' ) );
		} else {
			$src_full = false;
		}

		$name = apply_filters( 'prefix_filter_field_option_name', esc_html( $option_value['value'] ), $key, $item, $product );

		$option_price = pewc_get_option_price( $option_value, $item, $product );
		$option_percentage = '';

		// Check for percentages
		if( ! empty( $item['field_percentage'] ) && ! empty( $option_price ) ) {
			// Set the option price as a percentage of the product price
			$option_percentage = floatval( $option_price );
			$product_price = $product->get_price();
			$option_price = ( floatval( $option_price ) / 100 ) * $product_price;
			// Get display price according to inc tax / ex tax setting
			if( apply_filters( 'pewc_maybe_include_tax_on_options', true ) ) { // This filter might be needed for exc / inc / inc tax scenarios
				$option_price = pewc_maybe_include_tax( $product, $option_price );
			}
			// $option_percentage = floatval( $item['field_price'] );
			$option_class[] = 'pewc-select-option-has-percentage'; // not sure where this is used
		}

		// Get the price
		if ( ! empty( $option_price ) || ! apply_filters( 'pewc_hide_zero_option_price', true, $item ) ) {
			$option_cost = pewc_get_semi_formatted_raw_price( $option_price );
		} else {
			$option_cost = '';
		}

		$this_value = ( $first_option && $option_count === 0 ) ? '' : $option_value['value'];
		$extra_attributes = ( $this_value == $value ) ? 'selected="selected" ' : '';

		// 3.13.7
		if ( $add_on_image_action && ! empty( $src_full ) ) {
			$extra_attributes .= sprintf( 
				'data-imagesrc-full="%s" data-imagesrc-width="%s" data-imagesrc-height="%s"', 
				$src_full[0],
				$src_full[1],
				$src_full[2]
			);
		}

		// 3.26.5
		$extra_attributes .= 'aria-label="' . strip_tags( $name ) . '" ';

		// 3.26.0, formulas in prices
		$extra_attributes = apply_filters( 'pewc_select_box_option_attributes', $extra_attributes, $item, $option_value, $key, $option_count );
		if ( is_array( $extra_attributes ) ) {
			$extra_attributes = pewc_get_option_attribute_string( $extra_attributes );
		}
		$option_class = apply_filters( 'pewc_select_box_option_classes', $option_class, $item, $option_value, $key, $option_count );

		// These options are going to be replaced by divs by ddslick
		printf(
			'<option class="%s" data-option-cost="%s" data-imagesrc="%s" data-description="%s" value="%s" data-option-percentage="%s" %s>%s</option>',
			implode( ' ', $option_class ),
			esc_attr( $option_price ),
			esc_url( $src[0] ),
			$option_cost,
			esc_attr( $this_value ),
			$option_percentage,
			$extra_attributes,
			$name
		);

		$option_id = $id . '_' . $option_count;

		$all_options[$option_id] = array(
			'option_count'				=> $option_count,
			'option_cost'					=> $option_price,
			'option_value'				=> $this_value,
			'option_percentage'		=> $option_percentage
		);

		$option_count++;

	}

	echo '</select>';

	// Store the options elsewhere so we can get prices etc
	if( $all_options ) {
		foreach( $all_options as $option_id=>$option ) {
			$hidden_option_id = $option_id . '_hidden';
			printf(
				'<input type="hidden" id="%s" data-option-cost="%s" value="%s" data-option-percentage="%s">',
				$hidden_option_id,
				esc_attr( $option['option_cost'] ),
				esc_attr( $option['option_value'] ),
				esc_attr( $option['option_percentage'] )
			);
		}
	}

	echo $close_td;

}
