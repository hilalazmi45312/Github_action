<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Cache\Cache;
use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use Exception;

/**
 * Class CacheStatus
 *
 * Handles generation of information relating to the cache for the status report.
 */
class CacheStatus {

	// Dependencies.
	protected Cache $cache;
	protected Configuration $configuration;
	protected TemplateLoader $template_loader;
	protected FeedConfigRepository $config_repository;

	/**
	 * WoocommerceGpfCacheStatus constructor.
	 *
	 * @param Configuration $configuration
	 * @param Cache $cache
	 * @param TemplateLoader $woocommerce_gpf_template_loader
	 * @param FeedConfigRepository $config_repository
	 */
	public function __construct(
		Configuration $configuration,
		Cache $cache,
		TemplateLoader $woocommerce_gpf_template_loader,
		FeedConfigRepository $config_repository
	) {
		$this->cache             = $cache;
		$this->configuration     = $configuration;
		$this->template_loader   = $woocommerce_gpf_template_loader;
		$this->config_repository = $config_repository;
	}

	/**
	 * @param string $output
	 * @param string $settings_url
	 *
	 * @return string
	 * @throws Exception
	 */
	public function generate_status_output( string $output, string $settings_url ): string {

		global $wpdb, $table_prefix;

		if ( ! $this->cache->is_enabled() ) {
			return $output;
		}

		$active_feed_formats = $this->config_repository->get_active_feed_formats();
		// Work out how many products we have cached per-feed type.
		$status  = array_fill_keys( array_values( $active_feed_formats ), 0 );
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `name`,
			      COUNT(DISTINCT(post_id)) AS total
				   FROM %i
			   GROUP BY `name`',
				$table_prefix . 'wc_gpf_render_cache'
			),
			OBJECT_K
		);
		$results = wp_list_pluck( $results, 'total' );
		$status  = array_merge( $status, $results );

		// Work out the total number of eligible products.
		$args = [
			'status'   => [ 'publish' ],
			'type'     => [ 'simple', 'variable', 'bundle' ],
			'limit'    => 1,
			'offset'   => 0,
			'return'   => 'ids',
			'paginate' => true,
		];

		$results     = wc_get_products(
			apply_filters( 'woocommerce_gpf_wc_get_products_args', $args, 'status' )
		);
		$total_cache = $results->total;
		$rebuild_url = wp_nonce_url(
			add_query_arg(
				[
					'gpf_action' => 'rebuild_cache',
				],
				$settings_url
			),
			'gpf_rebuild_cache'
		);

		$all_feed_types = $this->configuration->get_feed_types();

		$status_items = '';
		foreach ( $active_feed_formats as $feed_type ) {
			if ( $all_feed_types[ $feed_type ]['cacheable'] !== true ) {
				continue;
			}
			$status_items .= $this->template_loader->get_template_with_variables(
				'woo-gpf',
				'admin-cache-status-item',
				[
					'name'   => $all_feed_types[ $feed_type ]['plural_name'],
					// Translators: Placeholders represent the number of items processed, and the total to be generated, e.g. 5 / 10
					'status' => sprintf( __( '<strong>%1$d</strong> / <strong>%2$d</strong> generated', 'woocommerce_gpf' ), $status[ $feed_type ], $total_cache ),
					'total'  => $total_cache,
				]
			);
		}
		$msg                 = '';
		$pending_clear_all   = as_get_scheduled_actions(
			[
				'hook'     => 'woocommerce_product_feeds_cache_clear_all',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
				'orderby'  => 'none',
			],
			'ids'
		);
		$pending_rebuild_all = as_get_scheduled_actions(
			[
				'hook'     => 'woocommerce_product_feeds_cache_rebuild_all',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
				'orderby'  => 'none',
			],
			'ids'
		);
		if ( ! empty( $pending_clear_all ) || ! empty( $pending_rebuild_all ) ) {
			$msg = $this->template_loader->get_template_with_variables(
				'woo-gpf',
				'admin-cache-rebuild-scheduled',
				[
					'msg' => esc_html( __( '** Cache rebuild scheduled **', 'woocommerce_gpf' ) ),
				]
			);
		}
		$cache_status_variables = [
			'status_items' => $status_items,
			'rebuild_url'  => $rebuild_url,
			'settings_url' => $settings_url,
			'msg'          => $msg,
		];

		return $this->template_loader->get_template_with_variables(
			'woo-gpf',
			'admin-cache-status',
			$cache_status_variables
		);
	}
}
