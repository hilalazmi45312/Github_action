<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Bing;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\AbstractProductFeedRenderer;
use Ademti\WoocommerceProductFeeds\Helpers\DebugService;
use Ademti\WoocommerceProductFeeds\Traits\FormatsFeedOutput;
use function apply_filters;
use function do_action;
use function header;
use function number_format;
use function setlocale;
use function strtolower;
use function substr;
use function wp_filter_nohtml_kses;

class ProductFeedRenderer extends AbstractProductFeedRenderer {

	use FormatsFeedOutput;

	private string $old_locale = 'en_US';

	/**
	 * Constructor. Grab the settings, and add filters if we have stuff to do
	 *
	 * @access public
	 *
	 * @param Configuration $configuration
	 * @param DebugService $debug
	 */
	public function __construct(
		Configuration $configuration,
		DebugService $debug,
		StoreInfo $store_info
	) {
		parent::__construct( $configuration, $debug, $store_info );
		// Bing doesn't like foreign chars. Note the current locale so we can swap back afterwards.
		$this->old_locale = get_locale();
	}

	/**
	 * Determine if prices should include, or exclude taxes.
	 *
	 * Country list from: https://help.bingads.microsoft.com/#apex/3/en/56731/1
	 */
	private function include_tax(): bool {
		return 'US' !== $this->store_info->base_country;
	}

	/**
	 * Render the feed header information
	 *
	 * @access public
	 */
	public function render_header(): void {

		// Bing doesn't like foreign chars
		setlocale( LC_CTYPE, 'en_US.UTF-8' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['feeddownload'] ) ) {
			header( 'Content-Disposition: attachment; filename="E-Commerce_Product_List.txt"' );
		} else {
			header( 'Content-Disposition: inline; filename="E-Commerce_Product_List.txt"' );
		}
		header( 'Content-Type: text/csv' );

		// Mandatory fields
		echo "id\ttitle\tlink\tprice\tdescription\timage_link\tavailability";

