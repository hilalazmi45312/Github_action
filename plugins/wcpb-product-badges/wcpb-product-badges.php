<?php

/**
 * Plugin name: Product Badges
 * Plugin URI: https://woocommerce.com/products/product-badges/
 * Description: Add badges to products to highlight key features, special offers and more.
 * Author: Kestrel
 * Author URI: https://kestrelwp.com
 * Version: 3.4.0
 * Requires at least: 6.3.0
 * Requires PHP: 7.4.0
 * Requires plugins: woocommerce
 * WC requires at least: 8.5.0
 * WC tested up to: 10.1.0
 * Woo: 6662686:47f602c9beac3790024f9e7c7f2b5e7e
 * Domain path: /languages
 * Text domain: wcpb-product-badges
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WCPB_Product_Badges' ) ) {

	define( 'WCPB_PRODUCT_BADGES_VERSION', '3.4.0' );
	define( 'WCPB_PRODUCT_BADGES_BADGES_PATH', __DIR__ . '/badges/' );
	define( 'WCPB_PRODUCT_BADGES_BADGES_URL', plugin_dir_url( WCPB_PRODUCT_BADGES_BADGES_PATH ) . 'badges/' );

	class WCPB_Product_Badges {

		public function __construct() {

			require_once __DIR__ . '/includes/class-wcpb-product-badges-activation.php';
			require_once __DIR__ . '/includes/class-wcpb-product-badges-translation.php';

			new WCPB_Product_Badges_Activation();
			new WCPB_Product_Badges_Translation();

			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

				add_action( 'before_woocommerce_init', function() {

					if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {

						Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );

					}

				});

				add_action( 'before_woocommerce_init', function() {

					if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) && version_compare( WC_VERSION, '8.0.0', '>=' ) ) {

						Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );

					}

				});

				function wcpb_product_badges_hpos_enabled() {

					// This function is not recommended for use in custom development

					return class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

				}

				require_once __DIR__ . '/includes/class-wcpb-product-badges-admin.php';
				require_once __DIR__ . '/includes/class-wcpb-product-badges-public.php';
				require_once __DIR__ . '/includes/class-wcpb-product-badges-upgrade.php';

				new WCPB_Product_Badges_Admin();
				new WCPB_Product_Badges_Public();
				new WCPB_Product_Badges_Upgrade();

			} else {

				add_action( 'admin_notices', function() {

					if ( current_user_can( 'edit_plugins' ) ) {

						?>

						<div class="notice notice-error">
							<p><strong><?php esc_html_e( 'Product Badges requires WooCommerce to be installed and activated.', 'wcpb-product-badges' ); ?></strong></p>
						</div>

						<?php

					}

				});

			}

		}

	}

	new WCPB_Product_Badges();

}
