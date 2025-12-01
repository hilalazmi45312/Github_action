<?php
/**
 * Delivery and Pickup scheduler block preview template
 * 
 * @since 3.7.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='dey-scheduler-block-preview_wrapper'>
	<div class='form-row'>
		<div class='dey-order-delivery-scheduler-button'>
			<label for='dey_order_delivery_scheduler_type'><?php esc_html_e('Delivery', 'delivery-slots-for-woocommerce'); ?></label>
			<input type='radio' class='dey-order-scheduler-type' checked='checked' >
		</div>
		<div class='dey-order-pickup-scheduler-button'>
			<label for='dey_order_pickup_scheduler_type'><?php esc_html_e('Local Pickup', 'delivery-slots-for-woocommerce'); ?></label>
			<input type='radio' class='dey-order-scheduler-type' value='order-local-pickup'>
		</div>
	</div>
	<div class='dey-delivery-fields'>
		<label for='dey-delivery-date-field'><?php echo esc_html(dey_get_order_delivery_date_field_label()); ?><span class='dey-required'>*</span></label>
		<p><input class='dey-delivery-date-field' /></p>
	</div>
</div>
<?php
