<?php

use Ademti\WoocommerceProductFeeds\Dependencies\Ademti\DismissibleWpNotices\DismissibleWpNoticeManager;
use Ademti\WoocommerceProductFeeds\Admin\Admin;
use Ademti\WoocommerceProductFeeds\Admin\AdminManager;
use Ademti\WoocommerceProductFeeds\Admin\AdminNotices;
use Ademti\WoocommerceProductFeeds\Admin\CacheStatus;
use Ademti\WoocommerceProductFeeds\Admin\FeedManager;
use Ademti\WoocommerceProductFeeds\Admin\FeedManagerListTable;
use Ademti\WoocommerceProductFeeds\Admin\ProductFeedImageManager;
use Ademti\WoocommerceProductFeeds\Admin\PromotionFeedUi;
use Ademti\WoocommerceProductFeeds\Admin\ReviewFeedUi;
use Ademti\WoocommerceProductFeeds\Admin\StatusReport;
use Ademti\WoocommerceProductFeeds\Cache\Cache;
use Ademti\WoocommerceProductFeeds\Cache\CacheInvalidator;
use Ademti\WoocommerceProductFeeds\Cache\Jobs\ClearAllJob;
use Ademti\WoocommerceProductFeeds\Cache\Jobs\ClearProductJob;
use Ademti\WoocommerceProductFeeds\Cache\Jobs\RebuildComplexJob;
use Ademti\WoocommerceProductFeeds\Cache\Jobs\RebuildProductJob;
use Ademti\WoocommerceProductFeeds\Cache\Jobs\RebuildSimpleJob;
use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigFactory;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use Ademti\WoocommerceProductFeeds\Features\AddToCartByFeedIdSupport;
use Ademti\WoocommerceProductFeeds\Features\ExpandedStructuredData;
use Ademti\WoocommerceProductFeeds\Features\ExpandedStructuredDataCacheInvalidator;
use Ademti\WoocommerceProductFeeds\Features\FeatureManager;
use Ademti\WoocommerceProductFeeds\Features\RestApi;
use Ademti\WoocommerceProductFeeds\Features\SetupTasks\SetupTasks;
use Ademti\WoocommerceProductFeeds\Features\StructuredData;
use Ademti\WoocommerceProductFeeds\Features\WoocommerceImportExportSupport;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Bing\ProductFeedRenderer as BingProductFeedRenderer;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google\InventoryFeedRenderer;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google\LocalProductFeedRenderer;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google\LocalProductInventoryFeed;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google\ProductFeedRenderer as GoogleProductFeedRenderer;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\ProductFeed;
use Ademti\WoocommerceProductFeeds\Feeds\PromotionFeeds\PromotionFeed;
use Ademti\WoocommerceProductFeeds\Feeds\PromotionFeeds\PromotionFeedRenderer;
use Ademti\WoocommerceProductFeeds\Feeds\ReviewFeeds\ReviewFeed;
use Ademti\WoocommerceProductFeeds\Feeds\ReviewFeeds\ReviewFeedGoogle;
use Ademti\WoocommerceProductFeeds\Feeds\ReviewFeeds\ReviewProductInfo;
use Ademti\WoocommerceProductFeeds\Helpers\CouponRepository;
use Ademti\WoocommerceProductFeeds\Helpers\DbManager;
use Ademti\WoocommerceProductFeeds\Helpers\DebugService;
use Ademti\WoocommerceProductFeeds\Helpers\IntegrationManager;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemExclusionService;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemFactory;
use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use Ademti\WoocommerceProductFeeds\Helpers\TermDepthRepository;
use Ademti\WoocommerceProductFeeds\Integrations\AdvancedCustomFields;
use Ademti\WoocommerceProductFeeds\Integrations\AdvancedCustomFieldsFormatter;
use Ademti\WoocommerceProductFeeds\Integrations\CurrencySwitcherForWoocommerce;
use Ademti\WoocommerceProductFeeds\Integrations\FacebookForWoocommerce;
use Ademti\WoocommerceProductFeeds\Integrations\GoogleAutomatedDiscountsForWoocommerce;
use Ademti\WoocommerceProductFeeds\Integrations\MeasurementPriceCalculator;
use Ademti\WoocommerceProductFeeds\Integrations\MinMaxQuantities;
use Ademti\WoocommerceProductFeeds\Integrations\Multicurrency;
use Ademti\WoocommerceProductFeeds\Integrations\PriceByCountry;
use Ademti\WoocommerceProductFeeds\Integrations\ProductBrandsForWoocommerce;
use Ademti\WoocommerceProductFeeds\Integrations\ProductBundles;
use Ademti\WoocommerceProductFeeds\Integrations\ProductVendors;
use Ademti\WoocommerceProductFeeds\Integrations\PwBulkEdit;
use Ademti\WoocommerceProductFeeds\Integrations\SeoByRankMath;
use Ademti\WoocommerceProductFeeds\Integrations\TheContentProtection;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceAdditionalVariationImages;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceCompositeProducts;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceCostOfGoods;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceGermanized;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceMinMaxQuantityStepControlSingle;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceMixAndMatchProducts;
use Ademti\WoocommerceProductFeeds\Integrations\WoocommerceMultilingual;
use Ademti\WoocommerceProductFeeds\Integrations\WooPayments;
use Ademti\WoocommerceProductFeeds\Integrations\YoastWoocommerceSeo;
use Ademti\WoocommerceProductFeeds\Jobs\ClearGoogleTaxonomyJob;
use Ademti\WoocommerceProductFeeds\Jobs\JobManager;
use Ademti\WoocommerceProductFeeds\Jobs\MaybeRefreshGoogleTaxonomiesJob;
use Ademti\WoocommerceProductFeeds\Jobs\RefreshCouponBrandMapJob;
use Ademti\WoocommerceProductFeeds\Jobs\RefreshCouponCategoryMapJob;
use Ademti\WoocommerceProductFeeds\Jobs\RefreshGoogleTaxonomyJob;
use Ademti\WoocommerceProductFeeds\Main;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Psr11\ServiceLocator;

