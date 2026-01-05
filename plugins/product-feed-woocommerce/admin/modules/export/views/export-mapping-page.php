<?php
/**
 * Export header page
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_pf_export_main">
	<p><?php echo esc_html( $step_info['description'] ); ?></p>
	<div class="wtpf_meta_mapping_box">
		<div class="wtpf_meta_mapping_box_hd wt_pf_noselect">
			<span class="dashicons dashicons-arrow-down"></span>
			<?php esc_html_e( 'Default fields', 'product-feed-woocommerce' ); ?>
			<span class="wtpf_meta_mapping_box_selected_count_box"><span class="wtpf_meta_mapping_box_selected_count_box_num">0</span> <?php esc_html_e( ' columns(s) selected', 'product-feed-woocommerce' ); ?></span>
		</div>
		<div style="clear:both;"></div>
		<div class="wtpf_meta_mapping_box_con" data-sortable="0" data-loaded="1" data-field-validated="0" data-key="" style="display:inline-block;">
			<table class="wt-pfd-mapping-tb wt-pfd-exporter-default-mapping-tb">
				<thead>
					<tr>
						<th>
							<input type="checkbox" name="" class="wt_pf_mapping_checkbox_main">
						</th>
						<th width="35%"><span id="wt_pf_channel_selected"><?php esc_html_e( 'Catalog', 'product-feed-woocommerce' ); ?></span> <?php esc_html_e( 'Attributes', 'product-feed-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'WooCommerce Product Fields', 'product-feed-woocommerce' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$draggable_tooltip = __( 'Drag to rearrange the columns', 'product-feed-woocommerce' );
				$tr_count = 0;
				$custom_attr_count = 0;
				$custom_attr = false;
				foreach ( $form_data_mapping_fields as $key => $val ) {
					if ( isset( $mapping_fields[ $key ] ) ) {
						$label = $mapping_fields[ $key ];
						$wc_prod_attributes = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::attribute_dropdown( $this->to_export, $val[0] );
						include 'export-mapping-tr-html.php';
						unset( $mapping_fields[ $key ] ); // remove the field from default list.
						$tr_count++;
					} elseif ( 'custom' === $this->to_export ) {
							$custom_attr = true;
							$label = $key;
							$wc_prod_attributes = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::attribute_dropdown( $this->to_export, $val[0] );
							include 'export-mapping-tr-html.php';
							$tr_count++;
							$custom_attr_count++;
					}
				}
				if ( count( $mapping_fields ) > 0 ) {
					foreach ( $mapping_fields as $key => $label ) {
						$wc_prod_attributes = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::attribute_dropdown( $this->to_export, $key );
						$row_matched = 0;
						if ( false !== strpos( $wc_prod_attributes, ' selected>' ) ) {
							$row_matched = 1;
						}
						$val = array( $key, $row_matched ); // enable the field.
						include 'export-mapping-tr-html.php';
						$tr_count++;
					}
				}
				if ( 0 == $tr_count ) {
					?>
					<tr>
						<td colspan="3" style="text-align:center;">
							<?php esc_html_e( 'No fields found.', 'product-feed-woocommerce' ); ?>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
				<?php if ( 'custom' === $this->to_export ) : ?>
					<?php // Add dynamic created fields here. ?>        
				<button class="button button-secondary wt_pf_add_attr_btn" type="button"> <?php esc_html_e( '+ Add New Attribute', 'product-feed-woocommerce' ); ?></button>
				<?php endif; ?>
		</div>
	</div>
	<div style="clear:both;"></div>
	<?php
	if ( $this->mapping_enabled_fields ) {
		foreach ( $this->mapping_enabled_fields as $mapping_enabled_field_key => $mapping_enabled_field ) {
			$mapping_enabled_field = ( ! is_array( $mapping_enabled_field ) ? array( $mapping_enabled_field, 0 ) : $mapping_enabled_field );

			if ( count( $form_data_mapping_enabled_fields ) > 0 ) {
				if ( in_array( $mapping_enabled_field_key, $form_data_mapping_enabled_fields ) ) {
					$mapping_enabled_field[1] = 1;
				} else {
					$mapping_enabled_field[1] = 0;
				}
			}
			?>
			<div class="wtpf_meta_mapping_box">
				<div class="wtpf_meta_mapping_box_hd wt_pf_noselect">
					<span class="dashicons dashicons-arrow-right"></span>
					<?php echo esc_html( $mapping_enabled_field[0] ); ?>
					<span class="wtpf_meta_mapping_box_selected_count_box"><span class="wtpf_meta_mapping_box_selected_count_box_num">0</span> <?php esc_html_e( ' columns(s) selected', 'product-feed-woocommerce' ); ?></span>
				</div>
				<div style="clear:both;"></div>
				<div class="wtpf_meta_mapping_box_con" data-sortable="0" data-loaded="0" data-field-validated="0" data-key="<?php echo esc_attr( $mapping_enabled_field_key ); ?>"></div>
			</div>
			<div style="clear:both;"></div>
			<?php
		}
	}
	?>
</div>
