<?php
/**
 * Functions for exporting Product Add-Ons
 * @since 1.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter post class
 * @since 1.4.0
 */
function pewc_filter_post_classes( $classes ) {
	global $post;
	if( is_single() && 'product' == get_post_type( $post->ID ) && pewc_has_extra_fields( $post->ID ) ) {
		$classes[] = 'has-extra-fields';
		if( pewc_has_flat_rate_field( $post->ID ) ) {
			$classes[] = 'has-flat-rate';
		}
	}
	return $classes;
}
add_filter( 'post_class', 'pewc_filter_post_classes' );

/**
 * Filter body class
 * @since 1.7.0
 */
function pewc_filter_body_classes( $classes ) {
	global $post;
	if( isset( $post->ID ) && 'product' == get_post_type( $post->ID ) && pewc_has_extra_fields( $post->ID ) ) {
		$product = wc_get_product( $post->ID );
		$classes[] = 'pewc-has-extra-fields';
		if( $product->get_type() == 'variable' ) {
			$classes[] = 'pewc-variable-product';
		}
		if( pewc_has_flat_rate_field( $post->ID ) ) {
			$classes[] = 'has-flat-rate';
		}
	}
	if( ! empty( get_theme_mod( 'pewc_show_inputs', 0 ) ) ) {
		$classes[] = 'pewc-show-inputs';
	}
	if( pewc_get_swatch_grid() ) {
		$classes[] = 'pewc-swatch-grid';
	}
	if( pewc_disable_hidden_fields() == 'yes' ) {
		$classes[] = 'pewc-disable-hidden-fields';
	}
	$classes[] = 'pewc-quantity-layout-' . pewc_get_quantity_layout();
	return $classes;
}
add_filter( 'body_class', 'pewc_filter_body_classes' );

/**
 * Get the product's extra fields
 * @since 1.6.0
 * @param $post_id
 * @return Array
 */
function pewc_get_extra_fields( $post_id ) {

	$group_transient = pewc_get_transient( 'pewc_extra_fields_' . $post_id );

	// An empty array means the product has no fields.
	// We don't need to check it again
	if( is_array( $group_transient ) ) {

		$product_extra_groups = $group_transient;

	} else {

		$has_migrated = pewc_has_migrated();

		if( ! $has_migrated ) {
			// This is the old, pre-3.0.0 method and will be deprecated in future versions
			$product_extra_groups = get_post_meta( $post_id, '_product_extra_groups', true );

		} else {
			// This is the post-3.0.0 method using post types
			// However, it still returns a big groups array like the old method for backwards compatibility
			$product_extra_groups = pewc_get_pewc_groups( $post_id );
		}

		pewc_set_transient( 'pewc_extra_fields_' . $post_id, $product_extra_groups );

	}

	// Filter the groups
	// Only filter on the front end, since 3.7.24
	if( ! is_admin() || wp_doing_ajax() ) {
		$product_extra_groups = apply_filters( 'pewc_filter_product_extra_groups', $product_extra_groups, $post_id );
	}

	return $product_extra_groups;

}

/**
 * Get child groups for this product
 * @since 3.0.0
 * @return Array
 */
function pewc_get_pewc_groups( $post_id ) {

	$groups = array();
	$group_ids = pewc_get_group_order( $post_id );
	// Iterate through the group IDs and build a big array
	if( $group_ids ) {
		$group_ids = explode( ',', $group_ids );
		foreach( $group_ids as $index=>$group_id ) {
			// Confirm that the group ID is an actual group
			if( 'publish' === get_post_status( $group_id ) ) {
				$groups[$group_id]['items'] = pewc_get_group_fields( $group_id );
			}
		}
	}

	return $groups;

}


/**
 * Get global a correctly formatted global group
 * Post 3.0.0 this is passed a group ID
 * @since 3.0.0
 * @param $group_param Mixed Either integer or array
 * @return Array
 */
function pewc_get_global_groups( $group_param ) {
	$has_migrated = pewc_has_migrated();
	if( ! $has_migrated ) {
		// This is the old, pre-3.0.0 method and will be deprecated in future versions
		return $group_param;
	} else {
		// This is the post-3.0.0 method using post types
		// We want it to return a big groups array like the old method for backwards compatibility
		$group['items'] = pewc_get_group_fields( $group_param );
		return $group;
	}
}

/**
 * Get the list of all global group IDs
 * @since 3.3.0
 * @return List
 */
function pewc_get_all_global_group_ids() {

	if( pewc_is_group_public() != 'yes' ) {

		$global_order = get_option( 'pewc_global_group_order' );

	} else {

		$global_order = pewc_get_transient( 'pewc_global_order' );

		if( ! $global_order ) {

			// Get all groups with no parent
			$args = array(
				'post_type'				=> 'pewc_group',
				'post_parent'			=> 0,
				'fields'				=> 'ids',
				'posts_per_page'		=> 999,
				'orderby'				=> 'menu_order',
				'order'					=> 'ASC'
			);
			$groups = new WP_Query( $args );
			$global_order = join( ',', $groups->posts );

			//
			pewc_set_transient( 'pewc_global_order', $global_order );

		}

	}

	return $global_order;

}

/**
 * Get the list of all global group IDs
 * @since	3.3.0
 * @version	3.19.1
 * @return List
 */
function pewc_get_global_groups_list() {

	// Get all groups with no parent
	$args = array(
		'post_type'				=> 'pewc_group',
		'post_parent'			=> 0,
		'posts_per_page'		=> 999,
		// 'fields'				=> 'ids',
		'orderby'				=> 'menu_order',
		'order'					=> 'ASC'
	);
	// 3.19.1, commented out the code block below because WP_Query overrides the global $post variable, causing an error with another plugin
	/*
	$groups = new WP_Query( $args );
	$group_list = array();
	if( $groups->have_posts() ) {
		while ( $groups->have_posts() ) {
			$groups->the_post();
			$group_id = get_the_ID();
			$group = get_post( $group_id );
			$group_list[$group_id] = sprintf(
				'#%s: %s (%s)',
				$group_id,
				get_the_title(),
				$group->post_title
			);
		}
	}
	*/
	// new code since 3.19.1
	$group_list = array();
	$groups = get_posts( $args );
	if ( ! empty( $groups ) ) {
		foreach ( $groups as $group ) {
			$group_id = $group->ID;
			$group_list[ $group_id ] = sprintf(
				'#%s: %s (%s)',
				$group_id,
				get_the_title( $group ),
				$group->post_title
			);
		}
	}

	return $group_list;

}

/**
 * Set the list of all global group IDs
 * @since 3.5.0
 */
