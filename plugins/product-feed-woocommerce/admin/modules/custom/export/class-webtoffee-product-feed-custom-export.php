<?php
/**
 * Webtoffee Product Feed Custom Export
 *
 * @package Webtoffee_Product_Feed_Custom_Export
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Custom_Export' ) ) {

	/**
	 * Webtoffee Product Feed Custom Export
	 *
	 * @package Webtoffee_Product_Feed_Custom_Export
	 */
	class Webtoffee_Product_Feed_Custom_Export extends Product_Feed_For_Woocommerce_Product {

		/**
		 * Parent module
		 *
		 * @var string|object
		 */
		public $parent_module = null;

		/**
		 * Product
		 *
		 * @var string|object
		 */
		public $product;

		/**
		 * Current product ID
		 *
		 * @var string
		 */
		public $current_product_id;

		/**
		 * Form data
		 *
		 * @var array
		 */
		public $form_data;

		/**
		 * Constructor
		 *
		 * @param object $parent_object The parent object.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $parent_object ) {

			$this->parent_module = $parent_object;
		}

		/**
		 * Prepare header
		 *
		 * @return array The export columns.
		 */
		public function prepare_header() {

			$export_columns = $this->parent_module->get_selected_column_names();

			/**
			 * Filter the export columns.
			 *
			 * @since 1.0.0
			 * @param array $export_columns The export columns.
			 * @return array The export columns.
			 */
			return apply_filters( 'wt_pf_alter_product_feed_csv_columns', $export_columns );
		}

		/**
		 * Prepare data that will be exported.
		 *
		 * @since 1.0.0
		 * @param array $form_data The form data.
		 * @param int   $batch_offset The batch offset.
		 * @param int   $step The step.
		 * @return array The export data.
		 */
		public function prepare_data_to_export( $form_data, $batch_offset, $step ) {

			$this->form_data = $form_data;

			$include_variations_type = ! empty( $form_data['post_type_form_data']['wt_pf_include_variations_type'] ) ? $form_data['post_type_form_data']['wt_pf_include_variations_type'] : '';

			$exc_stock_status = ! empty( $form_data['post_type_form_data']['item_outofstock'] ) ? $form_data['post_type_form_data']['item_outofstock'] : '';

			if ( '' === $exc_stock_status ) {
				$exc_stock_status = ! empty( $form_data['post_type_form_data']['wt_pf_exclude_outofstock'] ) ? $form_data['post_type_form_data']['wt_pf_exclude_outofstock'] : '';
			}

			$item_parentonly = ! empty( $form_data['post_type_form_data']['item_parentonly'] ) ? $form_data['post_type_form_data']['item_parentonly'] : '';

			if ( '' === $item_parentonly ) {
				$item_parentonly = ! empty( $form_data['post_type_form_data']['wt_pf_include_parent_only'] ) ? $form_data['post_type_form_data']['wt_pf_include_parent_only'] : '';
			}
			
			$prod_exc_categories = ! empty( $form_data['post_type_form_data']['item_exc_cat'] ) ? $form_data['post_type_form_data']['item_exc_cat'] : array();
			$prod_inc_categories = ! empty( $form_data['post_type_form_data']['item_inc_cat'] ) ? $form_data['post_type_form_data']['item_inc_cat'] : array();

			$cat_filter_type = ! empty( $form_data['post_type_form_data']['cat_filter_type'] ) ? $form_data['post_type_form_data']['cat_filter_type'] : '';
			if ( '' === $cat_filter_type ) {
				$cat_filter_type = ! empty( $form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] ) ? $form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] : 'include_cat';
			}

			$inc_exc_category = ! empty( $form_data['post_type_form_data']['inc_exc_cat'] ) ? $form_data['post_type_form_data']['inc_exc_cat'] : array();
			if ( empty( $inc_exc_category ) ) {
				$inc_exc_category = ! empty( $form_data['post_type_form_data']['wt_pf_inc_exc_category'] ) ? $form_data['post_type_form_data']['wt_pf_inc_exc_category'] : array();
			}

			if ( 'include_cat' === $cat_filter_type ) {
				$prod_inc_categories = $inc_exc_category;
			} else {
				$prod_exc_categories = $inc_exc_category;
			}
			$wt_pf_export_post_author = ! empty( $form_data['post_type_form_data']['wt_pf_export_post_author'] ) ? $form_data['post_type_form_data']['wt_pf_export_post_author'] : '';

			$brand_filter_type = ! empty( $form_data['post_type_form_data']['wt_pf_export_brand_filter_type'] ) ? $form_data['post_type_form_data']['wt_pf_export_brand_filter_type'] : 'include_brand';
			$inc_exc_brand = ! empty( $form_data['post_type_form_data']['wt_pf_inc_exc_brand'] ) ? $form_data['post_type_form_data']['wt_pf_inc_exc_brand'] : array();

			if ( 'include_brand' === $brand_filter_type ) {
				$prod_inc_brands = $inc_exc_brand;
			} else {
				$prod_exc_brands = $inc_exc_brand;
			}

			$prod_exc = ! empty( $form_data['post_type_form_data']['item_exc_prd'] ) ? $form_data['post_type_form_data']['item_exc_prd'] : array();

			if ( empty( $prod_exc ) ) {
				$prod_exc = ! empty( $form_data['post_type_form_data']['wt_pf_exclude_products'] ) ? $form_data['post_type_form_data']['wt_pf_exclude_products'] : array();
			}

			$tag_filter_type = ! empty( $form_data['post_type_form_data']['wt_pf_export_tag_filter_type'] ) ? $form_data['post_type_form_data']['wt_pf_export_tag_filter_type'] : 'include_tag';
			$inc_exc_tag = ! empty( $form_data['post_type_form_data']['wt_pf_inc_exc_tag'] ) ? $form_data['post_type_form_data']['wt_pf_inc_exc_tag'] : array();

			/*
			 WPML
			 *
			 */
			$item_post_lang = ! empty( $form_data['post_type_form_data']['wt_pf_export_post_language'] ) ? $form_data['post_type_form_data']['wt_pf_export_post_language'] : '';

			$prod_types = ! empty( $form_data['post_type_form_data']['wt_pf_product_types'] ) ? $form_data['post_type_form_data']['wt_pf_product_types'] : array();

			$prod_status = ! empty( $form_data['filter_form_data']['wt_pf_product_status'] ) ? $form_data['filter_form_data']['wt_pf_product_status'] : array();

			$export_sortby = ! empty( $form_data['filter_form_data']['wt_pf_sort_columns'] ) ? $form_data['filter_form_data']['wt_pf_sort_columns'] : 'ID';
			$export_sort_order = ! empty( $form_data['filter_form_data']['wt_pf_order_by'] ) ? $form_data['filter_form_data']['wt_pf_order_by'] : 'ASC';

			$export_limit = ! empty( $form_data['filter_form_data']['wt_pf_limit'] ) ? intval( $form_data['filter_form_data']['wt_pf_limit'] ) : 999999999; // user limit.
			$current_offset = ! empty( $form_data['filter_form_data']['wt_pf_offset'] ) ? intval( $form_data['filter_form_data']['wt_pf_offset'] ) : 0; // user offset.

			$batch_count = ! empty( $form_data['advanced_form_data']['wt_pf_batch_count'] ) ? $form_data['advanced_form_data']['wt_pf_batch_count'] : Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'default_export_batch' );
			/**
			 * Filter the batch count.
			 *
			 * @since 1.0.0
			 * @param int $batch_count The batch count.
			 * @return int The batch count.
			 */
			$batch_count = apply_filters( 'wt_product_feed_limit_per_request', $batch_count ); // ajax batch limit.

			$real_offset = ( $current_offset + $batch_offset );

			if ( $batch_count <= $export_limit ) {
				if ( ( $batch_offset + $batch_count ) > $export_limit ) { // last offset.
					$limit = $export_limit - $batch_offset;
				} else {
					$limit = $batch_count;
				}
			} else {
				$limit = $export_limit;
			}

			$product_array = array();
			$total_products = 0;
			if ( $batch_offset < $export_limit ) {
				$args = array(
					'status' => array( 'publish' ),
					'type' => array_keys( wc_get_product_types() ),
					'limit' => $limit,
					'offset' => $real_offset,
					'orderby' => $export_sortby,
					'order' => $export_sort_order,
					'return' => 'ids',
					'paginate' => true,
				);

				$include_variation = true;
				$filter_subscription_variation_only = false;
				$filter_normal_variation_only = false;
				if ( ! empty( $prod_types ) ) {
					$args['type'] = $prod_types;
				
					if ( 1 === count( $prod_types ) ) {
						$single_type = $prod_types[0];
					
						if ( in_array( $single_type, array( 'variable-subscription', 'subscription' ), true ) ) {
							$include_variation = false;
							$filter_subscription_variation_only = true;

						} elseif ( 'variation' === $single_type ) {
							$filter_normal_variation_only = true;
							$include_variation = false;
						}
					}
				}
				
				if ( empty( $item_parentonly ) && $include_variation && !in_array('variation', $args['type'])) {
					array_push( $args['type'], 'variation' );
				}

				if ( isset( $args['type'] ) && in_array( 'variation', $args['type'] ) && !in_array('variable', $args['type']) && !empty($item_parentonly) ) {
					array_push( $args['type'], 'variable' );
				}

				if ( '' !== $wt_pf_export_post_author ) {
					$args['author'] = is_array( $wt_pf_export_post_author ) ? implode( ',', $wt_pf_export_post_author ) : $wt_pf_export_post_author;
				}

				if ( ! empty( $prod_status ) ) {
					$args['status'] = $prod_status;
				}

				if ( ! empty( $prod_exc_categories ) ) {
					$args['exclude_category'] = $prod_exc_categories;
				}

				if ( ! empty( $prod_inc_categories ) ) {
					$args['category'] = $prod_inc_categories;
				}

				if ( ! empty( $prod_tags ) ) {
					$args['tag'] = $prod_tags;
				}

				if ( ! empty( $prod_exc ) ) {
					$temp_prd_ids = $prod_exc;
                    foreach ($temp_prd_ids as $prod_exc_id) {
                        $crnt_prd = wc_get_product($prod_exc_id);
                        if ( is_object($crnt_prd) && $crnt_prd->is_type('variable' ) ) {
                            //if variable is true, include it's variations
                            $variations = $crnt_prd->get_available_variations();
                            $variations_ids = wp_list_pluck($variations, 'variation_id');
                            foreach ($variations_ids as $variations_id) {
                                $prod_exc[] = $variations_id;
                            }
                        } elseif ( is_object($crnt_prd) && $crnt_prd->is_type('variation' ) ) {
                            $parent_id = $crnt_prd->get_parent_id();
                            if ( $parent_id ) {
                                $parent_product = wc_get_product( $parent_id );
                                $default_variation_id = $this->get_default_variation( $parent_product );
                                
                                // If this variation is the default variation of its parent, exclude the parent variable product too
                                if ( $default_variation_id == $prod_exc_id ) {
                                    $prod_exc[] = $parent_id;
                                }
                            }
                        }
                    }
					$args['exclude'] = $prod_exc; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				}

				if ( ! empty( $exc_stock_status ) ) {
					$available_status = wc_get_product_stock_status_options();
					unset( $available_status['outofstock'] );
					$args['stock_status'] = array_keys( $available_status );
				}

				$args['exclude_discarded'] = '_wt_feed_discard'; // To exclude individual excluded from product fetching.

				/**
				 * Filter the product catalog arguments.
				 *
				 * @since 1.0.0
				 * @param array $args The arguments.
				 * @return array The arguments.
				 */
				$args = apply_filters( 'wt_feed_product_catalog_args', $args );

				/**
				 * Polylang start.
				 */
				// if ( ( defined( 'POLYLANG_BASENAME' ) || function_exists( 'PLL' ) ) && ! empty( $item_post_lang ) ) {
				// 	$args['lang'] = $item_post_lang;
				// }
				// if ( ( defined( 'POLYLANG_BASENAME' ) || function_exists( 'PLL' ) ) && empty( $item_post_lang ) ) {
				// 	$args['lang'] = 'all';
				// }
				/**
				 * Polylang end.
				 */

				/*
				 * WPML - Swicth language to selected language for temparory export
				 */
				if ( class_exists( 'SitePress' ) && ! empty( $item_post_lang ) ) {
					global $sitepress;
					$current_lang = $sitepress->get_current_language(); // Take the current language to a variable to swicthback later.
					$default_language = $sitepress->get_default_language();
					$sitepress->switch_lang( $item_post_lang );
				}

				$products = wc_get_products( $args );

				$total_products = 0;
				if ( 0 == $batch_offset ) { // first batch.
					$total_item_args = $args;
					$total_item_args['limit'] = $export_limit; // user given limit.
					$total_item_args['offset'] = $current_offset; // user given offset.
					$total_products_count = wc_get_products( $total_item_args );
					$total_products = count( $total_products_count->products );
				}

				/*
				 * WPML - Swicth language back to the previous site language after the batch reading.
				 */
				if ( class_exists( 'SitePress' ) && ! empty( $item_post_lang ) ) {
					global $sitepress;
					$sitepress->switch_lang( $current_lang ); // Current language is previously stored.
				}

				$products_ids = $products->products;

				// If include category is selected and variable products are under those category, the variations will not be returned by the WC query.
				if ( ! empty( $prod_inc_categories ) ) {
					$temp_prod_ids = $products_ids;
					foreach ( $temp_prod_ids as $key => $product_id ) {
						$product = wc_get_product( $product_id );
						if ( $product->is_type( 'variable' ) ) {
							$variations = $product->get_available_variations();
							$variations_ids = wp_list_pluck( $variations, 'variation_id' );
							foreach ( $variations_ids as $variations_id ) {
								$products_ids[] = $variations_id;
							}
						}
					}
				}

				foreach ( $products_ids as $key => $product_id ) {
					$product = wc_get_product( $product_id );

					// Skip variations that belongs to a specific categories that is excluded in filter.
					if ( $product->is_type( 'variation' ) && ! empty( $prod_exc_categories ) ) {
						$parent_id = $product->get_parent_id();
						if ( has_term( $prod_exc_categories, 'product_cat', $parent_id ) ) {
							continue;
						}
					}

					if ( $product->is_type( 'variation' ) && ! empty( $item_parentonly ) ) {
						continue;
					}

					if ($product->is_type( 'variable' ) && ! empty( $item_parentonly ) ) {
						$default_variation_id = $this->get_default_variation( $product );
						if ( $default_variation_id ) {
							$product = wc_get_product( $default_variation_id );
						} else {
							continue;
						}
					}

					if ( $product->is_type( 'variation' ) && $filter_normal_variation_only) {
						$parent_id = $product->get_parent_id();
						if ( $parent_id ) {
							$parent = wc_get_product( $parent_id );
							if ( $parent && $parent->is_type( 'variable-subscription' ) ) {
								continue; // Exclude subscription variations
							}
						}
					}

					// Handle subscription variations if filter is enabled
					if ( $filter_subscription_variation_only && $product->is_type('variable-subscription') ) {
						// Loop through subscription variations
						foreach( $product->get_children() as $variation_id ) {
							// Get the WC_Product_Variation object
							$variation_product = wc_get_product( $variation_id );
							$this->process_product_for_export( $variation_product, $product_array );
						}
						continue;
					} 

					if ( $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' ) ) {
						continue;
					}

					$this->process_product_for_export( $product, $product_array );
					
				}
			}

			$return_products = array(
				'total' => $total_products,
				'data' => $product_array,
			);
			if ( 0 == $batch_offset && (0 == $total_products || empty($product_array)) ) {
				$return_products['no_post'] = __( 'Nothing to export under the selected criteria. Please try adjusting the filters.', 'product-feed-woocommerce' );
			}
			return $return_products;
		}

		/**
		 * Get default variation.
		 *
		 * @since 1.0.0
		 * @param object $product The product.
		 * @return int The variation ID.
		 */
		public function get_default_variation( $product ) {

			$variation_id = false;

			foreach ( $product->get_available_variations() as $variation_values ) {
				foreach ( $variation_values['attributes'] as $key => $attribute_value ) {
					$attribute_name = str_replace( 'attribute_', '', $key );
					$default_value = $product->get_variation_default_attribute( $attribute_name );
					if ( $default_value == $attribute_value ) {
						$is_default_variation = true;
					} else {
						$is_default_variation = false;
						break; // Stop this loop to start next main loop.
					}
				}

				if ( $is_default_variation ) {
					$variation_id = $variation_values['variation_id'];
					break; // Stop the main loop.
				}
			}
			return $variation_id;
		}

		/**
		 * Process product for export
		 *
		 * @param object $product Product object.
		 * @param array  $product_array Reference to product array.
		 */
		protected function process_product_for_export( $product, &$product_array ) {
			$this->parent_product = $product;
			$this->product = $product;
			$this->current_product_id = $product->get_id();
			$product_array[] = $this->generate_row_data_wc_lower( $product );
		}
		
		/**
		 * Generate row data.
		 *
		 * @since 1.0.0
		 * @param object $product_object The product object.
		 * @return array The row data.
		 */
		protected function generate_row_data_wc_lower( $product_object ) {

			$export_columns = $this->parent_module->get_selected_column_names();

			$product_id = $product_object->get_id();

			$csv_columns = $export_columns;

			$export_columns = ! empty( $csv_columns ) ? $csv_columns : array();

			$row = array();

			foreach ( $export_columns as $key => $value ) {
				if ( method_exists( $this, $value ) ) {
					$row[ $key ] = $this->$value( $key, $value, $export_columns );
				} elseif ( strpos( $value, 'meta:' ) !== false ) {
					$mkey = str_replace( 'meta:', '', $value );
					if ( $product_object->is_type( 'variation' ) ) {
						$product_id = $product_object->get_parent_id();
					}
					if ( ( strpos( $value, 'global_unique_id' ) !== false ) || ( strpos( $value, 'alg_ean' ) !== false ) ) {
						$product_id = $product_object->get_id();
					}
					$row[ $key ] = wp_strip_all_tags( get_post_meta( $product_id, $mkey, true ) );
					// TODO: wt_image_ function can be replaced with key exist check.
				} elseif ( strpos( $value, 'wt_pf_pa_' ) !== false ) {
					$atr_key = str_replace( 'wt_pf_pa_', '', $value );
					if ( $product_object->is_type( 'variation' ) ) {
						$product_object = wc_get_product( $product_object->get_parent_id() );
					}
					$value = '';
					if ( is_object( $product_object ) ) {
						$value = $product_object->get_attribute( $atr_key );
					}
					if ( ! empty( $value ) ) {
						$value = trim( $value );
					}
					$row[ $key ] = $value;
				} elseif ( strpos( $value, 'wt_pf_cattr_' ) !== false ) {
					$atr_key = str_replace( 'wt_pf_cattr_', '', $value );
					if ( $product_object->is_type( 'variation' ) ) {
						$product_object = wc_get_product( $product_object->get_parent_id() );
					}
					$value = '';
					if ( is_object( $product_object ) ) {
						$value = $product_object->get_attribute( $atr_key );

					}
					if ( ! empty( $value ) ) {
						$value = trim( $value );
								$value = str_replace( '|', ',', $value );
					}
					$row[ $key ] = $value;
				} elseif ( strpos( $value, 'wt_static_map_vl:' ) !== false ) { // Static value.
					$static_feed_value = str_replace( 'wt_static_map_vl:', '', $value );
					$row[ $key ] = $static_feed_value;
				} else {
					$row[ $key ] = '';
				}
			}
			/**
			 * Filter the row data.
			 *
			 * @since 1.0.0
			 * @param array $row The row data.
			 * @return array The row data.
			 */
			return apply_filters( "wt_batch_product_export_row_data_{$this->parent_module->module_base}", $row, $product_object );
		}

		/**
		 * Get product id.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function id( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product id.
			 *
			 * @since 1.0.0
			 * @param int $product_id The product id.
			 * @return int The product id.
			 */
			return apply_filters( 'wt_feed_filter_product_id', $this->product->get_id(), $this->product );
		}

		/**
		 * Get product sku.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function sku( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product sku.
			 *
			 * @since 1.0.0
			 * @param string $sku The product sku.
			 * @return string The product sku.
			 */
			return apply_filters( 'wt_feed_filter_product_sku', $this->product->get_sku(), $this->product );
		}

		/**
		 * Get store name.
		 *
		 * @since 1.0.0
		 * @return string The store name.
		 */
		public static function get_store_name() {
			$url = get_bloginfo( 'name' );
			return ( $url ) ? ( $url ) : 'My Store';
		}

		/**
		 * Get product name.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
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
				$separator = ',';

				$variation_attributes = implode( $separator, $attributes );

				// get product title with variation attribute.
				/**
				 * Filter the product title with variation attribute.
				 *
				 * @since 1.0.0
				 * @param bool $get_with_var_attributes The product title with variation attribute.
				 * @return bool The product title with variation attribute.
				 */
				$get_with_var_attributes = apply_filters( 'wt_feed_get_product_title_with_variation_attribute', true, $this->product );

				if ( $get_with_var_attributes ) {
					$title .= ' - ' . $variation_attributes;
				}
			}

			/**
			 * Filter the product title.
			 *
			 * @since 1.0.0
			 * @param string $title The product title.
			 * @return string The product title.
			 */
			return apply_filters( 'wt_feed_filter_product_title', $title, $this->product );
		}

		/**
		 * Get parent product title for variation.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function parent_title( $catalog_attr, $product_attr, $export_columns ) {
			$title = '';
			if ( $this->product->is_type( 'variation' ) ) {
				$parent_product = wc_get_product( $this->product->get_parent_id() );
				if ( is_object( $parent_product ) ) {
					$title = $parent_product->get_name();
				}
			} else {
				$title = $this->product->get_name();
			}

			/**
			 * Filter the product parent title.
			 *
			 * @since 1.0.0
			 * @param string $title The product parent title.
			 * @return string The product parent title.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_title', $title, $this->product );
		}


		/**
		 * Get product type.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function type( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			/**
			 * Filter the product type separator.
			 *
			 * @since 1.0.0
			 * @param string $separator The product type separator.
			 * @return string The product type separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$product_categories = '';
			$term_list = get_the_terms( $id, 'product_cat' );

			if ( is_array( $term_list ) ) {
				$col = array_column( $term_list, 'term_id' );
				array_multisort( $col, SORT_ASC, $term_list );
				$term_list = array_column( $term_list, 'name' );
				$product_categories = implode( ' > ', $term_list );
			}

			/**
			 * Filter the product local category.
			 *
			 * @since 1.0.0
			 * @param string $product_categories The product local category.
			 * @return string The product local category.
			 */
			return apply_filters( 'wt_feed_filter_product_local_category', $product_categories, $this->product );
		}

		/**
		 * Get product image URL.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function image_link( $catalog_attr, $product_attr, $export_columns ) {
			$image = '';
			if ( $this->product->is_type( 'variation' ) ) {
				// Variation product type.
				if ( has_post_thumbnail( $this->product->get_id() ) ) {
					$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_id() ), 'single-post-thumbnail' );
					$image = self::wt_feed_get_formatted_url( $get_image[0] );
				} elseif ( has_post_thumbnail( $this->product->get_parent_id() ) ) {
					$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_parent_id() ), 'single-post-thumbnail' );
					if ( is_array( $get_image ) ) {
						$image = self::wt_feed_get_formatted_url( $get_image[0] );
					}
				}
			} elseif ( has_post_thumbnail( $this->product->get_id() ) ) { // All product type except variation.
				$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_id() ), 'single-post-thumbnail' );
				$image = isset( $get_image[0] ) ? self::wt_feed_get_formatted_url( $get_image[0] ) : '';
			}
			if ( '' === $image ) {
				$image = 'https://via.placeholder.com/300';
			}
			/**
			 * Filter the product image.
			 *
			 * @since 1.0.0
			 * @param string $image The product image.
			 * @return string The product image.
			 */
			return apply_filters( 'wt_feed_filter_product_image', $image, $this->product );
		}

		/**
		 * Get Formatted URL
		 *
		 * @since 1.0.0
		 * @param string $url The URL.
		 * @return string The formatted URL.
		 */
		public static function wt_feed_get_formatted_url( $url = '' ) {
			if ( ! empty( $url ) ) {
				if ( substr( trim( $url ), 0, 4 ) === 'http' || substr(
					trim( $url ),
					0,
					3
				) === 'ftp' || substr( trim( $url ), 0, 4 ) === 'sftp' ) {
					return rtrim( $url, '/' );
				} else {
					$base = get_site_url();
					$url  = $base . $url;

					return rtrim( $url, '/' );
				}
			}

			return '';
		}

		/**
		 * Get product gallery.
		 *
		 * @since 1.0.0
		 * @param object $product The product.
		 * @return array The product gallery.
		 */
		public static function get_product_gallery( $product ) {
			$img_urls = array();
			$attachment_ids = array();

			if ( $product->is_type( 'variation' ) ) {

				/**
				 * If any Variation Gallery Image plugin not installed then get Variable Product Additional Image Ids .
				 */
				$parent_prod = wc_get_product( $product->get_parent_id() );
				if ( is_object( $parent_prod ) ) {
					$attachment_ids = $parent_prod->get_gallery_image_ids();
				}
			}

			/**
			 * Get Variable Product Gallery Image ids if Product is not a variation
			 * or variation does not have any gallery images
			 */
			if ( empty( $attachment_ids ) ) {
				$attachment_ids = $product->get_gallery_image_ids();
			}

			if ( $attachment_ids && is_array( $attachment_ids ) ) {
				$m_key = 1;
				foreach ( $attachment_ids as $attachment_id ) {
					$img_urls[ $m_key ] = self::wt_feed_get_formatted_url( wp_get_attachment_url( $attachment_id ) );
					$m_key++;
				}
			}

			return $img_urls;
		}


		/**
		 * Get product images (comma separated URLs).
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @param string $additional_img The additional image.
		 * @return mixed|void
		 */
		public function images( $catalog_attr, $product_attr, $export_columns, $additional_img = '' ) {
			$img_urls = self::get_product_gallery( $this->product );
			/**
			 * Filter the product images separator.
			 *
			 * @since 1.0.0
			 * @param string $separator The product images separator.
			 * @return string The product images separator.
			 */
			$separator = apply_filters( 'wt_feed_filter_category_separator', ' > ', $this->product );

			// Return Specific Additional Image URL.
			if ( '' !== $additional_img ) {
				if ( array_key_exists( $additional_img, $img_urls ) ) {
					$images = $img_urls[ $additional_img ];
				} else {
					$images = '';
				}
			} else {

				$images = implode( $separator, array_filter( $img_urls ) );
			}
			/**
			 * Filter the product images.
			 *
			 * @since 1.0.0
			 * @param string $images The product images.
			 * @return string The product images
			 */
			return apply_filters( 'wt_feed_filter_product_images', $images, $this->product );
		}

		/**
		 * Get product images 1.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_1( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 1 );
		}

		/**
		 * Get product images 2.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_2( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 2 );
		}

		/**
		 * Get product images 3.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_4( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 4 );
		}

		/**
		 * Get product images 5.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_5( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 5 );
		}

		/**
		 * Get product images 6.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_6( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 6 );
		}

		/**
		 * Get product images 7.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_7( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 7 );
		}

		/**
		 * Get product images 8.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_8( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 8 );
		}

		/**
		 * Get product images 9.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_9( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 9 );
		}

		/**
		 * Get product images 10.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function wtimages_10( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 10 );
		}

		/**
		 * Get product condition.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function condition( $catalog_attr, $product_attr, $export_columns ) {

			$custom_condition = get_post_meta( $this->product->get_id(), '_wt_feed_condition', true );

			if ( '' == $custom_condition ) {
				$custom_condition = get_post_meta( $this->product->get_id(), '_wt_google_condition', true );
			}

			$condition = ( '' == $custom_condition ) ? 'new' : $custom_condition;
			/**
			 * Filter the product condition.
			 *
			 * @since 1.0.0
			 * @param string $condition The product condition.
			 * @return string The product condition.
			 */
			return apply_filters( 'wt_feed_product_condition', $condition, $this->product );
		}

		/**
		 * Get product MPN.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function mpn( $catalog_attr, $product_attr, $export_columns ) {

			$custom_mpn = get_post_meta( $this->product->get_id(), '_wt_feed_mpn', true );

			if ( '' == $custom_mpn ) {
				$custom_mpn = get_post_meta( $this->product->get_id(), '_wt_google_mpn', true );
			}

			$mpn = ( '' == $custom_mpn ) ? '' : $custom_mpn;
			/**
			 * Filter the product MPN.
			 *
			 * @since 1.0.0
			 * @param string $mpn The product MPN.
			 * @return string The product MPN.
			 */
			return apply_filters( 'wt_feed_product_mpn', $mpn, $this->product );
		}

		/**
		 * Get product brand.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function brand( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
			$custom_brand = get_post_meta( $this->current_product_id, '_wt_feed_brand', true );
			if ( '' == $custom_brand ) {
				$custom_brand = get_post_meta( $this->product->get_id(), '_wt_google_brand', true );
			}
			if ( '' == $custom_brand ) {

				$brand   = get_the_term_list( $id, 'product_brand', '', ', ' );

				$has_brand = true;
				if ( is_wp_error( $brand ) || false === $brand ) {
					$has_brand = false;
				}

				if ( ! $has_brand && is_plugin_active( 'perfect-woocommerce-brands/perfect-woocommerce-brands.php' ) ) {
					$brand   = get_the_term_list( $id, 'pwb-brand', '', ', ' );
				}
				if ( ! $has_brand && ( is_plugin_active( 'yith-woocommerce-brands-add-on/init.php' ) || is_plugin_active( 'yith-woocommerce-brands-add-on-premium/init.php' ) ) ) {
					$brand   = get_the_term_list( $id, 'yith_product_brand', '', ', ' );
				}
				$string  = is_wp_error( $brand ) || ! $brand ? wp_strip_all_tags( self::get_store_name() ) : self::clean_string( $brand );
				$length = 100;
				if ( extension_loaded( 'mbstring' ) ) {

					if ( mb_strlen( $string, 'UTF-8' ) <= $length ) {
						/**
						 * Filter the product brand.
						 *
						 * @since 1.0.0
						 * @param string $string The product brand.
						 * @return string The product brand.
						 */
						return apply_filters( 'wt_feed_filter_product_brand', $string, $this->product );
					}

					$length -= mb_strlen( '...', 'UTF-8' );

					$brand_string = mb_substr( $string, 0, $length, 'UTF-8' ) . '...';
					/**
					 * Filter the product brand.
					 *
					 * @since 1.0.0
					 * @param string $brand_string The product brand.
					 * @return string The product brand.
					 */
					return apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
				} else {

					$string  = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );
					$string  = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

					if ( strlen( $string ) <= $length ) {
						/**
						 * Filter the product brand.
						 *
						 * @since 1.0.0
						 * @param string $string The product brand.
						 * @return string The product brand.
						 */
						return apply_filters( 'wt_feed_filter_product_brand', $string, $this->product );
					}

					$length -= strlen( '...' );

					$brand_string = substr( $string, 0, $length ) . '...';
					/**
					 * Filter the product brand.
					 *
					 * @since 1.0.0
					 * @param string $brand_string The product brand.
					 * @return string The product brand.
					 */
					return apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
				}
			} else {
				/**
				 * Filter the product brand.
				 *
				 * @since 1.0.0
				 * @param string $custom_brand The product brand.
				 * @return string The product brand.
				 */
				return apply_filters( 'wt_feed_filter_product_brand', $custom_brand, $this->product );
			}
		}

		/**
		 * Get product availability.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function availability( $catalog_attr, $product_attr, $export_columns ) {
			$status = $this->product->get_stock_status();
			if ( 'instock' === $status ) {
				$status = 'in_stock';
			} elseif ( 'outofstock' === $status ) {
				$status = 'out_of_stock';
			} elseif ( 'onbackorder' === $status ) {
				$status = 'backorder';
			} elseif ( 'preorder' === $status ) {
				$status = 'preorder';
			}

			/**
			 * Filter the product availability.
			 *
			 * @since 1.0.0
			 * @param string $status The product availability.
			 * @return string The product availability.
			 */
			return apply_filters( 'wt_feed_filter_product_availability', $status, $this->product );
		}


		/**
		 * Get product availability date.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function availability_date( $catalog_attr, $product_attr, $export_columns ) {

			$availability_date = get_post_meta( $this->product->get_id(), '_wt_feed_availability_date', true );

			if ( $availability_date ) {
				$availability_date = gmdate( 'c', strtotime( $availability_date ) );
			}

			/**
			 * Filter the product availability date.
			 *
			 * @since 1.0.0
			 * @param string $availability_date The product availability date.
			 * @return string The product availability date.
			 */
			return apply_filters( 'wt_feed_filter_product_availability_date', $availability_date, $this->product );
		}

		/**
		 * Get product quantity.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function quantity( $catalog_attr, $product_attr, $export_columns ) {
			$quantity = $this->product->get_stock_quantity();
			$status = $this->product->get_stock_status();

			// When product is outofstock , and it's quantity is empty, set quantity to 0.
			if ( 'outofstock' === $status && null === $quantity ) {
				$quantity = 0;
			}

			if ( $this->product->is_type( 'variable' ) && $this->product->has_child() ) {
				$visible_children = $this->product->get_visible_children();
				$qty = array();
				foreach ( $visible_children as $child ) {
					$child_qty = get_post_meta( $child, '_stock', true );
					$qty[] = (int) $child_qty;
				}

				$quantity = array_sum( $qty );
			}
			if ( $this->product->is_type( 'variation' ) ) {
				$parent_variations_qty = ! empty( $this->form_data['post_type_form_data']['wt_pf_parent_qty'] ) ? $this->form_data['post_type_form_data']['wt_pf_parent_qty'] : '';
				if ( 'sumof_variation_qty' == $parent_variations_qty ) {
					$parent_product = wc_get_product( $this->product->get_parent_id() );
					$visible_children = $parent_product->get_visible_children();
					$qty = array();
					foreach ( $visible_children as $child ) {
						$child_qty = get_post_meta( $child, '_stock', true );
						$qty[] = (int) $child_qty;
					}
					$quantity = array_sum( $qty );
				}
			}
			/**
			 * Filter the product quantity.
			 *
			 * @since 1.0.0
			 * @param int $quantity The product quantity.
			 * @return int The product quantity.
			 */
			return apply_filters( 'wt_feed_filter_product_quantity', $quantity, $this->product );
		}

		/**
		 * Get Product Sale Price start date.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function sale_price_sdate( $catalog_attr, $product_attr, $export_columns ) {
			$start_date = $this->product->get_date_on_sale_from();
			if ( is_object( $start_date ) ) {
				$sale_price_sdate = $start_date->date_i18n();
			} else {
				$sale_price_sdate = '';
			}
			/**
			 * Filter the product sale price start date.
			 *
			 * @since 1.0.0
			 * @param string $sale_price_sdate The product sale price start date.
			 * @return string The product sale price start date.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product );
		}

		/**
		 * Get Product Sale Price End Date.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function sale_price_edate( $catalog_attr, $product_attr, $export_columns ) {
			$end_date = $this->product->get_date_on_sale_to();
			if ( is_object( $end_date ) ) {
				$sale_price_edate = $end_date->date_i18n();
			} else {
				$sale_price_edate = '';
			}
			/**
			 * Filter the product sale price end date.
			 *
			 * @since 1.0.0
			 * @param string $sale_price_edate The product sale price end date.
			 * @return string The product sale price end date.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product );
		}

		/**
		 * Get first variation price.
		 *
		 * @since 1.0.0
		 * @return mixed|void
		 */
		public function first_variation_price() {

			$children = $this->product->get_visible_children();
			$price = $this->product->get_variation_price();
			if ( isset( $children[0] ) && ! empty( $children[0] ) ) {
				$variation = wc_get_product( $children[0] );
				$price = $variation->get_price();
			}
			/**
			 * Filter the product first variation price.
			 *
			 * @since 1.0.0
			 * @param float $price The product first variation price.
			 * @return float The product first variation price.
			 */
			return apply_filters( 'wt_feed_filter_product_first_variation_price', $price, $this->product );
		}

		/**
		 * Get product current price.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function current_price( $catalog_attr, $product_attr, $export_columns ) {
			$price = $this->product->get_price();

			$selected_currency = get_woocommerce_currency();
			$selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price( $price, $selected_currency, $selected_country, $this->product );
				}
			}

			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.
				// $price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);.

				$price = wc_format_decimal( $price, 2 );
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product current price.
			 *
			 * @since 1.0.0
			 * @param string $price The product current price.
			 * @return string The product current price.
			 */
			$price = apply_filters( 'wt_feed_filter_product_current_price', $price, $this->product );
			/**
			 * Filter the product current price.
			 *
			 * @since 1.0.0
			 * @param string $price The product current price.
			 * @return string The product current price.
			 */
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_current_price", $price, $this->product, $this->form_data );
		}

		/**
		 * Get product price with tax.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function price_with_tax( $catalog_attr, $product_attr, $export_columns ) {

			$tprice = $this->product->get_regular_price();
			$price = wc_get_price_including_tax( $this->product, array( 'price' => $tprice ) );
			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.
				// $price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);.

				$price = $price . ' ' . get_woocommerce_currency();
			}
			/**
			 * Filter the product price with tax.
			 *
			 * @since 1.0.0
			 * @param string $price The product price with tax.
			 * @return string The product price with tax.
			 */
			return apply_filters( 'wt_feed_filter_product_price_with_tax', $price, $this->product );
		}

		/**
		 * Get product current price with tax.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function current_price_with_tax( $catalog_attr, $product_attr, $export_columns ) {
			$cprice = $this->product->get_price();
			$price = wc_get_price_including_tax( $this->product, array( 'price' => $cprice ) );
			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.
				// $price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);.

				$price = $price . ' ' . get_woocommerce_currency();
			}
			/**
			 * Filter the product current price with tax.
			 *
			 * @since 1.0.0
			 * @param string $price The product current price with tax.
			 * @return string The product current price with tax.
			 */
			return apply_filters( 'wt_feed_filter_product_current_price_with_tax', $price, $this->product );
		}

		/**
		 * Get product sale price with tax.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function sale_price_with_tax( $catalog_attr, $product_attr, $export_columns ) {
			$sprice = $this->product->get_sale_price();
			if ( $sprice ) {
				$price = wc_get_price_including_tax( $this->product, array( 'price' => $sprice ) );
			}
			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.
				// $price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);.

				$price = $price . ' ' . get_woocommerce_currency();
			}
			/**
			 * Filter the product sale price with tax.
			 *
			 * @since 1.0.0
			 * @param string $price The product sale price with tax.
			 * @return string The product sale price with tax.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_with_tax', $price, $this->product );
		}

		/**
		 * Get Google Sale Price effective date.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function sale_price_effective_date( $catalog_attr, $product_attr, $export_columns ) {
			$effective_date = '';
			$from = $this->sale_price_sdate( $catalog_attr, $product_attr, $export_columns );
			$to = $this->sale_price_edate( $catalog_attr, $product_attr, $export_columns );
			if ( ! empty( $from ) && ! empty( $to ) ) {
				$from = gmdate( 'c', strtotime( $from ) );
				$to = gmdate( 'c', strtotime( $to ) );

				$effective_date = $from . '/' . $to;
			}

			return $effective_date;
		}
		/**
		 * Clean up strings for FB Graph POSTing.
		 * This function should will:
		 * 1. Replace newlines chars/nbsp with a real space
		 * 2. strip_tags()
		 * 3. trim()
		 *
		 * @since 1.0.0
		 *
		 * @param String $string The string to clean.
		 * @return string The cleaned string.
		 */
		public static function clean_string( $string ) {
			$string = do_shortcode( $string );
			$string = str_replace( array( '&amp%3B', '&amp;' ), '&', $string );
			$string = str_replace( array( "\r", '&nbsp;', "\t" ), ' ', $string );
			$string = wp_strip_all_tags( $string, false ); // true == remove line breaks.
			return $string;
		}

		/**
		 * Get product item group id.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function item_group_id( $catalog_attr, $product_attr, $export_columns ) {
			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			/**
			 * Filter the product item group id.
			 *
			 * @since 1.0.0
			 * @param string $id The product item group id.
			 * @return string The product item group id.
			 */
			return apply_filters( 'wt_feed_filter_product_item_group_id', $id, $this->product );
		}

		/**
		 * Get product number of ratings.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function number_of_ratings( $catalog_attr, $product_attr, $export_columns ) {
			$rating_count = $this->product->get_rating_counts();
			/**
			 * Filter the product number of ratings.
			 *
			 * @since 1.0.0
			 * @param string $rating_count The product number of ratings.
			 * @return string The product number of ratings.
			 */
			return apply_filters( 'wt_feed_product_number_of_ratings', $rating_count, $this->product );
		}

		/**
		 * Get product average review rating.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function average_review_rating( $catalog_attr, $product_attr, $export_columns ) {
			$average_review_rating = wc_format_decimal( $this->product->get_average_rating(), 2 );
			/**
			 * Filter the product average review rating.
			 *
			 * @since 1.0.0
			 * @param string $average_review_rating The product average review rating.
			 * @return string The product average review rating.
			 */
			return apply_filters( 'wt_feed_product_average_review_rating', $average_review_rating, $this->product );
		}

		/**
		 * Get product number of reviews.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function number_of_reviews( $catalog_attr, $product_attr, $export_columns ) {
			$review_count = $this->product->get_review_count();
			/**
			 * Filter the product number of reviews.
			 *
			 * @since 1.0.0
			 * @param string $review_count The product number of reviews.
			 * @return string The product number of reviews.
			 */
			return apply_filters( 'wt_feed_product_number_of_reviews', $review_count, $this->product );
		}

		/**
		 * Get Product Weight.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function weight( $catalog_attr, $product_attr, $export_columns ) {
			$weight = $this->product->get_weight();
			/**
			 * Filter the product weight.
			 *
			 * @since 1.0.0
			 * @param string $weight The product weight.
			 * @return string The product weight.
			 */
			return apply_filters( 'wt_feed_filter_product_weight', $weight, $this->product );
		}

		/**
		 * Get Weight Unit.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function weight_unit( $catalog_attr, $product_attr, $export_columns ) {
			$weight_unit = get_option( 'woocommerce_weight_unit' );
			/**
			 * Filter the product weight unit.
			 *
			 * @since 1.0.0
			 * @param string $weight_unit The product weight unit.
			 * @return string The product weight unit.
			 */
			return apply_filters( 'wt_feed_filter_product_weight_unit', $weight_unit, $this->product );
		}
		/**
		 * Get Product Width.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function width( $catalog_attr, $product_attr, $export_columns ) {
			$width = $this->product->get_width();
			/**
			 * Filter the product width.
			 *
			 * @since 1.0.0
			 * @param string $width The product width.
			 * @return string The product width.
			 */
			return apply_filters( 'wt_feed_filter_product_width', $width, $this->product );
		}

		/**
		 * Get Product Height.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function height( $catalog_attr, $product_attr, $export_columns ) {
			$height = $this->product->get_height();
			/**
			 * Filter the product height.
			 *
			 * @since 1.0.0
			 * @param string $height The product height.
			 * @return string The product height.
			 */
			return apply_filters( 'wt_feed_filter_product_height', $height, $this->product );
		}
		/**
		 * Get Product Length.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return mixed|void
		 */
		public function length( $catalog_attr, $product_attr, $export_columns ) {
			$length = $this->product->get_length();
			/**
			 * Filter the product length.
			 *
			 * @since 1.0.0
			 * @param string $length The product length.
			 * @return string The product length.
			 */
			return apply_filters( 'wt_feed_filter_product_length', $length, $this->product );
		}


		/**
		 * Google Formatted Shipping info
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @param string $key The key.
		 * @return string
		 */
		public function shipping( $catalog_attr, $product_attr, $export_columns, $key = '' ) {

			$shipping_details = $this->get_shipping();
			$shipping_details_xml = array();
			$shipping_str = '';
			if ( isset( $shipping_details ) && is_array( $shipping_details ) ) {
				foreach ( $shipping_details as $k => $shipping_item ) {

					unset( $shipping_details['zone_name'] );

					if ( isset( $shipping_item['region'] ) && empty( $shipping_item['region'] ) ) {
						unset( $shipping_item['region'] );
					}

					$shipping_child = '';
					foreach ( $shipping_item as $shipping_item_attr => $shipping_value ) {

						if ( 'price' === $shipping_item_attr ) {
							$shipping_value = number_format( $shipping_value, 2 ) . ' ' . get_woocommerce_currency();
						}
						if ( ( 'zone_name' !== $shipping_item_attr ) ) {
							$shipping_details_xml[ $k ][ $shipping_item_attr ] = $shipping_value;
						}
						if ( 'postal_code' !== $shipping_item_attr ) {
							$shipping_child .= $shipping_value . ':';
						}
					}
					$shipping_child = trim( $shipping_child, ':' );

					// Add separator for multiple shipping method -  comma for google.
					$shipping_str .= $shipping_child . ',';
				}

				$shipping_str = trim( $shipping_str, ',' );
			}

			if ( isset( $this->form_data['advanced_form_data']['wt_pf_file_as'] ) && 'xml' === $this->form_data['advanced_form_data']['wt_pf_file_as'] ) {
				/**
				 * Filter the Google product shipping XML.
				 *
				 * @since 1.0.0
				 * @param string $shipping_details_xml The Google product shipping XML.
				 * @return string The Google product shipping XML.
				 */
				return apply_filters( 'wt_feed_google_product_shipping_xml', $shipping_details_xml, $shipping_details, $this->product );
			}

			/**
			 * Filter the Google product shipping.
			 *
			 * @since 1.0.0
			 * @param string $shipping_str The Google product shipping.
			 * @return string The Google product shipping.
			 */
			return apply_filters( 'wt_feed_google_product_shipping', $shipping_str, $shipping_details, $this->product );
		}

		/**
		 * Get shipping.
		 *
		 * @since 1.0.0
		 * @return mixed|void
		 */
		public function get_shipping() {
			$shpping_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			$shipping_obj = new Webtoffee_Product_Feed_Shipping( $this->product, 'google', $this->form_data );
			$shipping_info = $shipping_obj->get_shipping_by_location( $shpping_country );
			/**
			 * Filter the processed shipping infos.
			 *
			 * @since 1.0.0
			 * @param string $shipping_info The processed shipping infos.
			 * @return string The processed shipping infos.
			 */
			return apply_filters( 'wt_feed_processed_shipping_infos', $shipping_info, $this->product );
		}

		/**
		 * Get shipping data.
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function shipping_data( $catalog_attr, $product_attr, $export_columns ) {

			return $this->shipping( $catalog_attr, $product_attr, $export_columns );
		}
		/**
		 * Get Product Shipping Class
		 *
		 * @since 1.0.0
		 * @param string $catalog_attr The catalog attribute.
		 * @param string $product_attr The product attribute.
		 * @param array  $export_columns The export columns.
		 * @return string
		 */
		public function shipping_class( $catalog_attr, $product_attr, $export_columns ) {
			$shipping_class = $this->product->get_shipping_class();
			/**
			 * Filter the product shipping class.
			 *
			 * @since 1.0.0
			 * @param string $shipping_class The product shipping class.
			 * @return string The product shipping class.
			 */
			return apply_filters( 'wt_feed_filter_product_shipping_class', $shipping_class, $this->product );
		}
	}

}
