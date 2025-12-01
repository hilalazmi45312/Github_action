<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Eventprime_html_Generator {

    public function ep_get_recurrence_interval() {
        $repeats = array(
            'daily' => esc_html__('Day(s)', 'eventprime-event-calendar-management'),
            'weekly' => esc_html__('Week(s)', 'eventprime-event-calendar-management'),
            'monthly' => esc_html__('Month(s)', 'eventprime-event-calendar-management'),
            'yearly' => esc_html__('Year(s)', 'eventprime-event-calendar-management'),
            'advanced' => esc_html__('Advanced', 'eventprime-event-calendar-management'),
            'custom_dates' => esc_html__('Custom Dates', 'eventprime-event-calendar-management'),
        );
        return $repeats;
    }

    public function get_ep_event_checkout_field_tabs() {
        $ep_functions = new Eventprime_Basic_Functions;
        $tabs = apply_filters(
                'ep_event_checkout_field_tabs',
                array(
                    'attendee_fields' => array(
                        'label' => esc_html__('Attendee Fields', 'eventprime-event-calendar-management'),
                        'target' => 'ep_event_attendee_fields_data',
                        'class' => array('ep_event_attendee_fields_wrap'),
                        'priority' => 10,
                    ),
                    'booking_fields' => array(
                        'label' => esc_html__('Booking Fields', 'eventprime-event-calendar-management'),
                        'target' => 'ep_event_booking_fields_data',
                        'class' => array('ep_event_booking_fields_wrap'),
                        'priority' => 20,
                    ),
                )
        );
        // Sort tabs based on priority.
        uasort($tabs, array($ep_functions, 'event_data_tabs_sort'));
        return $tabs;
    }

    /**
     * get name sub field in table structure
     * 
     * @param array $event_checkout_attendee_fields Saved Attendee Fields.
     * 
     * @return Field Html.
     */
    public function ep_get_checkout_essentials_fields_rows($event_checkout_attendee_fields = array(), $is_popup = '') {
        $field = '<tr class="ep-event-checkout-esse-name-field" title="' . esc_html__('Add attendee name field', 'eventprime-event-calendar-management') . '">';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name">' . esc_html__('Name', 'eventprime-event-calendar-management') . '</label>';
        $field .= '</td>';
        $field .= '<td>' . esc_html__('For adding attendee names', 'eventprime-event-calendar-management') . '</td>';
        $field .= '<td>';
        $em_event_checkout_name_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name']) && $event_checkout_attendee_fields['em_event_checkout_name'] == 1 ) ? 'checked="checked"' : '';
        $field .= '<input type="checkbox" name="em_event_checkout_name" class="ep-form-check-input" id="em_event_checkout_name' . $is_popup . '" value="1" data-label="' . esc_html__('Name', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_checked . '>';
        $field .= '</td>';
        $field .= '<td>&nbsp;</td>';
        $field .= '</tr>';
        $field .= $this->ep_get_name_sub_fields_rows($event_checkout_attendee_fields, $is_popup);
        return $field;
    }

    public function ep_get_name_sub_fields_rows($event_checkout_attendee_fields, $is_popup = '') {
        $field = $display = '';
        if (isset($event_checkout_attendee_fields['em_event_checkout_name']) && $event_checkout_attendee_fields['em_event_checkout_name'] == 1) {
            $display = 'style="display:table-row;"';
        }
        // first name
        $em_event_checkout_name_first_name_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name_first_name']) && $event_checkout_attendee_fields['em_event_checkout_name_first_name'] == 1 ) ? 'checked="checked"' : '';
        $first_name_display = ( empty($em_event_checkout_name_first_name_checked) ? 'style="display:none;"' : '' );
        $em_event_checkout_name_first_name_required_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name_first_name_required']) && $event_checkout_attendee_fields['em_event_checkout_name_first_name_required'] == 1 ) ? 'checked="checked"' : '';
        $field .= '<tr class="ep-sub-field-first-name ep-event-checkout-field-name-sub-row" title="' . esc_html__('Add attendee first name field', 'eventprime-event-calendar-management') . '" ' . $display . '>';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name_first_name' . $is_popup . '" class="ep-form-label">' . esc_html__('First Name', 'eventprime-event-calendar-management') . '</label></div>';
        $field .= '</td>';
        $field .= '<td>' . esc_html__('For adding attendee first name', 'eventprime-event-calendar-management') . '</td>';
        $field .= '<td>';
        $field .= '<div class="ep-form-check-wrap ep-di-flex ep-items-center"><input type="checkbox" class="ep-form-check-input ep-name-sub-fields" data-field_type="first-name" name="em_event_checkout_name_first_name' . $is_popup . '" id="em_event_checkout_name_first_name' . $is_popup . '" value="1" data-label="' . esc_html__('First Name', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_first_name_checked . '>';
        $field .= '</td>';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name_first_name_required' . $is_popup . '" class="ep-form-label ep-ml-3 ep-first-name-required" title="' . esc_html__('Require attendee first name field', 'eventprime-event-calendar-management') . '">';
        $field .= '<input type="checkbox" name="em_event_checkout_name_first_name_required' . $is_popup . '" id="em_event_checkout_name_first_name_required' . $is_popup . '" value="1" data-label="' . esc_html__('Required', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_first_name_required_checked . '>';
        $field .= '</label>';
        $field .= '</td>';
        $field .= '</tr>';
        // middle name
        $em_event_checkout_name_middle_name_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name_middle_name']) && $event_checkout_attendee_fields['em_event_checkout_name_middle_name'] == 1 ) ? 'checked="checked"' : '';
        $middle_name_display = ( empty($em_event_checkout_name_middle_name_checked) ? 'style="display:none;"' : '' );
        $em_event_checkout_name_middle_name_required_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name_middle_name_required']) && $event_checkout_attendee_fields['em_event_checkout_name_middle_name_required'] == 1 ) ? 'checked="checked"' : '';
        $field .= '<tr class="ep-sub-field-middle-name ep-event-checkout-field-name-sub-row" title="' . esc_html__('Add attendee middle name field', 'eventprime-event-calendar-management') . '" ' . $display . '>';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name_middle_name' . $is_popup . '" class="ep-form-label">' . esc_html__('Middle Name', 'eventprime-event-calendar-management') . '</label></div>';
        $field .= '</td>';
        $field .= '<td>' . esc_html__('For adding attendee middle name', 'eventprime-event-calendar-management') . '</td>';
        $field .= '<td>';
        $field .= '<div class="ep-form-check-wrap ep-di-flex ep-items-center"><input type="checkbox" class="ep-form-check-input ep-name-sub-fields" data-field_type="middle-name" name="em_event_checkout_name_middle_name' . $is_popup . '" id="em_event_checkout_name_middle_name' . $is_popup . '" value="1" data-label="' . esc_html__('Middle Name', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_middle_name_checked . '>';
        $field .= '</td>';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name_middle_name_required' . $is_popup . '" class="ep-form-label ep-ml-3 ep-middle-name-required" title="' . esc_html__('Require attendee middle name field', 'eventprime-event-calendar-management') . '">';
        $field .= '<input type="checkbox" name="em_event_checkout_name_middle_name_required' . $is_popup . '" id="em_event_checkout_name_middle_name_required' . $is_popup . '" value="1" data-label="' . esc_html__('Required', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_middle_name_required_checked . '>';
        $field .= '</label>';
        $field .= '</td>';
        $field .= '</tr>';
        // last name
        $em_event_checkout_name_last_name_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name_last_name']) && $event_checkout_attendee_fields['em_event_checkout_name_last_name'] == 1 ) ? 'checked="checked"' : '';
        $last_name_display = ( empty($em_event_checkout_name_last_name_checked) ? 'style="display:none;"' : '' );
        $em_event_checkout_name_last_name_required_checked = ( isset($event_checkout_attendee_fields['em_event_checkout_name_last_name_required']) && $event_checkout_attendee_fields['em_event_checkout_name_last_name_required'] == 1 ) ? 'checked="checked"' : '';
        $field .= '<tr class="ep-sub-field-last-name ep-event-checkout-field-name-sub-row" title="' . esc_html__('Add attendee last name field', 'eventprime-event-calendar-management') . '" ' . $display . '>';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name_last_name' . $is_popup . '" class="ep-form-label">' . esc_html__('Last Name', 'eventprime-event-calendar-management') . '</label></div>';
        $field .= '</td>';
        $field .= '<td>' . esc_html__('For adding attendee last name', 'eventprime-event-calendar-management') . '</td>';
        $field .= '<td>';
        $field .= '<div class="ep-form-check-wrap ep-di-flex ep-items-center"><input type="checkbox" class="ep-form-check-input ep-name-sub-fields" data-field_type="last-name" name="em_event_checkout_name_last_name' . $is_popup . '" id="em_event_checkout_name_last_name' . $is_popup . '" value="1" data-label="' . esc_html__('Last Name', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_last_name_checked . '>';
        $field .= '</td>';
        $field .= '<td>';
        $field .= '<label for="em_event_checkout_name_last_name_required' . $is_popup . '" class="ep-form-label ep-ml-3 ep-last-name-required" title="' . esc_html__('Require attendee last name field', 'eventprime-event-calendar-management') . '">';
        $field .= '<input type="checkbox" name="em_event_checkout_name_last_name_required' . $is_popup . '" id="em_event_checkout_name_last_name_required' . $is_popup . '" value="1" data-label="' . esc_html__('Required', 'eventprime-event-calendar-management') . '" ' . $em_event_checkout_name_last_name_required_checked . '>';
        $field .= '</label>';
        $field .= '</td>';
        $field .= '</tr>';
        return $field;
    }

    /*
     * return fixed checkout fields
     */

    public function ep_get_checkout_fixed_fields($em_event_checkout_fixed_fields = array()) {
        $terms_check = (!empty($em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_enabled']) ? 'checked="checked"' : '' );
        $display_check = (!empty($em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_enabled']) ? '' : 'style="display:none;"' );
        $term_label = (!empty($em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_label']) ? $em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_label'] : '' );
        $field = '<div class="ep-event-checkout-fixed-field ep-box-row">';
        $field .= '<div class="ep-box-col-12 ep-mt-3"><div class="ep-form-check"><label class="ep-form-label" for="em_event_checkout_fixed_terms">';
        $field .= '<input type="checkbox" name="em_event_checkout_fixed_terms" id="em_event_checkout_fixed_terms" class="ep-form-check-input" value="1" data-label="' . esc_html__('Terms & Conditions', 'eventprime-event-calendar-management') . '" ' . esc_attr($terms_check) . '>' . esc_html__('Terms & Conditions', 'eventprime-event-calendar-management');
        $field .= '</label></div></div>';
        $field .= '<div class="ep-box-col-12 ep-mt-3 ep-event-terms-sub-fields" ' . $display_check . '>';
        $field .= '<input type="text" name="em_event_checkout_terms_label" class="ep-form-control" id="em_event_checkout_terms_label" placeholder="' . esc_html__('Enter Label', 'eventprime-event-calendar-management') . '" value="' . esc_attr($term_label) . '">';
        $field .= '<div class="ep-error-message" id="ep_fixed_field_label_error"></div>';
        $field .= $this->ep_get_terms_sub_fields($em_event_checkout_fixed_fields);
        $field .= '</div>';
        $field .= '</div>';
        return $field;
    }

    /**
     * get terms sub field
     */
    public function ep_get_terms_sub_fields($em_event_checkout_fixed_fields) {
        $ep_functions = new Eventprime_Basic_Functions;
        $field = '';
        $term_option = (!empty($em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_option']) ? $em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_option'] : '' );
        $term_content = (!empty($em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_content']) ? $em_event_checkout_fixed_fields['em_event_checkout_fixed_terms_content'] : '' );
        // select page option
        $field .= '<div class="ep-sub-field-terms-page ep-box-row ep-mt-3">';
        $field .= '<div class="ep-box-col-1 "><label for="em_event_checkout_terms_page_option">';
        if ($term_option == 'page') {
            $field .= '<input type="radio" class="ep-terms-sub-fields" data-terms_type="page" name="em_event_checkout_terms_option" id="em_event_checkout_terms_page_option" value="page" data-label="' . esc_html__('Select Page', 'eventprime-event-calendar-management') . '" checked>';
        } else {
            $field .= '<input type="radio" class="ep-terms-sub-fields" data-terms_type="page" name="em_event_checkout_terms_option" id="em_event_checkout_terms_page_option" value="page" data-label="' . esc_html__('Select Page', 'eventprime-event-calendar-management') . '">';
        }
        $field .= '</label></div>';
        $field .= '<div class="ep-box-col-11 ep-sub-field-terms-page-options">';
        if ($term_option == 'page') {
            $field .= '<select name="em_event_checkout_terms_page" id="em_event_checkout_terms_page" class="ep-form-control ep-event-terms-options">';
        } else {
            $field .= '<select name="em_event_checkout_terms_page" id="em_event_checkout_terms_page" class="ep-form-control ep-event-terms-options" disabled>';
        }
        $field .= '<option value="">' . esc_html__('Select Page', 'eventprime-event-calendar-management') . '</option>';
        foreach ($ep_functions->ep_get_all_pages_list() as $page_id => $page_title) {
            if ($term_option == 'page' && is_int($term_content) && $term_content == $page_id) {
                $field .= '<option value="' . $page_id . '" selected>' . $page_title . '</option>';
            } else {
                $field .= '<option value="' . $page_id . '">' . $page_title . '</option>';
            }
        }
        $field .= '</select>';
        $field .= '<div class="ep-error-message" id="ep_fixed_field_page_option_error"></div>';
        $field .= '</div>';
        $field .= '</div>';

        // enter external url option
        $field .= '<div class="ep-sub-field-terms-url ep-box-row ep-mt-3">';
        $field .= '<div class="ep-box-col-1 "><label for="em_event_checkout_terms_url_option">';
        if ($term_option == 'url') {
            $field .= '<input type="radio" class="ep-terms-sub-fields" data-terms_type="url" name="em_event_checkout_terms_option" id="em_event_checkout_terms_url_option" value="url" data-label="' . esc_html__('Enter URL', 'eventprime-event-calendar-management') . '" checked>';
        } else {
            $field .= '<input type="radio" class="ep-terms-sub-fields" data-terms_type="url" name="em_event_checkout_terms_option" id="em_event_checkout_terms_url_option" value="url" data-label="' . esc_html__('Enter URL', 'eventprime-event-calendar-management') . '">';
        }
        $field .= '</label></div>';
        $field .= '<div class="ep-box-col-11 ep-sub-field-terms-url-options">';
        if ($term_option == 'url') {
            $field .= '<input type="url" name="em_event_checkout_terms_url" id="em_event_checkout_terms_url" class="ep-form-control ep-event-terms-options" placeholder="' . esc_html__('Enter URL', 'eventprime-event-calendar-management') . '" value="' . esc_attr($term_content) . '">';
        } else {
            $field .= '<input type="url" name="em_event_checkout_terms_url" id="em_event_checkout_terms_url" class="ep-form-control ep-event-terms-options" placeholder="' . esc_html__('Enter URL', 'eventprime-event-calendar-management') . '" disabled>';
        }
        $field .= '<div class="ep-error-message" id="ep_fixed_field_url_option_error"></div>';
        $field .= '</div>';
        $field .= '</div>';

        // enter custom content option
        $content = '';
        $field .= '<div class="ep-sub-field-terms-content ep-box-row ep-mt-3">';
        $field .= '<div class="ep-box-col-1 "><label for="em_event_checkout_terms_content_option">';
        if ($term_option == 'content') {
            $field .= '<input type="radio" class="ep-terms-sub-fields" data-terms_type="content" name="em_event_checkout_terms_option" id="em_event_checkout_terms_content_option" value="content" data-label="' . esc_html__('Enter Custom Content', 'eventprime-event-calendar-management') . '" checked>';
        } else {
            $field .= '<input type="radio" class="ep-terms-sub-fields" data-terms_type="content" name="em_event_checkout_terms_option" id="em_event_checkout_terms_content_option" value="content" data-label="' . esc_html__('Enter Custom Content', 'eventprime-event-calendar-management') . '">';
        }
        $field .= '</label></div>';
        $field .= '<div class="ep-box-col-11 ep-mb-3">';
        $field .= esc_html__('Enter Custom Content', 'eventprime-event-calendar-management');
        $field .= '</div>';
        if ($term_option == 'content') {
            $field .= '<div class="ep-box-col-12 ep-sub-field-terms-content-options ep-mt-3">';
            $field .= $ep_functions->ep_get_wp_editor(wp_kses_post($term_content), 'description');
            $field .= '<div class="ep-error-message" id="ep_fixed_field_custom_option_error"></div>';
            $field .= '</div>';
        } else {
            $field .= '<div class="ep-box-col-12 ep-sub-field-terms-content-options ep-mt-3" style="display:none;">';
            $field .= $ep_functions->ep_get_wp_editor("", 'description');
            $field .= '<div class="ep-error-message" id="ep_fixed_field_custom_option_error"></div>';
            $field .= '</div>';
        }
        $field .= '</div>';
        return $field;
    }
    
    /**
     * Add event data like no. of booking, no. of attendees
     */
    public function ep_add_event_statisticts_data_old( $post ) {
        $event_id = $post->ID;
        // get total bookings data
        $booking_controller = new EventPrime_Bookings;
        $ep_functions = new Eventprime_Basic_Functions;
        $event_bookings = $booking_controller->get_event_bookings_by_event_id( $event_id );
        $event_booking_count = count( $event_bookings );?>
        <div class="ep-event-summary-data-list">
            <label><?php esc_html_e( 'Total Bookings:', 'eventprime-event-calendar-management' );?></label>
            <label>
                <?php esc_attr_e( $event_booking_count );
                if( ! empty( $event_booking_count ) ) {
                    $event_booking_url = admin_url( 'edit.php?s&post_status=all&post_type=em_booking&event_id=' . esc_attr( $event_id ) );?> 
                    <a href="<?php echo esc_url( $event_booking_url );?>" target="__blank">
                        <?php esc_html_e( 'View', 'eventprime-event-calendar-management' );?>
                    </a><?php
                }?>
            </label>
        </div><?php
        // get total attendees
        $total_booking_numbers = $ep_functions->get_total_booking_number_by_event_id( $event_id );?>
        <div class="ep-event-summary-data-list">
            <label><?php esc_html_e( 'Total Attendees:', 'eventprime-event-calendar-management' );?></label>
            <label>
                <?php esc_attr_e( $total_booking_numbers );
                if( ! empty( $total_booking_numbers ) ) {
                    $event_attendee_page_url = admin_url( 'admin.php?page=ep-event-attendees-list&event_id=' . esc_attr( $event_id ) );?> 
                    <a href="<?php echo esc_url( $event_attendee_page_url );?>" target="__blank">
                        <?php esc_html_e( 'View', 'eventprime-event-calendar-management' );?>
                    </a><?php
                }?>
            </label>
        </div><?php
    }

    public function ep_add_event_statisticts_data($post) {
        $event_id = $post->ID;
        // get total bookings data
        $booking_controller = new EventPrime_Bookings;
        $ep_functions = new Eventprime_Basic_Functions;
        $booking_status = $ep_functions->eventprime_get_event_booking_stats_by_event_id($event_id);
        $event_booking_count = $booking_status['total_booking'];
         // get total attendees
        $total_booking = $booking_status['total_attendees']; 
        ?>
        <div class="ep-event-summary-data-list">
            <label><?php esc_html_e('Total Bookings:', 'eventprime-event-calendar-management'); ?></label>
            <label>
                <?php
                esc_attr_e($event_booking_count);
                if (!empty($event_booking_count)) {
                    $event_booking_url = admin_url('edit.php?s&post_status=all&post_type=em_booking&event_id=' . esc_attr($event_id));
                    $event_booking_url = add_query_arg(array('ep_booking_filter_nonce_field' => wp_create_nonce( 'ep_booking_filter_nonce_action' )), $event_booking_url);
                    ?> 
                    <a href="<?php echo esc_url($event_booking_url); ?>" target="__blank">
                        <?php esc_html_e('View', 'eventprime-event-calendar-management'); ?>
                    </a><?php }
                    ?>
            </label>
        </div>
        <div class="ep-event-summary-data-list">
            <label><?php esc_html_e('Total Attendees:', 'eventprime-event-calendar-management'); ?></label>
            <label>
                <?php
                esc_attr_e($total_booking);
                if (!empty($total_booking)) {
                    $event_attendee_page_url = admin_url('admin.php?page=ep-event-attendees-list&event_id=' . esc_attr($event_id));
                    $event_attendee_page_url = add_query_arg(array('ep_attendee_page_filter_nonce_field' => wp_create_nonce( 'ep_attendee_page_filter_nonce_action' )), $event_attendee_page_url);
                    
                    ?> 
                    <a href="<?php echo esc_url($event_attendee_page_url); ?>" target="__blank">
                        <?php esc_html_e('View', 'eventprime-event-calendar-management'); ?>
                    </a><?php }
                    ?>
            </label>
        </div>
        <?php
    }
    
    public function eventprime_booking_details_html($booking_id)
    {
        $booking_controller = new EventPrime_Bookings();
        $ep_functions = new Eventprime_Basic_Functions();
        $booking = $booking_controller->load_booking_detail( $booking_id );
        $order_info = isset( $booking->em_order_info ) ? $booking->em_order_info : array();
        $tickets = isset( $order_info['tickets'] ) ? $order_info['tickets'] : array();
        $ticket_sub_total = $offers = 0;
        ?>
        <div class="ep-py-3 ep-ps-3 ep-fw-bold ep-text-uppercase ep-text-small"><?php esc_html_e( 'Tickets', 'eventprime-event-calendar-management' );?></div>
        <div class="ep-box-tickets-table">
        <table class="ep-tickets-table ep-table ep-table-hover ep-text-small ep-table-borderless ep-text-start">
            <thead>
                <tr>
                    <th class="ep-ticket-item ep-ticket-type" colspan="2"><?php esc_html_e( 'Ticket Type','eventprime-event-calendar-management' );?></th>
                    <th class="ep-ticket-item ep-ticket-cost"><?php esc_html_e( 'Cost','eventprime-event-calendar-management' );?></th>
                    <th class="ep-ticket-item ep-ticket-qty"><?php esc_html_e( 'Qty','eventprime-event-calendar-management' );?></th>
                    <th  class="ep-ticket-item ep-ticket-fee"><?php esc_html_e( 'Additional fees','eventprime-event-calendar-management' );?></th>
                    <th  class="ep-ticket-item ep-ticket-fee"><?php esc_html_e( 'Offers','eventprime-event-calendar-management' );?></th>
                    <th class="ep-ticket-item ep-ticket-total"><?php esc_html_e( 'Sub Total','eventprime-event-calendar-management' );?></th>
                </tr>
            </thead>
            <?php 
            
                if( ! empty( $tickets ) ){?>
                    <tbody>
                        <?php foreach( $tickets as $ticket ){
                            if( ! empty( $ticket->offer ) ) {
                                $offers += $ticket->offer;
                            }?>
                            <tr>
                                <td colspan="2"><?php echo esc_attr( $ticket->name );?></td>
                                <td><?php echo esc_html( $ep_functions->ep_price_with_position( $ticket->price ) );?></td>
                                <td><?php echo esc_attr( $ticket->qty );?></td>
                                <td>
                                    <?php $additional_fees = array();
                                    if(isset($ticket->additional_fee)){
                                        foreach($ticket->additional_fee as $fees){
                                            if(isset($booking->eventprime_updated_pattern))
                                            {
                                                $additional_fees[] = $fees->label.' ('.$ep_functions->ep_price_with_position($fees->price).')';
                                            }
                                            else
                                            {
                                                $additional_fees[] = $fees->label.' ('.$ep_functions->ep_price_with_position($fees->price * $ticket->qty).')';
                                            }
                                        }
                                    }
                                    if(!empty($additional_fees)){
                                        echo wp_kses_post(implode(' | ',$additional_fees));
                                    }else{
                                        echo '--';
                                    }?>
                                </td>
                                <td><?php echo esc_html( (!empty( $ticket->offer ))?$ep_functions->ep_price_with_position($ticket->offer):'--');?></td>
                                <td><?php echo esc_html( $ep_functions->ep_price_with_position( $ticket->subtotal ) );?></td>
                            </tr><?php 
                            $ticket_sub_total = $ticket_sub_total + $ticket->subtotal;
                        }?>
                    </tbody><?php
                } else if( ! empty( $order_info['order_item_data'] ) ) {?>
                    <tbody>
                        <?php foreach( $order_info['order_item_data'] as $order_item_data ){?>
                            <tr>
                                <td colspan="2"><?php echo esc_attr( $order_item_data->variation_name );?></td>
                                <td><?php echo esc_html( $ep_functions->ep_price_with_position( $order_item_data->price ) );?></td>
                                <td><?php echo esc_attr( $order_item_data->quantity );?></td>
                                <td><?php echo '--';?></td>
                                <td><?php echo esc_html( $ep_functions->ep_price_with_position( $order_item_data->sub_total ) );?></td>
                            </tr><?php 
                            $ticket_sub_total = $ticket_sub_total + $order_item_data->sub_total;
                        }?>
                    </tbody><?php
                }
            ?>  
        </table>
    </div>
        <div class="ep-order-data-row ep-order-totals-items">
            <div class="ep-used-coupons">
                <?php 
                $order_info['coupon_code'] = isset( $order_info['coupon_code'] ) && !empty( $order_info['coupon_code'] ) ? array( $order_info['coupon_code'] ) : [];

                if ( isset($order_info['woo_coupon_data']) && !empty($order_info['woo_coupon_data']) && count($order_info['woo_coupon_data']) > 0 ) {
                    foreach ( $order_info['woo_coupon_data'] as $key => $val ) {
                        if ( isset($val['coupon_code']) && !empty($val['coupon_code']) ) {
                            $order_info['coupon_code'][] = $val['coupon_code'];
                        }
                    }
                }
                if ( isset($order_info['ep_coupon_data']) && !empty($order_info['ep_coupon_data']) && count($order_info['ep_coupon_data']) > 0 ) {
                    $order_info['coupon_code'][] = $order_info['ep_coupon_data']['coupon_code'];
                }

                if( isset($order_info['coupon_code']) && !empty($order_info['coupon_code']) ):
                ?>
                <ul class="ep_coupon_list">
                    <li><strong><?php esc_html_e('Coupon(s)','eventprime-event-calendar-management');?></strong></li>
                    <?php 
                    foreach ( $order_info['coupon_code'] as $coupon_code ){ ?>
                        <li class="code">
                            <span><?php echo esc_html($coupon_code);?></span>
                        </li>
                        <?php
                    } ?>
                </ul>
                <?php endif;?>
            </div>

            <table class="ep-order-totals ep-table ep-table-hover ep-text-small ep-table-borderless ep-ml-4">
                <tbody>
                    <tr>
                        <td class="label"><?php esc_html_e( 'Event Fee:', 'eventprime-event-calendar-management' );?></td>
                        <td width="1%"></td>
                        <td class="ep-ticket-total-amount">
                            <span>
                                <?php if( !empty( $order_info['event_fixed_price'] ) ) {
                                    echo esc_html( $ep_functions->ep_price_with_position( $order_info['event_fixed_price'] ) );
                                } else{
                                    echo esc_html( $ep_functions->ep_price_with_position( 0 ) );
                                }?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label"><?php esc_html_e( 'Tickets Subtotal:', 'eventprime-event-calendar-management' );?></td>
                        <td width="1%"></td>
                        <td class="ep-ticket-total-amount">
                            <span><?php echo esc_html( $ep_functions->ep_price_with_position( $ticket_sub_total ) );?></span>
                        </td>
                    </tr>
                    <?php 

                    do_action( 'ep_admin_booking_detail_after_tickets_subtotal', $booking ); 

                   /* if( ! empty( $offers ) ) {?>
                        <tr>
                            <td class="label"><?php esc_html_e( 'Offers:', 'eventprime-event-calendar-management' );?></td>
                            <td width="1%"></td>
                            <td class="ep-ticket-total-amount"><span>-<?php echo esc_html( $ep_functions->ep_price_with_position( $offers ) );?></span></td>
                        </tr><?php
                    }*/
                    if( isset( $order_info['coupon_code'] ) && isset($order_info['discount']) ) {?>
                        <tr>
                            <td class="label"><?php esc_html_e( 'Coupon:', 'eventprime-event-calendar-management' );?></td>
                            <td width="1%"></td>
                            <td class="ep-ticket-total-amount"><span>-<?php echo esc_html( $ep_functions->ep_price_with_position( $order_info['discount'] ) );?></span></td>
                        </tr><?php 
                    }?>
                    <tr>
                        <td class="label"><?php esc_html_e( 'Total:', 'eventprime-event-calendar-management' );?></td>
                        <td width="1%"></td>
                        <td class="ep-ticket-total-amount">
                            <span>
                                <?php echo wp_kses_post($ep_functions->ep_get_event_booking_total( $booking )); ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="clear"></div>
            <div class="clear"></div>
        </div>
        <?php
    }
    
    public function eventprime_checkout_total_html($total_price,$total_tickets,$event_id,$extra)
    {
        $ep_functions = new Eventprime_Basic_Functions(); 
        //print_r($extra);
        if(!empty($extra) && isset($extra['woocommerce_integration']))
        {
            $product_total_price = $extra['product_total_price'];
            $inital_total_price = $total_price;
            $total_price = $total_price + $product_total_price;
        }
        $total_price = apply_filters( 'ep_event_booking_total_price_extension',$total_price,$total_tickets,$event_id,$extra);
        if( $total_price ) {
            echo esc_html( $ep_functions->ep_price_with_position( $total_price ) );
        } else{
            $ep_functions->ep_show_free_event_price( $total_price );
        }?>
        <input type="hidden" name="ep_event_booking_total_price" value="<?php echo esc_attr( round($total_price, 2) );?>" />
        <input type="hidden" name="ep_event_booking_total_tickets" value="<?php echo absint( $total_tickets );?>" />
        <?php 
        do_action( 'eventprime_checkout_total_html',$total_price,$total_tickets,$event_id,$extra); 
        
    }

}
