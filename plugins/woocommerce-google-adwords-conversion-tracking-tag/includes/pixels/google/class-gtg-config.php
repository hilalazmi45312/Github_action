<?php
/**
 * Google Tag Gateway Configuration
 *
 * Handles server-side detection of the optimal GTG handler and provides
 * the configuration to the frontend via wpmDataLayer.
 *
 * @package SweetCode\Pixel_Manager\Pixels\Google
 */

namespace SweetCode\Pixel_Manager\Pixels\Google;

use SweetCode\Pixel_Manager\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Google Tag Gateway Configuration
 *
 * Server-side detection of the optimal proxy handler with caching.
 *
 * Priority:
 * 1. External (Cloudflare) - measurement_path responds without X-PMW-GTG-Handler header
 * 2. Isolated - direct PHP proxy responds with X-PMW-GTG-Handler: isolated
 * 3. WordPress - fallback when others fail
 */
class GTG_Config {

	/**
	 * Transient key for caching the handler
	 */
	const TRANSIENT_KEY = 'pmw_gtg_handler';

	/**
	 * Cache duration in seconds (24 hours)
	 */
	const CACHE_DURATION = DAY_IN_SECONDS;

	/**
	 * Option key for fallback when transients are disabled
	 */
	const OPTION_KEY = 'pmw_gtg_handler_cache';

	/**
	 * Valid handler types
	 */
	const VALID_HANDLERS = [ 'external', 'isolated', 'wordpress' ];

	/**
	 * Detect the GTG handler server-side
	 *
	 * Priority:
	 * 1. External (Cloudflare) - measurement_path responds without X-PMW-GTG-Handler
	 * 2. Isolated - direct PHP proxy responds with X-PMW-GTG-Handler: isolated
	 * 3. WordPress - fallback
	 *
	 * @return string Handler type ('external', 'isolated', 'wordpress')
	 */
	public static function detect_handler() {
		$measurement_path = Options::get_google_tag_gateway_measurement_path();
		$site_url         = get_site_url();

		// If no measurement path configured, can't use external/Cloudflare
		if ( empty( $measurement_path ) ) {
			// Still check if isolated proxy is available
			return self::check_isolated_proxy() ? 'isolated' : 'wordpress';
		}

		// Priority 1: Check if measurement_path is handled by external (Cloudflare)
		$handler = self::check_measurement_path( $site_url . $measurement_path );

		if ( 'external' === $handler ) {
			return 'external';
		}

		// If measurement_path returned 'isolated', use it
		if ( 'isolated' === $handler ) {
			return 'isolated';
		}

		// Priority 2: Check if isolated proxy is available via direct access
		if ( self::check_isolated_proxy() ) {
			return 'isolated';
		}

		// Priority 3: Fallback to WordPress proxy
		return 'wordpress';
	}

