<?php

namespace GtmEcommerceWooPro\Lib\Service\ServerSide;

use GtmEcommerceWooPro\Lib\EventStrategy\Server\PurchaseStrategy;
use GtmEcommerceWooPro\Lib\Util\MpClientUtil;

/**
 * Allows background calls to GTM server container to ensure:
 * - actual WC requests never fail because of GTM connection issues
 * - there is zero impact on performance
 */
class QueueService {
	/**
	 * MpClientUtil
	 *
	 * @var MpClientUtil
	 */
	protected $mpClientUtil;
	protected $wpSettingsUtil;
	protected $snakeCaseNamespace;
	protected $wcTransformerUtil;

	public function __construct ( $snakeCaseNamespace, $wpSettingsUtil, $mpClientUtil, $wcTransformerUtil) {
		$this->snakeCaseNamespace = $snakeCaseNamespace;
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->mpClientUtil = $mpClientUtil;
		$this->wcTransformerUtil = $wcTransformerUtil;
	}

	public function initialize() {
		add_filter( 'cron_schedules', [$this, 'schedules']);

		$cronName = $this->snakeCaseNamespace . '_serverside_queue';

		// option to disable the cron
		if ($this->wpSettingsUtil->getOption('event_server_purchase_background') !== '1') {
			$timestamp = wp_next_scheduled( $cronName );
			wp_unschedule_event( $timestamp, $cronName );
			return;
		}

		add_action( $cronName, [$this, 'cronJob'] );
		if ( ! wp_next_scheduled( $cronName ) ) {
			wp_schedule_event( time(), 'minutely', $cronName );
		}
	}

	public function schedules( $schedules ) {
		$schedules['minutely'] = array(
			'interval' => 60,
			'display' => __('Once a minute')
		);
		return $schedules;
	}


	public function cronJob() {
		// check last cronJob timestamp
		// default to 10 minutes ago
		$lastRun = false; //get_transient( $this->snakeCaseNamespace . '_serverside_queue_last_run' );
		if (false === $lastRun) {
			$lastRun = time() - 600;
		}

		set_transient( $this->snakeCaseNamespace . '_serverside_queue_last_run', time() );


		$query = new \WC_Order_Query( array(
			'orderby' => 'date',
			'order' => 'DESC',
			'date_modified' => '>' . $lastRun,
			'status' => 'processing'
		) );

		$orders = $query->get_orders();

		foreach ($orders as $order) {
			// server event tracked
			if ('1' === $order->get_meta(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED)) {
				return false;
			}

			// no client_id
			if ('' === $order->get_meta(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_CLIENT_ID)) {
				return false;
			}

			$clientId = $order->get_meta(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_CLIENT_ID);

			$event = $this->wcTransformerUtil->getPurchaseFromOrder($order);
			$event->setExtraProperty('event_source_url', get_home_url());

			$event->setExtraProperty('transaction_id', $order->get_order_number());
			$event->setExtraProperty('ip_override', $order->get_customer_ip_address());
			$event->setExtraProperty('user_id', $order->get_customer_id());
			$userData = [
				'user_id'      => $order->get_customer_id(),
				'email'        => $order->get_billing_email(),
				'phone_number' => $order->get_billing_phone(),
				'address'      => [
					[
						'first_name'  => $order->get_billing_first_name(),
						'last_name'   => $order->get_billing_last_name(),
						'street'      => join( ' ', array( $order->get_billing_address_1(), $order->get_billing_address_2() ) ),
						'postal_code' => $order->get_billing_postcode(),
						'country'     => $order->get_billing_country(),
						'region'      => $order->get_billing_state(),
						'city'        => $order->get_billing_city(),
					]
				],

				'customer_id'         => $order->get_customer_id(),
				'billing_first_name'  => $order->get_billing_first_name(),
				'billing_last_name'   => $order->get_billing_last_name(),
				'billing_address'     => join( ' ', array( $order->get_billing_address_1(), $order->get_billing_address_2() ) ),
				'billing_postcode'    => $order->get_billing_postcode(),
				'billing_country'     => $order->get_billing_country(),
				'billing_state'       => $order->get_billing_state(),
				'billing_city'        => $order->get_billing_city(),
				'billing_email'       => $order->get_billing_email(),
				'billing_phone'       => $order->get_billing_phone(),
				'shipping_first_name' => $order->get_shipping_first_name(),
				'shipping_last_name'  => $order->get_shipping_last_name(),
				'shipping_company'    => $order->get_shipping_company(),
				'shipping_address'    => join( ' ', array( $order->get_shipping_address_1(), $order->get_shipping_address_2() ) ),
				'shipping_postcode'   => $order->get_shipping_postcode(),
				'shipping_country'    => $order->get_shipping_country(),
				'shipping_state'      => $order->get_shipping_state(),
				'shipping_city'       => $order->get_shipping_city(),
				'customer_ip_address' => $order->get_customer_ip_address()
			];

			$user = $order->get_user();
			if ( $user instanceof \WP_User ) {
				$userData['email']      = $user->user_email;
				$userData['first_name'] = $user->first_name;
				$userData['last_name']  = $user->last_name;
			}

			$event->setExtraProperty('user_data', $userData);

			$cookies = $order->get_meta('_gtm_ecommerce_woo_purchase_server_event_cookies');

			$result = $this->mpClientUtil->sendEvents([$event], $clientId, $cookies);

			if (true === $result) {
				$order->update_meta_data(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED, '1');
				$order->save();
			}
		}

	}
}
