<?php
/**
 * Webtoffee Export Helper Library
 *
 * Includes helper functions for export modules
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro\Helpers\ExportHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Pf_Catalog_Export_Helper' ) ) {
	/**
	 * Wt_Pf_Catalog_Export_Helper Class.
	 */
	class Wt_Pf_Catalog_Export_Helper {
		/**
		 * Get CSV delimiters
		 */
		public static function _get_csv_delimiters() {
			return array(
				'tab' => array(
					'value' => __( 'Tab', 'product-feed-woocommerce' ),
					'val' => "\t",
				),
				'comma' => array(
					'value' => __( 'Comma', 'product-feed-woocommerce' ),
					'val' => ',',
				),
				'semicolon' => array(
					'value' => __( 'Semicolon', 'product-feed-woocommerce' ),
					'val' => ';',
				),
			);
		}
		/**
		 * Get local file path
		 *
		 * @param string $file_url File URL.
		 * @return string|bool
		 */
		public static function _get_local_file_path( $file_url ) {
			$file_path = untrailingslashit( ABSPATH ) . str_replace( site_url(), '', $file_url );

			if ( file_exists( $file_path ) ) {
				return $file_path;
			} else {
							/* Retrying if the directory structure is different from wordpress default file structure */
							$url_parms = explode( '/', $file_url );

							$file_name = end( $url_parms );

							$file_dir_name = prev( $url_parms );

							$file_path = WP_CONTENT_DIR . '/' . $file_dir_name . '/' . $file_name;

				if ( file_exists( $file_path ) ) {
						return $file_path;
				} else {

					return false;
				}
			}
		}
		/**
		 *  Get validation rules for each input fields.
		 *
		 * @param string $step Step.
		 * @param array $form_data Formdata.
		 * @param object $module_obj Module.
		 * @return array
		 */
		public static function get_validation_rules( $step, $form_data, $module_obj ) {
			$method_name = 'get_' . $step . '_screen_fields';
			$out = array();
			if ( method_exists( $module_obj, $method_name ) ) {
				$fields = $module_obj->{$method_name}( $form_data );
				$out = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::extract_validation_rules( $fields );
			}
			$form_data = null;
						$module_obj = null;
			unset( $form_data, $module_obj );
			return $out;
		}
		/**
		 * Sanitize form post data
		 *
		 * @param array $form_data Formdata.
		 * @param object $module_obj Module.
		 * @return array
		 */
		public static function sanitize_formdata( $form_data, $module_obj ) {
			$out = array();
			foreach ( $module_obj->steps as $step => $step_data ) {
				if ( 'mapping' == $step ) {

					/* general mapping fields section */
					if ( isset( $form_data['mapping_form_data'] ) && is_array( $form_data['mapping_form_data'] ) ) {
						$mapping_form_data = $form_data['mapping_form_data'];

						/* mapping fields. This is an internal purpose array */
						if ( isset( $mapping_form_data['mapping_fields'] ) && is_array( $mapping_form_data['mapping_fields'] ) ) {
							foreach ( $mapping_form_data['mapping_fields'] as $key => $value ) {
								$new_key = sanitize_text_field( $key );
							/**
							 * Filter the query arguments for a request.
							 *
							 * Enables adding extra arguments or setting defaults for a post
							 * collection request.
							 *
							* @since 1.0.0
							*
							* @param bool           $allow    Allow HTML.
							* @param string          $key    Input key.
							*/
								if ( apply_filters( 'wt_pf_allow_html_form_data', false, $key ) ) {
									$value = $value;
								} else {
									$value = array( sanitize_text_field( $value[0] ), absint( $value[1] ) );
								}
								unset( $mapping_form_data['mapping_fields'][ $key ] );
								$mapping_form_data['mapping_fields'][ $new_key ] = $value;
							}
						}

						/*mapping enabled meta items */
						if ( isset( $mapping_form_data['mapping_enabled_fields'] ) && is_array( $mapping_form_data['mapping_enabled_fields'] ) ) {
							$mapping_form_data['mapping_enabled_fields'] = Wt_Pf_Sh::sanitize_item( $mapping_form_data['mapping_enabled_fields'], 'text_arr' );
						}

						/* mapping fields. Selected fields only */
						if ( isset( $mapping_form_data['mapping_selected_fields'] ) && is_array( $mapping_form_data['mapping_selected_fields'] ) ) {
							foreach ( $mapping_form_data['mapping_selected_fields'] as $key => $value ) {
								$new_key = sanitize_text_field( $key );
								unset( $mapping_form_data['mapping_selected_fields'][ $key ] );
								/**
								 * Filter the query arguments for a request.
								 *
								 * Enables adding extra arguments or setting defaults for a post
								 * collection request.
								 *
								 * @since 1.0.0
								 *
								 * @param bool           $allow    Allow HTML.
								 * @param string          $key    Input key.
								 */
								if ( apply_filters( 'wt_pf_allow_html_form_data', false, $new_key ) ) {
									$mapping_form_data['mapping_selected_fields'][ $new_key ] = $value;
								} else {
									 $mapping_form_data['mapping_selected_fields'][ $new_key ] = sanitize_text_field( $value );
								}
							}
						}

						$out['mapping_form_data'] = $mapping_form_data;
					}

					/* meta mapping fields section */
					if ( isset( $form_data['meta_step_form_data'] ) && is_array( $form_data['meta_step_form_data'] ) ) {
						$meta_step_form_data = $form_data['meta_step_form_data'];
						/* mapping fields. This is an internal purpose array */
						if ( isset( $meta_step_form_data['mapping_fields'] ) && is_array( $meta_step_form_data['mapping_fields'] ) ) {
							foreach ( $meta_step_form_data['mapping_fields'] as $meta_key => $meta_value ) {
								foreach ( $meta_value as $key => $value ) {
									$new_key = sanitize_text_field( $key );
									$value = array( sanitize_text_field( $value[0] ), absint( $value[1] ) );
									unset( $meta_value[ $key ] );
									$meta_value[ $new_key ] = $value;
								}
								$meta_step_form_data['mapping_fields'][ $meta_key ] = $meta_value;
							}
						}

						/* mapping fields. Selected fields only */
						if ( isset( $meta_step_form_data['mapping_selected_fields'] ) && is_array( $meta_step_form_data['mapping_selected_fields'] ) ) {
							foreach ( $meta_step_form_data['mapping_selected_fields'] as $meta_key => $meta_value ) {
								foreach ( $meta_value as $key => $value ) {
									$new_key = sanitize_text_field( $key );
									unset( $meta_value[ $key ] );
									$meta_value[ $new_key ] = sanitize_text_field( $value );
								}
								$meta_step_form_data['mapping_selected_fields'][ $meta_key ] = $meta_value;
							}
						}

						$out['meta_step_form_data'] = $meta_step_form_data;
					}
				} else {
					$current_form_data_key = $step . '_form_data';
					$current_form_data = ( isset( $form_data[ $current_form_data_key ] ) ? $form_data[ $current_form_data_key ] : array() );
					if ( in_array( $step, $module_obj->step_need_validation_filter ) ) {
						$validation_rule = self::get_validation_rules( $step, $current_form_data, $module_obj );

						foreach ( $current_form_data as $key => $value ) {
							$no_prefix_key = str_replace( 'wt_pf_', '', $key );
							$current_form_data[ $key ] = Wt_Pf_Sh::sanitize_data( $value, $no_prefix_key, $validation_rule );
						}
					} else {
						$validation_rule = ( isset( $module_obj->validation_rule[ $step ] ) ? $module_obj->validation_rule[ $step ] : array() );
						foreach ( $current_form_data as $key => $value ) {
							$current_form_data[ $key ] = Wt_Pf_Sh::sanitize_data( $value, $key, $validation_rule );
						}
					}
					$out[ $current_form_data_key ] = $current_form_data;
				}
			}
			$form_data = null;
						$current_form_data = null;
						$mapping_form_data = null;
						$meta_step_form_data = null;
						$module_obj = null;
			unset( $form_data, $current_form_data, $mapping_form_data, $meta_step_form_data, $module_obj );
			return $out;
		}
		/**
		 * Debug panel
		 *
		 * @param string $module_base Module.
		 */
		public static function debug_panel( $module_base ) {
			if ( 'export' == $module_base ) {
				$debug_panel_btns = array(
					'refresh_step' => array(
						'title' => __( 'Refresh the step', 'product-feed-woocommerce' ),
						'icon' => 'dashicons dashicons-update',
						'onclick' => 'wt_pf_basic_' . $module_base . '.refresh_step();',
					),
					'console_form_data' => array(
						'title' => __( 'Console form data', 'product-feed-woocommerce' ),
						'icon' => 'dashicons dashicons-code-standards',
						'onclick' => 'wt_pf_basic_' . $module_base . '.console_formdata();',
					),
				);
			}
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables adding extra arguments or setting defaults for a post
			 * collection request.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $debug_panel_btns    Debug panel buttons.
			 * @param object          $module_base    Module.
			 */
			$debug_panel_btns = apply_filters( 'wt_pf_debug_panel_buttons_basic', $debug_panel_btns, $module_base );
			if ( defined( 'WT_PF_DEBUG_PRO' ) && WT_PF_DEBUG_PRO && is_array( $debug_panel_btns ) && count( $debug_panel_btns ) > 0 ) {
				?>
				<div class="wt_pf_debug_panel" title="<?php esc_html_e( 'For debugging process', 'product-feed-woocommerce' ); ?>">
					<div class="wt_pf_debug_panel_hd"><?php esc_html_e( 'Debug panel', 'product-feed-woocommerce' ); ?></div>
					<div class="wt_pf_debug_panel_con">
						<?php
						foreach ( $debug_panel_btns as $btn ) {
							?>
							<a onclick="<?php echo wp_kses_post( $btn['onclick'] ); ?>" title="<?php echo wp_kses_post( $btn['title'] ); ?>">
								<span class="<?php echo wp_kses_post( $btn['icon'] ); ?>"></span>
							</a>
							<?php
						}
						?>
					</div>
				</div>
				<?php
			}
		}



		/**
		 * Compress HTML
		 *
		 * @param string $html HTML to minify.
		 */
		public static function sanitize_and_minify_html( $html ) {

			$search = array(
				'/\>[^\S ]+/s', // strip whitespaces after tags, except space.
				'/[^\S ]+\</s', // strip whitespaces before tags, except space.
				'/(\s)+/s', // shorten multiple whitespace sequences.
				'/<!--(.|\s)*?-->/', // Remove HTML comments.
			);

			$replace = array(
				'>',
				'<',
				'\\1',
				'',
			);

			$html = preg_replace( $search, $replace, $html );

			return $html;
		}

		/**
		 * Get the feed URL
		 *
		 * @param string $url The generated feed URL.
		 * @return string
		 */
		public static function feed_get_formatted_url( $url = '' ) {
			if ( ! empty( $url ) ) {
				if ( substr( trim( $url ), 0, 4 ) === 'http' || substr(
					trim( $url ),
					0,
					3
				) === 'ftp' || substr( trim( $url ), 0, 4 ) === 'sftp' ) {
					return rtrim( $url, '/' );
				} else {
					$base = get_site_url();
					$url  = $base . $url;

					return rtrim( $url, '/' );
				}
			}

			return '';
		}
	}

}
