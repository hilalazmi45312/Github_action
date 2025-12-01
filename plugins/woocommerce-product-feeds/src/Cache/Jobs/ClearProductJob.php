<?php
namespace Ademti\WoocommerceProductFeeds\Cache\Jobs;

class ClearProductJob extends AbstractCacheRebuildJob {

	/**
	 * The action hook used for this job.
	 */
	protected string $action_hook = 'woocommerce_product_feeds_cache_clear_product';

	/**
	 * The number of arguments our hooked function expects.
	 */
	protected int $action_hook_arg_count = 1;

	/**
	 * Process the rebuild.
	 *
	 * @param array $post_id The post ID to process.
	 */
	public function task( $post_id ): void {
		global $wpdb, $table_prefix;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE post_id = %d',
				$table_prefix . 'wc_gpf_render_cache',
				$post_id
			)
		);
	}
}
