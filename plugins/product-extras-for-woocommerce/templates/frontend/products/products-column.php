<?php
/**
 * A products field template for the column layout
 * @since 2.6.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! pewc_is_pro() ) {
	return;
}

/**
 * @hooked pewc_enqueue_variations_scripts
 */
do_action( 'pewc_products_column_layout' );

$checkboxes_wrapper_classes = array(
	'pewc-column-wrapper',
	'child-product-wrapper'
);
//$manage_stock = false; // commented out on 3.21.4, added inside the loop instead

if( ! empty( $item['products_quantities'] ) ) {
	$products_quantities = ! empty( $item['products_quantities'] ) ? $item['products_quantities'] : '';
	$checkboxes_wrapper_classes[] = 'products-quantities-' . $item['products_quantities'];
}

/*
 * This fixes a specific bug when editing a product using the Column layout
 * The function "pewc_product_extra_fields" in functions-single-product.php has a similar process, but it does not consider the parent field ID,
 * and $quantity_field_values does not seem to get passed down to the pewc_field function, which is where this file is loaded
 */

// This is moved here so that Wishlists Ultimate can use it
$selected_variations = array();

if ( ! empty( $_GET['pewc_key'] ) && pewc_user_can_edit_products() ) {
	// we are editing an item in the cart, we need to loop through the cart to get the child product's quantity
	// check first if maybe we have saved this in a session already
	$cart_key = $_GET['pewc_key'];
	$session_key = 'pewc_child_products_products_column_'.$cart_key;
	$child_product_values = WC()->session->get( $session_key );

	if ( ! $child_product_values ) {
		// session doesn't exist yet, so this must be the first add-on field. retrieve the cart now
		$tmp_cart = WC()->cart->get_cart();
		if ( isset( $tmp_cart[$cart_key] ) ) {
			// this exists in the cart, so continue
			// get parent field ID so that we only get the correct children
			if ( isset( $tmp_cart[$cart_key]['product_extras']['products']['parent_field_id'] ) )
				$parent_field_id = $tmp_cart[$cart_key]['product_extras']['products']['parent_field_id'];

			if ( isset( $parent_field_id ) ) {
				// now loop through the cart to find the child products
				$child_product_values = array();

				foreach( $tmp_cart as $tmp_key => $tmp_item ) {
					if ( isset( $tmp_item['product_extras']['products']['child_field'] ) &&
						$tmp_item['product_extras']['products']['child_field'] &&
						isset( $tmp_item['product_extras']['products']['parent_field_id'] ) &&
						$tmp_item['product_extras']['products']['parent_field_id'] == $parent_field_id ) {

						// this is a child field, save the quantity and selected variation_id to be used later
						$child_product_values[ $tmp_item['product_extras']['products']['field_id'] ][ $tmp_item['product_id'] ] = array(
							'quantity' => $tmp_item['quantity'],
							'variation_id' => isset( $tmp_item['variation_id'] ) ? $tmp_item['variation_id'] : 0
						);
					}
				}

				WC()->session->set( $session_key, $child_product_values );
			}
		}
	}

	$quantity_field_values = array();
	//$selected_variations = array();

	if ( ! empty( $child_product_values ) ) {
		if ( isset( $child_product_values[$id] ) && is_array( $child_product_values[$id] )  ) {
			// set the quantity field values and selected vars
			foreach ( $child_product_values[$id] as $cid => $arr ) {
				$quantity_field_values[$cid] = $arr['quantity'];
				if ( isset( $arr['variation_id'] ) )
					$selected_variations[$cid] = $arr['variation_id'];
			}
		}
	}
}

// since 3.15.1, filter used by Wishlists Ultimate
$selected_variations = apply_filters( 'pewc_products_column_selected_variations', $selected_variations, $product );
?>

<div class="<?php echo join( ' ', $checkboxes_wrapper_classes ); ?>" data-products-quantities="<?php echo esc_attr( $item['products_quantities'] ); ?>">