function pewc_set_global_group_ids( $group_id ) {

	// Get all groups with no parent
	$args = array(
		'post_type'				=> 'pewc_group',
		'post_parent'			=> 0,
		'fields'					=> 'ids',
		'posts_per_page'	=> 999,
		'orderby'					=> 'menu_order',
		'order'						=> 'ASC'
	);

	// For WPML
	if( function_exists( 'icl_object_id' ) ) {
		$args['suppress_filters'] = true;
	}
	// for Polylang
	if ( function_exists( 'pll_get_post_translations' ) ) {
		$args['lang'] = '';
	}

	$groups = new WP_Query( $args );
	$global_order = join( ',', $groups->posts );

	update_option( 'pewc_global_group_order', $global_order );

}
add_action( 'pewc_after_save_group_metabox_data', 'pewc_set_global_group_ids' );

/**
 * Get the display order of groups for this product
 * @since 3.0.0
 * @return String
 */
function pewc_get_group_order( $product_id ) {
	$order = get_post_meta( $product_id, 'group_order', true );
	return $order;
}

/**
 * Get child fields for this group
 * @since 3.0.0
 * @return Array
 */
function pewc_get_group_fields( $group_id ) {
	$all_fields = array();
	$fields = get_post_meta( $group_id, 'field_ids', true );
	if( $fields ) {
		foreach( $fields as $field_id ) {
			// Confirm that the field ID is an actual field
			if( 'publish' === get_post_status( $field_id ) ) {
				$all_fields[$field_id] = pewc_create_item_object( $field_id );
			}
		}
	}
	return $all_fields;
}

/**
 * Before 3.0.0, field data was stored as a serialised array
 * This function just gets our post meta and formats it in an array so we can continue using pre-3.0 templates
 * @since	3.0.0
 * @version	3.15.0
 * @return Array
 */
function pewc_create_item_object( $field_id ) {

	$item = pewc_get_transient( 'pewc_item_object_' . $field_id );

	if( ! $item ) {

		$item = array(
			'field_id' 	=> $field_id
		);
		$params = pewc_get_field_params( $field_id );

		$all_params = get_post_meta( $field_id, 'all_params', true );

		// before 3.15.0
		/*if( ! empty( $all_params ) && pewc_enable_groups_as_post_types() ) {
			$item = $all_params; // before 3.15.0, this could cause undefined keys PHP warnings
		} else {
			if( $params ) {
				foreach( $params as $param ) {
					$value = get_post_meta( $field_id, $param, true );
					$item[$param] = $value;
				}
			}
		}*/

		// Since 3.15.0. This hopes to avoid "undefined keys" for fields created using an older version where a newer key did not exist yet
		// $params retrieves the key list from pewc_get_field_params() which is always updated when we add new field settings/keys
		if ( $params ) {
			foreach ( $params as $param ) {
				if ( ! empty( $all_params ) && pewc_enable_groups_as_post_types() ) {
					$item[$param] = $all_params[$param] ?? '';
				} else {
					$value = get_post_meta( $field_id, $param, true );
					$item[$param] = $value;
				}
			}
		}

		if( ! empty( $item ) ) {
			$item = apply_filters( 'pewc_before_update_field_all_params', $item, $field_id );
		}
		pewc_set_transient( 'pewc_item_object_' . $field_id, $item );

	}

	$item = apply_filters( 'pewc_item_object', $item, $field_id );

	return $item;

}

/**
 * Returns a list of all params for a field
 * @since 3.0.0
 * @return Array
 */
function pewc_get_field_params( $field_id=null ) {

	$params = array(
		'id',
		'group_id',
		'field_label',
		'field_type',
		'field_price',
		'field_options',
		'first_field_empty',
		'field_minchecks',
		'field_maxchecks',
		'child_products',
		'child_categories',
		'products_layout',
		'products_quantities',
		'allow_none',
		'number_columns',
		'hide_labels',
		'allow_multiple',
		'select_placeholder',
		'min_products',
		'max_products',
		'default_quantity',
		'force_quantity',
		'allow_multiple_components',
		'child_discount',
		'discount_type',
		'field_required',
		'field_flatrate',
		'field_display_as_swatch',
		'field_percentage',
		'field_minchars',
		'field_maxchars',
		'per_character',
		'show_char_counter',
		'field_freechars',
		'field_alphanumeric',
		'field_alphanumeric_charge',
		'field_minval',
		'field_maxval',
		'multiply',
		'min_date_today',
		'field_mindate',
		'field_maxdate',
		'field_maxdate_ymd',
		'offset_days',
		'weekdays',
		'blocked_dates',
		'field_color',
		'field_width',
		'field_show',
		'field_palettes',
		'field_default',
		'field_default_hidden',
		'field_image',
		'field_description',
		'condition_action',
		'condition_match',
		'condition_field',
		'condition_rule',
		'condition_value',
		'variation_field',
		'formula',
		'formula_action',
		'formula_round',
		'decimal_places',
		'field_rows',
		'multiple_uploads',
		'max_files',
		'multiply_price',
		'hidden_calculation',
		'field_visibility',
		'price_visibility',
		'option_price_visibility',
		'products_field_id',
		'child_qty_product_id',
		'reverse_formula_field',
		'reverse_input_field',
		'quantity_override',
		'replace_main_image',
		'layered_images',
		'parent_swatch_id',
		'field_swatchwidth',
		'field_step',
		'field_enable_range_slider'
	);

	return apply_filters( 'pewc_item_params', $params, $field_id );

}

/**
 * Returns the group title
 * @since 3.0.0
 * @return Array
 */
function pewc_get_group_title( $group_id, $group, $has_migrated ) {

	$group_title = '';

	if( $has_migrated ) {
		$group_title = get_post_meta( $group_id, 'group_title', true );
	} else if( isset( $group['meta']['group_title'] ) ) {
		$group_title = $group['meta']['group_title'];
	}

	return apply_filters( 'pewc_get_group_title', $group_title, $group_id, $has_migrated );

}

/**
 * Returns the group title
 * @since 3.0.0
 * @return Array
 */
function pewc_get_group_description( $group_id, $group, $has_migrated ) {
	$group_description = '';
	if( $has_migrated ) {
		$group_description = get_post_meta( $group_id, 'group_description', true );
	} else if( isset( $group['meta']['group_description'] ) ) {
		$group_description = $group['meta']['group_description'];
	}

	return apply_filters( 'pewc_get_group_description', $group_description, $group_id, $has_migrated );
}

/**
 * Returns the group layout
 * @since 3.1.1
 * @return Array
 */
function pewc_get_group_layout( $group_id ) {
	$group_layout = get_post_meta( $group_id, 'group_layout', true );
	if( ! $group_layout ) $group_layout = 'ul';
	return apply_filters( 'pewc_get_group_layout', $group_layout, $group_id );
}

/**
 * Returns the group class
 * @since 3.19.1
 * @return Array
 */
function pewc_get_group_class( $group_id ) {
	$group_class = get_post_meta( $group_id, 'group_class', true );
	return $group_class;
}

/**
 * Returns whether this group data is always included in the order
 * @since 3.20.0
 * @return Array
 */
