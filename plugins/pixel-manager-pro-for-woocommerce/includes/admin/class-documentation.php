<?php

namespace SweetCode\Pixel_Manager\Admin;

defined('ABSPATH') || exit; // Exit if accessed directly

class Documentation {

	public static function get_link( $key = 'default', $sweetcode_override = false ) {

		$url = self::get_documentation_host() . self::get_documentation_path($key);

		return self::add_utm_parameters($url, $key);
	}

	private static function add_utm_parameters( $url, $key ) {

		$url_parts = explode('#', $url);

		$url = $url_parts[0] . '?utm_source=woocommerce-plugin&utm_medium=documentation-link&utm_campaign=' . str_replace('_', '-', $key);

		if (count($url_parts) === 2) {
			$url .= '#' . $url_parts[1];
		}

		return $url;
	}

	private static function get_documentation_host() {
		return 'https://sweetcode.com';
	}

	private static function get_documentation_path( $key = 'default' ) {

		$documentation_links = [
			'default'                                           => '/docs/wpm/',
			'script_blockers'                                   => '/docs/wpm/setup/script-blockers/',
			'google_analytics_universal_property'               => '/docs/wpm/plugin-configuration/google-analytics',
			'google_analytics_4_id'                             => '/docs/wpm/plugin-configuration/google-analytics#connect-an-existing-google-analytics-4-property',
			'google_ads_conversion_id'                          => '/docs/wpm/plugin-configuration/google-ads#configure-the-plugin',
			'google_ads_conversion_label'                       => '/docs/wpm/plugin-configuration/google-ads#configure-the-plugin',
			'google_optimize_container_id'                      => '/docs/wpm/plugin-configuration/google-optimize',
			'google_optimize_anti_flicker'                      => '/docs/wpm/plugin-configuration/google-optimize#anti-flicker-snippet',
			'google_optimize_anti_flicker_timeout'              => '/docs/wpm/plugin-configuration/google-optimize#adjusting-the-anti-flicker-snippet-timeout',
			'facebook_pixel_id'                                 => '/docs/wpm/plugin-configuration/meta#find-the-pixel-id',
			'bing_uet_tag_id'                                   => '/docs/wpm/plugin-configuration/microsoft-advertising#setting-up-the-uet-tag',
			'twitter_pixel_id'                                  => '/docs/wpm/plugin-configuration/twitter#pixel-id',
			'twitter_event_ids'                                 => '/docs/wpm/plugin-configuration/twitter#event-setup',
			'pinterest_pixel_id'                                => '/docs/wpm/plugin-configuration/pinterest',
			'snapchat_pixel_id'                                 => '/docs/wpm/plugin-configuration/snapchat',
			'snapchat_capi_token'                               => '/docs/wpm/plugin-configuration/snapchat#conversions-api',
			'snapchat_advanced_matching'                        => '/docs/wpm/plugin-configuration/snapchat#advanced-matching',
			'tiktok_pixel_id'                                   => '/docs/wpm/plugin-configuration/tiktok',
			'tiktok_advanced_matching'                          => '/docs/wpm/plugin-configuration/tiktok#advanced-matching',
			'tiktok_eapi_token'                                 => '/docs/wpm/plugin-configuration/tiktok#access-token',
			'vwo_account_id'                                    => '/docs/wpm/plugin-configuration/vwo',
			'hotjar_site_id'                                    => '/docs/wpm/plugin-configuration/hotjar#hotjar-site-id',
			'google_gtag_deactivation'                          => '/docs/wpm/faq/#google-tag-assistant-reports-multiple-installations-of-global-site-tag-gtagjs-detected-what-shall-i-do',
			'google_consent_mode'                               => '/docs/wpm/consent-management/google#google-consent-mode',
			'restricted_consent_regions'                        => '/docs/wpm/consent-management/overview#explicit-consent-regions',
			'google_analytics_eec'                              => '/docs/wpm/plugin-configuration/google-analytics#enhanced-e-commerce-funnel-setup',
			'google_analytics_4_api_secret'                     => '/docs/wpm/plugin-configuration/google-analytics#ga4-api-secret',
			'google_enhanced_conversions'                       => '/docs/wpm/plugin-configuration/google-ads#enhanced-conversions',
			'google_ads_phone_conversion_number'                => '/docs/wpm/plugin-configuration/google-ads#phone-conversion-number',
			'google_ads_phone_conversion_label'                 => '/docs/wpm/plugin-configuration/google-ads#phone-conversion-number',
			'explicit_consent_mode'                             => '/docs/wpm/consent-management/overview#explicit-consent-mode',
			'facebook_capi_token'                               => '/docs/wpm/plugin-configuration/meta/#meta-facebook-conversion-api-capi',
			'facebook_advanced_matching'                        => '/docs/wpm/plugin-configuration/meta#meta-facebook-advanced-matching',
			'facebook_microdata'                                => '/docs/wpm/plugin-configuration/meta#microdata-tags-for-catalogues',
			'maximum_compatibility_mode'                        => '/docs/wpm/plugin-configuration/general-settings/#maximum-compatibility-mode',
			'dynamic_remarketing'                               => '/docs/wpm/plugin-configuration/shop-settings#dynamic-remarketing',
			'variations_output'                                 => '/docs/wpm/plugin-configuration/shop-settings#dynamic-remarketing',
			'aw_merchant_id'                                    => '/docs/wpm/plugin-configuration/google-ads/#conversion-cart-data',
			'custom_thank_you'                                  => '/docs/wpm/troubleshooting/#wc-custom-thank-you',
			'the_dismiss_button_doesnt_work_why'                => '/docs/wpm/faq/#the-dismiss-button-doesnt-work-why',
			'wp-rocket-javascript-concatenation'                => '/docs/wpm/troubleshooting',
			'litespeed-cache-inline-javascript-after-dom-ready' => '/docs/wpm/troubleshooting',
			'payment-gateways'                                  => '/docs/wpm/setup/requirements#payment-gateways',
			'test_order'                                        => '/docs/wpm/testing#test-order',
			'payment_gateway_tracking_accuracy'                 => '/docs/wpm/diagnostics/#payment-gateway-tracking-accuracy-report',
			'acr'                                               => '/docs/wpm/features/acr',
			'order_list_info'                                   => '/docs/wpm/diagnostics#order-list-info',
			'marketing_value_logic'                             => '/docs/wpm/plugin-configuration/shop-settings#marketing-value-logic',
			'marketing_value_subtotal'                          => '/docs/wpm/plugin-configuration/shop-settings#order-subtotal-default',
			'marketing_value_total'                             => '/docs/wpm/plugin-configuration/shop-settings#order-total',
			'marketing_value_profit_margin'                     => '/docs/wpm/plugin-configuration/shop-settings#profit-margin',
			'scroll_tracker_threshold'                          => '/docs/wpm/plugin-configuration/general-settings/#scroll-tracker',
			'google_ads_conversion_adjustments'                 => '/docs/wpm/plugin-configuration/google-ads#conversion-adjustments',
			'ga4_data_api'                                      => '/docs/wpm/plugin-configuration/google-analytics#ga4-data-api',
			'ga4_data_api_property_id'                          => '/docs/wpm/plugin-configuration/google-analytics#ga4-property-id',
			'ga4_data_api_credentials'                          => '/docs/wpm/plugin-configuration/google-analytics#ga4-data-api-credentials',
			'duplication_prevention'                            => '/docs/wpm/shop#order-duplication-prevention',
			'license_expired_warning'                           => '/docs/wpm/license-management#expired-license-warning',
			'subscription_value_multiplier'                     => '/docs/wpm/plugin-configuration/shop-settings#subscription-value-multiplier',
			'lazy_load_pmw'                                     => '/docs/wpm/plugin-configuration/general-settings#lazy-load-the-pixel-manager',
			'opportunity_google_enhanced_conversions'           => '/docs/wpm/opportunities#google-ads-enhanced-conversions',
			'opportunity_google_ads_conversion_adjustments'     => '/docs/wpm/opportunities#google-ads-conversion-adjustments',
			'ga4_page_load_time_tracking'                       => '/docs/wpm/plugin-configuration/google-analytics#page-load-time-tracking',
			'reddit_advertiser_id'                              => '/docs/wpm/plugin-configuration/reddit#setup-instruction',
			'reddit_advanced_matching'                          => '/docs/wpm/plugin-configuration/reddit#advanced-matching',
			'pinterest_ad_account_id'                           => '/docs/wpm/plugin-configuration/pinterest#ad-account-id',
			'pinterest_apic_token'                              => '/docs/wpm/plugin-configuration/pinterest#api-for-conversions-token',
			'pinterest_enhanced_match'                          => '/docs/wpm/plugin-configuration/pinterest#enhanced-match',
			'pinterest_advanced_matching'                       => '/docs/wpm/plugin-configuration/pinterest#advanced-matching',
			'outbrain_advertiser_id'                            => '/docs/wpm/plugin-configuration/outbrain',
			'taboola_account_id'                                => '/docs/wpm/plugin-configuration/taboola',
			'adroll_advertiser_id'                              => '/docs/wpm/plugin-configuration/adroll#advertiser-id-and-pixel-id',
			'adroll_pixel_id'                                   => '/docs/wpm/plugin-configuration/adroll#advertiser-id-and-pixel-id',
			'linkedin_partner_id'                               => '/docs/wpm/plugin-configuration/linkedin#partner-id',
			'google_tcf_support'                                => '/docs/wpm/consent-management/google#google-tcf-support',
			'logger_activation'                                 => '/docs/wpm/developers/logs#logger-activation',
			'log_level'                                         => '/docs/wpm/developers/logs#log-levels',
			'log_http_requests'                                 => '/docs/wpm/developers/logs#log-http-requests',
			'log_files'                                         => '/docs/wpm/developers/logs#accessing-log-files',
			'ltv_order_calculation'                             => '/docs/wpm/plugin-configuration/shop-settings#active-lifetime-value-calculation',
			'ltv_recalculation'                                 => '/docs/wpm/plugin-configuration/shop-settings#lifetime-value-recalculation',
			'order_modal_ltv'                                   => '/docs/wpm/shop#lifetime-value',
			'facebook_microdata_deprecation'                    => '/blog/facebook-microdata-for-catalog-deprecation-notice',
			'order_extra_details'                               => '/docs/wpm/plugin-configuration/shop-settings#extra-order-data-output',
			'microsoft_ads_consent_mode'                        => '/docs/wpm/consent-management/microsoft#microsoft-ads-consent-mode',
			'facebook_domain_verification_id'                   => '/docs/wpm/plugin-configuration/meta#domain-verification',
			'google_tag_gateway_measurement_path'               => '/docs/wpm/plugin-configuration/google#google-tag-gateway-for-advertisers',
			'google_tag_id'                                     => '/docs/wpm/plugin-configuration/google#google-tag-gateway-for-advertisers',
			'pageview_events_s2s'                               => '/docs/wpm/plugin-configuration/general-settings#track-pageview-events-server-to-server',
			'reddit_capi_token'                                 => '/docs/wpm/plugin-configuration/reddit#conversions-api-capi',
			'reddit_capi_test_event_code'                       => '/docs/wpm/plugin-configuration/reddit#testing',
			'load_deprecated_functions'                         => '/docs/wpm/plugin-configuration/general-settings#load-deprecated-functions',
			'bing_enhanced_conversions'                         => '/docs/wpm/plugin-configuration/microsoft-advertising#enhanced-conversions',
		];

		if (array_key_exists($key, $documentation_links)) {
			return $documentation_links[$key];
		} else {
			return $documentation_links['default'];
		}
	}
}
