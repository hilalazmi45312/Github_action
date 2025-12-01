<?php
/* Admin HTML Deals Settings for Simple Product */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-rules-content-wrapper">
	<h3 class="efw-rule-name">
		<?php echo esc_html__( 'Untitled', 'extra-fees-for-woocommerce' ); ?>
		<span class="dashicons dashicons-arrow-down"></span>
		<span class="dashicons dashicons-trash efw-delete-rule" data-ruleid=""></span>
	</h3>
	<input type="hidden" id="efw_fee_key" value="<?php echo esc_attr( $key ); ?>"/>
	<div class="efw-rule-fields efw-fee-wrapper">
		<p class="form-field">
			<label><?php esc_html_e( 'Rule Name', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="text" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_name]"/>
		</p>
		<p class="form-field efw-extra-fee-text">
			<label><?php esc_html_e( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="text" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_fee_text]" value=""/>
		</p>
		<p class="form-field efw-extra-fee-description">
			<label><?php esc_html_e( 'Fee Description', 'extra-fees-for-woocommerce' ); ?></label>
			<textarea name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_fee_description]"></textarea>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></label>
			<select class="efw-rule-fee-type" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_fee_type]">
				<option value="1"><?php esc_html_e( 'Fixed Fee', 'extra-fees-for-woocommerce' ); ?></option>
				<option value="2"><?php esc_html_e( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ); ?></option>
			</select>
		</p>
		<p class="form-field efw-rule-fixed-fee">
			<label><?php esc_html_e( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="number" min="0" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_fixed_fee]" value=""/>
		</p>
		<p class="form-field efw-rule-percent-fee">
			<label><?php esc_html_e( 'Fee Value in %', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="number" min="0" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_percent_fee]" value=""/>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'From Date', 'extra-fees-for-woocommerce' ); ?></label>
			<?php
			$rule_from_date = array(
				'name'        => "efw_product_fees[new][$key][efw_from_date]",
				'value'       => '',
				'wp_zone'     => false,
				'placeholder' => EFW_Date_Time::get_wp_date_format(),
			);
			efw_get_datepicker_html( $rule_from_date );
			?>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'To Date', 'extra-fees-for-woocommerce' ); ?></label>
			<?php
			$rule_to_date = array(
				'name'        => "efw_product_fees[new][$key][efw_to_date]",
				'value'       => '',
				'wp_zone'     => false,
				'placeholder' => EFW_Date_Time::get_wp_date_format(),
			);
			efw_get_datepicker_html( $rule_to_date );
			?>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Minimum Quantity', 'extra-fees-for-woocommerce' ); ?></label>
			<input type="number" min="0" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_minimum_qty]" value=""/>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Maximum Quantity', 'extra-fees-for-woocommerce' ); ?></label>
			<input type="number" min="0" name="efw_product_fees[new][<?php echo esc_attr( $key ); ?>][efw_maximum_qty]" value=""/>
		</p>
	</div>
</div>
<?php
