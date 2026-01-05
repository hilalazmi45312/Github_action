<?php
/**
 * Google Product Reviews product feed module
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Google
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Google_Product_Reviews' ) ) {

	/**
	 * Webtoffee Product Feed Google Product Reviews
	 *
	 * @since 1.0.0
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Google_Product_Reviews {

		/**
		 * Module ID
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $module_id = '';

		/**
		 * Module ID static
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public static $module_id_static = '';

		/**
		 * Module base
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $module_base = 'google_product_reviews';

		/**
		 * Module name
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Google Product Reviews';

		/**
		 * Selected column names
		 *
		 * @var string
		 * @since 1.0.0
		 */
		private $selected_column_names = null;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
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
		 * Set delimeter default
		 *
		 * @param array  $advanced_screen_fields Advanced screen fields.
		 * @param string $to_export To export.
		 * @param array  $advanced_form_data Advanced form data.
		 * @return array $advanced_screen_fields
		 */
		public function wt_exporter_set_delimeter_default( $advanced_screen_fields, $to_export, $advanced_form_data ) {

			if ( 'google_product_reviews' === $to_export ) {

				$advanced_screen_fields['file_as']['sele_vals'] = array( 'xml' => __( 'XML', 'product-feed-woocommerce' ) );
			}

			return $advanced_screen_fields;
		}

		/**
		 * Fn wt pf exporter steps basic
		 *
		 * @param array  $steps Steps.
		 * @param string $to_export To export.
		 * @return array $steps
		 */
		public function wt_pf_exporter_steps_basic( $steps, $to_export ) {

			if ( 'google_product_reviews' === $to_export ) {
				if ( isset( $steps['category_mapping'] ) ) {
					unset( $steps['category_mapping'] );
				}
			}	
			return $steps;
		}

		/**
		 * Exporter do export
		 *
		 * @param array  $export_data Export data.
		 * @param string $base Base.
		 * @param string $step Step.
		 * @param array  $form_data Form data.
		 * @param array  $selected_template_data Selected template data.
		 * @param string $method_export Method export.
		 * @param int    $batch_offset Batch offset.
		 * @return array $export_data
		 */
		public function exporter_do_export( $export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset ) {
			if ( $this->module_base != $base ) {
				return $export_data;
			}

			$this->set_selected_column_names( $form_data );

			include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path( __FILE__ ) . 'export/class-webtoffee-product-feed-google-productreviewsexport.php';
			$export = new Webtoffee_Product_Feed_Google_ProductReviewsExport( $this );

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
		 * Wt pf exporter post types basic
		 *
		 * @param array $arr Array.
		 * @return array $arr
		 */
		public function wt_pf_exporter_post_types_basic( $arr ) {

			$arr['google_product_reviews'] = __( 'Google Product Reviews', 'product-feed-woocommerce' );
			return $arr;
		}

		/**
		 * Get product post columns
		 *
		 * @return array
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path( __FILE__ ) . 'data/data-product-post-columns.php';
		}

		/**
		 * Set selected column names
		 *
		 * @param array $full_form_data Full form data.
		 * @return array $full_form_data
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
		 * @return array
		 */
		public function get_selected_column_names() {

			return $this->selected_column_names;
		}

		/**
		 * Exporter alter mapping fields
		 *
		 * @param array  $fields Fields.
		 * @param string $base Base.
		 * @param array  $mapping_form_data Mapping form data.
		 * @return array $fields
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
		 * @param array  $fields Fields.
		 * @param string $base Base.
		 * @param array  $advanced_form_data Advanced form data.
		 * @return array $fields
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
			$out['file_as']['sele_vals'] = array(
				'xml' => __( 'XML', 'product-feed-woocommerce' ),
			);
			$out['delimiter']['sele_vals'] = array(
				'comma' => array(
					'value' => __( 'Comma', 'product-feed-woocommerce' ),
					'val' => ',',
				),
			);
			return $out;
		}
	}

}

new Webtoffee_Product_Feed_Sync_Pro_Google_Product_Reviews();
