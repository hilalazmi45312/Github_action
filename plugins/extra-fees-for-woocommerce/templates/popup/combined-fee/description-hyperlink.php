<?php
/**
 * This template displays combined fee hyperlink in cart/checkout page.
 * 
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/combined-fee/description-hyperlink.php.
 * 
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>
<a href="#efw_combined_fee_desc_popup"
   class="efw-combined-fee-desc-popup" data-fee_details="<?php echo wc_esc_json( wp_json_encode($fee_detail)); ?>">
	<?php esc_html_e('More Info', 'extra-fees-for-woocommerce'); ?>
</a>
<?php
