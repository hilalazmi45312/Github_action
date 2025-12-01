<?php 
$ep_functions = new Eventprime_Basic_Functions;
$global_settings = $ep_functions->ep_get_global_settings();

if ( isset($global_settings->enable_gdpr_tools) && $global_settings->enable_gdpr_tools ==1 && isset($global_settings->show_gdpr_badge) && $global_settings->show_gdpr_badge==1) : ?>

<div id="ep-gdpr-badge" data-ep-modal-open="ep-gdpr-modal" class="ep-modal-open" style="display:inline;position:fixed;bottom:20px;right:20px;z-index:999999; font-size: 7px;">
    <div class="ep-gdpr-badge is-small w-inline-block">
        <div class="ep-gdpr-badge-icon-wrap">
            <img src="https://cdn.prod.website-files.com/64f78545d9d88206efb786f4/64f789838ff0813757f535df_Lock.svg" loading="lazy" alt="" class="badge-icon">
        </div>
        <div class="ep-gdpr-badge-text">
            <div class="ep-gdpr-badge-bold"> <?php esc_html_e( 'GDPR', 'eventprime-event-calendar-management' ); ?></div>
            <div><?php esc_html_e( 'COMPLIANT', 'eventprime-event-calendar-management' ); ?></div>  
        </div>
    </div>
</div>

<div id="ep_gdpr_modal"  class="ep-modal ep-modal-view ep_gdpr_modal" ep-modal="ep_gdpr_modal" style="display:none"  >
    <div class="ep-modal-overlay" ep-modal-close="ep_gdpr_modal"></div>
    <div class="ep-modal-dialog ep-modal-dialog-centered">
        <div class="ep-modal-content">
            <div class="ep-modal-body"> 
                <div class="ep_gdpr_modal_wrap">
                    <h3 class="ep-modal-title ep-px-4 ep-pt-3 ep-mt-0 ep-mb-3"><?php esc_html_e( 'How Your Data Is Handled', 'eventprime-event-calendar-management' ); ?></h3>
                    <a href="#" class="ep-modal-close close-popup" ep-modal-close="ep_gdpr_modal" style="position: absolute;right: 9px;top: 16px;text-align: right;color:#3f3d3d"><span class="material-icons">close</span></a>
                    <ul id="ep-gdpr-modal-content" class="ep-gdpr-modal-content">
                 <?php if ( $global_settings->enable_gdpr_tools ) : ?>
                <li><?php esc_html_e( 'This site is GDPR-compliant. You have access to your data and control over how it is used.', 'eventprime-event-calendar-management' ); ?></li>
                    <?php endif; ?>
                    <?php if ( $global_settings->enable_gdpr_download ) : ?>
                <li><?php esc_html_e( 'You can download a copy of your personal data at any time from your account dashboard.', 'eventprime-event-calendar-management' ); ?> </li>
                    <?php endif; ?>
                    <?php if ( $global_settings->enable_gdpr_delete ) : ?>
                <li><?php esc_html_e( 'You can request deletion of your data directly from your account dashboard.', 'eventprime-event-calendar-management' ); ?></li>
                    <?php endif; ?>
                    <?php if ( $global_settings->show_gdpr_consent_checkbox ) : ?>
                <li><?php esc_html_e( 'We ask for your consent before collecting your data during event bookings.', 'eventprime-event-calendar-management' ); ?></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $global_settings->gdpr_retention_period ) ) : ?>
                <li><?php printf( esc_html__( 'Your data will be automatically anonymized after %d days.', 'eventprime-event-calendar-management' ), $global_settings->gdpr_retention_period ); ?></li>
                    <?php endif; ?>
                    <?php if ( $global_settings->enable_cookie_consent_banner ) : ?>
                <li><?php esc_html_e( 'This site uses cookies and shows a consent banner to comply with cookie regulations.', 'eventprime-event-calendar-management' ); ?></li>
                    <?php endif; ?>
                       </ul> 
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif;
