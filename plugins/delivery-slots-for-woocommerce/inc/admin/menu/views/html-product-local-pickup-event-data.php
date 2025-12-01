<?php
/**
 * Product local pickup event data.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$event_data = dey_get_product_pickup_event_data( $product_pickup );
?>
<div class='dey-calender-event-popup'>
	<div class='dey-calender-event-title'>
		<?php
		/* translators: %1s: Item label, %2s: Item ID */
		printf( '<b>%1s:</b> #%2s', esc_html__( 'Item ID', 'delivery-slots-for-woocommerce' ), esc_attr( $product_pickup->get_product_id() ) );
		?>
		<span><?php echo wp_kses_post( dey_display_post_status( $product_pickup->get_status() ) ); ?></span>
	</div>
	<div class='dey-calender-event-content'>
		<?php
		if ( dey_check_is_array( $event_data ) ) :
			foreach ( $event_data as $data ) :
				?>
				<p class='dey-calender-event-value'>
					<label><?php echo esc_html( $data['label'] ); ?>: </label>
					<?php echo wp_kses_post( $data['value'] ); ?>
				</p>
				<?php
			endforeach;
		endif;
		?>
	</div>
</div>
<?php
