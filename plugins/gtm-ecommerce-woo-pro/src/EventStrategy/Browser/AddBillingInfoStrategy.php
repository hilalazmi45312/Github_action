<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\EventStrategy\EventStrategyTrait;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user sees a list of items/offerings
 */
class AddBillingInfoStrategy extends AbstractEventStrategy {
	use EventStrategyTrait;

	protected $eventName = EventType::ADD_BILLING_INFO;

	public function defineActions() {
		return [
			'woocommerce_before_checkout_form' => [ $this, 'beforeCheckoutForm' ],
		];
	}

	public $settings = [
		'on_checkout_submit' => [
			'label' => 'Trigger on Checkout Submit',
			'description' => 'Enable to make this event fire together only on checkout form submit.',
			'value' => false,
		]
	];

	public function beforeCheckoutForm() {
		$event = ( new Event(EventType::ADD_BILLING_INFO) )
			->setItems(array_values($this->getCartItems()));

		$stringifiedEvent = json_encode($event);

		if (true === $this->settings['on_checkout_submit']['value']) {
			$this->wcOutput->script(
				<<<EOD
var checkoutForm = jQuery( 'form.checkout' );
var billingFields = checkoutForm.find('.validate-required [id^="billing_"]').parents( '.validate-required' );
checkoutForm.on('submit', () => {
	if (billingFields.find('[id^="billing_"]').filter((i, el) => jQuery(el).val() === '').length === 0
	&& billingFields.filter('.woocommerce-invalid').length === 0) {
		dataLayer.push({$stringifiedEvent});
	}
});
EOD
			);
		} else {
			$this->wcOutput->script(
				<<<EOD
var checkoutForm = jQuery( 'form.checkout' );
var billingFields = checkoutForm.find('.validate-required [id^="billing_"]').parents( '.validate-required' );
billingFields.change(() => {
	setTimeout(() => {
		if (billingFields.find('[id^="billing_"]').filter((i, el) => jQuery(el).val() === '').length === 0
		&& billingFields.filter('.woocommerce-invalid').length === 0) {
			let event = {$stringifiedEvent};
			event.email = jQuery('#billing_email').val();
			event.phone_number = jQuery('#billing_phone').val();
			event.address = {};
			event.address.first_name = jQuery('#billing_first_name').val();
			event.address.last_name = jQuery('#billing_last_name').val();
			event.address.street = jQuery('#billing_address_1').val() + ' ' + jQuery('#billing_address_2').val();
			event.address.city = jQuery('#billing_city').val();
			event.address.region = jQuery('#billing_state').val();
			event.address.postal_code = jQuery('#billing_postcode').val();
			event.address.country = jQuery('#billing_country').val();
			dataLayer.push(event);
		}
	}, 100);
});
EOD
			);
		}
	}
}
