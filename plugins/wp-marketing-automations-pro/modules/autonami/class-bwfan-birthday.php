<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BWFAN_Birthday class
 */
class BWFAN_Birthday {

	private static $ins = null;

	public function __construct() {
		/** Settings */
		add_filter( 'bwfan_admin_settings_schema', [ $this, 'add_admin_setting_schema' ] );
		add_filter( 'bwfan_default_global_settings', [ $this, 'add_default_birthday_label' ] );

		/** handling birthday field on my profile page */
		add_action( 'show_user_profile', [ __CLASS__, 'show_birthday_on_my_profile' ], 15 );
		add_action( 'edit_user_profile', [ $this, 'show_birthday_field_on_user_profile' ], 15 );
		add_action( 'edit_user_profile_update', array( $this, 'update_birthday_details_user_meta' ) );

		/** birthday field functionality on a funnelkit optin form */
		if ( bwfan_is_funnel_active() ) {
			add_action( 'init', array( $this, 'load_optin_form_birthday_field' ) );
			add_filter( 'wffn_get_optin_default_fields', [ $this, 'add_birthday_field_optin_form' ] );
			add_action( 'wffn_optin_form_submit', array( $this, 'optin_form_submit' ), 999, 2 );
		}

		// Check if WooCommerce is active before proceeding to dependent hooks
		if ( ! bwfan_is_woocommerce_active() ) {
			return;
		}

		/** CSS compatability */
		add_action( 'wp_head', array( $this, 'bwfan_add_compatible_css_for_themes' ) );

		/** placing birthday field on the checkout form **/
		add_action( 'woocommerce_before_checkout_form', [ $this, 'bwfan_placing_birthday_field' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'update_order_meta' ], 99 );

		/** handling of birthday field on wc my account form */
		add_action( 'woocommerce_edit_account_form', [ $this, 'add_birthday_field_on_wc_my_account' ] );
		add_action( 'woocommerce_save_account_details', [ $this, 'update_birthday_details_user_meta' ] );

		/** Validate the birthday if field is required */
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_birthday' ), 999, 2 );

		if ( ! bwfan_is_funnel_active() ) {
			return;
		}

		/** showing fields on funnelkit checkout page **/
		add_filter( 'wfacp_advanced_fields', [ $this, 'attach_dob_field_checkout_form' ] );
		add_filter( 'wfacp_html_fields_bwfan_birthday_date', '__return_false' );
		add_action( 'process_wfacp_html', [ $this, 'showing_birthday_field_on_aerocheckout_page' ], 10, 3 );
		add_filter( 'wfacp_widget_fields_classes', [ $this, 'add_field_width_option' ], 10, 3 );

		/** DOB field on thank you page */
		add_shortcode( 'bwfan_thank_you_birthday_collection_form', array( $this, 'birthday_field_on_thankyou' ) );
		add_action( 'wp_ajax_nopriv_save_birthday_on_thankyou', array( $this, 'save_birthday_on_thankyou' ) );
		add_action( 'wp_ajax_save_birthday_on_thankyou', array( $this, 'save_birthday_on_thankyou' ) );

		/** Display dob field on single order in admin */
		add_filter( 'wfacp_admin_order_field', [ $this, 'display_dob_wfacp_field' ], 10, 2 );
	}