		// Optional fields
		if ( isset( $this->settings['product_fields']['bing_category'] ) ) {
			echo "\tproduct_category";
		}
		if ( isset( $this->settings['product_fields']['brand'] ) ) {
			echo "\tbrand";
		}
		if ( isset( $this->settings['product_fields']['mpn'] ) ) {
			echo "\tmpn";
		}
		if ( isset( $this->settings['product_fields']['gtin'] ) ) {
			echo "\tgtin";
		}
		if ( isset( $this->settings['product_fields']['condition'] ) ) {
			echo "\tCondition";
		}
		if ( isset( $this->settings['product_fields']['custom_label_0'] ) ) {
			echo "\tcustom_label_0";
		}
		if ( isset( $this->settings['product_fields']['custom_label_1'] ) ) {
			echo "\tcustom_label_1";
		}
		if ( isset( $this->settings['product_fields']['custom_label_2'] ) ) {
			echo "\tcustom_label_2";
		}
		if ( isset( $this->settings['product_fields']['custom_label_3'] ) ) {
			echo "\tcustom_label_3";
		}
		if ( isset( $this->settings['product_fields']['custom_label_4'] ) ) {
			echo "\tcustom_label_4";
		}
		if ( isset( $this->settings['product_fields']['shippingprice'] ) ) {
			echo "\tshipping(price)";
		}
		if ( isset( $this->settings['product_fields']['shippingcountryprice'] ) ) {
			echo "\tshipping(country:price)";
		}
		if ( isset( $this->settings['product_fields']['shippingcountryserviceprice'] ) ) {
			echo "\tshipping(country:service:price)";
		}
		if ( isset( $this->settings['product_fields']['bing_promotion_id'] ) ) {
			echo "\tpromotion_ID";
		}
		do_action( 'woocommerce_gpf_bing_feed_header_output', $this->settings );
		echo "\r\n";
	}

	/**
	 * Helper function used to output a value in a warnings-safe way
	 *
	 * @access public
	 *
	 * @param object $feed_item  The information about the item
	 * @param string $key  The particular attribute to output
	 *
	 * @return string  The output for this element.
	 */
	private function generate_element_output( $feed_item, $key ): string {
		$output          = '';
		$convert_charset = true;
		if ( 'brand' === $key ) {
			$convert_charset = false;
		}
		if ( isset( $this->settings['product_fields'][ $key ] ) ) {
			if ( isset( $feed_item->additional_elements[ $key ] ) ) {
				$output .= "\t" . $this->tsvescape( $feed_item->additional_elements[ $key ][0], $convert_charset );
			} else {
				$output .= "\t";
			}
		}

		return $output;
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

		if ( empty( $feed_item->price_inc_tax ) ) {
			return '';
		}

		$output = '';
		// id
		$output .= $this->tsvescape( $feed_item->guid ) . "\t";

		// title
		$output .= $this->tsvescape( substr( $feed_item->title, 0, 255 ), false ) . "\t";

		// link
		$output .= $this->tsvescape( $feed_item->purchase_link ) . "\t";

		// price
		if ( $this->include_tax() ) {
			$price = number_format( $feed_item->price_inc_tax, 2, '.', '' );
		} else {
			$price = number_format( $feed_item->price_ex_tax, 2, '.', '' );
		}
		$output .= $this->tsvescape( $price ) . "\t";

		// description
		// Bing doesn't allow HTML in descriptions.
		$description = wp_filter_nohtml_kses( $feed_item->description );
		$description = substr( $description, 0, 5000 );
		$output     .= $this->tsvescape( $description, false ) . "\t";

		// image_link
		if ( ! empty( $feed_item->image_link ) ) {
			$output .= $this->tsvescape( $feed_item->image_link );
		}

		// availability
		if ( isset( $feed_item->additional_elements['availability'][0] ) ) {
			switch ( $feed_item->additional_elements['availability'][0] ) {
				case 'preorder':
					$output .= "\tPre-Order";
					break;
				case 'backorder':
					$output .= "\tBack-Order";
					break;
				case 'out of stock':
					$output .= "\tOut Of Stock";
					break;
				case 'in stock':
				default:
					$output .= "\tIn Stock";
					break;
			}
		} else {
			$output .= "\tIn Stock";
		}

		$output .= $this->generate_element_output( $feed_item, 'bing_category' );
		$output .= $this->generate_element_output( $feed_item, 'brand' );
		$output .= $this->generate_element_output( $feed_item, 'mpn' );
		$output .= $this->generate_element_output( $feed_item, 'gtin' );

		if ( isset( $this->settings['product_fields']['condition'] ) ) {
			if ( ! empty( $feed_item->additional_elements['condition'][0] ) ) {
				switch ( strtolower( $feed_item->additional_elements['condition'][0] ) ) {
					case 'new':
						$output .= "\t" . $this->tsvescape( 'New' );
						break;
					case 'refurbished':
						$output .= "\t" . $this->tsvescape( 'Refurbished' );
						break;
					case 'used':
						$output .= "\t" . $this->tsvescape( 'Used' );
						break;
				}
			} else {
				$output .= "\t";
			}
		}

		$output .= $this->generate_element_output( $feed_item, 'custom_label_0' );
		$output .= $this->generate_element_output( $feed_item, 'custom_label_1' );
		$output .= $this->generate_element_output( $feed_item, 'custom_label_2' );
		$output .= $this->generate_element_output( $feed_item, 'custom_label_3' );
		$output .= $this->generate_element_output( $feed_item, 'custom_label_4' );

		$output .= $this->generate_element_output( $feed_item, 'shippingprice' );
		$output .= $this->generate_element_output( $feed_item, 'shippingcountryprice' );
		$output .= $this->generate_element_output( $feed_item, 'shippingcountryserviceprice' );

		$output .= $this->generate_element_output( $feed_item, 'bing_promotion_id' );

		$output .= apply_filters( 'woocommerce_gpf_bing_feed_row_output', '', $feed_item );
		$output .= "\r\n";

		return $output;
	}

	/**
	 * Output the feed footer
	 *
	 * @access public
	 */
	public function render_footer(): void {
		// Restore original locale - for completeness if anything else ever happens here.
		setlocale( LC_CTYPE, $this->old_locale );
		exit();
	}
}
