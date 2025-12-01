<?php
/**
 * The markup for options under groups
 *
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! pewc_is_pro() ) {
	// return;
} ?>

<div class="options_group pewc-group-settings">

	<p class="form-field">
		<?php $args = array(
			'id'			=> 'pewc_display_groups',
			'class' 		=> 'pewc-display-groups',
			'label'			=> __( 'Display groups as', 'pewc' ),
			'options'		=> array(
				'standard'		=> __( 'Standard', 'pewc' ),
				'accordion'		=> __( 'Accordion', 'pewc' ),
				'lightbox'		=> __( 'Lightbox', 'pewc' ),
				'steps'			=> __( 'Steps', 'pewc' ),
				'tabs'			=> __( 'Tabs', 'pewc' ),
			)
		);
		woocommerce_wp_select( $args ); ?>
	</p>

	<p class="form-field">
		<label for="pewc_global_groups_by_product"><?php _e( 'Assign global groups', 'pewc' ); ?></label>
		<?php $global_group_ids = pewc_get_global_groups_list();
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : false;
		$pewc_global_groups_by_product = get_post_meta( $post_id, 'pewc_global_groups_by_product', true );
		$pewc_global_groups_by_product = explode( ',', $pewc_global_groups_by_product ); ?>
		<select multiple="multiple" class="pewc-pewc_global_groups_by_product-groups pewc-multiselect" name="pewc_global_groups_by_product[]" id="pewc_global_groups_by_product">';
			<?php foreach( $global_group_ids as $group_id=>$group_label ) {
				$selected = ( isset( $pewc_global_groups_by_product ) && is_array( $pewc_global_groups_by_product ) && in_array( trim( $group_id ), $pewc_global_groups_by_product ) ) ? 'selected="selected"' : ''; ?>
				<option <?php echo $selected; ?> value="<?php echo trim( $group_id ); ?>"><?php echo trim( $group_label ); ?></option>
			<?php } ?>
		</select>
	</p>

	<p class="form-field">
		<label for="pewc_hide_quantity"><?php _e( 'Hide quantity', 'pewc' ); ?> <?php echo wc_help_tip( 'Hide the main quantity field on the frontend', 'pewc' ); ?></label>
		<?php
		// 3.26.11
		$pewc_hide_quantity = get_post_meta( $post_id, 'pewc_hide_quantity', 'no' );
		?>
		<input type="checkbox" name="pewc_hide_quantity" value="yes" <?php echo $pewc_hide_quantity == 'yes' ? 'checked="checked"' : ''; ?>>
	</p>

</div>

<?php
if( ! apply_filters( 'pewc_enable_assign_duplicate_groups', false ) ) {
	return;
}

?>

<div class="options_group">

	<div class="pewc-group-options-wrap">
		<?php printf(
			'<h3 class="pewc-group-meta-heading">%s</h3>',
			__( 'Assign groups to other products', 'pewc' )
		); ?>
	</div>

	<p class="form-field">
		<?php printf(
			'<label>%s</label>',
			__( 'Assign to products', 'pewc' )
		); ?>
		<select class="wc-product-search" data-options="" multiple="multiple" style="width: 100%;" name="pewc_assign_to_products[]" id="pewc_assign_to_products" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Choose the products', 'pewc' ); ?>" data-action="woocommerce_json_search_products" data-include="" data-exclude="">
		</select>
	</p>

	<?php $args = array(
		'id'			=> 'pewc_replace_existing_groups',
		'class' 	=> 'pewc-replace-existing-groups',
		'label'		=> __( 'Replace existing groups', 'pewc' )
	);
	woocommerce_wp_checkbox( $args ); ?>

	<p>
		<?php printf(
			'<a href="#" class="pewc_assign_groups_to_products button button-primary" id="pewc_assign_groups_to_products">%s</a>',
			__( 'Assign', 'pewc' )
		); ?>
	</p>

</div>
