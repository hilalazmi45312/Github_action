<?php
/**
 * Functions for optimised validation
 * @since 3.11.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'yes' === get_option( 'pewc_optimised_validation', 'no' ) ) {
	add_action( 'pewc_before_group_inner_tag_close', 'pewc_js_validation_notice_container', 1, 2 );
	add_filter( 'pewc_field_label_end', 'pewc_js_validation_notice_container2', 1, 4 );
	add_filter( 'pewc_filter_item_attributes', 'pewc_js_validation_attributes', 1, 2 );
	add_filter( 'pewc_filter_group_wrapper_class', 'custom_group_wrapper_disable_groups', 1, 4 );
	add_action( 'pewc_end_group_content_wrapper', 'custom_end_group_content_wrapper_disable_groups', 1, 5 );
}

/**
 * Add a notice container for validation error (for fields other than products/product-categories)
 * Handles the 'pewc_before_group_inner_tag_close' action hook
 * @since 3.11.0
 */
function pewc_js_validation_notice_container( $item, $group_layout ) {
	if( $group_layout != 'table' ) {
		echo '<span class="pewc-js-validation-notice"></span>';
	}
}

/**
 * Add a notice container for validation error (for products/product-categories fields)
 * Handles the 'pewc_filter_field_label' filter hook
 * @since 3.11.0
 */
function pewc_js_validation_notice_container2( $label, $product, $item, $group_layout ) {
	if( $group_layout == 'table' ) {
		$label .= ' <span class="pewc-js-validation-notice"></span>';
	}

	return $label;
}

/**
 * Add custom data attributes to be used by optimised validation
 * Handles the 'pewc_filter_item_attributes' filter hook
 * @since 3.11.0
 */
function pewc_js_validation_attributes( $attributes, $item ) {
	$label = ! empty( $item['field_label'] ) ? $item['field_label'] : $item['id'];

	if ( ! empty( $item['field_required'] ) ) {
		$attributes['data-validation-notice'] = esc_attr( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ) );
	}

	if ( ( $item['field_type'] == 'products' || $item['field_type'] == 'product-categories') && pewc_is_pro() &&
		$item['products_quantities'] == 'independent' &&
	 	( $item['products_layout'] == 'column' || $item['products_layout'] == 'checkboxes' || $item['products_layout'] == 'checkboxes-list' ) &&
		( ! empty( $item['min_products'] ) || ! empty( $item['max_products'] ) )
		) {

		$min_products = $max_products = 0;
		if ( ! empty( $item['min_products'] ) && $item['min_products'] > 0 ) {

			$min_products = $item['min_products'];
			$attributes['data-min-products'] = $min_products;
			$attributes['data-min-products-error'] = esc_attr( apply_filters(
				'pewc_filter_min_children_validation_notice',
				sprintf(
					__( '%s requires you to choose a minimum of %s products', 'pewc' ),
					esc_html( $label ),
					$min_products
				),
				esc_html( $label ),
				$min_products
			) );

		}
		if ( ! empty( $item['max_products'] ) && $item['max_products'] > 0 ) {

			$max_products = $item['max_products'];
			$attributes['data-max-products'] = $max_products;
			$attributes['data-max-products-error'] = esc_attr( apply_filters(
				'pewc_filter_max_children_validation_notice',
				sprintf(
					__( '%s requires you to choose a maximum of %s products', 'pewc' ),
					esc_html( $label ),
					$max_products
				),
				esc_html( $label ),
				$max_products
			) );

		}
		if ( $min_products == $max_products && $min_products > 0) {

			$attributes['data-exact-products-error'] = esc_attr( apply_filters(
				'pewc_filter_exact_children_validation_notice',
				sprintf(
					__( '%s requires you to choose %s products', 'pewc' ),
					esc_html( $label ),
					$min_products
				),
				esc_html( $label ),
				$min_products
			) );

		}

	} else if ( ( $item['field_type'] == 'image_swatch' || $item['field_type'] == 'checkbox_group' ) && ( ! empty( $item['field_minchecks'] ) || ! empty( $item['field_maxchecks'] ) ) ) {

		$field_minchecks = $field_maxchecks = 0;
		if ( ! empty( $item['field_minchecks'] ) && $item['field_minchecks'] > 0 ) {

			$field_minchecks = $item['field_minchecks'];
			$attributes['data-field-minchecks'] = $field_minchecks;
			$attributes['data-field-minchecks-error'] = esc_attr( apply_filters(
				'pewc_filter_minchecks_notice',
				sprintf(
					__( '%s requires at least %s items to be selected', 'pewc' ),
					$label,
					$field_minchecks
				),
				$label,
				$field_minchecks
			) );

		}
		if ( ! empty( $item['field_maxchecks'] ) && $item['field_maxchecks'] > 0 ) {

			$field_maxchecks = $item['field_maxchecks'];
			$attributes['data-field-maxchecks'] = $field_maxchecks;
			$attributes['data-field-maxchecks-error'] = esc_attr( apply_filters(
				'pewc_filter_maxchecks_notice',
				sprintf(
					__( '%s requires a maximum of %s items to be selected', 'pewc' ),
					$label,
					$field_maxchecks
				),
				$label,
				$field_maxchecks
			) );

		}
		// there's no equal validation in functions-cart.php, so maybe comment this out for now to be consistent
		/*
		if ( $field_minchecks == $field_maxchecks && $field_minchecks > 0) {

			$attributes['data-field-exact-error'] = esc_attr( apply_filters(
				'pewc_filter_exactchecks_notice',
				sprintf(
					__( '%s requires exactly %s items to be selected', 'pewc' ),
					$label,
					$item['field_minchecks']
				),
				$label,
				$item['field_minchecks']
			) );

		}
		*/

	} else if ( ( $item['field_type'] == 'number' || $item['field_type'] == 'name_price' ) && ( ! empty( $item['field_minval'] ) || ! empty( $item['field_maxval'] ) ) ) {

		$field_minval = $field_maxval = 0;
		if ( ! empty( $item['field_minval'] ) && $item['field_minval'] > 0 ) {

			$field_minval = $item['field_minval'];
			$attributes['data-field-minval'] = $field_minval;
			$attributes['data-field-minval-error'] = esc_attr( apply_filters( 'pewc_filter_minval_validation_notice', esc_html( $label ) . __( ': minimum value is ', 'pewc' ) . esc_html( $field_minval ) ) );

		}
		if ( ! empty( $item['field_maxval'] ) && $item['field_maxval'] > 0 ) {

			$field_maxval = $item['field_maxval'];
			$attributes['data-field-maxval'] = $field_maxval;
			$attributes['data-field-maxval-error'] = esc_attr( apply_filters( 'pewc_filter_maxval_validation_notice', esc_html( $label ) . __( ': maximum value is ', 'pewc' ) . esc_html( $field_maxval ) ) );

		}

	} else if ( ( $item['field_type'] == 'text' || $item['field_type'] == 'textarea' || $item['field_type'] == 'advanced-preview' ) && ( ! empty( $item['field_minchars'] ) || ! empty( $item['field_maxchars'] ) ) ) {

		$field_minchars = $field_maxchars = 0;
		if ( ! empty( $item['field_minchars'] ) && $item['field_minchars'] > 0 ) {
			$field_minchars = $item['field_minchars'];
			$attributes['data-field-minchars-error'] = esc_attr( apply_filters( 'pewc_filter_minchars_validation_notice', esc_html( $label ) . __( ': minimum number of characters: ', 'pewc' ) . esc_html( $field_minchars ), $label, $item ) );
		}
		if ( ! empty( $item['field_maxchars'] ) && $item['field_maxchars'] > 0 ) {
			$field_maxchars = $item['field_maxchars'];
			$attributes['data-field-maxchars-error'] = esc_attr( apply_filters( 'pewc_filter_maxchars_validation_notice', esc_html( $label ) . __( ': maximum number of characters: ', 'pewc' ) . esc_html( $field_maxchars ), $label, $item ) );
		}

	}

	return $attributes;
}

