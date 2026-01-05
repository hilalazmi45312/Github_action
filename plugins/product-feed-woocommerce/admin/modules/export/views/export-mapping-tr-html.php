<?php
/**
 * Export header page
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$checked = is_array( $val ) ? $val[1] : 0;
$val = ( is_array( $val ) ? $val[0] : $val );
$is_static = false;
$static_map_value = '';
if ( strpos( $val, 'wt_static_map_vl:' ) !== false ) {
	$is_static = true;
	$static_map_value = str_replace( 'wt_static_map_vl:', '', $val );
}
$is_compute = false;
$compute_map_value = '';
if ( strpos( $val, 'wt_compute_map_vl:' ) !== false ) {
	$is_compute = true;
	$compute_map_value = str_replace( 'wt_compute_map_vl:', '', $val );
}


?>
<tr id="columns_<?php echo esc_html( $key ); ?>" 
						   <?php
							if ( $custom_attr ) :
								echo 'class="wt_pf_dynamic_attr"';
endif;
							?>
 >
	<td>
	<?php if ( ! $custom_attr ) : ?>    
	<div class="wt_pf_sort_handle"><span class="dashicons dashicons-move"></span></div>
		<?php else : ?>
		<div class="wt_pf_delete_row_handle"><span class="dashicons dashicons-remove wt-pf-remove-row"></span></div>
		<?php endif; ?>
	<input type="checkbox" name="columns_key[]" class="columns_key wt_pf_mapping_checkbox_sub" value="<?php echo esc_html( $key ); ?>" <?php echo ( 1 === $checked ? 'checked' : '' ); ?>></td>
	<td>
			<?php if ( $custom_attr ) { ?>
		<input required="" type="text" name="wt_pf_custom_attr_val_<?php echo absint( $custom_attr_count ); ?>" value="<?php echo esc_html( $key ); ?>" class="wt_pf_dynamic_attr_input" id="wt_pf_custom_attr_val_<?php echo absint( $custom_attr_count ); ?>" style="width:180px;">
			<?php } else { ?>
				<label class="wt_pf_mapping_column_label"><?php echo esc_html( $label ); ?></label>
			<?php } ?>
				
	</td>
	<td>
		<select name="columns_val[]" class="wc-enhanced-select columns_val"  
		<?php
		if ( $custom_attr ) :
			echo 'wt_pf_custom_attr_selval_' . absint( $custom_attr_count );
endif;
		?>
		" >
					<?php
					$allowed_tags = array(
						'select' => array(
							'id' => array(),
							'class' => array(),
							'name' => array(),
						),
						'option' => array(
							'value' => array(),
							'selected' => array(),
						),
					);
					echo wp_kses( $wc_prod_attributes, $allowed_tags );
					?>
		</select>
	</td>
	<td>
		<input type="text" name="columns_static_val[]" value="<?php echo esc_attr( $static_map_value ); ?>" 
			<?php echo ! $is_static ? 'style="display: none;"' : ''; ?>
		 class="columns_static_val">
	</td>
</tr>
