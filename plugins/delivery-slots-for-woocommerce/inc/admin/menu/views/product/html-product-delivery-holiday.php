<?php
/**
 * Product delivery holiday.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name = 'dey_delivery_holidays[' . $key . ']';
?>
<div class="dey-delivery-holiday-wrapper dey-delivery-slots-wrapper">
	<h3><?php echo esc_html( $holiday['name'] ); ?>
		<span  class="dashicons dashicons-arrow-down dey-delivery-toggle"></span>
		<span  class="dashicons dashicons-trash dey-delete-delivery-holiday" title="<?php esc_attr_e( 'Remove', 'delivery-slots-for-woocommerce' ); ?>"></span>
	</h3>
	<div class="dey-delivery-holiday-content dey-delivery-slots-content-wrapper dey-hide">
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_holiday_name"><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $holiday['name'] ); ?>" />
		</p>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => $name . '[schedule_type]',
				'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ),
				'options' => dey_get_time_slots_schedule_types(),
				'value'   => isset( $holiday['schedule_type'] ) ? $holiday['schedule_type'] : '2',
				'default' => '2',
			)
		);
		?>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_holiday_from_date"><?php esc_html_e( 'From Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[from_date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $holiday['from_date'],
					'wp_zone'     => false,
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'Start Date of the Holiday.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_holiday_to_date"><?php esc_html_e( 'To Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[to_date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $holiday['to_date'],
					'wp_zone'     => false,
				)
			);

			echo wp_kses_post( wc_help_tip( __( 'End Date of the Holiday.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_holiday_recurring"><?php esc_html_e( 'Recurring', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="checkbox"  name="<?php echo esc_attr( $name ); ?>[recurring]" value="yes" <?php echo checked( 'yes', $holiday['recurring'] ); ?> />

			<?php echo wp_kses_post( __( 'When enabled, this holiday will repeat each year.', 'delivery-slots-for-woocommerce' ) ); ?>
		</p>

	</div>

</div>
<?php
