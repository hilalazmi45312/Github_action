<div class="ep-btn-group ep-overflow-hidden ep-event-views-filter-group" role="Event view Filters">
    <?php if( in_array( 'month', $args->event_views ) || in_array( 'week', $args->event_views ) || in_array( 'day', $args->event_views ) || in_array( 'listweek', $args->event_views ) ) {?>
        <button type="button" class="ep-btn ep-btn-outline-primary ep_event_view_filter <?php if($args->display_style == 'month'){echo 'ep-active-view';}?>" id="ep_event_view_calendar" title="<?php esc_html_e( 'Calendar View', 'eventprime-event-calendar-management' ); ?>" data-event_view="<?php echo esc_attr( 'month' );?>">
            <span class="material-icons-outlined ep-fs-6"> <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 20 20" height="18px" viewBox="0 0 20 20" width="18px" fill="#000000" class="ep_calendar_month ep-btn-text-fill-color"><g><rect fill="none" height="20" width="20" x="0"/></g><g><path d="M15.5,4H14V2h-1.5v2h-5V2H6v2H4.5C3.67,4,3,4.68,3,5.5v11C3,17.32,3.67,18,4.5,18h11c0.83,0,1.5-0.68,1.5-1.5v-11 C17,4.68,16.33,4,15.5,4z M15.5,16.5h-11V9h11V16.5z M15.5,7.5h-11v-2h11V7.5z M7.5,12H6v-1.5h1.5V12z M10.75,12h-1.5v-1.5h1.5V12z M14,12h-1.5v-1.5H14V12z M7.5,15H6v-1.5h1.5V15z M10.75,15h-1.5v-1.5h1.5V15z M14,15h-1.5v-1.5H14V15z"/></g></svg></span>
        </button>
    <?php } ?>
    
    <?php if( in_array( 'card', $args->event_views ) || in_array( 'square_grid', $args->event_views ) ) {
        $square_grid = ( in_array( 'card', $args->event_views ) ? 'card' : 'square_grid' );?>
        <button type="button" class="ep-btn ep-btn-outline-primary ep_event_view_filter <?php if( $args->display_style == 'card' || $args->display_style == 'square_grid' ){echo 'ep-active-view';}?>" id="ep_event_view_card" title="<?php esc_html_e( 'Square Grid View', 'eventprime-event-calendar-management' ); ?>" data-event_view="<?php echo esc_attr( $square_grid );?>">
            <span class="material-icons-outlined ep-fs-6"><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="18px" viewBox="0 0 24 24" width="18px" fill="#000000" class="ep_grid_view ep-btn-text-fill-color"><g><rect fill="none" height="24" width="24"/></g><g><g><g><path d="M3,3v8h8V3H3z M9,9H5V5h4V9z M3,13v8h8v-8H3z M9,19H5v-4h4V19z M13,3v8h8V3H13z M19,9h-4V5h4V9z M13,13v8h8v-8H13z M19,19h-4v-4h4V19z"/></g></g></g></svg></span>
        </button>
    <?php } ?>
    
    <?php if( in_array( 'list', $args->event_views ) || in_array( 'rows', $args->event_views ) ) {
        $rows = ( in_array( 'list', $args->event_views ) ? 'list' : 'rows' );?>
        <button type="button" class="ep-btn ep-btn-outline-primary ep_event_view_filter <?php if( $args->display_style == 'list' || $args->display_style == 'rows' ){echo 'ep-active-view';}?>" id="ep_event_view_list" title="<?php esc_html_e( 'Stacked Rows View', 'eventprime-event-calendar-management' ); ?>" data-event_view="<?php echo esc_attr( $rows );?>">
            <span class="material-icons-outlined ep-fs-6"><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 20 20" height="18px" viewBox="0 0 20 20" width="18px" fill="#000000" class="ep_view_agenda ep-btn-text-fill-color"><g><rect fill="none" height="20" width="20" y="0"/></g><g><g><path d="M15.5,3h-11C3.67,3,3,3.67,3,4.5v3C3,8.33,3.67,9,4.5,9h11C16.33,9,17,8.33,17,7.5v-3C17,3.67,16.33,3,15.5,3z M15.5,7.5 h-11v-3h11V7.5z"/><path d="M15.5,11h-11C3.67,11,3,11.67,3,12.5v3C3,16.33,3.67,17,4.5,17h11c0.83,0,1.5-0.67,1.5-1.5v-3C17,11.67,16.33,11,15.5,11z M15.5,15.5h-11v-3h11V15.5z"/></g></g></svg></span>
        </button>
    <?php } ?>
    
    <?php if( in_array( 'masonry', $args->event_views ) || in_array( 'staggered_grid', $args->event_views ) ) {
        $staggered_grid = ( in_array( 'masonry', $args->event_views ) ? 'masonry' : 'staggered_grid' );?>
        <button type="button" class="ep-btn ep-btn-outline-primary ep_event_view_filter <?php if( $args->display_style == 'masonry' || $args->display_style == 'staggered_grid' ){echo 'ep-active-view';}?>" id="ep_event_view_masonry" title="<?php esc_html_e( 'Staggered Grid View', 'eventprime-event-calendar-management' ); ?>" data-event_view="<?php echo esc_attr( $staggered_grid );?>">
            <span class="material-icons-outlined ep-fs-6"> <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 0 24 24" width="18px" fill="#000000" class="ep_dashboard ep-btn-text-fill-color"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 5v2h-4V5h4M9 5v6H5V5h4m10 8v6h-4v-6h4M9 17v2H5v-2h4M21 3h-8v6h8V3zM11 3H3v10h8V3zm10 8h-8v10h8V11zm-10 4H3v6h8v-6z"/></svg></span>
        </button>
    <?php } ?>
    
    <?php if( in_array( 'slider', $args->event_views ) ) {?>
        <button type="button" class="ep-btn ep-btn-outline-primary ep_event_view_filter <?php if($args->display_style == 'slider'){echo 'ep-active-view';}?>" id="ep_event_view_slider" title="<?php esc_html_e( 'Slider View', 'eventprime-event-calendar-management' ); ?>" data-event_view="<?php echo esc_attr( 'slider' );?>">
            <span class="material-icons-outlined ep-fs-6"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 0 24 24" width="18px" fill="#000000"class="ep_panorama_horizontal ep-btn-text-fill-color"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M20 6.54v10.91c-2.6-.77-5.28-1.16-8-1.16s-5.4.39-8 1.16V6.54c2.6.77 5.28 1.16 8 1.16 2.72.01 5.4-.38 8-1.16M21.43 4c-.1 0-.2.02-.31.06C18.18 5.16 15.09 5.7 12 5.7s-6.18-.55-9.12-1.64C2.77 4.02 2.66 4 2.57 4c-.34 0-.57.23-.57.63v14.75c0 .39.23.62.57.62.1 0 .2-.02.31-.06 2.94-1.1 6.03-1.64 9.12-1.64s6.18.55 9.12 1.64c.11.04.21.06.31.06.33 0 .57-.23.57-.63V4.63c0-.4-.24-.63-.57-.63z"/></svg></span>
        </button>
    <?php } ?>
</div>