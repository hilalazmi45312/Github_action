<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWoo\Lib\Service\GtmSnippetService;
use GtmEcommerceWooPro\Lib\Type\EventType;

abstract class AbstractLanguageStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::LANGUAGE;

	abstract protected function getCurrentLanguage();

	public function defineActions() {
		return [
			'wp_head' => [[$this, 'wpHead'], GtmSnippetService::PRIORITY_BEFORE_GTM],
		];
	}

	public function wpHead() {
		$lang = $this->getCurrentLanguage();

		if (false === is_string($lang) || true === empty($lang)) {
			return;
		}

		$event = ( new Event(EventType::LANGUAGE) )
			->setExtraProperty('language', strtolower($lang));

		$serializedEvent = json_encode($event);

		echo sprintf(
			"<script>var dataLayer = dataLayer || [];dataLayer.push(%s);</script>\n",
			filter_var($serializedEvent, FILTER_FLAG_STRIP_BACKTICK)
		);
	}
}
