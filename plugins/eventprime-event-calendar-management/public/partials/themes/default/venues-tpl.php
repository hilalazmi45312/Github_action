<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
?>
<div class="ep-event-venues-container ep-mb-5" id="ep-event-venues-container">
    <?php if( isset( $args->enable_search ) && $args->enable_search == 1 ) {
    $search_keyword = '';
    if( isset( $_GET['keyword'] ) && ! empty( $_GET['keyword'] ) ) {
        $search_keyword = sanitize_text_field( $_GET['keyword'] );
    }?>
    <form id="ep_venue_search_form" class="ep-box-wrap ep-box-search-form ep-box-bottom ep-mb-4" name="ep_venue_search_form" action="">
        <div class="ep-bg-light ep-border ep-rounded ep-px-3">
            <div class="ep-box-row">
                <div class="ep-box-col-8 ep-p-3 ep-position-relative">
                    <div class="ep-input-group">
                        <span class="ep-input-group-text ep-bg-white ep-text-muted">
                            <span class="material-icons-outlined">search</span>
                        </span>
                        <input type="hidden" name="ep_search" value="1" />
                        <input placeholder="<?php esc_attr_e( 'Keyword', 'eventprime-event-calendar-management' ); ?>" class="ep-form-control ep-form-control-sm ep-border-start-0" type="text" name="keyword" id="ep_keyword" value="<?php echo esc_attr( $search_keyword ); ?>" />  
                        <input class="ep-btn ep-btn-dark ep-btn-sm" type="submit" value="<?php esc_attr_e( 'Search', 'eventprime-event-calendar-management' ); ?>"/>
                    </div>
                </div>
                <div class="ep-box-col-4 ep-event-filter-block ep-text-item-right ep-d-inline-flex ep-align-items-center">
                    <?php if ( isset( $_GET['ep_search'] ) && ! empty( $_GET['ep_search'] ) && 1 == absint( $_GET['ep_search'] ) && !empty($search_keyword) ) {
                        $venues_page_url = get_permalink( $ep_functions->ep_get_global_settings( 'venues' ) );?>
                        <div class="ep-box-filter-search-buttons">
                            <a href="<?php echo esc_url( $venues_page_url );?>">
                                <?php esc_html_e( 'Clear', 'eventprime-event-calendar-management' ); ?>
                            </a>   
                        </div><?php
                    }?>
                </div>
            </div>
        </div>
    </form><?php 
} ?>

    <?php do_action('ep_venues_list_before_content', $args); ?>

    <?php if (isset($args->venues) && !empty($args->venues)) { ?>
        <div class="em_venues dbfl">
            <div class="ep-box-wrap ep-event-venues-<?php echo esc_attr($args->display_style); ?>-container">
                <div id="ep-event-venues-loader-section" class="ep-box-row ep-box-top ep-venue-<?php echo esc_attr($args->display_style); ?>-wrap">
                    <?php
                    switch ($args->display_style) {
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

                             // Loading card view by default
                    }
                    ?>
                </div>
            </div>
        </div><?php } else {
                ?>
        <div class="ep-alert ep-alert-warning ep-mt-3">
            <?php ( isset($_GET['ep_search']) ) ? esc_html_e('No Venue found related to your search.', 'eventprime-event-calendar-management') : esc_html_e('Currently, there are no venue. Please check back later.', 'eventprime-event-calendar-management'); ?>
        </div><?php }
        ?>

    <?php
    // Load performer load more template
    $ep_functions->ep_load_more_html('ep-venues', (object) $pargs);
    do_action('ep_venues_list_after_content', $args);
    ?>
</div>