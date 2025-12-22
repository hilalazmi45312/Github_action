<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Snapchat Conversions API
 *
 * @since 1.53.0
 */
class Snapchat_Capi extends Opportunity {

	public static function available() {

		// Snapchat must be active
		if (!Options::is_snapchat_active()) {
			return false;
		}

		// Snapchat CAPI must be disabled
		if (Options::is_snapchat_capi_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'              => 'snapchat-capi',
			'title'           => esc_html__(
				'Snapchat Conversions API',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => [
				esc_html__(
					'Snapchat Pixel is enabled but the Conversions API (CAPI) is not configured.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'The Conversions API sends events directly from your server to Snapchat, bypassing browser limitations like ad blockers and cookie restrictions. This improves attribution accuracy and campaign optimization.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'          => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('snapchat_capi_token'),
			'learn_more_link' => 'https://businesshelp.snapchat.com/s/article/conversions-api',
			'since'           => 1733529600, // timestamp: December 7, 2025
		];
	}
}
