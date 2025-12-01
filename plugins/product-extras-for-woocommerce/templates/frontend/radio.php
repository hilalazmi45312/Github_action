<?php
/**
 * A radio button template
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

	$option_index = 0;

	$radio_buttons = '<ul class="pewc-checkbox-group-wrapper">';

	foreach( $item['field_options'] as $key=>$option_value ) {

		$label = apply_filters( 'prefix_filter_field_option_name', wp_kses_post( $option_value['value'] ), $key, $item, $product );

		// Check if a key is set
		// $value is a misleading name for radio buttons because it refers to the label rather than the value
		// However, $value here is used for any existing value (e.g. from a default value for the field)
		// And we use $key instead for the value of the field being output. Make sense?
		if( empty( $option_value['key'] ) ) {
			// This field was saved before 2.4.5 and hasn't been updated since
			// 3.26.12, added sanitisation
			$key = str_replace( '-', '_', sanitize_title( pewc_keyify_field ( $option_value['value'] ) ) );
		} else {
			$key = $option_value['key'];
			// Remove any unwanted characters from the default or received value
			// $value = pewc_keyify_field( $value );
		}

		$option_price = pewc_get_option_price( $option_value, $item, $product );

		$option_percentage = '';

		$classes = array( 'pewc-radio-form-field' ); // input form
		$label_classes = array( 'pewc-radio-form-label' ); // label tag

		// Check for percentages
		if( ! empty( $item['field_percentage'] ) && ! empty( $option_price ) ) {
			// Set the option price as a percentage of the product price
			$option_percentage = floatval( $option_price );
			$product_price = $product->get_price();
			$option_price = ( floatval( $option_price ) / 100 ) * $product_price;
			// Get display price according to inc tax / ex tax setting
			//$option_price = pewc_maybe_include_tax( $product, $option_price ); // commented out on 3.9.2 to avoid double taxation, tax check is already done in pewc_get_option_price()
			$classes[] = 'pewc-option-has-percentage';
		}

		// 3.26.0, added filter for formulas in prices
		$classes = apply_filters( 'pewc_radio_option_classes', $classes, $item, $option_value, $key, $option_index );

	    if( ! empty( $option_price ) && pewc_display_option_prices_product_page( $item ) ) {
			$label .= apply_filters( 'pewc_option_price_separator', '+', $item );
			$label .= '<span class="pewc-option-cost-label">' . pewc_get_semi_formatted_raw_price( $option_price ) . '</span>';
			$label = apply_filters( 'pewc_option_name', $label, $item, $product, $option_price );
	    }

		// $radio_id = $id . '_' . strtolower( str_replace( ' ', '_', $option_value['value'] ) );
	    $radio_id = $id . '_' . strtolower( str_replace( ' ', '_', $key ) );
		$radio_id = apply_filters( 'pewc_filter_input_id', $radio_id, $item, 'id' ); // 3.26.5, apply filter to input ID

		$checked = ( is_array( $value ) && in_array( $option_value['value'], $value ) ) || ( ! is_array( $value ) && $value == $option_value['value'] )  ? 'checked=checked' : '';
		if ( ! empty( $checked ) && ! empty( $item['field_display_as_swatch'] ) ) {
			$label_classes[] = ' active-swatch';
		}

		$option_attributes = apply_filters( 'pewc_radio_option_attributes', '', $item, $option_value, $key, $option_index );
		$option_attribute_string = pewc_get_option_attribute_string();
		$option_attribute_string = apply_filters( 'pewc_option_attribute_string', $option_attribute_string, $item, $option_value, $option_index );

		$radio = sprintf(
	    	'<li><label class="%s" for="%s"><input data-option-cost="%s" type="radio" name="%s" id="%s" class="%s" data-option-percentage="%s" value="%s" %s %s>&nbsp;<span class="pewc-radio-option-text">%s</span><span class="pewc-theme-element"></span></label></li>',
			join( ' ', apply_filters( 'pewc_radio_label_classes', $label_classes, $item, $option_value, $option_index ) ),
			esc_attr( $radio_id ), // for
			esc_attr( $option_price ), // data-option-cost
			esc_attr( $name ), // name
			esc_attr( $radio_id ), // id
			join( ' ', $classes ),
			esc_attr( $option_percentage ),
			esc_attr( apply_filters( 'pewc_radio_option_value', $option_value['value'], $item, $option_value ) ), // 3.26.12, replaced $key with $option_value['value'] since $key may be keyified. This also fixes an issue with conditions using Radio options
			esc_attr( $checked ),
			$option_attribute_string,
	    	$label
	    );

		$radio_buttons .= apply_filters( 'pewc_filter_radio_button_field', $radio, $radio_id, $option_price, $id, $label, $option_value, $item );

		$option_index++;

	}

	$radio_buttons .= '</ul>';

	echo $open_td;
	echo $radio_buttons;
	echo $close_td;

}
