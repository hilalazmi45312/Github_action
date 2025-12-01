<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use Exception;
use WP_Term;

class TermDepthRepository {

	/**
	 * Internal cache.
	 */
	private array $cache = [];

	/**
	 * Get the depth of a given term.
	 *
	 * @param WP_Term $term WP_Term to find depth of.
	 *
	 * @return int|null
	 */
	public function get_depth( WP_Term $term ) {
		return $this->get_value_for_term( $term, 'depth' );
	}

	/**
	 * @param WP_Term $term WP_Term to find depth of.
	 *
	 * @return string
	 */
	public function get_hierarchy_string( WP_Term $term ) {
		return $this->get_value_for_term( $term, 'hierarchy_string' );
	}

	/**
	 * Order an array of term objects by their "depth", deepest last.
	 *
	 * @param array $terms
	 *
	 * @return array
	 */
	public function order_terms_by_depth( $terms ) {
		$sorted = $terms;
		usort( $sorted, [ $this, 'sort_callback' ] );

		return $sorted;
	}

	/**
	 * @param WP_Term $term WP_Term to find depth of.
	 * @param string $value 'depth', or 'hierarchy_string'
	 *
	 * @return mixed
	 */
	private function get_value_for_term( WP_Term $term, string $value ) {
		// If it is cached already, use it.
		if ( isset( $this->cache[ $term->term_id ][ $value ] ) ) {
			return $this->cache[ $term->term_id ][ $value ];
		}

		// Prime the cache.
		$this->prime_cache( $term );

		// Use the primed value.
		return $this->cache[ $term->term_id ][ $value ];
	}

	/**
	 * Prime the cache for a term.
	 *
	 * @param WP_Term $term WP_Term to prime the cache for.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	private function prime_cache( WP_Term $term ) {
		// Cache already exists. We're done.
		if ( isset( $this->cache[ $term->term_id ] ) ) {
			return;
		}
		// If this is a top level term, set depth to one, and hierarchy string to just the term's name.
		if ( 0 === $term->parent ) {
			$this->cache[ $term->term_id ] = [
				'depth'            => 1,
				'hierarchy_string' => $term->name,
			];

			return;
		}

		// Otherwise, try to recurse up the tree. First grab the parent term.
		$parent_term = get_term( $term->parent );
		// If not found, a parent term has been deleted, treat this as top-level.
		if ( is_null( $parent_term ) || is_wp_error( $parent_term ) ) {
			$this->cache[ $term->term_id ] = [
				'depth'            => 1,
				'hierarchy_string' => $term->name,
			];

			return;
		}

		// Set this term's values as derivative's of its parent.
		$this->cache[ $term->term_id ] = [
			'depth'            => $this->get_depth( $parent_term ) + 1,
			'hierarchy_string' => $this->get_hierarchy_string( $parent_term ) .
									apply_filters( 'woocommerce_gpf_hierarchy_separator', ' > ' ) .
									$term->name,
		];
	}

	/**
	 * usort callback
	 *
	 * Sort two term objects based on their depth in the hierarchy, deepest last.
	 *
	 * @param $value_a
	 * @param $value_b
	 *
	 * @return int
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function sort_callback( $value_a, $value_b ) {
		// Sort by depth if we can.
		if ( $this->get_depth( $value_a ) > $this->get_depth( $value_b ) ) {
			return 1;
		}
		if ( $this->get_depth( $value_a ) < $this->get_depth( $value_b ) ) {
			return -1;
		}
		// If depths are equal, sort on term ID to make sure we get
		// consistent results irrespective of input ordering.
		if ( $value_a->term_id > $value_b->term_id ) {
			return 1;
		}

		return -1;
	}
}
