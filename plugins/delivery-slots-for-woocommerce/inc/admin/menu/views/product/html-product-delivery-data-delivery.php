<?php
/**
 * Product delivery slot delivery panel.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="dey_delivery_slots_data_delivery" class="dey-delivery-slots-options-wrapper woocommerce_options_panel">
	<div class="options_group">
		<?php
		woocommerce_wp_select(
			array(
				'id'          => 'dey_delivery_slot_mode',
				'label'       => __( 'Delivery Slot Mode', 'delivery-slots-for-woocommerce' ),
				'options'     => dey_delivery_modes(),
				'default'     => '1',
				'desc_tip'    => true,
				'description' => __( 'Calendar: User can select their preferred delivery date. Expected Delivery Info: The approximate delivery date will be displayed to the user on the checkout.', 'delivery-slots-for-woocommerce' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => 'dey_delivery_days_availablity',
				'class'             => 'dey-product-calender-field dey-product-delivery-field',
				'label'             => __( 'Number of Days for Delivery Availablity', 'delivery-slots-for-woocommerce' ),
				'type'              => 'number',
				'default'           => '7',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
				'desc_tip'          => true,
				'description'       => __( 'The number of selectable delivery dates which is visible to the user on the checkout page.', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey-weekdays-prices"><?php esc_html_e( 'Delivery Fee for Days of the Week', 'delivery-slots-for-woocommerce' ); ?>
				<?php echo wp_kses_post( wc_help_tip( __( 'You can charge a delivery fee for specific days of the week.', 'delivery-slots-for-woocommerce' ) ) ); ?>
			</label>

			<?php
			$weekdays_prices = array_filter( (array) get_post_meta( $thepostid, 'dey_delivery_weekdays_prices', true ) );
			foreach ( dey_weekdays() as $key => $label ) :
				$price = isset( $weekdays_prices[ $key ] ) ? wc_format_localized_price( $weekdays_prices[ $key ] ) : '';
				$name  = 'dey_delivery_weekdays_prices[' . $key . ']';
				?>
				<span class="dey-weekdays-prices">
					<label><?php echo esc_html( $label ); ?></label>
					<input type="text" name="<?php echo esc_attr( $name ); ?>" class="wc_input_price dey-product-calender-field dey-product-delivery-field" value="<?php echo esc_attr( $price ); ?>"/>
				</span>
			<?php endforeach; ?>
		</p>
		<?php
		woocommerce_wp_text_input(
			array(
				'id'                => 'dey_delivery_same_day_fee',
				'class'             => 'dey-product-calender-field dey-product-delivery-field',
				'label'             => __( 'Delivery Fee for Same day', 'delivery-slots-for-woocommerce' ),
				'type'              => 'number',
				'default'           => '',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 'any',
				),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => 'dey_delivery_next_day_fee',
				'class'             => 'dey-product-calender-field dey-product-delivery-field',
				'label'             => __( 'Delivery Fee for Next day', 'delivery-slots-for-woocommerce' ),
				'type'              => 'number',
				'default'           => '',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 'any',
				),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => 'dey_delivery_max_per_day',
				'class'             => 'dey-product-calender-field dey-product-delivery-field',
				'label'             => __( 'Maximum Deliveries per Day', 'delivery-slots-for-woocommerce' ),
				'type'              => 'number',
				'default'           => '10',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
				'desc_tip'          => true,
				'description'       => __( 'The maximum number of deliveries which can be delivered each day.', 'delivery-slots-for-woocommerce' ),
			)
		);

		woocommerce_wp_checkbox(
			array(
				'id'          => 'dey_delivery_calender_mandatory_field',
				'class'       => 'dey-product-calender-field dey-product-delivery-field',
				'label'       => __( 'Force Users to Select a Delivery Date', 'delivery-slots-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'When enabled, users cannot place orders without selecting a delivery date.', 'delivery-slots-for-woocommerce' ),
			)
		);

		woocommerce_wp_select(
			array(
				'id'          => 'dey_delivery_time_mode',
				'class'       => 'dey-product-calender-field dey-product-delivery-field',
				'label'       => __( 'Time Selection Type', 'delivery-slots-for-woocommerce' ),
				'options'     => dey_delivery_time_modes(),
				'default'     => '1',
				'desc_tip'    => true,
				'description' => __( 'Time Selector: User can select the time of delivery. Time Slots: Users can select from a list of available time slots.', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_time_slot"><?php esc_html_e( 'Time Selector', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_delivery_available_time_from',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => get_post_meta( $thepostid, 'dey_delivery_available_time_from', true ),
					'class'       => 'dey-product-time-field dey-product-delivery-time-field dey-product-calender-field dey-product-delivery-field',
				)
			);

			dey_get_datepicker_html(
				array(
					'name'        => 'dey_delivery_available_time_to',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => get_post_meta( $thepostid, 'dey_delivery_available_time_to', true ),
					'class'       => 'dey-product-time-field dey-product-delivery-time-field dey-product-calender-field dey-product-delivery-field',
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'The minimum and maximum time which the user can select.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_time_slot"><?php esc_html_e( 'Delivery Range', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			$expected_date_from = get_post_meta( $thepostid, 'dey_delivery_expected_date_from', true );
			$expected_date_to   = get_post_meta( $thepostid, 'dey_delivery_expected_date_to', true );
			?>
			<input type="number" step="1" min="1" class="dey-product-expected-delivery-field dey-product-delivery-field" name="dey_delivery_expected_date_from" value="<?php echo esc_attr( $expected_date_from ); ?>" />
			<input type="number" step="1" min="1" class="dey-product-expected-delivery-field dey-product-delivery-field" name="dey_delivery_expected_date_to" value="<?php echo esc_attr( $expected_date_to ); ?>" />

			<?php echo wp_kses_post( wc_help_tip( __( 'The minimum and maximum no of days needed for delivering the order.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>
	</div>

	<div class="options_group">
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_delivery_days"><?php esc_html_e( 'Delivery Days', 'delivery-slots-for-woocommerce' ); ?></label>
			<select multiple="multiple" name="dey_delivery_days[]" class="dey_select2">
				<?php
				$option_value = array_filter( (array) get_post_meta( $thepostid, 'dey_delivery_days', true ) );
				foreach ( dey_weekdays() as $key => $name ) :
					$selected = ( in_array( $key, $option_value ) ) ? " selected='selected'" : '';
					?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $name ); ?></option>
					<?php
				endforeach;
				?>
			</select>
			<?php echo wp_kses_post( wc_help_tip( __( 'The delivery days can be restricted to specific days of the week.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_cutoff_time"><?php esc_html_e( 'Cut Off Time for Same Day', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_delivery_cutoff_time',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => get_post_meta( $thepostid, 'dey_delivery_cutoff_time', true ),
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'Order processing time will start from the next day if the order is placed after the cut off time.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_cutoff_time"><?php esc_html_e( 'Cut Off Time for Next Day', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_delivery_next_day_cutoff_time',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => get_post_meta( $thepostid, 'dey_delivery_next_day_cutoff_time', true ),
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'Order processing time will start from the next day if the order is placed after the cut off time.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_delivery_processing_time"><?php esc_html_e( 'Processing time', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			$option_value = get_post_meta( $thepostid, 'dey_delivery_processing_time', true );
			$periods      = dey_relative_date_picker_options( 2 );
			$option_value = dey_parse_relative_date_option( $option_value, 2 );
			?>
			<input type="number" min="1" step="1" name="dey_delivery_processing_time[number]" value="<?php echo esc_attr( $option_value['number'] ); ?>" />
			<select name="dey_delivery_processing_time[unit]">
				<?php
				foreach ( $periods as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value['unit'], $value, false ) . '>' . esc_html( $label ) . '</option>';
				}
				?>
			</select>

			<?php echo wp_kses_post( wc_help_tip( __( 'The duration taken for processing the order.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>     
	</div>

	<div class="options_group">
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_delivery_business_days_label"><b><?php esc_html_e( 'Business day of the Week', 'delivery-slots-for-woocommerce' ); ?></b></label>
		</p>
		<table class="dey-business-days">
			<?php
			$option_value = array_filter( (array) get_post_meta( $thepostid, 'dey_delivery_business_days', true ) );
			$option_value = dey_parse_business_day_option( $option_value, dey_business_day_default_option() );
			foreach ( dey_weekdays() as $key => $label ) :
				$name = 'dey_delivery_business_days[' . $key . ']';
				?>
				<tr>
					<td>
						<input type="checkbox" name="<?php echo esc_attr( $name . '[enable]' ); ?>" <?php echo checked( 'yes', $option_value[ $key ]['enable'] ); ?>/>
						<?php echo esc_html( $label ); ?>
					</td>
					<td class="dey-business-day-opening-time">
						<label for='dey_delivery_business_days'><?php esc_html_e( 'Opening Time', 'delivery-slots-for-woocommerce' ); ?></label>
						<?php
						dey_get_datepicker_html(
							array(
								'id'          => 'dey_delivery_business_days',
								'name'        => $name . '[opening_time]',
								'time_only'   => true,
								'wp_zone'     => true,
								'placeholder' => '00:00 am',
								'value'       => $option_value[ $key ]['opening_time'],
							)
						);
						?>
					</td>
					<td class="dey-business-day-closing-time">
						<label for='dey_delivery_business_days'><?php esc_html_e( 'Closing Time', 'delivery-slots-for-woocommerce' ); ?></label>
						<?php
						dey_get_datepicker_html(
							array(
								'id'          => 'dey_delivery_business_days',
								'name'        => $name . '[closing_time]',
								'time_only'   => true,
								'wp_zone'     => true,
								'placeholder' => '00:00 am',
								'value'       => $option_value[ $key ]['closing_time'],
							)
						);
						?>
					</td>
				</tr>
				<?php
			endforeach;
			?>
		</table>
	</div>

	<div class="options_group">
		<?php
		woocommerce_wp_checkbox(
			array(
				'id'          => 'dey_delivery_disable_total_payable',
				'label'       => __( 'Disable Total Payable value', 'delivery-slots-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'When enabled, the Total Payable value will be hidden on the Product page.', 'delivery-slots-for-woocommerce' ),
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => 'dey_delivery_fee_display_hide',
				'label'       => __( 'Hide Delivery Fee Zero', 'delivery-slots-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'When enabled, the Delivery fee value will be hidden in the cart and checkout pages when it is zero.', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
	</div>
</div>
<?php
