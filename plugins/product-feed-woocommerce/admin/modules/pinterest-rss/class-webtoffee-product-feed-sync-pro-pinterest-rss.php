<?php
/**
 * Pinterest RSS product feed module
 *
 * @link          
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Pinterest 
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Webtoffee_Product_Feed_Sync_Pro_Pinterest_Rss')) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Pinterest_Rss Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Pinterest_Rss {

		/**
		 * Module id
		 *
		 * @var string
		 */
		public $module_id = '';
		/**
		 * Module id static
		 *
		 * @var string
		 */
		public static $module_id_static = '';
		/**
		 * Module base
		 *
		 * @var string
		 */
		public $module_base = 'pinterest_rss';
		/**
		 * Module name
		 *
		 * @var string
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Pinterest RSS';
		/**
		 * Importer
		 *
		 * @var object
		 */
		private $importer = null;
		/**
		 * Exporter
		 *
		 * @var object
		 */
		private $exporter = null;
		/**
		 * Product categories
		 *
		 * @var array
		 */
		private $product_categories = null;
		/**
		 * Product tags
		 *
		 * @var array
		 */
		private $product_tags = null;
		/**
		 * Product taxonomies
		 *
		 * @var array
		 */
		private $product_taxonomies = array();
		/**
		 * All meta keys
		 *
		 * @var array
		 */
		private $all_meta_keys = array();
		/**
		 * Product attributes
		 *
		 * @var array
		 */
		private $product_attributes = array();
		/**
		 * Exclude hidden meta columns
		 *
		 * @var array
		 */
		private $exclude_hidden_meta_columns = array();
		/**
		 * Found product meta
		 *
		 * @var array
		 */
		private $found_product_meta = array();
		/**
		 * Found product hidden meta
		 *
		 * @var array
		 */
		private $found_product_hidden_meta = array();
		/**
		 * Selected column names
		 *
		 * @var array
		 */
		private $selected_column_names = null;
		/**
		 * Constructor
		 *
		 */
		public function __construct() {
			/**
			 * Check if the function is_plugin_active exists
			 */
			if (!function_exists('is_plugin_active')) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			/**
			 * Check if the WooCommerce plugin is active
			 */
			if (!is_plugin_active('woocommerce/woocommerce.php')) {
				return;
			}

			$this->module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id($this->module_base);
			self::$module_id_static = $this->module_id;

			add_filter('wt_pf_exporter_post_types_basic', array($this, 'wt_pf_exporter_post_types_basic'), 10, 1);

			add_filter('wt_pf_exporter_alter_filter_fields_basic', array($this, 'exporter_alter_filter_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_mapping_fields_basic', array($this, 'exporter_alter_mapping_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_advanced_fields_basic', array($this, 'exporter_alter_advanced_fields'), 10, 3);

			//add_filter('wt_pf_exporter_alter_meta_mapping_fields_basic', array($this, 'exporter_alter_meta_mapping_fields'), 10, 3);

			//add_filter('wt_pf_exporter_alter_mapping_enabled_fields_basic', array($this, 'exporter_alter_mapping_enabled_fields'), 10, 3);			

			add_filter('wt_pf_exporter_do_export_basic', array($this, 'exporter_do_export'), 10, 7);			
			
			add_filter('wt_feed_product_attributes_dropdown', array($this, 'product_attributes_dropdown'), 10, 3);
                        
            add_filter('wt_pf_exporter_steps_basic', array($this, 'wt_pf_exporter_steps_basic'), 10, 2);
			
		}

		/**
		 * Exporter do export
		 *
		 * @param array $export_data Export data.
		 * @param string $base Base.
		 * @param string $step Step.
		 * @param array $form_data Form data.
		 * @param array $selected_template_data Selected template data.
		 * @param string $method_export Method export.
		 * @param int $batch_offset Batch offset.
		 * @return array $export_data
		 */
		public function exporter_do_export($export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset) {
			if ($this->module_base != $base) {
				return $export_data;
			}

			$this->set_selected_column_names($form_data);

			/**
			 * Include the export class
			 */
            include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path(__FILE__) . 'export/class-webtoffee-product-feed-sync-pro-pinterest-rss-export.php';
			$export = new Webtoffee_Product_Feed_Sync_Pro_Pinterest_Rss_Export($this);

			/**
			 * Prepare the header row
			 */
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

			$arr['pinterest_rss'] = __('Pinterest RSS', 'product-feed-woocommerce');
			return $arr;
		}

                
		/**
		 * Add/Remove steps in export section.
		 * @param array $steps array of built in steps
		 * @param string $to_export or aka $base product, order etc
		 * @return array $steps 
		 */
		public function wt_pf_exporter_steps_basic($steps, $to_export) {

			if ('pinterest_rss' === $to_export) {
				if (isset($steps['category_mapping'])) {
					unset($steps['category_mapping']);
				}
			}
			return $steps;
		}                
                
		/**
		 * Read txt file which contains facebook taxonomy list
		 *
		 * @return array Array of categories.
		 */
		public static function get_category_array() {
			// Get All Pinterest Taxonomies
					
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
					list( $catId, $cat ) = explode('-', $line);
					$cat_key = absint(trim($catId));
					$cat_val = trim($cat);
					$taxonomy[$cat_key] = $cat_val;
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
		 * Get product tags
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
		 * Get product statuses
		 * @return array Array of product statuses.
		 */
		public static function get_product_statuses() {
			$product_statuses = array('publish', 'private', 'draft', 'pending', 'future');
			/**
			 * Filter the product statuses.
			 *
			 * @since 1.0.0
			 *
			 * @param array $product_statuses Product statuses.
			 * @return array
			 */
			return apply_filters('wt_pf_allowed_product_statuses', array_combine($product_statuses, $product_statuses));
		}

		/**
		 * Get product post columns
		 * @return array Array of product post columns.
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path(__FILE__) . 'data/data-product-post-columns.php';
		}

		/**
		 * Set selected column names
		 * @param array $full_form_data Full form data.
		 * @return array Modified form data.
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
		 * Get selected column names
		 * @return array|null Selected column names.
		 */
		public function get_selected_column_names() {

			return $this->selected_column_names;
		}

		/**
		 * Alter mapping fields
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $mapping_form_data Mapping form data.
		 * @return array Modified fields.
		 */
		public function exporter_alter_mapping_fields($fields, $base, $mapping_form_data) {
			if ($base == $this->module_base) {
				$fields = self::get_product_post_columns();
			}
			return $fields;
		}

		/**
		 * Alter advanced fields
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $advanced_form_data Advanced form data.
		 * @return array Modified fields.
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

			$out['file_as']['sele_vals'] = array(
				'xml'=>__('XML', 'product-feed-woocommerce'),
				'tsv'=>__('TSV', 'product-feed-woocommerce'),
				'csv'=>__('CSV', 'product-feed-woocommerce')
			);
			$out['delimiter']['sele_vals'] = array(
				'tab' => array('value' => __('Tab', 'product-feed-woocommerce'), 'val' => "\t"),
				'comma' => array('value' => __('Comma', 'product-feed-woocommerce'), 'val' => ","),
				'semicolon' => array('value' => __('Semicolon', 'product-feed-woocommerce'), 'val' => ";")
			);

			return $out;
		}

		
		/**
		 *  Customize the items in filter export page
		 * @param array $fields Fields.
		 * @param string $base Base.
		 * @param array $filter_form_data Filter form data.
		 * @return array Modified fields.
		 */
		public function exporter_alter_filter_fields($fields, $base, $filter_form_data) {
			if ($this->module_base != $base) {
				return $fields;
			}

			/* altering help text of default fields */
			$fields['limit']['label'] = __('Total number of products to export', 'product-feed-woocommerce');
			$fields['limit']['help_text'] = __('Exports specified number of products. e.g. Entering 500 with a skip count of 10 will export products from 11th to 510th position.', 'product-feed-woocommerce');
			$fields['offset']['label'] = wp_kses(__('Skip first <i>n</i> products', 'product-feed-woocommerce'), array('i' => array()));
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
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array('type' => 'sanitize_title_with_dashes_arr')
			);

			$fields['product_tags'] = array(
				'label' => __('Product tags', 'product-feed-woocommerce'),
				'placeholder' => __('Any tag', 'product-feed-woocommerce'),
				'field_name' => 'product_tags',
				'sele_vals' => $this->get_product_tags(),
				'help_text' => __('Enter the product tags to export only the respective products that have been tagged accordingly.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array('type' => 'sanitize_title_with_dashes_arr')
			);

			$fields['product_status'] = array(
				'label' => __('Product status', 'product-feed-woocommerce'),
				'placeholder' => __('Any status', 'product-feed-woocommerce'),
				'field_name' => 'product_status',
				'sele_vals' => self::get_product_statuses(),
				'help_text' => __('Filter products by their status.', 'product-feed-woocommerce'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array('type' => 'text_arr')
			);

			return $fields;
		}		
		
		/**
		 * Product attributes dropdown
		 * @param string $attribute_dropdown Attribute dropdown HTML.
		 * @param string $export_channel Export channel.
		 * @param string $selected Selected value.
		 * @return string Modified attribute dropdown HTML.
		 */
		public function product_attributes_dropdown($attribute_dropdown, $export_channel, $selected=''){
			
			if( 'pinterest_rss' === $export_channel ){
				
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'guid', 'guid' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'pubDate', 'pubDate' );       
				
				if( $selected && strpos($selected, 'wt_static_map_vl:') !== false ){
					$selected = 'wt-static-map-vl';
				}
				if ( $selected && strpos( $attribute_dropdown, 'value="' . $selected . '"' ) !== false ) {
						$attribute_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"' . ' selected', $attribute_dropdown );
				}
			}
	
			return $attribute_dropdown;
		}                 

		/**
		 * Get product conditions
		 * @return array Array of product conditions.
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
				 * @since 1.0.0
				 *
				 * @param array   $conditions    Conditions.
				 */
				return apply_filters( 'wt_feed_pinterest_product_conditions', $conditions );
		}	

		/**
		 * Get age group
		 * @return array Array of age groups.
		 */	
        public static function get_age_group() {
			$pinterest_age_group = array(
				'adult' => __('Adult', 'product-feed-woocommerce'),
				'kids' => __('Kids', 'product-feed-woocommerce'),
				'toddler' => __('Toddler', 'product-feed-woocommerce'),
				'infant' => __('Infant', 'product-feed-woocommerce'),
				'newborn' => __('Newborn', 'product-feed-woocommerce')
			);
			/**
			 * Filter the product age group.
			 *
			 * @since 1.0.0
			 *
			 * @param array $pinterest_age_group Product age group.
			 * @return array
			 */
			return apply_filters( 'wt_feed_pinterest_product_agegroup', $pinterest_age_group );

        }

	}

}

new Webtoffee_Product_Feed_Sync_Pro_Pinterest_Rss();