function pewc_get_group_include_in_order( $group_id ) {
	$always_include = get_post_meta( $group_id, 'always_include', true );
	return ! empty( $always_include ) ? true : false;
}

/**
 * Returns the group condition action
 * @since 3.8.0
 * @return Array
 */
function pewc_get_group_condition_action( $group_id, $group ) {

	$group_action = get_post_meta( $group_id, 'condition_action', true );
	return $group_action;

}

/**
 * Returns the group condition match
 * @since 3.8.0
 * @return Array
 */
function pewc_get_group_condition_match( $group_id ) {

	$condition_match = get_post_meta( $group_id, 'condition_match', true );
	return $condition_match;

}

/**
 * Returns the group conditions
 * @since 3.8.0
 * @return Array
 */
function pewc_get_group_conditions( $group_id ) {

	$conditions = get_post_meta( $group_id, 'conditions', true );
	return $conditions;

}

/**
 * Get all the fields on the page which will trigger a group condition
 * Maps field IDs to group
 * @since 3.8.0
 * @return Array
 */
function pewc_get_all_group_conditions_fields( $group_ids ) {

	$conditions_fields = array();
	if( $group_ids ) {
		foreach( $group_ids as $group_id ) {
			$conditions = pewc_get_group_conditions( $group_id );
			if( $conditions ) {
				foreach( $conditions as $condition ) {
					if( ! isset( $conditions_fields[$condition['field']] ) ) {
						$conditions_fields[$condition['field']] = array( $group_id );
					} else {
						$conditions_fields[$condition['field']][] = $group_id;
					}
				}
			}
		}
	}

	return $conditions_fields;

}

/**
 * Get all the fields on the page which a field is conditional on
 * @since 3.9.0
 * @return Array
 */
function pewc_get_all_field_conditions_fields( $groups, $product_id ) {

	$conditions_fields = array();

	if( $groups ) {
		foreach( $groups as $group_id=>$group ) {
			if( isset( $group['items'] ) ) {
				foreach( $group['items'] as $field_id=>$field ) {

					$conditions = get_post_meta( $field_id, 'condition_field', true );
					$field_ids = array();
					if( $conditions ) {
						foreach( $conditions as $condition ) {
							// Get the field ID
							$condition = explode( '_', $condition );
							if( isset( $condition[3] ) ) {
								$field_ids[] = $condition[3];
							}
						}
						$conditions_fields[$field_id] = $field_ids;
					}

				}
			}
		}
	}

	return $conditions_fields;

}

/**
 * Get an array of each field's conditions that we can use on the front end
 * @return Array
 * @since 3.9.0
 */
function pewc_get_conditions_by_field_id( $groups, $product_id ) {

	$field_conditions = array();

	if( $groups ) {
		foreach( $groups as $group_id=>$group ) {
			if( isset( $group['items'] ) ) {
				foreach( $group['items'] as $field_id=>$field ) {
					$conditions = pewc_get_field_conditions( $field, $product_id );
					if( $conditions ) {
						// Get the field type for each condition
						foreach( $conditions as $condition_id=>$condition ) {
							$condition_field = explode( '_', $condition['field'] );
							$condition_field_id = isset( $condition_field[3] ) ? $condition_field[3] : false;
							if( $condition_field_id ) {
								$conditions[$condition_id]['field_type'] = get_post_meta( $condition_field[3], 'field_type', true );
							} else {
								$conditions[$condition_id]['field_type'] = $condition['field'];
							}

						}

						$field_conditions[$field_id] = $conditions;

					}
				}
			}
		}
	}

	return apply_filters( 'pewc_all_conditions_by_field_id', $field_conditions, $groups, $product_id );

}

/**
 * Get fields which fields are conditional on
 * If field 1234 has a condition to display if field 4567 is checked, then 4567 is a trigger for 1234
 * @return Array
 * @since 3.9.0
 */
function pewc_get_all_conditional_triggers( $all_field_conditions, $post_id ) {

	$triggers = array();
	if( $all_field_conditions ) {
		foreach( $all_field_conditions as $field_id=>$field_triggers ) {
			$triggers = array_merge( $triggers, array_values( $field_triggers ) );
		}
	}

	return $triggers;

}

/**
 * Get a list of field IDs that each field is a trigger for
 * If field 1234 is a trigger for field 4567, then add 4567 to $triggers_for[1234]
 * @return Array
 * @since 3.9.0
 */
function pewc_get_triggers_for_fields( $all_field_conditions, $post_id ) {

	$triggers_for = array();
	if( $all_field_conditions ) {
		foreach( $all_field_conditions as $field_id=>$field_triggers ) {
			if( isset( $field_triggers ) ) {
				foreach( $field_triggers as $trigger_id=>$field_trigger ) {
					if( isset( $triggers_for[$field_trigger] ) ) {
						$triggers_for[$field_trigger][] = $field_id;
					} else {
						$triggers_for[$field_trigger] = array( $field_id );
					}
				}
			}

		}
	}

	return $triggers_for;

}

/**
 * Get fields triggered by cost and quantity conditions
 * @return Array
 * @since 3.9.0
 */
function pewc_get_triggered_by_field_type( $field_conditions, $type ) {

	$triggered_by = array();
	if( $field_conditions ) {
		foreach( $field_conditions as $field_id=>$field_triggers ) {
			if( $field_triggers ) {
				foreach( $field_triggers as $trigger_id=>$trigger ) {
					if( isset( $trigger['field_type'] ) && $trigger['field_type'] == $type ) {
						$triggered_by[] = $field_id;
					}
				}
			}
		}
	}

	return $triggered_by;

}

/**
 * Get all the fields on the page which are a component of a calculation field
 * @since 3.8.0
 * @return Array
 */
function pewc_get_all_calculation_components( $groups ) {
	$components = array();
	if( $groups ) {
		foreach( $groups as $group_id=>$group ) {
			if( $group['items'] ) {
				foreach( $group['items'] as $field_id=>$field ) {
					if( isset( $field['field_type'] ) && $field['field_type'] == 'calculation' ) {
						$formula = isset( $field['formula'] ) ? $field['formula'] : false;
						$formula = str_replace( '_field_price', '', $formula );

						if( $formula ) {

							if( $formula == '{look_up_table}' ) {

								// Find the elements for the look up table
								$lookup_fields = apply_filters( 'pewc_calculation_look_up_fields', array() );

								if( isset( $lookup_fields[$field_id][1] ) ) {
									$component_id = $lookup_fields[$field_id][1];
									if( isset( $components[$field_id] ) ) {
										$components[$component_id][] = $field_id;
									} else {
										$components[$component_id] = array( $field_id );
									}
								}
								if( isset( $lookup_fields[$field_id][2] ) ) {
									$component_id = $lookup_fields[$field_id][2];
									if( isset( $components[$field_id] ) ) {
										$components[$component_id][] = $field_id;
									} else {
										$components[$component_id] = array( $field_id );
									}
								}

							} else {

								// Component field ID => Calculation field ID
								$last_pos = 0;
								$opening_pos = 0;
								$positions = array();

								while( ( $last_pos = strpos( $formula, 'field_', $last_pos ) ) !== false ) {
							    $positions[] = $last_pos;
									$closing_pos = strpos( $formula, '}', $last_pos );
									$component_id = substr( $formula, $last_pos, $closing_pos-$last_pos );
									$component_id = str_replace( array( 'field_', '_option_price', '_field_price' ), '', $component_id );

									// $components works like this:
									// $component_id is the input field => $field_id is the field containing the calculation

									if( isset( $components[$field_id] ) ) {
										$components[$component_id][] = $field_id;
									} else {
										$components[$component_id] = array( $field_id );
									}
									$last_pos = $last_pos + strlen( 'field_' );
								}

							}

						}

					}
				}
			}
		}
	}
	return $components;
}

