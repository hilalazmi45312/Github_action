<?php
/**
 * A products field template for the radio list layout
 * @since 3.9.8
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

$number_columns = ( isset( $item['number_columns'] ) ) ? $item['number_columns'] : 3;
$radio_wrapper_classes = array(
	'pewc-radio-list-wrapper',
	'pewc-radio-images-wrapper',
	'child-product-wrapper'
);
$radio_wrapper_classes[] = 'pewc-columns-' . intval( $number_columns );
if( ! empty( $item['hide_labels'] ) ) {
	$radio_wrapper_classes[] = 'pewc-hide-labels';
}

if( ! empty( $item['products_quantities'] ) ) {
	$products_quantities = ! empty( $item['products_quantities'] ) ? $item['products_quantities'] : '';
	$radio_wrapper_classes[] = 'products-quantities-' . $item['products_quantities'];
}  ?>

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

		$name = apply_filters( 'pewc_child_product_title', get_the_title( $child_product_id ), $child_product ) . apply_filters( 'pewc_option_price_separator', '+', $item ) . '<span class="pewc-child-product-price-label">' . apply_filters( 'pewc_option_price', $price, $item, $child_product ) . '</span>';

		$field_name = $id . '_child_product';

		$radio_id = $id . '_' . $child_product_id;

		$wrapper_classes = array(
			'pewc-radio-wrapper'
		);
		if( $disabled ) {
			$wrapper_classes[] = 'pewc-checkbox-disabled';
		}

		$checked = ( $value == $child_product_id || ( is_array( $value ) && in_array( $child_product_id, $value ) ) ) ? 'checked="checked"' : '';

		// 3.26.0
		if ( $checked && empty( $quantity_field_values ) ) {
			$quantity_field_values = array( 1 );
		}

		$product_attributes = pewc_get_child_product_attributes( $child_product_id, $child_product, $item );

		$child_product_html = sprintf(
			'<div class="%s" %s>',
			join( ' ', apply_filters( 'pewc_child_product_wrapper_classes', $wrapper_classes, $child_product_id, $item ) ),
			join( ' ', $product_attributes )
		);

		$child_product_html .= sprintf(
			'<label for="%s" class="pewc-radio-list-label-wrapper">
				<input %s data-stock="%s" data-option-cost="%s" data-field-label="%s" type="radio" name="%s[]" id="%s" class="pewc-radio-form-field" value="%s" %s>
				<span class="pewc-theme-element"></span>
				<div class="pewc-radio-list-desc-wrapper">%s</div>
			</label>',
			esc_attr( $radio_id ),
			esc_attr( $disabled ),
			esc_attr( $available_stock ),
			esc_attr( $option_cost ),
			get_the_title( $child_product_id ),
			esc_attr( $field_name ),
			esc_attr( $radio_id ),
			esc_attr( $child_product_id ),
			esc_attr( $checked ),
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
