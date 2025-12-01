<?php
/**
 * Booking meta box html
 */
defined( 'ABSPATH' ) || exit;
$booking_controller = new EventPrime_Bookings;
$ep_functions = new Eventprime_Basic_Functions;
$booking_id = $post->ID;
$post_meta = get_post_meta( $booking_id );
$booking = $booking_controller->load_booking_detail( $booking_id );
?>

<div class="panel-wrap ep_event_metabox">
    <div class="ep-py-3 ep-ps-3 ep-fw-bold ep-text-uppercase ep-text-small">
        <button type="button" class="button" id="ep_show_booking_transaction_log"><?php esc_html_e( 'Transaction Log', 'eventprime-event-calendar-management' );?></button>
    </div>
    <span class="ep-booking-transaction-log" style="display: none;">
        <?php if(isset($booking->em_payment_log) && !empty($booking->em_payment_log)): 
            
        echo "<pre>";
            echo esc_html( wp_json_encode($booking->em_payment_log, JSON_PRETTY_PRINT ) );
        echo "</pre>";
        endif;
        ?>
    </span>
</div>