<?php
/**
 * This template displays combined fee description popup in cart/checkout page.
 * 
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/combined-fee/description-popup.php.
 * 
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>
<div id="efw_combined_fee_desc_popup" class="modal" class="efw-combined-fee-description-details">
	<?php echo wp_kses_post( $fee_description ); ?>
	<?php if ('yes' == get_option( 'efw_advance_combine_fee' )) : ?>
		<table>
			<thead>
				<tr>
					<th><?php esc_html_e('Fee Name', 'extra-fees-for-woocommerce'); ?></th>
					<th><?php esc_html_e('Fee Value', 'extra-fees-for-woocommerce'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($fee_detail['gateway_fee']['fee_value'])) : ?>
					<tr>
						<td><?php echo esc_html($fee_detail['gateway_fee']['fee_text']); ?></td>
						<td><?php echo wp_kses_post(wc_price($fee_detail['gateway_fee']['fee_value'])); ?></td>
					</tr>
					<?php 
				endif;

				if (!empty($fee_detail['order_fee']['fee_value'])) : 
					?>
					<tr>
						<td><?php echo esc_html($fee_detail['order_fee']['fee_text']); ?></td>
						<td><?php echo wp_kses_post(wc_price($fee_detail['order_fee']['fee_value'])); ?></td>
					</tr>
					<?php 
				endif;

				if (!empty($fee_detail['shipping_fee']['fee_value'])) : 
					?>
					<tr>
						<td><?php echo esc_html($fee_detail['shipping_fee']['fee_text']); ?></td>
						<td><?php echo wp_kses_post(wc_price($fee_detail['shipping_fee']['fee_value'])); ?></td>
					</tr>
					<?php 
				endif;

				if (!empty($fee_detail['product_fee']['fee_value'])) : 
					?>
					<tr>
						<td><?php echo esc_html($fee_detail['product_fee']['fee_text']); ?></td>
						<td><?php echo wp_kses_post(wc_price($fee_detail['product_fee']['fee_value'])); ?></td>
					</tr>
					<?php 
				endif;
				
				if (!empty($fee_detail['additional_fee']['fee_value'])) : 
					?>
					<tr>
						<td><?php echo esc_html($fee_detail['additional_fee']['fee_text']); ?></td>
						<td><?php echo wp_kses_post(wc_price($fee_detail['additional_fee']['fee_value'])); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php esc_html_e('Total', 'extra-fees-for-woocommerce'); ?></th>
					<td>
						<?php 
						echo wp_kses_post(
							wc_price(
								$fee_detail['gateway_fee']['fee_value'] 
								+ $fee_detail['order_fee']['fee_value'] 
								+ $fee_detail['shipping_fee']['fee_value'] 
								+ $fee_detail['product_fee']['fee_value']
								+ $fee_detail['additional_fee']['fee_value']
							)
						); 
						?>
					</td>
				</tr>
			</tfoot>
		</table>
	<?php endif; ?>
</div>
<?php
