<?php
/**
 * class-woocommerce-product-search-system.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package woocommerce-product-search
 * @since 4.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System-related helper functions.
 */
class WooCommerce_Product_Search_System {

	/**
	 * Return the memory limit in bytes.
	 *
	 * @return int
	 */
	public static function get_memory_limit() {
		$memory_limit = ini_get( 'memory_limit' );

		$matches = null;
		preg_match( '/([0-9]+)(.)/', $memory_limit, $matches );
		if ( isset( $matches[2] ) ) {

			$exp = array( 'K' => 1, 'M' => 2, 'G' => 3, 'T' => 4, 'P' => 5, 'E' => 6 );
			if ( key_exists( $matches[2], $exp ) ) {
				$memory_limit = intval( preg_replace( '/[^0-9]/', '', $memory_limit ) ) * pow( 1024, $exp[$matches[2]] );
			}
		} else {
			$memory_limit = intval( $memory_limit );
		}

		if ( $memory_limit < 0 ) {
			$memory_limit = PHP_INT_MAX;
		}

		return $memory_limit;
	}

	/**
	 * Return the maximum execution time in seconds.
	 *
	 * @return int
	 */
	public static function get_max_execution_time() {
		$max_execution_time = intval( ini_get( 'max_execution_time' ) );

		if ( $max_execution_time === 0 ) {
			$max_execution_time = PHP_INT_MAX;
		}

		return $max_execution_time;
	}

	/**
	 * Lift or limit the maximum execution time.
	 *
	 * @param int $seconds limit to the given number of seconds or lift the limit when the value 0 is provided
	 *
	 * @return boolean whether the action was successful
	 */
	public static function set_time_limit( $seconds = 0 ) {
		$success = false;
		if ( is_numeric( $seconds ) ) {
			$seconds = max( 0, intval( $seconds ) );
		} else {
			$seconds = 0;
		}
		if ( function_exists( 'set_time_limit' ) ) {
			$success = @set_time_limit( $seconds );
		} else if ( function_exists( 'ini_set' ) ) {
			$success = @ini_set( 'max_execution_time', $seconds ) !== false;
		}
		return $success;
	}
}
