<?php
/**
 * Ajax section of the Export module
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Export_Ajax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Export_Ajax' ) ) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_Export_Ajax
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Export_Ajax {

		/**
		 * Step
		 *
		 * @var string
		 */
		public $step = '';
		/**
		 * Steps
		 *
		 * @var array
		 */
		public $steps = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $step_btns = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $export_method = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $to_export = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $step_title = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $step_keys = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $current_step_index = 0;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $current_step_number = 1;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $last_page = false;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $total_steps = 0;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $step_summary = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $mapping_enabled_fields = array();
		/**
		 * Mapping
		 *
		 * @var array
		 */
		protected $mapping_templates = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		protected $selected_template = 0;
		/**
		 * Form data
		 *
		 * @var array
		 */
		protected $selected_template_form_data = array();
		/**
		 * This variable is using to store form_data of selected template or selected history entry
		 *
		 * @var object
		 */
		protected $export_obj = null;
		/**
		 * Rerun ID
		 *
		 * @var integer
		 */
		protected $rerun_id = 0;

		/**
		 * Constructor.
		 *
		 * @param   string  $export_obj Export object.
		 * @param   integer $to_export Export type.
		 * @param   string  $steps Steps.
		 * @param   integer $export_method Export method.
		 * @param   string  $selected_template Selected template.
		 * @param   string  $rerun_id Rerun ID.
		 * @since 1.0.0
		 */
		public function __construct( $export_obj, $to_export, $steps, $export_method, $selected_template, $rerun_id ) {
			$this->export_obj = $export_obj;
			$this->to_export = $to_export;
			$this->steps = $steps;
			$this->export_method = $export_method;
			$this->selected_template = $selected_template;
			$this->rerun_id = $rerun_id;
		}

		/**
		 *    Ajax main function to retrieve steps HTML
		 *
		 * @param   array $out Output response.
		 * @since 1.0.0
		 */
		public function get_steps( $out ) {

			// Nonce already verified on main function - sanitization thorugh helper functions.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
			}
			$steps_arra = isset( $_POST['steps'] ) ? map_deep( wp_unslash( $_POST['steps'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification.Missing
			$steps     = ( is_array( $steps_arra ) ? $steps_arra : array( $steps_arra ) );
			$steps     = Wt_Pf_Sh::sanitize_item( $steps, 'text_arr' );
			$page_html = array();

			if ( $this->selected_template > 0 ) {
				$this->get_template_form_data( $this->selected_template );
			} elseif ( $this->rerun_id > 0 ) {
				$this->selected_template_form_data = $this->export_obj->form_data;
			}

			foreach ( $steps as $step ) {
				$method_name = $step . '_page';
				if ( method_exists( $this, $method_name ) ) {
					$page_html[ $step ] = $this->{$method_name}();

					if ( 'method_export' == $step && ( $this->selected_template > 0 || $this->rerun_id > 0 ) ) {
						$out['template_data'] = $this->selected_template_form_data;
					}
				}
			}
			$out['status'] = 1;
			$out['page_html'] = $page_html;
			return $out;
		}

		/**
		 *     Ajax function to retrive meta step data
		 *
		 * @param   array $out Output response.
		 * @since 1.0.0
		 */
		public function get_meta_mapping_fields( $out ) {
			if ( $this->selected_template > 0 ) {
				$this->get_template_form_data( $this->selected_template );
			} elseif ( $this->rerun_id > 0 ) {
				$this->selected_template_form_data = $this->export_obj->form_data;
			}

			$this->get_mapping_enabled_fields();

			$meta_mapping_screen_fields = array();
			foreach ( $this->mapping_enabled_fields as $field_key => $field_vl ) {
				$field_vl = ( ! is_array( $field_vl ) ? array( $field_vl, 0 ) : $field_vl );
				$meta_mapping_screen_fields[ $field_key ] = array(
					'title' => '',
					'checked' => $field_vl[1],
					'fields' => array(),
				);
			}

			// taking current page form data.
			$meta_step_form_data = ( isset( $this->selected_template_form_data['meta_step_form_data'] ) ? $this->selected_template_form_data['meta_step_form_data'] : array() );

			/* form_data/template data of fields in mapping page */
			$form_data_meta_mapping_fields = isset( $meta_step_form_data['mapping_fields'] ) ? $meta_step_form_data['mapping_fields'] : array();
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables adding extra arguments or setting defaults for a post
			 * collection request.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $meta_mapping_screen_fields    Mapping fields.
			 * @param string          $this->to_export    Export post type.
			 * @param array           $form_data_meta_mapping_fields    Form Mapping fields.
			 */
			$meta_mapping_screen_fields = apply_filters( 'wt_pf_exporter_alter_meta_mapping_fields_basic', $meta_mapping_screen_fields, $this->to_export, $form_data_meta_mapping_fields );

			$draggable_tooltip = __( 'Drag to rearrange the columns', 'product-feed-woocommerce' );
			$module_url = plugin_dir_url( __DIR__ );

			$meta_html = array();
			if ( $meta_mapping_screen_fields && is_array( $meta_mapping_screen_fields ) ) {
				/* loop through mapping fields */
				foreach ( $meta_mapping_screen_fields as $meta_mapping_screen_field_key => $meta_mapping_screen_field_val ) {
					$current_meta_step_form_data = ( isset( $form_data_meta_mapping_fields[ $meta_mapping_screen_field_key ] ) ? $form_data_meta_mapping_fields[ $meta_mapping_screen_field_key ] : array() );
					ob_start();
					include dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-meta-step-page.php';
					$meta_html[ $meta_mapping_screen_field_key ] = ob_get_clean();
				}
			}
			foreach ( $meta_html as $key => $value ) {
				$meta_html[ $key ] = Wt_Pf_Catalog_Export_Helper::sanitize_and_minify_html( $value );
			}
			$out['status'] = 1;
			$out['meta_html'] = $meta_html;
			return $out;
		}
		/**
		 *     Ajax function to save template
		 *
		 * @param   array $out Output response.
		 * @since 1.0.0
		 */
		public function save_template( $out ) {
			return $this->do_save_template( 'save', $out );
		}
		/**
		 *     Ajax function to save template copy
		 *
		 * @param   array $out Output response.
		 * @since 1.0.0
		 */
		public function save_template_as( $out ) {
			return $this->do_save_template( 'save_as', $out );
		}
		/**
		 *     Ajax function to update template
		 *
		 * @param   array $out Output response.
		 * @since 1.0.0
		 */
		public function update_template( $out ) {
			return $this->do_save_template( 'update', $out );
		}

		/**
		 *     Ajax hook to upload the exported file.
		 *
		 * @param   array $out Output response.
		 * @since 1.0.0
		 */
		public function upload( $out ) {
			// Nonce already verified on main function - sanitization thorugh helper functions.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
			}
			$export_id = ( isset( $_POST['export_id'] ) ? intval( $_POST['export_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$out = $this->export_obj->process_upload( 'upload', $export_id, $this->to_export );
			if ( true === $out['response'] ) {
				$out['status'] = 1;
			} else {
				$out['status'] = 0;
			}
			return $out;
		}


		/**
		 * Process the export data
		 *
		 * @param array $out Response.
		 * @return array
		 */
		public function export( $out ) {

			// Nonce already verified on main function - sanitization thorugh helper functions.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
			}
			$offset    = ( isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$export_id = ( isset( $_POST['export_id'] ) ? intval( $_POST['export_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $this->rerun_id > 0 ) {
				$export_id = $this->rerun_id;
			}
			$file_name = '';

			if ( 0 == $offset ) {
				/* process form data */
				$form_data = ( isset( $_POST['form_data'] ) ? Webtoffee_Product_Feed_Sync_Pro_Common_Helper::process_formdata( maybe_unserialize( map_deep( wp_unslash( $_POST['form_data'] ), 'sanitize_text_field' ) ) ) : array() );

				// sanitize form data.
				$form_data = Wt_Pf_Catalog_Export_Helper::sanitize_formdata( $form_data, $this->export_obj );

				if ( isset( $form_data['category_mapping_form_data'] ) ) {
					/**
					 * Filter the query arguments for a request.
					 *
					 * Enables adding extra arguments or setting defaults for a post
					 * collection request.
					 *
					 * @since 1.0.0
					 *
					 * @param array           $form_data    Form data.
					 */
					$category_mapping_update = apply_filters( 'wt_pf_feed_category_mapping', $form_data );
				}

				if ( $this->rerun_id > 0 ) {
					/* updating finished status */
					$file_as = ( isset( $form_data['advanced_form_data']['wt_pf_file_as'] ) ? $form_data['advanced_form_data']['wt_pf_file_as'] : 'csv' );
					$file_name = sanitize_file_name( $form_data['post_type_form_data']['wt_pf_export_catalog_name'] . '.' . $file_as );
					$update_data = array(
						'file_name' => $file_name, // export file name.
						'created_at' => time(), // craeted time.
						'updated_at' => time(), // craeted time.
						'data' => maybe_serialize( $form_data ), // formadata.
						'status' => Webtoffee_Product_Feed_Sync_Pro_History::$status_arr['pending'], // pending.
						'status_text' => 'Pending', // pending, No need to add translate function. we can add this on printing page.
						'offset' => 0, // current offset, its always 0 on start.
						'total' => 0, // total records, not available now.
					);
					$update_data_type = array(
						'%s',
						'%d',
						'%d',
						'%s',
						'%d',
						'%s',
						'%d',
						'%d',
					);

					Webtoffee_Product_Feed_Sync_Pro_History::update_history_entry( $export_id, $update_data, $update_data_type );
				}
			} else {
				/* no need to send form_data. It will take from history table by `process_action` method */
				$form_data = array();
			}

			/* do the export process */
			$out = $this->export_obj->process_action( $form_data, 'export', $this->to_export, $file_name, $export_id, $offset );
			if ( true === $out['response'] ) {
				$out['status'] = 1;
			} else {
				$out['status'] = 0;
			}
			return $out;
		}

		/**
		 * Save/Update template (Ajax sub function)
		 *
		 * @param  string $step current step.
		 * @param array  $out Response.
		 * @return array response status, name, id
		 */
		public function do_save_template( $step, $out ) {
			// Nonce already verified on main function - sanitization thorugh helper functions.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
			}
			$is_update = ( 'update' == $step ) ? true : false;

			// take template name from post data, if not then create from time stamp.
			$template_name = ( isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : gmdate( 'd-M-Y h:i:s A' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.DateTime.RestrictedFunctions.date_date

			$template_name = stripslashes( $template_name );
			$out['name'] = $template_name;
			$out['id'] = 0;
			$out['status'] = 1;

			if ( '' != $this->to_export ) {
				global $wpdb;

				/* checking: just saved and again click the button so shift the action as update */
				if ( 'save' == $step && $this->selected_template > 0 ) {
					$is_update = true;
				}

				/* checking template with same name exists */
				$template_data = $this->get_mapping_template_by_name( $template_name );
				if ( $template_data ) {
					$is_throw_warn = false;
					if ( $is_update ) {
						if ( $template_data['id'] != $this->selected_template ) {
							$is_throw_warn = true;
						}
					} else {
						$is_throw_warn = true;
					}

					if ( $is_throw_warn ) {
						$out['status'] = 0;
						if ( 'save_as' == $step ) {
							$out['msg'] = __( 'Please enter a different name', 'product-feed-woocommerce' );
						} else {
							$out['msg'] = __( 'Template with same name already exists', 'product-feed-woocommerce' );
						}
						return $out;
					}
				}

				$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$template_tb;

				/* process form data */
				$form_data = ( isset( $_POST['form_data'] ) ? Webtoffee_Product_Feed_Sync_Pro_Common_Helper::process_formdata( maybe_unserialize( map_deep( wp_unslash( $_POST['form_data'] ), 'sanitize_text_field' ) ) ) : array() );

				// sanitize form data.
				$form_data = Wt_Pf_Catalog_Export_Helper::sanitize_formdata( $form_data, $this->export_obj );

				/* upadte the template */
				if ( $is_update ) {

					$update_data = array(
						'data' => maybe_serialize( $form_data ),
						'name' => $template_name, // may be a rename.
					);
					$update_data_type = array(
						'%s',
						'%s',
					);
					$update_where = array(
						'id' => $this->selected_template,
					);
					$update_where_type = array(
						'%d',
					);
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					if ( $wpdb->update( $tb, $update_data, $update_where, $update_data_type, $update_where_type ) !== false ) {
						$out['id'] = $this->selected_template;
						$out['msg'] = __( 'Template updated successfully', 'product-feed-woocommerce' );
						$out['name'] = $template_name;
						return $out;
					}
				} else {
					$insert_data = array(
						'template_type' => 'export',
						'item_type' => $this->to_export,
						'name' => $template_name,
						'data' => maybe_serialize( $form_data ),
					);
					$insert_data_type = array(
						'%s',
						'%s',
						'%s',
						'%s',
					);
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					if ( $wpdb->insert( $tb, $insert_data, $insert_data_type ) ) {
						$out['id'] = $wpdb->insert_id;
						$out['msg'] = __( 'Template saved successfully', 'product-feed-woocommerce' );
						return $out;
					}
				}
			}
			$out['status'] = 0;
			return $out;
		}

		/**
		 *  Step 1 (Ajax sub function)
		 *  Built in steps, post type choosing page
		 */
		public function post_type_page() {
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables adding extra arguments or setting defaults for a post
			 * collection request.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $post_types    Post types.
			 */
			$post_types = apply_filters( 'wt_pf_exporter_post_types_basic', array() );

			$post_types = ( ! is_array( $post_types ) ? array() : $post_types );
			$this->step = 'post_type';
			$step_info = $this->steps[ $this->step ];
			$item_type = $this->to_export;

			$item_filename = '';
			$item_country = 'US';
			$item_lang = '';
			$item_currency = '';
			$item_exc_cat = array();
			$item_inc_cat = array();
			$inc_exc_cat = array();
			$inc_exc_tag = array();
			$excl_prods = array();
			$item_outofstock = 0;
			$item_inc_parentonly = 0;
			$item_gen_interval = 'daily';
			$item_gen_cron_type = 'wordpress_cron';
			$item_product_type = array();
			$item_cat_filter_type = 'include_cat';
			$item_tag_filter_type = 'include_tag';
			$item_brand_filter_type = 'include_brand';
			$item_gen_cron_day = 'sunday';
			$item_gen_cron_ampm = 'am';
			$item_gen_cron_date = 1;
			$item_gen_cron_start_val = '1';
			$item_gen_cron_start_val_min = '01';
			$inc_exc_brand = array();
			$variations_type_selected = '';
			$item_author = '';
			$wt_pf_parent_qty = '';

			if ( $this->rerun_id > 0 ) {

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_filename'] ) ) {
					$item_filename = isset( $this->export_obj->form_data['post_type_form_data']['item_filename'] ) ? $this->export_obj->form_data['post_type_form_data']['item_filename'] : '';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_name'] ) ) {
					$item_filename = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_name'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_name'] : '';
				}
				$item_country = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_country'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_country'] : 'US';
				$item_lang = isset( $this->export_obj->form_data['post_type_form_data']['item_post_lang'] ) ? $this->export_obj->form_data['post_type_form_data']['item_post_lang'] : '';
				$item_currency = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_post_currency'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_export_post_currency'] : '';
				$item_cat_filter_type = isset( $this->export_obj->form_data['post_type_form_data']['item_cat_filter_type'] ) ? $this->export_obj->form_data['post_type_form_data']['item_cat_filter_type'] : 'include_cat';
				$item_exc_cat = isset( $this->export_obj->form_data['post_type_form_data']['item_exc_cat'] ) ? $this->export_obj->form_data['post_type_form_data']['item_exc_cat'] : array();
				$item_inc_cat = isset( $this->export_obj->form_data['post_type_form_data']['item_inc_cat'] ) ? $this->export_obj->form_data['post_type_form_data']['item_inc_cat'] : array();
				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_exc_prd'] ) ) {
					$excl_prods = isset( $this->export_obj->form_data['post_type_form_data']['item_exc_prd'] ) ? $this->export_obj->form_data['post_type_form_data']['item_exc_prd'] : array();
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_products'] ) ) {
					$excl_prods = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_products'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_products'] : array();
				}
				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_outofstock'] ) ) {
					$item_outofstock = ( isset( $this->export_obj->form_data['post_type_form_data']['item_outofstock'] ) && ( 'on' == $this->export_obj->form_data['post_type_form_data']['item_outofstock'] || 1 == $this->export_obj->form_data['post_type_form_data']['item_outofstock'] ) ) ? 1 : 0;
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_outofstock'] ) ) {
					$item_outofstock = ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_outofstock'] ) && ( 'on' == $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_outofstock'] || 1 == $this->export_obj->form_data['post_type_form_data']['wt_pf_exclude_outofstock'] ) ) ? 1 : 0;
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_parentonly'] ) ) {
					$item_inc_parentonly = ( isset( $this->export_obj->form_data['post_type_form_data']['item_parentonly'] ) && ( 'on' == $this->export_obj->form_data['post_type_form_data']['item_parentonly'] || 1 == $this->export_obj->form_data['post_type_form_data']['item_parentonly'] ) ) ? 1 : 0;
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_include_parent_only'] ) ) {
					$item_inc_parentonly = ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_include_parent_only'] ) && ( 'on' == $this->export_obj->form_data['post_type_form_data']['wt_pf_include_parent_only'] || 1 == $this->export_obj->form_data['post_type_form_data']['wt_pf_include_parent_only'] ) ) ? 1 : 0;
				}
				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_interval'] ) ) {
					$item_gen_interval = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_interval'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_interval'] : 'hourly';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_interval'] ) ) {
					$item_gen_interval = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_interval'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_export_catalog_interval'] : 'hourly';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_day'] ) ) {
					$item_gen_cron_day = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_day'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_cron_day'] : 'sunday';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_schedule_cron_day'] ) ) {
					$item_gen_cron_day = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_schedule_cron_day'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_schedule_cron_day'] : 'sunday';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_date'] ) ) {
					$item_gen_cron_date = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_date'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_cron_date'] : 1;
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_interval_date'] ) ) {
					$item_gen_cron_date = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_interval_date'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_interval_date'] : 1;
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_start_val'] ) ) {
					$item_gen_cron_start_val = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_start_val'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_cron_start_val'] : '1';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_val'] ) ) {
					$item_gen_cron_start_val = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_val'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_val'] : '1';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_start_val_min'] ) ) {
					$item_gen_cron_start_val_min = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_start_val_min'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_cron_start_val_min'] : '01';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_val_min'] ) ) {
					$item_gen_cron_start_val_min = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_val_min'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_val_min'] : '01';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_ampm'] ) ) {
					$item_gen_cron_ampm = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_ampm'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_cron_ampm'] : 'am';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_ampm_val'] ) ) {
					$item_gen_cron_ampm = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_ampm_val'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_cron_start_ampm_val'] : 'am';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_type'] ) ) {
					$item_gen_cron_type = isset( $this->export_obj->form_data['post_type_form_data']['item_gen_cron_type'] ) ? $this->export_obj->form_data['post_type_form_data']['item_gen_cron_type'] : 'wordpress_cron';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_catalog_cron_type'] ) ) {
					$item_gen_cron_type = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_catalog_cron_type'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_catalog_cron_type'] : 'wordpress_cron';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['item_product_type'] ) ) {
					$item_product_type = isset( $this->export_obj->form_data['post_type_form_data']['item_product_type'] ) ? $this->export_obj->form_data['post_type_form_data']['item_product_type'] : array();
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_product_types'] ) ) {
					$item_product_type = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_product_types'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_product_types'] : array();
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['cat_filter_type'] ) ) {
					$item_cat_filter_type = isset( $this->export_obj->form_data['post_type_form_data']['cat_filter_type'] ) ? $this->export_obj->form_data['post_type_form_data']['cat_filter_type'] : 'include_cat';
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] ) ) {
					$item_cat_filter_type = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] : 'include_cat';
				}

				if ( isset( $this->export_obj->form_data['post_type_form_data']['inc_exc_cat'] ) ) {
					$inc_exc_cat = isset( $this->export_obj->form_data['post_type_form_data']['inc_exc_cat'] ) ? $this->export_obj->form_data['post_type_form_data']['inc_exc_cat'] : array();
				} elseif ( isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_inc_exc_category'] ) ) {
					$inc_exc_cat = isset( $this->export_obj->form_data['post_type_form_data']['wt_pf_inc_exc_category'] ) ? $this->export_obj->form_data['post_type_form_data']['wt_pf_inc_exc_category'] : array();
				}
			}

			$this->prepare_step_summary();
			$this->prepare_footer_button_list();

			ob_start();
			$this->prepare_step_header_html();
			include_once dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-post-type-page.php';
			$this->prepare_step_footer_html();
			return ob_get_clean();
		}

		/**
		 *  Step 2 (Ajax sub function)
		 * Built in steps, export method choosing page
		 */
		public function method_export_page() {
			$this->step = 'method_export';
			$step_info = $this->steps[ $this->step ];
			if ( '' != $this->to_export ) {
				/* setting a default export method */
				$this->export_method = ( '' == $this->export_method ? $this->export_obj->default_export_method : $this->export_method );
				$this->export_obj->export_method = $this->export_method;
				$this->steps = $this->export_obj->get_steps();

				$form_data_export_template = $this->selected_template;
				$form_data_mapping_enabled = array();
				if ( $this->rerun_id > 0 ) {
					if ( isset( $this->selected_template_form_data['method_export_form_data'] ) ) {
						if ( isset( $this->selected_template_form_data['method_export_form_data']['selected_template'] ) ) {
							/* do not set this value to `$this->selected_template` */
							$form_data_export_template = $this->selected_template_form_data['method_export_form_data']['selected_template'];
						}
						if ( isset( $this->selected_template_form_data['method_export_form_data']['mapping_enabled_fields'] ) ) {
							$form_data_mapping_enabled = $this->selected_template_form_data['method_export_form_data']['mapping_enabled_fields'];
							$form_data_mapping_enabled = ( is_array( $form_data_mapping_enabled ) ? $form_data_mapping_enabled : array() );
						}
					}
				}

				$this->prepare_step_summary();
				$this->prepare_footer_button_list();

				/* meta field list for quick export */
				$this->get_mapping_enabled_fields();

				/* template list for template export */
				$this->get_mapping_templates();

				ob_start();
				$this->prepare_step_header_html();
				include_once dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-method-export-page.php';
				$this->prepare_step_footer_html();
				return ob_get_clean();
			} else {
				return '';
			}
		}

		/**
		 * Get step information
		 *
		 * @param string $step Step.
		 */
		public function get_step_info( $step ) {
			return isset( $this->steps[ $step ] ) ? $this->steps[ $step ] : array(
				'title' => ' ',
				'description' => ' ',
			);
		}

		/**
		 *   Step 3 (Ajax sub function)
		 *   Built in steps, filter page
		 */
		public function filter_page() {
			$this->step = 'filter';
			$step_info = $this->get_step_info( $this->step );
			if ( '' != $this->to_export ) {

				$this->prepare_step_summary();
				$this->prepare_footer_button_list();

				// taking current page form data.
				$filter_form_data = ( isset( $this->selected_template_form_data['filter_form_data'] ) ? $this->selected_template_form_data['filter_form_data'] : array() );

				$filter_screen_fields = $this->export_obj->get_filter_screen_fields( $filter_form_data );

				ob_start();
				$this->prepare_step_header_html();
				include_once dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-filter-page.php';
				$this->prepare_step_footer_html();
				return ob_get_clean();
			} else {
				return '';
			}
		}


		/**
		 * Step 4 (Ajax sub function)
		 * Built in steps, mapping page
		 */
		public function mapping_page() {
			$this->step = 'mapping';
			$step_info = $this->get_step_info( $this->step );
			if ( '' != $this->to_export ) {

				$this->prepare_step_summary();
				$this->prepare_footer_button_list();

				// taking current page form data.
				$mapping_form_data = ( isset( $this->selected_template_form_data['mapping_form_data'] ) ? $this->selected_template_form_data['mapping_form_data'] : array() );

				/* form_data/template data of fields in mapping page */
				$form_data_mapping_fields = isset( $mapping_form_data['mapping_fields'] ) ? $mapping_form_data['mapping_fields'] : array();

				/* default mapping page fields */
				$mapping_fields = array();
				/**
				 * Filter the query arguments for a request.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 *
				 * @param array           $mapping_fields    Post types.
				 * @param array           $this->to_export    Post types.
				 * @param array           $form_data_mapping_fields    Post types.
				 */
				$mapping_fields = apply_filters( 'wt_pf_exporter_alter_mapping_fields_basic', $mapping_fields, $this->to_export, $form_data_mapping_fields );

				/* meta fields list */
				$this->get_mapping_enabled_fields();

				/* mapping enabled meta fields */
				$form_data_mapping_enabled_fields = ( isset( $mapping_form_data['mapping_enabled_fields'] ) ? $mapping_form_data['mapping_enabled_fields'] : array() );

				ob_start();
				$this->prepare_step_header_html();
				include_once dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-mapping-page.php';
				$this->prepare_step_footer_html();
				return ob_get_clean();
			} else {
				return '';
			}
		}


		/**
		 * Step 4 (Ajax sub function)
		 * Built in steps, mapping page
		 */
		public function category_mapping_page() {
			/*
				 * Skip category mapping execution for google promotion, buy on google and local product inventory
				 */
			$skip_category_mapping_channels = array( 'google_local_product_inventory', 'google_promotions', 'buyon_google', 'pinterest_rss' );
			if ( in_array( $this->to_export, $skip_category_mapping_channels ) ) {
				return '';
			}
			$this->step = 'category_mapping';
			$step_info = $this->get_step_info( $this->step );

			if ( '' != $this->to_export ) {

				$this->prepare_step_summary();
				$this->prepare_footer_button_list();
				// taking current page form data.
				$mapping_form_data = ( isset( $this->selected_template_form_data['mapping_form_data'] ) ? $this->selected_template_form_data['mapping_form_data'] : array() );

				/* form_data/template data of fields in mapping page */
				$form_data_mapping_fields = isset( $mapping_form_data['mapping_fields'] ) ? $mapping_form_data['mapping_fields'] : array();

				/* default mapping page fields */
				$mapping_fields = array();
				/**
				 * Filter the query arguments for a request.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 *
				 * @param array           $mapping_fields    Post types.
				 * @param array           $this->to_export    Post types.
				 * @param array           $form_data_mapping_fields    Post types.
				 */
				$mapping_fields = apply_filters( 'wt_pf_exporter_alter_mapping_fields_basic', $mapping_fields, $this->to_export, $form_data_mapping_fields );

				/* meta fields list */
				$this->get_mapping_enabled_fields();

				/* mapping enabled meta fields */
				$form_data_mapping_enabled_fields = ( isset( $mapping_form_data['mapping_enabled_fields'] ) ? $mapping_form_data['mapping_enabled_fields'] : array() );
				$export_post_type = $this->to_export;

				if ( 'facebook' === $this->to_export ) {
					$export_post_type = 'facebook';
				} else {
					$export_post_type = 'google';
				}

				ob_start();
				$this->prepare_step_header_html();
				$ajax_render = true;
				include_once WT_PRODUCT_FEED_PRO_PLUGIN_PATH . '/admin/modules/' . $export_post_type . '/data/wt-feed-category-mapping.php';
				$this->prepare_step_footer_html();
				return ob_get_clean();
			} else {
				return '';
			}
		}

		/**
		 * Step 5 (Ajax sub function)
		 * Built in steps, advanced page
		 */
		public function advanced_page() {
			$this->step = 'advanced';
			$step_info = $this->steps[ $this->step ];
			if ( '' != $this->to_export ) {

				$this->prepare_step_summary();
				$this->prepare_footer_button_list();

				// taking current page form data.
				$advanced_form_data = ( isset( $this->selected_template_form_data['advanced_form_data'] ) ? $this->selected_template_form_data['advanced_form_data'] : array() );

				$advanced_screen_fields = $this->export_obj->get_advanced_screen_fields( $advanced_form_data );

				ob_start();
				$this->prepare_step_header_html();
				include_once dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-advanced-page.php';
				$this->prepare_step_footer_html();
				return ob_get_clean();
			} else {
				return '';
			}
		}

		/**
		 * Get template form data
		 *
		 * @param int $id ID.
		 */
		protected function get_template_form_data( $id ) {
			$template_data = $this->get_mapping_template_by_id( $id );
			if ( $template_data ) {
				$decoded_form_data = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::process_formdata( maybe_unserialize( $template_data['data'] ) );
				$this->selected_template_form_data = ( ! is_array( $decoded_form_data ) ? array() : $decoded_form_data );
			}
		}

		/**
		 * Taking mapping template by Name
		 *
		 * @global object $wpdb
		 * @param string $name Name.
		 * @return object
		 */
		protected function get_mapping_template_by_name( $name ) {
			global $wpdb;
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_mapping_template WHERE template_type=%s AND item_type=%s AND name=%s", array( 'export', $this->to_export, $name ) ), ARRAY_A ); // @codingStandardsIgnoreLine.
		}

		/**
		 * Taking mapping template by ID
		 *
		 * @global object $wpdb
		 * @param int $id ID.
		 * @return object
		 */
		protected function get_mapping_template_by_id( $id ) {
			global $wpdb;
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_mapping_template WHERE template_type=%s AND item_type=%s AND id=%d", array( 'export', $this->to_export, $id ) ), ARRAY_A ); // @codingStandardsIgnoreLine.
		}

		/**
		 * Taking all mapping templates
		 */
		protected function get_mapping_templates() {
			if ( '' == $this->to_export ) {
				return;
			}
			global $wpdb;
			$val = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_mapping_template WHERE template_type='export' AND item_type=%s ORDER BY id DESC", $this->to_export ), ARRAY_A ); // @codingStandardsIgnoreLine.

			// add a filter here for modules to alter the data.
			$this->mapping_templates = ( $val ? $val : array() );
		}

		/**
		 * Get meta field list for mapping page
		 */
		protected function get_mapping_enabled_fields() {
			$mapping_enabled_fields = array();
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables adding extra arguments or setting defaults for a post
			 * collection request.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $mapping_fields    Post types.
			 * @param array           $this->to_export    Post types.
			 * @param array           $form_data_mapping_fields    Post types.
			 */
			$this->mapping_enabled_fields = apply_filters( 'wt_pf_exporter_alter_mapping_enabled_fields_basic', $mapping_enabled_fields, $this->to_export, array() );
		}
		/**
		 * Footer
		 */
		protected function prepare_step_footer_html() {
			include dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-footer.php';
		}
		/**
		 * Summary
		 */
		protected function prepare_step_summary() {
			$step_info = $this->get_step_info( $this->step );
			$this->step_title = $step_info['title'];
			$this->step_keys = array_keys( $this->steps );
			$this->current_step_index = array_search( $this->step, $this->step_keys );
			$this->current_step_number = $this->current_step_index + 1;
			$this->last_page = ( ! isset( $this->step_keys[ $this->current_step_index + 1 ] ) ? true : false );
			$this->total_steps = count( $this->step_keys );
			/* translators: 1: current step number. 2: total steps */
			$this->step_summary        = sprintf( __( 'Step %1$d of %2$d', 'product-feed-woocommerce' ), $this->current_step_number, $this->total_steps );
		}
		/**
		 * Header
		 */
		protected function prepare_step_header_html() {
			include dirname( plugin_dir_path( __FILE__ ) ) . '/views/export-header.php';
		}
		/**
		 * Buttons
		 */
		protected function prepare_footer_button_list() {
			$out = array();
			$step_keys = $this->step_keys;
			$current_index = $this->current_step_index;
			$last_page = $this->last_page;
			if ( false !== $current_index ) {
				if ( $current_index > 0 ) {
					$out['back'] = array(
						'type' => 'button',
						'action_type' => 'step',
						'key' => $step_keys[ $current_index - 1 ],
						'text' => '<span class="dashicons dashicons-arrow-left-alt2" style="line-height:27px;"></span> ' . esc_html( __( 'Back', 'product-feed-woocommerce' ) ),
					);
				}

				if ( isset( $step_keys[ $current_index + 1 ] ) ) {
					$next_number = $current_index + 2;
					$next_key = $step_keys[ $current_index + 1 ];
					$next_title = $this->steps[ $next_key ]['title'];
					$out['next'] = array(
						'type' => 'button',
						'action_type' => 'step',
						'key' => $next_key,
						'text' => esc_html( __( 'Step', 'product-feed-woocommerce' ) ) . ' ' . $next_number . ': ' . $next_title . ' <span class="dashicons dashicons-arrow-right-alt2" style="line-height:27px;"></span>',
					);

					if ( 'quick' == $this->export_method || 'template' == $this->export_method ) {
						$out['or'] = array(
							'type' => 'text',
							'text' => esc_html( __( 'Or', 'product-feed-woocommerce' ) ),
						);
					}
				} else {
					$last_page = true;
				}

				if ( 'quick' == $this->export_method || 'template' == $this->export_method || $last_page ) {

					// Nonce verification already handled at AJAX entry point (get_steps method)
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$page_button_text = isset( $_REQUEST['rerun_id'] ) ? esc_html( __( 'Update', 'product-feed-woocommerce' ) ) : esc_html( __( 'Generate', 'product-feed-woocommerce' ) );

					$out['export']=array(
						'key'=>'export',
						'class'=>'pf_export_btn',
						'icon'=>'',
						'type'=>'button',
						'text'=> $page_button_text,
					);
				}
			}
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables adding extra arguments or setting defaults for a post
			 * collection request.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $mapping_fields    Post types.
			 * @param array           $this->to_export    Post types.
			 * @param array           $form_data_mapping_fields    Post types.
			 */
			$this->step_btns = apply_filters( 'wt_pf_exporter_alter_footer_btns_basic', $out, $this->step, $this->steps );
		}
	}
}
