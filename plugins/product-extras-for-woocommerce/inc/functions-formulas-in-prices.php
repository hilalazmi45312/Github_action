<?php
/**
 * Functions for repeatable groups
 * @since 3.26.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable formulas in field and option prices
 * @since 3.26.0
 */
function pewc_formulas_in_prices_enabled( $product_id=false ) {
	return apply_filters( 'pewc_enable_formulas_in_prices', false, $product_id );
}

/**
 * Checks if a field or option price possibly has a formula
 * @since 3.26.0
 */
function pewc_price_has_formula( $price ) {
	return false !== strpos( $price, '{' ) && false !== strpos( $price, '}' );
}

/**
 * @since 3.26.0
 */
function pewc_field_option_price_has_formula( $item ) {

	global $post;
	if ( ! empty( $post->ID ) && pewc_formulas_in_prices_enabled( $post->ID ) && ! empty( $item['field_options'] ) ) {
		foreach ( $item['field_options'] as $option ) {
			if ( ! empty( $option['price'] ) && pewc_price_has_formula( $option['price'] ) ) {
				return true;
			}
		}
	}
	return false;

}

/**
 * Adds classes to fields with formulas in their option prices
 * @since 3.26.0
 */
function pewc_classes_for_fields_with_formulas_in_prices( $classes, $item ) {

	if ( pewc_price_has_formula( $item['field_price'] ) ) {
		$classes[] = 'pewc-field-price-has-formula';
	}
	if ( pewc_field_option_price_has_formula( $item ) ) {
		$classes[] = 'pewc-field-option-price-has-formula';
	}
	return $classes;

}
add_filter( 'pewc_filter_single_product_classes', 'pewc_classes_for_fields_with_formulas_in_prices', 10, 2 );

/**
 * Adds attributes to fields with formulas in their prices
 * @since 3.26.0
 */
function pewc_attributes_for_fields_with_formulas_in_prices( $attributes, $item ) {

	if ( pewc_price_has_formula( $attributes['data-field-price'] ) || pewc_field_option_price_has_formula( $item ) ) {
		$attributes['data-field-price-formula-round'] = apply_filters( 'pewc_field_price_with_formula_round', 'no-rounding', $item );
		$attributes['data-field-price-formula-decimals'] = apply_filters( 'pewc_field_price_with_formula_decimals', 2, $item );
	}

	if ( pewc_price_has_formula( $attributes['data-field-price'] ) ) {
		// we use a different data attribute so that we do not lose the formula when the price is computed?
		$attributes['data-field-price-formula'] = $attributes['data-field-price'];

		preg_match_all( "|{(.*)}|U", $attributes['data-field-price'], $all_tags, PREG_PATTERN_ORDER );
		preg_match_all( "|{field_(.*)}|U", $attributes['data-field-price'], $all_fields, PREG_PATTERN_ORDER );
		$fields = '';
		if( ! empty( $all_fields[1] ) ) {
			$fields = json_encode( $all_fields[1] );
			$attributes['data-field-price-formula-fields'] = $fields;
		}
		$tags = '';
		if( ! empty( $all_tags[1] ) ) {
			$tags = json_encode( $all_tags[1] );
			$attributes['data-field-price-formula-tags'] = $tags;
		}
	}

	return $attributes;

}
add_filter( 'pewc_filter_item_attributes', 'pewc_attributes_for_fields_with_formulas_in_prices', 11, 2 );

/**
 * @since 3.26.0
 */
function pewc_option_attributes_for_price_with_formula( $option_attributes, $item, $option_value, $key, $option_index=false ) {

	if ( ! pewc_price_has_formula( $option_value['price'] ) ) {
		return $option_attributes;
	}

	// we do this because a blank string is passed into the filter, just in case other filters pass a string?
	if ( empty( $option_attributes ) ) {
		$option_attributes = array();
	} else if ( is_string( $option_attributes ) ) {
		// 3.26.5, new way of converting a string of option attributes to an array
		$option_attributes2 = array();
		$hair = wp_kses_hair( $option_attributes, '' );
		foreach ( $hair as $label => $values ) {
			$option_attributes2[$label] = $values['value'];
		}
		if ( ! empty( $option_attributes2 ) ) {
			$option_attributes = $option_attributes2;
		}
	}

	$option_attributes['data-option-cost-formula'] = $option_value['price']; // we store the formula here because data-option-cost will be evaluated

	preg_match_all( "|{(.*)}|U", $option_value['price'], $all_tags, PREG_PATTERN_ORDER );
	preg_match_all( "|{field_(.*)}|U", $option_value['price'], $all_fields, PREG_PATTERN_ORDER );
	$fields = '';
	if( ! empty( $all_fields[1] ) ) {
		$fields = json_encode( $all_fields[1] );
		$option_attributes['data-option-price-formula-fields'] = $fields;
	}
	$tags = '';
	if( ! empty( $all_tags[1] ) ) {
		$tags = json_encode( $all_tags[1] );
		$option_attributes['data-option-price-formula-tags'] = $tags;
	}

	if ( ! isset( $option_attributes['data-option-index'] ) ) {
		$option_attributes['data-option-index'] = $option_index;
	}

	return $option_attributes;

}
add_filter( 'pewc_swatch_option_attributes', 'pewc_option_attributes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_checkbox_group_option_attributes', 'pewc_option_attributes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_radio_option_attributes', 'pewc_option_attributes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_select_option_attributes', 'pewc_option_attributes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_select_box_option_attributes', 'pewc_option_attributes_for_price_with_formula', 11, 5 );

/**
 * Add a class to option/input tags if option price has formula
 * @since 3.26.0
 */
function pewc_option_classes_for_price_with_formula( $classes, $item, $option_value, $key, $option_index=false ) {

	if ( pewc_price_has_formula( $option_value['price'] ) ) {
		$classes[] = 'pewc-option-price-has-formula';
	}
	return $classes;

}
add_filter( 'pewc_swatch_option_classes', 'pewc_option_classes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_checkbox_group_option_classes', 'pewc_option_classes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_radio_option_classes', 'pewc_option_classes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_select_option_classes', 'pewc_option_classes_for_price_with_formula', 11, 5 );
add_filter( 'pewc_select_box_option_classes', 'pewc_option_classes_for_price_with_formula', 11, 5 );

/**
 * @since 3.26.0
 */
function pewc_hidden_fields_for_prices_with_formulas( $item, $id, $group_layout ) {

	if ( pewc_price_has_formula( $item['field_price'] ) ) {
		echo '<input type="hidden" name="' . $item['id'] . '_field_price_calculated" value="" />';
	}
	if ( pewc_field_option_price_has_formula( $item ) ) {
		$index = 0;
		foreach ( $item['field_options'] as $option ) {
			if ( ! empty( $option['price'] ) && pewc_price_has_formula( $option['price'] ) ) {
				echo '<input type="hidden" name="' . $item['id'] . '_option_' . $index . '_price_calculated' . '" value="" />';
			}
			$index++;
		}
	}

}
add_action( 'pewc_after_field_template', 'pewc_hidden_fields_for_prices_with_formulas', 11, 3 );
