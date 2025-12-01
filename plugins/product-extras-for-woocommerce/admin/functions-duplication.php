<?php
/**
 * Functions for duplicating fields
 * @since 3.3.6
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create a duplicate version of the group and its fields then assign to one or more products
 * @param $group_id
 * @param $parent_id
 * @param $duplicate_to	Array
 * @since 3.6.1
 */
function pewc_duplicate_and_assign( $post_id ) {

	$product_id = $_POST['post_id'];
	$assign_to = $_POST['assign_to'];
	$overwrite = ( $_POST['overwrite'] == 'true' ) ? true : false;

	if( get_post_type( $product_id ) != 'product' ) {
		wp_send_json_error( __( 'Not a product', 'pewc' ) );
		exit;
	}

	if( empty( $assign_to ) ) {
		wp_send_json_error( __( 'No assigned products', 'pewc' ) );
		exit;
	}

	// Delete the transient to ensure new groups sync up correctly
	delete_transient( 'pewc_extra_fields_' . $post_id );

	// Get the groups from this original product
	$product = wc_get_product( $product_id );

	$assigned_successfully = array();

	foreach( $assign_to as $duplicate_id ) {

		// Assign the duplicated group to this product
		$duplicate = wc_get_product( $duplicate_id );
		pewc_duplicate_groups_and_fields( $duplicate, $product, $overwrite, true );
		$assigned_successfully[] = $duplicate_id;
		// Delete the transient to ensure new groups sync up correctly
		delete_transient( 'pewc_extra_fields_' . $duplicate_id );

	}

	wp_send_json_success( array( 'assigned_to' => $assigned_successfully ) );
	exit;

}
// add_action( 'save_post', 'pewc_duplicate_and_assign' );
add_action( 'wp_ajax_pewc_duplicate_and_assign', 'pewc_duplicate_and_assign' );

/**
 * Duplicate groups and fields when we duplicate a product
 */
function pewc_product_duplicate( $duplicate, $product ) {

	// Are we duplicating groups and fields, i.e. cloning them and assigning new IDs
	$do_duplication = apply_filters( 'pewc_duplicate_fields', true, false );

	if( $do_duplication ) {
		pewc_duplicate_groups_and_fields( $duplicate, $product, true );
	}

	update_option( 'pewc_duplication_notice', 1 );

}
add_action( 'woocommerce_product_duplicate', 'pewc_product_duplicate', 10, 2 );

function pewc_product_duplication_notice() {
	$is_duplication = get_option( 'pewc_duplication_notice', 0 );
	$dismissed = get_option( 'pewc_duplication_closed' );
	if( ! $is_duplication || $dismissed == 'dismissed' || ! isset( $_GET['post'] ) ) {
		return;
	} ?>
	<div data-option="duplication_closed" class="notice notice-warning is-dismissible pewc-is-dismissible-pewc-notice">
		<?php
		printf(
			'<p>%s: <strong><p><a href="%s">%s</a></p></strong></p>',
			__('If you are duplicating products with add-on fields, please check the following article', 'pewc' ),
			'https://pluginrepublic.com/documentation/duplicate-groups-and-fields-when-duplicating-products/',
			__( 'Important Information when Duplicating Product Add-Ons', 'pewc' )
		); ?>
	</div>
<?php }
// add_action( 'admin_notices', 'pewc_product_duplication_notice' );

/**
 * Duplicate groups and fields from $product and assign them to $duplicate
 * @param	$overwrite		Overwrite existing groups - only used when assigning groups from another product, not when duplicating products
 * @param	$is_assigned	True when assigning groups from another product
 * @since	3.6.1
 * @version	3.22.1
 * 
 * $groups_to_process and $destination_parent added in 3.22.1 for use by the Import/Export functions
 */
