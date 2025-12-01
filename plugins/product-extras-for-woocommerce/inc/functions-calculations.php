<?php
/**
 * Functions for calculations
 * @since 3.5.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set our look up tables for calculation fields
 */
function pewc_calculation_look_up_tables() {

	if( ! is_product() && apply_filters( 'pewc_calculation_look_up_tables_product_page_only', true ) ) {
		return;
	}

	$tables = apply_filters( 'pewc_calculation_look_up_tables', array() );
	$fields = apply_filters( 'pewc_calculation_look_up_fields', array() ); ?>

		<script>
		var pewc_look_up_tables = <?php echo json_encode( $tables ); ?>;
		var pewc_look_up_fields = <?php echo json_encode( $fields ); ?>;
		</script>

	<?php

}
add_action( 'wp_head', 'pewc_calculation_look_up_tables' );

/**
 * Add additional values like formula for calculation fields
 * @since 3.11.4
 */
function pewc_update_cart_item_data_for_calc_fields( $cart_item_data, $item, $group_id, $field_id, $value ) {

	if ( ! pewc_enabled_calc_in_cart() ) return $cart_item_data;

	if ( $item['field_type'] == 'calculation' && ! empty( $item['formula'] ) && false !== strpos( $item['formula'], '{' ) && false !== strpos( $item['formula'], '}' ) ) {
		// add the field's formula to the cart item, in case we need to update price
		$cart_item_data['product_extras']['groups'][$group_id][$field_id]['formula'] = $item['formula'];
		$cart_item_data['product_extras']['groups'][$group_id][$field_id]['formula_action'] = $item['formula_action'];
		$cart_item_data['product_extras']['groups'][$group_id][$field_id]['decimal_places'] = $item['decimal_places'];

		// from templates/frontend/calculation.php
		preg_match_all( "|{field_(.*)}|U", $item['formula'], $all_fields, PREG_PATTERN_ORDER );
		if( ! empty( $all_fields[1]) ) {
			$cart_item_data['product_extras']['groups'][$group_id][$field_id]['all_fields'] = $all_fields[1]; // array?
		}
	
		if ( '{look_up_table}' === $item['formula'] ) {
			// add the axes and source table
			$cart_item_data['product_extras']['groups'][$group_id][$field_id]['x_input'] = ! empty( $item['x_input'] ) ? $item['x_input'] : '';
			$cart_item_data['product_extras']['groups'][$group_id][$field_id]['y_input'] = ! empty( $item['y_input'] ) ? $item['y_input'] : '';
			$cart_item_data['product_extras']['groups'][$group_id][$field_id]['look_up_table'] = ! empty( $item['look_up_table'] ) ? $item['look_up_table'] : '';
		}

		if ( false !== strpos( $item['formula'], '{quantity}' ) ) {
			// we'll use this so that we know if an item has a calculation that needs updating
			if ( isset( $cart_item_data['product_extras']['quantity_dependent_calc_fields'] ) ) {
				$qdcf = $cart_item_data['product_extras']['quantity_dependent_calc_fields'];
			} else {
				$qdcf = array();
			}
			$qdcf[] = $field_id;
			$cart_item_data['product_extras']['quantity_dependent_calc_fields'] = $qdcf;
		}

	}

	return $cart_item_data;
}
add_filter( 'pewc_filter_end_add_cart_item_data', 'pewc_update_cart_item_data_for_calc_fields', 10, 5);

/**
 * If quantity is updated, recalculate calculation fields that depend on the quantity
 * @since 3.11.4
 */
