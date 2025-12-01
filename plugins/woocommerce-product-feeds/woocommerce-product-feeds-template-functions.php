<?php

use Ademti\WoocommerceProductFeeds\Helpers\TemplateTags;

/**
 * Show a specific element with the value that would be rendered in the feed.
 *
 * Pass a product post object to fetch the value for a specific product, or leave blank
 * to fetch the value for the global $post.
 *
 * @param string $element
 * @param WP_Post|WC_Product|null $post
 */
function woocommerce_gpf_show_element( $element, $post = null ) {
	global $woocommerce_product_feeds_di;
	$template = $woocommerce_product_feeds_di['TemplateLoader'];

	TemplateTags::show_element( $element, $template, $post );
}

/**
 * Show a specific element with label, with the value that would be rendered in the feed.
 *
 * Pass a product post object to fetch the value for a specific product, or leave blank
 * to fetch the value for the global $post.
 *
 * @param string $element
 * @param WP_Post|WC_Product|null $post
 */
function woocommerce_gpf_show_element_with_label( $element, $post = null ) {
	global $woocommerce_product_feeds_di;
	$template      = $woocommerce_product_feeds_di['TemplateLoader'];
	$configuration = $woocommerce_product_feeds_di['Configuration'];
	$configuration->initialise();

	TemplateTags::show_element_with_label( $element, $configuration, $template, $post );
}

/**
 * Retrieve a specific element value that would be rendered in the feed.
 *
 * Pass a product post object to fetch the value for a specific product, or leave blank
 * to fetch the value for the global $post.
 *
 * @param string $element
 * @param WP_Post|WC_Product|null $post
 *
 * @return array
 */
function woocommerce_gpf_get_element_values( $element, $post = null ) {
	return TemplateTags::get_element_values( $element, $post );
}


/**
 * Determine if this is a feed URL.
 *
 * May need to be used before parse_query, so we have to manually check all
 * sorts of combinations.
 *
 * @return boolean  True if a feed is being generated.
 */
function woocommerce_gpf_is_generating_feed() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	return ( isset( $_SERVER['REQUEST_URI'] ) &&
			 stripos( sanitize_text_field( $_SERVER['REQUEST_URI'] ), '/woocommerce_gpf' ) === 0
		   ) || isset( $_REQUEST['woocommerce_gpf'] );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}
