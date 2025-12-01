<?php
/**
 * Functions for working with variations
 * @since 2.5.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add variation field to items in the back end
 * @since 2.5.0
 */
function pewc_variation_fields_wrapper( $group_key, $item_key, $item, $post_id ) {
	$product = wc_get_product( $post_id );
	if( $product && $product->is_type( 'variable' ) ) { ?>
		<div class="pewc-fields-wrapper pewc-fields-variations show_if_variable">
			<label><?php _e( 'Variations', 'pewc' ); ?></label>
			<?php $variations = $product->get_children();
				include( PEWC_DIRNAME . '/templates/admin/variation.php' ); ?>
		</div><!-- .pewc-fields-wrapper -->
	<?php
	}
}
add_action( 'pewc_end_product_extra_field', 'pewc_variation_fields_wrapper', 10, 4 );

/**
 * Add data-variations attributes to field item
 * @since 2.5.0
 */
function pewc_variation_item_attributes( $attributes, $item ) {
	if( ! empty( $item['variation_field'] ) ) {
		$attributes['data-variations'] = join( ',', $item['variation_field'] );
	}
	if ( ! empty( $item['products_layout'] ) && 'grid' === $item['products_layout'] ) {
		// 3.25.6
		$attributes['data-variation-quantity-symbol'] = apply_filters( 'pewc_child_products_quantity_symbol', ' x ', $item );
		$attributes['data-variation-separator'] = apply_filters( 'pewc_child_products_metadata_separator', ', ', $item );
	}
	return $attributes;
}
add_filter( 'pewc_filter_item_attributes', 'pewc_variation_item_attributes', 10, 2 );

/**
 * Filter pewc_get_conditional_field_visibility to check if a field is visible for this variant
 * @since 2.5.0
 */
function pewc_variation_field_visibility( $is_visible, $id, $item, $items, $product_id, $variation_id, $cart_item_data ) {
	// Is this a variant?
	$product = wc_get_product( $product_id );
	if( $product->get_type() == 'variable' || $product->get_type() == 'variable-subscription' ) {

		// Check if this field is visible for this variant
		if( isset( $item['variation_field'] ) && ! is_array( $item['variation_field'] ) ) {
			return $is_visible;
		}

		if( isset( $item['variation_field'] ) && ! in_array( $variation_id, $item['variation_field'] ) ) {
			// This field is not displayed for this variant
			$is_visible = false;
		}

	}

	return $is_visible;
}
add_filter( 'pewc_get_conditional_field_visibility', 'pewc_variation_field_visibility', 10, 7 );

/**
 * Replace the thumbnail on a Products or Products Categories field using the Column Layout when selecting a variation with an image
 * @since 3.26.3
 */
function pewc_column_layout_replace_thumbnail( $item=false, $child_product_id=false ) {
	return apply_filters( 'pewc_column_layout_replace_thumbnail', false, $item, $child_product_id );
}