function pewc_recalculate_calculation_fields_in_cart( $cart_item_key, $quantity, $old_quantity ) {

	if ( ! pewc_enabled_calc_in_cart() ) return; // do nothing

	$cart = WC()->cart->get_cart();

	if( empty( $cart[$cart_item_key]['product_extras']['quantity_dependent_calc_fields'] ) || $quantity == $old_quantity || $quantity < 1 ) {
		return; // do nothing
	}

	$cart_item = $cart[$cart_item_key];
	$product_extras = $cart_item['product_extras'];

	if ( empty( $product_extras['groups'] ) || ! is_array( $product_extras['groups'] ) ) {
		return; // product extras groups are empty, so we have nothing to loop through
	}

	// we need to recalculate the price because a calculation field depends on the quantity
	// in pewc.js, field values are replaced first (e.g. field_123, field_123_option_price, etc), then those inside brackets {}

	$groups = $product_extras['groups'];
	$original_price = $product_extras['original_price'];
	$add_on_price = 0;
	// quantity dependent calc fields is an array of field_ids
	$qdcf = $product_extras['quantity_dependent_calc_fields'];

	$other_values = array(); // save other add-on field values here (i.e. non-calc, global vars, etc)

	// set up global values
	$pewc_global_values = array(
		'quantity' => $quantity
	);

	// product values
	$pewc_global_values['product_price'] = $original_price;
	$pewc_global_values['product_width'] = $cart_item['data']->get_width();
	$pewc_global_values['product_length'] = $cart_item['data']->get_length();
	$pewc_global_values['product_height'] = $cart_item['data']->get_height();
	$pewc_global_values['product_weight'] = $cart_item['data']->get_weight();

	// AOU vars
	$pewc_global_values['variable_1'] = get_option( 'pewc_variable_1', 0 );
	$pewc_global_values['variable_2'] = get_option( 'pewc_variable_2', 0 );
	$pewc_global_values['variable_3'] = get_option( 'pewc_variable_3', 0 );

	// AOU global calc vars (custom vars by customer)
	$global_calc_vars = apply_filters( 'pewc_calculation_global_calculation_vars', false );
	if ( ! empty( $global_calc_vars ) && is_array( $global_calc_vars ) && count( $global_calc_vars ) > 1 ) {
		$pewc_global_values['global_calc_vars'] = $global_calc_vars;
	}

	// save global values inside $other_values so that we can pass them inside our function
	$other_values['pewc_global_values'] = $pewc_global_values;

	// save all fields in a "flat" array so that a field can access the value of another field in a different group
	// also, get all calculation components, as inspired by pewc_get_all_calculation_components()
	// we cannot use pewc_get_all_calculation_components() because the array structure is different
	$fields = array();
	$calc_components = array();
	foreach ( $groups as $group_id => $field ) {
		foreach ( $field as $field_id => $item ) {
			$fields[$field_id] = $item;

			// let's try to get calculation components
			if ( $item['type'] == 'calculation' ) {
				if ( '{look_up_table}' === $item['formula'] ) {
					if ( ! empty( $item['x_input'] ) ) {
						$component_id = $item['x_input'];
						if ( isset( $calc_components[$component_id] ) ) {
							$calc_components[$component_id][] = $field_id;
						}
						else {
							$calc_components[$component_id] = array( $field_id );
						}
					}
					if ( ! empty( $item['y_input'] ) ) {
						$component_id = $item['y_input'];
						if ( isset( $calc_components[$component_id] ) ) {
							$calc_components[$component_id][] = $field_id;
						}
						else {
							$calc_components[$component_id] = array( $field_id );
						}
					}
				}
				else if ( ! empty( $item['all_fields']) && is_array( $item['all_fields'] ) ) {
					foreach ( $item['all_fields'] as $component_id ) {
						$component_id = str_replace( array( '_option_price', '_field_price' ), '', $component_id );
						if ( isset( $calc_components[$component_id] ) ) {
							$calc_components[$component_id][] = $field_id;
						}
						else {
							$calc_components[$component_id] = array( $field_id );
						}
					}
				}
			}
		}
	}

	$all_replaced = false;
	$counter = 0;

	// Loop through only our quantity-dependent calc fields, then use the calc components array to find other fields affected by the change
	// pewc_evaluate_calc_field_formula() is recursive
	foreach ( $qdcf as $field_id ) {
		if ( isset( $fields[$field_id] ) ) {
			if ( ! empty( $calc_components[$field_id] ) ) {
				// this calc field triggers another calc field, so add that to our array
				$fields[$field_id]['triggers'] = $calc_components[$field_id];
			}
			list( $fields, $other_values, $all_replaced ) = pewc_evaluate_calc_field_formula( $fields, $field_id, $other_values );
		}
	}

	if ( ! $all_replaced && function_exists('pewc_error_log') ) {
		// let's log this for now in case we need to debug
		$error_log = print_r($fields, true);
		pewc_error_log( $error_log );
	}

	// even if not all formulas have been replaced, save the updated $groups and get the updated price as well
	$calc_set_price = 0;
	foreach ( $fields as $field_id => $item ) {
		if ( ! empty( $item['price'] ) ) {
			if ( 'calculation' === $item['type'] && 'price' === $item['formula_action'] ) {
				$calc_set_price = $item['price'];
			}
			else {
				$add_on_price += $item['price'];
			}
		}
		if ( isset( $item['evaluated_formula'] ) ) {
			// unset this for future updating of cart
			unset( $item['evaluated_formula'] );
		}

		// save the updated group
		$groups[$item['group_id']][$field_id] = $item;
	}

	// save updated groups
	$product_extras['groups'] = $groups;

	if ( ! empty( $product_extras['use_calc_set_price'] ) ) {
		$product_extras['price_with_extras'] = $calc_set_price;
	}
	else {
		$product_extras['price_with_extras'] = $original_price + $add_on_price;
	}

	// update product_extras in cart
	WC()->cart->cart_contents[$cart_item_key]['product_extras'] = $product_extras;

}
add_action( 'woocommerce_after_cart_item_quantity_update', 'pewc_recalculate_calculation_fields_in_cart', 10, 3 );

