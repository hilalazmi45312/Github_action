<?php
/**
 * Layout functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'dey_select2_html' ) ) {

	/**
	 * Return or display Select2 HTML.
	 *
	 * @return mixed
	 */
	function dey_select2_html( $args, $echo = true ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'                   => '',
				'id'                      => '',
				'name'                    => '',
				'list_type'               => '',
				'action'                  => '',
				'placeholder'             => '',
				'exclude_global_variable' => 'no',
				'custom_attributes'       => array(),
				'multiple'                => true,
				'allow_clear'             => true,
				'selected'                => true,
				'options'                 => array(),
			)
		);

		$multiple = $args['multiple'] ? 'multiple="multiple"' : '';
		$name     = esc_attr( '' !== $args['name'] ? $args['name'] : $args['id'] ) . '[]';
		$options  = array_filter( dey_check_is_array( $args['options'] ) ? $args['options'] : array() );

		$allowed_html = array(
			'select' => array(
				'id'                           => array(),
				'class'                        => array(),
				'data-placeholder'             => array(),
				'data-allow_clear'             => array(),
				'data-exclude-global-variable' => array(),
				'data-nonce'                   => array(),
				'data-action'                  => array(),
				'multiple'                     => array(),
				'name'                         => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
		);

		// Custom attribute handling.
		$custom_attributes = dey_format_custom_attributes( $args );
		$data_nonce        = ( 'products' == $args['list_type'] ) ? 'data-nonce="' . wp_create_nonce( 'search-products' ) . '"' : '';

		ob_start();
		?><select <?php echo esc_attr( $multiple ); ?> 
			name="<?php echo esc_attr( $name ); ?>" 
			id="<?php echo esc_attr( $args['id'] ); ?>" 
			data-action="<?php echo esc_attr( $args['action'] ); ?>" 
			data-exclude-global-variable="<?php echo esc_attr( $args['exclude_global_variable'] ); ?>" 
			class="dey_select2_search <?php echo esc_attr( $args['class'] ); ?>" 
			data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" 
			<?php echo wp_kses( implode( ' ', $custom_attributes ), $allowed_html ); ?>
			<?php echo wp_kses( $data_nonce, $allowed_html ); ?>
			<?php echo $args['allow_clear'] ? 'data-allow_clear="true"' : ''; ?> >
				<?php
				if ( is_array( $args['options'] ) ) {
					foreach ( $args['options'] as $option_id ) {
						$option_value = '';
						switch ( $args['list_type'] ) {
							case 'post':
								$option_value = get_the_title( $option_id );
								break;
							case 'products':
								$product = wc_get_product( $option_id );
								if ( $product ) {
									$option_value = $product->get_name() . ' (#' . absint( $option_id ) . ')';
								}
								break;
							case 'customers':
								$user = get_user_by( 'id', $option_id );
								if ( $user ) {
									$option_value = $user->display_name . '(#' . absint( $user->ID ) . ' &ndash; ' . $user->user_email . ')';
								}
								break;
						}

						if ( $option_value ) {
							?>
						<option value="<?php echo esc_attr( $option_id ); ?>" <?php echo $args['selected'] ? 'selected="selected"' : ''; // WPCS: XSS ok. ?>><?php echo esc_html( $option_value ); ?></option>
							<?php
						}
					}
				}
				?>
		</select>
		<?php
		$html = ob_get_clean();

		if ( $echo ) {
			echo wp_kses( $html, $allowed_html );
		}

		return $html;
	}

}

if ( ! function_exists( 'dey_format_custom_attributes' ) ) {

	/**
	 * Format Custom Attributes.
	 *
	 * @return array
	 */
	function dey_format_custom_attributes( $value ) {
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '=' . esc_attr( $attribute_value ) . '';
			}
		}

		return $custom_attributes;
	}

}

if ( ! function_exists( 'dey_get_datepicker_html' ) ) {

	/**
	 * Return or display Datepicker/DateTimepicker HTML.
	 *
	 * @return mixed
	 * */
	function dey_get_datepicker_html( $args, $echo = true ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'             => '',
				'id'                => '',
				'name'              => '',
				'placeholder'       => '',
				'custom_attributes' => array(),
				'value'             => '',
				'time_only'         => false,
				'wp_zone'           => true,
				'with_time'         => false,
				'error'             => '',
			)
		);

		$name = ( '' !== $args['name'] ) ? $args['name'] : $args['id'];

		$allowed_html = array(
			'input' => array(
				'id'          => array(),
				'type'        => array(),
				'placeholder' => array(),
				'class'       => array(),
				'value'       => array(),
				'name'        => array(),
				'min'         => array(),
				'max'         => array(),
				'data-error'  => array(),
				'style'       => array(),
			),
		);

		if ( $args['time_only'] ) {
			$format           = 'time';
			$alter_class_name = 'dey_alter_timepicker_value';
			$class_name       = 'dey_timepicker ';
		} else {
			$alter_class_name = 'dey_alter_datepicker_value';
			$class_name       = ( $args['with_time'] ) ? 'dey_datetimepicker ' : 'dey_datepicker ';
			$format           = ( $args['with_time'] ) ? 'Y-m-d H:i' : 'date';
		}

		// Custom attribute handling.
		$custom_attributes = dey_format_custom_attributes( $args );
		$value             = ! empty( $args['value'] ) ? DEY_Date_Time::get_wp_format_datetime( $args['value'], $format, $args['wp_zone'] ) : '';
		ob_start();
		?>
		<input type = "text" 
				id="<?php echo esc_attr( $args['id'] ); ?>"
				value = "<?php echo esc_attr( $value ); ?>"
				class="<?php echo esc_attr( $class_name . $args['class'] ); ?>" 
				placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" 
				data-error="<?php echo esc_attr( $args['error'] ); ?>" 
				<?php echo wp_kses( implode( ' ', $custom_attributes ), $allowed_html ); ?>
				/>

		<input type = "hidden" 
				class="<?php echo esc_attr( $alter_class_name ); ?>" 
				name="<?php echo esc_attr( $name ); ?>"
				value = "<?php echo esc_attr( $args['value'] ); ?>"
				/> 
		<?php
		$html = ob_get_clean();

		if ( $echo ) {
			echo wp_kses( $html, $allowed_html );
		}

		return $html;
	}

}

