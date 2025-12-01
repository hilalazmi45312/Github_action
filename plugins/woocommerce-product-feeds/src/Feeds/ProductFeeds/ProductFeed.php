<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds;

use Ademti\WoocommerceProductFeeds\Cache\Cache;
use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;
use Ademti\WoocommerceProductFeeds\Helpers\DebugService;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemExclusionService;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemFactory;
use Ademti\WoocommerceProductFeeds\Traits\ClearsUserContext;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use WC_Product;
use WC_Product_Variable;
use WP_Post;
use WP_User;
use function _prime_post_caches;
use function add_filter;
use function apply_filters;
use function array_map;
use function count;
use function define;
use function defined;
use function do_action;
use function header;
use function headers_sent;
use function ob_end_clean;
use function ob_get_level;
use function remove_filter;
use function set_time_limit;
use function wc_get_product;
use function wc_get_products;
use function wp_cache_flush_group;
use function wp_json_encode;

/*
 * Handles grabbing the products and invoking the relevant feed class to render the feed.
 */
class ProductFeed {

	use ClearsUserContext;

	// Dependencies.
	private Configuration $configuration;
	private Cache $cache;
	private Container $container;
	private DebugService $debug;
	private ProductFeedItemFactory $feed_item_factory;
	private ProductFeedItemExclusionService $exclusion_service;

	/**
	 * The class used for rendering the feed.
	 */
	protected ?AbstractProductFeedRenderer $feed;

	/**
	 * The settings.
	 */
	protected array $settings = [];

	/**
	 * The config of the feed we are processing.
	 */
	private FeedConfig $feed_config;

	/**
	 * @var WP_User|null
	 */
	private ?WP_User $original_user;

	/**
	 * Constructor. Add filters if we have stuff to do
	 *
	 * @access public
	 *
	 * @param Configuration $configuration
	 * @param Cache $cache
	 * @param DebugService $debug
	 * @param ProductFeedItemFactory $feed_item_factory
	 * @param ProductFeedItemExclusionService $exclusion_service
	 * @param Container $container
	 */
	public function __construct(
		Configuration $configuration,
		Cache $cache,
		DebugService $debug,
		ProductFeedItemFactory $feed_item_factory,
		ProductFeedItemExclusionService $exclusion_service,
		Container $container
	) {
		$this->configuration     = $configuration;
		$this->cache             = $cache;
		$this->debug             = $debug;
		$this->container         = $container;
		$this->feed_item_factory = $feed_item_factory;
		$this->exclusion_service = $exclusion_service;
	}

	/**
	 * Load the settings, and hook in so that we can generate the feed.
	 *
	 * @param FeedConfig $feed_config
	 */
	public function initialise( FeedConfig $feed_config ): void {
		// Store the config.
		$this->feed_config = $feed_config;

		// Get the info we need to look up the right class.
		$all_feed_types = $this->configuration->get_feed_types();

		// Load the settings.
		$this->settings = $this->configuration->get_settings();

		// Look up the right class to handle rendering the feed.
		$class = $all_feed_types[ $this->feed_config->type ]['class'];

		// Add hooks for future processing.
		add_action( 'template_redirect', [ $this, 'render_product_feed' ], 15 );
		if ( $this->has_category_limit() ) {
			add_filter(
				'woocommerce_product_data_store_cpt_get_products_query',
				[
					$this,
					'limit_query_by_category',
				],
				10,
				2
			);
		}

		// Instantiate the feed class.
		$this->feed = $this->container[ $class ];
		if ( method_exists( $this->feed, 'initialise' ) ) {
			$this->feed->initialise( $this->feed_config );
		}
	}

	/**
	 * Set a number of optimisations to make sure the plugin is usable on lower end setups.
	 *
	 * We stop plugins trying to cache, or compress the output since that causes everything to be
	 * held in memory and causes memory issues.
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator)
	 */
	private function set_optimisations(): void {

		global $wpdb;

		// Don't cache feed.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-store, must-revalidate, max-age=0' );
		}

		// Cater for large stores.
		$wpdb->hide_errors();
		@set_time_limit( 0 );
		while ( ob_get_level() ) {
			@ob_end_clean();
		}

		// Disable term ordering by Advanced Taxonomy Terms Order (http://www.nsp-code.com)
		// as it has horrible performance characteristics.
		add_filter( 'atto/ignore_get_object_terms', '__return_true', 9999 );
		remove_filter( 'terms_clauses', 'to_terms_clauses', 99 );

