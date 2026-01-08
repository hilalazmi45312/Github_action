<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: TikTok Events API
 *
 * @since 1.53.0
 */
class Tiktok_EAPI extends Opportunity {

	public static function available() {

		// TikTok Pixel must be enabled
		if (!Options::is_tiktok_active()) {
			return false;
		}

		// TikTok EAPI must be disabled
		if (Options::is_tiktok_eapi_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return array(
			'id'              => 'tiktok-eapi',
			'title'           => esc_html__(
				'TikTok Events API',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => array(
				esc_html__(
					'TikTok Pixel is enabled but the Events API is not configured.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'The Events API sends events directly from your server to TikTok, bypassing browser limitations like ad blockers and cookie restrictions. This improves attribution accuracy and campaign optimization.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			),
			'impact'          => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('tiktok_eapi_token'),
			'learn_more_link' => 'https://ads.tiktok.com/help/article?aid=10012410',
			'since'           => 1733875200, // timestamp: December 11, 2024
		);
	}
}
