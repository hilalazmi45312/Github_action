<?php
/**
 * class-rest-controller.php
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
 * @since 6.0.0
 */

namespace com\itthinx\woocommerce\search\engine;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST Controller
 */
class REST_Controller extends \WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wps/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Compute a parameter-based cache key.
	 *
	 * @param array $parameters set of parameters
	 *
	 * @return string
	 */
	protected static function get_cache_key( $parameters ) {

		return md5( json_encode( $parameters ) );
	}

	/**
	 * Convert value to boolean.
	 *
	 * String values 'true' and '1' and 'yes' evaluate to true.
	 * String values 'false' and '0' and 'no' evaluate to false.
	 * Numerical string values other than '0' evaluate to true.
	 * Integer values other than 0 evaluate to true.
	 * Integer value of 0 evaluates to false.
	 * Any other values adopt the value of $default.
	 *
	 * @param string|int|boolean $value
	 * @param boolean $default
	 *
	 * @return boolean
	 */
	public function to_boolean( $value, $default = false ) {
		if ( !is_bool( $default ) ) {
			$default = false;
		}
		if ( is_scalar( $value ) ) {
			if ( is_bool( $value ) ) {

			} else if ( is_int( $value ) ) {
				$value = $value !== 0;
			} else if ( is_string( $value ) ) {
				$value = strtolower( trim( $value ) );
				switch ( $value ) {
					case 'true':
					case '1':
					case 'yes':
						$value = true;
						break;
					case 'false':
					case '0':
					case 'no':
						$value = false;
						break;
					default:
						if ( is_numeric( $value ) ) {
							$value = intval( $value );
							$value = $value !== 0;
						} else {
							$value = $default;
						}
				}
			} else {
				$value = $default;
			}
		}
		return $value;
	}

	/**
	 * Provide the absolute integer or null.
	 *
	 * @param int|string $value
	 *
	 * @return int
	 */
	public function absint_or_null( $value ) {
		if ( is_numeric( $value ) ) {
			return absint( $value );
		}
		return null;
	}

	/**
	 * Provide the string or null.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function string_or_null( $value ) {
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		return null;
	}

	/**
	 * Validate string (single or comma-separated entries) or array based on enum.
	 *
	 * @param mixed $value
	 * @param array $args
	 * @param string $param
	 *
	 * @return boolean
	 */
	public function enum_string_array( $value, $args = null , $param = null ) {
		$valid = true;
		$params = $this->get_collection_params();
		if ( isset( $params[$param] ) ) {
			$enum = isset( $params[$param]['enum'] ) ? $params[$param]['enum'] : array();
			if ( is_array( $enum ) && count( $enum ) > 0 ) {
				if ( is_string( $value ) ) {
					$value = array_unique( array_map( 'trim', explode( ',', $value ) ) );
				}
				if ( is_array( $value ) ) {
					foreach ( $value as $val ) {
						if ( !in_array( $val, $enum ) ) {
							$valid = false;
							break;
						}
					}
				}
			}
		}
		return $valid;
	}

	/**
	 * Sanitize string or array of strings.
	 *
	 * @param string|string[] $value
	 *
	 * @return string|string[]
	 */
	public function sanitize_string_array( $value ) {
		if ( is_string( $value ) ) {
			$value = sanitize_text_field( $value );
		} else if ( is_array( $value ) ) {
			$value = array_map( 'sanitize_text_field', $value );
		} else {
			$value = '';
		}
		return $value;
	}
}
