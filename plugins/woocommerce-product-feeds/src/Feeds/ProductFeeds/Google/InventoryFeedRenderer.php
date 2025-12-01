<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;
use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\AbstractProductFeedRenderer;
use Ademti\WoocommerceProductFeeds\Helpers\DebugService;
use Ademti\WoocommerceProductFeeds\Traits\FormatsFeedOutput;
use function count;
use function header;
use function number_format;

class InventoryFeedRenderer extends AbstractProductFeedRenderer {

	use FormatsFeedOutput;

	/**
	 * Whether tax is excluded or not.
	 */
	private bool $tax_excluded = false;

	/**
	 * @var FeedConfig
	 */
	private FeedConfig $feed_config;

	/**
	 * Constructor. Grab the settings, and add filters if we have stuff to do
	 *
	 * @access public
	 *
	 * @param Configuration $configuration
	 * @param DebugService $debug
	 * @param StoreInfo $store_info
	 */
	public function __construct(
		Configuration $configuration,
		DebugService $debug,
		StoreInfo $store_info
	) {
		parent::__construct( $configuration, $debug, $store_info );
		if ( ! empty( $this->store_info->base_country ) ) {
			if ( 'US' === substr( $this->store_info->base_country, 0, 2 ) ||
				'CA' === substr( $this->store_info->base_country, 0, 2 ) ||
				'IN' === substr( $this->store_info->base_country, 0, 2 ) ) {
				$this->tax_excluded = true;
			}
		}
	}

	/**
	 * @param FeedConfig $feed_config
	 *
	 * @return void
	 */
	public function initialise( FeedConfig $feed_config ) {
		$this->feed_config = $feed_config;
	}

	/**
	 * Render the feed header information
	 */
	public function render_header(): void {

		header( 'Content-Type: application/xml; charset=UTF-8' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['feeddownload'] ) ) {
			header( 'Content-Disposition: attachment; filename="E-Commerce_Product_Inventory.xml"' );
		} else {
			header( 'Content-Disposition: inline; filename="E-Commerce_Product_Inventory.xml"' );
		}

		// Core feed information
		echo "<?xml version='1.0' encoding='UTF-8' ?>\n";
		echo "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom' xmlns:g='http://base.google.com/ns/1.0'>\n";
		echo "  <channel>\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '    <title>' . $this->esc_xml( $this->store_info->blog_name . ' Products' ) . "</title>\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '    <link>' . $this->store_info->site_url . "</link>\n";
		echo "    <description>This is the WooCommerce Product Inventory feed</description>\n";
		echo '    <generator>WooCommerce Google Product Feed Plugin v';
		echo esc_html( WOOCOMMERCE_GPF_VERSION );
		echo " (https://plugins.leewillis.co.uk/downloads/woocommerce-google-product-feed/)</generator>\n";
		echo "    <atom:link href='" . esc_url( $this->store_info->feed_url_base . $this->feed_config->id ) . "' rel='self' type='application/rss+xml' />\n";
	}


	/**
	 * Generate the output for an individual item
	 *
	 * @access public
	 *
	 * @param ProductFeedItem $feed_item The information about the item
	 *
	 * @return string
	 */
	public function render_item( ProductFeedItem $feed_item ): string {
		// Google do not allow free items in the feed.
		if ( empty( $feed_item->price_inc_tax ) ) {
			return '';
		}
		$output  = '';
		$output .= "    <item>\n";
		$output .= '      <guid>' . $feed_item->guid . "</guid>\n";

		$output .= $this->generate_price_output( $feed_item );

		if ( count( $feed_item->additional_elements ) ) {
			foreach ( $feed_item->additional_elements as $element_name => $element_values ) {
				foreach ( $element_values as $element_value ) {
					if ( 'availability' === $element_name ) {
						// Google no longer supports "available for order". Mapped this to "in stock" as per
						// specification update September 2014.
						if ( 'available for order' === $element_value ) {
							$element_value = 'in stock';
						}
						// Only send a value if the product is in stock
						if ( ! $feed_item->is_in_stock ) {
							$element_value = 'out of stock';
						}
					}
					$output .= '      <g:' . $element_name . '>';
					$output .= $this->esc_xml( $element_value );
					$output .= '</g:' . $element_name . ">\n";
				}
			}
		}

		$output .= "    </item>\n";

		return $output;
	}

	/**
	 * Render the applicable price elements.
	 *
	 * @param ProductFeedItem $feed_item The feed item to be rendered.
	 *
	 * @return string
	 */
	private function generate_price_output( ProductFeedItem $feed_item ): string {

		// Regular price
		if ( $this->tax_excluded ) {
			// Some country prices have to be submitted excluding tax
			$price = number_format( $feed_item->regular_price_ex_tax, 2, '.', '' );
		} else {
			// Others have to be submitted including tax
			$price = number_format( $feed_item->regular_price_inc_tax, 2, '.', '' );
		}
		$output = '      <g:price>' . $price . ' ' . $this->store_info->currency . "</g:price>\n";

		// If there's no sale price, then we're done.
		if ( empty( $feed_item->sale_price_inc_tax ) ) {
			return $output;
		}

		// Otherwise, include the sale_price tag.
		if ( $this->tax_excluded ) {
			$sale_price = number_format( $feed_item->sale_price_ex_tax, 2, '.', '' );
		} else {
			$sale_price = number_format( $feed_item->sale_price_inc_tax, 2, '.', '' );
		}
		$output .= '      <g:sale_price>' . $sale_price . ' ' . $this->store_info->currency . "</g:sale_price>\n";

		// Include start / end dates if provided.
		if ( ! empty( $feed_item->sale_price_start_date ) &&
			! empty( $feed_item->sale_price_end_date ) ) {
			$effective_date  = (string) $feed_item->sale_price_start_date;
			$effective_date .= '/';
			$effective_date .= (string) $feed_item->sale_price_end_date;
			$output         .= '      <g:sale_price_effective_date>' . $effective_date . '</g:sale_price_effective_date>';
		}

		return $output;
	}

	/**
	 * Output the feed footer
	 *
	 * @access public
	 */
	public function render_footer(): void {
		echo "  </channel>\n";
		echo '</rss>';
		exit();
	}
}
