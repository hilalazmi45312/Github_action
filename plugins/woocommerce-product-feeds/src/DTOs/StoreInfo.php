<?php

namespace Ademti\WoocommerceProductFeeds\DTOs;

use stdClass;
use function get_woocommerce_currency;
use function home_url;
use function wc;

class StoreInfo {

	// Props.
	private string $site_url;
	private string $feed_url_base;
	private string $blog_name;
	private string $currency;
	private string $weight_units;
	private string $base_country;

	/**
	 * List of props which are publicly __get() / __isset()-able.
	 *
	 * @var array|string[]
	 */
	private array $props = [
		'site_url',
		'feed_url_base',
		'blog_name',
		'currency',
		'weight_units',
		'base_country',
	];

	public function __construct() {
		// Assemble the data in a stdClass first so that we can support the legacy filter.
		$temp                = new stdClass();
		$temp->site_url      = home_url( '/' );
		$temp->feed_url_base = home_url( '/woocommerce_gpf/' );
		$temp->blog_name     = get_option( 'blogname' );
		$temp->currency      = get_woocommerce_currency();
		$temp->weight_units  = get_option( 'woocommerce_weight_unit' );
		$temp->base_country  = wc()->countries->get_base_country();
		$temp                = apply_filters( 'woocommerce_gpf_store_info', $temp );

		// Transfer the information into the class properties.
		$this->site_url      = $temp->site_url;
		$this->feed_url_base = $temp->feed_url_base;
		$this->blog_name     = $temp->blog_name;
		$this->currency      = $temp->currency;
		$this->weight_units  = $temp->weight_units;
		$this->base_country  = $temp->base_country;
	}

	/**
	 * @param string $prop
	 *
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function __get( string $prop ) {
		if ( in_array( $prop, $this->props, true ) ) {
			return apply_filters( 'woocommerce_gpf_store_info_' . $prop, $this->{$prop} );
		}
		throw new \RuntimeException( esc_html( 'Invalid property access (' . $prop . ') on StoreInfo' ) );
	}

	/**
	 * @param string $prop
	 *
	 * @return bool
	 */
	public function __isset( string $prop ) {
		if ( in_array( $prop, $this->props, true ) ) {
			return true;
		}
		return false;
	}
}
