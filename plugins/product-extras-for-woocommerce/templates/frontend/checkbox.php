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

$label = apply_filters( 'pewc_filter_field_option_name', wp_kses_post( $item['field_label'] ), $id, $item, $product );

$label_classes = array( 'pewc-checkbox-form-label' ); // label tag
if ( $value && ! empty( $item['field_display_as_swatch'] ) ) {
	$label_classes[] = 'active-swatch';
}

$label .= '<span class="required"> &#42;</span>';

if( ! empty( $item['field_price'] ) && pewc_display_field_prices_product_page( $item ) ) {
	$field_price = apply_filters( 'pewc_filter_display_price_for_percentages', $field_price, $product, $item );
	$label .= apply_filters( 'pewc_option_price_separator', '+', $item ) . '<span class="pewc-checkbox-price">' . pewc_get_semi_formatted_raw_price( $field_price ) . '</span>'; // 3.21.4, added span container
	$label = apply_filters( 'pewc_option_name', $label, $item, $product, $item['field_price'] );
}
$label = apply_filters( 'pewc_field_label_end', $label, $product, $item, $group_layout ); // tooltip can hook here

// 3.26.5
$attributes = apply_filters( 'pewc_filter_checkbox_field_attributes', array( 'aria-label' => strip_tags( $item['field_label'] ) ), $item );
$attribute_string = '';
if( ! empty( $attributes ) ) {
	foreach( $attributes as $attribute=>$attr_value ) {
		$attribute_string .= $attribute . '="' . esc_attr( $attr_value ) . '" ';
	}
}
$attribute_string .= checked( 1, $value, false );

if( $group_layout == 'table' ) {
	$open_td = '<td colspan=2>';
}

printf(
	'%s<label class="%s" for="%s"><input type="checkbox" class="pewc-form-field" id="%s" name="%s" %s value="__checked__">&nbsp;<span>%s</span><span class="pewc-theme-element"></span></label>%s',
	$open_td, // Set in functions-single-product.php
	join( ' ', $label_classes ),
	esc_attr( $id ),
	esc_attr( $id ),
	esc_attr( $id ),
	$attribute_string,
	$label,
	$close_td
); ?>
