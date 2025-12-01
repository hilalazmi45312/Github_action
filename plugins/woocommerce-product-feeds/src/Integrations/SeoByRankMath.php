<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use Ademti\WoocommerceProductFeeds\DTOs\FallbackRequest;
use Ademti\WoocommerceProductFeeds\Helpers\TermDepthRepository;
use Exception;
use WP_Error;
use function explode;
use function get_term;

/**
 * Integration for:
 * https://wordpress.org/plugins/seo-by-rank-math/
 */
class SeoByRankMath {

	/**
	 * @var TermDepthRepository
	 */
	protected TermDepthRepository $term_depth_repository;

	/**
	 * @param  TermDepthRepository  $term_depth_repository
	 */
	public function __construct( TermDepthRepository $term_depth_repository ) {
		$this->term_depth_repository = $term_depth_repository;
	}

	/**
	 * Run the integration.
	 */
	public function run(): void {
		add_filter( 'woocommerce_gpf_prepopulate_value_for_product', [ $this, 'prepopulate' ], 10, 5 );
		add_filter( 'woocommerce_gpf_custom_field_list', [ $this, 'register_field' ] );
	}

	/**
	 * Register the field so that it can be chosen as a prepopulate option.
	 *
	 * @param $field_list
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function register_field( $field_list ) {
		$field_list['SeoByRankMath:getPrimaryCategory']          = __(
			'Primary Category from SEO by Rank Math extension',
			'woocommerce_gpf'
		);
		$field_list['SeoByRankMath:getPrimaryCategoryHierarchy'] = __(
			'Primary Category (full hierarchy) from SEO by Rank Math extension',
			'woocommerce_gpf'
		);

		return $field_list;
	}

	public function prepopulate( $result, $prepopulate, $which_product, $specific_product, $general_product ) {
		list($type, $category_type) = explode( ':', $prepopulate );
		if ( 'SeoByRankMath' !== $type ) {
			return $result;
		}
		$product = ( 'general' === $which_product ) ? $general_product : $specific_product;
		switch ( $category_type ) {
			case 'getPrimaryCategory':
				return $this->getPrimaryCategory( $product );
				break;
			case 'getPrimaryCategoryHierarchy':
				return $this->getPrimaryCategoryHierarchy( $product );
				break;
			default:
				return [];
		}
	}

	/**
	 * Get the name of the primary category.
	 *
	 * @param $wc_product
	 *
	 * @return array|FallbackRequest
	 *
     * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public function getPrimaryCategory( $wc_product ) {
		$rank_math_primary_category_id = $wc_product->get_meta( 'rank_math_primary_product_cat' );
		if ( empty( $rank_math_primary_category_id ) ) {
			return new FallbackRequest( 'tax:product_cat' );
		}
		$term = get_term( $rank_math_primary_category_id );

		return $term->name ? [ $term->name ] : new FallbackRequest( 'tax:product_cat' );
	}
    // phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

	/**
	 * Get the name of the primary category.
	 *
	 * @param $wc_product
	 *
	 * @return array|FallbackRequest
	 *
     * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public function getPrimaryCategoryHierarchy( $wc_product ) {
		$rank_math_primary_category_id = $wc_product->get_meta( 'rank_math_primary_product_cat' );
		if ( empty( $rank_math_primary_category_id ) ) {
			return new FallbackRequest( 'taxhierarchy:product_cat' );
		}
		$term = get_term( $rank_math_primary_category_id );
		if ( ! $term || $term instanceof \WP_Error ) {
			return new FallbackRequest( 'taxhierarchy:product_cat' );
		}

		return [ $this->term_depth_repository->get_hierarchy_string( $term ) ];
	}
    // phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
}
