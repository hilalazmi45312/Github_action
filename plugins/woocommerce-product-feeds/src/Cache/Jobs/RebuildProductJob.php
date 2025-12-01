<?php

namespace Ademti\WoocommerceProductFeeds\Cache\Jobs;

class RebuildProductJob extends AbstractCacheRebuildJob {

	/**
	 * The action hook used for this job.
	 */
	protected string $action_hook = 'woocommerce_product_feeds_cache_rebuild_product';

	/**
	 * The number of arguments our hooked function expects.
	 */
	protected int $action_hook_arg_count = 1;

	/**
	 * Process the rebuild.
	 *
	 * @param int $post_id The post ID to process.
	 *
	 * @return void
	 */
	public function task( $post_id ) {

		if ( ! $post_id ) {
			return;
		}

		$this->initialise_rebuild();
		$this->clear_user_context();

		$post = get_post( $post_id );

		// If we get here either it exists, and is a product, or has been
		// deleted.
		// If it's deleted, or trashed, we just clear down the cache for the
		// post, otherwise we clear down and rebuild the cache for it.
		if ( $post && 'trash' !== $post->post_status ) {
			$this->drop_post_cache( $post_id );
			$this->clear_user_context();
			$this->rebuild_item( $post_id );
			$this->restore_user_context();
		} else {
			$this->drop_post_cache( $post_id );
		}

		$this->restore_user_context();
	}

	/**
	 * Clear existing cache item.
	 *
	 * @param int $post_id The post ID to drop.
	 */
	private function drop_post_cache( $post_id ): void {
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
