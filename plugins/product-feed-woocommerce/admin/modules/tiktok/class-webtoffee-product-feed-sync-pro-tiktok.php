<?php
/**
 * Tiktok Ads product feed module
 *
 * @link   
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Tiktok
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Tiktok' ) ) {

	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Tiktok Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Tiktok {

		/**
		 *  Module ID
		 *
		 * @var string
		 */
		public $module_id = '';

		/**
		 *  Module ID static
		 *
		 * @var string
		 */
		public static $module_id_static = '';

		/**
		 *  Module base
		 *
		 * @var string
		 */
		public $module_base = 'tiktok';

		/**
		 *  Module name
		 *
		 * @var string
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Tiktok';

		/**
		 *  Module importer
		 *
		 * @var string
		 */
		private $importer = null;

		/**
		 *  Module exporter
		 *
		 * @var string
		 */
		private $exporter = null;

		/**
		 *  Product categories
		 *
		 * @var string
		 */
		private $product_categories = null;

		/**
		 *  Product tags
		 *
		 * @var string
		 */
		private $product_tags = null;

		/**
		 *  Product taxonomies
		 *
		 * @var string
		 */
		private $product_taxonomies = array();

		/**
		 *  All meta keys
		 *
		 * @var string
		 */
		private $all_meta_keys = array();

		/**
		 *  Product attributes
		 *
		 * @var string
		 */
		private $product_attributes = array();

		/**
		 *  Exclude hidden meta columns
		 *
		 * @var string
		 */
		private $exclude_hidden_meta_columns = array();

		/**
		 *  Found product meta
		 *
		 * @var string
		 */
		private $found_product_meta = array();

		/**
		 *  Found product hidden meta
		 *
		 * @var string
		 */
		private $found_product_hidden_meta = array();

		/**
		 *  Selected column names
		 *
		 * @var string
		 */
		private $selected_column_names = null;
		/**
		 * Constructor
		 */
		public function __construct() {

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return;
			}

			$this->module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_filter( 'wt_pf_exporter_post_types_basic', array( $this, 'wt_pf_exporter_post_types_basic' ), 10, 1 );

			add_filter( 'wt_pf_exporter_alter_filter_fields_basic', array( $this, 'exporter_alter_filter_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_alter_mapping_fields_basic', array( $this, 'exporter_alter_mapping_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_alter_advanced_fields_basic', array( $this, 'exporter_alter_advanced_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_alter_meta_mapping_fields_basic', array( $this, 'exporter_alter_meta_mapping_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_alter_mapping_enabled_fields_basic', array( $this, 'exporter_alter_mapping_enabled_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_do_export_basic', array( $this, 'exporter_do_export' ), 10, 7 );

			add_filter( 'wt_pf_feed_category_mapping', array( $this, 'map_tiktok_category' ), 10, 1 );

			add_action( 'edit_product_cat', array( $this, 'wt_fbfeed_category_form_save_pro' ), 10, 1 );
			add_action( 'create_category', array( $this, 'wt_fbfeed_category_form_save_pro' ), 10, 1 );
		}

		/**
		 * Category form.
		 *
		 * @param int $term_id Term ID.
		 * @return bool
		 */
		public function wt_fbfeed_category_form_save_pro( $term_id ) {

			if ( isset( $_POST['wt_google_category'] ) ) {
				$wt_category_edit_nonce = isset( $_POST['wt_category_edit_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_category_edit_nonce'] ) ) : '';
				if ( isset( $_POST['wt_category_edit_nonce'] ) && ! wp_verify_nonce( $wt_category_edit_nonce, 'wt_category_edit_nonce' ) ) {
					return false;
				}

				$wt_google_category = absint( $_POST['wt_google_category'] );
				if ( 0 == $wt_google_category ) {
					delete_term_meta( $term_id, 'wt_google_category' );
				} else {
					update_term_meta( $term_id, 'wt_google_category', $wt_google_category );
				}
			}
		}
		/**
		 * Map tiktok category.
		 *
		 * @param array $form_data Form data.
		 * @return array
		 */
		public function map_tiktok_category( $form_data ) {

			if ( ( isset( $form_data['post_type_form_data']['item_type'] ) && $form_data['post_type_form_data']['item_type'] != $this->module_base ) || ( isset( $form_data['post_type_form_data']['wt_pf_export_post_type'] ) && $form_data['post_type_form_data']['wt_pf_export_post_type'] != $this->module_base ) ) {
				return $form_data;
			} else {

				foreach ( $form_data['category_mapping_form_data'] as $local_cat => $merchant_cat ) {
					if ( ! empty( $merchant_cat ) ) {
						$term_id = absint( str_replace( 'cat_mapping_', '', $local_cat ) );
						$wt_fb_category = absint( $merchant_cat );
						update_term_meta( $term_id, 'wt_google_category', $wt_fb_category );
					}
				}
				return $form_data;
			}
		}
		/**
		 * Exported do export
		 *
		 * @param array $export_data Export data
		 * @param string $base To export
		 * @param int $step Step
		 * @param array $form_data Form data
		 * @param array $selected_template_data Selected template
		 * @param string $method_export Method
		 * @param int $batch_offset Offset
		 * @return array
		 */
		public function exporter_do_export( $export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset ) {
			if ( $this->module_base != $base ) {
				return $export_data;
			}

			$this->set_selected_column_names( $form_data );

			include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path( __FILE__ ) . 'export/class-webtoffee-product-feed-sync-pro-tiktok-export.php';
			$export = new Webtoffee_Product_Feed_Sync_Pro_Tiktok_Export( $this );

			$header_row = $export->prepare_header();

			$data_row = $export->prepare_data_to_export( $form_data, $batch_offset, $step );

			$export_data = array(
				'head_data' => $header_row,
				'body_data' => $data_row['data'],
				'total' => $data_row['total'],
			);

			if ( isset( $data_row['no_post'] ) ) {
				$export_data['no_post'] = $data_row['no_post'];
			}

			return $export_data;
		}

		/**
		 * Adding current post type to export list
		 *
		 * @param array $arr Post types
		 * @return array
		 */
		public function wt_pf_exporter_post_types_basic( $arr ) {

			$arr['tiktok'] = __(
				'Tiktok Ads',
				'product-feed-woocommerce'
			);
			return $arr;
		}

		/**
		 * Read txt file which contains facebook taxonomy list.
		 *
		 * @return array
		 */
		public static function get_category_array() {
			// Get All Tiktok Taxonomies.

			$taxonomy = wp_cache_get( 'wt_pf_feed_google_categories' );

			if ( false === $taxonomy ) {

				$file_name = WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/google/data/google_taxonomy.txt';
				$custom_taxonomy_file = fopen( $file_name, 'r' );  // phpcs:ignore.
				$taxonomy = array();
				$taxonomy[''] = 'Do not map';
				if ( $custom_taxonomy_file ) {
					// First line contains metadata, ignore it.
					fgets( $custom_taxonomy_file );  // phpcs:ignore.
					while ( $line = fgets( $custom_taxonomy_file ) ) {  // phpcs:ignore.
						list( $cat_id, $cat ) = explode( '-', $line );
						$cat_key = absint( trim( $cat_id ) );
						$cat_val = trim( $cat );
						$taxonomy[ $cat_key ] = $cat_val;
					}
				}
				wp_cache_set( 'wt_pf_feed_google_categories', $taxonomy, '', WEEK_IN_SECONDS );
			}

			return $taxonomy;
		}

		/**
		 * Get product categories
		 *
		 * @return array
		 */
		private function get_product_categories() {
			if ( ! is_null( $this->product_categories ) ) {
				return $this->product_categories;
			}
			$out = array();
			$product_categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',  // WooCommerce product categories.
					'hide_empty' => false,           // Hide empty categories.
				)
			);
			if ( ! is_wp_error( $product_categories ) ) {
				$version = get_bloginfo( 'version' );
				foreach ( $product_categories as $category ) {
					$out[ $category->slug ] = ( ( $version < '4.8' ) ? $category->name : get_term_parents_list( $category->term_id, 'product_cat', array( 'separator' => ' -> ' ) ) );
				}
			}
			$this->product_categories = $out;
			return $out;
		}
		/**
		 * Get product tags
		 *
		 * @return array
		 */
		private function get_product_tags() {
			if ( ! is_null( $this->product_tags ) ) {
				return $this->product_tags;
			}
			$out = array();
			$product_tags = get_terms( 'product_tag' );
			if ( ! is_wp_error( $product_tags ) ) {
				foreach ( $product_tags as $tag ) {
					$out[ $tag->slug ] = $tag->name;
				}
			}
			$this->product_tags = $out;
			return $out;
		}
		/**
		 * Get product statuses
		 *
		 * @return array
		 */
		public static function get_product_statuses() {
			$product_statuses = array( 'publish', 'private', 'draft', 'pending', 'future' );
			/**
			 * Filter the product statuses.
			 *
			 * @since 1.0.0
			 *
			 * @param array $product_statuses Product statuses.
			 * @return array
			 */
			return apply_filters( 'wt_pf_allowed_product_statuses', array_combine( $product_statuses, $product_statuses ) );
		}
		/**
		 * Get post columns
		 *
		 * @return array
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path( __FILE__ ) . 'data/data-product-post-columns.php';
		}
		/**
		 * Get mapping fields
		 *
		 * @param array $mapping_enabled_fields Mappings.
		 * @param string $base Base.
		 * @param array $form_data_mapping_enabled_fields Enabled fields.
		 * @return array
		 */
		public function exporter_alter_mapping_enabled_fields( $mapping_enabled_fields, $base, $form_data_mapping_enabled_fields ) {
			if ( $base == $this->module_base ) {
				$mapping_enabled_fields = array();
				$mapping_enabled_fields['availability_price'] = array( __( 'Price Details', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['unique_product_identifiers'] = array( __( 'Unique Product Identifiers', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['detailed_product_attributes'] = array( __( 'Detailed Product Attributes', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['tax_shipping'] = array( __( 'Tax & Shipping', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['custom_label_identifiers'] = array( __( 'Custom Label Attributes', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['additional_attributes'] = array( __( 'Additional Attributes', 'product-feed-woocommerce' ), 1 );
			}
			return $mapping_enabled_fields;
		}
		/**
		 * Meta fields
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $step_page_form_data Step page form.
		 * @return array
		 */
		public function exporter_alter_meta_mapping_fields( $fields, $base, $step_page_form_data ) {
			if ( $base != $this->module_base ) {
				return $fields;
			}
			foreach ( $fields as $key => $value ) {
				switch ( $key ) {
					case 'availability_price':
						$fields[ $key ]['fields']['price'] = 'Regular Price[price]';
						$fields[ $key ]['fields']['sale_price'] = 'Sale Price[sale_price]';
						$fields[ $key ]['fields']['sale_price_effective_date'] = 'Sale Price Effective Date[sale_price_effective_date]';
						break;

					case 'unique_product_identifiers':
						$fields[ $key ]['fields']['brand'] = 'Manufacturer[brand]';
						$fields[ $key ]['fields']['gtin'] = 'GTIN[gtin]';
						$fields[ $key ]['fields']['mpn'] = 'MPN[mpn]';
						$fields[ $key ]['fields']['identifier_exists'] = 'Identifier Exist[identifier_exists]';
						break;

					case 'detailed_product_attributes':
						$fields[ $key ]['fields']['item_group_id'] = 'Item Group Id[item_group_id]';
						$fields[ $key ]['fields']['color'] = 'Color[color]';
						$fields[ $key ]['fields']['gender'] = 'Gender[gender]';
						$fields[ $key ]['fields']['age_group'] = 'Age Group[age_group]';
						$fields[ $key ]['fields']['material'] = 'Material[material]';
						$fields[ $key ]['fields']['pattern'] = 'Pattern[pattern]';
						$fields[ $key ]['fields']['size'] = 'Size of the item[size]';

						break;

					case 'tax_shipping':
						$fields[ $key ]['fields']['tax'] = 'Tax[tax]';
						$fields[ $key ]['fields']['shipping'] = 'Shipping';
						$fields[ $key ]['fields']['weight'] = 'Shipping Weight[shipping_weight]';

						break;

					case 'custom_label_identifiers':
						$fields[ $key ]['fields']['custom_label_0'] = 'Custom label 0 [custom_label_0]';
						$fields[ $key ]['fields']['custom_label_1'] = 'Custom label 1 [custom_label_1]';
						$fields[ $key ]['fields']['custom_label_2'] = 'Custom label 2 [custom_label_2]';
						$fields[ $key ]['fields']['custom_label_3'] = 'Custom label 3 [custom_label_3]';
						$fields[ $key ]['fields']['custom_label_4'] = 'Custom label 4 [custom_label_4]';
						break;

					case 'additional_attributes':
						$fields[ $key ]['fields']['merchant_brand'] = 'Merchant brand[merchant_brand]';
						$fields[ $key ]['fields']['productHisEval'] = 'Purchas&Feedback count [productHisEval]';
						$fields[ $key ]['fields']['android_url'] = 'Android url [android_url]';
						$fields[ $key ]['fields']['ios_url'] = 'iOS url [android_url]';
						$fields[ $key ]['fields']['video_link'] = 'Video link [video_link]';
						break;

					default:
						break;
				}
			}

			return $fields;
		}
		/**
		 * Selected columns
		 *
		 * @param array $full_form_data Form data.
		 * @return array
		 */
		public function set_selected_column_names( $full_form_data ) {

			if ( is_null( $this->selected_column_names ) ) {
				$this->selected_column_names = array();
				if ( isset( $full_form_data['mapping_form_data']['mapping_selected_fields'] ) && ! empty( $full_form_data['mapping_form_data']['mapping_selected_fields'] ) ) {
					$selected_mapped_fields = array();
					foreach ( $full_form_data['mapping_form_data']['mapping_selected_fields'] as $key => $value ) {
						if ( '' != $value ) {
							$this->selected_column_names[ $key ] = $value;
						}
					}
				}
				if ( isset( $full_form_data['meta_step_form_data']['mapping_selected_fields'] ) && ! empty( $full_form_data['meta_step_form_data']['mapping_selected_fields'] ) ) {
					$export_additional_columns = $full_form_data['meta_step_form_data']['mapping_selected_fields'];

					foreach ( $export_additional_columns as $value ) {
						foreach ( $value as $key => $vl ) {
							if ( '' != $vl ) {
								$this->selected_column_names[ $key ] = $vl;
							}
						}
					}
				}
				$this->selected_column_names = ( $this->selected_column_names );
			}

			return $full_form_data;
		}
		/**
		 * Get selected columns
		 *
		 * @return array
		 */
		public function get_selected_column_names() {

			return $this->selected_column_names;
		}
		/**
		 * Alter mappings
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $mapping_form_data Mappings.
		 * @return array
		 */
		public function exporter_alter_mapping_fields( $fields, $base, $mapping_form_data ) {
			if ( $base == $this->module_base ) {
				$fields = self::get_product_post_columns();
			}
			return $fields;
		}
		/**
		 * Alter mappings
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $advanced_form_data Mappings.
		 * @return array
		 */
		public function exporter_alter_advanced_fields( $fields, $base, $advanced_form_data ) {
			if ( $this->module_base != $base ) {
				return $fields;
			}
			$out = array();
			$out['header_empty_row'] = array(
				'tr_html' => '<tr id="header_empty_row"><th></th><td></td></tr>',
			);
			foreach ( $fields as $fieldk => $fieldv ) {
				$out[ $fieldk ] = $fieldv;
			}

			return $out;
		}

		/**
		 * Aletr mappings
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $filter_form_data Mappings.
		 * @return array
		 */
		public function exporter_alter_filter_fields( $fields, $base, $filter_form_data ) {
			if ( $this->module_base != $base ) {
				return $fields;
			}

			/* altering help text of default fields */
			$fields['limit']['label'] = __( 'Total number of products to export', 'product-feed-woocommerce' );
			$fields['limit']['help_text'] = __( 'Exports specified number of products. e.g. Entering 500 with a skip count of 10 will export products from 11th to 510th position.', 'product-feed-woocommerce' );
			$fields['offset']['label'] = __( 'Skip first <i>n</i> products', 'product-feed-woocommerce' );
			$fields['offset']['help_text'] = __( 'Skips specified number of products from the beginning of the database. e.g. Enter 10 to skip first 10 products from export.', 'product-feed-woocommerce' );

			$fields['product'] = array(
				'label' => __( 'Products', 'product-feed-woocommerce' ),
				'placeholder' => __( 'All products', 'product-feed-woocommerce' ),
				'attr' => array( 'data-exclude_type' => 'variable,variation' ),
				'field_name' => 'product',
				'sele_vals' => array(),
				'help_text' => __( 'Export specific products. Keyin the product names to export multiple products.', 'product-feed-woocommerce' ),
				'type' => 'multi_select',
				'css_class' => 'wc-product-search',
				'validation_rule' => array( 'type' => 'text_arr' ),
			);
			$fields['stock_status'] = array(
				'label' => __( 'Stock status', 'product-feed-woocommerce' ),
				'placeholder' => __( 'All status', 'product-feed-woocommerce' ),
				'field_name' => 'stock_status',
				'sele_vals' => array(
					'' => __( 'All status', 'product-feed-woocommerce' ),
					'instock' => __( 'In Stock', 'product-feed-woocommerce' ),
					'outofstock' => __( 'Out of Stock', 'product-feed-woocommerce' ),
					'onbackorder' => __( 'On backorder', 'product-feed-woocommerce' ),
				),
				'help_text' => __( 'Export products based on stock status.', 'product-feed-woocommerce' ),
				'type' => 'select',
				'validation_rule' => array( 'type' => 'text_arr' ),
			);
			$fields['exclude_product'] = array(
				'label' => __( 'Exclude products', 'product-feed-woocommerce' ),
				'placeholder' => __( 'Exclude products', 'product-feed-woocommerce' ),
				'attr' => array( 'data-exclude_type' => 'variable,variation' ),
				'field_name' => 'exclude_product',
				'sele_vals' => array(),
				'help_text' => __( 'Use this if you need to exclude a specific or multiple products from your export list.', 'product-feed-woocommerce' ),
				'type' => 'multi_select',
				'css_class' => 'wc-product-search',
				'validation_rule' => array( 'type' => 'text_arr' ),
			);

			$fields['product_categories'] = array(
				'label' => __( 'Product categories', 'product-feed-woocommerce' ),
				'placeholder' => __( 'Any category', 'product-feed-woocommerce' ),
				'field_name' => 'product_categories',
				'sele_vals' => $this->get_product_categories(),
				'help_text' => __( 'Export products belonging to a particular or from multiple categories. Just select the respective categories.', 'product-feed-woocommerce' ),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array( 'type' => 'sanitize_title_with_dashes_arr' ),
			);

			$fields['product_tags'] = array(
				'label' => __( 'Product tags', 'product-feed-woocommerce' ),
				'placeholder' => __( 'Any tag', 'product-feed-woocommerce' ),
				'field_name' => 'product_tags',
				'sele_vals' => $this->get_product_tags(),
				'help_text' => __( 'Enter the product tags to export only the respective products that have been tagged accordingly.', 'product-feed-woocommerce' ),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array( 'type' => 'sanitize_title_with_dashes_arr' ),
			);

			$fields['product_status'] = array(
				'label' => __( 'Product status', 'product-feed-woocommerce' ),
				'placeholder' => __( 'Any status', 'product-feed-woocommerce' ),
				'field_name' => 'product_status',
				'sele_vals' => self::get_product_statuses(),
				'help_text' => __( 'Filter products by their status.', 'product-feed-woocommerce' ),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array( 'type' => 'text_arr' ),
			);

			return $fields;
		}
		/**
		 * Product conditions.
		 *
		 * @return array
		 */
		public static function wt_feed_get_product_conditions() {
			$conditions = array(
				'new' => _x(
					'New',
					'product condition',
					'product-feed-woocommerce'
				),
				'refurbished' => _x(
					'Refurbished',
					'product condition',
					'product-feed-woocommerce'
				),
				'used' => _x(
					'Used',
					'product condition',
					'product-feed-woocommerce'
				),
			);
			/**
			 * Filter the product conditions.
			 *
			 * @since 1.0.0
			 *
			 * @param array $conditions Product conditions.
			 * @return array
			 */
			return apply_filters( 'wt_feed_tiktok_product_conditions', $conditions );
		}
		/**
		 * Get age group.
		 *
		 * @return array
		 */
		public static function get_age_group() {
			$tiktok_age_group = array(
				'adult' => __(
					'Adult',
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
			 * Filter the product age group.
			 *
			 * @since 1.0.0
			 *
			 * @param array $tiktok_age_group Product age group.
			 * @return array
			 */
			return apply_filters( 'wt_feed_tiktok_product_agegroup', $tiktok_age_group );
		}
	}

}

new Webtoffee_Product_Feed_Sync_Pro_Tiktok();
