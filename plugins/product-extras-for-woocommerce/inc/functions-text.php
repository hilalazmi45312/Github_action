<?php
/**
 * Functions for Text and Textarea
 * @since 3.11.3
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a character counter to text and textarea fields
 * Added advanced-preview on 3.11.6
 * @since 3.11.3
 */
function pewc_add_text_counter( $item, $id, $group_layout, $file ) {

	if ( ! pewc_is_text_field( $item ) ) {
		return; // do nothing
	}

	if ( empty( $item['show_char_counter'] ) || 'table' === $group_layout ) {
		return; // do nothing
	}

	echo pewc_output_text_counter( $item, $id );
}
add_action( 'pewc_after_include_frontend_template', 'pewc_add_text_counter', 10, 4);

/**
 * Add styles for character counter
 * @since 3.11.3
 */
function pewc_add_text_counter_styles() {
	$css = '
	/* Add-Ons Ultimate character counter */
	.pewc-text-counter-container {float:right; margin-top: 1em;}
	.pewc-text-counter-container .pewc-current-count.error { color:#ff0000; }
	tr td .pewc-text-counter-container {float:none;}';

	wp_add_inline_style( 'pewc-style', $css );
}
add_action( 'wp_enqueue_scripts', 'pewc_add_text_counter_styles', 9999 );

/**
 * Output the character counter. Part of pewc_add_text_counter() since 3.11.3, moved into a separate function since 3.21.3
 * @since 3.21.3
 */
function pewc_output_text_counter( $item, $id ) {

	$output = sprintf('<p class="pewc-text-counter-container %s"><small class="pewc-text-counter">', $id );
	$output .= '<span class="pewc-current-count">0</span>';

	if ( ! empty( $item['field_maxchars'] ) && apply_filters( 'pewc_text_counter_show_max', true, $item ) ) {
		$output .= apply_filters( 'pewc_text_counter_separator', ' / ', $item );
		$output .= sprintf('<span class="pewc-max-count">%d</span>', $item['field_maxchars'] );
	}

	$output .= '</small></p>';

	return $output;

}

/**
 * Check if a field is a text field
 * @since 3.21.3
 */
function pewc_is_text_field( $item ) {

	if ( empty( $item['field_type'] ) || ( 'text' != $item['field_type'] && 'textarea' != $item['field_type'] && 'advanced-preview' != $item['field_type'] ) ) {
		return false;
	}
	return true;

}

/**
 * Show character counter before the closing TD tag
 * @since 3.21.3
 */
function pewc_add_text_counter_table_layout( $close_td, $item, $id ) {

	if ( pewc_is_text_field( $item ) && ! empty( $item['show_char_counter'] ) ) {
		$close_td = pewc_output_text_counter( $item, $id ) . $close_td;
	}
	return $close_td;

}
add_filter( 'pewc_before_close_td', 'pewc_add_text_counter_table_layout', 10, 3 );
