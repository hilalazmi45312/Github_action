<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
?>

<?php
if( isset( $args->venues ) && !empty( $args->venues ) ) {?>
    <?php
    switch ( $args->display_style ) {
        case 'card':
                        case 'grid':
                            $venues_card_file = $ep_requests->eventprime_get_ep_theme('venues/card');
                            include $venues_card_file;
                            break;

                        case 'box':
                        case 'colored_grid':
                            $venues_box_file = $ep_requests->eventprime_get_ep_theme('venues/box');
                            include $venues_box_file;
                            break;

                        case 'list':
                        case 'rows':
                            $venues_list_file = $ep_requests->eventprime_get_ep_theme('venues/list');
                            include $venues_list_file;
                            break;

                        default:
                            $venues_default_file = $ep_requests->eventprime_get_ep_theme('venues/card');
                            include $venues_default_file;
                            break;
    }
} else{?>
    <div class="ep-alert-warning ep-alert-info">
        <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No Venue found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'No Venue found.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
}?>