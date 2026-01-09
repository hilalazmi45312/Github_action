<?php
/**
 * Export CommonHelper Helper Library
 *
 * Includes helper functions for export CommonHelper modules
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro\Helpers\CommonHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Common_Helper' ) ) {

	/**
	 * Webtoffee_Product_Feed_Shipping Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Common_Helper {

		/**
		 * The minimum version message.
		 *
		 * @since    1.0.0
		 * @var      string    $min_version_msg    Min version message.
		 */
		public static $min_version_msg = '';

				/**
				 * Check the minimum base version required for post type modules.
				 *
				 * @param string $post_type Post type.
				 * @param string $post_type_title Post type title.
				 * @param string $min_version Min version.
				 * @return bool
				 */
		public static function check_base_version( $post_type, $post_type_title, $min_version ) {
			$warn_icon = '<span class="dashicons dashicons-warning"></span>&nbsp;';
			if ( ! version_compare( WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, $min_version, '>=' ) ) {
								/* translators: 1: post type title b. 2: plugin name. 3: min version. 4: plugin title */
				self::$min_version_msg .= $warn_icon . sprintf( __( 'The %1$s requires a minimum version of %2$s %3$s. Please upgrade the %4$s accordingly.', 'product-feed-woocommerce' ), "<b>$post_type_title</b>", '<b>' . WT_P_IEW_PLUGIN_NAME . '</b>', "<b>v$min_version</b>", '<b>' . WT_P_IEW_PLUGIN_NAME . '</b>' ) . '<br />';
				add_action( 'admin_notices', array( __CLASS__, 'no_minimum_base_version' ) );
				return false;
			}
			return true;
		}

		/**
		 *
		 *   No minimum version error message
		 */
		public static function no_minimum_base_version() {
			?>
		<div class="notice notice-warning">
			<p>
				<?php
				echo wp_kses_post( self::$min_version_msg );
				?>
			</p>
		</div>
			<?php
		}

		/**
		 * Gets the product categories.
		 *
		 * @return array
		 */
		public static function get_product_categories() {

			$term_query = new \WP_Term_Query(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'fields'     => 'id=>name',
				)
			);

			$product_categories = $term_query->get_terms();
			return is_array( $product_categories ) ? $product_categories : array();
		}

		/**
		 * Gets the product categories.
		 *
		 * @return array
		 */
		public static function get_product_categories_sluged() {

			$out = array();
			$product_categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',  // WooCommerce product categories.
					'hide_empty' => true,           // Hide empty categories.
				)
			);
			if ( ! is_wp_error( $product_categories ) ) {
				foreach ( $product_categories as $category ) {
					$out[ $category->slug ] = $category->name;
				}
			}

			return $out;
		}


		/**
		 * Local Attribute List to map product value with merchant attributes
		 *
		 * @param string $export_channel The export channel.
		 * @param string $selected The selected value.
		 *
		 * @return string
		 */
		public static function attribute_dropdown( $export_channel, $selected = '' ) {

			$attribute_dropdown = wp_cache_get( 'wt_feed_dropdown_product_attributes_pro_v1' );

			if ( false === $attribute_dropdown ) {
				$attributes = array(
					'id'                        => esc_attr__( 'Product Id', 'product-feed-woocommerce' ),
					'title'                     => esc_attr__( 'Product Title', 'product-feed-woocommerce' ),
					'description'               => esc_attr__( 'Product Description', 'product-feed-woocommerce' ),
					'short_description'         => esc_attr__( 'Product Short Description', 'product-feed-woocommerce' ),
					'product_type'              => esc_attr__( 'Product Local Category', 'product-feed-woocommerce' ),
					'link'                      => esc_attr__( 'Product URL', 'product-feed-woocommerce' ),
					'ex_link'                   => esc_attr__( 'External Product URL', 'product-feed-woocommerce' ),
					'condition'                 => esc_attr__( 'Condition', 'product-feed-woocommerce' ),
					'item_group_id'             => esc_attr__( 'Parent Id [Group Id]', 'product-feed-woocommerce' ),
					'sku'                       => esc_attr__( 'SKU', 'product-feed-woocommerce' ),
					'sku_id'                    => esc_attr__( 'SKU+ID[sku_id]', 'product-feed-woocommerce' ),
					'parent_sku'                => esc_attr__( 'Parent SKU', 'product-feed-woocommerce' ),
					'availability'              => esc_attr__( 'Availability', 'product-feed-woocommerce' ),
					'quantity'                  => esc_attr__( 'Quantity', 'product-feed-woocommerce' ),
					'price'                     => esc_attr__( 'Regular Price', 'product-feed-woocommerce' ),
					'current_price'             => esc_attr__( 'Price', 'product-feed-woocommerce' ),
					'sale_price'                => esc_attr__( 'Sale Price', 'product-feed-woocommerce' ),
					'price_with_tax'            => esc_attr__( 'Regular Price With Tax', 'product-feed-woocommerce' ),
					'current_price_with_tax'    => esc_attr__( 'Price With Tax', 'product-feed-woocommerce' ),
					'sale_price_with_tax'       => esc_attr__( 'Sale Price With Tax', 'product-feed-woocommerce' ),
					'sale_price_sdate'          => esc_attr__( 'Sale Start Date', 'product-feed-woocommerce' ),
					'sale_price_edate'          => esc_attr__( 'Sale End Date', 'product-feed-woocommerce' ),
					'weight'                    => esc_attr__( 'Weight', 'product-feed-woocommerce' ),
					'width'                     => esc_attr__( 'Width', 'product-feed-woocommerce' ),
					'height'                    => esc_attr__( 'Height', 'product-feed-woocommerce' ),
					'length'                    => esc_attr__( 'Length', 'product-feed-woocommerce' ),
					'shipping_class'            => esc_attr__( 'Shipping Class', 'product-feed-woocommerce' ),
					'type'                      => esc_attr__( 'Product Type', 'product-feed-woocommerce' ),
					'variation_type'            => esc_attr__( 'Variation Type', 'product-feed-woocommerce' ),
					'visibility'                => esc_attr__( 'Visibility', 'product-feed-woocommerce' ),
					'rating_total'              => esc_attr__( 'Total Rating', 'product-feed-woocommerce' ),
					'rating_average'            => esc_attr__( 'Average Rating', 'product-feed-woocommerce' ),
					'tags'                      => esc_attr__( 'Tags', 'product-feed-woocommerce' ),
					'sale_price_effective_date' => esc_attr__( 'Sale Price Effective Date', 'product-feed-woocommerce' ),
					'is_bundle'                 => esc_attr__( 'Is Bundle', 'product-feed-woocommerce' ),
					'author_name'               => esc_attr__( 'Author Name', 'product-feed-woocommerce' ),
					'author_email'              => esc_attr__( 'Author Email', 'product-feed-woocommerce' ),
					'date_created'              => esc_attr__( 'Date Created', 'product-feed-woocommerce' ),
					'date_updated'              => esc_attr__( 'Date Updated', 'product-feed-woocommerce' ),
					'identifier_exists'         => esc_attr__( 'Identifier Exists', 'product-feed-woocommerce' ),
					'promotion_id'              => esc_attr__( 'Product Id', 'product-feed-woocommerce' ),
					'long_title'                => esc_attr__( 'Product Title', 'product-feed-woocommerce' ),
					'promotion_effective_dates' => esc_attr__( 'Promotion effective dates', 'product-feed-woocommerce' ),
				);
				$images     = array(
					'image_link'    => esc_attr__( 'Main Image', 'product-feed-woocommerce' ),
					'feature_image' => esc_attr__( 'Featured Image', 'product-feed-woocommerce' ),
					'additional_image_link'        => esc_attr__( 'Images [Comma Separated]', 'product-feed-woocommerce' ),
					'wtimages_1'       => esc_attr__( 'Additional Image 1', 'product-feed-woocommerce' ),
					'wtimages_2'       => esc_attr__( 'Additional Image 2', 'product-feed-woocommerce' ),
					'wtimages_3'       => esc_attr__( 'Additional Image 3', 'product-feed-woocommerce' ),
					'wtimages_4'       => esc_attr__( 'Additional Image 4', 'product-feed-woocommerce' ),
					'wtimages_5'       => esc_attr__( 'Additional Image 5', 'product-feed-woocommerce' ),
					'wtimages_6'       => esc_attr__( 'Additional Image 6', 'product-feed-woocommerce' ),
					'wtimages_7'       => esc_attr__( 'Additional Image 7', 'product-feed-woocommerce' ),
					'wtimages_8'       => esc_attr__( 'Additional Image 8', 'product-feed-woocommerce' ),
					'wtimages_9'       => esc_attr__( 'Additional Image 9', 'product-feed-woocommerce' ),
					'wtimages_10'      => esc_attr__( 'Additional Image 10', 'product-feed-woocommerce' ),
				);

				$attribute_dropdown = '<option></option>';
				$attribute_dropdown .= sprintf( '<optgroup label="%s">', esc_attr__( 'Constant', 'product-feed-woocommerce' ) );
				$attribute_dropdown .= sprintf( '<option style="font-weight: bold;" value="%s">%s</option>', 'wt-static-map-vl', esc_attr__( 'Static value', 'product-feed-woocommerce' ) );
				$attribute_dropdown .= '</optgroup>';

				if ( is_array( $attributes ) && ! empty( $attributes ) ) {
					$attribute_dropdown .= sprintf( '<optgroup label="%s">', esc_attr__( 'Primary Attributes', 'product-feed-woocommerce' ) );
					foreach ( $attributes as $key => $value ) {
						$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
					}
					$attribute_dropdown .= '</optgroup>';
				}

				if ( is_array( $images ) && ! empty( $images ) ) {
					$attribute_dropdown .= sprintf( '<optgroup label="%s">', esc_attr__( 'Image Attributes', 'product-feed-woocommerce' ) );
					foreach ( $images as $key => $value ) {
						$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
					}
					$attribute_dropdown .= '</optgroup>';
				}

				/**
				 * Allow meta in mapping.
				 *
				 * @param bool $meta_in_mapping Allow meta in mapping.
				 * @since 1.0.0
				 */
				$meta_in_mapping = apply_filters( 'wt_pf_allow_meta_in_mapping', true );
				if ( $meta_in_mapping ) {
					$product_metas = self::get_product_metakeys();
					if ( is_array( $product_metas ) && ! empty( $product_metas ) ) {
						$attribute_dropdown .= sprintf( '<optgroup label="%s">', esc_attr__( 'Custom Fields/Post Meta', 'product-feed-woocommerce' ) );
						foreach ( $product_metas as $key => $value ) {
							$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
						}
						$attribute_dropdown .= '</optgroup>';
					}
				}
				/**
				 * Allow global attributes in mapping.
				 *
				 * @param bool $global_in_mapping Allow global attributes in mapping.
				 * @since 1.0.0
				 */
				$global_in_mapping = apply_filters( 'wt_pf_allow_global_attr_in_mapping', true );
				if ( $global_in_mapping ) {
					$product_global_attrs = self::get_global_attributes();
					if ( is_array( $product_global_attrs ) && ! empty( $product_global_attrs ) ) {
						$attribute_dropdown .= sprintf( '<optgroup label="%s">', esc_attr__( 'Product Attributes', 'product-feed-woocommerce' ) );
						foreach ( $product_global_attrs as $key => $value ) {
							$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
						}
						$attribute_dropdown .= '</optgroup>';
					}
				}

				wp_cache_set( 'wt_feed_dropdown_product_attributes_pro_v1', $attribute_dropdown, '', WEEK_IN_SECONDS );

			}

			if ( $selected && strpos( $attribute_dropdown, 'value="' . $selected . '"' ) !== false ) {
				$attribute_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"  selected', $attribute_dropdown );
			}
			if ( $selected && strpos( $selected, 'wt_static_map_vl:' ) !== false ) {
				$selected = 'wt-static-map-vl';
			}
			if ( $selected && strpos( $selected, 'wt_compute_map_vl:' ) !== false ) {
				$selected = 'wt-compute-map-vl';
			}
			if ( $selected && strpos( $attribute_dropdown, 'value="' . $selected . '"' ) !== false ) {
				$attribute_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"  selected', $attribute_dropdown );
			}

			/**
			 * Filter the attribute dropdown.
			 *
			 * @param string $attribute_dropdown The attribute dropdown.
			 * @param string $export_channel The export channel.
			 * @param string $selected The selected value.
			 * @return string
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_product_attributes_dropdown', $attribute_dropdown, $export_channel, $selected );
		}



		/**
		 * Get All Custom Attributes
		 *
		 * @return array
		 */
		private static function get_product_metakeys() {
			$attribute_dropdown = wp_cache_get( 'wt_feed_dropdown_product_cutom_meta' );
			if ( false === $attribute_dropdown ) {
				global $wpdb;
				$attribute_dropdown = array();

				$attribute_dropdown['fb_product_category'] = __(
					'Facebook Product Category',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['google_product_category'] = __(
					'Google Product Category',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['brand'] = __(
					'Brand',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['gtin'] = __(
					'GTIN',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['mpn'] = __(
					'MPN',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['age_group'] = __(
					'Age group',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['gender'] = __(
					'Gender',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['color'] = __(
					'Color',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['size'] = __(
					'Size',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['material'] = __(
					'Material',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['pattern'] = __(
					'Pattern',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['unit_pricing_measure'] = __(
					'Unit pricing measure',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['unit_pricing_base_measure'] = __(
					'Unit pricing base measure',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['energy_efficiency_class'] = __(
					'Energy efficiency class',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['min_energy_efficiency_class'] = __(
					'Min energy efficiencycclass',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['max_energy_efficiency_class'] = __(
					'Max energy efficiency class',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['shipping_data'] = __(
					'Shipping',
					'product-feed-woocommerce'
				);

				$attribute_dropdown['store_code'] = __(
					'Store Code',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['pickup_method'] = __(
					'Pickup Method',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['pickup_sla'] = __(
					'Pickup SLA',
					'product-feed-woocommerce'
				);

				$attribute_dropdown['custom_label_0'] = __(
					'Custom label 0',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['custom_label_1'] = __(
					'Custom label 1',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['custom_label_2'] = __(
					'Custom label 2',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['custom_label_3'] = __(
					'Custom label 3',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['custom_label_4'] = __(
					'Custom label 4',
					'product-feed-woocommerce'
				);

				$attribute_dropdown['number_of_ratings']     = __(
					'Number of ratings',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['number_of_reviews']     = __(
					'Number of reviews',
					'product-feed-woocommerce'
				);
				$attribute_dropdown['average_review_rating'] = __(
					'Average review rating',
					'product-feed-woocommerce'
				);

				$attribute_dropdown['review_id'] = __( 'Review Id[review_id]', 'product-feed-woocommerce' );
				$attribute_dropdown['reviewer_name'] = __( 'Reviewer Name[name]', 'product-feed-woocommerce' );
				$attribute_dropdown['reviewer_id'] = __( 'Reviewer Id[reviewer_id]', 'product-feed-woocommerce' );
				$attribute_dropdown['review_timestamp'] = __( 'Review Timestamp [review_timestamp]', 'product-feed-woocommerce' );
				$attribute_dropdown['review_title'] = __( 'Review Title[title]', 'product-feed-woocommerce' );
				$attribute_dropdown['content'] = __( 'Review Content[content]', 'product-feed-woocommerce' );
				$attribute_dropdown['review_url'] = __( 'Review URL[review_url]', 'product-feed-woocommerce' );
				$attribute_dropdown['ratings'] = __( 'Ratings[ratings]', 'product-feed-woocommerce' );
				$attribute_dropdown['is_spam'] = __( 'Is Spam[is_spam]', 'product-feed-woocommerce' );
				$attribute_dropdown['collection_method'] = __( 'Collection Method[collection_method]', 'product-feed-woocommerce' );
				$attribute_dropdown['product_name'] = __( 'Product Name[product_name]', 'product-feed-woocommerce' );
				$attribute_dropdown['product_url'] = __( 'Product URL[product_url]', 'product-feed-woocommerce' );

				$default_exclude_keys = array(
					// WP internals.
					'_edit_lock',
					'_wp_old_slug',
					'_edit_last',
					'_wp_old_date',
					// WC internals.
					'_downloadable_files',
					'_sku',
					'_weight',
					'_width',
					'_height',
					'_length',
					'_file_path',
					'_file_paths',
					'_default_attributes',
					'_product_attributes',
					'_children',
					'_variation_description',
					// ignore variation description, engine will get child product description from WC CRUD WC_Product::get_description().
					// Plugin Data.
					'_wpcom_is_markdown',
					// JetPack Meta.
					'_yith_wcpb_bundle_data',
					// Yith product bundle data.
					'_et_builder_version',
					// Divi builder data.
					'_vc_post_settings',
					// Visual Composer (WP Bakery) data.
					'_enable_sidebar',
					'frs_woo_product_tabs',
				);

				/**
				 * Exclude meta keys from dropdown
				 *
				 * @since 1.0.0
				 *
				 * @param array $exclude              meta keys to exclude.
				 * @param array $default_exclude_keys Exclude keys by default.
				 */
				$user_exclude = apply_filters( 'wt_feed_dropdown_exclude_meta_keys', null, $default_exclude_keys );

				if ( is_array( $user_exclude ) && ! empty( $user_exclude ) ) {
					$user_exclude         = esc_sql( $user_exclude );
					$default_exclude_keys = array_merge( $default_exclude_keys, $user_exclude );
				}

				$default_exclude_key_patterns = array(
					'%_et_pb_%', // Divi builder data.
					'attribute_%', // Exclude product attributes from meta list.
					'_yoast_wpseo_%', // Yoast SEO Data.
					'_acf-%', // ACF duplicate fields.
					'_aioseop_%', // All In One SEO Pack Data.
					'_oembed%', // exclude oEmbed cache meta.
					'_wpml_%', // wpml metas.
					'_oh_add_script_%', // SOGO Add Script to Individual Pages Header Footer.
					'_wt_facebook_%', // This plugin meta.
					'_wt_google_%', // This plugin meta.
					'_wt_feed_%', // This plugin meta.
				);

				/**
				 * Exclude meta key patterns from dropdown
				 *
				 * @since 1.0.0
				 *
				 * @param array $exclude                      meta keys to exclude.
				 * @param array $default_exclude_key_patterns Exclude keys by default.
				 */
				$user_exclude_patterns = apply_filters( 'wt_feed_dropdown_exclude_meta_keys_pattern', null, $default_exclude_key_patterns );
				if ( is_array( $user_exclude_patterns ) && ! empty( $user_exclude_patterns ) ) {
					$default_exclude_key_patterns = array_merge( $default_exclude_key_patterns, $user_exclude_patterns );
				}

				$all_exclude_keys = array_merge( $default_exclude_keys, $default_exclude_key_patterns );

				// sql escaped, cached.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$data = $wpdb->get_results(
					$wpdb->prepare(
										/**
										 * The distinct metakey
										 *
										 *  @lang text The distinct metakey */
						"SELECT DISTINCT( meta_key ) FROM {$wpdb->postmeta} WHERE 1=1 AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' OR post_type = 'product_variation' ) AND ( meta_key NOT IN ( " . implode( ',', array_fill( 0, count( $default_exclude_keys ), '%s' ) ) . ' ) ' . implode( ' ', array_fill( 0, count( $default_exclude_key_patterns ), ' AND meta_key NOT LIKE %s' ) ) . ' )',
						$all_exclude_keys
					)
				); // phpcs:ignore

				if ( count( $data ) ) {
					foreach ( $data as $value ) {

						$attribute_dropdown[ 'meta:' . $value->meta_key ] = $value->meta_key;
					}
				}

				$attribute_dropdown['meta:_yoast_wpseo_title'] = __( 'Yoast Title', 'product-feed-woocommerce' ); // Yoast Title.
				$attribute_dropdown['meta:_yoast_wpseo_metadesc'] = __( 'Yoast Description', 'product-feed-woocommerce' ); // Yoast Description.
				$attribute_dropdown['meta:_aioseo_title'] = __( 'All in One SEO Title', 'product-feed-woocommerce' ); // All in One SEO Title.
				$attribute_dropdown['meta:_aioseo_description'] = __( 'All in One SEO Description', 'product-feed-woocommerce' ); // All in One SEO Description.
				$attribute_dropdown['meta:rank_math_title'] = __( 'Rank Math SEO Title', 'product-feed-woocommerce' ); // Rank Math SEO Title.
				$attribute_dropdown['meta:rank_math_description'] = __( 'Rank Math SEO Description', 'product-feed-woocommerce' ); // Rank Math SEO Description.

				wp_cache_add( 'wt_feed_dropdown_product_cutom_meta', $attribute_dropdown, '', WEEK_IN_SECONDS );

			}
			/**
			 * Filter the product additional fields.
			 *
			 * @param array $attribute_dropdown The product additional fields.
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_product_additional_fields', $attribute_dropdown );
		}

		/**
		 * Get global attributes.
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public static function get_global_attributes() {

				$global_attribute_dropdown = wp_cache_get( 'wt_feed_dropdown_product_global_attr_v5' );
			if ( false === $global_attribute_dropdown ) {
				$global_attribute_dropdown = array();
				// Load the main attributes.
				$global_attributes = wc_get_attribute_taxonomy_labels();
				if ( count( $global_attributes ) ) {
					foreach ( $global_attributes as $key => $value ) {
						$global_attribute_dropdown[ 'wt_pf_pa_' . $key ] = $value;
					}
				}
				wp_cache_set( 'wt_feed_dropdown_product_global_attr_v5', $global_attribute_dropdown, '', WEEK_IN_SECONDS );
			}
			/**
			 * Get global attributes.
			 *
			 * @return array
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_product_global_attributes_fields', $global_attribute_dropdown );
		}

		/**
		 * Get gender list
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public static function get_geneder_list() {
			$gender_options = array(
				'male'           => _x(
					'Male',
					'product gender',
					'product-feed-woocommerce'
				),
				'female'   => _x(
					'Female',
					'product gender',
					'product-feed-woocommerce'
				),
				'unisex'          => _x(
					'Unisex',
					'product gender',
					'product-feed-woocommerce'
				),
			);
			/**
			 * Exclude meta key patterns from dropdown
			 *
			 * @since 1.0.0
			 *
			 * @param array $gender_options   Gender list.
			 */
			return apply_filters( 'wt_feed_product_gender_options', $gender_options );
		}

		/**
		 * Age group
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public static function get_age_group() {
			$age_group = array(
				'all ages' => __(
					'All ages',
					'product-feed-woocommerce'
				),
				'adult' => __(
					'Adult',
					'product-feed-woocommerce'
				),
				'teen' => __(
					'Teen',
					'product-feed-woocommerce'
				),
				'kids' => __(
					'Kids',
					'product-feed-woocommerce'
				),
				'toddler' => __(
					'Toddler',
					'product-feed-woocommerce'
				),
				'infant' => __(
					'Infant',
					'product-feed-woocommerce'
				),
				'newborn' => __(
					'Newborn',
					'product-feed-woocommerce'
				),
			);
			    /**
				 * Exclude meta key patterns from dropdown
				 *
				 * @since 1.0.0
				 *
				 * @param array $age_group   Age group list.
				 */
			return apply_filters( 'wt_feed_product_agegroup', $age_group );
		}
				/**
				 * Get product conditions
				 *
				 * @return array
				 * @since 1.0.0
				 */
		public static function wt_feed_get_product_conditions() {
			$conditions = array(
				'new'           => _x(
					'New',
					'product condition',
					'product-feed-woocommerce'
				),
				'refurbished'   => _x(
					'Refurbished',
					'product condition',
					'product-feed-woocommerce'
				),
				'used'          => _x(
					'Used',
					'product condition',
					'product-feed-woocommerce'
				),
				'used_like_new' => _x(
					'Used like new',
					'product condition',
					'product-feed-woocommerce'
				),
				'used_good'     => _x(
					'Used good',
					'product condition',
					'product-feed-woocommerce'
				),
				'used_fair'     => _x(
					'Used fair',
					'product condition',
					'product-feed-woocommerce'
				),
			);
			/**
			 * Exclude meta key patterns from dropdown
			 *
			 * @since 1.0.0
			 *
			 * @param array $conditions   Product conditions.
			 * @return array
			 */
			return apply_filters( 'wt_feed_facebook_product_conditions', $conditions );
		}

		/**
		 *   Decode the post data as normal array from json encoded from data.
		 *   If step key is specified, then it will return the data corresponds to the form key
		 *
		 *   @param array  $form_data Form data.
		 *   @param string $key Key.
		 *   @since 1.0.0
		 */
		public static function process_formdata( $form_data, $key = '' ) {
			if ( '' !== $key ) {
				if ( isset( $form_data[ $key ] ) ) {
					if ( is_array( $form_data[ $key ] ) ) {
						$form_data_vl = $form_data[ $key ];
					} else {
						$form_data_vl = json_decode( stripslashes( $form_data[ $key ] ), true );
					}
				} else {
					$form_data_vl = array();
				}
			} else {
				$form_data_vl = array();
				foreach ( $form_data as $form_datak => $form_datav ) {
					$form_data_vl[ $form_datak ] = self::process_formdata( $form_data, $form_datak );
				}
			}
			return ( is_array( $form_data_vl ) ? $form_data_vl : array() );
		}

		/**
		 * Form field generator
		 *
		 * @param array $form_fields Form fields.
		 * @param array $form_data Form data.
		 * @since 1.0.0
		 */
		public static function field_generator( $form_fields, $form_data ) {
			include plugin_dir_path( __DIR__ ) . 'admin/partials/form-field-generator.php';
		}


		/**
		 *   Save advanced settings
		 *
		 *   @param  array $settings   array of setting values.
		 *   @since 1.0.0
		 */
		public static function set_advanced_settings( $settings ) {
			update_option( 'wt_pf_advanced_settings', $settings );
		}

		/**
		 *
		 *   Extract validation rule from form field array
		 *
		 *   @param  array $fields   form field array.
		 *   @since 1.0.0
		 */
		public static function extract_validation_rules( $fields ) {
			$out = array_map(
				function ( $r ) {
					return ( isset( $r['validation_rule'] ) ? $r['validation_rule'] : '' );
				},
				$fields
			);
			return array_filter( $out );
		}

		/**
		 *   Get advanced settings.
		 *
		 *   @param      string $key    key for specific setting (optional).
		 *   @return     mixed   if key provided then the value of key otherwise array of values.
		 *   @since 1.0.0
		 */
		public static function get_advanced_settings( $key = '' ) {
			$advanced_settings = get_option( 'wt_pf_advanced_settings' );
			$advanced_settings = ( $advanced_settings ? $advanced_settings : array() );
			if ( '' !== $key ) {
				$key = ( substr( $key, 0, 8 ) !== 'wt_pf_' ? 'wt_pf_' : '' ) . $key;
				if ( isset( $advanced_settings[ $key ] ) ) {
					return $advanced_settings[ $key ];
				} else {
					$default_settings = self::get_advanced_settings_default();
					return ( isset( $default_settings[ $key ] ) ? $default_settings[ $key ] : '' );
				}
			} else {
				$default_settings = self::get_advanced_settings_default();
				$advanced_settings = wp_parse_args( $advanced_settings, $default_settings );
				return $advanced_settings;
			}
		}

		/**
		 *   Get default value of advanced settings
		 *
		 *   @return     array   array of default values.
		 *   @since 1.0.0
		 */
		public static function get_advanced_settings_default() {
			$fields = self::get_advanced_settings_fields();
			foreach ( $fields as $key => $value ) {
				if ( isset( $value['value'] ) ) {
					$key = ( substr( $key, 0, 8 ) !== 'wt_pf_' ? 'wt_pf_' : '' ) . $key;
					$out[ $key ] = $value['value'];
				}
			}
			return $out;
		}

		/**
		 *   Get advanced fields
		 *
		 *   @return     array   array of fields.
		 *   @since 1.0.0
		 */
		public static function get_advanced_settings_fields() {
			$fields = array();
				/**
				 * Settings field array
		 *
		 * @since 1.0.0
		 *
				 * @param array $fields   Settings fields.
				 */
			return apply_filters( 'wt_pf_advanced_setting_fields_pro', $fields );
		}
		/**
		 * Get allowed screens
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public static function wt_allowed_screens() {
			$screens = array(
				'webtoffee_product_feed_main_pro_export',
				'webtoffee_product_feed_main_pro_history',
				'webtoffee_product_feed',
				'product-feed-woocommerce',
				'wt_import_export_for_woo_basic',
				'wt_import_export_for_woo_basic_export',
				'wt_import_export_for_woo_basic_import',
				'wt_import_export_for_woo_basic_history',
				'wt_import_export_for_woo_basic_history_log',
				'webtoffee_product_feed_pro',
			);
				/**
				 * Screen options
		 *
		 * @since 1.0.0
		 *
				 * @param array $screens   Screen options.
				 */
			return apply_filters( 'wt_pf_allowed_screens_pro', $screens );
		}
				/**
				 * Get current page
				 *
				 * @return string
				 */
		public static function wt_get_current_page() {
			return ( isset( $_GET['page'] ) ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		}

		/**
		 * Is allowed screen
		 *
		 * @return bool
		 */
		public static function wt_is_screen_allowed() {

			return in_array( self::wt_get_current_page(), self::wt_allowed_screens() );
		}

		/**
		 * Returns the timestamp of the provided time string using a specific timezone as the reference.
		 *
		 * @param string $str String timezone.
		 * @return int number of the seconds
		 */
		public static function wt_strtotimetz( $str ) {

			$wt_default_time_zone = self::get_advanced_settings( 'default_time_zone' );
			if ( $wt_default_time_zone ) {
				$timezone = wp_timezone_string();
				$strtotime = strtotime(
					$str,
					strtotime(
					// Convert timezone to offset seconds.
						( new \DateTimeZone( $timezone ) )->getOffset( new \DateTime() ) - ( new \DateTimeZone( date_default_timezone_get() ) )->getOffset( new \DateTime() ) . ' seconds'
					)
				);
			} else {
				$strtotime = strtotime( $str );
			}
			return $strtotime;
		}

		/**
		 * 	Check if running on VIP File System.
		 * 
		 * 	@since 1.0.5
		 * 	@return bool
		 */
		public static function is_vip_env() {
			return defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV;
		}
	}
}

