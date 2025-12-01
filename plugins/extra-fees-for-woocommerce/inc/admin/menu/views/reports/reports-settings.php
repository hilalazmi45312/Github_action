<?php
/* Admin HTML Settings for Reports */

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div class="efw-reports-field-wrapper">
	<div class="efw-reports-settings">
		<p>
			<select class="efw-duration-type" name="efw_duration_type">
				<option value="all"><?php esc_html_e( 'All Time' , 'extra-fees-for-woocommerce' ) ; ?></option>
				<option value="this_week"><?php esc_html_e( 'This Week' , 'extra-fees-for-woocommerce' ) ; ?></option>
				<option value="last_week"><?php esc_html_e( 'Last Week' , 'extra-fees-for-woocommerce' ) ; ?></option>
				<option value="this_month"><?php esc_html_e( 'This Month' , 'extra-fees-for-woocommerce' ) ; ?></option>
				<option value="last_month"><?php esc_html_e( 'Last Month' , 'extra-fees-for-woocommerce' ) ; ?></option>
				<option value="custom_date"><?php esc_html_e('Custom Date Range', 'extra-fees-for-woocommerce'); ?></option>
			</select>
		</p>
		<p class="efw-custom-date-range-field">
			<label><?php esc_html_e('Date Ranges', 'extra-fees-for-woocommerce'); ?></label>
			<?php
			$from_date_args = array(
				'id' => 'efw_reports_custom_from_date',
				'value' => '',
				'wp_zone' => false,
				'placeholder' => EFW_Date_Time::get_wp_date_format(),
			);
			efw_get_datepicker_html($from_date_args);
			
			esc_html_e('To', 'extra-fees-for-woocommerce');
			$to_date_args   = array(
				'id' => 'efw_reports_custom_to_date',
				'value' => '',
				'wp_zone' => false,
				'placeholder' => EFW_Date_Time::get_wp_date_format(),
			);
			efw_get_datepicker_html($to_date_args); 
			?>
		</p>
		<button class="efw-view-report button button-primary"><?php esc_html_e( 'View Report' , 'extra-fees-for-woocommerce' ) ; ?></button>
	</div>

	<div class="efw-reports-wrapper">
		<?php include EFW_ABSPATH . 'inc/admin/menu/views/reports/reports-data.php' ; ?>
	</div>
</div>
<?php
