<?php
/**
 * Product section of the plugin
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Idealo_Export
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Idealo_Export' ) ) {

	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Idealo_Export Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Idealo_Export extends Product_Feed_For_Woocommerce_Product {

		/**
		 * Parent module object.
		 *
		 * @var null
		 */
		public $parent_module = null;

		/**
		 * Product object.
		 *
		 * @var null
		 */
		public $product;

		/**
		 * Current product ID
		 *
		 * @var null
		 */
		public $current_product_id;

		/**
		 * Form data
		 *
		 * @var null
		 */
		public $form_data;

		/**
		 * Constructor
		 *
		 * @param object $parent_object Parent object.
		 * @since 1.0.0
		 */
		public function __construct( $parent_object ) {

			$this->parent_module = $parent_object;
		}

		/**
		 * Prepare header
		 *
		 * @since 1.0.0
		 */
		public function prepare_header() {

			$export_columns = $this->parent_module->get_selected_column_names();
			/**
			 * Filter the product feed CSV columns.
			 *
			 * @since 1.0.0
			 *
			 * @param array $export_columns Export columns.
			 */
			return apply_filters( 'wt_pf_alter_product_feed_csv_columns', $export_columns );
		}

		/**
		 * Prepare data that will be exported.
		 *
		 * @param array $form_data Form data.
		 * @param int   $batch_offset Batch offset.
		 * @param int   $step Step.
		 * @since 1.0.0
		 */
		public function prepare_data_to_export( $form_data, $batch_offset, $step ) {

			$this->form_data = $form_data;

			$exc_stock_status = ! empty( $form_data['post_type_form_data']['item_outofstock'] ) ? $form_data['post_type_form_data']['item_outofstock'] : '';
			
			if('' === $exc_stock_status){
                $exc_stock_status = !empty($form_data['post_type_form_data']['wt_pf_exclude_outofstock']) ? $form_data['post_type_form_data']['wt_pf_exclude_outofstock'] : '';
            }

			$item_parentonly = ! empty( $form_data['post_type_form_data']['item_parentonly'] ) ? $form_data['post_type_form_data']['item_parentonly'] : '';

			if ( '' === $item_parentonly ) {
				$item_parentonly = ! empty( $form_data['post_type_form_data']['wt_pf_include_parent_only'] ) ? $form_data['post_type_form_data']['wt_pf_include_parent_only'] : '';
			}
            
            $prod_exc_categories = !empty($form_data['post_type_form_data']['item_exc_cat']) ? $form_data['post_type_form_data']['item_exc_cat'] : array();            
            $prod_inc_categories = !empty($form_data['post_type_form_data']['item_inc_cat']) ? $form_data['post_type_form_data']['item_inc_cat'] : array();

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

			$prod_exc = ! empty( $form_data['post_type_form_data']['item_exc_prd'] ) ? $form_data['post_type_form_data']['item_exc_prd'] : array();

			if ( empty( $prod_exc ) ) {
				$prod_exc = ! empty( $form_data['post_type_form_data']['wt_pf_exclude_products'] ) ? $form_data['post_type_form_data']['wt_pf_exclude_products'] : array();
			}
			/*
			 * WPML
			 *
			 */
			$item_post_lang = ! empty( $form_data['post_type_form_data']['item_post_lang'] ) ? $form_data['post_type_form_data']['item_post_lang'] : '';

			$prod_tags = ! empty( $form_data['filter_form_data']['wt_pf_product_tags'] ) ? $form_data['filter_form_data']['wt_pf_product_tags'] : array();

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
			 *
			 * @param int $batch_count Batch count.
			 */
			$batch_count = apply_filters( 'wt_woocommerce_csv_export_limit_per_request', $batch_count ); // ajax batch limit.

			$real_offset = ( $current_offset + $batch_offset );

			if ( $batch_count <= $export_limit ) {
				if ( ( $batch_offset + $batch_count ) > $export_limit ) {
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
					'order' => $export_sort_order,  // 'return' => 'objects',
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

				if ( isset( $args['type'] ) && in_array( 'variation', $args['type'] ) && !in_array('variable', $args['type']) && !empty($item_parentonly)) {
					array_push( $args['type'], 'variable' );
				}

				if ( '' !== $wt_pf_export_post_author ) {
					$args['author'] = is_array( $wt_pf_export_post_author ) ? $wt_pf_export_post_author : implode( ',', $wt_pf_export_post_author );
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
					$args['exclude'] = $prod_exc;// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				}

				if ( ! empty( $exc_stock_status ) ) {
					$available_status = wc_get_product_stock_status_options();
					unset( $available_status['outofstock'] );
					$args['stock_status'] = array_keys( $available_status );
				}

				$args['exclude_discarded'] = '_wt_feed_discard'; // To exclude individual excluded from product fetching.

				/**
				 * Filter the query arguments for a request.
				 *
				 * @since 1.0.0
				 *
				 * @param array $args Query arguments.
				 */
				$args = apply_filters( 'wt_feed_product_catalog_args', $args );

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
				if ( 0 == $batch_offset ) {
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

				if ( 'export_image' == $step ) {
					$products_ids = $products;
				} else {
					$products_ids = $products->products;
				}

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

					// Skip variations that belong to excluded categories
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
			if ( 0 === $batch_offset && ( 0 === $total_products || empty( $product_array ) ) ) {
				$return_products['no_post'] = __(
					'Nothing to export under the selected criteria. Please try adjusting the filters.', 'product-feed-woocommerce'
				);
			}
			return $return_products;
		}

		/**
		 * Get default variation
		 *
		 * @param object $product Product.
		 * @return int
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
						break; // Stop this loop to start next main lopp.
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
		 * Generate row data
		 *
		 * @param object $product_object Product object.
		 * @return array
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
			 * Filter the batch product export row data.
			 *
			 * @since 1.0.0
			 *
			 * @param array $row Row data.
			 */
			return apply_filters( "wt_batch_product_export_row_data_{$this->parent_module->module_base}", $row, $product_object );
		}

		/**
		 * Get product id.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function id( $catalog_attr, $product_attr, $export_columns ) {
			$product_id = $this->product->get_id();
			/**
			 * Filter the product id.
			 *
			 * @since 1.0.0
			 *
			 * @param int $product_id Product id.
			 */
			return apply_filters( 'wt_feed_filter_product_id', $product_id, $this->product );
		}


		/**
		 * Get parent product title for variation.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $title Product parent title.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_title', $title, $this->product );
		}

		/**
		 * Get product description with HTML tags.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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

			// Remove spacial characters.
			$description = wp_check_invalid_utf8( wp_specialchars_decode( $description ), true );
			/**
			 * Filter the product description with HTML.
			 *
			 * @since 1.0.0
			 *
			 * @param string $description Product description with HTML.
			 */
			return apply_filters( 'wt_feed_filter_product_description_with_html', $description, $this->product );
		}

		/**
		 * Get product primary category.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function primary_category( $catalog_attr, $product_attr, $export_columns ) {
			$parent_category = '';
			/**
			 * Filter the product type separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product type separator.
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
			 */
			return apply_filters( 'wt_feed_filter_product_primary_category', $parent_category, $this->product );
		}

		/**
		 * Get product primary category id.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function primary_category_id( $catalog_attr, $product_attr, $export_columns ) {
			$parent_category_id = '';
			/**
			 * Filter the product type separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product type separator.
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
			 */
			return apply_filters( 'wt_feed_filter_product_primary_category_id', $parent_category_id, $this->product );
		}

		/**
		 * Get product child category.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function child_category( $catalog_attr, $product_attr, $export_columns ) {
			$child_category = '';
			/**
			 * Filter the product type separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product type separator.
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
			 */
			return apply_filters( 'wt_feed_filter_product_child_category', $child_category, $this->product );
		}

		/**
		 * Get shipping.
		 *
		 * @return mixed|void
		 */
		public function get_shipping() {

			$shpping_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			$shipping_obj = new Webtoffee_Product_Feed_Shipping( $this->product, 'google', $this->form_data );
			$shipping_info = $shipping_obj->get_shipping_by_location( $shpping_country );
			/**
			 * Filter the shipping info.
			 *
			 * @since 1.0.0
			 *
			 * @param string $shipping_info Shipping info.
			 */
			return apply_filters( 'wt_feed_processed_shipping_infos', $shipping_info, $this->product );
		}

		/**
		 * Get product child category id.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function child_category_id( $catalog_attr, $product_attr, $export_columns ) {
			$child_category_id = '';
			/**
			 * Filter the product type separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product type separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$child_category_obj = get_term_by( 'name', end( $full_category_array ), 'product_cat' );
				$child_category_id = isset( $child_category_obj->term_id ) ? $child_category_obj->term_id : '';
			}
			/**
			 * Filter the product child category id.
			 *
			 * @since 1.0.0
			 *
			 * @param string $child_category_id Product child category id.
			 */
			return apply_filters( 'wt_feed_filter_product_child_category_id', $child_category_id, $this->product );
		}

		/**
		 * Get product type.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function categoryPath( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
			/**
			 * Get product ID for category lookup.
			 * For variations, use parent product ID.
			 *
			 * @since 1.0.0
			 * @param int $id Product ID
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$product_categories = '';
			$term_list = get_the_terms( $id, 'product_cat' );

			if ( is_array( $term_list ) ) {
				$col = array_column( $term_list, 'term_id' );
				array_multisort( $col, SORT_ASC, $term_list );
				$term_list = array_column( $term_list, 'name' );
				$product_categories = implode( $separator, $term_list );
			}

			/**
			 * Filter the product local category.
			 *
			 * @since 1.0.0
			 *
			 * @param string $product_categories Product local category.
			 */
			return apply_filters( 'wt_feed_filter_product_local_category', $product_categories, $this->product );
		}

		/**
		 * Get product full category.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function product_full_cat( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			/**
			 * Filter the product type separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product type separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ', $this->product );

			$product_type = wp_strip_all_tags( wc_get_product_category_list( $id, $separator ) );

			/**
			 * Filter the product full category.
			 *
			 * @since 1.0.0
			 *
			 * @param string $product_type Product full category.
			 */
			return apply_filters( 'wt_feed_filter_product_full_category', $product_type, $this->product );
		}

		/**
		 * Get product URL.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function url( $catalog_attr, $product_attr, $export_columns ) {
			$link = $this->product->get_permalink();
			/**
			 * Filter the product link.
			 *
			 * @since 1.0.0
			 *
			 * @param string $link Product link.
			 */
			return apply_filters( 'wt_feed_filter_product_link', $link, $this->product );
		}

		/**
		 * Get product parent URL.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function parent_link( $catalog_attr, $product_attr, $export_columns ) {
			$link = $this->product->get_permalink();
			if ( $this->product->is_type( 'variation' ) ) {
				$link = $this->parent_product->get_permalink();
			}
			/**
			 * Filter the product parent link.
			 *
			 * @since 1.0.0
			 *
			 * @param string $link Product parent link.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_link', $link, $this->product );
		}

		/**
		 * Get product Canonical URL.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function canonical_link( $catalog_attr, $product_attr, $export_columns ) {
			// TODO: check if SEO plugin installed then return SEO canonical URL.
			$canonical_link = $this->parent_link();
			/**
			 * Filter the product canonical link.
			 *
			 * @since 1.0.0
			 *
			 * @param string $canonical_link Product canonical link.
			 */
			return apply_filters( 'wt_feed_filter_product_canonical_link', $canonical_link, $this->product );
		}

		/**
		 * Get external product URL.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function ex_link( $catalog_attr, $product_attr, $export_columns ) {
			$ex_link = '';
			if ( $this->product->is_type( 'external' ) ) {
				$ex_link = $this->product->get_product_url();
			}
			/**
			 * Filter the product external link.
			 *
			 * @since 1.0.0
			 *
			 * @param string $ex_link Product external link.
			 */
			return apply_filters( 'wt_feed_filter_product_ex_link', $ex_link, $this->product );
		}

		/**
		 * Get Formatted URL.
		 *
		 * @param string $url URL.
		 *
		 * @return string
		 * @since 1.0.0
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
					$url = $base . $url;

					return rtrim( $url, '/' );
				}
			}

			return '';
		}

		/**
		 * Get product image URL.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
					$image = self::wt_feed_get_formatted_url( $get_image[0] );
				}
			} elseif ( has_post_thumbnail( $this->product->get_id() ) ) { // All product type except variation.
				$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_id() ), 'single-post-thumbnail' );
				$image = isset( $get_image[0] ) ? self::wt_feed_get_formatted_url( $get_image[0] ) : '';
			}
			if ( '' === $image ) {
				$image = 'https://via.placeholder.com/300';
			}

			/**
			 * Filter the product image URL.
			 *
			 * @since 1.0.0
			 *
			 * @param string $image Product image URL.
			 */
			return apply_filters( 'wt_feed_filter_product_image', $image, $this->product );
		}

		/**
		 * Get product featured image URL.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function feature_image( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'single-post-thumbnail' );
			$image = isset( $get_image[0] ) ? self::wt_feed_get_formatted_url( $get_image[0] ) : '';

			/**
			 * Filter the product featured image URL.
			 *
			 * @since 1.0.0
			 *
			 * @param string $image Product featured image URL.
			 */
			return apply_filters( 'wt_feed_filter_product_feature_image', $image, $this->product );
		}

		/**
		 * Get product gallery.
		 *
		 * @param WC_Product $product The product object.
		 * @return array The product gallery image URLs.
		 * @since 1.0.0
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
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @param string $additional_img Additional image.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function imageUrls( $catalog_attr, $product_attr, $export_columns, $additional_img = '' ) {
			$img_urls = self::get_product_gallery( $this->product );
			/**
			 * Filter the product gallery separator.
			 *
			 * @since 1.0.0
			 *
			 * @param string $separator Product gallery separator.
			 */
			$separator = apply_filters( 'wt_feed_filter_category_separator', ' , ', $this->product );

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
			 *
			 * @param string $images Product images.
			 */
			return apply_filters( 'wt_feed_filter_product_images', $images, $this->product );
		}

		/**
		 * Get product condition.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $condition Product condition.
			 */
			return apply_filters( 'wt_feed_product_condition', $condition, $this->product );
		}

		/**
		 * Get product age group.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function age_group( $catalog_attr, $product_attr, $export_columns ) {

			$age_group = get_post_meta( $this->product->get_id(), '_wt_feed_agegroup', true );

			if ( '' == $age_group ) {
				$age_group = get_post_meta( $this->product->get_id(), '_wt_google_agegroup', true );
			}

			/**
			 * Filter the product age group.
			 *
			 * @since 1.0.0
			 *
			 * @param string $age_group Product age group.
			 */
			return apply_filters( 'wt_feed_google_product_age_group', $age_group, $this->product );
		}

		/**
		 * Get product material.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function material( $catalog_attr, $product_attr, $export_columns ) {

			$material = get_post_meta( $this->product->get_id(), '_wt_feed_material', true );
			if ( '' == $material ) {
				$material = get_post_meta( $this->product->get_id(), '_wt_google_material', true );
			}
			/**
			 * Filter the product material.
			 *
			 * @since 1.0.0
			 *
			 * @param string $material Product material.
			 */
			return apply_filters( 'wt_feed_product_google_material', $material, $this->product );
		}

		/**
		 * Get product pattern.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function pattern( $catalog_attr, $product_attr, $export_columns ) {

			$pattern = get_post_meta( $this->product->get_id(), '_wt_feed_pattern', true );
			if ( '' == $pattern ) {
				$pattern = get_post_meta( $this->product->get_id(), '_wt_google_pattern', true );
			}
			/**
			 * Filter the product pattern.
			 *
			 * @since 1.0.0
			 *
			 * @param string $pattern Product pattern.
			 */
			return apply_filters( 'wt_feed_product_google_pattern', $pattern, $this->product );
		}

		/**
		 * Get product unit pricing measure.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function unit_pricing_measure( $catalog_attr, $product_attr, $export_columns ) {

			$unit_pricing_measure = get_post_meta( $this->product->get_id(), '_wt_feed_unit_pricing_measure', true );
			if ( '' == $unit_pricing_measure ) {
				$unit_pricing_measure = get_post_meta( $this->product->get_id(), '_wt_google_unit_pricing_measure', true );
			}
			/**
			 * Filter the product unit pricing measure.
			 *
			 * @since 1.0.0
			 *
			 * @param string $unit_pricing_measure Product unit pricing measure.
			 */
			return apply_filters( 'wt_feed_product_google_unit_pricing_measure', $unit_pricing_measure, $this->product );
		}

		/**
		 * Get product unit pricing base measure.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function unit_pricing_base_measure( $catalog_attr, $product_attr, $export_columns ) {

			$unit_pricing_base_measure = get_post_meta( $this->product->get_id(), '_wt_feed_unit_pricing_base_measure', true );
			if ( '' == $unit_pricing_base_measure ) {
				$unit_pricing_base_measure = get_post_meta( $this->product->get_id(), '_wt_google_unit_pricing_base_measure', true );
			}
			/**
			 * Filter the product unit pricing base measure.
			 *
			 * @since 1.0.0
			 *
			 * @param string $unit_pricing_base_measure Product unit pricing base measure.
			 */
			return apply_filters( 'wt_feed_product_google_unit_pricing_base_measure', $unit_pricing_base_measure, $this->product );
		}

		/**
		 * Get product energy efficiency class.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function eec( $catalog_attr, $product_attr, $export_columns ) {

			$energy_efficiency_class = get_post_meta( $this->product->get_id(), '_wt_feed_energy_efficiency_class', true );
			if ( '' == $energy_efficiency_class ) {
				$energy_efficiency_class = get_post_meta( $this->product->get_id(), '_wt_google_energy_efficiency_class', true );
			}
			/**
			 * Filter the product energy efficiency class.
			 *
			 * @since 1.0.0
			 *
			 * @param string $energy_efficiency_class Product energy efficiency class.
			 */
			return apply_filters( 'wt_feed_product_google_energy_efficiency_class', $energy_efficiency_class, $this->product );
		}

		/**
		 * Get product minimum energy efficiency class.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function min_energy_efficiency_class( $catalog_attr, $product_attr, $export_columns ) {

			$min_energy_efficiency_class = get_post_meta( $this->product->get_id(), '_wt_feed_min_energy_efficiency_class', true );
			if ( '' == $min_energy_efficiency_class ) {
				$min_energy_efficiency_class = get_post_meta( $this->product->get_id(), '_wt_google_min_energy_efficiency_class', true );
			}
			/**
			 * Filter the product minimum energy efficiency class.
			 *
			 * @since 1.0.0
			 *
			 * @param string $min_energy_efficiency_class Product minimum energy efficiency class.
			 */
			return apply_filters( 'wt_feed_product_google_min_energy_efficiency_class', $min_energy_efficiency_class, $this->product );
		}

		/**
		 * Get product maximum energy efficiency class.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function max_energy_efficiency_class( $catalog_attr, $product_attr, $export_columns ) {

			$max_energy_efficiency_class = get_post_meta( $this->product->get_id(), '_wt_feed_max_energy_efficiency_class', true );
			if ( '' == $max_energy_efficiency_class ) {
				$max_energy_efficiency_class = get_post_meta( $this->product->get_id(), '_wt_google_max_energy_efficiency_class', true );
			}
			/**
			 * Filter the product maximum energy efficiency class.
			 *
			 * @since 1.0.0
			 *
			 * @param string $max_energy_efficiency_class Product maximum energy efficiency class.
			 */
			return apply_filters( 'wt_feed_product_google_max_energy_efficiency_class', $max_energy_efficiency_class, $this->product );
		}

		/**
		 * Get product brand.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function brand( $catalog_attr, $product_attr, $export_columns ) {

			$custom_brand = get_post_meta( $this->current_product_id, '_wt_feed_brand', true );
			if ( '' == $custom_brand ) {
				$custom_brand = get_post_meta( $this->product->get_id(), '_wt_google_brand', true );
			}
			if ( '' == $custom_brand ) {

				$brand = get_the_term_list( $this->current_product_id, 'product_brand', '', ', ' );

				$has_brand = true;
				if ( is_wp_error( $brand ) || false === $brand ) {
					$has_brand = false;
				}

				if ( ! $has_brand && is_plugin_active( 'perfect-woocommerce-brands/perfect-woocommerce-brands.php' ) ) {
					$brand = get_the_term_list( $this->current_product_id, 'pwb-brand', '', ', ' );
				}

				$string = is_wp_error( $brand ) || ! $brand ? wp_strip_all_tags( self::get_store_name() ) : self::clean_string( $brand );
				$length = 100;
				if ( extension_loaded( 'mbstring' ) ) {

					if ( mb_strlen( $string, 'UTF-8' ) <= $length ) {
						/**
						 * Filter the product brand.
						 *
						 * @since 1.0.0
						 *
						 * @param string $brand_string Product brand.
						 */
						return apply_filters( 'wt_feed_filter_product_brand', $string, $this->product );
					}

					$length -= mb_strlen( '...', 'UTF-8' );

					$brand_string = mb_substr( $string, 0, $length, 'UTF-8' ) . '...';

					/**
					 * Filter the product brand.
					 *
					 * @since 1.0.0
					 *
					 * @param string $brand_string Product brand.
					 */
					return apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
				} else {

					$string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );
					$string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

					if ( strlen( $string ) <= $length ) {
						/**
						 * Filter the product brand.
						 *
						 * @since 1.0.0
						 *
						 * @param string $brand_string Product brand.
						 */
						return apply_filters( 'wt_feed_filter_product_brand', $string, $this->product );
					}

					$length -= strlen( '...' );

					$brand_string = substr( $string, 0, $length ) . '...';
					/**
					 * Filter the product brand.
					 *
					 * @since 1.0.0
					 *
					 * @param string $brand_string Product brand.
					 */
					return apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
				}
			} else {
				/**
				 * Filter the product brand.
				 *
				 * @since 1.0.0
				 *
				 * @param string $brand_string Product brand.
				 */
				return apply_filters( 'wt_feed_filter_product_brand', $custom_brand, $this->product );
			}
		}

		/**
		 * Get product GTIN.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function gtin( $catalog_attr, $product_attr, $export_columns ) {

			$custom_gtin = get_post_meta( $this->product->get_id(), '_wt_feed_gtin', true );
			if ( '' == $custom_gtin ) {
				$custom_gtin = get_post_meta( $this->product->get_id(), '_wt_google_gtin', true );
			}
			if ( ! $custom_gtin ) {
				$custom_gtin = get_post_meta( $this->product->get_id(), '_global_unique_id', true );
			}
			$gtin = ( '' == $custom_gtin ) ? '' : $custom_gtin;
			/**
			 * Filter the product GTIN.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gtin Product GTIN.
			 */
			return apply_filters( 'wt_feed_product_gtin', $gtin, $this->product );
		}

		/**
		 * Get product MPN.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $mpn Product MPN.
			 */
			return apply_filters( 'wt_feed_product_mpn', $mpn, $this->product );
		}

		/**
		 * Get product HAN.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function hans( $catalog_attr, $product_attr, $export_columns ) {

			$han_value = get_post_meta( $this->product->get_id(), '_wt_feed_han', true );
			/**
			 * Filter the product HAN.
			 *
			 * @since 1.0.0
			 *
			 * @param string $han_value Product HAN.
			 */
			return apply_filters( 'wt_feed_product_han', $han_value, $this->product );
		}

		/**
		 * Get product EAN.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function eans( $catalog_attr, $product_attr, $export_columns ) {

			$ean_value = get_post_meta( $this->product->get_id(), '_wt_feed_ean', true );
			/**
			 * Filter the product EAN.
			 *
			 * @since 1.0.0
			 *
			 * @param string $ean_value Product EAN.
			 */
			return apply_filters( 'wt_feed_product_ean', $ean_value, $this->product );
		}

		/**
		 * Get product identifier exists.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function identifier_exists( $catalog_attr, $product_attr, $export_columns ) {

			$identifier_exists = 'no';
			if ( isset( $export_columns['sku'] ) || isset( $export_columns['brand'] ) ) {
				$identifier_exists = 'yes';
			}
			/**
			 * Filter the product identifier exists.
			 *
			 * @since 1.0.0
			 *
			 * @param string $identifier_exists Product identifier exists.
			 */
			return apply_filters( 'wt_feed_product_identifier_exists', $identifier_exists, $this->product );
		}

		/**
		 * Get product type.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function type( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product type.
			 *
			 * @since 1.0.0
			 *
			 * @param string $type Product type.
			 */
			return apply_filters( 'wt_feed_filter_product_type', $this->product->get_type(), $this->product );
		}

		/**
		 * Get product is bundle.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function is_bundle( $catalog_attr, $product_attr, $export_columns ) {
			$is_bundle = 'no';
			if ( $this->product->is_type( 'bundle' ) || $this->product->is_type( 'yith_bundle' ) || $this->product->is_type( 'bopobb' ) ) {
				$is_bundle = 'yes';
			}
			/**
			 * Filter the product is bundle.
			 *
			 * @since 1.0.0
			 *
			 * @param string $is_bundle Product is bundle.
			 */
			return apply_filters( 'wt_feed_filter_product_is_bundle', $is_bundle, $this->product );
		}

		/**
		 * Get product multipack.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function multipack( $catalog_attr, $product_attr, $export_columns ) {
			$multi_pack = '';
			if ( $this->product->is_type( 'grouped' ) ) {
				$multi_pack = ( ! empty( $this->product->get_children() ) ) ? count( $this->product->get_children() ) : '';
			}
			/**
			 * Filter the product multipack.
			 *
			 * @since 1.0.0
			 *
			 * @param string $multi_pack Product multipack.
			 */
			return apply_filters( 'wt_feed_filter_product_is_multipack', $multi_pack, $this->product );
		}

		/**
		 * Get product visibility.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function visibility( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product visibility.
			 *
			 * @since 1.0.0
			 *
			 * @param string $visibility Product visibility.
			 */
			return apply_filters( 'wt_feed_filter_product_visibility', $this->product->get_catalog_visibility(), $this->product );
		}

		/**
		 * Get product rating total.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function rating_total( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product rating total.
			 *
			 * @since 1.0.0
			 *
			 * @param string $rating_total Product rating total.
			 */
			return apply_filters( 'wt_feed_filter_product_rating_total', $this->product->get_rating_count(), $this->product );
		}

		/**
		 * Get product rating average.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function rating_average( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product rating average.
			 *
			 * @since 1.0.0
			 *
			 * @param string $rating_average Product rating average.
			 */
			return apply_filters( 'wt_feed_filter_product_rating_average', $this->product->get_average_rating(), $this->product );
		}

		/**
		 * Get product tags.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function tags( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			/**
			 * Separator for multiple tags
			 *
			 * @param string                     $separator
			 * @param array                      $config
			 * @param WC_Abstract_Legacy_Product $product
			 *
			 * @since 1.0.0
			 */
			$separator = apply_filters( 'wt_feed_tags_separator', ',', $this->product );

			$tags = wp_strip_all_tags( get_the_term_list( $id, 'product_tag', '', $separator, '' ) );

			/**
			 * Filter the product tags.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tags Product tags.
			 */
			return apply_filters( 'wt_feed_filter_product_tags', $tags, $this->product );
		}

		/**
		 * Get product item group ID.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function item_group_id( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			/**
			 * Filter the product item group ID.
			 *
			 * @since 1.0.0
			 *
			 * @param string $id Product item group ID.
			 */
			return apply_filters( 'wt_feed_filter_product_item_group_id', $id, $this->product );
		}

		/**
		 * Get product SKU.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function sku( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product SKU.
			 *
			 * @since 1.0.0
			 *
			 * @param string $sku Product SKU.
			 */
			return apply_filters( 'wt_feed_filter_product_sku', $this->product->get_sku(), $this->product );
		}

		/**
		 * Get product SKU ID.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function sku_id( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			$sku = ! empty( $this->product->get_sku() ) ? $this->product->get_sku() . '_' : '';
			$sku_id = $sku . $id;

			/**
			 * Filter the product SKU ID.
			 *
			 * @since 1.0.0
			 *
			 * @param string $sku_id Product SKU ID.
			 */
			return apply_filters( 'wt_feed_filter_product_sku_id', $sku_id, $this->product );
		}

		/**
		 * Get store name.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public static function get_store_name() {

			$url = get_bloginfo( 'name' );
			return ( $url ) ? ( $url ) : 'My Store';
		}

		/**
		 * Clean up strings for FB Graph POSTing.
		 * This function will:
		 * 1. Replace newlines chars/nbsp with a real space.
		 * 2. strip_tags().
		 * 3. trim().
		 *
		 * @param String $string String.
		 * @return string
		 * @since 1.0.0
		 */
		public static function clean_string( $string ) {
			$string = do_shortcode( $string );
			$string = str_replace( array( '&amp%3B', '&amp;' ), '&', $string );
			$string = str_replace( array( "\r", '&nbsp;', "\t" ), ' ', $string );
			$string = wp_strip_all_tags( $string, false ); // true == remove line breaks.
			return $string;
		}

		/**
		 * Get product availability.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $status Product availability.
			 */
			return apply_filters( 'wt_feed_filter_product_availability', $status, $this->product );
		}

		/**
		 * Get product availability date.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $availability_date Product availability date.
			 */
			return apply_filters( 'wt_feed_filter_product_availability_date', $availability_date, $this->product );
		}

		/**
		 * Get product add to cart link.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function add_to_cart_link( $catalog_attr, $product_attr, $export_columns ) {
			$url = $this->link();
			$suffix = 'add-to-cart=' . $this->product->get_id();

			$add_to_cart_link = wt_feed_make_url_with_parameter( $url, $suffix );

			/**
			 * Filter the product add to cart link.
			 *
			 * @since 1.0.0
			 *
			 * @param string $add_to_cart_link Product add to cart link.
			 */
			return apply_filters( 'wt_feed_filter_product_add_to_cart_link', $add_to_cart_link, $this->product );
		}

		/**
		 * Get product quantity.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function quantity( $catalog_attr, $product_attr, $export_columns ) {
			$quantity = $this->product->get_stock_quantity();
			$status = $this->product->get_stock_status();
			// when product is outofstock , and it's quantity is empty, set quantity to 0.
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
			 *
			 * @param string $quantity Product quantity.
			 */
			return apply_filters( 'wt_feed_filter_product_quantity', $quantity, $this->product );
		}

		/**
		 * Get Store Currency.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function currency( $catalog_attr, $product_attr, $export_columns ) {

			$currency = get_option( 'woocommerce_currency' );

			if ( ( class_exists( 'WCML_Multi_Currency' ) || class_exists( 'WOOCS' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) || class_exists( 'WC_Aelia_CurrencySwitcher' ) ) && ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
				$currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
			}

			/**
			 * Filter the product currency.
			 *
			 * @since 1.0.0
			 *
			 * @param string $currency Product currency.
			 */
			return apply_filters( 'wt_feed_filter_product_currency', $currency, $this->product );
		}

		/**
		 * Get Product Sale Price start date.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $sale_price_sdate Product sale price start date.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product );
		}

		/**
		 * Get Product Sale Price End Date.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $sale_price_edate Product sale price end date.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product );
		}

		/**
		 * Get first variation price.
		 *
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $price Product first variation price.
			 */
			return apply_filters( 'wt_feed_filter_product_first_variation_price', $price, $this->product );
		}

		/**
		 * Get current price.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function current_price( $catalog_attr, $product_attr, $export_columns ) {
			$price = $this->product->get_price();

			$selected_currency = get_woocommerce_currency();
			if ( ( class_exists( 'WCML_Multi_Currency' ) || class_exists( 'WOOCS' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) || class_exists( 'WC_Aelia_CurrencySwitcher' ) ) && ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
				$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
				$price = $this->get_converted_price( $price, $selected_currency );
			}

			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.
				// $price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);.

				$price = $price . ' ' . $selected_currency;
			}

			/**
			 * Filter the product current price.
			 *
			 * @since 1.0.0
			 *
			 * @param string $price Product current price.
			 */
			return apply_filters( 'wt_feed_filter_product_current_price', $price, $this->product );
		}

		/**
		 * Get product price with tax.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $price Product price with tax.
			 */
			return apply_filters( 'wt_feed_filter_product_price_with_tax', $price, $this->product );
		}

		/**
		 * Get current price with tax.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
			 *
			 * @param string $price Product current price with tax.
			 */
			return apply_filters( 'wt_feed_filter_product_current_price_with_tax', $price, $this->product );
		}

		/**
		 * Get sale price with tax.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function sale_price_with_tax( $catalog_attr, $product_attr, $export_columns ) {
			$sprice = $this->product->get_sale_price();
			$price = wc_get_price_including_tax( $this->product, array( 'price' => $sprice ) );
			if ( $price > 0 ) {

				// woo-discount-rules plugin compatiblity.
				// $price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);.

				$price = $price . ' ' . get_woocommerce_currency();
			}

			/**
			 * Filter the product sale price with tax.
			 *
			 * @since 1.0.0
			 *
			 * @param string $price Product sale price with tax.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_with_tax', $price, $this->product );
		}

		/**
		 * Get product color.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function colour( $catalog_attr, $product_attr, $export_columns ) {

			$color = get_post_meta( $this->product->get_id(), '_wt_feed_color', true );

			if ( '' === $color && $this->product->is_type( 'variation' ) ) {

				$attributes = $this->product->get_variation_attributes();

				if ( ! $attributes ) {

					/**
					 * Filter the product color.
					 *
					 * @since 1.0.0
					 *
					 * @param string $color Product color.
					 */
					return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_color", $color, $this->product );
				}

				$variant_names = array_keys( $attributes );

				foreach ( $variant_names as $original_variant_name ) {

					$label = wc_attribute_label( $original_variant_name, $this->product );

					$new_name = str_replace( 'custom_data:', '', self::sanitize_variant_name( $original_variant_name ) );
					if ( 'color' === $new_name || 'farbe' === $new_name || 'farben' === $new_name ) {
						$options = $this->get_variant_option_name( $this->product->get_id(), $label, $attributes[ $original_variant_name ] );
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

								case 'color':
								case 'farbe':
								case 'farben':
									$color = $option_values[0];

									break;

								default:
									break;
							}
						}
					}
				}
				if ( '' === $color ) {
					$parent = wc_get_product( $this->product->get_parent_id() );
					$product_attributes = $parent->get_attributes();
					if ( isset( $product_attributes['color'] ) ) {
						$color = $product_attributes['color']['options']['0'];
					}
					if ( isset( $product_attributes['farbe'] ) ) {
						$color = $product_attributes['farbe']['options']['0'];
					}
					if ( isset( $product_attributes['farben'] ) ) {
						$color = $product_attributes['farben']['options']['0'];
					}
					if ( isset( $product_attributes['Farbe'] ) ) {
						$color = $product_attributes['Farbe']['options']['0'];
					}
				}

				/**
				 * Filter the product color.
				 *
				 * @since 1.0.0
				 *
				 * @param string $color Product color.
				 */
				return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_color", $color, $this->product );
			} elseif ( '' === $color ) {
				$product_attributes = $this->product->get_attributes();
				if ( isset( $product_attributes['color'] ) ) {
					$color = $product_attributes['color']['options']['0'];
				}
				if ( isset( $product_attributes['farbe'] ) ) {
					$color = $product_attributes['farbe']['options']['0'];
				}
				if ( isset( $product_attributes['farben'] ) ) {
					$color = $product_attributes['farben']['options']['0'];
				}
				if ( isset( $product_attributes['Farbe'] ) ) {
					$color = $product_attributes['Farbe']['options']['0'];
				}
			}

			/**
			 * Filter the product color.
			 *
			 * @since 1.0.0
			 *
			 * @param string $color Product color.
			 */
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_color", $color, $this->product );
		}

		/**
		 * Get Product Weight.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function weight( $catalog_attr, $product_attr, $export_columns ) {
			$weight = $this->product->get_weight();

			/**
			 * Filter the product weight.
			 *
			 * @since 1.0.0
			 *
			 * @param string $weight Product weight.
			 */
			return apply_filters( 'wt_feed_filter_product_weight', $weight, $this->product );
		}

		/**
		 * Get Weight Unit.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function weight_unit( $catalog_attr, $product_attr, $export_columns ) {
			$weight_unit = get_option( 'woocommerce_weight_unit' );

			/**
			 * Filter the product weight unit.
			 *
			 * @since 1.0.0
			 *
			 * @param string $weight_unit Product weight unit.
			 */
			return apply_filters( 'wt_feed_filter_product_weight_unit', $weight_unit, $this->product );
		}

		/**
		 * Get Product Width.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function width( $catalog_attr, $product_attr, $export_columns ) {
			$width = $this->product->get_width();

			/**
			 * Filter the product width.
			 *
			 * @since 1.0.0
			 *
			 * @param string $width Product width.
			 */
			return apply_filters( 'wt_feed_filter_product_width', $width, $this->product );
		}

		/**
		 * Get Product Height.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function height( $catalog_attr, $product_attr, $export_columns ) {
			$height = $this->product->get_height();

			/**
			 * Filter the product height.
			 *
			 * @since 1.0.0
			 *
			 * @param string $height Product height.
			 */
			return apply_filters( 'wt_feed_filter_product_height', $height, $this->product );
		}

		/**
		 * Get Product Length.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function length( $catalog_attr, $product_attr, $export_columns ) {
			$length = $this->product->get_length();

			/**
			 * Filter the product length.
			 *
			 * @since 1.0.0
			 *
			 * @param string $length Product length.
			 */
			return apply_filters( 'wt_feed_filter_product_length', $length, $this->product );
		}

		/**
		 * Google Formatted Shipping info
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @param string $key Key.
		 * @return mixed|void
		 * @since 1.0.0
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
				 * Filter the product shipping XML.
				 *
				 * @since 1.0.0
				 *
				 * @param array $shipping_details_xml Shipping details XML.
				 * @param array $shipping_details Shipping details.
				 * @param WC_Product $product Product.
				 */
				return apply_filters( 'wt_feed_google_product_shipping_xml', $shipping_details_xml, $shipping_details, $this->product );
			}

			/**
			 * Filter the product shipping.
			 *
			 * @since 1.0.0
			 *
			 * @param string $shipping_str Product shipping.
			 * @param array $shipping_details Shipping details.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_google_product_shipping', $shipping_str, $shipping_details, $this->product );
		}

		/**
		 * Get Shipping Data.
		 *
		 * @param array $catalog_attr Catalog attribute.
		 * @param array $product_attr Product attribute.
		 * @param array $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function shipping_data( $catalog_attr, $product_attr, $export_columns ) {

			return $this->shipping( $catalog_attr, $product_attr, $export_columns );
		}


		/**
		 * Get Product Shipping Class
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function shipping_class( $catalog_attr, $product_attr, $export_columns ) {
			$shipping_class = $this->product->get_shipping_class();

			/**
			 * Filter the product shipping class.
			 *
			 * @since 1.0.0
			 *
			 * @param string $shipping_class Product shipping class.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_shipping_class', $shipping_class, $this->product );
		}

		/**
		 * Get Custom Label 0.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function custom_label_0( $catalog_attr, $product_attr, $export_columns ) {

			$custom_label_0 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_0', true );

			if ( '' == $custom_label_0 ) {
				$custom_label_0 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_0', true );
			}

			/**
			 * Filter the product custom label 0.
			 *
			 * @since 1.0.0
			 *
			 * @param string $custom_label_0 Product custom label 0.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_0', $custom_label_0, $this->product );
		}

		/**
		 * Get Custom Label 1.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function custom_label_1( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_1 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_1', true );

			if ( '' == $custom_label_1 ) {
				$custom_label_1 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_1', true );
			}

			/**
			 * Filter the product custom label 1.
			 *
			 * @since 1.0.0
			 *
			 * @param string $custom_label_1 Product custom label 1.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_1', $custom_label_1, $this->product );
		}

		/**
		 * Get Custom Label 2.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function custom_label_2( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_2 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_2', true );

			if ( '' == $custom_label_2 ) {
				$custom_label_2 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_2', true );
			}

			/**
			 * Filter the product custom label 2.
			 *
			 * @since 1.0.0
			 *
			 * @param string $custom_label_2 Product custom label 2.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_2', $custom_label_2, $this->product );
		}

		/**
		 * Get Custom Label 3.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function custom_label_3( $catalog_attr, $product_attr, $export_columns ) {

			$custom_label_3 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_3', true );

			if ( '' == $custom_label_3 ) {
				$custom_label_3 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_3', true );
			}

			/**
			 * Filter the product custom label 3.
			 *
			 * @since 1.0.0
			 *
			 * @param string $custom_label_3 Product custom label 3.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_3', $custom_label_3, $this->product );
		}

		/**
		 * Get Custom Label 4.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function custom_label_4( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_4 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_4', true );

			if ( '' == $custom_label_4 ) {
				$custom_label_4 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_4', true );
			}

			/**
			 * Filter the product custom label 4.
			 *
			 * @since 1.0.0
			 *
			 * @param string $custom_label_4 Product custom label 4.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_4', $custom_label_4, $this->product );
		}

		/**
		 * Get Date Created.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function date_created( $catalog_attr, $product_attr, $export_columns ) {
			$date_created = gmdate( 'Y-m-d', strtotime( $this->product->get_date_created() ) );

			/**
			 * Filter the product date created.
			 *
			 * @since 1.0.0
			 *
			 * @param string $date_created Product date created.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_date_created', $date_created, $this->product );
		}

		/**
		 * Get Date updated.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function date_updated( $catalog_attr, $product_attr, $export_columns ) {
			$date_updated = gmdate( 'Y-m-d', strtotime( $this->product->get_date_modified() ) );

			/**
			 * Filter the product date updated.
			 *
			 * @since 1.0.0
			 *
			 * @param string $date_updated Product date updated.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_date_updated', $date_updated, $this->product );
		}

		/** Get Google Sale Price effective date.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
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
		 * Get Tax Class.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function tax_class( $catalog_attr, $product_attr, $export_columns ) {

			/**
			 * Filter the product tax class.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tax_class Product tax class.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_tax_class', $this->product->get_tax_class(), $this->product );
		}

		/**
		 * Get Tax Status.
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed|void
		 * @since 1.0.0
		 */
		public function tax_status( $catalog_attr, $product_attr, $export_columns ) {

			/**
			 * Filter the product tax status.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tax_status Product tax status.
			 * @param WC_Product $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_tax_status', $this->product->get_tax_status(), $this->product );
		}
	}

}
