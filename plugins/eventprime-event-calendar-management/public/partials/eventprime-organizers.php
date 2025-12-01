<?php
$ep_requests = new EP_Requests;
$db_handler = new EP_DBhandler;
$ep_functions = new Eventprime_Basic_Functions;
wp_enqueue_script(
            'ep-organizer-views-js',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/em-organizer-frontend-custom.js',
            array( 'jquery' ), EVENTPRIME_VERSION
        );
        wp_localize_script(
            'ep-organizer-views-js', 
            'ep_frontend', 
            array(
                '_nonce' => wp_create_nonce('ep-frontend-nonce'),
                'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                'organizer_no_of_columns' => $ep_functions->ep_get_global_settings('organizer_no_of_columns')
            )
        );
        $organizers_data = array();
        $settings                           = new Eventprime_Global_Settings;
        $organizers_settings                = $settings->ep_get_settings( 'organizers' );

        $organizers_data['display_style']   = isset( $atts['display_style'] ) ? $atts["display_style"] : $organizers_settings->organizer_display_view;
        $organizers_data['limit']           = isset( $atts['limit'] ) ? ( empty($atts["limit"]) || !is_numeric($atts["limit"]) ? 10 : $atts["limit"]) : (empty($organizers_settings->organizer_limit) ? 10 : $organizers_settings->organizer_limit );
        $organizers_data['column']            = isset( $atts['cols'] ) && is_numeric($atts['cols']) ? $atts['cols']  : $organizers_settings->organizer_no_of_columns;
        $organizers_data['cols']            = isset( $atts['cols'] ) && is_numeric($atts['cols']) ? $ep_functions->ep_check_column_size( $atts['cols'] ) : $ep_functions->ep_check_column_size( $organizers_settings->organizer_no_of_columns );
        $organizers_data['load_more']       = isset( $atts['load_more'] ) ? $atts['load_more'] : $organizers_settings->organizer_load_more;
        $organizers_data['enable_search']   = isset( $atts['search'] ) ? $atts['search'] : $organizers_settings->organizer_search;
        $organizers_data['featured']        = isset( $atts["featured"] ) ? $atts["featured"] : 0;
        $organizers_data['popular']         = isset( $atts["popular"] ) ? $atts["popular"] : 0;
        $organizers_data['box_color'] = '';
        $order                               = isset( $atts["order"] ) ? $atts["order"] : 'desc';
        $orderby                             = isset( $atts["orderby"] ) ? $atts["orderby"] : 'term_id';
        $organizers_data['box_color'] = '';
        if( $organizers_data['display_style'] == 'box' || $organizers_data['display_style'] == 'colored_grid' ) {
            $organizers_data['box_color'] = ( isset( $atts["organizer_box_color"] ) && ! empty( $atts["organizer_box_color"] ) ) ? $atts["organizer_box_color"] : $organizers_settings->organizer_box_color;
            $organizers_data['colorbox_start'] = 1;
        }
       

        // Set query arguments
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
        $organizers_data['paged'] = $paged;
        $ep_search = isset( $_GET['ep_search'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
        
        $pargs = array(
            'orderby'    => $orderby,
            'order'   =>$order,
            'name__like' => $ep_search,
        );

        if ( $organizers_data['featured'] == 1 && ( $organizers_data['popular'] == 1 ) ) {
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

        // get featured event organizers
        if( $organizers_data['featured'] == 1 && ( $organizers_data['popular'] == 0 || $organizers_data['popular'] == '' ) ){ 
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
            );
        }
        
        // Get popular event organizers 
        if( $organizers_data['popular'] == 1 && ( $organizers_data['featured'] == 0 || $organizers_data['featured'] == '' ) ){
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
        
        $terms_per_page = $pargs['limit'] = $organizers_data['limit'];
        
        $organizers = $db_handler->ep_get_taxonomy_terms_with_pagination('em_event_organizer', $paged, $terms_per_page, $pargs);
        unset($pargs['meta_query']);
        $organizers_data['organizers_count'] = $pargs['total_count'] = $organizers['total_terms'];
        $organizers_data['organizers'] = $organizers['terms'];
        $pargs['load_more'] = $organizers_data['load_more'];
        $pargs['paged'] = $organizers['current_page'];
        $pargs['style'] = $organizers_data['display_style'];
        $pargs['featured'] = $organizers_data['featured'];
        $pargs['popular'] = $organizers_data['popular'];
        $pargs['cols'] = $organizers_data['column'];
        $pargs['box_color'] = '';
        $pargs['ep_search'] = $ep_search;
        
//        $organizers_data['organizers_count'] = $ep_functions->get_organizers_count( $pargs, $ep_search, $organizers_data['featured'], $organizers_data['popular'] );
//        $pargs = wp_parse_args( $pargs, $limit_args );
//        $organizers_data['organizers'] = $ep_functions->get_organizers_data( $pargs );

        ob_start();
        wp_enqueue_style(
            'ep-organizer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $args =  (object)$organizers_data;
?>
<div class="emagic">
<?php
$themepath = $ep_requests->eventprime_get_ep_theme('organizers-tpl');
include $themepath;
?>
</div>