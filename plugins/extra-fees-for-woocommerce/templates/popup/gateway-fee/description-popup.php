<?php
/**
 * This template displays gateway fee description popup in cart/checkout page.
 * 
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/gateway-fee/description-popup.php.
 * 
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>
<div id="efw_gateway_fee_desc_popup" class="modal" class="efw-gateway-fee-description-details">
	<?php echo wp_kses_post($fee_description); ?>
</div>
<?php
