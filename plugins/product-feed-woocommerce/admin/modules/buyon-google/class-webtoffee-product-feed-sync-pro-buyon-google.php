<?php
/**
 * Buy on Google product feed module
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Buyon_Google
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Buyon_Google' ) ) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Buyon_Google Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Buyon_Google {
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
		public $module_base = 'buyon_google';
		/**
		 *  Module name
		 *
		 * @var string
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Buyon Google';
		/**
		 *  Module importer
		 *
		 * @var string
		 */
		private $exporter = null;
		/**
		 *  Module exporter
		 *
		 * @var string
		 */
		private $product_categories = null;
		/**
		 *  Product categories
		 *
		 * @var string
		 */
		private $product_tags = null;
		/**
		 *  Product tags
		 *
		 * @var string
		 */
		private $product_taxonomies = array();
		/**
		 *  Product taxonomies
		 *
		 * @var string
		 */
		private $all_meta_keys = array();
		/**
		 *  All meta keys
		 *
		 * @var string
		 */
		private $product_attributes = array();
		/**
		 *  Product attributes
		 *
		 * @var string
		 */
		private $exclude_hidden_meta_columns = array();
		/**
		 *  Exclude hidden meta columns
		 *
		 * @var string
		 */
		private $found_product_meta = array();
		/**
		 *  Found product meta
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

			add_filter( 'wt_pf_exporter_alter_mapping_fields_basic', array( $this, 'exporter_alter_mapping_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_alter_advanced_fields_basic', array( $this, 'exporter_alter_advanced_fields' ), 10, 3 );

			add_filter( 'wt_pf_exporter_do_export_basic', array( $this, 'exporter_do_export' ), 10, 7 );

			add_filter( 'wt_pf_exporter_steps_basic', array( $this, 'wt_pf_exporter_steps_basic' ), 10, 2 );

			add_filter( 'wt_pf_exporter_alter_advanced_fields_basic', array( $this, 'wt_exporter_set_delimeter_default' ), 10, 3 );
		}

		/**
		 * Set delimiter defaults.
		 *
		 * @param array $advanced_screen_fields Advanced screen fields.
		 * @param array $to_export To export.
		 * @param array $advanced_form_data Advanced form data.
		 * @return array
		 */
		public function wt_exporter_set_delimeter_default( $advanced_screen_fields, $to_export, $advanced_form_data ) {

			if ( 'buyon_google' === $to_export ) {

				$advanced_screen_fields['delimiter']['sele_vals'] = array(
					'tab' => array(
						'value' => __(
							'Tab',
							'product-feed-woocommerce'
						),
						'val' => "\t",
					),
				);
				$advanced_screen_fields['delimiter']['value'] = "\t";
				$advanced_screen_fields['delimiter']['attr'] = array(
					'style' => 'width:290px!important;',
				);
				$advanced_screen_fields['delimiter']['after_form_field'] = '';
				$advanced_screen_fields['delimiter']['help_text'] = __(
					'Separator for differentiating the columns in the CSV file. Only Tab is supported for Google Local Product Inventory',
					'product-feed-woocommerce'
				);
			}

			return $advanced_screen_fields;
		}

		/**
		 * Add/Remove steps in export section.
		 *
		 * @param array  $steps array of built in steps.
		 * @param string $to_export or aka $to_export product, order etc.
		 * @return array $steps
		 */
		public function wt_pf_exporter_steps_basic( $steps, $to_export ) {

			if ( 'buyon_google' === $to_export ) {
				if ( isset( $steps['category_mapping'] ) ) {
					unset( $steps['category_mapping'] );
				}
			}
			return $steps;
		}
		/**
		 * Exported do export
		 *
		 * @param array $export_data Export data.
		 * @param string $base To export.
		 * @param string $step Step.
		 * @param array $form_data Form data.
		 * @param array $selected_template_data Selected template.
		 * @param string $method_export Method.
		 * @param int $batch_offset Offset.
		 * @return array
		 */
		public function exporter_do_export( $export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset ) {
			if ( $this->module_base != $base ) {
				return $export_data;
			}

			$this->set_selected_column_names( $form_data );

			include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path( __FILE__ ) . 'export/class-webtoffee-product-feed-sync-pro-bongexport.php';
			$export = new Webtoffee_Product_Feed_Sync_Pro_BONGExport( $this );

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
		 * @param array $arr Post types.
		 * @return array
		 */
		public function wt_pf_exporter_post_types_basic( $arr ) {

			$arr['buyon_google'] = __(
				'Buy on Google',
				'product-feed-woocommerce'
			);
			return $arr;
		}
		/**
		 * Product columns
		 *
		 * @return array
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path( __FILE__ ) . 'data/data-product-post-columns.php';
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
	}

}

new Webtoffee_Product_Feed_Sync_Pro_Buyon_Google();
