<?php
?>
<fieldset class="bwfan-tab-content bwfan-activeTab" setting-id="tab-birthday">
	<div class="form-group field-input">
		<label><?php esc_html_e( 'Show birthday field on checkout', 'wp-marketing-automations-pro' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input class="bwfan_enable_birthday_checkout" name="bwfan_enable_birthday_checkout" type="checkbox" value="1" <?php echo empty( $global_settings['bwfan_enable_birthday_checkout'] ) ? '' : 'checked'; ?> />
				<span class=""><?php esc_html_e( 'Enable birthday field to be shown on checkout page.', 'wp-marketing-automations-pro' ); ?></span>
			</div>
		</div>
	</div>
	<div class="form-group field-input">
		<label><?php esc_html_e( 'Field Description', 'wp-marketing-automations-pro' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="bwfan_birthday_field_desc" value="<?php echo empty( $global_settings['bwfan_birthday_field_desc'] ) ? '' : $global_settings['bwfan_birthday_field_desc']; //phpcs:ignore WordPress.Security.EscapeOutput ?>"/>
				<span class=""><?php esc_html_e( 'This description will be shown with the birthday field.', 'auutonami-automations-pro' ); ?></span>
			</div>
		</div>
	</div>
	<div class="form-group field-input">
		<label><?php esc_html_e( 'Checkout Field Placement', 'wp-marketing-automations-pro' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<select name="bwfan_birthday_field_position">
					<?php $selected_position = isset( $global_settings['bwfan_birthday_field_position'] ) && ! empty( $global_settings['bwfan_birthday_field_position'] ) ? $global_settings['bwfan_birthday_field_position'] : ''; ?>
					<option value="after_order_notes" <?php echo $selected_position === "after_order_notes" ? 'selected' : ''; ?> >After Order Notes</option>
					<option value="before_order_notes" <?php echo $selected_position === "before_order_notes" ? 'selected' : ''; ?> >Before Order Notes</option>
					<option value="after_billing_details" <?php echo $selected_position === "after_billing_details" ? 'selected' : ''; ?> >After Billing Details</option>
				</select>
			</div>
		</div>
	</div>
</fieldset>