/**
 * Get all the fields on the page which are set by child product quantity in a calculation
 * @since 3.12.4
 * @return Array
 */
function pewc_get_all_child_qty_dependents( $groups ) {
	$dependents = array();
	if( $groups ) {
		foreach( $groups as $group_id=>$group ) {
			if( $group['items'] ) {
				foreach( $group['items'] as $field_id=>$field ) {
					if( isset( $field['field_type'] ) && $field['field_type'] == 'calculation' ) {

						$action = isset( $field['formula_action'] ) ? $field['formula_action'] : false;
						$products_field_id = isset( $field['products_field_id'] ) ? $field['products_field_id'] : false;

						if( $action == 'child-qty' && $products_field_id ) {

							// $field_id is the ID of the calculation field that is setting the child product quantity
							// $products_field_id is the ID of the products field (radio, independent quantities only) whose quantity is being set by the calculation
							$dependents[$field_id] = $products_field_id;

						}

					}
				}
			}
		}
	}
	return $dependents;
}

/**
 * Returns the field condition action
 * @since 3.9.0
 * @return Array
 */
function pewc_get_field_condition_action( $field_id, $field ) {

	$field_action = get_post_meta( $field_id, 'condition_action', true );
	return $field_action;

}

/**
 * Returns the field condition match
 * @since 3.9.0
 * @return Array
 */
function pewc_get_field_condition_match( $field_id ) {

	$field_match = get_post_meta( $field_id, 'condition_match', true );
	return $field_match;

}

/**
 * Returns the global group rules
 * @since 3.0.0
 * @return Array
 */
function pewc_get_global_rules( $group_id, $group ) {
	$has_migrated = pewc_has_migrated();
	if( $has_migrated ) {
		$rules = get_post_meta( $group_id, 'global_rules', true );
	} else {
		$rules = isset( $group['global_rules'] ) ? $group['global_rules'] : false;
	}
	return $rules;
}

/**
 * Returns the global group operator
 * @since 3.0.0
 * @return Array
 */
function pewc_get_group_operator( $group_id, $group ) {
	$rules = pewc_get_global_rules( $group_id, $group );
	$operator = ( isset( $rules['operator'] ) && $rules['operator'] == 'any' ) ? 'any' : 'all';
	return $operator;
}

/**
 * Check if this product has extra fields
 * @since 1.4.0
 * @return Boolean
 */
function pewc_has_extra_fields( $product_id ) {
	return pewc_has_product_extra_groups( $product_id ) == 'yes' ? true : false;
	// $has_extra = get_post_meta( $product_id, 'has_addons', true );
	// return $has_extra;
}

/**
 * Check if this cart item has extra fields
 * @since 3.7.10
 * @return Boolean
 */
function pewc_cart_item_has_extra_fields( $cart_item ) {
	$has_extra = ! empty( $cart_item['product_extras']['groups'] ) ? true : false;
	return $has_extra;
}

function pewc_get_group_id( $id ) {
	// Work out group and field IDs from the $id
	$last_index = strrpos( $id, '_' );
	$field_id = substr( $id, $last_index + 1 ); // Find last instance of _
	$group_id = substr( $id, 0, $last_index ); // Remove _field_id from $id
	//$field_id = str_replace( '_', '', $field_id );
	$group_id = strrchr( $group_id, '_' );
	$group_id = str_replace( '_', '', $group_id );
	return $group_id;
}

function pewc_get_field_id( $id ) {
	// Work out group and field IDs from the $id
	$last_index = strrpos( $id, '_' );
	$field_id = substr( $id, $last_index + 1 ); // Find last instance of _
	return $field_id;
}

function pewc_get_field_type( $id, $items ) {
	if( $items ) {
		foreach( $items as $item ) {
			if( $item['id'] == $id ) {
				$field_type = $item['field_type'];
				return $field_type;
			}
		}
	}
	return '';
}

/**
 * Can we edit global groups as post types?
 * Disables the pre-3.2.20 Global Add-Ons page
 */
 function pewc_enable_groups_as_post_types() {
	 $display = get_option( 'pewc_enable_groups_as_post_types', 'yes' );
	 return apply_filters( 'pewc_enable_groups_as_post_types', $display=='yes' );
 }

 /**
  * Can we edit fields as post types?
  * Disables the pre-3.2.20 Global Add-Ons page
	* @since 3.6.0
  */
  function pewc_enable_fields_as_post_types() {
 	 return apply_filters( 'pewc_enable_fields_as_post_types', false );
  }

/**
 * Abbreviated form of wc_price
 * @param $price	Price
 * @param $args		Args
 * @return HTML
 */