defined( 'ABSPATH' ) || exit;

/**
 * Set up the DI container.
 */

global $woocommerce_product_feeds_di;
$woocommerce_product_feeds_di = new Container();

/***********************************************
 * Admin
 **********************************************/

$woocommerce_product_feeds_di['Admin']                   = static fn( $c ) => new Admin(
	$c['Configuration'],
	$c['TemplateLoader'],
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['ProductFeedImageManager']
);
$woocommerce_product_feeds_di['AdminManager']            = static fn( $c ) => new AdminManager(
	$c['Admin'],
	$c['AdminNotices'],
	$c['AdminServiceLocator'],
	$c['DbManager'],
	$c['FeedManager'],
	$c['ProductFeedImageManager'],
	$c['ReviewFeedUi'],
	$c['WoocommerceImportExportSupport'],
	$c['PromotionFeedUi']
);
$woocommerce_product_feeds_di['AdminServiceLocator']     = static fn( $c ) => new ServiceLocator(
	$c,
	[
		'CacheStatus',
		'StatusReport',
	]
);
$woocommerce_product_feeds_di['AdminNotices']            = static fn( $c ) => new AdminNotices(
	$c['DismissibleWpNoticeManager'],
	$c['TemplateLoader']
);
$woocommerce_product_feeds_di['CacheStatus']             = static fn( $c ) => new CacheStatus(
	$c['Configuration'],
	$c['Cache'],
	$c['TemplateLoader'],
	$c['FeedConfigRepository']
);
$woocommerce_product_feeds_di['FeedManager']             = static fn( $c ) => new FeedManager(
	$c['FeedConfigRepository'],
	$c['TemplateLoader'],
	$c['Configuration'],
	$c['FeedManagerListTable']
);
$woocommerce_product_feeds_di['FeedManagerListTable']    = static function ( $c ) {
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	return new FeedManagerListTable(
		$c['FeedConfigRepository'],
		$c['Configuration'],
		$c['TermDepthRepository']
	);
};
$woocommerce_product_feeds_di['ProductFeedImageManager'] = static fn( $c ) => new ProductFeedImageManager(
	$c['TemplateLoader'],
	$c['ProductFeedItemFactory']
);
$woocommerce_product_feeds_di['PromotionFeedUi']         = static fn( $c ) => new PromotionFeedUi( $c['TemplateLoader'] );
$woocommerce_product_feeds_di['ReviewFeedUi']            = static fn( $c ) => new ReviewFeedUi( $c['TemplateLoader'] );
$woocommerce_product_feeds_di['StatusReport']            = static fn( $c ) => new StatusReport(
	$c['TemplateLoader'],
	$c['Configuration'],
	$c['FeedConfigRepository']
);

/************************************************
 * Cache\Jobs
 ***********************************************/

