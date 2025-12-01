<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use Exception;
use WC_Product;

class GoogleAutomatedDiscountsForWoocommerce {

	/**
	 * @var Container
	 */
	protected Container $container;

	/**
	 * @var StoreInfo
	 */
	protected $store_info;

	/**
	 * @param  Container  $container
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Runs the integration.
	 *
	 * - Registers the auto_pricing_min_price field
	 * - Registers a pre-population option to bring across the value from the GADWC plugin
	 * - Processes the calculated value of auto_pricing_min_price to add a currency if missing
	 */
	public function run() {
		// Initialise the store info when we can.
		add_filter( 'template_redirect', [ $this, 'instantiate_store_info' ], 10 );
		// Register the auto_pricing_min_price field which is only available if this integration is active.
		add_filter( 'woocommerce_gpf_custom_field_list', [ $this, 'register_field' ] );
		// Register the pre-population option.
		add_filter(
			'woocommerce_gpf_all_product_fields_late_filtering',
			[ $this, 'add_auto_pricing_min_price_field' ],
			10,
			1
		);
		// Try and add a currency to values coming from elsewhere if they don't have it.
		add_filter( 'woocommerce_gpf_feed_item_google', [ $this, 'maybe_format' ], 10, 1 );
	}

	/**
	 * @param  ProductFeedItem  $feed_item
	 *
	 * @return ProductFeedItem
	 */
	public function maybe_format( ProductFeedItem $feed_item ): ProductFeedItem {

		// If no value set, nothing to do.
		if ( empty( $feed_item->additional_elements['auto_pricing_min_price'][0] ) ) {
			return $feed_item;
		}

		// If the value isn't purely numeric, leave it as-is.
		if ( ! preg_match( '/^[0-9.]*$/', $feed_item->additional_elements['auto_pricing_min_price'][0] ) ) {
			return $feed_item;
		}

		$formatted = number_format( $feed_item->additional_elements['auto_pricing_min_price'][0], 2 );
		$formatted = $formatted . ' ' . $this->store_info->currency;

		$feed_item->additional_elements['auto_pricing_min_price'] = [ $formatted ];

		return $feed_item;
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 * @throws Exception
	 */
	public function register_field( array $fields ): array {
		$fields['method:GoogleAutomatedDiscountsForWoocommerce::auto_pricing_min_price_field'] =
			__( 'Auto pricing min price from Google Automated Discounts For WooCommerce plugin', 'woocommerce_gpf' );

		return $fields;
	}

	/**
	 * Instantiate a copy of the StoreInfo class.
	 *
	 * @return void
	 */
	public function instantiate_store_info() {
		$this->store_info = $this->container['StoreInfo'];
	}

	/**
	 * @param  WC_Product  $product
	 *
	 * @return string
	 */
	public function auto_pricing_min_price_field( WC_Product $product ): string {
		// Work out the min price. Bail if none set.
		$min_price = $this->get_product_min_price( $product );
		if ( $min_price === null ) {
			return '';
		}

		// Return the value, formatted with currency string.
		return number_format( $min_price, 2 ) . ' ' . $this->store_info->currency;
	}

	/**
	 * Registers the auto_pricing_min_price field and makes it available on the options page.
	 *
	 * @param  array  $fields
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public function add_auto_pricing_min_price_field( array $fields ): array {
		$fields['auto_pricing_min_price'] = [
			'desc'            => __( 'Auto pricing min price', 'woocommerce_gpf' ),
			'full_desc'       => __(
				"Provides a minimum price for Google's Automated Discount program. Requires the 'Google Automated Discounts for WooCommerce' plugin.",
				'woocommerce_gpf'
			),
			'can_default'     => true,
			'callback'        => 'render_textfield',
			'can_prepopulate' => true,
			'feed_types'      => [ 'google' ],
			'google_len'      => 100,
			'max_values'      => 1,
			'ui_group'        => 'advanced',
		];

		return $fields;
	}

	/**
	 * @param  WC_Product  $product
	 *
	 * @return ?float
	 */
	private function get_product_min_price( WC_Product $product ): ?float {
		$min_price_manual = $product->get_meta( '_google_auto_min_price_man', true );
		$min_price_manual = $min_price_manual !== '' ? (float) $min_price_manual : null;
		$min_price_calc   = $product->get_meta( '_google_auto_min_price_calc', true );
		$min_price_calc   = $min_price_calc !== '' ? (float) $min_price_calc : null;
		$min_price        = null;

		// From the GADWC developers:
		// If _google_auto_min_price_calc is set, use _google_auto_min_price_calc. Override
		// it with _google_auto_min_price_man if that field is set as well.
		if ( ! empty( $min_price_calc ) ) {
			$min_price = $min_price_calc;
		}

		if ( ! empty( $min_price_manual ) ) {
			$min_price = $min_price_manual;
		}

		return $min_price;
	}
}
