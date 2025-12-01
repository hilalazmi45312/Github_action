<?php /* Variant DropDown menu changes */
if ( hpy_fdv_wc_version_check( '3.0' ) ) {
	add_filter( 'woocommerce_product_get_default_attributes', 'hpy_fdv_default_attribute', 10, 1 );
	add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'hpy_fdv_attribute_v2', 20, 2 );
} else {
	add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'hpy_fdv_attribute_args', 10, 1 );
}



/**
 * Re-order the variations depending on the chosen option. If nothing is set default to ID.
 *
 * @param array $args
 *
 * @return array
 */
function hpy_fdv_attribute_args( $args = array() ) {
	$sortby = ! empty( get_option( 'hpy_variant_sort' ) ) ? get_option( 'hpy_variant_sort' ) : 'id';

	$product   = $args['product'];
	$attribute = strtolower( $args['attribute'] );

	$product_class = new WC_Product_Variable( $product );
	$children      = $product_class->get_visible_children();
	$i             = 0;
	if ( ! empty( $children ) ) {
		foreach ( $children as $child ) {
			$required      = 'attribute_' . $attribute;
			$meta          = get_post_meta( $child );
			$to_use        = $meta[ $required ][0];
			$product_child = new WC_Product_Variation( $child );
			$prices[ $i ]  = array( $product_child->get_price(), $to_use );
			++$i;
		}

		if ( 'price-low' === $sortby || 'price-high' === $sortby ) {
			if ( isset( $prices ) ) {
				if ( 'price-low' === $sortby ) {
					sort( $prices );
				} else {
					rsort( $prices );
				}
			}
		}
		$args['selected'] = $prices[0][1];

		$args['show_option_none'] = '';
	}

	return $args;
}

/**
 * Remove the Choose an Option text - if applicable
 *
 * @param $args
 *
 * @return array|mixed|string|string[]
 */

function hpy_fdv_attribute_v2( $args ) {
	if ( 'yes' !== get_option( 'hpy_disabled_auto_remove_dropdown' ) ) {
		$args['show_option_none'] = false;
	}

	return $args;
}

