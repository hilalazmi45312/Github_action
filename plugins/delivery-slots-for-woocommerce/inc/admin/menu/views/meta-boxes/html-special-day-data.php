<?php
/**
 * Special Day data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="dey-special-day-data">
	<div id="dey_special_day_data_wrapper" class="dey-special-day-options-wrapper">
		<div class="options_group">
			<?php
			woocommerce_wp_select(
				array(
					'id'      => 'dey_special_day_schedule_type',
					'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ),
					'options' => dey_get_special_day_schedule_types(),
					'default' => '1',
				)
			);
			?>
			<p class="form-field dey-date-field">
				<label for="dey_date"><?php esc_html_e( 'Date', 'delivery-slots-for-woocommerce' ); ?></label>
				<?php
				dey_get_datepicker_html(
					array(
						'id'          => 'dey_date',
						'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
						'value'       => $dey_special_day->get_date(),
						'wp_zone'     => false,
					)
				);
				?>
			</p>
			<?php
			woocommerce_wp_text_input(
				array(
					'id'        => 'dey_price',
					'label'     => __( 'Price', 'delivery-slots-for-woocommerce' ),
					'data_type' => 'price',
					'value'     => $dey_special_day->get_price(),
				)
			);
			?>
			<p class="form-field dey-order-count-field">
				<label for="dey_order_count"><?php esc_html_e( 'Maximum Number of Orders', 'delivery-slots-for-woocommerce' ); ?></label>
				<input type="number" min="1" step="1" name="dey_order_count" value="<?php echo esc_attr( $dey_special_day->get_order_count() ); ?>">
				<?php if ( ! empty( $dey_special_day->get_order_count() ) ) : ?>
					<span>
						<?php
						$remaining_order_count = intval( $dey_special_day->get_order_count() ) - intval( $dey_special_day->get_order_usage_count() );
						/* translators: %1$s: Used orders, %2$s: Remaining orders */
						printf( esc_html__( '%1$s Orders used, %2$s Orders remaining', 'delivery-slots-for-woocommerce' ), intval( $dey_special_day->get_order_usage_count() ), esc_attr( $remaining_order_count ) );
						?>
					</span>
					<input type='button' class='dey-reset-order-special-day dey-order-reset-button' 
							data-id="<?php echo esc_attr( $dey_special_day->get_id() ); ?>" 
							value="<?php esc_attr_e( 'Reset', 'delivery-slots-for-woocommerce' ); ?>" />
				<?php endif; ?>
				<?php echo wp_kses_post( wc_help_tip( __( 'The maximum number of orders which can be delivered on this day.', 'delivery-slots-for-woocommerce' ) ) ); ?>
			</p>
		</div>
		<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
	</div>
</div>
<?php
