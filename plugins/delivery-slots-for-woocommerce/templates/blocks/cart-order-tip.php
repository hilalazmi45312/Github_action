<?php
/**
 * This template displays the order tip in the cart block.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/blocks/cart-order-tip.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.7.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * This hook is used to display the content before order tip wrapper.
 *
 * @since 3.7.0
 */
do_action('dey_before_block_cart_order_tip_wrapper');
?>
<div class="<?php echo esc_attr(implode(' ', dey_get_cart_order_tip_wrapper_classes())); ?>">
	<p class="dey-tip-description"><?php echo wp_kses_post(dey_get_order_tip_description_message()); ?></p>
	<?php
	$current = dey_get_selected_order_tip();
	if (( dey_is_predefined_order_tip_type() || dey_is_mixed_order_tip_type() ) && dey_check_is_array(dey_get_order_tip_buttons())) :
		?>
		<div class="dey-order-tip-predefined-buttons">
			<?php
			foreach (dey_get_order_tip_buttons() as $button_value => $button_label) :
				?>
				<button type="button" 
						class="<?php echo esc_attr(implode(' ', dey_get_order_tip_button_classes($button_value, $current))); ?>" 
						data-amount="<?php echo esc_attr($button_value); ?>">
							<?php echo wp_kses_post($button_label); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	if (dey_is_custom_order_tip_type() || dey_is_mixed_order_tip_type()) :
		?>
		<div class="<?php echo esc_attr(implode(' ', dey_get_order_tip_custom_amount_actions_classes())); ?>">
			<p class="dey-order-tip-field-row">
				<input type="number" id="dey_order_tip_custom_amount" min="0"
					   class="dey-number-field dey-order-tip-custom-amount" 
					   step="<?php echo esc_attr(dey_number_field_step_value()); ?>"
					   placeholder="<?php echo esc_attr(dey_get_custom_tip_field_placeholder()); ?>" />

				<button type="button" class="dey-order-tip-button dey-order-tip-custom-amount-button"><?php echo esc_html(dey_get_custom_add_tip_button_label()); ?></button>
			</p>
		</div>
	<?php endif; ?>

</div>
<?php
/**
 * This hook is used to display the content after order tip wrapper.
 *
 * @since 3.7.0
 */
do_action('dey_after_block_cart_order_tip_wrapper');