function pewc_wc_format_price( $price, $args=array() ) {
	extract( apply_filters( 'wc_price_args', wp_parse_args( $args, array(
		'ex_tax_label'       => false,
		'currency'           => '',
		'decimal_separator'  => wc_get_price_decimal_separator(),
		'thousand_separator' => wc_get_price_thousand_separator(),
		'decimals'           => wc_get_price_decimals(),
		'price_format'       => get_woocommerce_price_format(),
	) ) ) );
	$negative = $price < 0;
	$price = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
	$price = apply_filters( 'formatted_woocommerce_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );
	if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $decimals > 0 ) {
		$price = wc_trim_zeros( $price );
	}
	$formatted_price = ( $negative ? '-' : '' ) . sprintf( $price_format, '<span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol( $currency ) . '</span>', $price );
	return $formatted_price;
}

/**
 * Check if this product has a flat rate field
 * @since 1.4.0
 * @return Boolean
 */
function pewc_has_flat_rate_field( $product_id ) {
	$product_extra_groups = pewc_get_extra_fields( $product_id );
	if( ! empty( $product_extra_groups ) ) {
		foreach( $product_extra_groups as $group ) {
			if( ! empty( $group['items'] ) ) {
				foreach( $group['items'] as $key=>$item ) {
					if( ! empty( $item['field_flatrate'] ) ) {
						return true;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Return attributes for text or textarea field
 * @since 2.1.0
 * @return Array
 */
function pewc_get_text_field_attributes( $item ) {
	$attributes = array(
		'data-minchars'				=> ! empty( $item['field_minchars'] ) ? $item['field_minchars'] : '',
		'data-maxchars'				=> ! empty( $item['field_maxchars'] ) ? $item['field_maxchars'] : '',
		'data-freechars'			=> '0',
		'data-alphanumeric'			=> '',
		'data-alphanumeric-charge'	=> '',
		'aria-label'				=> strip_tags( $item['field_label'] ), // 3.26.5
	);
	if( pewc_is_pro() ) {
		$attributes['data-freechars'] = ! empty( $item['field_freechars'] ) ? $item['field_freechars'] : '';
		$attributes['data-alphanumeric'] = ! empty( $item['field_alphanumeric'] ) ? $item['field_alphanumeric'] : '';
		$attributes['data-alphanumeric-charge'] = ! empty( $item['field_alphanumeric_charge'] ) ? $item['field_alphanumeric_charge'] : '';
	}
	$attributes = apply_filters( 'pewc_filter_text_field_attributes', $attributes, $item );
	$return = '';
	if( $attributes ) {
		foreach( $attributes as $attribute=>$value ) {
			$return .= $attribute . '="' . $value . '" ';
		}
	}

	return $return;
}

/**
 * Return attributes for color picker field.
 *
 * @since   3.7.7
 * @version 3.7.7
 *
 * @param   array   $item
 *
 * @return  string
 */
function pewc_get_color_field_attributes( $item ) {
    $return = '';

    $attributes = array(
        'data-color'        => ! empty( $item['field_color'] ) ? $item['field_color'] : '',
        'data-box-width'    => ! empty( $item['field_width'] ) ? $item['field_width'] : '',
        'data-show'         => ! empty( $item['field_show']) ? 'true' : 'false',
        'data-palettes'     => ! empty( $item['field_palettes']) ? 'true' : 'false'
    );

    $attributes = apply_filters( 'pewc_filter_color_picker_field_attributes', $attributes, $item );
    if( $attributes ) {
        foreach( $attributes as $attribute => $value ) {
            $return .= $attribute . '="' . $value . '" ';
        }
    }

    return $return;
}

/**
 * Get a formatted price but without any HTML
 */
function pewc_get_semi_formatted_price( $child_product ) {
	$price = $child_product->get_price();
	$semi_formatted_price = $price;
	$negative = $price < 0;
	$price = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
	$price = apply_filters( 'formatted_woocommerce_price', number_format( $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ), $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
	if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && wc_get_price_decimals() > 0 ) {
		$price = wc_trim_zeros( $price );
	}
	$semi_formatted_price = ( $negative ? '-' : '' ) . sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $price );
	return $semi_formatted_price;
}

/**
 * Get a formatted price without any HTML for a price string
 */
function pewc_get_semi_formatted_raw_price( $price ) {
	$semi_formatted_price = $price;
	$negative = $price < 0;
	$price = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
	$price = apply_filters( 'formatted_woocommerce_price', number_format( $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ), $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
	if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && wc_get_price_decimals() > 0 ) {
		$price = wc_trim_zeros( $price );
	}
	$semi_formatted_price = ( $negative ? '-' : '' ) . sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $price );
	return $semi_formatted_price;
}

/**
 * Get all simple products
 */
function pewc_get_simple_products() {
	$args = array(
		'type'		=> 'simple',
		'return'	=> 'ids',
		'limit'		=> apply_filters( 'pewc_simple_products_limit', 10 )
	);
	$products = wc_get_products( $args );
	return $products;
}

/**
 * Get all product categories
 *
 * @return array $product_categories
 */
function pewc_get_product_categories() {

	$args = array(
		'hide_empty' => true,
		'number' => 999,
		'fields' => 'ids',
		'update_term_meta_cache' => false
	);

	$product_categories = apply_filters(
		'woocommerce_product_categories',
		get_terms( 'product_cat', $args )
	);

	return $product_categories;
}

/**
 * Get all products for a product category - with a cap for performance
 *
 * Since 3.9.7
 * @param array $categories
 * @return array $product_ids
 */
function pewc_get_products_for_cats( $categories ) {

	$args = array(
		'status'		=> 'publish',
		'type'			=> ['simple','variable'],
		'orderby' 		=> 'menu_order',
		'order' 		=> 'ASC',
		'limit'			=> apply_filters( 'pewc_products_for_cats_limit', 99 ),
		'exclude' 		=> [ get_queried_object_id() ],
		'category' 		=> $categories,
		'stock_status' 	=> apply_filters( 'pewc_products_for_cats_stock', 'instock' ),
		'return'		=> 'ids'
	);

	// 3.26.13, added filter
	$args = apply_filters( 'pewc_get_products_for_cats_args', $args );

	$products = wc_get_products( $args );

	if( ! empty( $products ) ){

		return $products;
	}

	return false;
}


/**
 * Build the cache for Product Categories add ons
 *
 * Since 3.9.7
 * @param int $field_id
 * @param array $categories
 * @return array | bool $product_ids
 */
function pewc_get_product_categories_addon_products( $field_id, $categories ){

	if( !$field_id ){
		return false;
	}

	$cached_products = pewc_get_transient( 'pewc_categories_field_products_' . $field_id );
	if( $cached_products ){
		return $cached_products;
	}

	if( !is_array( $categories ) || empty( $categories )){
		return false;
	}

	$child_products = pewc_get_products_for_cats( $categories );

	if( !$child_products ){
		return false;
	}

	pewc_set_transient( 'pewc_categories_field_products_' . $field_id, $child_products );

	return $child_products;
}

/**
 * Check whether we're displaying prices with tax or not
 */
function pewc_maybe_include_tax( $product, $price, $cart_price = false ) {

	// global $product;
	$ignore = get_option( 'pewc_ignore_tax', 'no' );
	if ( $price === '' || $price == '0' || $ignore == 'yes' ) {
		return $price;
	}

	$is_negative = ( $price < 0 ) ? true : false;
	if( $is_negative ) {
		// 3.19.2, allow negative prices to be taxed. Disabled by default because WC doesn't tax negative prices, i.e. wc_get_price_including_tax() returns 0 on negative prices
		if ( apply_filters('pewc_allow_tax_on_negative_prices', false ) ) {
			$price = abs( $price );
		} else {
			return $price;
		}
	}

	if( is_object( $product ) ) {
		if ($cart_price) {
			// this price is to be used on the cart page
			$tax_display_mode = get_option( 'woocommerce_tax_display_cart' );
		}
		else {
			// this price is to be used on the product page
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		}
		$display_price = $tax_display_mode == 'incl' ? wc_get_price_including_tax( $product, array( 'price' => $price, 'qty' => 1 ) ) : wc_get_price_excluding_tax( $product, array( 'price' => $price, 'qty' => 1 ) );
	} else {
		$display_price = $price;
	}

	if ( $is_negative ) {
		// 3.19.2, return to its negative format. We only arrive here if filter pewc_allow_tax_on_negative_prices is true
		$display_price = 0 - $display_price;
	}

	return $display_price;

}

/**
 * We might need to remove tax from the add-ons so that tax isn't doubled in the cart
 */
function pewc_get_price_without_tax( $price, $product ) {

	// global $product;
	$ignore = get_option( 'pewc_ignore_tax', 'no' );
	if ( $price === '' || $price == '0' || $ignore == 'yes' ) {
		// No tax has been added here
		return $price;
	}

	$is_negative = ( $price < 0 ) ? true : false;
	if( $is_negative ) {
		if ( apply_filters('pewc_allow_tax_on_negative_prices', false ) ) {
			// 3.19.2, allow tax on negative prices
			$price = abs( $price );
		} else {
			return $price;
		}
	}

	// Taken from wc_get_price_excluding_tax
	$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
	$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
	$remove_taxes   = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $price, $base_tax_rates, true ) : WC_Tax::calc_tax( $price, $tax_rates, true );
	$return_price   = $price - array_sum( $remove_taxes );

	if ( $is_negative ) {
		// 3.19.2, negate the value
		$return_price = 0 - $return_price;
	}

	return $return_price;

}

/**
 * Do we need to remove the tax from the add-on field price?
 */
function pewc_adjust_tax() {

	$adjust = false;

	// Ensure we don't modify the price if taxes are not enabled
	if( get_option( 'woocommerce_calc_taxes' ) == 'no' ) {
		return $adjust;
	}

	$tax_display = get_option( 'woocommerce_tax_display_cart' ); // original
	//$tax_display = get_option( 'woocommerce_tax_display_shop' ); // use this so that add-on prices are stripped of taxes before getting added to the cart?

	if( ( ! wc_prices_include_tax() && $tax_display == 'incl' ) ) {

		// We need to remove tax if prices are entered exclusive of tax but prices are displayed including tax
		$adjust = 'remove';

	} else if( wc_prices_include_tax() && $tax_display == 'excl' ) {

		// We need to add tax if prices are entered including tax but prices are displayed without tax
		$adjust = 'add';

	}

	return apply_filters( 'pewc_adjust_tax', $adjust );

}

/**
 * Get the adjusted add-on field price in the cart
 */
function pewc_get_adjusted_product_addon_price( $cart_item, $cart_key ) {

	$new_price = apply_filters( 'pewc_filter_calculated_cost_before_calculate_totals', $cart_item['product_extras']['price_with_extras'], $cart_item, $cart_key );

	if( apply_filters( 'pewc_ignore_tax_adjustments', false, $cart_item ) || ! wc_tax_enabled() ) {
		return $new_price;
	}

	// Do we need the price without tax?
	$adjust_tax = pewc_adjust_tax();

	$original_price = isset( $cart_item['product_extras']['original_price'] ) ? $cart_item['product_extras']['original_price'] : $new_price;
	$original_extras = $new_price - $original_price;

	if( $adjust_tax == 'remove' ) {

		if( ! empty( $cart_item['product_extras']['use_calc_set_price'] ) ) {
			// Just use the calculated price
			$new_price = pewc_get_price_without_tax( $new_price, $cart_item['data'] );
		} else {
			// Strip the tax off the extras to get a price without tax
			$extras_without_tax = pewc_get_price_without_tax( $original_extras, $cart_item['data'] );
			$original_extras = pewc_get_price_without_tax( $new_price, $cart_item['data'] );
			$new_price = $original_price + $extras_without_tax;
		}

	} else if( $adjust_tax == 'add' ) {

		$tax_rate = pewc_get_tax_rate( $cart_item );

		$original_extras = $original_extras * $tax_rate;
		$new_price = $original_price + $original_extras;

	}

	return $new_price;

}

/**
 * Get the adjusted add-on field price in the cart
 * Adjusts price per field, then saved in the cart_item, so that they don't need to get adjusted in the cart
 * @since	3.9.5
 * @version	3.26.4
 */
function pewc_get_adjusted_product_addon_field_price( $price, $product ) {

	if( pewc_adjust_tax() == 'remove' ) {
		// Strip out the tax if prices entered excl tax but displayed incl tax
		$price = pewc_get_price_without_tax( $price, $product );
	} else if( pewc_adjust_tax() == 'add' ) {
		// Add tax to the cart to match its original price in the backend
		// This seems to be how WooCommerce saves the product price in the cart session, i.e. whatever was the value in the backend
		$tmp_cart_item = array(
			'data' => $product
		); // we need cart_item below, but it's not available, so create a tmp one
		$tax_rate = pewc_get_tax_rate( $tmp_cart_item );
		$price = $price * $tax_rate;
	} else if ( apply_filters( 'pewc_auto_detect_tax_rate_for_adjustment', false, $product ) ) {
		// 3.26.4, maybe we can detect if a tax has been applied by using pewc_maybe_include_tax()?
		// This is needed for this scenario: inc/inc/inc, shop country is DK, DK has 25% tax, but other countries like NO or GB has 0% tax.
		// So WC will actually remove the tax from the price entered in the backend. This means that 1.00 becomes 0.8 (0.8 + 25% tax = 1)
		// pewc_adjust_tax() couldn't detect the scenario above, so we use this. Maybe this is even better in the long run, but needs further testing for other tax settings
		$calculated = pewc_maybe_include_tax( $product, 1, true );
		if ( $calculated != 1 && $calculated > 0 ) {
			// price was adjusted? adjust the price again to its original value in the backend, this is used in the cart data (not outputted) to avoid double taxation
			$price = $price / $calculated;
		}
	}
	return $price;
}


/**
 * Get the tax rate for an order line item
 * @since 3.9.2
 */
function pewc_get_tax_rate( $cart_item ) {

	$wc_tax = new WC_Tax();
	$billing_country = WC()->customer->get_billing_country();

	// Get the tax class and relevant tax data
	$tax_class = $cart_item['data']->get_tax_class();
	$tax_data = $wc_tax->find_rates( array( 'country' => $billing_country, 'tax_class' => $tax_class ) );

	if( ! empty( $tax_data ) ) {
		$tax_rate = reset($tax_data)['rate'];
		// Return the tax rate as a decimal
		$tax_rate = 1 + ( $tax_rate / 100 );
		return $tax_rate;
	}

	return 1;

}

/**
 * Check if product has a calculation field
 */
function pewc_has_calculation_field( $product_id ) {

	if( ! pewc_is_pro() ) return false;

	$has_calculation = false;
	$groups = pewc_get_extra_fields( $product_id );
	foreach( $groups as $group ) {
		if( isset( $group['items'] ) ) {
			foreach( $group['items'] as $field ) {
				if(
					( isset( $field['field_type'] ) && $field['field_type'] == 'calculation' ) || 
					( isset( $field['field_price'] ) && pewc_price_has_formula( $field['field_price'] ) ) || 
					pewc_field_option_price_has_formula( $field ) 
				) {
					$has_calculation = true;
					break;
				}
			}
		}
	}
	return $has_calculation;
}

/**
 * Check if product has a color picker field
 */
function pewc_has_color_picker_field( $product_id ) {
  $has_color_picker = false;
  $groups = pewc_get_extra_fields( $product_id );
  foreach( $groups as $group ) {
    if( isset( $group['items'] ) ) {
      foreach( $group['items'] as $field ) {
        if( isset( $field['field_type'] ) && $field['field_type'] == 'color-picker' ) {
          $has_color_picker = true;
          break;
      	}
      }
    }
  }
  return $has_color_picker;
}

/**
 * Have we enabled DropZone.js uploads?
 */
function pewc_enable_ajax_upload() {
	$enable_js = get_option( 'pewc_enable_dropzonejs', 'no' );
	return apply_filters( 'pewc_enable_dropzonejs', $enable_js );
}

function pewc_get_max_upload() {
	$pewc_max_upload = get_option( 'pewc_max_upload', 1 );
	return apply_filters( 'pewc_filter_max_upload', $pewc_max_upload );
}

/**
 * Get a list of all subscription variations
 * @return Array
 */
function pewc_get_subscription_variations() {

	$variations = array();

	$args = array(
		'type'		=> 'variable-subscription',
		'limit'		=> -1,
		'return'	=> 'ids'
	);
	$query = new WC_Product_Query( $args );
	$variable_subscriptions = $query->get_products();

	if( $variable_subscriptions ) {

		foreach( $variable_subscriptions as $variable_subscription ) {

			$variation = new WC_Product_Variable( $variable_subscription );
			$available_variations = $variation->get_available_variations();

			if( $available_variations ) {

				foreach( $available_variations as $available_variation ) {

					$v = wc_get_product( $available_variation['variation_id'] );
					$variations[$available_variation['variation_id']] = $v->get_name();

				}

			}

		}

	}

	return $variations;

}

/**
 * Is product add-on editing enabled?
 * @return Boolean
 */
function pewc_user_can_edit_products() {

	// if( ! pewc_is_pro() ) {
	// 	return false;
	// }

	$can_edit = false;
	if( get_option( 'pewc_enable_cart_editing', 'no' ) == 'yes' ) {
		$can_edit = true;
	}

	return apply_filters( 'pewc_user_can_edit_products', $can_edit );

}

/**
 * Are we using circular swatches?
 * @since 3.16.0
 */
function pewc_get_circular_swatches( $item=false, $product_id=false ) {
	
	$circular = get_option( 'pewc_circular_swatches', false );
	return apply_filters( 'pewc_circular_swatches', $circular, $item, $product_id );

}

/**
 * Get swatch width
 * @since 3.16.0
 */
function pewc_get_swatch_width() {
	
	$width = get_option( 'pewc_swatch_width', '0' );
	return $width;

}

/**
 * Get width of colour swatch
 * @since 3.20.0
 */
function pewc_get_color_swatch_width() {
	
	$width = get_option( 'pewc_color_swatch_width', '60' );
	return $width;

}

/**
 * Get height of colour swatch
 * @since 3.20.0
 */
function pewc_get_color_swatch_height() {
	
	$height = get_option( 'pewc_color_swatch_height', '60' );
	return $height;

}



/**
 * Get swatch border width
 * @since 3.20.0
 */
function pewc_get_swatch_border_width() {
	
	$width = get_option( 'pewc_swatch_border_width', '0' );
	return $width;

}

/**
 * Return some HTML for the image in an image swatch field
 * @param	$option_value	Array
 * @since 3.5.0
 */
function pewc_get_swatch_image_html( $option_value, $item ) {

	if( empty( $option_value['image'] ) && empty( $option_value['image_alt'] ) ) {
		return wc_placeholder_img( pewc_get_swatch_thumbnail_size( $item ) );
	}

	$alt_attachment_id = ! empty( $option_value['image_alt'] ) ? $option_value['image_alt'] : false;
	$attachment_id = $option_value['image'];

	// If we've got an alt image, then we need to display that as the swatch image
	// The standard image is just used to replace the main product image
	$display_id = $alt_attachment_id ? $alt_attachment_id : $attachment_id;

	$full_size = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
	//$full_src = wp_get_attachment_image_src( $display_id, $full_size ); // 3.26.16, commented out, because if alt image exists, the main swatch image isn't used to replace the product image
	// 3.26.16, use the main swatch image if it exists
	if ( $attachment_id ) {
		$full_src_id = $attachment_id;
	} else if ( $alt_attachment_id ) {
		// only use the alt image if the main swatch image doesn't exist
		$full_src_id = $alt_attachment_id;
	} else {
		// both images don't exist
		$full_src_id = false;
	}
	$full_src = wp_get_attachment_image_src( $full_src_id, $full_size );

	if ( ! $full_src ) {
		return wc_placeholder_img( pewc_get_swatch_thumbnail_size( $item ) );
	}

	$alt_full_src = $alt_attachment_id ? wp_get_attachment_image_src( $attachment_id, $full_size ) : $full_src;

	$image = wp_get_attachment_image(
		$display_id,
		pewc_get_swatch_thumbnail_size( $item ),
		false,
		array(
			'title'                   => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
			'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
			'data-src'                => esc_url( $full_src[0] ),
			'data-large_image'        => esc_url( $full_src[0] ),
			'data-large_image_width'  => esc_attr( $full_src[1] ),
			'data-large_image_height' => esc_attr( $full_src[2] ),
			'data-alt_image'		  => isset( $alt_full_src[0] ) ? esc_url( $alt_full_src[0] ) : '',
			'alt'					=> _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ), // 3.26.5
		)
	);

	return $image;
}

/**
 * Return the image size for a swatch field
 * @since 3.20.0
 */
function pewc_get_swatch_thumbnail_size( $item ) {

	$size = get_option( 'pewc_swatch_image_size', 'thumbnail' );
	return apply_filters( 'pewc_image_swatch_thumbnail_size', $size, $item );

}

/**
 * Return the layout for product quantity fields when a quantity field is present
 * @since 3.21.0
 */
function pewc_get_quantity_layout() {

	$layout = get_option( 'pewc_quantity_layout', 'grid' );
	return apply_filters( 'pewc_quantity_layout', $layout );

}

/**
 * Return the URL for an image in an image swatch field
 * @param	$image Array
 * @since 3.7.1
 */
function pewc_get_swatch_image_url( $option_value, $item ) {

	if( empty( $option_value['image'] ) ) {
		return array( wc_placeholder_img_src( pewc_get_swatch_thumbnail_size( $item ) ) );
	}

	$attachment_id = $option_value['image'];

	$size = apply_filters( 'woocommerce_gallery_full_size', pewc_get_swatch_thumbnail_size( $item ) );
	$src = wp_get_attachment_image_src( $attachment_id, $size );

	if ( false === $src ) {
		// 3.13.7, maybe the image was deleted, return default
		return array( wc_placeholder_img_src( pewc_get_swatch_thumbnail_size( $item ) ) );
	}

	return $src;

}

/**
 * Return renaming options for uploads
 * @since 3.7.0
 */
function pewc_get_rename_uploads() {

	$rename = get_option( 'pewc_rename_uploads', false );
	return $rename;

}

/**
 * Organise uploads into unique folders per order?
 * @since 3.7.0
 */
function pewc_get_pewc_organise_uploads() {

	$organise = get_option( 'pewc_organise_uploads', 'no' );
	return $organise;

}

function pewc_enable_pdf_uploads() {

	$enable = get_option( 'pewc_enable_pdf_uploads', 'no' );
	return $enable;

}

/**
 * Show the tax suffix after all add-on prices?
 * @since 3.7.15
 */
function pewc_show_price_suffix() {
	$enable = get_option( 'pewc_tax_suffix', 'no' );
	return $enable;
}

/**
 * Show the tax suffix after all add-on prices?
 * @since 3.7.15
 */
function pewc_reset_hidden_fields( $post_id ) {
	$reset = get_option( 'pewc_reset_fields', 'no' );
	return apply_filters( 'pewc_reset_hidden_fields', $reset, $post_id );
}

/**
 * Set the quantity field to the default value on independent child product quantity?
 * @since 3.13.5
 */
function pewc_set_child_quantity_default( $post_id ) {
	$set = 'no';
	return apply_filters( 'pewc_set_child_quantity_default', $set, $post_id );
}

/**
 * Optimise conditions?
 * @since 3.8.7
 */
function pewc_conditions_timer( $time ) {
	$optimise = get_option( 'pewc_optimise_conditions', 'no' );
	if( $optimise == 'yes' ) {
		$time = 500;
	}
	return $time;
}
add_filter( 'pewc_conditions_timer', 'pewc_conditions_timer' );

/**
 * Optimise conditions?
 * @since 3.8.7
 */
function pewc_calculations_timer( $time ) {
	$optimise = get_option( 'pewc_optimise_calculations', 'no' );
	if( $optimise == 'yes' ) {
		$time = 500;
	}
	return $time;
}
add_filter( 'pewc_calculations_timer', 'pewc_calculations_timer' );

/**
 * Check if field is visible on page
 * @since 3.13.7
 */
function pewc_field_visible_in( $page, $field_visibility, $field_id, $group_id, $product_id ) {
	// array values are pages where they are visible, e.g. hide in product means fields are hidden in the product page, so they are visible in the cart, order page, and docs (i.e. emails, invoices, etc.)
	$visible_pages = array(
		'visible'			=> array( 'product', 'cart', 'order', 'docs' ), 
		'display_product'	=> array( 'product' ),
		'hide_product'		=> array( 'cart', 'order', 'docs' ),
		'hide_customer'		=> array(),
	);

	if ( empty( $field_visibility ) || ! isset( $visible_pages[ $field_visibility ] ) || 'visible' === $field_visibility ) {
		return true; // visible
	}

	if ( in_array( $page, $visible_pages[ $field_visibility ] ) ) {
		$visible = true;
	} else {
		$visible = false;
	}

	return apply_filters( 'pewc_field_visible_in_'.$page, $visible, $field_id, $group_id, $product_id );
}

/**
 * Container function for set_transient. Allow filters to be used to not set transients on some or all
 * @since 3.15.0
 */
function pewc_set_transient( $transient_name, $transient_value ) {

	if ( apply_filters( 'pewc_disable_all_transients', false ) ) {
		return false;
	}

	// allow specific transients to be disabled
	if ( apply_filters( 'pewc_disable_transient_'.$transient_name, false ) ) {
		return false;
	}

	set_transient( $transient_name, $transient_value, pewc_get_transient_expiration() );

}

/**
 * Container function for get_transient. Allow filters to be used to not get transients on some or all
 * @since 3.15.0
 */
function pewc_get_transient( $transient_name ) {

	if ( apply_filters( 'pewc_disable_all_transients', false ) ) {
		return false;
	}

	// allow specific transients to be disabled
	if ( apply_filters( 'pewc_disable_transient_'.$transient_name, false ) ) {
		return false;
	}

	return get_transient( $transient_name );

}

/**
 * Prevent a product that is sold individually to be added in the cart more than once
 * @since 3.17.2
 */
function pewc_validate_child_products_sold_individually() {
	return apply_filters( 'pewc_validate_child_products_sold_individually', true );
}

/**
 * Display a progress bar
 * @since 3.18.0
 */
function pewc_enable_progress_bar( $product_id, $groups ) {
	$bar = get_option( 'pewc_progress_bar', 'no' );
	return apply_filters( 'pewc_progress_bar_status', $bar, $product_id, $groups );
}

/**
 * Filter the number input type for range sliders
 * @since 3.19.0
 */
function pewc_number_field_input_type( $input_type, $product_id, $item ) {
    // Check if the enable_range_slider is set to 'checked'
    $enable_range_slider = isset( $item['field_enable_range_slider'] ) ? $item['field_enable_range_slider'] : '';

    if( $enable_range_slider == '1' ) {
        return 'range';
    }

    return $input_type;
}
add_filter( 'pewc_number_field_input_type', 'pewc_number_field_input_type', 10, 3 );

/**
 * Replace backslashes with slashes because backslashes gets trimmed by WP later and then the file isn't readable anymore
 * Filter was introduced in 3.21.7, created a container function in 3.23.1
 * @since 3.23.1
 */
function pewc_replace_backslashes_in_file_paths() {
	return apply_filters( 'pewc_replace_backslashes_in_file_paths', false );
}

/**
 * Exclude fields with empty values from the cart meta
 * @since 3.26.7
 */
function pewc_hide_empty_fields_cart() {
	return get_option( 'pewc_hide_empty_fields_cart', 'yes' );
}

/**
 * Display fields hidden by conditions as visible but disabled
 * @since 3.26.7
 */
function pewc_disable_hidden_fields() {
	return apply_filters( 'pewc_disable_hidden_fields', get_option( 'pewc_disable_hidden_fields', 'no' ) );
}

/**
 * Check if Hide quantity setting is enabled
 * @since 3.26.11
 */
function pewc_hide_quantity( $product ) {

	if ( $product->is_type( 'variation' ) ) {
		$product_id = $product->get_parent_id();
	} else {
		$product_id = $product->get_id();
	}
	$hide = get_post_meta( $product_id, 'pewc_hide_quantity', 'no' );
	
	return 'yes' === $hide ? true : false;
}
