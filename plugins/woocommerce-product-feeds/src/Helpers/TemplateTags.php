<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use Exception;
use WC_Product;
use WC_Product_Variation;
use WP_Post;
use function array_map;
use function esc_html;
use function implode;
use function is_null;

class TemplateTags {

	/**
	 * Show the value of a Google Product Feed data value.
	 *
	 * @param string $element The element to show, e.g. 'gtin', 'mpn'
	 * @param TemplateLoader $template
	 * @param WC_Product|WP_Post|null $product The product to get the value from. Leave blank to try to use the global $post object
	 *
	 * @return void
	 */
	public static function show_element( $element, $template, $product = null ) {
		$values = self::get_element_values( $element, $product );
		if ( empty( $values ) ) {
			return;
		}
		$template->output_template_with_variables(
			'frontend',
			'gpf-element',
			[
				'values' => implode( ', ', array_map( 'esc_html', $values ) ),
			]
		);
	}

	/**
	 * Show the value of a Google Product Feed data value with label.
	 *
	 * @param string $element The element to show, e.g. 'gtin', 'mpn'
	 * @param Configuration $configuration
	 * @param TemplateLoader $template
	 * @param WC_Product|WP_Post|null $product The product to get the value from. Leave blank to try to use the global $post object
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public static function show_element_with_label(
		string $element,
		Configuration $configuration,
		TemplateLoader $template,
		$product = null
	) {
		// Grab the value.
		$values = self::get_element_values( $element, $product );
		if ( empty( $values ) ) {
			return;
		}
		// Grab the label text.
		if ( ! empty( $configuration->product_fields[ $element ]['desc'] ) ) {
			$label = $configuration->product_fields[ $element ]['desc'];
		} else {
			$label = ucfirst( $element );
		}
		$template->output_template_with_variables(
			'frontend',
			'gpf-element-with-label',
			[
				'label'  => esc_html( $label ),
				'values' => implode( ', ', array_map( 'esc_html', $values ) ),
			]
		);
	}

	/**
	 * Retrieve the value of a Google Product Feed data value.
	 *
	 * @param string $element The element to retrieve, e.g. 'gtin', 'mpn'
	 * @param WC_Product|WP_Post|null $product The product to get the value from. Leave blank to try to use the global $post object
	 *
	 * @return array|null Array of the values for the element on the requested post.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function get_element_values( $element, $product = null ): ?array {
		$product = self::find_product( $product );
		if ( ! $product ) {
			return null;
		}
		$feed_item = self::get_feed_item( $product );
		if ( ! empty( $feed_item->additional_elements[ $element ] ) ) {
			return $feed_item->additional_elements[ $element ];
		}

		return null;
	}

	/**
	 * @param WC_Product|WP_Post|null $requested_product
	 *
	 * @return WC_Product|null
	 */
	public static function find_product( $requested_product ): ?WC_Product {
		global $post;
		// If this is already a product, there's nothing todo. Return it as-is.
		if ( $requested_product instanceof WC_Product ) {
			return $requested_product;
		}
		// If this is a post, instantiate a WC_Product from it and return it.
		if ( $requested_product instanceof WP_Post ) {
			$product = wc_get_product( $requested_product->ID );
			if ( $product ) {
				return $product;
			}
		}
		// If null, try and instantiate a product from the global $post.
		if ( is_null( $requested_product ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				return $product;
			}
		}

		// Failed to locate a suitable product.
		return null;
	}

	/**
	 * Retrieve a specific element value that would be rendered in the feed.
	 *
	 * Pass a product post object to fetch the value for a specific product, or leave blank
	 * to fetch the value for the global $post.
	 *
	 * @param WC_Product $product
	 *
	 * @return ProductFeedItem
	 */
	private static function get_feed_item( WC_Product $product ): ProductFeedItem {
		global $woocommerce_product_feeds_di;
		if ( $product instanceof WC_Product_Variation ) {
			$general_product = wc_get_product( $product->get_parent_id() );
		} else {
			$general_product = $product;
		}

		return $woocommerce_product_feeds_di['ProductFeedItemFactory']
			->create( 'all', $product, $general_product );
	}
}
