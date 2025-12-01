<?php
/**
 * This template displays the product scheduler field.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/product/product-scheduler.php
 *
 * To maintain compatibility, Delivery Slots for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.5.0
 * @var int $product_id Product ID.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<div class='dey-product-scheduler-fields-wrapper' data-is_product_scheduler_type="<?php echo esc_attr( dey_is_user_selection_type( $product_id ) && dey_is_product_scheduler() ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>">
	<?php
	if ( dey_is_user_selection_type( $product_id ) && dey_is_product_scheduler() ) :
		?>
		<div class='form-row'>
			<div class='dey-product-delivery-scheduler-button'>
				<input type='radio' id='dey_product_delivery_scheduler_type' name='dey_product_scheduler_type' class='dey-product-scheduler-type' value='product-delivery' checked='checked'>
				<label for='dey_product_delivery_scheduler_type'><?php esc_html_e( 'Delivery', 'delivery-slots-for-woocommerce' ); ?></label>
			</div>
			<div class='dey-product-pickup-scheduler-button'>
				<input type='radio' id='dey_product_pickup_scheduler_type' name='dey_product_scheduler_type' class='dey-product-scheduler-type' value='product-local-pickup'>
				<label for='dey_product_pickup_scheduler_type'><?php esc_html_e( 'Local Pickup', 'delivery-slots-for-woocommerce' ); ?></label>
			</div>
		</div>
		<?php
	endif;

	/**
	 * This hook is used to displays the product scheduler fields.
	 *
	 * @since 3.5.0
	 */
	do_action( 'dey_handle_product_scheduler_fields', $product_id );
	?>
</div>  
