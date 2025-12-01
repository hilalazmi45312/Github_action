<?php
/**
 * A date field template
 * @since 2.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 3.26.5
$attributes = apply_filters( 'pewc_filter_date_field_attributes', array( 'aria-label' => strip_tags( $item['field_label'] ) ), $item );
$attribute_string = '';
if( ! empty( $attributes ) ) {
	foreach( $attributes as $attribute=>$attr_value ) {
		$attribute_string .= $attribute . '="' . esc_attr( $attr_value ) . '" ';
	}
}

printf(
	'%s<input type="text" autocomplete="off" readonly class="pewc-form-field pewc-date-field pewc-date-field-%s" id="%s" name="%s" value="%s" %s>%s',
	$open_td, // Set in functions-single-product.php
	esc_attr( $item['field_id'] ),
	esc_attr( $id ),
	esc_attr( $id ),
	esc_attr( $value ),
	$attribute_string,
	$close_td
);

$params = pewc_get_date_field_params( $item ); ?>

<script>
	jQuery( document ).ready( function($) {
		$( 'body' ).on( 'focus', '.pewc-date-field-<?php echo esc_attr( $item['field_id'] ); ?>', function() {
			var params = $( this ).attr( 'data-params' );
	    $( this ).datepicker(
				<?php
				if( $params ) {
					printf(
						'{ %s }',
						$params
					);
				} ?>
			);
		});
	});
</script>
