<?php
/**
 * Additional Fee Rules Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<tr class="efw-additional-fee-rules-content-wrapper">
	<td>
		<input type="number" class="efw-min-cart-qty" name="efw_additional_fee_rule[new][{{key}}][efw_min_cart_qty]" min="0" step="any"/>
	</td>
	<td>
		<input type="number" class="efw-max-cart-qty" name="efw_additional_fee_rule[new][{{key}}][efw_max_cart_qty]" min="0" step="any"/>
	</td>
	<td>
		<input type="number" class="efw-additional-fee-value" name="efw_additional_fee_rule[new][{{key}}][efw_fee_value]" min="0" step="any"/>
	</td>
	<td>
		<span class="dashicons dashicons-trash efw-remove-additional-fee"></span>
	</td>
</tr>
