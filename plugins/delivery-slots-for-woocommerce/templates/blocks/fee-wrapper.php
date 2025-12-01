<?php
/**
 * This template displays the fee wrapper in the cart/checkout block.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/blocks/fee-wrapper.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.7.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='dey-block-components-totals-fees wc-block-components-totals-item wc-block-components-totals-fees wc-block-components-totals-fees__'<?php echo esc_attr($fee->id); ?>>
	<span class='wc-block-components-totals-item__label'><?php echo esc_attr($fee->name); ?></span>
	<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value"><?php echo wp_kses_post(dey_price($fee->total)); ?>
		<a href='javascript:void(0)' class='dey-cart-remove-order-tip'><?php echo esc_html(dey_get_order_tip_fee_remove_label()); ?></a>
	</span>
</div>
<?php