function pewc_duplicate_groups_and_fields( $duplicate, $product, $overwrite=true, $is_assigned=false, $groups_to_process=array(), $destination_parent=false ) {

	$map_groups = array();
	$map_fields	= array();
	$map_variations = array();
	$duplicate_fields = array();
	$field_params = pewc_get_field_params();
	$is_import_export = false;

	if ( false !== $destination_parent && ! empty( $groups_to_process ) ) {
		// 3.22.1, we are importing/exporting group(s) to either another product or as global
		$is_import_export = true;
		$duplicate_id = (int) $destination_parent; // this is 0 if global
		if ( $duplicate_id > 0 ) {
			$duplicate = wc_get_product( $duplicate_id );
			if ( ! $duplicate ) {
				pewc_error_log( 'Error in processing: product ID ' . $duplicate_id .' does not exist' );
				return;
			}
		}
	} else {
		$duplicate_id = $duplicate->get_id();
		// Check for any variation specific fields
		if( $duplicate->is_type( 'variable' ) ) {
			$product_children = $product->get_children();
			$duplicate_children = $duplicate->get_children();
			foreach( $product_children as $index=>$variation_id ) {
				$map_variations[$variation_id] = $duplicate_children[$index];
			}
		}
	}

	// Look for a list of groups
	if ( $is_import_export ) {
		$product_group_order = $groups_to_process;
	} else if( ! $is_assigned ) {
		// This is a duplicated product so the group order will already exist
		$product_group_order = pewc_get_group_order( $duplicate_id );
	} else {
		// We are assigning these groups to another product
		$product_group_order = pewc_get_group_order( $product->get_ID() );
	}

	if( ! $is_assigned ) {

		// If we are duplicating a product
		$duplicate_group_order = array();

	} else if( $is_assigned && $overwrite ) {

		// If we are overwriting the original groups, i.e. assigning existing groups to an existing product
		$duplicate_group_order = array();

	} else if ( $duplicate_id === 0 && $is_import_export ) {

		// import/export (global)
		$duplicate_group_order = get_option( 'pewc_global_group_order', '' );
		if ( ! empty( $duplicate_group_order ) ) {
			$duplicate_group_order = explode( ',', $duplicate_group_order );
		} else {
			$duplicate_group_order = array();
		}

	} else {

		// Append duplicated groups to existing groups in an existing product
		// import/export (products) should also arrive here
		$duplicate_group_order = pewc_get_group_order( $duplicate_id );
		if ( ! empty( $duplicate_group_order ) ) {
			$duplicate_group_order = explode( ',', $duplicate_group_order );
		} else {
			$duplicate_group_order = array();
		}

	}

	$current_user_id = get_current_user_id();

	if( $product_group_order ) {

		// 3.22.1, this could be an array already if using import/export
		if ( ! is_array( $product_group_order ) ) {
			$product_group_order = explode( ',', $product_group_order );
		}

		foreach( $product_group_order as $group_id ) {

			// Duplicate the group
			$product_group = get_post( $group_id );

			$args = array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_author'    => $current_user_id,
				'post_content'   => '',
				'post_excerpt'   => '',
				'post_name'      => $product_group->post_name,
				'post_parent'    => $duplicate_id,
				'post_password'  => '',
				'post_status'    => 'publish',
				'post_title'     => $product_group->post_title,
				'post_type'      => 'pewc_group',
				'to_ping'        => '',
				'menu_order'     => $product_group->menu_order
			);

			// This is our new group ID
			$duplicate_group_id = wp_insert_post( $args );

			// Map old group IDs to duplicate group IDs
			$map_groups[$group_id] = $duplicate_group_id;
			$duplicate_group_order[] = $duplicate_group_id;

			// Save the group meta
			$group_title = get_post_meta( $group_id, 'group_title', true );
			$group_description = get_post_meta( $group_id, 'group_description', true );
			$group_layout = get_post_meta( $group_id, 'group_layout', true );
			update_post_meta( $duplicate_group_id, 'group_title', $group_title );
			update_post_meta( $duplicate_group_id, 'group_description', $group_description );
			update_post_meta( $duplicate_group_id, 'group_layout', $group_layout );

			// 3.22.0
			pewc_duplicate_repeatable_postmeta( $group_id, $duplicate_group_id );

			$group_action = get_post_meta( $group_id, 'condition_action', true );
			$group_match = pewc_get_group_condition_match( $group_id );
			$group_conditions = pewc_get_group_conditions( $group_id ); // Group conditions are updated after mapping below
			update_post_meta( $duplicate_group_id, 'condition_action', $group_action );
			update_post_meta( $duplicate_group_id, 'condition_match', $group_match );

			// Find each field in each group, duplicate and assign new IDs
			$product_fields = pewc_get_group_fields( $group_id );
			$duplicate_fields[$duplicate_group_id] = array();

			if( $product_fields ) {

				foreach( $product_fields as $product_field ) {

					$field_id = $product_field['field_id'];

					$args = array(
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_author'    => $current_user_id,
						'post_content'   => '',
						'post_excerpt'   => '',
						'post_name'      => sanitize_title_with_dashes( $product_field['field_label'] ),
						'post_parent'    => $duplicate_group_id,
						'post_status'    => 'publish',
						'post_title'     => $product_field['field_label'],
						'post_type'      => 'pewc_field'
					);

					// This is our new field ID
					$duplicate_field_id = wp_insert_post( $args );

					// Map old field IDs to duplicate field IDs
					$map_fields[$field_id] = $duplicate_field_id;

					$duplicate_fields[$duplicate_group_id][$duplicate_field_id] = array();

					// This method catches products saved pre 3.3.0
					if( $field_params ) {
						foreach( $field_params as $field_param ) {
							if( isset( $product_field[$field_param] ) ) {
								$duplicate_fields[$duplicate_group_id][$duplicate_field_id][$field_param] = $product_field[$field_param];
							}
						}
					}

					// This method catches products saved from 3.3.0
					$product_all_params = get_post_meta( $field_id, 'all_params', true );
					$duplicate_all_params = array();

					if( empty( $product_all_params['field_default'] ) && ! empty( $product_all_params['field_default_hidden'] ) ) {
						$product_all_params['field_default'] = $product_all_params['field_default_hidden'];
					}

					if( $product_all_params ) {
						foreach( $product_all_params as $param=>$param_value ) {
							$duplicate_fields[$duplicate_group_id][$duplicate_field_id][$param] = $param_value;
						}
					}

					$duplicate_fields[$duplicate_group_id][$duplicate_field_id]['field_id'] = $duplicate_field_id;
					$duplicate_fields[$duplicate_group_id][$duplicate_field_id]['id'] = 'pewc_group_' . $duplicate_group_id . '_' . $duplicate_field_id;

				}

			}

			// Update group conditions with new IDs
			// 3.22.1, skip if we're importing/exporting to Global Groups, because group conditions are not available
			if( $group_conditions && ! ( 0 === $duplicate_id && pewc_enable_groups_as_post_types() ) ) {
				foreach( $group_conditions as $group_index=>$group_condition ) {
					if ( empty( $group_condition['field'] ) || 'not-selected' == $group_condition['field'] ) {
						continue; // skip incomplete conditions
					}
					$ids = explode( '_', $group_condition['field'] );
					if ( empty( $ids[2] ) || empty( $ids[3] ) ) {
						continue; // 3.22.1, maybe this is an incorrect condition, or a group condition not accessible to global
					}
					$old_group_id = $ids[2];
					$old_field_id = $ids[3];
					if ( ! isset( $map_groups[$old_group_id] ) || ! isset( $map_fields[$old_field_id] ) ) {
						// it's possible that when importing/exporting, the group this condition is based on was not included, therefore mapping would fail. Remove condition?
						unset( $group_conditions[$group_index] );
						continue;
					}
					$new_group_id = $map_groups[$old_group_id];
					$new_field_id = $map_fields[$old_field_id];
					$group_condition['field'] = 'pewc_group_' . $new_group_id . '_' . $new_field_id;
					$group_conditions[$group_index] = $group_condition;
				}

				// 3.22.1, only save group conditions if it exists
				update_post_meta( $duplicate_group_id, 'conditions', $group_conditions );
			}
			//update_post_meta( $duplicate_group_id, 'conditions', $group_conditions ); // commented out 3.22.1, move it to inside the condition above

			// Now we have to update the group field IDs
			update_post_meta( $duplicate_group_id, 'field_ids', array_keys( $duplicate_fields[$duplicate_group_id] ) );

		}

	}

	// After iterating through all groups and fields, we can replace references to old group and field IDs
	if( $duplicate_fields ) {

		foreach( $duplicate_fields as $group_id=>$fields_by_group ) {

			foreach( $fields_by_group as $duplicate_field_id=>$duplicate_field_params ) {

				if( ! empty( $duplicate_field_params['condition_field'] ) ) {

					foreach( $duplicate_field_params['condition_field'] as $index=>$condition_field ) {

						// Replace old group and field IDs
						$condition_field = explode( '_', $condition_field );
						if( isset( $condition_field[2] ) && ! empty( $map_groups[$condition_field[2]] ) && ! empty( $map_fields[$condition_field[3]] ) ) {
							$condition_field[2] = $map_groups[$condition_field[2]];
							$condition_field[3] = $map_fields[$condition_field[3]];
						}

						$duplicate_field_params['condition_field'][$index] = join( '_', $condition_field );

					}

				}

				if( ! empty( $duplicate_field_params['variation_field'] ) && ! empty( $map_variations ) ) {

					foreach( $duplicate_field_params['variation_field'] as $index=>$variation_id ) {

						// Replace old variation IDs
						$duplicate_variation_id = $map_variations[$variation_id];
						$duplicate_field_params['variation_field'][$index] = $duplicate_variation_id;

					}

				}

				if( ! empty( $duplicate_field_params['formula'] ) ) {

					// Iterate through each {field_xxx} tag
					foreach( $map_fields as $old_field_id=>$new_field_id ) {
						$duplicate_field_params['formula'] = str_replace( '{field_' . $old_field_id, '{field_' . $new_field_id, $duplicate_field_params['formula'] );
					}

				}

				// 3.25.3, allow other plugins to modify their own fields (e.g. Advanced Calculations)
				$duplicate_field_params = apply_filters( 'pewc_filter_duplicate_field_params', $duplicate_field_params, $duplicate_field_id, $group_id, $product, $map_fields );

				foreach( $duplicate_field_params as $duplicate_field_param=>$value ) {
					update_post_meta( $duplicate_field_id, $duplicate_field_param, $value );
				}

			}

		}

	}

	// Now we have to update the group field IDs
	if ( $duplicate_id > 0 ) {
		update_post_meta( $duplicate_id, 'group_order', join( ',', $duplicate_group_order ) );
	} else if ( $duplicate_id === 0 && $is_import_export ) {
		// 3.22.1, update global group order
		update_option( 'pewc_global_group_order', join( ',', $duplicate_group_order ) );
	}

}
