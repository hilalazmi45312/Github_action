<?php
/**
 * This template displays product fee details for products in shop page.
 * 
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/loop/product-fee-details.php.
 * 
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>
<div class="efw-fee-details-wrapper">
	<?php
	/**
	 * Hook:efw_product_fee_in_shop_page. 
	 *
	 * @since 1.0
	 */
	$productfee = apply_filters('efw_product_fee_in_shop_page', $product_fee);
	?>
	<div class="efw-fee-details price">
		<p class="efw-shop-fee-content">
			<?php 
			$fee_description = efw_get_fee_description($product->get_id());
			if (!$fee_description) : 
				?>
				<label><b><?php echo esc_html($fee_text); ?></b></label>
				<?php 
			else :
				efw_get_template('popup/product-fee/fee-text-hyperlink.php', array( 'fee_text' => $fee_text, 'product' => $product ));
			endif; 
			?>
			<span><?php echo wp_kses_post(wc_price($productfee)); ?></span>
		</p>
		<?php
		if (efw_check_is_array($rule_fee_texts)) :
			foreach ($rule_fee_texts as $rule_id => $rule_fee_value) :
				$rule_object    = efw_get_fee_rule( $rule_id ) ;
				if (!is_object($rule_object)) :
					continue;
				endif;
								
				$rule_fee_text  = $rule_object->get_fee_text();
				$rule_fee_descriptions = efw_get_rule_fee_descriptions($product->get_id());
				$rule_fee_description = isset($rule_fee_descriptions[$rule_id]) ? $rule_fee_descriptions[$rule_id]:'';
				?>
				<p class="efw-shop-fee-content">
					<?php
					if (!$rule_fee_description) :
						?>
						<label><b><?php echo esc_html($rule_fee_text); ?></b></label>
						<?php
					else :
						efw_get_template('popup/product-fee/fee-text-rule-hyperlink.php', array( 'rule_id' => $rule_id, 'product' => $product, 'rule_fee_text' => $rule_fee_text ));
					endif; 
					?>
					<span><?php echo wp_kses_post(wc_price($rule_fee_value)); ?></span>
				</p>
				<?php
			endforeach;
		endif;
		?>
	</div>
	<div class="efw-total-payable-amount price">
		<label><b><?php esc_html_e('Total Payable Amount', 'extra-fees-for-woocommerce'); ?></b></label>
		<span><?php echo wp_kses_post(wc_price($total_payable_amount + $price)); ?></span>
	</div>
</div>
<?php
