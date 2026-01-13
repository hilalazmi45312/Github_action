<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\EventStrategy\EventStrategyTrait;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user sees a list of items/offerings
 */
class AddPaymentInfoStrategy extends AbstractEventStrategy {
	use EventStrategyTrait;

	protected $eventName = EventType::ADD_PAYMENT_INFO;
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
		$event = ( new Event(EventType::ADD_PAYMENT_INFO) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		if (true === $this->settings['on_checkout_submit']['value']) {
			$this->wcOutput->script(
				<<<EOD
var checkoutForm = jQuery( 'form.checkout' );
checkoutForm.on('submit', () => {
	let paymentMethod = jQuery( '.woocommerce-checkout input[name="payment_method"]:checked' ).attr( 'id' );
	let event = {$stringifiedEvent};
	event.ecommerce.payment_type = paymentMethod;

	dataLayer.push(event);
	window.gtm_ecommerce_pro.fireEvent(event);
});
EOD
			);
		} else {
			$this->wcOutput->script(
				<<<EOD
jQuery( document.body ).on( 'payment_method_selected', () => {
	let paymentMethod = jQuery( '.woocommerce-checkout input[name="payment_method"]:checked' ).attr( 'id' );
	let event = {$stringifiedEvent};
	event.ecommerce.payment_type = paymentMethod;

	dataLayer.push(event);
	window.gtm_ecommerce_pro.fireEvent(event);
});
EOD
			);
		}

		$this->tracked = true;
	}

	public function wpFooter() {
		if (true === $this->tracked || false === is_checkout() || true === is_order_received_page()) {
			return;
		}

		$event = ( new Event(EventType::ADD_PAYMENT_INFO) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		$this->wcOutput->script(
			<<<EOD
document.addEventListener('DOMContentLoaded', () => {
    const isBlockCheckout = document.querySelector('.wp-block-woocommerce-checkout');

    if (isBlockCheckout) {
        const checkInitialMethod = () => {
            const paymentInput = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
            if (paymentInput) {
                pushPaymentToDataLayer(paymentInput.value);
                return true;
            }
            return false;
        };

        let attempts = 0;
        const maxAttempts = 5;
        const checkInterval = setInterval(() => {
            if (checkInitialMethod() || attempts >= maxAttempts) {
                clearInterval(checkInterval);
            }
            attempts++;
        }, 500);

        document.body.addEventListener('change', (event) => {
            const target = event.target;
            if (target.matches('input[name="radio-control-wc-payment-method-options"]')) {
                pushPaymentToDataLayer(target.value);
            }
        });
    }
});

function pushPaymentToDataLayer(paymentMethod) {
    if (!paymentMethod) {
    	return;
	}

    let event = {$stringifiedEvent};
    event.ecommerce.payment_type = paymentMethod;

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

		$event = ( new Event(EventType::ADD_PAYMENT_INFO) )
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

    let paymentMethod = jQuery( '.woocommerce-checkout input[name="payment_method"]:checked' ).attr( 'id' );

	if (undefined === paymentMethod) {
		const el = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');

		if (null === el) {
			return;
		}

		paymentMethod = el.value;
	}

    let event = {$stringifiedEvent};
    event.ecommerce.payment_type = paymentMethod;

    dataLayer.push(event);
    window.gtm_ecommerce_pro.fireEvent(event);
});
EOD
		);
	}
}
