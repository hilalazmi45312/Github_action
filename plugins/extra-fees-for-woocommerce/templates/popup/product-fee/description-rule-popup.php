<?php
/**
 * This template displays product fee rule description as popup in product page.
 *
 * This template can be overridden by copying it to your theme/extra-fees-for-woocommerce/popup/product-fee/description-rule-popup.php.
 *
 * To maintain compatibility, Extra Fees For WooCommerce will update the template files and you have to copy the updated files to your theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div id="efw_fee_desc_rule_popup" class="modal" class="efw-fee-rule-description-details">
	<?php echo wp_kses_post( $fee_description ); ?>
</div>
<?php
