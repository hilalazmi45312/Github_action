<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Google Enhanced Conversions
 *
 * @since 1.28.0
 */
class Google_Enhanced_Conversions extends Opportunity {

	public static function available() {

		// Google must be active
		if (!Options::is_google_active()) {
			return false;
		}

		// Enhanced Conversions must be disabled
		if (Options::is_google_enhanced_conversions_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'              => 'google-enhanced-conversions',
			'title'           => esc_html__(
				'Google Enhanced Conversions',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => [
				esc_html__(
					'The Pixel Manager detected that Google is enabled, but Enhanced Conversions for Google has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Enhanced Conversions for Google will help you track more conversions that otherwise would get lost, such as cross-device conversions.',
					'woocommerce-google-adwords-conversion-tracking-tag	'
				),
			],
			'impact'          => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('google_enhanced_conversions'),
			'learn_more_link' => Documentation::get_link('opportunity_google__enhanced_conversions'),
			'since'           => 1672895375, // timestamp
		];
	}
}
