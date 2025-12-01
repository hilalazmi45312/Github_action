<?php
/**
 * The markup for an option
 *
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>


<tr class="product-extra-option-wrapper" data-option-count="<?php echo esc_attr( $option_count ); ?>">

	<td class="pewc-option-image">

		<?php $image_wrapper_classes = array(
			'pewc-field-image-' . $item_key . '_' . $option_count
		);
		$remove_class = '';
		$field_image = '';
		$src = trailingslashit( PEWC_PLUGIN_URL ) . 'assets/images/placeholder-small.png';
		$placeholder = trailingslashit( PEWC_PLUGIN_URL ) . 'assets/images/placeholder-small.png';
		$option_image = '';
		if( ! empty( $item['field_options'][$option_count]['image'] ) ) {
			$option_image = $item['field_options'][$option_count]['image'];
			$option_image_src = wp_get_attachment_image_src( $option_image );
			if ( $option_image_src ) {
				$src = $option_image_src[0];
			}
			$image_wrapper_classes[] = 'has-image';
			$remove_class = 'remove-image';
		} ?>
		<div class="pewc-field-image <?php echo join( ' ', $image_wrapper_classes ); ?>">
			<div class='image-preview-wrapper'>
				<a href="#" class="pewc-upload-button pewc-upload-option-image <?php echo esc_attr( $remove_class ); ?>" data-item-id="<?php echo esc_attr( $item_key . '_' . $option_count ); ?>">
					<img data-placeholder="<?php echo $placeholder; ?>" src="<?php echo esc_url( $src ); ?>" style="height: 30px; width: 30px;">
				</a>
			</div>
			<input type="hidden" name="_product_extra_groups_<?php echo esc_attr( $group_id ); ?>_<?php echo esc_attr( $item_key ); ?>[field_options][<?php echo esc_attr( $option_count ); ?>][image]" class="pewc-image-attachment-id pewc-image-std-attachment-id" value="<?php echo esc_attr( $option_image ); ?>">
		</div>
	</td>

	<td class="pewc-option-image">

		<?php $image_wrapper_classes = array(
			'pewc-field-image-swatch-' . $item_key . '_' . $option_count
		);
		$remove_class = '';
		$field_image = '';
		$src = trailingslashit( PEWC_PLUGIN_URL ) . 'assets/images/placeholder-small.png';
		$placeholder = trailingslashit( PEWC_PLUGIN_URL ) . 'assets/images/placeholder-small.png';
		$option_image = '';
		if( ! empty( $item['field_options'][$option_count]['image_alt'] ) ) {
			$option_image = $item['field_options'][$option_count]['image_alt'];
			$option_image_src = wp_get_attachment_image_src( $option_image );
			if ( $option_image_src ) {
				$src = $option_image_src[0];
			}
			$image_wrapper_classes[] = 'has-image';
			$remove_class = 'remove-image';
		} ?>
		<div class="pewc-field-image <?php echo join( ' ', $image_wrapper_classes ); ?>">
			<div class='image-preview-wrapper'>
				<a href="#" class="pewc-upload-button pewc-upload-option-image pewc-upload-option-image-alt <?php echo esc_attr( $remove_class ); ?>" data-item-id="<?php echo esc_attr( $item_key . '_' . $option_count ); ?>">
					<img data-placeholder="<?php echo $placeholder; ?>" src="<?php echo esc_url( $src ); ?>" style="height: 30px; width: 30px;">
				</a>
			</div>
			<input type="hidden" name="_product_extra_groups_<?php echo esc_attr( $group_id ); ?>_<?php echo esc_attr( $item_key ); ?>[field_options][<?php echo esc_attr( $option_count ); ?>][image_alt]" class="pewc-image-attachment-id pewc-image-alt-attachment-id" value="<?php echo esc_attr( $option_image ); ?>">
		</div>
	</td>

	<td class="pewc-option-hex">

		<?php $option_hex = isset( $item['field_options'][$key]['hex'] ) ? $item['field_options'][$key]['hex'] : ''; ?>
		<input type="text" class="pewc-field-option-hex pewc-field-color" name="_product_extra_groups_<?php echo esc_attr( $group_id ); ?>_<?php echo esc_attr( $item_key ); ?>[field_options][<?php echo esc_attr( $option_count ); ?>][hex]" value="<?php echo esc_attr( $option_hex ); ?>">

	</td>
	
	<td class="pewc-option-option">

		<?php $option_value = isset( $item['field_options'][$key]['value'] ) ? $item['field_options'][$key]['value'] : ''; ?>
		<input type="text" class="pewc-field-option-value" name="_product_extra_groups_<?php echo esc_attr( $group_id ); ?>_<?php echo esc_attr( $item_key ); ?>[field_options][<?php echo esc_attr( $option_count ); ?>][value]" value="<?php echo esc_attr( $option_value ); ?>">

	</td>

	<td class="pewc-option-price">

		<?php $option_price = isset( $item['field_options'][$key]['price'] ) ? $item['field_options'][$key]['price'] : ''; ?>
		<?php 
		// 3.26.0
		if ( pewc_formulas_in_prices_enabled( $post_id ) ) { ?>
			<input type="text" class="pewc-field-option-price" name="_product_extra_groups_<?php echo esc_attr( $group_id ); ?>_<?php echo esc_attr( $item_key ); ?>[field_options][<?php echo esc_attr( $option_count ); ?>][price]" value="<?php echo esc_attr( $option_price ); ?>">
		<?php } else { ?>
			<input type="number" class="pewc-field-option-price" name="_product_extra_groups_<?php echo esc_attr( $group_id ); ?>_<?php echo esc_attr( $item_key ); ?>[field_options][<?php echo esc_attr( $option_count ); ?>][price]" value="<?php echo esc_attr( $option_price ); ?>" step="<?php echo apply_filters( 'pewc_field_item_price_step', '0.01', $item ); ?>">
		<?php } ?>

	</td>

	<?php do_action( 'pewc_after_option_params', $option_count, $group_id, $item_key, $item, $key ); ?>

	<td class="product-extra-field-10 pewc-actions pewc-select-actions">

		<span class="sort-option pewc-action"><span class="dashicons dashicons-menu"></span></span>
		<span class="remove-option pewc-action"><?php _e( 'Remove', 'pewc' ); ?></span>

	</td>

</tr>

<?php do_action( 'pewc_after_option_row', $option_count, $group_id, $item_key, $item, $key ); ?>
