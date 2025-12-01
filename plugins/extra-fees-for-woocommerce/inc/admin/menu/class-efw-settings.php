<?php
/**
 * Admin Settings Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Settings' ) ) {

	/**
	 * EFW_Settings Class
	 */
	class EFW_Settings {

		/**
		 * Setting pages.
		 */
		private static $settings = array() ;

		/**
		 * Error messages.
		 */
		private static $errors = array() ;

		/**
		 * Plugin slug.
		 */
		private static $plugin_slug = 'efw' ;

		/**
		 * Update messages.
		 */
		private static $messages = array() ;

		/**
		 * Include the settings page classes.
		 */
		public static function get_settings_pages() {
			if ( ! empty( self::$settings ) ) {
				return self::$settings ;
			}

			include_once EFW_PLUGIN_PATH . '/inc/abstracts/class-efw-settings-page.php' ;

			$settings = array() ;
			$tabs     = self::settings_page_tabs() ;

			foreach ( $tabs as $tab_name ) {
				$settings[ sanitize_key( $tab_name ) ] = include 'tabs/' . sanitize_key( $tab_name ) . '.php' ;
			}
						/**
						 * Filter settings pages. 
						 *
						 * @since 1.0
						 */
			self::$settings = apply_filters( sanitize_key( self::$plugin_slug . '_get_settings_pages' ) , $settings ) ;

			return self::$settings ;
		}

		/**
		 * Add a message.
		 */
		public static function add_message( $text ) {
			self::$messages[] = $text ;
		}

		/**
		 * Add an error.
		 */
		public static function add_error( $text ) {
			self::$errors[] = $text ;
		}

		/**
		 * Output messages + errors.
		 */
		public static function show_messages() {
			if ( count( self::$errors ) > 0 ) {
				echo '<div id="message" class="error inline">' ;
				foreach ( self::$errors as $error ) {
					self::error_message( $error ) ;
				}
				echo '</div>' ;
			} elseif ( count( self::$messages ) > 0 ) {
				echo '<div id="message" class="updated inline ' . esc_html( self::$plugin_slug ) . '_save_msg">' ;
				foreach ( self::$messages as $message ) {
					self::success_message( $message ) ;
				}
				echo '</div>' ;
			}
		}

		/**
		 * Show an success message.
		 */
		public static function success_message( $text, $echo = true ) {
			ob_start() ;
			$contents = '<p><strong>' . esc_html( $text ) . '</strong></p>' ;
			ob_end_clean() ;

			if ( $echo ) {
				$allowed_html = array(
					'div'    => array(
						'class' => array(),
					),
					'p'      => array(),
					'i'      => array(
						'class'       => array(),
						'aria-hidden' => array(),
					),
					'strong' => array(),
						) ;

				echo wp_kses( $contents , $allowed_html ) ;
			} else {
				return $contents ;
			}
		}

		/**
		 * Show an error message.
		 */
		public static function error_message( $text, $echo = true ) {
			ob_start() ;
			$contents = '<p><strong><i class="fa fa-exclamation-triangle"></i> ' . esc_html( $text ) . '</strong></p>' ;
			ob_end_clean() ;

			if ( $echo ) {
				$allowed_html = array(
					'div'    => array(
						'class' => array(),
					),
					'p'      => array(),
					'i'      => array(
						'class'       => array(),
						'aria-hidden' => array(),
					),
					'strong' => array(),
						) ;

				echo wp_kses( $contents , $allowed_html ) ;
			} else {
				return $contents ;
			}
		}

		/**
		 * Settings page tabs
		 */
		public static function settings_page_tabs() {

			return array(
				'productfee',
				'gatewayfee',
				'ordertotalfee',
				'shippingfee',
				'advance',
				'reports',
					) ;
		}

		/**
		 * Handles the display of the settings page in admin.
		 */
		public static function output() {
			global $current_section, $current_tab ;
						/**
			 * Trigger settings start. 
			 *
			 * @since 1.0
			 */
			do_action( sanitize_key( self::$plugin_slug . '_settings_start' ) ) ;

			$tabs = efw_get_allowed_setting_tabs() ;

			/* Include admin html settings */
			include_once 'views/html-settings.php' ;
		}

		/**
		 * Handles the display of the settings page buttons in page.
		 */
		public static function output_buttons( $reset = true ) {

			/* Include admin html settings buttons */
			include_once 'views/html-settings-buttons.php' ;
		}

		/**
		 * Output admin fields.
		 */
		public static function output_fields( $value ) {

			if ( ! isset( $value[ 'type' ] ) || 'efw_custom_fields' != $value[ 'type' ] ) {
				return ;
			}

			$value[ 'id' ]                = isset( $value[ 'id' ] ) ? $value[ 'id' ] : '' ;
			$value[ 'css' ]               = isset( $value[ 'css' ] ) ? $value[ 'css' ] : '' ;
			$value[ 'desc' ]              = isset( $value[ 'desc' ] ) ? $value[ 'desc' ] : '' ;
			$value[ 'title' ]             = isset( $value[ 'title' ] ) ? $value[ 'title' ] : '' ;
			$value[ 'class' ]             = isset( $value[ 'class' ] ) ? $value[ 'class' ] : '' ;
			$value[ 'default' ]           = isset( $value[ 'default' ] ) ? $value[ 'default' ] : '' ;
			$value[ 'name' ]              = isset( $value[ 'name' ] ) ? $value[ 'name' ] : $value[ 'id' ] ;
			$value[ 'placeholder' ]       = isset( $value[ 'placeholder' ] ) ? $value[ 'placeholder' ] : '' ;
			$value[ 'without_label' ]     = isset( $value[ 'without_label' ] ) ? $value[ 'without_label' ] : false ;
			$value[ 'custom_attributes' ] = isset( $value[ 'custom_attributes' ] ) ? $value[ 'custom_attributes' ] : '' ;

			// Custom attribute handling.
			$custom_attributes = efw_format_custom_attributes( $value ) ;
			$allowed_html      = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'style'  => array(),
				'min'    => array(),
				'max'    => array(),
				'span'   => array( 'class' => array(), 'data-tip' => array() ),
					) ;
			// Description handling.
			$field_description = WC_Admin_Settings::get_field_description( $value ) ;
			$description       = $field_description[ 'description' ] ;
			$tooltip_html      = $field_description[ 'tooltip_html' ] ;

			// Switch based on type
			switch ( $value[ 'efw_field' ] ) {

				case 'subtitle':
					?>
					<tr valign="top" >
						<th scope="row" colspan="2">
							<?php echo esc_html( $value[ 'title' ] ) ; ?><?php echo wp_kses( $tooltip_html , $allowed_html ) ; ?>
							<p><?php echo wp_kses( $description , $allowed_html ) ; ?></p>
						</th>
					</tr>
					<?php
					break ;

				case 'ajaxmultiselect':
					$option_value       = get_option( $value[ 'id' ] , $value[ 'default' ] ) ;
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"><?php echo esc_html( $value[ 'title' ] ) ; ?></label><?php echo wp_kses( $tooltip_html , $allowed_html ) ; ?>
						</th>
						<td>
							<?php
							$value[ 'options' ] = $option_value ;
							efw_select2_html( $value ) ;
							echo wp_kses( $description , $allowed_html ) ;
							?>
						</td>
					</tr>
					<?php
					break ;

				case 'wpeditor':
					$option_value = get_option( $value[ 'id' ] , $value[ 'default' ] ) ;
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"><?php echo esc_html( $value[ 'title' ] ) ; ?></label><?php echo wp_kses( $tooltip_html , $allowed_html ) ; ?>
						</th>
						<td>
							<?php
							wp_editor(
									$option_value , $value[ 'id' ] , array(
								'media_buttons' => false,
								'editor_class'  => esc_attr( $value[ 'class' ] ),
									)
							) ;

							echo wp_kses( $description , $allowed_html ) ;
							?>
						</td>
					</tr>
					<?php
					break ;

				// Days/months/years selector.
				case 'relative_date_selector':
					$option_value = get_option( $value[ 'id' ] , $value[ 'default' ] ) ;
					$periods      = efw_relative_date_picker_options( $value[ 'option_type' ] ) ;
					$option_value = efw_parse_relative_date_option( $option_value , $value[ 'option_type' ] ) ;
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"><?php echo esc_html( $value[ 'title' ] ) ; ?> <?php echo wp_kses_post( $tooltip_html ) ; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">
							<input
								name="<?php echo esc_attr( $value[ 'id' ] ) ; ?>[number]"
								id="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
								type="number"
								value="<?php echo esc_attr( $option_value[ 'number' ] ) ; ?>"
								class="<?php echo esc_attr( $value[ 'class' ] ) ; ?>"
								placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ) ; ?>"
								step="1"
								min="1"
								<?php echo wp_kses( implode( ' ' , $custom_attributes ) , $allowed_html ) ; // WPCS: XSS ok. ?>
								/>&nbsp;
							<select name="<?php echo esc_attr( $value[ 'id' ] ) ; ?>[unit]">
								<?php
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value[ 'unit' ] , $value , false ) . '>' . esc_html( $label ) . '</option>' ;
								}
								?>
							</select> <?php echo wp_kses( $description , $allowed_html ) ; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break ;
										
				case 'button':
					?>
					<tr valign="top" >
						<th scope="row">
							<label for="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"><?php echo esc_html( $value[ 'title' ] ) ; ?></label><?php echo wp_kses( $tooltip_html , $allowed_html ) ; ?>
						</th>
						<td>
							<input type="submit" 
									name="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
									id="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
									class="button-primary <?php echo esc_attr( $value[ 'class' ] ) ; ?>"
									value="<?php echo esc_attr( $value[ 'title' ] ) ; ?>" />
						</td>
					</tr>
					<?php
					break ;
				case 'file':
					?>
					<tr valign="top" >
						<th scope="row">
							<label for="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"><?php echo esc_html( $value[ 'title' ] ) ; ?></label><?php echo wp_kses( $tooltip_html , $allowed_html ) ; ?>
						</th>
						<td>
							<input type="file" 
									name="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
									id="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
									class="<?php echo esc_attr( $value[ 'class' ] ) ; ?>"/>
							<input type="submit" 
									name="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
									id="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
									class="button-primary <?php echo esc_attr( $value[ 'class' ] ) ; ?>"
									value="<?php echo esc_attr( $value[ 'button_title' ] ) ; ?>" />
							<?php echo wp_kses( $description , $allowed_html ) ; // WPCS: XSS ok. ?>
						</td>
						<?php wp_nonce_field( 'efw_import_settings', '_efw_import_nonce', false, true ); ?>
					</tr>
					<?php
					break ;
			}
		}

		/**
		 * Save admin fields.
		 */
		public static function save_fields( $value, $option, $raw_value ) {

			if ( ! isset( $option[ 'type' ] ) || 'efw_custom_fields' != $option[ 'type' ] ) {
				return $value ;
			}

			// Format the value based on option type.
			switch ( $option[ 'efw_field' ] ) {
				case 'ajaxmultiselect':
					$value = array_filter( ( array ) $raw_value ) ;
					break ;
				case 'relative_date_selector':
					$value = efw_parse_relative_date_option( $raw_value , $option[ 'option_type' ] ) ;
					break ;
				case 'wpeditor':
					$value = wc_clean( $raw_value ) ;
					break ;
			}

			return $value ;
		}

		/**
		 * Reset admin fields.
		 */
		public static function reset_fields( $options ) {
			if ( ! is_array( $options ) ) {
				return false ;
			}

			// Loop options and get values to reset.
			foreach ( $options as $option ) {
				if ( ! isset( $option[ 'id' ] ) || ! isset( $option[ 'type' ] ) || ! isset( $option[ 'default' ] ) ) {
					continue ;
				}

				update_option( $option[ 'id' ] , $option[ 'default' ] ) ;
			}
			return true ;
		}
	}

}
