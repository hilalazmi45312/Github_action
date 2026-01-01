<?php
/**
 * Handles the Google product reviews export actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\GoogleProductReviews
 * @version   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Google_ProductReviewsExport' ) ) {

	/**
	 * Webtoffee Product Feed Google Product Reviews Export
	 *
	 * @package Product Feed for WooCommerce
	 */
	class Webtoffee_Product_Feed_Google_ProductReviewsExport {

		/**	
		 * Parent module
		 *
		 * @var null
		 */
		public $parent_module = null;

		/**
		 * Product
		 *
		 * @var null
		 */
		public $product;

		/**
		 * Current product id
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
		 * Comment
		 *
		 * @var null
		 */
		public $comment;

		/**
		 * Review
		 *
		 * @var null
		 */
		public $review;

		/**
		 * Constructor
		 *
		 * @param object $parent_object Parent object.
		 */
		public function __construct( $parent_object ) {

			$this->parent_module = $parent_object;
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'wt_feed_exclude_cat_query' ), 10, 2 );
		}

		/**
		 * Prepare header
		 *
		 * @return array
		 */
		public function prepare_header() {

			$export_columns = $this->parent_module->get_selected_column_names();
			/**
			 * Wt pf alter product feed csv columns
			 *
			 * @param array $export_columns Export columns.
			 * @return array $export_columns
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_pf_alter_product_feed_csv_columns', $export_columns );
		}

		/**
		 * Prepare data that will be exported.
		 *
		 * @param array  $form_data Form data.
		 * @param int    $batch_offset Batch offset.
		 * @param string $step Step.
		 * @return array
		 */
		public function prepare_data_to_export( $form_data, $batch_offset, $step ) {

			$this->form_data = $form_data;

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

			/*
			 WPML
			 *
			 */
			$item_post_lang = ! empty( $form_data['post_type_form_data']['item_post_lang'] ) ? $form_data['post_type_form_data']['item_post_lang'] : '';

			if ( '' === $item_post_lang ) {
				$item_post_lang = ! empty( $form_data['post_type_form_data']['wt_pf_export_post_language'] ) ? $form_data['post_type_form_data']['wt_pf_export_post_language'] : '';
			}

			$prod_tags = ! empty( $form_data['filter_form_data']['wt_pf_product_tags'] ) ? $form_data['filter_form_data']['wt_pf_product_tags'] : array();

			$prod_types = ! empty( $form_data['post_type_form_data']['item_product_type'] ) ? $form_data['post_type_form_data']['item_product_type'] : array();

			if ( empty( $prod_types ) ) {
				$prod_types = ! empty( $form_data['post_type_form_data']['wt_pf_product_types'] ) ? $form_data['post_type_form_data']['wt_pf_product_types'] : array();
			}

			$prod_status = ! empty( $form_data['filter_form_data']['wt_pf_product_status'] ) ? $form_data['filter_form_data']['wt_pf_product_status'] : array();

			$export_sortby = ! empty( $form_data['filter_form_data']['wt_pf_sort_columns'] ) ? $form_data['filter_form_data']['wt_pf_sort_columns'] : 'ID';
			$export_sort_order = ! empty( $form_data['filter_form_data']['wt_pf_order_by'] ) ? $form_data['filter_form_data']['wt_pf_order_by'] : 'ASC';

			$products_ids = array();
			$args = array(
				'post_type' => 'product',
				'status' => array( 'publish' ),
				'type' => array_keys( wc_get_product_types() ),
				'return' => 'ids',
				'limit' => -1,
				'status' => 'any',
			);
			if ( empty( $item_parentonly ) ) {
				array_push( $args['type'], 'variation' );
			}

			if ( ! empty( $prod_status ) ) {
				$args['status'] = $prod_status;
			}

			if ( ! empty( $prod_types ) ) {
				// Remove 'variation' type if "Only include default product variation" is selected
				if ( ! empty( $item_parentonly ) ) {
					$args['type'] = array_diff( $prod_types, array( 'variation' ) );
				} else {
					$args['type'] = $prod_types;
				}
			}

			if ( 1 === count( $prod_types ) && 'variable' === $prod_types[0] && empty( $item_parentonly ) ) {
				array_push( $args['type'], 'variation' );
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
			$products_ids = wc_get_products( $args );

			$export_limit = ! empty( $form_data['filter_form_data']['wt_pf_limit'] ) ? intval( $form_data['filter_form_data']['wt_pf_limit'] ) : 999999999; // user limit.
			$current_offset = ! empty( $form_data['filter_form_data']['wt_pf_offset'] ) ? intval( $form_data['filter_form_data']['wt_pf_offset'] ) : 0; // user offset.

			$batch_count = ! empty( $form_data['advanced_form_data']['wt_pf_batch_count'] ) ? $form_data['advanced_form_data']['wt_pf_batch_count'] : Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'default_export_batch' );
			/**
			 * Wt product feed limit per request
			 *
			 * @param int $batch_count Batch count.
			 * @return int $batch_count
			 * @since 1.0.0
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
					'post_type' => 'product',
					'number' => $limit,
					'offset' => $real_offset,
					'return' => 'ids',
					'paginate' => true,
					'status' => 'any',
				);
				if ( ! empty( $products_ids ) ) {
					$args['post__in'] = $products_ids;
				}
				/**
				 * Wt feed product review args
				 *
				 * @param array $args Args.
				 * @return array $args
				 * @since 1.0.0
				 */
				$args = apply_filters( 'wt_feed_product_review_args', $args );

				$review_query = new WP_Comment_Query();
				$reviews = $review_query->query( $args );

				$total_reviews = 0;
				if ( 0 === $batch_offset ) { // first batch.
					$review_query = new WP_Comment_Query();
					$args = array(
						'count' => true,
						'post_type' => 'product',
						'status' => 'any',
					);
					if ( ! empty( $products_ids ) ) {
						$args['post__in'] = $products_ids;
					}
					$total_reviews = $review_query->query( $args );
				}

				foreach ( $reviews as $key => $review ) {

					$product_array[] = $this->generate_row_data( $review );
				}
			}

			$return_products = array(
				'total' => $total_reviews,
				'data' => $product_array,
			);
			if ( 0 === $batch_offset && ( 0 === $total_products || empty( $product_array ) ) ) {
				$return_products['no_post'] = __( 'Nothing to export under the selected criteria. Please try adjusting the filters.', 'product-feed-woocommerce' );
			}
			return $return_products;
		}

		/**
		 * Generate row data
		 *
		 * @param object $comment Comment.
		 * @return array
		 */
		protected function generate_row_data( $comment ) {

			$export_columns = $this->parent_module->get_selected_column_names();
			$this->review = $comment;
			$this->current_product_id = $comment->comment_post_ID;
			$row = array();

			$review_swap_key = array(
				'gtin',
				'mpn',
				'sku',
				'brand',
			);
			$review_combined_key = array(
				'reviewer_name',
				'reviewer_id',
			);
			foreach ( $export_columns as $key => $value ) {

				if ( in_array( $key, $review_swap_key ) ) {
					$value = 'review_product_details';
					$key = 'products';
				}

				if ( in_array( $key, $review_combined_key ) ) {
					$value = 'reviewer_name_id';
					$key = 'reviewer';
				}

				if ( strpos( $value, 'meta:' ) !== false ) {
					$mkey = str_replace( 'meta:', '', $value );
					$row[ $key ] = get_comment_meta( $comment->ID, $mkey, true );
					// TODO!
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
			 * Wt batch product export row data
			 *
			 * @param array $row Row.
			 * @param object $comment Comment.
			 * @return array $row
			 * @since 1.0.0
			 */
			return apply_filters( "wt_batch_product_export_row_data_{$this->parent_module->module_base}", $row, $comment );
		}

		/**
		 * Review id
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $review_attr Review attribute.
		 * @param type $export_columns Export columns.
		 */
		public function review_id( $catalog_attr, $review_attr, $export_columns ) {
			/**
			 * Wt feed filter review id
			 *
			 * @param string $review_id Review id.
			 * @param object $review Review.
			 * @return string $review_id
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_id', $this->review->comment_ID, $this->review );
		}

		/**
		 * Reviewer name
		 *
		 * @return string
		 */
		public function reviewer_name() {
			/**
			 * Wt feed filter reviewer name
			 *
			 * @param string $reviewer_name Reviewer name.
			 * @param object $review Review.
			 * @return string $reviewer_name
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_reviewer_name', $this->review->comment_author, $this->review );
		}

		/**
		 * Reviewer name id
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function reviewer_name_id( $catalog_attr, $product_attr, $export_columns ) {

			$reviewer_name_id = array();
			$reviewer_name_id['name'] = $this->review->comment_author;
			$reviewer_name_id['reviewer_id'] = $this->review->user_id;
			/**
			 * Wt feed filter reviewer name id
			 *
			 * @param array $reviewer_name_id Reviewer name id.
			 * @param object $review Review.
			 * @return array $reviewer_name_id
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_reviewer_name_id', $reviewer_name_id, $this->review );
		}

		/**
		 * Reviewer id
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function reviewer_id( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Wt feed filter reviewer id
			 *
			 * @param string $reviewer_id Reviewer id.
			 * @param object $review Review.
			 * @return string $reviewer_id
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_reviewer_id', $this->review->user_id, $this->review );
		}

		/**
		 * Review timestamp
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function review_timestamp( $catalog_attr, $product_attr, $export_columns ) {
			$date_time = $this->review->comment_date_gmt;

			$review_date = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date_time ) );
			/**
			 * Wt feed filter review timestamp
			 *
			 * @param string $review_date Review date.
			 * @param object $review Review.
			 * @return string $review_date
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_timestamp', $review_date, $this->review );
		}

		/**
		 * Review title
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function review_title( $catalog_attr, $product_attr, $export_columns ) {

			$title = get_comment_meta( $this->review->comment_ID, 'title', true );

			if ( '' == $title ) {
				$title = get_comment_meta( $this->review->comment_ID, 'reviewx_title', true );
			}
			/**
			 * Wt feed filter review title
			 *
			 * @param string $title Review title.
			 * @param object $review Review.
			 * @return string $title
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_title', $title, $this->review );
		}

		/**
		 * Review content
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function content( $catalog_attr, $product_attr, $export_columns ) {
			/**
			 * Wt feed filter review content
			 *
			 * @param string $content Review content.
			 * @param object $review Review.
			 * @return string $content
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_content', $this->review->comment_content, $this->review );
		}

		/**
		 * Review url
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function review_url( $catalog_attr, $product_attr, $export_columns ) {

			$product = wc_get_product( $this->current_product_id );
			$product_url = '';
			if ( $product instanceof WC_Product ) {
				$product_url = $product->get_permalink();
			}
			$review_url_details = array(
				'@attributes' => array( 'type' => 'group' ),
				'@value' => $product_url,
			);
			/**
			 * Wt feed filter review url
			 *
			 * @param array $review_url_details Review url details.
			 * @param object $review Review.
			 * @return array $review_url_details
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_url', $review_url_details, $this->review );
		}

		/**
		 * Ratings
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function ratings( $catalog_attr, $product_attr, $export_columns ) {

			$rating = get_comment_meta( $this->review->comment_ID, 'rating', true );

			$review_rating_details = array(
				'overall' => array(
					'@attributes' => array(
						'min' => 1,
						'max' => 5,
					),
					'@value' => $rating,
				),
			);
			/**
			 * Wt feed filter review rating
			 *
			 * @param array $review_rating_details Review rating details.
			 * @param object $review Review.
			 * @return array $review_rating_details
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_rating', $review_rating_details, $this->review );
		}

		/**
		 * Is spam
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function is_spam( $catalog_attr, $product_attr, $export_columns ) {

			$is_spam = ( 'spam' === $this->review->comment_approved ) ? 'true' : 'false';
			/**
			 * Wt feed filter review is spam
			 *
			 * @param string $is_spam Is spam.
			 * @param object $review Review.
			 * @return string $is_spam
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_is_spam', $is_spam, $this->review );
		}

		/**
		 * Product sku
		 *
		 * @return string
		 */
		public function product_sku() {

			$product = wc_get_product( $this->current_product_id );
			$sku = '';
			if ( $product instanceof WC_Product ) {
				$sku = $product->get_sku();
			}
			/**
			 * Wt feed filter review skus
			 *
			 * @param string $sku Sku.
			 * @param object $review Review.
			 * @return string $sku
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_skus', $sku, $this->review );
		}

		/**
		 * Product gtin
		 *
		 * @return string
		 */
		public function product_gtin() {

			$custom_gtin = get_post_meta( $this->current_product_id, '_wt_feed_gtin', true );
			if ( ! $custom_gtin ) {
				$custom_gtin = get_post_meta( $this->current_product_id, '_wt_google_gtin', true );
			}
			if ( ! $custom_gtin ) {
				$custom_gtin = get_post_meta( $this->current_product_id, '_global_unique_id', true );
			}
			$gtin = ( '' == $custom_gtin ) ? '' : $custom_gtin;
			/**
			 * Wt feed filter review gtin
			 *
			 * @param string $gtin Gtin.
			 * @param object $review Review.
			 * @return string $gtin
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_gtins', $gtin, $this->review );
		}

		/**
		 * Product mpn
		 *
		 * @return string
		 */
		public function product_mpn() {

			$custom_mpn = get_post_meta( $this->current_product_id, '_wt_feed_mpn', true );
			if ( ! $custom_mpn ) {
				$custom_mpn = get_post_meta( $this->current_product_id, '_wt_google_mpn', true );
			}
			if ( ! $custom_mpn ) {
				$custom_mpn = get_post_meta( $this->current_product_id, '_global_unique_id', true );
			}
			$mpn = ( '' == $custom_mpn ) ? '' : $custom_mpn;
			/**
			 * Wt feed filter review mpns
			 *
			 * @param string $mpn Mpn.
			 * @param object $review Review.
			 * @return string $mpn
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_review_mpns', $mpn, $this->review );
		}

		/**
		 * Product brand
		 *
		 * @return string
		 */
		public function product_brand() {

			$custom_brand = get_post_meta( $this->current_product_id, '_wt_feed_brand', true );
			if ( ! $custom_brand ) {
				$custom_brand = get_post_meta( $this->current_product_id, '_wt_google_brand', true );
			}
			if ( ! $custom_brand ) {

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
						 * Wt feed filter product brands
						 *
						 * @param string $brand_string Brand string.
						 * @param int $current_product_id Current product id.
						 * @return string $brand_string
						 * @since 1.0.0
						 */
						return apply_filters( 'wt_feed_filter_product_brands', $string, $this->current_product_id );
					}

					$length -= mb_strlen( '...', 'UTF-8' );

					$brand_string = mb_substr( $string, 0, $length, 'UTF-8' ) . '...';
					/**
					 * Wt feed filter product brands
					 *
					 * @param string $brand_string Brand string.
					 * @param int $current_product_id Current product id.
					 * @return string $brand_string
					 * @since 1.0.0
					 */
					return apply_filters( 'wt_feed_filter_product_brands', $brand_string, $this->current_product_id );
				} else {

					$string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );
					$string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

					if ( strlen( $string ) <= $length ) {
						/**
						 * Wt feed filter product brands
						 *
						 * @param string $brand_string Brand string.
						 * @param int $current_product_id Current product id.
						 * @return string $brand_string
						 * @since 1.0.0
						 */
						return apply_filters( 'wt_feed_filter_product_brands', $string, $this->current_product_id );
					}

					$length -= strlen( '...' );

					$brand_string = substr( $string, 0, $length ) . '...';
					/**
					 * Wt feed filter product brands
					 *
					 * @param string $brand_string Brand string.
					 * @param int $current_product_id Current product id.
					 * @return string $brand_string
					 * @since 1.0.0
					 */
					return apply_filters( 'wt_feed_filter_product_brands', $brand_string, $this->current_product_id );
				}
			} else {
				/**
				 * Wt feed filter product brands
				 *
				 * @param string $custom_brand Custom brand.
				 * @param int $current_product_id Current product id.
				 * @return string $custom_brand
				 * @since 1.0.0
				 */
				return apply_filters( 'wt_feed_filter_product_brands', $custom_brand, $this->current_product_id );
			}
		}

		/**
		 * Clean string
		 *
		 * @param string $string String.
		 * @return string $string
		 */
		public static function clean_string( $string ) {
			$string = do_shortcode( $string );
			$string = str_replace( array( '&amp%3B', '&amp;' ), '&', $string );
			$string = str_replace( array( "\r", '&nbsp;', "\t" ), ' ', $string );
			$string = wp_strip_all_tags( $string, false ); // true == remove line breaks.
			return $string;
		}

		/**
		 * Review product details
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function review_product_details( $catalog_attr, $product_attr, $export_columns ) {
			$reviewe_product = array();

			$product = wc_get_product( $this->current_product_id );

			if ( $product instanceof WC_Product ) {
				$prd_ids_details = array(
					'product_ids' => array(
						'gtins' => array( 'gtin' => $this->product_gtin() ),
						'mpns' => array( 'mpn' => $this->product_mpn() ),
						'skus' => array( 'sku' => $this->product_sku() ),
						'brands' => array( 'brand' => $this->product_brand() ),
					),
				);

				// If all identifiers are false, map Product name+Brand to the MPN field.
				if ( '' === $prd_ids_details['product_ids']['gtins']['gtin'] && '' === $prd_ids_details['product_ids']['mpns']['mpn'] && '' === $prd_ids_details['product_ids']['skus']['sku'] ) {
					$prd_ids_details['product_ids']['mpns']['mpn'] = $product->get_name() . ' ' . $this->product_brand();
				}

				$reviewe_product['product'] = $prd_ids_details;
				$reviewe_product['product']['product_name'] = $product->get_name();
				$reviewe_product['product']['product_url'] = $product->get_permalink();
			}
			/**
			 * Wt feed filter product product details
			 *
			 * @param array $reviewe_product Reviewe product.
			 * @param int $current_product_id Current product id.
			 * @return array $reviewe_product
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_product_details', $reviewe_product, $this->current_product_id );
		}

		/**
		 * Product name
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function product_name( $catalog_attr, $product_attr, $export_columns ) {

			$product = wc_get_product( $this->current_product_id );
			$product_name = '';
			if ( $product instanceof WC_Product ) {
				$product_name = $product->get_name();
			}
			/**
			 * Wt feed filter product title
			 *
			 * @param string $product_name Product name.
			 * @param int $current_product_id Current product id.
			 * @return string $product_name
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_title', $product_name, $this->current_product_id );
		}

		/**
		 * Product url
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function product_url( $catalog_attr, $product_attr, $export_columns ) {

			$product = wc_get_product( $this->current_product_id );
			$product_url = '';
			if ( $product instanceof WC_Product ) {
				$product_url = $product->get_permalink();
			}
			/**
			 * Wt feed filter product url
			 *
			 * @param string $product_url Product url.
			 * @param int $current_product_id Current product id.
			 * @return string $product_url
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_url', $product_url, $this->current_product_id );
		}

		/**
		 * Link
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function link( $catalog_attr, $product_attr, $export_columns ) {

			$product = wc_get_product( $this->current_product_id );
			$product_url = '';
			if ( $product instanceof WC_Product ) {
				$product_url = $product->get_permalink();
			}
			/**
			 * Wt feed filter product link
			 *
			 * @param string $product_url Product url.
			 * @param int $current_product_id Current product id.
			 * @return string $product_url
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_link', $product_url, $this->current_product_id );
		}

		/**
		 * Long title
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function long_title( $catalog_attr, $product_attr, $export_columns ) {

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
				 * Wt feed get product title with variation attribute
				 *
				 * @param bool $get_with_var_attributes Get with var attributes.
				 * @param object $product Product.
				 * @return bool $get_with_var_attributes
				 * @since 1.0.0
				 */
				$get_with_var_attributes = apply_filters( 'wt_feed_get_product_title_with_variation_attribute', true, $this->product );

				if ( $get_with_var_attributes ) {
					$title .= ' - ' . $variation_attributes;
				}
			}

			/**
			 * Wt feed filter product title
			 *
			 * @param string $title Title.
			 * @param object $product Product.
			 * @return string $title
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_title', $title, $this->product );
		}

		/**
		 * Get store name
		 *
		 * @return string
		 */
		public static function get_store_name() {

			$url = get_bloginfo( 'name' );
			return ( $url ) ? ( $url ) : 'My Store';
		}

		/**
		 * Item group id
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
		 */
		public function item_group_id( $catalog_attr, $product_attr, $export_columns ) {

			$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
			/**
			 * Wt feed filter product item group id
			 *
			 * @param int $id Item group id.
			 * @param object $product Product.
			 * @return int $id
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_item_group_id', $id, $this->product );
		}

		/**
		 * Availability
		 *
		 * @param type $catalog_attr Catalog attribute.
		 * @param type $product_attr Product attribute.
		 * @param type $export_columns Export columns.
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
			 * Wt feed filter product availability
			 *
			 * @param string $status Status.
			 * @param object $product Product.
			 * @return string $status
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_filter_product_availability', $status, $this->product );
		}

		/**
		 * Filter taxonomy query
		 *
		 * @param array $query The query.
		 * @param array $query_vars The query vars.
		 * @return array
		 */
		public function wt_feed_exclude_cat_query( $query, $query_vars ) {
			if ( ! empty( $query_vars['exclude_category'] ) ) {
				$query['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $query_vars['exclude_category'], // Use the value of previous block of code.
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
