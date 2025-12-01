<?php
$ep_functions = new Eventprime_Basic_Functions;
$global_settings = $ep_functions->ep_get_global_settings();
?>

<div class="ep-box-row">
    <div class="ep-box-col-12 ep-border-left ep-border-3 ep-ps-3 ep-border-warning ep-mb-4">
        <span class="ep-text-uppercase ep-fw-bold ep-text-small"><?php esc_html_e( 'My Data', 'eventprime-event-calendar-management' ); ?></span>
    </div>
</div>

<div class="ep-box-row ep-mt-4 ep-gdpr-actions-wrap">
    <?php if ( $global_settings->enable_gdpr_tools && $global_settings->enable_gdpr_delete ) : ?>
        <div class="ep-box-col-6 ep-box-col-md-3 ep-gdpr-action-box ep-mb-4">
            <div class="ep-border ep-rounded ep-p-3">
                <div class="ep-fw-bold ep-mb-1"><?php esc_html_e( 'Delete Bookings Data', 'eventprime-event-calendar-management' ); ?></div>
                <p class="ep-text-small ep-mb-2"><?php esc_html_e( 'Instantly delete your EventPrime bookings data.', 'eventprime-event-calendar-management' ); ?></p>
                <button id="ep_delete_my_data" class="ep-btn ep-btn-danger ep-btn-sm"><?php esc_html_e( 'Delete', 'eventprime-event-calendar-management' ); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( $global_settings->enable_gdpr_tools && $global_settings->enable_gdpr_delete_request ) : ?>
        <div class="ep-box-col-6 ep-box-col-md-3 ep-gdpr-action-box ep-mb-4">
            <div class="ep-border ep-rounded ep-p-3">
                <div class="ep-fw-bold ep-mb-1"><?php esc_html_e( 'Request Delete All Data', 'eventprime-event-calendar-management' ); ?></div>
                <p class="ep-text-small ep-mb-2"><?php esc_html_e( 'Submit a request to remove all your data.', 'eventprime-event-calendar-management' ); ?></p>
                <button id="ep_request_data_erasure" class="ep-btn ep-btn-primary ep-btn-sm"><?php esc_html_e( 'Request Deletion', 'eventprime-event-calendar-management' ); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( $global_settings->enable_gdpr_tools && $global_settings->enable_gdpr_download ) : ?>
        <div class="ep-box-col-6 ep-box-col-md-3 ep-gdpr-action-box ep-mb-4">
            <div class="ep-border ep-rounded ep-p-3">
                <div class="ep-fw-bold ep-mb-1"><?php esc_html_e( 'Export Bookings Data', 'eventprime-event-calendar-management' ); ?></div>
                <p class="ep-text-small ep-mb-2"><?php esc_html_e( 'Download a file with your bookings data.', 'eventprime-event-calendar-management' ); ?></p>
                <button id="ep_download_gdpr_privacy_data" class="ep-btn ep-btn-primary ep-btn-sm"><?php esc_html_e( 'Download', 'eventprime-event-calendar-management' ); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( $global_settings->enable_gdpr_tools && $global_settings->enable_gdpr_download_request ) : ?>
        <div class="ep-box-col-6 ep-box-col-md-3 ep-gdpr-action-box ep-mb-4">
            <div class="ep-border ep-rounded ep-p-3">
                <div class="ep-fw-bold ep-mb-1"><?php esc_html_e( 'Request Export All Data', 'eventprime-event-calendar-management' ); ?></div>
                <p class="ep-text-small ep-mb-2"><?php esc_html_e( 'Submit a request to export all your data.', 'eventprime-event-calendar-management' ); ?></p>
                <button id="ep_request_data_export" class="ep-btn ep-btn-primary ep-btn-sm"><?php esc_html_e( 'Request Export', 'eventprime-event-calendar-management' ); ?></button>
            </div>
        </div>
    <?php endif; ?>

</div>
