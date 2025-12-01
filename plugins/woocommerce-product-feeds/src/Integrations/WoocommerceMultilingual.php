<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use WC_Product;
use function add_filter;
use function is_admin;
use function strtoupper;

/**
 * Integration for:
 * https://wordpress.org/plugins/woocommerce-multilingual/
 */
class WoocommerceMultilingual {

	/**
	 * The currency we want to output.
	 */
	private string $currency = '';

	/**
	 * Capture the currency requested. Add hooks / filters.
	 */
	public function run(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$currency = isset( $_REQUEST['currency'] ) ?
			sanitize_text_field( $_REQUEST['currency'] ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Bail if no currency forced.
		if ( empty( $currency ) ) {
			return;
		}

		$this->currency = strtoupper( $currency );
		add_filter( 'wcml_client_currency', [ $this, 'set_currency' ], 10, 1 );
		add_filter( 'woocommerce_gpf_cache_name', [ $this, 'granularise_cache_name' ], 10, 1 );
		add_filter( 'woocommerce_gpf_feed_item', [ $this, 'add_currency_arg_to_product_permalinks' ], 10, 2 );
	}

	/**
	 * Set the desired currency in WooCommerce Multilingual
	 *
	 * @param $current_currency
	 */
	public function set_currency( $current_currency ): string {
		if ( is_admin() ) {
			return $current_currency;
		}
		$wc_session = WC()->session;
		if ( $wc_session ) {
			$wc_session->set( 'client_currency', $this->currency );
			return $this->currency;
		}

		return $current_currency;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function granularise_cache_name( $name ): string {
		return $name . '_' . $this->currency;
	}

	/**
	 * @param $feed_item
	 * @param $wc_product
	 *
	 * @return mixed
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	public function add_currency_arg_to_product_permalinks( ProductFeedItem $feed_item, WC_Product $wc_product ): ProductFeedItem {
		$feed_item->purchase_link = add_query_arg(
			[ 'currency' => $this->currency ],
			$feed_item->purchase_link
		);

		return $feed_item;
	}
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
}
