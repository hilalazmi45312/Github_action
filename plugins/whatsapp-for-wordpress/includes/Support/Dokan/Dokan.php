<?php

namespace NTA_WhatsApp\Support\Dokan;

use NTA_WhatsApp\Helper;

defined( 'ABSPATH' ) || exit;

class Dokan {

	protected static $instance;

	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->run();
		}
	}

	private function __construct() {
	}

	private static function run() {
		if ( Helper::isEnabledDokanVendor() ) {
			SellerDisplay::getInstance();
			Settings::getInstance();
		}
	}
}
