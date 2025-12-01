<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class EP_DBhandler {
    
    public function ep_get_distinct_months($identifier) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);

        $query = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y%m') as ym, 
                         DATE_FORMAT(created_at, '%M %Y') as label
                  FROM $table
                  ORDER BY ym DESC";

        return $wpdb->get_results($query);
    }

    public function insert_row($identifier, $data, $format = null) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);
        $result = $wpdb->insert($table, $data, $format);

        if ($result !== false) {
            return $wpdb->insert_id;
        } else {
            return false;
        }
    }

    public function update_row($identifier, $unique_field, $unique_field_value, $data, $format = null, $where_format = null) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);
        if ($unique_field === false) {
            $unique_field = $ep_activator->get_db_table_unique_field_name($identifier);
        }

        if (is_numeric($unique_field_value)) {
            $unique_field_value = (int) $unique_field_value;
            $result = $wpdb->get_row($wpdb->prepare("SELECT * from $table where $unique_field = %d", $unique_field_value));
        } else {
            $result = $wpdb->get_row($wpdb->prepare("SELECT * from $table where $unique_field = %s", $unique_field_value));
        }

        if ($result === null) {
            return false;
        }

        $where = array($unique_field => $unique_field_value);
        return $wpdb->update($table, $data, $where, $format, $where_format);
    }

    public function remove_row($identifier, $unique_field, $unique_field_value, $where_format = null) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);
        if ($unique_field === false) {
            $unique_field = $ep_activator->get_db_table_unique_field_name($identifier);
        }

        if (is_numeric($unique_field_value)) {
            $unique_field_value = (int) $unique_field_value;
            $result = $wpdb->get_row($wpdb->prepare("SELECT * from $table WHERE $unique_field = %d", $unique_field_value));
        } else {
            $result = $wpdb->get_row($wpdb->prepare("SELECT * from $table WHERE $unique_field = %s", $unique_field_value));
        }

        if ($result === null) {
            return false;
        }

        $where = array($unique_field => $unique_field_value);
        return $wpdb->delete($table, $where, $where_format);
    }

    public function get_row($identifier, $unique_field_value, $unique_field = false, $output_type = 'OBJECT') {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);
        $result = null;
        if ($unique_field === false) {
            $unique_field = $ep_activator->get_db_table_unique_field_name($identifier);
        }

        if (is_numeric($unique_field_value)) {
            $unique_field_value = (int) $unique_field_value;
            $result = $wpdb->get_row($wpdb->prepare("SELECT * from $table where $unique_field = %d", $unique_field_value),$output_type);
        } else {
            $result = $wpdb->get_row($wpdb->prepare("SELECT * from $table where $unique_field = %s", $unique_field_value),$output_type);
        }


        if ($result != null) {
            return $result;
        }
    }

    public function get_value($identifier, $field, $unique_field_value, $unique_field = false) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);

        if ($unique_field === false) {
            $unique_field = $ep_activator->get_db_table_unique_field_name($identifier);
        }

        if (is_numeric($unique_field_value)) {
            $unique_field_value = (int) $unique_field_value;
            $result = $wpdb->get_var($wpdb->prepare("SELECT $field from $table where $unique_field = %d", $unique_field_value));
        } else {
            $result = $wpdb->get_var($wpdb->prepare("SELECT $field from $table where $unique_field = %s", $unique_field_value));
        }

        if (isset($result) && $result != null) {
            return $result;
        }
    }

    public function get_value_with_multicondition($identifier, $field, $where) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);
        $qry = "SELECT $field from $table where";
        $i = 0;
        $args = array();
        foreach ($where as $column_name => $column_value) {

            if ($i !== 0) {
                $qry .= ' AND';
            }

            $format = $ep_activator->get_db_table_field_type($identifier, $column_name);
            $qry .= " $column_name = $format";

            if (is_numeric($column_value)) {
                $args[] = (int) $column_value;
            } else {
                $args[] = $column_value;
            }

            $i++;
        }
        $results = $wpdb->get_var($wpdb->prepare($qry, $args));
        return $results;
    }

    public function get_all_result($identifier, $column = '*', $where = 1, $result_type = 'results', $offset = 0, $limit = false, $sort_by = null, $descending = false, $additional = '', $output = 'OBJECT', $distinct = false) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table = $ep_activator->get_db_table_name($identifier);
        $unique_id_name = $ep_activator->get_db_table_unique_field_name($identifier);
        $args = array();
        if (!$sort_by) {
            $sort_by = $unique_id_name;
        }
        if (is_string($column) && strpos($column, 'distinct')) {
            $column = str_replace('distinct ', '', $column);
            $distinct = true;
        } elseif (is_string($column) && strpos($column, 'DISTINCT')) {
            $column = str_replace('DISTINCT ', '', $column);
            $distinct = true;
        }

        if ($column != '' && !is_array($column) && $distinct == false) {
            $qry = "SELECT $column FROM $table WHERE";
        } elseif ($column != '' && !is_array($column) && $distinct == true) {
            $qry = "SELECT DISTINCT $column FROM $table WHERE";
        } elseif (is_array($column)) {
            $qry = 'SELECT ' . implode(', ', $column) . " FROM $table WHERE";
        }

        if (is_array($where)) {
            $i = 0;
            foreach ($where as $column_name => $column_value) {

                if ($i !== 0) {
                    $qry .= ' AND';
                }

                $format = $ep_activator->get_db_table_field_type($identifier, $column_name);
                $qry .= " $column_name = $format";

                if (is_numeric($column_value)) {
                    $args[] = (int) $column_value;
                } else {
                    $args[] = $column_value;
                }

                $i++;
            }
            if ($additional != '') {
                $qry .= ' ' . $additional;
            }
        } elseif ($where == 1) {
            if ($additional != '') {
                $qry .= ' ' . $additional;
            } else {
                $qry .= ' 1';
            }
        }

        if ($descending === false) {
            $qry .= " ORDER BY $sort_by";
        } else {
            $qry .= " ORDER BY $sort_by DESC";
        }

        if ($limit === false) {
            $qry .= '';
        } else {
            $qry .= " LIMIT $limit OFFSET $offset";
        }

        if ($result_type === 'results' || $result_type === 'row' || $result_type === 'var') {
            $method_name = 'get_' . $result_type;
            if (count($args) === 0) {
                if ($result_type === 'results') :
                    $results = $wpdb->$method_name($qry, $output);
                else :
                    $results = $wpdb->$method_name($qry);
                endif;
            } else {
                if ($result_type === 'results') :
                    $results = $wpdb->$method_name($wpdb->prepare($qry, $args), $output);
                else :
                    $results = $wpdb->$method_name($wpdb->prepare($qry, $args));
                endif;
            }
        } else {
            return null;
        }

        if (is_array($results) && count($results) === 0) {
            return null;
        }
        return $results;
    }
    
    public function ep_count( $identifier, $where = 1, $data_specifiers = '' ) {
        global $wpdb;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator();
        $table_name   = $ep_activator->get_db_table_name( $identifier );
        if ( $data_specifiers=='' ) {
            $unique_id_name = $ep_activator->get_db_table_unique_field_name( $identifier );
            if ( $unique_id_name === false ) {
				return false; }
        } else {
			$unique_id_name = $data_specifiers; }

        $qry = "SELECT COUNT($unique_id_name) FROM $table_name WHERE ";

        if ( is_array( $where ) ) {
            $i =0;
            foreach ( $where as $column_name => $column_value ) {
                if ( $i!=0 ) {
					$qry .= 'AND '; }
                if ( is_numeric( $column_value ) ) {
                    $column_value = (int) $column_value;
                    $qry         .= $wpdb->prepare( "$column_name = %d ", $column_value );
                } else {
                    $qry .= $wpdb->prepare( "$column_name = %s ", $column_value );
                }
            }
        } elseif ( $where == 1 ) {
			$qry .= '1 '; }

        $count = $wpdb->get_var( $qry );

        if ( $count === null ) {
			return false; }

        return (int) $count;
    }
    
    public function ep_get_pagination( $num_of_pages, $pagenum, $base = '' ) {
		if ( $pagenum=='' ) {
			$pagenum =1; }
        if ( $base=='' ) {
			$base = esc_url_raw( add_query_arg( 'pagenum', '%#%' ) ); }
		$args = array(
			'base'               => $base,
			'format'             => '',
			'total'              => $num_of_pages,
			'current'            => $pagenum,
			'show_all'           => true,
			'end_size'           => 1,
			'mid_size'           => 2,
			'prev_next'          => true,
			'prev_text'          => esc_html__( '&laquo;', 'eventprime-event-calendar-management' ),
			'next_text'          => esc_html__( '&raquo;', 'eventprime-event-calendar-management' ),
			'type'               => 'array',
			'add_args'           => false,
			'add_fragment'       => '',
			'before_page_number' => '',
			'after_page_number'  => '',
		);

		$page_links = paginate_links( $args );
                $disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;
                if ( 1 == $pagenum ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $num_of_pages == $pagenum ) {
			$disable_last = true;
			$disable_next = true;
		}

                
                //print_r($page_links);
               // echo '<br/>'. sanitize_text_field($page_links[0]);
                $pagination_html = '';
                 // Prepare pagination UI HTML
                if(isset($page_links) && !empty($page_links))
                {
                    $pagination_html .='<span class="pagination-links">';
                    $pre_links = ($pagenum==1)?$pagenum:$pagenum-1;
                    $next_links = ($pagenum==1)?$pagenum:$pagenum+1;
                    if($disable_first)
                    {
                        $pagination_html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
                    }
                    else
                    {
                        $pagination_html .= '<span class="tablenav-pages-navspan button" aria-hidden="true">'. str_replace('">1','">«', $page_links[1]).'</span>';
                    }
                    if($disable_prev)
                    {
                        
                        $pagination_html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
                    }
                    else
                    {
                        $pagination_html .= '<span class="tablenav-pages-navspan button" aria-hidden="true">'. str_replace('">'.($pagenum-1),'">‹', $page_links[$pre_links]).'</span>';
                    }
                    
                    $pagination_html .= '<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="pagenum" value="' . $pagenum . '" size="1" aria-describedby="table-paging" ><span class="tablenav-paging-text"> '.$pagenum.' of <span class="total-pages">' . $num_of_pages . '</span></span></span>';
                    
                    if($disable_next)
                    {
                        $pagination_html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
                    }
                    else
                    {
                        $pagination_html .= '<span class="tablenav-pages-navspan button" aria-hidden="true">'. str_replace('">'.($pagenum+1),'">›', $page_links[$next_links]).'</span>';
                    }
                    
                    if($disable_last)
                    {
                        $pagination_html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
                    }
                    else
                    {
                        //$pagination_html .= '<span class="pagination-links"><span class="tablenav-pages-navspan button" aria-hidden="true">'.$page_links[$num_of_pages].'</span>';
                        $pagination_html .= '<span class="tablenav-pages-navspan button" aria-hidden="true">'. str_replace('">'.$num_of_pages,'">»',$page_links[$num_of_pages]).'</span>';
                    }
                    
                      $pagination_html .= '</span>';
                    
                   
                }
                else
                {
                    $pagination_html = '<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">1</span></span></span>
<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span>';
                }

                return $pagination_html;
	}


    public function ep_get_all_event_minimum_data()
    {
        global $wpdb;
        $query = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s";
        $results = $wpdb->get_results($wpdb->prepare($query, 'em_event', 'publish'));
        return $results;

    }
    
    public function eventprime_get_all_posts_new($post_type, $result_type = 'posts', $status = 'publish', $orderby = 'title', $offset = 0, $order = 'ASC', $limit = -1, $meta_key = '', $meta_value = '', $meta_query = array(), $tax_query = array(), $date_query = array(), $search = '', $author = 0, $exclude_posts = array()) {
        $return = false;

        $args = array(
            'post_type'      => $post_type,
            'post_status'    => $status,
            'orderby'        => $orderby,
            'offset'         => $offset,
            'order'          => $order,
            'posts_per_page' => ($limit > 0) ? $limit : 10, // Default to 10 posts
        );

        if (!empty($meta_key)) $args['meta_key'] = $meta_key;
        if (!empty($meta_value)) $args['meta_value'] = $meta_value;
        if (!empty($meta_query)) $args['meta_query'] = $meta_query;
        if (!empty($tax_query)) $args['tax_query'] = $tax_query;
        if (!empty($date_query)) $args['date_query'] = $date_query;
        if (!empty($search)) $args['s'] = $search;
        if (!empty($author)) $args['author'] = $author;
        if (!empty($exclude_posts)) $args['post__not_in'] = $exclude_posts;

        $cache_key = md5(json_encode($args));
        $cached_posts = wp_cache_get($cache_key, 'eventprime_posts');

        if ($cached_posts !== false) {
            return $cached_posts;
        }

        $query = new WP_Query($args);

        if ($result_type === 'count') {
            $return = $query->found_posts;
        } else {
            if ($query->have_posts()) {
                $return = $query->get_posts();
            }
        }

        wp_reset_postdata();

        wp_cache_set($cache_key, $return, 'eventprime_posts', 3600); // Cache for 1 hour
        return $return;
    }


    public function eventprime_get_all_posts($post_type, $result_type = 'posts', $status = 'publish', $orderby = 'title', $offset = 0, $order = 'ASC', $limit = -1, $meta_key = '', $meta_value = '', $meta_query = array(), $tax_query = array(), $date_query = array(), $search = '', $author = 0, $exclude_posts = array()) {
        // Set up the query arguments
        $return = false;
        $args = array(
            'post_type' => $post_type,
            'post_status' => $status,
            'orderby' => $orderby,
            'offset' => $offset,
            'order' => $order,
            'posts_per_page' => $limit,
        );

        // Add parameters only if they are not empty
        if (!empty($meta_key)) {
            $args['meta_key'] = $meta_key;
        }

        if (!empty($meta_value)) {
            $args['meta_value'] = $meta_value;
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        if (!empty($date_query)) {
            $args['date_query'] = $date_query;
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($author)) {
            $args['author'] = $author;
        }

        if (!empty($exclude_posts)) {
            $args['post__not_in'] = $exclude_posts;
        }

       // print_r($args);die;
        // Create a new instance of WP_Query
        $query = new WP_Query($args);

        // Check the result type
        if ($result_type === 'count') {
            // Return the total count of posts
            $return = $query->found_posts;
        }
        else
        {
            // Check if any posts were found
            if ($query->have_posts()) {
                // Return the array of posts
                $return = $query->get_posts();
            }
        }

        // No posts found
        wp_reset_postdata();
        return $return;
    }

    // deprecated functions
    public function get_performer_all_data($args = array()) {
        $default = array(
            'orderby' => 'title',
            'numberposts' => -1,
            'offset' => 0,
            'order' => 'ASC',
            'post_type' => 'em_performer',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'em_status',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'em_status',
                        'value' => 0,
                        'compare' => '!='
                    ),
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'em_display_front',
                        'value' => 1,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'em_display_front',
                        'value' => 'true',
                        'compare' => '='
                    ),
                )
            )
        );
        $default = apply_filters('ep_performers_render_argument', $default, $args);
        $args = wp_parse_args($args, $default);
        $posts = get_posts($args);
        return $posts;
    }

    public function get_performer_field_data($fields = array()) {
        $response = array();
        $posts = $this->get_performer_all_data();
        if (!empty($posts) && count($posts) > 0) {
            foreach ($posts as $post) {
                $post_data = array();
                if (!empty($fields)) {
                    if (in_array('id', $fields, true)) {
                        $post_data['id'] = $post->ID;
                    }
                    if (in_array('image_url', $fields, true)) {
                        $featured_img_url = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                        $post_data['image_url'] = (!empty($featured_img_url) ) ? $featured_img_url : '';
                    }
                    if (in_array('name', $fields, true)) {
                        $post_data['name'] = $post->post_title;
                    }
                }
                if (!empty($post_data)) {
                    $response[] = $post_data;
                }
            }
        }
        return $response;
    }
    
    public function eventprime_update_event_tickets_and_category($post_id,$post,$wp_post)
    {
        $ep_functions = new Eventprime_Basic_Functions;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator;
        
        if (isset($post['em_ticket_category_data']) && !empty($post['em_ticket_category_data'])) {
            $em_ticket_category_data = json_decode(stripslashes($post['em_ticket_category_data']), true);
        }

        if (!empty($em_ticket_category_data)) {
            $cat_priority = 1;
            foreach ($em_ticket_category_data as $cat) {
                $cat_id = $cat['id'];
                $get_field_data = '';
                $arg = array();
                if (!empty($cat_id)) {
                    $get_field_data = $this->get_all_result('TICKET_CATEGORIES', '*', array('event_id' => $post_id, 'id' => $cat_id));
                    $data = array('name' => $cat['name'], 'capacity' => $cat['capacity'], 'priority' => $cat_priority, 'last_updated_by' => get_current_user_id(), 'updated_at' => wp_date("Y-m-d H:i:s", time()));
                    foreach ($data as $key => $value) {
                        $arg[] = $ep_activator->get_db_table_field_type('TICKET_CATEGORIES', $key);
                    }
                    $this->update_row('TICKET_CATEGORIES', 'id', $cat_id, $data, $arg, '%d');
                    do_action('ep_update_category_data', $cat_id, $cat, $post_id);
                } else {
                    $save_data = array();
                    $arg = array();
                    $save_data['event_id'] = $post_id;
                    $save_data['name'] = $cat['name'];
                    $save_data['capacity'] = $cat['capacity'];
                    $save_data['priority'] = 1;
                    $save_data['status'] = 1;
                    $save_data['created_by'] = get_current_user_id();
                    $save_data['created_at'] = wp_date("Y-m-d H:i:s", time());
                    foreach ($save_data as $key => $value) {
                        $arg[] = $ep_activator->get_db_table_field_type('TICKET_CATEGORIES', $key);
                    }
                    $cat_id = $this->insert_row('TICKET_CATEGORIES', $save_data, $arg);
                    do_action('ep_update_category_data', $cat_id, $cat, $post_id);
                }
                $cat_priority++;
                //save tickets
                if (isset($cat['tickets']) && !empty($cat['tickets'])) {
                    $cat_ticket_priority = 1;
                    foreach ($cat['tickets'] as $ticket) {
                        $ticket_data = array();
                        if (isset($ticket['id']) && !empty($ticket['id'])) {
                            $ticket_id = (int) $ticket['id'];
                            if (!empty($ticket_id)) {
                                $get_ticket_data = $this->get_all_result('TICKET', '*', array('id' => $ticket_id));
                                if (!empty($get_ticket_data)) {
                                    $ticket_data['name'] = addslashes($ticket['name']);
                                    $ticket_data['description'] = isset($ticket['description']) ? addslashes(str_replace('"', "'", $ticket['description'])) : '';
                                    $ticket_data['price'] = isset($ticket['price']) ? number_format(floatval($ticket['price']),2,'.','') : 0;
                                    $ticket_data['capacity'] = isset($ticket['capacity']) ? absint($ticket['capacity']) : 0;
                                    $ticket_data['icon'] = isset($ticket['icon']) ? absint($ticket['icon']) : '';
                                    $ticket_data['priority'] = $cat_ticket_priority;
                                    $ticket_data['updated_at'] = wp_date("Y-m-d H:i:s", time());
                                    $ticket_data['additional_fees'] = ( isset($ticket['ep_additional_ticket_fee_data']) && !empty($ticket['ep_additional_ticket_fee_data']) ) ? wp_json_encode($ticket['ep_additional_ticket_fee_data']) : '';
                                    $ticket_data['allow_cancellation'] = isset($ticket['allow_cancellation']) ? absint($ticket['allow_cancellation']) : 0;
                                    $ticket_data['show_remaining_tickets'] = isset($ticket['show_remaining_tickets']) ? absint($ticket['show_remaining_tickets']) : 0;
                                    // date
                                    $start_date = [];
                                    if (isset($ticket['em_ticket_start_booking_type']) && !empty($ticket['em_ticket_start_booking_type'])) {
                                        $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                        if ($ticket['em_ticket_start_booking_type'] == 'custom_date') {
                                            if (isset($ticket['em_ticket_start_booking_date']) && !empty($ticket['em_ticket_start_booking_date'])) {
                                                $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                            }
                                            if (isset($ticket['em_ticket_start_booking_time']) && !empty($ticket['em_ticket_start_booking_time'])) {
                                                $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                            }
                                        } elseif ($ticket['em_ticket_start_booking_type'] == 'event_date') {
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        } elseif ($ticket['em_ticket_start_booking_type'] == 'relative_date') {
                                            if (isset($ticket['em_ticket_start_booking_days']) && !empty($ticket['em_ticket_start_booking_days'])) {
                                                $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                            }
                                            if (isset($ticket['em_ticket_start_booking_days_option']) && !empty($ticket['em_ticket_start_booking_days_option'])) {
                                                $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                            }
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_starts'] = wp_json_encode($start_date);
                                    // end date
                                    $end_date = [];
                                    if (isset($ticket['em_ticket_ends_booking_type']) && !empty($ticket['em_ticket_ends_booking_type'])) {
                                        $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                        if ($ticket['em_ticket_ends_booking_type'] == 'custom_date') {
                                            if (isset($ticket['em_ticket_ends_booking_date']) && !empty($ticket['em_ticket_ends_booking_date'])) {
                                                $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                            }
                                            if (isset($ticket['em_ticket_ends_booking_time']) && !empty($ticket['em_ticket_ends_booking_time'])) {
                                                $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                            }
                                        } elseif ($ticket['em_ticket_ends_booking_type'] == 'event_date') {
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        } elseif ($ticket['em_ticket_ends_booking_type'] == 'relative_date') {
                                            if (isset($ticket['em_ticket_ends_booking_days']) && !empty($ticket['em_ticket_ends_booking_days'])) {
                                                $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                            }
                                            if (isset($ticket['em_ticket_ends_booking_days_option']) && !empty($ticket['em_ticket_ends_booking_days_option'])) {
                                                $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                            }
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_ends'] = wp_json_encode($end_date);

                                    // visibility
                                    $ticket_data['visibility'] = $ticket_visibility = array();
                                    if (isset($ticket['em_tickets_user_visibility']) && !empty($ticket['em_tickets_user_visibility'])) {
                                        $ticket_visibility['em_tickets_user_visibility'] = $ticket['em_tickets_user_visibility'];
                                    }
                                    if (isset($ticket['em_ticket_for_invalid_user']) && !empty($ticket['em_ticket_for_invalid_user'])) {
                                        $ticket_visibility['em_ticket_for_invalid_user'] = $ticket['em_ticket_for_invalid_user'];
                                    }
                                    if (isset($ticket['em_tickets_visibility_time_restrictions']) && !empty($ticket['em_tickets_visibility_time_restrictions'])) {
                                        $ticket_visibility['em_tickets_visibility_time_restrictions'] = $ticket['em_tickets_visibility_time_restrictions'];
                                    }
                                    if (isset($ticket['em_ticket_visibility_user_roles']) && !empty($ticket['em_ticket_visibility_user_roles'])) {
                                        $ticket_visibility['em_ticket_visibility_user_roles'] = $ticket['em_ticket_visibility_user_roles'];
                                    }
                                    if (!empty($ticket_visibility)) {
                                        $ticket_data['visibility'] = wp_json_encode($ticket_visibility);
                                    }

                                    $ticket_data['show_ticket_booking_dates'] = (isset($ticket['show_ticket_booking_dates']) ) ? 1 : 0;
                                    $ticket_data['min_ticket_no'] = isset($ticket['min_ticket_no']) ? absint($ticket['min_ticket_no']) : 0;
                                    $ticket_data['max_ticket_no'] = isset($ticket['max_ticket_no']) ? absint($ticket['max_ticket_no']) : 0;
                                    if (isset($ticket['offers']) && !empty($ticket['offers'])) {
                                        $ticket_data['offers'] = $ticket['offers'];
                                    }
                                    $ticket_data['multiple_offers_option'] = ( isset($ticket['multiple_offers_option']) && !empty($ticket['multiple_offers_option']) ) ? $ticket['multiple_offers_option'] : '';
                                    $ticket_data['multiple_offers_max_discount'] = ( isset($ticket['multiple_offers_max_discount']) && !empty($ticket['multiple_offers_max_discount']) ) ? $ticket['multiple_offers_max_discount'] : '';
                                    $ticket_data['ticket_template_id'] = ( isset($ticket['ticket_template_id']) && !empty($ticket['ticket_template_id']) ) ? $ticket['ticket_template_id'] : '';
                                    $format = array();
                                    foreach ($ticket_data as $key => $value) {
                                        $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                                    }
                                    $this->update_row('TICKET', 'id', $ticket_id, $ticket_data, $format);
                                    do_action('ep_update_insert_ticket_additional_data', $ticket_id, $ticket, $post_id);
                                } else {
                                    $ticket_data = $this->ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority);
                                    $format = array();
                                    foreach ($ticket_data as $key => $value) {
                                        $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                                    }
                                    $result = $this->insert_row('TICKET', $ticket_data, $format);
                                    do_action('ep_update_insert_ticket_additional_data', $result, $ticket, $post_id);
                                }
                            } else {
                                $ticket_data = $this->ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority);
                                $format = array();
                                foreach ($ticket_data as $key => $value) {
                                    $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                                }
                                $result = $this->insert_row('TICKET', $ticket_data, $format);
                                do_action('ep_update_insert_ticket_additional_data', $result, $ticket, $post_id);
                            }
                        } else {
                            $ticket_data = $this->ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority);
                            $format = array();
                            foreach ($ticket_data as $key => $value) {
                                $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                            }
                            $result = $this->insert_row('TICKET', $ticket_data, $format);
                            do_action('ep_update_insert_ticket_additional_data', $result, $ticket, $post_id);
                        }
                        $cat_ticket_priority++;
                    }
                }
            }
        }
        // delete category
        if (isset($post['em_ticket_category_delete_ids']) && !empty($post['em_ticket_category_delete_ids'])) {
            $em_ticket_category_delete_ids = $post['em_ticket_category_delete_ids'];
            $del_ids = json_decode(stripslashes($em_ticket_category_delete_ids));
            if (is_string($em_ticket_category_delete_ids) && is_array(json_decode(stripslashes($em_ticket_category_delete_ids))) && json_last_error() == JSON_ERROR_NONE) {
                foreach ($del_ids as $id) {
                    $this->remove_row('TICKET_CATEGORIES', 'id', $id);
                }
            }
        }
        // save tickets
        if (isset($post['em_ticket_individual_data']) && !empty($post['em_ticket_individual_data'])) {
            $em_ticket_individual_data = json_decode(stripslashes($post['em_ticket_individual_data']), true);
            if (isset($em_ticket_individual_data) && !empty($em_ticket_individual_data)) {
                $tic = 0;
                foreach ($em_ticket_individual_data as $ticket) {
                    if (isset($ticket['id']) && !empty($ticket['id'])) {
                        
                        $ticket_id = (int) $ticket['id'];
                        if(!empty($ticket_id))
                        {
                        $ticket_data = array();
                        $ticket_data['name'] = addslashes($ticket['name']);
                        $ticket_data['description'] = isset($ticket['description']) ? addslashes(str_replace('"', "'", $ticket['description'])) : '';
                        $ticket_data['price'] = isset($ticket['price']) ? number_format(floatval($ticket['price']),2,'.','') : 0;
                        $ticket_data['capacity'] = isset($ticket['capacity']) ? absint($ticket['capacity']) : 0;
                        $ticket_data['icon'] = isset($ticket['icon']) ? absint($ticket['icon']) : '';
                        $ticket_data['updated_at'] = wp_date("Y-m-d H:i:s", time());
                        $ticket_data['additional_fees'] = ( isset($ticket['ep_additional_ticket_fee_data']) && !empty($ticket['ep_additional_ticket_fee_data']) ) ? wp_json_encode($ticket['ep_additional_ticket_fee_data']) : '';
                        $ticket_data['allow_cancellation'] = isset($ticket['allow_cancellation']) ? absint($ticket['allow_cancellation']) : 0;
                        $ticket_data['show_remaining_tickets'] = isset($ticket['show_remaining_tickets']) ? absint($ticket['show_remaining_tickets']) : 0;
                        // date
                        $start_date = [];
                        if (isset($ticket['em_ticket_start_booking_type']) && !empty($ticket['em_ticket_start_booking_type'])) {
                            $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                            if ($ticket['em_ticket_start_booking_type'] == 'custom_date') {
                                if (isset($ticket['em_ticket_start_booking_date']) && !empty($ticket['em_ticket_start_booking_date'])) {
                                    $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                }
                                if (isset($ticket['em_ticket_start_booking_time']) && !empty($ticket['em_ticket_start_booking_time'])) {
                                    $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                }
                            } elseif ($ticket['em_ticket_start_booking_type'] == 'event_date') {
                                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                            } elseif ($ticket['em_ticket_start_booking_type'] == 'relative_date') {
                                if (isset($ticket['em_ticket_start_booking_days']) && !empty($ticket['em_ticket_start_booking_days'])) {
                                    $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                }
                                if (isset($ticket['em_ticket_start_booking_days_option']) && !empty($ticket['em_ticket_start_booking_days_option'])) {
                                    $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                }
                                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                            }
                        }
                        $ticket_data['booking_starts'] = wp_json_encode($start_date);
                        // end date
                        $end_date = [];
                        if (isset($ticket['em_ticket_ends_booking_type']) && !empty($ticket['em_ticket_ends_booking_type'])) {
                            $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                            if ($ticket['em_ticket_ends_booking_type'] == 'custom_date') {
                                if (isset($ticket['em_ticket_ends_booking_date']) && !empty($ticket['em_ticket_ends_booking_date'])) {
                                    $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                }
                                if (isset($ticket['em_ticket_ends_booking_time']) && !empty($ticket['em_ticket_ends_booking_time'])) {
                                    $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                }
                            } elseif ($ticket['em_ticket_ends_booking_type'] == 'event_date') {
                                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                            } elseif ($ticket['em_ticket_ends_booking_type'] == 'relative_date') {
                                if (isset($ticket['em_ticket_ends_booking_days']) && !empty($ticket['em_ticket_ends_booking_days'])) {
                                    $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                }
                                if (isset($ticket['em_ticket_ends_booking_days_option']) && !empty($ticket['em_ticket_ends_booking_days_option'])) {
                                    $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                }
                                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                            }
                        }
                        $ticket_data['booking_ends'] = wp_json_encode($end_date);

                        // visibility
                        $ticket_data['visibility'] = $ticket_visibility = array();
                        if (isset($ticket['em_tickets_user_visibility']) && !empty($ticket['em_tickets_user_visibility'])) {
                            $ticket_visibility['em_tickets_user_visibility'] = $ticket['em_tickets_user_visibility'];
                        }
                        if (isset($ticket['em_ticket_for_invalid_user']) && !empty($ticket['em_ticket_for_invalid_user'])) {
                            $ticket_visibility['em_ticket_for_invalid_user'] = $ticket['em_ticket_for_invalid_user'];
                        }
                        if (isset($ticket['em_tickets_visibility_time_restrictions']) && !empty($ticket['em_tickets_visibility_time_restrictions'])) {
                            $ticket_visibility['em_tickets_visibility_time_restrictions'] = $ticket['em_tickets_visibility_time_restrictions'];
                        }
                        if (isset($ticket['em_ticket_visibility_user_roles']) && !empty($ticket['em_ticket_visibility_user_roles'])) {
                            $ticket_visibility['em_ticket_visibility_user_roles'] = $ticket['em_ticket_visibility_user_roles'];
                        }
                        if (!empty($ticket_visibility)) {
                            $ticket_data['visibility'] = wp_json_encode($ticket_visibility);
                        }

                        $ticket_data['show_ticket_booking_dates'] = (isset($ticket['show_ticket_booking_dates']) ) ? 1 : 0;
                        $ticket_data['min_ticket_no'] = isset($ticket['min_ticket_no']) ? absint($ticket['min_ticket_no']) : 0;
                        $ticket_data['max_ticket_no'] = isset($ticket['max_ticket_no']) ? absint($ticket['max_ticket_no']) : 0;
                        if (isset($ticket['offers']) && !empty($ticket['offers'])) {
                            $ticket_data['offers'] = $ticket['offers'];
                        }
                        $ticket_data['multiple_offers_option'] = ( isset($ticket['multiple_offers_option']) && !empty($ticket['multiple_offers_option']) ) ? $ticket['multiple_offers_option'] : '';
                        $ticket_data['multiple_offers_max_discount'] = ( isset($ticket['multiple_offers_max_discount']) && !empty($ticket['multiple_offers_max_discount']) ) ? $ticket['multiple_offers_max_discount'] : '';
                        $ticket_data['ticket_template_id'] = ( isset($ticket['ticket_template_id']) && !empty($ticket['ticket_template_id']) ) ? $ticket['ticket_template_id'] : '';
                        $arg = array();
                        foreach ($ticket_data as $key => $value) {
                            $arg[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                        }
                        $this->update_row('TICKET', 'id', $ticket_id, $ticket_data, $arg, '%d');
                        do_action('ep_update_insert_ticket_additional_data', $ticket_id, $ticket, $post_id);
                        }
                        else {
                            $ticket_data = $this->ep_add_individual_tickets($post_id, $ticket);
                        $arg = array();
                        foreach ($ticket_data as $key => $value) {
                            $arg[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                        }
                        $result = $this->insert_row('TICKET', $ticket_data, $arg);
                        do_action('ep_update_insert_ticket_additional_data', $result, $ticket, $post_id);
                       }
                    } else {
                        $ticket_data = $this->ep_add_individual_tickets($post_id, $ticket);
                        $arg = array();
                        foreach ($ticket_data as $key => $value) {
                            $arg[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                        }
                        $result = $this->insert_row('TICKET', $ticket_data, $arg);
                        do_action('ep_update_insert_ticket_additional_data', $result, $ticket, $post_id);
                    }
                    $tic++;
                    //error_log($tic);
                }
            }
        }
        // delete tickets
        if (isset($post['em_ticket_individual_delete_ids']) && !empty($post['em_ticket_individual_delete_ids'])) {
            $em_ticket_individual_delete_ids = $post['em_ticket_individual_delete_ids'];
            $del_ids = json_decode(stripslashes($em_ticket_individual_delete_ids));
            if (is_string($em_ticket_individual_delete_ids) && is_array(json_decode(stripslashes($em_ticket_individual_delete_ids))) && json_last_error() == JSON_ERROR_NONE) {
                foreach ($del_ids as $id) {
                    $get_field_data = $this->get_row('TICKET', $id);
                    if (!empty($get_field_data)) {
                        $this->remove_row('TICKET', 'id', $id, '%d');
                    }
                }
            }
        }
        
        // Save tickets order 
		if( isset( $post['ep_ticket_order_arr'] ) && !empty( $post['ep_ticket_order_arr'] ) ) {
			$ep_ticket_order_arr = sanitize_text_field( $post['ep_ticket_order_arr'] ); 
			$ep_ticket_order_arr_explode = explode( ',', $ep_ticket_order_arr ); 

			$get_existing_individual_ticket_lists = $ep_functions->get_existing_individual_ticket_lists($post_id);
                        if(isset($get_existing_individual_ticket_lists) && !empty($get_existing_individual_ticket_lists))
                        {
                            foreach ( $get_existing_individual_ticket_lists as $ticket ) { 
                                    if ( ! in_array( $ticket->id, $ep_ticket_order_arr_explode ) ) {
                                            array_push( $ep_ticket_order_arr_explode, $ticket->id ); 
                                    }
                            }
                        }

			update_post_meta( $post_id, 'ep_ticket_order_arr', $ep_ticket_order_arr_explode ); 
		}

        do_action('ep_after_save_event_tickets_and_category', $post_id, $post);
        
    }
    
    public function eventprime_update_event_custom_fields($post_id,$post,$wp_post)
    {
            // event checkout fields
         $ep_functions = new Eventprime_Basic_Functions;
        $event_checkout_attendee_fields = array();
        // check for name field
        if (isset($post['em_event_checkout_name']) && !empty($post['em_event_checkout_name'])) {
            $event_checkout_attendee_fields['em_event_checkout_name'] = absint($post['em_event_checkout_name']);
            if (isset($post['em_event_checkout_name_first_name']) && !empty($post['em_event_checkout_name_first_name'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_first_name'] = absint($post['em_event_checkout_name_first_name']);
            }
            if (isset($post['em_event_checkout_name_first_name_required']) && !empty($post['em_event_checkout_name_first_name_required'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_first_name_required'] = absint($post['em_event_checkout_name_first_name_required']);
            }
            if (isset($post['em_event_checkout_name_middle_name']) && !empty($post['em_event_checkout_name_middle_name'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_middle_name'] = absint($post['em_event_checkout_name_middle_name']);
            }
            if (isset($post['em_event_checkout_name_middle_name_required']) && !empty($post['em_event_checkout_name_middle_name_required'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_middle_name_required'] = absint($post['em_event_checkout_name_middle_name_required']);
            }
            if (isset($post['em_event_checkout_name_last_name']) && !empty($post['em_event_checkout_name_last_name'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_last_name'] = absint($post['em_event_checkout_name_last_name']);
            }
            if (isset($post['em_event_checkout_name_last_name_required']) && !empty($post['em_event_checkout_name_last_name_required'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_last_name_required'] = absint($post['em_event_checkout_name_last_name_required']);
            }
        }
        // check for checkout fields
        if (isset($post['em_event_checkout_fields_data']) && count($post['em_event_checkout_fields_data']) > 0) {
            $event_checkout_attendee_fields['em_event_checkout_fields_data'] = array();
            foreach ($post['em_event_checkout_fields_data'] as $cfd) {
                $event_checkout_attendee_fields['em_event_checkout_fields_data'][] = absint($cfd);
            }
            // get required field data
            if (isset($post['em_event_checkout_fields_data_required']) && count($post['em_event_checkout_fields_data_required']) > 0) {
                $event_checkout_attendee_fields['em_event_checkout_fields_data_required'] = array();
                foreach ($post['em_event_checkout_fields_data_required'] as $cfdr) {
                    $event_checkout_attendee_fields['em_event_checkout_fields_data_required'][] = absint($cfdr);
                }
            }
        }
        update_post_meta($post_id, 'em_event_checkout_attendee_fields', $event_checkout_attendee_fields);
        // event checkout fixed fields
        $event_checkout_fixed_fields = array();
        if (isset($post['em_event_checkout_fixed_terms_enabled']) && absint($post['em_event_checkout_fixed_terms_enabled']) == 1) {
            $event_checkout_fixed_fields['em_event_checkout_fixed_terms_enabled'] = $post['em_event_checkout_fixed_terms_enabled'];
            if (isset($post['em_event_checkout_fixed_terms_label'])) {
                $event_checkout_fixed_fields['em_event_checkout_fixed_terms_label'] = sanitize_text_field($post['em_event_checkout_fixed_terms_label']);
            }
            if (isset($post['em_event_checkout_fixed_terms_option'])) {
                $event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] = sanitize_text_field($post['em_event_checkout_fixed_terms_option']);
            }
            if (isset($post['em_event_checkout_fixed_terms_content'])) {
                if ($event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] == 'page') {
                    $event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] = absint($post['em_event_checkout_fixed_terms_content']);
                } else if ($event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] == 'content') {
                    $event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] = wp_kses_post($post['em_event_checkout_fixed_terms_content']);
                } else {
                    $event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] = sanitize_text_field($post['em_event_checkout_fixed_terms_content']);
                }
            }
        }
        update_post_meta($post_id, 'em_event_checkout_fixed_fields', $event_checkout_fixed_fields);
        $em_event_checkout_booking_fields = array();
        // check for booking fields
        if (isset($post['em_event_booking_fields_data']) && count($post['em_event_booking_fields_data']) > 0) {
            $em_event_checkout_booking_fields['em_event_booking_fields_data'] = array();
            foreach ($post['em_event_booking_fields_data'] as $cfd) {
                $em_event_checkout_booking_fields['em_event_booking_fields_data'][] = absint($cfd);
            }
            // get required field data
            if (isset($post['em_event_booking_fields_data_required']) && count($post['em_event_booking_fields_data_required']) > 0) {
                $em_event_checkout_booking_fields['em_event_booking_fields_data_required'] = array();
                foreach ($post['em_event_booking_fields_data_required'] as $cfdr) {
                    $em_event_checkout_booking_fields['em_event_booking_fields_data_required'][] = absint($cfdr);
                }
            }
        }
        update_post_meta($post_id, 'em_event_checkout_booking_fields', $em_event_checkout_booking_fields);
        
    }
    
    public function eventprime_update_event_recurring_events($post_id,$post,$wp_post,$post_data)
    {
             // handle recurring events request
         $ep_functions = new Eventprime_Basic_Functions;
         $em_start_date= isset($post_data['em_start_date'])?$post_data['em_start_date']:'';
         $em_start_time = isset($post_data['em_start_time'])?$post_data['em_start_time']:'';
         $em_end_date = isset($post_data['em_end_date'])?$post_data['em_end_date']:'';
         $em_end_time = isset($post_data['em_end_time'])?$post_data['em_end_time']:'';
         
        if (isset($post['em_enable_recurrence']) && $post['em_enable_recurrence'] == 1) {
            update_post_meta($post_id, 'em_enable_recurrence', 1);
            $add_recurrence = 1;
            $old_em_recurrence_step = get_post_meta($post_id, 'em_recurrence_step', true);
            $old_em_recurrence_interval = get_post_meta($post_id, 'em_recurrence_interval', true);
            $em_recurrence_step = (isset($post['em_recurrence_step']) && !empty($post['em_recurrence_step']) ) ? absint($post['em_recurrence_step']) : 1;
            update_post_meta($post_id, 'em_recurrence_step', $em_recurrence_step);
            // update the parent event first
            do_action('ep_update_parent_event_status', $post_id, $post);

            if (isset($post['em_recurrence_interval']) && !empty($post['em_recurrence_interval'])) {
                $em_recurrence_interval = sanitize_text_field($post['em_recurrence_interval']);
                update_post_meta($post_id, 'em_recurrence_interval', $em_recurrence_interval);
                if ((!empty($old_em_recurrence_step) && $old_em_recurrence_step == $em_recurrence_step ) && (!empty($old_em_recurrence_interval) && $old_em_recurrence_interval == $em_recurrence_interval )) {
                    $add_recurrence = 0;
                }
                if (empty($add_recurrence)) {
                    // check for weekly interval
                    if ($em_recurrence_interval == 'weekly') {
                        $weekly_days = $post['em_selected_weekly_day'];
                        $old_weekly_days = get_post_meta($post_id, 'em_selected_weekly_day', true);
                        if ($old_weekly_days != $weekly_days) {
                            $add_recurrence = 1;
                        }
                    }
                    // check for monthly interval
                    if ($em_recurrence_interval == 'monthly') {
                        $monthly_day = $post['em_recurrence_monthly_day'];
                        $old_monthly_day = get_post_meta($post_id, 'em_recurrence_monthly_day', true);
                        if ($old_monthly_day != $monthly_day) {
                            $add_recurrence = 1;
                        } else {
                            if ($monthly_day == 'day') {
                                $em_recurrence_monthly_weekno = $post['em_recurrence_monthly_weekno'];
                                $old_em_recurrence_monthly_weekno = get_post_meta($post_id, 'em_recurrence_monthly_weekno', true);
                                if ($old_em_recurrence_monthly_weekno != $em_recurrence_monthly_weekno) {
                                    $add_recurrence = 1;
                                } else {
                                    $em_recurrence_monthly_fullweekday = $post['em_recurrence_monthly_fullweekday'];
                                    $old_em_recurrence_monthly_fullweekday = get_post_meta($post_id, 'em_recurrence_monthly_fullweekday', true);
                                    if ($old_em_recurrence_monthly_fullweekday != $em_recurrence_monthly_fullweekday) {
                                        $add_recurrence = 1;
                                    }
                                }
                            }
                        }
                    }
                    // check for yearly interval
                    if ($em_recurrence_interval == 'yearly') {
                        $yearly_day = $post['em_recurrence_yearly_day'];
                        $old_yearly_day = get_post_meta($post_id, 'em_recurrence_yearly_day', true);
                        if ($old_yearly_day != $yearly_day) {
                            $add_recurrence = 1;
                        } else {
                            if ($yearly_day == 'day') {
                                $em_recurrence_yearly_weekno = $post['em_recurrence_yearly_weekno'];
                                $old_em_recurrence_yearly_weekno = get_post_meta($post_id, 'em_recurrence_yearly_weekno', true);
                                if ($old_em_recurrence_yearly_weekno != $em_recurrence_yearly_weekno) {
                                    $add_recurrence = 1;
                                } else {
                                    $em_recurrence_yearly_fullweekday = $post['em_recurrence_yearly_fullweekday'];
                                    $old_em_recurrence_yearly_fullweekday = get_post_meta($post_id, 'em_recurrence_yearly_fullweekday', true);
                                    if ($old_em_recurrence_yearly_fullweekday != $em_recurrence_yearly_fullweekday) {
                                        $add_recurrence = 1;
                                    } else {
                                        $em_recurrence_yearly_monthday = $post['em_recurrence_yearly_monthday'];
                                        $old_em_recurrence_yearly_monthday = get_post_meta($post_id, 'em_recurrence_yearly_monthday', true);
                                        if ($old_em_recurrence_yearly_monthday != $em_recurrence_yearly_monthday) {
                                            $add_recurrence = 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // check for advanced interval
                    if ($em_recurrence_interval == 'advanced') {
                        $advanced_dates = $post['em_recurrence_advanced_dates'];
                        $old_advanced_dates = get_post_meta($post_id, 'em_recurrence_advanced_dates', true);
                        if ($old_advanced_dates != $advanced_dates) {
                            $add_recurrence = 1;
                        }
                    }
                    // check for custom dates interval
                    if ($em_recurrence_interval == 'custom_dates') {
                        $custom_dates = json_decode(stripslashes($post['em_recurrence_selected_custom_dates']), true);
                        $old_custom_dates = get_post_meta($post_id, 'em_recurrence_selected_custom_dates', true);
                        if ($old_custom_dates != $custom_dates) {
                            $add_recurrence = 1;
                        }
                    }
                    // check for recurrence ends
                    if (isset($post['em_recurrence_ends']) && !empty($post['em_recurrence_ends'])) {
                        $em_recurrence_ends = $post['em_recurrence_ends'];
                        $old_recurrence_ends = get_post_meta($post_id, 'em_recurrence_ends', true);
                        if ($old_recurrence_ends != $em_recurrence_ends) {
                            $add_recurrence = 1;
                        } else {
                            if ($em_recurrence_ends == 'on') {
                                $em_recurrence_limit = $post['em_recurrence_limit'];
                                $old_em_recurrence_limit = get_post_meta($post_id, 'em_recurrence_limit', true);
                                if ($old_em_recurrence_limit != $em_recurrence_limit) {
                                    $add_recurrence = 1;
                                }
                            } else {
                                $em_recurrence_occurrence_time = $post['em_recurrence_occurrence_time'];
                                $old_em_recurrence_occurrence_time = get_post_meta($post_id, 'em_recurrence_occurrence_time', true);
                                if ($old_em_recurrence_occurrence_time != $em_recurrence_occurrence_time) {
                                    $add_recurrence = 1;
                                }
                            }
                        }
                    }
                }
                if ($add_recurrence) {
                    // first delete old child events
                    $this->ep_delete_child_events($post_id);
                    $em_recurrence_ends = (isset($post['em_recurrence_ends']) && !empty($post['em_recurrence_ends']) ) ? $post['em_recurrence_ends'] : 'after';
                    update_post_meta($post_id, 'em_recurrence_ends', $em_recurrence_ends);
                    $last_date_on = $stop_after = $recurrence_limit_timestamp = $start_date_only = '';
                    if ($em_recurrence_ends == 'on') {
                        $last_date_on = $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_recurrence_limit']));
                        if (empty($last_date_on)) {
                            $last_date_on = $ep_functions->ep_date_to_timestamp($ep_functions->ep_timestamp_to_date(current_time('timestamp',true)));
                        }
                        if (!empty($last_date_on)) {
                            update_post_meta($post_id, 'em_recurrence_limit', $last_date_on);
                            
                            //$recurrence_limit = new DateTime('@' . $last_date_on);
                            $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

                            $recurrence_limit = new DateTime('now', $timezone);
                            $recurrence_limit->setTimestamp($last_date_on);

                            //$recurrence_limit->setTime( 0,0,0,0 );
                            $recurrence_limit_timestamp = $recurrence_limit->getTimestamp();
                        }
                        // update start date format
//                        $start_date_only = new DateTime('@' . $em_start_date);
//                        
//                        $start_date_only->setTime(0, 0, 0, 0);
                        
                        $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

                        $start_date_only = new DateTime('now', $timezone);
                        $start_date_only->setTimestamp($em_start_date);
                        $start_date_only->setTime(0, 0, 0); // Sets time to midnight in local timezone

                    }

                    if ($em_recurrence_ends == 'after') {
                        $stop_after = absint($post['em_recurrence_occurrence_time']);
                        update_post_meta($post_id, 'em_recurrence_occurrence_time', $stop_after);
                    }

                    $data = array('start_date' => $em_start_date, 'start_time' => $em_start_time, 'end_date' => $em_end_date, 'end_time' => $em_end_time, 'recurrence_step' => $em_recurrence_step, 'recurrence_interval' => $em_recurrence_interval, 'last_date_on' => $last_date_on, 'stop_after' => $stop_after, 'recurrence_limit_timestamp' => $recurrence_limit_timestamp, 'start_date_only' => $start_date_only);
                    
                    switch ($em_recurrence_interval) {
                        case 'daily':
                            $this->ep_event_daily_recurrence($wp_post, $data, $post);
                            break;
                        case 'weekly':
                            $this->ep_event_weekly_recurrence($wp_post, $data, $post);
                            break;
                        case 'monthly':
                            $this->ep_event_monthly_recurrence($wp_post, $data, $post);
                            break;
                        case 'yearly':
                            $this->ep_event_yearly_recurrence($wp_post, $data, $post);
                            break;
                        case 'advanced':
                            $this->ep_event_advanced_recurrence($wp_post, $data, $post);
                            break;
                        case 'custom_dates':
                            $this->ep_event_custom_dates_recurrence($wp_post, $data, $post);
                            break;
                    }
                }
            }
        } else {
            update_post_meta($post_id, 'em_enable_recurrence', 0);
            // check if event have the child events and delete them
            if (isset($post['ep_event_count_child_events']) && !empty($post['ep_event_count_child_events'])) {
                $ep_event_count_child_events = absint($post['ep_event_count_child_events']);
                if (!empty($ep_event_count_child_events)) {
                    //delete the child events
                    $this->ep_delete_child_events($post_id);
                }
            }
            update_post_meta($post_id, 'em_recurrence_step', 0);
            update_post_meta($post_id, 'em_recurrence_interval', '');
        }
    }
    
    public function eventprime_update_event_post_meta($post_id, $post,$wp_post)
    {
        $ep_functions = new Eventprime_Basic_Functions;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator;
        
        $post_data = array();
        $post_data['em_id'] = isset($post['post_ID'])?$post['post_ID']:$post_id;
        $post_data['em_name'] = $post['post_title'] ?? 'no title';
        /* taxonomy update */
        //print_r($post['tax_input']);die;
        if (isset($post['tax_input']) && !empty($post['tax_input'])) {
           $post_data['em_event_type'] = (isset($post['tax_input']['em_event_type']))?$post['tax_input']['em_event_type']:'';
           $post_data['em_venue'] = (isset($post['tax_input']['em_venue']))?$post['tax_input']['em_venue']:'';
           $post_data['em_organizer'] = (isset($post['tax_input']['em_event_organizer']) && !empty($post['tax_input']['em_event_organizer']))?$post['tax_input']['em_event_organizer']:'';
           $post_data['em_performer'] = (isset($post['em_performers']) && !empty($post['em_performers']))?$post['em_performers']:'';
           $old_event_venue_id = get_post_meta($post_id, 'em_venue', true);
           if (!empty($old_event_venue_id) && $old_event_venue_id != $post_data['em_venue']) {
               // remove event seat data
               $event_seat_data = get_post_meta($post_id, 'em_seat_data', true);
               if (!empty($event_seat_data)) {
                   $post_data['em_seat_data'] = array();

               }
           }
        }
        
        $post_data['em_gallery_image_ids'] = (isset($post['em_gallery_image_ids'])) ? $post['em_gallery_image_ids'] : '';
        $post_data['em_start_date'] = $em_start_date = (isset($post['em_start_date'])) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_start_date'])) : '';
        $post_data['em_start_time'] = $em_start_time = (isset($post['em_start_time'])) ? $post['em_start_time'] : '';
        $post_data['em_hide_event_start_time'] = (isset($post['em_hide_event_start_time'])) ? 1 : 0;
        $post_data['em_hide_event_start_date'] = (isset($post['em_hide_event_start_date'])) ? 1 : 0;
        $post_data['em_end_date'] = $em_end_date = (isset($post['em_end_date'])) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_end_date'])) : $em_start_date;
        $post_data['em_end_time'] = $em_end_time = (isset($post['em_end_time'])) ? sanitize_text_field($post['em_end_time']): '';
        $post_data['em_hide_event_end_time'] = (isset($post['em_hide_event_end_time'])) ? 1 : 0;
        $post_data['em_hide_end_date'] = (isset($post['em_hide_end_date'])) ? 1 : 0;
        $post_data['em_all_day'] = $em_all_day = (isset($post['em_all_day'])) ? 1 : 0;
        
        if ($em_all_day == 1) {
            $post_data['em_end_date'] = $em_start_date;
            $post_data['em_start_time'] = '12:00 AM';
            $post_data['em_end_time'] = '11:59 PM';
        } else {
            if ($em_start_date > $em_end_date) {
                $post_data['em_end_date'] = $em_start_date;
            } else if ($em_start_date == $em_end_date) {
                if ($em_start_time == $em_end_time) {
                    if (empty($em_start_time)) {
                        $post_data['em_start_time'] = '12:00 AM';
                        $post_data['em_end_time'] = '11:59 PM';
                    } else {
                        if ($em_end_time !== '11:59 PM') {
                           $post_data['em_end_time'] = '11:59 PM';
                        }
                    }
                }
            } else if ($em_start_date < $em_end_date) {
                if (empty($em_start_time)) {
                    $post_data['em_start_time'] = '12:00 AM';
                    $post_data['em_end_time'] = '11:59 PM';
                } else if (!empty($em_end_time)) {
                    $post_data['em_end_time'] = $em_end_time;
                } else {
                    if ($em_end_time !== '11:59 PM') {
                        $post_data['em_end_time'] = '11:59 PM';
                    }
                }
            }
        }
        
        $ep_date_time_format = 'Y-m-d';
        $start_date = $post_data['em_start_date'];
        $start_time = $post_data['em_start_time'];
        $merge_start_date_time = $ep_functions->ep_datetime_to_timestamp($ep_functions->ep_timestamp_to_date($start_date, 'Y-m-d', 1) . ' ' . $start_time, $ep_date_time_format, '', 0, 1);
        if (!empty($merge_start_date_time)) {
            $post_data['em_start_date_time'] = $merge_start_date_time;
        }
        
        $end_date = $post_data['em_end_date'];
        $end_time = $post_data['em_end_time'];
        $merge_end_date_time = $ep_functions->ep_datetime_to_timestamp($ep_functions->ep_timestamp_to_date($end_date, 'Y-m-d', 1) . ' ' . $end_time, $ep_date_time_format, '', 0, 1);
        if (!empty($merge_end_date_time)) {
            $post_data['em_end_date_time'] = $merge_end_date_time;
        }
        
        //event date placeholder
        $post_data['em_event_date_placeholder'] = $em_event_date_placeholder = (isset($post['em_event_date_placeholder'])) ? sanitize_text_field($post['em_event_date_placeholder']) : '';
        $post_data['em_event_date_placeholder_custom_note'] = (!empty($em_event_date_placeholder) && $em_event_date_placeholder == 'custom_note' && isset($post['em_event_date_placeholder_custom_note']))?sanitize_text_field($post['em_event_date_placeholder_custom_note']):'';
        // add event more dates
        $post_data['em_event_more_dates'] = (isset($post['em_event_more_dates'])) ? 1 : 0;
        $event_more_dates = array();
        if (isset($post['em_event_more_dates']) && !empty($post['em_event_more_dates'])) {
            if (isset($post['em_event_add_more_dates']) && count($post['em_event_add_more_dates']) > 0) {
                foreach ($post['em_event_add_more_dates'] as $key => $more_dates) {
                    $new_date = array();
                    $new_date['uid'] = absint($more_dates['uid']);
                    $new_date['date'] = $ep_functions->ep_date_to_timestamp(sanitize_text_field($more_dates['date']));
                    $new_date['time'] = sanitize_text_field($more_dates['time']);
                    $new_date['label'] = sanitize_text_field($more_dates['label']);
                    $event_more_dates[] = $new_date;
                }
            }
        }
        $post_data['em_event_add_more_dates'] = $event_more_dates;
        // booking & tickets
        $em_enable_booking = isset($post['em_enable_booking']) ? sanitize_text_field($post['em_enable_booking']) : '';
        if ($em_enable_booking == 'bookings_on') {
            // check for ticket. If no ticket created then bookings will be off
            $ep_event_has_ticket = isset($post['ep_event_has_ticket'])?absint($post['ep_event_has_ticket']):0;
            if ($ep_event_has_ticket == 0) {
                $em_enable_booking = 'bookings_off';
            }
        }
        $post_data['em_enable_booking'] = $em_enable_booking;
        // check for external booking
        if (!empty($em_enable_booking) && $em_enable_booking == 'external_bookings') {
            $post_data['em_custom_link'] = (isset($post['em_custom_link']) && !empty($post['em_custom_link'])) ? sanitize_url($post['em_custom_link']) : '';
            // open in new browser
            $post_data['em_custom_link_new_browser'] = (isset($post['em_custom_link_new_browser'])) ? 1 : 0;
            
        }
        // One time event fee
        $post_data['em_fixed_event_price'] = (isset($post['em_fixed_event_price']) && !empty($post['em_fixed_event_price'])) ? sanitize_text_field($post['em_fixed_event_price']) : '';
        // hide booking status
        $post_data['em_hide_booking_status'] = (isset($post['em_hide_booking_status'])) ? 1 : 0;
       
        // allow cancellation option
        $post_data['em_allow_cancellations'] = (isset($post['em_allow_cancellations'])) ? 1 : 0;
        
        // add other settings meta box
        $post_data['em_event_text_color'] = (isset($post['em_event_text_color'])) ? sanitize_text_field($post['em_event_text_color']) : '';
        $post_data['em_audience_notice'] = (isset($post['em_audience_notice'])) ? sanitize_textarea_field($post['em_audience_notice']) : '';
        
        
        $post_data['eventprime_event_theme'] = (isset($post['eventprime_event_theme'])) ? sanitize_text_field($post['eventprime_event_theme']) : '';
        
        // add restrictions settings meta box
        $post_data['em_event_max_tickets_per_user'] = (isset($post['em_event_max_tickets_per_user'])) ? sanitize_text_field($post['em_event_max_tickets_per_user']) : '';
        $post_data['em_event_max_tickets_per_order'] = (isset($post['em_event_max_tickets_per_order'])) ? sanitize_text_field($post['em_event_max_tickets_per_order']) : '';
        $post_data['em_event_max_tickets_reached_message'] = (isset($post['em_event_max_tickets_reached_message'])) ? sanitize_textarea_field($post['em_event_max_tickets_reached_message']) : '';
        $post_data['em_restrict_no_of_bookings_per_user'] = (isset($post['em_restrict_no_of_bookings_per_user'])) ? absint($post['em_restrict_no_of_bookings_per_user']) : '';
        // save social info
        $em_social_links = array();
        if (isset($post['em_social_links']) && count($post['em_social_links']) > 0) {
            foreach ($post['em_social_links'] as $social_key => $social_links) {
                if (!empty($social_links)) {
                    $em_social_links[$social_key] = sanitize_url($social_links);
                }
            }
        }
        $post_data['em_social_links'] = $em_social_links;
        
        // add result settings meta box
        
        $post_data['ep_select_result_page'] = (isset($post['ep_select_result_page'])) ? sanitize_text_field($post['ep_select_result_page']) : '';
        $post_data['ep_result_start_from_type'] = (isset($post['ep_result_start_from_type'])) ? sanitize_text_field($post['ep_result_start_from_type']) : '';
        $post_data['ep_result_start_date'] = (isset($post['ep_result_start_date'])) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['ep_result_start_date'])) : '';
        $post_data['ep_result_start_time'] = (isset($post['ep_result_start_time'])) ? sanitize_text_field($post['ep_result_start_time']) : '';
        $post_data['ep_result_start_days'] = (isset($post['ep_result_start_days'])) ? sanitize_text_field($post['ep_result_start_days']) : '';
        $post_data['ep_result_start_days_option'] = (isset($post['ep_result_start_days_option'])) ? sanitize_text_field($post['ep_result_start_days_option']) : '';
        $post_data['ep_result_start_event_option'] = (isset($post['ep_result_start_event_option'])) ? sanitize_text_field($post['ep_result_start_event_option']) : '';
        
        // edit booking
        $em_allow_edit_booking = $em_edit_booking_date_data = '';
        if (!empty($post['em_allow_edit_booking'])) {
            $em_allow_edit_booking = 1;
            $em_edit_booking_date_data = array(
                'em_edit_booking_date_type' => sanitize_text_field($post['em_edit_booking_date_type']),
                'em_edit_booking_date_date' => (!empty($post['em_edit_booking_date_date']) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_edit_booking_date_date'])) : '' ),
                'em_edit_booking_date_time' => sanitize_text_field($post['em_edit_booking_date_time']),
                'em_edit_booking_date_days' => sanitize_text_field($post['em_edit_booking_date_days']),
                'em_edit_booking_date_days_option' => sanitize_text_field($post['em_edit_booking_date_days_option']),
                'em_edit_booking_date_event_option' => sanitize_text_field($post['em_edit_booking_date_event_option']),
            );
        }
        $post_data['em_allow_edit_booking'] = $em_allow_edit_booking;
        $post_data['em_edit_booking_date_data'] = $em_edit_booking_date_data;
        
        $this->eventprime_save_event_metadata($post_id,$post_data);

        do_action('ep_after_save_event_metadata', $post_id, $post);
        
        $this->eventprime_update_event_tickets_and_category($post_id, $post, $wp_post);

        $this->eventprime_update_event_custom_fields($post_id, $post, $wp_post);
        $em_enable_recurrence_exist = metadata_exists('post', $post_id,'em_enable_recurrence');
        $previous_recurrence = get_post_meta($post_id,'em_enable_recurrence',true);
        $recurrence =  (isset($post['em_enable_recurrence'])) ? 1 : 0;
        if($em_enable_recurrence_exist==false || $previous_recurrence!=$recurrence || (!empty($post['ep_event_child_events_update_confirm']) && $post['ep_event_child_events_update_confirm']=='update_children'))
        {
            $this->eventprime_update_event_recurring_events($post_id, $post, $wp_post,$post_data);
        }
                // check for update recurrences
        if (!empty($post['em_enable_recurrence']) && $post['em_enable_recurrence'] == 1) {
            if (!empty($post['ep_event_child_events_update_confirm'])) {
                $ep_event_child_events_update_confirm = $post['ep_event_child_events_update_confirm'];
                if ($ep_event_child_events_update_confirm == 'update_children') {
                    // update child events
                    $this->ep_update_child_events($post_id);
                }
            }
        }
        
        do_action('ep_after_save_event_data', $post_id, $post);
        
        
        
    }
    
    public function eventprime_save_event_metadata($post_id,$post_data)
    {
        if(!empty($post_data))
        {
            foreach($post_data as $key=>$value)
            {
                if($key=='em_event_type')
                {
                    $value_array = maybe_unserialize($value);
                    if(is_array($value_array))
                    {
                        foreach($value_array as $val)
                        {
                            if($val!='0')
                            {
                                $value = $val;
                            }
                        }
                        
                    }
                    else
                    {
                        $value = $value_array;
                    }
                }
                update_post_meta($post_id,$key, $value);
            }
        }
    }

    //deprecated
    public function eventprime_update_post_meta($post_id, $post,$wp_post) {
        $ep_functions = new Eventprime_Basic_Functions;
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator;
        update_post_meta($post_id, 'em_id', $post['post_ID']);
        update_post_meta($post_id, 'em_name', $post['post_title']);

        // tax_input
        $event_type_val = $venue_val = $organizer_val = '';
        $performer_val = array();
        if (isset($post['tax_input']) && !empty($post['tax_input'])) {
            $tax_input = $post['tax_input'];
            // event type
            if (isset($tax_input['em_event_type'])) {
                $event_type_val = $tax_input['em_event_type'];
            }
            // venue
            //print_r($tax_input);die;
            if (isset($tax_input['em_venue']) && isset($tax_input['em_venue'])) {
                $event_venue_id = $tax_input['em_venue'];
                // check for old venue
                $old_event_venue_id = get_post_meta($post_id, 'em_venue', true);
                if (!empty($old_event_venue_id) && $old_event_venue_id != $event_venue_id) {
                    // remove event seat data
                    $event_seat_data = get_post_meta($post_id, 'em_seat_data', true);
                    if (!empty($event_seat_data)) {
                        update_post_meta($post_id, 'em_seat_data', array());
                    }
                }
                $venue_val = $tax_input['em_venue'];
            }
            // Organizer
            if (isset($tax_input['em_event_organizer']) && count($tax_input['em_event_organizer']) > 0) {
                $organizer_val = $tax_input['em_event_organizer'];
            }
            // Performer
            if (isset($post['em_performers']) && count($post['em_performers']) > 0) {
                $performer_val = $post['em_performers'];
            }
        }
        update_post_meta($post_id, 'em_event_type', $event_type_val);
        update_post_meta($post_id, 'em_venue', $venue_val);
        update_post_meta($post_id, 'em_organizer', $organizer_val);
        update_post_meta($post_id, 'em_performer', $performer_val);
        // event gallery
        $em_gallery_image_ids = isset($post['em_gallery_image_ids']) ? $post['em_gallery_image_ids'] : '';
        update_post_meta($post_id, 'em_gallery_image_ids', $em_gallery_image_ids);
        // start date
        $em_start_date = isset($post['em_start_date']) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_start_date'])) : '';
        update_post_meta($post_id, 'em_start_date', $em_start_date);
        //start time
        $em_start_time = isset($post['em_start_time']) ? sanitize_text_field($post['em_start_time']) : '';
        update_post_meta($post_id, 'em_start_time', $em_start_time);
        // hide start time
        $em_hide_start_time = isset($post['em_hide_event_start_time']) ? 1 : 0;
        update_post_meta($post_id, 'em_hide_event_start_time', $em_hide_start_time);
        // hide start date
        $em_hide_event_start_date = isset($post['em_hide_event_start_date']) ? 1 : 0;
        update_post_meta($post_id, 'em_hide_event_start_date', $em_hide_event_start_date);
        // end date
        $em_end_date = isset($post['em_end_date']) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_end_date'])) : $em_start_date;
        update_post_meta($post_id, 'em_end_date', $em_end_date);
        //end time
        $em_end_time = isset($post['em_end_time']) ? sanitize_text_field($post['em_end_time']) : '';
        update_post_meta($post_id, 'em_end_time', $em_end_time);
        // hide end time
        $em_hide_event_end_time = isset($post['em_hide_event_end_time']) ? 1 : 0;
        update_post_meta($post_id, 'em_hide_event_end_time', $em_hide_event_end_time);
        // hide end date
        $em_hide_end_date = isset($post['em_hide_end_date']) ? 1 : 0;
        update_post_meta($post_id, 'em_hide_end_date', $em_hide_end_date);
        // all day
        $em_all_day = isset($post['em_all_day']) ? 1 : 0;
        update_post_meta($post_id, 'em_all_day', $em_all_day);
        // if event is all day then end date will be same as start date
        if ($em_all_day == 1) {
            $em_end_date = $em_start_date;
            update_post_meta($post_id, 'em_end_date', $em_end_date);
            $em_start_time = '12:00 AM';
            $em_end_time = '11:59 PM';
            update_post_meta($post_id, 'em_start_time', $em_start_time);
            update_post_meta($post_id, 'em_end_time', $em_end_time);
        } else {
            if ($em_start_date > $em_end_date) {
                update_post_meta($post_id, 'em_end_date', $em_start_date);
            } else if ($em_start_date == $em_end_date) {
                if ($em_start_time == $em_end_time) {
                    if (empty($em_start_time)) {
                        update_post_meta($post_id, 'em_start_time', '12:00 AM');
                        update_post_meta($post_id, 'em_end_time', '11:59 PM');
                    } else {
                        if ($em_end_time !== '11:59 PM') {
                            update_post_meta($post_id, 'em_end_time', '11:59 PM');
                        }
                    }
                }
            } else if ($em_start_date < $em_end_date) {
                if (empty($em_start_time)) {
                    update_post_meta($post_id, 'em_start_time', '12:00 AM');
                    update_post_meta($post_id, 'em_end_time', '11:59 PM');
                } else if (!empty($em_end_time)) {
                    update_post_meta($post_id, 'em_end_time', $em_end_time);
                } else {
                    if ($em_end_time !== '11:59 PM') {
                        update_post_meta($post_id, 'em_end_time', '11:59 PM');
                    }
                }
            }
        }
        // update start and end datetime meta
        $ep_date_time_format = 'Y-m-d';
        $start_date = get_post_meta($post_id, 'em_start_date', true);
        $start_time = get_post_meta($post_id, 'em_start_time', true);
        $merge_start_date_time = $ep_functions->ep_datetime_to_timestamp($ep_functions->ep_timestamp_to_date($start_date, 'Y-m-d', 1) . ' ' . $start_time, $ep_date_time_format, '', 0, 1);
        if (!empty($merge_start_date_time)) {
            update_post_meta($post_id, 'em_start_date_time', $merge_start_date_time);
        }
        $end_date = get_post_meta($post_id, 'em_end_date', true);
        $end_time = get_post_meta($post_id, 'em_end_time', true);
        $merge_end_date_time = $ep_functions->ep_datetime_to_timestamp($ep_functions->ep_timestamp_to_date($end_date, 'Y-m-d', 1) . ' ' . $end_time, $ep_date_time_format, '', 0, 1);
        if (!empty($merge_end_date_time)) {
            update_post_meta($post_id, 'em_end_date_time', $merge_end_date_time);
        }
        
        
        //event date placeholder
        $em_event_date_placeholder = isset($post['em_event_date_placeholder']) ? sanitize_text_field($post['em_event_date_placeholder']) : '';
        update_post_meta($post_id, 'em_event_date_placeholder', $em_event_date_placeholder);
        $em_event_date_placeholder_custom_note = '';
        if (!empty($em_event_date_placeholder) && $em_event_date_placeholder == 'custom_note') {
            $em_event_date_placeholder_custom_note = sanitize_text_field($post['em_event_date_placeholder_custom_note']);
        }
        update_post_meta($post_id, 'em_event_date_placeholder_custom_note', $em_event_date_placeholder_custom_note);
        // add event more dates
        $em_event_more_dates = isset($post['em_event_more_dates']) ? 1 : 0;
        update_post_meta($post_id, 'em_event_more_dates', $em_event_more_dates);
        $event_more_dates = array();
        if (isset($post['em_event_more_dates']) && !empty($post['em_event_more_dates'])) {
            if (isset($post['em_event_add_more_dates']) && count($post['em_event_add_more_dates']) > 0) {
                foreach ($post['em_event_add_more_dates'] as $key => $more_dates) {
                    $new_date = array();
                    $new_date['uid'] = absint($more_dates['uid']);
                    $new_date['date'] = $ep_functions->ep_date_to_timestamp(sanitize_text_field($more_dates['date']));
                    $new_date['time'] = sanitize_text_field($more_dates['time']);
                    $new_date['label'] = sanitize_text_field($more_dates['label']);
                    $event_more_dates[] = $new_date;
                }
            }
        }
        update_post_meta($post_id, 'em_event_add_more_dates', $event_more_dates);
        // booking & tickets
        $em_enable_booking = isset($post['em_enable_booking']) ? sanitize_text_field($post['em_enable_booking']) : '';
        if ($em_enable_booking == 'bookings_on') {
            // check for ticket. If no ticket created then bookings will be off
            $ep_event_has_ticket = absint($post['ep_event_has_ticket']);
            if ($ep_event_has_ticket == 0) {
                $em_enable_booking = 'bookings_off';
            }
        }
        update_post_meta($post_id, 'em_enable_booking', $em_enable_booking);
        // check for external booking
        if (!empty($em_enable_booking) && $em_enable_booking == 'external_bookings') {
            $em_custom_link = isset($post['em_custom_link']) && !empty($post['em_custom_link']) ? sanitize_url($post['em_custom_link']) : '';
            update_post_meta($post_id, 'em_custom_link', $em_custom_link);
            // open in new browser
            $em_custom_link_new_browser = isset($post['em_custom_link_new_browser']) ? 1 : 0;
            update_post_meta($post_id, 'em_custom_link_new_browser', $em_custom_link_new_browser);
        }
        // One time event fee
        $em_fixed_event_price = isset($post['em_fixed_event_price']) && !empty($post['em_fixed_event_price']) ? sanitize_text_field($post['em_fixed_event_price']) : '';
        update_post_meta($post_id, 'em_fixed_event_price', $em_fixed_event_price);
        // hide booking status
        $em_hide_booking_status = isset($post['em_hide_booking_status']) ? 1 : 0;
        update_post_meta($post_id, 'em_hide_booking_status', $em_hide_booking_status);
        // allow cancellation option
        $em_allow_cancellations = isset($post['em_allow_cancellations']) ? 1 : 0;
        update_post_meta($post_id, 'em_allow_cancellations', $em_allow_cancellations);

        
        
        if (isset($post['em_ticket_category_data']) && !empty($post['em_ticket_category_data'])) {
            $em_ticket_category_data = json_decode(stripslashes($post['em_ticket_category_data']), true);
        }

        if (!empty($em_ticket_category_data)) {
            $cat_priority = 1;
            foreach ($em_ticket_category_data as $cat) {
                $cat_id = $cat['id'];
                $get_field_data = '';
                $arg = array();
                if (!empty($cat_id)) {
                    $get_field_data = $this->get_all_result('TICKET_CATEGORIES', '*', array('event_id' => $post_id, 'id' => $cat_id));
                    $data = array('name' => $cat['name'], 'capacity' => $cat['capacity'], 'priority' => $cat_priority, 'last_updated_by' => get_current_user_id(), 'updated_at' => wp_date("Y-m-d H:i:s", time()));
                    foreach ($data as $key => $value) {
                        $arg[] = $ep_activator->get_db_table_field_type('TICKET_CATEGORIES', $key);
                    }
                    $this->update_row('TICKET_CATEGORIES', 'id', $cat_id, $data, $arg, '%d');
                } else {
                    $save_data = array();
                    $arg = array();
                    $save_data['event_id'] = $post_id;
                    $save_data['name'] = $cat['name'];
                    $save_data['capacity'] = $cat['capacity'];
                    $save_data['priority'] = 1;
                    $save_data['status'] = 1;
                    $save_data['created_by'] = get_current_user_id();
                    $save_data['created_at'] = wp_date("Y-m-d H:i:s", time());
                    foreach ($save_data as $key => $value) {
                        $arg[] = $ep_activator->get_db_table_field_type('TICKET_CATEGORIES', $key);
                    }
                    $cat_id = $this->insert_row('TICKET_CATEGORIES', $save_data, $arg);
                }
                $cat_priority++;
                //save tickets
                if (isset($cat['tickets']) && !empty($cat['tickets'])) {
                    $cat_ticket_priority = 1;
                    foreach ($cat['tickets'] as $ticket) {
                        $ticket_data = array();
                        if (isset($ticket['id']) && !empty($ticket['id'])) {
                            $ticket_id = (int) $ticket['id'];
                            if (!empty($ticket_id)) {
                                $get_ticket_data = $this->get_all_result('TICKET', '*', array('id' => $ticket_id));
                                if (!empty($get_ticket_data)) {
                                    $ticket_data['name'] = addslashes($ticket['name']);
                                    $ticket_data['description'] = isset($ticket['description']) ? addslashes(str_replace('"', "'", $ticket['description'])) : '';
                                    $ticket_data['price'] = isset($ticket['price']) ? number_format(floatval($ticket['price']),2,'.','') : 0;
                                    $ticket_data['capacity'] = isset($ticket['capacity']) ? absint($ticket['capacity']) : 0;
                                    $ticket_data['icon'] = isset($ticket['icon']) ? absint($ticket['icon']) : '';
                                    $ticket_data['priority'] = $cat_ticket_priority;
                                    $ticket_data['updated_at'] = wp_date("Y-m-d H:i:s", time());
                                    $ticket_data['additional_fees'] = ( isset($ticket['ep_additional_ticket_fee_data']) && !empty($ticket['ep_additional_ticket_fee_data']) ) ? wp_json_encode($ticket['ep_additional_ticket_fee_data']) : '';
                                    $ticket_data['allow_cancellation'] = isset($ticket['allow_cancellation']) ? absint($ticket['allow_cancellation']) : 0;
                                    $ticket_data['show_remaining_tickets'] = isset($ticket['show_remaining_tickets']) ? absint($ticket['show_remaining_tickets']) : 0;
                                    // date
                                    $start_date = [];
                                    if (isset($ticket['em_ticket_start_booking_type']) && !empty($ticket['em_ticket_start_booking_type'])) {
                                        $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                        if ($ticket['em_ticket_start_booking_type'] == 'custom_date') {
                                            if (isset($ticket['em_ticket_start_booking_date']) && !empty($ticket['em_ticket_start_booking_date'])) {
                                                $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                            }
                                            if (isset($ticket['em_ticket_start_booking_time']) && !empty($ticket['em_ticket_start_booking_time'])) {
                                                $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                            }
                                        } elseif ($ticket['em_ticket_start_booking_type'] == 'event_date') {
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        } elseif ($ticket['em_ticket_start_booking_type'] == 'relative_date') {
                                            if (isset($ticket['em_ticket_start_booking_days']) && !empty($ticket['em_ticket_start_booking_days'])) {
                                                $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                            }
                                            if (isset($ticket['em_ticket_start_booking_days_option']) && !empty($ticket['em_ticket_start_booking_days_option'])) {
                                                $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                            }
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_starts'] = wp_json_encode($start_date);
                                    // end date
                                    $end_date = [];
                                    if (isset($ticket['em_ticket_ends_booking_type']) && !empty($ticket['em_ticket_ends_booking_type'])) {
                                        $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                        if ($ticket['em_ticket_ends_booking_type'] == 'custom_date') {
                                            if (isset($ticket['em_ticket_ends_booking_date']) && !empty($ticket['em_ticket_ends_booking_date'])) {
                                                $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                            }
                                            if (isset($ticket['em_ticket_ends_booking_time']) && !empty($ticket['em_ticket_ends_booking_time'])) {
                                                $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                            }
                                        } elseif ($ticket['em_ticket_ends_booking_type'] == 'event_date') {
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        } elseif ($ticket['em_ticket_ends_booking_type'] == 'relative_date') {
                                            if (isset($ticket['em_ticket_ends_booking_days']) && !empty($ticket['em_ticket_ends_booking_days'])) {
                                                $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                            }
                                            if (isset($ticket['em_ticket_ends_booking_days_option']) && !empty($ticket['em_ticket_ends_booking_days_option'])) {
                                                $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                            }
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_ends'] = wp_json_encode($end_date);

                                    // visibility
                                    $ticket_data['visibility'] = $ticket_visibility = array();
                                    if (isset($ticket['em_tickets_user_visibility']) && !empty($ticket['em_tickets_user_visibility'])) {
                                        $ticket_visibility['em_tickets_user_visibility'] = $ticket['em_tickets_user_visibility'];
                                    }
                                    if (isset($ticket['em_ticket_for_invalid_user']) && !empty($ticket['em_ticket_for_invalid_user'])) {
                                        $ticket_visibility['em_ticket_for_invalid_user'] = $ticket['em_ticket_for_invalid_user'];
                                    }
                                    if (isset($ticket['em_tickets_visibility_time_restrictions']) && !empty($ticket['em_tickets_visibility_time_restrictions'])) {
                                        $ticket_visibility['em_tickets_visibility_time_restrictions'] = $ticket['em_tickets_visibility_time_restrictions'];
                                    }
                                    if (isset($ticket['em_ticket_visibility_user_roles']) && !empty($ticket['em_ticket_visibility_user_roles'])) {
                                        $ticket_visibility['em_ticket_visibility_user_roles'] = $ticket['em_ticket_visibility_user_roles'];
                                    }
                                    if (!empty($ticket_visibility)) {
                                        $ticket_data['visibility'] = wp_json_encode($ticket_visibility);
                                    }

                                    $ticket_data['show_ticket_booking_dates'] = (isset($ticket['show_ticket_booking_dates']) ) ? 1 : 0;
                                    $ticket_data['min_ticket_no'] = isset($ticket['min_ticket_no']) ? absint($ticket['min_ticket_no']) : 0;
                                    $ticket_data['max_ticket_no'] = isset($ticket['max_ticket_no']) ? absint($ticket['max_ticket_no']) : 0;
                                    if (isset($ticket['offers']) && !empty($ticket['offers'])) {
                                        $ticket_data['offers'] = $ticket['offers'];
                                    }
                                    $ticket_data['multiple_offers_option'] = ( isset($ticket['multiple_offers_option']) && !empty($ticket['multiple_offers_option']) ) ? $ticket['multiple_offers_option'] : '';
                                    $ticket_data['multiple_offers_max_discount'] = ( isset($ticket['multiple_offers_max_discount']) && !empty($ticket['multiple_offers_max_discount']) ) ? $ticket['multiple_offers_max_discount'] : '';
                                    $ticket_data['ticket_template_id'] = ( isset($ticket['ticket_template_id']) && !empty($ticket['ticket_template_id']) ) ? $ticket['ticket_template_id'] : '';
                                    $format = array();
                                    foreach ($ticket_data as $key => $value) {
                                        $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                                    }
                                    $this->update_row('TICKET', 'id', $ticket_id, $ticket_data, $format);
                                } else {
                                    $ticket_data = $this->ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority);
                                    $format = array();
                                    foreach ($ticket_data as $key => $value) {
                                        $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                                    }
                                    $result = $this->insert_row('TICKET', $ticket_data, $format);
                                }
                            } else {
                                $ticket_data = $this->ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority);
                                $format = array();
                                foreach ($ticket_data as $key => $value) {
                                    $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                                }
                                $result = $this->insert_row('TICKET', $ticket_data, $format);
                            }
                        } else {
                            $ticket_data = $this->ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority);
                            $format = array();
                            foreach ($ticket_data as $key => $value) {
                                $format[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                            }
                            $result = $this->insert_row('TICKET', $ticket_data, $format);
                        }
                        $cat_ticket_priority++;
                    }
                }
            }
        }
        // delete category
        if (isset($post['em_ticket_category_delete_ids']) && !empty($post['em_ticket_category_delete_ids'])) {
            $em_ticket_category_delete_ids = $post['em_ticket_category_delete_ids'];
            $del_ids = json_decode(stripslashes($em_ticket_category_delete_ids));
            if (is_string($em_ticket_category_delete_ids) && is_array(json_decode(stripslashes($em_ticket_category_delete_ids))) && json_last_error() == JSON_ERROR_NONE) {
                foreach ($del_ids as $id) {
                    $this->remove_row('TICKET_CATEGORIES', 'id', $id);
                }
            }
        }
        // save tickets
        if (isset($post['em_ticket_individual_data']) && !empty($post['em_ticket_individual_data'])) {
            $em_ticket_individual_data = json_decode(stripslashes($post['em_ticket_individual_data']), true);
            if (isset($em_ticket_individual_data) && !empty($em_ticket_individual_data)) {
                $tic = 0;
                foreach ($em_ticket_individual_data as $ticket) {
                    if (isset($ticket['id']) && !empty($ticket['id'])) {
                        
                        $ticket_id = (int) $ticket['id'];
                        if(!empty($ticket_id))
                        {
                        $ticket_data = array();
                        $ticket_data['name'] = addslashes($ticket['name']);
                        $ticket_data['description'] = isset($ticket['description']) ? addslashes(str_replace('"', "'", $ticket['description'])) : '';
                        $ticket_data['price'] = isset($ticket['price']) ? number_format(floatval($ticket['price']),2,'.','') : 0;
                        $ticket_data['capacity'] = isset($ticket['capacity']) ? absint($ticket['capacity']) : 0;
                        $ticket_data['icon'] = isset($ticket['icon']) ? absint($ticket['icon']) : '';
                        $ticket_data['updated_at'] = wp_date("Y-m-d H:i:s", time());
                        $ticket_data['additional_fees'] = ( isset($ticket['ep_additional_ticket_fee_data']) && !empty($ticket['ep_additional_ticket_fee_data']) ) ? wp_json_encode($ticket['ep_additional_ticket_fee_data']) : '';
                        $ticket_data['allow_cancellation'] = isset($ticket['allow_cancellation']) ? absint($ticket['allow_cancellation']) : 0;
                        $ticket_data['show_remaining_tickets'] = isset($ticket['show_remaining_tickets']) ? absint($ticket['show_remaining_tickets']) : 0;
                        // date
                        $start_date = [];
                        if (isset($ticket['em_ticket_start_booking_type']) && !empty($ticket['em_ticket_start_booking_type'])) {
                            $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                            if ($ticket['em_ticket_start_booking_type'] == 'custom_date') {
                                if (isset($ticket['em_ticket_start_booking_date']) && !empty($ticket['em_ticket_start_booking_date'])) {
                                    $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                }
                                if (isset($ticket['em_ticket_start_booking_time']) && !empty($ticket['em_ticket_start_booking_time'])) {
                                    $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                }
                            } elseif ($ticket['em_ticket_start_booking_type'] == 'event_date') {
                                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                            } elseif ($ticket['em_ticket_start_booking_type'] == 'relative_date') {
                                if (isset($ticket['em_ticket_start_booking_days']) && !empty($ticket['em_ticket_start_booking_days'])) {
                                    $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                }
                                if (isset($ticket['em_ticket_start_booking_days_option']) && !empty($ticket['em_ticket_start_booking_days_option'])) {
                                    $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                }
                                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                            }
                        }
                        $ticket_data['booking_starts'] = wp_json_encode($start_date);
                        // end date
                        $end_date = [];
                        if (isset($ticket['em_ticket_ends_booking_type']) && !empty($ticket['em_ticket_ends_booking_type'])) {
                            $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                            if ($ticket['em_ticket_ends_booking_type'] == 'custom_date') {
                                if (isset($ticket['em_ticket_ends_booking_date']) && !empty($ticket['em_ticket_ends_booking_date'])) {
                                    $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                }
                                if (isset($ticket['em_ticket_ends_booking_time']) && !empty($ticket['em_ticket_ends_booking_time'])) {
                                    $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                }
                            } elseif ($ticket['em_ticket_ends_booking_type'] == 'event_date') {
                                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                            } elseif ($ticket['em_ticket_ends_booking_type'] == 'relative_date') {
                                if (isset($ticket['em_ticket_ends_booking_days']) && !empty($ticket['em_ticket_ends_booking_days'])) {
                                    $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                }
                                if (isset($ticket['em_ticket_ends_booking_days_option']) && !empty($ticket['em_ticket_ends_booking_days_option'])) {
                                    $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                }
                                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                            }
                        }
                        $ticket_data['booking_ends'] = wp_json_encode($end_date);

                        // visibility
                        $ticket_data['visibility'] = $ticket_visibility = array();
                        if (isset($ticket['em_tickets_user_visibility']) && !empty($ticket['em_tickets_user_visibility'])) {
                            $ticket_visibility['em_tickets_user_visibility'] = $ticket['em_tickets_user_visibility'];
                        }
                        if (isset($ticket['em_ticket_for_invalid_user']) && !empty($ticket['em_ticket_for_invalid_user'])) {
                            $ticket_visibility['em_ticket_for_invalid_user'] = $ticket['em_ticket_for_invalid_user'];
                        }
                        if (isset($ticket['em_tickets_visibility_time_restrictions']) && !empty($ticket['em_tickets_visibility_time_restrictions'])) {
                            $ticket_visibility['em_tickets_visibility_time_restrictions'] = $ticket['em_tickets_visibility_time_restrictions'];
                        }
                        if (isset($ticket['em_ticket_visibility_user_roles']) && !empty($ticket['em_ticket_visibility_user_roles'])) {
                            $ticket_visibility['em_ticket_visibility_user_roles'] = $ticket['em_ticket_visibility_user_roles'];
                        }
                        if (!empty($ticket_visibility)) {
                            $ticket_data['visibility'] = wp_json_encode($ticket_visibility);
                        }

                        $ticket_data['show_ticket_booking_dates'] = (isset($ticket['show_ticket_booking_dates']) ) ? 1 : 0;
                        $ticket_data['min_ticket_no'] = isset($ticket['min_ticket_no']) ? absint($ticket['min_ticket_no']) : 0;
                        $ticket_data['max_ticket_no'] = isset($ticket['max_ticket_no']) ? absint($ticket['max_ticket_no']) : 0;
                        if (isset($ticket['offers']) && !empty($ticket['offers'])) {
                            $ticket_data['offers'] = $ticket['offers'];
                        }
                        $ticket_data['multiple_offers_option'] = ( isset($ticket['multiple_offers_option']) && !empty($ticket['multiple_offers_option']) ) ? $ticket['multiple_offers_option'] : '';
                        $ticket_data['multiple_offers_max_discount'] = ( isset($ticket['multiple_offers_max_discount']) && !empty($ticket['multiple_offers_max_discount']) ) ? $ticket['multiple_offers_max_discount'] : '';
                        $ticket_data['ticket_template_id'] = ( isset($ticket['ticket_template_id']) && !empty($ticket['ticket_template_id']) ) ? $ticket['ticket_template_id'] : '';
                        $arg = array();
                        foreach ($ticket_data as $key => $value) {
                            $arg[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                        }
                        $this->update_row('TICKET', 'id', $ticket_id, $ticket_data, $arg, '%d');
                        }
                        else {
                            $ticket_data = $this->ep_add_individual_tickets($post_id, $ticket);
                        $arg = array();
                        foreach ($ticket_data as $key => $value) {
                            $arg[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                        }
                        $result = $this->insert_row('TICKET', $ticket_data, $arg);
                       }
                    } else {
                        $ticket_data = $this->ep_add_individual_tickets($post_id, $ticket);
                        $arg = array();
                        foreach ($ticket_data as $key => $value) {
                            $arg[] = $ep_activator->get_db_table_field_type('TICKET', $key);
                        }
                        $result = $this->insert_row('TICKET', $ticket_data, $arg);
                    }
                    $tic++;
                    //error_log($tic);
                }
            }
        }
        // delete tickets
        if (isset($post['em_ticket_individual_delete_ids']) && !empty($post['em_ticket_individual_delete_ids'])) {
            $em_ticket_individual_delete_ids = $post['em_ticket_individual_delete_ids'];
            $del_ids = json_decode(stripslashes($em_ticket_individual_delete_ids));
            if (is_string($em_ticket_individual_delete_ids) && is_array(json_decode(stripslashes($em_ticket_individual_delete_ids))) && json_last_error() == JSON_ERROR_NONE) {
                foreach ($del_ids as $id) {
                    $get_field_data = $this->get_row('TICKET', $id);
                    if (!empty($get_field_data)) {
                        $this->remove_row('TICKET', 'id', $id, '%d');
                    }
                }
            }
        }
        
        // Save tickets order 
		if( isset( $post['ep_ticket_order_arr'] ) && !empty( $post['ep_ticket_order_arr'] ) ) {
			$ep_ticket_order_arr = sanitize_text_field( $post['ep_ticket_order_arr'] ); 
			$ep_ticket_order_arr_explode = explode( ',', $ep_ticket_order_arr ); 

			$get_existing_individual_ticket_lists = $ep_functions->get_existing_individual_ticket_lists( $post->ID ); 
			foreach ( $get_existing_individual_ticket_lists as $ticket ) { 
				if ( ! in_array( $ticket->id, $ep_ticket_order_arr_explode ) ) {
					array_push( $ep_ticket_order_arr_explode, $ticket->id ); 
				}
			}

			update_post_meta( $post_id, 'ep_ticket_order_arr', $ep_ticket_order_arr_explode ); 
		}
        
        // event checkout fields
        $event_checkout_attendee_fields = array();
        // check for name field
        if (isset($post['em_event_checkout_name']) && !empty($post['em_event_checkout_name'])) {
            $event_checkout_attendee_fields['em_event_checkout_name'] = absint($post['em_event_checkout_name']);
            if (isset($post['em_event_checkout_name_first_name']) && !empty($post['em_event_checkout_name_first_name'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_first_name'] = absint($post['em_event_checkout_name_first_name']);
            }
            if (isset($post['em_event_checkout_name_first_name_required']) && !empty($post['em_event_checkout_name_first_name_required'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_first_name_required'] = absint($post['em_event_checkout_name_first_name_required']);
            }
            if (isset($post['em_event_checkout_name_middle_name']) && !empty($post['em_event_checkout_name_middle_name'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_middle_name'] = absint($post['em_event_checkout_name_middle_name']);
            }
            if (isset($post['em_event_checkout_name_middle_name_required']) && !empty($post['em_event_checkout_name_middle_name_required'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_middle_name_required'] = absint($post['em_event_checkout_name_middle_name_required']);
            }
            if (isset($post['em_event_checkout_name_last_name']) && !empty($post['em_event_checkout_name_last_name'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_last_name'] = absint($post['em_event_checkout_name_last_name']);
            }
            if (isset($post['em_event_checkout_name_last_name_required']) && !empty($post['em_event_checkout_name_last_name_required'])) {
                $event_checkout_attendee_fields['em_event_checkout_name_last_name_required'] = absint($post['em_event_checkout_name_last_name_required']);
            }
        }
        // check for checkout fields
        if (isset($post['em_event_checkout_fields_data']) && count($post['em_event_checkout_fields_data']) > 0) {
            $event_checkout_attendee_fields['em_event_checkout_fields_data'] = array();
            foreach ($post['em_event_checkout_fields_data'] as $cfd) {
                $event_checkout_attendee_fields['em_event_checkout_fields_data'][] = absint($cfd);
            }
            // get required field data
            if (isset($post['em_event_checkout_fields_data_required']) && count($post['em_event_checkout_fields_data_required']) > 0) {
                $event_checkout_attendee_fields['em_event_checkout_fields_data_required'] = array();
                foreach ($post['em_event_checkout_fields_data_required'] as $cfdr) {
                    $event_checkout_attendee_fields['em_event_checkout_fields_data_required'][] = absint($cfdr);
                }
            }
        }
        update_post_meta($post_id, 'em_event_checkout_attendee_fields', $event_checkout_attendee_fields);
        // event checkout fixed fields
        $event_checkout_fixed_fields = array();
        if (isset($post['em_event_checkout_fixed_terms_enabled']) && absint($post['em_event_checkout_fixed_terms_enabled']) == 1) {
            $event_checkout_fixed_fields['em_event_checkout_fixed_terms_enabled'] = $post['em_event_checkout_fixed_terms_enabled'];
            if (isset($post['em_event_checkout_fixed_terms_label'])) {
                $event_checkout_fixed_fields['em_event_checkout_fixed_terms_label'] = sanitize_text_field($post['em_event_checkout_fixed_terms_label']);
            }
            if (isset($post['em_event_checkout_fixed_terms_option'])) {
                $event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] = sanitize_text_field($post['em_event_checkout_fixed_terms_option']);
            }
            if (isset($post['em_event_checkout_fixed_terms_content'])) {
                if ($event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] == 'page') {
                    $event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] = absint($post['em_event_checkout_fixed_terms_content']);
                } else if ($event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] == 'content') {
                    $event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] = wp_kses_post($post['em_event_checkout_fixed_terms_content']);
                } else {
                    $event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] = sanitize_text_field($post['em_event_checkout_fixed_terms_content']);
                }
            }
        }
        update_post_meta($post_id, 'em_event_checkout_fixed_fields', $event_checkout_fixed_fields);
        $em_event_checkout_booking_fields = array();
        // check for booking fields
        if (isset($post['em_event_booking_fields_data']) && count($post['em_event_booking_fields_data']) > 0) {
            $em_event_checkout_booking_fields['em_event_booking_fields_data'] = array();
            foreach ($post['em_event_booking_fields_data'] as $cfd) {
                $em_event_checkout_booking_fields['em_event_booking_fields_data'][] = absint($cfd);
            }
            // get required field data
            if (isset($post['em_event_booking_fields_data_required']) && count($post['em_event_booking_fields_data_required']) > 0) {
                $em_event_checkout_booking_fields['em_event_booking_fields_data_required'] = array();
                foreach ($post['em_event_booking_fields_data_required'] as $cfdr) {
                    $em_event_checkout_booking_fields['em_event_booking_fields_data_required'][] = absint($cfdr);
                }
            }
        }
        update_post_meta($post_id, 'em_event_checkout_booking_fields', $em_event_checkout_booking_fields);
        

            // handle recurring events request
        if (isset($post['em_enable_recurrence']) && $post['em_enable_recurrence'] == 1) {
            update_post_meta($post_id, 'em_enable_recurrence', 1);
            $add_recurrence = 1;
            $old_em_recurrence_step = get_post_meta($post_id, 'em_recurrence_step', true);
            $old_em_recurrence_interval = get_post_meta($post_id, 'em_recurrence_interval', true);
            $em_recurrence_step = (isset($post['em_recurrence_step']) && !empty($post['em_recurrence_step']) ) ? absint($post['em_recurrence_step']) : 1;
            update_post_meta($post_id, 'em_recurrence_step', $em_recurrence_step);
            // update the parent event first
            do_action('ep_update_parent_event_status', $post_id, $post);

            if (isset($post['em_recurrence_interval']) && !empty($post['em_recurrence_interval'])) {
                $em_recurrence_interval = sanitize_text_field($post['em_recurrence_interval']);
                update_post_meta($post_id, 'em_recurrence_interval', $em_recurrence_interval);
                if ((!empty($old_em_recurrence_step) && $old_em_recurrence_step == $em_recurrence_step ) && (!empty($old_em_recurrence_interval) && $old_em_recurrence_interval == $em_recurrence_interval )) {
                    $add_recurrence = 0;
                }
                if (empty($add_recurrence)) {
                    // check for weekly interval
                    if ($em_recurrence_interval == 'weekly') {
                        $weekly_days = $post['em_selected_weekly_day'];
                        $old_weekly_days = get_post_meta($post_id, 'em_selected_weekly_day', true);
                        if ($old_weekly_days != $weekly_days) {
                            $add_recurrence = 1;
                        }
                    }
                    // check for monthly interval
                    if ($em_recurrence_interval == 'monthly') {
                        $monthly_day = $post['em_recurrence_monthly_day'];
                        $old_monthly_day = get_post_meta($post_id, 'em_recurrence_monthly_day', true);
                        if ($old_monthly_day != $monthly_day) {
                            $add_recurrence = 1;
                        } else {
                            if ($monthly_day == 'day') {
                                $em_recurrence_monthly_weekno = $post['em_recurrence_monthly_weekno'];
                                $old_em_recurrence_monthly_weekno = get_post_meta($post_id, 'em_recurrence_monthly_weekno', true);
                                if ($old_em_recurrence_monthly_weekno != $em_recurrence_monthly_weekno) {
                                    $add_recurrence = 1;
                                } else {
                                    $em_recurrence_monthly_fullweekday = $post['em_recurrence_monthly_fullweekday'];
                                    $old_em_recurrence_monthly_fullweekday = get_post_meta($post_id, 'em_recurrence_monthly_fullweekday', true);
                                    if ($old_em_recurrence_monthly_fullweekday != $em_recurrence_monthly_fullweekday) {
                                        $add_recurrence = 1;
                                    }
                                }
                            }
                        }
                    }
                    // check for yearly interval
                    if ($em_recurrence_interval == 'yearly') {
                        $yearly_day = $post['em_recurrence_yearly_day'];
                        $old_yearly_day = get_post_meta($post_id, 'em_recurrence_yearly_day', true);
                        if ($old_yearly_day != $yearly_day) {
                            $add_recurrence = 1;
                        } else {
                            if ($yearly_day == 'day') {
                                $em_recurrence_yearly_weekno = $post['em_recurrence_yearly_weekno'];
                                $old_em_recurrence_yearly_weekno = get_post_meta($post_id, 'em_recurrence_yearly_weekno', true);
                                if ($old_em_recurrence_yearly_weekno != $em_recurrence_yearly_weekno) {
                                    $add_recurrence = 1;
                                } else {
                                    $em_recurrence_yearly_fullweekday = $post['em_recurrence_yearly_fullweekday'];
                                    $old_em_recurrence_yearly_fullweekday = get_post_meta($post_id, 'em_recurrence_yearly_fullweekday', true);
                                    if ($old_em_recurrence_yearly_fullweekday != $em_recurrence_yearly_fullweekday) {
                                        $add_recurrence = 1;
                                    } else {
                                        $em_recurrence_yearly_monthday = $post['em_recurrence_yearly_monthday'];
                                        $old_em_recurrence_yearly_monthday = get_post_meta($post_id, 'em_recurrence_yearly_monthday', true);
                                        if ($old_em_recurrence_yearly_monthday != $em_recurrence_yearly_monthday) {
                                            $add_recurrence = 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // check for advanced interval
                    if ($em_recurrence_interval == 'advanced') {
                        $advanced_dates = $post['em_recurrence_advanced_dates'];
                        $old_advanced_dates = get_post_meta($post_id, 'em_recurrence_advanced_dates', true);
                        if ($old_advanced_dates != $advanced_dates) {
                            $add_recurrence = 1;
                        }
                    }
                    // check for custom dates interval
                    if ($em_recurrence_interval == 'custom_dates') {
                        $custom_dates = json_decode(stripslashes($post['em_recurrence_selected_custom_dates']), true);
                        $old_custom_dates = get_post_meta($post_id, 'em_recurrence_selected_custom_dates', true);
                        if ($old_custom_dates != $custom_dates) {
                            $add_recurrence = 1;
                        }
                    }
                    // check for recurrence ends
                    if (isset($post['em_recurrence_ends']) && !empty($post['em_recurrence_ends'])) {
                        $em_recurrence_ends = $post['em_recurrence_ends'];
                        $old_recurrence_ends = get_post_meta($post_id, 'em_recurrence_ends', true);
                        if ($old_recurrence_ends != $em_recurrence_ends) {
                            $add_recurrence = 1;
                        } else {
                            if ($em_recurrence_ends == 'on') {
                                $em_recurrence_limit = $post['em_recurrence_limit'];
                                $old_em_recurrence_limit = get_post_meta($post_id, 'em_recurrence_limit', true);
                                if ($old_em_recurrence_limit != $em_recurrence_limit) {
                                    $add_recurrence = 1;
                                }
                            } else {
                                $em_recurrence_occurrence_time = $post['em_recurrence_occurrence_time'];
                                $old_em_recurrence_occurrence_time = get_post_meta($post_id, 'em_recurrence_occurrence_time', true);
                                if ($old_em_recurrence_occurrence_time != $em_recurrence_occurrence_time) {
                                    $add_recurrence = 1;
                                }
                            }
                        }
                    }
                }
                if ($add_recurrence) {
                    // first delete old child events
                    $this->ep_delete_child_events($post_id);
                    $em_recurrence_ends = (isset($post['em_recurrence_ends']) && !empty($post['em_recurrence_ends']) ) ? $post['em_recurrence_ends'] : 'after';
                    update_post_meta($post_id, 'em_recurrence_ends', $em_recurrence_ends);
                    $last_date_on = $stop_after = $recurrence_limit_timestamp = $start_date_only = '';
                    if ($em_recurrence_ends == 'on') {
                        $last_date_on = $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_recurrence_limit']));
                        if (empty($last_date_on)) {
                            $last_date_on = $ep_functions->ep_date_to_timestamp($ep_functions->ep_timestamp_to_date(current_time('timestamp',true)));
                        }
                        if (!empty($last_date_on)) {
                            update_post_meta($post_id, 'em_recurrence_limit', $last_date_on);
//                            $recurrence_limit = new DateTime('@' . $last_date_on);
//                            //$recurrence_limit->setTime( 0,0,0,0 );
//                            $recurrence_limit_timestamp = $recurrence_limit->getTimestamp();
                            $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

                            $recurrence_limit = new DateTime('now', $timezone);
                            $recurrence_limit->setTimestamp($last_date_on);

                            $recurrence_limit_timestamp = $recurrence_limit->getTimestamp();
                            
                        }
                        // update start date format
//                        $start_date_only = new DateTime('@' . $em_start_date);
//                        $start_date_only->setTime(0, 0, 0, 0);
                        
                        $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone());

                        $start_date_only = new DateTime('now', $timezone);
                        $start_date_only->setTimestamp($em_start_date);
                        $start_date_only->setTime(0, 0, 0); // Midnight in local time
                        
                    }

                    if ($em_recurrence_ends == 'after') {
                        $stop_after = absint($post['em_recurrence_occurrence_time']);
                        update_post_meta($post_id, 'em_recurrence_occurrence_time', $stop_after);
                    }

                    $data = array('start_date' => $em_start_date, 'start_time' => $em_start_time, 'end_date' => $em_end_date, 'end_time' => $em_end_time, 'recurrence_step' => $em_recurrence_step, 'recurrence_interval' => $em_recurrence_interval, 'last_date_on' => $last_date_on, 'stop_after' => $stop_after, 'recurrence_limit_timestamp' => $recurrence_limit_timestamp, 'start_date_only' => $start_date_only);
                    
                    switch ($em_recurrence_interval) {
                        case 'daily':
                            $this->ep_event_daily_recurrence($wp_post, $data, $post);
                            break;
                        case 'weekly':
                            $this->ep_event_weekly_recurrence($wp_post, $data, $post);
                            break;
                        case 'monthly':
                            $this->ep_event_monthly_recurrence($wp_post, $data, $post);
                            break;
                        case 'yearly':
                            $this->ep_event_yearly_recurrence($wp_post, $data, $post);
                            break;
                        case 'advanced':
                            $this->ep_event_advanced_recurrence($wp_post, $data, $post);
                            break;
                        case 'custom_dates':
                            $this->ep_event_custom_dates_recurrence($wp_post, $data, $post);
                            break;
                    }
                }
            }
        } else {
            update_post_meta($post_id, 'em_enable_recurrence', 0);
            // check if event have the child events and delete them
            if (isset($post['ep_event_count_child_events']) && !empty($post['ep_event_count_child_events'])) {
                $ep_event_count_child_events = absint($post['ep_event_count_child_events']);
                if (!empty($ep_event_count_child_events)) {
                    //delete the child events
                    $this->ep_delete_child_events($post_id);
                }
            }
            update_post_meta($post_id, 'em_recurrence_step', 0);
            update_post_meta($post_id, 'em_recurrence_interval', '');
        }
        // add other settings meta box
        $em_event_text_color = isset($post['em_event_text_color']) ? sanitize_text_field($post['em_event_text_color']) : '';
        update_post_meta($post_id, 'em_event_text_color', $em_event_text_color);
        $em_audience_notice = isset($post['em_audience_notice']) ? sanitize_textarea_field($post['em_audience_notice']) : '';
        update_post_meta($post_id, 'em_audience_notice', $em_audience_notice);
        // save social info
        $em_social_links = array();
        if (isset($post['em_social_links']) && count($post['em_social_links']) > 0) {
            foreach ($post['em_social_links'] as $social_key => $social_links) {
                if (!empty($social_links)) {
                    $em_social_links[$social_key] = sanitize_url($social_links);
                }
            }
        }
        update_post_meta($post_id, 'em_social_links', $em_social_links);

        // check for update recurrences
        if (!empty($post['em_enable_recurrence']) && $post['em_enable_recurrence'] == 1) {
            if (!empty($post['ep_event_child_events_update_confirm'])) {
                $ep_event_child_events_update_confirm = $post['ep_event_child_events_update_confirm'];
                if ($ep_event_child_events_update_confirm == 'update_children') {
                    // update child events
                    $this->ep_update_child_events($post_id);
                }
            }
        }
        // add result settings meta box
        $ep_select_result_page = isset($post['ep_select_result_page']) ? sanitize_text_field($post['ep_select_result_page']) : '';
        update_post_meta($post_id, 'ep_select_result_page', $ep_select_result_page);
        $ep_result_start_from_type = isset($post['ep_result_start_from_type']) ? sanitize_text_field($post['ep_result_start_from_type']) : '';
        update_post_meta($post_id, 'ep_result_start_from_type', $ep_result_start_from_type);
        //result start date
        $ep_result_start_date = isset($post['ep_result_start_date']) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['ep_result_start_date'])) : '';
        update_post_meta($post_id, 'ep_result_start_date', $ep_result_start_date);
        //result start time
        $ep_result_start_time = isset($post['ep_result_start_time']) ? sanitize_text_field($post['ep_result_start_time']) : '';
        update_post_meta($post_id, 'ep_result_start_time', $ep_result_start_time);
        $ep_result_start_days = isset($post['ep_result_start_days']) ? sanitize_text_field($post['ep_result_start_days']) : '';
        update_post_meta($post_id, 'ep_result_start_days', $ep_result_start_days);
        $ep_result_start_days_option = isset($post['ep_result_start_days_option']) ? sanitize_text_field($post['ep_result_start_days_option']) : '';
        update_post_meta($post_id, 'ep_result_start_days_option', $ep_result_start_days_option);
        $ep_result_start_event_option = isset($post['ep_result_start_event_option']) ? sanitize_text_field($post['ep_result_start_event_option']) : '';
        update_post_meta($post_id, 'ep_result_start_event_option', $ep_result_start_event_option);
        // edit booking
        $em_allow_edit_booking = $em_edit_booking_date_data = '';
        if (!empty($post['em_allow_edit_booking'])) {
            $em_allow_edit_booking = 1;
            $em_edit_booking_date_data = array(
                'em_edit_booking_date_type' => sanitize_text_field($post['em_edit_booking_date_type']),
                'em_edit_booking_date_date' => (!empty($post['em_edit_booking_date_date']) ? $ep_functions->ep_date_to_timestamp(sanitize_text_field($post['em_edit_booking_date_date'])) : '' ),
                'em_edit_booking_date_time' => sanitize_text_field($post['em_edit_booking_date_time']),
                'em_edit_booking_date_days' => sanitize_text_field($post['em_edit_booking_date_days']),
                'em_edit_booking_date_days_option' => sanitize_text_field($post['em_edit_booking_date_days_option']),
                'em_edit_booking_date_event_option' => sanitize_text_field($post['em_edit_booking_date_event_option']),
            );
        }
        update_post_meta($post_id, 'em_allow_edit_booking', $em_allow_edit_booking);
        update_post_meta($post_id, 'em_edit_booking_date_data', $em_edit_booking_date_data);
        do_action('ep_after_save_event_data', $post_id, $post);
    }

    public function ep_add_tickets_in_category($cat_id, $post_id, $ticket, $cat_ticket_priority) {
        $ticket_data['category_id'] = $cat_id;
        $ticket_data['event_id'] = $post_id;
        $ticket_data['name'] = addslashes($ticket['name']);
        $ticket_data['description'] = isset($ticket['description']) ? addslashes(str_replace('"', "'", $ticket['description'])) : '';
        $ticket_data['price'] = isset($ticket['price']) ? number_format(floatval($ticket['price']),2,'.','') : 0;
        $ticket_data['special_price'] = '';
        $ticket_data['capacity'] = isset($ticket['capacity']) ? absint($ticket['capacity']) : 0;
        $ticket_data['is_default'] = 1;
        $ticket_data['is_event_price'] = 0;
        $ticket_data['icon'] = isset($ticket['icon']) ? absint($ticket['icon']) : '';
        $ticket_data['priority'] = $cat_ticket_priority;
        $ticket_data['status'] = 1;
        $ticket_data['created_at'] = wp_date("Y-m-d H:i:s", time());
        // new
        $ticket_data['additional_fees'] = ( isset($ticket['ep_additional_ticket_fee_data']) && !empty($ticket['ep_additional_ticket_fee_data']) ) ? wp_json_encode($ticket['ep_additional_ticket_fee_data']) : '';
        $ticket_data['allow_cancellation'] = isset($ticket['allow_cancellation']) ? absint($ticket['allow_cancellation']) : 0;
        $ticket_data['show_remaining_tickets'] = isset($ticket['show_remaining_tickets']) ? absint($ticket['show_remaining_tickets']) : 0;
        // date
        $start_date = [];
        if (isset($ticket['em_ticket_start_booking_type']) && !empty($ticket['em_ticket_start_booking_type'])) {
            $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
            if ($ticket['em_ticket_start_booking_type'] == 'custom_date') {
                if (isset($ticket['em_ticket_start_booking_date']) && !empty($ticket['em_ticket_start_booking_date'])) {
                    $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                }
                if (isset($ticket['em_ticket_start_booking_time']) && !empty($ticket['em_ticket_start_booking_time'])) {
                    $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                }
            } elseif ($ticket['em_ticket_start_booking_type'] == 'event_date') {
                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
            } elseif ($ticket['em_ticket_start_booking_type'] == 'relative_date') {
                if (isset($ticket['em_ticket_start_booking_days']) && !empty($ticket['em_ticket_start_booking_days'])) {
                    $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                }
                if (isset($ticket['em_ticket_start_booking_days_option']) && !empty($ticket['em_ticket_start_booking_days_option'])) {
                    $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                }
                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
            }
        }
        $ticket_data['booking_starts'] = wp_json_encode($start_date);
        // end date
        $end_date = [];
        if (isset($ticket['em_ticket_ends_booking_type']) && !empty($ticket['em_ticket_ends_booking_type'])) {
            $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
            if ($ticket['em_ticket_ends_booking_type'] == 'custom_date') {
                if (isset($ticket['em_ticket_ends_booking_date']) && !empty($ticket['em_ticket_ends_booking_date'])) {
                    $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                }
                if (isset($ticket['em_ticket_ends_booking_time']) && !empty($ticket['em_ticket_ends_booking_time'])) {
                    $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                }
            } elseif ($ticket['em_ticket_ends_booking_type'] == 'event_date') {
                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
            } elseif ($ticket['em_ticket_ends_booking_type'] == 'relative_date') {
                if (isset($ticket['em_ticket_ends_booking_days']) && !empty($ticket['em_ticket_ends_booking_days'])) {
                    $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                }
                if (isset($ticket['em_ticket_ends_booking_days_option']) && !empty($ticket['em_ticket_ends_booking_days_option'])) {
                    $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                }
                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
            }
        }
        $ticket_data['booking_ends'] = wp_json_encode($end_date);
        // visibility
        $ticket_data['visibility'] = $ticket_visibility = array();
        if (isset($ticket['em_tickets_user_visibility']) && !empty($ticket['em_tickets_user_visibility'])) {
            $ticket_visibility['em_tickets_user_visibility'] = $ticket['em_tickets_user_visibility'];
        }
        if (isset($ticket['em_ticket_for_invalid_user']) && !empty($ticket['em_ticket_for_invalid_user'])) {
            $ticket_visibility['em_ticket_for_invalid_user'] = $ticket['em_ticket_for_invalid_user'];
        }
        if (isset($ticket['em_tickets_visibility_time_restrictions']) && !empty($ticket['em_tickets_visibility_time_restrictions'])) {
            $ticket_visibility['em_tickets_visibility_time_restrictions'] = $ticket['em_tickets_visibility_time_restrictions'];
        }
        if (!empty($ticket_visibility)) {
            $ticket_data['visibility'] = wp_json_encode($ticket_visibility);
        }
        $ticket_data['show_ticket_booking_dates'] = (isset($ticket['show_ticket_booking_dates']) ) ? 1 : 0;
        $ticket_data['min_ticket_no'] = isset($ticket['min_ticket_no']) ? absint($ticket['min_ticket_no']) : 0;
        $ticket_data['max_ticket_no'] = isset($ticket['max_ticket_no']) ? absint($ticket['max_ticket_no']) : 0;
        $ticket_data['offers'] = ( isset($ticket['offers']) && !empty($ticket['offers']) ) ? $ticket['offers'] : '';
        $ticket_data['multiple_offers_option'] = ( isset($ticket['multiple_offers_option']) && !empty($ticket['multiple_offers_option']) ) ? $ticket['multiple_offers_option'] : '';
        $ticket_data['multiple_offers_max_discount'] = ( isset($ticket['multiple_offers_max_discount']) && !empty($ticket['multiple_offers_max_discount']) ) ? $ticket['multiple_offers_max_discount'] : '';
        $ticket_data['ticket_template_id'] = ( isset($ticket['ticket_template_id']) && !empty($ticket['ticket_template_id']) ) ? $ticket['ticket_template_id'] : '';
        return $ticket_data;
    }

    // add individual tickets
    public function ep_add_individual_tickets($post_id, $ticket) {
        $ticket_data = array();
        $ticket_data['category_id'] = 0;
        $ticket_data['event_id'] = $post_id;
        $ticket_data['name'] = addslashes($ticket['name']);
        $ticket_data['description'] = isset($ticket['description']) ? addslashes(str_replace('"', "'", $ticket['description'])) : '';
        $ticket_data['price'] = isset($ticket['price']) ? number_format(floatval($ticket['price']),2,'.','') : 0;
        $ticket_data['special_price'] = '';
        $ticket_data['capacity'] = isset($ticket['capacity']) ? absint($ticket['capacity']) : 0;
        $ticket_data['is_default'] = 1;
        $ticket_data['is_event_price'] = 0;
        $ticket_data['icon'] = isset($ticket['icon']) ? absint($ticket['icon']) : '';
        $ticket_data['priority'] = 1;
        $ticket_data['status'] = 1;
        $ticket_data['created_at'] = wp_date("Y-m-d H:i:s", time());
        // new
        $ticket_data['additional_fees'] = ( isset($ticket['ep_additional_ticket_fee_data']) && !empty($ticket['ep_additional_ticket_fee_data']) ) ? wp_json_encode($ticket['ep_additional_ticket_fee_data']) : '';
        $ticket_data['allow_cancellation'] = isset($ticket['allow_cancellation']) ? absint($ticket['allow_cancellation']) : 0;
        $ticket_data['show_remaining_tickets'] = isset($ticket['show_remaining_tickets']) ? absint($ticket['show_remaining_tickets']) : 0;
        // date
        $start_date = [];
        if (isset($ticket['em_ticket_start_booking_type']) && !empty($ticket['em_ticket_start_booking_type'])) {
            $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
            if ($ticket['em_ticket_start_booking_type'] == 'custom_date') {
                if (isset($ticket['em_ticket_start_booking_date']) && !empty($ticket['em_ticket_start_booking_date'])) {
                    $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                }
                if (isset($ticket['em_ticket_start_booking_time']) && !empty($ticket['em_ticket_start_booking_time'])) {
                    $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                }
            } elseif ($ticket['em_ticket_start_booking_type'] == 'event_date') {
                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
            } elseif ($ticket['em_ticket_start_booking_type'] == 'relative_date') {
                if (isset($ticket['em_ticket_start_booking_days']) && !empty($ticket['em_ticket_start_booking_days'])) {
                    $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                }
                if (isset($ticket['em_ticket_start_booking_days_option']) && !empty($ticket['em_ticket_start_booking_days_option'])) {
                    $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                }
                $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
            }
        }
        $ticket_data['booking_starts'] = wp_json_encode($start_date);
        // end date
        $end_date = [];
        if (isset($ticket['em_ticket_ends_booking_type']) && !empty($ticket['em_ticket_ends_booking_type'])) {
            $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
            if ($ticket['em_ticket_ends_booking_type'] == 'custom_date') {
                if (isset($ticket['em_ticket_ends_booking_date']) && !empty($ticket['em_ticket_ends_booking_date'])) {
                    $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                }
                if (isset($ticket['em_ticket_ends_booking_time']) && !empty($ticket['em_ticket_ends_booking_time'])) {
                    $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                }
            } elseif ($ticket['em_ticket_ends_booking_type'] == 'event_date') {
                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
            } elseif ($ticket['em_ticket_ends_booking_type'] == 'relative_date') {
                if (isset($ticket['em_ticket_ends_booking_days']) && !empty($ticket['em_ticket_ends_booking_days'])) {
                    $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                }
                if (isset($ticket['em_ticket_ends_booking_days_option']) && !empty($ticket['em_ticket_ends_booking_days_option'])) {
                    $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                }
                $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
            }
        }
        $ticket_data['booking_ends'] = wp_json_encode($end_date);
        // visibility
        $ticket_data['visibility'] = $ticket_visibility = array();
        if (isset($ticket['em_tickets_user_visibility']) && !empty($ticket['em_tickets_user_visibility'])) {
            $ticket_visibility['em_tickets_user_visibility'] = $ticket['em_tickets_user_visibility'];
        }
        if (isset($ticket['em_ticket_for_invalid_user']) && !empty($ticket['em_ticket_for_invalid_user'])) {
            $ticket_visibility['em_ticket_for_invalid_user'] = $ticket['em_ticket_for_invalid_user'];
        }
        if (isset($ticket['em_tickets_visibility_time_restrictions']) && !empty($ticket['em_tickets_visibility_time_restrictions'])) {
            $ticket_visibility['em_tickets_visibility_time_restrictions'] = $ticket['em_tickets_visibility_time_restrictions'];
        }
        if (isset($ticket['em_ticket_visibility_user_roles']) && !empty($ticket['em_ticket_visibility_user_roles'])) {
            $ticket_visibility['em_ticket_visibility_user_roles'] = $ticket['em_ticket_visibility_user_roles'];
        }
        if (!empty($ticket_visibility)) {
            $ticket_data['visibility'] = wp_json_encode($ticket_visibility);
        }
        $ticket_data['show_ticket_booking_dates'] = (isset($ticket['show_ticket_booking_dates']) ) ? 1 : 0;
        $ticket_data['min_ticket_no'] = isset($ticket['min_ticket_no']) ? absint($ticket['min_ticket_no']) : 0;
        $ticket_data['max_ticket_no'] = isset($ticket['max_ticket_no']) ? absint($ticket['max_ticket_no']) : 0;
        $ticket_data['offers'] = ( isset($ticket['offers']) && !empty($ticket['offers']) ) ? $ticket['offers'] : '';
        $ticket_data['multiple_offers_option'] = ( isset($ticket['multiple_offers_option']) && !empty($ticket['multiple_offers_option']) ) ? $ticket['multiple_offers_option'] : '';
        $ticket_data['multiple_offers_max_discount'] = ( isset($ticket['multiple_offers_max_discount']) && !empty($ticket['multiple_offers_max_discount']) ) ? $ticket['multiple_offers_max_discount'] : '';
        $ticket_data['ticket_template_id'] = ( isset($ticket['ticket_template_id']) && !empty($ticket['ticket_template_id']) ) ? $ticket['ticket_template_id'] : '';
        return $ticket_data;
    }

    public function ep_delete_child_events($post_id) {
        $ep_functions = new Eventprime_Basic_Functions;
        $booking_controller = new EventPrime_Bookings;
        $child_events = $ep_functions->ep_get_child_events($post_id);
        if (!empty($child_events)) {

            foreach ($child_events as $child_post) {
                // check category and tickets and delete them
                $cates = $ep_functions->get_existing_category_lists($child_post->ID);
                if (!empty($cates)) {
                    foreach ($cates as $category) {
                        if (!empty($category->id)) {
                            // first delete tickets of this category
                            $cat_tickets = $ep_functions->get_existing_category_ticket_lists($child_post->ID, $category->id);
                            if (!empty($cat_tickets)) {
                                foreach ($cat_tickets as $ticket) {
                                    $this->remove_row('TICKET', 'id', $ticket->id);
                                }
                            }
                            $this->remove_row('TICKET_CATEGORIES', 'id', $category->id);
                        }
                    }
                }
                // get individual tickets
                $individual_tickets = $ep_functions->get_existing_individual_ticket_lists($child_post->ID);
                if (!empty($individual_tickets)) {
                    foreach ($individual_tickets as $ticket) {
                        $this->remove_row('TICKET', 'id', $ticket->id);
                    }
                }

                // delete booking of this event
                /*
                $event_bookings = $booking_controller->get_event_bookings_by_event_id($child_post->ID);
                if (!empty($event_bookings)) {
                    foreach ($event_bookings as $booking) {
                        // delete booking
                        wp_delete_post($booking->ID, true);
                    }
                }
                */
                // delete terms relationships
                wp_delete_object_term_relationships($child_post->ID, array('em_venue', 'em_event_type', 'em_event_organizer'));
                // delete event
                wp_delete_post($child_post->ID, true);
                // delete child event ext data
                do_action('ep_delete_event_data', $child_post->ID);
            }
        }
    }

    public function ep_event_daily_recurrence($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
//        $start_date = new DateTime('@' . $data['start_date']);
//        $end_date = new DateTime('@' . $data['end_date']);
        
        $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

        $start_date = new DateTime( 'now', $timezone );
        $start_date->setTimestamp( $data['start_date'] );

        $end_date = new DateTime( 'now', $timezone );
        $end_date->setTimestamp( $data['end_date'] );

        $modify_string = $this->get_date_modification_string($data);
        $counter = 0;
        $old_post_metas = get_post_custom($post->ID);
        $new_posts = array();
        // Last date on condition
        if (!empty($data['last_date_on'])) {
            while ($data['start_date_only']->modify($modify_string)->getTimestamp() <= $data['recurrence_limit_timestamp']) {
                $start_date->modify($modify_string);
                $end_date->modify($modify_string);
                // get date timestamp
                $child_start_date = $start_date->getTimestamp();
                $child_end_date = $end_date->getTimestamp();
                // create child event
                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    // set parent id to recurring post
                    update_post_meta($new_post_id, 'em_is_daily_recurrence', 1);
                }
                $counter++;
            }
        } elseif (!empty($data['stop_after'])) { // stop after condition
            while ($counter < $data['stop_after']) {
                $start_date->modify($modify_string);
                $end_date->modify($modify_string);
                // get date timestamp
                $child_start_date = $start_date->getTimestamp();
                $child_end_date = $end_date->getTimestamp();
                // create child event
                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    // set parent id to recurring post
                    update_post_meta($new_post_id, 'em_is_daily_recurrence', 1);
                }
                $counter++;
            }
        }
    }

    /**
     * Method to create weekly recurring events
     * 
     * @param object $post Post. 
     * 
     * @param array $data Data.
     */
    public function ep_event_weekly_recurrence($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
        $week_days = ( isset($post_data['em_selected_weekly_day']) && !empty($post_data['em_selected_weekly_day']) ? $post_data['em_selected_weekly_day'] : [] );
        if (count($week_days) > 0) {
            update_post_meta($post->ID, 'em_selected_weekly_day', $week_days);
            $step = absint($data['recurrence_step']);
            $step_string = ($step > 1) ? ' weeks' : ' week';
            // start modify string
            $full_week_days = $ep_functions->ep_get_week_day_full();
            $day_name = $full_week_days[$week_days[0]];
            $wk = 1;
            $wstart = 0;
            if ($step == 1) {
                $modify_string = 'Next ' . $day_name;
            } elseif ($step == 2) {
                $modify_string = 'Second ' . $day_name;
            } elseif ($step == 3) {
                $modify_string = 'Third ' . $day_name;
            } elseif ($step == 4) {
                $modify_string = 'Fourth ' . $day_name;
            } elseif ($step == 5) {
                $modify_string = 'Fifth ' . $day_name;
            } elseif ($step == 6) {
                $modify_string = 'Sixth ' . $day_name;
            } elseif ($step == 7) {
                $modify_string = 'Seventh ' . $day_name;
            } else {
                $modify_string = $day_name . ' +' . $step . ' ' . $step_string;
            }

//            $start_date = new DateTime('@' . $data['start_date']);
//            $end_date = new DateTime('@' . $data['end_date']);
            
            $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

            $start_date = new DateTime( 'now', $timezone );
            $start_date->setTimestamp( $data['start_date'] );

            $end_date = new DateTime( 'now', $timezone );
            $end_date->setTimestamp( $data['end_date'] );

            $counter = 0;
            $old_post_metas = get_post_custom($post->ID);
            $default_modify_string = $modify_string;
            // Last date on condition
            if (!empty($data['last_date_on'])) {
                while ($data['start_date_only']->modify($modify_string)->getTimestamp() <= $data['recurrence_limit_timestamp']) {
                    if ($counter == 0) {
                        $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
                        $start_date->modify($new_modify_string);
                        $end_date->modify($new_modify_string);
                    } else {
                        $start_date->modify($modify_string);
                        $end_date->modify($modify_string);
                    }

                    // get date timestamp
                    $child_start_date = $start_date->getTimestamp();
                    $child_end_date = $end_date->getTimestamp();
                    // create child event
                    $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                    if (!empty($new_post_id) && is_int($new_post_id)) {
                        // set parent id to recurring post
                        update_post_meta($new_post_id, 'em_is_weekly_recurrence', 1);
                        $counter++;
                    }

                    if (count($week_days) > 1) {
                        foreach ($week_days as $key => $value) {
                            if ($wstart == 0) {
                                $wstart = 1;
                                continue;
                            }
                            $day_name = $full_week_days[$value];
                            $modify_string = 'next ' . $day_name;
                            $wk++;
                            if ($data['start_date_only']->modify($modify_string)->getTimestamp() <= $data['recurrence_limit_timestamp']) {
                                // get date of next recure
                                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
                                $start_date->modify($new_modify_string);
                                $end_date->modify($new_modify_string);
                                // get date timestamp
                                $child_start_date = $start_date->getTimestamp();
                                $child_end_date = $end_date->getTimestamp();
                                // create child event
                                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                                if (!empty($new_post_id) && is_int($new_post_id)) {
                                    // set parent id to recurring post
                                    update_post_meta($new_post_id, 'em_is_weekly_recurrence', 1);
                                    $counter++;
                                }
                            }
                            if ($wk == count($week_days)) {
                                // move to start condition
                                $wk = 1;
                                $wstart = 0;
                                $day_name = $full_week_days[$week_days[0]];
                                $newstep = $step - 1;
                                //echo $modify_string = $day_name. ' +'. $newstep. ' '. $step_string;echo "<br>";
                                $modify_string = $default_modify_string;
                                break;
                            }
                        }
                    }
                }
            } elseif (!empty($data['stop_after'])) { // stop after condition
                while ($counter < $data['stop_after']) {
                    if ($counter == 0) {
                        $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
                        $start_date->modify($new_modify_string);
                        $end_date->modify($new_modify_string);
                    } else {
                        $start_date->modify($modify_string);
                        $end_date->modify($modify_string);
                    }
                    // get date timestamp
                    $child_start_date = $start_date->getTimestamp();
                    $child_end_date = $end_date->getTimestamp();
                    // create child event
                    $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                    if (!empty($new_post_id) && is_int($new_post_id)) {
                        // set parent id to recurring post
                        update_post_meta($new_post_id, 'em_is_weekly_recurrence', 1);
                        $counter++;
                    }

                    if (count($week_days) > 1) {
                        foreach ($week_days as $key => $value) {
                            if ($wstart == 0) {
                                $wstart = 1;
                                continue;
                            }
                            $day_name = $full_week_days[$value];
                            $modify_string = 'next ' . $day_name;
                            $wk++;
                            if ($counter < $data['stop_after']) {
                                // get date of next recure
                                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
                                $start_date->modify($new_modify_string);
                                $end_date->modify($new_modify_string);
                                // get date timestamp
                                $child_start_date = $start_date->getTimestamp();
                                $child_end_date = $end_date->getTimestamp();
                                // create child event
                                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                                if (!empty($new_post_id) && is_int($new_post_id)) {
                                    // set parent id to recurring post
                                    update_post_meta($new_post_id, 'em_is_weekly_recurrence', 1);
                                    $counter++;
                                }
                            }
                            if ($wk == count($week_days)) {
                                // move to start condition
                                $wk = 1;
                                $wstart = 0;
                                $day_name = $full_week_days[$week_days[0]];
                                $newstep = $step - 1;
                                $modify_string = $default_modify_string;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    
    /**
     * Method to create monthly recurring events
     * 
     * @param object $post Post. 
     * 
     * @param array $data Data.
     */
    public function ep_event_monthly_recurrence($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
        $em_recurrence_monthly_day = isset($post_data['em_recurrence_monthly_day']) ? $post_data['em_recurrence_monthly_day'] : '';
        update_post_meta($post->ID, 'em_recurrence_monthly_day', $em_recurrence_monthly_day);
        $data['em_recurrence_monthly_day'] = $em_recurrence_monthly_day;
        if ($em_recurrence_monthly_day == 'day') {
            $em_recurrence_monthly_weekno = isset($post_data['em_recurrence_monthly_weekno']) ? $post_data['em_recurrence_monthly_weekno'] : '';
            $em_recurrence_monthly_fullweekday = isset($post_data['em_recurrence_monthly_fullweekday']) ? $post_data['em_recurrence_monthly_fullweekday'] : '';
            update_post_meta($post->ID, 'em_recurrence_monthly_weekno', $em_recurrence_monthly_weekno);
            update_post_meta($post->ID, 'em_recurrence_monthly_fullweekday', $em_recurrence_monthly_fullweekday);
            $data['em_recurrence_monthly_weekno'] = $em_recurrence_monthly_weekno;
            $data['em_recurrence_monthly_fullweekday'] = $em_recurrence_monthly_fullweekday;
        }

//        $start_date = new DateTime('@' . $data['start_date']);
//        $end_date = new DateTime('@' . $data['end_date']);
        
        $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone() ?? 'UTC' );

        $start_date = new DateTime( 'now', $timezone );
        $start_date->setTimestamp( $data['start_date'] );

        $end_date = new DateTime( 'now', $timezone );
        $end_date->setTimestamp( $data['end_date'] );

        $modify_string = $this->get_date_modification_string($data);
        $old_post_metas = get_post_custom($post->ID);
        // get date of next recure
        $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
        $counter = 0;
        // Last date on condition
        if (!empty($data['last_date_on'])) {
            while ($data['start_date_only']->modify($new_modify_string)->getTimestamp() <= $data['recurrence_limit_timestamp']) {
                $start_date->modify($new_modify_string);
                $end_date->modify($new_modify_string);
                // get date timestamp
                $child_start_date = $start_date->getTimestamp();
                $child_end_date = $end_date->getTimestamp();
                // create child event
                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    // set parent id to recurring post
                    update_post_meta($new_post_id, 'em_is_monthly_recurrence', 1);
                    $counter++;
                }
                // get date of next recure
                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
            }
        } elseif (!empty($data['stop_after'])) { // stop after condition
            while ($counter < $data['stop_after']) {
                $start_date->modify($new_modify_string);
                $end_date->modify($new_modify_string);
                // get date timestamp
                $child_start_date = $start_date->getTimestamp();
                $child_end_date = $end_date->getTimestamp();
                // create child event
                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    // set parent id to recurring post
                    update_post_meta($new_post_id, 'em_is_monthly_recurrence', 1);
                    $counter++;
                }
                // get date of next recure
                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
            }
        }
    }

    /**
     * Method to create yearly recurring events
     * 
     * @param object $post Post. 
     * 
     * @param array $data Data.
     */
    public function ep_event_yearly_recurrence_old($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
        $em_recurrence_yearly_day = isset($post_data['em_recurrence_yearly_day']) ? $post_data['em_recurrence_yearly_day'] : '';
        update_post_meta($post->ID, 'em_recurrence_yearly_day', $em_recurrence_yearly_day);
        $data['em_recurrence_yearly_day'] = $em_recurrence_yearly_day;
        $current_year = gmdate('Y');
        if ($em_recurrence_yearly_day == 'day') {
            $em_recurrence_yearly_weekno = isset($post_data['em_recurrence_yearly_weekno']) ? $post_data['em_recurrence_yearly_weekno'] : '';
            $em_recurrence_yearly_fullweekday = isset($post_data['em_recurrence_yearly_fullweekday']) ? $post_data['em_recurrence_yearly_fullweekday'] : '';
            $em_recurrence_yearly_monthday = isset($post_data['em_recurrence_yearly_monthday']) ? $post_data['em_recurrence_yearly_monthday'] : '';
            update_post_meta($post->ID, 'em_recurrence_yearly_weekno', $em_recurrence_yearly_weekno);
            update_post_meta($post->ID, 'em_recurrence_yearly_fullweekday', $em_recurrence_yearly_fullweekday);
            update_post_meta($post->ID, 'em_recurrence_yearly_monthday', $em_recurrence_yearly_monthday);
            $data['em_recurrence_yearly_weekno'] = $em_recurrence_yearly_weekno;
            $data['em_recurrence_yearly_fullweekday'] = $em_recurrence_yearly_fullweekday;
            $data['em_recurrence_yearly_monthday'] = $em_recurrence_yearly_monthday;
            $data['em_recurrence_yearly_year'] = $current_year;
        }
//        $start_date = new DateTime('@' . $data['start_date']);
//        $end_date = new DateTime('@' . $data['end_date']);
        
        $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

        $start_date = new DateTime( 'now', $timezone );
        $start_date->setTimestamp( $data['start_date'] );

        $end_date = new DateTime( 'now', $timezone );
        $end_date->setTimestamp( $data['end_date'] );
        
        $modify_string = $this->get_date_modification_string($data);
        //print_r($modify_string);die;
        $old_post_metas = get_post_custom($post->ID);
        // get date of next recure
        $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
        //print_r($new_modify_string);die;
        $counter = 0;
        // Last date on condition
        if (!empty($data['last_date_on'])) {
            while ($data['start_date_only']->modify($new_modify_string)->getTimestamp() <= $data['recurrence_limit_timestamp']) {
                $start_date->modify($new_modify_string);
                $end_date->modify($new_modify_string);
                // get date timestamp
                $child_start_date = $start_date->getTimestamp();
                $child_end_date = $end_date->getTimestamp();
                // create child event
                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    // set parent id to recurring post
                    update_post_meta($new_post_id, 'em_is_yearly_recurrence', 1);
                    $counter++;
                }
                // get date of next recure
                $data['em_recurrence_yearly_year'] = $data['em_recurrence_yearly_year'] + $data['recurrence_step'];
                $modify_string = $this->get_date_modification_string($data);
                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
            }
        } elseif (!empty($data['stop_after'])) { // stop after condition
            while ($counter < $data['stop_after']) {
                $start_date->modify($new_modify_string);
                $end_date->modify($new_modify_string);
                // get date timestamp
                $child_start_date = $start_date->getTimestamp();
                $child_end_date = $end_date->getTimestamp();
                // create child event
                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    // set parent id to recurring post
                    update_post_meta($new_post_id, 'em_is_yearly_recurrence', 1);
                    $counter++;
                }
                // get date of next recure
                $data['em_recurrence_yearly_year'] = $data['em_recurrence_yearly_year'] + $data['recurrence_step'];
                $modify_string = $this->get_date_modification_string($data);
                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
            }
        }
    }

    public function ep_event_yearly_recurrence($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;

        $em_recurrence_yearly_day = $post_data['em_recurrence_yearly_day'] ?? '';
        update_post_meta($post->ID, 'em_recurrence_yearly_day', $em_recurrence_yearly_day);
        $data['em_recurrence_yearly_day'] = $em_recurrence_yearly_day;

        if ($em_recurrence_yearly_day === 'day') {
            $em_recurrence_yearly_weekno       = $post_data['em_recurrence_yearly_weekno'] ?? '';
            $em_recurrence_yearly_fullweekday  = $post_data['em_recurrence_yearly_fullweekday'] ?? '';
            $em_recurrence_yearly_monthday     = $post_data['em_recurrence_yearly_monthday'] ?? '';

            update_post_meta($post->ID, 'em_recurrence_yearly_weekno', $em_recurrence_yearly_weekno);
            update_post_meta($post->ID, 'em_recurrence_yearly_fullweekday', $em_recurrence_yearly_fullweekday);
            update_post_meta($post->ID, 'em_recurrence_yearly_monthday', $em_recurrence_yearly_monthday);

            $data['em_recurrence_yearly_weekno']      = $em_recurrence_yearly_weekno;
            $data['em_recurrence_yearly_fullweekday'] = $em_recurrence_yearly_fullweekday;
            $data['em_recurrence_yearly_monthday']    = $em_recurrence_yearly_monthday;
            // we'll set em_recurrence_yearly_year globally below
        }

        $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());

        $start_date = new DateTime('now', $timezone);
        $start_date->setTimestamp((int)$data['start_date']);

        $end_date = new DateTime('now', $timezone);
        $end_date->setTimestamp((int)$data['end_date']);

        // Ensure start_date_only exists (it’s used in the first while condition)
        $data['start_date_only'] = $data['start_date_only'] ?? clone $start_date;

        // Always initialize the yearly baseline from the event start_date in site TZ
        $data['em_recurrence_yearly_year'] = isset($data['em_recurrence_yearly_year'])
            ? (int)$data['em_recurrence_yearly_year']
            : (int)$start_date->format('Y');

        // Guard step to avoid notices
        $data['recurrence_step'] = isset($data['recurrence_step']) ? (int)$data['recurrence_step'] : 1;

        $modify_string = $this->get_date_modification_string($data);
        $old_post_metas = get_post_custom($post->ID);

        // First next occurrence modifier
        $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);

        $counter = 0;

        if (!empty($data['last_date_on'])) {
            while ($data['start_date_only']->modify($new_modify_string)->getTimestamp() <= (int)$data['recurrence_limit_timestamp']) {
                $start_date->modify($new_modify_string);
                $end_date->modify($new_modify_string);

                $child_start_date = $start_date->getTimestamp();
                $child_end_date   = $end_date->getTimestamp();

                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    update_post_meta($new_post_id, 'em_is_yearly_recurrence', 1);
                    $counter++;
                }

                // advance year and recompute modifier
                $data['em_recurrence_yearly_year'] += $data['recurrence_step'];
                $modify_string   = $this->get_date_modification_string($data);
                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
            }
        } elseif (!empty($data['stop_after'])) {
            while ($counter < (int)$data['stop_after']) {
                $start_date->modify($new_modify_string);
                $end_date->modify($new_modify_string);

                $child_start_date = $start_date->getTimestamp();
                $child_end_date   = $end_date->getTimestamp();

                $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                if (!empty($new_post_id) && is_int($new_post_id)) {
                    update_post_meta($new_post_id, 'em_is_yearly_recurrence', 1);
                    $counter++;
                }

                $data['em_recurrence_yearly_year'] += $data['recurrence_step'];
                $modify_string   = $this->get_date_modification_string($data);
                $new_modify_string = $this->get_new_modify_string($start_date, $modify_string);
            }
        }
    }

    
    /**
     * Method to create advanced recurring events
     * 
     * @param object $post Post. 
     * 
     * @param array $data Data.
     */
    public function ep_event_advanced_recurrence($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
        if (isset($post_data['em_recurrence_advanced_dates']) && is_array($post_data['em_recurrence_advanced_dates'])) {
            $em_recurrence_advanced_dates = $post_data['em_recurrence_advanced_dates'];
        } else {
            $em_recurrence_advanced_dates = isset($post_data['em_recurrence_advanced_dates']) ? json_decode(stripslashes($post_data['em_recurrence_advanced_dates'])) : '';
        }
        update_post_meta($post->ID, 'em_recurrence_advanced_dates', $em_recurrence_advanced_dates);
        $data['em_recurrence_advanced_dates'] = $em_recurrence_advanced_dates;
        $weeknos_data = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
//        $start_date = new DateTime('@' . $data['start_date']);
//        $end_date = new DateTime('@' . $data['end_date']);
        
        $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone() );

        $start_date = new DateTime( 'now', $timezone );
        $start_date->setTimestamp( $data['start_date'] );

        $end_date = new DateTime( 'now', $timezone );
        $end_date->setTimestamp( $data['end_date'] );
        
        $modify_string = '';
        if (!empty($em_recurrence_advanced_dates)) {
            $m = gmdate('m');
            $y = gmdate('Y');
            $i = 0;
            $step = absint($data['recurrence_step']);
            $stop_recurr = 0;
            $counter = 1;
            // Last date on condition
            if (!empty($data['last_date_on'])) {
                $recurr_limit_month = gmdate('m', $data['recurrence_limit_timestamp']);
                $recurr_limit_year = gmdate('Y', $data['recurrence_limit_timestamp']);
                while ($stop_recurr == 0) {
                    if ($i > 0) {
                        $m += $step;
                        if ($m > 12) {
                            $y++;
                            $monDiff = $m - 12;
                            $m = $monDiff;
                        }
                        if ($y == $recurr_limit_year) {
                            if ($m > $recurr_limit_month) {
                                $stop_recurr = 1;
                                break;
                            }
                        }
                    }
                    foreach ($em_recurrence_advanced_dates as $adv) {
                        $advs = explode("-", $adv);
                        //checking whether a day name occurs in the given week no of the month or not
                        $dates = $this->nthDayInMonth($advs[1], array_search($advs[0], $weeknos_data), $m, $y);
                        if (!empty($dates)) {
                            if (strtotime($dates) < $data['start_date'])
                                continue;
                            $newdates = date_create($dates);
                            $child_start_date1 = date_create(gmdate("Y-m-d", $data['start_date']));
                            $start_date_diff = date_diff($child_start_date1, $newdates);
                            $modify_string = $start_date_diff->days > 1 ? '+' . $start_date_diff->days . ' days' : '+' . $start_date_diff->days . ' day';
                            $start_date->modify($modify_string);
                            if ($start_date->getTimestamp() > $data['recurrence_limit_timestamp']) {
                                $stop_recurr = 1;
                                break;
                            }
                            $end_date->modify($modify_string);
                            // get date timestamp
                            $child_start_date = $start_date->getTimestamp();
                            $child_end_date = $end_date->getTimestamp();
                            // create child event
                            $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                            if (!empty($new_post_id) && is_int($new_post_id)) {
                                // set parent id to recurring post
                                update_post_meta($new_post_id, 'em_is_advanced_recurrence', 1);
                                $counter++;
                            }
                            // reset the variable so we can start from the actual start & end dates
//                            $start_date = new DateTime('@' . $data['start_date']);
//                            $end_date = new DateTime('@' . $data['end_date']);
                            
                            $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone() );

                            $start_date = new DateTime( 'now', $timezone );
                            $start_date->setTimestamp( $data['start_date'] );

                            $end_date = new DateTime( 'now', $timezone );
                            $end_date->setTimestamp( $data['end_date'] );
                            
                        }
                    }
                    $i++;
                }
            } elseif (!empty($data['stop_after'])) { // stop after condition
                while ($counter < $data['stop_after']) {
                    if ($i > 0) {
                        $m += $step;
                        if ($m > 12) {
                            $y++;
                            $monDiff = $m - 12;
                            $m = $monDiff;
                        }
                    }
                    foreach ($em_recurrence_advanced_dates as $adv) {
                        $advs = explode("-", $adv);
                        //checking whether a day name occurs in the given week no of the month or not
                        $dates = $this->nthDayInMonth($advs[1], array_search($advs[0], $weeknos_data), $m, $y);
                        if (!empty($dates)) {
                            if (strtotime($dates) < $data['start_date'])
                                continue;
                            $newdates = date_create($dates);
                            $child_start_date1 = date_create(gmdate("Y-m-d", $data['start_date']));
                            $start_date_diff = date_diff($child_start_date1, $newdates);
                            $modify_string = $start_date_diff->days > 1 ? '+' . $start_date_diff->days . ' days' : '+' . $start_date_diff->days . ' day';
                            $start_date->modify($modify_string);
                            $end_date->modify($modify_string);
                            // get date timestamp
                            $child_start_date = $start_date->getTimestamp();
                            $child_end_date = $end_date->getTimestamp();
                            // create child event
                            $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                            if (!empty($new_post_id) && is_int($new_post_id)) {
                                // set parent id to recurring post
                                update_post_meta($new_post_id, 'em_is_advanced_recurrence', 1);
                                $counter++;
                            }
                            if ($counter >= $data['stop_after']) {
                                $stop_recurr = 1;
                                break;
                            }
                            // reset the variable so we can start from the actual start & end dates
//                            $start_date = new DateTime('@' . $data['start_date']);
//                            $end_date = new DateTime('@' . $data['end_date']);
                            
                            $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone() );

                            $start_date = new DateTime( 'now', $timezone );
                            $start_date->setTimestamp( $data['start_date'] );

                            $end_date = new DateTime( 'now', $timezone );
                            $end_date->setTimestamp( $data['end_date'] );

                        }
                    }
                    $i++;
                }
            }
        }
    }

    /**
     * Method to create custom dates recurring events
     * 
     * @param object $post Post. 
     * 
     * @param array $data Data.
     */
    public function ep_event_custom_dates_recurrence($post, $data = array(), $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
        if (isset($post_data['em_recurrence_selected_custom_dates']) && is_array($post_data['em_recurrence_selected_custom_dates'])) {
            $em_recurrence_selected_custom_dates = $post_data['em_recurrence_selected_custom_dates'];
        } else {
            $em_recurrence_selected_custom_dates = isset($post_data['em_recurrence_selected_custom_dates']) ? json_decode(stripslashes($post_data['em_recurrence_selected_custom_dates'])) : '';
        }
        update_post_meta($post->ID, 'em_recurrence_selected_custom_dates', $em_recurrence_selected_custom_dates);
        $data['em_recurrence_selected_custom_dates'] = $em_recurrence_selected_custom_dates;
        if (!empty($em_recurrence_selected_custom_dates)) {
            
//            $start_date = new DateTime('@' . $data['start_date']);
//            $end_date = new DateTime('@' . $data['end_date']);
            
            $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone() );

            $start_date = new DateTime( 'now', $timezone );
            $start_date->setTimestamp( $data['start_date'] );

            $end_date = new DateTime( 'now', $timezone );
            $end_date->setTimestamp( $data['end_date'] );
            
            $modify_string = '';
            $counter = 1;
            foreach ($em_recurrence_selected_custom_dates as $cdates) {
                if (!empty($cdates)) {
                    if (strtotime($cdates) < $data['start_date'])
                        continue;
                    $newdates = date_create($cdates);
                    $child_start_date1 = date_create(gmdate("Y-m-d", $data['start_date']));
                    $start_date_diff = date_diff($child_start_date1, $newdates);
                    $modify_string = $start_date_diff->days > 1 ? '+' . $start_date_diff->days . ' days' : '+' . $start_date_diff->days . ' day';
                    $start_date->modify($modify_string);
                    $end_date->modify($modify_string);
                    // get date timestamp
                    $child_start_date = $start_date->getTimestamp();
                    $child_end_date = $end_date->getTimestamp();
                    // create child event
                    $new_post_id = $this->ep_create_child_event($post, $child_start_date, $child_end_date, $counter, $post_data);
                    if (!empty($new_post_id) && is_int($new_post_id)) {
                        // set parent id to recurring post
                        update_post_meta($new_post_id, 'em_is_custom_dates_recurrence', 1);
                        $counter++;
                    }
                    // reset the variable so we can start from the actual start & end dates
//                    $start_date = new DateTime('@' . $data['start_date']);
//                    $end_date = new DateTime('@' . $data['end_date']);
                    
                    $timezone = new DateTimeZone( $ep_functions->ep_get_site_timezone() );

                    $start_date = new DateTime( 'now', $timezone );
                    $start_date->setTimestamp( $data['start_date'] );

                    $end_date = new DateTime( 'now', $timezone );
                    $end_date->setTimestamp( $data['end_date'] );
                    
                }
            }
        }
    }

    public function get_date_modification_string($data) {
        $ep_functions = new Eventprime_Basic_Functions;
        $step = absint($data['recurrence_step']);
        $interval = $data['recurrence_interval'];
        $modify_string = '+' . $step;
        switch ($interval) {
            case 'daily':
                $modify_string .= ($step > 1) ? ' days' : ' day';
                break;
            case 'weekly':
                $modify_string .= ($step > 1) ? ' weeks' : ' week';
                break;
            case 'monthly':
                $step_string = ($step > 1) ? ' months' : ' month';
                if ($data['em_recurrence_monthly_day'] == 'date') {
                    $modify_string = 'this day +' . $step . $step_string;
                } else {
                    $week_num = $ep_functions->ep_get_week_number();
                    $week_full_day = $ep_functions->ep_get_week_day_full();
                    $modify_string = $week_num[$data['em_recurrence_monthly_weekno']] . ' ' . $week_full_day[$data['em_recurrence_monthly_fullweekday']] . ' of +' . $step . ' ' . $step_string;
                }
                break;
            case 'yearly':
                $step_string = ($step > 1) ? ' years' : ' year';
                if ($data['em_recurrence_yearly_day'] == 'date') {
                    $modify_string = 'this day +' . $step . $step_string;
                } else {
                    $week_num = $ep_functions->ep_get_week_number();
                    $week_full_day = $ep_functions->ep_get_week_day_full();
                    $month_name = $ep_functions->ep_get_month_name();
                    $modify_string = $week_num[$data['em_recurrence_yearly_weekno']] . ' ' . $week_full_day[$data['em_recurrence_yearly_fullweekday']] . ' of ' . $month_name[$data['em_recurrence_yearly_monthday']] . ' ' . ($data['em_recurrence_yearly_year'] + $step);

                }
                break;
        }
        return $modify_string;
    }

    public function get_new_modify_string($start_date, $modify_string) {
        $ep_functions = new Eventprime_Basic_Functions;
        $child_start_date1 = $ep_functions->ep_timestamp_to_date($start_date->getTimestamp(), 'Y-m-d', 1);
        $tmp_date = new DateTime($child_start_date1);
        $tmp1_date = $tmp_date;
        $tmp2_date = $tmp1_date;
        $tmp1_date->modify($modify_string);
        $start_date_diff = date_diff(new DateTime($child_start_date1), $tmp1_date);
        $new_modify_string = $start_date_diff->days > 1 ? '+' . $start_date_diff->days . ' days' : '+' . $start_date_diff->days . ' day';
        return $new_modify_string;
    }

    /**
     * Create child events
     * 
     * @param object $post Parent Event Data.
     * 
     * @param int $start_date Start Date Timestamp.
     * 
     * @param int $end_date End Date Timestamp.
     */
    public function ep_create_child_event($post, $start_date, $end_date, $counter = 0, $post_data = array()) {
        $ep_activator = new Eventprime_Event_Calendar_Management_Activator;
        $ep_functions = new Eventprime_Basic_Functions;
        if (!empty($post) && !empty($start_date) && !empty($end_date)) {
            global $wpdb;
            $child_name = ( isset($post_data['em_add_slug_in_event_title']) && absint($post_data['em_add_slug_in_event_title']) == 1 ) ? $this->ep_format_event_title($post->ID, $post->post_title, $counter, $start_date, $post_data) : $post->post_title;
            // add new child post
            $new_post = array(
                'post_title' => $child_name,
                'post_status' => $post->post_status,
                'post_content' => $post->post_content,
                'post_type' => $post->post_type,
                'post_author' => get_current_user_id(),
                'post_parent' => $post->ID,
                'comment_status'=> 'closed', // Disable comments
                'ping_status'   => 'closed'  // Disable pingbacks
            );
            
            $new_post_id = wp_insert_post($new_post); // new post id
            $old_post_metas = get_post_custom($post->ID);
            //print_r($old_post_metas);die;
            // add all metas
            if (!empty($old_post_metas)) {
                foreach ($old_post_metas as $meta_key => $meta_value) {
                    if ($meta_key == 'em_start_date') {
                        update_post_meta($new_post_id, $meta_key, $start_date);
                    } elseif ($meta_key == 'em_end_date') {
                        update_post_meta($new_post_id, $meta_key, $end_date);
                    } elseif ($meta_key == 'em_id') {
                        update_post_meta($new_post_id, $meta_key, $new_post_id);
                    } elseif ($meta_key == 'em_name') {
                        update_post_meta($new_post_id, $meta_key, $child_name);
                    } elseif ($meta_key == 'em_venue' || $meta_key == 'em_event_type') {
                        wp_set_post_terms($new_post_id, isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : maybe_unserialize(array()), $meta_key, false);
                        update_post_meta($new_post_id, $meta_key, isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : maybe_unserialize(array()) );
                        
                    } elseif ($meta_key == 'em_performer' || $meta_key == 'em_sponsor') {
                        wp_set_post_terms($new_post_id, isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : maybe_unserialize(array()), $meta_key, false);
                        update_post_meta($new_post_id, $meta_key, isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : maybe_unserialize(array()) );
                    } elseif ($meta_key == 'em_organizer') {
                        $orgs = isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : array();
                        wp_set_post_terms($new_post_id, $orgs, 'em_event_organizer', false);
                        update_post_meta($new_post_id, $meta_key, $orgs);
                    } else {
                        update_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value[0]));
                    }
                }
                // update start and end datetime meta
                $ep_date_time_format = 'Y-m-d';
                $start_date = get_post_meta($new_post_id, 'em_start_date', true);
                $start_time = get_post_meta($new_post_id, 'em_start_time', true);
                $merge_start_date_time = $ep_functions->ep_datetime_to_timestamp($ep_functions->ep_timestamp_to_date($start_date, 'Y-m-d', 1) . ' ' . $start_time, $ep_date_time_format, '', 0, 1);
                if (!empty($merge_start_date_time)) {
                    update_post_meta($new_post_id, 'em_start_date_time', $merge_start_date_time);
                }
                $end_date = get_post_meta($new_post_id, 'em_end_date', true);
                $end_time = get_post_meta($new_post_id, 'em_end_time', true);
                $merge_end_date_time = $ep_functions->ep_datetime_to_timestamp($ep_functions->ep_timestamp_to_date($end_date, 'Y-m-d', 1) . ' ' . $end_time, $ep_date_time_format, '', 0, 1);
                if (!empty($merge_end_date_time)) {
                    update_post_meta($new_post_id, 'em_end_date_time', $merge_end_date_time);
                }
            }
            // check for categories
            $cat_table_name = $wpdb->prefix . 'eventprime_ticket_categories';
            $price_options_table = $wpdb->prefix . 'em_price_options';
            $categories = $this->get_all_result('TICKET_CATEGORIES', '*', array('event_id' => $post->ID));

            if (!empty($categories)) {
                foreach ($categories as $cat) {
                    $cat_data = array();
                    $arg = array();
                    $cat = (array) $cat;
                    $cat_data['event_id'] = $new_post_id;
                    $cat_data['parent_id'] = $cat['id'];
                    $cat_data['name'] = $cat['name'];
                    $cat_data['capacity'] = $cat['capacity'];
                    $cat_data['priority'] = $cat['priority'];
                    $cat_data['status'] = $cat['status'];
                    $cat_data['created_by'] = $cat['created_by'];
                    $cat_data['last_updated_by'] = $cat['last_updated_by'];
                    $cat_data['created_at'] = wp_date("Y-m-d H:i:s", time());
                    $cat_data['updated_at'] = wp_date("Y-m-d H:i:s", time());
                    foreach ($cat_data as $key => $value) {
                        $arg[] = $ep_activator->get_db_table_field_type('TICKET_CATEGORIES', $key);
                    }
                    $cat_id = $this->insert_row('TICKET_CATEGORIES', $cat_data, $arg);
                    $cat_tickets = $ep_functions->get_existing_category_ticket_lists($post->ID, $cat['id'], false);
                    if (!empty($cat_tickets)) {
                        foreach ($cat_tickets as $ticket) {
                            $ticket = (array) $ticket;
                            $parent_price_option_id = $ticket['id'];
                            unset($ticket['id']);
                            $ticket['event_id'] = $new_post_id;
                            $ticket['parent_price_option_id'] = $parent_price_option_id;
                            $ticket['category_id'] = $cat_id;
                            $ticket['created_at'] = wp_date("Y-m-d H:i:s", time());
                            $ticket['updated_at'] = wp_date("Y-m-d H:i:s", time());

                            $result = $this->insert_row('TICKET', $ticket);
                        }
                    }
                }
            }
            // check for individual ticket
            $individual_tickets = $ep_functions->get_existing_individual_ticket_lists($post->ID, false);
            if (!empty($individual_tickets)) {
                foreach ($individual_tickets as $ticket) {
                    $ticket = (array) $ticket;
                    $parent_price_option_id = $ticket['id'];
                    unset($ticket['id']);
                    $ticket['event_id'] = $new_post_id;
                    $ticket['parent_price_option_id'] = $parent_price_option_id;
                    $ticket['created_at'] = wp_date("Y-m-d H:i:s", time());
                    $ticket['updated_at'] = wp_date("Y-m-d H:i:s", time());
                    $result = $this->insert_row('TICKET', $ticket);
                }
            }
            do_action('ep_after_save_event_child_data', $new_post_id, $post_data);
            return $new_post_id;
        }
        return;
    }

    /**
     * Format recurring event title
     * 
     * @param int $id Post Id.
     * 
     * @param string $post_title Post Title.
     * 
     * @param int $counter Counter
     * 
     * @param string $date Event Start Date.
     * 
     * @return string $post_title Post Title
     */
    public function ep_format_event_title($id, $post_title, $counter, $date, $post_data = array()) {
        $ep_functions = new Eventprime_Basic_Functions;
        update_post_meta($id, 'em_add_slug_in_event_title', 1);
        $em_event_slug_type_options = (isset($post_data['em_event_slug_type_options']) && !empty($post_data['em_event_slug_type_options']) ) ? $post_data['em_event_slug_type_options'] : '';
        update_post_meta($id, 'em_event_slug_type_options', $em_event_slug_type_options);
        if (!empty($em_event_slug_type_options)) {
            $em_recurring_events_slug_format = ( isset($post_data['em_recurring_events_slug_format']) && !empty($post_data['em_recurring_events_slug_format']) ) ? $post_data['em_recurring_events_slug_format'] : '';
            update_post_meta($id, 'em_recurring_events_slug_format', $em_recurring_events_slug_format);
            if (!empty($em_recurring_events_slug_format)) {
                if ($em_recurring_events_slug_format == 'date') {
                    $date = $ep_functions->ep_timestamp_to_date($date);
                    if ($em_event_slug_type_options == 'prefix') {
                        $post_title = $date . '-' . $post_title;
                    } else {
                        $post_title = $post_title . '-' . $date;
                    }
                } else {
                    $occurance_number = $counter + 1;
                    if ($em_event_slug_type_options == 'prefix') {
                        $post_title = $occurance_number . '-' . $post_title;
                    } else {
                        $post_title = $post_title . '-' . $occurance_number;
                    }
                }
            }
        }
        return $post_title;
    }

    public function nthDayInMonth($n, $day, $m = '', $y = '') {
        // day is in range 0 Sunday to 6 Saturday
        $y = (!empty($y) ? $y : gmdate('Y') );
        $m = (!empty($m) ? $m : gmdate('m') );
        $d = $this->firstDayInMonth($day, $m, $y);
        $weeks = $this->getWeeksInMonth($y, $m, 7); //1 (for monday) to 7 (for sunday)
        $week_status = array();
        foreach ($weeks as $weekNumber => $week) {
            $week_status[$weekNumber] = $week[0] . '/' . $week[1];
        }
        $week_start_end = explode("/", $week_status[$n]);
        $start_date = $week_start_end[0];
        $end_date = $week_start_end[1];
        $week_w_count = array();
        $week_date_range = array();
        $w_loop_start = 1;
        while (strtotime($start_date) <= strtotime($end_date)) {
            $timestamp = strtotime($start_date);
            $day_w_count = gmdate('w', $timestamp);
            $week_w_count[$w_loop_start] = $day_w_count;
            $week_date_range[$w_loop_start] = $start_date;
            $start_date = gmdate("Y-m-d", strtotime("+1 days", strtotime($start_date)));
            $w_loop_start++;
        }
        if (in_array($day, $week_w_count)) {
            $key_value = array_search($day, $week_w_count);
            $newDate = $week_date_range[$key_value];
            unset($week_status);
            unset($week_start_end);
            unset($week_w_count);
            unset($week_date_range);
            return $newDate;
        }
        unset($week_status);
        unset($week_start_end);
        unset($week_w_count);
        unset($week_date_range);
        return '';
    }

    public function firstDayInMonth($day, $m = '', $y = '') {
        // day is in range 0 Sunday to 6 Saturday
        $y = (!empty($y) ? $y : gmdate('Y') );
        $m = (!empty($m) ? $m : gmdate('m') );
        $fdate = gmdate($y . '-' . $m . '-01');
        $fd = gmdate('w', strtotime($fdate));
        $od = 1 + ( $day - $fd + 7 ) % 7;
        $newDate = gmdate($y . '-' . $m . '-' . $od);
        return $newDate;
    }

    public function getWeeksInMonth($year, $month, $lastDayOfWeek) {
        $aWeeksOfMonth = [];
        $date = new DateTime("{$year}-{$month}-01");
        $iDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $aOneWeek = [$date->format('Y-m-d')];
        $weekNumber = 1;
        for ($i = 1; $i <= $iDaysInMonth; $i++) {
            if ($lastDayOfWeek == $date->format('N') || $i == $iDaysInMonth) {
                $aOneWeek[] = $date->format('Y-m-d');
                $aWeeksOfMonth[$weekNumber++] = $aOneWeek;
                $date->add(new DateInterval('P1D'));
                $aOneWeek = [$date->format('Y-m-d')];
                $i++;
            }
            $date->add(new DateInterval('P1D'));
        }
        return $aWeeksOfMonth;
    }
    
    public function get_event_organizer_field_data( $fields = array() ) {
        $response = array();
        $terms = $this->get_organizers_data();
        if( !empty( $terms->terms ) && count( $terms->terms ) > 0 ) {
            foreach( $terms->terms as $term ) {
                $term_data = array();
                if( !empty( $fields ) ) {
                    if( in_array( 'id', $fields, true ) ) {
                        $term_data['id'] = $term->id;
                    }
                    if( in_array( 'name', $fields, true ) ) {
                        $term_data['name'] = $term->name;
                    }
                }
                if( ! empty( $term_data ) ) {
                    $response[] = $term_data;
                }
            }
        }
        return $response;
    }
    
    public function get_organizers_data( $args = array() ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $defaults = array( 
            'taxonomy'   => 'em_event_organizer', // Specify the taxonomy
            'hide_empty' => false,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => 'em_status',
                    'value'   => 0,
                    'compare' => '!='
                ),
                array(
                    'key'     => 'em_status',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $args = wp_parse_args( $args, $defaults );
        $terms = get_terms( $args );
        $organizers = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $organizers;
        }
        foreach( $terms as $term ){
            $organizer = $ep_functions->get_single_organizer( $term->term_id, $term );
            if( ! empty( $organizer ) ) {
                $organizers[] = $organizer;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $organizers;
        return $wp_query;
    }
    
     public function get_event_type_field_data( $fields = array(), $with_id = 0 ) {
        $response = array();
        $terms = $this->get_event_types_data();
        if( !empty( $terms->terms ) && count( $terms->terms ) > 0 ) {
            foreach( $terms->terms as $term ) {
                $term_data = array();
                if( ! empty( $fields ) ) {
                    if( in_array( 'id', $fields, true ) ) {
                        $term_data['id'] = $term->id;
                    }
                    if( in_array( 'name', $fields, true ) ) {
                        $term_data['name'] = $term->name;
                    }
                    if( in_array( 'em_color', $fields, true ) ) {
                        $term_data['em_color'] = !empty( $term->em_color ) ? $term->em_color : '';
                    }
                    if( in_array( 'em_type_text_color', $fields, true ) ) {
                        $term_data['em_type_text_color'] = !empty( $term->em_type_text_color ) ? $term->em_type_text_color : '';
                    }
                }
                if( ! empty( $term_data ) ) {
                    if( ! empty( $with_id ) ) {
                        $response[$term_data['id']] = $term_data;
                    } else{
                        $response[] = $term_data;
                    }
                }
            }
        }
        return $response;
    }
    
    //depricated.
    public function get_event_types_data( $args = array() ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $defaults = array( 
            'taxonomy'   => 'em_event_type', // Specify the taxonomy
            'hide_empty' => false,
        );

        $args  = wp_parse_args( $args, $defaults );
        $terms = get_terms( $args );
        $event_types = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $event_types;
        }
        foreach( $terms as $term ){
            $event_type = $ep_functions->get_single_event_type( $term->term_id, $term );
            if( ! empty( $event_type ) ) {
                $event_types[] = $event_type;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $event_types;
        return $wp_query;
    }
    
    public function get_event_venues_field_data( $fields = array(), $with_id = 0 ) {
        $response = array();
        $terms = $this->get_venues_data();
        if( !empty( $terms->terms ) && count( $terms->terms ) > 0 ) {
            foreach( $terms->terms as $term ) {
                $term_data = array();
                if( !empty( $fields ) ) {
                    if( in_array( 'id', $fields, true ) ) {
                        $term_data['id'] = $term->id;
                    }
                    if( in_array( 'name', $fields, true ) ) {
                        $term_data['name'] = $term->name;
                    }
                    if( in_array( 'address', $fields, true ) ) {
                        $term_data['address'] = !empty( $term->em_address ) ? $term->em_address : '';
                    }
                    if( in_array( 'image', $fields, true ) ) {
                        $image_url = plugin_dir_url(EP_PLUGIN_FILE) . 'admin/partials/images/dummy_image.png';
                        if( ! empty( $term->em_gallery_images ) ) {
                            if( count( $term->em_gallery_images ) > 0 ) {
                                $img_url = wp_get_attachment_image_src( $term->em_gallery_images[0], 'large' );
                                if( ! empty( $img_url ) && isset( $img_url[0] ) ) {
                                    $image_url = $img_url[0];
                                }
                            }
                        }
                        $term_data['image'] = $image_url;
                    }
                }
                if( ! empty( $term_data ) ) {
                    if( ! empty( $with_id ) ) {
                        $response[$term_data['id']] = $term_data;
                    } else{
                        $response[] = $term_data;
                    }
                }
            }
        }
        return $response;
    }
    
    public function get_venues_data( $args = array() ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $defaults = array( 
            'taxonomy'   => 'em_venue', // Specify the taxonomy
            'hide_empty' => false,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => 'em_status',
                    'value'   => 0,
                    'compare' => '!='
                ),
                array(
                    'key'     => 'em_status',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        $args  = wp_parse_args( $args, $defaults );
        $terms = get_terms( $args );
        $venues = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $venues;
        }
        foreach( $terms as $term ){
            
            $venue = $ep_functions->get_single_venue( $term->term_id, $term );
            if( ! empty( $venue ) ) {
                $venues[] = $venue;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $venues;
        return $wp_query;
    }
    
    public function ep_get_taxonomy_terms_with_pagination($taxonomy, $paged = 1, $terms_per_page = 10, $args= array() ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $offset = ( (int)$paged - 1 ) * (int)$terms_per_page;

        $defaults = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => $terms_per_page,
            'offset'     => $offset,
        );
 

        $args        = wp_parse_args( $args, $defaults );
        //print_r($args);die;
        $terms = get_terms( $args );
        $event_types = array();
        if( !empty( $terms )){
          
        foreach( $terms as $term ){
            if($taxonomy=='em_event_type')
            {
                $event_type = $ep_functions->get_single_event_type( $term->term_id, $term );
            }
            if($taxonomy=='em_event_organizer')
            {
                $event_type = $ep_functions->get_single_organizer( $term->term_id, $term );
            }
            if($taxonomy=='em_venue')
            {
                $event_type = $ep_functions->get_single_venue( $term->term_id, $term );
            }
            if( ! empty( $event_type ) ) {
                $event_types[] = $event_type;
            }
        }
        }

        $args_total = $args;
        unset($args_total['number']);
        unset($args_total['offset']);
        $total_terms = wp_count_terms( $args_total );
        $total_pages = ceil( (int)$total_terms / (int)$terms_per_page );

        return array(
            'terms'       => $event_types,
            'total_pages' => $total_pages,
            'current_page'=> $paged,
            'total_terms' => $total_terms
        );
    }
    
    public function ep_update_child_events( $post_id ) {
		global $wpdb;
		// get child events
                $ep_functions = new Eventprime_Basic_Functions;
		$child_events = $ep_functions->ep_get_child_events( $post_id );
		if( ! empty( $child_events ) ) {
			$parent_post_data = get_post( $post_id );
			if( ! empty( $parent_post_data ) ) {
				$parent_post_metas = get_post_custom( $post_id );
				$counter = 0;
				$em_add_slug_in_event_title = get_post_meta( $post_id, 'em_add_slug_in_event_title', true );
				$parent_post_title = $parent_post_data->post_title;
				$em_recurring_events_slug_format = get_post_meta( $post_id, 'em_recurring_events_slug_format', true );
				$em_event_slug_type_options = get_post_meta( $post_id, 'em_event_slug_type_options', true );
				// check for categories
				$cat_table_name = $wpdb->prefix.'eventprime_ticket_categories';
				$price_options_table = $wpdb->prefix.'em_price_options';
				//$categories = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $cat_table_name WHERE `event_id` = %d ORDER BY `id` ASC", $post_id ) );
				$parent_categories = $ep_functions->get_event_ticket_category( $post_id );
				$individual_tickets = $ep_functions->get_existing_individual_ticket_lists( $parent_post_data->ID, false );
				foreach ( $child_events as $child_post ) {
					// update all metas
					$child_start_date = get_post_meta( $child_post->ID, 'em_start_date', true );
					$child_event_name = $parent_post_title;
					// generate the child name
					if( ! empty( $em_add_slug_in_event_title ) ) {
						if( ! empty( $em_recurring_events_slug_format ) ) {
							if( $em_recurring_events_slug_format == 'date' ) {
								$date = $ep_functions->ep_timestamp_to_date( $child_start_date );
								if( $em_event_slug_type_options == 'prefix' ) {
									$child_event_name = $date . '-' . $parent_post_title;
								} else{
									$child_event_name = $parent_post_title . '-' . $date;
								}
							} else{
								$occurance_number = $counter + 1;
								if( $em_event_slug_type_options == 'prefix' ) {
									$child_event_name = $occurance_number . '-' . $parent_post_title;
								} else{
									$child_event_name = $parent_post_title . '-' . $occurance_number;
								}
							}
						}
					}
					// update child post title and content
					$child_post_update = array(
						'ID'           => $child_post->ID,
						'post_title'   => $child_event_name,
						'post_content' => $parent_post_data->post_content,
					);
				  	// Update the post into the database
					wp_update_post( $child_post_update );
					if( ! empty( $parent_post_metas ) ) {
						foreach( $parent_post_metas as $meta_key => $meta_value ) {
							if( $meta_key == 'em_start_date' || $meta_key == 'em_end_date' || $meta_key == 'em_id' || $meta_key == 'em_ls_seat_plan' || $meta_key == 'em_seat_data' || $meta_key == 'meeting_data' ) {
								continue;
							} elseif( $meta_key == 'em_name' ) {
								update_post_meta( $child_post->ID, $meta_key, $child_event_name );
							} elseif( $meta_key == 'em_venue' || $meta_key == 'em_event_type' ) {
								wp_set_post_terms($child_post->ID, isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : maybe_unserialize(array()), $meta_key, false);
                                                                update_post_meta($child_post->ID, $meta_key, isset($meta_value[0]) ? maybe_unserialize($meta_value[0]) : maybe_unserialize(array()) );
                                                        } elseif( $meta_key == 'em_performer' || $meta_key == 'em_sponsor' ) {
								wp_set_post_terms( $child_post->ID, isset( $meta_value[0] ) ? maybe_unserialize( $meta_value[0] ) : maybe_unserialize(array()), $meta_key, false );
								update_post_meta( $child_post->ID, $meta_key, isset( $meta_value[0] ) ? maybe_unserialize( $meta_value[0] ) : maybe_unserialize( array() ) );
							} elseif( $meta_key == 'em_organizer' ) {
								$orgs = isset( $meta_value[0] ) ? maybe_unserialize( $meta_value[0] ) : array();
								wp_set_post_terms( $child_post->ID, $orgs, 'em_event_organizer', false );
								update_post_meta( $child_post->ID, $meta_key, $orgs );
							} else{
								update_post_meta( $child_post->ID, $meta_key, maybe_unserialize( $meta_value[0] ) );
							}
						}
						// update start and end datetime meta
						$ep_date_time_format = 'Y-m-d';
						$start_date = get_post_meta( $child_post->ID, 'em_start_date', true );
						$start_time = get_post_meta( $child_post->ID, 'em_start_time', true );
						$merge_start_date_time = $ep_functions->ep_datetime_to_timestamp( $ep_functions->ep_timestamp_to_date( $start_date, 'Y-m-d', 1 ) . ' ' . $start_time, $ep_date_time_format, '', 0, 1 );
						if( ! empty( $merge_start_date_time ) ) {
							update_post_meta( $child_post->ID, 'em_start_date_time', $merge_start_date_time );
						}
						$end_date = get_post_meta( $child_post->ID, 'em_end_date', true );
						$end_time = get_post_meta( $child_post->ID, 'em_end_time', true );
						$merge_end_date_time = $ep_functions->ep_datetime_to_timestamp( $ep_functions->ep_timestamp_to_date( $end_date, 'Y-m-d', 1 ) . ' ' . $end_time, $ep_date_time_format, '', 0, 1 );
						if( ! empty( $merge_end_date_time ) ) {
							update_post_meta( $child_post->ID, 'em_end_date_time', $merge_end_date_time );
						}
					}
					// category and ticket update
					if( ! empty( $parent_categories ) ) {
						foreach( $parent_categories as $parent_category ) {
							$get_cat_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $cat_table_name WHERE `event_id` = %d AND `parent_id` = %d", $child_post->ID, $parent_category->id ) );
							if( ! empty( $get_cat_data ) ) {
								$name = $parent_category->name;
								$capacity = $parent_category->capacity;
								$wpdb->update( $cat_table_name, 
									array( 
										'name' 		  	  => $name,
										'capacity' 		  => $capacity,
										'last_updated_by' => get_current_user_id(),
										'updated_at' 	  => wp_date("Y-m-d H:i:s", time())
									), 
									array( 'id' => $get_cat_data->id )
								);
								// update tickets
								if( ! empty( $parent_category->tickets ) ) {
									foreach( $parent_category->tickets as $parent_tickets ) {
										$get_ticket_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $price_options_table WHERE `event_id` = %d AND `parent_price_option_id` = %d", $child_post->ID, $parent_tickets->id ) );
										if( ! empty( $get_ticket_data ) ) {
											$wpdb->update( $price_options_table, 
												array( 
													'name' 		  	  			   => $parent_tickets->name,
													'description' 	  			   => $parent_tickets->description,
													'start_date' 	  			   => $parent_tickets->start_date,
													'end_date' 	  				   => $parent_tickets->end_date,
													'price' 	  				   => $parent_tickets->price,
													'special_price' 	  		   => $parent_tickets->special_price,
													'capacity' 	  				   => $parent_tickets->capacity,
													'icon' 	  					   => $parent_tickets->icon,
													'variation_color' 	  		   => $parent_tickets->variation_color,
													'seat_data' 	  			   => $parent_tickets->seat_data,
													'additional_fees' 	  		   => $parent_tickets->additional_fees,
													'allow_cancellation' 	  	   => $parent_tickets->allow_cancellation,
													'show_remaining_tickets' 	   => $parent_tickets->show_remaining_tickets,
													'show_ticket_booking_dates'    => $parent_tickets->show_ticket_booking_dates,
													'min_ticket_no' 	  		   => $parent_tickets->min_ticket_no,
													'max_ticket_no' 	  		   => $parent_tickets->max_ticket_no,
													'visibility' 	  			   => $parent_tickets->visibility,
													'offers' 	  				   => $parent_tickets->offers,
													'booking_starts' 	  		   => $parent_tickets->booking_starts,
													'booking_ends' 	  			   => $parent_tickets->booking_ends,
													'multiple_offers_option' 	   => $parent_tickets->multiple_offers_option,
													'multiple_offers_max_discount' => $parent_tickets->multiple_offers_max_discount,
													'ticket_template_id' 	  	   => $parent_tickets->ticket_template_id,
													'last_updated_by' 			   => get_current_user_id(),
													'updated_at' 	  			   => wp_date("Y-m-d H:i:s", time())
												), 
												array( 'id' => $get_ticket_data->id )
											);
										} else{
											$ticket_data = array();
											$ticket_data['event_id'] 				   = $child_post->ID;
											$ticket_data['parent_price_option_id'] 	   = $parent_tickets->id;
											$ticket_data['category_id'] 			   = $get_cat_data->id;
											$ticket_data['name'] 		  	  		   = $parent_tickets->name;
											$ticket_data['description'] 	  		   = $parent_tickets->description;
											$ticket_data['start_date'] 	  			   = $parent_tickets->start_date;
											$ticket_data['end_date'] 	  			   = $parent_tickets->end_date;
											$ticket_data['price'] 	  				   = $parent_tickets->price;
											$ticket_data['special_price'] 	  		   = $parent_tickets->special_price;
											$ticket_data['capacity'] 	  			   = $parent_tickets->capacity;
											$ticket_data['icon'] 	  				   = $parent_tickets->icon;
											$ticket_data['variation_color'] 	  	   = $parent_tickets->variation_color;
											$ticket_data['seat_data'] 	  			   = $parent_tickets->seat_data;
											$ticket_data['additional_fees'] 	  	   = $parent_tickets->additional_fees;
											$ticket_data['allow_cancellation'] 	  	   = $parent_tickets->allow_cancellation;
											$ticket_data['show_remaining_tickets'] 	   = $parent_tickets->show_remaining_tickets;
											$ticket_data['show_ticket_booking_dates']  = $parent_tickets->show_ticket_booking_dates;
											$ticket_data['min_ticket_no'] 	  		   = $parent_tickets->min_ticket_no;
											$ticket_data['max_ticket_no'] 	  		   = $parent_tickets->max_ticket_no;
											$ticket_data['visibility'] 	  			   = $parent_tickets->visibility;
											$ticket_data['offers'] 	  				   = $parent_tickets->offers;
											$ticket_data['booking_starts'] 	  		   = $parent_tickets->booking_starts;
											$ticket_data['booking_ends'] 	  		   = $parent_tickets->booking_ends;
											$ticket_data['multiple_offers_option'] 	   = $parent_tickets->multiple_offers_option;
											$ticket_data['multiple_offers_max_discount'] = $parent_tickets->multiple_offers_max_discount;
											$ticket_data['ticket_template_id'] 	  	   = $parent_tickets->ticket_template_id;
											$ticket_data['created_at'] 				   = wp_date( "Y-m-d H:i:s", time() );
											$ticket_data['updated_at'] 				   = wp_date( "Y-m-d H:i:s", time() );
											$result = $wpdb->insert( $price_options_table, $ticket_data );
										}
									}
								}
							} else{
								$cat_data = array();
								$cat_data['event_id'] 		 = $child_post->ID;
								$cat_data['parent_id'] 		 = $parent_category->id;
								$cat_data['name'] 			 = $parent_category->name;
								$cat_data['capacity'] 		 = $parent_category->capacity;
								$cat_data['priority'] 		 = $parent_category->priority;
								$cat_data['status'] 		 = $parent_category->status;
								$cat_data['created_by'] 	 = $parent_category->created_by;
								$cat_data['last_updated_by'] = $parent_category->last_updated_by;
								$cat_data['created_at'] 	 = wp_date( "Y-m-d H:i:s", time() );
								$cat_data['updated_at'] 	 = wp_date( "Y-m-d H:i:s", time() );
								$result = $wpdb->insert( $cat_table_name, $cat_data );
								$cat_id = $wpdb->insert_id;

								$cat_tickets = $ep_functions->get_existing_category_ticket_lists( $parent_post_data->ID, $parent_category->id, false );
								if( !empty( $cat_tickets ) ) {
									foreach( $cat_tickets as $ticket ) {
										$ticket = (array)$ticket;
										$parent_price_option_id = $ticket['id'];
										unset( $ticket['id'] );
										$ticket['event_id'] = $child_post->ID;
										$ticket['parent_price_option_id'] = $parent_price_option_id;
										$ticket['category_id'] = $cat_id;
										$ticket['created_at'] = wp_date( "Y-m-d H:i:s", time() );
										$ticket['updated_at'] = wp_date( "Y-m-d H:i:s", time() );
										$result = $wpdb->insert( $price_options_table, $ticket );
									}
								}
							}
						}
					}
					// check for individual ticket
					if( ! empty( $individual_tickets ) ) {
						foreach( $individual_tickets as $ticket ) {
							$parent_price_option_id = $ticket->id;
							$get_indi_ticket_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $price_options_table WHERE `event_id` = %d AND `parent_price_option_id` = %d", $child_post->ID, $parent_price_option_id ) );
							if( ! empty( $get_indi_ticket_data ) ) {
								$updated_ticket_data = array( 
									'name' 		  	  			   => $ticket->name,
									'description' 	  			   => $ticket->description,
									'start_date' 	  			   => $ticket->start_date,
									'end_date' 	  				   => $ticket->end_date,
									'price' 	  				   => $ticket->price,
									'special_price' 	  		   => $ticket->special_price,
									'capacity' 	  				   => $ticket->capacity,
									'icon' 	  					   => $ticket->icon,
									'variation_color' 	  		   => $ticket->variation_color,
									'seat_data' 	  			   => $ticket->seat_data,
									'additional_fees' 	  		   => $ticket->additional_fees,
									'allow_cancellation' 	  	   => $ticket->allow_cancellation,
									'show_remaining_tickets' 	   => $ticket->show_remaining_tickets,
									'show_ticket_booking_dates'    => $ticket->show_ticket_booking_dates,
									'min_ticket_no' 	  		   => $ticket->min_ticket_no,
									'max_ticket_no' 	  		   => $ticket->max_ticket_no,
									'visibility' 	  			   => $ticket->visibility,
									'offers' 	  				   => $ticket->offers,
									'booking_starts' 	  		   => $ticket->booking_starts,
									'booking_ends' 	  			   => $ticket->booking_ends,
									'multiple_offers_option' 	   => $ticket->multiple_offers_option,
									'multiple_offers_max_discount' => $ticket->multiple_offers_max_discount,
									'ticket_template_id' 	  	   => $ticket->ticket_template_id,
									'updated_at' 	  			   => wp_date("Y-m-d H:i:s", time())
								);
								$wpdb->update( $price_options_table, 
									$updated_ticket_data, 
									array( 'id' => $get_indi_ticket_data->id )
								);
							} else{
								$ticket = (array)$ticket;
								unset( $ticket['id'] );
								$ticket['event_id'] = $child_post->ID;
								$ticket['parent_price_option_id'] = $parent_price_option_id;
								$ticket['created_at'] = wp_date( "Y-m-d H:i:s", time() );
								$ticket['updated_at'] = wp_date( "Y-m-d H:i:s", time() );
								$result = $wpdb->insert( $price_options_table, $ticket );
							}
						}
					}
					$counter++;
					do_action( 'ep_after_edit_event_child_data', $child_post->ID, $child_post );
				}
			}
		}
	}
        
        
    

}
