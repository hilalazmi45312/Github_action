<?php
/* Admin HTML Fee Settings for Simple Product */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="show_if_simple  show_if_accommodation-booking show_if_booking efw-fee-wrapper">
	<p class="form-field">
		<label><?php esc_html_e( 'Enable Product Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="checkbox" class="efw_enable_fee" name="efw_enable_fee" <?php echo checked( $enable, 'yes', true ); ?>/>
	</p>
	<p class="form-field efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee should apply from', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_fee_from" class="efw-fee-from">
			<option value="1" <?php selected( $fee_from, '1', true ); ?>><?php esc_html_e( 'Product Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php selected( $fee_from, '2', true ); ?>><?php esc_html_e( 'Category Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="form-field efw-show-if-product-level efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Text is Obtained from', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_text_from" class="efw-text-from">
			<option value="1" <?php selected( $fee_text_from, '1', true ); ?>><?php esc_html_e( 'Global Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="3" <?php selected( $fee_text_from, '3', true ); ?>><?php esc_html_e( 'Category Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php selected( $fee_text_from, '2', true ); ?>><?php esc_html_e( 'Product Level Settings', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="form-field efw-show-if-product-level efw-extra-fee-text efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		<input type="text" name="efw_fee_text" value="<?php echo esc_attr( $fee_text ); ?>"/>
	</p>
	<p class="form-field efw-show-if-product-level efw-extra-fee-description efw-toggle-extra-fee-desc efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Description', 'extra-fees-for-woocommerce' ); ?></label>
		<textarea name="efw_fee_description"><?php echo wp_kses_post( $fee_description ); ?></textarea>
	</p>
	<p class="form-field efw-show-if-product-level efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_fee_type" class="efw-fee-type">
			<option value="1" <?php selected( $fee_type, '1', true ); ?>><?php esc_html_e( 'Fixed Fee', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php selected( $fee_type, '2', true ); ?>><?php esc_html_e( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="form-field efw-show-if-product-level efw-fixed-fee efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		<input type="number" step="any" min="0" name="efw_fixed_value" value="<?php echo esc_attr( $fixed_value ); ?>"/>
	</p>
	<p class="form-field efw-show-if-product-level efw-percent-fee efw-show-if-extra-fee-enable">
		<label><?php esc_html_e( 'Fee Value in %', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
		<input type="number" step="any" min="0" name="efw_percent_value" value="<?php echo esc_attr( $percent_value ); ?>"/>
	</p>
	<div class="efw-extra-fee-wrapper efw-show-if-product-level efw-show-if-extra-fee-enable">
		<div class="efw-rules-content">
			<?php
			if ( efw_check_is_array( $rule_ids ) ) {
				foreach ( $rule_ids as $rule_id ) {
					$rule = efw_get_fee_rule( $rule_id );
					include 'simple-product-fees.php';
				}
			}
			?>
		</div>
		<div class="efw-add-rule-button-wrapper">
			<button class="efw-add-rule-for-simple button"><?php esc_html_e( 'Add Rule', 'extra-fees-for-woocommerce' ); ?></button>
		</div>
	</div>
</div>
<?php
