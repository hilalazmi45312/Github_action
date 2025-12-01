<?php
/* --------------------------------------------------------- *
 *  GDPR Settings – EventPrime
 * --------------------------------------------------------- */

$global_settings  = new Eventprime_Global_Settings;
 
$global_options   = $global_settings->ep_get_settings();
$privacy_default  = !empty(get_privacy_policy_url()) ? get_privacy_policy_url(): site_url().'/privacy-policy';
       
/* Current values (fallbacks included) */
$enable_gdpr_tools           = ! empty( $global_options->enable_gdpr_tools );
$enable_gdpr_download        = ! empty( $global_options->enable_gdpr_download );
$enable_gdpr_delete          = ! empty( $global_options->enable_gdpr_delete );
$enable_gdpr_download_request      = ! empty( $global_options->enable_gdpr_download_request );
$enable_gdpr_delete_request        = ! empty( $global_options->enable_gdpr_delete_request );
$show_gdpr_consent_checkbox  = ! empty( $global_options->show_gdpr_consent_checkbox );
$gdpr_consent_text           = ! empty( $global_options->gdpr_consent_text )
    ? stripslashes($global_options->gdpr_consent_text)
    : __( "I agree to the site's Privacy Policy.", 'eventprime-event-calendar-management' );
$gdpr_privacy_policy_url     = ! empty( $global_options->gdpr_privacy_policy_url )
    ? $global_options->gdpr_privacy_policy_url
    : $privacy_default;
$gdpr_retention_period       = isset( $global_options->gdpr_retention_period )
    ? esc_attr( $global_options->gdpr_retention_period )
    : '';
?>

<div class="ep-setting-tab-content">
    <h2><?php esc_html_e( 'GDPR', 'eventprime-event-calendar-management' ); ?></h2>
    <input type="hidden" name="em_setting_type" value="gdpr_settings">
</div>

<table class="form-table">
<tbody>

<!-- Enable GDPR Tools -->
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="enable_gdpr_tools"><?php esc_html_e( 'Enable GDPR Tools', 'eventprime-event-calendar-management' ); ?></label>
    </th>
    <td class="forminp">
        <label class="ep-toggle-btn">
            <input name="enable_gdpr_tools" id="enable_gdpr_tools"
                   onclick="ep_hide_show_child_setting(this,'ep_enable_gdpr_tools_child')"
                   type="checkbox" value="1" <?php checked( $enable_gdpr_tools ); ?>>
            <span class="ep-toogle-slider round"></span>
        </label>
        <div class="ep-help-tip-info ep-my-2 ep-text-muted">
            <?php esc_html_e( 'Turns on user data access, export, and deletion tools.', 'eventprime-event-calendar-management' ); ?>
        </div>
    </td>
</tr>

