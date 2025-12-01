<?php
/**
 * Functions for tooltips
 * @since 3.5.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


function pewc_enable_tooltips() {
	$enable_tooltips = get_option( 'pewc_enable_tooltips', 'no' );
	return apply_filters( 'pewc_enable_tooltips', $enable_tooltips );
}

// Tooltips
function pewc_add_tooltip_icon( $label, $product, $item ) {

	if( pewc_enable_tooltips() == 'yes' ) {

		$field_description = ! empty( $item['field_description'] ) ? $item['field_description'] : '';
		if( ! empty( $item['field_description'] ) ) {

			$label .= sprintf(
				'&nbsp;<span title="%s" class="dashicons dashicons-editor-help tooltip"></span>',
				esc_attr( $item['field_description'] )
			);

		}

	}

	// Enhanced
	if( pewc_field_has_enhanced_tooltip( $item ) ) {
		$label .= '&nbsp;<span class="dashicons dashicons-editor-help pewc-tooltip-button"></span>';
	}

	return $label;

}
add_filter( 'pewc_field_label_end', 'pewc_add_tooltip_icon', 10, 3 );

/**
 * Enhanced tooltips
 * @since 3.21.0
 */
function pewc_enhanced_tooltips_enabled() {
	$enhanced = pewc_enable_tooltips() == 'enhanced' ? 'yes' : 'no';
	return $enhanced;
}

/**
 * Add the tooltip source post ID as a field attribute
 * @since 3.21.0
 */
function pewc_add_enhanced_tooltip_attribute( $attributes, $item ) {
	
	if( pewc_enhanced_tooltips_enabled() != 'yes' ) {
		return $attributes;
	}
	if( ! empty( $item['field_description'] ) ) {
		$attributes['data-tooltip-id'] = absint( $item['field_description'] );
	}
	return $attributes;

}
add_filter( 'pewc_filter_item_attributes', 'pewc_add_enhanced_tooltip_attribute', 10, 2 );

/**
 * Check if a specific field has an enhanced tooltip
 * @since 3.21.0
 */
function pewc_field_has_enhanced_tooltip( $item ) {

	$has_enhanced = false;
	if( pewc_enhanced_tooltips_enabled() == 'yes' ) {

		if( ! empty( $item['field_description'] ) ) {
			$has_enhanced = true;
		}

	}
	return $has_enhanced;
}

/**
 * Check if a specific field has an enhanced tooltip
 * @since 3.21.0
 */
function pewc_update_field_classes_for_tooltips( $classes, $item ) {

	if( pewc_field_has_enhanced_tooltip( $item ) ) {

		if( ! empty( $item['field_description'] ) ) {
			$classes[] = 'has-enhanced-tooltip';
		}

	}
	return $classes;
}
add_filter( 'pewc_filter_single_product_classes', 'pewc_update_field_classes_for_tooltips', 10, 2 );

/**
 * Print the tooltips for this product
 * @since 3.21.0
 */
function pewc_print_tooltips() {

	if ( pewc_enhanced_tooltips_enabled() != 'yes' || ! is_product() ) {
		return; // 3.21.2
	}

	$product_id = get_the_ID();
	if( get_post_type( $product_id ) != 'product' ) {
		return;
	}
	
	$groups = pewc_get_extra_fields( $product_id );
	$tooltip_ids = array();
	if( $groups ) {
		foreach( $groups as $group_id=>$fields ) {
			if( ! empty( $fields['items'] ) ) {
				foreach( $fields['items'] as $field_id=>$field ) {
					if( ! empty( $field['field_description'] ) ) {
						$tooltip_ids[] = absint( $field['field_description'] );
					}
				}
			}
		}
	}

	if( $tooltip_ids ) {
		foreach( $tooltip_ids as $post_id ) {
			include( PEWC_DIRNAME . '/templates/frontend/tooltips/tooltip.php' );
		}
	}

}
add_action( 'wp_footer', 'pewc_print_tooltips' );