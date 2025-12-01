<?php
/**
 * Pickup location panel - Holiday.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$name = 'dey_order_pickup_holidays[' . $key . ']';
?>
<div class='dey-scheduler-rule-holiday-wrapper dey-order-delivery-slots-wrapper'>
	<h3><?php echo esc_html( $holiday['name'] ); ?>
		<span  class='dashicons dashicons-arrow-down dey-order-delivery-toggle'></span>
		<span  class='dashicons dashicons-trash dey-delete-order-delivery-slots-rule' title="<?php esc_attr_e( 'Remove', 'delivery-slots-for-woocommerce' ); ?>"></span>
	</h3>
	<div class='dey-scheduler-rule-holiday-content dey-order-delivery-slots-content-wrapper dey-hide'>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_name'><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='text' name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $holiday['name'] ); ?>" />
		</p>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_from_date'><?php esc_html_e( 'From Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[from_date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $holiday['from_date'],
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'Start Date of the Holiday.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_to_date'><?php esc_html_e( 'To Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[to_date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $holiday['to_date'],
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'End Date of the Holiday.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_holiday_recurring'><?php esc_html_e( 'Recurring', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='checkbox'  name="<?php echo esc_attr( $name ); ?>[recurring]" value="yes" <?php echo checked( 'yes', $holiday['recurring'] ); ?> />

			<?php echo wp_kses_post( __( 'When enabled, this holiday will repeat each year.', 'delivery-slots-for-woocommerce' ) ); ?>
		</p>
	</div>
</div>
<?php
