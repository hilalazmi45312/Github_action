<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class EP_Requests {

    public function get_upcoming_events_for_taxonomy($taxonomy_slug, $term_id, $hide_past_events = true, $limit = 5, $paged = 1, $args = array()) {
        //print_r($term_id);die;
        // Build the base query arguments
        $filter = array(
            'post_type' => 'em_event',
            'orderby' => 'meta_value',
            'meta_key' => 'em_start_date_time',
            'order' => 'ASC',
            'posts_per_page' => $limit, // Number of posts per page
            'paged' => $paged, // Current page
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy_slug,
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
            'meta_query' => array(),
        );

        // Add a meta query to hide past events if necessary
        if ($hide_past_events) {
            $filter['meta_query'][] = array(
                'key' => 'em_end_date_time',
                'value' => current_time('timestamp'), // Use current timestamp
                'compare' => '>=', // Compare greater than or equal to the current time
                'type' => 'NUMERIC', // Use NUMERIC for timestamp comparison
            );
        }

        // Merge any additional arguments passed
        $args = wp_parse_args($args, $filter);

        // Run the query
        $wp_query = new WP_Query($args);

        return $wp_query;
    }
    
    public function eventprime_get_ep_theme( $type, $id='') 
    {
            $ep_functions = new Eventprime_Basic_Functions;
            //$ep_theme = get_option('eventprime_theme','default');
            $ep_theme = $ep_functions->ep_get_global_settings( 'eventprime_theme' );
           // print_r($ep_theme.'ff');die;
            $plugin_path            = plugin_dir_path( EP_PLUGIN_FILE );
            $wp_theme_dir           = get_stylesheet_directory();
            if(!empty($id))
            {
                $ep_theme = $this->ep_get_event_specific_theme($id,$type,$ep_theme);
            }
            //print_r($type);die;
            $override_ep_theme_path = $wp_theme_dir . '/eventprime/themes/';
            $override_ep_theme      = $override_ep_theme_path . $ep_theme . '/' . $type . '.php';
            $default_ep_theme       = $plugin_path . 'public/partials/themes/' . $ep_theme . '/' . $type . '.php';
            if ( file_exists( $override_ep_theme ) ) {
                    $path = $override_ep_theme;
            } elseif ( file_exists( $default_ep_theme ) ) {
                    $path = $default_ep_theme;
            } else {
                    $path = $plugin_path . 'public/partials/themes/default/' . $type . '.php';
            }

            return apply_filters('eventprime_theme_path', $path, $type, $plugin_path, $wp_theme_dir);
    }
    
    public function ep_get_event_specific_theme($id,$type,$theme)
    {
         
            $event_specific_theme = get_post_meta($id,'eventprime_event_theme',true);
            if($event_specific_theme!=='')
            {
                $theme = $event_specific_theme;
            }
        
        return $theme;
    }

}
