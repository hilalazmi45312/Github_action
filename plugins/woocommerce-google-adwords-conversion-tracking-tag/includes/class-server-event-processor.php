<?php

namespace SweetCode\Pixel_Manager;

use SweetCode\Pixel_Manager\Pixels\Pixel_Adapter_Registry;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Server-side event processor with filter pipeline
 *
 * Processes server-to-server events through a 5-stage filter pipeline:
 * 1. Pre-processing filters - pmw_server_event_payload_pre (runs once, all events, before pixel processing)
 * 2. Global event filters - pmw_server_event_payload_event_{event} (runs once per event type, before pixel processing)
 * 3. Pixel-specific filters - pmw_server_event_payload_{pixel} (runs per pixel, all events)
 * 4. Pixel + event filters - pmw_server_event_payload_{pixel}_{event} (runs per pixel per event)
 * 5. Post-processing filters - pmw_server_event_payload_post (runs per pixel, before sending to API)
 *
 * Each filter stage can:
 * - Modify the event data
 * - Return null to block the event from being sent
 * - Add custom parameters for tracking/attribution
 *
 * @package SweetCode\Pixel_Manager
 * @since 1.51.0
 */
class Server_Event_Processor {

	/**
	 * Process a server-to-server event through the filter pipeline
	 *
	 * @param array $event_data Raw event data from client
	 * @return void
	 */
	public static function process_event( $event_data ) {

		// Stage 1: Pre-processing filters
		// Apply filters before any pixel-specific processing
		$event_data = apply_filters('pmw_server_event_payload_pre', $event_data);

		// If pre-processing filter returns null/false, stop processing
		if (empty($event_data)) {
			return;
		}

		// Extract the event name for use in filters (e.g., 'add_to_cart', 'purchase')
		$event_name = isset($event_data['event']) ? $event_data['event'] : null;

		// Stage 2: Global event filter (runs once per event type, before pixel processing)
		// Apply filter for this specific event across all pixels
		if ($event_name) {
			$event_data = apply_filters("pmw_server_event_payload_event_{$event_name}", $event_data, $event_name);

			// If event filter returns null/false, stop processing
			if (empty($event_data)) {
				return;
			}
		}

		// Send to each active pixel
		self::send_to_pixels($event_data, $event_name);
	}

	/**
	 * Send event data to active server-to-server pixels
	 *
	 * @param array $event_data Processed event data
	 * @param string|null $event_name The event name (e.g., 'add_to_cart')
	 * @return void
	 */
	private static function send_to_pixels( $event_data, $event_name = null ) {

		// Get all registered pixel adapters
		$adapters = Pixel_Adapter_Registry::get_adapters();

		// Process each pixel through its adapter
		foreach ($adapters as $pixel_name => $adapter) {

			// Check if event data exists for this pixel
			if (!isset($event_data[ $pixel_name ])) {
				continue;
			}

			// Check if adapter is available (class exists, etc.)
			if (!$adapter->is_available()) {
				continue;
			}

			// Let the adapter process the event (handles filtering and sending)
			$adapter->process_event($event_data[ $pixel_name ], $event_name);
		}
	}
}
