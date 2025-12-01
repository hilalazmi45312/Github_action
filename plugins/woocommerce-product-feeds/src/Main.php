<?php

namespace Ademti\WoocommerceProductFeeds;

use Ademti\WoocommerceProductFeeds\Admin\AdminManager;
use Ademti\WoocommerceProductFeeds\Cache\Cache;
use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigFactory;
use Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\ContainerExceptionInterface;
use Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\NotFoundExceptionInterface;
use Ademti\WoocommerceProductFeeds\Features\FeatureManager;
use Ademti\WoocommerceProductFeeds\Helpers\IntegrationManager;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Psr11\ServiceLocator;
use Ademti\WoocommerceProductFeeds\Jobs\JobManager;

class Main {

	// Dependencies.
	private AdminManager $admin_manager;
	private Cache $cache;
	private Configuration $configuration;
	private FeatureManager $feature_manager;
	private FeedConfigFactory $feed_config_factory;
	private ServiceLocator $main_service_locator;
	private IntegrationManager $integration_manager;
	private JobManager $job_manager;

	/**
	 * WoocommerceProductFeedsMain constructor.
	 *
	 * @param AdminManager $admin_manager
	 * @param Cache $cache
	 * @param Configuration $configuration
	 * @param FeatureManager $feature_manager
	 * @param FeedConfigFactory $feed_config_factory
	 * @param ServiceLocator $main_service_locator
	 * @param IntegrationManager $integration_manager
	 * @param JobManager $job_manager
	 */
	public function __construct(
		AdminManager $admin_manager,
		Cache $cache,
		Configuration $configuration,
		FeatureManager $feature_manager,
		FeedConfigFactory $feed_config_factory,
		ServiceLocator $main_service_locator,
		IntegrationManager $integration_manager,
		JobManager $job_manager
	) {
		$this->admin_manager        = $admin_manager;
		$this->cache                = $cache;
		$this->configuration        = $configuration;
		$this->feature_manager      = $feature_manager;
		$this->feed_config_factory  = $feed_config_factory;
		$this->integration_manager  = $integration_manager;
		$this->main_service_locator = $main_service_locator;
		$this->job_manager          = $job_manager;
	}

