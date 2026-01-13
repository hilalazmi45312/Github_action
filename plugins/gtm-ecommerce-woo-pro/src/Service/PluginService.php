<?php

namespace GtmEcommerceWooPro\Lib\Service;

/**
 * Logic to handle embedding Gtm Snippet
 */
class PluginService extends \GtmEcommerceWoo\Lib\Service\PluginService {
	protected $feedbackUrl = 'https://woocommerce.com/products/google-tag-manager-for-woocommerce-pro/#reviews';
	protected $feedbackDays = 14;
	protected $serviceNotice = false;

	public function initialize() {
		parent::initialize();

		$this->wcOutputUtil->scriptFile('gtm-ecommerce-woo-pro');

		if ('1' === $this->wpSettingsUtil->getOption('defer_events')) {
			$this->wcOutputUtil->localizedScript(
				'gtm-ecommerce-woo-pro-defer-events',
				'event_deferral_setings',
				[
					'event_name' => $this->wpSettingsUtil->getOption('defer_events_event_name'),
					'timeout' => $this->wpSettingsUtil->getOption('defer_events_timeout')
				]
			);

			$this->wcOutputUtil->scriptFile('gtm-ecommerce-woo-pro-defer-events');
		}

		add_action('rest_api_init', [$this, 'registerRestRoutes']);
	}

	public function registerRestRoutes() {
		register_rest_route(
			'gtm-ecommerce-woo/v1',
			'/verify-transaction',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handleTransactionVerification'],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function handleTransactionVerification(\WP_REST_Request $request) {
		$orderId = sanitize_text_field($request->get_param('transaction_id') ?? '');
		$customerEmailHash = sanitize_text_field($request->get_param('customer_email') ?? '');

		if (empty($orderId) || empty($customerEmailHash)) {
			return new \WP_REST_Response(['result' => false], 400);
		}

		$order = wc_get_order($orderId);

		if (!$order) {
			return new \WP_REST_Response(['result' => false]);
		}

		$orderEmail = $order->get_billing_email();

		if (empty($orderEmail)) {
			return new \WP_REST_Response(['result' => false]);
		}

		$hashedOrderEmail = hash('sha256', $orderEmail);

		if ($hashedOrderEmail !== $customerEmailHash) {
			return new \WP_REST_Response(['result' => false]);
		}

		$orderDate = $order->get_date_created();
		if (!$orderDate) {
			return new \WP_REST_Response(['result' => false]);
		}

		$thirtyMinutesAgo = new \WC_DateTime('-30 minutes');
		if ($orderDate < $thirtyMinutesAgo) {
			return new \WP_REST_Response(['result' => false]);
		}

		return new \WP_REST_Response(['result' => true]);
	}
}

