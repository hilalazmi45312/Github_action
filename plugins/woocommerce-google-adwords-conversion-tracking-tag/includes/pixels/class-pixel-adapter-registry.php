<?php

namespace SweetCode\Pixel_Manager\Pixels;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Registry for server-side pixel adapters
 *
 * Allows adapters to auto-register themselves and provides
 * a central point to retrieve all available adapters.
 *
 * @package SweetCode\Pixel_Manager\Pixels\Adapters
 * @since 1.51.0
 */
class Pixel_Adapter_Registry {

	/**
	 * Registered adapters
	 *
	 * @var Pixel_Adapter[]
	 */
	private static $adapters = [];

	/**
	 * Register a pixel adapter
	 *
	 * @param Pixel_Adapter $adapter The adapter to register
	 * @return void
	 */
	public static function register( Pixel_Adapter $adapter ) {
		self::$adapters[ $adapter->get_pixel_name() ] = $adapter;
	}

	/**
	 * Get all registered adapters
	 *
	 * @return Pixel_Adapter[]
	 */
	public static function get_adapters() {
		return self::$adapters;
	}

	/**
	 * Get a specific adapter by pixel name
	 *
	 * @param string $pixel_name The pixel name (e.g., 'facebook', 'tiktok')
	 * @return Pixel_Adapter|null The adapter or null if not found
	 */
	public static function get_adapter( $pixel_name ) {
		return isset(self::$adapters[ $pixel_name ]) ? self::$adapters[ $pixel_name ] : null;
	}

	/**
	 * Check if an adapter is registered
	 *
	 * @param string $pixel_name The pixel name
	 * @return bool True if registered, false otherwise
	 */
	public static function has_adapter( $pixel_name ) {
		return isset(self::$adapters[ $pixel_name ]);
	}
}
