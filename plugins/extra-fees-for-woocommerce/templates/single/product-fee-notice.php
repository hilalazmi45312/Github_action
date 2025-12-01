<?php
/**
 * This template displays product fee for products in product page.
 *
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/single/product-fee-notice.php.
 *
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Hook:efw_product_fee_in_single_product_page.
 *
 * @since 1.0
 */
$productfee = apply_filters( 'efw_product_fee_in_single_product_page', $product_fee );
?>
<table class="efw-product-fee-table">
	<tr class="efw-product-fee-row">
		<td class="efw-product-fee-description">
			<?php
			$fee_description = efw_get_fee_description( $product->get_id() );
			if ( ! $fee_description ) :
				echo esc_html( $fee_text );
						else :
							efw_get_template(
								'popup/product-fee/fee-text-hyperlink.php',
								array(
									'fee_text' => $fee_text,
									'product'  => $product,
								)
							);
						endif;
						?>
		</td>
		<td class="efw-product-fee-price">
			<?php
			echo wp_kses_post( wc_price( $productfee ) );
			?>
		</td>
	</tr>
	<?php
	if ( efw_check_is_array( $rule_fee_texts ) ) :
		foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) :
			?>
			<tr class="efw-product-multiple-fee-row">
				<td class="efw-product-multiple-fee-description">
					<?php
					$rule_object = efw_get_fee_rule( $rule_id );
					if ( ! is_object( $rule_object ) ) :
						continue;
					endif;

					$min_qty = $rule_object->get_minimum_qty();
					$max_qty = $rule_object->get_maximum_qty();

					$rule_fee_text = $rule_object->get_fee_text();
					if ( empty( $min_qty ) && ! empty( $max_qty ) ) {
						$rule_fee_text = $rule_object->get_fee_text() . '<br>( Upto ' . $max_qty . ' Qty)<br>';
					} elseif ( ! empty( $min_qty ) && empty( $max_qty ) ) {
						$rule_fee_text = $rule_object->get_fee_text() . '<br>( From ' . $min_qty . ' Qty)<br>';
					} elseif ( ! empty( $min_qty ) && ! empty( $max_qty ) ) {
						$rule_fee_text = $rule_object->get_fee_text() . '<br>( From ' . $min_qty . ' to ' . $max_qty . ' Qty)<br>';
					}

					$rule_fee_descriptions = efw_get_rule_fee_descriptions( $product->get_id() );
					$rule_fee_description  = isset( $rule_fee_descriptions[ $rule_id ] ) ? $rule_fee_descriptions[ $rule_id ] : '';
					if ( ! $rule_fee_description ) :
						echo do_shortcode( $rule_fee_text );
					else :
						efw_get_template(
							'popup/product-fee/fee-text-rule-hyperlink.php',
							array(
								'rule_id'       => $rule_id,
								'product'       => $product,
								'rule_fee_text' => $rule_fee_text,
							)
						);
					endif;
					?>
				</td>
				<td class="efw-product-multiple-fee-price">
					<?php
					echo wp_kses_post( wc_price( $rule_fee_value ) );
					?>
				</td>
			</tr>
			<?php
		endforeach;
	endif;
	?>
	<tr class="efw-total-payable-amount-row">
		<td class="efw-total-payable-amount-label">
			<?php
			esc_html_e( 'Total Payable Amount', 'extra-fees-for-woocommerce' );
			?>
		</td>
		<td class="efw-total-payable-amount-price">
			<?php
			echo wp_kses_post( wc_price( $total_payable_amount + $price ) );
			?>
		</td>
	</tr>
</table>
<?php