function hpy_fdv_default_attribute( $defaults ) {
	global $product;

	if ( ! $product ) {
		return $defaults;
	}

	if ( is_string( $product ) ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( empty( $product ) || is_wp_error( $product ) ) {
			return $defaults;
		}
	}

	if ( 'product' !== $product->post_type ) {
		return $defaults;
	}

	$respect         = get_option( 'hpy_variant_respect' );
	$sortby          = apply_filters( 'hpy_fdv_custom_sortby', ! empty( get_option( 'hpy_variant_sort' ) ) ? get_option( 'hpy_variant_sort' ) : 'id' );
	$thensort        = apply_filters( 'hpy_fdv_custom_then_sortby', ! empty( get_option( 'hpy_variant_then_sort' ) ) ? get_option( 'hpy_variant_then_sort' ) : 'default' );
	$hide_oos        = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
	$first_attribute = '';

	if ( 'yes' === $respect && ! empty( $defaults ) ) {
		return $defaults;
	}

	if ( empty( $sortby ) ) {
		$sortby = 'id';
	}

	if ( ! $product->is_type( 'variable' ) ) {
		return $defaults;
	}

	$children   = $product->get_children();
	$attributes = array();

	foreach ( $children as $key => $child ) {
		$_child       = wc_get_product( $child );
		$position     = array_search( $key, array_keys( $children ), true );
		$title        = $_child->get_name();
		$menu_order   = array();
		$stock_qty    = $_child->get_stock_quantity();
		$sales        = $_child->get_total_sales();
		$stock_status = $_child->is_in_stock();
		$attrs        = $_child->get_attributes();
		foreach ( $attrs as $akey => $attr ) {
			$pattrs = explode( ',', str_replace( array( ', ', ' | ', '|' ), ',', strtolower( $product->get_attribute( $akey ) ) ) );
			if ( empty( $first_attribute ) ) {
				$first_attribute = $akey;
			}
			$menu_order[ $akey ] = array_search( strtolower( $attr ), $pattrs, true );
		}

		if ( $hide_oos && ! $stock_status ) {
			// If Hide out of Stock is set, and this variant is out of stock, then skip.
			continue;
		}

		if ( 'publish' === $_child->get_status() ) {
			$attributes[] = apply_filters(
				'hpy_fdv_build_attribute_filter',
				array(
					'price'       => ! empty( $_child->get_price() ) ? $_child->get_price() : '0',
					'id'          => $_child->get_id(),
					'position'    => $position,
					'sales'       => $sales,
					'stock_level' => $stock_qty,
					'menu_order'  => $menu_order,
					'title'       => $title,
					'stock_count' => $stock_qty,
				)
			);
		}
	}

	$secondary_sort = false;

	switch ( $sortby ) {

		case 'price-low':
			$secondary_sort = true;
			$attributes     = hpy_fdv_multidimensional_sort( $attributes, 'price-low' );
			$attributes     = hpy_fdv_remove_element_by_value( $attributes, 'price', '0' );

			break;

		case 'price-high':
			$secondary_sort = true;
			$attributes     = hpy_fdv_multidimensional_sort( $attributes, 'price-high' );
			$attributes     = hpy_fdv_remove_element_by_value( $attributes, 'price', '0' );
			break;

		case 'position':
			$attributes = hpy_fdv_multidimensional_sort( $attributes, 'position' );
			break;

		case 'id':
			$attributes = hpy_fdv_multidimensional_sort( $attributes, 'id' );
			break;

		case 'menu_order':
			$attributes = hpy_fdv_multidimensional_sort( $attributes, 'menu_order', $first_attribute );
			break;

		case 'alphabetically':
			$attributes = hpy_fdv_multidimensional_sort( $attributes, 'alphabetically' );
			break;

		case 'stock':
			$attributes = hpy_fdv_multidimensional_sort( $attributes, 'stock' );
			break;

		default:
			$secondary_sort = apply_filters( 'hpy_fdv_do_secondary_sort', true );
			$attributes     = apply_filters( 'hpy_fdv_trigger_sort', $attributes );
			break;

	}

	if ( empty( $attributes ) ) {
		return $defaults;
	}

	if ( $secondary_sort ) {
		$attributes = hpy_fdv_secondary_sort( $attributes, $thensort, $sortby );
	}

	$stock_status = array();

	$count = count( $attributes );
	for ( $i = 0; $i < $count; $i++ ) {
		$_prod = wc_get_product( $attributes[ $i ]['id'] );

		$stock_limit = get_option( 'hpy_variant_stockLimit' );

		if ( ! empty( $stock_limit ) ) {
			$stock_qty = $_prod->get_stock_quantity();
			if ( $stock_qty < $stock_limit && ! is_null( $stock_qty ) ) {
				$stock = 'outofstock';
			} else {
				$stock = $_prod->get_stock_status();
			}
		} else {
			$stock = $_prod->get_stock_status();
		}

		if ( 'outofstock' === $stock ) {
			$stock_status[ $i ] = 'outofstock';
		} else {
			$stock_status[ $i ] = 'instock';
		}
	}

	if ( count( array_unique( $stock_status ) ) > 1 && count( array_unique( $stock_status ) ) < count( $attributes ) ) {
		foreach ( $stock_status as $key => $value ) {
			if ( 'outofstock' === $value ) {
				unset( $attributes[ $key ] );
			}
		}
	}

	$attributes = array_values( $attributes );

	$_prod = ! empty( $attributes[0]['id'] ) ? wc_get_product( $attributes[0]['id'] ) : false;

	if ( empty( $_prod ) ) {
		return apply_filters( 'hpy_fdv_attributes_return', $defaults );
	}

	$attr = hpy_fdv_populate_empty_attributes( $_prod->get_attributes(), $_prod );

	$defaults = array();

	foreach ( $attr as $key => $value ) {
		if ( ! empty( $value ) ) {
			$defaults[ $key ] = $value;
		}
	}

	return apply_filters( 'hpy_fdv_attributes_return', $defaults );
}

function hpy_fdv_remove_element_by_value( $array, $key, $value ) {
	foreach ( $array as $subKey => $subArray ) {
		if ( $subArray[ $key ] == $value ) {
			unset( $array[ $subKey ] );
		}
	}
	return $array;
}

function hpy_fdv_secondary_sort( $attributes, $sortby, $origial_sort ) {

	$attribute_split = array();
	foreach ( $attributes as $akey => $avalue ) {
		$attribute_split[ $avalue['price'] ][] = $avalue;
	}

	foreach ( $attribute_split as $skey => $split ) {
		switch ( apply_filters( 'hpy_fdv_secondary_sort_switch', $sortby ) ) {

			// Sort using the Secondary filter - Currently defaults to Position, so don't change anything if set to Position
			case 'then_sales':
				$split = hpy_fdv_multidimensional_sort( $split, 'sales' );
				break;

			case 'then_id':
				$split = hpy_fdv_multidimensional_sort( $split, 'id' );
				break;

			case 'then_stock':
				$split = hpy_fdv_multidimensional_sort( $split, 'stock' );
				break;

			default:
				$split = apply_filters( 'hpy_fdv_trigger_sort', $split );
				break;

		}

		$attribute_split[ $skey ] = $split;
	}

	$attributes = hpy_fdv_array_flatten( $attribute_split );

	return apply_filters( 'hpy_fdv_secondary_sort_filter', $attributes );
}