<?php if( $item['child_products'] ) {

	// 3.26.0
	$default_child_products = pewc_get_default_child_products( $item['field_default'] );
	$selected_variant_id = 0;

	// 3.26.3
	$thumbnail_size = pewc_get_swatch_thumbnail_size( $item );

	foreach( $item['child_products'] as $child_product_id ) {
		$manage_stock = false; // 3.21.4, always reset per child product

		$wrapper_classes = array(
			'pewc-checkbox-image-wrapper',
			'pewc-radio-checkbox-image-wrapper',
			'pewc-checkbox-wrapper'
		);

		$value = apply_filters( 'pewc_default_product_column_value_before_checked', $value, $id, $item, $child_product_id );
		$checked = ( $value == $child_product_id || ( is_array( $value ) && in_array( $child_product_id, $value ) ) || in_array( $child_product_id, $default_child_products ) ) ? 'checked="checked"' : '';

		if( $checked ) {
			$wrapper_classes[] = 'checked';
		}

		$child_product = wc_get_product( $child_product_id );
		if( ! is_object( $child_product ) || get_post_status( $child_product_id ) != 'publish' ) {
			continue;
		}

		$variant_wrapper = '';

		if( $child_product->is_type( 'variation' ) ) {

			$excerpt = sprintf(
				'<div class="pewc-column-excerpt"><p>%s</p></div>',
				$child_product->get_description()
			);

		} else {

			$excerpt = sprintf(
				'<div class="pewc-column-excerpt"><p>%s</p></div>',
				get_the_excerpt( $child_product_id )
			);

		}

		$max = '';
		$first_variant_id = false; // 3.26.11, used for the quantity field

		if( $child_product->get_type() == 'variable' ) {

			$variants = apply_filters( 'pewc_product_column_variants', $child_product->get_children(), $child_product, $item );

			if( $variants ) {

				$available_variations = $child_product->get_available_variations();

				// 3.26.4
				$variation_classes = array( 'pewc-variable-child-select' );
				$replace_thumbnail = pewc_column_layout_replace_thumbnail( $item, $child_product_id );
				if ( $replace_thumbnail ) {
					$variation_classes[] = 'pewc-replace-variable-image';
				}

				$variant_wrapper = sprintf(
					'<select name="%s" id="%s" class="%s" data-product_variations="%s">',
					'pewc_child_variants_' . esc_attr( $id ) . '_' . $child_product_id,
					'pewc_child_variants_' . esc_attr( $id ) . '_' . $child_product_id,
					implode( ' ', $variation_classes ),
					htmlspecialchars( wp_json_encode( $available_variations ) )
				);
				foreach( $variants as $variant_id ) {
					$variant = wc_get_product( $variant_id );
					$variant_price = pewc_maybe_include_tax( $variant, $variant->get_price() );

					// Check for discounts
					if( ! empty( $item['child_discount'] ) && ! empty( $item['discount_type'] ) ) {
						$variant_price = pewc_get_discounted_child_price( $variant_price, $item['child_discount'], $item['discount_type'] );
					}

					// 3.26.3, let's use a single variable for the attributes
					$option_attributes = '';

					// Check stock availability
					//$disabled = '';
					if( ! $variant->is_purchasable() || ( ! $variant->is_in_stock() && ! $variant->backorders_allowed() ) ) {
						//$disabled = 'disabled';
						$option_attributes .= 'disabled="disabled" ';
					}

					// 3.21.3, moved here so that $max can be taken from the selected variation (e.g. when Enable editing is checked in the settings)
					$selected = '';
					if ( ( isset( $selected_variations[$child_product_id] ) && $selected_variations[$child_product_id] == $variant_id ) || in_array( $variant_id, $default_child_products ) ) {
						$selected = 'selected="selected"'; // this is used later
						$option_attributes .= $selected . ' ';
						$selected_variant_id = $variant_id;

						if ( ! in_array( 'checked', $wrapper_classes ) ) {
							$wrapper_classes[] = 'checked';
						}
					}

					// Check available stock if stock is managed
					$available_stock = '';
					//$max = ''; // 3.21.3, incorrectly resets $max, this is already done in line 141. Remove later
					if( $variant->managing_stock() ) {
						$manage_stock = true;
						$available_stock = $variant->get_stock_quantity();
						if( ( $available_stock > 0 && '' === $max ) || '' !== $selected ) {
							// 3.19.2, only do this if $max is blank. This is added to the independent quantity field, so we need to take the value of the first variant
							// 3.21.3, also use the max stock of the selected variation
							$max = ' max="' . $available_stock . '"';
						}
					}

					// 3.26.3, get variation image
					if ( $replace_thumbnail ) {
						$variant_image = wp_get_attachment_image_url( $variant->get_image_id(), $thumbnail_size );
						if ( empty( $variant_image ) ) {
							$variant_image = wc_placeholder_img_src( $thumbnail_size );
						}
						$option_attributes .= 'data-variation-image="' . esc_attr( $variant_image ) . '" ';
					}

					// Write the option
					$variant_wrapper .= sprintf(
						'<option data-option-cost="%s" data-stock="%s" value="%s" %s>%s</option>',
						esc_attr( floatval( $variant_price ) ),
						esc_attr( $available_stock ),
						$variant_id,
						$option_attributes,
						apply_filters( 'pewc_variation_name_variable_child_select', $variant->get_formatted_name(), $variant )
					);

					if ( false === $first_variant_id ) {
						$first_variant_id = $variant_id;
					}
				}
				$variant_wrapper .= '</select>';
				$wrapper_classes[] = 'pewc-variable-child-product-wrapper';

			}

			$description = '<div class="pewc-column-description"></div>';

		} else {
			// Simple product
			$wrapper_classes[] = 'pewc-simple-child-product-wrapper';

			$description = '';
		}

		$child_price = pewc_maybe_include_tax( $child_product, $child_product->get_price() ); // 3.9.7

		if( ! empty( $item['child_discount'] ) && ! empty( $item['discount_type'] ) ) {
			$discounted_price = pewc_get_discounted_child_price( $child_price, $item['child_discount'], $item['discount_type'] );
			$price = wc_format_sale_price( $child_price, $discounted_price );
			$option_cost = $discounted_price;
		} else {
			$price = $child_product->get_price_html();
			$option_cost = $child_price;
		}

		if( ! $option_cost ) $option_cost = 0;

		// Check stock availability
		$disabled = '';
		if( ! $child_product->is_purchasable() || ( ! $child_product->is_in_stock() && ! $child_product->backorders_allowed() ) ) {
			$disabled = 'disabled';
		}
		// Check available stock if stock is managed
		$available_stock = '';
		// 3.26.19, added backorders in condition
		if( $child_product->managing_stock() && ! $child_product->backorders_allowed() ) {
			$manage_stock = true;
			$available_stock = $child_product->get_stock_quantity();
			// 3.26.11, maybe this is a simple product, or max is not yet set?
			if ( empty( $max ) ) {
				$max = ' max="' . esc_attr( $available_stock ). '" ';
			}
		}

		//$image_url = ( get_post_thumbnail_id( $child_product_id ) ) ? wp_get_attachment_image_url( get_post_thumbnail_id( $child_product_id ), apply_filters( 'pewc_child_product_image_size', 'full', $child_product_id ) ) : wc_placeholder_img_src();
		//$image = '<img src="' . esc_url( $image_url ) . '">';
		// Use this for a proper img object. Since 3.12.1
		$image = pewc_get_swatch_image_html( array( 'image'=>get_post_thumbnail_id( $child_product_id ) ), $item );

		$child_product_title = apply_filters( 'pewc_child_product_title', get_the_title( $child_product_id ), $child_product);

		$name = sprintf(
			'<h4 class="pewc-radio-image-desc"><a href="%s" target="%s">%s</a></h4>',
			get_permalink( $child_product_id ),
			apply_filters( 'pewc_child_product_title_target', '_blank' ),
			$child_product_title,
		);

		$price = sprintf(
			'<p class="pewc-column-price-wrapper">%s</p>',
			apply_filters( 'pewc_option_price', $price, $item, $child_product )
		);

		$field_name = $id . '_child_product';

		$checkbox_id = $id . '_' . $child_product_id;

		$wrapper_classes[] = $checkbox_id;

		if( $disabled ) {
			$wrapper_classes[] = 'pewc-checkbox-disabled';
		}

		$quantity_field = '';
		$quantity_field_value = 0;

		// Look for child quantity when we're editing a product
		if ( ! empty($quantity_field_values[$child_product_id]) ) {
			$quantity_field_value = $quantity_field_values[$child_product_id];
		} else if ( ! empty( $default_child_products ) && ( in_array( $child_product_id, $default_child_products ) || $selected_variant_id > 0 ) ) {
			// 3.26.0
			$quantity_field_value = 1;
		}
		$quantity_field_value = apply_filters( 'pewc_child_product_independent_quantity', $quantity_field_value, $child_product_id, $item, $first_variant_id );

		// 3.21.2, auto-select child product if quantity > 0
		// 3.26.11, added ! $disabled to the condition
		if( $quantity_field_value > 0 && ! $disabled ) {
			$checked = 'checked="checked"';
			if ( ! in_array( 'checked', $wrapper_classes ) ) {
				$wrapper_classes[] = 'checked';
			}
		}

		if( $products_quantities == 'independent' ) {
			// Add a quantity field for each child checkbox

			// 3.26.5
			$attributes = apply_filters( 'pewc_filter_column_quantity_attributes', array( 'aria-label' => strip_tags( $child_product_title ) . ' ' . __( 'Quantity', 'pewc' ) ), $item );
			$attribute_string = '';
			if( ! empty( $attributes ) ) {
				foreach( $attributes as $attribute=>$attr_value ) {
					$attribute_string .= $attribute . '="' . esc_attr( $attr_value ) . '" ';
				}
			}
			// 3.26.11, used in JS
			if ( ! empty( $item['default_quantity'] ) ) {
				$attribute_string .= 'data-default-quantity="' . esc_attr( $item['default_quantity'] ) . '" ';
			}

			// The name format is {$id}_child_quantity_{$child_product_id}
			// Where $id is the field ID and $child_product_id is the child product ID
			$quantity_field = sprintf(
				'<span class="pewc-quantity-wrapper"><input type="number" min="0" step="1" %s class="pewc-form-field pewc-child-quantity-field" name="%s" value="%s" %s %s></span>',
				$max,
				esc_attr( $id ) . '_child_quantity_' . esc_attr( $child_product_id ),
				apply_filters( 'pewc_child_quantity', $quantity_field_value, $child_product_id, $item ),
				$disabled,
				$attribute_string,
			);
		}

		$add_button = sprintf(
			'%s<a href="#" class="button alt pewc-add-button">%s</a><a href="#" class="button pewc-add-button pewc-added">%s</a>',
			$quantity_field,
			__( 'Add', 'pewc' ),
			__( 'Remove', 'pewc' )
		);

	  $checkbox = sprintf(
	    '<div class="%s" data-option-id="%s" data-manage-stock="%s"><label for="%s"><input data-option-cost="%s" %s data-field-label="%s" type="checkbox" name="%s[]" id="%s" class="pewc-checkbox-form-field pewc-column-form-field" value="%s" %s %s>%s</label><div class="pewc-checkbox-desc-wrapper">%s%s%s<div class="pewc-column-variants-wrapper">%s</div>%s<p class="pewc-column-add-wrapper">%s</p></div></div>',
			join( ' ', $wrapper_classes ),
			esc_attr( $checkbox_id ),
			$manage_stock,
	    esc_attr( $checkbox_id ),
	    esc_attr( $option_cost ),
			$checked,
			get_the_title( $child_product_id ),
	    esc_attr( $field_name ),
	    esc_attr( $checkbox_id ),
	    esc_attr( $child_product_id ),
			esc_attr( $checked ),
			esc_attr( $disabled ),
	    $image,
			apply_filters( 'pewc_child_product_name', $name, $item, $available_stock, $child_product ),
			$price,
			apply_filters( 'pewc_child_product_excerpt', $excerpt, $item, $available_stock, $child_product ),
			$variant_wrapper,
			$description,
			$add_button
	  );
	  echo apply_filters( 'pewc_filter_checkbox', $checkbox, $child_product_id, $price, $id, $name, $item );

		do_action( 'pewc_after_child_product_item', $id, $child_product, $child_product_id );
	}

} ?>

</div><!-- .pewc-radio-images-wrapper -->
