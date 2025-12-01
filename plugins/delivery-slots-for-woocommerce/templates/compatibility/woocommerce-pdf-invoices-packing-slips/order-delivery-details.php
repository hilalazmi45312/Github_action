<?php
/**
 * This template displays the order delivery details.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/compatibility/woocommerce-pdf-invoices-packing-slips/order-delivery-details.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.7.0
 * @var array $delivery_details Delivery details.
 * @var string $order_scheduler_type Order scheduler type.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<h2><b>
	<?php
	if ( 'order_local_pickup' === $order_scheduler_type ) :
		esc_html_e( 'Local Pickup Details', 'delivery-slots-for-woocommerce' );
	else :
		esc_html_e( 'Order Delivery Details', 'delivery-slots-for-woocommerce' );
	endif;
	?>
</b></h2>
<br/>
<table class='dey-delivery-order-details'>
	<?php foreach ( $delivery_details as $key => $delivery_detail ) : ?>
		<tr class="dey-delivery-order-<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?>">
			<th><?php echo esc_html( $delivery_detail['label'] ) . ': '; ?></th>
			<td><?php echo wp_kses_post( $delivery_detail['value'] ); ?></td>
		</tr>
	<?php endforeach; ?>
</table>