/**
 * Evaluates a single calc field, recursive
 * To-do: ACF fields
 * Look Up Table - issue if one of the axis is referencing another calc field
 * @since 3.11.4
 */
function pewc_evaluate_calc_field_formula( $fields, $field_id, $other_values, $all_replaced = true, $traversed = array() ) {

	$item = $fields[$field_id];

	if ( empty( $item['evaluated_formula'] ) ) {
		$item['evaluated_formula'] = $item['formula']; // start with the original
	}

	if ( pewc_formula_is_evaluated( $item['evaluated_formula'] ) ) {
		return array( $fields, $other_values, $traversed ); // the formula has been evaluated thoroughly, so we can skip this now
	}
	else if ( count( $traversed ) > 2 ) {
		return array( $fields, $other_values, $traversed ); // prevent infinite loop?
	}

	if ( '{look_up_table}' === $item['formula'] && ! pewc_formula_is_evaluated( $item['evaluated_formula'] ) ) {
		// evaluate look up table... this should return evaluated_formula with the value found in the look up table
		// all_fields and pewc_global_values should be skipped, and we go directly to the bottom part of this functiom
		$item = pewc_evaluate_look_up_table( $item, $fields );
	}

	if ( ! empty( $item['all_fields'] ) && is_array( $item['all_fields'] ) ) {
		foreach ( $item['all_fields'] as $field_id2 ) {

			// reset vars
			$the_value = false;

			if ( isset( $other_values[$field_id2] ) ) {
				// a value has been set already for this, use that value. can be 0
				$the_value = $other_values[$field_id2];
			}
			else {
				// a value has not been set yet, get the value directly from $groups, and also save it in $other_values
				if ( strpos( $field_id2, '_option_price' ) !== false ) {

					$clean_field_id = str_replace( '_option_price', '', $field_id2 );
					if ( ! empty( $fields[$clean_field_id]['value_without_price']) ) {
						// value_without_price is usually used by fields with options, because 'value' would contain an HTML price
						$the_value = $fields[$clean_field_id]['value_without_price'];
					}
					else $the_value = 0;

				} else if ( strpos( $field_id2, '_field_price' ) !== false ) {

					$clean_field_id = str_replace( '_field_price', '', $field_id2 );
					if ( ! empty( $fields[$clean_field_id]['price'] ) ) {
						$the_value = $fields[$clean_field_id]['price'];
					}
					else $the_value = 0;

				} else if ( strpos( $field_id2, '_number_uploads' ) !== false ) {

					$clean_field_id = str_replace( '_number_uploads', '', $field_id2 );
					$the_value = 0; // default to 0
					if ( isset( $fields[$clean_field_id] ) ) {
						$tmp_field = $fields[$clean_field_id];
						if ( $tmp_field['type'] == 'upload' && ! empty( $tmp_field['files'] ) && is_array( $tmp_field['files'] ) ) {
							$the_value = count( $tmp_field['files'] );
						}
					}

				} else if ( isset( $fields[$field_id2] ) ) {

					$tmp_field = $fields[$field_id2];
					if ( $tmp_field['type'] == 'calculation' && ( empty( $tmp_field['evaluated_formula'] ) || ! pewc_formula_is_evaluated( $tmp_field['evaluated_formula'] ) ) ) {
						// this is a calc field whose value hasn't been evaluated yet, so go deeper?
						if ( ! in_array( $field_id, $traversed ) ) {
							// we haven't passed this field, so ok to go deeper?
							list( $fields, $other_values, $all_replaced ) = pewc_evaluate_calc_field_formula( $fields, $field_id2, $other_values, $all_replaced, array_merge( $traversed, array( $field_id ) ) );
							// try again?
							$tmp_field = $fields[$field_id2];
							if ( ! empty( $tmp_field['evaluated_formula'] ) && pewc_formula_is_evaluated( $tmp_field['evaluated_formula'] ) ) {
								$the_value = $tmp_field['value']; // this is fully evaluated
							} else {
								// don't know what to do yet, but prevent infinite loop
							}
						} else {
							// don't know what to do yet, but prevent infinite loop
						}
					}
					else if ( ! empty( $tmp_field['value'] ) && is_numeric( $tmp_field['value'] ) ) {
						// this is a non-calc field, so safe to get the current value
						$the_value = $tmp_field['value'];
					}

				} else {
					// field was not found, default to zero
					$the_value = 0;
				}

				if ( false !== $the_value ) {
					$other_values[$field_id2] = $the_value; // save for later use
				}
			}

			if ( false !== $the_value ) {
				// replace it on the formula
				$item['evaluated_formula'] = str_replace( '{field_'.$field_id2.'}', $the_value, $item['evaluated_formula'] );
			} else {
				// value is still false
			}
		}
	}

	// now replace global values like quantity
	if ( is_array( $other_values['pewc_global_values'] ) && ! pewc_formula_is_evaluated( $item['evaluated_formula'] ) ) {
		foreach ( $other_values['pewc_global_values'] as $gkey => $gvalue ) {
			if ( ! is_array( $gvalue ) ) {
				$item['evaluated_formula'] = str_replace( '{'.$gkey.'}', $gvalue, $item['evaluated_formula'] );

			} else if ( $gkey == 'global_calc_vars' ) {
				// loop through the global_calc_vars, custom vars by customer using the filter "pewc_calculation_global_calculation_vars"
				foreach ( $gvalue as $gkey2 => $gvalue2 ) {
					$item['evaluated_formula'] = str_replace( '{'.$gkey2.'}', $gvalue2, $item['evaluated_formula'] );
				}
			}
		}
	}

	// check if evaluated formula still has unreplaced values
	if ( ! pewc_formula_is_evaluated( $item['evaluated_formula'] ) ) {
		$all_replaced = false; // set this to false
	}
	else {
		// all values in the formula has been replaced, update value?
		// to-do: what if this calc field only references number fields, could be a waste of computation
		include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';
		$eval_value = WC_Eval_Math::evaluate( $item['evaluated_formula'] );
		// set decimal place if it exists
		if ( ! empty( $item['decimal_places'] ) ) {
			$eval_value = wc_format_decimal( $eval_value, $item['decimal_places'] );
		}
		$item['value'] = $eval_value;
		if ( ! empty($item['formula_action']) && ( $item['formula_action'] == 'cost' || $item['formula_action'] == 'price' ) ) {
			$item['price'] = $eval_value;
		}
		$other_values[$field_id] = $eval_value; // save this new value

		if ( ! empty( $item['triggers'] ) ) {
			// save this now so that the triggered fields can get the updated values
			$fields[$field_id] = $item;
			// this field triggers another field, so update those too...
			foreach ( $item['triggers'] as $field_id2 ) {
				if ( ! in_array( $field_id, $traversed ) ) {
					// we haven't passed this field, so ok to go deeper?
					list( $fields, $other_values, $all_replaced ) = pewc_evaluate_calc_field_formula( $fields, $field_id2, $other_values, $all_replaced, array_merge( $traversed, array( $field_id ) ) );
				} else {
					// don't know what to do yet, but prevent infinite loop
				}
			}
		}
	}

	$fields[$field_id] = $item;

	return array( $fields, $other_values, $all_replaced );
}

