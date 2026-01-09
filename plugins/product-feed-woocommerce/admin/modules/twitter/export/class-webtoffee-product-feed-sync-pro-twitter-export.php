<?php
/**
 * Twitter export
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if (!defined('WPINC')) {
    exit;
}

if (!class_exists('Webtoffee_Product_Feed_Sync_Pro_Twitter_Export')) {
    /**
     * Webtoffee_Product_Feed_Sync_Pro_Twitter_Export Class.
     */
    class Webtoffee_Product_Feed_Sync_Pro_Twitter_Export extends Product_Feed_For_Woocommerce_Product {

        /**
         * Parent module object.
         *
         * @var object
         */
        public $parent_module = null;

        /**
         * Product object.
         *
         * @var object
         */
        public $product;

        /**
         * Current product ID.
         *
         * @var int
         */
        public $current_product_id;

        /**
         * Form data.
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
        public function __construct($parent_object) {
            $this->parent_module = $parent_object;
        }

        /**
         * Prepare CSV header.
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
            return apply_filters('wt_pf_alter_product_feed_csv_columns', $export_columns);
        }

        /**
         * Prepare data that will be exported.
         *
         * @param array $form_data Form data.
         * @param int $batch_offset Batch offset.
         * @param int $step Step.
         * @return array
         */
        public function prepare_data_to_export($form_data, $batch_offset, $step) {

            $this->form_data = $form_data;

            $exc_stock_status = !empty($form_data['post_type_form_data']['item_outofstock']) ? $form_data['post_type_form_data']['item_outofstock'] : '';
            
            if('' === $exc_stock_status){
                $exc_stock_status = !empty($form_data['post_type_form_data']['wt_pf_exclude_outofstock']) ? $form_data['post_type_form_data']['wt_pf_exclude_outofstock'] : '';
            }
            
            $item_parentonly = ! empty( $form_data['post_type_form_data']['item_parentonly'] ) ? $form_data['post_type_form_data']['item_parentonly'] : '';

			if ( '' === $item_parentonly ) {
				$item_parentonly = ! empty( $form_data['post_type_form_data']['wt_pf_include_parent_only'] ) ? $form_data['post_type_form_data']['wt_pf_include_parent_only'] : '';
			}
            
            $prod_exc_categories = !empty($form_data['post_type_form_data']['item_exc_cat']) ? $form_data['post_type_form_data']['item_exc_cat'] : array();            
            $prod_inc_categories = !empty($form_data['post_type_form_data']['item_inc_cat']) ? $form_data['post_type_form_data']['item_inc_cat'] : array();

            $cat_filter_type = !empty($form_data['post_type_form_data']['cat_filter_type']) ? $form_data['post_type_form_data']['cat_filter_type'] : '';
            
            if( '' === $cat_filter_type ){
                $cat_filter_type = !empty($form_data['post_type_form_data']['wt_pf_export_cat_filter_type']) ? $form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] : 'include_cat';
            }
            
            $inc_exc_category = !empty($form_data['post_type_form_data']['inc_exc_cat']) ? $form_data['post_type_form_data']['inc_exc_cat'] : array();
            if( empty($inc_exc_category) ){
                $inc_exc_category = !empty($form_data['post_type_form_data']['wt_pf_inc_exc_category']) ? $form_data['post_type_form_data']['wt_pf_inc_exc_category'] : array();
            }
            
            if ('include_cat' === $cat_filter_type) {
                $prod_inc_categories = $inc_exc_category;
            } else {
                $prod_exc_categories = $inc_exc_category;
            }

            
            $wt_pf_export_post_author = !empty($form_data['post_type_form_data']['wt_pf_export_post_author']) ? $form_data['post_type_form_data']['wt_pf_export_post_author'] : '';                            
            
            $brand_filter_type = !empty($form_data['post_type_form_data']['wt_pf_export_brand_filter_type']) ? $form_data['post_type_form_data']['wt_pf_export_brand_filter_type'] : 'include_brand';
            $inc_exc_brand = !empty($form_data['post_type_form_data']['wt_pf_inc_exc_brand']) ? $form_data['post_type_form_data']['wt_pf_inc_exc_brand'] : array();

            if ('include_brand' === $brand_filter_type) {
                $prod_inc_brands = $inc_exc_brand;
            } else {
                $prod_exc_brands = $inc_exc_brand;
            }            

            $prod_exc = !empty($form_data['post_type_form_data']['item_exc_prd']) ? $form_data['post_type_form_data']['item_exc_prd'] : array();

            if( empty($prod_exc) ){
                $prod_exc = !empty($form_data['post_type_form_data']['wt_pf_exclude_products']) ? $form_data['post_type_form_data']['wt_pf_exclude_products'] : array();
            }
            
            
            $tag_filter_type = !empty($form_data['post_type_form_data']['wt_pf_export_tag_filter_type']) ? $form_data['post_type_form_data']['wt_pf_export_tag_filter_type'] : 'include_tag';
            $inc_exc_tag = !empty($form_data['post_type_form_data']['wt_pf_inc_exc_tag']) ? $form_data['post_type_form_data']['wt_pf_inc_exc_tag'] : array();
            /* WPML
             * 
             */
            $item_post_lang = !empty($form_data['post_type_form_data']['wt_pf_export_post_language']) ? $form_data['post_type_form_data']['wt_pf_export_post_language'] : '';
            
            $prod_types = !empty($form_data['post_type_form_data']['item_product_type']) ? $form_data['post_type_form_data']['item_product_type'] : array();
            
            if( empty( $prod_types ) ){
                $prod_types = !empty($form_data['post_type_form_data']['wt_pf_product_types']) ? $form_data['post_type_form_data']['wt_pf_product_types'] : array();
            }
            
            $prod_status = !empty($form_data['filter_form_data']['wt_pf_product_status']) ? $form_data['filter_form_data']['wt_pf_product_status'] : array();

            $export_sortby = !empty($form_data['filter_form_data']['wt_pf_sort_columns']) ? $form_data['filter_form_data']['wt_pf_sort_columns'] : 'ID';
            $export_sort_order = !empty($form_data['filter_form_data']['wt_pf_order_by']) ? $form_data['filter_form_data']['wt_pf_order_by'] : 'ASC';

            $export_limit = !empty($form_data['filter_form_data']['wt_pf_limit']) ? intval($form_data['filter_form_data']['wt_pf_limit']) : 999999999; //user limit
            $current_offset = !empty($form_data['filter_form_data']['wt_pf_offset']) ? intval($form_data['filter_form_data']['wt_pf_offset']) : 0; //user offset

            $batch_count = !empty($form_data['advanced_form_data']['wt_pf_batch_count']) ? $form_data['advanced_form_data']['wt_pf_batch_count'] : Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings('default_export_batch');
            
            /**
             * Filter the batch count.
             *
             * @since 1.0.0
             *
             * @param string   $batch_count    Batch count.
             */
            $batch_count = apply_filters('wt_woocommerce_csv_export_limit_per_request', $batch_count); //ajax batch limit
            
            $real_offset = ($current_offset + $batch_offset);

            if ($batch_count <= $export_limit) {
                if (($batch_offset + $batch_count) > $export_limit) { //last offset
                    $limit = $export_limit - $batch_offset;
                } else {
                    $limit = $batch_count;
                }
            } else {
                $limit = $export_limit;
            }

            $product_array = array();
            $total_products = 0;
            if ($batch_offset < $export_limit) {
                $args = array(
                    'status' => array('publish'),
                    'type' => array_keys(wc_get_product_types()),
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

				if ( isset( $args['type'] ) && in_array( 'variation', $args['type'] ) && !in_array('variable', $args['type']) && !empty($item_parentonly)) {
					array_push( $args['type'], 'variable' );
				}

                if (!empty($prod_status)) {
                    $args['status'] = $prod_status;
                }           
                
                if( '' !== $wt_pf_export_post_author ){
                    $args['author'] = is_array( $wt_pf_export_post_author ) ? $wt_pf_export_post_author : implode(',', $wt_pf_export_post_author);
                }

                if (!empty($prod_exc_categories)) {
                    $args['exclude_category'] = $prod_exc_categories;
                }

                if( 'include_tag' === $tag_filter_type && !empty($inc_exc_tag) ){
                    $args['tag'] = $inc_exc_tag;
                }                
                if( 'exclude_tag' === $tag_filter_type && !empty($inc_exc_tag) ){
                    $args['exclude_tag'] = $inc_exc_tag;
                }
                
                if (!empty($prod_inc_brands)) {
                    $args['include_brands'] = $prod_inc_brands;
                }
                if (!empty($prod_exc_brands)) {
                    $args['exclude_brands'] = $prod_exc_brands;
                }                
                
                if (!empty($prod_inc_categories)) {
                    $args['category'] = $prod_inc_categories;
                }

                if (!empty($prod_exc)) {
                    $temp_prd_ids = $prod_exc;
                    foreach ($temp_prd_ids as $prod_exc_id) {
                        $crnt_prd = wc_get_product($prod_exc_id);
                        if ( $crnt_prd->is_type('variable' ) ) {
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

                if (!empty($exc_stock_status)) {
                    $available_status = wc_get_product_stock_status_options();
                    unset($available_status['outofstock']);                    
                    $args['stock_status'] = array_keys($available_status);
                }

                // Export all language products if WPML is active and the language selected is all.
                if (function_exists('icl_object_id') && isset($_SERVER["HTTP_REFERER"]) && strpos(sanitize_text_field(wp_unslash($_SERVER["HTTP_REFERER"])), 'lang=all') !== false) {
                    $args['suppress_filters'] = true; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- Needed to prevent filter interference during export
                }


                $args['exclude_discarded'] = '_wt_feed_discard'; // To exclude individual excluded from product fetching.

                /**
                 * Filter the product catalog arguments.
                 *
                 * @since 1.0.0
                 *
                 * @param array  $args    Arguments.
                 */
                $args = apply_filters("wt_feed_product_catalog_args", $args);

                /*
                 * WPML - Swicth language to selected language for temparory export
                 */
                if (class_exists('SitePress') && !empty($item_post_lang)) {
                    //$args['suppress_filters'] = true;
                    global $sitepress;
                    $current_lang = $sitepress->get_current_language(); // Take the current language to a variable to swicthback later.
                    $default_language = $sitepress->get_default_language();
                    $sitepress->switch_lang($item_post_lang);
                }

                $products = wc_get_products($args);

                $total_products = 0;
                if (0 == $batch_offset) { //first batch
                    $total_item_args = $args;
                    $total_item_args['limit'] = $export_limit; //user given limit
                    $total_item_args['offset'] = $current_offset; //user given offset
                    $total_products_count = wc_get_products($total_item_args);
                    $total_products = count($total_products_count->products);
                }

                /*
                 * WPML - Swicth language back to the previous site language after the batch reading.
                 */
                if (class_exists('SitePress') && !empty($item_post_lang)) {
                    //$args['suppress_filters'] = true;
                    global $sitepress;
                    $sitepress->switch_lang($current_lang); // Current language is previously stored
                }

                $products_ids = $products->products;

                // If include category is selected and variable products are under those category, the variations will not be returned by the WC query
                if ( !empty( $prod_inc_categories ) ) {
                    $temp_prod_ids = $products_ids;
                    foreach ($temp_prod_ids as $key => $product_id) {
                        $product = wc_get_product($product_id);
                        if ($product->is_type('variable')) {
                            $variations = $product->get_available_variations();
                            $variations_ids = wp_list_pluck($variations, 'variation_id');
                            foreach ($variations_ids as $variations_id) {
                                $products_ids[] = $variations_id;
                            }
                        }
                    }
                }

                foreach ($products_ids as $key => $product_id) {
                    $product = wc_get_product($product_id);

                    // Skip variations that belongs to a specific categories that is excluded in filter
                    if ($product->is_type('variation') && !empty($prod_exc_categories)) {
                        $parent_id = $product->get_parent_id();
                        if (has_term($prod_exc_categories, 'product_cat', $parent_id)) {
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
         * Get default variation.
         *
         * @param object $product Product.
         * @return array
         */
        public function get_default_variation($product) {

            $variation_id = false;

            foreach ($product->get_available_variations() as $variation_values) {
                foreach ($variation_values['attributes'] as $key => $attribute_value) {
                    $attribute_name = str_replace('attribute_', '', $key);
                    $default_value = $product->get_variation_default_attribute($attribute_name);
                    if ($default_value == $attribute_value) {
                        $is_default_variation = true;
                    } else {
                        $is_default_variation = false;
                        break; // Stop this loop to start next main lopp
                    }
                }

                if ($is_default_variation) {
                    $variation_id = $variation_values['variation_id'];
                    break; // Stop the main loop
                }
            }
            /**
             * Filter the default variation.
             *
             * @since 1.0.0
             *
             * @param string  $variation_id    Variation ID.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_default_variation', $variation_id, $product );
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
         * @param object $product_object Product object.
         * @return array
         */
        protected function generate_row_data_wc_lower($product_object) {

            $export_columns = $this->parent_module->get_selected_column_names();

            $product_id = $product_object->get_id();            
			$product = get_post( $product_id );

            $csv_columns = $export_columns;

            $export_columns = !empty($csv_columns) ? $csv_columns : array();

            $row = array();

            foreach ($export_columns as $key => $value) {

                if (method_exists($this, $value)) {                    
                    $row[$key] = $this->$value($key, $value, $export_columns);
                } elseif (strpos($value, 'meta:') !== false) {
                    $mkey = str_replace('meta:', '', $value);
                    if($product_object->is_type('variation')){
                        $product_id = $product_object->get_parent_id();
                    }
                    $row[$key] = wp_strip_all_tags( get_post_meta($product_id, $mkey, true) );
                    // TODO
                    // wt_image_ function can be replaced with key exist check
                } elseif (strpos($value, 'wt_static_map_vl:') !== false) { // Static value.
                    $static_feed_value = str_replace('wt_static_map_vl:', '', $value);
                    $row[$key] = $static_feed_value;
                } else {
                    $row[$key] = '';
                }
            }

            /**
             * Filter the row data.
             *
             * @since 1.0.0
             *
             * @param array  $row    Row.
             * @param object $product Product.
             */
            return apply_filters( "wt_batch_product_export_row_data", $row, $product );
        }

        /**
         * Get product id.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function id($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product id.
             *
             * @since 1.0.0
             *
             * @param string  $id    ID.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_id', $this->product->get_id(), $this->product);
        }

        /**
         * Get parent product title for variation.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function parent_title($catalog_attr, $product_attr, $export_columns) {
            $title = '';
            if ($this->product->is_type('variation')) {
                $parent_product = wc_get_product($this->product->get_parent_id());
                if (is_object($parent_product)) {
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
             * @param string  $title    Title.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_parent_title', $title, $this->product);
        }

        /**
         * Get product description with HTML tags.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function description_with_html($catalog_attr, $product_attr, $export_columns) {
            $description = $this->product->get_description();

            // Get Variation Description
            if (empty($description) && $this->product->is_type('variation')) {
                $description = '';
                if (!is_null($this->parent_product)) {
                    $description = $this->parent_product->get_description();
                }
            }

            if (empty($description)) {
                $description = $this->product->get_short_description();
            }

            //$description = CommonHelper::remove_shortcodes( $description );
            // Add variations attributes after description to prevent Facebook error
            if ($this->product->is_type('variation')) {
                $variationInfo = explode('-', $this->product->get_name());
                if (isset($variationInfo[1])) {
                    $extension = $variationInfo[1];
                } else {
                    $extension = $this->product->get_id();
                }
                $description .= ' ' . $extension;
            }

            //remove spacial characters
            $description = wp_check_invalid_utf8(wp_specialchars_decode($description), true);

            /**
             * Filter the product description with HTML.
             *
             * @since 1.0.0
             *
             * @param string  $description    Description.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_description_with_html', $description, $this->product);
        }      

        /**
         * Get product primary category.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string       
         */
        public function primary_category($catalog_attr, $product_attr, $export_columns) {
            $parent_category = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');

            $full_category = $this->product_type($catalog_attr, $product_attr, $export_columns);
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $parent_category = $full_category_array[0];
            }

            /**
             * Filter the product primary category.
             *
             * @since 1.0.0
             *
             * @param string  $parent_category    Parent category.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_primary_category', $parent_category, $this->product);
        }

        /**
         * Get product primary category id.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function primary_category_id($catalog_attr, $product_attr, $export_columns) {
            $parent_category_id = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');
            $full_category = $this->product_type($catalog_attr, $product_attr, $export_columns);
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $parent_category_obj = get_term_by('name', $full_category_array[0], 'product_cat');
                $parent_category_id = isset($parent_category_obj->term_id) ? $parent_category_obj->term_id : "";
            }

            /**
             * Filter the product primary category id.
             *
             * @since 1.0.0
             *
             * @param string  $parent_category_id    Parent category id.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_primary_category_id', $parent_category_id, $this->product);
        }

        /**
         * Get product child category.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function child_category($catalog_attr, $product_attr, $export_columns) {
            $child_category = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');
            $full_category = $this->product_type();
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $child_category = end($full_category_array);
            }

            /**
             * Filter the product child category.
             *
             * @since 1.0.0
             *
             * @param string  $child_category    Child category.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_child_category', $child_category, $this->product);
        }

        /**
         * Get product shipping.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function get_shipping() {

            $shpping_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
            $shipping_obj = new Webtoffee_Product_Feed_Shipping($this->product, 'google', $this->form_data);
            $shipping_info = $shipping_obj->get_shipping_by_location($shpping_country);

            if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
                    $selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
                    foreach ($shipping_info as $key => $value ){
                        $shipping_price = isset($value['price']) ? $value['price'] : 0;
                        if($shipping_price>0){
                            $shipping_price_convrtd = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($shipping_price, $selected_currency, $selected_country, $this->product);
                            $shipping_info[$key]['price'] = $shipping_price_convrtd;
                        }
                    }
				}
			}

            /**
             * Filter the product shipping.
             * @since 1.0.0
             *
             * @param array  $shipping_info    Shipping info. 
             * @param object $product Product.
             */
            return apply_filters("wt_feed_processed_shipping_infos", $shipping_info, $this->product);
        }

        /**
         * Get product child category id.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function child_category_id($catalog_attr, $product_attr, $export_columns) {
            $child_category_id = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');
            $full_category = $this->product_type();
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $child_category_obj = get_term_by('name', end($full_category_array), 'product_cat');
                $child_category_id = isset($child_category_obj->term_id) ? $child_category_obj->term_id : "";
            }

            /**
             * Filter the product child category id.
             *
             * @since 1.0.0
             *
             * @param string  $child_category_id    Child category id.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_child_category_id', $child_category_id, $this->product);
        }
              
        /**
		 * Get product google category.
		 *
		 * @param array $catalog_attr Catalog attributes.
		 * @param array $product_attr Product attributes.
		 * @param array $export_columns Export columns.
		 * @return string
		 */
		public function google_product_category( $catalog_attr, $product_attr, $export_columns ) {

			$custom_twitter_category = get_post_meta( $this->current_product_id, '_wt_google_google_product_category', true );

			if ( '' == $custom_twitter_category ) {

				$category_path = wp_get_post_terms( $this->current_product_id, 'product_cat', array( 'fields' => 'all' ) );

				$twitter_product_category = array();
				foreach ( $category_path as $category ) {
					$twitter_category_id = get_term_meta( $category->term_id, 'wt_google_category', true );
					if ( $twitter_category_id ) {

						$twitter_category_list = wp_cache_get( 'wt_fbfeed_google_product_categories_array' );

						if ( false === $twitter_category_list ) {
							$twitter_category_list             = Webtoffee_Product_Feed_Sync_Pro_Twitter::get_category_array();
							wp_cache_set( 'wt_fbfeed_google_product_categories_array', $twitter_category_list, '', WEEK_IN_SECONDS );
						}

						$twitter_category = isset( $twitter_category_list[ $twitter_category_id ] ) ? $twitter_category_list[ $twitter_category_id ] : '';
						if ( '' !== $twitter_category ) {
							$twitter_product_category[]    = $twitter_category;
						}
					}
				}

				$twitter_product_category = empty( $twitter_product_category ) ? '' : implode( ', ', $twitter_product_category );

			} else {

					$twitter_category_list = wp_cache_get( 'wt_fbfeed_google_product_categories_array' );

				if ( false === $twitter_category_list ) {
					$twitter_category_list             = Webtoffee_Product_Feed_Sync_Pro_Twitter::get_category_array();
					wp_cache_set( 'wt_fbfeed_google_product_categories_array', $twitter_category_list, '', WEEK_IN_SECONDS );
				}

					$twitter_product_category = $twitter_category_list[ $custom_twitter_category ];
			}
			/**
			* Filter the product twitter category.
			*
			* @since 1.0.0
			*
			* @param string  $twitter_product_category    Twitter product category.
			* @param object $product Product.
			*/
			return apply_filters( 'wt_feed_filter_product_twitter_category', $twitter_product_category, $this->product );
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

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
			/**
			* Filter the product type separator.
			*
			* @since 1.0.0
			*
			* @param string  $separator    Separator.
			* @param object $product Product.
			*/
			$separator          = apply_filters( 'wt_feed_product_type_separator', ' > ' );
			$product_categories = '';
			$term_list          = get_the_terms( $id, 'product_cat' );

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
			* @param string  $product_categories    Product categories.
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
        public function product_full_cat($catalog_attr, $product_attr, $export_columns) {

            $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );

            $separator = apply_filters('wt_feed_product_type_separator', ' > ', $this->product);

            $product_type = wp_strip_all_tags(wc_get_product_category_list($id, $separator));

            /**
             * Filter the product full category.
             *
             * @since 1.0.0
             *
             * @param string  $product_type    Product full category.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_local_category', $product_type, $this->product);
        }

        /**
         * Get product parent URL.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function parent_link($catalog_attr, $product_attr, $export_columns) {
            $link = $this->product->get_permalink();
            if ($this->product->is_type('variation')) {
                $link = $this->parent_product->get_permalink();
            }

            /**
             * Filter the product parent link.
             *
             * @since 1.0.0
             *
             * @param string  $link    Link.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_parent_link', $link, $this->product);
        }

        /**
         * Get product Canonical URL.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function canonical_link($catalog_attr, $product_attr, $export_columns) {
            //TODO: check if SEO plugin installed then return SEO canonical URL
            $canonical_link = $this->parent_link();

            /**
             * Filter the product canonical link.
             *
             * @since 1.0.0
             *
             * @param string  $canonical_link    Canonical link.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_canonical_link', $canonical_link, $this->product);
        }

        /**
         * Get external product URL.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function ex_link($catalog_attr, $product_attr, $export_columns) {
            $ex_link = '';
            if ($this->product->is_type('external')) {
                $ex_link = $this->product->get_product_url();
            }

            /**
             * Filter the product external link.
             *
             * @since 1.0.0
             *
             * @param string  $ex_link    External link.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_ex_link', $ex_link, $this->product);
        }

        /**
         * Get Formatted URL
         *
         * @param string $url
         *
         * @return string
         */
        public static function wt_feed_get_formatted_url($url = '') {
            if (!empty($url)) {
                if (substr(trim($url), 0, 4) === 'http' || substr(trim($url),
                                0,
                                3) === 'ftp' || substr(trim($url), 0, 4) === 'sftp') {
                    return rtrim($url, '/');
                } else {
                    $base = get_site_url();
                    $url = $base . $url;

                    return rtrim($url, '/');
                }
            }

            return '';
        }

        /**
         * Get product image URL.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function image_link($catalog_attr, $product_attr, $export_columns) {
            $image = '';
            if ($this->product->is_type('variation')) {
                // Variation product type
                if (has_post_thumbnail($this->product->get_id())) {
                    $getImage = wp_get_attachment_image_src(get_post_thumbnail_id($this->product->get_id()), 'single-post-thumbnail');
                    $image = self::wt_feed_get_formatted_url($getImage[0]);
                } elseif (has_post_thumbnail($this->product->get_parent_id())) {
                    $getImage = wp_get_attachment_image_src(get_post_thumbnail_id($this->product->get_parent_id()), 'single-post-thumbnail');
                    $image = self::wt_feed_get_formatted_url($getImage[0]);
                }
            } elseif (has_post_thumbnail($this->product->get_id())) { // All product type except variation
                $getImage = wp_get_attachment_image_src(get_post_thumbnail_id($this->product->get_id()), 'single-post-thumbnail');
                $image = isset($getImage[0]) ? self::wt_feed_get_formatted_url($getImage[0]) : '';
            }
            if ('' === $image) {
                $image = 'https://via.placeholder.com/300';
            }
            /**
             * Filter the product image.
             *
             * @since 1.0.0
             *
             * @param string  $image    Image.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_image', $image, $this->product);
        }

        /**
         * Get product featured image URL.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function feature_image($catalog_attr, $product_attr, $export_columns) {

            $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );

            $getImage = wp_get_attachment_image_src(get_post_thumbnail_id($id), 'single-post-thumbnail');
            $image = isset($getImage[0]) ? self::wt_feed_get_formatted_url($getImage[0]) : '';

            /**
             * Filter the product feature image.
             *
             * @since 1.0.0
             *
             * @param string  $image    Image.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_feature_image', $image, $this->product);
        }

        /**
         * Get product gallery.
         *
         * @param object $product Product.
         * @return array
         */
        public static function get_product_gallery($product) {
            $imgUrls = [];
            $attachmentIds = [];

            if ($product->is_type('variation')) {

                /**
                 * If any Variation Gallery Image plugin not installed then get Variable Product Additional Image Ids .
                 */
                $parent_prod = wc_get_product($product->get_parent_id());
                if (is_object($parent_prod)) {
                    $attachmentIds = $parent_prod->get_gallery_image_ids();
                }
            }

            /**
             * Get Variable Product Gallery Image ids if Product is not a variation
             * or variation does not have any gallery images
             */
            if (empty($attachmentIds)) {
                $attachmentIds = $product->get_gallery_image_ids();
            }

            if ($attachmentIds && is_array($attachmentIds)) {
                $mKey = 1;
                foreach ($attachmentIds as $attachmentId) {
                    $imgUrls[$mKey] = Wt_Pf_Catalog_Export_Helper::feed_get_formatted_url(wp_get_attachment_url($attachmentId));
                    $mKey++;
                }
            }

            return $imgUrls;
        }

        /**
         * Get product images (comma separated URLs).
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function images($catalog_attr, $product_attr, $export_columns, $additionalImg = '') {
            $imgUrls = self::get_product_gallery($this->product);
            $separator = apply_filters('wt_feed_filter_category_separator', ' > ', $this->product);

            // Return Specific Additional Image URL
            if ('' !== $additionalImg) {
                if (array_key_exists($additionalImg, $imgUrls)) {
                    $images = $imgUrls[$additionalImg];
                } else {
                    $images = '';
                }
            } else {

                $images = implode($separator, array_filter($imgUrls));
            }

            /**
             * Filter the product images.
             *
             * @since 1.0.0
             *
             * @param string  $images    Images.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_images', $images, $this->product);
        }

        /**
         * Get product images 1.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_1($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 1);
        }

        /**
         * Get product images 2.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_2($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 2);
        }

        /**
         * Get product images 3.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_3($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 3);
        }

        /**
         * Get product images 4.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_4($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 4);
        }

        /**
         * Get product images 5.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_5($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 5);
        }

        /**
         * Get product images 6.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_6($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 6);
        }

        /**
         * Get product images 7.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_7($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 7);
        }

        /**
         * Get product images 8.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_8($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 8);
        }

        /**
         * Get product images 9.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_9($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 9);
        }

        /**
         * Get product images 10.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function wtimages_10($catalog_attr, $product_attr, $export_columns) {
            return $this->images($catalog_attr, $product_attr, $export_columns, 10);
        }

        /**
         * Get product condition.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function condition($catalog_attr, $product_attr, $export_columns) {

            $custom_condition = get_post_meta($this->product->get_id(), '_wt_feed_condition', true);

            if ('' == $custom_condition) {
                $custom_condition = get_post_meta($this->product->get_id(), '_wt_twitter_condition', true);
            }

            if ('' == $custom_condition) {
                $custom_condition = get_post_meta($this->product->get_id(), '_wt_google_condition', true);
            }

            $condition = ('' == $custom_condition) ? 'new' : $custom_condition;

            /**
             * Filter the product condition.
             *
             * @since 1.0.0
             *
             * @param string  $condition    Condition.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_condition', $condition, $this->product);
        }

        /**
         * Get product age group.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function age_group($catalog_attr, $product_attr, $export_columns) {

            $age_group = get_post_meta($this->product->get_id(), '_wt_feed_agegroup', true);

            if ('' == $age_group) {
                $age_group = get_post_meta($this->product->get_id(), '_wt_twitter_agegroup', true);
            }

            if ('' == $age_group) {
                $age_group = get_post_meta($this->product->get_id(), '_wt_google_agegroup', true);
            }

            /**
             * Filter the product age group.
             *
             * @since 1.0.0
             *
             * @param string  $age_group    Age group.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_age_group', $age_group, $this->product);
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
				$gender = get_post_meta( $this->product->get_id(), '_wt_twitter_gender', true );
			}
			/**
			* Filter the product gender.
			*
			* @since 1.0.0
			*
			* @param string  $gender    Gender.
			* @param object $product Product.
			*/
			return apply_filters( 'wt_feed_twitter_product_gender', $gender, $this->product );
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
				$size = get_post_meta( $this->product->get_id(), '_wt_twitter_size', true );
			}
			/**
			* Filter the product size.
			*
			* @since 1.0.0
			*
			* @param string  $size    Size.
			* @param object $product Product.
			*/
			return apply_filters( 'wt_feed_twitter_product_size', $size, $this->product );
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
				$color = get_post_meta( $this->product->get_id(), '_wt_twitter_color', true );
			}
			/**
			 * Filter the product color.
			 *
			 * @since 1.0.0
			 *
			 * @param string  $color    Color.
			 * @param object $product Product.
			 */
			return apply_filters( 'wt_feed_twitter_product_color', $color, $this->product );
		}

        /**
         * Get product material.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function material($catalog_attr, $product_attr, $export_columns) {

            $material = get_post_meta($this->product->get_id(), '_wt_feed_material', true);
            if ('' == $material) {
                $material = get_post_meta($this->product->get_id(), '_wt_twitter_material', true);
            }
            /**
             * Filter the product material.
             *
             * @since 1.0.0
             *
             * @param string  $material    Material.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_material', $material, $this->product);
        }

        /**
         * Get product pattern.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function pattern($catalog_attr, $product_attr, $export_columns) {

            $pattern = get_post_meta($this->product->get_id(), '_wt_feed_pattern', true);
            if ('' == $pattern) {
                $pattern = get_post_meta($this->product->get_id(), '_wt_twitter_pattern', true);
            }
            /**
             * Filter the product pattern.
             *
             * @since 1.0.0
             *
             * @param string  $pattern    Pattern.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_pattern', $pattern, $this->product);
        }

        /**
         * Get product unit pricing measure.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.     
         * @return string
         */
        public function unit_pricing_measure($catalog_attr, $product_attr, $export_columns) {

            $unit_pricing_measure = get_post_meta($this->product->get_id(), '_wt_feed_unit_pricing_measure', true);
            if ('' == $unit_pricing_measure) {
                $unit_pricing_measure = get_post_meta($this->product->get_id(), '_wt_twitter_unit_pricing_measure', true);
            }
            /**
             * Filter the product unit pricing measure.
             *
             * @since 1.0.0
             *
             * @param string  $unit_pricing_measure    Unit pricing measure.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_unit_pricing_measure', $unit_pricing_measure, $this->product);
        }

        /**
         * Get product unit pricing base measure.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.         
         * @return string
         */
        public function unit_pricing_base_measure($catalog_attr, $product_attr, $export_columns) {

            $unit_pricing_base_measure = get_post_meta($this->product->get_id(), '_wt_feed_unit_pricing_base_measure', true);
            if ('' == $unit_pricing_base_measure) {
                $unit_pricing_base_measure = get_post_meta($this->product->get_id(), '_wt_twitter_unit_pricing_base_measure', true);
            }
            /**
             * Filter the product unit pricing base measure.
             *
             * @since 1.0.0
             *
             * @param string  $unit_pricing_base_measure    Unit pricing base measure.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_unit_pricing_base_measure', $unit_pricing_base_measure, $this->product);
        }

        /**
         * Get product energy efficiency class.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.             
         * @return string
         */
        public function energy_efficiency_class($catalog_attr, $product_attr, $export_columns) {

            $energy_efficiency_class = get_post_meta($this->product->get_id(), '_wt_feed_energy_efficiency_class', true);
            if ('' == $energy_efficiency_class) {
                $energy_efficiency_class = get_post_meta($this->product->get_id(), '_wt_twitter_energy_efficiency_class', true);
            }
            /**
             * Filter the product energy efficiency class.
             *
             * @since 1.0.0
             *
             * @param string  $energy_efficiency_class    Energy efficiency class.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_energy_efficiency_class', $energy_efficiency_class, $this->product);
        }

        /**
         * Get product min energy efficiency class.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                 
         * @return string
         */
        public function min_energy_efficiency_class($catalog_attr, $product_attr, $export_columns) {

            $min_energy_efficiency_class = get_post_meta($this->product->get_id(), '_wt_feed_min_energy_efficiency_class', true);
            if ('' == $min_energy_efficiency_class) {
                $min_energy_efficiency_class = get_post_meta($this->product->get_id(), '_wt_twitter_min_energy_efficiency_class', true);
            }
            /**
             * Filter the product min energy efficiency class.
             *
             * @since 1.0.0
             *
             * @param string  $min_energy_efficiency_class    Min energy efficiency class.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_min_energy_efficiency_class', $min_energy_efficiency_class, $this->product);
        }

        /**
         * Get product max energy efficiency class.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                 
         * @return string
         */
        public function max_energy_efficiency_class($catalog_attr, $product_attr, $export_columns) {

            $max_energy_efficiency_class = get_post_meta($this->product->get_id(), '_wt_feed_max_energy_efficiency_class', true);
            if ('' == $max_energy_efficiency_class) {
                $max_energy_efficiency_class = get_post_meta($this->product->get_id(), '_wt_twitter_max_energy_efficiency_class', true);
            }
            /**
             * Filter the product max energy efficiency class.
             *
             * @since 1.0.0
             *
             * @param string  $max_energy_efficiency_class    Max energy efficiency class.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_twitter_product_max_energy_efficiency_class', $max_energy_efficiency_class, $this->product);
        }

        /**
         * Get product brand.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                 
         * @return string
         */
        public function brand($catalog_attr, $product_attr, $export_columns) {


            $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );
            $custom_brand = get_post_meta($this->current_product_id, '_wt_feed_brand', true);
            if ( ! $custom_brand ) {
                $custom_brand = get_post_meta($this->product->get_id(), '_wt_twitter_brand', true);
            }
            if ( !$custom_brand ) {

                $brand = get_the_term_list($id, 'product_brand', '', ', ');

                $has_brand = true;
                if (is_wp_error($brand) || false === $brand) {
                    $has_brand = false;
                }

                if (!$has_brand && is_plugin_active('perfect-woocommerce-brands/perfect-woocommerce-brands.php')) {
                    $brand = get_the_term_list($id, 'pwb-brand', '', ', ');
                }

                $string = is_wp_error($brand) || !$brand ? wp_strip_all_tags(self::get_store_name()) : self::clean_string($brand);
                $length = 100;
                if (extension_loaded('mbstring')) {

                    if (mb_strlen($string, 'UTF-8') <= $length) {
                        /**
                         * Filter the product brand.
                         *
                         * @since 1.0.0
                         * 
                         * @param string  $string    String.
                         * @param object $product Product.
                         */
                        return apply_filters('wt_feed_filter_product_brand', $string, $this->product);
                    }

                    $length -= mb_strlen('...', 'UTF-8');

                    $brand_string = mb_substr($string, 0, $length, 'UTF-8') . '...';
                    /**
                     * Filter the product brand.
                     *
                     * @since 1.0.0
                     * 
                     * @param string  $brand_string    Brand string.
                     * @param object $product Product.
                     */
                    return apply_filters('wt_feed_filter_product_brand', $brand_string, $this->product);
                } else {

                    $string = filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                    $string = filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

                    if (strlen($string) <= $length) {
                        /**
                         * Filter the product brand.
                         *
                         * @since 1.0.0
                         * 
                         * @param string  $string    String.
                         * @param object $product Product.
                         */
                        return apply_filters('wt_feed_filter_product_brand', $string, $this->product);
                    }

                    $length -= strlen('...');

                    $brand_string = substr($string, 0, $length) . '...';
                    /**
                     * Filter the product brand.
                     *
                     * @since 1.0.0
                     * 
                     * @param array  $export_columns    Export columns.
                     */
                    return apply_filters('wt_feed_filter_product_brand', $brand_string, $this->product);
                }
            } else {
                /**
                 * Filter the product brand.
                 *
                 * @since 1.0.0
                 * 
                 * @param array  $export_columns    Export columns.
                 */
                return apply_filters('wt_feed_filter_product_brand', $custom_brand, $this->product);
            }
        }

        /**
         * Get product GTIN.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                     
         * @return string
         */
        public function gtin($catalog_attr, $product_attr, $export_columns) {

            $custom_gtin = get_post_meta($this->product->get_id(), '_wt_feed_gtin', true);
            if ('' == $custom_gtin) {
                $custom_gtin = get_post_meta($this->product->get_id(), '_wt_twitter_gtin', true);
            }
            if(!$custom_gtin){
                $custom_gtin = get_post_meta($this->product->get_id(), '_global_unique_id', true);
            }
            $gtin = ('' == $custom_gtin) ? '' : $custom_gtin;
            /**
             * Filter the product GTIN.
             *
             * @since 1.0.0
             * 
             * @param string  $gtin    GTIN.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_gtin', $gtin, $this->product);
        }

        /**
         * Get product MPN.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                     
         * @return string
         */
        public function mpn($catalog_attr, $product_attr, $export_columns) {

            $custom_mpn = get_post_meta($this->product->get_id(), '_wt_feed_mpn', true);
            if ('' == $custom_mpn) {
                $custom_mpn = get_post_meta($this->product->get_id(), '_wt_twitter_mpn', true);
            }
            $mpn = ('' == $custom_mpn) ? '' : $custom_mpn;
            /**
             * Filter the product MPN.
             *
             * @since 1.0.0
             * 
             * @param string  $mpn    MPN.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_mpn', $mpn, $this->product);
        }

        /**
         * Get product identifier exists.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                     
         * @return string
         */
        public function identifier_exists($catalog_attr, $product_attr, $export_columns) {

            $identifier_exists = 'no';
            if (isset($export_columns['sku']) || isset($export_columns['brand'])) {
                $identifier_exists = 'yes';
            }
            /**
             * Filter the product identifier exists.
             *
             * @since 1.0.0
             * 
             * @param string  $identifier_exists    Identifier exists.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_identifier_exists', $identifier_exists, $this->product);
        }

        /**
         * Get product type.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                     
         * @return string
         */
        public function type($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product type.
             *
             * @since 1.0.0
             * 
             * @param string  $type    Type.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_type', $this->product->get_type(), $this->product);
        }

        /**
         * Get product is bundle.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                     
         * @return string
         */
        public function is_bundle($catalog_attr, $product_attr, $export_columns) {
            $is_bundle = 'no';
            if ($this->product->is_type('bundle') || $this->product->is_type('yith_bundle') || $this->product->is_type('bopobb') ) {
                $is_bundle = 'yes';
            }

            /**
             * Filter the product is bundle.
             *
             * @since 1.0.0
             * 
             * @param string  $is_bundle    Is bundle.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_is_bundle', $is_bundle, $this->product);
        }

        /**
         * Get product multipack.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.                     
         * @return string
         */
        public function multipack($catalog_attr, $product_attr, $export_columns) {
            $multi_pack = '';
            if ($this->product->is_type('grouped')) {
                $multi_pack = (!empty($this->product->get_children()) ) ? count($this->product->get_children()) : '';
            }

            /**
             * Filter the product multipack.
             *
             * @since 1.0.0
             * 
             * @param string  $multi_pack    Multi pack.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_is_multipack', $multi_pack, $this->product);
        }

        /**
         * Get product visibility.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function visibility($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product visibility.
             *
             * @since 1.0.0
             * 
             * @param string  $visibility    Visibility.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_visibility', $this->product->get_catalog_visibility(), $this->product);
        }

        /**
         * Get product rating total.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function rating_total($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product rating total.
             *
             * @since 1.0.0
             * 
             * @param string  $rating_total    Rating total.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_rating_total', $this->product->get_rating_count(), $this->product);
        }

        /**
         * Get product rating average.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function rating_average($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product rating average.
             *
             * @since 1.0.0
             * 
             * @param string  $rating_average    Rating average.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_rating_average', $this->product->get_average_rating(), $this->product);
        }

        /**
         * Get product total sold.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function total_sold($catalog_attr, $product_attr, $export_columns) {
            //Todo
        }

        /**
         * Get product tags.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function tags($catalog_attr, $product_attr, $export_columns) {

            $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );

            /**
             * Separator for multiple tags
             *
             * @param string                     $separator
             * @param array                      $config
             * @param WC_Abstract_Legacy_Product $product
             *
             * @since 1.0.0
             * 
             * @param array  $export_columns    Export columns.
             */
            $separator = apply_filters('wt_feed_tags_separator', ',', $this->product);

            $tags = wp_strip_all_tags( get_the_term_list($id, 'product_tag', '', $separator, '') );

            /**
             * Filter the product tags.
             *
             * @since 1.0.0
             * 
             * @param string  $tags    Tags.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_tags', $tags, $this->product);
        }

        /**
         * Get product item group id.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function item_group_id($catalog_attr, $product_attr, $export_columns) {

            $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );

            /**
             * Filter the product item group id.
             *
             * @since 1.0.0
             * 
             * @param string  $id    Item group id.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_item_group_id', $id, $this->product);
        }

        /**
         * Get product sku.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function sku($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product sku.
             *
             * @since 1.0.0
             * 
             * @param string  $sku    SKU.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_sku', $this->product->get_sku(), $this->product);
        }

        /**
         * Get product sku id.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function sku_id($catalog_attr, $product_attr, $export_columns) {


            $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );

            $sku = !empty($this->product->get_sku()) ? $this->product->get_sku() . '_' : '';
            $sku_id = $sku . $id;

            /**
             * Filter the product sku id.
             *
             * @since 1.0.0
             * 
             * @param string  $sku_id    SKU ID.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_sku_id', $sku_id, $this->product);
        }

        /**
         * Get store name.
         *
         * @return string
         */
        public static function get_store_name() {

            $url = get_bloginfo('name');
            return ( $url) ? ( $url ) : 'My Store';
        }

        /**
         * Clean up strings for FB Graph POSTing.
         * This function should will:
         * 1. Replace newlines chars/nbsp with a real space
         * 2. strip_tags()
         * 3. trim()
         *
         * @access public
         * @param String string
         * @return string
         */
        public static function clean_string($string) {
            $string = do_shortcode($string);
            $string = str_replace(array('&amp%3B', '&amp;'), '&', $string);
            $string = str_replace(array("\r", '&nbsp;', "\t"), ' ', $string);
            $string = wp_strip_all_tags($string, false); // true == remove line breaks
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
			* @param string  $parent_sku    Parent SKU.
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
        public function availability($catalog_attr, $product_attr, $export_columns) {

            $status = 'in_stock';
            if($this->product->is_type('bundle')){
                $bundled_items = $this->product->get_bundled_items();
                if (!empty( $bundled_items ) ) {
                    foreach ( $bundled_items as $bundled_item ) {
                        $stock_status = $bundled_item->product->get_stock_status();
                        if ('outofstock' === $stock_status) {
                            $status = $stock_status;
                            break;
                        }
                    }
                }
            }else{
                $status = $this->product->get_stock_status();
            }
            if ('instock' === $status) {
                $status = 'in_stock';
            } elseif ('outofstock' === $status) {
                $status = 'out_of_stock';
            } elseif ('onbackorder' === $status) {
                $status = 'backorder';
            } elseif ('preorder' === $status) {
                $status = 'preorder';
            }

            /**
             * Filter the product availability.
             *
             * @since 1.0.0
             * 
             * @param string  $status    Status.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_availability', $status, $this->product);
        }

        /**
         * Get product availability date.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function availability_date($catalog_attr, $product_attr, $export_columns) {


            $availability_date = get_post_meta($this->product->get_id(), '_wt_feed_availability_date', true);

            if ( $availability_date ) {
                $availability_date = gmdate('c', strtotime($availability_date));
            }

            /**
             * Filter the product availability date.
             *
             * @since 1.0.0
             * 
             * @param string  $availability_date    Availability date.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_availability_date', $availability_date, $this->product);
        }

        /**
         * Get product add to cart link.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function add_to_cart_link($catalog_attr, $product_attr, $export_columns) {
            $url = $this->link();
            $suffix = 'add-to-cart=' . $this->product->get_id();

            $add_to_cart_link = wt_feed_make_url_with_parameter($url, $suffix);

            /**
             * Filter the product add to cart link.
             *
             * @since 1.0.0
             * 
             * @param string  $add_to_cart_link    Add to cart link.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_add_to_cart_link', $add_to_cart_link, $this->product);
        }

        /**
         * Get product quantity.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function quantity($catalog_attr, $product_attr, $export_columns) {
            $quantity = $this->product->get_stock_quantity();
            $status = $this->product->get_stock_status();

            //when product is outofstock , and it's quantity is empty, set quantity to 0
            if ('outofstock' === $status && $quantity === null) {
                $quantity = 0;
            }

            if ($this->product->is_type('variable') && $this->product->has_child()) {
                $visible_children = $this->product->get_visible_children();
                $qty = array();
                foreach ($visible_children as $child) {
                    $childQty = get_post_meta($child, '_stock', true);
                    $qty[] = (int) $childQty;
                }

                $quantity = array_sum($qty);
            }
            if ($this->product->is_type('variation')) {
                $parent_variations_qty = !empty($this->form_data['post_type_form_data']['wt_pf_parent_qty']) ? $this->form_data['post_type_form_data']['wt_pf_parent_qty'] : '';
                if( 'sumof_variation_qty' == $parent_variations_qty ){   
                    $parent_product = wc_get_product($this->product->get_parent_id());
                    $visible_children = $parent_product->get_visible_children();
                    $qty = array();
                    foreach ($visible_children as $child) {
                        $childQty = get_post_meta($child, '_stock', true);
                        $qty[] = (int) $childQty;
                    }
                    $quantity = array_sum($qty);
                }     
            }

            /**
             * Filter the product quantity.
             *
             * @since 1.0.0
             * 
             * @param string  $quantity    Quantity.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_quantity', $quantity, $this->product);
        }

        /**
         * Get Store Currency.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function currency($catalog_attr, $product_attr, $export_columns) {

            $currency = get_option('woocommerce_currency');
            if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
				}
			}

            /**
             * Filter the product currency.
             *
             * @since 1.0.0
             * 
             * @param string  $currency    Currency.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_currency', $currency, $this->product);
        }

        /**
         * Get Product Sale Price start date.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function sale_price_sdate($catalog_attr, $product_attr, $export_columns) {
            $startDate = $this->product->get_date_on_sale_from();
            if (is_object($startDate)) {
                $sale_price_sdate = $startDate->date_i18n();
            } else {
                $sale_price_sdate = '';
            }

            /**
             * Filter the product sale price start date.
             *
             * @since 1.0.0
             * 
             * @param string  $sale_price_sdate    Sale price start date.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product);
        }

        /**
         * Get Product Sale Price End Date.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns. 
         * @return string
         */
        public function sale_price_edate($catalog_attr, $product_attr, $export_columns) {
            $endDate = $this->product->get_date_on_sale_to();
            if (is_object($endDate)) {
                $sale_price_edate = $endDate->date_i18n();
            } else {
                $sale_price_edate = "";
            }

            /**
             * Filter the product sale price end date.
             *
             * @since 1.0.0
             * 
             * @param string  $sale_price_edate    Sale price end date.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product);
        }

        /**
         * Get first variation price.
         *
         * @return string
         */
        public function first_variation_price() {

            $children = $this->product->get_visible_children();
            $price = $this->product->get_variation_price();
            if (isset($children[0]) && !empty($children[0])) {
                $variation = wc_get_product($children[0]);
                $price = $variation->get_price();
            }

            /**
             * Filter the product first variation price.
             *
             * @since 1.0.0
             * 
             * @param string  $price    Price.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_first_variation_price', $price, $this->product);
        }        
        /**
		 * Get product converted price.
		 *
		 * @param int    $price Product price.
		 * @param string $selected_currency Selected country.
         * @param array $export_columns Export columns. 
		 * @return string
		 */
		public function get_converted_price( $price, $selected_currency ) {

			if ( get_woocommerce_currency() !== $selected_currency && $price > 0 ) {
					$wcml_mc = new WCML_Multi_Currency();
					$currencies = $wcml_mc->get_currencies( true );

					$woo_currencies       = get_woocommerce_currencies();

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
			$selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($price, $selected_currency, $selected_country, $this->product);
				}
			}

			if ( $price > 0 ) {
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product price.
			 * @since 1.0.0
			 *
			 * @param string  $price    Price.
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
        public function current_price($catalog_attr, $product_attr, $export_columns) {
            $price = $this->product->get_price();

            $selected_currency = get_woocommerce_currency();
            $selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
            if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($price, $selected_currency, $selected_country, $this->product);
				}
			}

            if ($price > 0) {
                                
                // woo-discount-rules plugin compatiblity                
                //$price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);
                
                $need_decimal_format = apply_filters('wt_wc_format_decimal_needed', true);
                if($need_decimal_format){
                    $price = wc_format_decimal($price, 2);
                }
                $price = $price . ' ' . $selected_currency;
            }
            return apply_filters('wt_feed_filter_product_current_price', $price, $this->product);
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
			$selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
			if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($price, $selected_currency, $selected_country, $this->product);
				}
			}

			if ( $price > 0 ) {
				$price = $price . ' ' . $selected_currency;
			}
			/**
			 * Filter the product sale price.
			 *
			 * @since 1.0.0
			 *
			 * @param string  $price    Price.
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
        public function price_with_tax($catalog_attr, $product_attr, $export_columns) {

            $tprice = $this->product->get_regular_price();
            $price = wc_get_price_including_tax($this->product, array('price' => $tprice));
            
            $selected_currency = get_woocommerce_currency();
            $selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
            if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($price, $selected_currency, $selected_country, $this->product);
				}
			}
            
            if ($price > 0) {
                                
                // woo-discount-rules plugin compatiblity                
                //$price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);
                
                $need_decimal_format = apply_filters('wt_wc_format_decimal_needed', true);
                if($need_decimal_format){
                    $price = wc_format_decimal($price, 2);
                }
                $price = $price . ' ' . $selected_currency;
            }
            /**
             * Filter the product price with tax.
             *
             * @since 1.0.0
             * 
             * @param string  $price    Price.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_price_with_tax', $price, $this->product);
        }   

        /**
         * Get product current price with tax.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function current_price_with_tax($catalog_attr, $product_attr, $export_columns) {
            $cprice = $this->product->get_price();
            $price = wc_get_price_including_tax($this->product, array('price' => $cprice));
            
            $selected_currency = get_woocommerce_currency();
            $selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
            if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($price, $selected_currency, $selected_country, $this->product);
				}
			}         
            
            if ($price > 0) {
                                
                // woo-discount-rules plugin compatiblity                
                //$price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);
                
                $need_decimal_format = apply_filters('wt_wc_format_decimal_needed', true);
                if($need_decimal_format){
                    $price = wc_format_decimal($price, 2);
                };
                $price = $price . ' ' . $selected_currency;
            }   
            /**
             * Filter the product price with tax.
             *
             * @since 1.0.0 
             * 
             * @param string  $price    Price.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_current_price_with_tax', $price, $this->product);
        }

        /**
         * Get product sale price with tax.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function sale_price_with_tax($catalog_attr, $product_attr, $export_columns) {
            $sprice = $this->product->get_sale_price();
            $price = wc_get_price_including_tax($this->product, array('price' => $sprice));
            
            $selected_currency = get_woocommerce_currency();
            $selected_country = $this->form_data['post_type_form_data']['wt_pf_export_catalog_country'];
            if ( Webtoffee_Product_Feed_Sync_Pro_Admin::is_multi_currency_active() ) {
				if ( ! empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ) {
					$selected_currency = $this->form_data['post_type_form_data']['wt_pf_export_post_currency'];
					$price = Webtoffee_Product_Feed_Sync_Pro_Admin::get_converted_price($price, $selected_currency, $selected_country, $this->product);
				}
			}         
            
            if ($price > 0) {
                                
                // woo-discount-rules plugin compatiblity                
                //$price = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $this->product, 1, $price, 'discounted_price', true, true);
                
                $need_decimal_format = apply_filters('wt_wc_format_decimal_needed', true);
                if($need_decimal_format){
                    $price = wc_format_decimal($price, 2);
                }
                $price = $price . ' ' . $selected_currency;
            }
            /**
             * Filter the product current price with tax.
             *
             * @since 1.0.0
             * 
             * @param string  $price    Price.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_sale_price_with_tax', $price, $this->product);
        }

        /**
         * Get Product Weight.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function weight($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product weight.
             *
             * @since 1.0.0
             * 
             * @param string  $weight    Weight.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_weight', $this->product->get_weight(), $this->product);
        }       

        /**
         * Get Weight Unit.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function weight_unit($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product weight unit.
             *
             * @since 1.0.0
             * 
             * @param string  $weight_unit    Weight unit.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_weight_unit', get_option('woocommerce_weight_unit'), $this->product);
        }

        /**
         * Get Product Width.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function width($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product width.
             *
             * @since 1.0.0
             * 
             * @param string  $width    Width.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_width', $this->product->get_width(), $this->product);
        }

        /**
         * Get Product Height.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function height($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product height.
             *
             * @since 1.0.0
             * 
             * @param string  $height    Height.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_height', $this->product->get_height(), $this->product);
        }

        /**
         * Get Product Length.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function length($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product length.
             *
             * @since 1.0.0
             * 
             * @param string  $length    Length.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_length', $this->product->get_length(), $this->product);
        }

        /**
         * Google Formatted Shipping info
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @param string $key Key.
         * @return string
         */
        public function shipping($catalog_attr, $product_attr, $export_columns, $key = '') {


            $shipping_details = apply_filters( 'wt_feed_google_shipping_details', array(), $this->product, $this->form_data );
            
            if( empty( $shipping_details ) ){
                $shipping_details = $this->get_shipping();
            }
            
            $selected_currency = !empty( $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ? $this->form_data['post_type_form_data']['wt_pf_export_post_currency'] : get_woocommerce_currency();
            
            $shipping_details_xml = array();
            $shipping_str = '';

            if (isset($shipping_details) && is_array($shipping_details)) {
                foreach ($shipping_details as $k => $shipping_item) {

                    unset($shipping_details['zone_name']);

                    if (isset($shipping_item['region']) && empty($shipping_item['region'])) {
                        unset($shipping_item['region']);
                    }

                    $shipping_child = '';
                    foreach ($shipping_item as $shipping_item_attr => $shipping_value) {

                        if ('price' === $shipping_item_attr) {
                            $shipping_value = number_format($shipping_value, 2) . ' ' . $selected_currency;
                        }
                        if (( 'zone_name' !== $shipping_item_attr)) {
                            $shipping_details_xml[$k][$shipping_item_attr] = $shipping_value;
                        }
                        if ('postal_code' !== $shipping_item_attr) {
                            $shipping_child .= $shipping_value . ":";
                        }
                    }
                    $shipping_child = trim($shipping_child, ":");

                    //Add separator for multiple shipping method -  comma for google
                    $shipping_str .= $shipping_child . ',';
                }

                $shipping_str = trim($shipping_str, ',');
            }

            if (isset($this->form_data['advanced_form_data']['wt_pf_file_as']) && 'xml' === $this->form_data['advanced_form_data']['wt_pf_file_as']) {
                /**
                 * Filter the google product shipping xml.
                 *
                 * @since 1.0.0
                 * 
                 * @param array  $shipping_details_xml    Shipping details xml.
                 * @param array  $shipping_details    Shipping details.
                 * @param object $product Product.
                 */
                return apply_filters('wt_feed_google_product_shipping_xml', $shipping_details_xml, $shipping_details, $this->product);
            }

            /**
             * Filter the google product shipping.
             *
             * @since 1.0.0
             * 
             * @param string  $shipping_str    Shipping string.
             * @param array  $shipping_details    Shipping details.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_google_product_shipping', $shipping_str, $shipping_details, $this->product);
        }

        /**
         * Get shipping data.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function shipping_data($catalog_attr, $product_attr, $export_columns) {

            return $this->shipping($catalog_attr, $product_attr, $export_columns);
        }
        
        /**
         * Get product inventory.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string 
         */
        public function inventory( $catalog_attr, $product_attr, $export_columns ) {
            if ( $this->product->is_type( 'variation' ) ) {

                    $variation_obj	 = new WC_Product_variation( $this->product->get_id() );
                    $stock			 = $variation_obj->get_stock_quantity();
            } else {
                    $stock = $this->product->get_stock_quantity();
            }
            /**
             * Filter the product inventory.
             *
             * @since 1.0.0
             * 
             * @param string  $stock    Stock.
             * @param object $product Product.
             */
            return apply_filters( 'wt_feed_twitter_product_get_inventory', $stock, $this->product );
        }        

        /**
         * Get Shipping Cost.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function shipping_cost($catalog_attr, $product_attr, $export_columns) {
            //Todo
        }

        /**
         * Get Product Shipping Class
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function shipping_class($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product shipping class.
             *
             * @since 1.0.0
             * 
             * @param string  $shipping_class    Shipping class.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_shipping_class', $this->product->get_shipping_class(), $this->product);
        }

        /**
         * Get custom label 0.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function custom_label_0($catalog_attr, $product_attr, $export_columns) {

            $custom_label_0 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_0', true);

            if ('' == $custom_label_0) {
                $custom_label_0 = get_post_meta($this->product->get_id(), '_wt_google_custom_label_0', true);
            }
            /**
             * Filter the product custom label 0.
             *
             * @since 1.0.0
             * 
             * @param string  $custom_label_0    Custom label 0.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_google_custom_label_0', $custom_label_0, $this->product);
        }

        /**
         * Get custom label 1.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function custom_label_1($catalog_attr, $product_attr, $export_columns) {
            $custom_label_1 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_1', true);

            if ('' == $custom_label_1) {
                $custom_label_1 = get_post_meta($this->product->get_id(), '_wt_google_custom_label_1', true);
            }
            /**
             * Filter the product custom label 1.
             *
             * @since 1.0.0
             * 
             * @param string  $custom_label_1    Custom label 1.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_google_custom_label_1', $custom_label_1, $this->product);
        }

        /**
         * Get custom label 2.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function custom_label_2($catalog_attr, $product_attr, $export_columns) {
            $custom_label_2 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_2', true);

            if ('' == $custom_label_2) {
                $custom_label_2 = get_post_meta($this->product->get_id(), '_wt_google_custom_label_2', true);
            }
            /**
             * Filter the product custom label 2.
             *
             * @since 1.0.0
             * 
             * @param string  $custom_label_2    Custom label 2.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_google_custom_label_2', $custom_label_2, $this->product);
        }

        /**
         * Get custom label 3.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function custom_label_3($catalog_attr, $product_attr, $export_columns) {

            $custom_label_3 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_3', true);

            if ('' == $custom_label_3) {
                $custom_label_3 = get_post_meta($this->product->get_id(), '_wt_google_custom_label_3', true);
            }
            /**
             * Filter the product custom label 3.
             *
             * @since 1.0.0
             * 
             * @param string  $custom_label_3    Custom label 3.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_google_custom_label_3', $custom_label_3, $this->product);
        }

        /**
         * Get custom label 4.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function custom_label_4($catalog_attr, $product_attr, $export_columns) {
            $custom_label_4 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_4', true);

            if ('' == $custom_label_4) {
                $custom_label_4 = get_post_meta($this->product->get_id(), '_wt_google_custom_label_4', true);
            }
            /**
             * Filter the product custom label 4.
             *
             * @since 1.0.0
             * 
             * @param string  $custom_label_4    Custom label 4.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_product_google_custom_label_4', $custom_label_4, $this->product);
        }

        /**
         * Get Date Created.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function date_created($catalog_attr, $product_attr, $export_columns) {
            $date_created = gmdate('Y-m-d', strtotime($this->product->get_date_created()));

            /**
             * Filter the product date created.
             *
             * @since 1.0.0
             * 
             * @param string  $date_created    Date created.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_date_created', $date_created, $this->product);
        }

        /**
         * Get Date updated.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function date_updated($catalog_attr, $product_attr, $export_columns) {
            $date_updated = gmdate('Y-m-d', strtotime($this->product->get_date_modified()));

            /**
             * Filter the product date updated.
             *
             * @since 1.0.0
             * 
             * @param string  $date_updated    Date updated.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_date_updated', $date_updated, $this->product);
        }

        /** Get Google Sale Price effective date.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function sale_price_effective_date($catalog_attr, $product_attr, $export_columns) {
            $effective_date = '';
            $from = $this->sale_price_sdate($catalog_attr, $product_attr, $export_columns);
            $to = $this->sale_price_edate($catalog_attr, $product_attr, $export_columns);
            if (!empty($from) && !empty($to)) {
                $from = gmdate('c', strtotime($from));
                $to = gmdate('c', strtotime($to));

                $effective_date = $from . '/' . $to;
            }

            /**
             * Filter the product sale price effective date.
             *
             * @since 1.0.0
             * 
             * @param string  $effective_date    Effective date.
             * @param object $product Product.
             */
            return $effective_date;
        }

        /**
         * Get product tax class.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function tax_class($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product tax class.
             *
             * @since 1.0.0
             * 
             * @param string  $tax_class    Tax class.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_tax_class', $this->product->get_tax_class(), $this->product);
        }

        /**
         * Get product tax status.
         *
         * @param array $catalog_attr Catalog attributes.
         * @param array $product_attr Product attributes.
         * @param array $export_columns Export columns.
         * @return string
         */
        public function tax_status($catalog_attr, $product_attr, $export_columns) {
            /**
             * Filter the product tax status.
             *
             * @since 1.0.0
             * 
             * @param string  $tax_status    Tax status.
             * @param object $product Product.
             */
            return apply_filters('wt_feed_filter_product_tax_status', $this->product->get_tax_status(), $this->product);
        }

    }

}
