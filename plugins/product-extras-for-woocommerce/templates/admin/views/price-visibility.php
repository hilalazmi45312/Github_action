<?php
/**
 * The markup for a field item in the admin
 *
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( isset( $group_id ) && isset( $item_key ) ) {

	$field_name = '_product_extra_groups_' . esc_attr( $group_id ) . '_' . esc_attr( $item_key ) . '[field_visibility]';
	$field_id = 'field_visibility_' . esc_attr( $group_id ) . '_' . esc_attr( $item_key );

	$price_field_name = '_product_extra_groups_' . esc_attr( $group_id ) . '_' . esc_attr( $item_key ) . '[price_visibility]';
	$price_field_id = 'price_visibility_' . esc_attr( $group_id ) . '_' . esc_attr( $item_key );

	$option_field_name = '_product_extra_groups_' . esc_attr( $group_id ) . '_' . esc_attr( $item_key ) . '[option_price_visibility]';
	$option_field_id = 'option_price_visibility_' . esc_attr( $group_id ) . '_' . esc_attr( $item_key );

} else {

	// This must be a new field
	$field_name = '';
	$field_id = '';

	$price_field_name = '';
	$price_field_id = '';

	$option_field_name = '';
	$option_field_id = '';

} ?>

<div class="pewc-fields-wrapper">

	<div class="product-extra-field-third">

		<label>
			<?php _e( 'Field Visibility', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Decide on what pages to show the field', 'pewc' ); ?>
		</label>

		<select class="pewc-field-item pewc-field-visibility" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>">
			<?php
			$options = array(
				'visible'			=> __( 'Display everywhere', 'pewc' ),
				'display_product'	=> __( 'Display on product page only', 'pewc' ),
				'hide_product'		=> __( 'Hide on product page only', 'pewc' ),
				'hide_customer'		=> __( 'Hide from customer', 'pewc' ),
			);
			$field_visibility = isset( $item['field_visibility'] ) ? $item['field_visibility'] : 'visible';
			foreach( $options as $key=>$value ) {
				$selected = selected( $field_visibility, $key, false );
				echo '<option ' . $selected . ' value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			} ?>
		</select>

	</div>

	<div class="product-extra-field-third pewc-visibility-fields">

		<label>
			<?php _e( 'Field Price Visibility', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Decide on what pages to show the price', 'pewc' ); ?>
		</label>

		<select class="pewc-field-item pewc-field-price-visibility" name="<?php echo esc_attr( $price_field_name ); ?>" id="<?php echo esc_attr( $price_field_id ); ?>">
			<?php
			$options = array(
				'visible'		=> __( 'Visible', 'pewc' ),
				'product'		=> __( 'Hide on product page only', 'pewc' ),
				'hidden'		=> __( 'Hidden', 'pewc' ),
			);
			$price_visibility = isset( $item['price_visibility'] ) ? $item['price_visibility'] : 'visible';
			foreach( $options as $key=>$value ) {
				$selected = selected( $price_visibility, $key, false );
				echo '<option ' . $selected . ' value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			} ?>
		</select>

	</div>

	<div class="product-extra-field-third pewc-visibility-fields pewc-option-price-visibility-field">

		<label>
			<?php _e( 'Option Price Visibility', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Decide on what pages to show option prices. Choose Value Only if you only want to use the option price in a calculation.', 'pewc' ); ?>
		</label>

		<select class="pewc-field-item pewc-option-price-visibility" name="<?php echo esc_attr( $option_field_name ); ?>" id="<?php echo esc_attr( $option_field_id ); ?>">
			<?php
			$options = array(
				'visible'		=> __( 'Visible', 'pewc' ),
				'product'		=> __( 'Hide on product page only', 'pewc' ),
				'hidden'		=> __( 'Hidden', 'pewc' ),
			);

			if ( pewc_is_pro() ) {
				// this is used for Calculation fields, and they are only available on Pro
				$options['value'] = __( 'Value Only', 'pewc' );
			}

			$option_price_visibility = isset( $item['option_price_visibility'] ) ? $item['option_price_visibility'] : 'all';
			foreach( $options as $key=>$value ) {
				$selected = selected( $option_price_visibility, $key, false );
				echo '<option ' . $selected . ' value="' . esc_attr( $key ) . '"';
				// 3.26.16, added ! empty( $item['allow_multiple'] ) to the condition to allow Value Only on Swatch fields if allow_multiple is unchecked
				if ( isset( $item ) && ( ( 'image_swatch' == $item['field_type'] && ! empty( $item['allow_multiple'] ) ) || 'checkbox_group' == $item['field_type'] ) && 'value' == $key ) {
					echo ' disabled="disabled"';
				}
				echo '>' . esc_html( $value ) . '</option>';
			} ?>
		</select>

	</div>

</div><!-- .pewc-fields-wrapper -->