/**
 * Evaluate a look up table calculation field
 * @since 3.11.4
 */
function pewc_evaluate_look_up_table( $item, $fields ) {

	$null_signifier = apply_filters( 'pewc_look_up_table_null_signifier', '*' );
	$x_input = ! empty( $item['x_input'] ) ? $item['x_input'] : '';
	$y_input = ! empty( $item['y_input'] ) ? $item['y_input'] : '';
	$look_up_table = ! empty( $item['look_up_table'] ) ? $item['look_up_table'] : '';

	if ( empty ( $look_up_table ) ) {
		return $item;
	}

	$tables = apply_filters( 'pewc_calculation_look_up_tables', array() );

	if ( empty( $tables ) || ! isset( $tables[$look_up_table] ) || ! is_array( $tables[$look_up_table] ) ) {
		// table was not found, just go back
		return $item;
	}

	$table = $tables[$look_up_table];

	$x_field = isset( $fields[$x_input] ) ? $fields[$x_input] : false;
	$y_field = isset( $fields[$y_input] ) ? $fields[$y_input] : false;

	// to-do: maybe do recursive function again if x or y are not evaluated yet?
	if ( $x_field && $x_field['type'] == 'calculation' && ( empty( $x_field['evaluated_formula'] ) || ( ! empty( $x_field['evaluated_formula'] ) && ! pewc_formula_is_evaluated( $x_field['evaluated_formula'] ) ) ) ) {
		// the source field for x is not evaluated yet, so go back for now?
		return $item;
	}
	if ( $y_field && $y_field['type'] == 'calculation' && ( empty( $y_field['evaluated_formula'] ) || ( ! empty( $y_field['evaluated_formula'] ) && ! pewc_formula_is_evaluated( $y_field['evaluated_formula'] ) ) ) ) {
		// the source field for y is not evaluated yet, so go back for now?
		return $item;
	}
	if ( $x_field && $y_field && $x_field['type'] != 'calculation' && $y_field['type'] != 'calculation' ) {
		// these look up table's axes are not referencing any calc fields, so maybe no chance the value has changed, so just evaluate to the current value?
		$item['evaluated_formula'] = $item['value'];
		return $item;
	}

	// default values for x and y, in case we can't find a value?
	$x_value = 0;
	$y_value = 0;

	if ( $x_field && isset( $x_field['value'] ) ) {
		$x_value = $x_field['value'];
	}
	if ( $y_field && isset( $y_field['value'] ) ) {
		$y_value = $y_field['value'];
	}

	if ( isset( $table[$x_value][$y_value] ) ) {
		// we already found our value, so update already and we don't need to proceed with the others anymore
		$item['value'] = $table[$x_value][$y_value];
		$item['evaluated_formula'] = $item['value'];
		return $item;
	}

	if ( isset( $table[$x_value] ) ) {
		// x was found in the table
		$x_axis = $table[$x_value];
	} else {
		// x was not found in the table
		$x_index = pewc_find_nearest_index_look_up_table( $x_value, $table );
		if ( isset( $table[$x_index] ) ) {
			$x_axis = $table[$x_index];
		}
	}

	if ( ! isset( $x_axis ) ) {
		// we can't find an x-axis for this somehow, so log for now then return
		pewc_error_log( $error_log );
		return $item;
	}

	if ( isset( $x_axis[$y_value] ) ) {
		// y was found on the x-axis, so we have a value
		$look_up_value = $x_axis[$y_value];
	} else {
		// y was not found on the x-axis
		$y_index = pewc_find_nearest_index_look_up_table( $y_value, $x_axis );
		if ( isset( $x_axis[$y_index] ) ) {
			$look_up_value = $x_axis[$y_index];
		}
	}

	if ( ! isset( $look_up_value ) || $look_up_value == $null_signifier ) {
		// just return again for now?
		return $item;
	}

	// now evaluate
	$item['evaluated_formula'] = $look_up_value;

	return $item;
}

