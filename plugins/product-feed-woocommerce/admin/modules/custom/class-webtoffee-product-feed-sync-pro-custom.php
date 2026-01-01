<?php
/**
 * Product section of the plugin
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Custom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Custom' ) ) {
	/**
	 * Custom product feed
	 *
	 * @package WooCommerce_Product_Feed_Pro
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Custom {

		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $module_id = '';

		/**
		 * Module ID static
		 *
		 * @var string
		 */
		public static $module_id_static = '';

		/**
		 * Module base
		 *
		 * @var string
		 */
		public $module_base = 'custom';

		/**
		 * Module name
		 *
		 * @var string
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Custom';

		/**
		 * Selected column names
		 *
		 * @var array
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
		}

		/**
		 * Exporter do export
		 *
		 * @param array  $export_data The export data.
		 * @param string $base The base.
		 * @param string $step The step.
		 * @param array  $form_data The form data.
		 * @param array  $selected_template_data The selected template data.
		 * @param string $method_export The method export.
		 * @param int    $batch_offset The batch offset.
		 * @return array The export data
		 */
		public function exporter_do_export( $export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset ) {
			if ( $this->module_base != $base ) {
				return $export_data;
			}

			$this->set_selected_column_names( $form_data );

			include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path( __FILE__ ) . 'export/class-webtoffee-product-feed-custom-export.php';
			$export = new Webtoffee_Product_Feed_Custom_Export( $this );

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
		 * @param array $arr The array of post types.
		 * @return array The array of post types.
		 */
		public function wt_pf_exporter_post_types_basic( $arr ) {

			$arr['custom'] = __( 'Custom Feed', 'product-feed-woocommerce' );
			return $arr;
		}

		/**
		 * Get product post columns
		 *
		 * @return array The product post columns.
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path( __FILE__ ) . 'data/data-product-post-columns.php';
		}

		/**
		 * Set selected column names
		 *
		 * @param array $full_form_data The full form data.
		 * @return array The full form data.
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
		 * Get selected column names
		 *
		 * @return array The selected column names.
		 */
		public function get_selected_column_names() {

			return $this->selected_column_names;
		}

		/**
		 * Exporter alter mapping fields
		 *
		 * @param array  $fields The fields.
		 * @param string $base The base.
		 * @param array  $mapping_form_data The mapping form data.
		 * @return array The fields.
		 */
		public function exporter_alter_mapping_fields( $fields, $base, $mapping_form_data ) {
			if ( $base == $this->module_base ) {
				$fields = self::get_product_post_columns();
			}
			return $fields;
		}

		/**
		 * Exporter alter advanced fields
		 *
		 * @param array  $fields The fields.
		 * @param string $base The base.
		 * @param array  $advanced_form_data The advanced form data.
		 * @return array The fields.
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
			if ( 'custom' === $base ) {

				$out['file_as']['sele_vals'] = array(
					'xml' => __( 'XML', 'product-feed-woocommerce' ),
					'csv' => __( 'CSV', 'product-feed-woocommerce' ),
					'txt' => __( 'TXT', 'product-feed-woocommerce' ),

				);
				$out['delimiter']['sele_vals'] = array(
					'comma' => array(
						'value' => __( 'Comma', 'product-feed-woocommerce' ),
						'val' => ',',
					),
					'pipe' => array(
						'value' => __( 'Pipe', 'product-feed-woocommerce' ),
						'val' => '|',
					),
					'semicolon' => array(
						'value' => __( 'Semicolon', 'product-feed-woocommerce' ),
						'val' => ';',
					),
					'tab' => array(
						'value' => __( 'Tab', 'product-feed-woocommerce' ),
						'val' => "\t",
					),
				);
			}
			return $out;
		}
	}

}

new Webtoffee_Product_Feed_Sync_Pro_Custom();
