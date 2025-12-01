<?php
/**
 * Extra fees Order fee page.
 */
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<table id="efw_multiple_fee_table" class="form-table">
	<tr valign="top">
		<th><input type="checkbox" class="efw-order-fee-rule-checkbox-all" data-id="all"></th>
		<?php
		$header = efw_multiple_level_table_header();
		foreach ($header as $headers) { 
			?>
			<th><?php echo esc_attr($headers); ?></th>
			<?php
		} 
		?>
		<th></th>
	</tr>
	<?php
	$efw_fee_rule = get_option('efw_fee_rule', efw_order_fee_table_default_values());
	$required = '2' == get_option('efw_ordertotalfee_fee_configuration') ? 'required' : '';

	foreach ($efw_fee_rule as $key => $rules) { 
		?>
		<tr data-id="<?php echo esc_attr($key); ?>" valign="top">
			<td><input type="checkbox" class="efw-order-fee-rule-checkbox" data-id="<?php echo esc_attr($key); ?>"></td> 
			<td><input type="number" class="efw-min-cart-fee" name="efw_fee_rule[<?php echo esc_attr( $key ); ?>][min_cart_fee]" value="<?php echo esc_attr( $rules['min_cart_fee'] ); ?>" step="any"></td>
			<td><input type="number" class="efw-max-cart-fee" name="efw_fee_rule[<?php echo esc_attr( $key ); ?>][max_cart_fee]" value="<?php echo esc_attr( $rules['max_cart_fee'] ); ?>" step="any"></td>
			<td><select name="efw_fee_rule[<?php echo esc_attr($key); ?>][fee_type]">
				<?php
				foreach (efw_get_fee_type_options() as $option => $value) { 
					?>
					<option value="<?php echo esc_attr($option); ?>" <?php echo selected($option, $rules['fee_type']); ?> ><?php echo esc_attr($value); ?></option>
											  <?php
				} 
				?>
				</select></td>
			<td><input type="number" <?php echo esc_attr($required); ?>
					class="efw-multiple-fee-value" name="efw_fee_rule[<?php echo esc_attr( $key ); ?>][fee_value]" value="<?php echo esc_attr( $rules['fee_value'] ); ?>" step="any"></td>
			<td>
			<?php 
			if (0 !== $key) { 
				?>
					<input type="button" class="efw-multiple-fee-remove" data-id="<?php echo esc_attr( $key ); ?>" value="X">
					<?php
			} 
			?>
				</td>
		</tr>
		<?php
	} 
	?>
	<tr>
		<td colspan="5"><input type="button" class="efw-add-new-multiple-fee" value="<?php esc_attr_e('Add new', 'extra-fees-for-woocommerce') ; ?>"/>
		<input type="button" class="efw-order-fee-rule-checkbox-remove" value="<?php esc_attr_e('Remove Selected Rule', 'extra-fees-for-woocommerce') ; ?>"/></td>
	</tr>
</table>
