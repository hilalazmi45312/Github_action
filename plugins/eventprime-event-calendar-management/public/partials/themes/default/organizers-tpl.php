<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
?>
<div class="ep-organizers-container ep-mb-5" id="ep-organizers-container">
<?php if( isset( $args->enable_search ) && $args->enable_search == 1 ) {
$search_keyword = '';
if( isset( $_GET['keyword'] ) && ! empty( $_GET['keyword'] ) ) {
    $search_keyword = sanitize_text_field( $_GET['keyword'] );
}?>
<form id="ep_organizer_search_form" class="ep-box-wrap ep-box-search-form ep-box-bottom ep-mb-4" name="ep_organizer_search_form" action="">
    <div class="ep-bg-light ep-border ep-rounded ep-px-3">
    <div class="ep-box-row">
        <div class="ep-box-col-8 ep-py-3 ep-position-relative">
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
                $organizers_page_url = get_permalink( $ep_functions->ep_get_global_settings('organizers_page') );?>
                <div class="ep-box-filter-search-buttons">
                    <a href="<?php echo esc_url( $organizers_page_url ); ?>">
                        <?php esc_html_e( 'Clear', 'eventprime-event-calendar-management' ); ?>
                    </a>   
                </div>
            <?php }?>
        </div>
    </div>
    </div>
</form><?php 
} ?>
<?php do_action( 'ep_organizers_list_before_content', $args ); ?>

<?php
if( isset( $args->organizers ) && !empty( $args->organizers ) ) {?>
    <div class="em_organizers dbfl">
        <div class="ep-event-organizers-<?php echo esc_attr($args->display_style);?>-container ep-box-wrap">
            <div id="ep-event-organizers-loader-section" class="ep-px-3 ep-box-row ep-box-top ep-organizer-<?php echo esc_attr($args->display_style);?>-wrap ">
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

                }?>
            </div>
        </div>
    </div><?php
} else{?>
    <div class="ep-alert ep-alert-warning ep-mt-3 ep-fs-6">
        <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No organizers found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'Currently, there are no organizer. Please check back later.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
}?>

<?php
$ep_functions->ep_load_more_html('ep-organizers', (object)$pargs);
do_action( 'ep_organizers_list_after_content', $args ); 
?>
</div> 