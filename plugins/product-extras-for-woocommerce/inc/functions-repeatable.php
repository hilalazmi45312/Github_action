<?php
/**
 * Functions for repeatable groups
 * @since 3.22.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns whether this group data is repeatable
 * @since 3.22.0
 */
function pewc_get_group_repeatable( $group_id ) {
	$repeatable = get_post_meta( $group_id, 'repeatable', true );
	return ! empty( $repeatable ) ? true : false;
}

/**
 * Returns whether we repeat by quantity
 * @since 3.22.0
 */
function pewc_get_group_repeatable_by_quantity( $group_id ) {
	$repeatable_by_quantity = get_post_meta( $group_id, 'repeatable_by_quantity', true );
	return ! empty( $repeatable_by_quantity ) ? true : false;
}

/**
 * Returns repeat limit
 * @since 3.22.0
 */
function pewc_get_group_repeatable_limit( $group_id ) {
	$limit = (int) get_post_meta( $group_id, 'repeatable_limit', true );
	if( $limit === 0 ) $limit = 999;
	return $limit;
}

/**
 * Returns the type of label update we do when cloning, i.e. adding a number after each repeated group title or field label. Default is 'group'
 * @since 3.22.0
 */
function pewc_repeatable_labeling( $group_id ) {
	return apply_filters( 'pewc_repeatable_labeling', 'group', $group_id );
}

/**
 * Return the labeling format depending on the labeling type
 * @since 3.22.0
 */
function pewc_repeatable_label_format( $type = 'group', $output = 'display' ) {
	if ( 'field' === $type || 'order' === $output ) {
		// 3.24.3, added order output for order item meta labels
		$format = apply_filters( 'pewc_repeatable_label_format_field', '[field_label] ([clone_count])', $output );
	} else {
		if ( 'notice' === $output ) {
			$format = apply_filters( 'pewc_repeatable_label_format_group', '[group_title] ([clone_count]): [field_label]', $output );
		} else {
			$format = apply_filters( 'pewc_repeatable_label_format_group', '[group_title] ([clone_count])', $output );
		}
	}
	return $format;
}

/**
 * Process the label format to return a display-ready label. Used by both cloned group titles and cloned fields
 * @since 3.22.0
 */
function pewc_repeatable_get_label( $format, $group_title, $field_label, $clone_count ) {
	//$clone_count += 1;
	$find = array( '[group_title]', '[field_label]', '[clone_count]' );
	$replace = array( $group_title, $field_label, $clone_count );
	$label = str_replace( $find, $replace, $format );
	return $label;
}

/**
 * Display a button on the frontend product page that allows customers to repeat a group
 * @since 3.22.0
 */