$woocommerce_product_feeds_di['ClearAllJob']       = static fn( $c ) => new ClearAllJob(
	$c['Configuration'],
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['ProductFeedItemFactory'],
	$c['ProductFeedItemExclusionService'],
	$c
);
$woocommerce_product_feeds_di['ClearProductJob']   = static fn( $c ) => new ClearProductJob(
	$c['Configuration'],
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['ProductFeedItemFactory'],
	$c['ProductFeedItemExclusionService'],
	$c
);
$woocommerce_product_feeds_di['RebuildComplexJob'] = static fn( $c ) => new RebuildComplexJob(
	$c['Configuration'],
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['ProductFeedItemFactory'],
	$c['ProductFeedItemExclusionService'],
	$c
);
$woocommerce_product_feeds_di['RebuildProductJob'] = static fn( $c ) => new RebuildProductJob(
	$c['Configuration'],
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['ProductFeedItemFactory'],
	$c['ProductFeedItemExclusionService'],
	$c
);
$woocommerce_product_feeds_di['RebuildSimpleJob']  = static fn( $c ) => new RebuildSimpleJob(
	$c['Configuration'],
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['ProductFeedItemFactory'],
	$c['ProductFeedItemExclusionService'],
	$c
);

/************************************************
 * Cache
 ***********************************************/

$woocommerce_product_feeds_di['Cache']            = static fn( $c ) => new Cache( $c['DebugService'], $c );
$woocommerce_product_feeds_di['CacheInvalidator'] = static fn( $c ) => new CacheInvalidator( $c['Cache'] );

/************************************************
 * Configuration
 ***********************************************/

$woocommerce_product_feeds_di['Configuration']        = static fn( $c ) => new Configuration( $c['TermDepthRepository'] );
$woocommerce_product_feeds_di['FeedConfigFactory']    = static fn( $c ) => new FeedConfigFactory(
	$c['FeedConfigRepository'],
	$c['Configuration']
);
$woocommerce_product_feeds_di['FeedConfigRepository'] = static fn() => new FeedConfigRepository();

/************************************************
 * DTOs
 ***********************************************/
$woocommerce_product_feeds_di['StoreInfo'] = static fn() => new StoreInfo();

/************************************************
 * Features\SetupTasks
 ***********************************************/

$woocommerce_product_feeds_di['SetupTasks'] = static fn() => new SetupTasks();

/************************************************
 * Features
 ***********************************************/

$woocommerce_product_feeds_di['FeatureManager']                         = static fn( $c ) => new FeatureManager(
	$c['AddToCartByFeedIdSupport'],
	$c['FeatureServiceLocator'],
	$c['RestApi'],
	$c['SetupTasks'],
	$c['Configuration']
);
$woocommerce_product_feeds_di['FeatureServiceLocator']                  = static fn( $c ) => new ServiceLocator(
	$c,
	[
		'ExpandedStructuredData',
		'StructuredData',
		'ExpandedStructuredDataCacheInvalidator',
	]
);
$woocommerce_product_feeds_di['AddToCartByFeedIdSupport']               = static fn() => new AddToCartByFeedIdSupport();
$woocommerce_product_feeds_di['ExpandedStructuredData']                 = static fn( $c ) => new ExpandedStructuredData(
	$c['ProductFeedItemFactory']
);
$woocommerce_product_feeds_di['ExpandedStructuredDataCacheInvalidator'] = static fn() => new ExpandedStructuredDataCacheInvalidator();
$woocommerce_product_feeds_di['RestApi']                                = static fn( $c ) => new RestApi( $c['Configuration'] );
$woocommerce_product_feeds_di['StructuredData']                         = static fn( $c ) => new StructuredData(
	$c['ProductFeedItemFactory']
);
$woocommerce_product_feeds_di['WoocommerceImportExportSupport']         = static fn( $c ) => new WoocommerceImportExportSupport( $c['Configuration'] );

/************************************************
 * Feeds\ProductFeeds\Bing
 ***********************************************/

$woocommerce_product_feeds_di['BingFeed'] = static fn( $c ) => new BingProductFeedRenderer(
	$c['Configuration'],
	$c['DebugService'],
	$c['StoreInfo']
);

/************************************************
 * Feeds\ProductFeeds\Google
 ***********************************************/

$woocommerce_product_feeds_di['GoogleInventoryFeed']             = static fn( $c ) => new InventoryFeedRenderer(
	$c['Configuration'],
	$c['DebugService'],
	$c['StoreInfo']
);
$woocommerce_product_feeds_di['GoogleLocalProductFeed']          = static fn( $c ) => new LocalProductFeedRenderer(
	$c['Configuration'],
	$c['DebugService'],
	$c['StoreInfo']
);
$woocommerce_product_feeds_di['GoogleLocalProductInventoryFeed'] = static fn( $c ) => new LocalProductInventoryFeed(
	$c['Configuration'],
	$c['DebugService'],
	$c['StoreInfo']
);
$woocommerce_product_feeds_di['GoogleProductFeed']               = static fn( $c ) => new GoogleProductFeedRenderer(
	$c['Configuration'],
	$c['DebugService'],
	$c['StoreInfo']
);


