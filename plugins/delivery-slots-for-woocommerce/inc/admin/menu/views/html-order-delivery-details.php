<?php
/**
 * Order delivery details.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

foreach ( $order_delivery_details as $key => $delivery_detail ) :
	?>
	<p class="dey-delivery-order-details dey-delivery-order-<?php echo esc_attr( str_replace( '_' , '-' , $key ) ) ; ?>">
		<strong><?php echo esc_html( $delivery_detail[ 'label' ] ) ; ?></strong>
		<?php echo wp_kses_post( $delivery_detail[ 'value' ] ) ; ?>
	</p>
	<?php
endforeach ;
