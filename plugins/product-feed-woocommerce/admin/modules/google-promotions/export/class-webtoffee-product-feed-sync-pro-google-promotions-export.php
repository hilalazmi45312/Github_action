<?php
/**
 * Handles the Google export actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\GooglePromotions
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Google_Promotions_Export' ) ) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Google_Promotions_Export Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Google_Promotions_Export extends Product_Feed_For_Woocommerce_Product {
		/**
		 * Module parent object.		
		 *
		 * @var object
		 */
		public $parent_module = null;
		/**
		 * Module product object.
		 *
		 * @var object
		 */
		public $product;
		/**
		 * Module current product ID.
		 *
		 * @var int
		 */
		public $current_product_id;
		/**
		 * Module form data.
		 *
		 * @var array
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
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'wt_glpi_feed_exclude_cat_query' ), 10, 2 );
		}
		/**
		 * Prepare CSV header
		 *
		 * @return array
		 */
		public function prepare_header() {

			$export_columns = $this->parent_module->get_selected_column_names();
			/**
			 * Filter the export columns.
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
			 * Filter the CSV export limit per request.
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

				if ( isset( $args['type'] ) && in_array( 'variation', $args['type'] ) && !in_array('variable', $args['type']) && !empty($item_parentonly) ) {
					array_push( $args['type'], 'variable' );
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
					// TODO
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
			 * Filter the batch product export row data.
			 *
			 * @since 1.0.0
			 *
			 * @param array $row Export row.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_batch_product_export_row_data', $row, $product );
		}

		/**
		 * Get product store code.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function store_code( $catalog_attr, $product_attr, $export_columns ) {

			$store_code = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'glpi_store_code' );
			if ( '' === $store_code ) {
				$store_code = wp_strip_all_tags( self::get_store_name() );
			}
			/**
			 * Filter the product store code.
			 *
			 * @since 1.0.0
			 *
			 * @param string $store_code Store code.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_store_code', $store_code, $this->product );
		}

		/**
		 * Get product pickup method.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function pickup_method( $catalog_attr, $product_attr, $export_columns ) {

			$packing_method = get_post_meta( $this->product->get_id(), '_wt_feed_glpi_pickup_method', true );
			if ( '' == $packing_method ) {
				$packing_method = get_post_meta( $this->product->get_id(), '_wt_google_glpi_pickup_method', true );
			}
			/**
			 * Filter the product pickup method.
			 *
			 * @since 1.0.0
			 *
			 * @param string $packing_method Pickup method.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_packing_method', $packing_method, $this->product );
		}

		/**
		 * Get Packing SLA.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function pickup_sla( $catalog_attr, $product_attr, $export_columns ) {

			$packing_sla = get_post_meta( $this->product->get_id(), '_wt_feed_glpi_pickup_sla', true );
			if ( '' == $packing_sla ) {
				$packing_sla = get_post_meta( $this->product->get_id(), '_wt_google_glpi_pickup_sla', true );
			}
			/**
			 * Filter the product pickup SLA.
			 *
			 * @since 1.0.0
			 *
			 * @param string $packing_sla Pickup SLA.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_packing_sla', $packing_sla, $this->product );
		}

		/**
		 * Get product promotion id - id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function promotion_id( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filter the product promotion ID.
			 *
			 * @since 1.0.0
			 *
			 * @param int $id Promotion ID.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_promotion_id', $this->product->get_id(), $this->product );
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
			 * Filter the product SKU.
			 *
			 * @since 1.0.0
			 *
			 * @param string $sku SKU.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sku', $this->product->get_sku(), $this->product );
		}

				/**
				 * Get product name.
				 *
				 * @param array $catalog_attr Catalog attributes.
				 * @param array $product_attr Product attributes.
				 * @param array $export_columns Export columns.
				 * @return string
				 */
		public function long_title( $catalog_attr, $product_attr, $export_columns ) {

				$title = $this->product->get_name();

				// Add all available variation attributes to variation title.
			if ( $this->product->is_type( 'variation' ) && ! empty( $this->product->get_attributes() ) ) {
						$title      = $this->parent_product->get_name();
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
				 * Filter whether to include variation attributes in product title.
				 *
				 * @since 1.0.0
				 *
				 * @param bool $get_with_var_attributes Whether to include variation attributes.
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
		 * Store name
		 *
		 * @return string
		 */
		public static function get_store_name() {

			$url = get_bloginfo( 'name' );
			return ( $url ) ? ( $url ) : 'My Store';
		}
		/**
		 * Get product item group id.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return int
		 */
		public function item_group_id( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
			/**
			 * Filter the product item group ID.
			 *
			 * @since 1.0.0
			 *
			 * @param int $id Item group ID.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_item_group_id', $id, $this->product );
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
			 * @param string $status Status.
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
			 * @param string $availability_date Availability date.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_availability_date', $availability_date, $this->product );
		}
		/**
		 * Get product quantity.
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
			 * @param int $quantity Quantity.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_quantity', $quantity, $this->product );
		}

		/**
		 * Get product price sale start date.
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
			 * @param string $sale_price_sdate Sale price start date.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product );
		}

		/**
		 * Get product price sale end date.
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
			 * @param string $sale_price_edate Sale price end date.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product );
		}
		/**
		 * First variation price
		 *
		 * @return float
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
			 * @param float $price Price.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_first_variation_price', $price, $this->product );
		}
		/**
		 * Get product converted price.
		 *
		 * @param int    $price Product price.
		 * @param string $selected_currency Selected country.
		 * @return float
		 */
		public function get_converted_price( $price, $selected_currency ) {

			if ( get_woocommerce_currency() !== $selected_currency && $price > 0 ) {
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
			 * @param string $price Price.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_price', $price, $this->product );
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
			 * @param string $price Sale price.
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
			 * @param string $price Price with tax.
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
			 * @param string $price Current price with tax.
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
			 * @param string $price Sale price with tax.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_with_tax', $price, $this->product );
		}

		/** Get Google Sale Price effective date.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function promotion_effective_dates( $catalog_attr, $product_attr, $export_columns ) {
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
				 * Filter taxonomy query
				 *
				 * @param array $query The query.
				 * @param array $query_vars The query vars.
				 * @return array
				 */
		public function wt_glpi_feed_exclude_cat_query( $query, $query_vars ) {
			if ( ! empty( $query_vars['exclude_category'] ) ) {
				$query['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field' => 'id',
					'terms' => $query_vars['exclude_category'], // Use the value of previous block of code.
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