/************************************************
 * Feeds\ProductFeeds
 ***********************************************/

$woocommerce_product_feeds_di['ProductFeed'] = static fn( $c ) => new ProductFeed(
	$c['Configuration'],
	$c['Cache'],
	$c['DebugService'],
	$c['ProductFeedItemFactory'],
	$c['ProductFeedItemExclusionService'],
	$c
);

/************************************************
 * Feeds\ReviewFeeds
 ***********************************************/

$woocommerce_product_feeds_di['ReviewFeedGoogle']  = static fn( $c ) => new ReviewFeedGoogle(
	$c['TemplateLoader'],
	$c['DebugService']
);
$woocommerce_product_feeds_di['ReviewFeed']        = static fn( $c ) => new ReviewFeed(
	$c['Cache'],
	$c['ReviewFeedGoogle'],
	$c['ReviewProductInfo']
);
$woocommerce_product_feeds_di['ReviewProductInfo'] = static fn( $c ) => new ReviewProductInfo(
	$c['Cache'],
	$c['ProductFeedItemExclusionService'],
	$c['ProductFeedItemFactory']
);

/************************************************
 * Feeds\PromotionFeeds
 ***********************************************/

$woocommerce_product_feeds_di['PromotionFeed']         = static fn( $c ) => new PromotionFeed( $c['CouponRepository'], $c['PromotionFeedRenderer'] );
$woocommerce_product_feeds_di['PromotionFeedRenderer'] = static fn( $c ) => new PromotionFeedRenderer(
	$c['TemplateLoader'],
	$c['Configuration'],
	$c['DebugService'],
	$c['StoreInfo']
);

/************************************************
 * Helpers
 ***********************************************/

$woocommerce_product_feeds_di['CouponRepository']                = static fn( $c ) => new CouponRepository( $c['StoreInfo'] );
$woocommerce_product_feeds_di['DbManager']                       = static fn( $c ) => new DbManager(
	$c['Cache'],
	$c['FeedConfigRepository'],
	$c['Configuration']
);
$woocommerce_product_feeds_di['DebugService']                    = static fn() => new DebugService();
$woocommerce_product_feeds_di['IntegrationManager']              = static fn( $c ) => new IntegrationManager( $c );
$woocommerce_product_feeds_di['ProductFeedItemExclusionService'] = static fn() => new ProductFeedItemExclusionService();
$woocommerce_product_feeds_di['ProductFeedItemFactory']          = static fn( $c ) => new ProductFeedItemFactory(
	$c['Configuration'],
	$c['DebugService'],
	$c['TermDepthRepository'],
	$c['FeedConfigRepository'],
	$c
);

$woocommerce_product_feeds_di['TemplateLoader']      = static fn() => new TemplateLoader();
$woocommerce_product_feeds_di['TermDepthRepository'] = static fn() => new TermDepthRepository();

/************************************************
 * Integrations
 ***********************************************/