function pewc_end_group_repeatable( $group_id, $group, $group_index ) {

	if ( ! empty( $group['is_repeatable'] ) && ! empty( $group['has_repeatable'] ) ) {

		if ( isset( $_POST['pewc-repeat-group-count-'.$group_id] ) ) {
			$clone_count = (int) $_POST['pewc-repeat-group-count-'.$group_id];
			if ( $clone_count > 1 && ( empty( $group['clone_count'] ) || $group['clone_count'] < $clone_count ) ) {
				return; // we only add this button after the last repeated group
			}
		} else {
			$clone_count = 1;
		}

		$repeat_limit = pewc_get_group_repeatable_limit( $group_id );
		$group_title = pewc_get_group_title( $group_id, $group, pewc_has_migrated() );
		$label_type = pewc_repeatable_labeling( $group_id );
		$label_format = pewc_repeatable_label_format( $label_type );
		$repeat_by_quantity = pewc_get_group_repeatable_by_quantity( $group_id );

		printf(
			'<p class="pewc-repeat-group pewc-repeat-group-%s %s">
				<a href="#" 
					id="pewc-repeat-group-%s" 
					class="pewc-repeat-group-button" 
					data-clone-counter="%s" 
					data-repeat-limit="%s" 
					data-repeat-labeling="%s" 
					data-repeat-label-format="%s" 
					data-repeat-by-quantity="%s" 
					data-group-title="%s"
				>%s</a>
			</p>
			<input type="hidden" id="pewc-repeat-group-count-%s" name="pewc-repeat-group-count-%s" value="%s">',
			$group_id,
			$repeat_by_quantity ? 'pewc-repeat-by-quantity pewc-visibility-hidden' : '',
			$group_id,
			$clone_count,
			$repeat_limit,
			esc_attr( $label_type ),
			esc_attr( $label_format ),
			$repeat_by_quantity ? 'yes' : 'no',
			esc_attr( $group_title ),
			__( '+ Add More', 'pewc'),
			$group_id,
			$group_id,
			$clone_count,
		);

		// so that this JS file is only loaded when needed?
		if ( ! wp_script_is( 'pewc-js-repeatable', 'enqueued' ) ) {
			$version = defined( 'PEWC_SCRIPT_DEBUG' ) && PEWC_SCRIPT_DEBUG ? time() : PEWC_PLUGIN_VERSION;
			wp_register_script( 'pewc-js-repeatable', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-repeatable.js', array( 'pewc-script' ), $version, true );
			wp_enqueue_script( 'pewc-js-repeatable' );
			wp_enqueue_style( 'pewc-tooltipster-style', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/style-repeatable.css', array( 'pewc-style' ), $version );
		}

	}

}
add_action( 'pewc_end_group', 'pewc_end_group_repeatable', 10, 3 );

/**
 * Return an array of fields that can be repeated
 * @since 3.22.0
 */
function pewc_get_repeatable_fields() {
	$repeatable_fields = array( 'number', 'select', 'text', 'radio' );
	return $repeatable_fields;
}

/**
 * Add the array of repeatable fields to pewc_vars to be accessed on the frontend by Javascript
 * @since 3.22.0
 */
function pewc_repeatable_vars( $vars, $post_id ) {
	$vars['repeatable_fields'] = pewc_get_repeatable_fields();
	$vars['repeatable_confirm_remove'] = apply_filters( 'pewc_repeatable_confirm_remove', __( 'Are you sure you want to remove this group?', 'pewc' ), $post_id ); // 3.26.18
	return $vars;
}
add_filter( 'pewc_localize_script_vars', 'pewc_repeatable_vars', 10, 2 );

/**
 * Change ID to an array so that we can loop through them when adding to the cart?
 * @since 3.22.0
 */
function pewc_change_to_array_id( $id, $item, $type='' ) {

	if ( pewc_is_repeatable_field( $item ) ) {
		// some fields need unique IDs?
		$unique_fields = array( 'radio', 'select' );
		// $item['clone_count'] is not empty if this is a submitted page
		if( in_array( $item['field_type'], $unique_fields ) && ! empty( $item['clone_count'] ) ) {
			$index = floor( $item['clone_count'] - 1 );
			if ( 'id' === $type ) {
				$id .= '_' . $index; // this is used in radio and select IDs, which must be unique?
			} else {
				$id .= '[' . $index . ']'; // this is used in radio name
			}
		} else if ( 'id' !== $type ) {
			// we are filtering input names
			$id .= '[]';
		}
	}
	return $id;

}
add_filter( 'pewc_filter_input_id', 'pewc_change_to_array_id', 10, 3 );

/**
 * Filter posted value if it's from a repeated field
 * @since 3.22.0
 */
function pewc_filter_repeated_value( $value, $id, $item, $posted ) {
	if ( ! empty( $item['group_is_repeatable'] ) && isset( $posted[$id] ) && is_array( $posted[$id] ) ) {
		$repeatable_fields = pewc_get_repeatable_fields();
		if ( in_array( $item['field_type'], $repeatable_fields ) ) {
			$index = ! empty( $item['clone_count'] ) ? ( $item['clone_count'] - 1 ) : 0;
			$value = ! empty( $posted[$id][$index] ) ? $posted[$id][$index] : ''; // 3.26.5
		}
	}
	return $value;
}
add_filter( 'pewc_default_field_value', 'pewc_filter_repeated_value', 10, 4 );

/**
 * Build a new array with repeated groups
 * @since 3.22.0
 */
function pewc_build_groups_array_with_repeated( $product_extra_groups ) {
	$all_groups = array();
	$repeatable_fields = pewc_get_repeatable_fields();
	$group_index = 0;

	foreach( $product_extra_groups as $group_id => $group ) {
		// add the group_id to the $group array so that we can use it later
		if( ! is_array( $group ) ) {
			$group = array();
		}
		$group['id'] = $group_id;
		$group_is_repeatable = pewc_get_group_repeatable( $group_id );
		if ( $group_is_repeatable ) {
			$group['is_repeatable'] = true;
			$group['title'] = $orig_group_title = pewc_get_group_title( $group_id, $group, pewc_has_migrated() );
			$group['repeat_by_quantity'] = pewc_get_group_repeatable_by_quantity( $group_id );
		}
		// save the original group first
		$all_groups[$group_index] = $group;

		if ( $group_is_repeatable && ! empty( $group['items'] ) ) {
			$has_repeatable = false;
			$clone_count = ! empty( $_POST['pewc-repeat-group-count-'.$group_id] ) ? (int) $_POST['pewc-repeat-group-count-'.$group_id] : 0;
			$quantity = ! empty( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1;

			if ( $clone_count > 1 ) {
				// loop through all the items and retain only repeatable fields
				$cloned_group = array();
				foreach ( $group['items'] as $field_id => $item ) {
					if ( in_array( $item['field_type'], $repeatable_fields ) ) {
						$item['is_repeatable'] = true;
						$cloned_group['items'][$field_id] = $item;
						$has_repeatable = true;
					}
				}

				if ( ! empty( $cloned_group ) ) {
					$cloned_group['id'] = $group_id;
					$cloned_group['is_repeatable'] = $group_is_repeatable;
					// add this indicator to the original group
					$all_groups[$group_index]['has_repeatable'] = true;
					// for labeling
					$label_type = pewc_repeatable_labeling( $group_id );
					if ( 'group' === $label_type ) {
						$group_label_format = pewc_repeatable_label_format( $label_type );
					}
				} else {
					continue; // nothing to clone, skip to the next group
				}
				// now add the cloned group repeatedly
				for ( $i = 2; $i <= $clone_count; $i++ ) {
					if ( $group['repeat_by_quantity'] && $i > $quantity ) {
						// if this group is repeated by quantity, we skip cloned groups that go beyond the quantity, because they might be hidden. They don't get added to the cart
						continue;
					}
					$cloned_group['clone_count'] = $i;
					$cloned_group['has_repeatable'] = true;
					if ( 'group' === $label_type ) {
						$cloned_group['title'] = pewc_repeatable_get_label( $group_label_format, $orig_group_title, '', $i );
					} else {
						$cloned_group['title'] = $orig_group_title;
					}
					$all_groups[++$group_index] = $cloned_group;
				}
			} else {
				// on first load, check if we have repeatable fields. We'll use it later to determine if we need to display the Add More button
				foreach ( $group['items'] as $field_id => $item ) {
					if ( in_array( $item['field_type'], $repeatable_fields ) ) {
						$has_repeatable = true;
						break;
					}
				}
				if ( $has_repeatable ) {
					$all_groups[$group_index]['has_repeatable'] = true;
				}
			}
		}
		$group_index++;
	}

	return $all_groups;
}

/**
 * Add additional data to $item variable that we can use later
 * @since 3.22.0
 */
function pewc_add_clone_count_to_item( $item, $group, $group_id, $post_id ) {

	// this is not empty if $_POST exists
	if ( ! empty( $group['clone_count'] ) && ! empty( $item['is_repeatable'] ) ) {
		$item['clone_count'] = $group['clone_count'];
		$labeling_type = pewc_repeatable_labeling( $group_id );
		if ( 'field' === $labeling_type ) {
			// also update field label
			$format = pewc_repeatable_label_format( $labeling_type );
			$item['field_label'] = pewc_repeatable_get_label( $format, $group['title'], $item['field_label'], $group['clone_count'] );
		}
	}

	if ( ! empty( $group['is_repeatable'] ) ) {
		$item['group_is_repeatable'] = true; // used to detect if we need to change the input names to array
	}
	return $item;

}
add_filter( 'pewc_filter_item_start_list', 'pewc_add_clone_count_to_item', 10, 4 );

/**
 * Build a new array combining the original group and the cloned groups into one, used in pewc_get_item_data()
 * @since	3.22.0
 * @version	3.24.3
 * 
 * @param	$output		Added in 3.24.3, value is 'order' in pewc_add_custom_data_to_order()
 */
function pewc_rebuild_cart_item_data ( $product_extras, $output='display' ) {

	$all_groups = array();

	foreach ( $product_extras['groups'] as $group_id => $group ) {
		// save the group ID to the group var
		$group['id'] = $group_id;

		// check if this group is repeatable by quantity
		$repeat_by_quantity = pewc_get_group_repeatable_by_quantity( $group_id );
		if ( $repeat_by_quantity ) {
			// activate the filters that disable the quantity on the cart page, for shortcode only. The filter used by Cart Blocks (woocommerce_store_api_product_quantity_editable) is called earlier, so we check it differently
			add_filter( 'woocommerce_cart_item_quantity', 'pewc_repeatable_disable_cart_item_quantity', 11, 3 ); // shortcode
		}

		// add the default group to the catch-all $all_groups
		$all_groups[] = $group;

		$label_type = pewc_repeatable_labeling( $group_id );
		if ( 'group' === $label_type && isset( $group[$group_id]['label'] ) ) {
			// keep a copy of this to be used later
			$group_title = array( $group_id => $group[$group_id] );
		}

		if ( ! empty( $product_extras['cloned_groups'][$group_id] ) ) {
			foreach ( $product_extras['cloned_groups'][$group_id] as $clone_count => $cloned_group ) {
				if ( empty( $cloned_group['id'] ) ) {
					$cloned_group['id'] = $group_id;
				}

				if ( 'field' === $label_type || 'order' === $output ) {
					// update field labels with the clone count
					// 3.24.2, added order output condition, so that item meta data has unique labels
					foreach ( $cloned_group as $cloned_field_id => $cloned_field ) {
						if ( ! empty( $cloned_field['label'] ) ) {
							$label_format = pewc_repeatable_label_format( $label_type, $output );
							$new_label = pewc_repeatable_get_label( $label_format, '', $cloned_field['label'], $clone_count );
							$cloned_group[$cloned_field_id]['label'] = $new_label;
						}
					}
				} else {
					// update the group titles of the cloned groups
					if ( empty( $cloned_group[$group_id] ) && ! empty( $group_title[$group_id]['label'] ) ) {
						// update the title
						$cloned_group_title = $group_title;
						$label_format = pewc_repeatable_label_format( $label_type );
						$new_label = pewc_repeatable_get_label( $label_format, $cloned_group_title[$group_id]['label'], '', $clone_count );
						$cloned_group_title[$group_id]['label'] = $new_label;
						$cloned_group = $cloned_group_title + $cloned_group;
					}
				}

				$all_groups[] = $cloned_group;
			}
		}
	}

	return $all_groups;

}

/**
 * Add a class to cloned groups
 * @since 3.22.0
 */
function pewc_repeated_group_wrapper_classes( $wrapper_classes, $group_id, $group_index, $first_group_class, $group, $post_id ) {
	if ( pewc_get_group_repeatable( $group_id ) ) {
		// 3.26.5
		$wrapper_classes[] = 'pewc-repeatable-group';
		// moved here
		if ( ! empty( $group['clone_count'] ) ) {
			$wrapper_classes[] = 'pewc-cloned-group';
		}
	}
	return $wrapper_classes;
}
add_filter( 'pewc_group_wrapper_classes', 'pewc_repeated_group_wrapper_classes', 10, 6 );

/**
 * Add repeatable settings to the Global Groups as post type
 * @since 3.22.0
 */
function pewc_metabox_repeatable_fields( $pewc_groups ) {
	$pewc_groups[] = array(
		'ID'		=> 'repeatable',
		'name'		=> 'repeatable',
		'title'		=> __( 'Repeatable', 'pewc' ),
		'type'		=> 'checkbox',
		'class'		=> '',
		'input_class' => 'pewc-group-repeatable',
	);
	$pewc_groups[] = array(
		'ID'		=> 'repeatable_by_quantity',
		'name'		=> 'repeatable_by_quantity',
		'title'		=> __( 'Attach to Quantity', 'pewc' ),
		'type'		=> 'checkbox',
		'class'		=> '',
		'input_class' => '',
	);
	$pewc_groups[] = array(
		'ID'		=> 'repeatable_limit',
		'name'		=> 'repeatable_limit',
		'title'		=> __( 'Repeatable Limit', 'pewc' ),
		'type'		=> 'number',
		'class'		=> '',
	);
	return $pewc_groups;
}
add_filter( 'pewc_groups_metabox_fields', 'pewc_metabox_repeatable_fields', 10, 1 );

/**
 * Filter the Repeatable by Quantity container class for Global Groups as post type
 * @since 3.22.0
 */
function pewc_metabox_repeatable_by_quantity_class( $class, $post, $field ) {
	if ( ! empty( $post->ID ) && 'pewc_group' === get_post_type( $post->ID ) && 'repeatable_by_quantity' === $field['ID'] ) {
		if ( ! empty( $class ) ) {
			$class .= ' ';
		}
		$class .= 'pewc-repeatable-options-' . $post->ID . ' pewc-repeatable-by-quantity-' . $post->ID;
		$is_repeatable = get_post_meta( $post->ID, 'repeatable', true );
		if ( empty( $is_repeatable ) ) {
			$class .= ' hidden';
		}
	}
	return $class;
}
add_filter( 'pewc_metabox_checkbox_output_class', 'pewc_metabox_repeatable_by_quantity_class', 10, 3 );

/**
 * Filter the Repeatable Limit container class for Global Groups as post type
 * @since 3.22.0
 */
function pewc_metabox_repeatable_limit_class( $class, $post, $field ) {
	if ( ! empty( $post->ID ) && 'pewc_group' === get_post_type( $post->ID ) && 'repeatable_limit' === $field['ID'] ) {
		if ( ! empty( $class ) ) {
			$class .= ' ';
		}
		$class .= 'pewc-repeatable-options-' . $post->ID . ' pewc-repeatable-limit-' . $post->ID;
		$is_repeatable = get_post_meta( $post->ID, 'repeatable', true );
		if ( empty( $is_repeatable ) ) {
			$class .= ' hidden';
		}
	}
	return $class;
}
add_filter( 'pewc_metabox_number_output_class', 'pewc_metabox_repeatable_limit_class', 10, 3 );

/**
 * Filter the Repeatable checkbox attributes for Global Groups as post type
 * $attributes should be an array
 * @since 3.22.0
 */
function pewc_metabox_repeatable_attributes( $attributes, $post, $field ) {
	if ( is_array( $attributes ) && ! empty( $post->ID ) && 'pewc_group' === get_post_type( $post->ID ) && 'repeatable' === $field['ID'] ) {
		$attributes['data-group-id'] = $post->ID;
	}
	return $attributes;
}
add_filter( 'pewc_metabox_checkbox_output_attributes', 'pewc_metabox_repeatable_attributes', 10, 3 );

/**
 * Save repeatable values in Global Groups as post type
 * @since 3.22.0
 */
function pewc_save_repeatable_metabox_data( $group_id ) {
	if( 'pewc_group' !== get_post_type( $group_id ) ) {
		// Do groups separately
		return;
	}

	// Save our metaboxes
	if( isset( $_POST['repeatable'] ) ) {
		$data = sanitize_text_field( $_POST['repeatable'] );
		update_post_meta( $group_id, 'repeatable', $data );
	} else {
		delete_post_meta( $group_id, 'repeatable' );
	}
	if( isset( $_POST['repeatable_by_quantity'] ) ) {
		$data = sanitize_text_field( $_POST['repeatable_by_quantity'] );
		update_post_meta( $group_id, 'repeatable_by_quantity', $data );
	} else {
		delete_post_meta( $group_id, 'repeatable_by_quantity' );
	}
	update_post_meta( $group_id, 'repeatable_limit', sanitize_text_field( $_POST['repeatable_limit'] ) );
}
add_action( 'pewc_after_save_group_metabox_data', 'pewc_save_repeatable_metabox_data', 10, 1 );

/**
 * Duplicate postmeta values when a group is duplicated. Called by both Global Add-ons and Global Groups as post type
 * @since 3.22.0
 */
function pewc_duplicate_repeatable_postmeta( $old_group_id, $new_group_id ) {
	$repeatable = get_post_meta( $old_group_id, 'repeatable', true );
	$repeatable_by_quantity = get_post_meta( $old_group_id, 'repeatable_by_quantity', true );
	$repeatable_limit = get_post_meta( $old_group_id, 'repeatable_limit', true );
	if ( ! empty( $repeatable ) ) {
		update_post_meta( $new_group_id, 'repeatable', $repeatable );
	}
	if ( ! empty( $repeatable_by_quantity ) ) {
		update_post_meta( $new_group_id, 'repeatable_by_quantity', $repeatable_by_quantity );
	}
	if ( ! empty( $repeatable_limit ) ) {
		update_post_meta( $new_group_id, 'repeatable_limit', $repeatable_limit );
	}
}

/**
 * If a product has a group that is repeatable by quantity, disable cart quantity (when using [woocommerce_cart] shortcode). Hooks into woocommerce_cart_item_quantity
 * @since 3.22.0
 */
function pewc_repeatable_disable_cart_item_quantity( $cart_item_quantity, $cart_item_key, $cart_item=false ) {

	if ( false !== strpos( $cart_item_quantity, '<input' ) ) {
		return $cart_item['quantity'];
	}

	return $cart_item_quantity;

}

/**
 * If a product has a group that is repeatable by quantity, disable cart quantity (when using Blocks)
 * @since 3.22.0
 */
function pewc_repeatable_disable_api_product_quantity( $editable, $product, $cart_item ) {

	if ( ! empty( $cart_item['product_extras']['groups'] ) && $editable ) {
		foreach ( $cart_item['product_extras']['groups'] as $group_id => $group ) {
			$repeat_by_quantity = pewc_get_group_repeatable_by_quantity( $group_id );
			if ( $repeat_by_quantity ) {
				// if just one group is repeatable by quantity, disable the quantity (mark 'editable' as false)
				return false;
			}
		}
	}
	return $editable;

}
add_filter( 'woocommerce_store_api_product_quantity_editable', 'pewc_repeatable_disable_api_product_quantity', 11, 3 );

/**
 * Add repeated group values to Add-Ons by Order page
 * @since 3.24.7
 */
function pewc_filter_metabox_aou_groups ( $groups, $post_id, $metabox_field=false ) {
	// check if this order AOU has a marker where repeated values were saved correctly?
	$product_extras_version = get_post_meta( $post_id, 'pewc_product_extras_version', true );
	if ( ! $product_extras_version && is_array( $groups ) ) {
		// this is an old order before we fixed the issue with repeated values not getting included in pewc_product_extra_fields
		$new_groups = array();

		// since we don't have order_item_id, use product_id and item_cost to retrieve only the add-ons for this product?
		$product_id = get_post_meta( $post_id, 'pewc_product_id', true );
		$item_cost = get_post_meta( $post_id, 'pewc_item_cost', true );

		foreach( $groups as $group_id => $group ) {
			// check if this group has a repeatable group
			$repeatable = get_post_meta( $group_id, 'repeatable', true );
			if ( $repeatable ) {
				// if just one group has a repeatable group, re-create the groups array that includes the cloned group
				$order_id = get_post_meta( $post_id, 'pewc_order_id', true );
				if ( $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						// get order items
						$order_items = $order->get_items();
						if ( $order_items ) {
							// loop through the items and collect the complete product_extras values
							foreach ( $order_items as $order_item_id => $order_item ) {
								$product_extras = $order_item->get_meta( 'product_extras', true );

								if ( isset( $product_extras['groups'] ) ) {
									$order_item_total = $order_item->get_total();
									$order_item_product_id = $order_item->get_product_id();
									if ( $product_id != $order_item_product_id || $item_cost != $order_item_total ) {
										continue; // this could be from a different order item, which is displayed separately, so we skip this?
									}
									foreach ( $product_extras['groups'] as $group_id => $fields ) {
										foreach ( $fields as $field_id => $field ) {
											if ( $field_id == $group_id ) {
												continue; // skip, this could be the group header
											}
											$new_group = array(
												'id' => $field['id'],
												'type' => $field['type'],
												'label' => $field['label'],
												'price' => $field['price'],
												'value' => $field['value']
											);
											// we add the order_item_id to the key to keep add-ons unique per order item/product
											$new_groups[$order_item_id . '_' . $group_id][] = $new_group;
										}
										if ( isset( $product_extras['cloned_groups'][$group_id] ) ) {
											// this order item has cloned groups, add them to the new_groups array
											foreach ( $product_extras['cloned_groups'][$group_id] as $clone_index => $cloned_fields ) {
												foreach ( $cloned_fields as $field ) {
													$new_group = array(
														'id' => $field['id'],
														'type' => $field['type'],
														'label' => $field['label'] . ' (' . $clone_index . ')', // add clone_index so that they have their own column in the Export file,
														'price' => $field['price'],
														'value' => $field['value']
													);
													$new_groups[$order_item_id . '_' . $group_id][] = $new_group;
												}
											}
										}
									} // end foreach $product_extras['groups'] loop
								}

							} // end foreach $order_items loop

							if ( ! empty( $new_groups ) ) {
								// we have our new groups array, we can stop the loop so we only do this once
								$groups = $new_groups;
								break;
							}
						}
					}
				}
			}
		}
	}
	return $groups;
}
add_filter( 'pewc_filter_metabox_aou_groups', 'pewc_filter_metabox_aou_groups', 10, 3 );

/**
 * Add a hidden input field for repeatable fields, used in pewc_is_group_visible() and pewc_get_conditional_field_visibility()
 * @since 3.25.4
 */
function pewc_add_repeatable_field_hidden_input( $item, $id, $group_layout ) {

	if ( pewc_is_repeatable_field( $item ) ) {
		echo '<input type="hidden" name="' . $item['id'] . '_is_repeatable" value="1" />';
	}

}
add_action( 'pewc_after_field_template', 'pewc_add_repeatable_field_hidden_input', 10, 3 );

/**
 * Add a class to the repeatable field, used in conditions.js
 * @since 3.26.5
 */
function pewc_add_repeatable_field_classes( $classes, $item ) {

	if ( pewc_is_repeatable_field( $item ) ) {
		$classes[] = 'pewc-repeatable-field';
		// 3.26.7
		if ( pewc_multiply_repeatable_with_quantity( $item ) ) {
			$classes[] = 'pewc-multiply-repeatable';
		}
	}
	return $classes;

}
add_filter( 'pewc_filter_single_product_classes', 'pewc_add_repeatable_field_classes', 10, 2 );

/**
 * Function for detecting repeatable fields
 * @since 3.26.5
 */
function pewc_is_repeatable_field( $item ) {

	$repeatable_fields = pewc_get_repeatable_fields();
	return ( ! empty( $item['group_is_repeatable'] ) && ! empty( $item['field_type'] ) && in_array( $item['field_type'], $repeatable_fields ) );

}

/**
 * Check if we want to multiple repeatable add-on field prices with the product quantity
 * @since 3.26.7
 */
function pewc_multiply_repeatable_with_quantity( $item ) {

	return apply_filters( 'pewc_multiply_repeatable_field_prices_with_quantity', false, $item );

}

/**
 * Display remove clone button
 * @since 3.26.18
 */
function pewc_remove_cloned_group_button( $group_id, $group, $group_index ) {

	if ( pewc_get_group_repeatable( $group_id ) && ! pewc_get_group_repeatable_by_quantity( $group_id ) ) {

		$remove_button = apply_filters( 'pewc_filter_remove_repeatable_group_button', '<a href="#" class="pewc-remove-clone">[x] ' . __( 'Remove', 'pewc' ) . '</a>', $group_id, $group, $group_index );

		echo $remove_button;

	}

}
add_action( 'pewc_open_group_inner', 'pewc_remove_cloned_group_button', 11, 3 );
