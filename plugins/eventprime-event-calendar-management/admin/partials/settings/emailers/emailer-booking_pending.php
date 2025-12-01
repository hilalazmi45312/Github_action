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
                    <label for="send_booking_pending_email">
                        <?php esc_html_e( 'Enable/Disable', 'eventprime-event-calendar-management' );?>
                    </label>
                </th>
                <td class="forminp forminp-text">
                    <label class="ep-toggle-btn">
                        <input name="send_booking_pending_email" id="send_booking_pending_email" type="checkbox" value="1" <?php echo isset($global_options->send_booking_pending_email ) && $global_options->send_booking_pending_email == 1 ? 'checked' : '';?>>
                        <span class="ep-toogle-slider round"></span>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="booking_pending_email_subject">
                        <?php esc_html_e( 'Subject', 'eventprime-event-calendar-management' );?><span class="ep-required">*</span>
                    </label>
                </th>
                <td class="forminp forminp-text">
                    <input name="booking_pending_email_subject" class="regular-text" id="booking_pending_email_subject" type="text" value="<?php echo isset($global_options->booking_pending_email_subject) ? esc_attr($global_options->booking_pending_email_subject) : esc_html__('Your booking payment is pending', 'eventprime-event-calendar-management');?>" required>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="booking_pending_email">
                        <?php esc_html_e( 'Contents', 'eventprime-event-calendar-management' );?><span class="ep-required">*</span>
                    </label>
                </th>
                <td class="forminp forminp-text">
                    <?php 
                    add_action( 'media_buttons', array( $this, 'ep_fields_list_for_email' ) );
                    $content = isset($global_options->booking_pending_email) ? $global_options->booking_pending_email : '';
                    wp_editor( $content, 'booking_pending_email' );?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="booking_pending_email_cc">
                        <?php esc_html_e( 'Email CC', 'eventprime-event-calendar-management' );?>
                    </label>
                </th>
                <td class="forminp forminp-text">
                    <input name="booking_pending_email_cc" class="regular-text" id="booking_pending_email_cc" type="text" value="<?php echo isset($global_options->booking_pending_email_cc) ? esc_attr($global_options->booking_pending_email_cc) : '';?>">
                </td>
            </tr>
        </tbody>
    </table>