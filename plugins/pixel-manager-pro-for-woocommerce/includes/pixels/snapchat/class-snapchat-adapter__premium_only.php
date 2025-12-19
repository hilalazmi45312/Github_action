<?php

namespace SweetCode\Pixel_Manager\Pixels\Snapchat;

use SweetCode\Pixel_Manager\Pixels\Core\Abstract_Pixel_Adapter;
use SweetCode\Pixel_Manager\Pixels\Core\Pixel_Descriptor;
use SweetCode\Pixel_Manager\Pixels\Core\Pixel_Registry;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Snapchat CAPI adapter for server-side event processing
 *
 * Handles filtering and sending events to Snapchat Conversions API.
 * Also implements Pixel_Descriptor for unified pixel registry.
 *
 * @package SweetCode\Pixel_Manager\Pixels\Adapters\Snapchat
 * @since 1.51.0
 */
class Snapchat_Adapter extends Abstract_Pixel_Adapter implements Pixel_Descriptor {

	/**
	 * Constructor - auto-registers this adapter and descriptor
	 */
	public function __construct() {
		Pixel_Registry::register( $this );
		Pixel_Registry::register_descriptor( $this );
	}

	/**
	 * Get the pixel name
	 *
	 * @return string
	 */
	public function get_pixel_name() {
		return 'snapchat';
	}

	/**
	 * Get the pixel name (Pixel_Descriptor interface)
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->get_pixel_name();
	}

	/**
	 * Get the pixel's human-readable label
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Snapchat';
	}

	/**
	 * Get the pixel's category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'marketing';
	}

	/**
	 * Check if the pixel is currently active
	 *
	 * @return bool
	 */
	public function is_active() {
		return \SweetCode\Pixel_Manager\Options::is_snapchat_active();
	}

	/**
	 * Check if the pixel has browser-side tracking
	 *
	 * @return bool
	 */
	public function has_browser_tracking() {
		return true;
	}

	/**
	 * Check if the pixel has server-side tracking
	 *
	 * @return bool
	 */
	public function has_server_tracking() {
		return true;
	}

	/**
	 * Get the pixel's configuration schema
	 *
	 * @return array
	 */
	public function get_config_schema() {
		return [];
	}

	/**
	 * Check if Snapchat CAPI is available
	 *
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'SweetCode\Pixel_Manager\Pixels\Snapchat\Snapchat_CAPI' );
	}

	/**
	 * Send event data to Snapchat Conversions API
	 *
	 * @param array $pixel_data Filtered event data ready to send
	 * @return void
	 */
	protected function send( $pixel_data ) {
		Snapchat_CAPI::send_s2s_event( $pixel_data );
	}
}

// Auto-register the adapter
new Snapchat_Adapter();