/**
 * Find the nearest index for the look up table. Inspired by the one in pewc.js
 * @since 3.11.4
 */
function pewc_find_nearest_index_look_up_table( $value, $array ) {
	$keys = array_keys( $array );

	// do we need to sort the keys?

	if ( $value <= $keys[0] ) {
		$x_index = $keys[0];
	} else {
		// find the index
		for ( $i = 0; $i < count($keys); $i++ ) {
			if ( $value > $keys[$i] && isset( $keys[$i+1] ) && $value <= $keys[$i+1] ) {
				// Find the first key that is greater than the value passed in
				return $keys[$i+1];
			}
		}
		if ( $keys[count($keys)-1] == 'max') {
			return 'max';
		}
		// fallback?
		$x_index = $keys[count($keys)-1];
	}

	return $x_index;
}

/**
 * Check if the formula is fully evaluated, that is, no more variables in brackets
 * @since 3.11.4
 */
function pewc_formula_is_evaluated( $formula ) {
	if ( strpos( $formula, '{' ) === false && strpos( $formula, '}' ) === false) 
		return true;
	else return false;
}

/**
 * Check if calculation in the cart (if quantity is updated) is enabled. We do this for now while we're in beta?
 * @since 3.11.4
 */
function pewc_enabled_calc_in_cart() {
	return apply_filters( 'pewc_enable_calc_in_cart', false );
}
