<?php
/* Admin HTML Fee Settings for Reports */

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<table class="efw-reports-data-content widefat">
	<tr>
		<th><b><?php esc_html_e( 'Order Fee Collected:' , 'extra-fees-for-woocommerce' ) ; ?></b></th>
		<td><?php echo wp_kses_post(wc_price( $order_fee_value )) ; ?></td>
	</tr>
	<tr>
		<th><b><?php esc_html_e( 'Product Fee Collected:' , 'extra-fees-for-woocommerce' ) ; ?></b></th>
		<td><?php echo wp_kses_post(wc_price( $product_fee_value )) ; ?></td>
	</tr>
	<tr>
		<th><b><?php esc_html_e( 'Payment Gateway Fee Collected:' , 'extra-fees-for-woocommerce' ) ; ?></b></th>
		<td><?php echo wp_kses_post(wc_price( $gateway_fee_value )) ; ?></td>
	</tr>
		<th><b><?php esc_html_e( 'Shipping Fee Collected:' , 'extra-fees-for-woocommerce' ) ; ?></b></th>
		<td><?php echo wp_kses_post(wc_price( $shipping_fee_value )) ; ?></td>
	</tr>
	<tr>
		<th><b><?php esc_html_e( 'Total Fee Collected:' , 'extra-fees-for-woocommerce' ) ; ?></b></th>
		<td><?php echo wp_kses_post(wc_price( $total_fee )) ; ?></td>
	</tr>
</table>
<?php
