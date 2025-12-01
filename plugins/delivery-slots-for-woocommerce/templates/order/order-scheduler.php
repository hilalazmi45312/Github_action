<?php
/**
 * This template displays the order scheduler field.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/order/order-scheduler.php
 *
 * To maintain compatibility, Delivery Slots for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.0.0
 * @modified 4.0.0
 * @var object $scheduler_rule Scheduler rule object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class='dey-order-scheduler-fields-wrapper'>
	<?php
	$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
	if ( is_object( $scheduler_rule ) ) :
		?>
		<?php if ( dey_is_order_scheduler_type() ) : ?>
			<?php
			$selected_scheduler_type = dey_get_selected_order_scheduler_data_from_session( 'order_scheduler_type' );
			?>
			<div class='form-row'> 
				<div class='dey-order-delivery-scheduler-button'>
					<label for='dey_order_delivery_scheduler_type'><?php esc_html_e( 'Delivery', 'delivery-slots-for-woocommerce' ); ?></label>
					<input type='radio' name='dey_order_scheduler_type' id='dey_order_delivery_scheduler_type' class='dey-order-scheduler-type dey-order-delivery' value='order-delivery' <?php checked( 'order-delivery', $selected_scheduler_type, true ); ?> >
				</div>
				<div class='dey-order-pickup-scheduler-button'>
					<label for='dey_order_pickup_scheduler_type'><?php esc_html_e( 'Local Pickup', 'delivery-slots-for-woocommerce' ); ?></label>
					<input type='radio' name='dey_order_scheduler_type' id='dey_order_pickup_scheduler_type' class='dey-order-scheduler-type dey-order-local-pickup' value='order-local-pickup' <?php checked( 'order-local-pickup', $selected_scheduler_type, true ); ?>>
				</div>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * This hook is used to displays the order scheduler fields.
		 *
		 * Hook:dey_handle_order_scheduler_fields.
		 *
		 * @since 3.0.0
		 */
		do_action( 'dey_handle_order_scheduler_fields', $scheduler_rule );
		?>
		<input type='hidden' name='dey_scheduler_rule_id' value='<?php echo esc_attr( $scheduler_rule->get_id() ); ?>'>
	<?php endif; ?>
</div>  
