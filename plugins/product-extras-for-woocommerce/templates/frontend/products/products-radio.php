<?php
/**
 * A products field template for the radio layout
 * @since 2.2.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! pewc_is_pro() ) {
	return;
}

$number_columns = ( isset( $item['number_columns'] ) ) ? $item['number_columns'] : 3;
$radio_wrapper_classes = array(
	'pewc-radio-images-wrapper',
	'child-product-wrapper'
);

/**
* Show product names as tooltips option - check if enabled
 */
if ( pewc_get_product_tooltips() ) {
	$radio_wrapper_classes[] = 'product-tooltip-enabled';
}

$radio_wrapper_classes[] = 'pewc-columns-' . intval( $number_columns );
if( ! empty( $item['hide_labels'] ) ) {
	$radio_wrapper_classes[] = 'pewc-hide-labels';
}

$product_quantities = '';
if( ! empty( $item['products_quantities'] ) ) {
	$products_quantities = ! empty( $item['products_quantities'] ) ? $item['products_quantities'] : '';
	$radio_wrapper_classes[] = 'products-quantities-' . $item['products_quantities'];
} ?>

<div class="<?php echo join( ' ', $radio_wrapper_classes ); ?>" data-products-quantities="<?php echo esc_attr( $item['products_quantities'] ); ?>">

<?php if( $item['child_products'] ) {

	foreach( $item['child_products'] as $child_product_id ) {

		$child_product = wc_get_product( $child_product_id );
		if( ! is_object( $child_product ) || get_post_status( $child_product_id ) != 'publish' ) {
			continue;
		}

		$child_price = pewc_maybe_include_tax( $child_product, $child_product->get_price() );
		if( ! empty( $item['child_discount'] ) && ! empty( $item['discount_type'] ) ) {
			$discounted_price = pewc_get_discounted_child_price( $child_price, $item['child_discount'], $item['discount_type'] );
			$price = wc_format_sale_price( $child_price, $discounted_price );
			$option_cost = $discounted_price;
		} else {
			$price = $child_product->get_price_html();
			$option_cost = $child_price;
		}

		$option_cost = apply_filters( 'pewc_child_product_option_cost', $option_cost, $item, $child_product, $post_id );

		$disabled = '';
		if( ! $child_product->is_purchasable() || ( ! $child_product->is_in_stock() && ! $child_product->backorders_allowed() ) ) {
			$disabled = 'disabled';
		}
		// Check available stock if stock is managed
		$available_stock = '';
		if( $child_product->managing_stock() == 'yes' ) {
			$available_stock = $child_product->get_stock_quantity();
		}
		// booking-as-child-product
		$available_stock = apply_filters( 'pewc_child_product_available_stock', $available_stock, $child_product, $item, $post_id );

		//$image_url = ( get_post_thumbnail_id( $child_product_id ) ) ? wp_get_attachment_image_url( get_post_thumbnail_id( $child_product_id ), apply_filters( 'pewc_child_product_image_size', 'full', $child_product_id ) ) : wc_placeholder_img_src();
		//$image = '<img src="' . esc_url( $image_url ) . '">';
		// Use this for a proper img object. Since 3.12.1
		$image = pewc_get_swatch_image_html( array( 'image'=>get_post_thumbnail_id( $child_product_id ) ), $item );

		$child_product_title = apply_filters( 'pewc_child_product_title', get_the_title( $child_product_id ), $child_product );
		$name = $child_product_title . apply_filters( 'pewc_option_price_separator', '+', $item ) . '<span class="pewc-child-product-price-label">' . apply_filters( 'pewc_option_price', $price, $item, $child_product ) . '</span>';

		$field_name = $id . '_child_product';

		$radio_id = $id . '_' . $child_product_id;

		$wrapper_classes = array(
			'pewc-radio-wrapper',
			'pewc-radio-image-wrapper',
			'pewc-radio-checkbox-image-wrapper'
		);
		if( $disabled ) {
			$wrapper_classes[] = 'pewc-checkbox-disabled';
		}

		$checked = ( $value == $child_product_id || ( is_array( $value ) && in_array( $child_product_id, $value ) ) ) ? 'checked="checked"' : '';
		if ( ! empty( $checked ) ) {
			$wrapper_classes[] = 'checked';
		}

		$product_attributes = pewc_get_child_product_attributes( $child_product_id, $child_product, $item );

		$child_product_html = sprintf(
			'<div class="%s" %s>',
			join( ' ', apply_filters( 'pewc_child_product_wrapper_classes', $wrapper_classes, $child_product_id, $item ) ),
			join( ' ', $product_attributes )
		);

		$child_product_html .= sprintf(
			'<label for="%s"><input %s data-stock="%s" data-option-cost="%s" data-field-label="%s" type="radio" name="%s[]" id="%s" class="pewc-radio-form-field" value="%s" %s>%s<span class="pewc-theme-element"></span></label><div class="pewc-radio-image-desc">%s</div>',
				esc_attr( $radio_id ),
				esc_attr( $disabled ),
				esc_attr( $available_stock ),
				esc_attr( $option_cost ),
				get_the_title( $child_product_id ),
				esc_attr( $field_name ),
				esc_attr( $radio_id ),
				esc_attr( $child_product_id ),
				esc_attr( $checked ),
				$image,
				apply_filters( 'pewc_child_product_name', $name, $item, $available_stock, $child_product )
		);

		$child_product_html .= '</div>';

		echo apply_filters( 'pewc_filter_radio', $child_product_html, $child_product_id, $price, $id, $name, $item );

		do_action( 'pewc_after_child_product_item', $id, $child_product, $child_product_id );

	}

} ?>

</div><!-- .pewc-radio-images-wrapper -->


<?php if( $products_quantities == 'independent' ) {

	pewc_child_product_independent_quantity_field( $quantity_field_values, $child_product_id, $id, $item );

} ?>
