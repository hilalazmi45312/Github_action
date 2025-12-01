<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
?>
<?php
if( isset( $args->event_types ) && !empty( $args->event_types ) ) {?>
    <?php
    switch ( $args->display_style ) {
        case 'card':
        case 'grid':
            $card_file = $ep_requests->eventprime_get_ep_theme('event_types/card');
            include $card_file;
            break;
        case 'box':
        case 'colored_grid':
            $box_file = $ep_requests->eventprime_get_ep_theme('event_types/box');
            include $box_file;
            break;
        case 'list':
        case 'rows':
            $list_file = $ep_requests->eventprime_get_ep_theme('event_types/list');
            include $list_file;
            break;
        default: 
            $card_file = $ep_requests->eventprime_get_ep_theme('event_types/card');
            include $card_file; // Loading card view by default
            break;
    }
}?>
