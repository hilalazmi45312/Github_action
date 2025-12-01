<?php

namespace Ademti\WoocommerceProductFeeds\Configuration;

use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;

/**
 * Class FeedConfigRepository
 */
class FeedConfigRepository {

	private ?array $feed_configs = null;

	/**
	 * Retrieve all feed configs
	 *
	 * @return array
	 */
	public function all() {
		$this->ensure_loaded();
		$results = [];
		foreach ( $this->feed_configs as $feed_id => $feed_config ) {
			$config = new FeedConfig();
			$config->set_id( $feed_id );
			foreach ( $feed_config as $key => $value ) {
				$config->$key = $value;
			}
			$results[] = $config;
		}

		return $results;
	}

	/**
	 * Retrieve a stored config by ID.
	 *
	 * @param string $config_id
	 *
	 * @return FeedConfig|null
	 */
	public function get( $config_id ) {
		$this->ensure_loaded();

		if ( ! isset( $this->feed_configs[ $config_id ] ) ) {
			return null;
		}
		$config     = new FeedConfig();
		$config->id = $config_id;
		foreach ( $this->feed_configs[ $config_id ] as $key => $value ) {
			$config->$key = $value;
		}

		return $config;
	}

	/**
	 * Save a stored config.
	 *
	 * @param array $config
	 * @param string|null $config_id
	 */
	public function save( $config, $config_id = null ): void {
		$this->ensure_loaded();
		if ( null === $config_id ) {
			$config_id = $this->generate_config_id();
		}
		$this->feed_configs[ $config_id ] = $config;
		update_option( 'woocommerce_gpf_feed_configs', $this->feed_configs );
	}

	/**
	 * Delete a stored config.
	 *
	 * @param $config_id
	 */
	public function delete( string $config_id ): void {
		$this->ensure_loaded();
		unset( $this->feed_configs[ $config_id ] );
		update_option( 'woocommerce_gpf_feed_configs', $this->feed_configs );
	}

	/**
	 * Get a list of active product feed feed types.
	 *
	 * @psalm-return list<mixed>
	 */
	public function get_active_feed_formats(): array {
		$this->ensure_loaded();

		return array_values( array_unique( wp_list_pluck( $this->feed_configs, 'type' ) ) );
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 *
	 * @psalm-param 'googlepromotions' $type
	 */
	public function has_active_feed_of_type( string $type ) {
		// ensure_loaded() taken care of by get_active_feed_formats.
		return in_array( $type, $this->get_active_feed_formats(), true );
	}

	/**
	 * Generate a new ID.
	 *
	 * @return string
	 */
	private function generate_config_id() {
		$this->ensure_loaded();
		do {
			$config_id = substr( wp_hash( microtime() ), 0, 16 );
		} while ( isset( $this->feed_configs[ $config_id ] ) );

		return $config_id;
	}

	/**
	 * Ensure that the configs have been loaded from the database.
	 */
	private function ensure_loaded(): void {
		if ( null === $this->feed_configs ) {
			$this->feed_configs = get_option( 'woocommerce_gpf_feed_configs', [] );
		}
	}
}
