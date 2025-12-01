<?php
/* Admin HTML Fees Settings for Simple Product */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-rules-content-wrapper">
	<h3 class="efw-rule-name">
		<?php
			echo ! empty( $rule->get_name() ) ? esc_html( $rule->get_name() ) : esc_html__( 'Untitled', 'extra-fees-for-woocommerce' );
		?>
		<span class="dashicons dashicons-arrow-down"></span>
		<span class="dashicons dashicons-trash efw-delete-rule" data-ruleid="<?php echo esc_attr( $rule_id ); ?>"></span>
	</h3>
	<div class="efw-rule-fields efw-fee-wrapper">
		<p class="form-field">
			<label><?php esc_html_e( 'Rule Name', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="text" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_name]" value="<?php echo esc_attr( $rule->get_name() ); ?>"/>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="text" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_fee_text]" value="<?php echo esc_attr( $rule->get_fee_text() ); ?>"/>
		</p>
				<p class="form-field efw-extra-fee-description">
					<label><?php esc_html_e( 'Fee Description', 'extra-fees-for-woocommerce' ); ?></label>
					<textarea name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_fee_description]"><?php echo wp_kses_post( $rule->get_fee_description() ); ?></textarea>
				</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></label>
			<select class="efw-rule-fee-type" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_fee_type]">
				<option value="1" <?php echo selected( $rule->get_fee_type(), '1', true ); ?>><?php esc_html_e( 'Fixed Fee', 'extra-fees-for-woocommerce' ); ?></option>
				<option value="2" <?php echo selected( $rule->get_fee_type(), '2', true ); ?>><?php esc_html_e( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ); ?></option>
			</select>
		</p>
		<p class="form-field efw-rule-fixed-fee">
			<label><?php esc_html_e( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="number" min="0" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_fixed_fee]" value="<?php echo esc_attr( $rule->get_fixed_fee() ); ?>"/>
		</p>
		<p class="form-field efw-rule-percent-fee">
			<label><?php esc_html_e( 'Fee Value in %', 'extra-fees-for-woocommerce' ); ?><span class='required'>*</span></label>
			<input type="number" min="0" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_percent_fee]" value="<?php echo esc_attr( $rule->get_percent_fee() ); ?>"/>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'From Date', 'extra-fees-for-woocommerce' ); ?></label>
			<?php
			$rule_from_date = array(
				'name'        => "efw_product_fees[$rule_id][efw_from_date]",
				'value'       => $rule->get_from_date(),
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
				'name'        => "efw_product_fees[$rule_id][efw_to_date]",
				'value'       => $rule->get_to_date(),
				'wp_zone'     => false,
				'placeholder' => EFW_Date_Time::get_wp_date_format(),
			);
			efw_get_datepicker_html( $rule_to_date );
			?>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Minimum Quantity', 'extra-fees-for-woocommerce' ); ?></label>
			<input type="number" min="0" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_minimum_qty]" value="<?php echo esc_attr( $rule->get_minimum_qty() ); ?>"/>
		</p>
		<p class="form-field">
			<label><?php esc_html_e( 'Maximum Quantity', 'extra-fees-for-woocommerce' ); ?></label>
			<input type="number" min="0" name="efw_product_fees[<?php echo esc_attr( $rule_id ); ?>][efw_maximum_qty]" value="<?php echo esc_attr( $rule->get_maximum_qty() ); ?>"/>
		</p>
	</div>
</div>
<?php
