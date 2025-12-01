<?php
/**
 * A checkbox field template
 * @since 2.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Labels are used with the inputs
// echo pewc_field_label( $item, $id );

if( isset( $item['field_options'] ) ) {

	$checkboxes = '<ul class="pewc-checkbox-group-wrapper">';

	$option_index = 0;

	foreach( $item['field_options'] as $key=>$option_value ) {

		$classes = array( 'pewc-checkbox-form-field' );
		$name = esc_html( $option_value['value'] );
		$option_percentage = '';

		// Set price differently if percentage is enabled
		if( pewc_is_pro() && ! empty( $item['field_percentage'] ) && ! empty( $option_value['price'] ) ) {

			// Set the option price as a percentage of the product price
			$product_price = $product->get_price();
			$option_price = pewc_get_option_price( $option_value, $item, $product );
			$option_price = ( floatval( $option_price ) / 100 ) * $product_price;
			if( apply_filters( 'pewc_maybe_include_tax_on_options', true ) ) { // This filter might be needed for exc / inc / inc tax scenarios
				$option_price = pewc_maybe_include_tax( $product, $option_price );
			}
			//$option_percentage = floatval( $option_price ); // incorrect?
			$option_percentage = $option_value['price']; // 3.21.4
			$classes[] = 'pewc-option-has-percentage';

		} else {

			$option_price = pewc_get_option_price( $option_value, $item, $product );

		}

		// 3.26.0, added filter for formulas in prices
		$classes = apply_filters( 'pewc_checkbox_group_option_classes', $classes, $item, $option_value, $key, $option_index );

	    if( ! empty( $option_price ) && pewc_display_option_prices_product_page( $item ) ) {
			$name .= apply_filters( 'pewc_option_price_separator', '+', $item ) . '<span class="pewc-option-cost-label">' . pewc_get_semi_formatted_raw_price( $option_price ) . '</span>';
			$name = apply_filters( 'pewc_option_name', $name, $item, $product, $option_price );
		}

	    $radio_id = $id . '_' . strtolower( str_replace( ' ', '_', $option_value['value'] ) );

		if( ! is_array( $value ) ) {
			$value = explode ( ' | ', $value );
		}

		$checked = ( is_array( $value ) && in_array( $option_value['value'], $value ) ) ? 'checked="checked"' : '';

		$option_attributes = apply_filters( 'pewc_checkbox_group_option_attributes', 'aria-label="' . strip_tags( htmlspecialchars_decode( $name ) ) . '"', $item, $option_value, $key, $option_index );
		$option_attribute_string = pewc_get_option_attribute_string( $option_attributes );

	    $radio = sprintf(
			'<li><label class="pewc-checkbox-form-label" for="%s"><input data-option-cost="%s" data-option-percentage="%s" type="checkbox" name="%s[]" id="%s" class="%s" value="%s" %s %s>&nbsp;%s<span class="pewc-theme-element"></span></label></li>',
			esc_attr( $radio_id ),
			esc_attr( $option_price ),
			esc_attr( $option_percentage ),
			esc_attr( $id ),
			esc_attr( $radio_id ),
			join( ' ', $classes ),
			esc_attr( $option_value['value'] ),
			esc_attr( $checked ),
			$option_attribute_string,
			$name
	    );

	    $checkboxes .= apply_filters( 'pewc_filter_checkbox_group_field', $radio, $radio_id, $option_price, $id, $name, $option_value, $item );

		$option_index++;

	}

	$checkboxes .= '</ul>';

	echo $open_td;
	echo $checkboxes;
	echo $close_td;

}