		// Disable query monitor.
		// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
		do_action( 'qm/cease' );
		// phpcs:enable
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function log_query_args( array $args ): array {
		$this->debug->log( 'Query args:' . wp_json_encode( $args, JSON_PRETTY_PRINT ) );

		return $args;
	}

	/**
	 * Generate the query function to use, and argument array.
	 *
	 * Identifies the query function to be used to retrieve products, either
	 * WordPress' get_posts(), or wc_get_products() depending on whether
	 * wc_get_products() is available.
	 *
	 * Also constructs the base arguments array to be passed to the query
	 * function.
	 *
	 * @param int $chunk_size The number of products to be retrieved per
	 *                             query.
	 *
	 * @return array               The arguments array.
	 */
	private function get_query_args( int $chunk_size ): array {
		$args = [
			'status'  => [ 'publish' ],
			'type'    => [ 'simple', 'variable' ],
			'limit'   => $chunk_size,
			'offset'  => (int) $this->feed_config->start,
			'orderby' => 'ID',
			'order'   => 'ASC',
		];
		if ( $this->cache->is_enabled() ) {
			$args['return'] = 'ids';
		}

		return apply_filters(
			'woocommerce_gpf_wc_get_products_args',
			$args,
			'feed'
		);
	}

	/**
	 * @return bool
	 */
	private function has_category_limit(): bool {
		$categories = array_map( 'intval', $this->feed_config->categories );

		return ! empty( $categories ) &&
				'' !== $this->feed_config->category_filter;
	}

