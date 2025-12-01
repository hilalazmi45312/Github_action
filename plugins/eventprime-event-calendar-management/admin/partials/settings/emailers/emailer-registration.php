<?php
$global_settings = new Eventprime_Global_Settings;
$global_options = $global_settings->ep_get_settings();
$ep_functions = new Eventprime_Basic_Functions;
$sub_options = $global_settings->sub_options;
?>
<table class="form-table">
        <tbody>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="registration_email_subject">
                        <?php esc_html_e( 'Subject', 'eventprime-event-calendar-management' );?><span class="ep-required">*</span>
                    </label>
                </th>
                <td class="forminp forminp-text">
                    <input name="registration_email_subject" id="registration_email_subject" class="regular-text" type="text" value="<?php echo isset($global_options->registration_email_subject) ? esc_attr($global_options->registration_email_subject) : esc_html__('Register successfully', 'eventprime-event-calendar-management');?>" required>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="em_payment_test_mode">
                        <?php esc_html_e( 'Contents', 'eventprime-event-calendar-management' );?><span class="ep-required">*</span>
                    </label>
                </th>
                <td class="forminp forminp-text">
                    <?php 
                    $content = isset($global_options->registration_email_content) ? $global_options->registration_email_content : '';
                    wp_editor( $content, 'registration_email_content' );?>
                </td>
            </tr>
        </tbody>
    </table>