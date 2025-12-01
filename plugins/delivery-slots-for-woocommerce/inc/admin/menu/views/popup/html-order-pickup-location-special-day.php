<?php
/**
 * Popup - Special day.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div class='dey-hide dey-special-day-modal-wrapper' id='dey_order_pickup_location_special_day_modal'>
	<div class='dey-special-day-modal-header'><h3><b><?php esc_html_e( 'New Specific Date', 'delivery-slots-for-woocommerce' ); ?></h3></b></div>
	<div class='dey-special-day-modal-content'>
		<p class='dey-error'></p>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_special_day_name'><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type='text' id='dey_special_day_name' class='dey-special-day-name' name='dey_special_days[name]' />
		</p>
		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_special_day_date'><?php esc_html_e( 'Date', 'delivery-slots-for-woocommerce' ); ?><span class='required'>*</span></label>
			<?php
			dey_get_datepicker_html(
				array(
					'name'        => 'dey_special_days[date]',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
				)
			);
			?>
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<?php
			$fee_label = __( 'Price', 'delivery-slots-for-woocommerce' ) . $currency_symbol;
			?>
			<label for='dey_special_day_price'><?php echo wp_kses_post( $fee_label ); ?></label>
			<input type='text' class='wc_input_price' class='dey-special-day-price' id='dey_special_day_price' name='dey_special_days[price]' />
		</p>

		<p class='form-field dey-delivery-slots-field'>
			<label for='dey_special_day_order_count'><?php esc_html_e( 'Order Count', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type='number' min='1' step='1' class='dey-special-day-order-count' id='dey_special_day_order_count' name='dey_special_days[order_count]'/>
			<?php
			echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered on this day.', 'delivery-slots-for-woocommerce' ) ) );
			?>
		</p>
	</div>
	<div class='dey-special-day-modal-footer'>
		<input type='button' class='button-primary dey-create-order-pickup-location-special-day' value="<?php esc_html_e( 'Create', 'delivery-slots-for-woocommerce' ); ?>">
	</div>
</div>
<?php
