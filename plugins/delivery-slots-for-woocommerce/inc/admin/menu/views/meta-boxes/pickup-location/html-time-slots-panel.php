<?php
/**
 * Pickup location panel - Time slots.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_order_pickup_scheduler_data_time_slots' class='dey-order-pickup-scheduler-options-wrapper woocommerce_options_panel'>
	<div class='dey-order-scheduler-time-slots-general-wrapper'>
		<h4><?php esc_html_e( 'Pickup Location Time Slots', 'delivery-slots-for-woocommerce' ); ?></h4>
		<?php
		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Force Users to Select a Time Slot', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_time_slot_required',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'dey-order-local-pickup-time-slot-field',
				'value'   => $dey_pickup_location->get_pickup_time_slot_required(),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Display As Soon as Possible Option in Time Slot', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_as_soon_as_possible',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'dey-order-local-pickup-time-slot-field',
				'value'   => $dey_pickup_location->get_pickup_as_soon_as_possible(),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Display the First Available Time Slot by Default', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_first_available_time_slot',
				'type'    => 'checkbox',
				'default' => 'no',
				'value'   => $dey_pickup_location->get_pickup_first_available_time_slot(),
				'class'   => 'dey-order-local-pickup-time-slot-field',
				'desc'    => __( 'When enabled, the first available time slot will be the default value in the time slot picker.', 'delivery-slots-for-woocommerce' ),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Hide the Time Slot Price if it is zero', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_time_slot_hide_zero_price',
				'type'    => 'checkbox',
				'default' => 'no',
				'value'   => $dey_pickup_location->get_pickup_time_slot_hide_zero_price(),
				'class'   => 'dey-order-local-pickup-time-slot-field',
			)
		);

		woocommerce_wp_text_input(
			array(
				'label'             => __( 'Global Maximum Pickup Per Day', 'delivery-slots-for-woocommerce' ),
				'id'                => 'dey_order_pickup_time_slot_max',
				'type'              => 'number',
				'default'           => '',
				'value'             => $dey_pickup_location->get_pickup_time_slot_max(),
				'class'             => 'dey-order-local-pickup-time-slot-field',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			)
		);
		?>
	</div>
	<div class='options_group'>
	<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_order_pickup_time_slots_mode',
				'label'   => __( 'Time Slots Based on', 'delivery-slots-for-woocommerce' ),
				'options' => array(
					'1' => __( 'Global Level', 'delivery-slots-for-woocommerce' ),
					'2' => __( 'Rule Level', 'delivery-slots-for-woocommerce' ),
				),
				'class'   => 'dey-order-pickup-location-time-slots-mode',
				'value'   => $dey_pickup_location->get_time_slots_mode(),
				'default' => '1',
			)
		);
		?>
	</div>
	<div class='options_group dey-order-pickup-scheduler-time-slots-wrapper'>
		<h4><?php esc_html_e( 'Time Slots', 'delivery-slots-for-woocommerce' ); ?></h4>
		<a href='#dey_order_pickup_location_time_slot_modal' class='button dey-add-new-order-time-slot' rel='modal:open'><?php esc_html_e( 'Add New Time Slot', 'delivery-slots-for-woocommerce' ); ?></a>
		<?php require_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/popup/html-order-pickup-location-time-slot.php'; ?>
		<div class='dey-order-time-slots-inner-wrapper'>
			<?php
			$time_slots = array_filter( (array) $dey_pickup_location->get_time_slots() );
			if ( dey_check_is_array( $time_slots ) ) :
				foreach ( $time_slots as $key => $time_slot ) :
					include 'html-time-slot.php';
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>
<?php
