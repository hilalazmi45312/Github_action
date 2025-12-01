<?php

namespace Ademti\WoocommerceProductFeeds\Features;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Exception;

class RestApi {

	// Dependencies.
	private Configuration $configuration;

	/**
	 * @param Configuration $configuration
	 */
	public function __construct( Configuration $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * Actually run the feature.
	 *
	 * @return void
	 */
	public function initialise(): void {
		add_filter( 'woocommerce_rest_product_schema', [ $this, 'rest_api_product_schema' ], 10 );
		add_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'rest_api_output_v2' ], 10, 3 );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', [ $this, 'rest_api_output_v2' ], 10, 3 );
		add_filter( 'woocommerce_rest_insert_product_object', [ $this, 'rest_api_maybe_update_v2' ], 10, 3 );
		add_filter(
			'woocommerce_rest_insert_product_variation_object',
			[ $this, 'rest_api_maybe_update_v2' ],
			10,
			3
		);
	}

	/**
	 * @param $schema
	 *
	 * @return array
	 * @throws Exception
	 */
	public function rest_api_product_schema( array $schema ): array {
		$elements           = $this->generate_element_list();
		$schema['gpf_data'] = [
			'description' => __( 'Google product feed data', 'woocommerce_gpf' ),
			'type'        => 'object',
			'content'     => [
				'view',
				'edit',
			],
		];
		foreach ( $elements as $key => $description ) {
			$schema['gpf_data']['items'][ $key ] = [
				'description' => $description,
				'type'        => 'string',
				'context'     => [
					'view',
					'edit',
				],
			];
		}

		return $schema;
	}

	/**
	 * Include MSRP prices in REST API for products/xx
	 *
	 * REST API v2 - WooCommerce 3.x
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	public function rest_api_output_v2( $response, $product, $request ) {
		// Hide internal meta values.
		$meta_keys = [
			'woocommerce_gpf_schema_cache',
			'woocommerce_gpf_schema_cache_timestamp',
		];
		if ( ! empty( $response->data['meta_data'] ) ) {
			foreach ( $response->data['meta_data'] as $idx => $meta_item ) {
				if ( in_array( $meta_item->key, $meta_keys, true ) ) {
					unset( $response->data['meta_data'][ $idx ] );
				}
			}
		}

		// Create a nicely formatted set of gpf data fields.
		$response->data['gpf_data'] = [];
		$meta                       = get_post_meta( $product->get_id(), '_woocommerce_gpf_data', true );
		$elements                   = $this->generate_element_list();
		foreach ( array_keys( $elements ) as $id ) {
			if ( ! empty( $meta[ $id ] ) ) {
				$response->data['gpf_data'][ $id ] = $meta[ $id ];
			} else {
				$response->data['gpf_data'][ $id ] = null;
			}
		}

		return $response;
	}
	// phpcs:enable

	/**
	 * Update the MSRP for a product via REST API v2.
	 *
	 * REST API v2 - WooCommerce 3.0.x
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	public function rest_api_maybe_update_v2( $product, $request, $creating ) {
		// Do nothing if no GPF data passed in for update.
		if ( ! isset( $request['gpf_data'] ) ) {
			return $product;
		}
		// Merge passed values over the top of existing ones.
		$meta = get_post_meta( $product->get_id(), '_woocommerce_gpf_data', true );
		if ( '' === $meta || false === $meta ) {
			$meta = [];
		}
		$meta = array_merge( $meta, $request['gpf_data'] );
		// Save the changes.
		update_post_meta( $product->get_id(), '_woocommerce_gpf_data', $meta );

		return $product;
	}
	// phpcs:enable

	/**
	 * Generate a list of our elements from the common field class.
	 *
	 * @return array   Array of GPF columns with appropriate keys.
	 * @throws Exception
	 */
	private function generate_element_list() {
		$fields = wp_list_pluck( $this->configuration->product_fields, 'desc' );
		foreach ( $fields as $key => $value ) {
			// Translators: Placeholder is the name of a specific data field.
			$fields[ $key ] = sprintf( __( 'Google product feed: %s', 'woocommerce_gpf' ), $value );
		}
		$fields['exclude_product'] = __( 'Google product feed: Hide product from feed (Y/N)', 'woocommerce_gpf' );

		return $fields;
	}
}