	/**
	 * @return void
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\ContainerExceptionInterface
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\NotFoundExceptionInterface
	 */
	public function run(): void {
		if ( is_admin() ) {
			$this->admin_manager->run();
		}
		$this->job_manager->run();
		$this->feature_manager->run();
		$this->cache->initialise();

		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'plugins_loaded', [ $this->configuration, 'initialise' ], 2 );
		add_action( 'plugins_loaded', [ $this, 'initialise_cache_invalidator' ], 11 );
		add_action( 'plugins_loaded', [ $this->integration_manager, 'initialise' ] );
		add_action( 'plugins_loaded', [ $this, 'block_wordpress_gzip_compression' ] );
		add_action( 'init', [ $this, 'register_endpoints' ] );
		add_action( 'template_redirect', [ $this, 'trigger_feeds' ] );
		add_filter(
			'woocommerce_customer_default_location_array',
			[ $this, 'set_customer_default_location' ]
		);
		add_filter( 'http_request_args', [ $this, 'prevent_wporg_update_check' ], 10, 2 );
	}

	/**
	 * Initialise a cache invalidator if the cache is active.
	 *
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function initialise_cache_invalidator() {
		if ( $this->cache->is_enabled() ) {
			$this->main_service_locator->get( 'CacheInvalidator' )->initialise();
		}
	}

	/**
	 * Block wordpress.org plugins with similar names overwriting through WordPress' update mechanism.
	 *
	 * @param array $request
	 * @param string|null $url
	 *
	 * @return array
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator)
	 */
	public function prevent_wporg_update_check( array $request, ?string $url ): array {
		if ( 0 === strpos( $url, 'https://api.wordpress.org/plugins/update-check/' ) ) {
			$my_plugin = plugin_basename( __FILE__ );
			$plugins   = @json_decode( $request['body']['plugins'], true );
			if ( null === $plugins ) {
				return $request;
			}
			// Freemius updater creates a request without the active array set.
			if ( isset( $plugins['active'] ) && is_array( $plugins['active'] ) ) {
				unset( $plugins['active'][ array_search( $my_plugin, $plugins['active'], true ) ] );
			}
			unset( $plugins['plugins'][ $my_plugin ] );
			$request['body']['plugins'] = wp_json_encode( $plugins );
		}

		return $request;
	}

	/**
	 * Disable attempts to GZIP the feed output to avoid memory issues.
	 */
	public function block_wordpress_gzip_compression(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['woocommerce_gpf'] ) ) {
			remove_action( 'init', 'ezgz_buffer' );
		}
	}

	/**
	 * Override the default customer address.
	 *
	 * @param array $location
	 *
	 * @return array
	 */
	public function set_customer_default_location( array $location ): array {
		if ( woocommerce_gpf_is_generating_feed() ) {
			return wc_format_country_state_string( get_option( 'woocommerce_default_country' ) );
		}

		return $location;
	}

	/**
	 * Register our rewrite rules & tags.
	 */
	public function register_endpoints(): void {
		add_rewrite_tag( '%woocommerce_gpf%', '([^/]+)' );
		add_rewrite_tag( '%gpf_start%', '([0-9]{1,})' );
		add_rewrite_tag( '%gpf_limit%', '([0-9]{1,})' );
		add_rewrite_tag( '%gpf_categories%', '^(\d+(,\d+)*)?$' );

		add_rewrite_rule( 'woocommerce_gpf/([^/]+)/gpf_start/([0-9]{1,})/gpf_limit/([0-9]{1,})/gpf_categories/(\d+(,\d+)*)', 'index.php?woocommerce_gpf=$matches[1]&gpf_start=$matches[2]&gpf_limit=$matches[3]&gpf_categories=$matches[4]', 'top' );
		add_rewrite_rule( 'woocommerce_gpf/([^/]+)/gpf_start/([0-9]{1,})/gpf_limit/([0-9]{1,})', 'index.php?woocommerce_gpf=$matches[1]&gpf_start=$matches[2]&gpf_limit=$matches[3]', 'top' );
		add_rewrite_rule( 'woocommerce_gpf/([^/]+)/gpf_start/([0-9]{1,})', 'index.php?woocommerce_gpf=$matches[1]&gpf_start=$matches[2]', 'top' );
		add_rewrite_rule( 'woocommerce_gpf/([^/]+)/gpf_categories/(\d+(,\d+)*)', 'index.php?woocommerce_gpf=$matches[1]&gpf_categories=$matches[2]', 'top' );
		add_rewrite_rule( 'woocommerce_gpf/([^/]+)', 'index.php?woocommerce_gpf=$matches[1]', 'top' );
		if ( get_site_transient( 'woocommerce_gpf_rewrite_flush_required' ) === '1' ) {
			flush_rewrite_rules();
			delete_site_transient( 'woocommerce_gpf_rewrite_flush_required' );
		}
	}

	/**
	 * Register query args.
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'woocommerce_gpf';
		// Legacy vars.
		$vars[] = 'gpf_start';
		$vars[] = 'gpf_limit';
		$vars[] = 'gpf_categories';

		return $vars;
	}

	/**
	 * Instantiate the relevant classes dependent on the feed request type.
	 */
	public function trigger_feeds(): void {
		$feed_config = $this->feed_config_factory->create_from_request();
		if ( null === $feed_config ) {
			return;
		}

		if ( 'googlereview' === $feed_config->type ) {
			$this->main_service_locator
				->get( 'ReviewFeed' )
				->initialise( $feed_config );
		} elseif ( 'googlepromotions' === $feed_config->type ) {
			$this->main_service_locator
				->get( 'PromotionFeed' )
				->initialise( $feed_config );
		} else {
			$this->main_service_locator
				->get( 'ProductFeed' )
				->initialise( $feed_config );
		}
	}
}
