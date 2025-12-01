<?php

namespace Ademti\WoocommerceProductFeeds\DTOs;

class FallbackRequest {

	protected string $fallback_destination;

	/**
	 * @param  string  $fallback_destination
	 */
	public function __construct( string $fallback_destination ) {
		$this->fallback_destination = $fallback_destination;
	}

	/**
	 * @return string
	 */
	public function get_destination() {
		return $this->fallback_destination;
	}
}
