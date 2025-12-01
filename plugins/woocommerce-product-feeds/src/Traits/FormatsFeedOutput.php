<?php

namespace Ademti\WoocommerceProductFeeds\Traits;

use function apply_filters;
use function esc_xml;
use function function_exists;
use function html_entity_decode;
use function iconv;
use function preg_replace;
use function str_replace;
use function stripos;
use function strpos;

trait FormatsFeedOutput {

	/**
	 * Helper function used to output an escaped value for use in a tab separated file
	 *
	 * @access protected
	 *
	 * @param string $string The string to be escaped
	 * @param bool $charset_convert
	 *
	 * @return string         The escaped string
	 */
	protected function tsvescape( string $string, bool $charset_convert = true ): string {

		$string = html_entity_decode( $string, ENT_HTML401 | ENT_QUOTES ); // Convert any HTML entities
		if ( $charset_convert ) {
			$string = iconv(
				'UTF-8',
				'ASCII//TRANSLIT//IGNORE',
				$string
			);
		}

		$doneescape = false;
		if ( strpos( $string, '"' ) !== false ) {
			$string     = str_replace( '"', '""', $string );
			$string     = "\"$string\"";
			$doneescape = true;
		}
		$string = str_replace( [ "\n", "\r", "\t" ], ' ', $string );

		if ( ! $doneescape && stripos( $string, apply_filters( 'woocommerce_gpf_tsv_separator', "\t" ) ) !== false ) {
			$string = "\"$string\"";
		}

		return apply_filters( 'woocommerce_gpf_tsv_escape_string', $string );
	}

	/**
	 * Escape a value for use in XML.
	 *
	 * Uses WordPress' esc_xml if available. Otherwise @param string $value
	 *
	 * @return string
	 * @see old_esc_xml()
	 *
	 */
	protected function esc_xml( string $value ): string {
		$value = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u',
			'',
			$value
		);

		// Use old-style CDATA escaping if esc_xml() not present.
		if ( ! function_exists( 'esc_xml' ) ) { //
			return $this->old_esc_xml( $value );
		}

		// WordPress' esc_xml() can return an empty string on some failure cases it would seem,
		// so, we'll grab the result and only use if either the input was empty, or
		// the escaped content is non-empty.
		$escaped = esc_xml( $value );
		if ( empty( $value ) || ! empty( $escaped ) ) {
			return esc_xml( $value );
		}

		// If we get here, we had a non-empty input string, but got an empty string back
		// from esc_xml(). We fall back on old-style CDATA escaping.
		return $this->old_esc_xml( $value );
	}

	/**
	 * Trim out bogus UTF-8 chars, and CDATA wrap the string.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function old_esc_xml( string $value ): string {
		$value = str_replace( ']]>', ']]]]><![CDATA[>', $value );

		return '<![CDATA[' . $value . ']]>';
	}
}
