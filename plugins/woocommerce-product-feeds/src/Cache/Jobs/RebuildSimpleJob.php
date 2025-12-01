<?php

namespace Ademti\WoocommerceProductFeeds\Cache\Jobs;

class RebuildSimpleJob extends AbstractCacheRebuildBatchJob {
	/**
	 * The action hook used for this job.
	 */
	protected string $action_hook = 'woocommerce_product_feeds_cache_rebuild_simple';

	/**
	 * Legacy class name for filters.
	 */
	protected string $legacy_class_name = 'WoocommerceGpfRebuildSimpleJob';

	/**
	 * Array of product types that this job will handle.
	 */
	protected array $product_types = [ 'simple' ];
}
