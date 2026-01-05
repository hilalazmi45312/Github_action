<?php
/**
 * Idealo product feed module
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Idealo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Idealo' ) ) {

	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Idealo Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Idealo {

		/**
		 *  Module id
		 *
		 * @var string
		 */
		public $module_id = '';

		/**
		 *  Module base
		 *
		 * @var string
		 */
		public $module_base = 'idealo';

		/**
		 *  Module name
		 *
		 * @var string
		 */
		public $module_name = 'Webtoffee Product Feed Catlaog for Idealo';

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
		 *  Selected column names
		 *
		 * @var string
		 */
		private $selected_column_names = null;

		/**
		 *  Module id static
		 *
		 * @var string
		 */
		public static $module_id_static = '';

		/**
		 *  Constructor
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

			add_filter( 'wt_pf_feed_category_mapping', array( $this, 'map_google_category' ), 10, 1 );

			add_filter( 'wt_pf_exporter_steps_basic', array( $this, 'wt_pf_exporter_steps_basic' ), 10, 2 );

			add_filter( 'wt_feed_product_attributes_dropdown', array( $this, 'product_attributes_dropdown' ), 10, 3 );
		}

		/**
		 * Idealo category form fields.
		 *
		 * @param object $category Category.
		 * @return void
		 * @since 1.0.0
		 */
		public function wt_fbfeed_category_form_fields_pro( $category ) {

			$fb_category_id = '';
			if ( current_filter() == 'product_cat_edit_form_fields' ) {
				$fb_category_id = get_term_meta( $category->term_id, 'wt_google_category', true );
			}
			?>

		<tr class="form-field">
			<th scope="row" valign="top"><label for="wt_google_category">Google Category</label></th>
			<td>
				<select name="wt_google_category" class="wc-enhanced-select">
					<?php
						$allowed_tags = array(
							'select' => array(
								'id' => array(),
								'class' => array(),
								'name' => array(),
							),
							'option' => array(
								'value' => array(),
								'selected' => array(),
							),
						);
						echo wp_kses( $this->wt_google_category_dropdown( $fb_category_id ), $allowed_tags );
					?>
				</select>

				<p class="description"><?php esc_html_e( 'The Google Category corresponding to this category in the website.', 'product-feed-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<input type="hidden" name="wt_category_edit_nonce" value="<?php echo wp_kses_post( wp_create_nonce( 'wt_category_edit_nonce' ) ); ?>" />

			<?php
		}

		/**
		 * Save the Idealo category.
		 *
		 * @param int $term_id Term ID.
		 * @return bool
		 * @since 1.0.0
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
		 * Map the Idealo category.
		 *
		 * @param array $form_data Form data.
		 * @return array
		 * @since 1.0.0
		 */
		public function map_google_category( $form_data ) {

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
		 * Export the Idealo feed.
		 *
		 * @param array  $export_data Export data.
		 * @param string $base Base.
		 * @param string $step Step.
		 * @param array  $form_data Form data.
		 * @param array  $selected_template_data Selected template data.
		 * @param string $method_export Method export.
		 * @param int    $batch_offset Batch offset.
		 * @return array
		 * @since 1.0.0
		 */
		public function exporter_do_export( $export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset ) {
			if ( $this->module_base != $base ) {
				return $export_data;
			}

			$this->set_selected_column_names( $form_data );

						include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/export/class-product-feed-for-woocommerce-product.php';
			include plugin_dir_path( __FILE__ ) . 'export/class-webtoffee-product-feed-sync-pro-idealo-export.php';
			$export = new Webtoffee_Product_Feed_Sync_Pro_Idealo_Export( $this );

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
		 * @since 1.0.0
		 */
		public function wt_pf_exporter_post_types_basic( $arr ) {

			$arr['idealo'] = __( 'Idealo', 'product-feed-woocommerce' );
			return $arr;
		}



		/**
		 * Add/Remove steps in export section.
		 *
		 * @param array  $steps array of built in steps.
		 * @param string $to_export To export.
		 * @return array $steps
		 * @since 1.0.0
		 */
		public function wt_pf_exporter_steps_basic( $steps, $to_export ) {

			if ( 'idealo' === $to_export ) {
				if ( isset( $steps['category_mapping'] ) ) {
					unset( $steps['category_mapping'] );
				}
			}
			return $steps;
		}

		/**
		 * Read txt file which contains facebook taxonomy list
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public static function get_category_array() {
			// Get All Google Taxonomies.

			$taxonomy = wp_cache_get( 'wt_pf_feed_google_categories' );

			if ( false === $taxonomy ) {

				$file_name = WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/google/data/google_taxonomy.txt';
				$custom_taxonomy_file = fopen( $file_name, 'r' );  // phpcs:ignore
				$taxonomy = array();
				$taxonomy[''] = 'Do not map';
				if ( $custom_taxonomy_file ) {
					// First line contains metadata, ignore it.
					fgets( $custom_taxonomy_file );  // phpcs:ignore
					while ( $line = fgets( $custom_taxonomy_file ) ) {  // phpcs:ignore
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
		 * Product attributes dropdown.
		 *
		 * @param array  $attribute_dropdown Attribute dropdown.
		 * @param string $export_channel Export channel.
		 * @param string $selected Selected.
		 * @return array
		 * @since 1.0.0
		 */
		public function product_attributes_dropdown( $attribute_dropdown, $export_channel, $selected = '' ) {

			if ( 'idealo' === $export_channel ) {

				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'url', 'Product URL[url]' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'categoryPath', 'Product Categories[categoryPath]' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'imageUrls', 'Image URLs[imageUrls]' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'colour', 'Colour' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'hans', 'Hans' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'eans', 'Eans' );
				$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Quantity', 'Quantity' );

				if ( $selected && strpos( $selected, 'wt_static_map_vl:' ) !== false ) {
					$selected = 'wt-static-map-vl';
				}
				if ( $selected && strpos( $attribute_dropdown, 'value="' . $selected . '"' ) !== false ) {
					$attribute_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"  selected', $attribute_dropdown );
				}
			}

			return $attribute_dropdown;
		}

		/**
		 * Get product tags.
		 *
		 * @return array $tags
		 * @since 1.0.0
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
		 * Get product statuses.
		 *
		 * @return array $product_statuses
		 * @since 1.0.0
		 */
		public static function get_product_statuses() {
			$product_statuses = array( 'publish', 'private', 'draft', 'pending', 'future' );
			/**
			 * Filter the product statuses.
			 *
			 * @param array $product_statuses Product statuses.
			 * @return array
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_pf_allowed_product_statuses', array_combine( $product_statuses, $product_statuses ) );
		}

		/**
		 * Get product post columns.
		 *
		 * @return array $product_post_columns
		 * @since 1.0.0
		 */
		public static function get_product_post_columns() {
			return include plugin_dir_path( __FILE__ ) . 'data/data-product-post-columns.php';
		}

		/**
		 * Alter mapping enabled fields.
		 *
		 * @param array  $mapping_enabled_fields Mapping enabled fields.
		 * @param string $base Base.
		 * @param array  $form_data_mapping_enabled_fields Form data mapping enabled fields.
		 * @return array $mapping_enabled_fields
		 * @since 1.0.0
		 */
		public function exporter_alter_mapping_enabled_fields( $mapping_enabled_fields, $base, $form_data_mapping_enabled_fields ) {
			if ( $base == $this->module_base ) {
				$mapping_enabled_fields = array();
				$mapping_enabled_fields['availability_price'] = array( __( 'Availability & Price', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['unique_product_identifiers'] = array( __( 'Unique Product Identifiers', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['detailed_product_attributes'] = array( __( 'Detailed Product Attributes', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['payment_and_delivery'] = array( __( 'Payment and Delivery', 'product-feed-woocommerce' ), 1 );
				$mapping_enabled_fields['energy_labels'] = array( __( 'Energy Labels', 'product-feed-woocommerce' ), 1 );

			}
			return $mapping_enabled_fields;
		}

		/**
		 * Alter meta mapping fields.
		 *
		 * @param array  $fields Fields.
		 * @param string $base Base.
		 * @param array  $step_page_form_data Step page form data.
		 * @return array $fields
		 * @since 1.0.0
		 */
		public function exporter_alter_meta_mapping_fields( $fields, $base, $step_page_form_data ) {
			if ( $base != $this->module_base ) {
				return $fields;
			}
			foreach ( $fields as $key => $value ) {
				switch ( $key ) {
					case 'availability_price':
						$fields[ $key ]['fields']['Quantity'] = 'Quantity[Quantity]';
						$fields[ $key ]['fields']['price'] = 'Price[price]';
						$fields[ $key ]['fields']['FreeReturnDays'] = 'FreeReturnDays[FreeReturnDays]';
						break;

					case 'unique_product_identifiers':
						$fields[ $key ]['fields']['brand'] = 'Manufacturer[brand]';
						$fields[ $key ]['fields']['eans']  = 'EANS[eans]';
						$fields[ $key ]['fields']['hans']  = 'HANS[hans]';
						break;

					case 'detailed_product_attributes':
						$fields[ $key ]['fields']['colour'] = 'Colour[colour]';
						$fields[ $key ]['fields']['gender'] = 'Gender[gender]';
						$fields[ $key ]['fields']['material'] = 'Material[material]';
						$fields[ $key ]['fields']['size'] = 'Size of the item[size]';
						// $fields[$key]['fields']['quantity'] = 'Quantity [quantity]'; // speker specific and wine specific
						$fields[ $key ]['fields']['merchantName'] = 'merchantName[merchantName]';
						$fields[ $key ]['fields']['formerPrice'] = 'formerPrice[formerPrice]';
						$fields[ $key ]['fields']['voucherCode'] = 'voucherCode[voucherCode]';
						$fields[ $key ]['fields']['deliveryComment'] = 'deliveryComment[deliveryComment]';
						$fields[ $key ]['fields']['fulfillmentType'] = 'fulfillmentType[fulfillmentType]';
						$fields[ $key ]['fields']['merchantId'] = 'merchantId[merchantId]';
						$fields[ $key ]['fields']['deposit'] = 'deposit[deposit]';
						$fields[ $key ]['fields']['maxOrderProcessingTime'] = 'maxOrderProcessingTime[maxOrderProcessingTime]';
						$fields[ $key ]['fields']['twoManHandlingFee'] = 'twoManHandlingFee[twoManHandlingFee]';
						$fields[ $key ]['fields']['disposalFee'] = 'disposalFee[disposalFee]';
						$fields[ $key ]['fields']['used'] = 'used[used]';
						$fields[ $key ]['fields']['download'] = 'download[download]';
						$fields[ $key ]['fields']['replica'] = 'replica[replica]';
						break;
					case 'payment_and_delivery':
						$fields[ $key ]['fields']['deliveryTime'] = __( 'deliveryTime', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_credit_card'] = __( 'paymentCosts_credit_card', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_cash_in_advance'] = __( 'paymentCosts_cash_in_advance', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_cash_on_delivery'] = __( 'paymentCosts_cash_on_delivery', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_direct_debit'] = __( 'paymentCosts_direct_debit', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_paypal'] = __( 'paymentCosts_paypal', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_giropay'] = __( 'paymentCosts_giropay', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_google_checkout'] = __( 'paymentCosts_google_checkout', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_invoice'] = __( 'paymentCosts_invoice', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_postal_order'] = __( 'paymentCosts_postal_order', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_paysafecard'] = __( 'paymentCosts_paysafecard', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_sofortueberweisung'] = __( 'paymentCosts_sofortueberweisung', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_amazon_payment'] = __( 'paymentCosts_amazon_payment', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_electronical_payment_standard'] = __( 'paymentCosts_electronical_payment_standard', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['paymentCosts_ecotax'] = __( 'paymentCosts_ecotax', 'product-feed-woocommerce' );

						$fields[ $key ]['fields']['deliveryCost_spedition'] = __( 'deliveryCost_spedition', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_trans_o_flex'] = __( 'deliveryCost_trans_o_flex', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_tnt'] = __( 'deliveryCost_tnt', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_pick_point'] = __( 'deliveryCost_pick_point', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_hermes'] = __( 'deliveryCost_hermes', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_gls_think_green'] = __( 'deliveryCost_gls_think_green', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_gls'] = __( 'deliveryCost_gls', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_german_express_logistics'] = __( 'deliveryCost_german_express_logistics', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_dpd'] = __( 'deliveryCost_dpd', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_download'] = __( 'deliveryCost_download', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_dhl_go_green'] = __( 'deliveryCost_dhl_go_green', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_dhl'] = __( 'deliveryCost_dhl', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_deutsche_post'] = __( 'deliveryCost_deutsche_post', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_fedex'] = __( 'deliveryCost_fedex', 'product-feed-woocommerce' );
						$fields[ $key ]['fields']['deliveryCost_ups'] = __( 'deliveryCost_ups', 'product-feed-woocommerce' );
						break;

					case 'energy_labels':
						$fields[ $key ]['fields']['eec'] = 'Energy Efficiency Class[eec]';
						break;

					default:
						break;
				}
			}

			return $fields;
		}

		/**
		 * Set selected column names.
		 *
		 * @param array $full_form_data Full form data.
		 * @return array $full_form_data
		 * @since 1.0.0
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
		 * Get selected column names.
		 *
		 * @return array $selected_column_names
		 * @since 1.0.0
		 */
		public function get_selected_column_names() {

			return $this->selected_column_names;
		}

		/**
		 * Alter mapping fields.
		 *
		 * @param array  $fields Fields.
		 * @param string $base Base.
		 * @param array  $mapping_form_data Mapping form data.
		 * @return array $fields
		 * @since 1.0.0
		 */
		public function exporter_alter_mapping_fields( $fields, $base, $mapping_form_data ) {
			if ( $base == $this->module_base ) {
				$fields = self::get_product_post_columns();
			}
			return $fields;
		}

		/**
		 * Alter advanced fields.
		 *
		 * @param array  $fields Fields.
		 * @param string $base Base.
		 * @param array  $advanced_form_data Advanced form data.
		 * @return array $fields
		 * @since 1.0.0
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
			if ( 'idealo' === $base ) {

				$out['file_as']['sele_vals'] = array(
					'csv' => __( 'CSV', 'product-feed-woocommerce' ),
					'xml' => __( 'XML', 'product-feed-woocommerce' ),
					'txt' => __( 'TXT', 'product-feed-woocommerce' ),
				);
				$out['delimiter']['sele_vals'] = array(
					'comma' => array(
						'value' => __( 'Comma', 'product-feed-woocommerce' ),
						'val' => ',',
					),
					'tab' => array(
						'value' => __( 'Tab', 'product-feed-woocommerce' ),
						'val' => "\t",
					),
					'semicolon' => array(
						'value' => __( 'Semicolon', 'product-feed-woocommerce' ),
						'val' => ';',
					),
				);
			}
						return $out;
		}


		/**
		 *  Customize the items in filter export page
		 *
		 * @param array  $fields Fields.
		 * @param string $base Base.
		 * @param array  $filter_form_data Filter form data.
		 * @return array $fields
		 * @since 1.0.0
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
		 * Get product conditions.
		 *
		 * @return array $conditions
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
			 * @param array $conditions Product conditions.
			 * @return array $conditions
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_google_product_conditions', $conditions );
		}

		/**
		 * Get age group.
		 *
		 * @return array $age_group
		 * @since 1.0.0
		 */
		public static function get_age_group() {
			$google_age_group = array(
				'adult' => __( 'Adult', 'product-feed-woocommerce' ),
				'kids' => __( 'Kids', 'product-feed-woocommerce' ),
				'toddler' => __( 'Toddler', 'product-feed-woocommerce' ),
				'infant' => __( 'Infant', 'product-feed-woocommerce' ),
				'newborn' => __( 'Newborn', 'product-feed-woocommerce' ),
			);
			/**
			 * Filter the age group.
			 *
			 * @param array $age_group Age group.
			 * @return array $age_group
			 * @since 1.0.0
			 */
			return apply_filters( 'wt_feed_google_product_agegroup', $google_age_group );
		}

		/**
		 * Google category dropdown.
		 *
		 * @param string $selected Selected.
		 * @return string $category_dropdown
		 * @since 1.0.0
		 */
		public function wt_google_category_dropdown( $selected = '' ) {

			$category_dropdown = wp_cache_get( 'wt_googlefeed_dropdown_product_categories' );

			if ( false === $category_dropdown ) {
				$categories = self::get_category_array();

				// Primary Attributes.
				$category_dropdown = '';

				foreach ( $categories as $key => $value ) {
					$category_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
				}

				wp_cache_set( 'wt_googlefeed_dropdown_product_categories', $category_dropdown, '', WEEK_IN_SECONDS );
			}

			if ( $selected && strpos( $category_dropdown, 'value="' . $selected . '"' ) !== false ) {
				$category_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"  selected', $category_dropdown );
			}

			return $category_dropdown;
		}
	}

}

new Webtoffee_Product_Feed_Sync_Pro_Idealo();
