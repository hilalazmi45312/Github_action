<?php
/**
 * Scheduler rule panel - Time slots general.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_scheduler_rule_data_time_slots_general' class='dey-scheduler-rule-options-wrapper woocommerce_options_panel'>
	<div class='options_group dey-scheduler-rule-general-time-slot-options-wrapper dey-scheduler-rule-delivery-time-slots-wrapper'>
		<h4><?php esc_html_e( 'Order Delivery Time Slots', 'delivery-slots-for-woocommerce' ); ?></h4>
			<?php
			woocommerce_wp_checkbox(
				array(
					'label'   => __( 'Force Users to Select a Time Slot', 'delivery-slots-for-woocommerce' ),
					'default' => 'no',
					'id'      => 'dey_order_delivery_time_slot_required',
					'class'   => 'dey-order-delivery-time-slot-field',
					'value'   => $dey_scheduler_rule->get_delivery_time_slot_required(),
				)
			);

			woocommerce_wp_checkbox(
				array(
					'label'   => __( 'Display As Soon as Possible Option in Time Slot', 'delivery-slots-for-woocommerce' ),
					'default' => 'no',
					'id'      => 'dey_order_delivery_as_soon_as_possible',
					'class'   => 'dey-order-delivery-time-slot-field',
					'value'   => $dey_scheduler_rule->get_delivery_as_soon_as_possible(),
				)
			);

			woocommerce_wp_checkbox(
				array(
					'label'       => __( 'Display the First Available Time Slot by Default', 'delivery-slots-for-woocommerce' ),
					'default'     => 'no',
					'id'          => 'dey_order_delivery_first_available_time_slot',
					'class'       => 'dey-order-delivery-time-slot-field',
					'value'       => $dey_scheduler_rule->get_delivery_first_available_time_slot(),
					'description' => __( 'When enabled, the first available time slot will be the default value in the time slot picker.', 'delivery-slots-for-woocommerce' ),
				)
			);

			woocommerce_wp_checkbox(
				array(
					'label'   => __( 'Hide the Time Slot Price if it is zero', 'delivery-slots-for-woocommerce' ),
					'default' => 'no',
					'id'      => 'dey_order_delivery_time_slot_hide_zero_price',
					'class'   => 'dey-order-delivery-time-slot-field',
					'value'   => $dey_scheduler_rule->get_delivery_time_slot_hide_zero_price(),
				)
			);

			woocommerce_wp_text_input(
				array(
					'label'             => __( 'Maximum Deliveries Per Day', 'delivery-slots-for-woocommerce' ),
					'id'                => 'dey_order_delivery_time_slot_max',
					'type'              => 'number',
					'default'           => '',
					'class'             => 'dey-order-delivery-time-slot-field',
					'value'             => $dey_scheduler_rule->get_delivery_time_slot_max(),
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
				)
			);
			?>
	</div>
	<div class='options_group dey-scheduler-rule-general-time-slot-options-wrapper dey-scheduler-rule-local-pickup-time-slots-wrapper'>
		<h4><?php esc_html_e( 'Order Local Pickup Time Slots Settings' ); ?></h4>
		<?php
		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Force Users to Select a Time Slot', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_time_slot_required',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'dey-order-local-pickup-time-slot-field',
				'value'   => $dey_scheduler_rule->get_pickup_time_slot_required(),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Display As Soon as Possible Option in Time Slot', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_as_soon_as_possible',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'dey-order-local-pickup-time-slot-field',
				'value'   => $dey_scheduler_rule->get_pickup_as_soon_as_possible(),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'label'   => __( 'Display the First Available Time Slot by Default', 'delivery-slots-for-woocommerce' ),
				'id'      => 'dey_order_pickup_first_available_time_slot',
				'type'    => 'checkbox',
				'default' => 'no',
				'value'   => $dey_scheduler_rule->get_pickup_first_available_time_slot(),
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
				'value'   => $dey_scheduler_rule->get_pickup_time_slot_hide_zero_price(),
				'class'   => 'dey-order-local-pickup-time-slot-field',
			)
		);

		woocommerce_wp_text_input(
			array(
				'label'             => __( 'Global Maximum Pickup Per Day', 'delivery-slots-for-woocommerce' ),
				'id'                => 'dey_order_pickup_time_slot_max',
				'type'              => 'number',
				'default'           => '',
				'value'             => $dey_scheduler_rule->get_pickup_time_slot_max(),
				'class'             => 'dey-order-local-pickup-time-slot-field',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			)
		);
		?>
	</div>
</div>
<?php
