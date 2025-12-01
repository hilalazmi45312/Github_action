<?php

namespace Ademti\WoocommerceProductFeeds\Configuration;

use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;
use Exception;

class FeedConfigFactory {

	// Dependencies.
	private FeedConfigRepository $config_repository;
	private Configuration $configuration;

	/**
	 * @param FeedConfigRepository $config_repository
	 * @param Configuration $configuration
	 */
	public function __construct(
		FeedConfigRepository $config_repository,
		Configuration $configuration
	) {
		$this->config_repository = $config_repository;
		$this->configuration     = $configuration;
	}

	/**
	 * @return null|FeedConfig
	 * @throws Exception
	 */
	public function create_from_request() {
		global $wp_query;

		$all_feed_types = $this->configuration->get_feed_types();

		$requested_feed = get_query_var( 'woocommerce_gpf', null );
		if ( is_null( $requested_feed ) ) {
			return null;
		}

		// Try and load a predefined config from the database.
		$config = $this->config_repository->get( $requested_feed );
		if ( is_null( $config ) || ! isset( $all_feed_types[ $config->type ] ) ) {
			die( esc_html( __( 'Invalid feed requested', 'woocommerce_gpf' ) ) );
		}

		/**
		 * If we get here, we have a config. Check other query args to see if we need to override from them.
		 */

		// gpf_start
		$start = get_query_var( 'gpf_start', null );
		if ( ! is_null( $start ) ) {
			$config->set_start( $start );
		}

		// gpf_limit
		$limit = get_query_var( 'gpf_limit', null );
		if ( ! is_null( $limit ) ) {
			$config->set_limit( $limit );
		}

		// gpf_categories
		$categories = get_query_var( 'gpf_categories', null );
		if ( ! is_null( $categories ) ) {
			$config->set_categories( explode( ',', $categories ) );
			$config->set_category_filter( 'only' );
		}

		return apply_filters( 'woocommerce_gpf_config_from_request', $config, $wp_query );
	}
}
