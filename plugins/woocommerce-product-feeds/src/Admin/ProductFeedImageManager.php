<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use Ademti\WoocommerceProductFeeds\Helpers\ProductFeedItemFactory;
use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use Exception;

class ProductFeedImageManager {

	// Dependencies.
	private TemplateLoader $template;
	private ProductFeedItemFactory $feed_item_factory;

	/**
	 * WoocommerceProductFeedsFeedImageManager constructor.
	 *
	 * @param TemplateLoader $template_loader
	 * @param ProductFeedItemFactory $feed_item_factory
	 */
	public function __construct(
		TemplateLoader $template_loader,
		ProductFeedItemFactory $feed_item_factory
	) {
		$this->template          = $template_loader;
		$this->feed_item_factory = $feed_item_factory;
	}

	/**
	 * Add hooks for AJAX callbacks.
	 */
	public function initialise(): void {
		add_action( 'wp_ajax_woo_gpf_exclude_media', [ $this, 'exclude_media' ] );
		add_action( 'wp_ajax_woo_gpf_include_media', [ $this, 'include_media' ] );
		add_action( 'wp_ajax_woo_gpf_set_primary_media', [ $this, 'set_primary_media' ] );
		add_action( 'wp_ajax_woo_gpf_set_lifestyle_media', [ $this, 'set_lifestyle_media' ] );
		add_filter( 'is_protected_meta', [ $this, 'register_protected_meta' ], 10, 3 );
	}

	/**
	 * Hide our meta value from the Custom Fields metabox.
	 *
	 * @param $protected
	 * @param $meta_key
	 * @param $meta_type
	 *
	 * @return bool|mixed
	 */
	public function register_protected_meta( $protected, $meta_key, $meta_type ) {
		if ( 'woocommerce_gpf_primary_media_id' === $meta_key && 'post' === $meta_type ) {
			return true;
		}
		if ( 'woocommerce_gpf_lifestyle_media_id' === $meta_key && 'post' === $meta_type ) {
			return true;
		}

		return $protected;
	}

	/**
	 * Renders the summary of images calculated for a given post.
	 *
	 * @param $post
	 *
	 * @return void
	 * @throws Exception
	 */
	public function render_summary( \WP_Post $post ) {

		$wc_product = wc_get_product( $post );
		if ( ! $wc_product ) {
			return;
		}

		$excluded_images = $wc_product->get_meta( 'woocommerce_gpf_excluded_media_ids', true );
		if ( empty( $excluded_images ) || ! is_array( $excluded_images ) ) {
			$excluded_images = [];
		}

		$primary_media_id   = $wc_product->get_meta( 'woocommerce_gpf_primary_media_id', true );
		$lifestyle_media_id = $wc_product->get_meta( 'woocommerce_gpf_lifestyle_media_id', true );

		$feed_item          = $this->feed_item_factory->create( 'google', $wc_product, $wc_product, false );
		$images_and_sources = $this->get_image_sources_by_url( $feed_item );

		$this->template->output_template_with_variables( 'woo-gpf', 'meta-field-image-info-header', [] );
		foreach ( $feed_item->ordered_images as $image ) {
			$this->output_image_source(
				$wc_product->get_id(),
				$image['url'],
				$images_and_sources,
				$excluded_images,
				$primary_media_id,
				$lifestyle_media_id
			);
		}
		$this->template->output_template_with_variables( 'woo-gpf', 'meta-field-image-info-footer', [] );
	}

	/**
	 * Generate an array of image URLs keyed by URL, showing how they were retrieved. Options are:
	 * - 'product_image'   - the image is set as the product image on the product
	 * - 'product_gallery' - the image is set in the product gallery
	 * - 'attachment'      - the image is attached to the post via WordPress' media mechanism
	 * @return array
	 */
	private function get_image_sources_by_url( ProductFeedItem $feed_item ) {
		$results = [];
		foreach ( $feed_item->image_sources as $id => $data ) {
			$results[ $data['url'] ]       = $data;
			$results[ $data['url'] ]['id'] = $id;
		}

		return $results;
	}

