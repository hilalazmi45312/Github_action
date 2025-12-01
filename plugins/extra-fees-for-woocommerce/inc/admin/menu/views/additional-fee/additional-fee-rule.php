<?php
/**
 * MLM Rules Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<tr class="efw-additional-fee-rules-content-wrapper">
	<td>
		<input type="number" class="efw-min-cart-qty" name="efw_additional_fee_rule[<?php echo esc_attr( $additional_fee_rule_id ); ?>][efw_min_cart_qty]" value="<?php echo esc_attr( $additional_fee->get_minimum_quantity() ); ?>" min="0" step="any"/></td>
	<td>
		<input type="number" class="efw-max-cart-qty" name="efw_additional_fee_rule[<?php echo esc_attr( $additional_fee_rule_id ); ?>][efw_max_cart_qty]" value="<?php echo esc_attr( $additional_fee->get_maximum_quantity() ); ?>" min="0" step="any"/></td>
	<td>
		<input type="number" class="efw-additional-fee-value" name="efw_additional_fee_rule[<?php echo esc_attr( $additional_fee_rule_id ); ?>][efw_fee_value]" value="<?php echo esc_attr( $additional_fee->get_fee_value() ); ?>" min="0" step="any"/></td>
	<td>
		<span class="dashicons dashicons-trash efw-remove-additional-fee"></span>
	</td>
</tr>