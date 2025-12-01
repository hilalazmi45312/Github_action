<?php
/**
 * Scheduler rule panel - Order local pickup.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_scheduler_rule_data_local_pickup' class='dey-scheduler-rule-options-wrapper woocommerce_options_panel'>
	<div class='options_group'>
		<h3><?php esc_html_e( 'General Settings', 'delivery-slots-for-woocommerce' ); ?></h3>
		<?php
		woocommerce_wp_text_input(
			array(
				'id'                => 'dey_order_pickup_days_availability',
				'label'             => __( 'Number of Days for Pickup Availability', 'delivery-slots-for-woocommerce' ) . " <span class='required'>*</span>",
				'type'              => 'number',
				'default'           => '7',
				'value'             => $dey_scheduler_rule->get_pickup_days_availability(),
				'desc_tip'          => true,
				'description'       => __( 'The number of selectable pickup dates which is visible to the user on the checkout page.', 'delivery-slots-for-woocommerce' ),
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			)
		);

		woocommerce_wp_select(
			array(
				'label'             => __( 'Pickup Days', 'delivery-slots-for-woocommerce' ),
				'id'                => 'dey_order_pickup_days[]',
				'default'           => array( '1', '2', '3', '4', '5', '6', '7' ),
				'options'           => dey_weekdays(),
				'class'             => 'dey_select2',
				'value'             => $dey_scheduler_rule->get_pickup_days(),
				'custom_attributes' => array( 'multiple' => 'multiple' ),
				'desc_tip'          => true,
				'description'       => __( 'The pickup days can be restricted to specific days of the week.', 'delivery-slots-for-woocommerce' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'label'             => __( 'Maximum Orders per Day for Pickup', 'delivery-slots-for-woocommerce' ),
				'id'                => 'dey_order_pickup_max_per_day',
				'type'              => 'number',
				'default'           => '10',
				'value'             => $dey_scheduler_rule->get_pickup_max_per_day(),
				'desc_tip'          => true,
				'description'       => __( 'The maximum number of orders which can be picked up each day', 'delivery-slots-for-woocommerce' ),
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			)
		);
		woocommerce_wp_checkbox(
			array(
				'label'       => __( 'Force Users to select the Pickup Location', 'delivery-slots-for-woocommerce' ),
				'id'          => 'dey_order_pickup_location_required',
				'default'     => 'no',
				'value'       => $dey_scheduler_rule->get_pickup_location_required(),
				'description' => __( 'When enabled, users cannot place orders without selecting a Pickup Location.', 'delivery-slots-for-woocommerce' ),
			)
		);
		woocommerce_wp_checkbox(
			array(
				'label'       => __( 'Force Users to Select a Pickup Date', 'delivery-slots-for-woocommerce' ),
				'id'          => 'dey_order_pickup_calender_required',
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => $dey_scheduler_rule->get_pickup_calender_required(),
				'description' => __( 'When enabled, users cannot place orders without selecting a Pickup Date.', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
	</div>
	<div class='options_group'>
		<h3><?php esc_html_e( 'Time Settings', 'delivery-slots-for-woocommerce' ); ?></h3>
		<?php
		woocommerce_wp_select(
			array(
				'label'       => __( 'Time Selection Type', 'delivery-slots-for-woocommerce' ),
				'id'          => 'dey_order_pickup_time_mode',
				'default'     => '1',
				'options'     => dey_delivery_time_modes(),
				'value'       => $dey_scheduler_rule->get_pickup_time_mode(),
				'desc_tip'    => true,
				'description' => __( 'Time selector: User can select the time of pickup. Time Slots: User can select from a list of available time slots.', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
		<p class="form-field dey-order-delivery-slots-field">
			<label for="dey_time_slot"><?php esc_html_e( 'Time Selector', 'delivery-slots-for-woocommerce' ); ?> <span class='required'>*</span></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_order_pickup_available_time_from',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'class'       => 'dey-order-local-pickup-time-field',
					'value'       => $dey_scheduler_rule->get_pickup_available_time_from(),
				)
			);

			dey_get_datepicker_html(
				array(
					'name'        => 'dey_order_pickup_available_time_to',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'class'       => 'dey-order-local-pickup-time-field',
					'value'       => $dey_scheduler_rule->get_pickup_available_time_to(),
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'The minimum and maximum time which the user can select.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>
	</div>
	<div class='options_group'>
		<h3><?php esc_html_e( 'Processing Time Settings', 'delivery-slots-for-woocommerce' ); ?></h3>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_order_pickup_processing_time'><?php esc_html_e( 'Processing time', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			$option_value = $dey_scheduler_rule->get_pickup_processing_time();
			$periods      = dey_relative_date_picker_options( 2 );
			$option_value = dey_parse_relative_date_option( $option_value, 2 );
			?>
			<input type='number' min='1' step='1' name='dey_order_pickup_processing_time[number]' value="<?php echo esc_attr( $option_value['number'] ); ?>" />
			<select name='dey_order_pickup_processing_time[unit]'>
				<?php
				foreach ( $periods as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value['unit'], $value, false ) . '>' . esc_html( $label ) . '</option>';
				}
				?>
			</select>
			<?php echo wp_kses_post( wc_help_tip( __( 'The duration taken for processing the order.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>
		<p class='form-field'>
			<label for='dey_order_pickup_same_day_cutoff_time'><?php esc_html_e( 'Cut Off Time for Same Day Pickup', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_order_pickup_same_day_cutoff_time',
					'default'     => '',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => $dey_scheduler_rule->get_pickup_same_day_cutoff_time(),
					'desc_tip'    => true,
					'description' => __( 'Order processing time will start from the next day if the order is placed after the cut off time.', 'delivery-slots-for-woocommerce' ),
				)
			);
			?>
		</p>
		<p class='form-field'>
			<label for='dey_order_pickup_next_day_cutoff_time'><?php esc_html_e( 'Cut Off Time for Next Day Pickup', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_order_pickup_next_day_cutoff_time',
					'default'     => '',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => $dey_scheduler_rule->get_pickup_next_day_cutoff_time(),
					'desc_tip'    => true,
					'description' => __( 'Order processing time will start from the next day if the order is placed after the cut off time.', 'delivery-slots-for-woocommerce' ),
				)
			);
			?>
		</p>
	</div>
	<div class='options_group'>
		<h3><?php esc_html_e( 'Fee Settings', 'delivery-slots-for-woocommerce' ); ?></h3>
		<div class='dey-scheduler-rule-order-local-pickup-weekdays-prices-options'>
			<p class='form-field'>
				<?php
				esc_html_e( 'Pickup Fee for the Days of the Week', 'delivery-slots-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( __( 'You can charge a local pickup fee for specific days of the week.', 'delivery-slots-for-woocommerce' ) ) );
				?>
			</p>
			<p class='form-field'>
				<?php
				$option_value = $dey_scheduler_rule->get_pickup_weekdays_prices();
				foreach ( dey_weekdays() as $key => $label ) :
					$label .= $currency_symbol;
					$price  = isset( $option_value[ $key ] ) ? wc_format_localized_price( $option_value[ $key ] ) : '';
					?>
					<span class='dey-weekdays-prices'>
						<label><?php echo wp_kses_post( $label ); ?></label>
						<input type='text' name="dey_order_pickup_weekdays_prices[<?php echo esc_attr( $key ); ?>]" class='wc_input_price dey-order-local-pickup-weekdays-prices' value="<?php echo esc_attr( $price ); ?>"/>
					</span>
					<?php
				endforeach;
				?>
			</p>
		</div>
		<?php
		woocommerce_wp_text_input(
			array(
				'label'             => __( 'Pickup Fee for Same day', 'delivery-slots-for-woocommerce' ) . $currency_symbol,
				'id'                => 'dey_order_pickup_same_day_fee',
				'type'              => 'number',
				'default'           => '',
				'value'             => $dey_scheduler_rule->get_pickup_same_day_fee(),
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 'any',
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'label'             => __( 'Pickup Fee for Next day', 'delivery-slots-for-woocommerce' ) . $currency_symbol,
				'id'                => 'dey_order_pickup_next_day_fee',
				'type'              => 'number',
				'default'           => '',
				'value'             => $dey_scheduler_rule->get_pickup_next_day_fee(),
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 'any',
				),
			)
		);
		?>
	</div>
	<div class='options_group'>
		<h3><?php esc_html_e( 'Tax Settings', 'delivery-slots-for-woocommerce' ); ?></h3>
		<?php
		woocommerce_wp_checkbox(
			array(
				'label'       => __( 'Calculate tax', 'delivery-slots-for-woocommerce' ),
				'id'          => 'dey_order_pickup_calculate_tax',
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => $dey_scheduler_rule->get_pickup_calculate_tax(),
				'description' => __( 'You can calculate Tax for pickup Fee.', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
	</div>
</div>
<?php
