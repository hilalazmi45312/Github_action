<?php
/**
 * This template displays the order delivery details.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/order-delivery-details.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 1.0.0
 * @var array $delivery_details Delivery details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

foreach ( $delivery_details as $key => $delivery_detail ) :
	?>
	<p class="dey-delivery-order-details dey-delivery-order-<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?>">
		<b><?php echo esc_html( $delivery_detail['label'] ); ?></b>
		<?php echo wp_kses_post( $delivery_detail['value'] ); ?>
	</p>
	<?php
endforeach;
