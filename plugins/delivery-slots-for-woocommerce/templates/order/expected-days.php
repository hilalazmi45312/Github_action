<?php
/**
 * This template displays the expected days field.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/order/expected-days.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 1.0.0
 * @var string $msg Expected order delivery date info message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="dey-order-delivery-slots-fields-wrapper dey-order-expected-delivery-info-wrapper"><?php echo wp_kses_post( $msg ); ?></div>  
