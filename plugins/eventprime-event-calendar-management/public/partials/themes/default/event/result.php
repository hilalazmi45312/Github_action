<?php
$ep_functions = new Eventprime_Basic_Functions;

$event_id = ! empty( $args->event->em_id ) ? absint( $args->event->em_id ) : 0;
if ( ! $event_id ) { return; }

$ep_select_result_page = get_post_meta( $event_id, 'ep_select_result_page', true );
if ( empty( $ep_select_result_page ) ) { return; }

$ep_result_page_link = get_page_link( $ep_select_result_page );
if ( empty( $ep_result_page_link ) ) { return; }

$now = (int) $ep_functions->ep_get_current_timestamp(); // site-local "now"
$tz  = wp_timezone(); // site timezone

// Robust parser for custom date + time (time like "11:34 AM")
$parse_custom_dt = static function( $date_meta, $time_meta, $tz ) : int {
    // Normalize date to Y-m-d string
    if ( is_numeric( $date_meta ) ) {
        $date_str = ( new DateTimeImmutable( '@' . (int) $date_meta ) )->setTimezone( $tz )->format( 'Y-m-d' );
    } else {
        $date_str = trim( (string) $date_meta );
    }

    $time_str = trim( (string) ( $time_meta ?: '00:00' ) );

    // Try common 12h and 24h patterns
    $patterns = [
        'Y-m-d g:i A', // 1:05 PM
        'Y-m-d h:i A', // 01:05 PM
        'Y-m-d g:i a', // 1:05 pm
        'Y-m-d h:i a', // 01:05 pm
        'Y-m-d H:i',   // 13:05
    ];

    foreach ( $patterns as $p ) {
        $dt = DateTimeImmutable::createFromFormat( $p, $date_str . ' ' . $time_str, $tz );
        if ( $dt instanceof DateTimeImmutable ) {
            // Ensure no fatal parse warnings (minute mismatches etc.)
            $errs = DateTimeImmutable::getLastErrors();
            if ( empty( $errs['warning_count'] ) && empty( $errs['error_count'] ) ) {
                return $dt->getTimestamp();
            }
        }
    }

    // Lenient fallback using the site timezone
    try {
        $dt = new DateTimeImmutable( $date_str . ' ' . $time_str, $tz );
        return $dt->getTimestamp();
    } catch ( Exception $e ) {
        return 0;
    }
};

// Precomputed event timestamps
$event_start_ts = (int) get_post_meta( $event_id, 'em_start_date_time', true );
$event_end_ts   = (int) get_post_meta( $event_id, 'em_end_date_time', true );

$show_event_result = 0; // hidden until condition passes

$from_type = get_post_meta( $event_id, 'ep_result_start_from_type', true );

if ( $from_type === 'custom_date' ) {
    $d = get_post_meta( $event_id, 'ep_result_start_date', true );   // timestamp or Y-m-d
    $t = get_post_meta( $event_id, 'ep_result_start_time', true );   // e.g., "11:34 AM"
    $start_ts = $parse_custom_dt( $d, $t, $tz );

    if ( $start_ts && $now >= $start_ts ) {
        $show_event_result = 1;
    }

} elseif ( $from_type === 'event_date' ) {
    $opt        = get_post_meta( $event_id, 'ep_result_start_event_option', true ); // 'event_start' | 'event_ends'
    $trigger_ts = ( $opt === 'event_ends' ) ? $event_end_ts : $event_start_ts;

    if ( $trigger_ts && $now >= $trigger_ts ) {
        $show_event_result = 1;
    }

} elseif ( $from_type === 'relative_date' ) {
    $days      = absint( get_post_meta( $event_id, 'ep_result_start_days', true ) );
    $when      = get_post_meta( $event_id, 'ep_result_start_days_option', true );  // 'before' | 'after'
    $event_opt = get_post_meta( $event_id, 'ep_result_start_event_option', true ); // 'event_start' | 'event_ends'

    $base_ts = ( $event_opt === 'event_ends' ) ? $event_end_ts : $event_start_ts;
    if ( $base_ts ) {
        $sign      = ( $when === 'after' ) ? 1 : -1;
        $min_start = $base_ts + ( $sign * $days * DAY_IN_SECONDS );
        if ( $now >= $min_start ) {
            $show_event_result = 1;
        }
    }

} else {
    // Fallback: show
    $show_event_result = 1;
}

$event_detail_message_for_recap = $ep_functions->ep_get_global_settings( 'event_detail_message_for_recap' );
if ( empty( $event_detail_message_for_recap ) ) {
    $event_detail_message_for_recap = 'Please click here to check results for this event';
}

if ( $show_event_result ) : ?>
    <div class="ep-box-col-12 ep-mb-3">
        <div class="ep-box-row ep-event-detail-result-container ep-p-2 ep-bg-light-green ep-bg-opacity-10 ep-rounded-1 ep-border-1 ep-border-green ep-align-items-center">
            <div class="ep-box-col-10 ep-d-flex ep-flex-column ep-my-1">
                <span class="ep-fw-bold ep-fs-6"><?php esc_html_e( 'Results', 'eventprime-event-calendar-management' ); ?></span>
                <span><?php echo wp_kses_post( $event_detail_message_for_recap ); ?></span>
            </div>
            <div class="ep-box-col-2 ">
                <a href="<?php echo esc_url( $ep_result_page_link ); ?>">
                    <div class="ep-btn ep-btn-green ep-box-w-100 ep-my-0 ep-py-2">
                        <span class="ep-fw-bold ep-text-small"><?php esc_html_e( 'View Results', 'eventprime-event-calendar-management' ); ?></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
