<?php
/**
 * Booking meta box html
 */
defined( 'ABSPATH' ) || exit;
$html_generator = new Eventprime_html_Generator();
$booking_id = $post->ID;
?>
<div class="panel-wrap ep_event_metabox">
    <?php $html_generator->eventprime_booking_details_html($booking_id); ?>
</div>
