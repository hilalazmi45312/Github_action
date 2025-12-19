<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Microsoft Enhanced Conversions
 *
 * @since 1.53.0
 */
class Microsoft_Enhanced_Conversions extends Opportunity {

	public static function available() {

		// Microsoft Ads must be active
		if (!Options::is_bing_active()) {
			return false;
		}

		// Enhanced Conversions must be disabled
		if (Options::is_bing_enhanced_conversions_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'              => 'microsoft-enhanced-conversions',
			'title'           => esc_html__(
				'Microsoft Enhanced Conversions',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => [
				esc_html__(
					'Microsoft Ads is enabled but Enhanced Conversions is not active.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enhanced Conversions sends hashed first-party customer data (e.g. email) to Microsoft, improving conversion attribution accuracy, especially for cross-device and cookieless scenarios.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'          => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('bing_enhanced_conversions'),
			'learn_more_link' => 'https://help.ads.microsoft.com/apex/index/3/en/60178',
			'since'           => 1733529600, // timestamp: December 7, 2025
		];
	}
}
