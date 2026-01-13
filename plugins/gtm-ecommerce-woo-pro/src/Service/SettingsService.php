<?php

namespace GtmEcommerceWooPro\Lib\Service;

use GtmEcommerceWoo\Lib\Service\SettingsService as FreeSettingsService;

/**
 * Logic related to working with settings and options
 */
class SettingsService extends FreeSettingsService {

	const SETTING_NAME_EVENT_BROWSER_PURCHASE_ONLY_ON_THANK_YOU_PAGE = 'event_browser_purchase_only_on_thank_you_page';

	protected $uuidPrefix = 'gtm-ecommerce-woo-pro';

	protected $allowServerTracking = true;
	protected $isPro = true;

	protected $filter = 'advanced';

	protected $eventsConfig = [
		'abandon_cart' => [
			'default_disabled' => true,
			'description' => 'Fires on cart summary page after tab/browser close.',
		],
		'abandon_checkout' => [
			'default_disabled' => true,
			'description' => 'Fires on checkout form page after tab/browser close.',
		]
	];

	public function settingsInit() {
		parent::settingsInit();

		$this->wpSettingsUtil->addSettingsSection(
			'browser_events_settings',
			'Web Events Settings',
			'',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsField(
			self::SETTING_NAME_EVENT_BROWSER_PURCHASE_ONLY_ON_THANK_YOU_PAGE,
			'Track purchase event only on thank you page?',
			[$this, 'checkboxField'],
			'browser_events_settings',
			'If unchecked, purchase will be tracked before payment gateway redirection, just after order is created in WordPress database. Note that <strong>checking this option can reduce the number of tracked purchase events</strong>, as tracking requires the customer to return to the store after payment, which is not always the case.'
		);

		/*$this->wpSettingsUtil->addSettingsField(
			'defer_events',
			'Defer events?',
			[$this, 'checkboxField'],
			'event_inspector',
			'[ADVANCED] defer events pushed to data layer until "DOM ready" event. Useful when using asynchronous Consent Management Platform to ensure consent state is provided before firing any events.'
		);*/

		$this->wpSettingsUtil->addSettingsSection(
			'gtm_server_container_jsons',
			'GTM Server-Side Presets',
			'Once your server container is ready, configure it with selected presets. Make sure you configured GTM Web container with server-side preset. You can use any number of the presets, see plugin <a href="https://docs.tagconcierge.com/" target="_blank">Documentation</a> for details):<br /><br />
                <div id="gtm-ecommerce-woo-server-presets-loader" style="text-align: center;"><span class="spinner is-active" style="float: none;"></span></div><div class="metabox-holder"><div id="gtm-ecommerce-woo-server-presets-grid" class="postbox-container" style="float: none;"><div id="gtm-ecommerce-woo-server-preset-tmpl" style="display: none;"><div style="display: inline-block;
    margin-left: 4%; width: 45%" class="postbox"><h3 class="name">Google Analytics 4</h3><div class="inside"><p class="description">Description</p><p><b>Supported events:</b> <span class="events-count">2</span> <span class="events-list dashicons dashicons-info-outline" style="cursor: pointer;"></span></p><p><a class="download button button-primary" href="#">Download</a></p><p>Version: <span class="version">N/A</span></p></div></div></div></div></div><br /><div id="gtm-ecommerce-woo-presets-upgrade" style="text-align: center; display: none;"><a class="button button-primary" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a></div>',
			'gtm_server_presets'
		);

		/*$this->wpSettingsUtil->addSettingsSection(
			'gtm_server_settings',
			'GTM Server-Side Settings',
			'Control how your server-to-serve integration behaves.',
			'gtm_server'
		);

		$this->wpSettingsUtil->addSettingsField(
			'cookies_storage',
			'Cookies Storage',
			[$this, 'checkboxField'],
			'gtm_server_settings',
			'In order to connect web events with server events identifiers from cookies can be stored on the server and added to server events.',
			['disabled' => false, 'title' => 'Checking will start storing cookies']
		);*/

		$this->wpSettingsUtil->addSettingsField(
			'event_server_purchase_background',
			'purchase (background)',
			[$this, 'checkboxField'],
			'events_server',
			'Improves tracking quality sending more data about purchase and user. Guarantees no impact on performance and stability of the transaction flow. This is recommended setting.',
			['disabled' => false, 'title' => 'Alternative purchase tracking']
		);

		$this->wpSettingsUtil->addSettingsField(
			'event_server_refund',
			'refund (coming soon)',
			[$this, 'checkboxField'],
			'events_server',
			'Track refunds via server-side',
			['disabled' => true, 'title' => 'coming soon']
		);
	}

	public function enqueueScripts( $hook) {
		parent::enqueueScripts($hook);

		if ( 'settings_page_gtm-ecommerce-woo' != $hook ) {
			return;
		}

		wp_enqueue_script( 'gtm-ecommerce-woo-admin-server', plugin_dir_url( __DIR__ . '/../../../') . 'assets/admin-server.js', [], $this->pluginVersion );
	}
}
