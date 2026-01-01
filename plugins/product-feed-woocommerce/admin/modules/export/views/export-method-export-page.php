<?php
/**
 * Export page method select
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_pf_export_main">
	<p><?php echo esc_html( $step_info['description'] ); ?></p>
	
	<div class="wt_pf_warn wt_pf_method_export_wrn" style="display:none;">
		<?php esc_html_e( 'Please select an export method', 'product-feed-woocommerce' ); ?>
	</div>

	<div class="wt_pf_warn wt_pf_export_template_wrn" style="display:none;">
		<?php esc_html_e( 'Please select an export template.', 'product-feed-woocommerce' ); ?>
	</div>
	<div id="product-type-message" class="updated" style="margin:0px;display: none;background: #dceff4;"><p><?php esc_html_e( 'The free version of this plugin exports and imports only WooCommerce Simple, Grouped and External/Affiliate product types.', 'product-feed-woocommerce' ); ?></p></div>
	<table class="form-table wt-pfd-form-table">
		<tr>
			<th><label><?php esc_html_e( 'Select an export method', 'product-feed-woocommerce' ); ?></label></th>
			<td colspan="2" style="width:75%;">
				<div class="wt_pf_radio_block">
					<?php
					if ( empty( $this->mapping_templates ) ) {
						unset( $this->export_obj->export_methods['template'] );
					}
					foreach ( $this->export_obj->export_methods as $key => $value ) {
						?>
						<p>
							<input type="radio" value="<?php echo esc_html( $key ); ?>" id="wt_pf_export_<?php echo esc_html( $key ); ?>_export" name="wt_pf_export_method_export" <?php echo ( $key == $this->export_method ? 'checked="checked"' : '' ); ?>><b><label for="wt_pf_export_<?php echo esc_html( $key ); ?>_export"><?php echo esc_attr( $value['title'] ); ?></label></b> <br />
							<span><label for="wt_pf_export_<?php echo esc_html( $key ); ?>_export"><?php echo esc_html( $value['description'] ); ?></label></span>
						</p>
						<?php
					}
					?>
				</div>

			</td>
		</tr>
		<?php if ( ! empty( $this->mapping_enabled_fields ) ) : ?>
		<tr class="wt-pfd-export-method-options wt-pfd-export-method-options-quick">
			<th style="width:150px; text-align:left; vertical-align:top;"><label><?php esc_html_e( 'Include fields from the respective groups', 'product-feed-woocommerce' ); ?></label></th>
			<td colspan="2" style="width:75%;">
				<?php
				foreach ( $this->mapping_enabled_fields as $mapping_enabled_field_key => $mapping_enabled_field ) {
					$mapping_enabled_field = ( ! is_array( $mapping_enabled_field ) ? array( $mapping_enabled_field, 0 ) : $mapping_enabled_field );

					if ( $this->rerun_id > 0 ) {
						if ( in_array( $mapping_enabled_field_key, $form_data_mapping_enabled ) ) {
							$mapping_enabled_field[1] = 1; // mark it as checked.
						} else {
							$mapping_enabled_field[1] = 0; // mark it as unchecked.
						}
					}
					?>
					<div class="wt_pf_checkbox" style="padding-left:0px;">
						<input type="checkbox" id="wt_pf_<?php echo esc_html( $mapping_enabled_field_key ); ?>" name="wt_pf_include_these_fields[]" value="<?php echo esc_html( $mapping_enabled_field_key ); ?>" <?php echo ( 1 == $mapping_enabled_field[1] ? 'checked="checked"' : '' ); ?> /> 
						<label for="wt_pf_<?php echo esc_html( $mapping_enabled_field_key ); ?>"><?php echo esc_html( $mapping_enabled_field[0] ); ?></label>
					</div>  
					<?php
				}
				?>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Enabling any of these ensures that all the fields from the respective groups are included in your export.', 'product-feed-woocommerce' ); ?></span>
			</td>
		</tr>
		<?php endif; ?>

		<tr class="wt-pfd-export-method-options wt-pfd-export-method-options-template" style="display:none;">
			<th><label><?php esc_html_e( 'Export template', 'product-feed-woocommerce' ); ?></label></th>
			<td>
				<select class="wt-pfd-export-template-sele">
					<option value="0">-- <?php esc_html_e( 'Select a template', 'product-feed-woocommerce' ); ?> --</option>
					<?php
					foreach ( $this->mapping_templates as $mapping_template ) {
						?>
						<option value="<?php echo esc_html( $mapping_template['id'] ); ?>" <?php echo ( $form_data_export_template == $mapping_template['id'] ? ' selected="selected"' : '' ); ?>>
						<?php echo esc_html( $mapping_template['name'] ); ?>
						</option>
						<?php
					}
					?>
				</select>
			</td>
			<td>
			</td>
		</tr>
	</table>
</div>
