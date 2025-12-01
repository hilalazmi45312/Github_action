<?php
/**
 * Functions for Cart and Checkout Blocks
 * @since 3.21.7
 * @package WooCommerce Product Add-Ons Ultimate
 */

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if in cart or checkout page, or if using StoreAPI (e.g. mini-cart)
 * @since 3.21.7
 */
function pewc_page_has_wc_blocks() {
	if ( ! class_exists( 'WC_Blocks_Utils' ) ) {
		return false;
	}
	return (
		WC_Blocks_Utils::has_block_in_page( get_the_id(), 'woocommerce/cart' ) || 
		WC_Blocks_Utils::has_block_in_page( get_the_id(), 'woocommerce/checkout' ) || 
		WC()->is_store_api_request() 
	);
}

/**
 * Enqueues script for use in Block pages
 * @since	3.21.7
 * @version	3.24.8
 */
function pewc_wc_blocks_enqueue_scripts() {

	// 3.24.3, enqueue pewc-blocks.js only if wc-cart-block-frontend is enqueued. Fixed an error in Query Monitor
	// 3.24.8, added wc-checkout-block-frontend for the checkout page
	// 3.26.1, created pewc_enqueue_blocks_script() function
	if ( pewc_enqueue_blocks_script( 'cart' ) ) {
		$deps = array( 'wc-cart-block-frontend' );
	} else if ( pewc_enqueue_blocks_script( 'checkout' ) ) {
		$deps = array( 'wc-checkout-block-frontend' );
	} else {
		return;
	}

	$version = defined( 'PEWC_SCRIPT_DEBUG' ) && PEWC_SCRIPT_DEBUG ? time() : PEWC_PLUGIN_VERSION;

	// 3.23.1, removed dependency on pewc-script so that this script is still loaded even if Dequeue scripts is enabled
	wp_register_script( 'pewc-blocks', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-blocks.js', $deps, $version, true );
	wp_enqueue_script( 'pewc-blocks' );

}
add_action( 'wp_enqueue_scripts', 'pewc_wc_blocks_enqueue_scripts' );

/**
 * Registers endpoints used in pewc-blocks.js
 * @since 3.21.7
 */
function pewc_wc_blocks_endpoints() {

	if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'		=> CartItemSchema::IDENTIFIER,
				'namespace'	   => 'pewc_data',
				'data_callback'   => 'pewc_item_data_callback',
				'schema_callback' => 'pewc_item_schema_callback',
				'schema_type'	 => ARRAY_A,
			)
		);
	}

}
add_action( 'woocommerce_blocks_loaded', 'pewc_wc_blocks_endpoints' );

/**
 * Callback function to register endpoint data for blocks, used in cart page
 * @since 3.21.7
 */
function pewc_item_data_callback( $cart_item ) {

	$pewc_data = array(
		'key' => $cart_item['key']
	);
	if ( ! empty( $cart_item['product_extras']['groups'] ) ) {
		if ( pewc_user_can_edit_products() ) {
			$pewc_data['edit_html'] = apply_filters(
				'pewc_after_cart_item_edit_options_blocks',
				sprintf(
					'&nbsp;<small>[%s]</small>',
					apply_filters( 'pewc_after_cart_item_edit_options_text', __( 'Edit options', 'pewc' ), $cart_item['data']->get_id() )
				)
			);
		}

		$uploaded_files = array();
		foreach ( $cart_item['product_extras']['groups'] as $group_id => $fields ) {
			foreach ( $fields as $field_id => $field ) {
				if ( 'upload' === $field['type'] && ! empty( $field['files'] ) ) {
					foreach ( $field['files'] as $file ) {
						if ( is_file( $file['file'] ) && ! empty( $file['url'] ) ) {
							$thumb = pewc_thumb_anti_cache( $file['url'] );
							$uploaded_file = array(
								'url' => $thumb,
								'type' => strpos( $file['type'], 'image/' ) !== false && ! apply_filters( 'pewc_show_link_only_cart', false ) ? 'image' : 'link',
								'name' => $file['name'],
							);
							if ( ! empty( $file['quantity'] ) ) {
								$uploaded_file['quantity'] = sprintf(
									'%s: %s',
									__( 'Quantity', 'pewc' ),
									$file['quantity']
								);
							}
							// convert label to a string that we can match on the frontend. Not sure yet how WooCommerce converts the strings
							$label_key = 'pewc-upload-' . $field_id; // this matches the className data in pewc_get_item_data
							$uploaded_files[$label_key][] = $uploaded_file;
						}
					}
				}
			}
		}
		if ( ! empty( $uploaded_files ) ) {
			$pewc_data['uploaded_files'] = $uploaded_files;
		}
	}

	if ( ! empty( $cart_item['product_extras']['products']['child_field'] ) ) {
		// this is a child product
		if ( 'yes' === get_option( 'pewc_hide_child_products_cart', 'no' ) ) {
			// 3.26.20, hide product in the cart. If this is triggered, the steps below is no longer needed?
			$pewc_data['key'] .= ' ' . 'pewc-hidden-child-product';
		} else if ( ! empty( $cart_item['product_extras']['products']['products_quantities'] ) ) {
			// 3.22.1, add a new class for quantities, which we can use if we want to remove the Remove Item link in WC Blocks Cart
			// link is removed using registerCheckoutFilters >> showRemoveItemLink in assets/js/pewc-blocks.js
			$pewc_data['key'] .= ' ' . 'pewc-quantities-' . $cart_item['product_extras']['products']['products_quantities'];
			if ( 'independent' !== $cart_item['product_extras']['products']['products_quantities'] ) {
				$pewc_data['arrow_right'] = esc_url( trailingslashit( PEWC_PLUGIN_URL ) . 'assets/images/arrow-right.svg' );
			}
		}
	} else if ( ! empty( $cart_item['product_extras']['child_fields'] ) ) {
		// 3.26.20, this is a parent product
		if ( 'yes' === get_option( 'pewc_hide_parent_products_cart', 'no' ) ) {
			$pewc_data['key'] .= ' ' . 'pewc-hidden-parent-product';
		} else if ( 'yes' === get_option( 'pewc_hide_child_products_cart', 'no' ) ) {
			// child products are hidden, do we want to update the parent's totals?
		}
	}

	return $pewc_data;

}

