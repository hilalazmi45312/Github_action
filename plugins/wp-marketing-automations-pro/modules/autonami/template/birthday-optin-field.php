<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly
if ( ! class_exists( 'WFFN_Optin_Form_Field_Birthday' ) ) {
	/**
	 * This class will control all Optin Birthday mapping functionality on optin submission.
	 * Class WFFN_Optin_Form_Field_Birthday
	 */
	class WFFN_Optin_Form_Field_Birthday extends WFFN_Optin_Form_Field {

		private static $ins = null;
		public static $slug = 'optin_birthday';
		public $index = 30;

		/**
		 * WFFN_Optin_Form_Field_Birthday constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @return WFFN_Optin_Form_Field_Birthday|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self;
			}

			return self::$ins;
		}

		/**
		 * @return string
		 */
		public static function get_slug() {
			return self::$slug;
		}

		/**
		 * Return title of this form field
		 */
		public function get_title() {
			return __( 'Birthday', 'wp-marketing-automations-pro' );
		}

		/**
		 * @param $field_data
		 *
		 * @return string|void
		 */
		public function get_field_output( $field_data ) {
			$field_data = wp_parse_args( $field_data, $this->get_field_format() );

			$global_settings = BWFAN_Common::get_global_settings();

			$name     = $this->get_prefix() . $this::get_slug();
			$width    = isset( $field_data['width'] ) ? esc_attr( $field_data['width'] ) : '';
			$label    = isset( $field_data['label'] ) ? esc_attr( $field_data['label'] ) : '';
			$required = isset( $field_data['required'] ) ? esc_attr( $field_data['required'] ) : false;
			$hash     = isset( $field_data['hash_key'] ) ? esc_attr( $field_data['hash_key'] ) : '';
			$value    = $this->get_default_value( $field_data );
			$class    = $this->get_input_class( $field_data );

			if ( ! empty( $global_settings['bwfan_hide_dob_field'] ) && ! empty( $value ) ) {
				return;
			}

			// Parse date for the dropdowns if we have a value
			$selected_date  = '';
			$selected_month = '';
			$selected_year  = '';

			if ( ! empty( $value ) ) {
				$date_parts = explode( '-', $value );
				if ( count( $date_parts ) === 3 ) {
					$selected_year  = $date_parts[0];
					$selected_month = $date_parts[1];
					$selected_date  = $date_parts[2];
				}
			}
			?>
            <style>
                .bwfac_form_field_birthday .wffn-optin-input,
                .bwfac_form_field_birthday .bwfan-dob-wrapper {
                    width: 100%;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .bwfac_form_field_birthday .bwfan-dob-field {
                    flex: 1;
                    min-width: 80px;
                }
            </style>
            <div class="bwfac_form_sec bwfac_form_field_birthday <?php echo esc_attr( $width ); ?>">
				<?php if ( ! empty( $label ) ) { ?>
                    <label for="wfop_id_<?php echo esc_attr( $name ) . '_' . esc_attr( $hash ); ?>"><?php echo esc_html( $label );
						echo ( $required ) ? '<span>*</span>' : ''; ?> </label>
				<?php } ?>
                <div class="wfop_input_cont bwfan-dob-wrapper">
                    <select name="<?php echo esc_attr( $name ); ?>_mm" class="bwfan-dob-field wffn-optin-input <?php echo esc_attr( $class ) ?>" data-field="<?php echo esc_attr( $label ); ?>" data-label="month">
                        <option value=""><?php esc_html_e( 'Select Month', 'wp-marketing-automations-pro' ); ?></option>
						<?php foreach ( BWFAN_Birthday::get_date_select_options( 'month' ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_month, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
                    </select>
                    <select name="<?php echo esc_attr( $name ); ?>_dd" class="bwfan-dob-field wffn-optin-input <?php echo esc_attr( $class ) ?>" data-field="<?php echo esc_attr( $label ); ?>" data-label="day">
                        <option value=""><?php esc_html_e( 'Select Day', 'wp-marketing-automations-pro' ); ?></option>
						<?php foreach ( BWFAN_Birthday::get_date_select_options( 'date' ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_date, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
                    </select>
                    <select name="<?php echo esc_attr( $name ); ?>_yy" class="bwfan-dob-field wffn-optin-input <?php echo esc_attr( $class ) ?>" data-field="<?php echo esc_attr( $label ); ?>" data-label="year">
                        <option value=""><?php esc_html_e( 'Select Year', 'wp-marketing-automations-pro' ); ?></option>
						<?php foreach ( BWFAN_Birthday::get_date_select_options( 'year' ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_year, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
                    </select>
                    <input type="hidden" id="wfop_id_<?php echo esc_attr( $name ) . '_' . esc_attr( $hash ); ?>" class="<?php echo esc_attr( $class ) ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
                </div>
            </div>
			<?php
		}

		/**
		 * @return array
		 */
		public function get_field_format() {
			return array(
				'width'       => 'wffn-sm-100',
				'type'        => $this::get_slug(),
				'label'       => __( 'Birthday', 'wp-marketing-automations-pro' ),
				'placeholder' => '',
				'required'    => false,
				'InputName'   => $this->get_prefix() . $this::get_slug(),
				'default'     => '',
			);
		}

		public function get_sanitized_value( $data, $field ) {
			// Handle new format with multiple selects
			$base_name = $field['InputName'];
			$year      = isset( $data[ $base_name . '_yy' ] ) ? wffn_clean( $data[ $base_name . '_yy' ] ) : '';
			$month     = isset( $data[ $base_name . '_mm' ] ) ? wffn_clean( $data[ $base_name . '_mm' ] ) : '';
			$day       = isset( $data[ $base_name . '_dd' ] ) ? wffn_clean( $data[ $base_name . '_dd' ] ) : '';

			// If all parts are present, create the date
			if ( ! empty( $year ) && ! empty( $month ) && ! empty( $day ) ) {
				return $year . '-' . $month . '-' . $day;
			}

			// Fall back to old format if new format isn't present
			return isset( $data[ $base_name ] ) ? wffn_clean( $data[ $base_name ] ) : '';
		}

		public function get_default_value( $field_data ) {
			if ( ! WFFN_Common::is_page_builder_editor() && true === apply_filters( 'wffn_optin_default_login_data', true, $field_data ) ) {
				global $current_user;
				if ( ! $current_user instanceof WP_User ) {
					return '';
				}

				return get_user_meta( $current_user->ID, 'bwfan_birthday_date', true );
			}

			return '';
		}
	}

	if ( class_exists( 'WFOPP_Core' ) ) {
		WFOPP_Core()->form_fields->register( WFFN_Optin_Form_Field_Birthday::get_instance() );
	}
}