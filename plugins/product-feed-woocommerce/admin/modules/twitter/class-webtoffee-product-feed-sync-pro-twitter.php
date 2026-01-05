<?php
/**
 * Twitter/X Shopping Manager product feed module
 *
 * @link          
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Twitter 
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Webtoffee_Product_Feed_Sync_Pro_Twitter')) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Twitter Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Twitter {

		/**
		 *  Module id
		 *
		 * @var string
		 */
		public $module_id = '';
		/**
		 *  Module id static
		 *
		 * @var string
		 */
		public static $module_id_static = '';
		/**
		 *  Module base
		 *
		 * @var string
		 */
		public $module_base = 'twitter';
		/**
		 *  Module name
		 *
		 * @var string
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Twitter';
		/**
		 *  Importer
		 *
		 * @var object
		 */
		private $importer = null;
		/**
		 *  Exporter
		 *
		 * @var object
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
		 * @var array
		 */
		private $product_taxonomies = array();
		/**
		 *  All meta keys
		 *
		 * @var array
		 */
		private $all_meta_keys = array();
		/**
		 *  Product attributes
		 *
		 * @var array
		 */
		private $product_attributes = array();
		/**
		 *  Exclude hidden meta columns
		 *
		 * @var array
		 */
		private $exclude_hidden_meta_columns = array();
		/**
		 *  Found product meta
		 *
		 * @var array
		 */
		private $found_product_meta = array();
		/**
		 *  Found product hidden meta
		 *
		 * @var array
		 */
		private $found_product_hidden_meta = array();
		/**
		 *  Selected column names
		 *
		 * @var array
		 */
		private $selected_column_names = null;

		/**
		 *  Constructor
		 *
		 * @return void
		 */
		public function __construct() {
	
			if (!function_exists('is_plugin_active')) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			if (!is_plugin_active('woocommerce/woocommerce.php')) {
				return;
			}

			$this->module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id($this->module_base);
			self::$module_id_static = $this->module_id;

			add_filter('wt_pf_exporter_post_types_basic', array($this, 'wt_pf_exporter_post_types_basic'), 10, 1);

			add_filter('wt_pf_exporter_alter_filter_fields_basic', array($this, 'exporter_alter_filter_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_mapping_fields_basic', array($this, 'exporter_alter_mapping_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_advanced_fields_basic', array($this, 'exporter_alter_advanced_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_meta_mapping_fields_basic', array($this, 'exporter_alter_meta_mapping_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_mapping_enabled_fields_basic', array($this, 'exporter_alter_mapping_enabled_fields'), 10, 3);			

			add_filter('wt_pf_exporter_do_export_basic', array($this, 'exporter_do_export'), 10, 7);
                        
            add_filter('wt_feed_product_attributes_dropdown', array($this, 'product_attributes_dropdown'), 10, 3);

			add_filter('wt_pf_feed_category_mapping', array($this, 'map_twitter_category'), 10, 1);

			add_action( 'edit_product_cat', array($this, 'wt_fbfeed_category_form_save_pro'), 10, 1 );
			
			add_action( 'create_category', array($this, 'wt_fbfeed_category_form_save_pro'), 10, 1 );
                        
			if ( version_compare( get_bloginfo( 'version' ), '6.1', '>=' ) ) {
				add_action('saved_product_cat', array($this, 'wt_fbfeed_category_form_save_pro_ajx_new'), 10, 4);
			}else{
				add_action('saved_product_cat', array($this, 'wt_fbfeed_category_form_save_pro_ajx'), 10, 3);
			}
				
		}

		/**
		 * Twitter category form fields.
		 *
		 * @param int $term_id Term ID.
		 * @return bool|void
		 * @since 1.0.0
		 */
		public function wt_fbfeed_category_form_save_pro( $term_id ) {

			if ( isset( $_POST[ 'wt_google_category' ] ) ) {
				if ( ! isset( $_POST['wt_category_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wt_category_edit_nonce'] ) ), 'wt_category_edit_nonce' ) ) {
					return false;
				}

				$wt_twitter_category = absint( $_POST[ 'wt_google_category' ] );
				if(0 == $wt_twitter_category){
					delete_term_meta($term_id, 'wt_google_category');
				}else{
					update_term_meta( $term_id, 'wt_google_category', $wt_twitter_category );
				}
			}
		
		}

		/**
		 * Save the Twitter category.
		 *
		 * @param int $term_id Term id.
		 * @param int $tt_id Term id.
		 * @param bool $update Update.
		 * @param array $args Args.
		 * @return bool
		 * @since 1.0.0
		 */
		public function wt_fbfeed_category_form_save_pro_ajx_new( $term_id, $tt_id, $update, $args ) {

			if ( isset( $args[ 'wt_google_category' ] ) ) {
				if ( ! isset( $args['wt_category_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $args['wt_category_edit_nonce'] ) ), 'wt_category_edit_nonce' ) ) {
						return false;
				}

				$wt_twitter_category = absint( $args[ 'wt_google_category' ] );
				if(0 == $wt_twitter_category){
						delete_term_meta($term_id, 'wt_google_category');
				}else{
						update_term_meta( $term_id, 'wt_google_category', $wt_twitter_category );
				}
			}

		}

		/**
		 * Save the Twitter category.
		 *
		 * @param int $term_id Term id.
		 * @param int $tt_id Term id.
		 * @param bool $update Update.
		 * @return bool
		 * @since 1.0.0
		 */
		public function wt_fbfeed_category_form_save_pro_ajx( $term_id, $tt_id, $update ) {

			if ( isset( $_POST[ 'wt_google_category' ] ) ) {
				if ( ! isset( $_POST['wt_category_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wt_category_edit_nonce'] ) ), 'wt_category_edit_nonce' ) ) {
						return false;
				}

				$wt_twitter_category = absint( $_POST[ 'wt_google_category' ] );
				if(0 == $wt_twitter_category){
						delete_term_meta($term_id, 'wt_google_category');
				}else{
						update_term_meta( $term_id, 'wt_google_category', $wt_twitter_category );
				}
			}

		}        
		
		/**
		 * Map the Twitter category.
		 *
		 * @param array $form_data Form data.
		 * @return array Modified form data.
		 * @since 1.0.0
		 */
		public function map_twitter_category($form_data) {

			if ( ( isset($form_data['post_type_form_data']['item_type']) &&  $form_data['post_type_form_data']['item_type'] != $this->module_base ) || ( isset($form_data['post_type_form_data']['wt_pf_export_post_type']) &&  $form_data['post_type_form_data']['wt_pf_export_post_type'] != $this->module_base )) {
				return $form_data;
			} else {

				foreach ($form_data['category_mapping_form_data'] as $local_cat => $merchant_cat) {
					if (!empty($merchant_cat)) {
						$term_id = absint(str_replace('cat_mapping_', '', $local_cat));
						$wt_fb_category = absint($merchant_cat);
						update_term_meta($term_id, 'wt_google_category', $wt_fb_category);
					}
				}
				return $form_data;
			}
		}

		/**
		 * Export the Twitter category.
		 *
		 * @param array $export_data Export data.
		 * @param string $base Base.
		 * @param int $step Step.
		 * @param array $form_data Form data.
		 * @param array $selected_template_data Selected template data.
		 * @param string $method_export Method export.
		 * @param int $batch_offset Batch offset.
		 * @return array Modified export data.
		 * @since 1.0.0
		 */
		public function exporter_do_export($export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset) {
			if ($this->module_base != $base) {
				return $export_data;
			}

			$this->set_selected_column_names($form_data);

			include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path(__FILE__) . 'export/class-webtoffee-product-feed-sync-pro-twitter-export.php';
			$export = new Webtoffee_Product_Feed_Sync_Pro_Twitter_Export($this);

			$header_row = $export->prepare_header();

			$data_row = $export->prepare_data_to_export($form_data, $batch_offset, $step);

			$export_data = array(
				'head_data' => $header_row,
				'body_data' => $data_row['data'],
				'total' => $data_row['total'],
			);

			if (isset($data_row['no_post'])) {
				$export_data['no_post'] = $data_row['no_post'];
			}


			return $export_data;
		}

		/**
		 * Adding current post type to export list
		 *
		 * @param array $arr Array of post types.
		 * @return array Modified array of post types.
		 */
		public function wt_pf_exporter_post_types_basic($arr) {

			$arr['twitter'] = __('X Shopping Manager', 'product-feed-woocommerce');
			return $arr;
		}

		/**
		 * Read txt file which contains Google taxonomy list
		 *
		 * @return array Array of categories.
		 */
		public static function get_category_array() {
			// Get All Twitter Taxonomies
					
			$taxonomy = wp_cache_get('wt_pf_feed_google_categories');

			if (false === $taxonomy) {
			
			$fileName = WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/google/data/google_taxonomy.txt';
			$customTaxonomyFile = fopen($fileName, 'r');  // phpcs:ignore
			$taxonomy = array();
			$taxonomy[''] = 'Do not map';
			if ($customTaxonomyFile) {
				// First line contains metadata, ignore it
				fgets($customTaxonomyFile);  // phpcs:ignore
				while ($line = fgets($customTaxonomyFile)) {  // phpcs:ignore
					if (strpos($line, '-') !== false) {
						list($catId, $cat) = explode('-', $line, 2);
						$cat_key = absint(trim($catId));
						$cat_val = trim($cat);
						$taxonomy[$cat_key] = $cat_val;
					}
				}
			}
				wp_cache_set('wt_pf_feed_google_categories', $taxonomy, '', WEEK_IN_SECONDS);
			}

			return $taxonomy;
		}

		/**
		 * Get product categories
		 * @return array Array of product categories.
		 */
		private function get_product_categories() {
			if (!is_null($this->product_categories)) {
				return $this->product_categories;
			}
			$out = array();
			$product_categories = get_terms( 
				array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false,
				)
			);
			if (!is_wp_error($product_categories)) {
				$version = get_bloginfo('version');
				foreach ($product_categories as $category) {
					$out[$category->slug] = (( $version < '4.8') ? $category->name : get_term_parents_list($category->term_id, 'product_cat', array('separator' => ' -> ')));
				}
			}
			$this->product_categories = $out;
			return $out;
		}

		/**
		 * Get product tags.
		 *
		 * @return array Array of product tags.
		 */
		private function get_product_tags() {
			if (!is_null($this->product_tags)) {
				return $this->product_tags;
			}
			$out = array();
			$product_tags = get_terms('product_tag');
			if (!is_wp_error($product_tags)) {
				foreach ($product_tags as $tag) {
					$out[$tag->slug] = $tag->name;
				}
			}
			$this->product_tags = $out;
			return $out;
		}

		/**
		 * Get product statuses.
		 *
		 * @return array Array of product statuses.
		 */
		public static function get_product_statuses() {
			$product_statuses = array('publish', 'private', 'draft', 'pending', 'future');
			return apply_filters('wt_pf_allowed_product_statuses', array_combine($product_statuses, $product_statuses));
		}

		/**
		 * Get product post columns.
		 *
		 * @return array Array of product post columns.
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path(__FILE__) . 'data/data-product-post-columns.php';
		}

		/**
		 * Alter mapping enabled fields.
		 *
		 * @param array $mapping_enabled_fields Mapping enabled fields.
		 * @param string $base Base.
		 * @param array $form_data_mapping_enabled_fields Form data mapping enabled fields.
		 * @return array Modified mapping enabled fields.
		 * @since 1.0.0
		 */
		public function exporter_alter_mapping_enabled_fields($mapping_enabled_fields, $base, $form_data_mapping_enabled_fields) {
			if ($base === $this->module_base) {
				$mapping_enabled_fields = array();
				$mapping_enabled_fields['optional_specs'] = array(__('Optional specs', 'product-feed-woocommerce'), 1);							
			}
			return $mapping_enabled_fields;
		}

		/**
		 * Alter meta mapping fields.
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $step_page_form_data Step page form data.
		 * @return array Modified fields.
		 * @since 1.0.0
		 */
		public function exporter_alter_meta_mapping_fields($fields, $base, $step_page_form_data) {
			if ($base != $this->module_base) {
				return $fields;
			}
			foreach ($fields as $key => $value) {
				switch ($key) {
					case 'optional_specs':

						$fields[$key]['fields']['mobile_link'] = 'Product URL[mobile_link]';
						$fields[$key]['fields']['wtimages_1'] = 'Additional Image 1 [additional_image_link]';
						$fields[$key]['fields']['wtimages_2'] = 'Additional Image 2 [additional_image_link]';
						$fields[$key]['fields']['wtimages_3'] = 'Additional Image 3 [additional_image_link]';
						$fields[$key]['fields']['wtimages_4'] = 'Additional Image 4 [additional_image_link]';
						$fields[$key]['fields']['wtimages_5'] = 'Additional Image 5 [additional_image_link]';
						$fields[$key]['fields']['wtimages_6'] = 'Additional Image 6 [additional_image_link]';
						$fields[$key]['fields']['wtimages_7'] = 'Additional Image 7 [additional_image_link]';
						$fields[$key]['fields']['wtimages_8'] = 'Additional Image 8 [additional_image_link]';
						$fields[$key]['fields']['wtimages_9'] = 'Additional Image 9 [additional_image_link]';
						$fields[$key]['fields']['wtimages_10'] = 'Additional Image 10 [additional_image_link]';
						$fields[$key]['fields']['google_product_category']             = 'Google Product Category[google_product_category]'; 
						$fields[$key]['fields']['product_type']                 = 'Product Categories[product_type]';
						$fields[$key]['fields']['inventory'] = 'Quantity[inventory]';
						$fields[$key]['fields']['sale_price'] = 'Sale Price[sale_price]';
						$fields[$key]['fields']['sale_price_effective_date'] = 'Sale Price Effective Date[sale_price_effective_date]';
						$fields[$key]['fields']['item_group_id'] = 'Item Group Id[item_group_id]';
						$fields[$key]['fields']['gender'] = 'Gender[gender]';
						$fields[$key]['fields']['color'] = 'Color[color]';
						$fields[$key]['fields']['size'] = 'Size of the item[size]';
						$fields[$key]['fields']['age_group'] = 'Age Group[age_group]';
						$fields[$key]['fields']['custom_label_0'] = 'Custom label 0 [custom_label_0]';
						$fields[$key]['fields']['custom_label_1'] = 'Custom label 1 [custom_label_1]';
						$fields[$key]['fields']['custom_label_2'] = 'Custom label 2 [custom_label_2]';
						$fields[$key]['fields']['custom_label_3'] = 'Custom label 3 [custom_label_3]';
						$fields[$key]['fields']['custom_label_4'] = 'Custom label 4 [custom_label_4]';
						break;

					default:
						break;
				}
			}

			return $fields;
		}

		/**
		 * Product attributes dropdown.
		 *
		 * @param string $attribute_dropdown Attribute dropdown.
		 * @param string $export_channel Export channel.
		 * @param string $selected Selected.
		 * @return string Modified attribute dropdown.
		 * @since 1.0.0
		 */
			public function product_attributes_dropdown($attribute_dropdown, $export_channel, $selected = '') {


			if ('twitter' === $export_channel) {


				$attribute_dropdown .= sprintf('<option value="%s">%s</option>', 'inventory', 'inventory');                        

				if ($selected && strpos($selected, 'wt_static_map_vl:') !== false) {
					$selected = 'wt-static-map-vl';
				}
				if ($selected && strpos($attribute_dropdown, 'value="' . $selected . '"') !== false) {
					$attribute_dropdown = str_replace('value="' . $selected . '"', 'value="' . $selected . '"' . ' selected', $attribute_dropdown);
				}
			}


			return $attribute_dropdown;
		}                
                
                
		/**
		 * Set selected column names.
		 *
		 * @param array $full_form_data Full form data.
		 * @return array Modified form data.
		 * @since 1.0.0
		 */
		public function set_selected_column_names($full_form_data) {

			if (is_null($this->selected_column_names)) {
				$this->selected_column_names = array();
				if (isset($full_form_data['mapping_form_data']['mapping_selected_fields']) && !empty($full_form_data['mapping_form_data']['mapping_selected_fields'])) {
					$selected_mapped_fields = array();
					foreach ($full_form_data['mapping_form_data']['mapping_selected_fields'] as $key => $value) {
						if ("" != $value) {
							$this->selected_column_names[$key] = $value;
						}
					}
				}
				if (isset($full_form_data['meta_step_form_data']['mapping_selected_fields']) && !empty($full_form_data['meta_step_form_data']['mapping_selected_fields'])) {
					$export_additional_columns = $full_form_data['meta_step_form_data']['mapping_selected_fields'];

					foreach ($export_additional_columns as $value) {
						foreach ($value as $key => $vl) {
							if ("" != $vl) {
								$this->selected_column_names[$key] = $vl;
							}
						}
					}
				}
				$this->selected_column_names = ($this->selected_column_names);
			}


			return $full_form_data;
		}

		/**
		 * Get selected column names.
		 *
		 * @return array|null Selected column names.
		 * @since 1.0.0
		 */
		public function get_selected_column_names() {

			return $this->selected_column_names;
		}

		/**
		 * Alter mapping fields.
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $mapping_form_data Mapping form data.
		 * @return array Modified fields.
		 * @since 1.0.0
		 */
		public function exporter_alter_mapping_fields($fields, $base, $mapping_form_data) {
			if ($base == $this->module_base) {
				$fields = self::get_product_post_columns();
			}
			return $fields;
		}

		/**
		 * Alter advanced fields.
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $advanced_form_data Advanced form data.
		 * @return array Modified fields.
		 * @since 1.0.0
		 */
		public function exporter_alter_advanced_fields($fields, $base, $advanced_form_data) {
			if ($this->module_base != $base) {
				return $fields;
			}
			$out = array();
			$out['header_empty_row'] = array(
				'tr_html' => '<tr id="header_empty_row"><th></th><td></td></tr>'
			);
			foreach ($fields as $fieldk => $fieldv) {
				$out[$fieldk] = $fieldv;
			}
                        if ('twitter' === $base) {

                            $out['file_as']['sele_vals'] = array(
                                'csv' => __('CSV', 'product-feed-woocommerce')                                
                            );
                            $out['delimiter']['sele_vals'] = array(
                                'comma' => array('value' => __('Comma', 'product-feed-woocommerce'), 'val' => ","),
								'tab' => array('value' => __('Tab', 'product-feed-woocommerce'), 'val' => "\t"),
                            );
                        }

			return $out;
		}

		
		/**
		 *  Customize the items in filter export page
		 *
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $filter_form_data Filter form data.
		 * @return array Modified fields.
		 * @since 1.0.0
		 */
		public function exporter_alter_filter_fields($fields, $base, $filter_form_data) {
			if ($this->module_base != $base) {
				return $fields;
			}

			/* altering help text of default fields */
			$fields['limit']['label'] = __('Total number of products to export', 'product-feed-woocommerce');
			$fields['limit']['help_text'] = __('Exports specified number of products. e.g. Entering 500 with a skip count of 10 will export products from 11th to 510th position.', 'product-feed-woocommerce');
			$fields['offset']['label'] = __('Skip first <i>n</i> products', 'product-feed-woocommerce');
			$fields['offset']['help_text'] = __('Skips specified number of products from the beginning of the database. e.g. Enter 10 to skip first 10 products from export.', 'product-feed-woocommerce');

			$fields['product'] = array(
				'label' => __('Products', 'product-feed-woocommerce'),
				'placeholder' => __('All products', 'product-feed-woocommerce'),
				'attr' => array('data-exclude_type' => 'variable,variation'),
				'field_name' => 'product',
				'sele_vals' => array(),
				'help_text' => __('Export specific products. Keyin the product names to export multiple products.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-product-search',
				'validation_rule' => array('type' => 'text_arr')
			);
			$fields['stock_status'] = array(
				'label' => __('Stock status', 'product-feed-woocommerce'),
				'placeholder' => __('All status', 'product-feed-woocommerce'),
				'field_name' => 'stock_status',
				'sele_vals' => array('' => __('All status', 'product-feed-woocommerce'), 'instock' => __('In Stock', 'product-feed-woocommerce'), 'outofstock' => __('Out of Stock', 'product-feed-woocommerce'), 'onbackorder' => __('On backorder', 'product-feed-woocommerce')),
				'help_text' => __('Export products based on stock status.', 'product-feed-woocommerce'),
				'type' => 'select',
				'validation_rule' => array('type' => 'text_arr')
			);
			$fields['exclude_product'] = array(
				'label' => __('Exclude products', 'product-feed-woocommerce'),
				'placeholder' => __('Exclude products', 'product-feed-woocommerce'),
				'attr' => array('data-exclude_type' => 'variable,variation'),
				'field_name' => 'exclude_product',
				'sele_vals' => array(),
				'help_text' => __('Use this if you need to exclude a specific or multiple products from your export list.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-product-search',
				'validation_rule' => array('type' => 'text_arr')
			);

			$fields['product_categories'] = array(
				'label' => __('Product categories', 'product-feed-woocommerce'),
				'placeholder' => __('Any category', 'product-feed-woocommerce'),
				'field_name' => 'product_categories',
				'sele_vals' => $this->get_product_categories(),
				'help_text' => __('Export products belonging to a particular or from multiple categories. Just select the respective categories.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select wt-wc-enhanced-search',
				'validation_rule' => array('type' => 'sanitize_title_with_dashes_arr')
			);

			$fields['product_tags'] = array(
				'label' => __('Product tags', 'product-feed-woocommerce'	),
				'placeholder' => __('Any tag', 'product-feed-woocommerce'),
				'field_name' => 'product_tags',
				'sele_vals' => $this->get_product_tags(),
				'help_text' => __('Enter the product tags to export only the respective products that have been tagged accordingly.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select wt-wc-enhanced-search',
				'validation_rule' => array('type' => 'sanitize_title_with_dashes_arr')
			);

			$fields['product_status'] = array(
				'label' => __('Product status', 'product-feed-woocommerce'),
				'placeholder' => __('Any status', 'product-feed-woocommerce'),
				'field_name' => 'product_status',
				'sele_vals' => self::get_product_statuses(),
				'help_text' => __('Filter products by their status.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select wt-wc-enhanced-search',
				'validation_rule' => array('type' => 'text_arr')
			);

			return $fields;
		}		
		
		/**
		 * Get product conditions.
		 *
		 * @return array Array of product conditions.
		 * @since 1.0.0
		 */
		public static function wt_feed_get_product_conditions() {
			$conditions = array(
				'new'           => _x( 'New', 'product condition', 'product-feed-woocommerce' ),
				'refurbished'   => _x( 'Refurbished', 'product condition', 'product-feed-woocommerce' ),
				'used'          => _x( 'Used', 'product condition', 'product-feed-woocommerce' ),
			);
			/**
			 * Filter the product conditions.
			 *
			 * @param array $conditions Conditions.
			 * @return array
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_twitter_product_conditions', $conditions );
		}	

		/**
		 * Get age group.
		 *
		 * @return array Array of age groups.
		 * @since 1.0.0
		 */
		public static function get_age_group() {
				$twitter_age_group = array(
					'adult' => __('Adult', 'product-feed-woocommerce'),
					'all ages' =>  __('All ages', 'product-feed-woocommerce'),
					'teen' => __('Teen', 'product-feed-woocommerce'),
					'kids' => __('Kids', 'product-feed-woocommerce'),
					'toddler' => __('Toddler', 'product-feed-woocommerce'),
					'infant' => __('Infant', 'product-feed-woocommerce'),
					'newborn' => __('Newborn', 'product-feed-woocommerce')
				);
				/**
				 * Filter the age group.
				 *
				 * @param array $twitter_age_group Age group.
				 * @return array
				 * @since 1.0.0
				 */
				return apply_filters( 'wt_feed_twitter_product_agegroup', $twitter_age_group );
		}

	}

}

new Webtoffee_Product_Feed_Sync_Pro_Twitter();
                        