	/**
	 * Output an image, its sources and actions.
	 *
	 * @param int $product_id
	 * @param string $url
	 * @param array $all_images_and_sources
	 * @param array $excluded_images
	 * @param string $primary_media_id
	 * @param string $lifestyle_media_id
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	private function output_image_source(
		$product_id,
		$url,
		$all_images_and_sources,
		$excluded_images,
		$primary_media_id,
		$lifestyle_media_id
	) {

		if ( isset( $all_images_and_sources[ $url ] ) ) {
			$images_and_sources = $all_images_and_sources[ $url ];
		} else {
			return;
		}

		$image_actions = $this->template->get_template_with_variables(
			'woo-gpf',
			'meta-field-image-info-include-action',
			[
				'nonce' => wp_create_nonce( 'woo_gpf_include_media' ),
			]
		);

		$image_actions .= $this->template->get_template_with_variables(
			'woo-gpf',
			'meta-field-image-info-exclude-action',
			[
				'nonce' => wp_create_nonce( 'woo_gpf_exclude_media' ),
			]
		);

		$primary_status = '';
		if ( (int) $primary_media_id === (int) $images_and_sources['id'] ) {
			$primary_status = 'woo-gpf-image-source-list-item-primary';
		}
		$lifestyle_status = '';
		if ( (int) $lifestyle_media_id === (int) $images_and_sources['id'] ) {
			$lifestyle_status = 'woo-gpf-image-source-list-item-lifestyle';
		}
		$image_actions .= $this->template->get_template_with_variables(
			'woo-gpf',
			'meta-field-image-info-set-primary-action',
			[
				'nonce' => wp_create_nonce( 'woo_gpf_set_primary_media' ),
			]
		);
		$image_actions .= $this->template->get_template_with_variables(
			'woo-gpf',
			'meta-field-image-info-set-lifestyle-action',
			[
				'nonce' => wp_create_nonce( 'woo_gpf_set_lifestyle_media' ),
			]
		);

		$image_source_content = '<ul class="woo-gpf-image-source-source-list">';
		foreach ( $images_and_sources['sources'] as $source ) {
			switch ( $source ) {
				case 'product_image':
					$image_source_content .= '<li>' .
											__( 'Set as product image', 'woocommerce_gpf' ) .
											'</li>';
					break;
				case 'product_gallery':
					$image_source_content .= '<li>' .
											__( 'Added via product gallery', 'woocommerce_gpf' ) .
											'</li>';
					break;
				case 'attachment':
					$image_source_content .= '<li>' .
											__( 'Attached as media to product', 'woocommerce_gpf' ) .
											'</li>';
					break;
				default:
					$image_source_content .= '<li>' .
											__( 'Added via filters', 'woocommerce_gpf' ) .
											'</li>';
					break;
			}
		}
		$image_source_content .= '</ul>';

		$image = wp_get_attachment_image(
			$images_and_sources['id'],
			'thumbnail',
			false,
			[
				'class' => 'woo-gpf-image-source-image',
			]
		);

		$list_item_status = 'woo-gpf-image-source-list-item-included';
		if ( in_array( $images_and_sources['id'], $excluded_images, true ) ) {
			$list_item_status = 'woo-gpf-image-source-list-item-excluded';
		}

		$this->template->output_template_with_variables(
			'woo-gpf',
			'meta-field-image-info-item',
			[
				'product_id'       => $product_id,
				'media_id'         => $images_and_sources['id'],
				'image'            => $image,
				'image_sources'    => $image_source_content,
				'image_actions'    => $image_actions,
				'list_item_status' => $list_item_status,
				'primary_status'   => $primary_status,
				'lifestyle_status' => $lifestyle_status,
			]
		);
	}

	/**
	 * AJAX Callback to handle adding an item to the list of excluded IDs.
	 *
	 * @return never
	 */
	public function exclude_media() {
		$nonce      = ! empty( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : null;
		$media_id   = ! empty( $_POST['media_id'] ) ? (int) $_POST['media_id'] : null;
		$product_id = ! empty( $_POST['product_id'] ) ? (int) $_POST['product_id'] : null;

		// Validate nonce
		if ( ! wp_verify_nonce( $nonce, 'woo_gpf_exclude_media' ) ) {
			die( 'Unauthorised' );
		}

		// Retrieve list of excluded IDs for this post
		$excluded_media_ids = get_post_meta( $product_id, 'woocommerce_gpf_excluded_media_ids', true );
		if ( empty( $excluded_media_ids ) || ! is_array( $excluded_media_ids ) ) {
			$excluded_media_ids = [];
		}
		// Add ID to list & save.
		if ( ! in_array( $media_id, $excluded_media_ids, true ) ) {
			$excluded_media_ids[] = (int) $media_id;
		}

		// Save list
		update_post_meta( $product_id, 'woocommerce_gpf_excluded_media_ids', $excluded_media_ids );

		// Make sure this isn't set as the primary, if so unset it.
		$primary_media_id = get_post_meta( $product_id, 'woocommerce_gpf_primary_media_id', true );
		if ( (int) $primary_media_id === (int) $media_id ) {
			delete_post_meta( $product_id, 'woocommerce_gpf_primary_media_id' );
		}

		// Make sure this isn't set as the lifestyle, if so unset it.
		$lifestyle_media_id = get_post_meta( $product_id, 'woocommerce_gpf_lifestyle_media_id', true );
		if ( (int) $lifestyle_media_id === (int) $media_id ) {
			delete_post_meta( $product_id, 'woocommerce_gpf_lifestyle_media_id' );
		}

		// Make sure the cache is bumped.
		do_action( 'woocommerce_gpf_media_ids_updated', $product_id );

		$this->echo_image_config( $product_id );
		die();
	}

	/**
	 * AJAX Callback to handle removing an item from the list of excluded IDs.
	 *
	 * @return never
	 */
	public function include_media() {
		$nonce      = ! empty( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : null;
		$media_id   = ! empty( $_POST['media_id'] ) ? (int) $_POST['media_id'] : null;
		$product_id = ! empty( $_POST['product_id'] ) ? (int) $_POST['product_id'] : null;

		// Validate nonce
		if ( ! wp_verify_nonce( $nonce, 'woo_gpf_include_media' ) ) {
			die( 'Unauthorised' );
		}

		// Retrieve list of excluded IDs for this post
		$excluded_media_ids = get_post_meta( $product_id, 'woocommerce_gpf_excluded_media_ids', true );
		if ( empty( $excluded_media_ids ) || ! is_array( $excluded_media_ids ) ) {
			$excluded_media_ids = [];
		}
		// Remove ID from the list.
		foreach ( array_keys( $excluded_media_ids, (int) $media_id, true ) as $key ) {
			unset( $excluded_media_ids[ $key ] );
		}
		$excluded_media_ids = array_values( $excluded_media_ids );

		// Save list
		update_post_meta( $product_id, 'woocommerce_gpf_excluded_media_ids', $excluded_media_ids );
		do_action( 'woocommerce_gpf_media_ids_updated', $product_id );

		$this->echo_image_config( $product_id );
		die();
	}

	/**
	 * AJAX Callback to handle setting a media item as primary.
	 *
	 * @return never
	 */
	public function set_primary_media() {
		$nonce      = ! empty( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : null;
		$media_id   = ! empty( $_POST['media_id'] ) ? (int) $_POST['media_id'] : null;
		$product_id = ! empty( $_POST['product_id'] ) ? (int) $_POST['product_id'] : null;

		// Validate nonce
		if ( ! wp_verify_nonce( $nonce, 'woo_gpf_set_primary_media' ) ) {
			die( 'Unauthorised' );
		}

		// Save list
		update_post_meta( $product_id, 'woocommerce_gpf_primary_media_id', $media_id );

		do_action( 'woocommerce_gpf_media_ids_updated', $product_id );

		$this->echo_image_config( $product_id );
		die();
	}

	/**
	 * AJAX Callback to handle setting a media item as primary.
	 *
	 * @return never
	 */
	public function set_lifestyle_media() {
		$nonce      = ! empty( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : null;
		$media_id   = ! empty( $_POST['media_id'] ) ? (int) $_POST['media_id'] : null;
		$product_id = ! empty( $_POST['product_id'] ) ? (int) $_POST['product_id'] : null;

		// Validate nonce
		if ( ! wp_verify_nonce( $nonce, 'woo_gpf_set_lifestyle_media' ) ) {
			die( 'Unauthorised' );
		}

		// Save list
		update_post_meta( $product_id, 'woocommerce_gpf_lifestyle_media_id', $media_id );

		do_action( 'woocommerce_gpf_media_ids_updated', $product_id );

		$this->echo_image_config( $product_id );
		die();
	}

	private function echo_image_config( ?int $product_id ): void {
		$primary_media_id   = (int) get_post_meta(
			$product_id,
			'woocommerce_gpf_primary_media_id',
			true
		);
		$lifestyle_media_id = (int) get_post_meta(
			$product_id,
			'woocommerce_gpf_lifestyle_media_id',
			true
		);
		$excluded_media_ids = get_post_meta(
			$product_id,
			'woocommerce_gpf_excluded_media_ids',
			true
		);
		if ( empty( $excluded_media_ids ) || ! is_array( $excluded_media_ids ) ) {
			$excluded_media_ids = [];
		}
		echo wp_json_encode(
			[
				'excluded_media_ids' => $excluded_media_ids,
				'primary_media_id'   => $primary_media_id,
				'lifestyle_media_id' => $lifestyle_media_id,
			]
		);
	}
}