	/**
	 * Apply category limits to the query.
	 *
	 * @param array $query
	 * @param array $query_vars
	 *
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	public function limit_query_by_category( array $query, array $query_vars ): array {
		$categories = array_map( 'intval', $this->feed_config->categories );

		$tax_query = [
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $categories,
		];
		if ( 'except' === $this->feed_config->category_filter ) {
			$tax_query['operator'] = 'NOT IN';
		}
		$query['tax_query'][] = $tax_query;

		return $query;
	}
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Render the product feed requests - calls the sub-classes according
	 * to the feed required.
	 */
	public function render_product_feed(): void {

		global $_wp_using_ext_object_cache;

		$this->set_optimisations();

		$this->clear_user_context();

		$this->feed->render_header();

		if ( $this->cache->is_enabled() ) {
			$chunk_size = 100;
		} else {
			$chunk_size = 10;
		}
		$chunk_size = apply_filters( 'woocommerce_gpf_chunk_size', $chunk_size, $this->cache->is_enabled() );

		$args = $this->get_query_args( $chunk_size );

		if ( $this->debug->debug_active() ) {
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'log_query_args' ], 99999 );
		}

		$output_count = 0;
		$limit        = $this->feed_config->limit;

		// Note: $products will be:
		// - post IDs if the cache is enabled
		// - WC_Product objects if cache is disabled
		$products      = wc_get_products( $args );
		$product_count = count( $products );

		$this->debug->log( 'Retrieved %d products', [ $product_count ] );
		while ( $product_count ) {

			// First, if enabled, we output any that we have in the cache.
			if ( $this->cache->is_enabled() ) {
				$outputs = $this->cache->fetch_multi( $products, $this->feed_config->type );
				foreach ( $outputs as $product_id => $output ) {
					if ( ! empty( $output ) ) {
						$this->debug->log( 'Retrieved %d from cache', [ $product_id ] );
						echo $outputs[ $product_id ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						++$output_count;
					} else {
						$this->debug->log( 'Retrieved empty record from cache for %d', [ $product_id ] );
					}
					if ( -1 !== $limit && $output_count >= $limit ) {
						$this->debug->log( '[%d] Reached limit (%d). Exiting.', [ __LINE__, $limit ] );
						break 2; // Break out of the containing while loop
					}
				}
				// Remove any we got from the list to be generated.
				$products = array_diff( $products, array_keys( $outputs ) );
			}

			// If we have any still to generate, go do them.
			foreach ( $products as $product ) {
				if ( $this->process_product( $product ) ) {
					++$output_count;
				}
				// Quit if we've done all the products
				if ( -1 !== $limit && $output_count >= $limit ) {
					$this->debug->log( '[%d] Reached limit (%d). Exiting.', [ __LINE__, $limit ] );
					break 2; // Break out of the containing while loop
				}
			}

			$args['offset'] += $chunk_size;

			// If we're using the built-in object cache then flush it every chunk so
			// that we don't keep churning through memory.
			if ( ! $_wp_using_ext_object_cache ) {
				wp_cache_flush_group( 'posts' );
				wp_cache_flush_group( 'products' );
			}

			$products      = wc_get_products( $args );
			$product_count = count( $products );

			$this->debug->log( 'Retrieved %d products', [ $product_count ] );
		}
		$this->feed->render_footer();

		// Just in case render_footer() doesn't exit.
		$this->restore_user_context();
	}


	/**
	 * Process a product, outputting its information.
	 *
	 * Uses process_simple_product() to process simple products, or all products if variation
	 * support is disabled. Uses process_variable_product() to process variable products.
	 *
	 * @param int|WC_Product|WP_Post $product
	 *
	 * @return bool                  True if one or more products were output,
	 *                               false otherwise.
	 */
	private function process_product( $product ): bool {

		// Make sure we have a WC_Product.
		if ( is_int( $product ) ) {
			$woocommerce_product = wc_get_product( $product );
		} else {
			$woocommerce_product = $product;
		}
		// WC's product query can return IDs that don't resolve to actual products.
		if ( empty( $woocommerce_product ) ) {
			return false;
		}
		$product_type = $woocommerce_product->get_type();
		$this->debug->log( 'Processing %s product (%d)', [ $product_type, $woocommerce_product->get_id() ] );

		$include_variations = apply_filters(
			'woocommerce_gpf_include_variations',
			! empty( $this->settings['include_variations'] ),
			$woocommerce_product
		);
		if ( $woocommerce_product instanceof WC_Product_Variable && $include_variations ) {
			return $this->process_variable_product( $woocommerce_product );
		}

		return $this->process_simple_product( $woocommerce_product );
	}

	/**
	 * Process a simple product, and output its elements.
	 *
	 * @param WC_Product $woocommerce_product WooCommerce Product Object (May not be Simple)
	 *
	 * @return bool                          True if one or more products were output, false
	 *                                       otherwise.
	 */
	private function process_simple_product( WC_Product $woocommerce_product ): bool {
		// Check whether it should be excluded
		if ( $this->exclusion_service->should_exclude( $woocommerce_product, $this->feed_config->type ) ) {
			$this->debug->log( '%d excluded, skipping...', [ $woocommerce_product->get_id() ] );
			$this->cache->store( $woocommerce_product->get_id(), $this->feed_config->type, '' );

			return false;
		}
		// Construct the data for this item.
		$feed_item = $this->feed_item_factory->create(
			$this->feed_config->type,
			$woocommerce_product,
			$woocommerce_product
		);

		$output = apply_filters(
			'woocommerce_gpf_render_item_output_' . $this->feed_config->type,
			$this->feed->render_item( $feed_item ),
			$feed_item,
			$woocommerce_product
		);
		$this->cache->store( $woocommerce_product->get_id(), $this->feed_config->type, $output );
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return ! empty( $output );
	}

	/**
	 * Process a variable product, and output its elements.
	 *
	 * @param WC_Product $woocommerce_product WooCommerce Product Object
	 *
	 * @return bool                          True if one or more products were output, false
	 *                                       otherwise.
	 */
	private function process_variable_product( WC_Product $woocommerce_product ): bool {

		// Check if the whole product is excluded.
		if ( $this->exclusion_service->should_exclude( $woocommerce_product, $this->feed_config->type ) ) {
			$this->cache->store( $woocommerce_product->get_id(), $this->feed_config->type, '' );
			$this->debug->log( '%d excluded, skipping...', [ $woocommerce_product->get_id() ] );

			return false;
		}
		$variation_ids = $woocommerce_product->get_children();
		_prime_post_caches( $variation_ids, true, false );
		$output = '';
		foreach ( $variation_ids as $variation_id ) {
			// Get the variation product.
			$variation_product = wc_get_product( $variation_id );
			if ( ! $variation_product ) {
				continue;
			}
			// Skip to the next variation if this one isn't to be included.
			if ( $this->exclusion_service->should_exclude( $variation_product, $this->feed_config->type ) ) {
				$this->debug->log( 'variation %d is excluded', [ $variation_id ] );
				continue;
			}
			$feed_item = $this->feed_item_factory->create(
				$this->feed_config->type,
				$variation_product,
				$woocommerce_product
			);

			// Render it.
			$output .= $this->feed->render_item( $feed_item );
		}
		$this->cache->store( $woocommerce_product->get_id(), $this->feed_config->type, $output );
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return ! empty( $output );
	}

	/**
	 * Ensures that product data is generated without any user context.
	 * @return void
	 */
	private function clear_user_context() {
		$this->original_user = wp_get_current_user();
		if ( $this->original_user->ID !== 0 ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Restores the user context after generation.
	 * @return void
	 */
	private function restore_user_context() {
		if ( ! empty( $this->original_user ) ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
			wp_set_current_user( $this->original_user->ID );
		}
	}
}
