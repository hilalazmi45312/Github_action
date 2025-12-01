<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use Ademti\WoocommerceProductFeeds\DTOs\PromotionFeedItem;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use DateTime;
use WC_Coupon;
use function count;
use function wp_cache_flush;

class CouponRepository {

	/**
	 * @var StoreInfo
	 */
	private StoreInfo $store_info;

	/**
	 * @var int
	 */
	private int $chunk_size;

	/**
	 * @param StoreInfo $store_info
	 */
	public function __construct( StoreInfo $store_info ) {
		$this->store_info = $store_info;
	}

	/**
	 * @return int
	 */
	public function get_chunk_size(): int {
		if ( ! isset( $this->chunk_size ) ) {
			$this->chunk_size = apply_filters( 'woocommerce_gpf_promotions_chunk_size', 20 );
		}

		return $this->chunk_size;
	}

	/**
	 * Retrieve a chunk of coupons.
	 *
	 * @param int $offset
	 *
	 * @return array
	 */
	public function get_coupons( int $offset = 0 ): array {
		return get_posts(
			[
				'posts_per_page' => $this->get_chunk_size(),
				'offset'         => $offset,
				'post_type'      => 'shop_coupon',
				'meta_query'     => [
					[
						'key'   => 'woocommerce_gpf_visibility',
						'value' => true,
					],
					[
						'key'     => 'discount_type',
						'compare' => 'IN',
						'value'   => [ 'fixed_cart', 'fixed_product', 'percent' ],
					],
				],
			]
		);
	}

	public function generate_category_coupon_map() {
		return $this->generate_term_coupon_map(
			'categories',
			'excluded_categories',
			'get_product_types',
			'get_product_type_exclusions',
			'product_cat'
		);
	}

	public function generate_brand_coupon_map() {
		return $this->generate_term_coupon_map(
			'brands',
			'excluded_brands',
			'get_product_brands',
			'get_product_brand_exclusions',
			'product_brand'
		);
	}

	/**
	 * @param  string  $key
	 * @param  string  $excluded_key
	 * @param  string  $pfi_method
	 * @param  string  $pfi_exclusions_method
	 * @param  string  $taxonomy
	 *
	 * @return array
	 */
	public function generate_term_coupon_map(
		string $key,
		string $excluded_key,
		string $pfi_method,
		string $pfi_exclusions_method,
		string $taxonomy
	) {

		global $_wp_using_ext_object_cache;

		$chunk_size = $this->get_chunk_size();
		$offset     = 0;
		$map        = [
			$key          => [],
			$excluded_key => [],
		];

		$now = new DateTime();

		$coupon_posts      = $this->get_coupons( $offset );
		$coupon_post_count = count( $coupon_posts );
		while ( $coupon_post_count ) {
			foreach ( $coupon_posts as $coupon_post ) {
				// Grab the WC_Coupon instance.
				$coupon = new WC_Coupon( $coupon_post->ID );
				// Ignore it if it has ended.
				$expires = $coupon->get_date_expires();
				if ( $expires && $expires < $now ) {
					continue;
				}
				// Create a PromotionFeedItem.
				$coupon_feed_item = new PromotionFeedItem( $coupon, $this->store_info );
				// Ignore the coupon if it was ineligible for the feed.
				if ( ! $coupon_feed_item->is_eligible() ) {
					continue;
				}
				// Grab the categories/excluded categories for this coupon.
				$terms          = $coupon_feed_item->$pfi_method();
				$excluded_terms = $coupon_feed_item->$pfi_exclusions_method();
				// Skip it if there are none.
				if ( empty( $terms ) && empty( $excluded_terms ) ) {
					continue;
				}
				// Add them to the map.
				foreach ( $terms as $selected_term_id ) {
					$id_and_descendants   = get_term_children( $selected_term_id, $taxonomy );
					$id_and_descendants[] = $selected_term_id;
					foreach ( $id_and_descendants as $term_id ) {
						if ( empty( $map[ $key ][ $term_id ] ) ) {
							$map[ $key ][ $term_id ] = [];
						}
						$map[ $key ][ $term_id ][] = $coupon_feed_item->get_promotion_id();
					}
				}
				foreach ( $excluded_terms as $excluded_term_id ) {
					$id_and_descendants   = get_term_children( $excluded_term_id, $taxonomy );
					$id_and_descendants[] = $excluded_term_id;
					foreach ( $id_and_descendants as $term_id ) {
						if ( empty( $map[ $excluded_key ][ $term_id ] ) ) {
							$map[ $excluded_key ][ $term_id ] = [];
						}
						$map[ $excluded_key ][ $term_id ][] = $coupon_feed_item->get_promotion_id();
					}
				}
			}
			$offset += $chunk_size;

			// If we're using the built-in object cache then flush it every chunk so
			// that we don't keep churning through memory.
			if ( ! $_wp_using_ext_object_cache ) {
				wp_cache_flush();
			}

			$coupon_posts      = $this->get_coupons( $offset );
			$coupon_post_count = count( $coupon_posts );
		}

		return $map;
	}
}
