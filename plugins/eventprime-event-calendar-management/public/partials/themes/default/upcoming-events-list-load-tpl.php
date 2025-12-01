<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
if( isset( $args->events->posts ) && ! empty( $args->events->posts ) && count( $args->events->posts ) > 0 ) {?>
    <?php
    switch ( $args->event_args['event_style']) {
        case 'card':
        case 'grid':
            $upcoming_card_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/card');
            include $upcoming_card_file;
            break;

        case 'mini-list':
        case 'plain_list':
            $upcoming_mini_list_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/mini-list');
            include $upcoming_mini_list_file;
            break;

        case 'list':
        case 'rows': 
            $upcoming_list_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/list');
            include $upcoming_list_file;
            break;

        default: 
            $upcoming_default_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/mini-list');
            include $upcoming_default_file;
            break;

    }
}
