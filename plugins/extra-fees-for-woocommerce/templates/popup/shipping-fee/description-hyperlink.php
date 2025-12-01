<?php
/**
 * This template displays shipping fee hyperlink in cart/checkout page.
 * 
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/shipping-fee/description-hyperlink.php.
 * 
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>
<a href="#efw_shipping_fee_desc_popup"
   class="efw-shipping-fee-desc-popup"
   data-shipping_id='<?php echo esc_attr($shipping_id); ?>'>
	<?php esc_html_e('More Info', 'extra-fees-for-woocommerce'); ?>
</a>
<?php