	/**
	 * Birthday field on aerocheckout frontend
	 *
	 * @param $field
	 * @param $key
	 * @param $args
	 *
	 * @return void
	 */
	public function showing_birthday_field_on_aerocheckout_page( $field, $key, $args ) {
		if ( 'bwfan_birthday_date' !== $key ) {
			return;
		}

		$global_settings = BWFAN_Common::get_global_settings();
		$selected_date   = '';
		$selected_month  = '';
		$selected_year   = '';
		if ( is_user_logged_in() ) {
			$user    = get_user_by( 'id', get_current_user_id() );
			$contact = new BWFCRM_Contact( $user->user_email );
			/** Get DOB from contact */
			$dob = $contact->get_field_by_slug( 'dob' );
			/** Get Dob from user meta */
			$user_dob      = get_user_meta( get_current_user_id(), 'bwfan_birthday_date', true );
			$birthday_date = empty( $dob ) ? $user_dob : $dob;

			if ( ! empty( $global_settings['bwfan_hide_dob_field'] ) && ! empty( $birthday_date ) ) {
				return;
			}

			if ( ! empty( $birthday_date ) ) {
				$date_parts = explode( '-', $birthday_date );
				if ( count( $date_parts ) === 3 ) {
					$selected_year  = $date_parts[0];
					$selected_month = $date_parts[1];
					$selected_date  = $date_parts[2];
				}
			}
		}

		/** If year, month and day are empty */
		if ( empty( $selected_year ) && empty( $selected_date ) && empty( $selected_month ) && ! is_null( WC()->session ) ) {
			$selected_year  = WC()->session->get( 'bwfan_birthday_date_yy', '' );
			$selected_date  = WC()->session->get( 'bwfan_birthday_date_dd', '' );
			$selected_month = WC()->session->get( 'bwfan_birthday_date_mm', '' );
		}

		$birthday_label = isset( $args['label'] ) ? $args['label'] : '';
		$class          = isset( $args['class'] ) && ! empty( $args['class'] ) ? $args['class'] : '';
		$label_class    = isset( $args['label_class'] ) && ! empty( $args['label_class'] ) ? $args['label_class'] : '';
		$input_class    = isset( $args['input_class'] ) && ! empty( $args['input_class'] ) ? $args['input_class'] : '';
		$required       = isset( $args['required'] ) && ! empty( $args['required'] ) ? $args['required'] : false;

		$show_custom_field_at_thankyou = isset( $args['show_custom_field_at_thankyou'] ) && ! empty( $args['show_custom_field_at_thankyou'] ) ? $args['show_custom_field_at_thankyou'] : '';
		$show_custom_field_at_email    = isset( $args['show_custom_field_at_email'] ) && ! empty( $args['show_custom_field_at_email'] ) ? $args['show_custom_field_at_email'] : '';

		if ( isset( $args['bwfan_birthday_date'] ) && intval( $args['bwfan_birthday_date'] ) > 0 ) {
			$min_filter = function () use ( $args ) {
				$current_year = (int) current_time( 'Y' );
				$current_year = $current_year - intval( $args['bwfan_birthday_date'] );

				return "$current_year-01-01";
			};

			add_filter( 'bwfan_set_birthday_max', $min_filter );
		}

		$dob_fields = array(
			'bwfan_birthday_date_dd' => [
				'id'                            => 'bwfan_birthday_date_dd',
				'data_label'                    => __( 'Day', 'wp-marketing-automations-pro' ),
				'placeholder'                   => __( 'Day', 'wp-marketing-automations-pro' ),
				'type'                          => 'select',
				'required'                      => $required,
				'options'                       => self::get_date_select_options( 'date', 'aerocheckout' ),
				'default'                       => '',
				'show_custom_field_at_thankyou' => $show_custom_field_at_thankyou,
				'show_custom_field_at_email'    => $show_custom_field_at_email,
				'is_wfacp_field'                => true,
				'field_type'                    => 'advanced',
				'allow_delete'                  => true,
			],
			'bwfan_birthday_date_mm' => [
				'id'                            => 'bwfan_birthday_date_mm',
				'data_label'                    => __( 'Month', 'wp-marketing-automations-pro' ),
				'placeholder'                   => __( 'Month', 'wp-marketing-automations-pro' ),
				'type'                          => 'select',
				'required'                      => $required,
				'options'                       => self::get_date_select_options( 'month', 'aerocheckout' ),
				'show_custom_field_at_thankyou' => $show_custom_field_at_thankyou,
				'show_custom_field_at_email'    => $show_custom_field_at_email,
				'is_wfacp_field'                => true,
				'field_type'                    => 'advanced',
				'allow_delete'                  => true,
				'default'                       => '',
			],
			'bwfan_birthday_date_yy' => [
				'id'                            => 'bwfan_birthday_date_yy',
				'data_label'                    => __( 'Year', 'wp-marketing-automations-pro' ),
				'placeholder'                   => __( 'Year', 'wp-marketing-automations-pro' ),
				'cssready'                      => array(),
				'type'                          => 'select',
				'required'                      => $required,
				'options'                       => self::get_date_select_options( 'year', 'aerocheckout' ),
				'default'                       => '',
				'show_custom_field_at_thankyou' => $show_custom_field_at_thankyou,
				'show_custom_field_at_email'    => $show_custom_field_at_email,
				'is_wfacp_field'                => true,
				'field_type'                    => 'advanced',
				'allow_delete'                  => true,
			]
		);

		if ( isset( $args['bwfan_birthday_date'] ) && intval( $args['bwfan_birthday_date'] ) > 0 ) {
			remove_filter( 'bwfan_set_birthday_max', $min_filter );
		}

		$default_fields = [
			'input_class' => [
				'wfacp-form-control',
				'bwfan-dob-field'
			],
			'label_class' => [
				'wfacp-form-control-label',
			],
			'class'       => [
				'bwf-dob-field-wrap',
				'form-row',
				'wfacp-dob-wrapper',
				'wfacp-form-control-wrapper',
				'wfacp-col-left-third',
				'wfacp_drop_list',
				'wfacp_dropdown',

			],
			'cssready'    => [
				'wfacp-col-left-third',
			],

		];

		$selected_value = [
			'bwfan_birthday_date_dd' => $selected_date,
			'bwfan_birthday_date_mm' => $selected_month,
			'bwfan_birthday_date_yy' => $selected_year,
		];

		?>

        <div class="form-row wfacp-form-control-wrapper wfacp-col-full bwfan-birthday-wrap">
			<?php
			if ( ! empty( $birthday_label ) ) {
				?>
                <label for="bwfan_birthday_date" class="wfacp-form-control-label bwfan-birthday-label"><?php echo $birthday_label; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
					<?php if ( ! empty( $required ) ) { ?>
                        <abbr class="required" title="required">*</abbr>
					<?php } ?>
                </label>
				<?php
			}

			if ( is_array( $dob_fields ) && count( $dob_fields ) > 0 ) {
				foreach ( $dob_fields as $k => $field ) {
					$field['input_class'] = array_merge( $default_fields['input_class'], $input_class );;
					$field['label_class'] = array_merge( $default_fields['label_class'], $label_class );
					$field['class']       = array_merge( $default_fields['class'], $class );
					$field['cssready']    = $default_fields['cssready'];
					woocommerce_form_field( $k, $field, $selected_value[ $k ] );
				}
			}
			?>
        </div>
		<?php
	}

	/**
	 *  on the basis of placement hooking the field
	 */
	public function bwfan_placing_birthday_field() {
		if ( ! $this->is_native_checkout() ) {
			return;
		}

		$hook            = false;
		$global_settings = BWFAN_Common::get_global_settings();

		if ( ! isset( $global_settings['bwfan_dob_field'] ) || empty( $global_settings['bwfan_dob_field'] ) ) {
			return;
		}

		$field_position = isset( $global_settings['bwfan_birthday_field_position'] ) && ! empty( $global_settings['bwfan_birthday_field_position'] ) ? $global_settings['bwfan_birthday_field_position'] : 'after_billing_details';
		switch ( $field_position ) {
			case 'after_order_notes':
				$hook = 'woocommerce_after_order_notes';
				break;
			case 'before_order_notes':
				$hook = 'woocommerce_before_order_notes';
				break;
			case 'after_billing_details':
				if ( ! is_user_logged_in() && WC()->checkout()->is_registration_enabled() ) {
					$hook = 'woocommerce_after_checkout_registration_form';
				} else {
					$hook = 'woocommerce_after_checkout_billing_form';
				}
				break;
		}

		add_action( $hook, [ $this, 'add_dob_field_checkout_form' ] );
	}

	/**
	 * Checks if native WC checkout
	 *
	 * @return bool
	 */
	public function is_native_checkout() {
		global $post;
		if ( is_null( $post ) ) {
			return false;
		}

		if ( $post->post_status !== 'publish' || ( class_exists( 'WFACP_Common' ) && $post->post_type === WFACP_Common::get_post_type_slug() ) || 0 !== did_action( 'wfacp_after_checkout_page_found' ) ) {
			return false;
		}

		$meta_exist = get_post_meta( $post->ID, '_is_aero_checkout_page', true );

		return empty( $meta_exist );
	}

	/**
	 * Display date of birth field in FunnelKit Checkout
	 *
	 * @return void
	 */
	public function add_dob_field_checkout_form() {
		$birthday_label = '';
		$class          = '';
		$label_class    = '';
		$input_class    = '';
		$label_position = '';
		$wfacp_id       = 0;
		$required       = false;

		include __DIR__ . '/template/birthday-field.php';
	}

	public static function get_date_select_options( $type = 'date', $page = '' ) {
		$options = [];

		switch ( $type ) {
			case 'date':
				$options = 'aerocheckout' === $page ? array( '' => __( 'Select Day', 'wp-marketing-automations-pro' ) ) : [];
				for ( $i = 1; $i <= 31; $i ++ ) {
					$key             = sprintf( "%02d", $i );
					$options[ $key ] = $i;
				}
				break;

			case 'month':
				$options = 'aerocheckout' === $page ? array( '' => __( 'Select Month', 'wp-marketing-automations-pro' ) ) : [];
				for ( $i = 1; $i <= 12; $i ++ ) {
					$key             = sprintf( "%02d", $i );
					$options[ $key ] = date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) );
				}
				break;

			case 'year':
				$options = 'aerocheckout' === $page ? array( '' => __( 'Select Year', 'wp-marketing-automations-pro' ) ) : [];

				$current_year = BWFAN_PRO_Common::get_birthday_max_value( 'year' );
				if ( empty( $current_year ) ) {
					$current_year = (int) current_time( 'Y' );
				}

