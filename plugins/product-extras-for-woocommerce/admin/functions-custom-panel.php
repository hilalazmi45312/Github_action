<?php
/**
 * Functions for setting up the Product Add-Ons panel
 * @since 1.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * You can filter the Product Add-Ons name
 */
function pewc_get_post_type_labels() {
	return apply_filters(
		'pewc_filter_post_type_label',
		array(
			'single' => __( 'Product Add-On' ),
			'plural' => __( 'Product Add-Ons' )
		)
	);
}

/**
 * Product Add On tab / panel
 * @param $tabs	List of tabs
 */
function pewc_product_tabs( $tabs ) {
	$label = pewc_get_post_type_labels();
	$tabs['pewc'] = array(
		'label'		=> $label['plural'],
		'target'	=> 'pewc_options',
		'class'		=> array( 'show_if_simple', 'show_if_variable', 'show_if_simple_booking' ),
		'priority'	=> 100
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'pewc_product_tabs' );

/**
 * Change tab icon
 */
function pewc_icon_style() { ?>
	<style>
		#woocommerce-product-data ul.wc-tabs li.pewc_options a:before { font-family: WooCommerce; content: '\e007'; }
	</style><?php
}
add_action( 'admin_head', 'pewc_icon_style' );

/**
 * Product Add-Ons tab options.
 */
function pewc_tab_options() {
	global $post;
	$class = pewc_is_pro() ? 'pewc-is-pro' : '';
	$has_migrated = pewc_has_migrated();
	if( $has_migrated ) {
		$class .= ' pewc_has_migrated';
	}
	if( pewc_enable_ajax_upload() == 'yes' ) {
		$class .= ' pewc-is-ajax-upload';
	} ?>

	<div id='pewc_options' class='panel woocommerce_options_panel pewc_panel <?php echo esc_attr( $class ); ?>'>

		<div class="options_group">
			<div class="options-group-inner">
				<ul class="new-field-list">
					<?php include( PEWC_DIRNAME . '/templates/admin/new-field-item.php' ); ?>
				</ul>
				<table class="new-option">
					<?php include( PEWC_DIRNAME . '/templates/admin/views/option-new.php' ); ?>
				</table>
				<div class="new-information-row">
					<?php include( PEWC_DIRNAME . '/templates/admin/views/information-row-new.php' ); ?>
				</div>

				<div class="product-extra-group-data" id="product_extra_groups">

					<!-- Start of the new-group-row element -->
					<?php include( PEWC_DIRNAME . '/templates/admin/new-group.php' ); ?>

					<?php include( PEWC_DIRNAME . '/templates/admin/new-conditional-row.php' ); ?>

					<?php $group_order = pewc_get_group_order( $post->ID ); ?>

					<input type="hidden" id="pewc_group_order" name="pewc_group_order" value="<?php echo $group_order; ?>">
					<input type="hidden" id="pewc_addons_loaded" name="pewc_addons_loaded" value="<?php echo ! pewc_enable_ajax_load_addons(); ?>">

					<div id="pewc_group_wrapper">
						<?php if( ! pewc_enable_ajax_load_addons( $post->ID ) ) {
							$groups = pewc_get_extra_fields( $post->ID );
							pewc_display_product_groups( $groups, $post->ID, false );
						} ?>
					</div>

				</div><!-- #product_extra_groups -->

				<p class="pewc-import-export-aou-select-all"><input type="checkbox" id="pewc_select_all_group_for_export"> <?php _e( 'Select all groups', 'pewc' ); ?></p>
				<p><a href="#" class="button button-primary add_new_group"><?php _e( 'Add Group', 'pewc' ); ?></a></p>

			</div>
		</div>

		<?php include( PEWC_DIRNAME . '/templates/admin/group-settings.php' );
		// Deprecated in 2.1.0
		// include( PEWC_DIRNAME . '/templates/admin/import-groups.php' ); ?>
		<?php wp_nonce_field( 'add_new_pewc_group_nonce', 'add_new_pewc_group_nonce' ); ?>
		<?php do_action( 'pewc_end_tab_options', $groups, $post->ID ); ?>
		<div class="pewc-loading"><span class="spinner"></span></div>

	</div>

<?php }
add_action( 'woocommerce_product_data_panels', 'pewc_tab_options' );

/**
 * Display the groups
 * @param $groups 	_product_extra_groups meta data
 * @param $post_id	Post ID for product
 * @param $is_ajax	Are we loading the add-ons via AJAX?
 * @param $global		Boolean, true if doing global addons
 *
 * Usually $post_id will be the ID of the product we're on
 * However, this function is also called by AJAX when importing groups
 * In which case, $post_id will be the ID of the product where groups are imported from
 */
function pewc_display_product_groups( $groups, $post_id, $is_ajax=false, $global=false ) {

	$licence = pewc_get_license_level();
	$group_count = 0;
	$has_migrated = pewc_has_migrated();

	// Get global groups as an array
	$global_groups = pewc_get_all_global_group_ids();
	$global_groups = explode( ',', $global_groups );

	if( $groups ) {

		$field_types = pewc_field_types();

		foreach( $groups as $group_id=>$group ) {

			if( $post_id === 0 ) {

				// Need to check if migration has taken place and make sure $group is formed correctly
				$group = pewc_get_global_groups( $group_id );

			} else {

				// We're not on the global settings page so ensure we don't output any global groups here
				if( in_array( $group_id, $global_groups ) ) {
					continue;
				}

			} ?>

			<div data-group-count="<?php echo $group_count; ?>" data-group-id="<?php echo esc_attr( $group_id ); ?>" id="group-<?php echo esc_attr( $group_id ); ?>" class="group-row">

				<div class="new-field-table field-table collapse-panel">

					<?php $group_title = pewc_get_group_title( $group_id, $group, $has_migrated ); ?>

					<div class="wc-metabox">

						<div class="pewc-group-heading-wrap">

							<?php
							printf(
								'<h3 class="pewc-group-meta-heading">
								<input type="checkbox" class="pewc-import-export-aou-group-checkbox" value="%s"> 
								%s <span class="meta-item-id">%s</span>: <span class="pewc-display-title">%s</span></h3>',
								$group_id,
								__( 'Group', 'pewc' ),
								'&#35;' . $group_id,
								stripslashes( $group_title )
							); ?>

							<?php include( PEWC_DIRNAME . '/templates/admin/group-meta-actions.php' ); ?>

						</div><!-- .pewc-group-heading-wrap -->

					</div><!-- .pewc-group-meta-table -->

					<?php do_action( 'pewc_after_group_title', $group_id, $group, $post_id, $is_ajax ); ?>

					<div class="pewc-all-fields-wrapper">

						<?php include( PEWC_DIRNAME . '/templates/admin/group.php' ); ?>

						<?php printf(
							'<h3 class="pewc-field-heading">%s</h3>',
							__( 'Fields', 'pewc' )
						); ?>
						<ul class="field-list" data-pewc-field-list-group-id="<?php echo esc_attr( $group_id ); ?>"><?php
							// 3.23.1, ul.field-list needs to be empty with no whitespaces, so that the CSS style ul.field-list.ui-sortable:empty works
							if( isset( $group['items'] ) ) {
								$item_count = 0;
								foreach( $group['items'] as $item ) {
									if( isset( $item['field_type'] ) ) {
										include( PEWC_DIRNAME . '/templates/admin/field-item.php' );
										$item_count++;
									}
								}
							}
						?></ul>
						<p><a href="#" class="button add_new_field"><?php _e( 'Add Field', 'pewc' ); ?></a></p>
					</div><!-- .pewc-fields-wrapper -->
				</div>

			</div><!-- .group-row -->
		<?php $group_count++;
		}
	}
}

/**
 * Save the custom fields.
 */
function pewc_save_product_extra_options( $post_id ) {

	$has_migrated = pewc_has_migrated();

	// Check nonce

	if( $has_migrated ) {

		// Save the group order
		if( ! empty( $_POST['pewc_group_order'] ) ) {

			// Save all group IDs to this product
			update_post_meta( $post_id, 'group_order', sanitize_text_field( $_POST['pewc_group_order'] ) );
			$group_order = explode( ',', $_POST['pewc_group_order'] );

		} else {

			$group_order = array();
			delete_post_meta( $post_id, 'group_order' );

		}

		// This method of saving field data introduced in 3.3.0
		if( ! empty( $_POST ) ) {

			$params = pewc_get_field_params();
			$has_addons = false;

			// Use this to store the field order for each group
			$field_ids = array();

			// We use this to save the IDs of images that might be used as secondary images for swatches
			$secondary_images = array();

			foreach( $_POST as $key=>$value ) {

				if( strpos( $key, '_product_extra_groups_' ) !== false ) {

					$has_addons = true;

					// Get the group ID
					$ids = str_replace( '_product_extra_groups_', '', $key );
					$ids = explode( '_', $ids );
					$group_id = isset( $ids[0] ) ? $ids[0] : false;
					$field_id = isset( $ids[1] ) ? $ids[1] : false;

					// Delete the conditional rules transient
					delete_transient( 'pewc_rules_transient_pewc_group_' . $group_id . '_' . $field_id );

					if( ! in_array( $group_id, $group_order ) ) {

						// If we've found a group that isn't in the group order, delete it
						$delete = wp_delete_post( $group_id, true );

					} else {

						// Save group meta
						if( ! $field_id ) {

							$condition_action = ! empty( $_POST['_product_extra_groups_' . $group_id]['condition_action'] ) ? $_POST['_product_extra_groups_' . $group_id]['condition_action'] : false;
							$condition_match = ! empty( $_POST['_product_extra_groups_' . $group_id]['condition_match'] ) ? $_POST['_product_extra_groups_' . $group_id]['condition_match'] : false;
							$condition_field = ! empty( $_POST['_product_extra_groups_' . $group_id]['condition_field'] ) ? array_values( $_POST['_product_extra_groups_' . $group_id]['condition_field'] ) : false;
							$condition_rule = ! empty( $_POST['_product_extra_groups_' . $group_id]['condition_rule'] ) ? array_values( $_POST['_product_extra_groups_' . $group_id]['condition_rule'] ) : false;
							$condition_value = ! empty( $_POST['_product_extra_groups_' . $group_id]['condition_value'] ) ? array_values( $_POST['_product_extra_groups_' . $group_id]['condition_value'] ) : false;
							$condition_field_type = ! empty( $_POST['_product_extra_groups_' . $group_id]['condition_field_type'] ) ? array_values( $_POST['_product_extra_groups_' . $group_id]['condition_field_type'] ) : false;

							// Combine this into one array
							$conditions = array();
							if( $condition_field ) {
								foreach( $condition_field as $index=>$field ) {
									if ( empty( $condition_field[$index] ) || 'not-selected' == $condition_field[$index] ) {
										continue; // skip incomplete conditions
									}
									$conditions[] = array(
										'field'				=> $condition_field[$index],
										'rule'				=> $condition_rule[$index],
										'value'				=> $condition_value[$index],
										'field_type'		=> $condition_field_type[$index]
									);
								}
							}

							// Set the group meta data
							update_post_meta( $group_id, 'group_title', sanitize_text_field( $_POST['_product_extra_groups_' . $group_id]['meta']['group_title'] ) );
							update_post_meta( $group_id, 'group_description', wp_kses_post( $_POST['_product_extra_groups_' . $group_id]['meta']['group_description'] ) );
							update_post_meta( $group_id, 'group_layout', $_POST['_product_extra_groups_' . $group_id]['meta']['group_layout'] );
							$group_class = ! empty( $_POST['_product_extra_groups_' . $group_id]['meta']['group_class'] ) ? $_POST['_product_extra_groups_' . $group_id]['meta']['group_class'] : '';
							update_post_meta( $group_id, 'group_class', $group_class );
							$always_include = ! empty( $_POST['_product_extra_groups_' . $group_id]['meta']['always_include'] ) ? $_POST['_product_extra_groups_' . $group_id]['meta']['always_include'] : false;
							if( $always_include ) {
								update_post_meta( $group_id, 'always_include', true );
							} else {
								delete_post_meta( $group_id, 'always_include' );
							}

							// 3.22.0
							$repeatable = ! empty( $_POST['_product_extra_groups_' . $group_id]['meta']['repeatable'] ) ? $_POST['_product_extra_groups_' . $group_id]['meta']['repeatable'] : false;
							if( $repeatable ) {
								update_post_meta( $group_id, 'repeatable', true );
							} else {
								delete_post_meta( $group_id, 'repeatable' );
							}
							$repeatable_by_quantity = ! empty( $_POST['_product_extra_groups_' . $group_id]['meta']['repeatable_by_quantity'] ) ? $_POST['_product_extra_groups_' . $group_id]['meta']['repeatable_by_quantity'] : false;
							if( $repeatable_by_quantity ) {
								update_post_meta( $group_id, 'repeatable_by_quantity', true );
							} else {
								delete_post_meta( $group_id, 'repeatable_by_quantity' );
							}
							update_post_meta( $group_id, 'repeatable_limit', sanitize_text_field( $_POST['_product_extra_groups_' . $group_id]['meta']['repeatable_limit'] ) );

							update_post_meta( $group_id, 'condition_action', $condition_action );
							update_post_meta( $group_id, 'condition_match', $condition_match );
							update_post_meta( $group_id, 'conditions', $conditions );

							$group_ids[] = $group_id;

						}

						$all_params = array( 'field_id' => $field_id );

						// Add this field ID to the group order
						if( $field_id && $group_id ) {
							$field_ids[$group_id][] = $field_id;
						}

						// Save each parameter as post meta
						foreach( $params as $param ) {

							if( isset( $value[$param] ) ) {

								// Ensure the options array doesn't get out of sync
								if( in_array( $param, array( 'field_options', 'condition_field', 'condition_rule', 'condition_value' ) ) ) {
									$value[$param] = array_values( $value[$param] );
								}

								// Need to sanitise this
								if ( isset( $value['field_type'] ) && 'radio' === $value['field_type'] && 'field_options' === $param && ! empty( $value[$param] ) ) {
									// 3.26.12, sanitise the radio options
									$value = pewc_sanitise_radio_field( $value );
								}
								update_post_meta( $field_id, $param, $value[$param] );
								$all_params[$param] = $value[$param];

								// Update our secondary swatches array
								if( $param == 'parent_swatch_id' && ! empty( $value[$param] ) ) {
									$parent_id = $value['parent_swatch_id'];
									$secondary_images[$parent_id][] = $field_id;
								}

							} else {

								delete_post_meta( $field_id, $param );
							}
						}

						// Filter any values here just before they're saved
						$all_params = apply_filters( 'pewc_before_update_field_all_params', $all_params, $field_id );

						update_post_meta( $field_id, 'all_params', $all_params );

						// Delete the item object transient used on the front end
						delete_transient( 'pewc_item_object_' . $field_id );

						if ( ! empty( $all_params['field_type'] ) && 'product-categories' === $all_params['field_type'] ) {
							// 3.13.1, delete product categories transient used on the front end
							delete_transient( 'pewc_categories_field_products_'.$field_id );
						}

						// Now create the transient nice and fresh
						pewc_create_item_object( $field_id );

					}

				}

			}

			// Save the field order for each group
			if( $field_ids ) {

				foreach( $field_ids as $g_id=>$f_ids ) {
					// Remove empty and duplicate values
					$f_ids = array_unique( array_filter( $f_ids ) );
					update_post_meta( $g_id, 'field_ids', $f_ids );
				}

			}

			// Use this on the front end to check for add-on fields
			if( $has_addons ) {
				pewc_set_transient( 'pewc_has_extra_fields_' . $post_id, 'yes' );
			} else {
				pewc_set_transient( 'pewc_has_extra_fields_' . $post_id, 'no' );
			}

		}

	} else {

		if( isset( $_POST['_product_extra_groups'] ) ) {
			// Add some sanitisation
			$groups = pewc_sanitise_groups( $_POST['_product_extra_groups'] );
			update_post_meta( $post_id, '_product_extra_groups', $groups );
		} else {
			delete_post_meta( $post_id, '_product_extra_groups' );
		}

	}

	$product = wc_get_product( $post_id );
	$display = ( isset( $_POST['pewc_display_groups'] ) ) ? $_POST['pewc_display_groups'] : 'standard';
	if( isset( $_POST['pewc_display_groups'] ) ) {
		$product->update_meta_data( 'pewc_display_groups', sanitize_text_field( $display ) );
		//$product->save();
	}

	$pewc_global_groups_by_product = ( isset( $_POST['pewc_global_groups_by_product'] ) ) ? $_POST['pewc_global_groups_by_product'] : '';
	if( ! empty( $_POST['pewc_global_groups_by_product'] ) ) {
		$product->update_meta_data( 'pewc_global_groups_by_product', join( ',', $pewc_global_groups_by_product ) );
		//$product->save();
	} else {
		$product->update_meta_data( 'pewc_global_groups_by_product', false );
		//$product->save();
	}

	// 3.26.11
	if ( ! empty( $_POST['pewc_hide_quantity'] ) && 'yes' === $_POST['pewc_hide_quantity'] ) {
		$product->update_meta_data( 'pewc_hide_quantity', 'yes' );
	} else {
		$product->update_meta_data( 'pewc_hide_quantity', 'no' );
	}

	// 3.26.11, let's call this here
	$product->save();

	// Save our secondary swatches
	if( $secondary_images ) {
		update_post_meta( $post_id, 'pewc_secondary_images', $secondary_images );
	}

	// Delete the transient used on the front end
	delete_transient( 'pewc_extra_fields_' . $post_id );
	delete_transient( 'pewc_has_extra_fields_' . $post_id );
	// Delete our list of product IDs and titles used on the global page
	delete_transient( 'pewc_product_ids' );
	delete_transient( 'pewc_product_titles' );

}
add_action( 'woocommerce_process_product_meta', 'pewc_save_product_extra_options' );

/**
 * Available field types
 */
function pewc_field_types() {
	$field_types = array(
		'calculation'			=> __( 'Calculation', 'pewc' ),
		'checkbox'				=> __( 'Checkbox', 'pewc' ),
		'checkbox_group'		=> __( 'Checkbox Group', 'pewc' ),
		'color-picker'    		=> __( 'Color Picker', 'pewc' ),
		'date'					=> __( 'Date', 'pewc' ),
		'information'			=> __( 'Information', 'pewc' ),
		'name_price'			=> __( 'Name Your Price', 'pewc' ),
		'number'				=> __( 'Number', 'pewc' ),
		'products'				=> __( 'Products', 'pewc' ),
		'product-categories'	=> __( 'Product Categories', 'pewc' ),
		'radio'					=> __( 'Radio Group', 'pewc' ),
		'select'				=> __( 'Select', 'pewc' ),
		'select-box'			=> __( 'Select Box', 'pewc' ),
		'image_swatch'			=> __( 'Swatch', 'pewc' ),		
		'text'					=> __( 'Text', 'pewc' ),
		'textarea'				=> __( 'Textarea', 'pewc' ),
		'upload'				=> __( 'Upload', 'pewc' )
	);
	return apply_filters( 'pewc_filter_field_types', $field_types );
}

/**
 * Group requirements
 * @return Array
 */
function pewc_group_requirements() {
	$group_requirements = array(
		'all'		=> __( 'All required fields', 'pewc' ),
		'depends'	=> __( 'All required fields if first field complete', 'pewc' )
	);
	return $group_requirements;
}

/**
 * Add the custom price label fields
 * @since 2.4.0
 */
function pewc_display_fields() {
	woocommerce_wp_text_input(
		array(
			'id'            => 'pewc_price_label',
			'label'         => __( 'Price label', 'pewc' ),
			'desc_tip'      => true,
			'description'   => __( 'Additional or replacement text for the price.', 'pewc' ),
		)
	);
	woocommerce_wp_select(
		array(
			'id'            => 'pewc_price_display',
			'label'         => __( 'Price label display', 'pewc' ),
			'desc_tip'      => true,
			'description'   => __( 'Decide where to display the label.', 'pewc' ),
			'options'				=> array(
				'before'			=> __( 'Before price', 'pewc' ),
				'after'				=> __( 'After price', 'pewc' ),
				'hide'				=> __( 'Hide price', 'pewc' )
			)
		)
	);
	if( apply_filters( 'pewc_enable_minimum_price_option', false ) ) {
		woocommerce_wp_text_input(
			array(
				'id'            => 'pewc_minimum_price',
				'label'         => __( 'Minimum price', 'pewc' ),
				'desc_tip'      => true,
				'description'   => __( 'Set an optional minimum price for this product.', 'pewc' ),
				'data_type'			=> 'price'
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'            => 'pewc_force_minimum',
				'label'         => __( 'Force minimum price', 'pewc' ),
				'desc_tip'      => true,
				'description'   => __( 'Enable this to automatically increase the product price to the minimum.', 'pewc' ),
			)
		);
	}

}
add_action( 'woocommerce_product_options_pricing', 'pewc_display_fields' );

// Save the custom fields
function pewc_save_custom_label_fields( $post_id ) {
	$product = wc_get_product( $post_id );
	$pewc_price_label = isset( $_POST['pewc_price_label'] ) ? $_POST['pewc_price_label'] : '';
	$product->update_meta_data( 'pewc_price_label', sanitize_text_field( $pewc_price_label ) );
	$pewc_price_display = isset( $_POST['pewc_price_display'] ) ? $_POST['pewc_price_display'] : '';
	$product->update_meta_data( 'pewc_price_display', sanitize_text_field( $pewc_price_display ) );
	$minimum_price = isset( $_POST['pewc_minimum_price'] ) ? $_POST['pewc_minimum_price'] : '';
	$product->update_meta_data( 'pewc_minimum_price', wc_format_decimal( $minimum_price ) );
	$force_minimum = isset( $_POST['pewc_force_minimum'] ) ? 'yes' : 'no';
	update_post_meta( $post_id, 'pewc_force_minimum', $force_minimum );
	$product->save();
}
add_action( 'woocommerce_process_product_meta', 'pewc_save_custom_label_fields' );

/**
 * Get the default value for our field
 * Used in the back end
 * @since 3.5.0
 */
function pewc_get_field_default( $item ) {

	if( ! empty( $item['field_default'] ) || ( isset( $item['field_default'] ) && $item['field_default'] === '0' ) ) {
		$default = $item['field_default'];
	} else {
		$default = '';
	}

	if( ! empty( $item['field_default_hidden'] ) || ( isset( $item['field_default_hidden'] ) && $item['field_default_hidden'] === '0' ) ) {
		$default = $item['field_default_hidden'];
	} else {
		$default = '';
	}

	return $default;

}

/**
 * Whether to allow variations as child products
 * @since 3.7.10
 */
function pewc_child_products_method( $post_id, $field_id ) {

	$include_variations = get_option( 'pewc_child_variations', 'yes' );
	$method = 'woocommerce_json_search_products';
	if( $include_variations == 'yes' ) {
		$method = 'woocommerce_json_search_products_and_variations';
	}

	return apply_filters( 'pewc_filter_child_products_method', $method, $post_id, $field_id );

}
