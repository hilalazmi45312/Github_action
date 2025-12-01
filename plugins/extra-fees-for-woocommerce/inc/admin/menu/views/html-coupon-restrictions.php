<?php
/* Admin HTML Fee Settings for Coupon */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="efw_fee_coupon_data" class="panel woocommerce_options_panel">
	<?php
	if ('yes' === get_option('efw_productfee_enable')) {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'efw_product_fee',
				'label'       => __( 'Product Fee', 'extra-fees-for-woocommerce' ),
				'value'       => get_post_meta($coupon->get_id(), '_efw_enable_product_fee', true),
				'description' => __('If enabled, the product fee will not be applied when this coupon is applied to the order.', 'extra-fees-for-woocommerce'),
			)
		);
	}

	if ('yes' === get_option('efw_gatewayfee_enable')) {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'efw_gateway_fee',
				'label'       => __( 'Gateway Fee', 'extra-fees-for-woocommerce' ),
				'value'       => get_post_meta($coupon->get_id(), '_efw_enable_gateway_fee', true),
				'description' => __('If enabled, the payment gateway fee will not be applied when this coupon is applied to the order.', 'extra-fees-for-woocommerce'),
			)
		);
	}

	if ('yes' === get_option('efw_ordertotalfee_enable')) {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'efw_order_fee',
				'label'       => __( 'Order Fee', 'extra-fees-for-woocommerce' ),
				'value'       => get_post_meta($coupon->get_id(), '_efw_enable_order_fee', true),
				'description' => __('If enabled, the order fee will not be applied when this coupon is applied to the order.', 'extra-fees-for-woocommerce'),
			)
		);
	}

	if ('yes' === get_option('efw_shippingfee_enable')) {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'efw_shipping_fee',
				'label'       => __( 'Shipping Fee', 'extra-fees-for-woocommerce' ),
				'value'       => get_post_meta($coupon->get_id(), '_efw_enable_shipping_fee', true),
				'description' => __('If enabled, the shipping fee will not be applied when this coupon is applied to the order.', 'extra-fees-for-woocommerce'),
			)
		);
	}
	?>
</div>
<?php
