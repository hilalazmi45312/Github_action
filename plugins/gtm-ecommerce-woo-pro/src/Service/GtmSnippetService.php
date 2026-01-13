<?php

namespace GtmEcommerceWooPro\Lib\Service;

/**
 * Logic to handle embedding Gtm Snippet
 */
class GtmSnippetService extends \GtmEcommerceWoo\Lib\Service\GtmSnippetService {

	public function initialize() {
		parent::initialize();
		$this->initializeUserId();

		if ('1' === $this->wpSettingsUtil->getOption('consent_mode_integration_complianz_enabled')) {
			add_action( 'wp_head', [$this, 'consentModeIntegrationComplianz'], self::PRIORITY_BEFORE_GTM );
		}
	}

	public function consentModeIntegrationComplianz() {
		$snippet = <<<END
<script>
const debounce = (callback, wait) => {
  let timeoutId = null;
  return (...args) => {
    window.clearTimeout(timeoutId);
    timeoutId = window.setTimeout(() => {
      callback(...args);
    }, wait);
  };
}
const handleCmplzCategories = debounce(() => {
  const cats = cmplz_accepted_categories();
  const consentState = {
    'ad_storage': cats.includes('marketing') ? 'granted' : 'denied',
    'ad_user_data': cats.includes('marketing') ? 'granted' : 'denied',
    'ad_personalization': cats.includes('marketing') ? 'granted' : 'denied',
    'analytics_storage': cats.includes('statistics') ? 'granted' : 'denied'
  };
  gtag('consent', 'update', consentState);
  dataLayer.push({
    event: 'consent_update',
    consent_state: consentState
  });
}, 1);

document.addEventListener('cmplz_cookie_banner_data', handleCmplzCategories);
document.addEventListener("cmplz_enable_category", handleCmplzCategories);
</script>
END;

		echo filter_var($snippet, FILTER_FLAG_STRIP_BACKTICK) . "\n";
	}

	private function initializeUserId() {
		$isPluginDisabled = (bool) $this->wpSettingsUtil->getOption('disabled');

		if (true === $isPluginDisabled) {
			return;
		}

		$isUserTrackingEnabled = (bool) $this->wpSettingsUtil->getOption('track_user_id');

		if (false === $isUserTrackingEnabled) {
			return;
		}

		add_action( 'wp_head', [$this, 'pushUserId'], self::PRIORITY_BEFORE_GTM );
	}

	public function pushUserId() {
		$userId = get_current_user_id();

		if (0 === $userId) {
			return;
		}

		echo sprintf(
			"<script>var dataLayer = dataLayer || [];dataLayer.push({\"event\":\"user_id\",\"user_id\":\"%s\"});</script>\n",
			(int) $userId
		);
	}
}
