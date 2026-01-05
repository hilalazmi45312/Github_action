<?php
/**
 * Handles the Facebook export actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\Facebook
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Facebook_Export' ) ) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Facebook_Export Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Facebook_Export extends Product_Feed_For_Woocommerce_Product {

		/**
		 * Parent module object.
		 *
		 * @var string
		 */
		public $parent_module = null;

		/**
		 * Product object.
		 *
		 * @var string
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
		 * @var string
		 */
		public $form_data;

		/**
		 * Constructor.
		 *
		 * @param object $parent_object Description.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $parent_object ) {

			$this->parent_module = $parent_object;
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'wt_pf_exclude_cat_query' ), 10, 2 );
		}

		/**
		 * Prepare CSV header
		 *
		 * @return array
		 */
		public function prepare_header() {

			$export_columns = $this->parent_module->get_selected_column_names();
			/**
			 * Filter the product feed CSV columns.
			 *	
			 * @since 1.0.0
			 *
			 * @param array   $export_columns    Export columns.
			 */
			return apply_filters( 'wt_pf_alter_product_feed_csv_columns', $export_columns );
		}

		/**
		 * Prepare data that will be exported.
		 *
		 * @param array   $form_data Form data.
		 * @param integer $batch_offset Offset.
		 * @param string  $step Step.
		 * @return array
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

			$wt_pf_export_post_author = ! empty( $form_data['post_type_form_data']['wt_pf_export_post_author'] ) ? $form_data['post_type_form_data']['wt_pf_export_post_author'] : '';

			$inc_exc_category = ! empty( $form_data['post_type_form_data']['inc_exc_cat'] ) ? $form_data['post_type_form_data']['inc_exc_cat'] : array();
			if ( empty( $inc_exc_category ) ) {
				$inc_exc_category = ! empty( $form_data['post_type_form_data']['wt_pf_inc_exc_category'] ) ? $form_data['post_type_form_data']['wt_pf_inc_exc_category'] : array();
			}

			if ( 'include_cat' === $cat_filter_type ) {
				$prod_inc_categories = $inc_exc_category;
			} else {
				$prod_exc_categories = $inc_exc_category;
			}

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
			 * @param array $batch_count Batch count.
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

				if ( isset( $args['type'] ) && in_array( 'variation', $args['type'] ) && !in_array('variable', $args['type'])) {
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
					$args['exclude'] = $prod_exc; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				}

				if ( ! empty( $exc_stock_status ) ) {
					$available_status = wc_get_product_stock_status_options();
					unset( $available_status['outofstock'] );
					$args['stock_status'] = array_keys( $available_status );
				}
				$args['exclude_discarded'] = '_wt_feed_discard'; // To exclude individual excluded from product fetching.

				/**
				 * Filter the query arguments.
				 *
				 * @since 1.0.0
				 *
				 * @param array $args Query arguments.
				 */
				$args = apply_filters( 'wt_feed_product_catalog_args', $args );

				/*
				 * WPML - Switch language to selected language for temparory export
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
					
					// Skip variations that belongs to a specific categories that is excluded in filter.
					if ($product->is_type( 'variation' ) && ! empty( $prod_exc_categories ) ) {
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
		 * Generate row data
		 *
		 * @param object $product_object Product.
		 * @return array
		 */
		protected function generate_row_data_wc_lower( $product_object ) {

			$export_columns = $this->parent_module->get_selected_column_names();

			$product_id = $product_object->get_id();
			$product = get_post( $product_id );

			$csv_columns = $export_columns;

			$export_columns = ! empty( $csv_columns ) ? $csv_columns : array();

			$row = array();

			foreach ( $export_columns as $key => $value ) {

				if ( strpos( $value, 'meta:' ) !== false ) {
					$mkey = str_replace( 'meta:', '', $value );
					$row[ $key ] = get_post_meta( $product_id, $mkey, true );
					// TODO.
					// wt_image_ function can be replaced with key exist check.
				} elseif ( strpos( $value, 'wt_static_map_vl:' ) !== false ) {
					$static_feed_value = str_replace( 'wt_static_map_vl:', '', $value );
					$row[ $key ] = $static_feed_value;
				} elseif ( method_exists( $this, $value ) ) {
					$row[ $key ] = $this->$value( $key, $value, $export_columns );
				} else {
					$row[ $key ] = '';
				}
			}
			/**
			 * Filter the product feed row data.
			 *	
			 * @since 1.0.0
			 *
			 * @param array $row Export row.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_batch_product_feed_row_data', $row, $product );
		}

		/**
		 * Get product id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function id( $catalog_attr, $product_attr, $export_columns ) {

			$product_id = $this->product->get_id();
			$fb_retailer_id = $this->product->get_sku() ? $this->product->get_sku() . '_' . $product_id : 'wc_post_id_' . $product_id;
			/**
			 * Filter the product id.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $fb_retailer_id Facebook retailer id.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_id', $fb_retailer_id, $this->product );
		}

		/**
		 * Get product title.
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
				$separator = ',';

				$variation_attributes = implode( $separator, $attributes );

				/**
				 * Filter the product title with variation attribute.
				 *	
				 * @since 1.0.0
				 *
				 * @param bool $get_with_var_attributes Get with var attributes.
				 * @param object $product Product.
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
			 *
			 * @param string $title Product title.
			 * @param object $product Product.
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
			* @since 1.0.0
			*
			* @param string $title Product parent title.
			* @param object $product Product.
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

			// Add variations attributes after description to prevent Facebook error.
			if ( $this->product->is_type( 'variation' ) && ( '' === $description ) ) {
				$variation_info = explode( '-', $this->product->get_name() );

				if ( isset( $variation_info[1] ) ) {
					$extension = $variation_info[1];
				} else {
					$extension = $this->product->get_id();
				}
				$description .= ' ' . $extension;
			}

			// strip tags and special characters.
			$description = wp_strip_all_tags( $description );
			/**
			 * Filter the product description.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $description Product description.
			 * @param object $product Product.
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
			 * @param object $product Product.
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
			 * Filter the product short description.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $short_description Product short description.
			 * @param object $product Product.
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
			 * Filter the product primary category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $parent_category Product primary category.
			 * @param object $product Product.
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
			 * @param string $parent_category_id Product primary category id.
			 * @param object $product Product.
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
			 * Filter the product primary category id.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $parent_category_id Product primary category id.
			 * @param object $product Product.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$parent_category_obj = get_term_by( 'name', $full_category_array[0], 'product_cat' );
				$parent_category_id = isset( $parent_category_obj->term_id ) ? $parent_category_obj->term_id : '';
			}
			/**
			 * Filter the product primary category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $parent_category Product primary category.
			 * @param object $product Product.
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
			 * Filter the product child category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $child_category Product child category.
			 * @param object $product Product.
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_child_category', $child_category, $this->product );
		}

		/**
		 * Get product child category id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function child_category_id( $catalog_attr, $product_attr, $export_columns ) {
			$child_category_id = '';
			/**
			 * Filter the product child category id.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $child_category_id Product child category id.
			 * @param object $product Product.
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_child_category_id', $child_category_id, $this->product );
		}

		/**
		 * Get product type.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function product_type( $catalog_attr, $product_attr, $export_columns ) {
			$id = $this->product->get_id();
			if ( $this->product->is_type( 'variation' ) ) {
				$id = $this->product->get_parent_id();
			}
			/**
			 * Filter the product type.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $product_type Product type.
			 * @param object $product Product.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$product_categories = '';
			$term_list = get_the_terms( $id, 'product_cat' );

			if ( is_array( $term_list ) ) {
				$col = array_column( $term_list, 'term_id' );
				array_multisort( $col, SORT_ASC, $term_list );
				$term_list = array_column( $term_list, 'name' );
				// TODO: Remove Manual Separator and add Dynamically with hook. Hook function also need to modified from array to object.
				$product_categories = implode( ' > ', $term_list );
			}
			/**
			 * Filter the product local category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $product_categories Product local category.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_local_category', $product_categories, $this->product );
		}

		/**
		 * Get product full category.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function product_full_cat( $catalog_attr, $product_attr, $export_columns ) {

			$id = $this->product->get_id();
			if ( $this->product->is_type( 'variation' ) ) {
				$id = $this->product->get_parent_id();
			}
			/**
			 * Filter the product full category separator.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $separator Product full category separator.
			 * @param object $product Product.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ', $this->product );

			$product_type = wp_strip_all_tags( wc_get_product_category_list( $id, $separator ) );
			/**
			 * Filter the product full category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $product_type Product full category.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_local_category', $product_type, $this->product );
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_link', $link, $this->product );
		}

		/**
		 * Get product parent link.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_link', $link, $this->product );
		}

		/**
		 * Get product canonical link.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_canonical_link', $canonical_link, $this->product );
		}

		/**
		 * Get product external url.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function ex_link( $catalog_attr, $product_attr, $export_columns ) {
			$ex_link = '';
			if ( $this->product->is_type( 'external' ) ) {
				$ex_link = $this->product->get_product_url();
			}
			/**
			 * Filter the product external url.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $ex_link Product external url.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_ex_link', $ex_link, $this->product );
		}

		/**
		 * Get Formatted URL
		 *
		 * @param string $url URL.
		 *
		 * @return string
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
		 * Get product image link.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * Filter the product image link.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $image Product image link.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_image', $image, $this->product );
		}

		/**
		 * Get product featured image.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function feature_image( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'single-post-thumbnail' );
			$image = isset( $get_image[0] ) ? wt_feed_get_formatted_url( $get_image[0] ) : '';
			/**
			 * Filter the product featured image.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $image Product featured image.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_feature_image', $image, $this->product );
		}
		/**
		 * Product gallery
		 *
		 * @param object $product Product.
		 * @return array
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
		 * Get product image links.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @param array $additional_img Export imgs.
		 * @return string
		 */
		public function additional_image_link( $catalog_attr, $product_attr, $export_columns, $additional_img = '' ) {
			$img_urls = self::get_product_gallery( $this->product );
			/**
			 * Filter the product additional image link.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $images Product additional image link.
			 * @param object $product Product.
			 */
			$separator = apply_filters( 'wt_feed_filter_category_separator', ' | ', $this->product );

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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_images', $images, $this->product );
		}

		/**
		 * Get product images.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @param array $additional_img Export imgs.
		 * @return string
		 */
		public function images( $catalog_attr, $product_attr, $export_columns, $additional_img = '' ) {
			$img_urls = self::get_product_gallery( $this->product );
			/**
			 * Filter the product images.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $images Product images.
			 * @param object $product Product.
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
			 *
			 * @param string $images Product images.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_images', $images, $this->product );
		}
		/**
		 * Get product image1.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_1( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 1 );
		}
		/**
		 * Get product image2.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_2( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 2 );
		}
		/**
		 * Get product image3.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_3( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 3 );
		}
		/**
		 * Get product image4.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_4( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 4 );
		}
		/**
		 * Get product image5.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_5( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 5 );
		}
		/**
		 * Get product image6.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_6( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 6 );
		}
		/**
		 * Get product image7.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_7( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 7 );
		}
		/**
		 * Get product image8.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_8( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 8 );
		}
		/**
		 * Get product image9.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_9( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 9 );
		}
		/**
		 * Get product image10.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function wtimages_10( $catalog_attr, $product_attr, $export_columns ) {
			return $this->images( $catalog_attr, $product_attr, $export_columns, 10 );
		}
		/**
		 * Get product condition.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function condition( $catalog_attr, $product_attr, $export_columns ) {

			$custom_condition = get_post_meta( $this->product->get_id(), '_wt_feed_condition', true );

			if ( '' == $custom_condition ) {
				$custom_condition = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}

			$condition = ( '' == $custom_condition ) ? 'new' : $custom_condition;
			/**
			 * Filter the product condition.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $condition Product condition.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_condition', $condition, $this->product );
		}
		/**
		 * Get product age group.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function age_group( $catalog_attr, $product_attr, $export_columns ) {

			$age_group = get_post_meta( $this->product->get_id(), '_wt_feed_agegroup', true );
			if ( '' == $age_group ) {
				$age_group = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}
			/**
			 * Filter the product age group.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $age_group Product age group.
			 * @param object $product Product.
			 */		
			return apply_filters( 'wt_feed_facebook_product_age_group', $age_group, $this->product );
		}
		/**
		 * Get product gender.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function gender( $catalog_attr, $product_attr, $export_columns ) {

			$gender = get_post_meta( $this->product->get_id(), '_wt_feed_gender', true );
			if ( '' == $gender ) {
				$gender = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}
			/**
			 * Filter the product gender.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $gender Product gender.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_facebook_product_gender', $gender, $this->product );
		}
		/**
		 * Get product size.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function size( $catalog_attr, $product_attr, $export_columns ) {

			$size = get_post_meta( $this->product->get_id(), '_wt_feed_size', true );
			if ( '' == $size ) {
				$size = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}
			/**
			 * Filter the product size.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $size Product size.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_facebook_product_size', $size, $this->product );
		}
		/**
		 * Get product color.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function color( $catalog_attr, $product_attr, $export_columns ) {

			$color = get_post_meta( $this->product->get_id(), '_wt_feed_color', true );
			if ( '' == $color ) {
				$color = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}
			/**
			 * Filter the product color.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $color Product color.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_color', $color, $this->product );
		}
		/**
		 * Get product material.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function material( $catalog_attr, $product_attr, $export_columns ) {

			$material = get_post_meta( $this->product->get_id(), '_wt_feed_material', true );
			if ( '' == $material ) {
				$material = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}
			/**
			 * Filter the product material.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $material Product material.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_material', $material, $this->product );
		}
		/**
		 * Get product pattern.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function pattern( $catalog_attr, $product_attr, $export_columns ) {

			$pattern = get_post_meta( $this->product->get_id(), '_wt_feed_pattern', true );
			if ( '' == $pattern ) {
				$pattern = get_post_meta( $this->product->get_id(), '_wt_facebook_condition', true );
			}
			/**
			 * Filter the product pattern.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $pattern Product pattern.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_pattern', $pattern, $this->product );
		}
		/**
		 * Get product identified exist.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function identifier_exists( $catalog_attr, $product_attr, $export_columns ) {

			$identifier_exists = 'no';
			if ( isset( $export_columns['sku'] ) || isset( $export_columns['brand'] ) ) {
				$identifier_exists = 'yes';
			}
			/**
			 * Filter the product identified exist.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $identifier_exists Product identified exist.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_identifier_exists', $identifier_exists, $this->product );
		}
		/**
		 * Get product type.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function type( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product type.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $type Product type.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_type', $this->product->get_type(), $this->product );
		}
		/**
		 * Get product is bundle.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function is_bundle( $catalog_attr, $product_attr, $export_columns ) {

			$is_bundle = ( $this->product->is_type( 'bundle' ) || $this->product->is_type( 'yith_bundle' ) ) ? 'yes' : 'no';
			/**
			 * Filter the product is bundle.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $is_bundle Product is bundle.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_is_bundle', $is_bundle, $this->product );
		}

		/**
		 * Get product multipack.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_is_multipack', $multi_pack, $this->product );
		}
		/**
		 * Get product visibility.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function visibility( $catalog_attr, $product_attr, $export_columns ) {
			/*
			 * "active", "archived", "staging", "published", "hidden", "visible_only_with_overrides", "whitelist_only"
			 */
			$visibility = $this->product->get_catalog_visibility();
			$visibility = ( 'visible' === $visibility ) ? 'active' : 'staging';
			/**
			 * Filter the product visibility.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $visibility Product visibility.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_visibility', $visibility, $this->product );
		}
		/**
		 * Get product rating total.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function rating_total( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product rating total.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $rating_total Product rating total.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_rating_total', $this->product->get_rating_count(), $this->product );
		}
		/**
		 * Get product rating avg.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function rating_average( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product rating average.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $rating_average Product rating average.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_rating_average', $this->product->get_average_rating(), $this->product );
		}
		/**
		 * Get product fb category.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function fb_product_category( $catalog_attr, $product_attr, $export_columns ) {

			$custom_fb_category = get_post_meta( $this->current_product_id, '_wt_facebook_fb_product_category', true );

			if ( '' == $custom_fb_category ) {

				// If variation, take the category from the parent.
				if ( $this->product->is_type( 'variation' ) ) {
					$product_id = $this->product->get_parent_id();
				} else {
					$product_id = $this->current_product_id;
				}
				$category_path = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'all' ) );

				$fb_product_category = array();
				foreach ( $category_path as $category ) {
					$fb_category_id = get_term_meta( $category->term_id, 'wt_fb_category', true );
					if ( $fb_category_id ) {

						$fb_category_list = wp_cache_get( 'wt_fbfeed_fb_product_categories_array' );

						if ( false === $fb_category_list ) {
							$fb_category_list = Webtoffee_Product_Feed_Sync_Pro_Facebook::get_category_array();
							wp_cache_set( 'wt_fbfeed_fb_product_categories_array', $fb_category_list, '', WEEK_IN_SECONDS );
						}

						$fb_category = isset( $fb_category_list[ $fb_category_id ] ) ? $fb_category_list[ $fb_category_id ] : '';
						if ( '' !== $fb_category ) {
							$fb_product_category[] = $fb_category;
						}
					}
				}

				$fb_product_category = empty( $fb_product_category ) ? '' : implode( ', ', $fb_product_category );
			} else {

				$fb_category_list = wp_cache_get( 'wt_fbfeed_fb_product_categories_array' );

				if ( false === $fb_category_list ) {
					$fb_category_list = Webtoffee_Product_Feed_Sync_Pro_Facebook::get_category_array();
					wp_cache_set( 'wt_fbfeed_fb_product_categories_array', $fb_category_list, '', WEEK_IN_SECONDS );
				}

				$fb_product_category = $fb_category_list[ $custom_fb_category ];
			}
			/**
			 * Filter the product fb category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $fb_product_category Product fb category.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_fb_category', $fb_product_category, $this->product );
		}
		/**
		 * Get product google cat.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function google_product_category( $catalog_attr, $product_attr, $export_columns ) {

			$custom_google_category = get_post_meta( $this->current_product_id, '_wt_google_google_product_category', true );

			if ( '' == $custom_google_category ) {

				// If variation, take the category from the parent.
				if ( $this->product->is_type( 'variation' ) ) {
					$product_id = $this->product->get_parent_id();
				} else {
					$product_id = $this->current_product_id;
				}
				$category_path = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'all' ) );

				$google_product_category = array();
				foreach ( $category_path as $category ) {
					$google_category_id = get_term_meta( $category->term_id, 'wt_google_category', true );
					if ( $google_category_id ) {

						$google_category_list = wp_cache_get( 'wt_fbfeed_google_product_categories_array' );

						if ( false === $google_category_list ) {
							$google_category_list = Webtoffee_Product_Feed_Sync_Pro_Google::get_category_array();
							wp_cache_set( 'wt_fbfeed_google_product_categories_array', $google_category_list, '', WEEK_IN_SECONDS );
						}

						$google_category = isset( $google_category_list[ $google_category_id ] ) ? $google_category_list[ $google_category_id ] : '';
						if ( '' !== $google_category ) {
							$google_product_category[] = $google_category;
						}
					}
				}

				$google_product_category = empty( $google_product_category ) ? '' : implode( ', ', $google_product_category );
			} else {

				$google_category_list = wp_cache_get( 'wt_fbfeed_google_product_categories_array' );

				if ( false === $google_category_list ) {
					$google_category_list = Webtoffee_Product_Feed_Sync_Pro_Google::get_category_array();
					wp_cache_set( 'wt_fbfeed_google_product_categories_array', $google_category_list, '', WEEK_IN_SECONDS );
				}

				$google_product_category = $google_category_list[ $custom_google_category ];
			}
			/**
			 * Filter the product google category.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $google_product_category Product google category.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_google_category', $google_product_category, $this->product );
		}

		/**
		 * Get product tags.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function tags( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			/**
			 * Separator for multiple tags
			 *
			 * @param string $separator Separator for multiple tags.
			 * @param array $config Config.
			 * @param WC_Abstract_Legacy_Product $product Product.
			 *
			 * @since 1.0.0
			 */
			$separator = apply_filters( 'wt_feed_tags_separator', ',', $this->product );

			$tags = ( get_the_term_list( $id, 'product_tag', '', $separator, '' ) );
			/**
			 * Filter the product tags.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $tags Product tags.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_tags', $tags, $this->product );
		}
		/**
		 * Get product item group id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function item_group_id( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
			/**
			 * Filter the product item group id.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $id Product id.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_item_group_id', $id, $this->product );
		}
		/**
		 * Get product sku.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function sku( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product sku.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $sku Product sku.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sku', $this->product->get_sku(), $this->product );
		}
		/**
		 * Get product sku_id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
	     * @return string
		 */
		public function sku_id( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );

			$sku = ! empty( $this->product->get_sku() ) ? $this->product->get_sku() . '_' : '';
			$sku_id = $sku . $id;
			/**
			 * Filter the product sku id.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $sku_id Product sku id.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sku_id', $sku_id, $this->product );
		}
		/**
		 * Get product brand.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function brand( $catalog_attr, $product_attr, $export_columns ) {

			$custom_brand = get_post_meta( $this->current_product_id, '_wt_feed_brand', true );

			if ( '' == $custom_brand ) {
				$custom_brand = get_post_meta( $this->current_product_id, '_wt_facebook_brand', true );
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

				if ( ! $has_brand && ( is_plugin_active( 'yith-woocommerce-brands-add-on/init.php' ) || is_plugin_active( 'yith-woocommerce-brands-add-on-premium/init.php' ) ) ) {
					$brand = get_the_term_list( $this->current_product_id, 'yith_product_brand', '', ', ' );
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
						 * @param string $string Product brand.
						 * @param object $product Product.
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
					 * @param object $product Product.
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
					 * @param object $product Product.
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
					 * @param object $product Product.
					 */
					return apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
				}
			} else {
				/**
				 * Filter the product brand.
				 *	
				 * @since 1.0.0
				 *
				 * @param string $custom_brand Product brand.
				 * @param object $product Product.
				 */
				return apply_filters( 'wt_feed_filter_product_brand', $custom_brand, $this->product );
			}
		}
		/**
		 * Get storename
		 *
		 * @return string
		 */
		public static function get_store_name() {

			$url = get_bloginfo( 'name' );
			return ( $url ) ? ( $url ) : 'My Store';
		}

		/**
		 * Clean up strings for FB Graph POSTing.
		 * This function should will:
		 * 1. Replace newlines chars/nbsp with a real space
		 * 2. strip_tags()
		 * 3. trim()
		 *
		 * @param String $string String.
		 * @return string
		 */
		public static function clean_string( $string ) {
			$string = do_shortcode( $string );
			$string = str_replace( array( '&amp%3B', '&amp;' ), '&', $string );
			$string = str_replace( array( "\r", '&nbsp;', "\t" ), ' ', $string );
			$string = wp_strip_all_tags( $string, false ); // true == remove line breaks.
			return $string;
		}
		/**
		 * Get product parent sku.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function parent_sku( $catalog_attr, $product_attr, $export_columns ) {
			$parent_sku = $this->product->get_sku();
			if ( $this->product->is_type( 'variation' ) ) {
				$parent_sku = $this->parent_product->get_sku();
			}
			/**
			 * Filter the product parent sku.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $parent_sku Product parent sku.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_sku', $parent_sku, $this->product );
		}
		/**
		 * Get product availability.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function availability( $catalog_attr, $product_attr, $export_columns ) {
			$status = $this->product->get_stock_status();
			if ( 'instock' === $status ) {
				$status = 'in stock';
			} elseif ( 'outofstock' === $status ) {
				$status = 'out of stock';
			} elseif ( 'onbackorder' === $status ) {
				$status = 'on backorder';
			}
			/**
			 * Filter the product availability.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $status Product availability.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_availability', $status, $this->product );
		}
		/**
		 * Get product avail date.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function availability_date( $catalog_attr, $product_attr, $export_columns ) {

			$feed_settings = get_option( 'wt_feed_settings' );

			$availability_date_settings = isset( $feed_settings['wt_feed_identifier']['availability_date'] ) ? $feed_settings['wt_feed_identifier']['availability_date'] : 'enable';

			if ( 'onbackorder' !== $this->product->get_stock_status() || 'disable' === $availability_date_settings ) {
				return '';
			}

			$meta_field_name = 'wt_feed_availability_date';

			if ( $this->product->is_type( 'variation' ) ) {
				$meta_field_name .= '_var';
			}

			$availability_date = get_post_meta( $this->product->get_id(), $meta_field_name, true );

			if ( '' !== $availability_date ) {
				$availability_date = gmdate( 'c', strtotime( $availability_date ) );
			}
			/**
			 * Filter the product availability date.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $availability_date Product availability date.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_availability_date', $availability_date, $this->product );
		}
		/**
		 * Get product add to cart link.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_add_to_cart_link', $add_to_cart_link, $this->product );
		}
		/**
		 * Get product qty.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return int
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
			/**
			 * Filter the product quantity.
			 *	
			 * @since 1.0.0
			 *
			 * @param int $quantity Product quantity.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_quantity', $quantity, $this->product );
		}

		/**
		 * Get product currency.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function currency( $catalog_attr, $product_attr, $export_columns ) {

			$currency = get_option( 'woocommerce_currency' );

			if ( class_exists( 'WCML_Multi_Currency' ) && ! empty( $this->form_data['post_type_form_data']['item_post_currency'] ) ) {
				$currency = $this->form_data['post_type_form_data']['item_post_currency'];
			}
			/**
			 * Filter the product currency.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $currency Product currency.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_currency', $currency, $this->product );
		}

		/**
		 * Get product sale price start date.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product );
		}

		/**
		 * Get product sale price end date.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product );
		}
		/**
		 * Get product converted price.
		 *
		 * @param int    $price Product price.
		 * @param string $selected_currency Selected country.
		 * @return float
		 */
		public function get_converted_price( $price, $selected_currency ) {

			if ( get_woocommerce_currency() != $selected_currency && $price > 0 ) {
				$wcml_mc = new WCML_Multi_Currency();
				$currencies = $wcml_mc->get_currencies( true );

				$woo_currencies = get_woocommerce_currencies();

				if ( ! empty( $woo_currencies[ $selected_currency ] ) && ! empty( $currencies[ $selected_currency ] ) ) {
					$price = $price * $currencies[ $selected_currency ]['rate'];
				}
			}
			return $price;
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
			$price = $this->product->get_regular_price();

			if ( $this->product->is_type( 'variable' ) ) {
				$price = $this->first_variation_price();
			}

			$selected_currency = get_woocommerce_currency();
			if ( class_exists( 'WCML_Multi_Currency' ) && ! empty( $this->form_data['post_type_form_data']['item_post_currency'] ) ) {
				$selected_currency = $this->form_data['post_type_form_data']['item_post_currency'];
				$price = $this->get_converted_price( $price, $selected_currency );
			}

			if ( $price > 0 ) {
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product price.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $price Product price.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_price', $price, $this->product );
		}
		/**
		 * Get product current price.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function current_price( $catalog_attr, $product_attr, $export_columns ) {
			$price = $this->product->get_price();

			$selected_currency = get_woocommerce_currency();
			if ( class_exists( 'WCML_Multi_Currency' ) && ! empty( $this->form_data['post_type_form_data']['item_post_currency'] ) ) {
				$selected_currency = $this->form_data['post_type_form_data']['item_post_currency'];
				$price = $this->get_converted_price( $price, $selected_currency );
			}

			if ( $price > 0 ) {
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product current price.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $price Product current price.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_current_price', $price, $this->product );
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

			$selected_currency = get_woocommerce_currency();
			if ( class_exists( 'WCML_Multi_Currency' ) && ! empty( $this->form_data['post_type_form_data']['item_post_currency'] ) ) {
				$selected_currency = $this->form_data['post_type_form_data']['item_post_currency'];
				$price = $this->get_converted_price( $price, $selected_currency );
			}

			if ( $price > 0 ) {
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product sale price.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $price Product sale price.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price', $price, $this->product );
		}
		/**
		 * Get product price with tax.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function price_with_tax( $catalog_attr, $product_attr, $export_columns ) {

			$tprice = $this->product->get_regular_price();
			$price = wc_get_price_including_tax( $this->product, array( 'price' => $tprice ) );
			if ( $price > 0 ) {
				$price = $price . ' ' . get_woocommerce_currency();
			}
			/**
			 * Filter the product price with tax.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $price Product price with tax.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_price_with_tax', $price, $this->product );
		}
		/**
		 * Get product current price with tax.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function current_price_with_tax( $catalog_attr, $product_attr, $export_columns ) {
			$cprice = $this->product->get_price();
			$price = wc_get_price_including_tax( $this->product, array( 'price' => $cprice ) );
			if ( $price > 0 ) {
				$price = $price . ' ' . get_woocommerce_currency();
			}
			/**
			 * Filter the product current price with tax.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $price Product current price with tax.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_current_price_with_tax', $price, $this->product );
		}
		/**
		 * Get product sale price with tax.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function sale_price_with_tax( $catalog_attr, $product_attr, $export_columns ) {
			$sprice = $this->product->get_sale_price();
			$price = wc_get_price_including_tax( $this->product, array( 'price' => $sprice ) );
			if ( $price > 0 ) {
				$price = $price . ' ' . get_woocommerce_currency();
			}
			/**
			 * Filter the product sale price with tax.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $price Product sale price with tax.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_with_tax', $price, $this->product );
		}
		/**
		 * First variation price
		 *
		 * @return string
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
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_first_variation_price', $price, $this->product );
		}

		/**
		 * Get product weight.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function weight( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product weight.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $weight Product weight.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_weight', $this->product->get_weight(), $this->product );
		}

		/**
		 * Get product wunit.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function weight_unit( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product weight unit.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $weight_unit Product weight unit.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_weight_unit', get_option( 'woocommerce_weight_unit' ), $this->product );
		}

		/**
		 * Get product width.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function width( $catalog_attr, $product_attr, $export_columns ) {
			/**
 		     * Filter the product width.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $width Product width.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_width', $this->product->get_width(), $this->product );
		}

		/**
		 * Get product height.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function height( $catalog_attr, $product_attr, $export_columns ) {
			/**		
			 * Filter the product height.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $height Product height.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_height', $this->product->get_height(), $this->product );
		}

		/**
		 * Get product length.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function length( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product length.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $length Product length.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_length', $this->product->get_length(), $this->product );
		}

		/**
		 * Get product shipping.
		 *
		 * @param array  $catalog_attr Catalog attributes.
		 * @param array  $product_attr Product attributes.
		 * @param array  $export_columns Export columns.
		 * @param string $key Shipping.
		 * @return string
		 */
		public function shipping( $catalog_attr, $product_attr, $export_columns, $key = '' ) {

			$shipping_details = $this->get_shipping();
			$shipping_details_xml = array();
			$shipping_str = '';
			if ( isset( $shipping_details ) && is_array( $shipping_details ) ) {
				foreach ( $shipping_details as $k => $shipping_item ) {

					unset( $shipping_details['zone_name'] );
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

					// Add separator for multiple shipping method -  comma for facebook.
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
			 * @param array $shipping_details_xml Product shipping XML.
			 * @param array $shipping_details Product shipping details.
			 * @param object $product Product.
			 */
				return apply_filters( 'wt_feed_facebook_product_shipping_xml', $shipping_details_xml, $shipping_details, $this->product );
			}
			/**
			 * Filter the product shipping.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $shipping_str Product shipping.
			 * @param array $shipping_details Product shipping details.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_facebook_product_shipping', $shipping_str, $shipping_details, $this->product );
		}
		/**
		 * Get product shipping data.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function shipping_data( $catalog_attr, $product_attr, $export_columns ) {

			return $this->shipping( $catalog_attr, $product_attr, $export_columns );
		}
		/**
		 * Get shipping
		 *
		 * @return string
		 */
		public function get_shipping() {

			$shpping_country = $this->form_data['post_type_form_data']['item_country'];
			$shipping_obj = new Webtoffee_Product_Feed_Shipping( $this->product, 'facebook', $this->form_data );
			$shipping_info = $shipping_obj->get_shipping_by_location( $shpping_country );
			/**
			 * Filter the product shipping data.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $shipping_str Product shipping.
			 * @param array $shipping_details Product shipping details.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_processed_shipping_infos', $shipping_info, $this->product );
		}


		/**
		 * Get product shipping class.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function shipping_class( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product shipping class.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $shipping_class Product shipping class.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_shipping_class', $this->product->get_shipping_class(), $this->product );
		}
		/**
		 * Get product custom label0.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function custom_label_0( $catalog_attr, $product_attr, $export_columns ) {

			$custom_label_0 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_0', true );

			if ( '' == $custom_label_0 ) {
				$custom_label_0 = get_post_meta( $this->product->get_id(), '_wt_facebook_custom_label_0', true );
			}
			/**
			 * Filter the product custom label0.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $custom_label_0 Product custom label0.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_custom_label_0', $custom_label_0, $this->product );
		}
		/**
		 * Get product custom label1.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function custom_label_1( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_1 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_1', true );

			if ( '' == $custom_label_1 ) {
				$custom_label_1 = get_post_meta( $this->product->get_id(), '_wt_facebook_custom_label_1', true );
			}
			/**
			 * Filter the product custom label1.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $custom_label_1 Product custom label1.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_custom_label_1', $custom_label_1, $this->product );
		}
		/**
		 * Get product custom label2.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function custom_label_2( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_2 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_2', true );

			if ( '' == $custom_label_2 ) {
				$custom_label_2 = get_post_meta( $this->product->get_id(), '_wt_facebook_custom_label_2', true );
			}
			/**
			 * Filter the product custom label2.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $custom_label_2 Product custom label2.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_custom_label_2', $custom_label_2, $this->product );
		}
		/**
		 * Get product custom label3.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function custom_label_3( $catalog_attr, $product_attr, $export_columns ) {

			$custom_label_3 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_3', true );

			if ( '' == $custom_label_3 ) {
				$custom_label_3 = get_post_meta( $this->product->get_id(), '_wt_facebook_custom_label_3', true );
			}
			/**
			 * Filter the product custom label3.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $custom_label_3 Product custom label3.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_custom_label_3', $custom_label_3, $this->product );
		}
		/**
		 * Get product custom label4.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function custom_label_4( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_4 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_4', true );

			if ( '' == $custom_label_4 ) {
				$custom_label_4 = get_post_meta( $this->product->get_id(), '_wt_facebook_custom_label_4', true );
			}
			/**
			 * Filter the product custom label4.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $custom_label_4 Product custom label4.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_product_facebook_custom_label_4', $custom_label_4, $this->product );
		}



		/**
		 * Get product date created.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function date_created( $catalog_attr, $product_attr, $export_columns ) {
			$date_created = gmdate( 'Y-m-d', strtotime( $this->product->get_date_created() ) );
			/**
			 * Filter the product date created.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $date_created Product date created.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_date_created', $date_created, $this->product );
		}
		/**
		 * Get product date updated.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function date_updated( $catalog_attr, $product_attr, $export_columns ) {
			$date_updated = gmdate( 'Y-m-d', strtotime( $this->product->get_date_modified() ) );
			/**
			 * Filter the product date updated.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $date_updated Product date updated.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_date_updated', $date_updated, $this->product );
		}

		/** Get Facebook Sale Price effective date.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
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
		 * Get product tax data.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function tax_class( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product tax class.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $tax_class Product tax class.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_tax_class', $this->product->get_tax_class(), $this->product );
		}
		/**
		 * Get product tax status.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function tax_status( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product tax status.
			 *	
			 * @since 1.0.0
			 *
			 * @param string $tax_status Product tax status.
			 * @param object $product Product.
			 */		
			return apply_filters( 'wt_feed_filter_product_tax_status', $this->product->get_tax_status(), $this->product );
		}
		/**
		 * Filter taxonomy query
		 *
		 * @param array $query The query.
		 * @param array $query_vars The query vars.
		 * @return array
		 */
		public function wt_pf_exclude_cat_query( $query, $query_vars ) {
			if ( ! empty( $query_vars['exclude_category'] ) ) {

				$query['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => $query_vars['exclude_category'],
					'operator' => 'NOT IN',
				);
			}
			if ( ! empty( $query_vars['exclude_discarded'] ) ) {
				$query['meta_query'][] = array(
					array(
						'key' => '_wt_feed_discard',
						'compare' => 'NOT EXISTS', // this should exclude all exclude from feed checked products.
					),
				);
			}
			return $query;
		}
	}

}
