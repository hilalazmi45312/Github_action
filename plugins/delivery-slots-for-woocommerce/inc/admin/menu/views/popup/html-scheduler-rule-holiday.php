<?php
/**
 * Scheduler rule popup - Holiday.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div class='dey-hide dey-holiday-modal-wrapper' id='dey_scheduler_rule_holiday_modal'>
	<div class='dey-holiday-modal-header'><h3><b><?php esc_html_e( 'New Holiday', 'delivery-slots-for-woocommerce' ); ?></h3></b></div>
	<div class='dey-holiday-modal-content'>
		<p class='dey-error'></p>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_name'><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type='text' class='dey-holiday-name' id='dey_holiday_name' name='dey_holidays[name]' />
		</p>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_holidays[schedule_type]',
				'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ),
				'options' => dey_get_order_scheduler_options(),
				'default' => '2',
			)
		);
		?>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_from_date'><?php esc_html_e( 'From Date', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => 'dey_holidays[from_date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'Start Date of the Holiday.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_to_date'><?php esc_html_e( 'To Date', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => 'dey_holidays[to_date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'End Date of the Holiday.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_recurring'><?php esc_html_e( 'Recurring', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='checkbox'  class='dey-holiday-recurring' id='dey_holiday_recurring' name='dey_holidays[recurring]'/>
			<?php echo wp_kses_post( __( 'When enabled, this holiday will repeat each year.', 'delivery-slots-for-woocommerce' ) ); ?>
		</p>
	</div>
	<div class='dey-holiday-modal-footer'>
		<input type='button' class='button-primary dey-create-scheduler-rule-holiday' value="<?php esc_html_e( 'Create', 'delivery-slots-for-woocommerce' ); ?>">
	</div>
</div>
<?php
