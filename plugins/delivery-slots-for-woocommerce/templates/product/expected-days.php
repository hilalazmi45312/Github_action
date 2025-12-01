<?php
/**
 * This template displays the expected days field.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/product/expected-days.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 1.0.0
 * @var string $msg Expected product delivery date info message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="dey-product-delivery-slots-fields-wrapper dey-product-expected-delivery-info-wrapper"><?php echo wp_kses_post( $msg ); ?></div>  
