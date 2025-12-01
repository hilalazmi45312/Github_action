<?php
/**
 * A select field template
 * @since	2.0.0
 * @version	3.22.0
 * @package	WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

// echo pewc_field_label( $item, $id );

if( isset( $item['field_options'] ) ) {

	echo $open_td;

	do_action( 'pewc_before_select_field', $item, $id, $group_layout );

	$index = 0;
	$first_option = ! empty( $item['first_field_empty'] ) ? true : false;
	$option_count = 0;

	$select_id = apply_filters( 'pewc_filter_input_id', $id, $item, 'id' ); // 3.26.5, apply filter to select form ID

	// 3.26.5
	$attributes = apply_filters( 'pewc_filter_select_field_attributes', array( 'aria-label' => strip_tags( $item['field_label'] ) ), $item );
	$attribute_string = '';
	if( ! empty( $attributes ) ) {
		foreach( $attributes as $attribute=>$attr_value ) {
			$attribute_string .= $attribute . '="' . esc_attr( $attr_value ) . '" ';
		}
	}

	$select_field = sprintf(
		'<select class="pewc-form-field" id="%s" name="%s" %s>',
		esc_attr( $select_id ),
		esc_attr( $name ), // 3.22.0, changed to $name
		$attribute_string,
	);

	if( ! empty( $item['field_options'] ) ) {

		foreach( $item['field_options'] as $key=>$option_value ) {

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
			}

			// Include prices in option labels
			if( ! empty( $option_price ) && pewc_display_option_prices_product_page( $item ) ) {
				$name .= apply_filters( 'pewc_option_price_separator', '+', $item ) . pewc_get_semi_formatted_raw_price( $option_price );
				$name = apply_filters( 'pewc_option_name', $name, $item, $product, $option_price );
			}

			$this_value = ( $first_option && $option_count === 0 ) ? '' : $option_value['value'];
			$selected = ( $this_value == $value ) ? 'selected="selected"' : '';

			$option_attributes = apply_filters( 'pewc_select_option_attributes', 'aria-label="' . strip_tags( $name ) . '"', $item, $option_value, $key, $option_count );
			$option_attribute_string = pewc_get_option_attribute_string( $option_attributes );
			$option_attribute_string = apply_filters( 'pewc_option_attribute_string', $option_attribute_string, $item, $option_value, $option_count );

			// 3.21.2
			$option_class = array();
			if ( $first_option && $option_count === 0 && ! empty( $item['field_percentage'] ) ) {
				$option_class[] = 'pewc-first-option'; // so that option text is not replaced when price is updated
			} else if ( ! empty( $option_percentage ) ) {
				$option_class[] = 'pewc-select-option-has-percentage'; // not really sure where this is used
			}

			// 3.26.0, added filter for formulas in prices
			$option_class = apply_filters( 'pewc_select_option_classes', $option_class, $item, $option_value, $key, $option_count );

			$option = sprintf(
				'<option class="%s" data-option-cost="%s" value="%s" %s data-option-percentage="%s" %s>%s</option>',
				implode( ' ', $option_class ),
				esc_attr( $option_price ),
				esc_attr( apply_filters( 'pewc_select_option_value', $this_value, $item, $option_value ) ), // value
				$selected,
				$option_percentage,
				$option_attribute_string,
				$name
			);

			$select_field .= apply_filters( 'pewc_filter_select_option_field', $option, $option_price, $this_value, $selected, $option_percentage, $name, $item );

			$option_count++;

		}

	}

	echo $select_field;

	echo '</select>';

	do_action( 'pewc_after_select_field', $item, $id, $group_layout );

	echo $close_td;

}
