<?php


namespace GtmEcommerceWooPro\Lib\Service;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WC_Order;
use WC_Product;

/**
 * Generic tools service, consider splitting into separate components
 */
class ToolsService {

	protected $wpSettingsUtil;

	public function __construct( $wpSettingsUtil ) {
		$this->wpSettingsUtil = $wpSettingsUtil;
	}


	public function initialize() {
		$this->googleCartData();

		if ('1' === $this->wpSettingsUtil->getOption('server_side_endpoint_cogs_enabled')) {
			add_action('rest_api_init', [$this, 'registerRoutes']);
		}
	}

	public function googleCartData() {

		add_filter('gtm_ecommerce_woo_item', function( $item, $product) {
			if ($this->wpSettingsUtil->getOption('google_business_vertical')) {
				$item->setExtraProperty(
					'google_business_vertical',
					$this->wpSettingsUtil->getOption('google_business_vertical')
				);
			}

			if ($this->wpSettingsUtil->getOption('dynamic_remarketing_google_id_pattern')) {
				$item->setItemId($this->getProductId($product, $this->wpSettingsUtil->getOption('dynamic_remarketing_google_id_pattern')));
				$item->setExtraProperty('id', $this->getProductId($product, $this->wpSettingsUtil->getOption('dynamic_remarketing_google_id_pattern')));
				$item->setExtraProperty('ecomm_prodid', $this->getProductId($product, $this->wpSettingsUtil->getOption('dynamic_remarketing_google_id_pattern')));
			}

			return $item;
		}, 10, 2);

		add_filter('gtm_ecommerce_woo_event', function( $event) {

			if ('purchase' === $event->name) {

				if ($this->wpSettingsUtil->getOption('cart_data_google_merchant_id')) {
					$event->setExtraEcomProperty(
						'aw_merchant_id',
						$this->wpSettingsUtil->getOption('cart_data_google_merchant_id')
					);
				}

				if ($this->wpSettingsUtil->getOption('cart_data_google_feed_country')) {
					$event->setExtraEcomProperty(
						'aw_feed_country',
						$this->wpSettingsUtil->getOption('cart_data_google_feed_country')
					);
				}

				if ($this->wpSettingsUtil->getOption('cart_data_google_feed_lanuage')) {
					$event->setExtraEcomProperty(
						'aw_feed_lanuage',
						$this->wpSettingsUtil->getOption('cart_data_google_feed_lanuage')
					);
				}
			}

			return $event;
		});
	}

	protected function getProductId( $product, $pattern ) {
		$id = $pattern;
		if (strstr($id, '{{id}}')) {
			$id = str_replace('{{id}}', $product->get_id(), $id);
		}

		if (strstr($id, '{{sku}}')) {
			$id = str_replace('{{sku}}', $product->get_sku(), $id);
		}

		return $id;
	}

	public function registerRoutes() {
		register_rest_route('gtm-ecommerce-woo/v1', '/cogs/calculate', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [$this, 'handleCalculation'],
			'permission_callback' => [$this, 'checkPermission'],
			'args' => [
				'value' => [
					'required' => true,
					'type' => 'number',
				],
				'transaction_id' => [
					'required' => true,
					'type' => 'string',
				],
				'items' => [
					'required' => true,
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'required' => ['item_id', 'quantity', 'price'],
						'properties' => [
							'item_id' => [
								'type' => 'string',
							],
							'quantity' => [
								'type' => 'integer',
								'minimum' => 1,
							],
							'price' => [
								'type' => 'number',
								'minimum' => 0,
							],
						],
					],
				],
			],
		]);
	}

	/**
	 * Permission check for the API endpoint
	 */
	public function checkPermission( WP_REST_Request $request) {
		// Check server-side container URL is configured
		$serverContainerUrl = $this->wpSettingsUtil->getOption('gtm_server_container_url');
		if (empty($serverContainerUrl)) {
			return false;
		}

		// Parse domain from the container URL
		$containerDomain = parse_url($serverContainerUrl, PHP_URL_HOST);
		if (!$containerDomain) {
			return false;
		}

		// Get the request origin
		$origin = get_http_origin();
		if (!$origin) {
			return false;
		}

		// Check if request is from the configured container domain
		$originDomain = parse_url($origin, PHP_URL_HOST);
		return $originDomain === $containerDomain;
	}

	/**
	 * Handle the COGS calculation request
	 */
	public function handleCalculation( WP_REST_Request $request) {
		$params = $request->get_params();

		// Initialize response data
		$response = [
			'success' => true,
			'transaction_id' => $params['transaction_id'],
			'items' => $params['items'],
			'value' => $params['value']
		];

		$order = wc_get_order( $params['transaction_id'] );

		if ( ! $order ) {
			return;
		}

		// Process each item
		foreach ( $order->get_items() as $item_id => $item ) {
			$quantity   = (float) $item['qty'];
			$itemCost = 0;
			$product = null;
			if ( $item instanceof \WC_Order_Item && $item->get_meta( '_wc_cog_item_cost' ) ) {

				$itemCost = (float) $item->get_meta( '_wc_cog_item_cost' );
				$product = wc_get_product($item->get_product_id());

			} elseif ( $item_id && ! empty( $item ) && class_exists('\WC_COG_Product') ) {

				$productId = ( ! empty( $item['variation_id'] ) ) ? $item['variation_id'] : $item['product_id'];
				$product = wc_get_product($productId);
				$itemCost  = (float) \WC_COG_Product::get_cost( $productId );
			}

			if (null === $product) {
				continue;
			}

			$sku = $product->get_sku();

			foreach ( $response['items'] as &$resItem ) {
				if ($resItem['item_id'] === $sku) {
					$resItem['price'] -= $itemCost;
				}
			}

			$response['value'] -= $itemCost * $quantity;
		}

		return rest_ensure_response($response);
	}
}
