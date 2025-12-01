<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use WC_Product;
use WC_Product_Variation;
use function apply_filters;
use function get_post_meta;
use function maybe_unserialize;

class ProductFeedItemExclusionService {

	/**
	 * @var array
	 */
	private array $excluded_catalog_visibilities;

	/**
	 * Work out if a WC Product should be excluded from the feed.
	 *
	 * @param WC_Product $wc_product The product to check.
	 * @param string $feed_type
	 *
	 * @return bool True if the product should be excluded. False otherwise.
	 */
	public function should_exclude( WC_Product $wc_product, string $feed_type ): bool {
		$excluded = false;

		// Make sure we allow plugins to override which product visibilities we exclude.
		if ( ! isset( $this->excluded_catalog_visibilities ) ) {
			$this->excluded_catalog_visibilities = apply_filters(
				'woocommerce_gpf_excluded_catalog_visibilities',
				[ 'hidden' ]
			);
		}

		// Check to see if the product is excluded based on its catalog visibility.
		if ( in_array( $wc_product->get_catalog_visibility(), $this->excluded_catalog_visibilities, true ) ) {
			$excluded = true;
		}
		// Check to see if the product has been excluded in the feed config.
		$gpf_data = get_post_meta( $wc_product->get_id(), '_woocommerce_gpf_data', true );
		if ( ! empty( $gpf_data ) ) {
			$gpf_data = maybe_unserialize( $gpf_data );
		}
		if ( ! empty( $gpf_data['exclude_product'] ) ) {
			$excluded = true;
		}
		// If it's a variation, check if the variation is disabled.
		if ( $wc_product instanceof WC_Product_Variation && $wc_product->get_status() !== 'publish' ) {
			$excluded = true;
		}
		if ( $wc_product instanceof WC_Product_Variation ) {
			$parent_id = $wc_product->get_parent_id();

			return apply_filters(
				'woocommerce_gpf_exclude_variation',
				apply_filters( 'woocommerce_gpf_exclude_product', $excluded, $parent_id, $feed_type ),
				$wc_product->get_id(),
				$feed_type
			);
		}
		$parent_id = $wc_product->get_id();

		return apply_filters( 'woocommerce_gpf_exclude_product', $excluded, $parent_id, $feed_type );
	}
}
