<?php

namespace Ademti\WoocommerceProductFeeds\Features;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Features\SetupTasks\SetupTasks;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Psr11\ServiceLocator;

class FeatureManager {

	// Dependencies.
	private AddToCartByFeedIdSupport $add_to_cart_by_feed_id_support;
	private Configuration $configuration;
	private RestApi $rest_api;
	private ServiceLocator $feature_service_locator;
	private SetupTasks $setup_tasks;

	/**
	 * @param  AddToCartByFeedIdSupport  $add_to_cart_by_feed_id_support
	 * @param  ServiceLocator  $feature_service_locator
	 * @param  RestApi  $rest_api
	 * @param  SetupTasks  $setup_tasks
	 * @param  Configuration  $configuration
	 */
	public function __construct(
		AddToCartByFeedIdSupport $add_to_cart_by_feed_id_support,
		ServiceLocator $feature_service_locator,
		RestApi $rest_api,
		SetupTasks $setup_tasks,
		Configuration $configuration
	) {
		$this->add_to_cart_by_feed_id_support = $add_to_cart_by_feed_id_support;
		$this->configuration                  = $configuration;
		$this->feature_service_locator        = $feature_service_locator;
		$this->rest_api                       = $rest_api;
		$this->setup_tasks                    = $setup_tasks;
	}

	/**
	 * Run the required features.
	 *
	 * @return void
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\ContainerExceptionInterface
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\NotFoundExceptionInterface
	 */
	public function run(): void {
		add_action( 'init', [ $this, 'structured_data_feature' ] );
		$this->add_to_cart_by_feed_id_support->initialise();
		$this->rest_api->initialise();
		$this->setup_tasks->initialise();
	}

	/**
	 * @return void
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\ContainerExceptionInterface
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\NotFoundExceptionInterface
	 */
	public function structured_data_feature(): void {
		$settings            = $this->configuration->get_settings();
		$use_expanded_schema = isset( $settings['expanded_schema'] ) && 'on' === $settings['expanded_schema'];
		if ( $use_expanded_schema ) {
			$this->feature_service_locator->get( 'ExpandedStructuredData' )->initialise();
			$this->feature_service_locator->get( 'ExpandedStructuredDataCacheInvalidator' )->initialise();
		} else {
			$this->feature_service_locator->get( 'StructuredData' )->initialise();
		}
	}
}
