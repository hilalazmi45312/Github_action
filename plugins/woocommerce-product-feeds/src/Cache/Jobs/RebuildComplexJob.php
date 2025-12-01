<?php

namespace Ademti\WoocommerceProductFeeds\Cache\Jobs;

class RebuildComplexJob extends AbstractCacheRebuildBatchJob {
	/*
	 * The action hook used for this job.
	 */
	protected string $action_hook = 'woocommerce_product_feeds_cache_rebuild_complex';

	/**
	 * Legacy class name for filters.
	 */
	protected string $legacy_class_name = 'WoocommerceGpfRebuildComplexJob';

	/**
	 * Array of product types that this job will handle.
	 */
	protected array $product_types = [ 'variable' ];
}
