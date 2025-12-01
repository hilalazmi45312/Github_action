<?php
/**
 * Scheduler rule popup - Time slot.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div class='dey-hide dey-order-time-slot-modal-wrapper' id='dey_scheduler_rule_time_slot_modal'>
	<div class='dey-order-time-slot-modal-header'>
		<h3><b><?php esc_html_e( 'New Time slot', 'delivery-slots-for-woocommerce' ); ?></b></h3>
	</div>
	<div class='dey-order-time-slot-modal-content'>
		<span class='dey-error'></span>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slot_name'><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type='text' name='dey_time_slot[name]' class='dey-time-slot-name'/>
		</p>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_time_slot[schedule_type]',
				'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ),
				'options' => dey_get_order_scheduler_options(),
				'value'   => '3', // Both delivery and pickup.
			)
		);
		?>
		<p class='form-field'>				
			<label><?php esc_html_e( 'Time Slot Cutoff Time', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_time_slot[cutoff_time]',
					'time_only'   => true,
					'value'       => '',
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
				)
			);
			?>
		</p>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_from_time'><?php esc_html_e( 'Time Slots', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_time_slot[from_time]',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
				)
			);

			dey_get_datepicker_html(
				array(
					'id'          => 'dey_time_slot[to_time]',
					'time_only'   => true,
					'placeholder' => DEY_Date_Time::get_wp_time_format(),
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'Please select the start time and end time for the time slot.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<?php
			$fee_label = __( 'Time Slot Fee', 'delivery-slots-for-woocommerce' ) . $currency_symbol;
			?>
			<label for='dey_time_slot_price'><?php echo wp_kses_post( $fee_label ); ?></label>
			<input type='text' class='wc_input_price' id='dey_time_slot_price' name='dey_time_slot[price]' />
			<?php echo wp_kses_post( wc_help_tip( __( 'You can charge a delivery fee for this time slot.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slot_week_days'><?php esc_html_e( 'Restricted Week Days', 'delivery-slots-for-woocommerce' ); ?></label>
			<select multiple='multiple' name='dey_time_slot[week_days][]' class='dey_select2 dey-week-days'>
				<?php foreach ( dey_weekdays() as $week_key => $week_name ) : ?>
					<option value="<?php echo esc_attr( $week_key ); ?>"><?php echo esc_html( $week_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php echo wp_kses_post( wc_help_tip( __( 'The time slot can be restricted to specific days of the week.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_time_slot_order_count'><?php esc_html_e( 'Order Count', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='number' min='1' step='1' id='dey_time_slot_order_count' name='dey_time_slot[order_count]' />
			<?php echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered in one slot.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>
	</div>
	<div class='dey-order-time-slot-modal-footer'>
		<input type='button' class='button-primary dey-create-scheduler-rule-time-slot' value="<?php esc_html_e( 'Create', 'delivery-slots-for-woocommerce' ); ?>">
	</div>
</div>
<?php
