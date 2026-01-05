<?php
/**
 * Handles the export actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\Export
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Webtoffee_Product_Feed_Sync_Pro_Export Class.
 */
if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Export' ) ) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Export class
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Export {
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $module_id = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $module_id_static = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $module_base = 'export';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $export_dir = WP_CONTENT_DIR . '/uploads/webtoffee_product_feed';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $export_dir_name = '/webtoffee_product_feed';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $steps = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $allowed_export_file_type = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		private $to_export = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		private $to_export_id = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		private $rerun_id = 0;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $export_method = '';
		/**
		 * Validation rule
		 *
		 * @var array
		 */
		public $validation_rule = array();
		/**
		 * Export methods
		 *
		 * @var array
		 */
		public $export_methods = array();
		/**
		 * Steps that need validation filter
		 *
		 * @var string
		 */
		public $step_need_validation_filter = array( 'filter', 'advanced' );
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $selected_template = 0;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $default_batch_count = 0; /* configure this value in `advanced_setting_fields` method */
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $selected_template_data = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $default_export_method = '';  /* configure this value in `advanced_setting_fields` method */
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $use_bom = true;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $form_data = array();
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_action( 'init', array( $this, 'wt_product_feed_pro_load_translations_export' ) );

			$this->validation_rule = array(
				'post_type' => array(), /* no validation rule. So default sanitization text */
				'method_export' => array(
					'mapping_enabled_fields' => array( 'type' => 'text_arr' ), // in case of quick export.
				),
			);

			$this->step_need_validation_filter = array( 'filter', 'advanced' );

			/* advanced plugin settings */
			add_filter( 'wt_pf_advanced_setting_fields_pro', array( $this, 'advanced_setting_fields' ), 11 );

			/* setting default values, this method must be below of advanced setting filter */
			// $this->get_defaults();
			add_action( 'init', array( $this, 'get_defaults' ) );

			/* main ajax hook. The callback function will decide which is to execute. */

			add_action( 'wp_ajax_pf_export_ajax_pro', array( $this, 'ajax_main' ), 11 );

			/* Admin menu for export */
			add_filter( 'wt_pf_admin_menu_pro', array( $this, 'add_admin_pages' ), 10, 1 );

			/* Download export file via nonce URL */
			add_action( 'admin_init', array( $this, 'download_file' ), 11 );

			add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'product_data_panels' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ), 15 );
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'wt_feed_variable_custom_meta_fields' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'wt_feed_save_variable_metas' ), 10, 1 );
			// Style the tab icons.
			add_action( 'admin_enqueue_scripts', array( $this, 'wt_feed_tab_styles' ), 11 );
		}

		/**
		 * Load translations.
		 */
		public function wt_product_feed_pro_load_translations_export() {

			/* allowed file types */
			$this->allowed_export_file_type = array(
				'xml' => __( 'XML', 'product-feed-woocommerce' ),
				'csv' => __( 'CSV', 'product-feed-woocommerce' ),
				'tsv' => __( 'TSV', 'product-feed-woocommerce' ),
				'txt' => __( 'TXT', 'product-feed-woocommerce' ),
			);

			/* default step list */
			$this->steps = array(
				'post_type' => array(
					'title' => __( 'Create new feed', 'product-feed-woocommerce' ),
					'description' => __( 'Fill the basic feed settings to proceed.', 'product-feed-woocommerce' ),
				),
				'mapping' => array(
					'title' => __( 'Attribute mapping', 'product-feed-woocommerce' ),
					'description' => __( 'Map the attributes of catalog feed corresponding to woocommerce.', 'product-feed-woocommerce' ),
				),
				'category_mapping' => array(
					'title' => __( 'Category mapping', 'product-feed-woocommerce' ),
					'description' => __( 'Map the categories of catalog feed corresponding to merchant.', 'product-feed-woocommerce' ),
				),
				'advanced' => array(
					'title' => __( 'Generate feed', 'product-feed-woocommerce' ),
					'description' => __( 'Generate merchant catalog.', 'product-feed-woocommerce' ),
				),
			);

			$this->export_methods = array(
				'quick' => array(
					'title' => __( 'Quick export', 'product-feed-woocommerce' ),
					'description' => __( 'Exports all the basic fields.', 'product-feed-woocommerce' ),
				),
				'template' => array(
					'title' => __( 'Pre-saved template', 'product-feed-woocommerce' ),
					'description' => __( 'Exports data as per the specifications(filters,selective column,mapping etc) from the previously saved file.', 'product-feed-woocommerce' ),
				),
				'new' => array(
					'title' => __( 'Advanced export', 'product-feed-woocommerce' ),
					'description' => __( 'Exports data after a detailed process of filtration, column selection and advanced options. The configured settings can be saved as a template for future exports.', 'product-feed-woocommerce' ),
				),
			);
		}

		/**
		 * Get default setting options.
		 *
		 * @since 1.0.0
		 */
		public function get_defaults() {
			$this->default_export_method = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'default_export_method' );
			$this->default_batch_count = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'default_export_batch' );
			$this->use_bom = (bool) Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'include_bom' );
		}



		/**
		 *   Fields for advanced settings.
		 *
		 * @since 1.0.0
		 * @param array $fields Settings fields.
		 * @return array
		 */
		public function advanced_setting_fields( $fields ) {
			$export_methods = array_map(
				function ( $vl ) {
					return $vl['title'];
				},
				$this->export_methods
			);

			$fields['default_export_batch'] = array(
				'label' => __( 'Default feed batch count', 'product-feed-woocommerce' ),
				'type' => 'number',
				'value' => 10,
				'field_name' => 'default_export_batch',
				'help_text' => __( 'Provide the default count for the records to be generated in a batch.', 'product-feed-woocommerce' ),
				'validation_rule' => array( 'type' => 'absint' ),
				'attr' => array(
					'min' => 1,
					'max' => 200,
				),
			);
			$fields['glpi_store_code'] = array(
				'label' => __( 'Google local product inventory store code', 'product-feed-woocommerce' ),
				'type' => 'text',
				'value' => '',
				'placeholder' => 'eg:- Store123',
				'field_name' => 'glpi_store_code',
				'help_text' => __( 'The store code [store_code] attribute is case-sensitive and must match the store codes submitted in your Business Profiles.', 'product-feed-woocommerce' ),
				'validation_rule' => array( 'type' => 'text' ),
			);
			$fields['all_shipping_zone'] = array(
				'label' => __( 'Add shipping costs of all countries to the feed', 'product-feed-woocommerce' ),
				'type' => 'checkbox',
				'checkbox_fields' => array( 1 => '' ),
				'value' => 0,
				'field_name' => 'all_shipping_zone',
				'css_class' => 'wt_pf_checkbox_toggler wt_pf_toggler_blue',
				'help_text' => __( 'Add shipping costs of all countries to the feed', 'product-feed-woocommerce' ),
				'validation_rule' => array( 'type' => 'absint' ),
			);

			return $fields;
		}

		/**
		 * Adding admin menus.
		 *
		 * @since 1.0.0
		 * @param array $menus Admin menus.
		 * @return array
		 */
		public function add_admin_pages( $menus ) {
			$menu_temp = array(
				$this->module_base => array(
					'menu',
					__( 'Create new feed', 'product-feed-woocommerce' ),
					__( 'WebToffee Product Feed', 'product-feed-woocommerce' ),
					/**
					 * Capability for menus.
					 *
					 * @since 1.0.0
					 *
					 * @param string $capability Capability.
					 */
					apply_filters( 'wt_import_export_allowed_capability', 'export' ),
					$this->module_id,
					array( $this, 'admin_settings_page' ),
					'dashicons-cart',
					56,
				),
				$this->module_base . '-sub' => array(
					'submenu',
					$this->module_id,
					__( 'Create new feed', 'product-feed-woocommerce' ),
					__( 'Create new feed', 'product-feed-woocommerce' ),
					/**
					 * Capability for menus.
					 *
					 * @since 1.0.0
					 * @param string $capability Capability.
					 */
					apply_filters( 'wt_import_export_allowed_capability', 'export' ),
					$this->module_id,
					array( $this, 'admin_settings_page' ),
				),
			);
			unset( $menus['general-settings'] );
			$menus = array_merge( $menu_temp, $menus );
			return $menus;
		}


		/**
		 *   Export page.
		 *
		 * @since 1.0.0
		 */
		public function admin_settings_page() {

			/**
			*   Check it is a rerun call
			*/
			$requested_rerun_id = ( isset( $_GET['wt_pf_rerun'] ) ? absint( $_GET['wt_pf_rerun'] ) : 0 );// phpcs:ignore WordPress.Security.NonceVerification
			$this->rerun_id = $requested_rerun_id;
			$this->_process_rerun( $requested_rerun_id );

			$this->enqueue_assets();
				$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : null;// phpcs:ignore WordPress.Security.NonceVerification

			switch ( $tab ) :
				case 'export':
					include plugin_dir_path( __FILE__ ) . 'views/main.php';// Put your HTML here.
					break;

				case 'history':
					$import = new Webtoffee_Product_Feed_Sync_Pro_History();
					$import->admin_settings_page();
					break;
				case 'cron':
					$import = new Webtoffee_Product_Feed_Sync_Pro_Cron();
					$import->admin_settings_page();
					break;
				case 'settings':
					  $wtimportexport = new Webtoffee_Product_Feed_Sync_Pro();
					$import = new Webtoffee_Product_Feed_Sync_Pro_Admin( $wtimportexport->get_plugin_name(), $wtimportexport->get_version() );
					$import->admin_settings_page();
					break;
				default:
					include plugin_dir_path( __FILE__ ) . 'views/main.php';
					break;
		endswitch;
		}


		/**
		 *   Main ajax hook to handle all export related requests
		 */
		public function ajax_main() {

			include_once plugin_dir_path( __FILE__ ) . 'classes/class-webtoffee-product-feed-sync-pro-export-ajax.php';
			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				/**
				*   Check it is a rerun call
				*/
				// Nonce already verified on main function - sanitization thorugh helper functions.
				$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
				if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
					return false;
				}

				if ( ! $this->_process_rerun( ( isset( $_POST['rerun_id'] ) ? absint( $_POST['rerun_id'] ) : 0 ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$this->export_method = ( isset( $_POST['export_method'] ) ? sanitize_text_field( wp_unslash( $_POST['export_method'] ) ) : '' );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification
					$this->to_export = ( isset( $_POST['to_export'] ) ? sanitize_text_field( wp_unslash( $_POST['to_export'] ) ) : '' );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification
					$this->selected_template = ( isset( $_POST['selected_template'] ) ? intval( $_POST['selected_template'] ) : 0 );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification
				}

				$this->get_steps();

				$ajax_obj = new Webtoffee_Product_Feed_Sync_Pro_Export_Ajax( $this, $this->to_export, $this->steps, $this->export_method, $this->selected_template, $this->rerun_id );

				$export_action = isset( $_POST['export_action'] ) ? sanitize_text_field( wp_unslash( $_POST['export_action'] ) ) : '';// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification
				$data_type = isset( $_POST['data_type'] ) ? sanitize_text_field( wp_unslash( $_POST['data_type'] ) ) : '';// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification

				$allowed_ajax_actions = array( 'get_steps', 'get_meta_mapping_fields', 'save_template', 'save_template_as', 'update_template', 'upload', 'export', 'export_image' );

				$out = array(
					'status' => 0,
					'msg' => __( 'Error', 'product-feed-woocommerce' ),
				);

				if ( method_exists( $ajax_obj, $export_action ) && in_array( $export_action, $allowed_ajax_actions ) ) {
					$out = $ajax_obj->{$export_action}( $out );
				}

				if ( 'json' == $data_type ) {
					echo json_encode( $out );
				}
			}
			exit();
		}
		/**
		 * Export filter screen fields.
		 *
		 * @since 1.0.0
		 * 		
		 * @param array $filter_form_data Form data filters.
		 * @return array
		 */
		public function get_filter_screen_fields( $filter_form_data ) {
			$filter_screen_fields = array(
				'limit' => array(
					'label' => __( 'Limit', 'product-feed-woocommerce' ),
					'value' => '',
					'type' => 'number',
					'field_name' => 'limit',
					'placeholder' => 'Unlimited',
					'help_text' => __( 'The actual number of records you want to export. e.g. A limit of 500 with an offset 10 will export records from 11th to 510th position.', 'product-feed-woocommerce' ),
					'attr' => array(
						'step' => 1,
						'min' => 0,
					),
					'validation_rule' => array( 'type' => 'absint' ),
				),
				'offset' => array(
					'label' => __( 'Offset', 'product-feed-woocommerce' ),
					'value' => '',
					'field_name' => 'offset',
					'placeholder' => __( '0', 'product-feed-woocommerce' ),
					'help_text' => __( 'Specify the number of records that should be skipped from the beginning of the database. e.g. An offset of 10 skips the first 10 records.', 'product-feed-woocommerce' ),
					'type' => 'number',
					'attr' => array(
						'step' => 1,
						'min' => 0,
					),
					'validation_rule' => array( 'type' => 'absint' ),
				),
			);
			/**
			 * Filter the filter screen fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $filter_screen_fields Filter screen fields.
			 * @param string $to_export To export.
			 * @param array $filter_form_data Filter form data.
			 */
			$filter_screen_fields = apply_filters( 'wt_pf_exporter_alter_filter_fields_basic', $filter_screen_fields, $this->to_export, $filter_form_data );
			return $filter_screen_fields;
		}
		/**
		 * Export advanced screen fields.
		 *
		 * @since 1.0.0
		 * @param array $advanced_form_data Advanced data filters.
		 * @return array
		 */
		public function get_advanced_screen_fields( $advanced_form_data ) {
			$file_into_arr = array( 'local' => __( 'Local', 'product-feed-woocommerce' ) );

			/* taking available remote adapters */
			$remote_adapter_names = array();
			/**
			 * Filter the advanced screen fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $remote_adapter_names Remote adapter names.
			 */
			$remote_adapter_names = apply_filters( 'wt_pf_exporter_remote_adapter_names_basic', $remote_adapter_names );
			if ( $remote_adapter_names && is_array( $remote_adapter_names ) ) {
				foreach ( $remote_adapter_names as $remote_adapter_key => $remote_adapter_vl ) {
					$file_into_arr[ $remote_adapter_key ] = $remote_adapter_vl;
				}
			}

			$delimiter_default = isset( $advanced_form_data['wt_pf_delimiter'] ) ? $advanced_form_data['wt_pf_delimiter'] : ',';
			$advanced_screen_fields = array(

				'batch_count' => array(
					'label' => __( 'Process in batches of', 'product-feed-woocommerce' ),
					'type' => 'text',
					'merge_right' => true,
					'value' => $this->default_batch_count,
					'field_name' => 'batch_count',
					'help_text' => __( 'The number of records that the server will process for every iteration within the configured timeout interval.', 'product-feed-woocommerce' ),
					'validation_rule' => array( 'type' => 'absint' ),
				),
				'file_as' => array(
					'label' => __(
						'Export file format',
						'product-feed-woocommerce'
					),
					'type' => 'select',
					'sele_vals' => $this->allowed_export_file_type,
					'field_name' => 'file_as',
					'form_toggler' => array(
						'type' => 'parent',
						'target' => 'wt_pf_file_as',
					),
				),
				'delimiter' => array(
					'label' => __(
						'Delimiter',
						'product-feed-woocommerce'
					),
					'type' => 'select',
					'value' => ',',
					'css_class' => 'wt_pf_delimiter_preset',
					'tr_id' => 'delimiter_tr',
					'field_name' => 'delimiter_preset',
					'sele_vals' => Wt_Pf_Catalog_Export_Helper::_get_csv_delimiters(),
					'form_toggler' => array(
						'type' => 'child',
						'id' => 'wt_pf_file_as',
						'val' => 'csv|txt',
					),
					'help_text' => __( 'Separator for differentiating the columns in the CSV file. Assumes TAB by default.', 'product-feed-woocommerce' ),
					'validation_rule' => array( 'type' => 'skip' ),
					'after_form_field' => '<input type="text" class="wt_pf_custom_delimiter" name="wt_pf_delimiter" value="' . $delimiter_default . '" maxlength = "1" />',
				),
			);

			/* taking advanced fields from post type modules */
			/**
			 * Filter the steps.
			 *
			 * @since 1.0.0
			 *
			 * @param array $advanced_screen_fields Advanced screen fields.
			 * @param string $to_export To export.
			 * @param array $advanced_form_data Advanced form data.
			 */
			$advanced_screen_fields = apply_filters( 'wt_pf_exporter_alter_advanced_fields_basic', $advanced_screen_fields, $this->to_export, $advanced_form_data );
			return $advanced_screen_fields;
		}

		/**
		 * Get steps
		 */
		public function get_steps() {
			if ( 'quick' == $this->export_method ) {
				$out = array(
					'post_type' => $this->steps['post_type'],
					'method_export' => $this->steps['method_export'],
					'advanced' => $this->steps['advanced'],
				);
				$this->steps = $out;
			}
			/**
			 * Filter the steps.
			 *
			 * @since 1.0.0
			 *
			 * @param array $steps Steps.
			 * @param string $to_export To export.
			 */
			$this->steps = apply_filters( 'wt_pf_exporter_steps_basic', $this->steps, $this->to_export );
			return $this->steps;
		}


		/**
		 *   Validating and Processing rerun action.
		 *
		 * @since 1.0.0
		 * @param integer $rerun_id cron id.
		 * @return bool
		 */
		protected function _process_rerun( $rerun_id ) {

			if ( $rerun_id > 0 ) {
				/* check the history module is available */
				$history_module_obj = Webtoffee_Product_Feed_Sync_Pro::load_modules( 'history' );
				if ( ! is_null( $history_module_obj ) ) {
					/* check the history entry is for export and also has form_data */
					$history_data = $history_module_obj->get_history_entry_by_id( $rerun_id );

					if ( $history_data && $history_data['template_type'] == $this->module_base ) {
							$form_data = maybe_unserialize( $history_data['data'] );

						if ( $form_data && is_array( $form_data ) ) {

							$posted_to_export = '';
							if ( isset( $_POST['to_export'] ) ) {
								// Nonce already verified on main function.
								$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
								if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
									return false;
								}
								$posted_to_export = ( isset( $_POST['to_export'] ) ? sanitize_text_field( wp_unslash( $_POST['to_export'] ) ) : '' );
							}
							$this->to_export = ( isset( $form_data['post_type_form_data'] ) && isset( $form_data['post_type_form_data']['item_type'] ) ? $form_data['post_type_form_data']['item_type'] : '' );
							if ( '' == $this->to_export ) {
														$this->to_export = ( isset( $form_data['post_type_form_data'] ) && isset( $form_data['post_type_form_data']['wt_pf_export_post_type'] ) ? $form_data['post_type_form_data']['wt_pf_export_post_type'] : '' );
							}

							if ( ( $posted_to_export ) && $posted_to_export !== $this->to_export ) { // If the channel type changes fro rerun, it should reflect on coming steps.
								$this->to_export = $posted_to_export;
							}
							if ( '' != $this->to_export ) {
								$this->export_method = ( isset( $form_data['method_export_form_data'] ) && isset( $form_data['method_export_form_data']['method_export'] ) && '' != $form_data['method_export_form_data']['method_export'] ? $form_data['method_export_form_data']['method_export'] : $this->default_export_method );
								$this->rerun_id = $rerun_id;
								$this->form_data = $form_data;
								// process steps based on the export method in the history entry.
								// $this->get_steps(); // Commented to block loading all steps for re-run.
								return true;
							}
						}
					}
				}
			}
			return false;
		}
		/**
		 *   Assets loading.
		 *
		 * @since 1.0.0
		 */
		protected function enqueue_assets() {
			if ( Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_is_screen_allowed() ) {
				wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, false );
				wp_enqueue_style( 'jquery-ui-datepicker' );
				wp_enqueue_style( WEBTOFFEE_PRODUCT_FEED_PRO_ID . '-jquery-ui', WT_PRODUCT_FEED_PRO_PLUGIN_URL . 'admin/css/jquery-ui.css', array(), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, 'all' );

				$file_names = array();
				if ( ! $this->rerun_id ) {
					$file_details = Webtoffee_Product_Feed_Sync_Pro_History::get_filename_items();
					foreach ( $file_details as $key => $value ) {
						$file_names[] = str_replace( '.csv', '', $value['file_name'] );
					}
				}

				$params = array(
					'item_type' => '',
					'steps' => $this->steps,
					'rerun_id' => $this->rerun_id,
					'to_export' => isset( $_GET['wt_to_export'] ) ? sanitize_text_field( wp_unslash( $_GET['wt_to_export'] ) ) : $this->to_export,// phpcs:ignore WordPress.Security.NonceVerification
					'export_method' => $this->export_method,
					'export_file_names' => json_encode( $file_names ),
					'msgs' => array(
						'choosed_template' => __(
							'Choosed template: ',
							'product-feed-woocommerce'
						),
						'choose_export_method' => __(
							'Please select an export method.',
							'product-feed-woocommerce'
						),
						'choose_template' => __(
							'Please select an export template.',
							'product-feed-woocommerce'
						),
						'step' => __(
							'Step',
							'product-feed-woocommerce'
						),
						'choose_ftp_profile' => __(
							'Please select an FTP profile.',
							'product-feed-woocommerce'
						),
						'export_cancel_warn' => __(
							'Are you sure to stop the product feed generation?',
							'product-feed-woocommerce'
						),
						'export_fill_warn' => __(
							'Please fill the file name',
							'product-feed-woocommerce'
						),
						'file_name_duplicate' => __(
							'File name already exist',
							'product-feed-woocommerce'
						),
					),
				);
				wp_localize_script( $this->module_id, 'wt_pf_export_basic_params', $params );

				$this->add_select2_lib(); // adding select2 JS, It checks the availibility of woocommerce.
			}
		}

		/**
		 *
		 * Enqueue select2 library, if woocommerce available use that
		 */
		protected function add_select2_lib() {
			/* enqueue scripts */
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION );
			} else {
				wp_enqueue_style( WEBTOFFEE_PRODUCT_FEED_PRO_ID . '-select2', WT_PRODUCT_FEED_PRO_PLUGIN_URL . 'admin/css/select2.css', array(), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, 'all' );
				wp_enqueue_script( WEBTOFFEE_PRODUCT_FEED_PRO_ID . '-select2', WT_PRODUCT_FEED_PRO_PLUGIN_URL . 'admin/js/select2.js', array( 'jquery' ), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, false );
			}
		}


		/**
		 * Upload data to the user chosen remote method (Eg: FTP).
		 *
		 * @param   string  $step the action to perform, here 'upload'.
		 * @param   integer $export_id export id.
		 * @param   string  $to_export to export type.
		 *
		 * @return array
		 */
		public function process_upload( $step, $export_id, $to_export ) {
			$out = array(
				'response' => false,
				'export_id' => 0,
				'history_id' => 0, // same as that of export id.
				'finished' => 0,
				'file_url' => '',
				'msg' => '',
			);

			if ( 0 == $export_id ) {
				return $out;
			}

			// take history data by export_id.
			$export_data = Webtoffee_Product_Feed_Sync_Pro_History::get_history_entry_by_id( $export_id );
			if ( is_null( $export_data ) ) {
				return $out;
			}

			$form_data = maybe_unserialize( $export_data['data'] );

			// taking file name.
			$file_name = ( isset( $export_data['file_name'] ) ? $export_data['file_name'] : '' );

			$file_path = $this->get_file_path( $file_name );
			if ( false === $file_path ) {
				$update_data = array(
					'status' => Webtoffee_Product_Feed_Sync_Pro_History::$status_arr['failed'],
					'status_text' => 'File not found.', // no need to add translation function.
				);
				$update_data_type = array(
					'%d',
					'%s',
				);
				Webtoffee_Product_Feed_Sync_Pro_History::update_history_entry( $export_id, $update_data, $update_data_type );

				return $out;
			}

			/* updating output parameters */
			$out['export_id'] = $export_id;
			$out['history_id'] = $export_id;
			$out['file_url'] = '';

			// check where to copy the files.
			$file_into = 'local';
			if ( isset( $form_data['advanced_form_data'] ) ) {
				$file_into = ( isset( $form_data['advanced_form_data']['wt_pf_file_into'] ) ? $form_data['advanced_form_data']['wt_pf_file_into'] : 'local' );
			}

			if ( 'local' != $file_into ) {
				$remote_adapter = Webtoffee_Product_Feed_Sync_Pro::get_remote_adapters( 'export', $file_into );
				if ( is_null( $remote_adapter ) ) {
					$msg = sprintf( 'Unable to initailize %s', $file_into );
					Webtoffee_Product_Feed_Sync_Pro_History::record_failure( $export_id, $msg );
					$out['msg'] = $msg;
					return $out;
				}

				/* upload the file */
				$upload_out_format = array(
					'response' => true,
					'msg' => '',
				);

				$advanced_form_data = ( isset( $form_data['advanced_form_data'] ) ? $form_data['advanced_form_data'] : array() );

				$upload_data = $remote_adapter->upload( $file_path, $file_name, $advanced_form_data, $upload_out_format );
				$out['response'] = ( isset( $upload_data['response'] ) ? $upload_data['response'] : false );
				$out['msg'] = ( isset( $upload_data['msg'] ) ? $upload_data['msg'] : __( 'Error', 'product-feed-woocommerce' ) );

				// unlink the local file.
				wp_delete_file( $file_path );
			} else {
				$out['response'] = true;
				$out['file_url'] = html_entity_decode( $this->get_file_url( $file_name ), ENT_QUOTES, 'UTF-8' );
			}

			$out['finished'] = 1;  // if any error then also its finished, but with errors.
			if ( true === $out['response'] ) {
				$out['msg'] = __( 'Finished', 'product-feed-woocommerce' );

				/* updating finished status */
				$update_data = array(
					'status' => 1,  // success.
				);
				$update_data_type = array(
					'%d',
				);
				Webtoffee_Product_Feed_Sync_Pro_History::update_history_entry( $export_id, $update_data, $update_data_type );

			} else // failed.
			{
				// no need to add translation function in message.
				Webtoffee_Product_Feed_Sync_Pro_History::record_failure( $export_id, 'Failed while uploading' );
			}
			return $out;
		}


		/**
		 *   Do the export process
		 *
		 * @param   array   $form_data Form data.
		 * @param   string  $step export step.
		 * @param   string  $to_process to export type.
		 * @param   string  $file_name Filename.
		 * @param   integer $export_id id of export.
		 * @param   integer $offset offset.
		 *
		 * @return array
		 */
		public function process_action( $form_data, $step, $to_process, $file_name = '', $export_id = 0, $offset = 0 ) {

			$out = array(
				'response' => false,
				'new_offset' => 0,
				'export_id' => 0,
				'history_id' => 0, // same as that of export id.
				'total_records' => 0,
				'finished' => 0,
				'file_url' => '',
				'msg' => '',
			);
			/* prepare form_data, If this was not first batch */
			if ( $export_id > 0 ) {
				// take history data by export_id.
				$export_data = Webtoffee_Product_Feed_Sync_Pro_History::get_history_entry_by_id( $export_id );
				if ( is_null( $export_data ) ) {
					return $out;
				}

				// processing form data.
				$form_data = ( isset( $export_data['data'] ) ? maybe_unserialize( $export_data['data'] ) : array() );
			}
			$this->to_export = $to_process;
			$default_batch_count = $this->_get_default_batch_count( $form_data );
			$batch_count = $default_batch_count;
			$file_as = 'csv';
			$csv_delimiter = ',';
			$total_records = 0;

			if ( isset( $form_data['advanced_form_data'] ) ) {
				$batch_count = ( isset( $form_data['advanced_form_data']['wt_pf_batch_count'] ) ? $form_data['advanced_form_data']['wt_pf_batch_count'] : $batch_count );
				$file_as = ( isset( $form_data['advanced_form_data']['wt_pf_file_as'] ) ? $form_data['advanced_form_data']['wt_pf_file_as'] : 'csv' );
				$csv_delimiter = ( isset( $form_data['advanced_form_data']['wt_pf_delimiter'] ) ? $form_data['advanced_form_data']['wt_pf_delimiter'] : ',' );
				$csv_delimiter = ( '' == $csv_delimiter ? ',' : $csv_delimiter );
			}
			$file_as = ( isset( $this->allowed_export_file_type[ $file_as ] ) ? $file_as : 'csv' );

			if ( isset( $form_data['post_type_form_data']['item_filename'] ) ) {
				$generated_file_name = sanitize_file_name( $form_data['post_type_form_data']['item_filename'] . '.' . $file_as );
			} elseif ( isset( $form_data['post_type_form_data']['wt_pf_export_catalog_name'] ) ) {
				$generated_file_name = sanitize_file_name( $form_data['post_type_form_data']['wt_pf_export_catalog_name'] . '.' . $file_as );
			}

			if ( 0 == $export_id ) {
				$file_name = ( '' == $file_name ? $generated_file_name : sanitize_file_name( $file_name . '.' . $file_as ) );
				$export_id = Webtoffee_Product_Feed_Sync_Pro_History::create_history_entry( $file_name, $form_data, $this->to_export, $step );
				$offset = 0;
			} else {
				// taking file name from export data.
				$file_name = ( isset( $export_data['file_name'] ) ? $export_data['file_name'] : $generated_file_name );
				$total_records = ( isset( $export_data['total'] ) ? $export_data['total'] : 0 );
			}

			/* setting history_id in Log section */

			$file_path = $this->get_file_path( $file_name );
			if ( false === $file_path ) {
				$msg = 'Unable to create backup directory. Please grant write permission for `wp-content` folder.';

				// no need to add translation function in message.
				Webtoffee_Product_Feed_Sync_Pro_History::record_failure( $export_id, $msg );

				$out['msg'] = $msg;
				return $out;
			}

			/* giving full data */
			/**
			 * Filter the full form data.
			 *
			 * @since 1.0.0
			 *
			 * @param array $form_data Form data.
			 * @param string $to_process To process.
			 * @param string $step Step.
			 * @param array $selected_template_data Selected template data.
			 */
			$form_data = apply_filters( 'wt_pf_export_full_form_data_basic', $form_data, $to_process, $step, $this->selected_template_data );

			/* hook to get data from corresponding module. Eg: product, order */
			$export_data = array(
				'total' => 100,
				'head_data' => array(
					'abc' => 'hd1',
					'bcd' => 'hd2',
					'cde' => 'hd3',
					'def' => 'hd4',
				),
				'body_data' => array(
					array(
						'abc' => 'Abc1',
						'bcd' => 'Bcd1',
						'cde' => 'Cde1',
						'def' => 'Def1',
					),
					array(
						'abc' => 'Abc2',
						'bcd' => 'Bcd2',
						'cde' => 'Cde2',
						'def' => 'Def2',
					),
				),
			);

			/* in scheduled export. The export method will not available so we need to take it from form_data */
			$form_data_export_method = ( isset( $form_data['method_export_form_data'] ) && isset( $form_data['method_export_form_data']['method_export'] ) ? $form_data['method_export_form_data']['method_export'] : $this->default_export_method );
			$this->export_method = ( '' == $this->export_method ? $form_data_export_method : $this->export_method );
			/**
			 * Capability for menus.
			 *
			 * @since 1.0.0
			 *
			 * @param array $export_data Export data.
			 * @param string $to_process To process.
			 * @param string $step Step.
			 * @param array $form_data Form data.
			 * @param array $selected_template_data Selected template data.
			 * @param string $export_method Export method.
			 * @param int $offset Offset.
			 */
			$export_data = apply_filters( 'wt_pf_exporter_do_export_basic', $export_data, $to_process, $step, $form_data, $this->selected_template_data, $this->export_method, $offset );

			if ( 0 == $offset ) {
				$total_records = intval( isset( $export_data['total'] ) ? $export_data['total'] : 0 );
			}
			$this->_update_history_after_export( $export_id, $offset, $total_records, $export_data );

			/* checking action is finshed */
			$is_last_offset = false;
			$new_offset = $offset + $batch_count; // increase the offset.
			if ( $new_offset >= $total_records ) {
				$is_last_offset = true;
			}

			/* no data from corresponding module */
			if ( $export_data ) {

				if ( 'xml' == $file_as ) {
					include_once WT_PRODUCT_FEED_PRO_PLUGIN_PATH . 'admin/classes/class-webtoffee-product-feed-sync-pro-xmlwriter.php';
					$writer = new Webtoffee_Product_Feed_Sync_Pro_Xmlwriter( $file_path, $form_data );
				} else {
					if( 'tsv' === $file_as || 'txt' === $file_as ){
						$csv_delimiter = "\t";
					}
					
					include_once WT_PRODUCT_FEED_PRO_PLUGIN_PATH . 'admin/classes/class-webtoffee-product-feed-sync-pro-csvwriter.php';
					$writer = new Webtoffee_Product_Feed_Sync_Pro_Csvwriter( $file_path, $offset, $csv_delimiter, $this->use_bom );
				}

						/**
			*   Alter export data before writing to file.
			*
			* @since 1.0.0
			*   @param  array   $export_data        data to export
			*   @param  int     $offset             current offset
			*   @param  boolean $is_last_offset     is current offset is last one
			*   @param  string  $file_as            file type to write Eg: XML, CSV
			*   @param  string  $to_export          Post type
			*   @param  string  $csv_delimiter      CSV delimiter. In case of CSV export
			*   @return array   $export_data        Altered export data
			*/
				$export_data = apply_filters( 'wt_pf_alter_export_data_basic', $export_data, $offset, $is_last_offset, $file_as, $this->to_export, $csv_delimiter );

				$writer->write_to_file( $export_data, $offset, $is_last_offset, $this->to_export );
			}

			/* updating output parameters */
			$out['total_records'] = $total_records;
			$out['export_id'] = $export_id;
			$out['history_id'] = $export_id;
			$out['file_url'] = '';
			$out['response'] = true;

			/* updating action is finshed */
			if ( $is_last_offset ) {
				// check where to copy the files.
				$file_into = 'local';
				if ( isset( $form_data['advanced_form_data'] ) ) {
					$file_into = ( isset( $form_data['advanced_form_data']['wt_pf_file_into'] ) ? $form_data['advanced_form_data']['wt_pf_file_into'] : 'local' );
				}
				if ( 'local' != $file_into ) {
					$out['finished'] = 2; // file created, next upload it.
					/* translators:%s: export option like remote, local */
					$out['msg'] = sprintf( __( 'Uploading to %s', 'product-feed-woocommerce' ), $file_into );
				} else {
					$out['file_url'] = html_entity_decode( $this->get_file_url( $file_name ), ENT_QUOTES, 'UTF-8' );
					$out['finished'] = 1; // finished.

								$history_page_url = '#';
					if ( Webtoffee_Product_Feed_Sync_Pro_Admin::module_exists( 'history' ) ) {
							$history_module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id( 'history' );
							$history_page_url = admin_url( 'admin.php?page=webtoffee_product_feed_main_pro_export&tab=history' );
					}

								$raw_file_url = content_url() . '/uploads/webtoffee_product_feed/' . ( $file_name );

					$msg = '<span class="wt_pf_popup_close dashicons dashicons-dismiss" style="line-height:10px;width:auto" onclick="wt_pf_basic_export.hide_export_info_box();"></span>';
								$msg .= '<h2><span class="dashicons dashicons-yes" style="background:#20B93E;color:#fff;border-radius:15px; margin-right:10px;padding:5px;"></span><br/><p></p><span style="font-weight:400">' . __( 'Feed generated successfully!', 'product-feed-woocommerce' ) . '</span></h2>';
								$msg .= '<span class="wt_pf_info_box_finished_text" style="font-size: 10px; display:block">';
								$msg .= '<a class="button media-button" style="margin-top:10px;margin-right:10px;font-weight:bold;padding:5px 15px" onclick="wt_pf_basic_export.hide_export_info_box();" target="_blank" href="' . $out['file_url'] . '" ><span style="margin-top: 7px;margin-right: 4px;" class="dashicons dashicons-download"></span>' . __( 'Download', 'product-feed-woocommerce' ) . '</a>'
										. '<a class="button media-butto" style="margin-top:10px;margin-right:10px;font-weight:bold;padding:5px 15px" onclick="wt_pf_basic_export.hide_export_info_box();" target="_blank" href="' . $history_page_url . '" >' . __( 'Manage feeds', 'product-feed-woocommerce' ) . '</a>'
										. '<button class="button button-primary wt_pf_copy" style="margin-top:10px;font-weight:bold;padding:5px 15px" data-uri="' . $raw_file_url . '" >' . __( 'Copy URL', 'product-feed-woocommerce' ) . '</a>'
										. '</span>';

					if ( 0 == $total_records && isset( $export_data['no_post'] ) ) {

						$out['no_post'] = true;
						$msg = $export_data['no_post'];
					}
					$out['msg'] = $msg;

					/* updating finished status */
					$update_data = array(
						'status' => Webtoffee_Product_Feed_Sync_Pro_History::$status_arr['finished'],
						'status_text' => 'Finished', // translation function not needed.
						'updated_at' => time(), // updated time.
					);
					$update_data_type = array(
						'%d',
						'%s',
						'%d',

					);
					Webtoffee_Product_Feed_Sync_Pro_History::update_history_entry( $export_id, $update_data, $update_data_type );
				}

				// schedule catalog generation as per the interval on the last offset.
				if ( isset( $form_data['post_type_form_data']['wt_pf_export_catalog_interval'] ) && 'manual' !== $form_data['post_type_form_data']['wt_pf_export_catalog_interval'] ) {
					$cron_obj = new Webtoffee_Product_Feed_Sync_Pro_Cron();
					$cron_obj->add_schedule( $out, $form_data );
				}
			} else {
				$out['new_offset'] = $new_offset;
				/* translators: 1: new offset. 2: total records */
				$out['msg'] = sprintf( __( 'Exporting...(%1$d out of %2$d)', 'product-feed-woocommerce' ), $new_offset, $total_records );

				$export_percentage = (int) ( ( $new_offset / $total_records ) * 100 );
				$out['total_percent'] = $export_percentage;
				$out['total_done'] = $new_offset;

			}
			return $out;
		}

		/**
		 *   Download file via a nonce URL
		 *
		 * @param string $file_name File name.
		 * @since 1.0.0
		 * @return string File path.
		 */
		public static function get_file_path( $file_name ) {
			if ( ! is_dir( self::$export_dir ) ) {
				if ( ! mkdir( self::$export_dir, 0775 ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
					return false;
				} else {
					$files_to_create = array( 'index.php' => '<?php // Silence is golden' );
					foreach ( $files_to_create as $file => $file_content ) {
						if ( ! file_exists( self::$export_dir . '/' . $file ) ) {
							$fh = @fopen( self::$export_dir . '/' . $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
							if ( is_resource( $fh ) ) {
								fwrite( $fh, $file_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
								fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
							}
						}
					}
				}
			}
			return self::$export_dir . '/' . $file_name;
		}

		/**
		 *   Download file via a nonce URL
		 */
		public function download_file() {
			if ( isset( $_GET['wt_pf_export_download'] ) ) {// phpcs:ignore WordPress.Security.NonceVerification
				// phpcs:ignore Nonce and user role check handled by check_write_access method.
				if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
					$file_name = ( isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '' );// phpcs:ignore WordPress.Security.NonceVerification
					if ( '' != $file_name ) {
						$file_arr = explode( '.', $file_name );
						$file_ext = end( $file_arr );
						if ( isset( $this->allowed_export_file_type[ $file_ext ] ) || 'zip' == $file_ext ) {
							$file_path = self::$export_dir . '/' . $file_name;
							if ( file_exists( $file_path ) && is_file( $file_path ) ) {
								header( 'Pragma: public' );
								header( 'Expires: 0' );
								header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
								header( 'Cache-Control: private', false );
								header( 'Content-Transfer-Encoding: binary' );
								header( 'Content-Disposition: attachment; filename="' . $file_name . '";' );
								header( 'Content-Description: File Transfer' );
								header( 'Content-Type: application/octet-stream' );
								header( 'Content-Length: ' . filesize( $file_path ) );
								readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
								exit();

							}
						}
					}
				}
			}
		}
		/**
		 *   Update history after export
		 *
		 * @param   integer $export_id ID.
		 * @param   integer $offset Export offset.
		 * @param   integer $total_records Total records to export.
		 * @param   array   $export_data Data of export.
		 * @since 1.0.0
		 */
		private function _update_history_after_export( $export_id, $offset, $total_records, $export_data ) {
			/* we need to update total record count on first batch */
			if ( 0 == $offset ) {
				$update_data = array(
					'total' => $total_records,
				);
			} else {
				/* updating completed offset */
				$update_data = array(
					'offset' => $offset,
				);
			}
			$update_data_type = array(
				'%d',
			);
			Webtoffee_Product_Feed_Sync_Pro_History::update_history_entry( $export_id, $update_data, $update_data_type );
		}
		/**
		 * Get default batch count.
		 *
		 * @param   array $form_data Form data.
		 * @since 1.0.0
		 * @return int Default batch count.
		 */
		private function _get_default_batch_count( $form_data ) {
			/**
			 * Filter the default batch count.
			 *
			 * @since 1.0.0
			 *
			 * @param int $default_batch_count Default batch count.
			 * @param string $to_export To export.
			 * @param array $form_data Form data.
			 * @return int Default batch count.
			 */
			$default_batch_count = absint( apply_filters( 'wt_pf_exporter_alter_default_batch_count_basic', $this->default_batch_count, $this->to_export, $form_data ) );
			$form_data = null;
			unset( $form_data );
			return ( 0 == $default_batch_count ? $this->default_batch_count : $default_batch_count );
		}

		/**
		 * Generating downloadable URL for a file
		 *
		 * @param string $file_name Filename.
		 * @return string
		 */
		private function get_file_url( $file_name ) {
			return wp_nonce_url( admin_url( 'admin.php?wt_pf_export_download=true&file=' . $file_name ), WEBTOFFEE_PRODUCT_FEED_PRO_ID );
		}

		/**
		 * Product additional fields
		 *
		 * @param array $tabs Tabs.
		 * @return array
		 */
		public function product_data_tabs( $tabs ) {
			$wt_feed_tab = array(
				'label'    => _x( 'WebToffee Product Feed', 'product data tab', 'product-feed-woocommerce' ),
				'target'   => 'wt_feed_data',
				'class'    => array( 'hide_if_variable' ),
				'priority' => 35,
			);
			$index = array_search( 'linked_product', array_keys( $tabs ), true );
			if ( false === $index ) {
				$tabs['wt_feed'] = $wt_feed_tab;
			} else {
				$tabs = array_merge(
					array_slice( $tabs, 0, $index ),
					array(
						'wt_feed' => $wt_feed_tab,
					),
					array_slice( $tabs, $index )
				);
			}
			return $tabs;
		}
		/**
		 * Product data panels edit screen
		 */
		public function product_data_panels() {
			include 'views/html-product-data-feed.php';
		}
		/**
		 * Save product edit
		 *
		 * @param int $post_id Post id.
		 */
		public function save_product_data( $post_id ) {

			if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
				return;
			}
			// Save product properties.
			$props = array(
				'discard',
				'brand',
				'condition',
				'gtin',
				'mpn',
				'agegroup',
				'gender',
				'size',
				'color',
				'material',
				'pattern',
				'unit_pricing_measure',
				'unit_pricing_base_measure',
				'energy_efficiency_class',
				'min_energy_efficiency_class',
				'max_energy_efficiency_class',
				'glpi_pickup_method',
				'glpi_pickup_sla',
				'custom_label_0',
				'custom_label_1',
				'custom_label_2',
				'custom_label_3',
				'custom_label_4',
				'google_product_category',
			);

			foreach ( $props as $prop ) {
				$key   = "_wt_feed_{$prop}";
				$value = ( ! empty( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( $value ) {
					update_post_meta( $post_id, $key, $value );
				} else {
					delete_post_meta( $post_id, $key );
				}
			}

				$channel_based_params = array(
					'_wt_facebook_fb_product_category',
					'_wt_google_google_product_category',
				);
				foreach ( $channel_based_params as $chanel_prop ) {

					$value = ( ! empty( $_POST[ $chanel_prop ] ) ? wc_clean( wp_unslash( $_POST[ $chanel_prop ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

					if ( $value ) {
						update_post_meta( $post_id, $chanel_prop, $value );
					} else {
						delete_post_meta( $post_id, $chanel_prop );
					}
				}
		}
		/**
		 * Edit variation meta fields
		 *
		 * @param int $variation_count Variation count.
		 * @param int $variation_id Variation ID.
		 * @param object $variation_prod Product.
		 */
		public function wt_feed_variable_custom_meta_fields( $variation_count, $variation_id, $variation_prod ) {

			include 'views/html-product-variation-data-feed.php';
		}
		/**
		 * Save variation edit
		 *
		 * @param int $id ID.
		 * @return void
		 */
		public function wt_feed_save_variable_metas( $id ) {

			if ( empty( $_POST['_wt_feed_variations'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wt_feed_variations'] ) ), 'wt_feed_variations' ) || ! isset( $_POST['variable_post_id'] ) || ! is_array( $_POST['variable_post_id'] ) ) {
				return;
			}

			$variable_post_id = isset( $_POST['variable_post_id'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['variable_post_id'] ) ) : array();
			$count = ! empty( $variable_post_id ) ? max( array_keys( $variable_post_id ) ) : 0;
			for ( $i = 0; $i <= $count; $i++ ) {
				if ( isset( $_POST['variable_post_id'][ $i ] ) ) {

					$post_id = sanitize_text_field( wp_unslash( $_POST['variable_post_id'][ $i ] ) );
					$props = array(
						'discard',
						'brand',
						'gtin',
						'mpn',
						'condition',
						'agegroup',
						'gender',
						'size',
						'color',
						'material',
						'pattern',
						'unit_pricing_measure',
						'unit_pricing_base_measure',
						'energy_efficiency_class',
						'min_energy_efficiency_class',
						'max_energy_efficiency_class',
						'glpi_pickup_method',
						'glpi_pickup_sla',
						'custom_label_0',
						'custom_label_1',
						'custom_label_2',
						'custom_label_3',
						'custom_label_4',
						'google_product_category',
					);
					foreach ( $props as $prop ) {
						$key = "_wt_feed_{$prop}";
						$value = ( ! empty( $_POST[ $key ][ $i ] ) ? wc_clean( wp_unslash( $_POST[ $key ][ $i ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

						if ( $value ) {
							update_post_meta( $post_id, $key, $value );
						} else {
							delete_post_meta( $post_id, $key );
						}
					}
					$channel_based_params = array(
						'_wt_facebook_fb_product_category',
						'_wt_google_google_product_category',
					);
					foreach ( $channel_based_params as $chanel_prop ) {

							$value = ( ! empty( $_POST[ $chanel_prop ][ $i ] ) ? wc_clean( wp_unslash( $_POST[ $chanel_prop ][ $i ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

						if ( $value ) {
								update_post_meta( $post_id, $chanel_prop, $value );
						} else {
								delete_post_meta( $post_id, $chanel_prop );
						}
					}
				}
			}
		}
		/**
		 * Tab icon
		 */
		public function wt_feed_tab_styles() {

			$custom_css = '#woocommerce-product-data ul.wc-tabs li.wt_feed_options a::before{
						font-family: Dashicons;
						font-style: normal;
						font-weight: 600;
						font-variant: normal;
						text-transform: none;
						speak: none;
						display: inline-block;
						text-decoration: inherit;
						-webkit-font-smoothing: antialiased;
						-moz-osx-font-smoothing: grayscale;
						content: "\f312";
					}';
			wp_add_inline_style( 'woocommerce_admin_styles', $custom_css );
		}
	}
}
Webtoffee_Product_Feed_Sync_Pro::$loaded_modules['export'] = new Webtoffee_Product_Feed_Sync_Pro_Export();
