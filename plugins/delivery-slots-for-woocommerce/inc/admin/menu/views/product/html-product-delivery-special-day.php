<?php
/**
 * Product delivery Special day.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name = 'dey_delivery_special_days[' . $key . ']';
?>
<div class="dey-delivery-special-day-wrapper dey-delivery-slots-wrapper">
	<h3><?php echo esc_html( $special_day['name'] ); ?>
		<span  class="dashicons dashicons-arrow-down dey-delivery-toggle" title="<?php esc_attr_e( 'Toggle', 'delivery-slots-for-woocommerce' ); ?>"></span>
		<span  class="dashicons dashicons-trash dey-delete-delivery-special-day" title="<?php esc_attr_e( 'Remove', 'delivery-slots-for-woocommerce' ); ?>"></span>
	</h3>
	<div class="dey-delivery-special-day-content dey-delivery-slots-content-wrapper dey-hide">
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_special_day_name"><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $special_day['name'] ); ?>" />
		</p>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => $name . '[schedule_type]',
				'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ),
				'options' => dey_get_time_slots_schedule_types(),
				'value'   => isset( $special_day['schedule_type'] ) ? $special_day['schedule_type'] : '2',
				'default' => '2',
			)
		);
		?>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_special_day_date"><?php esc_html_e( 'Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => $name . '[date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $special_day['date'],
					'wp_zone'     => false,
				)
			);
			?>
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_special_day_price"><?php esc_html_e( 'Price', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" class="wc_input_price" name="<?php echo esc_attr( $name ); ?>[price]" value="<?php echo esc_attr( wc_format_localized_price( $special_day['price'] ) ); ?>" />
		</p>

		<p class="form-field dey-delivery-slots-field">
			<label for="dey_special_day_order_count"><?php esc_html_e( 'Order Count', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="number" min="1" step="1" name="<?php echo esc_attr( $name ); ?>[order_count]" value="<?php echo esc_attr( $special_day['order_count'] ); ?>" />

			<?php if ( ! empty( $special_day['order_count'] ) ) : ?>
				<input type="button" class="dey-reset-product-special-day dey-order-reset-button" 
						data-product_id="<?php echo esc_attr( $thepostid ); ?>" 
						data-key="<?php echo esc_attr( $key ); ?>" 
						value="<?php esc_attr_e( 'Reset', 'delivery-slots-for-woocommerce' ); ?>"
						/>
					<?php endif; ?>
					<?php
					echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered on this day.', 'delivery-slots-for-woocommerce' ) ) );
					?>
		</p>
	</div>
</div>
<?php
