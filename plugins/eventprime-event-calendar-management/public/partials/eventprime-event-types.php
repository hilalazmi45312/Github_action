<?php
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
$db_handler = new EP_DBhandler;
wp_enqueue_script(
            'ep-type-views-js',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/em-type-frontend-custom.js',
            array( 'jquery' ), EVENTPRIME_VERSION
        );
        wp_localize_script(
            'ep-type-views-js', 
            'ep_frontend', 
            array(
                '_nonce' => wp_create_nonce('ep-frontend-nonce'),
                'ajaxurl'   => admin_url( 'admin-ajax.php' )
            )
        );
        
        $event_types_data = array();
        $settings                            = new Eventprime_Global_Settings;
        $event_types_settings                = $settings->ep_get_settings( 'event_types' );
        //var_dump(($atts['limit'] ));die;
        $event_types_data['display_style']   = isset( $atts['display_style'] ) ? $atts["display_style"] : $event_types_settings->type_display_view;
        $event_types_data['limit'] = isset( $atts['limit'] ) ? ( empty($atts["limit"]) || !is_numeric($atts["limit"]) ? 10 : $atts["limit"]) : ( empty( $event_types_settings->type_limit ) ? 10 : $event_types_settings->type_limit );
        $event_types_data['column']            = isset( $atts['cols']) && is_numeric($atts['cols'])? $atts['cols'] : $event_types_settings->type_no_of_columns;
        $event_types_data['cols']            = isset( $atts['cols']) && is_numeric($atts['cols']) ? $ep_functions->ep_check_column_size( $atts['cols'] ) : $ep_functions->ep_check_column_size( $event_types_settings->type_no_of_columns );
        $event_types_data['load_more']       = isset( $atts['load_more'] ) ? $atts['load_more'] : $event_types_settings->type_load_more;
        $event_types_data['enable_search']   = isset( $atts['search'] ) ? $atts['search'] : $event_types_settings->type_search;
        $event_types_data['featured']        = isset( $atts["featured"] ) ? $atts["featured"] : 0;
        $event_types_data['popular']         = isset( $atts["popular"] ) ? $atts["popular"] : 0;
        $event_types_data['popular']         = isset( $atts["popular"] ) ? $atts["popular"] : 0;
        $order                               = isset( $atts["order"] ) ? $atts["order"] : 'desc';
        $orderby                             = isset( $atts["orderby"] ) ? $atts["orderby"] : 'term_id';
        $event_types_data['box_color'] = '';
        if( $event_types_data['display_style'] == 'box' || $event_types_data['display_style'] == 'colored_grid' ) {
            $event_types_data['box_color'] = ( isset( $atts["type_box_color"] ) && ! empty( $atts["type_box_color"] ) ) ? $atts["type_box_color"] : $event_types_settings->type_box_color;
            $event_types_data['colorbox_start'] = 1;
        }
        

        // Set query arguments
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
        $event_types_data['paged'] = $paged;
        $ep_search = isset( $_GET['ep_search'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
        $pargs = array(
            'orderby'    => $orderby,
            'order'   =>$order,
            'name__like' => $ep_search,
        );
        
        if ( $event_types_data['featured'] == 1 && ( $event_types_data['popular'] == 1 ) ) {
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
        
        if( $event_types_data['featured'] == 1 && ( $event_types_data['popular'] == 0 || $event_types_data['popular'] == '' ) ){ 
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
        if( $event_types_data['popular'] == 1 && ( $event_types_data['featured'] == 0 || $event_types_data['featured'] == '' ) ){
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
        
        $terms_per_page = $pargs['limit'] = $event_types_data['limit'];
        
        $event_types = $db_handler->ep_get_taxonomy_terms_with_pagination('em_event_type', $paged, $terms_per_page, $pargs);
        unset($pargs['meta_query']);
        $event_types_data['event_types_count'] = $pargs['total_count'] = $event_types['total_terms'];
        $event_types_data['event_types'] = $event_types['terms'];
        $pargs['load_more'] = $event_types_data['load_more'];
        $pargs['paged'] = $event_types['current_page'];
        $pargs['style'] = $event_types_data['display_style'];
        $pargs['featured'] = $event_types_data['featured'];
        $pargs['popular'] = $event_types_data['popular'];
        $pargs['cols'] = $event_types_data['column'];
        $pargs['box_color'] = '';
        $pargs['ep_search'] = $ep_search;
        
        
        //$pargs['total_count'] = $event_types_data['event_types_count'];
        //print_r($event_types);die;
       // $pargs = wp_parse_args( $pargs, $limit_args );
        wp_enqueue_style(
            'ep-event-type-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $args = (object)$event_types_data;
?>
<div class="emagic">
<?php
$themepath = $ep_requests->eventprime_get_ep_theme('event-types-tpl');
include $themepath;
?>
</div>