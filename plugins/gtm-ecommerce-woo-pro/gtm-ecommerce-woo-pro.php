<?php
/**
 * Plugin Name: Tag Pilot PRO - Google Tag Manager Integration for WooCommerce
 * Plugin URI: https://tagconcierge.com/google-tag-manager-for-woocommerce/
 * Description: Push WooCommerce eCommerce (GA4) information to GTM DataLayer. Use packaged GTM integration to measure your customers' activities.
 * Version:     1.17.1
 * Author:      Tag Concierge
 * Author URI:  https://tagconcierge.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 10.4.3
 * Woo: 7424130:288e08109d3061ad3a8886a94db87d9b
 */

namespace GtmEcommerceWooPro;

require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use GtmEcommerceWooPro\Lib\Container;

$pluginData = get_file_data(__FILE__, array('Version' => 'Version'), false);
$pluginVersion = $pluginData['Version'];


// TODO: add a wp-config.php CONSTANT to control this behaviour
// unfortunately can't be added to customer functions.php because the Google Ads
// plugin runs this logic before themes are loaded
add_filter( 'woocommerce_gla_disable_gtag_tracking', '__return_true' );

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( FeaturesUtil::class ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
});


add_action('plugins_loaded', function () use ( $pluginVersion) {
	$container = new Container($pluginVersion);
	$container->getSettingsService()->initialize();
	$container->getGtmSnippetService()->initialize();
	$container->getEventStrategiesService()->initialize();
	$pluginService = $container->getPluginService();
	$pluginService->initialize();
	$container->getEventInspectorService()->initialize();
	$container->getQueueService()->initialize();
	$container->getCookiesService()->initialize();
	$container->getProductFeedService()->initialize();
	$container->getToolsService()->initialize();
	$container->getOrderMonitorService()->initialize();

	register_activation_hook( __FILE__, [ $pluginService, 'activationHook' ] );
});
