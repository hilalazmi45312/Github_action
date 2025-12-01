<?php
/**
 * Order Tip block preview template
 * 
 * @since 3.7.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='dey-order-tip-block-preview_wrapper'>
	<div class="<?php echo esc_attr(implode(' ', dey_get_cart_order_tip_wrapper_classes())); ?>">
		<p class='dey-tip-description'><?php echo wp_kses_post(dey_get_order_tip_description_message()); ?></p>
		<div class="<?php echo esc_attr(implode(' ', dey_get_order_tip_custom_amount_actions_classes())); ?>">
			<p class='dey-order-tip-field-row'>
				<input type='number' id='dey_order_tip_custom_amount' min='0'
					   class='dey-number-field dey-order-tip-custom-amount' 
					   step="<?php echo esc_attr(dey_number_field_step_value()); ?>"
					   placeholder="<?php echo esc_attr(dey_get_custom_tip_field_placeholder()); ?>" />
				<button type='button' class='dey-order-tip-button dey-order-tip-custom-amount-button'><?php echo esc_html(dey_get_custom_add_tip_button_label()); ?></button>
			</p>
		</div>
	</div>
</div>
<?php
