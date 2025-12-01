<?php
/* Admin HTML Fee Settings for Simple Product */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-fee-wrapper">
	<h2><?php esc_html_e('Product Fee Settings', 'extra-fees-for-woocommerce'); ?></h2>
	<div class="form-field">
		<label><?php esc_html_e( 'Enable Product Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="checkbox" class="efw_enable_fee" name="efw_enable_fee"/>
	</div>
	<div class="form-field efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Text is Obtained from', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_text_from" class="efw-text-from">
			<option value="1"><?php esc_html_e( 'Global Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2"><?php esc_html_e( 'Category Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</div>
	<div class="form-field efw-extra-fee-text efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		<input type="text" name="efw_fee_text" value=""/>
	</div>
	<div class="form-field efw-extra-fee-description efw-toggle-extra-fee-desc efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Description', 'extra-fees-for-woocommerce' ); ?></label>
		<textarea name="efw_fee_description"></textarea>
	</div>
	<div class="form-field efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_fee_type" class="efw-fee-type">
			<option value="1"><?php esc_html_e( 'Fixed Fee', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2"><?php esc_html_e( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</div>
	<div class="form-field efw-fixed-fee efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		<input type="number" step="any" min="0" name="efw_fixed_value" value=""/>
	</div>
	<div class="form-field efw-percent-fee efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Value in %', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		<input type="number" step="any" min="0" name="efw_percent_value" value=""/>
	</div>
</div>
<?php
