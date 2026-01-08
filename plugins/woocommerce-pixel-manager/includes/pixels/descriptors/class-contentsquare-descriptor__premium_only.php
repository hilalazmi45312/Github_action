<?php
/**
 * Contentsquare Pixel Descriptor
 *
 * Browser-only pixel descriptor for Contentsquare analytics tracking.
 *
 * @package SweetCode\Pixel_Manager
 * @since 1.46.0
 */

namespace SweetCode\Pixel_Manager\Pixels\Descriptors;

use SweetCode\Pixel_Manager\Options;
use SweetCode\Pixel_Manager\Pixels\Core\Abstract_Pixel_Descriptor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Contentsquare_Descriptor
 *
 * Descriptor for Contentsquare pixel (browser-only tracking).
 * Contentsquare is a digital experience analytics platform.
 */
class Contentsquare_Descriptor extends Abstract_Pixel_Descriptor {

	/**
	 * Get the pixel's unique identifier
	 *
	 * @return string
	 */
	public function get_name() {
		return 'contentsquare';
	}

	/**
	 * Get the pixel's human-readable label
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Contentsquare';
	}

	/**
	 * Get the pixel's category
	 *
	 * Note: Contentsquare is primarily a statistics/analytics pixel.
	 *
	 * @return string
	 */
	public function get_category() {
		return 'statistics';
	}

	/**
	 * Check if the pixel is currently active
	 *
	 * @return bool
	 */
	public function is_active() {
		return Options::is_contentsquare_active();
	}
}

// Auto-instantiate to register with the registry
new Contentsquare_Descriptor();