				$min_year = BWFAN_PRO_Common::get_birthday_min_value( 'year' );
				if ( empty( $min_year ) ) {
					$years_back = apply_filters( 'bwfan_birthday_min_years', 100 );
					$years_back = max( 1, intval( $years_back ) );
					$min_year   = $current_year - $years_back;
				}


				for ( $i = $current_year; $i >= $min_year; $i -- ) {
					$options[ (string) $i ] = (string) $i;
				}
				break;
		}

		return $options;
	}

	/**
	 * Validates a date to ensure it's properly formatted, valid, and not in the future
	 *
	 * @param string $date Date in YYYY-MM-DD format to validate
	 *
	 * @return array ['valid' => bool, 'message' => string]
	 */
	public static function validate_birthday_date( $date ) {
		$result = array(
			'valid'   => false,
			'message' => ''
		);

		// Check if date is empty
		if ( empty( $date ) ) {
			$result['message'] = __( 'Date of birth is required.', 'wp-marketing-automations-pro' );

			return $result;
		}

		// Validate date format
		$date_parts = explode( '-', $date );
		if ( count( $date_parts ) !== 3 || ! is_numeric( $date_parts[0] ) || ! is_numeric( $date_parts[1] ) || ! is_numeric( $date_parts[2] ) ) {
			$result['message'] = __( 'Invalid date format. Please use YYYY-MM-DD format.', 'wp-marketing-automations-pro' );

			return $result;
		}

		$year  = (int) $date_parts[0];
		$month = (int) $date_parts[1];
		$day   = (int) $date_parts[2];

		// Check if date is valid
		if ( ! checkdate( $month, $day, $year ) ) {
			$result['message'] = __( 'Please enter a valid date.', 'wp-marketing-automations-pro' );

			return $result;
		}

		// Check if date is in the future
		$selected_date = new DateTime( $date );
		$current_date  = new DateTime( 'today' );

		if ( $selected_date > $current_date ) {
			$result['message'] = __( 'Date of birth cannot be in the future. Please enter a valid date.', 'wp-marketing-automations-pro' );

			return $result;
		}

		// Date is valid
		$result['valid'] = true;

		return $result;
	}

	/**
	 * Add date of birth field in FunnelKit Checkout admin
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function attach_dob_field_checkout_form( $fields ) {
		if ( 0 === WFACP_Common::get_id() ) {
			return $fields;
		}

		$data = WFACP_Common::get_post_meta_data( WFACP_Common::get_id(), '_wfacp_checkout_fields' );
		if ( empty( $data ) ) {
			return $fields;
		}
		$birthday_field = $data['advanced']['bwfan_birthday_date'] ?? [];

		$fields['bwfan_birthday_date'] = [
			'type'                => 'wfacp_html',
			'class'               => [ 'bwfan_birthday_date' ],
			'id'                  => 'bwfan_birthday_date',
			'field_type'          => 'advanced',
			'label'               => __( 'Date of Birth', 'wp-marketing-automations-pro' ),
			'created_by'          => 'bwfanbirthday',
			'is_wfacp_field'      => true,
			'required'            => $birthday_field['required'] ?? false,
			'show_required_field' => true
		];

		return $fields;
	}

	/** updating birthday field in order meta **/
	/**
	 * @param $order_id
	 *
	 * @return void
	 */
	public function update_order_meta( $order_id ) {
		$year  = filter_input( INPUT_POST, 'bwfan_birthday_date_yy' );
		$month = filter_input( INPUT_POST, 'bwfan_birthday_date_mm' );
		$day   = filter_input( INPUT_POST, 'bwfan_birthday_date_dd' );

		if ( empty( $year ) || empty( $month ) || empty( $day ) ) {
			$birthday_date = filter_input( INPUT_POST, 'billing_birthdate' );
			if ( empty( $birthday_date ) ) {
				return;
			}
		} else {
			$birthday_date = $year . '-' . $month . '-' . $day;
		}

		$birthday_date = BWFCRM_Contact::get_date_value( $birthday_date );
		$order         = wc_get_order( $order_id );
		// check if the contact cid exists
		$contact_data = $order->get_meta( '_woofunnel_cid' );
		// if contact data is empty, get the email address from order
		if ( empty( $contact_data ) ) {
			$contact_data = $order->get_billing_email();
		}

		$contact = new BWFCRM_Contact( $contact_data );
		if ( ! $contact->is_contact_exists() ) {
			return;
		}
		$global_settings = BWFAN_Common::get_global_settings();
		$hide_field      = ! empty( $global_settings['bwfan_hide_dob_field'] );
		/** Allow overriding the hide_field setting via filter. */
		$hide_field = apply_filters( 'bwfan_skip_dob_update', $hide_field, $order_id, $contact );
		$order->update_meta_data( 'bwfan_birthday_date', $birthday_date );
		$order->save();
		if ( $hide_field && $contact->get_field_by_slug( 'dob' ) ) {
			return;
		}
		$contact->set_field_by_slug( 'dob', $birthday_date );
		$contact->save_fields();
		$user_id = $order->get_user_id() ? $order->get_user_id() : $contact->contact->get_wpid();
		if ( empty( $user_id ) ) {
			return;
		}
		update_user_meta( $user_id, 'bwfan_birthday_date', $birthday_date );
	}

	/**
	 * @param $optin_page_id
	 * @param $posted_data
	 *
	 * @return void
	 */
	public function optin_form_submit( $optin_page_id, $posted_data ) {
		/** Birthday field is not present */
		$base_name = 'optin_birthday';

		// Try new format with dropdowns first
		$year  = isset( $posted_data[ $base_name . '_yy' ] ) ? $posted_data[ $base_name . '_yy' ] : '';
		$month = isset( $posted_data[ $base_name . '_mm' ] ) ? $posted_data[ $base_name . '_mm' ] : '';
		$day   = isset( $posted_data[ $base_name . '_dd' ] ) ? $posted_data[ $base_name . '_dd' ] : '';

		// If we have all date parts, create the date string
		if ( ! empty( $year ) && ! empty( $month ) && ! empty( $day ) ) {
			$birthday_date = $year . '-' . $month . '-' . $day;
		} else {
			// Fall back to old format if new format isn't present
			if ( ! isset( $posted_data[ $base_name ] ) || empty( $posted_data[ $base_name ] ) ) {
				return;
			}
			$birthday_date = $posted_data[ $base_name ];
		}

		// Validate the date
		$validation = self::validate_birthday_date( $birthday_date );
		if ( ! $validation['valid'] ) {
			return;
		}

		$contact = isset( $posted_data['cid'] ) ? $posted_data['cid'] : ( isset( $posted_data['optin_email'] ) ? $posted_data['optin_email'] : '' );
		if ( empty( $contact ) ) {
			return;
		}

		$bwfcrm_contact = new BWFCRM_Contact( $contact );
		if ( ! $bwfcrm_contact->is_contact_exists() ) {
			return;
		}

		$birthday_date = BWFCRM_Contact::get_date_value( $birthday_date );

		$bwfcrm_contact->set_field_by_slug( 'dob', $birthday_date );
		$bwfcrm_contact->save_fields();

		if ( empty( $bwfcrm_contact->contact->get_wpid() ) ) {
			return;
		}

		update_user_meta( $bwfcrm_contact->contact->get_wpid(), 'bwfan_birthday_date', $birthday_date );
	}

	/**
	 * Add date of birth field in FunnelKit Optin
	 *
	 * @return void
	 */
	public function load_optin_form_birthday_field() {
		if ( ! class_exists( 'WFFN_Optin_Form_Field' ) ) {
			return;
		}

		require_once( __DIR__ . '/template/birthday-optin-field.php' ); //phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	}

	/**
	 * add CSS for both native and funnel checkout
	 * @return void
	 */
	public function bwfan_add_compatible_css_for_themes() {
		if ( ! is_checkout() ) {
			return;
		}

		// loading CSS only for astra theme
		if ( defined( 'ASTRA_THEME_VERSION' ) ) {
			?>
            <style>
                .theme-astra input[type=date] {
                    padding: 0.75rem;
                    border: 1px solid var(--ast-border-color);
                }

                body #wfacp-e-form .wfacp_main_form.woocommerce input[type=date] {
                    width: 100%;
                }
            </style>
			<?php
		}
	}

	public function add_birthday_field_optin_form( $output ) {
		$output['basic']['wfop_optin_birthday'] = [
			'label'       => __( 'Date of Birth', 'wp-marketing-automations-pro' ),
			'type'        => 'optin_birthday',
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 10,
			'id'          => 'wfop_optin_birthday',
			'placeholder' => __( 'Birthday', 'wp-marketing-automations-pro' ),
			'width'       => 'wffn-sm-100'
		];

		return $output;
	}

	/**
	 * Returns the field schema if funnel builder is enabled.
	 *
	 * @return array[]
	 */
	public function get_funnel_builder_enabled_field_schema() {
		$birthdate_text  = __( "Birthdate", 'wp-marketing-automations-pro' );
		$Submit_btn_text = __( "Save", 'wp-marketing-automations-pro' );

		return array(
			array(
				'id'          => 'bwfan_dob_field_funnelkit_checkout',
				'type'        => 'text',
				'class'       => 'bwfan_dob_field ',
				'required'    => false,
				'show'        => false,
				'toggler'     => array(),
				'isWooField'  => true,
				'wrap_before' => "<br/><h3>" . __( 'FunnelKit Checkout', 'wp-marketing-automations-pro' ) . "</h3><p><i>" . __( 'FunnelKit Checkout provides Date of Birth field in its field editor. Go to Checkout > Fields and drag the Date of Birth field.', 'wp-marketing-automations-pro' ) . "</i></p>",
			),
			array(
				'id'          => 'bwfan_dob_field_funnelkit_checkout',
				'type'        => 'text',
				'class'       => 'bwfan_dob_field ',
				'required'    => false,
				'show'        => false,
				'toggler'     => array(),
				'wrap_before' => "<br/><h3>" . __( 'FunnelKit Optin', 'wp-marketing-automations-pro' ) . "</h3><p><i>" . __( 'FunnelKit Optin provides Date of Birth field in its field editor. Go to Optin > Fields and drag the Date of Birth field.', 'wp-marketing-automations-pro' ) . "</i></p>",
			),
			array(
				'id'            => 'bwfan_dob_field',
				'label'         => __( 'Enable Field', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Enable Date of Birth field on WooCommerce checkout", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'isWooField'    => true,
				'show'          => true,
				'wrap_before'   => '<br/><h3>' . __( 'WooCommerce Checkout', 'wp-marketing-automations-pro' ) . '</h3>',
				'toggler'       => array(),
			),
			array(
				'id'         => 'dob_notice',
				'type'       => 'notice',
				'class'      => '',
				'isWooField' => true,
				'status'     => 'warning',
				'message'    => '<b>' . __( 'Note:', 'wp-marketing-automations-pro' ) . '</b>' . '&nbsp' . __( "If you're using FunnelKit Checkout or FunnelKit Optin, you can add the Date of Birth field to any position using the field editor. The label and required settings can also be configured directly within the field editor.", 'wp-marketing-automations-pro' ),
				'dismiss'    => false,
				'isHtml'     => true,
				'required'   => false,
				'toggler'    => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'          => 'bwfan_birthday_field_label',
				'label'       => __( 'Field Label', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => 'bwfan_birthday_field_label',
				'required'    => false,
				'placeholder' => __( 'Enter field\'s label', 'wp-marketing-automations-pro' ),
				'show'        => true,
				'isWooField'  => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'            => 'bwfan_dob_required',
				'label'         => __( 'Required', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_required ',
				'checkboxlabel' => __( "Make date of birth field required", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'show'          => true,
				'isWooField'    => true,
				'toggler'       => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'          => 'bwfan_birthday_field_position',
				'label'       => __( 'Field Position', 'wp-marketing-automations-pro' ),
				'type'        => 'select',
				'placeholder' => __( 'Select Position', 'wp-marketing-automations-pro' ),
				'multiple'    => false,
				'isWooField'  => true,
				'class'       => 'bwfan_birthday_field_position',
				'options'     => array(
					array(
						'value' => 'after_billing_details',
						'label' => __( 'After Billing Details', 'wp-marketing-automations-pro' ),
					),
					array(
						'value' => 'before_order_notes',
						'label' => __( 'Before Order Notes', 'wp-marketing-automations-pro' ),
					),
					array(
						'value' => 'after_order_notes',
						'label' => __( 'After Order Notes', 'wp-marketing-automations-pro' ),
					),
				),
				'required'    => false,
				'show'        => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'            => 'bwfan_dob_field_my_account',
				'label'         => __( 'Enable Field', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Enable Date of Birth field on My Account", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'isWooField'    => true,
				'show'          => true,
				'toggler'       => array(),
				'wrap_before'   => '<br/><h3>' . __( 'WooCommerce My Account', 'wp-marketing-automations-pro' ) . '</h3>',
			),
			array(
				'id'          => 'bwfan_my_account_birthday_field_label',
				'label'       => __( 'Field Label', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => 'bwfan_birthday_field_label',
				'required'    => false,
				'placeholder' => __( 'Enter field\'s label', 'wp-marketing-automations-pro' ),
				'show'        => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field_my_account',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'            => 'bwfan_dob_field_thankyou',
				'label'         => __( 'Enable Shortcode', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Enable Date of Birth collection form shortcode on the Thank you page", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'show'          => true,
				'toggler'       => array(),
				'isWooField'    => true,
				'wrap_before'   => '<br/><h3>' . __( 'WooCommerce Thank You', 'wp-marketing-automations-pro' ) . '</h3>',
			),
			array(
				'id'         => 'bwfan_dob_field_shortcode',
				'type'       => 'copier',
				'hint'       => __( "Place this shortcode on the Thank you page to show the form. Will work only on the thank you page.", 'wp-marketing-automations-pro' ),
				'required'   => false,
				'copy_text'  => '[bwfan_thank_you_birthday_collection_form label="' . $birthdate_text . '" button="' . $Submit_btn_text . '"]',
				'toggler'    => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field_thankyou',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
				'isWooField' => true,
			),
			array(
				'id'          => 'bwfan_dob_ty_success_msg',
				'label'       => __( 'Success Message', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => 'bwfan_dob_ty_success_msg',
				'required'    => false,
				'placeholder' => __( 'Success message', 'wp-marketing-automations-pro' ),
				'show'        => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field_thankyou',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
				'isWooField'  => true,
			),
			array(
				'id'            => 'bwfan_hide_dob_field',
				'label'         => __( 'Hide Field', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Hide field if Date of Birth is already available for a contact", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'show'          => true,
				'toggler'       => array(),
				'wrap_before'   => "<br/><h3>" . __( 'Visibility', 'wp-marketing-automations-pro' ) . "</h3>",
			),
		);
	}

	/**
	 * Returns the field schema for WooCommerce only fields.
	 *
	 * @return array[]
	 */
	public function get_woocommerce_enabled_field_schema() {
		return array(
			array(
				'id'            => 'bwfan_dob_field',
				'label'         => __( 'Enable Field', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Enable Date of Birth field on WooCommerce checkout", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'isWooField'    => true,
				'show'          => true,
				'wrap_before'   => '<br/><h3>' . __( 'WooCommerce Checkout', 'wp-marketing-automations-pro' ) . '</h3>',
				'toggler'       => array(),
			),
			array(
				'id'          => 'bwfan_birthday_field_label',
				'label'       => __( 'Field Label', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => 'bwfan_birthday_field_label',
				'required'    => false,
				'placeholder' => __( 'Enter field\'s label', 'wp-marketing-automations-pro' ),
				'show'        => true,
				'isWooField'  => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'            => 'bwfan_dob_required',
				'label'         => __( 'Required', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_required ',
				'checkboxlabel' => __( "Make date of birth field required", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'show'          => true,
				'isWooField'    => true,
				'toggler'       => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'          => 'bwfan_birthday_field_position',
				'label'       => __( 'Field Position', 'wp-marketing-automations-pro' ),
				'type'        => 'select',
				'placeholder' => __( 'Select Position', 'wp-marketing-automations-pro' ),
				'multiple'    => false,
				'isWooField'  => true,
				'class'       => 'bwfan_birthday_field_position',
				'options'     => array(
					array(
						'value' => 'after_billing_details',
						'label' => __( 'After Billing Details', 'wp-marketing-automations-pro' ),
					),
					array(
						'value' => 'before_order_notes',
						'label' => __( 'Before Order Notes', 'wp-marketing-automations-pro' ),
					),
					array(
						'value' => 'after_order_notes',
						'label' => __( 'After Order Notes', 'wp-marketing-automations-pro' ),
					),
				),
				'required'    => false,
				'show'        => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'            => 'bwfan_dob_field_my_account',
				'label'         => __( 'Enable Field', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Enable Date of Birth field on My Account", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'isWooField'    => true,
				'show'          => true,
				'toggler'       => array(),
				'wrap_before'   => '<br/><h3>' . __( 'WooCommerce My Account', 'wp-marketing-automations-pro' ) . '</h3>',
			),
			array(
				'id'          => 'bwfan_my_account_birthday_field_label',
				'label'       => __( 'Field Label', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => 'bwfan_birthday_field_label',
				'required'    => false,
				'placeholder' => __( 'Enter field\'s label', 'wp-marketing-automations-pro' ),
				'show'        => true,
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'bwfan_dob_field_my_account',
							'value' => true,
						),
					),
					'relation' => 'OR',
				),
			),
			array(
				'id'            => 'bwfan_hide_dob_field',
				'label'         => __( 'Hide Field', 'wp-marketing-automations-pro' ),
				'type'          => 'checkbox',
				'class'         => 'bwfan_dob_field ',
				'checkboxlabel' => __( "Hide field if Date of Birth is already available for a contact", 'wp-marketing-automations-pro' ),
				'required'      => false,
				'show'          => true,
				'toggler'       => array(),
				'wrap_before'   => "<br/><h3>" . __( 'Visibility', 'wp-marketing-automations-pro' ) . "</h3>",
			),
		);
	}

	/**
	 * @param $settings_schema
	 *
	 * @return array|mixed
	 */
	public function add_admin_setting_schema( $settings_schema ) {

		$birthday_field = bwfan_is_funnel_active() ? $this->get_funnel_builder_enabled_field_schema() : $this->get_woocommerce_enabled_field_schema();


		$birthday_reminder_fields = array(
			'key'     => 'birthday_reminder',
			'label'   => __( 'Birthday Reminder', 'wp-marketing-automations-pro' ),
			'heading' => __( 'Date Of Birth Field Settings', 'wp-marketing-automations-pro' ),
			'fields'  => $birthday_field
		);

		// fetching the position of advanced field in the tabs array
		$keys  = array_column( $settings_schema[0]['tabs'], 'key' );
		$index = array_search( 'advanced', $keys );
		$pos   = false === $index ? count( $settings_schema[0]['tabs'] ) : $index;

		// pushing birthday field before advanced field
		$new_ordered_tabs = array_merge( array_slice( $settings_schema[0]['tabs'], 0, $pos ), array( $birthday_reminder_fields ), array_slice( $settings_schema[0]['tabs'], $pos ) );

		// overriding the tabs array with new array
		$settings_schema[0]['tabs'] = $new_ordered_tabs;

		return $settings_schema;
	}

	/**
	 * Provide default values to the new settings
	 *
	 * @param $global_settings
	 *
	 * @return mixed
	 */
	public function add_default_birthday_label( $global_settings ) {
		$global_settings['bwfan_birthday_field_label']            = __( 'Date of Birth', 'wp-marketing-automations-pro' );
		$global_settings['bwfan_my_account_birthday_field_label'] = __( 'Date of Birth', 'wp-marketing-automations-pro' );
		$global_settings['bwfan_dob_ty_success_msg']              = __( 'Thank you for submitting your Birthdate!', 'wp-marketing-automations-pro' );

		return $global_settings;
	}

	/**
	 * showing birthday field on wc my account page
	 *
	 * @return void
	 */
	public function add_birthday_field_on_wc_my_account() {
		$global_settings = BWFAN_Common::get_global_settings();
		/** If dob field is not enable on my-account page */
		if ( ! isset( $global_settings['bwfan_dob_field_my_account'] ) || empty( $global_settings['bwfan_dob_field_my_account'] ) ) {
			return;
		}

		$birthday_label = $global_settings['bwfan_my_account_birthday_field_label'] ?? __( 'Date of Birth', 'wp-marketing-automations-pro' );
		$class          = '';
		$label_class    = '';
		$input_class    = '';
		$label_position = '';
		$required       = false;
		echo '<div class="wfacp_bwfan_birthday_field_wrap">';
		include __DIR__ . '/template/birthday-field.php';
		echo '</div>';
	}

	/**
	 * updating birthday in user meta and in autonami contact
	 *
	 * @param $user_id
	 *
	 * @return void
	 */
	public function update_birthday_details_user_meta( $user_id ) {
		$global_settings = BWFAN_Common::get_global_settings();

		$account_field = ( isset( $global_settings['bwfan_dob_field_my_account'] ) && ! empty( $global_settings['bwfan_dob_field_my_account'] ) );
		if ( ! $account_field ) {
			/** Check if dob field enabled for account area */
			return;
		}

		$required = isset( $global_settings['bwfan_dob_required'] ) ? $global_settings['bwfan_dob_required'] : false;
		if ( $required && 'woocommerce_save_account_details' !== current_action() ) {
			$required = false;
		}

		$bwfan_birthday = filter_input( INPUT_POST, 'bwfan_birthday_date' );

		// First check for the separate fields format (prioritize this)
		$year  = filter_input( INPUT_POST, 'bwfan_birthday_date_yy' );
		$month = filter_input( INPUT_POST, 'bwfan_birthday_date_mm' );
		$day   = filter_input( INPUT_POST, 'bwfan_birthday_date_dd' );

		if ( empty( $year ) && empty( $month ) && empty( $day ) && ! empty( $global_settings['bwfan_hide_dob_field'] ) ) {
			return;
		}

		// If we have all components, create the date string
		if ( ! empty( $year ) && ! empty( $month ) && ! empty( $day ) ) {
			$bwfan_birthday = $year . '-' . $month . '-' . $day;
		}

		// Check if we have a birthday value now
		if ( empty( $bwfan_birthday ) && $required ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Date of Birth is a required field.', 'wp-marketing-automations-pro' ), 'error' );
			}

			return;
		}

		if ( ! empty( $bwfan_birthday ) ) {
			// Validate the date
			$validation = self::validate_birthday_date( $bwfan_birthday );
			if ( ! $validation['valid'] ) {
				wc_add_notice( $validation['message'], 'error' );

				return;
			}

			// Format the date correctly
			$bwfan_birthday = BWFCRM_Contact::get_date_value( $bwfan_birthday );

			// Update user meta
			update_user_meta( $user_id, 'bwfan_birthday_date', $bwfan_birthday );

			// Get user email
			$email = filter_input( INPUT_POST, 'account_email' );
			if ( empty( $email ) ) {
				/** this is for edit profile */
				$email = filter_input( INPUT_POST, 'email' );
			}
			if ( empty( $email ) && is_user_logged_in() ) {
				$user  = wp_get_current_user();
				$email = $user->user_email;
			}

			if ( ! empty( $email ) ) {
				$contact        = new BWFCRM_Contact( $email );
				$contact_exists = $contact->is_contact_exists();

				if ( $contact_exists ) {
					$contact->set_field_by_slug( 'dob', $bwfan_birthday );
					$contact->save_fields();
				}
			}
		}
	}

	/**
	 * Show birthday on user profile page
	 *
	 * @param $user
	 *
	 * @return void
	 */
	public static function show_birthday_on_my_profile( $user ) {
		$global_settings = BWFAN_Common::get_global_settings();
		if ( ! isset( $global_settings['bwfan_dob_field_my_account'] ) || empty( $global_settings['bwfan_dob_field_my_account'] ) ) {
			return;
		}


		$contact = new BWFCRM_Contact( $user->user_email );
		/** Get DOB from contact */
		$dob = $contact->get_field_by_slug( 'dob' );
		/** Get Dob from user meta */
		$user_dob = get_user_meta( $user->ID, 'bwfan_birthday_date', true );
		$user_dob = empty( $dob ) ? $user_dob : $dob;

		if ( empty( $user_dob ) ) {
			return;
		}

		$birthday_label = ! empty( $global_settings['bwfan_my_account_birthday_field_label'] ) ? $global_settings['bwfan_my_account_birthday_field_label'] : __( 'Date of Birth', 'wp-marketing-automations-pro' );

		?>
        <table class="form-table">
            <tr>
				<?php if ( is_account_page() ) { ?>
                    <td><?php echo esc_html( $birthday_label ); ?></td>
				<?php } else { ?>
                    <th><?php echo esc_html( $birthday_label ); ?></th>
				<?php } ?>
                <td>
					<?php echo esc_html( date_i18n( BWFAN_Common::bwfan_get_date_format(), strtotime( $user_dob ) ) ); ?>
                </td>
            </tr>
        </table>
		<?php
	}

	/**
	 * showing birthday field on my account
	 *
	 * @param $user
	 *
	 * @return void
	 */
	public function show_birthday_field_on_user_profile( $user ) {
		$global_settings = BWFAN_Common::get_global_settings();
		if ( ! isset( $global_settings['bwfan_dob_field_my_account'] ) || empty( $global_settings['bwfan_dob_field_my_account'] ) ) {
			return;
		}

		$birthday_label = ! empty( $global_settings['bwfan_my_account_birthday_field_label'] ) ? $global_settings['bwfan_my_account_birthday_field_label'] : __( 'Date of Birth', 'wp-marketing-automations-pro' );

		$contact = new BWFCRM_Contact( $user->user_email );
		/** Get DOB from contact */
		$dob = $contact->get_field_by_slug( 'dob' );
		/** Get Dob from user meta */
		$user_dob = get_user_meta( $user->ID, 'bwfan_birthday_date', true );
		$user_dob = empty( $dob ) ? $user_dob : $dob;

		$max = BWFAN_PRO_Common::get_birthday_max_value();
		$min = BWFAN_PRO_Common::get_birthday_min_value();
		?>
        <table class="form-table">
            <tr>
                <th><?php echo esc_html( $birthday_label ); ?></label></th>
                <td>
                    <input type="date" name="bwfan_birthday_date" id="bwfan_birthday_date" value="<?php echo $user_dob; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  ?>" class="regular-text" <?php echo $max; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo $min; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/>
                </td>
            </tr>
        </table>
		<?php
	}

	/**
	 * @param $attr
	 *
	 * @return false|string|void
	 */
	public function birthday_field_on_thankyou( $attr ) {
		$global_settings = BWFAN_Common::get_global_settings();
		if ( ! isset( $global_settings['bwfan_dob_field_thankyou'] ) || empty( $global_settings['bwfan_dob_field_thankyou'] ) ) {
			return;
		}

		$label  = isset( $attr['label'] ) && ! empty( $attr['label'] ) ? trim( $attr['label'] ) : '';
		$button = isset( $attr['button'] ) ? $attr['button'] : "Save";

		$order_id = filter_input( INPUT_GET, 'order_id' );

		/** matching of order and its key */
		$order = wc_get_order( $order_id );
		if ( empty( $order_id ) || ! $order instanceof WC_Order ) {
			return;
		}
		$key       = $order->get_order_key();
		$order_key = filter_input( INPUT_GET, 'key' );
		if ( empty( $order_key ) || ( $key !== strval( $order_key ) ) ) {
			return;
		}

		$cid     = $order->get_meta( '_woofunnel_cid' );
		$contact = new BWFCRM_Contact( $cid );

		$dob_value  = ( $contact instanceof BWFCRM_Contact && $contact->is_contact_exists() ) ? $contact->get_field_by_slug( 'dob' ) : '';
		$hide_field = ! empty( $global_settings['bwfan_hide_dob_field'] );
		$hide_field = apply_filters( 'bwfan_thankyou_hide_dob_field', $hide_field, $dob_value, $order_id, $contact );
		if ( $hide_field && ! empty( $dob_value ) ) {
			return;
		}

		$max = BWFAN_PRO_Common::get_birthday_max_value();
		$min = BWFAN_PRO_Common::get_birthday_min_value();

		// Parse existing date value if available
		$selected_date  = '';
		$selected_month = '';
		$selected_year  = '';

		if ( ! empty( $dob_value ) ) {
			$date_parts = explode( '-', $dob_value );
			if ( count( $date_parts ) === 3 ) {
				$selected_year  = $date_parts[0];
				$selected_month = $date_parts[1];
				$selected_date  = $date_parts[2];
			}
		}

		ob_start();
		?>
        <div class="bwfan-tyb-wrap">
			<?php echo $label ? '<div class="bwfan-tyb-label"><label>' . esc_html( $label ) . '</label></div>' : ''; ?>
            <div class="bwfan-tyb-field">
				<?php
				$plugin_version  = defined( 'BWFAN_VERSION' ) ? BWFAN_VERSION : '0.0.0';
				$use_dropdown_ui = version_compare( $plugin_version, '3.5.3', '>' );

				if ( $use_dropdown_ui ) :
					?>
                    <select name="bwfan_birthday_date_mm" class="bwfan-dob-field" data-field="<?php echo esc_attr( $label ); ?>" data-label="month">
                        <option value=""><?php esc_html_e( 'Select Month', 'wp-marketing-automations-pro' ); ?></option>
						<?php foreach ( BWFAN_Birthday::get_date_select_options( 'month' ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_month, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
                    </select>
                    <select name="bwfan_birthday_date_dd" class="bwfan-dob-field" data-field="<?php echo esc_attr( $label ); ?>" data-label="day">
                        <option value=""><?php esc_html_e( 'Select Day', 'wp-marketing-automations-pro' ); ?></option>
						<?php foreach ( BWFAN_Birthday::get_date_select_options( 'date' ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_date, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
                    </select>
                    <select name="bwfan_birthday_date_yy" class="bwfan-dob-field" data-field="<?php echo esc_attr( $label ); ?>" data-label="year">
                        <option value=""><?php esc_html_e( 'Select Year', 'wp-marketing-automations-pro' ); ?></option>
						<?php foreach ( BWFAN_Birthday::get_date_select_options( 'year' ) as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_year, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
                    </select>
				<?php else : ?>
                    <input type="date" name="bwfan_ty_field_dob" id="bwfan-tyb-field" value="<?php echo esc_attr( $dob_value ); ?>" placeholder="<?php echo esc_html( $label ); ?>" class="regular-text" <?php echo isset( $max ) ? $max : ''; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo isset( $min ) ? $min : ''; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
				<?php endif; ?>
                <span class="bwfan-tyb-btn button-primary button" id="bwfan-tyb-save-btn"><?php echo esc_attr( $button ); ?></span>
            </div>
            <input type="hidden" id="bwfan-order-id" name="bwfan_order_id" value="<?php echo esc_attr( $order_id ); ?>">
            <input type="hidden" id="bwfan-cid" name="bwfan_cid" value="<?php echo esc_attr( $cid ); ?>">
            <input type="hidden" id="bwfan-tyb-nonce" name="bwfan_tyb_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bwfan_tyb_nonce' ) ); ?>">
            <div class="bwfan-tyb-response"><span class="bwfan-tyb-msg"></span></div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ajax callback for save birthday
	 *
	 * @return void
	 */
	public function save_birthday_on_thankyou() {
		BWFAN_PRO_Common::nocache_headers();
		$global_settings = BWFAN_Common::get_global_settings();

		$result = [
			'status' => 2,
			'msg'    => __( "Some error occurred.", "wp-marketing-automations-pro" ),
		];

		$nonce = filter_input( INPUT_POST, 'nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bwfan_tyb_nonce' ) ) {
			$result['msg'] = __( "Security verification failed.", "wp-marketing-automations-pro" );
			wp_send_json( $result );
		}

		if ( ! isset( $global_settings['bwfan_dob_field_thankyou'] ) || empty( $global_settings['bwfan_dob_field_thankyou'] ) ) {
			$result['msg'] = __( "Shortcode settings are not enabled.", "wp-marketing-automations-pro" );
			wp_send_json( $result );
		}

		$birthday = filter_input( INPUT_POST, 'birthday' );

		$year  = filter_input( INPUT_POST, 'bwfan_birthday_date_yy' );
		$month = filter_input( INPUT_POST, 'bwfan_birthday_date_mm' );
		$day   = filter_input( INPUT_POST, 'bwfan_birthday_date_dd' );

		if ( ! empty( $year ) && ! empty( $month ) && ! empty( $day ) ) {
			$birthday = $year . '-' . $month . '-' . $day;
		} else {
			// Otherwise use the complete date field
			$birthday = filter_input( INPUT_POST, 'birthday' );

			if ( empty( $birthday ) ) {
				$birthday = filter_input( INPUT_POST, 'bwfan_ty_field_dob' );
			}
		}

		if ( empty( $birthday ) ) {
			$result['msg'] = __( "Date of birth is required.", "wp-marketing-automations-pro" );
			wp_send_json( $result );
		}

		// Validate the date
		$validation = self::validate_birthday_date( $birthday );
		if ( ! $validation['valid'] ) {
			$result['msg'] = $validation['message'];
			wp_send_json( $result );
		}

		$cid      = filter_input( INPUT_POST, 'cid' );
		$order_id = filter_input( INPUT_POST, 'order_id' );

		if ( empty( $order_id ) && empty( $cid ) ) {
			$result['msg'] = __( "Required data is missing.", "wp-marketing-automations-pro" );
			wp_send_json( $result );
		}

		// Format the date for storage
		$birthday = BWFCRM_Contact::get_date_value( $birthday );

		$success_msg = isset( $global_settings['bwfan_dob_ty_success_msg'] ) ? $global_settings['bwfan_dob_ty_success_msg'] : '';

		if ( intval( $cid ) > 0 ) {
			$contact = $this->save_content_dob( $cid, $birthday, 1 );
			if ( true === $contact ) {
				$result['status'] = 1;
				$result['msg']    = $success_msg;
				wp_send_json( $result );
			}
		}

		if ( intval( $order_id ) > 0 ) {
			$contact = $this->save_content_dob( $order_id, $birthday, 2 );
			if ( true === $contact ) {
				$result['status'] = 1;
				$result['msg']    = $success_msg;
				wp_send_json( $result );
			}
		}

		wp_send_json( $result );
	}

	/**
	 * @param $value - cid, order id, order obj
	 * @param $dob
	 * @param $type - 1 cid, 2 order id, 3 order obj
	 *
	 * @return bool
	 */
	protected function save_content_dob( $value, $dob = '', $type = '1' ) {
		if ( empty( $value ) && empty( $type ) && empty( $dob ) ) {
			return false;
		}

		/** Type 1 - cid */
		if ( 1 === intval( $type ) ) {
			$crm_contact = new BWFCRM_Contact( $value );
			if ( empty( $crm_contact ) || ! $crm_contact->is_contact_exists() ) {
				return false;
			}

			return $this->save_dob( $crm_contact, $dob );
		}

		/** Type 2 - order id */
		if ( 2 === intval( $type ) ) {
			$order = wc_get_order( $value );
			if ( ! $order instanceof WC_Order ) {
				return false;
			}

			$cid = $order->get_meta( '_woofunnel_cid' );
			if ( empty( $cid ) ) {
				return $this->save_content_dob( $order, $dob, 3 );
			}

			return $this->save_content_dob( $cid, $dob, 1 );
		}

		/**
		 * Type 3 - order obj
		 * Order doesn't contain the cid
		 */
		if ( 3 === intval( $type ) ) {
			/** @var WC_Order $value */
			$email = $value->get_billing_email();
			if ( empty( $email ) || ! is_email( $email ) ) {
				return false;
			}
			$ins = WooFunnels_DB_Updater::get_instance();
			$ins->woofunnels_wc_order_create_contact( $value->get_id(), [], $value );

			$order = wc_get_order( $value->get_id() );
			if ( ! $order instanceof WC_Order ) {
				return false;
			}

			$cid = $order->get_meta( '_woofunnel_cid' );
			if ( empty( $cid ) ) {
				return false;
			}

			return $this->save_content_dob( $cid, $dob, 1 );
		}

		return false;
	}

	/**
	 * @param $crm_contact BWFCRM_Contact
	 * @param $dob
	 *
	 * @return bool
	 */
	protected function save_dob( $crm_contact, $dob ) {
		try {
			$crm_contact->set_field_by_slug( 'dob', $dob );
			$crm_contact->save_fields();
		} catch ( Error $e ) {
			return false;
		}

		return true;
	}

	public static function get_instance() {
		if ( is_null( self::$ins ) ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Validate Birthday field if required on wc checkout
	 *
	 * @param $fields
	 * @param $errors
	 *
	 * @return void
	 */
	public function validate_birthday( $fields, $errors ) {
		$global_settings = BWFAN_Common::get_global_settings();
		if ( ! $global_settings['bwfan_dob_field'] ) {
			return;
		}
		$required = $global_settings['bwfan_dob_required'] ?? false;

		/** Check if funnelkit checkout */
		$wfacp_id = $_POST['_wfacp_post_id'] ?? 0;
		$fields   = class_exists( 'WFACP_Common' ) ? WFACP_Common::get_advanced_fields() : [];

		// Check if required based on global settings or checkout field settings
		if ( $wfacp_id > 0 && isset( $fields['bwfan_birthday_date'] ) ) {
			return;
		}

		$birthday_label = ! empty( $global_settings['bwfan_birthday_field_label'] ) ? $global_settings['bwfan_birthday_field_label'] : __( 'Date of Birth', 'wp-marketing-automations-pro' );
		$month          = filter_input( INPUT_POST, 'bwfan_birthday_date_mm' );
		$day            = filter_input( INPUT_POST, 'bwfan_birthday_date_dd' );
		$year           = filter_input( INPUT_POST, 'bwfan_birthday_date_yy' );

		// If the field is not required and no date components are provided, return early
		if ( ! $required && empty( $month ) && empty( $day ) && empty( $year ) ) {
			return;
		}

		// If the field is required, check that all components are provided
		if ( $required && ( empty( $month ) || empty( $day ) || empty( $year ) ) && empty( $global_settings['bwfan_hide_dob_field'] ) ) {
			$errors->add( 'validation', '<strong>' . $birthday_label . '</strong> ' . __( 'is a required field.', 'wp-marketing-automations-pro' ) );

			return;
		}

		if ( ! empty( $month ) && ! empty( $day ) && ! empty( $year ) ) {
			// Validate the date format
			if ( ! checkdate( $month, $day, $year ) ) {
				$errors->add( 'validation', ' <strong>' . $birthday_label . '</strong> : ' . __( 'Please enter a valid date.', 'wp-marketing-automations-pro' ) );

				return;
			}

			// Check for future date
			$birthday_date = $year . '-' . $month . '-' . $day;
			$selected_date = new DateTime( $birthday_date );
			$current_date  = new DateTime( 'today' );

			if ( $selected_date > $current_date ) {
				$errors->add( 'validation', ' <strong>' . $birthday_label . '</strong> ' . __( 'cannot be in the future. Please enter a valid date.', 'wp-marketing-automations-pro' ) );
			}
		}
	}

	/**
	 * Add field width options in page editors for FK checkout
	 *
	 * @param $options
	 * @param $field
	 * @param $class_list
	 *
	 * @return mixed
	 */
	public function add_field_width_option( $options, $field, $class_list ) {
		if ( ! isset( $field['id'] ) || 'bwfan_birthday_date' !== $field['id'] ) {
			return $options;
		}

		return $class_list;
	}

	/**
	 * @param $field
	 * @param $field_key
	 *
	 * @return mixed
	 */
	public function display_dob_wfacp_field( $field, $field_key ) {
		if ( 'wfacp_bwfan_birthday_date' !== $field_key ) {
			return $field;
		}

		$order = wc_get_order( intval( filter_input( INPUT_GET, 'id' ) ) );
		if ( ! $order instanceof WC_Order ) {
			return $field;
		}

		$wfacp_id        = wfacp_get_order_meta( $order, '_wfacp_post_id' );
		$c_fields        = WFACP_Common::get_checkout_fields( $wfacp_id );
		$advanced_fields = isset( $c_fields['advanced'] ) ? $c_fields['advanced'] : [];
		if ( empty( $advanced_fields ) || ! isset( $advanced_fields['bwfan_birthday_date'] ) ) {
			return $field;
		}

		$field['type'] = "text";

		return $field;
	}
}

if ( bwfan_is_woocommerce_active() || bwfan_is_funnel_active() ) {
	BWFAN_Birthday::get_instance();
}
