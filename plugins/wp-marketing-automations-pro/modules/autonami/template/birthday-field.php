<?php
defined( 'ABSPATH' ) || exit;

global $wp_locale;
$global_settings = BWFAN_Common::get_global_settings();
$required        = ! empty( $wfacp_id ) || ! isset( $global_settings['bwfan_dob_required'] ) ? $required : $global_settings['bwfan_dob_required'];

if ( empty( $birthday_label ) ) {
	$birthday_label = ! empty( $global_settings['bwfan_birthday_field_label'] ) ? $global_settings['bwfan_birthday_field_label'] : __( 'Date of Birth', 'wp-marketing-automations-pro' );
}

$birthday_date   = '';
$selected_date   = '';
$selected_month  = '';
$selected_year   = '';
$plugin_version  = defined( 'BWFAN_VERSION' ) ? BWFAN_VERSION : '0.0.0';
$use_dropdown_ui = version_compare( $plugin_version, '3.5.3', '>' );

if ( is_user_logged_in() ) {
	$user    = get_user_by( 'id', get_current_user_id() );
	$contact = new BWFCRM_Contact( $user->user_email );
	/** Get DOB from contact */
	$dob = $contact->get_field_by_slug( 'dob' );
	/** Get Dob from user meta */
	$user_dob      = get_user_meta( get_current_user_id(), 'bwfan_birthday_date', true );
	$birthday_date = empty( $dob ) ? $user_dob : $dob;

	if ( ! empty( $birthday_date ) ) {
		$date_parts = explode( '-', $birthday_date );
		if ( count( $date_parts ) === 3 ) {
			$selected_year  = $date_parts[0];
			$selected_month = $date_parts[1];
			$selected_date  = $date_parts[2];
		}
	}

	$hide_field = ! empty( $global_settings['bwfan_hide_dob_field'] ) && ! empty( $birthday_date );

	if ( is_account_page() && ! empty( $hide_field ) ) {
		BWFAN_Birthday::show_birthday_on_my_profile( $user );
	}

	// If field should be hidden on account page, don't display anything
	if ( $hide_field ) {
		return;
	}

	if ( ! $use_dropdown_ui ) {
		$max = BWFAN_PRO_Common::get_birthday_max_value();
		$min = BWFAN_PRO_Common::get_birthday_min_value();

		$input_args  = 'type=date';
		$placeholder = '';
		if ( 'wfacp-modern-label' === $label_position ) {
			if ( empty( $birthday_date ) ) {
				$input_args = 'type=text';
			}
			$input_args  .= ' onfocus="(this.type=\'date\')" onblur="(this.value == \'\' ? this.type=\'text\' : \'\')"';
			$placeholder = ! empty( $placeholder_label ) ? $placeholder_label : $birthday_label;
		}
	}

}

/** If year, month and day are empty */
if ( empty( $selected_year ) && empty( $selected_date ) && empty( $selected_month ) && ! is_null( WC()->session ) ) {
	$selected_year  = WC()->session->get( 'bwfan_birthday_date_yy', '' );
	$selected_date  = WC()->session->get( 'bwfan_birthday_date_dd', '' );
	$selected_month = WC()->session->get( 'bwfan_birthday_date_mm', '' );
}

$dob_class = '';
if ( $use_dropdown_ui ) {
	$dob_class = 'bwfan-dob-wrapper';
}

if ( empty( $wfacp_id ) && ! empty( $required ) ) {
	$class .= " validate-required";
}
?>
<div class="bwfan-birthday-section bwfan-birthday-section--checkout form-row form-row-wide <?php echo $label_position; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
    <p class="form-row form-row-wide wfacp-anim-wrap <?php echo $class; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" id="bwfan_birthday_field">
        <label for="bwfan_birthday_date" class="<?php echo $label_class; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> bwfan-birthday-label"><?php echo $birthday_label; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( ! empty( $required ) ) { ?>
                <abbr class="required" title="required">*</abbr>
			<?php } ?>
        </label>
        <span class="woocommerce-input-wrapper <?php echo $dob_class; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	        <?php
	        if ( $use_dropdown_ui ) :
		        ?>
                <select name="bwfan_birthday_date_mm" id="bwfan_birthday_date_mm" class="bwfan-dob-field bwf-wid-90 wfacp_dob wfacp_month wfacp-form-control" data-field="<?php echo esc_attr( $birthday_label ); ?>" data-label="month">
                <option value=""><?php esc_html_e( 'Select Month', 'wp-marketing-automations-pro' ); ?></option>
                <?php foreach ( BWFAN_Birthday::get_date_select_options( 'month' ) as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_month, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
                <select name="bwfan_birthday_date_dd" id="bwfan_birthday_date_dd" class="bwfan-dob-field bwf-wid-90 wfacp_dob wfacp_da wfacp-form-control" data-field="<?php echo esc_attr( $birthday_label ); ?>" data-label="day">
                <option value=""><?php esc_html_e( 'Select Day', 'wp-marketing-automations-pro' ); ?></option>
                <?php foreach ( BWFAN_Birthday::get_date_select_options( 'date' ) as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_date, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
                <select name="bwfan_birthday_date_yy" id="bwfan_birthday_date_yy" class="bwfan-dob-field bwf-wid-90 wfacp_dob wfacp_year wfacp-form-control" data-field="<?php echo esc_attr( $birthday_label ); ?>" data-label="year">
                <option value=""><?php esc_html_e( 'Select Year', 'wp-marketing-automations-pro' ); ?></option>
                <?php foreach ( BWFAN_Birthday::get_date_select_options( 'year' ) as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_year, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
	        <?php else : ?>
                <input <?php echo esc_attr( $input_args ) ?> class="input-text <?php echo esc_attr( $input_class ) ?>" name="bwfan_birthday_date" id="bwfan_birthday_date" placeholder="<?php echo esc_html( $placeholder ) ?>" value="<?php echo esc_sql( $birthday_date ) ?>" <?php echo $max; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo $min; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> >
	        <?php endif; ?>
        </span>
    </p>
</div>