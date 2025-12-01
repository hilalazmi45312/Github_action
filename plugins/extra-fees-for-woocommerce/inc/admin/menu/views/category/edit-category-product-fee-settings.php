<?php
/* Admin HTML Fee Settings for Simple Product */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Enable Product Fee', 'extra-fees-for-woocommerce' ); ?></label>
		</th>
		<td>
			<input type="checkbox" class="efw_enable_fee" name="efw_enable_fee" <?php echo checked( $enable, 'yes', true ); ?>/>
		</td>
	</tr>
	<tr class="form-field efw-show-if-extra-fee-enable">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Fee Text is Obtained from', 'extra-fees-for-woocommerce' ); ?></label>
		</th>
		<td>
			<select name="efw_text_from" class="efw-text-from">
				<option value="1" <?php selected( $fee_text_from, '1', true ); ?>><?php esc_html_e( 'Global Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
				<option value="2" <?php selected( $fee_text_from, '2', true ); ?>><?php esc_html_e( 'Category Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
			</select>
		</td>
	</tr>
	<tr class="form-field efw-extra-fee-text efw-show-if-extra-fee-enable">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		</th>
		<td>
			<input type="text" name="efw_fee_text" value="<?php echo esc_attr( $fee_text ); ?>"/>
		</td>
	</tr>
	<tr class="form-field efw-extra-fee-description efw-toggle-extra-fee-desc efw-show-if-extra-fee-enable">
		<th scope="row" valign="top">	
			<label><?php esc_html_e( 'Fee Description', 'extra-fees-for-woocommerce' ); ?></label>
		</th>
		<td>
			<textarea name="efw_fee_description"><?php echo wp_kses_post( $fee_description ); ?></textarea>
		</td>
	</tr>
	<tr class="form-field efw-show-if-extra-fee-enable">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></label>
		</th>
		<td>
			<select name="efw_fee_type" class="efw-fee-type">
				<option value="1" <?php selected( $fee_type, '1', true ); ?>><?php esc_html_e( 'Fixed Fee', 'extra-fees-for-woocommerce' ); ?></option>
				<option value="2" <?php selected( $fee_type, '2', true ); ?>><?php esc_html_e( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ); ?></option>
			</select>
		</td>
	</tr>
	<tr class="form-field efw-fixed-fee efw-show-if-extra-fee-enable">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		</th>
		<td>
			<input type="number" step="any" min="0" name="efw_fixed_value" value="<?php echo esc_attr( $fixed_value ); ?>"/>
		</td>
	</tr>
	<tr class="form-field efw-percent-fee efw-show-if-extra-fee-enable">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Fee Value in %', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		</th>
		<td>
			<input type="number" step="any" min="0" name="efw_percent_value" value="<?php echo esc_attr( $percent_value ); ?>"/>
		</td>
	</tr>
<?php
