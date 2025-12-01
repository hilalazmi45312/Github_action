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

if( ! isset( $item_key ) ) {
	$name = '';
	$formula_action_name = '';
	$round_name = '';
	$decimals_name = '';
	$hidden_name = '';
	$products_field_id_name = '';
	$child_qty_product_id_name = '';
	$reverse_formula_field_name = '';
	$reverse_input_field_name = '';
	$quantity_override_name = '';
} else {
	$name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[formula]';
	$formula_action_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[formula_action]';
	$round_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[formula_round]';
	$decimals_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[decimal_places]';
	$hidden_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[hidden_calculation]';
	$products_field_id_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[products_field_id]';
	$child_qty_product_id_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[child_qty_product_id]';
	$reverse_formula_field_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[reverse_formula_field]';
	$reverse_input_field_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[reverse_input_field]';
	$quantity_override_name = '_product_extra_groups_' . $group_id . '_' . $item_key . '[quantity_override]';
}

$formula = ( isset( $item['formula'] ) ) ? $item['formula'] : array(); ?>

<div class="pewc-fields-wrapper pewc-calculation-fields">

	<div class="product-extra-field product-extra-field-two-thirds">
		<?php
		$formula = isset( $item['formula'] ) ? $item['formula'] : ''; ?>
		<label>
			<?php _e( 'Formula', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Enter the formula for the calculation', 'pewc' ); ?>
		</label>
		<input type="text" class="pewc-calculation-field" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $formula ); ?>">
		<small>
			Tags:
			{product_price},
			{field_x},
			{variable_1},
			{variable_2},
			{variable_3}
		</small>
	</div>

	<div class="product-extra-field-third product-extra-field-last">
		<?php $checked = ! empty( $item['hidden_calculation'] ); ?>
		<input <?php checked( $checked, 1, true ); ?> type="checkbox" class="pewc-field-item pewc-hide-labels" name="<?php echo esc_attr( $hidden_name ); ?>" value="1">
		<label class="pewc-checkbox-field-label">
			<?php _e( 'Hide Calculation?', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Enable this option to hide the calculation', 'pewc' ); ?>
		</label>
	</div>

</div>

<div class="pewc-fields-wrapper pewc-calculation-fields">

	<div class="product-extra-field-third">

		<?php $formula_round = ! empty( $item['formula_round'] ) ? $item['formula_round'] : ''; ?>
		<label>
			<?php _e( 'Round Result', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Round the result of the calculation up or down', 'pewc' ); ?>
		</label>

		<select class="pewc-field-item pewc-field-round" name="<?php echo $round_name; ?>">
			<?php
			printf(
				'<option value="no-rounding">%s</option>',
				__( 'No rounding', 'pewc' )
			);
			printf(
				'<option %s value="floor">%s</option>',
				selected( $formula_round, 'floor', false ),
				__( 'Round down', 'pewc' )
			);
			printf(
				'<option %s value="ceil">%s</option>',
				selected( $formula_round, 'ceil', false ),
				__( 'Round up', 'pewc' )
			); ?>
		</select>

	</div>

	<div class="product-extra-field-third">

		<?php $decimal_places = ! empty( $item['decimal_places'] ) ? $item['decimal_places'] : '0'; ?>
		<label>
			<?php _e( 'Decimal Places', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Define how many decimals to return the answer to', 'pewc' ); ?>
		</label>
		<input type="number" class="pewc-field-item pewc-decimal-places" name="<?php echo esc_attr( $decimals_name ); ?>" value="<?php echo $decimal_places; ?>">

	</div>

	<div class="product-extra-field-third product-extra-field-last">

		<?php $action = ! empty( $item['formula_action'] ) ? $item['formula_action'] : ''; ?>
		<label>
			<?php _e( 'Action', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Select what to do with this field', 'pewc' ); ?>
		</label>

		<select class="pewc-field-item pewc-field-action" name="<?php echo $formula_action_name; ?>">
			<?php
			printf(
				'<option value="no-action">%s</option>',
				__( '--', 'pewc' )
			);
			printf(
				'<option %s value="cost">%s</option>',
				selected( $action, 'cost', false ),
				__( 'Display As Cost', 'pewc' )
			);
			printf(
				'<option %s value="price">%s</option>',
				selected( $action, 'price', false ),
				__( 'Set Product Price', 'pewc' )
			);
			printf(
				'<option %s value="qty">%s</option>',
				selected( $action, 'qty', false ),
				__( 'Update Quantity', 'pewc' )
			);
			printf(
				'<option %s value="child-qty">%s</option>',
				selected( $action, 'child-qty', false ),
				__( 'Set Child Product Quantity', 'pewc' )
			);
			printf(
				'<option %s value="weight">%s</option>',
				selected( $action, 'weight', false ),
				__( 'Add to Product Weight', 'pewc' )
			);
			printf(
				'<option %s value="length">%s</option>',
				selected( $action, 'length', false ),
				__( 'Add to Product Length', 'pewc' )
			);
			printf(
				'<option %s value="width">%s</option>',
				selected( $action, 'width', false ),
				__( 'Add to Product Width', 'pewc' )
			);
			printf(
				'<option %s value="height">%s</option>',
				selected( $action, 'height', false ),
				__( 'Add to Product Height', 'pewc' )
			);  ?>
		</select>

	</div>

</div>

<div class="pewc-fields-wrapper pewc-child-product-fields">

	<div class="product-extra-field-fifth">

		<?php $products_field_id = ! empty( $item['products_field_id'] ) ? $item['products_field_id'] : ''; ?>
		<label>
			<?php _e( 'Products Field ID', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Define the field ID where the quantity should be updated', 'pewc' ); ?>
		</label>
		<input type="number" class="pewc-field-item pewc-products-field-id" name="<?php echo esc_attr( $products_field_id_name ); ?>" value="<?php echo $products_field_id; ?>">

	</div>

	<div class="product-extra-field-fifth">

		<?php $child_qty_product_id = ! empty( $item['child_qty_product_id'] ) ? $item['child_qty_product_id'] : ''; ?>
		<label>
			<?php _e( 'Product ID', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Specify the ID of the child product to auto select when quantity is updated', 'pewc' ); ?>
		</label>
		<input type="number" class="pewc-field-item pewc-child-qty-product-id" name="<?php echo esc_attr( $child_qty_product_id_name ); ?>" value="<?php echo $child_qty_product_id; ?>">

	</div>

	<div class="product-extra-field-fifth">

		<?php $reverse_formula_field = ! empty( $item['reverse_formula_field'] ) ? $item['reverse_formula_field'] : ''; ?>
		<label>
			<?php _e( 'Reverse Formula Field ID', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Specify the ID of the field with the reverse formula', 'pewc' ); ?>
		</label>
		<input type="number" class="pewc-field-item pewc-reverse-formula" name="<?php echo esc_attr( $reverse_formula_field_name ); ?>" value="<?php echo $reverse_formula_field; ?>">

	</div>

	<div class="product-extra-field-fifth">

		<?php $reverse_input_field = ! empty( $item['reverse_input_field'] ) ? $item['reverse_input_field'] : ''; ?>
		<label>
			<?php _e( 'Original Input Field ID', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Specify the ID of the number field that would need to be updated by the result of the reverse formula', 'pewc' ); ?>
		</label>
		<input type="number" class="pewc-field-item pewc-reverse-input" name="<?php echo esc_attr( $reverse_input_field_name ); ?>" value="<?php echo $reverse_input_field; ?>">

	</div>

	<div class="product-extra-field-fifth product-extra-field-last">

		<?php $checked = ! empty( $item['quantity_override'] ); ?>
		<input <?php checked( $checked, 1, true ); ?> type="checkbox" class="pewc-field-item pewc-quantity-override" name="<?php echo esc_attr( $quantity_override_name ); ?>" value="1">
		<label class="pewc-checkbox-field-label">
			<?php _e( 'Override Calculation?', 'pewc' ); ?>
			<?php echo wc_help_tip( 'Enable this option to allow users to override the result of the calculation when they manually update the child product quantity', 'pewc' ); ?>
		</label>

	</div>

</div>
