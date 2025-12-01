<?php
/**
 * Product delivery time slots panel.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div id="dey_delivery_slots_data_time_slots" class="dey-delivery-slots-options-wrapper woocommerce_options_panel">
	<div class="options_group">
		<?php
		woocommerce_wp_checkbox(
				array(
					'id'    => 'dey_delivery_time_slot_mandatory_field',
					'label' => __( 'Force Users to Select a Time Slot' , 'delivery-slots-for-woocommerce' ),
				)
		) ;
		woocommerce_wp_checkbox(
				array(
					'id'    => 'dey_delivery_enable_as_soon_as_possible',
					'label' => __( 'Display As Soon as Possible Option in Time Slot' , 'delivery-slots-for-woocommerce' ),
				)
		) ;
		woocommerce_wp_checkbox(
				array(
					'id'          => 'dey_delivery_first_available_time_slot',
					'label'       => __( 'Display the First Available Time Slot by Default' , 'delivery-slots-for-woocommerce' ),
					'description' => __( 'When enabled, the first available time slot will be the default value in the time slot picker.' , 'delivery-slots-for-woocommerce' ),
				)
		) ;
				woocommerce_wp_checkbox(
				array(
					'id'          => 'dey_delivery_time_slot_hide_zero_price',
					'label'       => __( 'Hide the Time Slot Price if it is zero' , 'delivery-slots-for-woocommerce' ),
				)
				) ;
				woocommerce_wp_text_input(
				array(
					'id'                => 'dey_delivery_time_slot_max',
					'label'             => __( 'Global Maximum Deliveries Per Day' , 'delivery-slots-for-woocommerce' ),
					'type'              => 'number',
					'custom_attributes' => array( 'min' => 1, 'step' => 1 ),
				)
				) ;
				?>
	</div>
	<div class="options_group dey-delivery-time-slots-wrapper">
		<h4><?php esc_html_e( 'Time Slots' , 'delivery-slots-for-woocommerce' ) ; ?></h4>
		<input type="button" class="dey-add-delivery-time-slot" value="<?php esc_attr_e( 'Add Time Slot' , 'delivery-slots-for-woocommerce' ) ; ?>"/>

		<div class="dey-delivery-time-slots-inner-wrapper">
			<?php
			$time_slots = array_filter( ( array ) get_post_meta( $thepostid , 'dey_delivery_time_slots' , true ) ) ;
			if ( dey_check_is_array( $time_slots ) ) :
				foreach ( $time_slots as $key => $time_slot ) :
					include 'html-product-delivery-time-slot.php' ;
				endforeach ;
			endif ;
			?>
		</div>
	</div>
</div>
<?php
