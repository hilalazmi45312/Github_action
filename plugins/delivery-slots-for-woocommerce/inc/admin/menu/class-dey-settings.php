<?php
/**
 * Admin Settings Class.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Settings' ) ) {

	/**
	 * DEY_Settings Class
	 */
	class DEY_Settings {

		/**
		 * Setting pages.
		 *
		 * @var array
		 */
		private static $settings = array();

		/**
		 * Errors.
		 *
		 * @var array
		 */
		private static $errors = array();

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		private static $plugin_slug = 'dey';

		/**
		 * Messages.
		 *
		 * @var array
		 */
		private static $messages = array();

		/**
		 * Include the settings page classes.
		 */
		public static function get_settings_pages() {
			if ( ! empty( self::$settings ) ) {
				return self::$settings;
			}

			include_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-settings-page.php';

			$settings = array();
			$tabs     = self::settings_page_tabs();

			foreach ( $tabs as $tab_name ) {
				$settings[ str_replace( '-', '_', $tab_name ) ] = include 'tabs/' . sanitize_key( $tab_name ) . '.php';
			}
			/**
			 * This hook is used to alter the settings pages.
			 *
			 * @since 1.0
			 */
			self::$settings = apply_filters( sanitize_key( self::$plugin_slug . '_get_settings_pages' ), $settings );

			return self::$settings;
		}

		/**
		 * Add a message.
		 */
		public static function add_message( $text ) {
			self::$messages[] = $text;
		}

		/**
		 * Add an error.
		 */
		public static function add_error( $text ) {
			self::$errors[] = $text;
		}

		/**
		 * Output messages + errors.
		 */
		public static function show_messages() {
			if ( count( self::$errors ) > 0 ) {
				foreach ( self::$errors as $error ) {
					self::error_message( $error );
				}
			} elseif ( count( self::$messages ) > 0 ) {
				foreach ( self::$messages as $message ) {
					self::success_message( $message );
				}
			}
		}

		/**
		 * Show an success message.
		 */
		public static function success_message( $text, $echo = true ) {
			ob_start();
			$contents = '<div id="message " class="updated inline ' . esc_html( self::$plugin_slug ) . '_save_msg"><p><strong>' . esc_html( $text ) . '</strong></p></div>';
			ob_end_clean();

			if ( $echo ) {
				echo wp_kses_post( $contents );
			} else {
				return $contents;
			}
		}

		/**
		 * Show an error message.
		 */
		public static function error_message( $text, $echo = true ) {
			ob_start();
			$contents = '<div id="message" class="error inline"><p><strong>' . esc_html( $text ) . '</strong></p></div>';
			ob_end_clean();

			if ( $echo ) {
				echo wp_kses_post( $contents );
			} else {
				return $contents;
			}
		}

		/**
		 * Settings page tabs.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function settings_page_tabs() {

			return array(
				'order-delivery',
				'local-pickup',
				'product-delivery',
				'product-local-pickup',
				'order-tip',
				'advanced',
				'notifications',
				'shortcodes',
			);
		}

		/**
		 * Handles the display of the settings page in admin.
		 */
		public static function output() {
			global $current_section, $current_tab;

			$tabs = dey_get_allowed_setting_tabs();

			/* Include admin html settings */
			include_once 'views/html-settings.php';
		}

		/**
		 * Handles the display of the settings page buttons in page.
		 */
		public static function output_buttons( $reset = true ) {

			/* Include admin html settings buttons */
			include_once 'views/html-settings-buttons.php';
		}

		/**
		 * Output admin fields.
		 */
		public static function output_fields( $value ) {

			if ( ! isset( $value['type'] ) || 'dey_custom_fields' != $value['type'] ) {
				return;
			}

			$value['id']                = isset( $value['id'] ) ? $value['id'] : '';
			$value['css']               = isset( $value['css'] ) ? $value['css'] : '';
			$value['desc']              = isset( $value['desc'] ) ? $value['desc'] : '';
			$value['title']             = isset( $value['title'] ) ? $value['title'] : '';
			$value['class']             = isset( $value['class'] ) ? $value['class'] : '';
			$value['default']           = isset( $value['default'] ) ? $value['default'] : '';
			$value['name']              = isset( $value['name'] ) ? $value['name'] : $value['id'];
			$value['placeholder']       = isset( $value['placeholder'] ) ? $value['placeholder'] : '';
			$value['without_label']     = isset( $value['without_label'] ) ? $value['without_label'] : false;
			$value['custom_attributes'] = isset( $value['custom_attributes'] ) ? $value['custom_attributes'] : '';

			// Custom attribute handling.
			$custom_attributes = dey_format_custom_attributes( $value );

			// Description handling.
			$field_description = WC_Admin_Settings::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];

			// Switch based on type.
			switch ( $value['dey_field'] ) {

				case 'wpeditor':
					$option_value = get_option( $value['id'], $value['default'] );
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label><?php echo wp_kses_post( $tooltip_html ); ?>
						</th>
						<td>
							<?php
							wp_editor(
								$option_value,
								$value['id'],
								array(
									'media_buttons' => false,
									'editor_class'  => esc_attr( $value['class'] ),
								)
							);

							echo wp_kses_post( $description );
							?>
						</td>
					</tr>
					<?php
					break;

				// timepicker.
				case 'timepicker':
					$value['value'] = get_option( $value['id'], $value['default'] );
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">

							<?php
							dey_get_datepicker_html( $value );
							echo wp_kses_post( $description ); // WPCS: XSS ok.
							?>
						</td>
					</tr>
					<?php
					break;

				// Weekday price.
				case 'weekday_prices':
					$option_value = array_filter( (array) get_option( $value['id'], $value['default'] ) );
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); // WPCS: XSS ok. ?></label>
						</th>
						<td>
							<?php
							foreach ( dey_weekdays() as $key => $label ) :
								$price = isset( $option_value[ $key ] ) ? wc_format_localized_price( $option_value[ $key ] ) : '';
								$name  = $value['name'] . '[' . $key . ']';
								?>
								<span class="dey-weekdays-prices">
									<label><?php echo esc_html( $label ); ?></label>
									<input type="text" name="<?php echo esc_attr( $name ); ?>" class="wc_input_price <?php echo esc_attr( $value['class'] ); ?>" value="<?php echo esc_attr( $price ); ?>"/>
								</span>
							<?php endforeach; ?>
						</td>
					</tr>
					<?php
					break;

				// Weekday price.
				case 'business_day_prices':
					$option_value = get_option( $value['id'], $value['default'] );
					$option_value = dey_parse_business_day_option( $option_value, dey_business_day_default_option() );
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); // WPCS: XSS ok. ?></label>
						</th>
						<td>
							<table class="dey-business-days">
							<?php
							foreach ( dey_weekdays() as $key => $label ) :
								$name = $value['name'] . '[' . $key . ']';
								?>
								<tr>
									<td class="dey-business-day-enable">
										<label>
											<input type="checkbox" name="<?php echo esc_attr( $name . '[enable]' ); ?>" <?php echo checked( 'yes', $option_value[ $key ]['enable'] ); ?>/>
											<?php echo esc_html( $label ); ?>
										</label>
									</td>
									<td class="dey-business-day-opening-time">
										<label><?php esc_html_e( 'Opening Time', 'delivery-slots-for-woocommerce' ); ?></label>
										<?php
										dey_get_datepicker_html(
											array(
												'id'      => $value['id'],
												'name'    => $name . '[opening_time]',
												'time_only' => true,
												'wp_zone' => true,
												'placeholder' => '00:00 am',
												'value'   => $option_value[ $key ]['opening_time'],
											)
										);
										?>
									</td>
									<td class="dey-business-day-closing-time">
										<label><?php esc_html_e( 'Closing Time', 'delivery-slots-for-woocommerce' ); ?></label>
										<?php
										dey_get_datepicker_html(
											array(
												'id'      => $value['id'],
												'name'    => $name . '[closing_time]',
												'time_only' => true,
												'wp_zone' => true,
												'placeholder' => '23:59 pm',
												'value'   => $option_value[ $key ]['closing_time'],
											)
										);
										?>
									</td>
								</tr>
							<?php endforeach; ?>
							</table>
						</td>
					</tr>
					<?php
					break;

				// Days/months/years selector.
				case 'relative_date_selector':
					$option_value = get_option( $value['id'], $value['default'] );
					$periods      = dey_relative_date_picker_options( $value['option_type'] );
					$option_value = dey_parse_relative_date_option( $option_value, $value['option_type'] );
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>[number]"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="number"
								value="<?php echo esc_attr( $option_value['number'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								step="1"
								min="1"
								<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ); // WPCS: XSS ok. ?>
								/>&nbsp;
							<select name="<?php echo esc_attr( $value['id'] ); ?>[unit]">
								<?php
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value['unit'], $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select> <?php echo wp_kses_post( $description ); // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;
			}
		}

		/**
		 * Save the setting fields.
		 *
		 * @return mixed
		 */
		public static function prepare_field_value( $value, $option, $raw_value ) {

			if ( ! isset( $option['type'] ) || 'dey_custom_fields' != $option['type'] ) {
				return $value;
			}

			$value = null;

			// Format the value based on option type.
			switch ( $option['dey_field'] ) {
				case 'business_day_prices':
					$post_values = array();
					foreach ( $raw_value as $business_day_key => $business_day ) {
						$business_day['enable']           = isset( $business_day['enable'] ) ? 'yes' : 'no';
						$post_values[ $business_day_key ] = $business_day;
					}
					$value = array_filter( $post_values );
					break;
				case 'weekday_prices':
				case 'relative_date_selector':
					$value = array_filter( (array) $raw_value );
					break;
				case 'timepicker':
					$value = wc_clean( wp_unslash( $raw_value ) );
					break;
				case 'wpeditor':
					$value = $raw_value;
					break;
			}

			return $value;
		}

		/**
		 * Reset the setting fields.
		 *
		 * @return bool
		 */
		public static function reset_fields( $options ) {
			if ( ! is_array( $options ) ) {
				return false;
			}

			// Loop options and get values to reset.
			foreach ( $options as $option ) {
				if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) || ! isset( $option['default'] ) ) {
					continue;
				}

				update_option( $option['id'], $option['default'] );
			}

			return true;
		}
	}

}
