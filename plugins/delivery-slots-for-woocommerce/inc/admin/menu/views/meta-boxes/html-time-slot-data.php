<?php
/**
 * Time slot data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id='dey-time-slot-data'>
	<div id='dey_time_slot_data_wrapper' class='dey-time-slot-options-wrapper'>
		<div class='options_group'>
			<?php
			woocommerce_wp_select(
				array(
					'id'      => 'dey_time_slot_schedule_type',
					'label'   => __( 'Time Slot Applicable For', 'delivery-slots-for-woocommerce' ),
					'options' => dey_get_time_slots_schedule_types(),
					'default' => '1',
				)
			);
			?>
			<p class='form-field'>				
				<label for='dey_time_slot_cutoff_time'><?php esc_html_e( 'Time Slot Cutoff Time', 'delivery-slots-for-woocommerce' ); ?></label>
				<?php
				dey_get_datepicker_html(
					array(
						'id'          => 'dey_time_slot_cutoff_time',
						'time_only'   => true,
						'value'       => $dey_time_slot->get_cutoff_time(),
						'placeholder' => DEY_Date_Time::get_wp_time_format(),
					)
				);
				?>
			</p>
			<p class='form-field dey-time-slot-field'>				
				<label for='dey_time_slot'><?php esc_html_e( 'Time Slot Range', 'delivery-slots-for-woocommerce' ); ?></label>
				<?php
				dey_get_datepicker_html(
					array(
						'id'          => 'dey_time_slot_from',
						'time_only'   => true,
						'value'       => $dey_time_slot->get_time_slot_from(),
						'placeholder' => DEY_Date_Time::get_wp_time_format(),
					)
				);
				dey_get_datepicker_html(
					array(
						'id'          => 'dey_time_slot_to',
						'time_only'   => true,
						'value'       => $dey_time_slot->get_time_slot_to(),
						'placeholder' => DEY_Date_Time::get_wp_time_format(),
					)
				);

				echo wp_kses_post( wc_help_tip( __( 'Please select the start time and end time for the time slot.', 'delivery-slots-for-woocommerce' ) ) );
				?>
			</p>

			<?php
			woocommerce_wp_text_input(
				array(
					'id'          => 'dey_price',
					'label'       => __( 'Time Slot Fee', 'delivery-slots-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'data_type'   => 'price',
					'value'       => $dey_time_slot->get_price(),
					'description' => __( 'You can charge a time slot fee for this time slot.', 'delivery-slots-for-woocommerce' ),
				)
			);
			woocommerce_wp_checkbox(
				array(
					'id'          => 'dey_enable_week_days',
					'class'       => 'dey_enable_week_days',
					'value'       => $dey_time_slot->get_enable_week_days(),
					'label'       => __( 'Restrict Time Slot to Specific Days', 'delivery-slots-for-woocommerce' ),
					'description' => __( 'When enabled, this time slot can be restricted to specific days of the week.', 'delivery-slots-for-woocommerce' ),
				)
			);
			?>
			<p class='form-field dey-week-days-field'>
				<label for='dey_time_slot'><?php esc_html_e( 'Delivery Days', 'delivery-slots-for-woocommerce' ); ?></label>
				<select multiple='multiple' name="dey_week_days[]" class="dey_select2 dey_week_days">
					<?php
					foreach ( dey_weekdays() as $key => $name ) :
						$selected = in_array( $key, $dey_time_slot->get_week_days() ) ? " selected='selected'" : '';
						?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $name ); ?></option>
						<?php
					endforeach;
					?>
				</select>
				<?php echo wp_kses_post( wc_help_tip( __( 'The time slot can be restricted to specific days of the week.', 'delivery-slots-for-woocommerce' ) ) ); ?>
			</p>

			<p class='form-field dey-order-count-field'>
				<label for='dey_order_count'><?php esc_html_e( 'Maximum Number of Orders', 'delivery-slots-for-woocommerce' ); ?></label>
				<input type='number' min='1' step='1' name='dey_order_count' value="<?php echo esc_attr( $dey_time_slot->get_order_count() ); ?>">
				<?php if ( ! empty( $dey_time_slot->get_order_count() ) ) : ?>
					<input type='button' class='dey-reset-order-time-slot dey-order-reset-button' 
						data-id="<?php echo esc_attr( $dey_time_slot->get_id() ); ?>" 
						value="<?php esc_attr_e( 'Reset', 'delivery-slots-for-woocommerce' ); ?>"/>
				<?php endif; ?>
				<?php echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered in one slot.', 'delivery-slots-for-woocommerce' ) ) ); ?>
			</p>
		</div>
		<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
	</div>
</div>
<?php