/**
 * Callback function to register schema for data, used in cart page
 * @since 3.21.7
 */
function pewc_item_schema_callback() {

	return array(
		'properties' => array(
			'key' => array(
				'type' => 'string',
			),
			'uploaded_files' => array(
				'type' => 'array',
			)
		),
	);

}

/**
 * Disable the quantity field on WooCommerce Blocks Cart
 * @since 3.22.1
 * 
 * Note: workaround for woocommerce_cart_item_quantity which is not used in WC Blocks (as of 9.4.3)
 */
function pewc_wc_blocks_quantity_editable( $value, $products, $cart_item ) {

	if ( ! empty( $cart_item['product_extras']['products']['child_field'] ) && ! empty( $cart_item['product_extras']['products']['products_quantities'] ) ) {
		// child product
		// 3.27.1, added the filter pewc_disable_child_quantities which always disables child product quantities in cart shortcode via Javascript
		if ( 'independent' !== $cart_item['product_extras']['products']['products_quantities'] || apply_filters( 'pewc_disable_child_quantities', true ) ) {
			$value = false;
		}
	} else if ( ! empty( $cart_item['data'] ) && pewc_hide_quantity( $cart_item['data'] ) ) {
		// 3.26.11, this is not a child product, check if quantity is hidden for the product
		$value = false;
	}
	return $value;

}
add_filter( 'woocommerce_store_api_product_quantity_editable', 'pewc_wc_blocks_quantity_editable', 11, 3 );

/**
 * Add an extra class to the main price display so we can adjust it (WC Blocks)
 * @since 3.24.5
 */
function pewc_wc_blocks_product_price( $content ) {

	if ( false !== strpos( $content, 'wc-block-components-product-price ' ) ) {
		$content = str_replace( 'wc-block-components-product-price ', 'wc-block-components-product-price pewc-main-price ', $content );
	}
	return $content;

}
add_filter( 'render_block_woocommerce/product-price', 'pewc_wc_blocks_product_price', 10, 1 );

/**
 * Check if we should enqueue pewc-blocks.js
 * @since 3.26.1
 */
function pewc_enqueue_blocks_script( $page=false ) {

	$enqueue = false;

	if ( 'cart' === $page && ( wp_script_is( 'wc-cart-block-frontend', 'enqueued' ) || WC_Blocks_Utils::has_block_in_page( get_the_id(), 'woocommerce/cart' ) ) ) {
		$enqueue = true;
	} else if ( 'checkout' === $page && (  wp_script_is( 'wc-checkout-block-frontend', 'enqueued' ) || WC_Blocks_Utils::has_block_in_page( get_the_id(), 'woocommerce/checkout' ) ) ) {
		$enqueue = true;
	}

	return apply_filters( 'pewc_enqueue_blocks_script', $enqueue, $page );

}
