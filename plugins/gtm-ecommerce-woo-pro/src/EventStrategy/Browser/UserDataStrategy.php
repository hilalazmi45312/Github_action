<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Service\GtmSnippetService;
use GtmEcommerceWooPro\Lib\Type\EventType;
use GtmEcommerceWooPro\Lib\Factory\UserDataFactory;

class UserDataStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::USER_DATA;

	/**
	 * @var UserDataFactory
	 */
	private $userDataFactory;

	public function __construct( $wcTransformerUtil, $wcOutputUtil, UserDataFactory $userDataFactory ) {
		parent::__construct( $wcTransformerUtil, $wcOutputUtil );
		$this->userDataFactory = $userDataFactory;
	}

	public function defineActions() {
		return [
			'wp_head' => [[$this, 'wpHead'], GtmSnippetService::PRIORITY_BEFORE_GTM ],
			'rest_api_init' => [[$this, 'restApiInit'], 10],
			'wp_footer' => [[$this, 'checkoutFormUserData'], 10],
		];
	}

	public function restApiInit() {
		register_rest_route(
			'gtm-ecommerce-woo/v1',
			'/get-user-data',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'endpointGetUserData' ],
				'permission_callback' => [ $this, 'permissionCallback' ],
			]
		);
	}

	public function permissionCallback() {
		return is_user_logged_in();
	}

	public function endpointGetUserData() {
		$user = wp_get_current_user();

		if ( 0 === $user->ID ) {
			return new \WP_REST_Response( [ 'error' => 'unauthorized' ], 401 );
		}

		$userData = $this->userDataFactory->create( $user );

		if ( empty( $userData['phone'] ) || empty( $userData['address']['street'] ) ) {
			$orders = wc_get_orders(
				[
					'customer_id' => $user->ID,
					'limit'       => 1,
					'orderby'     => 'date',
					'order'       => 'DESC',
				]
			);

			if ( ! empty( $orders ) ) {
				$lastOrder = $orders[0];
				$orderUserData = $this->userDataFactory->create( $lastOrder );

				if ( empty( $userData['phone'] ) && ! empty( $orderUserData['phone'] ) ) {
					$userData['phone'] = $orderUserData['phone'];
					if ( isset( $orderUserData['sha256_phone_number'] ) ) {
						$userData['sha256_phone_number'] = $orderUserData['sha256_phone_number'];
					}
				}
				if ( empty( $userData['address']['first_name'] ) ) {
					$userData['address']['first_name'] = $orderUserData['address']['first_name'];
				}
				if ( empty( $userData['address']['last_name'] ) ) {
					$userData['address']['last_name'] = $orderUserData['address']['last_name'];
				}
				if ( empty( $userData['address']['street'] ) ) {
					$userData['address']['street'] = $orderUserData['address']['street'];
				}
				if ( empty( $userData['address']['city'] ) ) {
					$userData['address']['city'] = $orderUserData['address']['city'];
				}
				if ( empty( $userData['address']['region'] ) ) {
					$userData['address']['region'] = $orderUserData['address']['region'];
				}
				if ( empty( $userData['address']['postal_code'] ) ) {
					$userData['address']['postal_code'] = $orderUserData['address']['postal_code'];
				}
				if ( empty( $userData['address']['country'] ) ) {
					$userData['address']['country'] = $orderUserData['address']['country'];
				}
			}
		}

		$userData = array_filter(
			$userData,
			function ( $value ) {
				if ( is_array( $value ) ) {
					return ! empty( array_filter( $value ) );
				}
				return ! is_null( $value ) && '' !== $value;
			}
		);

		if ( isset( $userData['address'] ) ) {
			$userData['address'] = array_filter(
				$userData['address'],
				function ( $value ) {
					return ! is_null( $value ) && '' !== $value;
				}
			);
		}

		return new \WP_REST_Response( $userData, 200 );
	}

	public function checkoutFormUserData() {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}

		$this->wcOutput->script(
			<<<EOD
(function($, dataLayer) {
	const inputs = ['#billing_email', '#billing_phone', '#email', '#shipping-phone', '#shipping-email', '#billing-phone', '#billing-email'];
	const \$inputs = $(inputs.join(','));

	const checkAndPush = () => {
		const email = $('#billing_email').val() || $('#email').val() || $('#billing-email').val() || $('#shipping-email').val();
		const phone = $('#billing_phone').val() || $('#shipping-phone').val() || $('#billing-phone').val();

		if (email && phone) {
			dataLayer.push({
				'event': 'user_data',
				'email': email,
				'phone_number': phone,
				'user_data': {
					'email': email,
					'phone': phone
				}
			});
		}
	};

	$(document).on('blur', inputs.join(','), checkAndPush);

})(jQuery, dataLayer);
EOD
		);
	}

	public function wpHead()
	{
		$this->orderUserData();
		$this->generalUserData();
	}

	private function generalUserData()
	{
		if (!is_user_logged_in()) {
			return;
		}

		echo <<<EOD
<script>
var dataLayer = dataLayer || [];
(function(dataLayer, sessionStorage){
	const STORAGE_KEY = 'gtm_ecommerce_woo_user_data';
	const storedData = sessionStorage.getItem(STORAGE_KEY);

	if (storedData) {
		try {
			const userData = JSON.parse(storedData);
			if (userData && !userData.error) {
				const event = {
					'event': 'user_data',
					'email': userData.email,
					'phone_number': userData.phone,
					'address': userData.address,
					'user_data': userData
				};
				dataLayer.push(event);
			}
		} catch (e) {
			sessionStorage.removeItem(STORAGE_KEY);
		}
	}
})(dataLayer, window.sessionStorage);
</script>
EOD;

		$getUserDataEndpointUrl = sprintf('%s%s', get_rest_url(), 'gtm-ecommerce-woo/v1/get-user-data');
		$nonce = wp_create_nonce('wp_rest');

		$this->wcOutput->script(
			<<<EOD
(function($, dataLayer, sessionStorage){
	const STORAGE_KEY = 'gtm_ecommerce_woo_user_data';
	const storedData = sessionStorage.getItem(STORAGE_KEY);

	if (storedData) {
		return;
	}

	$.ajax({
		type: 'POST',
		url: '{$getUserDataEndpointUrl}',
		beforeSend: function (xhr) {
			xhr.setRequestHeader('X-WP-Nonce', '{$nonce}');
		},
		success: function(response) {
			if (response && response.email) {
				sessionStorage.setItem(STORAGE_KEY, JSON.stringify(response));
				const event = {
					'event': 'user_data',
					'email': response.email,
					'phone_number': response.phone,
					'address': response.address,
					'user_data': response
				};
				dataLayer.push(event);
			}
		}
	});
})(jQuery, dataLayer, window.sessionStorage);
EOD
		);
	}

	private function orderUserData()
	{
		if (false === is_wc_endpoint_url('order-received')) {
			return;
		}

		if (false === isset($_GET['key'])) {
			return;
		}

		$orderKey = sanitize_text_field($_GET['key']);
		$orderId = wc_get_order_id_by_order_key($orderKey);

		if (null === $orderId) {
			return;
		}

		/** WC_Order @var WC_Order|false $order*/
		$order = wc_get_order($orderId);

		if (false === $order) {
			return;
		}

		$userData = $this->userDataFactory->create( $order );

		$event = ( new Event(EventType::USER_DATA) )
			->setExtraProperty('email', $userData['email'])
			->setExtraProperty('sha256_email_address', $userData['sha256_email_address'])
			->setExtraProperty('phone_number', $userData['phone'])
			->setExtraProperty('address', $userData['address'])
			->setExtraProperty('user_data', array_filter($userData, function($value) {
				return !is_null($value) && '' !== $value;
			}));
		;

		$serializedEvent = json_encode($event);

		echo sprintf(
			"<script>var dataLayer = dataLayer || [];dataLayer.push(%s);</script>\n",
			filter_var($serializedEvent, FILTER_FLAG_STRIP_BACKTICK)
		);
	}

}
