<?php

namespace Ademti\WoocommerceProductFeeds\Cache;

class CacheInvalidator {

	// Dependencies.
	private Cache $cache;

	/**
	 * Keep track of post parents when posts are deleted so we can cache clean
	 * appropriately.
	 *
	 * @var array
	 */
	private array $parent_map = [];

	/**
	 * Keep track of post types when posts are deleted so we can cache clean
	 * appropriately.
	 *
	 * @var array
	 */
	private array $product_delete_map = [];

	/**
	 * Constructor.
	 *
	 * Hook all the events we are interested in.
	 *
	 * @param Cache $cache
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	public function initialise(): void {
		// When a product is added / updated, rebuild the cache for that product.
		add_action( 'woocommerce_new_product', [ $this, 'save_product' ], 90 );
		add_action( 'woocommerce_update_product', [ $this, 'save_product' ], 90 );

		// If we don't attach to these we miss product gallery updates.
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_product' ], 90, 1 );

		// When a product is trashed / removed drop its cache.
		add_action( 'wp_trash_post', [ $this, 'pre_delete_product' ], 90 );
		add_action( 'delete_post', [ $this, 'pre_delete_product' ], 90 );
		add_action( 'trashed_post', [ $this, 'clear_product' ], 90 );
		add_action( 'deleted_post', [ $this, 'clear_product' ], 90 );

		// When a product is restored from the trash, build its cache.
		add_action( 'untrashed_post', [ $this, 'save_product' ], 90 );

		// When a variant is added / updated, rebuild the cache for the parent
		// product.
		add_action( 'woocommerce_new_product_variation', [ $this, 'save_variation' ], 90 );
		add_action( 'woocommerce_update_product_variation', [ $this, 'save_variation' ], 90 );

		// When a variant is removed, rebuild the cache for the parent product.
		add_action( 'wp_trash_post', [ $this, 'pre_delete_variation' ], 90 );
		add_action( 'delete_post', [ $this, 'pre_delete_variation' ], 90 );
		add_action( 'woocommerce_delete_product_variation', [ $this, 'remove_variation' ], 90 );
		add_action( 'woocommerce_trash_product_variation', [ $this, 'remove_variation' ], 90 );

		// When image exclusions are changed, rebuild the cache for the product.
		add_action( 'woocommerce_gpf_media_ids_updated', [ $this, 'save_product' ] );

		// When category / term / attribute updated, rebuild cache for affected products.
		add_action( 'edited_term', [ $this, 'rebuild_term' ], 90, 3 );

		// When a category / term / attribute is removed, rebuild cache for affected products.
		add_action( 'delete_term', [ $this, 'rebuild_term_objects' ], 90, 5 );

		// When plugin settings are changed, rebuild full cache.
		add_action( 'woocommerce_update_options_gpf', [ $this, 'rebuild_all' ], 90 );
	}

	/**
	 * Handle saving of a product.
	 *
	 * @param int $product_id The product ID of the product being saved.
	 *
	 * @return void
	 */
	public function save_product( $product_id ) {
		// Do nothing if the post is not a "product".
		$post = get_post( $product_id );
		if ( $post && 'product' !== $post->post_type ) {
			return;
		}
		// Schedule a rebuild.
		$this->rebuild_product( $product_id );
	}

	/**
	 * Clear out entries from the cache without triggering a rebuild for relevant post types.
	 *
	 * @param $product_id
	 */
	public function clear_product( $product_id ): void {
		if ( isset( $this->product_delete_map[ $product_id ] ) ) {
			$this->cache->clear_product( $product_id );
		}
	}

	/**
	 * Make a note of the post type so we can know whether we need to do
	 * anything when the product is actually trashed / deleted.
	 *
	 * @param $product_id
	 *
	 * @return void
	 */
	public function pre_delete_product( $product_id ) {
		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}
		$this->product_delete_map[ $product_id ] = $post->post_type;
	}

	/**
	 * Make a note of the post parent if there is one prior to trashing / deleting
	 * a variant.
	 *
	 * @return void
	 */
	public function pre_delete_variation( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ||
			'product_variation' !== $post->post_type ||
			empty( $post->post_parent ) ) {
			return;
		}
		$this->parent_map[ $post_id ] = $post->post_parent;
	}

	/**
	 * Rebuild a cache for a variant.
	 *
	 * Triggers a rebuild of the main product since the minimum cache unit is
	 * the parent product, not individual variations.
	 *
	 * @param int $product_id The main product ID.
	 *
	 * @return void
	 */
	public function remove_variation( $product_id ) {
		$parent_id = isset( $this->parent_map[ $product_id ] ) ? $this->parent_map[ $product_id ] : null;
		if ( ! $parent_id ) {
			return;
		}
		$this->rebuild_product( $parent_id );
	}

	/**
	 * Rebuild a cache for a variant.
	 *
	 * Triggers a rebuild of the main product since the minimum cache unit is
	 * the parent product, not individual variations.
	 *
	 * @param int $product_id The main product ID.
	 *
	 * @return void
	 */
	public function save_variation( $product_id ) {
		$product   = wc_get_product( $product_id );
		$parent_id = $product->get_parent_id();
		$this->rebuild_product( $parent_id );
	}

	/**
	 * Rebuild the cache for a product.
	 *
	 * @param int $product_id The product ID of the product to rebuild.
	 *
	 * @return void
	 */
	public function rebuild_product( $product_id ) {
		$this->cache->flush_product( $product_id );
	}

	/**
	 * Rebuild the cache for all products attached to a term.
	 *
	 * @param int $term_id The term ID to refresh.
	 *
	 * @return void
	 */
	public function rebuild_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! $this->is_product_taxonomy( $taxonomy ) ) {
			// Invalid, or non-product taxonomy. Ignore it.
			return;
		}
		$this->cache->flush_term( $term_id, $tt_id, $taxonomy );
	}

	/**
	 * Handle term deletion. Rebuild for all affected products.
	 *
	 * $object_ids is a list of all objects related to the term that has been
	 * deleted. They *may* not all be products so the background job needs to
	 * validate them before rebuilding.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 *
	 * @return void
	 */
	public function rebuild_term_objects( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		if ( ! $this->is_product_taxonomy( $taxonomy ) ) {
			// Invalid, or non-product taxonomy. Ignore it.
			return;
		}
		$this->cache->flush_objects( $object_ids );
	}

	/**
	 * Rebuild the cache for all products.
	 *
	 * @return void
	 */
	public function rebuild_all() {
		$this->cache->flush_all();
	}

	/**
	 * @param $taxonomy
	 *
	 * @return bool
	 */
	private function is_product_taxonomy( $taxonomy ) {
		$taxonomy_definition = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_definition ) {
			// Invalid taxonomy passed. Not product related..
			return false;
		}
		if ( ! is_array( $taxonomy_definition->object_type ) ) {
			// No product types, associated.
			return false;
		}
		if ( in_array( 'product', $taxonomy_definition->object_type, true ) ) {
			// Is a product taxonomy
			return true;
		}

		// Not a product taxonomy.
		return false;
	}
}
