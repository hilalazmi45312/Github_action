<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Features\WoocommerceImportExportSupport;
use Ademti\WoocommerceProductFeeds\Helpers\DbManager;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Psr11\ServiceLocator;

class AdminManager {
	// Dependencies.
	private Admin $admin;
	private AdminNotices $admin_notices;
	private ServiceLocator $admin_service_locator;
	private DbManager $db_manager;
	private FeedManager $feed_manager;
	private ProductFeedImageManager $product_feed_image_manager;
	private ReviewFeedUi $review_feed_ui;
	private WoocommerceImportExportSupport $woocommerce_import_export_support;
	private PromotionFeedUi $promotion_feed_ui;

	/**
	 * @param Admin $admin
	 * @param AdminNotices $admin_notices
	 * @param ServiceLocator $admin_service_locator
	 * @param DbManager $db_manager
	 * @param FeedManager $feed_manager
	 * @param ProductFeedImageManager $product_feed_image_manager
	 * @param ReviewFeedUi $review_feed_ui
	 * @param WoocommerceImportExportSupport $woocommerce_import_export_support
	 */
	public function __construct(
		Admin $admin,
		AdminNotices $admin_notices,
		ServiceLocator $admin_service_locator,
		DbManager $db_manager,
		FeedManager $feed_manager,
		ProductFeedImageManager $product_feed_image_manager,
		ReviewFeedUi $review_feed_ui,
		WoocommerceImportExportSupport $woocommerce_import_export_support,
		PromotionFeedUi $promotion_feed_ui
	) {
		$this->admin                             = $admin;
		$this->admin_notices                     = $admin_notices;
		$this->admin_service_locator             = $admin_service_locator;
		$this->db_manager                        = $db_manager;
		$this->feed_manager                      = $feed_manager;
		$this->product_feed_image_manager        = $product_feed_image_manager;
		$this->review_feed_ui                    = $review_feed_ui;
		$this->woocommerce_import_export_support = $woocommerce_import_export_support;
		$this->promotion_feed_ui                 = $promotion_feed_ui;
	}

	/**
	 * Run the features.
	 *
	 * @return void
	 */
	public function run() {
		$this->admin->initialise();
		$this->db_manager->initialise();
		$this->review_feed_ui->initialise();
		$this->woocommerce_import_export_support->initialise();
		$this->admin_notices->initialise();
		$this->product_feed_image_manager->initialise();
		$this->feed_manager->initialise();
		$this->promotion_feed_ui->initialise();
		add_filter( 'woocommerce_gpf_cache_status', [ $this, 'cache_status' ], 10, 2 );
		add_action( 'woocommerce_system_status_report', [ $this, 'status_report' ] );
	}

	/**
	 * Run the status report functionality.
	 *
	 * @return void
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\ContainerExceptionInterface
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\NotFoundExceptionInterface
	 */
	public function status_report(): void {
		$this->admin_service_locator->get( 'StatusReport' )->render();
	}

	/**
	 * Run the cache status functionality.
	 *
	 * @param string $output
	 * @param string $settings_url
	 *
	 * @return string
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\ContainerExceptionInterface
	 * @throws \Ademti\WoocommerceProductFeeds\Dependencies\Psr\Container\NotFoundExceptionInterface
	 */
	public function cache_status( string $output, string $settings_url ): string {
		return $this->admin_service_locator->get( 'CacheStatus' )->generate_status_output( $output, $settings_url );
	}
}
