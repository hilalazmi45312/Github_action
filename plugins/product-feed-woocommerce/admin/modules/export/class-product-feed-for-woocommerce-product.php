<?php
/**
 * Handles the product actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\Export
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Product_Feed_For_Woocommerce_Product' ) ) {
	/**
	 * Product_Feed_For_Woocommerce_Product Class.
	 */
	class Product_Feed_For_Woocommerce_Product {
		/**
		 * Parent product
		 *
		 * @var string
		 */
		public $parent_product;
		/**
		 * Current product id
		 *
		 * @var string
		 */
		public $current_product_id;
		/**
		 * Current product
		 *
		 * @var object|WC_Product
		 */
		public $product;

		/**
		 * Form data
		 *
		 * @var array
		 */
		public $form_data;
		
		/**
		 * Constructor.
		 *
		 * @param type $product Description.
		 * @since 1.0.0
		 */
		public function __construct( $product ) {
			$this->parent_product = $product;
			$this->current_product_id = $product->get_id();
			$this->product = $product;
		}


		/**
		 * Get product name.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function title( $catalog_attr, $product_attr, $export_columns ) {

			$title = $this->product->get_name();

			// Add all available variation attributes to variation title.
			if ( $this->product->is_type( 'variation' ) && ! empty( $this->product->get_attributes() ) ) {
				$title = $this->parent_product->get_name();
				$attributes = array();
				foreach ( $this->product->get_attributes() as $slug => $value ) {
					$attribute = $this->product->get_attribute( $slug );
					if ( ! empty( $attribute ) ) {
						$attributes[ $slug ] = $attribute;
					}
				}

				// set variation attributes with separator.
				$separator = ', ';

				$variation_attributes = implode( $separator, $attributes );

				// get product title with variation attribute.
				/**
				 * Get product title with variation attribute.
				 *				 
				 * @since 1.0.0
				 *
				 * @param bool $get_with_var_attributes Get with variation attribute.
				 * @param object $product Product object.
				 */
				$get_with_var_attributes = apply_filters( 'wt_feed_get_product_title_with_variation_attribute', true, $this->product );

				if ( $get_with_var_attributes ) {
					$title .= ' - ' . $variation_attributes;
				}
			}
			/**
			 * Filter the product title with variation attribute.
			 *			 
			 * @since 1.0.0
			 *
			 * @param string $title Product title.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_title', $title, $this->product );
		}

		/**
		 * Get product parent title.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function parent_title( $catalog_attr, $product_attr, $export_columns ) {
			if ( $this->product->is_type( 'variation' ) ) {
				$title = $this->parent_product->get_name();
			} else {
				$title = $this->title();
			}
			/**
			 * Filter the product parent title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $title Product parent title.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_title', $title, $this->product );
		}

		/**
		 * Get product description.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function description( $catalog_attr, $product_attr, $export_columns ) {
			$description = $this->product->get_description();

			// Get Variation Description.
			if ( '' === $description && $this->product->is_type( 'variation' ) ) {
				$description = '';
				$parent_product = wc_get_product( $this->product->get_parent_id() );
				if ( is_object( $parent_product ) ) {
					$description = $parent_product->get_description();
				}
			}

			if ( '' === $description ) {
				$description = $this->product->get_short_description();
			}

			// Add variations attributes after description to prevent Google error.
			if ( $this->product->is_type( 'variation' ) && ( '' === $description ) ) {
				$attributes = $this->product->get_attributes();
				if ( ! empty( $attributes ) ) {
					$variation_values = array();
					foreach ( $attributes as $attribute_name => $attribute_value ) {
						// Get proper case from term name, fallback to raw value
						$term = get_term_by( 'slug', $attribute_value, $attribute_name );
						$variation_values[] = $term ? $term->name : $attribute_value;
					}
					$description .= ' ' . implode( ', ', $variation_values );
				} else {
					$description .= ' ' . $this->product->get_id();
				}
			}

			// strip tags and special characters.
			$description = wp_strip_all_tags( $description );
			/**
			 * Filter the product description.
			 *
			 * @since 1.0.0
			 *
			 * @param string $description Product description.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_description', $description, $this->product );
		}

		/**
		 * Get product description with HTML tags.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function description_with_html( $catalog_attr, $product_attr, $export_columns ) {
			$description = $this->product->get_description();

			// Get Variation Description.
			if ( empty( $description ) && $this->product->is_type( 'variation' ) ) {
				$description = '';
				if ( ! is_null( $this->parent_product ) ) {
					$description = $this->parent_product->get_description();
				}
			}

			if ( empty( $description ) ) {
				$description = $this->product->get_short_description();
			}

			// Add variations attributes after description to prevent Facebook error.
			if ( $this->product->is_type( 'variation' ) ) {
				$variation_info = explode( '-', $this->product->get_name() );
				if ( isset( $variation_info[1] ) ) {
					$extension = $variation_info[1];
				} else {
					$extension = $this->product->get_id();
				}
				$description .= ' ' . $extension;
			}

			// remove spacial characters.
			$description = wp_check_invalid_utf8( wp_specialchars_decode( $description ), true );
			/**
			 * Filter the product description with HTML tags.
			 *
			 * @since 1.0.0
			 *
			 * @param string $description Product description.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_description_with_html', $description, $this->product );
		}

		/**
		 * Get product short description.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function short_description( $catalog_attr, $product_attr, $export_columns ) {
			$short_description = $this->product->get_short_description();

			// Get Variation Short Description.
			if ( empty( $short_description ) && $this->product->is_type( 'variation' ) ) {
				$short_description = $this->parent_product->get_short_description();
			}

			// Strip tags and special characters.
			$short_description = wp_strip_all_tags( $short_description );

			/**
			 * Filter the product short description before export.
			 *
			 * Allows modification of the product short description before it's exported.
			 *
			 * @since 1.0.0
			 *
			 * @param string $short_description The product short description.
			 * @param object $product          The product object.
			 */
			return apply_filters( 'wt_feed_filter_product_short_description', $short_description, $this->product );
		}

		/**
		 * Get product primary category.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function primary_category( $catalog_attr, $product_attr, $export_columns ) {
			$parent_category = '';
			/**
			 * Filter the product primary category separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product primary category separator.
			 * @param object $product Product object.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );

			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$parent_category = $full_category_array[0];
			}
			/**
			 * Filter the product primary category.
			 *
			 * @since 1.0.0
			 *
			 * @param string $parent_category Product primary category.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_primary_category', $parent_category, $this->product );
		}

		/**
		 * Get product primary category id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function primary_category_id( $catalog_attr, $product_attr, $export_columns ) {
			$parent_category_id = '';
			/**
			 * Filter the product primary category separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $parent_category_id Product primary category id.
			 * @param object $product Product object.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$parent_category_obj = get_term_by( 'name', $full_category_array[0], 'product_cat' );
				$parent_category_id = isset( $parent_category_obj->term_id ) ? $parent_category_obj->term_id : '';
			}
			/**
			 * Filter the product primary category id.
			 *
			 * @since 1.0.0
			 *
			 * @param string $parent_category_id Product primary category id.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_primary_category_id', $parent_category_id, $this->product );
		}

		/**
		 * Get product child category.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function child_category( $catalog_attr, $product_attr, $export_columns ) {
			$child_category = '';
			/**
			 * Filter the product child category separator.
			 * 
			 * @since 1.0.0
			 *
			 * @param string $separator Product child category separator.
			 * @param object $product Product object.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$child_category = end( $full_category_array );
			}
			/**
			 * Filter the product child category.
			 *
			 * @since 1.0.0
			 *
			 * @param string $child_category Product child category.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_child_category', $child_category, $this->product );
		}
		/**
		 * Get product price.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function price( $catalog_attr, $product_attr, $export_columns ) {

			$rprice = $this->product->get_regular_price();

			if ( $this->product->is_type( 'variable' ) ) {
				$rprice = $this->first_variation_price();
			}

			$price = $rprice;
			if ( is_plugin_active( 'woo-discount-rules/woo-discount-rules.php' ) || is_plugin_active( 'woo-discount-rules-pro/woo-discount-rules-pro.php' ) ) {
				// woo-discount-rules plugin compatiblity.

				/**
				 * Filter the product price.
				 *
				 * @since 1.0.0
				 *
				 * @param string $price Product price.
				 * @param object $product Product object.
				 * @param array $form_data Form data.
				 */
				$discounted_price = apply_filters( 'advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $rprice, 'discounted_price', true, true );
				if ( ! empty( $discounted_price ) && $this->product->is_on_sale() ) {
					$price = $discounted_price;
				} else {
					$price = $rprice;
				}
				if ( $this->product->is_on_sale() && ! empty( $discounted_price ) ) {
					$price = $this->product->get_sale_price();
				}
			} else {
				 $price = $rprice;
			}

			if ( class_exists( 'YITH_WC_Dynamic_Pricing_Discounts' ) ) {
				if ( $this->product->is_on_sale() ) {
					$price = $this->product->get_sale_price();
				}
			}

			$selected_currency = get_woocommerce_currency();
			$selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price( $price, $selected_currency, $selected_country, $this->product );
				}
			}

			if ( $price > 0 ) {
				/**
				 * Filter the product price.
				 *
				 * @since 1.0.0
				 *
				 * @param string $price Product price.
				 * @param object $product Product object.
				 * @param array $form_data Form data.
				 */
				$need_decimal_format = apply_filters( 'wt_wc_format_decimal_needed', true );
				if ( $need_decimal_format ) {
					$price = wc_format_decimal( $price, 2 );
				}
				$price = $price . ' ' . $selected_currency;
			}

				/**
				 * Filter the product price.
				 *
				 * @since 1.0.0
				 *
				 * @param string $price Product price.
				 * @param object $product Product object.
				 * @param array $form_data Form data.
				 */
			$price = apply_filters( 'wt_feed_filter_product_price', $price, $this->product, $this->form_data );
				/**
				 * Filter the product price.
				 *
				 * @since 1.0.0
				 *
				 * @param string $price Product price.
				 * @param object $product Product object.
				 * @param array $form_data Form data.
				 */
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_price", $price, $this->product, $this->form_data );
		}

		/**
		 * Get product sale price.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function sale_price( $catalog_attr, $product_attr, $export_columns ) {
			$price = $this->product->get_sale_price();
			
			if ( ! $price && ( is_plugin_active( 'woo-discount-rules/woo-discount-rules.php' ) || is_plugin_active( 'woo-discount-rules-pro/woo-discount-rules-pro.php' ) ) ) {
				$price = $this->product->get_regular_price();
			}

			if ( is_plugin_active( 'yaypricing/yaypricing.php' ) || is_plugin_active( 'yaypricing-pro/yaypricing.php' ) ) {
				$product_sale             = new \YAYDP\Core\Sale_Display\YAYDP_Product_Sale( $this->product );
				$min_max_discounted_price = $product_sale->get_min_max_discounted_price();
				if ( is_array( $min_max_discounted_price ) ) {
					$min_discounted_price               = $min_max_discounted_price['min'];
					$max_discounted_price               = $min_max_discounted_price['max'];
					$discounted_prices = array_unique( array( $min_discounted_price, $max_discounted_price ) );
					if ( ! empty( $discounted_prices ) ) {
						$price = $discounted_prices[0];
					}
				}
			}

			$selected_currency = get_woocommerce_currency();
			$selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price( $price, $selected_currency, $selected_country, $this->product );
				}
			}

			if ( class_exists( 'YITH_WC_Dynamic_Pricing_Discounts' ) ) {
				if ( $this->product->is_on_sale() ) {
					$price = $this->product->get_sale_price();
				} else {
					$price = $this->product->get_regular_price();
				}
				$product_manager = YWDPD_Frontend::get_instance();
				$price = $product_manager->get_dynamic_price( $price, $this->product, 1 );
			}

			// RightPress dynamic pricing support.
			if ( class_exists( 'RP_WCDPD_Product_Pricing' ) ) {
				$price = RP_WCDPD_Product_Pricing::apply_simple_product_pricing_rules_to_product_price( $price, $this->product );
			}

			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.

				/**
				 * Filter the product sale price.
				 *
				 * @since 1.0.0
				 *
				 * @param string $price Product sale price.
				 * @param object $product Product object.
				 * @param array $form_data Form data.
				 */
				$discounted_price = apply_filters( 'advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true );

				if ( ! empty( $discounted_price ) ) {
						$price = $discounted_price;
				}
				/**
				 * Filter the product sale price.
				 *
				 * @since 1.0.0
				 *
				 * @param string $price Product sale price.
				 * @param object $product Product object.
				 * @param array $form_data Form data.
				 */
				$need_decimal_format = apply_filters( 'wt_wc_format_decimal_needed', true );
				if ( $need_decimal_format ) {
					$price = wc_format_decimal( $price, 2 );
				}
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product sale price.
			 *
			 * @since 1.0.0
			 *
			 * @param string $price Product sale price.
			 * @param object $product Product object.
			 * @param array $form_data Form data.
			 */
			$price = apply_filters( 'wt_feed_filter_product_sale_price', $price, $this->product, $this->form_data );
			
			/**
			 * Filter the product sale price.
			 *
			 * @since 1.0.0
			 *
			 * @param string $price Product sale price.
			 * @param object $product Product object.
			 * @param array $form_data Form data.
			 */
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_sale_price", $price, $this->product, $this->form_data );
		}

		/**
		 * Get product GTIN.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function gtin( $catalog_attr, $product_attr, $export_columns ) {

			$custom_gtin = get_post_meta( $this->product->get_id(), '_wt_feed_gtin', true );
			if ( ! $custom_gtin ) {
				$custom_gtin = get_post_meta( $this->product->get_id(), '_global_unique_id', true );
			}
			$gtin = ( $custom_gtin ) ? $custom_gtin : '';
			/**
			 * Filter the product GTIN.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gtin Product GTIN.
			 * @param object $product Product object.
			 */
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_gtin", $gtin, $this->product );
		}
		/**
		 * Get product MPN.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function mpn( $catalog_attr, $product_attr, $export_columns ) {

			$custom_mpn = get_post_meta( $this->product->get_id(), '_wt_feed_mpn', true );

			$mpn = ( $custom_mpn ) ? $custom_mpn : '';
			/**
			 * Filter the product MPN.
			 *
			 * @since 1.0.0
			 *
			 * @param string $mpn Product MPN.
			 * @param object $product Product object.
			 */
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_mpn", $mpn, $this->product );
		}


		/**
		 * Get variant option name.
		 *
		 * @since 1.0.0
		 * @param int    $product_id The product ID.
		 * @param string $label The label.
		 * @param string $default_value The default value.
		 * @return string
		 */
		public function get_variant_option_name( $product_id, $label, $default_value ) {

			$meta = get_post_meta( $product_id, $label, true );
			$attribute_name = str_replace( 'attribute_', '', $label );
			$term = get_term_by( 'slug', $meta, $attribute_name );
			return ( $term && $term->name ) ? $term->name : $default_value;
		}

		/**
		 * Sanitize variant name.
		 *
		 * @since 1.0.0
		 * @param string $name The name.
		 * @return string
		 */
		public static function sanitize_variant_name( $name ) {

			$name = str_replace( array( 'attribute_', 'pa_' ), '', strtolower( $name ) );

			if ( 'colour' === $name ) {
				$name = 'color';
			}

			switch ( $name ) {
				case 'size':
				case 'color':
				case 'gender':
				case 'pattern':
					break;
				default:
					$name = 'custom_data:' . strtolower( $name );
					break;
			}

			return $name;
		}

		/**
		 * Get custom attribute data.
		 *
		 * @param array  $product The product.
		 * @param string $attribute_name The attribute name.
		 * @return string
		 */
		public function custom_attr_data( $product, $attribute_name ) {

			$attributes = $product->get_variation_attributes();
			$attr_value = '';

			if ( ! $attributes ) {
				/**
				 * Filter the product data.
				 *
				 * @param string $attribute_name The attribute name.
				 * @param object $product The product.
				 * @param array $form_data The form data.
				 * @since 1.0.0
				 */
				return apply_filters( "wt_feed_{$attribute_name}_product_data", $attribute_name, $product, $this->form_data );
			}

			$variant_names = array_keys( $attributes );

			foreach ( $variant_names as $original_variant_name ) {

				$label = wc_attribute_label( $original_variant_name, $product );

				$new_name = str_replace( 'custom_data:', '', self::sanitize_variant_name( $original_variant_name ) );

				$options = $this->get_variant_option_name( $product->get_id(), $label, $attributes[ $original_variant_name ] );

				if ( $options ) {

					if ( is_array( $options ) ) {

						$option_values = array_values( $options );
					} else {

						$option_values = array( $options );

						if ( count( $option_values ) === 1 && empty( $option_values[0] ) ) {
							$option_values[0] = 'any';
						}
					}

					switch ( $new_name ) {

						case $attribute_name:
							$attr_value = $option_values[0];

							break;

						default:
							break;
					}
				}
			}
			if ( '' == $attr_value ) {
				$parent = wc_get_product( $product->get_parent_id() );
				$product_attributes = $parent->get_attributes();
				if ( isset( $product_attributes[ $attribute_name ] ) ) {
					$attr_value = $product_attributes[ $attribute_name ]['options']['0'];
				}
			}
			if ( '' == $attr_value ) {
				$product_attributes = $product->get_attributes();
				if ( isset( $product_attributes[ $attribute_name ] ) ) {
					$attr_value = $product_attributes[ $attribute_name ]['options']['0'];
				}
			}
			/**
			 * Filter the product additional fields.
			 *
			 * @param string $attr_value The attribute value.
			 * @param object $product The product.
			 * @param array $form_data The form data.
			 * @since 1.0.0
			 */
			return apply_filters( "wt_feed_{$attribute_name}_product_data", $attr_value, $product, $this->form_data );
		}

		/**
		 * Get product URL.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function link( $catalog_attr, $product_attr, $export_columns ) {
			$link = $this->product->get_permalink();
			/**
			 * Filter the product link.
			 *
			 * @since 1.0.0
			 *
			 * @param string $link Product link.
			 * @param object $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_link', $link, $this->product );
		}
	}

}
