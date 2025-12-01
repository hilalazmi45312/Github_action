<?php

namespace Ademti\WoocommerceProductFeeds\Cache\Jobs;

use Ademti\WoocommerceProductFeeds\Cache\Cache;
use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemExclusionService;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemFactory;
use Ademti\WoocommerceProductFeeds\Traits\ClearsUserContext;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use Exception;
use WC_Product_Variable;
use WP_User;

abstract class AbstractCacheRebuildJob {

	use ClearsUserContext;

	// Dependencies.
	protected Cache $cache;
	protected Configuration $configuration;
	protected Container $container;
	protected FeedConfigRepository $feed_config_repository;
	protected ProductFeedItemFactory $feed_item_factory;
	protected ProductFeedItemExclusionService $exclusion_service;

	/**
	 * Array of product feed formats which will be rebuilt.
	 */
	private array $feed_formats;

	/**
	 * Array of non-product feed formats which will be rebuilt.
	 */
	private array $non_product_feed_formats;

	/**
	 * Instances of the feed handling classes.
	 */
	private array $feed_handlers;

	/**
	 * @var string  The hook used for this job.
	 */
	protected string $action_hook;

	/**
	 * @var int The number of arguments our hooked function expects.
	 */
	protected int $action_hook_arg_count = 0;

	/**
	 * Constructor.
	 *
	 * Store dependencies, and attach action callback.
	 *
	 * @param Configuration $configuration
	 * @param Cache $cache
	 * @param FeedConfigRepository $feed_config_repository
	 * @param ProductFeedItemFactory $feed_item_factory
	 * @param ProductFeedItemExclusionService $exclusion_service
	 * @param Container $container
	 */
	public function __construct(
		Configuration $configuration,
		Cache $cache,
		FeedConfigRepository $feed_config_repository,
		ProductFeedItemFactory $feed_item_factory,
		ProductFeedItemExclusionService $exclusion_service,
		Container $container
	) {
		$this->configuration          = $configuration;
		$this->cache                  = $cache;
		$this->feed_config_repository = $feed_config_repository;
		$this->feed_item_factory      = $feed_item_factory;
		$this->exclusion_service      = $exclusion_service;
		$this->container              = $container;
		add_action( $this->action_hook, [ $this, 'task' ], 10, $this->action_hook_arg_count );
	}

	/**
	 * Initialise the classes we need to perform rebuilds, and set up some optimisations.
	 *
	 * @SuppressWarnings (PHPMD.ErrorControlOperator)
	 */
	public function initialise_rebuild(): void {
		$feed_types                     = $this->configuration->get_feed_types();
		$this->feed_formats             = [];
		$this->non_product_feed_formats = [];

		// Build the feed handlers array.
		foreach ( array_keys( $feed_types ) as $feed_id ) {
			$class                           = $feed_types[ $feed_id ]['class'];
			$this->feed_handlers[ $feed_id ] = $this->container[ $class ];
		}
		$all_feed_formats = $this->feed_config_repository->get_active_feed_formats();
		foreach ( $all_feed_formats as $feed_format ) {
			if ( $feed_types[ $feed_format ]['cacheable'] !== true ) {
				continue;
			}
			if ( 'product' === $feed_types[ $feed_format ]['type'] ) {
				$this->feed_formats[] = $feed_format;
			} else {
				$this->non_product_feed_formats[] = $feed_format;
			}
		}

		// Disable term ordering by Advanced Taxonomy Terms Order from
		// (http://www.nsp-code.com) as it has horrible performance
		// characteristics.
		add_filter( 'atto/ignore_get_object_terms', '__return_true', 9999 );
		if ( has_filter( 'terms_clauses', 'to_terms_clauses' ) ) {
			remove_filter( 'terms_clauses', 'to_terms_clauses', 99 );
		} else {
			add_action(
				'plugins_loaded',
				function () {
					remove_filter( 'terms_clauses', 'to_terms_clauses', 99 );
				}
			);
		}
	}

	/**
	 * Cancel Process
	 *
	 * Stop processing all queue items and clear jobs of this type.
	 */
	public function cancel_all(): void {
		as_unschedule_all_actions( $this->action_hook );
	}

	/**
	 * Rebuild a specific item.
	 *
	 * @param $product_id
	 *
	 * @return bool|void
	 */
	protected function rebuild_item( int $product_id ) {
		// Load the settings.
		$settings = $this->configuration->get_settings();

		$woocommerce_product = wc_get_product( $product_id );
		if ( empty( $woocommerce_product ) ) {
			// It is not a product. We are done.
			return;
		}

		/**
		 * Handle rebuild for non-product feed types.
		 */
		foreach ( $this->non_product_feed_formats as $feed_id ) {
			$this->feed_handlers[ $feed_id ]->rebuild_item( $woocommerce_product );
		}

		$include_variations = apply_filters(
			'woocommerce_gpf_include_variations',
			! empty( $settings['include_variations'] ),
			$woocommerce_product
		);
		if ( $woocommerce_product instanceof WC_Product_Variable &&
			$include_variations ) {
			return $this->process_variable_product( $woocommerce_product );
		}

		return $this->process_simple_product( $woocommerce_product );
	}

	/**
	 * Process a simple product.
	 *
	 * @return bool
	 */
	protected function process_simple_product( $woocommerce_product ) {

		foreach ( $this->feed_formats as $feed_format ) {
			// Construct the data for this item.
			if ( $this->exclusion_service->should_exclude( $woocommerce_product, $feed_format ) ) {
				$this->cache->store( $woocommerce_product->ID, $feed_format, '' );
				continue;
			}
			$feed_item = $this->feed_item_factory->create( $feed_format, $woocommerce_product, $woocommerce_product );

			// Render it.
			$output = $this->feed_handlers[ $feed_format ]->render_item( $feed_item );

			// Store it to the cache.
			$this->cache->store( $feed_item->general_id, $feed_format, $output );
		}

		return true;
	}

	/**
	 * Process a variable product.
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function process_variable_product( WC_Product_Variable $woocommerce_product ) {
		$variation_ids = $woocommerce_product->get_children();
		foreach ( $this->feed_formats as $feed_format ) {
			$output = '';
			// Skip if the parent product is excluded for this feed type.
			if ( $this->exclusion_service->should_exclude( $woocommerce_product, $feed_format ) ) {
				$this->cache->store( $woocommerce_product->get_id(), $feed_format, '' );
				continue;
			}
			foreach ( $variation_ids as $variation_id ) {
				// Get the variation product.
				$variation_product = wc_get_product( $variation_id );
				if ( ! $variation_product ) {
					continue;
				}
				// Skip to the next if this variation isn't to be included.
				if ( $this->exclusion_service->should_exclude( $variation_product, $feed_format ) ) {
					continue;
				}
				$feed_item = $this->feed_item_factory->create(
					$feed_format,
					$variation_product,
					$woocommerce_product
				);
				// Render it.
				$output .= $this->feed_handlers[ $feed_format ]->render_item( $feed_item );
			}
			$this->cache->store( $woocommerce_product->get_id(), $feed_format, $output );
		}

		return true;
	}
}
