<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;

if( isset( $args->performers ) && !empty( $args->performers ) ) {?>
    <?php
    switch ( $args->display_style ) {
        case 'card':
        case 'grid':
            $card_view_file = $ep_requests->eventprime_get_ep_theme('performers/card');
            include $card_view_file;
            break;
        case 'box': 
        case 'colored_grid':
            $box_view_file = $ep_requests->eventprime_get_ep_theme('performers/box');
            include $box_view_file;
            break;
        case 'list':
        case 'rows': 
            $list_view_file = $ep_requests->eventprime_get_ep_theme('performers/list');
            include $list_view_file;
            break;
        default: 
            $card_view_file = $ep_requests->eventprime_get_ep_theme('performers/card');
            include $card_view_file;
            break;
    }?>
    </div><?php
} else{?>
    <div class="ep-alert-warning ep-alert-info">
        <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No performers found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'No performers found.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
}?>