/**
 * Hide subtotals until all required fields are completed?
 * @since 3.12.2
 */
function pewc_hide_totals_validation( $product=false ) {
	$hide = get_option( 'pewc_hide_totals_validation', 'no' );
	return apply_filters( 'pewc_hide_totals_validation', ( 'yes' === $hide ), $product );
}

/**
 * Disable next groups until all required fields in the current group are completed
 * @since 3.13.7
 */
function pewc_disable_groups_required_completed( $product=false ) {
	$disable = get_option( 'pewc_disable_groups_required_completed', 'no' );
	return apply_filters( 'pewc_disable_groups_required_completed', ( 'yes' === $disable ), $product );
}

/**
 * Add a custom class used by disable groups
 * Uses filter hook "pewc_filter_group_wrapper_class"
 * @since 3.15.0
 */
function custom_group_wrapper_disable_groups( $group_classes, $group_id, $group, $post_id ) {
	if ( $post_id ) {
		$product = wc_get_product( $post_id );
		if ( $product && pewc_disable_groups_required_completed( $product ) ) {
			$group_classes[] = 'pewc-disable-group-class';
		}
	}
	return $group_classes;
}

/**
 * Custom action, adds notice container at the end of a group if disable groups is enabled
 * Uses action hook "pewc_end_group_content_wrapper"
 * @since 3.15.0
 */
function custom_end_group_content_wrapper_disable_groups( $group, $group_id, $display, $product_extra_groups, $product ) {
	if ( $product && pewc_disable_groups_required_completed( $product ) ) {
		printf(
			'<div class="pewc-group-js-validation-notice">%s</div>',
			apply_filters( 
				'pewc_group_js_validation_notice',
				__( 'Some fields in this group failed validation.', 'pewc' ), 
				$product,
				$group_id )
			);
	}
}

/**
 * Disable the scroll animation when a product with Steps layout fails validation
 * @since 3.15.0
 */
function pewc_disable_scroll_on_steps_validation( $product=false ) {
	$disable = get_option( 'pewc_disable_scroll_on_steps_validation', 'no' );
	return apply_filters( 'pewc_disable_scroll_on_steps_validation', ( 'yes' === $disable ), $product );
}
