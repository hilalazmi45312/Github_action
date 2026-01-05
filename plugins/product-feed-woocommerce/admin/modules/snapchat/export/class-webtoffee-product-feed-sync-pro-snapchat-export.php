<?php
/**
 * Handles the Snapchat export actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\Snapchat
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Snapchat_Export' ) ) {

	/**
	 * Class for handling Snapchat product feed export functionality
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Snapchat_Export extends Product_Feed_For_Woocommerce_Product {

		/**
		 * Parent module object
		 *
		 * @var object
		 */
		public $parent_module = null;

		/**
		 * Current product object
		 *
		 * @var WC_Product
		 */
		public $product;

		/**
		 * Current product ID
		 *
		 * @var int
		 */
		public $current_product_id;

		/**
		 * Form data for the feed
		 *
		 * @var array
		 */
		public $form_data;

		/**
		 * Mapping of Snapchat feed keys
		 *
		 * @var array
		 */
		public $snapchat_feed_keys = array();

		/**
		 * Constructor
		 *
		 * @param object $parent_object Parent module object.
		 */
		public function __construct( $parent_object ) {
			$this->parent_module = $parent_object;
		}

		/**
		 * Prepare header columns for the feed.
		 *
		 * @param array $form_data Feed configuration data.
		 * @return array Modified column headers.
		 */
		public function prepare_header( $form_data ) {

			$export_columns = $this->parent_module->get_selected_column_names();

			/**
			 * Filters the CSV column headers for product feed.
			 *
			 * @since 1.0.0
			 * @param array $export_columns Array of column headers.
			 */
			return apply_filters( 'wt_pf_alter_product_feed_csv_columns', $export_columns );
		}

		/**
		 * Prepare product data for export.
		 *
		 * @param array $form_data Feed configuration data.
		 * @param int   $batch_offset Current batch offset.
		 * @param int   $step Current step.
		 * @return array Product data for export.
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
			 * Filter the batch count.
			 *
			 * @since 1.0.0
			 *
			 * @param int $batch_count Batch count.
			 * @return int
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
			 * @return array
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
			 * Filter the product export row data.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $row Export row.
			 * @param object $product Product.
			 * @return array
			 */
			return apply_filters( 'wt_batch_product_export_row_data', $row, $product );
		}

		/**
		 * Get product ID
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed Product ID.
		 */
		public function id( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product ID.
			 *
			 * @since 1.0.0
			 * @param int        $id      Product ID.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_id', $this->product->get_id(), $this->product );
		}

		/**
		 * Get promotion ID for the product
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return mixed Promotion ID.
		 */
		public function promotion_id( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product promotion ID.
			 *
			 * @since 1.0.0
			 * @param int        $id      Product promotion ID.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_promotion_id', $this->product->get_id(), $this->product );
		}

		/**
		 * Get parent product title for variation
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Parent product title or current product title.
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
			 * Filters the parent product title.
			 *
			 * @since 1.0.0
			 * @param string     $title   Parent product title.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_title', $title, $this->product );
		}

		/**
		 * Get product description with HTML tags
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product description with HTML.
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

			// Remove shortcodes from description.

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

			// Remove special characters.
			$description = wp_check_invalid_utf8( wp_specialchars_decode( $description ), true );

			/**
			 * Filters the product description with HTML.
			 *
			 * @since 1.0.0
			 * @param string     $description Product description with HTML.
			 * @param WC_Product $product     Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_description_with_html', $description, $this->product );
		}


		/**
		 * Get product primary category
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Primary category name.
		 */
		public function primary_category( $catalog_attr, $product_attr, $export_columns ) {
			$parent_category = '';
			/**
			 * Filters the product type separator.
			 *
			 * @since 1.0.0
			 * @param string $separator Separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );

			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$parent_category = $full_category_array[0];
			}

			/**
			 * Filters the primary category.
			 *
			 * @since 1.0.0
			 * @param string     $parent_category Primary/parent category name.
			 * @param WC_Product $product         Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_primary_category', $parent_category, $this->product );
		}

		/**
		 * Get product primary category ID
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string|int Primary category ID.
		 */
		public function primary_category_id( $catalog_attr, $product_attr, $export_columns ) {
			$parent_category_id = '';
			/**
			 * Filters the product type separator.
			 *
			 * @since 1.0.0
			 * @param string $separator Separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$parent_category_obj = get_term_by( 'name', $full_category_array[0], 'product_cat' );
				$parent_category_id = isset( $parent_category_obj->term_id ) ? $parent_category_obj->term_id : '';
			}

			/**
			 * Filters the primary category ID.
			 *
			 * @since 1.0.0
			 * @param string|int $parent_category_id Primary/parent category ID.
			 * @param WC_Product $product           Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_primary_category_id', $parent_category_id, $this->product );
		}

		/**
		 * Get product child category
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Child category name.
		 */
		public function child_category( $catalog_attr, $product_attr, $export_columns ) {
			$child_category = '';
			/**
			 * Filters the product type separator.
			 *
			 * @since 1.0.0
			 * @param string $separator Separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$child_category = end( $full_category_array );
			}

			/**
			 * Filters the child category.
			 *
			 * @since 1.0.0
			 * @param string     $child_category Child category name.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_child_category', $child_category, $this->product );
		}

		/**
		 * Get product child category ID
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string|int Child category ID.
		 */
		public function child_category_id( $catalog_attr, $product_attr, $export_columns ) {
			$child_category_id = '';
			/**
			 * Filters the product type separator.
			 *
			 * @since 1.0.0
			 * @param string $separator Separator.
			 */
			$separator = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$full_category = $this->product_type();
			if ( ! empty( $full_category ) ) {
				$full_category_array = explode( $separator, $full_category );
				$child_category_obj = get_term_by( 'name', $full_category_array[1], 'product_cat' );
				$child_category_id = isset( $child_category_obj->term_id ) ? $child_category_obj->term_id : '';
			}

			/**
			 * Filters the child category ID.
			 *
			 * @since 1.0.0
			 * @param string|int $child_category_id Child category ID.
			 * @param WC_Product $product           Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_child_category_id', $child_category_id, $this->product );
		}

		/**
		 * Get product full category path
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Full category path.
		 */
		public function product_type( $catalog_attr, $product_attr, $export_columns ) {
			$product_type = '';
			if ( is_object( $this->product ) ) {
				$product_type = $this->product->get_type();
			}

			/**
			 * Filters the product type.
			 *
			 * @since 1.0.0
			 * @param string     $product_type Product type.
			 * @param WC_Product $product      Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_type', $product_type, $this->product );
		}

		/**
		 * Get product parent URL
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Parent product URL.
		 */
		public function parent_url( $catalog_attr, $product_attr, $export_columns ) {
			$parent_url = '';
			if ( is_object( $this->product ) ) {
				$parent_url = get_permalink( $this->product->get_parent_id() );
			}

			/**
			 * Filters the product parent URL.
			 *
			 * @since 1.0.0
			 * @param string     $parent_url Parent product URL.
			 * @param WC_Product $product    Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_parent_url', $parent_url, $this->product );
		}

		/**
		 * Get product canonical URL
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Canonical URL.
		 */
		public function canonical_url( $catalog_attr, $product_attr, $export_columns ) {
			$canonical_url = '';
			if ( is_object( $this->product ) ) {
				$canonical_url = get_permalink( $this->product->get_id() );
			}

			/**
			 * Filters the product canonical URL.
			 *
			 * @since 1.0.0
			 * @param string     $canonical_url Canonical URL.
			 * @param WC_Product $product       Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_canonical_url', $canonical_url, $this->product );
		}

		/**
		 * Get external product URL
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string External product URL.
		 */
		public function external_url( $catalog_attr, $product_attr, $export_columns ) {
			$external_url = '';
			if ( is_object( $this->product ) ) {
				$external_url = $this->product->get_meta( '_wt_feed_external_url' );
			}

			/**
			 * Filters the product external URL.
			 *
			 * @since 1.0.0
			 * @param string     $external_url External product URL.
			 * @param WC_Product $product      Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_external_url', $external_url, $this->product );
		}

		/**
		 * Get shipping information in Google format
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @param string $key Optional shipping key.
		 * @return string|array Shipping information formatted for Google feed.
		 */
		public function shipping( $catalog_attr, $product_attr, $export_columns, $key = '' ) {

			$shipping_details = $this->get_shipping();
			$shipping_details_xml = array();
			$shipping_str = '';
			if ( isset( $shipping_details ) && is_array( $shipping_details ) ) {
				foreach ( $shipping_details as $k => $shipping_item ) {

					unset( $shipping_item['zone_name'] );

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

					// Add separator for multiple shipping method - comma for google.
					$shipping_str .= $shipping_child . ',';
				}

				$shipping_str = trim( $shipping_str, ',' );
			}

			if ( isset( $this->form_data['advanced_form_data']['wt_pf_file_as'] ) && 'xml' === $this->form_data['advanced_form_data']['wt_pf_file_as'] ) {
				/**
				 * Filters the Snapchat product shipping XML.
				 *
				 * @since 1.0.0
				 * @param array      $shipping_details_xml Formatted shipping details for XML.
				 * @param array      $shipping_details     Raw shipping details.
				 * @param WC_Product $product             Product object.
				 */
				return apply_filters( 'wt_feed_bing_product_shipping_xml', $shipping_details_xml, $shipping_details, $this->product );
			}

			/**
			 * Filters the Bing product shipping.
			 *
			 * @since 1.0.0
			 * @param string     $shipping_str     Formatted shipping string.
			 * @param array      $shipping_details Raw shipping details.
			 * @param WC_Product $product         Product object.
			 */
			return apply_filters( 'wt_feed_bing_product_shipping', $shipping_str, $shipping_details, $this->product );
		}

		/**
		 * Get shipping data
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string|array Shipping data in the same format as shipping().
		 */
		public function shipping_data( $catalog_attr, $product_attr, $export_columns ) {

			return $this->shipping( $catalog_attr, $product_attr, $export_columns );
		}

		/**
		 * Get shipping cost
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 */
		public function shipping_cost( $catalog_attr, $product_attr, $export_columns ) {
			// Todo: Implement shipping cost calculation.
		}

		/**
		 * Get product shipping class
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Shipping class slug.
		 */
		public function shipping_class( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product shipping class.
			 *
			 * @since 1.0.0
			 * @param string     $shipping_class Shipping class slug.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_shipping_class', $this->product->get_shipping_class(), $this->product );
		}

		/**
		 * Get custom label 0
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Custom label 0 value.
		 */
		public function custom_label_0( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_0 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_0', true );

			if ( '' == $custom_label_0 ) {
				$custom_label_0 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_0', true );
			}
			/**
			 * Filters the product custom label 0.
			 *
			 * @since 1.0.0
			 * @param string     $custom_label_0 Custom label 0 value.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_0', $custom_label_0, $this->product );
		}

		/**
		 * Get custom label 1
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Custom label 1 value.
		 */
		public function custom_label_1( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_1 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_1', true );

			if ( '' == $custom_label_1 ) {
				$custom_label_1 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_1', true );
			}
			/**
			 * Filters the product custom label 1.
			 *
			 * @since 1.0.0
			 * @param string     $custom_label_1 Custom label 1 value.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_1', $custom_label_1, $this->product );
		}

		/**
		 * Get custom label 2
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Custom label 2 value.
		 */
		public function custom_label_2( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_2 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_2', true );

			if ( '' == $custom_label_2 ) {
				$custom_label_2 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_2', true );
			}
			/**
			 * Filters the product custom label 2.
			 *
			 * @since 1.0.0
			 * @param string     $custom_label_2 Custom label 2 value.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_2', $custom_label_2, $this->product );
		}

		/**
		 * Get custom label 3
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Custom label 3 value.
		 */
		public function custom_label_3( $catalog_attr, $product_attr, $export_columns ) {

			$custom_label_3 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_3', true );

			if ( '' == $custom_label_3 ) {
				$custom_label_3 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_3', true );
			}
			/**
			 * Filters the product custom label 3.
			 *
			 * @since 1.0.0
			 * @param string     $custom_label_3 Custom label 3 value.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_3', $custom_label_3, $this->product );
		}

		/**
		 * Get custom label 4
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Custom label 4 value.
		 */
		public function custom_label_4( $catalog_attr, $product_attr, $export_columns ) {
			$custom_label_4 = get_post_meta( $this->product->get_id(), '_wt_feed_custom_label_4', true );

			if ( '' == $custom_label_4 ) {
				$custom_label_4 = get_post_meta( $this->product->get_id(), '_wt_google_custom_label_4', true );
			}
			/**
			 * Filters the product custom label 4.
			 *
			 * @since 1.0.0
			 * @param string     $custom_label_4 Custom label 4 value.
			 * @param WC_Product $product        Product object.
			 */
			return apply_filters( 'wt_feed_product_google_custom_label_4', $custom_label_4, $this->product );
		}


		/**
		 * Get product creation date
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product creation date in Y-m-d format.
		 */
		public function date_created( $catalog_attr, $product_attr, $export_columns ) {
			$date_created = gmdate( 'Y-m-d', strtotime( $this->product->get_date_created() ) );

			/**
			 * Filters the product creation date.
			 *
			 * @since 1.0.0
			 * @param string     $date_created Product creation date.
			 * @param WC_Product $product      Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_date_created', $date_created, $this->product );
		}

		/**
		 * Get product last update date
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product update date in Y-m-d format.
		 */
		public function date_updated( $catalog_attr, $product_attr, $export_columns ) {
			$date_updated = gmdate( 'Y-m-d', strtotime( $this->product->get_date_modified() ) );

			/**
			 * Filters the product update date.
			 *
			 * @since 1.0.0
			 * @param string     $date_updated Product update date.
			 * @param WC_Product $product      Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_date_updated', $date_updated, $this->product );
		}

		/**
		 * Get sale price start date
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Sale price start date in ISO 8601 format.
		 */
		public function sale_price_sdate( $catalog_attr, $product_attr, $export_columns ) {
			$start_date = $this->product->get_date_on_sale_from();
			if ( is_object( $start_date ) ) {
				$sale_price_sdate = $start_date->date_i18n();
			} else {
				$sale_price_sdate = '';
			}

			/**
			 * Filters the product sale price start date.
			 *
			 * @since 1.0.0
			 * @param string     $sale_price_sdate Sale price start date.
			 * @param WC_Product $product           Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product );
		}

		/**
		 * Get sale price end date
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Sale price end date in ISO 8601 format.
		 */
		public function sale_price_edate( $catalog_attr, $product_attr, $export_columns ) {
			$end_date = $this->product->get_date_on_sale_to();
			if ( is_object( $end_date ) ) {
				$sale_price_edate = $end_date->date_i18n();
			} else {
				$sale_price_edate = '';
			}

			/**
			 * Filters the product sale price end date.
			 *
			 * @since 1.0.0
			 * @param string     $sale_price_edate Sale price end date.
			 * @param WC_Product $product           Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product );
		}

		/**
		 * Get sale price effective date range
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Sale price effective date range in ISO 8601 format.
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
		 * Get product tax class
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Tax class.
		 */
		public function tax_class( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product tax class.
			 *
			 * @since 1.0.0
			 * @param string     $tax_class Tax class.
			 * @param WC_Product $product   Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_tax_class', $this->product->get_tax_class(), $this->product );
		}

		/**
		 * Get product tax status
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Tax status (taxable, shipping, none).
		 */
		public function tax_status( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product tax status.
			 *
			 * @since 1.0.0
			 * @param string     $tax_status Tax status.
			 * @param WC_Product $product    Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_tax_status', $this->product->get_tax_status(), $this->product );
		}

		/**
		 * Get product weight
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product weight.
		 */
		public function weight( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product weight.
			 *
			 * @since 1.0.0
			 * @param string     $weight  Product weight.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_weight', $this->product->get_weight(), $this->product );
		}

		/**
		 * Get weight unit
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Weight unit (kg, g, lbs, oz).
		 */
		public function weight_unit( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the weight unit.
			 *
			 * @since 1.0.0
			 * @param string     $weight_unit Weight unit from WooCommerce settings.
			 * @param WC_Product $product     Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_weight_unit', get_option( 'woocommerce_weight_unit' ), $this->product );
		}

		/**
		 * Get product width
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product width.
		 */
		public function width( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product width.
			 *
			 * @since 1.0.0
			 * @param string     $width   Product width.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_width', $this->product->get_width(), $this->product );
		}

		/**
		 * Get product height
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product height.
		 */
		public function height( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product height.
			 *
			 * @since 1.0.0
			 * @param string     $height  Product height.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_height', $this->product->get_height(), $this->product );
		}

		/**
		 * Get product length
		 *
		 * @param string $catalog_attr Catalog attribute.
		 * @param string $product_attr Product attribute.
		 * @param array  $export_columns Export columns.
		 * @return string Product length.
		 */
		public function length( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Filters the product length.
			 *
			 * @since 1.0.0
			 * @param string     $length  Product length.
			 * @param WC_Product $product Product object.
			 */
			return apply_filters( 'wt_feed_filter_product_length', $this->product->get_length(), $this->product );
		}

		/**
		 * Get shipping details
		 *
		 * @return array Array of shipping details including zones, prices and services.
		 */
		public function get_shipping() {
			$shpping_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			$shipping_obj = new Webtoffee_Product_Feed_Shipping( $this->product, 'google', $this->form_data );
			$shipping_info = $shipping_obj->get_shipping_by_location( $shpping_country );

			/**
			 * Filters the processed shipping information.
			 *
			 * @since 1.0.0
			 * @param array      $shipping_info Shipping information array.
			 * @param WC_Product $product       Product object.
			 */
			return apply_filters( 'wt_feed_processed_shipping_infos', $shipping_info, $this->product );
		}
	}

}
