<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

abstract class AbstractChangeLanguageStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::CHANGE_LANGUAGE;

	abstract protected function getCurrentLanguage();

	public function defineActions() {
		return [
			'wp_footer' => [[$this, 'addScript'], 10],
		];
	}

	public function addScript() {
		$lang = $this->getCurrentLanguage();

		if (false === is_string($lang) || true === empty($lang)) {
			return;
		}

		$stringifiedEvent = json_encode(new Event(EventType::CHANGE_LANGUAGE));

		$this->wcOutput->script(
			<<<EOD
(function(dataLayer){
    const currentLanguage = '{$lang}';
    const previousLanguage = localStorage.getItem('tc_ext_multilingual_previous_language');

    if (null !== previousLanguage && previousLanguage !== currentLanguage) {
        let event = {$stringifiedEvent};

        dataLayer.push({
            ...event,
            previous: previousLanguage,
            current: currentLanguage
        });
    }

    localStorage.setItem('tc_ext_multilingual_previous_language', currentLanguage);
})(dataLayer);
EOD
		);
	}
}