$woocommerce_product_feeds_di['AdvancedCustomFieldsFormatter']              = static fn() => new AdvancedCustomFieldsFormatter();
$woocommerce_product_feeds_di['AdvancedCustomFields']                       = static fn( $c ) => new AdvancedCustomFields(
	$c['AdvancedCustomFieldsFormatter']
);
$woocommerce_product_feeds_di['CurrencySwitcherForWoocommerce']             = static fn( $c ) => new CurrencySwitcherForWoocommerce(
	$c['FeedConfigFactory'],
	$c['TemplateLoader']
);
$woocommerce_product_feeds_di['FacebookForWoocommerce']                     = static fn() => new FacebookForWoocommerce();
$woocommerce_product_feeds_di['GoogleAutomatedDiscountsForWoocommerce']     = static fn( $c ) => new GoogleAutomatedDiscountsForWoocommerce( $c );
$woocommerce_product_feeds_di['MeasurementPriceCalculator']                 = static fn() => new MeasurementPriceCalculator();
$woocommerce_product_feeds_di['MinMaxQuantities']                           = static fn() => new MinMaxQuantities();
$woocommerce_product_feeds_di['Multicurrency']                              = static fn( $c ) => new Multicurrency(
	$c['FeedConfigFactory'],
	$c['TemplateLoader']
);
$woocommerce_product_feeds_di['PriceByCountry']                             = static fn() => new PriceByCountry();
$woocommerce_product_feeds_di['ProductBrandsForWooCommerce']                = static fn() => new ProductBrandsForWoocommerce();
$woocommerce_product_feeds_di['ProductBundles']                             = static fn() => new ProductBundles();
$woocommerce_product_feeds_di['ProductVendors']                             = static fn() => new ProductVendors();
$woocommerce_product_feeds_di['PwBulkEdit']                                 = static fn( $c ) => new PwBulkEdit( $c['Configuration'] );
$woocommerce_product_feeds_di['SeoByRankMath']                              = static fn( $c ) => new SeoByRankMath( $c['TermDepthRepository'] );
$woocommerce_product_feeds_di['TheContentProtection']                       = static fn() => new TheContentProtection();
$woocommerce_product_feeds_di['WoocommerceAdditionalVariationImages']       = static fn() => new WoocommerceAdditionalVariationImages();
$woocommerce_product_feeds_di['WoocommerceCompositeProducts']               = static fn() => new WoocommerceCompositeProducts();
$woocommerce_product_feeds_di['WoocommerceCostOfGoods']                     = static fn() => new WoocommerceCostOfGoods();
$woocommerce_product_feeds_di['WoocommerceGermanized']                      = static fn() => new WoocommerceGermanized();
$woocommerce_product_feeds_di['WoocommerceMinMaxQuantityStepControlSingle'] = static fn() => new WoocommerceMinMaxQuantityStepControlSingle();
$woocommerce_product_feeds_di['WoocommerceMixAndMatchProducts']             = static fn() => new WoocommerceMixAndMatchProducts();
$woocommerce_product_feeds_di['WoocommerceMultilingual']                    = static fn() => new WoocommerceMultilingual();
$woocommerce_product_feeds_di['WooPayments']                                = static fn( $c ) => new WooPayments(
	$c['FeedConfigFactory'],
	$c['TemplateLoader']
);
$woocommerce_product_feeds_di['YoastWoocommerceSeo']                        = static fn() => new YoastWoocommerceSeo();

// Class aliases for integrations to support prepopulation choices data.
class_alias( WoocommerceCostOfGoods::class, 'WoocommerceCostOfGoods' );
class_alias( YoastWoocommerceSeo::class, 'WoocommerceGpfYoastWoocommerceSeo' );
class_alias( WoocommerceGermanized::class, 'WoocommerceProductFeedsWoocommerceGermanized' );

/************************************************
 * Jobs
 ***********************************************/

$woocommerce_product_feeds_di['ClearGoogleTaxonomyJob']          = static fn() => new ClearGoogleTaxonomyJob();
$woocommerce_product_feeds_di['JobManager']                      = static fn( $c ) => new JobManager( $c );
$woocommerce_product_feeds_di['MaybeRefreshGoogleTaxonomiesJob'] = static fn( $c ) => new MaybeRefreshGoogleTaxonomiesJob( $c['Configuration'] );
$woocommerce_product_feeds_di['RefreshGoogleTaxonomyJob']        = static fn() => new RefreshGoogleTaxonomyJob();
$woocommerce_product_feeds_di['RefreshCouponCategoryMapJob']     = static fn( $c ) => new RefreshCouponCategoryMapJob( $c['CouponRepository'] );
$woocommerce_product_feeds_di['RefreshCouponBrandMapJob']        = static fn( $c ) => new RefreshCouponBrandMapJob( $c['CouponRepository'] );

/************************************************
 * \
 ***********************************************/

$woocommerce_product_feeds_di['Main'] = static fn( $c ) => new Main(
	$c['AdminManager'],
	$c['Cache'],
	$c['Configuration'],
	$c['FeatureManager'],
	$c['FeedConfigFactory'],
	$c['MainServiceLocator'],
	$c['IntegrationManager'],
	$c['JobManager']
);

$woocommerce_product_feeds_di['MainServiceLocator'] = static fn( $c ) => new ServiceLocator(
	$c,
	[
		'ProductFeed',
		'ReviewFeed',
		'PromotionFeed',
		'CacheInvalidator',
	]
);

/************************************************
 * Externals
 ***********************************************/
$woocommerce_product_feeds_di['DismissibleWpNoticeManager'] = static fn() => DismissibleWpNoticeManager::get_instance( plugin_dir_url( __FILE__ ) . 'vendor-prefixed/leewillis77/dismissible-wp-notices/' );
