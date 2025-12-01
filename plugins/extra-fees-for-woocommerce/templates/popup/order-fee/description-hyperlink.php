<?php
/**
 * This template displays order fee hyperlink in cart/checkout page.
 * 
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/order-fee/description-hyperlink.php.
 * 
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>
<a href="#efw_order_fee_desc_popup"
   class="efw-order-fee-desc-popup">
	<?php esc_html_e('More Info', 'extra-fees-for-woocommerce'); ?>
</a>
<?php
