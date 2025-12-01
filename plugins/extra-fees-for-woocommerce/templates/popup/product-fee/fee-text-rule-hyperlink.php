<?php
/**
 * This template displays product fee rule for products as hyperlink in product page.
 *
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/product-fee/fee-text-rule-hyperlink.php.
 *
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<a href="#efw_fee_desc_rule_popup" 
   class="efw-fee-desc-rule-popup" 
   data-rule_id= "<?php echo wp_kses_post( $rule_id ); ?>" 
   data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">
	<b><?php echo do_shortcode( $rule_fee_text ); ?></b>
</a>
<?php