<!-- All child options inside this wrapper will auto-hide if main toggle is off -->
<tr class="ep_enable_gdpr_tools_child" style="<?php echo $enable_gdpr_tools ? '' : 'display:none;'; ?>">
    <td colspan="2" style="padding:0;">
        <table class="form-table" style="margin:0;">
        <tbody>
            
        <!-- Show GDPR Compliance Badge on Frontend Pages -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="show_gdpr_badge">
                    <?php esc_html_e( 'Show GDPR Compliance Badge on Frontend Pages', 'eventprime-event-calendar-management' ); ?>
                </label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="show_gdpr_badge" id="show_gdpr_badge" type="checkbox"
                           value="1" <?php checked( ! empty( $global_options->show_gdpr_badge ) ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Display a GDPR compliance badge on EventPrime frontend pages to inform users that their data is handled responsibly. Clicking the info icon will show GDPR handling details.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>


        <!-- Enable Data Download -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="enable_gdpr_download"><?php esc_html_e( 'Enable Data Download', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="enable_gdpr_download" id="enable_gdpr_download" type="checkbox"
                           value="1" <?php checked( $enable_gdpr_download ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Allows users to download their personal data.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>

        <!-- Enable Data Deletion -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="enable_gdpr_delete"><?php esc_html_e( 'Enable Data Deletion', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="enable_gdpr_delete" id="enable_gdpr_delete" type="checkbox"
                           value="1" <?php checked( $enable_gdpr_delete ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Allows users to deletion of their booking data.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>

          <!-- Enable Data Download -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="enable_gdpr_download_request"><?php esc_html_e( 'Enable Data Download Request', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="enable_gdpr_download_request" id="enable_gdpr_download_request" type="checkbox"
                           value="1" <?php checked( $enable_gdpr_download_request ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Allows users to request to admin for download their personal data .', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>

        <!-- Enable Data Deletion -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="enable_gdpr_delete_request"><?php esc_html_e( 'Enable Data Deletion Requests', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="enable_gdpr_delete_request" id="enable_gdpr_delete_request" type="checkbox"
                           value="1" <?php checked( $enable_gdpr_delete_request ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Allows users to request deletion of their data all data.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>

        
        <!-- Show Consent Checkbox -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="show_gdpr_consent_checkbox"><?php esc_html_e( 'Show Consent Checkbox in Form', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="show_gdpr_consent_checkbox" id="show_gdpr_consent_checkbox" type="checkbox"  onclick="ep_hide_show_child_setting(this,'ep_show_gdpr_consent_checkbox_child')"
                           value="1" <?php checked( $show_gdpr_consent_checkbox ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
            </td>
        </tr>

        <!-- Consent Checkbox Text -->
        <tr valign="top" class="ep_show_gdpr_consent_checkbox_child" style="<?php echo $show_gdpr_consent_checkbox ? '' : 'display:none;'; ?>">
            <th scope="row" class="titledesc">
                <label for="gdpr_consent_text"><?php esc_html_e( 'Consent Checkbox Text', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <input name="gdpr_consent_text" id="gdpr_consent_text" class="regular-text"
                       type="text" value="<?php echo esc_attr( $gdpr_consent_text ); ?>">
            </td>
        </tr>

        <!-- Privacy Policy URL -->
        <tr valign="top" class="ep_show_gdpr_consent_checkbox_child" style="<?php echo $show_gdpr_consent_checkbox ? '' : 'display:none;'; ?>">
            <th scope="row" class="titledesc">
                <label for="gdpr_privacy_policy_url"><?php esc_html_e( 'Privacy Policy URL', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <input name="gdpr_privacy_policy_url" id="gdpr_privacy_policy_url" class="regular-text"
                       type="url" value="<?php echo esc_url( $gdpr_privacy_policy_url ); ?>">
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Defaults to the WordPress privacy policy page if set.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>

        <!-- Data Retention Period -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="gdpr_retention_period"><?php esc_html_e( 'Data Retention Period (days)', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td class="forminp">
                <input name="gdpr_retention_period" id="gdpr_retention_period" class="small-text"
                       type="number" min="1" step="1" value="<?php echo $gdpr_retention_period; ?>">
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Leave blank to disable. This option controls how long EventPrime retains personal data related to bookings before automatically anonymizing it. EventPrime only anonymizes user data stored within its own tables (like bookings and related metadata). For complete removal of user data across your site, it is recommended to combine this with your broader site-wide GDPR compliance workflow.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>
        <?php /* 
        <!-- Enable Cookie Consent Banner -->
        
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="enable_cookie_consent_banner">
                    <?php esc_html_e( 'Enable Cookie Consent Banner', 'eventprime-event-calendar-management' ); ?>
                </label>
            </th>
            <td class="forminp">
                <label class="ep-toggle-btn">
                    <input name="enable_cookie_consent_banner" id="enable_cookie_consent_banner" type="checkbox"
                           value="1" <?php checked( ! empty( $global_options->enable_cookie_consent_banner ) ); ?>>
                    <span class="ep-toogle-slider round"></span>
                </label>
                <div class="ep-help-tip-info ep-my-2 ep-text-muted">
                    <?php esc_html_e( 'Show a cookie consent banner to users visiting EventPrime pages.', 'eventprime-event-calendar-management' ); ?>
                </div>
            </td>
        </tr>

        <!-- Cookie Consent Message -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="cookie_consent_message">
                    <?php esc_html_e( 'Cookie Consent Message', 'eventprime-event-calendar-management' ); ?>
                </label>
            </th>
            <td class="forminp">
                <textarea name="cookie_consent_message" id="cookie_consent_message" class="large-text"><?php echo esc_textarea( isset( $global_options->cookie_consent_message ) ? $global_options->cookie_consent_message : esc_html__('We use cookies to ensure you get the best experience on our website.', 'eventprime-event-calendar-management') ); ?></textarea>
            </td>
        </tr>

        <!-- Cookie Consent Button Text -->
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="cookie_consent_button_text">
                    <?php esc_html_e( 'Cookie Consent Button Text', 'eventprime-event-calendar-management' ); ?>
                </label>
            </th>
            <td class="forminp">
                <input name="cookie_consent_button_text" id="cookie_consent_button_text" class="regular-text"
                       type="text" value="<?php echo esc_attr( isset( $global_options->cookie_consent_button_text ) ? $global_options->cookie_consent_button_text : esc_html__('Accept', 'eventprime-event-calendar-management') ); ?>">
            </td>
        </tr>
         */ ?>

        </tbody>
        </table>
    </td>
</tr>

</tbody>
</table>
