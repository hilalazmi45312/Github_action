<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
$db_handler = new EP_DBhandler;
$map_key = $ep_functions->ep_get_global_settings('gmap_api_key');
wp_enqueue_script(
    'eventprime-venue',
    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/eventprime-venue.js',
    array( 'jquery' ), EVENTPRIME_VERSION
);
wp_localize_script(
    'eventprime-venue', 
    'ep_frontend', 
    array(
        '_nonce' => wp_create_nonce('ep-frontend-nonce'),
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'is_map_key' =>(!empty($map_key))?true:false
    )
);

$venues_data = array();
$settings                     = new Eventprime_Global_Settings;
$venues_settings              = $settings->ep_get_settings( 'venues' );

$venues_data['display_style'] = isset( $atts['display_style'] ) ? $atts["display_style"] : $venues_settings->venue_display_view;
$venues_data['limit'] = isset( $atts['limit'] ) ? ( empty($atts["limit"]) || !is_numeric($atts["limit"]) ? 10 : $atts["limit"]) : ( !empty( $venues_settings->venue_limit ) ? $venues_settings->venue_limit:10 );
$venues_data['column']        = isset( $atts['cols'] ) && is_numeric( $atts['cols'] ) ? $atts['cols'] : $venues_settings->venue_no_of_columns;
$venues_data['cols']          = isset( $atts['cols'] ) && is_numeric( $atts['cols'] ) ? $ep_functions->ep_check_column_size( $atts['cols'] ) : $ep_functions->ep_check_column_size( $venues_settings->venue_no_of_columns );
$venues_data['load_more']     = isset( $atts['load_more'] ) ? $atts['load_more'] : $venues_settings->venue_load_more;
$venues_data['enable_search'] = isset( $atts['search'] ) ? $atts['search'] : $venues_settings->venue_search;
$venues_data['featured']      = isset( $atts["featured"] ) ? $atts["featured"] : 0;
$venues_data['popular']       = isset( $atts["popular"] ) ? $atts["popular"] : 0;
$order                               = isset( $atts["order"] ) ? $atts["order"] : 'desc';
$orderby                             = isset( $atts["orderby"] ) ? $atts["orderby"] : 'term_id';
$venues_data['box_color'] = '';
if( $venues_data['display_style'] == 'box' || $venues_data['display_style'] == 'colored_grid' ) {
    $venues_data['box_color'] = ( isset( $atts["venue_box_color"] ) && ! empty( $atts["venue_box_color"] ) ) ? $atts["venue_box_color"] : $venues_settings->venue_box_color;
    $venues_data['colorbox_start'] = 1;
}

// Set query arguments
$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
$venues_data['paged'] = $paged;
$ep_search = isset( $_GET['ep_search'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
$pargs = array(
    'orderby'    => $orderby,
    'order'   =>$order,
    'name__like' => $ep_search,
);

if ( $venues_data['featured'] == 1 && ( $venues_data['popular'] == 1 ) ) {
    $pargs['meta_query'] = array(
        'relation' => 'AND',
        array(
           'key'       => 'em_is_featured',
           'value'     => 1,
           'compare'   => '='
        )
    );
    $pargs['orderby'] ='count';
    $pargs['order'] ='DESC';
}

// Get featured event venues
if( $venues_data['featured'] == 1 && ( $venues_data['popular'] == 0 || $venues_data['popular'] == '' ) ){ 
    $pargs['meta_query'] = array(
        'relation' => 'AND',
        array(
           'key'       => 'em_is_featured',
           'value'     => 1,
           'compare'   => '='
        )

    );
}
// Get popular event types
if( $venues_data['popular'] == 1 && ( $venues_data['featured'] == 0 || $venues_data['featured'] == '' ) ){
    $pargs['orderby'] ='count';
    $pargs['order'] ='DESC';
}


$terms_per_page = $pargs['limit'] = $venues_data['limit'];

$venues = $db_handler->ep_get_taxonomy_terms_with_pagination('em_venue', $paged, $terms_per_page, $pargs);
unset($pargs['meta_query']);
$venues_data['venue_count'] = $pargs['total_count'] = $venues['total_terms'];
$venues_data['venues'] = $venues['terms'];
$pargs['load_more'] = $venues_data['load_more'];
$pargs['paged'] = $venues['current_page'];
$pargs['style'] = $venues_data['display_style'];
$pargs['featured'] = $venues_data['featured'];
$pargs['popular'] = $venues_data['popular'];
$pargs['cols'] = $venues_data['column'];
$pargs['box_color'] = '';
$pargs['ep_search'] = $ep_search;


ob_start();
wp_enqueue_style(
    'ep-venue-views-css',
    plugin_dir_url( EP_PLUGIN_FILE )  . 'public/css/ep-frontend-views.css',
    false, EVENTPRIME_VERSION
);
$args = (object)$venues_data;
?>
<div class="emagic">
<?php
$themepath = $ep_requests->eventprime_get_ep_theme('venues-tpl');
include $themepath;
?>
</div>