function hpy_fdv_array_flatten( $array ) {
	if ( ! is_array( $array ) ) {
		return false;
	}
	$result = array();
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$result = array_merge( $result, $value );
		} else {
			$result[ $key ] = $value;
		}
	}
	return $result;
}

function hpy_fdv_populate_empty_attributes( $attributes, $product ) {

	foreach ( $attributes as $a_key => $a_value ) {
		if ( empty( $a_value ) ) {
			$parent_id = wc_get_product( $product->get_id() )->get_parent_id();
			if ( strpos( $a_key, 'pa_' ) !== false ) {
				$attrs = wc_get_product_terms( $parent_id, $a_key, array( 'fields' => 'names' ) );
			} else {
				$attrs = hpy_fdv_get_product_attributes( $parent_id, $a_key );
			}
			$attr = array_shift( $attrs );

			if ( ! empty( $attr ) ) {
				$attributes[ $a_key ] = strtolower( str_replace( ' ', '_', $attr ) );
			}
		}
	}

	return apply_filters( 'hpy_fdv_empty_attribute_return', $attributes );
}

function hpy_fdv_get_product_attributes( $product_id, $a_key ) {
	$attributes = get_post_meta( $product_id, '_product_attributes', true )[ $a_key ];

	$attribute_array = array();
	if ( ! empty( $attributes['value'] ) ) {
		$attribute_array = explode( '|', str_replace( ' | ', '|', $attributes['value'] ) );
	}

	return $attribute_array;
}

function hpy_fdv_multidimensional_sort( $array, $check, $attribute = '' ) {

	if ( 'price-low' === $check ) {
		usort( $array, 'hpy_fdv_sort_by_price' );
	} elseif ( 'price-high' === $check ) {
		usort( $array, 'hpy_fdv_sort_by_price_high' );
	} elseif ( 'position' === $check ) {
		usort( $array, 'hpy_fdv_sort_by_position' );
	} elseif ( 'menu_order' === $check ) {
		usort(
			$array,
			function ( $a, $b ) use ( $attribute ) {
				return $a['menu_order'][ $attribute ] - $b['menu_order'][ $attribute ];
			}
		);
	} elseif ( 'alphabetically' === $check ) {
		usort( $array, 'hpy_fdv_sort_alphabetically' );
	} elseif ( 'stock' === $check ) {
		usort( $array, 'hpy_fdv_sort_stock' );
	} else {
		usort( $array, 'hpy_fdv_sort_by_id' );
	}

	return apply_filters( 'hpy_fdv_sort_filter', $array );
}

function hpy_fdv_sort_by_price( $a, $b ) {
	return $a['price'] - $b['price'];
}

function hpy_fdv_sort_by_price_high( $a, $b ) {
	return $b['price'] - $a['price'];
}

function hpy_fdv_sort_by_position( $a, $b ) {
	return $a['position'] - $b['position'];
}

function hpy_fdv_sort_alphabetically( $a, $b ) {
	return strcmp( $a['title'], $b['title'] );
}

function hpy_fdv_sort_stock( $a, $b ) {
	if ( $b['stock_count'] == $a['stock_count'] ) {
		return 0;
	}
	return $b['stock_count'] < $a['stock_count'] ? -1 : 1;
}

function hpy_fdv_sort_by_attribute( $a, $b, $attribute ) {
	return $a['id'][ $attribute ] - $b['id'][ $attribute ];
}

function hpy_fdv_sort_by_id( $a, $b ) {
	return $a['id'] - $b['id'];
}

function hpy_fdv_get_attribute_menu_order( $child, $parent ) {
	$attributes = $child->get_attributes();

	$_primary = false;
	foreach ( $attributes as $key => $value ) {
		// Check for Primary Attribute. If not set, or multiple set, use first available Attribute.
		$primary = get_post_meta( $parent->get_id(), 'attribute_' . $key . '_primary', true );
		if ( $primary ) {
			$_primary = $key;
			break;
		}
	}

	if ( ! $_primary ) {
		$_primary = array_key_first( $attributes );
	}

	$_atts = explode( ', ', $parent->get_attribute( $_primary ) );

	return array_search( $attributes[ $_primary ], $_atts, true );
}

add_filter( 'woocommerce_hide_invisible_variations', 'hpy_fdv_hide_invisible_variants' );
function hpy_fdv_hide_invisible_variants() {
	return apply_filters( 'hpy_fdv_hide_unavailable_variants', true );
}
