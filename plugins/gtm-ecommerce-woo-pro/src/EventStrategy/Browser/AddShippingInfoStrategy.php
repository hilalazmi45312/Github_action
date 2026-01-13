<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\EventStrategy\EventStrategyTrait;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user sees a list of items/offerings
 */
class AddShippingInfoStrategy extends AbstractEventStrategy {
	use EventStrategyTrait;

	protected $eventName = EventType::ADD_SHIPPING_INFO;
	protected $tracked = false;

	public function defineActions() {
		return [
			'woocommerce_before_checkout_form' => [ $this, 'beforeCheckoutForm' ],
			'wp_head' => [$this, 'wpHead'],
			'wp_footer' => [ $this, 'wpFooter' ]
		];
	}

	public $settings = [
		'on_checkout_submit' => [
			'label' => 'Trigger on Checkout Submit',
			'description' => 'Enable to make this event fire together only on checkout form submit.',
			'value' => false,
		],
		'fallback_event' => [
			'label' => 'Fallback trigger on checkout submit',
			'description' => 'Enable to fire fallback event just after checkout submit.',
			'value' => false,
		]
	];

	public function beforeCheckoutForm() {
		$event = ( new Event(EventType::ADD_SHIPPING_INFO) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		if (true === $this->settings['on_checkout_submit']['value']) {
			$this->wcOutput->script(
				<<<EOD
var checkoutForm = jQuery( 'form.checkout' );
checkoutForm.on('submit', () => {
	let event = {$stringifiedEvent};
	event.ecommerce.shipping_tier = jQuery('input[name^="shipping_method"][type="radio"]:checked').val();

	dataLayer.push(event);
	window.gtm_ecommerce_pro.fireEvent(event);
} );
EOD
			);
		} else {
			$this->wcOutput->script(
				<<<EOD
var checkoutForm = jQuery( 'form.checkout' );
checkoutForm.on( 'change', 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]',  function() {
	let event = {$stringifiedEvent};
	event.ecommerce.shipping_tier = jQuery(this).val();

	dataLayer.push(event);
	window.gtm_ecommerce_pro.fireEvent(event);
} );
EOD
			);
		}

		$this->tracked = true;
	}

	public function wpFooter() {
		if (true === $this->tracked || false === is_checkout() || true === is_order_received_page()) {
			return;
		}

		$event = ( new Event(EventType::ADD_SHIPPING_INFO) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		$this->wcOutput->script(
			<<<EOD
document.addEventListener('DOMContentLoaded', () => {
    const findSelectedShipping = () => {
        const shippingContainer = document.querySelector('.wc-block-components-shipping-rates-control__package');
        if (!shippingContainer) {
        	return null;
		}

        const selectedRadio = shippingContainer.querySelector('.wc-block-components-radio-control__input:checked');
        if (!selectedRadio) {
        	return null;
		}

        return selectedRadio.value.split(':')[0];
    };

    const checkAndPushShipping = () => {
        const shipping = findSelectedShipping();
        if (shipping) {
            pushShippingToDataLayer(shipping);
            return true;
        }
        return false;
    };

    let attempts = 0;
    const maxAttempts = 5;
    const checkInterval = setInterval(() => {
        if (checkAndPushShipping() || attempts >= maxAttempts) {
            clearInterval(checkInterval);
        }
        attempts++;
    }, 500);

    document.body.addEventListener('change', (event) => {
        const target = event.target;
        if (target.classList.contains('wc-block-components-radio-control__input') &&
            target.closest('.wc-block-components-shipping-rates-control__package')) {
            const shipping = findSelectedShipping();
            if (shipping) {
                pushShippingToDataLayer(shipping);
            }
        }
    });
});

function pushShippingToDataLayer(methodName) {
    if (!methodName) {
    	return;
	}

    let event = {$stringifiedEvent};
    event.ecommerce.shipping_tier = methodName;

	dataLayer.push(event);
	window.gtm_ecommerce_pro.fireEvent(event);
}
EOD
		);
	}

	public function wpHead()
	{
		if (false === $this->settings['fallback_event']['value']) {
			return;
		}

		$event = ( new Event(EventType::ADD_SHIPPING_INFO) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		$this->wcOutput->script(
			<<<EOD
jQuery(document).on('click', function(e) {
    const button = jQuery(e.target).closest('button');
    if (!button.length) {
    	return;
    }

    if (true === window.gtm_ecommerce_pro.hasEventFired('{$this->eventName}')) {
    	return;
    }

    let shippingMethod = jQuery('input[name^="shipping_method"][type="radio"]:checked').val();

	if (undefined === shippingMethod) {
		const shippingContainer = document.querySelector('.wc-block-components-shipping-rates-control__package');
        if (!shippingContainer) {
        	return null;
		}

        const selectedRadio = shippingContainer.querySelector('.wc-block-components-radio-control__input:checked');
        if (!selectedRadio) {
        	return null;
		}

        shippingMethod = selectedRadio.value.split(':')[0];
	}

    let event = {$stringifiedEvent};
    event.ecommerce.payment_type = shippingMethod;

    dataLayer.push(event);
    window.gtm_ecommerce_pro.fireEvent(event);
});
EOD
		);
	}
}