	/**
	 * Check measurement_path for handler type
	 *
	 * @param string $base_url The base URL with measurement_path.
	 * @return string|null Handler type or null if check failed
	 */
	private static function check_measurement_path( $base_url ) {
		$health_url = $base_url . '/healthy';

		$response = wp_remote_get(
			$health_url,
			[
				'timeout'   => 5,
				'sslverify' => self::should_verify_ssl(),
				'headers'   => [
					'Cache-Control' => 'no-cache',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$handler_header = wp_remote_retrieve_header( $response, 'x-pmw-gtg-handler' );

		// No header = Cloudflare (external) is proxying
		if ( empty( $handler_header ) ) {
			return 'external';
		}

		// Return the detected handler (isolated or wordpress)
		if ( in_array( $handler_header, self::VALID_HANDLERS, true ) ) {
			return $handler_header;
		}

		return null;
	}

	/**
	 * Check if SSL verification should be disabled for health checks
	 *
	 * Detects local development environments where self-signed certificates
	 * are commonly used.
	 *
	 * @return bool True to verify SSL, false to skip verification
	 */
	private static function should_verify_ssl() {
		// Allow explicit override via filter
		$filter_value = apply_filters( 'pmw_gtg_health_check_sslverify', null );
		if ( null !== $filter_value ) {
			return (bool) $filter_value;
		}

		// Detect common local development domains
		$site_url = get_site_url();
		$host     = wp_parse_url( $site_url, PHP_URL_HOST );

		$local_patterns = [
			'.test',
			'.local',
			'.localhost',
			'.dev',
			'.ddev.site',
			'localhost',
		];

		foreach ( $local_patterns as $pattern ) {
			// PHP 5.6 compatible string ends with check
			$pattern_len = strlen( $pattern );
			if ( substr( $host, -$pattern_len ) === $pattern || ltrim( $pattern, '.' ) === $host ) {
				return false;
			}
		}

		// Check for IP addresses (usually local)
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if isolated proxy is available via direct access
	 *
	 * @return bool True if isolated proxy is available
	 */
	private static function check_isolated_proxy() {
		$proxy_url = GTG_Proxy::get_isolated_proxy_url();

		if ( ! $proxy_url ) {
			return false;
		}

		$response = wp_remote_get(
			$proxy_url . '?healthCheck=1',
			[
				'timeout'   => 5,
				'sslverify' => self::should_verify_ssl(),
				'headers'   => [
					'Cache-Control' => 'no-cache',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$handler_header = wp_remote_retrieve_header( $response, 'x-pmw-gtg-handler' );

		return 'isolated' === $handler_header;
	}

	/**
	 * Get the proxy URL for direct PHP access
	 *
	 * @return string|false
	 */
	public static function get_proxy_url() {
		return GTG_Proxy::get_isolated_proxy_url();
	}

	/**
	 * Get cached handler or detect and cache
	 *
	 * Uses transients with fallback to options table when transients are disabled.
	 *
	 * @param bool $force_refresh Force re-detection.
	 * @return string Handler type
	 */
	public static function get_handler( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = self::get_cached_handler();
			if ( null !== $cached ) {
				return $cached;
			}
		}

		$handler = self::detect_handler();
		self::cache_handler( $handler );

		return $handler;
	}

	/**
	 * Get the cached handler from transient or options
	 *
	 * @return string|null Handler type or null if not cached
	 */
	private static function get_cached_handler() {
		// Try transient first
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( false !== $cached && in_array( $cached, self::VALID_HANDLERS, true ) ) {
			return $cached;
		}

		// Fallback to options table (for when transients are disabled)
		$option = get_option( self::OPTION_KEY );
		if ( is_array( $option ) && isset( $option['handler'], $option['expires'] ) ) {
			// Check if option cache is still valid
			if ( $option['expires'] > time() && in_array( $option['handler'], self::VALID_HANDLERS, true ) ) {
				return $option['handler'];
			}
		}

		return null;
	}

	/**
	 * Cache the handler in transient and options
	 *
	 * @param string $handler Handler type.
	 * @return bool Success
	 */
	private static function cache_handler( $handler ) {
		if ( ! in_array( $handler, self::VALID_HANDLERS, true ) ) {
			return false;
		}

		// Cache in transient
		set_transient( self::TRANSIENT_KEY, $handler, self::CACHE_DURATION );

		// Also cache in options as fallback (with expiry timestamp)
		update_option(
			self::OPTION_KEY,
			[
				'handler' => $handler,
				'expires' => time() + self::CACHE_DURATION,
			],
			false // Don't autoload
		);

		return true;
	}

	/**
	 * Clear the cached handler
	 * Called when GTG settings change
	 *
	 * @return bool
	 */
	public static function clear_cached_handler() {
		delete_transient( self::TRANSIENT_KEY );
		delete_option( self::OPTION_KEY );
		return true;
	}

	/**
	 * Force refresh the handler detection
	 * Called when settings change to immediately detect the new handler
	 *
	 * @return string The newly detected handler
	 */
	public static function refresh_handler() {
		self::clear_cached_handler();
		return self::get_handler( true );
	}
}
