<?php
/**
 * The markup for a group
 *
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="pewc-group-meta-table wc-metabox" data-group-id="<?php echo $group_id; ?>">
	<div class="form-row">
		<div class="product-extra-field-third">
			<label>
				<?php _e( 'Group Title', 'pewc' ); ?>
				<?php echo wc_help_tip( 'Enter a title for this group that will be displayed on the product page. Leave blank if you wish.', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<input type="text" class="pewc-group-title" name="_product_extra_groups_<?php echo $group_id; ?>[meta][group_title]" value="<?php echo esc_attr( $group_title ); ?>">
		</div>
	</div>
	<div class="form-row">
		<div class="product-extra-field-third pewc-description">
			<?php $description = pewc_get_group_description( $group_id, $group, $has_migrated ); ?>
			<label>
				<?php _e( 'Group Description', 'pewc' ); ?>
				<?php echo wc_help_tip( 'An optional description for the group', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<textarea class="pewc-group-description" name="_product_extra_groups_<?php echo $group_id; ?>[meta][group_description]"><?php echo esc_html( $description ); ?></textarea>
		</div>
	</div>
	<div class="form-row">
		<div class="product-extra-field-third">
			<?php $group_layout = pewc_get_group_layout( $group_id ); ?>
			<label>
				<?php _e( 'Group Layout', 'pewc' ); ?>
				<?php echo wc_help_tip( 'Choose how to display the fields in this group.', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<select class="pewc-group-layout" name="_product_extra_groups_<?php echo $group_id; ?>[meta][group_layout]">
				<?php do_action( 'pewc_start_group_layout_options', $group_layout ); ?>
				<option <?php selected( $group_layout, 'ul', true ); ?> value="ul"><?php _e( 'Standard', 'pewc' ); ?></option>
				<option <?php selected( $group_layout, 'table', true ); ?> value="table"><?php _e( 'Table', 'pewc' ); ?></option>
				<option <?php selected( $group_layout, 'cols-2', true ); ?> value="cols-2"><?php _e( 'Two Columns', 'pewc' ); ?></option>
				<option <?php selected( $group_layout, 'cols-3', true ); ?> value="cols-3"><?php _e( 'Three Columns', 'pewc' ); ?></option>
			</select>
		</div>
	</div>
	<div class="form-row">
		<div class="product-extra-field-third">
			<?php $group_class = pewc_get_group_class( $group_id ); ?>
			<label>
				<?php _e( 'Group Class', 'pewc' ); ?>
				<?php echo wc_help_tip( 'Optional classes to add to this group.', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<input type="text" class="pewc-group-class" name="_product_extra_groups_<?php echo $group_id; ?>[meta][group_class]" value="<?php echo esc_attr( $group_class ); ?>" />
		</div>
		
	</div>
	<div class="form-row group-conditions-row">
		<div class="product-extra-field-third">
			<label>
				<?php _e( 'Group Conditions', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right pewc-fields-conditionals">
			<?php include( PEWC_DIRNAME . '/templates/admin/views/group-condition.php' ); ?>
		</div>
	</div>
	<div class="form-row group-conditions-row">
		<div class="product-extra-field-third">
			<label>
				<?php _e( 'Always Include in Order', 'pewc' ); ?>
				<?php echo wc_help_tip( 'Select this option to ensure that information from the fields in this group are always passed to the order, even if the group is hidden by its conditions.', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<?php $checked = pewc_get_group_include_in_order( $group_id ) ? 'checked' : ''; ?>
			<input type="checkbox" <?php echo $checked; ?> class="pewc-group-always-include" name="_product_extra_groups_<?php echo $group_id; ?>[meta][always_include]" value="1>" />
		</div>
	</div>
	<div class="form-row">
		<div class="product-extra-field-third">
			<label>
				<?php _e( 'Repeatable', 'pewc' ); ?>
				<?php echo wc_help_tip( 'Select this option to allow this group to be repeatable on the frontend product pages', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<?php $checked = pewc_get_group_repeatable( $group_id ) ? 'checked' : ''; ?>
			<input type="checkbox" <?php echo $checked; ?> class="pewc-group-repeatable" name="_product_extra_groups_<?php echo $group_id; ?>[meta][repeatable]" value="1" data-group-id="<?php echo $group_id ?>" />
		</div>
	</div>
	<div class="form-row pewc-repeatable-options-<?php echo $group_id ?> pewc-repeatable-by-quantity-<?php echo $group_id ?><?php echo empty( $checked ) ? ' hidden' : ''; ?>">
		<div class="product-extra-field-third">
			<label>
				<?php _e( 'Attach to Quantity', 'pewc' ); ?>
				<?php echo wc_help_tip( 'Select this option to repeat a group depending on the product quantity', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<?php $checked_quantity = pewc_get_group_repeatable_by_quantity( $group_id ) ? 'checked' : ''; ?>
			<input type="checkbox" <?php echo $checked_quantity; ?> class="pewc-group-repeatable-by-quantity" name="_product_extra_groups_<?php echo $group_id; ?>[meta][repeatable_by_quantity]" value="1" />
		</div>
	</div>
	<div class="form-row pewc-repeatable-options-<?php echo $group_id ?> pewc-repeatable-limit-<?php echo $group_id ?><?php echo empty( $checked ) ? ' hidden' : ''; ?>">
		<div class="product-extra-field-third">
			<label>
				<?php _e( 'Repeat Limit', 'pewc' ); ?>
				<?php echo wc_help_tip( 'The number of times a group can be repeated on the frontend. Enter 0 for unlimited.', 'pewc' ); ?>
			</label>
		</div>
		<div class="product-extra-field-two-thirds-right">
			<?php $repeat_limit = pewc_get_group_repeatable_limit( $group_id ); ?>
			<input type="number" class="pewc-group-repeatable-limit" name="_product_extra_groups_<?php echo $group_id; ?>[meta][repeatable_limit]" value="<?php echo $repeat_limit; ?>" />
		</div>
	</div>
	<?php do_action( 'pewc_group_extra_fields', $group_id, $group ); ?>
</div>
