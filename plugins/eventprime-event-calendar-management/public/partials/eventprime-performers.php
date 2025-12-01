<?php
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
wp_enqueue_script(
            'ep-performer-views-js',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/em-performer-frontend-custom.js',
            array( 'jquery' ), EVENTPRIME_VERSION
        );
        wp_localize_script(
            'ep-performer-views-js', 
            'ep_frontend', 
            array(
                '_nonce' => wp_create_nonce('ep-frontend-nonce'),
                'ajaxurl'   => admin_url( 'admin-ajax.php' )
            )
        );
        $settings                           = new Eventprime_Global_Settings;
        $performers_settings                = $settings->ep_get_settings( 'performers' );
        $performers_data                    = array();
        $performers_data['display_style']   = isset( $atts['display_style'] ) ? $atts["display_style"] : $performers_settings->performer_display_view;
        $performers_data['limit']           = isset( $atts['limit'] ) ? ( empty($atts["limit"]) || !is_numeric($atts["limit"]) ? 10 : $atts["limit"]) : ( empty( $performers_settings->performer_limit ) ? 10 : $performers_settings->performer_limit );
        $performers_data['column']          = isset( $atts['cols'] ) && is_numeric($atts['cols']) ? $atts['cols'] : $performers_settings->performer_no_of_columns;
        $performers_data['cols']            = isset( $atts['cols'] ) && is_numeric($atts['cols']) ? $ep_functions->ep_check_column_size( $atts['cols'] ) : $ep_functions->ep_check_column_size( $performers_settings->performer_no_of_columns );
        $performers_data['load_more']       = isset( $atts['load_more'] ) ? $atts['load_more'] : $performers_settings->performer_load_more;
        $performers_data['enable_search']   = isset( $atts['search'] ) ? $atts['search'] : $performers_settings->performer_search;
        $performers_data['featured']        = isset( $atts["featured"] ) ? $atts["featured"] : 0;
        $performers_data['popular']         = isset( $atts["popular"] ) ? $atts["popular"] : 0;
        $performers_data['orderby']         = isset( $atts["orderby"] ) ? $atts["orderby"] : 'date';
        if($performers_data['orderby'] == 'rand'){
            
            $performers_data['orderby'] = 'RAND('.rand().')';
        }
        $performers_data['box_color'] = '';
        $performers_data['box_color'] = '';
        if( $performers_data['display_style'] == 'box' || $performers_data['display_style'] == 'colored_grid' ) {
            $performers_data['box_color'] = ( isset( $atts["performer_box_color"] ) && ! empty( $atts["performer_box_color"] ) ) ? $atts["performer_box_color"] : $performers_settings->performer_box_color;
            $performers_data["colorbox_start"] = 1;
        }
        // set query arguments
        $paged     = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
        $performers_data['paged'] = $paged;
        $ep_search = isset( $_GET['ep_search'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
        $pargs     = array(
            'orderby'        => $performers_data['orderby'],
            'posts_per_page' => $performers_data['limit'],
            'offset'         => (int)( $paged - 1 ) * (int)$performers_data['limit'],
            'paged'          => $paged,
            's'              => $ep_search,
        );
        // if featured enabled then get featured performers
        if( $performers_data['popular'] == 0 && $performers_data['featured'] == 1 ) {
            $pargs['meta_query'] = array(
                'relation'     => 'AND',
                array(
                    'key'      => 'em_display_front',
                    'value'    => 1,
                    'compare'  => '='
                ),
                array(
                    'key'   => 'em_is_featured',
                    'value' => 1
                )
            );
        }

        
        if( $performers_data['popular'] == 1 && $performers_data['featured'] == 0) {
            $performers_data['performers'] = $ep_functions->get_popular_event_performers($performers_data['limit']);
        }elseif( $performers_data['popular'] == 1 && $performers_data['featured'] == 1) {
            $performers_data['performers'] = $ep_functions->get_popular_event_performers($performers_data['limit'], $performers_data['featured']);
        }
        else
        {
            $performers_data['performers'] = $ep_functions->get_performers_post_data( $pargs );
        }
        ob_start();
        wp_enqueue_style(
            'ep-performer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $args = (object)$performers_data;
?>
<div class="emagic">
<?php
$themepath = $ep_requests->eventprime_get_ep_theme('performers-tpl');
include $themepath;
?>
</div>
