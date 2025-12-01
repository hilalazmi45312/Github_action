<?php
/**
 * Functions for child product quickviews
 * @since 3.8.6
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if QuickView is enabled
 * @since 3.8.6
 */
function pewc_child_product_quickview() {
	global $post;
	$quickview = get_option( 'pewc_child_product_quickview', 'no' );
	return apply_filters( 'pewc_child_product_quickview_enabled', $quickview, $post );
}

/**
 * Get QuickView link text
 * @since 3.24.4
 */
function pewc_quickview_link_text() {
	global $post;
	$link_text = get_option( 'pewc_quickview_text', '' );
	return apply_filters( 'pewc_child_product_quickview_link_text', $link_text, $post );
}

/**
 * Add a link to the product title if QuickView is enabled
 * @since 3.8.6
 */
function pewc_add_quickview_child_product( $name, $item, $available_stock, $child_product ) {
	
	$quickview = pewc_child_product_quickview();
	if( $quickview != 'no' ) {

		$child_product_id = $child_product->get_id();
		// Check link text
		$link_text = pewc_quickview_link_text();
		
		if ( $item['products_layout'] == 'column' ) {
			
			// product fields with column layout already have <a> tags inside <h4> tags, and some browsers do not like wrapping another a tag around it, so rebuild $name instead
			// taken from templates/frontend/products/products-column.php
			$name = sprintf(
				'<h4 class="pewc-radio-image-desc"><a href="#" class="pewc-show-quickview" data-child-product-id="%s">%s</a></h4>',
				$child_product_id,
				apply_filters( 'pewc_child_product_title', get_the_title( $child_product_id ), $child_product)
			);

		} else {

			// moved here since 3.24.7
			$target = '';
			if( $quickview == 'tab' ) {
				// Open the link in a new tab
				$url = get_the_permalink( $child_product_id );
				$target = '_blank';
			} else {
				$url = '#';
			}
			$target = apply_filters( 'pewc_quickview_new_tab', $target, $item, $child_product_id );

			if( ! $link_text ) {

				$name = sprintf(
					'<a href="%s" class="pewc-show-quickview" data-child-product-id="%s" target="%s">%s</a>',
					esc_url( $url ),
					$child_product_id,
					esc_attr( $target ),
					$name
				);

			} else {

				$name = sprintf(
					'%s<p class="pewc-quickview-link"><a href="%s" target="%s" class="pewc-show-quickview" data-child-product-id="%s">%s</a></p>',
					$name,
					esc_url( $url ),
					esc_attr( $target ),
					$child_product_id,
					esc_html( $link_text )
				);

			}
			
		}
	}
	return $name;
}
add_filter( 'pewc_child_product_name', 'pewc_add_quickview_child_product', 10, 4 );

/**
 * Add a class name
 * @since 3.8.6
 */
function pewc_add_quickview_field_class( $classes, $item ) {
	if( pewc_child_product_quickview() != 'no' ) {
		$classes[] = 'pewc-has-quickview';
	}
	return $classes;
}
add_filter( 'pewc_filter_single_product_classes', 'pewc_add_quickview_field_class', 10, 2 );

/**
 * Build the QuickView template
 * @since	3.8.6
 * @version	3.26.15
 */
function pewc_display_quickview_template( $field_id, $child_product, $child_product_id ) {

	if( pewc_child_product_quickview() != 'yes' ) {
		return;
	}

	$original_post = $GLOBALS['post'];

	$GLOBALS['post'] = get_post( $child_product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $GLOBALS['post'] ); // this also overrides the global $product object

	/**
	 * Hook: woocommerce_before_single_product.
	 *
	 * @hooked woocommerce_output_all_notices - 10
	 */
	remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );

	/**
	 * Hook: woocommerce_single_product_summary.
	 *
	 * @hooked woocommerce_template_single_title - 5
	 * @hooked woocommerce_template_single_rating - 10
	 * @hooked woocommerce_template_single_price - 10
	 * @hooked woocommerce_template_single_excerpt - 20
	 * @hooked woocommerce_template_single_add_to_cart - 30
	 * @hooked woocommerce_template_single_meta - 40
	 * @hooked woocommerce_template_single_sharing - 50
	 * @hooked WC_Structured_Data::generate_product_data() - 60
	 */
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	/*
	* @hooked woocommerce_output_product_data_tabs - 10
	* @hooked woocommerce_upsell_display - 15
	* @hooked woocommerce_output_related_products - 20
	*/
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );


	// 3.26.15
	do_action( 'pewc_before_quickview_template_load', $field_id, $child_product_id );	
	$path = pewc_include_frontend_template( 'quickview/quickview.php' );
	if ( $path ) {
		include( $path );
	}
	do_action( 'pewc_after_quickview_template_load', $field_id, $child_product_id );


	$GLOBALS['post'] = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $GLOBALS['post'] ); // we need to do this again to put back the original $product object (parent product)

	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

}
add_action( 'pewc_after_child_product_item', 'pewc_display_quickview_template', 10, 3 );

/**
 * Add the background
 * @since 3.8.6
 */
function pewc_display_quickview_background() {
	if( pewc_child_product_quickview() == 'yes' ) { ?>
		<div id="pewc-quickview-background"></div>
	<?php }
}
add_action( 'wp_footer', 'pewc_display_quickview_background' );
