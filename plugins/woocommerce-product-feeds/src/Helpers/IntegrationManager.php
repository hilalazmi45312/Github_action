<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use RankMath;
use WC_Bundles;
use WC_Composite_Products;
use WC_Payments_Features;
use function class_exists;
use function defined;
use function is_callable;
use function version_compare;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class IntegrationManager {

	// Dependencies.
	protected Container $container;

	/**
	 * Array of integration keys.
	 *
	 * @var array|string[]
	 */
	protected array $integrations = [];

	/**
	 * @param  Container  $container
	 */
	public function __construct( Container $container ) {
		$this->container    = $container;
		$this->integrations = [
			'advanced_custom_fields',
			'cost_of_goods',
			'currency_switcher_for_woocommerce',
			'facebook_for_woocommerce',
			'google_automated_discounts_for_woocommerce',
			'measurement_price_calculator',
			'min_max_quantities',
			'multicurrency',
			'price_by_country',
			'product_brands_for_woocommerce',
			'product_bundles',
			'product_vendors',
			'pw_bulk_edit',
			'seo_by_rank_math',
			'the_content_protection',
			'woocommerce_additional_variation_images',
			'woocommerce_composite_products',
			'woocommerce_germanized',
			'woocommerce_min_max_quantity_step_control_single',
			'woocommerce_mix_and_match_products',
			'woocommerce_multilingual',
			'woo_payments',
			'yoast_woocommerce_seo',
		];
	}

	/**
	 * Initialise integrations.
	 */
	public function initialise(): void {
		foreach ( $this->integrations as $integration ) {
			$callback = $integration . '_integration';
			if ( apply_filters( 'woocommerce_gpf_disable_' . $integration . '_integration', false ) ) {
				continue;
			}
			$this->$callback();
		}
	}

	/**
	 * https://woocommerce.com/products/facebook/
	 *
	 * @return void
	 */
	private function facebook_for_woocommerce_integration() {
		if ( ! class_exists( '\WC_Facebook_Loader' ) ) {
			return;
		}
		$this->container['FacebookForWoocommerce']->run();
	}

	/**
	 * https://woocommerce.com/products/product-brands-for-woocommerce/
	 *
	 * @return void
	 */
	private function product_brands_for_woocommerce_integration(): void {
		if ( ! defined( 'PRODUCT_BRANDS_FOR_WOOCOMMERCE_VERSION' ) ) {
			return;
		}
		$this->container['ProductBrandsForWooCommerce']->run();
	}

	/**
	 * https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/
	 *
	 * @return void
	 */
	private function yoast_woocommerce_seo_integration(): void {
		if ( ! defined( 'WPSEO_WOO_VERSION' ) ) {
			return;
		}
		$this->container['YoastWoocommerceSeo']->run();
	}

	/**
	 * Various plugins.
	 *
	 * @return void
	 */
	private function the_content_protection_integration(): void {
		if ( ! defined( 'ELEMENTOR_VERSION' ) &&
			! defined( 'SITEORIGIN_PANELS_VERSION' ) ) {
			return;
		}

		$this->container['TheContentProtection']->run();
	}

	/**
	 * https://woocommerce.com/products/product-vendors/
	 *
	 * @return void
	 */
	private function product_vendors_integration(): void {
		if ( ! defined( 'WC_PRODUCT_VENDORS_VERSION' ) ||
			version_compare( WC_PRODUCT_VENDORS_VERSION, '2.1.16', 'lt' ) ) {
			return;
		}
		$this->container['ProductVendors']->run();
	}

	/**
	 * https://woocommerce.com/products/minmax-quantities
	 *
	 * @return void
	 */
	private function min_max_quantities_integration(): void {
		if ( ! defined( 'WC_MIN_MAX_QUANTITIES' ) ||
			version_compare( WC_MIN_MAX_QUANTITIES, '2.4.5', 'lt' ) ) {
			return;
		}
		$this->container['MinMaxQuantities']->run();
	}

	/**
	 * https://woocommerce.com/products/multi-currency/
	 *
	 * @return void
	 */
	private function multicurrency_integration(): void {
		if ( ! defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ||
			version_compare( WOOCOMMERCE_MULTICURRENCY_VERSION, '1.9.0', 'lt' ) ) {
			return;
		}
		$this->container['Multicurrency']->run();
	}

	/**
	 * https://woocommerce.com/products/price-by-country/
	 *
	 * @return void
	 */
	private function price_by_country_integration(): void {
		if ( ! class_exists( '\CBP_Country_Based_Price' ) ) {
			return;
		}
		$this->container['PriceByCountry']->run();
	}

	/**
	 * https://woocommerce.com/products/woocommerce-cost-of-goods/
	 *
	 * @return void
	 */
	private function cost_of_goods_integration(): void {
		if ( ! class_exists( '\WC_COG_Loader' ) ) {
			return;
		}
		$this->container['WoocommerceCostOfGoods']->run();
	}

	/**
	 * https://woocommerce.com/products/woocommerce-mix-and-match-products/
	 *
	 * @return void
	 */
	private function woocommerce_mix_and_match_products_integration(): void {
		if ( ! class_exists( '\WC_Mix_and_Match' ) ) {
			return;
		}
		$mnm_instance = \WC_Mix_and_Match::instance();
		if ( empty( $mnm_instance->version ) ||
			version_compare( $mnm_instance->version, '1.10.2', '<' ) ) {
			return;
		}
		$this->container['WoocommerceMixAndMatchProducts']->run();
	}

	/**
	 * https://woocommerce.com/products/currency-switcher-for-woocommerce/
	 *
	 * @return void
	 */
	private function currency_switcher_for_woocommerce_integration(): void {
		// Try and find the global WCCS.
		$wccs = new \stdClass();
		if ( class_exists( '\Wpexperts\CurrencySwitcherForWoocommerce\WCCS' ) ) {
			if ( is_callable( '\Wpexperts\CurrencySwitcherForWoocommerce\WCCS::Instance' ) ) {
				$wccs = \Wpexperts\CurrencySwitcherForWoocommerce\WCCS::Instance();
			}
		}
		if ( ! defined( 'WCCS_VERSION' ) ||
			version_compare( WCCS_VERSION, '1.9.0', 'lt' ) ||
			! is_callable( [ $wccs, 'wccs_get_currencies' ] ) ) {
			return;
		}
		$this->container['CurrencySwitcherForWoocommerce']->run();
	}

	/**
	 * https://woocommerce.com/products/composite-products/
	 *
	 * @return void
	 */
	private function woocommerce_composite_products_integration(): void {
		if ( ! class_exists( 'WC_Composite_Products' ) ||
			! is_callable( 'WC_Composite_Products::instance' ) ) {
			return;
		}
		$wc_cp = WC_Composite_Products::instance();
		if ( empty( $wc_cp->version ) || version_compare( $wc_cp->version, '7.0.0', 'lt' ) ) {
			return;
		}
		$this->container['WoocommerceCompositeProducts']->run();
	}

	/**
	 * https://woocommerce.com/products/product-bundles/
	 *
	 * @return void
	 */
	private function product_bundles_integration(): void {
		if ( ! class_exists( 'WC_Bundles' ) ||
			! is_callable( 'WC_Bundles::instance' ) ) {
			return;
		}
		$wc_pb = WC_Bundles::instance();
		if ( empty( $wc_pb->version ) || version_compare( $wc_pb->version, '6.2.4', 'lt' ) ) {
			return;
		}
		$this->container['ProductBundles']->run();
	}

	/**
	 * https://codecanyon.net/item/woocommerce-min-max-quantity-step-control/22962198
	 *
	 * @return void
	 */
	private function woocommerce_min_max_quantity_step_control_single_integration(): void {
		if ( ! defined( 'WC_MMQ_S_PLUGIN_BASE_FILE' ) ) {
			return;
		}
		$this->container['WoocommerceMinMaxQuantityStepControlSingle']->run();
	}

	/**
	 * https://wordpress.org/plugins/woocommerce-multilingual/
	 *
	 * @return void
	 */
	private function woocommerce_multilingual_integration(): void {
		if ( ! defined( 'WCML_VERSION' ) ) {
			return;
		}
		$this->container['WoocommerceMultilingual']->run();
	}

	/**
	 * https://en-gb.wordpress.org/plugins/pw-bulk-edit/
	 *
	 * @return void
	 */
	private function pw_bulk_edit_integration(): void {
		if ( ! defined( 'PWBE_VERSION' ) ) {
			return;
		}
		$this->container['PwBulkEdit']->run();
	}

	/**
	 * https://wordpress.org/plugins/advanced-custom-fields/
	 *
	 * @return void
	 */
	private function advanced_custom_fields_integration(): void {
		if ( ! class_exists( '\ACF' ) ) {
			return;
		}
		$this->container['AdvancedCustomFields']->run();
	}

	/**
	 * https://wordpress.org/plugins/woocommerce-germanized/
	 *
	 * @return void
	 */
	private function woocommerce_germanized_integration(): void {
		if ( ! class_exists( '\WooCommerce_Germanized' ) ) {
			return;
		}
		$this->container['WoocommerceGermanized']->run();
	}

	/**
	 * https://woocommerce.com/products/woocommerce-additional-variation-images/
	 *
	 * @return void
	 */
	private function woocommerce_additional_variation_images_integration(): void {
		if ( ! defined( 'WC_ADDITIONAL_VARIATION_IMAGES_VERSION' ) ||
			version_compare( WC_ADDITIONAL_VARIATION_IMAGES_VERSION, '1.9.0', 'lt' ) ) {
			return;
		}
		$this->container['WoocommerceAdditionalVariationImages']->run();
	}

	/**
	 * https://woocommerce.com/products/measurement-price-calculator/
	 *
	 * @return void
	 */
	private function measurement_price_calculator_integration(): void {
		if ( ! class_exists( '\WC_Measurement_Price_Calculator_Loader' ) ||
			version_compare( \WC_Measurement_Price_Calculator::VERSION, '3.20.1', 'lt' ) ) {
			return;
		}
		$this->container['MeasurementPriceCalculator']->run();
	}

	/**
	 * https://sweetcode.com/plugins/gadwc/
	 *
	 * @return void
	 */
	private function google_automated_discounts_for_woocommerce_integration(): void {
		if ( ! defined( 'SGADWC_CURRENT_VERSION' ) ||
			version_compare( SGADWC_CURRENT_VERSION, '1.5.99', 'lt' ) ) {
			return;
		}
		$this->container['GoogleAutomatedDiscountsForWoocommerce']->run();
	}

	/**
	 * https://woocommerce.com/payments/
	 *
	 * @return void
	 */
	private function woo_payments_integration(): void {
		if ( ! class_exists( WC_Payments_Features::class ) ||
			! is_callable( [ WC_Payments_Features::class, 'is_customer_multi_currency_enabled' ] ) ) {
			return;
		}
		if ( WC_Payments_Features::is_customer_multi_currency_enabled() ) {
			$this->container['WooPayments']->run();
		}
	}

	/**
	 * @return void
	 */
	private function seo_by_rank_math_integration(): void {
		if ( ! class_exists( RankMath::class ) ) {
			return;
		}
		$this->container['SeoByRankMath']->run();
	}
}
