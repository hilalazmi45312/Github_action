<?php
/*
 * Layout functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'efw_select2_html' ) ) {

	/**
	 * Return or display Select2 HTML
	 *
	 * @return string
	 */
	function efw_select2_html( $args, $echo = true ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'             => '',
				'id'                => '',
				'name'              => '',
				'list_type'         => '',
				'action'            => '',
				'placeholder'       => '',
				'custom_attributes' => array(),
				'multiple'          => true,
				'allow_clear'       => true,
				'selected'          => true,
				'options'           => array(),
			)
		);

		$multiple = $args['multiple'] ? 'multiple="multiple"' : '';
		$name     = esc_attr( '' !== $args['name'] ? $args['name'] : $args['id'] ) . '[]';
		$options  = array_filter( efw_check_is_array( $args['options'] ) ? $args['options'] : array() );

		$allowed_html = array(
			'select' => array(
				'id'                           => array(),
				'class'                        => array(),
				'data-placeholder'             => array(),
				'data-allow_clear'             => array(),
				'data-exclude-global-variable' => array(),
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
		$custom_attributes = efw_format_custom_attributes( $args );

		ob_start();
		?><select <?php echo esc_attr( $multiple ); ?> 
			name="<?php echo esc_attr( $name ); ?>" 
			id="<?php echo esc_attr( $args['id'] ); ?>" 
			data-action="<?php echo esc_attr( $args['action'] ); ?>" 
						data-exclude-global-variable="<?php echo esc_attr( $args['exclude_global_variable'] ); ?>" 
			class="efw_select2_search <?php echo esc_attr( $args['class'] ); ?>" 
			data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" 
			<?php echo wp_kses( implode( ' ', $custom_attributes ), $allowed_html ); // WPCS: XSS ok. ?>
			<?php echo $args['allow_clear'] ? 'data-allow_clear="true"' : ''; // WPCS: XSS ok. ?> >
				<?php
				if ( is_array( $args['options'] ) ) {
					foreach ( $args['options'] as $option_id ) {
						$option_value = '';
						switch ( $args['list_type'] ) {
							case 'post':
								$option_value = get_the_title( $option_id );
								break;
							case 'products':
								$option_value = get_the_title( $option_id ) . ' (#' . absint( $option_id ) . ')';
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

if ( ! function_exists( 'efw_format_custom_attributes' ) ) {

	/**
	 * Format Custom Attributes
	 *
	 * @return array
	 */
	function efw_format_custom_attributes( $value ) {
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '=' . esc_attr( $attribute_value ) . '';
			}
		}

		return $custom_attributes;
	}
}

if ( ! function_exists( 'efw_get_template' ) ) {

	/**
	 *  Get other templates from themes
	 */
	function efw_get_template( $template_name, $args = array() ) {

		wc_get_template( $template_name, $args, 'efw/', EFW()->templates() );
	}
}

if ( ! function_exists( 'efw_get_template_html' ) ) {

	/**
	 *  Like efw_get_template, but returns the HTML instead of outputting.
	 *
	 *  @return string
	 */
	function efw_get_template_html( $template_name, $args = array() ) {

		ob_start();
		efw_get_template( $template_name, $args );
		return ob_get_clean();
	}
}

if ( ! function_exists( 'efw_relative_date_picker_options' ) ) {

	/**
	 * Relative date picker options.
	 *
	 * @return array
	 */
	function efw_relative_date_picker_options( $type = 'full' ) {

		$options = array(
			'seconds' => esc_html__( 'Second(s)', 'extra-fees-for-woocommerce' ),
			'minutes' => esc_html__( 'Minute(s)', 'extra-fees-for-woocommerce' ),
			'hours'   => esc_html__( 'Hour(s)', 'extra-fees-for-woocommerce' ),
			'days'    => esc_html__( 'Day(s)', 'extra-fees-for-woocommerce' ),
			'weeks'   => esc_html__( 'Week(s)', 'extra-fees-for-woocommerce' ),
			'months'  => esc_html__( 'Month(s)', 'extra-fees-for-woocommerce' ),
		);

		switch ( $type ) {
			case '1':
				unset( $options['seconds'] );
				unset( $options['weeks'] );
				unset( $options['months'] );
				break;

			case '2':
				unset( $options['hours'] );
				unset( $options['days'] );
				unset( $options['weeks'] );
				unset( $options['months'] );
				break;

			case '3':
				unset( $options['seconds'] );
				unset( $options['days'] );
				unset( $options['weeks'] );
				unset( $options['months'] );
				break;
			case '4':
				unset( $options['seconds'] );
				unset( $options['minutes'] );
				break;
			case '5':
				unset( $options['seconds'] );
				unset( $options['minutes'] );
				unset( $options['hours'] );
				break;
		}

		return $options;
	}
}

if ( ! function_exists( 'efw_parse_relative_date_option' ) ) {

	/**
	 * Parse relative date option.
	 *
	 * @return array.
	 */
	function efw_parse_relative_date_option( $raw_value, $option_type ) {

		$options = efw_relative_date_picker_options( $option_type );

		return wp_parse_args(
			(array) $raw_value,
			array(
				'number' => '',
				'unit'   => reset( $options ),
			)
		);
	}
}

if ( ! function_exists( 'efw_get_datepicker_html' ) ) {

	/**
	 * Return or display Datepicker/DateTimepicker HTML.
	 *
	 * @return string
	 * */
	function efw_get_datepicker_html( $args, $echo = true ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'             => '',
				'id'                => '',
				'name'              => '',
				'placeholder'       => '',
				'custom_attributes' => array(),
				'value'             => '',
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

		$class_name = ( $args['with_time'] ) ? 'efw_datetimepicker ' : 'efw_datepicker ';
		$format     = ( $args['with_time'] ) ? 'Y-m-d H:i' : 'date';

		// Custom attribute handling.
		$custom_attributes = efw_format_custom_attributes( $args );
		$value             = ! empty( $args['value'] ) ? EFW_Date_Time::get_wp_format_datetime( $args['value'], $format, $args['wp_zone'] ) : '';
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
			   class="efw_alter_datepicker_value" 
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
