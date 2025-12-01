<?php
/**
 * Order delivery event data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
$event_data = dey_get_order_delivery_event_data( $order_calender ) ;
?>
<div class="dey-calender-event-popup">
	<div class="dey-calender-event-title">
		<?php echo esc_html( '#' . $order_calender->get_order_id() ) ; ?>
		<span><?php echo wp_kses_post( dey_display_post_status( $order_calender->get_status() ) ) ; ?></span>
	</div>
	<div class="dey-calender-event-content">

		<?php
		if ( dey_check_is_array( $event_data ) ) :
			foreach ( $event_data as $data ) :
				?>
				<p class="dey-calender-event-value">
					<label><?php echo esc_html( $data[ 'label' ] ) ; ?>: </label>
					<?php echo wp_kses_post( $data[ 'value' ] ) ; ?>
				</p>
				<?php
			endforeach ;
		endif ;
		?>
	</div>
</div>
<?php