if ( ! function_exists( 'dey_localize_jquery_ui_timepicker_addon' ) ) {

	/**
	 * Add the Inline script of the timepicker addon.
	 *
	 * @return mixed
	 * */
	function dey_localize_jquery_ui_timepicker_addon() {
		if ( ! wp_script_is( 'jquery-ui-timpicker-addon', 'enqueued' ) ) {
			return;
		}

		$timepicker_defaults = wp_json_encode(
			array(
				'timeOnlyTitle' => __( 'Choose Time', 'delivery-slots-for-woocommerce' ),
				'timeText'      => __( 'Time', 'delivery-slots-for-woocommerce' ),
				'hourText'      => __( 'Hour', 'delivery-slots-for-woocommerce' ),
				'minuteText'    => __( 'Minute', 'delivery-slots-for-woocommerce' ),
				'secondText'    => __( 'Second', 'delivery-slots-for-woocommerce' ),
				'currentText'   => __( 'Now', 'delivery-slots-for-woocommerce' ),
				'closeText'     => __( 'Done', 'delivery-slots-for-woocommerce' ),
				'isRTL'         => is_rtl(),
				'altTimeFormat' => 'HH:mm',
			)
		);

		wp_add_inline_script( 'jquery-ui-timpicker-addon', "jQuery(document).ready(function(jQuery){jQuery.timepicker.setDefaults({$timepicker_defaults});});" );
	}

}

if ( ! function_exists( 'dey_convert_wp_date_format_php_to_jquery' ) ) {

	/**
	 * Convert the WP date format PHP to the jQuery.
	 *
	 * @return string
	 * */
	function dey_convert_wp_date_format_php_to_jquery() {
		$wp_date_format = get_option( 'date_format' );

		$format_list = array(
			'd' => 'dd',
			'j' => 'd',
			'l' => 'DD',
			'z' => 'o', // Day.
			'F' => 'MM',
			'M' => 'M',
			'n' => 'm',
			'm' => 'mm', // Month.
			'Y' => 'yy',
			'y' => 'y', // Year.
		);

		$jqueryui_format = '';
		$escaping        = false;
		for ( $i = 0; $i < strlen( $wp_date_format ); $i++ ) {
			$char = $wp_date_format[ $i ];

			if ( '\\' === $char ) { // PHP date format escaping character
				++$i;

				if ( $escaping ) {
					$jqueryui_format .= $wp_date_format[ $i ];
				} else {
					$jqueryui_format .= "'" . $wp_date_format[ $i ];
				}

				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping         = false;
				}

				if ( isset( $format_list[ $char ] ) ) {
					$jqueryui_format .= $format_list[ $char ];
				} else {
					$jqueryui_format .= $char;
				}
			}
		}

		return $jqueryui_format;
	}

}

if ( ! function_exists( 'dey_localize_jquery_ui_datepicker' ) ) {

	/**
	 * Add the Inline script of the datepicker.
	 *
	 * @return mixed
	 * */
	function dey_localize_jquery_ui_datepicker() {
		global $wp_locale;

		if ( ! wp_script_is( 'jquery-ui-datepicker', 'enqueued' ) ) {
			return;
		}
		$datepicker_defaults = wp_json_encode(
			array(
				'closeText'       => __( 'Close' ),
				'currentText'     => __( 'Today' ),
				'monthNames'      => array_values( $wp_locale->month ),
				'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
				'nextText'        => __( 'Next' ),
				'prevText'        => __( 'Previous' ),
				'dayNames'        => array_values( $wp_locale->weekday ),
				'dayNamesShort'   => array_values( $wp_locale->weekday_abbrev ),
				'dayNamesMin'     => array_values( $wp_locale->weekday_initial ),
				'dateFormat'      => dey_convert_wp_date_format_php_to_jquery(),
				'firstDay'        => absint( get_option( 'start_of_week' ) ),
				'isRTL'           => $wp_locale->is_rtl(),
			)
		);

		wp_add_inline_script( 'jquery-ui-datepicker', "jQuery(function(jQuery){jQuery.datepicker.setDefaults({$datepicker_defaults});});" );
	}
}

