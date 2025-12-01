<?php
/**
 * Holiday data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div id="dey-holiday-data">
	<div id="dey_holiday_data_wrapper" class="dey-holiday-options-wrapper">
		<div class="options_group">
			<?php
			woocommerce_wp_select(
				array(
					'id' => 'dey_holiday_schedule_type',
					'label' => __('Applicable For', 'delivery-slots-for-woocommerce'),
					'options' => dey_get_holiday_schedule_types(),
					'default' => '1',
				)
			);
			?>
			<p class="form-field dey-holiday-field">
				<label for="dey_from_date"><?php esc_html_e( 'From Date' , 'delivery-slots-for-woocommerce' ) ; ?></label>
				<?php
				dey_get_datepicker_html( array(
					'id'          => 'dey_from_date',
					'wp_zone'     => false,
					'value'       => $dey_holiday->get_from_date(),
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
				) ) ;

				echo wp_kses_post( wc_help_tip( __( 'Start Date of the Holiday.' , 'delivery-slots-for-woocommerce' ) ) ) ;
				?>
			</p>
			<p class="form-field dey-holiday-field">
				<label for="dey_to_date"><?php esc_html_e( 'TO Date' , 'delivery-slots-for-woocommerce' ) ; ?></label>
				<?php
				dey_get_datepicker_html( array(
					'id'          => 'dey_to_date',
					'wp_zone'     => false,
					'value'       => $dey_holiday->get_to_date(),
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
				) ) ;

				echo wp_kses_post( wc_help_tip( __( 'End Date of the Holiday.' , 'delivery-slots-for-woocommerce' ) ) ) ;
				?>
			</p>
			<?php
			woocommerce_wp_checkbox(
					array(
						'id'          => 'dey_recurring',
						'value'       => $dey_holiday->get_recurring(),
						'label'       => __( 'Repeat Holiday' , 'delivery-slots-for-woocommerce' ),
						'description' => __( 'When enabled, this holiday will repeat each year.' , 'delivery-slots-for-woocommerce' ),
					)
			) ;
			?>
		</div>
		<?php wp_nonce_field( 'dey_save_data' , 'dey_meta_nonce' ) ; ?>
	</div>
</div>
<?php
