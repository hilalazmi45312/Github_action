<?php
/**
 * Export meta step page
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="wt-pfd-mapping-tb wt-pfd-exporter-meta-mapping-tb" data-field-type="<?php echo esc_html( $meta_mapping_screen_field_key ); ?>">
	<thead>
		<tr>
			<th>
				<?php
				$is_checked = ( isset( $meta_mapping_screen_field_val['checked'] ) && 1 == $meta_mapping_screen_field_val['checked'] ? 1 : 0 );
				$checked_attr = ( 1 == $is_checked ? ' checked="checked"' : '' );
				?>
				<input type="checkbox" name="" class="wt_pf_mapping_checkbox_main" <?php echo esc_html( $checked_attr ); ?>>
			</th>
			<th width="35%"><?php esc_html_e( 'Column', 'product-feed-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Column name', 'product-feed-woocommerce' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$tr_count = 0;
		$custom_attr = false; // Custom attr is not applicable in meta as custom field deosnot have a meta section.
		if ( isset( $meta_mapping_screen_field_val['fields'] ) && is_array( $meta_mapping_screen_field_val['fields'] ) && count( $meta_mapping_screen_field_val['fields'] ) > 0 ) {
			foreach ( $meta_mapping_screen_field_val['fields'] as $key => $val ) {
				$val = is_array( $val ) ? $val : array( $val, 0 );
				$label = $val[0];

				if ( isset( $current_meta_step_form_data[ $key ] ) ) {
					$val = ( is_array( $current_meta_step_form_data[ $key ] ) ? $current_meta_step_form_data[ $key ] : array( $current_meta_step_form_data[ $key ], 1 ) );
				} else {
					$val[1] = $is_checked; // parent is checked.
				}



				$selected_val = ! empty( $current_meta_step_form_data[ $key ][0] ) ? $current_meta_step_form_data[ $key ][0] : $key;
				$wc_prod_attributes = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::attribute_dropdown( $this->to_export, $selected_val );

				$row_matched = 0;
				if ( false !== strpos( $wc_prod_attributes, ' selected>' ) ) {
					$row_matched = 1;
				}
				$val[1] = $row_matched;

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
