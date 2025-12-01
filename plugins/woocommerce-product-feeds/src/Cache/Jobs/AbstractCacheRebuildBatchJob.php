<?php

namespace Ademti\WoocommerceProductFeeds\Cache\Jobs;

class AbstractCacheRebuildBatchJob extends AbstractCacheRebuildJob {

	/**
	 * Array of product types that this job will handle.
	 */
	protected array $product_types = [];

	/**
	 * The number of arguments our hooked function expects.
	 */
	protected int $action_hook_arg_count = 3;

	/**
	 * Temporary storage of term filtering requirements.
	 */
	protected array $term_filter;

	/**
	 * Support passing the legacy job class name to interested filters to support 3rd party code.
	 */
	protected string $legacy_class_name = __CLASS__;

	/**
	 * Task controller.
	 *
	 * Takes care of processing the current sub-task, and either re-pushing back
	 * to the queue for the next sub-task or completing the item.
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param array $term_filter
	 */
	public function task( int $offset, int $limit, array $term_filter = [] ): void {

		$this->initialise_rebuild();
		$this->clear_user_context();
		$this->term_filter = $term_filter;

		// Grab the products
		$args = apply_filters(
			'woocommerce_gpf_wc_get_products_args',
			[
				'status'  => [ 'publish' ],
				'type'    => $this->product_types,
				'limit'   => $limit,
				'offset'  => $offset,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'return'  => 'ids',
			],
			get_class( $this )
		);
		// Also filter with the legacy job name, just in case.
		$args = apply_filters(
			'woocommerce_gpf_wc_get_products_args',
			$args,
			$this->legacy_class_name
		);

		$this->clear_user_context();

		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'filter_query' ], 10, 2 );
		$ids = wc_get_products( $args );
		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'filter_query' ], 10 );

		// Rebuild the cache for the items.
		foreach ( $ids as $id ) {
			$this->rebuild_item( $id );
		}

		$this->restore_user_context();

		// Bail if we've completed.
		if ( count( $ids ) < $limit ) {
			$this->restore_user_context();
			return;
		}

		// Queue up the next chunk.
		as_schedule_single_action(
			time(),
			$this->action_hook,
			[
				$offset + $limit,
				$limit,
				$term_filter,
			],
			'woocommerce-product-feeds'
		);
		$this->restore_user_context();
	}

	/**
	 * Handle requirement to filter by term attachment.
	 *
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Product_Query.
	 *
	 * @return array modified $query
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function filter_query( $query, $query_vars ) {
		if ( empty( $this->term_filter['taxonomy'] ) ||
			empty( $this->term_filter['term_id'] ) ) {
			return $query;
		}
		$query['tax_query'][] = [
			'taxonomy' => $this->term_filter['taxonomy'],
			'field'    => 'term_id',
			'terms'    => $this->term_filter['term_id'],
		];

		return $query;
	}
}
