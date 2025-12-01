<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use function wp_get_attachment_image_src;

/**
 * Integration for:
 * https://woocommerce.com/products/woocommerce-additional-variation-images/
 */
class WoocommerceAdditionalVariationImages {
	/**
	 * Run the integration.
	 */
	public function run(): void {
		add_filter( 'woocommerce_gpf_additional_images_to_register', [ $this, 'get_images' ], 10, 4 );
	}

	/**
	 * @param $images
	 * @param $specific_product
	 * @param $general_product
	 * @param $image_style
	 *
	 * @return mixed
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function get_images( $images, $specific_product, $general_product, $image_style ) {
		// Do nothing unless this is a variation.
		if ( $specific_product->get_type() !== 'variation' ) {
			return $images;
		}
		// Look for additional variation images.
		$media_ids = $specific_product->get_meta( '_wc_additional_variation_images', true );
		if ( empty( $media_ids ) ) {
			return $images;
		}
		$media_ids = explode( ',', $media_ids );

		// Find out their URLs and format for return.
		$new_images = [];
		foreach ( $media_ids as $media_id ) {
			if ( empty( $media_id ) ) {
				continue;
			}
			$full_image_src = wp_get_attachment_image_src( $media_id, $image_style, false );
			if ( ! empty( $full_image_src ) ) {
				$new_images[ $media_id ] = $full_image_src[0];
			}
		}

		if ( ! empty( $new_images ) ) {
			$images['wc_additional_variation_images'] = $new_images;
		}

		return $images;
	}
}
