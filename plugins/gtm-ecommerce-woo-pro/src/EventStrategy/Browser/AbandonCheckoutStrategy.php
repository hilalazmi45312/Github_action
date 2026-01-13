<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\EventStrategy\EventStrategyTrait;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AbandonCheckoutStrategy extends AbstractEventStrategy {
	use EventStrategyTrait;

	protected $eventName = EventType::ABANDON_CHECKOUT;

	public function defineActions() {
		return [
			'woocommerce_before_checkout_form' => [ $this, 'beforeCheckoutForm' ],
		];
	}

	public function beforeCheckoutForm() {
		$event = ( new Event(EventType::ABANDON_CHECKOUT) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		$this->wcOutput->script(
			<<<EOD

(function($) {
	let lastRefresh = (new Date()).getTime(), validNavigation = false;

	const refreshKeys = [82, 116];
	const isRefresh = () => {
		return ((new Date()).getTime() - lastRefresh) < 500;
	};
	const isValidNavigation = () => {
		return validNavigation;
	};
	const validNavigationCallback = () => { validNavigation = true; };

    $(document).bind('keydown', (e) => {
		if (-1 < refreshKeys.indexOf(e.which)) {
			lastRefresh = (new Date()).getTime();
		}
	});
	$('a').bind('click', validNavigationCallback);
	$('form').bind('submit', validNavigationCallback);
	$('input[type=submit]').bind('click', validNavigationCallback);

	$(window).bind('beforeunload', function(event) {
        if (isRefresh() || isValidNavigation()) {
            return;
        }

		dataLayer.push({$stringifiedEvent});
    });
}(jQuery));
EOD
		);
	}
}
