<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
if( isset( $args->organizers ) && !empty( $args->organizers ) ) {?>
    <?php
    switch ( $args->display_style ) {
        case 'card':
        case 'grid':
            $organizers_card_file = $ep_requests->eventprime_get_ep_theme('organizers/card');
            include $organizers_card_file;
            break;

        case 'box': 
        case 'colored_grid':
            $organizers_box_file = $ep_requests->eventprime_get_ep_theme('organizers/box');
            include $organizers_box_file;
            break;

        case 'list': 
        case 'rows':
            $organizers_list_file = $ep_requests->eventprime_get_ep_theme('organizers/list');
            include $organizers_list_file;
            break;

        default: 
            $organizers_default_file = $ep_requests->eventprime_get_ep_theme('organizers/card');
            include $organizers_default_file;
            break;
    }    
} else{?>
    <div class="ep-alert-warning ep-alert-info">
        <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No organizers found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'No organizers found.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
}?>