<?php
/**
 * Scheduler rule panel - Special day.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$name = 'dey_special_days[' . $key . ']';
?>
<div class='dey-scheduler-rule-special-day-wrapper dey-order-delivery-slots-wrapper'>
	<h3><?php echo esc_html( $special_day['name'] ); ?>
		<span  class='dashicons dashicons-arrow-down dey-order-delivery-toggle' title="<?php esc_attr_e( 'Toggle', 'delivery-slots-for-woocommerce' ); ?>"></span>
		<span  class='dashicons dashicons-trash dey-delete-order-delivery-slots-rule' title="<?php esc_attr_e( 'Remove', 'delivery-slots-for-woocommerce' ); ?>"></span>
	</h3>
	<div class='dey-scheduler-rule-special-day-content dey-order-delivery-slots-content-wrapper dey-hide'>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_special_day_name'><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type='text' name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $special_day['name'] ); ?>" />
		</p>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => $name . '[schedule_type]',
				'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ),
				'options' => dey_get_order_scheduler_options(),
				'value'   => isset( $special_day['schedule_type'] ) ? $special_day['schedule_type'] : '2',
			)
		);
		?>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_special_day_date'><?php esc_html_e( 'Date', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $special_day['date'],
				)
			);
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<?php
			$fee_label = __( 'Price', 'delivery-slots-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')';
			?>
			<label for='dey_special_day_price'><?php echo wp_kses_post( $fee_label ); ?></label>
			<input type='text' class='wc_input_price' name="<?php echo esc_attr( $name ); ?>[price]" value="<?php echo esc_attr( wc_format_localized_price( $special_day['price'] ) ); ?>" />
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_special_day_order_count'><?php esc_html_e( 'Order Count', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='number' min='1' step='1' name="<?php echo esc_attr( $name ); ?>[order_count]" value="<?php echo esc_attr( $special_day['order_count'] ); ?>" />

			<?php if ( ! empty( $special_day['order_count'] ) ) : ?>
				<span>
					<?php
					$used_order_count      = isset( $special_day['used_order_count'] ) ? intval( $special_day['used_order_count'] ) : 0;
					$remaining_order_count = intval( $special_day['order_count'] ) - $used_order_count;
					/* translators: %1$s: Used orders, %2$s: Remaining orders */
					printf( esc_html__( '%1$s Orders used, %2$s Orders remaining', 'delivery-slots-for-woocommerce' ), esc_attr( $used_order_count ), esc_attr( $remaining_order_count ) );
					?>
				</span>
				<input type='button' class='button dey-reset-scheduler-rule-special-day-usage-count' data-key="<?php echo esc_attr( $key ); ?>" value="<?php esc_attr_e( 'Reset', 'delivery-slots-for-woocommerce' ); ?>"/>
			<?php endif; ?>
			<?php echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered on this day.', 'delivery-slots-for-woocommerce' ) ) ); ?>
		</p>
	</div>
</div>
<?php
