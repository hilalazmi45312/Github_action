<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use Ademti\WoocommerceProductFeeds\Helpers\DebugService;
use function apply_filters;
use function get_option;
use function html_entity_decode;
use function iconv;
use function str_replace;
use function strpos;

/**
 * Feed base class.
 *
 * Provides common methods used by all feeds, and sets up main feed properties.
 */
abstract class AbstractProductFeedRenderer {

	// Dependencies.
	protected Configuration $configuration;
	protected DebugService $debug;

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * The core store information.
	 */
	protected StoreInfo $store_info;

	/**
	 * Constructor.
	 * Grab the settings, and set up the store info object
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
		$this->configuration = $configuration;
		$this->debug         = $debug;
		$this->store_info    = $store_info;

		// Read the settings, and set up the store info object.
		$this->settings = $this->configuration->get_settings();
	}

	/**
	 * Helper function used to output an escaped value for use in a CSV
	 *
	 * @access protected
	 *
	 * @param string $string The string to be escaped
	 *
	 * @return string         The escaped string
	 */
	protected function csvescape( string $string ): string {

		$done_escape = false;
		if ( strpos( $string, '"' ) !== false ) {
			$string      = str_replace( '"', '""', $string );
			$string      = "\"$string\"";
			$done_escape = true;
		}

		$string = str_replace( [ "\n", "\r" ], ' ', $string );

		if ( ! $done_escape && stripos( $string, ',' ) !== false ) {
			$string = "\"$string\"";
		}

		return $string;
	}

	/**
	 * Override this to generate output at the start of the file
	 * Opening XML declarations, CSV header rows etc.
	 *
	 * @access public
	 */
	abstract public function render_header(): void;

	/**
	 * Override this to generate the output for an individual item
	 *
	 * @access public
	 *
	 * @param ProductFeedItem $item object Item object
	 *
	 * @return string
	 */
	abstract public function render_item( ProductFeedItem $item ): string;

	/**
	 * Override this to generate output at the start of the file
	 * Opening XML declarations, CSV header rows etc.
	 *
	 * @access public
	 *
	 * @param  $store_info object Object containing information about the store
	 */
	abstract public function render_footer(): void;
}
