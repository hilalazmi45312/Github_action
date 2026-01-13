<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\WooCommerceMultilingual\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class ChangeCurrencyStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::CHANGE_CURRENCY;

	public function defineActions() {
		return [
			'wp_footer' => [[$this, 'addScript'], 10],
		];
	}

	public function addScript() {
		$stringifiedEvent = json_encode(new Event(EventType::CHANGE_CURRENCY));

		$this->wcOutput->script(
			<<<EOD
(function(dataLayer){
    try {
        const currentCurrency = wcml_mc_settings.current_currency.code;
        const previousCurrency = localStorage.getItem('tc_ext_multilingual_previous_currency');

        if (null !== previousCurrency && previousCurrency !== currentCurrency) {
            let event = {$stringifiedEvent};

            dataLayer.push({
                ...event,
                previous: previousCurrency,
                current: currentCurrency
            });
        }

        localStorage.setItem('tc_ext_multilingual_previous_currency', currentCurrency);
    } catch (e) {

    }
})(dataLayer);
EOD
		);
	}
}
