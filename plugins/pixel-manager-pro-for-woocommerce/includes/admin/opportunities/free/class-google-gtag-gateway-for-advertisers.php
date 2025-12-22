<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Google tag gateway for advertisers
 *
 * @since 1.48.0
 */
class Google_Gtag_Gateway_For_Advertisers extends Opportunity {

	public static function available() {

		// Temporarily disabled - we don't want to recommend this in the current release
		return false;

		// Google tag gateway for advertisers must be disabled
		if (Options::get_google_tag_gateway_measurement_path()) {
			return false;
		}

		// Since 1.53.0, the built-in proxy makes the Google Tag Gateway available to everyone
		// No Cloudflare or other external infrastructure required

		return true;
	}

	public static function card_data() {

		return [
			'id'              => 'google-tag-gateway-for-advertisers',
			'title'           => esc_html__(
				'Google tag gateway for advertisers',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => [
				esc_html__(
					'The Pixel Manager detected that you are not using the Google tag gateway for advertisers.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling the Google tag gateway for advertisers will allow you to track conversions and events more accurately.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'          => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('google_tag_gateway_measurement_path'),
			'learn_more_link' => 'https://support.google.com/google-ads/answer/16214371',
			'since'           => 1747353600, // timestamp
		];
	}
}
