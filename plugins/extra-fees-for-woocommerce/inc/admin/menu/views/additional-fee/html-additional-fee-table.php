<?php
/**
 * Extra fees Additional fee table.
 */
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<table class="efw-additional-fee-rule-content-wrapper form-table">
	<tr valign="top">
		<th><?php esc_html_e('Minimum Quantity', 'extra-fees-for-woocommerce'); ?></th>
		<th><?php esc_html_e('Maximum Quantity', 'extra-fees-for-woocommerce'); ?></th>
		<th><?php esc_html_e('Fee Value', 'extra-fees-for-woocommerce'); ?></th>
		<th><?php esc_html_e('Remove', 'extra-fees-for-woocommerce'); ?></th>
	</tr>
	<tbody class="efw-additional-fee-rule-content" data-new_rule="<?php echo esc_attr( $new_rule ); ?>">
		<?php
		if ( efw_check_is_array( $additional_fee_rule_ids ) ) {
			foreach ( $additional_fee_rule_ids as $additional_fee_rule_id) { 
				$additional_fee = efw_get_additional_fee( $additional_fee_rule_id );

				if ( ! is_object( $additional_fee ) ) {
					continue;
				}

				include 'additional-fee-rule.php';
			}
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<input type="button" class="efw-add-new-additional-fee" value="<?php esc_attr_e('Add new', 'extra-fees-for-woocommerce') ; ?>"/>
			</td>
		</tr>
	</tfoot>
</table>
