<?php

namespace Ademti\WoocommerceProductFeeds\DTOs;

use Exception;
use RuntimeException;

class FeedConfig {

	/**
	 * Default config on which to base items.
	 */
	private const DEFAULT_CONFIG = [
		'id'              => '',
		'type'            => '',
		'name'            => '',
		'category_filter' => '',
		'categories'      => [],
		'start'           => 0,
		'limit'           => -1,
	];

	/**
	 * The config settings.
	 */
	private array $config;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->config = apply_filters( 'woocommerce_gpf_feed_config_default', self::DEFAULT_CONFIG );
	}

	/**
	 * @param string $type
	 */
	public function set_type( string $type ): void {
		$this->config['type'] = $type;
	}

	/**
	 * @param string $category_filter
	 */
	public function set_category_filter( string $category_filter ): void {
		$this->config['category_filter'] = $category_filter;
	}

	/**
	 * @param array $categories
	 */
	public function set_categories( array $categories ): void {
		$this->config['categories'] = $categories;
	}

	/**
	 * @param string $start
	 *
	 * @throws Exception
	 */
	public function set_start( string $start ): void {
		if ( ! is_numeric( $start ) ) {
			throw new RuntimeException(
				esc_html( 'Invalid feed start requested: ' . $start )
			);
		}
		$this->config['start'] = $start;
	}

	/**
	 * @param string $limit
	 */
	public function set_limit( string $limit ): void {
		$this->config['limit'] = $limit;
	}

	/**
	 * @param string $name
	 */
	public function set_name( string $name ): void {
		$this->config['name'] = $name;
	}

	/**
	 * @param string $feed_id
	 */
	public function set_id( string $feed_id ): void {
		$this->config['id'] = $feed_id;
	}

	/**
	 * Magic setter for non-core config properties.
	 *
	 * @param $key
	 * @param $value
	 */
	public function __set( $key, $value ) {
		$setter = 'set_' . $key;
		if ( is_callable( [ $this, $setter ] ) ) {
			$this->$setter( $value );

			return;
		}
		$valid_keys = apply_filters( 'woocommerce_gpf_feed_config_valid_extra_keys', [] );
		if ( in_array( $key, $valid_keys, true ) ) {
			$this->config[ $key ] = $value;
		}
	}

	/**
	 * Magic getter.
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __get( $key ) {
		// Return the property if it is set.
		if ( isset( $this->config[ $key ] ) ) {
			return $this->config[ $key ];
		}
		// If not, return NULL if the key is valid...
		$valid_keys = apply_filters( 'woocommerce_gpf_feed_config_valid_extra_keys', [] );
		if ( in_array( $key, $valid_keys, true ) ) {
			return null;
		}
		// ... throw an exception otherwise
		throw new Exception( 'Attempt to retrieve invalid config key' );
	}

	/**
	 * @return array
	 */
	public function to_array() {
		return $this->config;
	}

	/**
	 * Gets an HTML-safe summary of the feed.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function get_readable_summary() {
		$summary = '<strong>' . esc_html( $this->config['name'] ) . "</strong>\n<br>";
		foreach ( $this->config as $key => $value ) {
			if ( in_array( $key, [ 'id', 'name', 'start' ], true ) ) {
				continue;
			}
			if ( 'limit' === $key && -1 === $value ) {
				continue;
			}
			if ( is_array( $value ) ) {
				if ( $key === 'categories' ) {
					$value = $this->generate_category_list( $value );
				} else {
					$value = implode( ', ', $value );
				}
			}
			$key = ucfirst( str_replace( '_', ' ', $key ) );
			if ( empty( $value ) ) {
				$value = '-';
			}
			$summary .= '&nbsp;&nbsp;' . esc_html( $key ) . ': ' . esc_html( $value ) . "\n<br>";
		}

		return $summary;
	}

	/**
	 * @param array $value
	 *
	 * @return string
	 */
	private function generate_category_list( array $value ) {
		$categories = array_map( [ $this, 'generate_category_string' ], $value );

		return implode( ', ', $categories );
	}

	/**
	 * @param $term_id
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @return mixed|string|null
	 * @throws Exception
	 */
	private function generate_category_string( $term_id ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term ) {
			return __( 'Unknown category - maybe deleted?', 'woocommerce_gpf' );
		}
		$parent_string = get_term_parents_list(
			$term_id,
			'product_cat',
			[
				'separator' => ' » ',
				'link'      => false,
				'inclusive' => false,
			]
		);
		return esc_html( $parent_string . $term->name . '(' . $term_id . ')' );
	}
}
