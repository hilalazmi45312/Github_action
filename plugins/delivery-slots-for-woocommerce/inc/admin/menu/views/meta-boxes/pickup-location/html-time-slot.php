<?php
/**
 * Pickup location panel - Time slot.
 *
 * @since 4.0.0
 */
defined( 'ABSPATH' ) || exit;

$name = 'dey_order_pickup_time_slots[' . $key . ']';
?>
<div class='dey-scheduler-rule-time-slot-wrapper dey-order-delivery-slots-wrapper'>
	<h3><?php echo esc_html( $time_slot['name'] ); ?>
		<span class='dashicons dashicons-arrow-down dey-order-delivery-toggle' title="<?php esc_attr_e( 'Toggle', 'delivery-slots-for-woocommerce' ); ?>"></span>
		<span class='dashicons dashicons-trash dey-delete-order-delivery-slots-rule' title="<?php esc_attr_e( 'Remove', 'delivery-slots-for-woocommerce' ); ?>"></span>
	</h3>
	<div class='dey-scheduler-rule-time-slot-content dey-order-delivery-slots-content-wrapper dey-hide'>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slot_name'><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='text' name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $time_slot['name'] ); ?>" />
		</p>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slots'><?php esc_html_e( 'Time Slots', 'delivery-slots-for-woocommerce' ); ?>
				<?php echo wp_kses_post( wc_help_tip( __( 'Please select the start time and end time for the time slot.', 'delivery-slots-for-woocommerce' ) ) ); ?>
			</label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[from_time]',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => $time_slot['from_time'],
				)
			);

			dey_get_datepicker_html(
				array(
					'name'        => $name . '[to_time]',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
					'value'       => $time_slot['to_time'],
				)
			);
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slot_price'><?php esc_html_e( 'Price', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='text' class='wc_input_price' name="<?php echo esc_attr( $name ); ?>[price]" value="<?php echo esc_attr( wc_format_localized_price( $time_slot['price'] ) ); ?>" />
			<?php echo wp_kses_post( wc_help_tip( __( 'You can charge a delivery fee for this time slot.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>

		<p class='form-field'>
			<label for='dey_time_slot_week_days'><?php esc_html_e( 'Restricted Week Days', 'delivery-slots-for-woocommerce' ); ?></label>
			<select multiple='multiple' name="<?php echo esc_attr( $name ); ?>[week_days][]" class='dey_select2 dey-week-days'>
				<?php
				foreach ( dey_weekdays() as $week_key => $week_name ) :
					$selected = ( in_array( $week_key, $time_slot['week_days'] ) ) ? " selected='selected'" : '';
					?>
					<option value="<?php echo esc_attr( $week_key ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $week_name ); ?></option>
					<?php
				endforeach;
				?>
			</select>

			<?php echo wp_kses_post( wc_help_tip( __( 'The time slot can be restricted to specific days of the week.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slot_order_count'><?php esc_html_e( 'Order Count', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='number' min='1' step='1' name="<?php echo esc_attr( $name ); ?>[order_count]" value="<?php echo esc_attr( $time_slot['order_count'] ); ?>" />

			<?php if ( ! empty( $time_slot['order_count'] ) ) : ?>
				<input type='button' class='button dey-reset-pickup-location-time-slot-usage-count' data-key="<?php echo esc_attr( $key ); ?>" value="<?php esc_attr_e( 'Reset', 'delivery-slots-for-woocommerce' ); ?>"/>
			<?php endif; ?>
			<?php echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered in one slot.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>
	</div>
</div>
<?php
