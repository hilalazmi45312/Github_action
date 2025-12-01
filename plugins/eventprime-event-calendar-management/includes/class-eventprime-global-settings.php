<?php

class Eventprime_Global_Settings{
    
    public $setting_options = [];
    
    public $sub_options = [];
    
    public $ep_setting_tabs = '';

    public function __construct() {
        $this->get_custom_css_setting_options();
        $this->get_button_labels_setting_options();
        $this->get_frontend_views_settings_options();
        $this->get_pages_setting_options();
        $this->get_payment_setting_options();
        $this->get_email_setting_options();
        $this->get_general_setting_options();
        $this->get_forms_setting_options();
        $this->get_license_setting_options();
        $this->ep_get_front_views_content_settings();
        $this->get_gdpr_setting_options();

        
    }
    
    /**
     * Merge all GDPR-related default settings
    */
    public function get_gdpr_setting_options() {

       // Default to WP privacy policy page, or fallback to /privacy-policy

       $gdpr_options = array(
           // Master toggle
           'enable_gdpr_tools'           => '',   // '' = off, '1' = on

           // Child toggles
           'enable_gdpr_download'        => '',
           'enable_gdpr_delete'          => '',
           'enable_gdpr_download_request'        => '',
           'enable_gdpr_delete_request'          => '',
           'show_gdpr_consent_checkbox'  => '',
           'show_gdpr_badge'             => '',

           // Text & URLs
           'gdpr_consent_text'           => "I agree to the site's Privacy Policy.",
           'gdpr_privacy_policy_url'     => '',

           // Data retention (days) – blank means disabled
           'gdpr_retention_period'       => '',
           'cookie_consent_message'       => 'We use cookies to ensure you get the best experience on our website.',
           'cookie_consent_button_text'  => 'Accept',
           'enable_cookie_consent_banner'=> '',

       );

       $this->setting_options = array_merge( $this->setting_options, $gdpr_options );
   }
    
    /**
     * Merge all front end submission related settings
     */
    public function get_fes_setting_options() {
        $fes_options = array(
            'ues_confirm_message'                => 'Thank you for submitting your event. We will review and publish it soon.',
            'ues_login_message'                  => 'Please login to submit your event.',
            'ues_default_status'                 => 'draft',
            'allow_submission_by_anonymous_user' => '',
            'frontend_submission_roles'          => array(),
            'ues_restricted_submission_message'  => 'You are not authorised to access this page. Please contact with your administrator.',
            'frontend_submission_sections'       => array( 'fes_event_featured_image' => 1, 'fes_event_booking' => 1, 'fes_event_link' => 1, 'fes_event_type' => 1, 'fes_event_location' => 1, 'fes_event_performer' => 1, 'fes_event_organizer' => 1, 'fes_event_more_options' => 1, 'fes_event_text_color' => 1 ),
            'frontend_submission_required'       => array( 'fes_event_description' => 0, 'fes_event_booking' => 0, 'fes_booking_price' => 0,'fes_event_link' => 0, 'fes_event_type' => 0, 'fes_event_location' => 0, 'fes_event_performer' => 0, 'fes_event_organizer' => 0 ),
            'fes_allow_media_library'            => '',
            'fes_allow_user_to_delete_event'     => '',
            'fes_show_add_event_in_profile'      => '',
        );
        $this->setting_options = array_merge( $this->setting_options, $fes_options );
    }

    /**
     * Merge custom css related settings
     */
    public function get_custom_css_setting_options() {
        $custom_css_options = array(
            'custom_css' => ''
        );
        $this->setting_options = array_merge( $this->setting_options, $custom_css_options );
    }

    /**
     * Merge button labels related settings
     */
    public function get_button_labels_setting_options() {
        $button_titles_options = array(
            'button_titles' => ''
        );
        $this->setting_options = array_merge( $this->setting_options, $button_titles_options );
    }

    /**
     * Merge frontend views related settings
     */
    public function get_frontend_views_settings_options() {
        $this->get_performers_setting_options();
        $this->get_events_setting_options();
        $this->get_event_types_setting_options();
        $this->get_venues_setting_options();
        $this->get_organizers_setting_options();
        $this->get_event_details_setting_options();
    }

    /**
     * Merge performers view related options
     */
    public function get_performers_setting_options( $return_options = FALSE ) {
        $performers_options = array(
            'performer_display_view'              => 'card',
            'performer_limit'                     => 0,
            'pop_performer_limit'                 => 5,
            'performer_no_of_columns'             => 4,
            'performer_load_more'                 => 1,
            'performer_search'                    => 1,
            'single_performer_show_events'        => 1,
            'single_performer_event_display_view' => 'mini-list',
            'single_performer_event_limit'        => 0,
            'single_performer_event_column'       => 4,
            'single_performer_event_load_more'    => 1,
            'single_performer_hide_past_events'   => 0,
            'performer_box_color'                 => array('A6E7CF', 'DBEEC1', 'FFD3B6', 'FFA9A5'),
            'single_performer_event_section_title'   => 'Upcoming Events',
        );
        $performers_options = apply_filters('ep_performers_options',$performers_options);
        if( $return_options == TRUE ) {
            return $performers_options;
        }
        $this->setting_options = array_merge( $this->setting_options, $performers_options );
    }
    
    /*
     * Merge event types view related options
     */
    public function get_events_setting_options($return_options = FALSE){
        $events_options = array(
            'event_listings_date_format_std_option'      => '', 
            'event_listings_date_format_val'      => '', 
            'default_cal_view'                => 'month',
            'enable_default_calendar_date'    => 0,
            'calendar_title_format'           => 'MMMM, YYYY',
            'hide_calendar_rows'              => 0,
            'hide_time_on_front_calendar'     => 0,
            'show_event_types_on_calendar'     => 1,
            'eventprime_theme' =>'default',
            'front_switch_view_option'        => array( 'month', 'week', 'day', 'listweek', 'square_grid', 'staggered_grid', 'slider', 'rows' ),
            'hide_past_events'                => 0,
            'show_no_of_events_card'          => 10,
            'card_view_custom_value'          => 1,
            'disable_filter_options'          => 0,
            'hide_old_bookings'               => 0,
            'calendar_column_header_format'   => 'dddd',
            'shortcode_hide_upcoming_events'  => 0,
            'redirect_third_party'            => 0,
            'hide_event_custom_link'          => 0,
            'show_qr_code_on_single_event'    => 1,
            'show_max_event_on_calendar_date' => 3,
            'event_booking_status_option'     => '',
            'open_detail_page_in_new_tab'     => 0,
            'events_no_of_columns'            => '',
            'events_image_visibility_options' => 'cover',
            'events_image_height'             => '',
            'show_trending_event_types'       => 0,
            'no_of_event_types_displayed'     => 5,
            'show_events_per_event_type'      => 0,
            'sort_by_events_or_bookings'      => '',
        );
        if( $return_options == TRUE ) {
            return $events_options;
        }
        $this->setting_options = array_merge( $this->setting_options, $events_options );
    }
    /**
     * Merge event types view related options
     */
    public function get_event_types_setting_options( $return_options = FALSE ) {
        $event_types_options = array(
            'type_display_view'              => 'card',
            'type_limit'                     => 0,
            'type_no_of_columns'             => 4,
            'type_load_more'                 => 1,
            'type_search'                    => 1,
            'single_type_show_events'        => 1,
            'single_type_event_display_view' => 'mini-list',
            'single_type_event_limit'        => 0,
            'single_type_event_column'       => 4,
            'single_type_event_load_more'    => 1,
            'single_type_hide_past_events'   => 0,
            'type_box_color'                 => array('A6E7CF', 'DBEEC1', 'FFD3B6', 'FFA9A5'),
            'single_type_event_order'        => 'asc',
            'single_type_event_orderby'      => 'em_start_date_time',
            'single_type_event_section_title' => 'Upcoming Events',
        );
        if( $return_options == TRUE ) {
            return $event_types_options;
        }
        $this->setting_options = array_merge( $this->setting_options, $event_types_options );
    }

    /**
     * Merge venues view related options
     */
    public function get_venues_setting_options( $return_options = FALSE ) {
        $venues_options = array(
            'venue_display_view'              => 'card',
            'venue_limit'                     => 0,
            'venue_no_of_columns'             => 4,
            'venue_load_more'                 => 1,
            'venue_search'                    => 1,
            'single_venue_show_events'        => 1,
            'single_venue_event_display_view' => 'mini-list',
            'single_venue_event_limit'        => 0,
            'single_venue_event_column'       => 4,
            'single_venue_event_load_more'    => 1,
            'single_venue_hide_past_events'   => 1,
            'single_venue_event_section_title' => 'Upcoming Events',
            'venue_box_color'                 => array('A6E7CF', 'DBEEC1', 'FFD3B6', 'FFA9A5'),
        );
        $venues_options = apply_filters('ep_venues_options',$venues_options);
        if( $return_options == TRUE ) {
            return $venues_options;
        }
        $this->setting_options = array_merge( $this->setting_options, $venues_options );
    }
    /**
     * Merge organizers view related options
     */
    public function get_organizers_setting_options( $return_options = FALSE ) {
        $organizers_options = array(
            'organizer_display_view'              => 'card',
            'organizer_limit'                     => 0,
            'organizer_no_of_columns'             => 4,
            'organizer_load_more'                 => 1,
            'organizer_search'                    => 1,
            'single_organizer_show_events'        => 1,
            'single_organizer_event_display_view' => 'mini-list',
            'single_organizer_event_limit'        => 0,
            'single_organizer_event_column'       => 0,
            'single_organizer_event_load_more'    => 1,
            'single_organizer_hide_past_events'   => 0,
            'single_organizer_event_section_title'   => 'Upcoming Events',
            'organizer_box_color'                 => array('A6E7CF', 'DBEEC1', 'FFD3B6', 'FFA9A5'),
        );
        $organizers_options = apply_filters('ep_organizers_options',$organizers_options);
        if( $return_options == TRUE ) {
            return $organizers_options;
        }
        $this->setting_options = array_merge( $this->setting_options, $organizers_options );
    }

    /**
     * Merge event detail view related options
     */
    public function get_event_details_setting_options( $return_options = FALSE ){
        $event_detail_options = array(
            'single_event_date_format_std_option'      => '', 
            'single_event_date_format_val'      => '', 
            'show_qr_code_on_single_event'       => 1,
            'expand_venue_container'             => 1,
            'hide_weather_tab'                   => 0,
            'weather_unit_fahrenheit'            => 0,
            'hide_map_tab'                       => 0,
            'hide_other_event_tab'               => 0,
            'hide_age_group_section'             => 0,
            'hide_note_section'                  => 0,
            'hide_performers_section'            => 0,
            'hide_organizers_section'            => 0,
            'show_print_icon'                    => 0,
            'event_detail_image_width'           => '',
            'event_detail_image_height'          => 'auto',
            'event_detail_image_height_custom'   => '',
            'event_detail_image_align'           => '',
            'event_detail_image_auto_scroll'     => 0,
            'event_detail_image_slider_duration' => 4,
            'event_detail_message_for_recap'     => 'This event has ended and results are now available.',
            'event_detail_result_heading'        => 'Results',
            'event_detail_result_button_label'   => 'View Results',
        );
        if( $return_options == TRUE ) {
            return $event_detail_options;
        }
        $this->setting_options = array_merge( $this->setting_options, $event_detail_options );
    }

    /**
     * Merge Pages related options
     */
    public function get_pages_setting_options(){
        $pages_options = array(
            'performers_page'      => '',
            'venues_page'          => '',
            'events_page'          => '',
            'booking_page'         => '',
            'profile_page'         => '',
            'event_types'          => '',
            'event_submit_form'    => '',
            'booking_details_page' => '',
            'event_organizers'     => '',
            'login_page'           => '',
            'register_page'        => '',
        );
        $pages_options = apply_filters('ep_add_pages_options',$pages_options);
        $this->setting_options = array_merge( $this->setting_options, $pages_options );
    }
    /**
     * Merge Payment related options
     */
    public function get_payment_setting_options() {
        $payment_options = array(
            'payment_order'       => array(),
            'currency'            => 'USD',
            'currency_position'   => 'before',
            'paypal_processor'    => '',
            'paypal_client_id'    => '',
            'default_payment_processor' => ''
        );
        $payment_options          = apply_filters( 'ep_add_emailer_options', $payment_options );
        $this->setting_options    = array_merge( $this->setting_options, $payment_options );
    }
    public function get_email_setting_options(){
        $admin_email = get_option('admin_email');
        $email_options = array(
            'disable_admin_email'                   => '',
            'disable_frontend_email'                => '',
            'registration_email_subject'            => 'User registration successful!',
            'registration_email_content'            => '',
            'reset_password_mail_subject'           => 'Reset your password',
            'reset_password_mail'                   => '',
            'send_booking_pending_email'            => 1,
            'booking_pending_email_subject'         => 'Your payment is pending',
            'booking_pending_email'                 => '',
            'booking_pending_email_cc'              => '',
            'send_booking_pending_admin_email'      => 1,
            'booking_pending_admin_email_subject'   => 'Booking Pending',
            'booking_pending_admin_email'           => '',
            'booking_pending_admin_email_cc'        => '',
            'send_booking_confirm_email'            => 1,
            'booking_confirm_email_subject'         => 'Your booking is confirmed!',
            'booking_confirmed_email'               => '',
            'booking_confirmed_email_cc'            => '',
            'send_booking_cancellation_email'       => 1,
            'booking_cancelation_email_subject'     => 'Your booking has been cancelled',
            'booking_cancelation_email'             => '',
            'booking_cancelation_email_cc'          => '',
            'send_booking_refund_email'             => 1,
            'booking_refund_email_subject'          => 'Refund for your booking',
            'booking_refund_email'                  => '',
            'booking_refund_email_cc'               => '',
            'send_event_submitted_email'            => 1,
            'event_submitted_email_subject'         => 'Event submitted successfully!',
            'event_submitted_email'                 => '',
            'event_submitted_email_cc'              => '',
            'send_event_approved_email'             => 1,
            'event_approved_email_subject'          => 'Your event is now live!',
            'event_approved_email'                  => '',
            'send_admin_booking_confirm_email'      => 1,
            'admin_booking_confirmed_email_subject' => 'New event booking',
            'admin_booking_confirmed_email'         => '',
            'admin_booking_confirmed_email_cc'      => '',
            'admin_booking_confirm_email_attendees' => '',
            'ep_admin_email_to'                     => $admin_email,
            'ep_admin_email_from'                   => $admin_email,
         
        );
        $email_options = apply_filters('ep_add_emailer_options',$email_options);
        $this->setting_options = array_merge( $this->setting_options, $email_options );
    }
    
    /**
     * Genral options Setting
     */
    public function get_general_setting_options(){
        $ep_functions = new Eventprime_Basic_Functions;
        $general_options = array(
            // regular settings
            'eventprime_theme'                         => 'default',
            'time_format'                         => 'h:mmt',
            'default_calendar_date'               => $ep_functions->ep_get_local_timestamp(),
            'required_booking_attendee_name'      => 0,
            'hide_0_price_from_frontend'          => 0,
            'datepicker_format'                   => 'yy-mm-dd&Y-m-d',
            'show_qr_code_on_ticket'              => 1,
            'checkout_page_timer'                 => 4,
            'enable_event_time_to_user_timezone'  => 1,
            'show_timezone_message_on_event_page' => 1,
            'timezone_related_message'            => 'All event times are displayed based on {{$timezone}} timezone.',
            'ep_frontend_font_size'               => 14,
            'hide_wishlist_icon'                  => 0,
            'enable_dark_mode'                    => 0,
            
            //SEO
            'enable_seo_urls'                     => 0,
            'seo_urls'                            => array( 'event_page_type_url' => 'event', 'performer_page_type_url' => 'performer', 'organizer_page_type_url' => 'organizer', 'venues_page_type_url' => 'venue', 'types_page_type_url' => 'event-type', 'sponsor_page_type_url' => 'sponsor' ),
            'ep_desk_normal_screen'               => '',
            'ep_desk_large_screen'                => '',

            //EXternal
            'gmap_api_key'                        => '',
            'weather_api_key'                        => '',
            'social_sharing'                      => 0,
            'gcal_sharing'                        => 0,
            'google_cal_client_id'                => '',
            'google_cal_api_key'                  => '',
            'google_recaptcha'                    => 0,
            'google_recaptcha_site_key'           => '',
            'google_recaptcha_secret_key'         => ''
        );
        $general_options = apply_filters('ep_add_general_options',$general_options);
        $this->setting_options = array_merge( $this->setting_options, $general_options );
    }

    /**
     * Merge forms related settings
     */
    public function get_forms_setting_options() {
        $this->get_fes_setting_options();
        $this->get_login_form_setting_options();
        $this->get_register_form_setting_options();
        $this->get_checkout_register_form_setting_options();
    }

    /**
     * Merge all login form related settings
     */
    public function get_login_form_setting_options() {
        $ep_functions = new Eventprime_Basic_Functions;
        $login_options = array(
            'login_id_field'                   => 'username',
            'login_id_field_label_setting'     => 'User Name',
            'login_password_label'             => 'Password',
            'login_show_rememberme'            => '1',
            'login_show_rememberme_label'      => 'Remember me',
            'login_show_forgotpassword'        => '1',
            'login_show_forgotpassword_label'  => 'Forgot password?',
            'login_google_recaptcha'           => '',
            'login_google_recaptcha_label'     => '',
            'login_heading_text'               => '',
            'login_subheading_text'            => '',
            'login_button_label'               => 'Login',
            'login_redirect_after_login'       => ( ! empty( $ep_functions->ep_get_global_settings( 'profile_page' ) ) ? $ep_functions->ep_get_global_settings( 'profile_page' ) : '' ),
            'login_show_registerlink'          => 1,
            'login_show_registerlink_label'    => 'Register',
        );
        $this->setting_options = array_merge( $this->setting_options, $login_options );
    }

    /**
     * Merge all register form related settings
     */
    public function get_register_form_setting_options() {
        $register_options = array(
            'login_registration_form'           => 'ep',
            'login_rm_registration_form'        => '',
            'register_google_recaptcha'         => '',
            'register_username'                 => array( 'show' => 1, 'mandatory' => 1, 'label' => 'User Name' ),
            'register_email'                    => array( 'show' => 1, 'mandatory' => 1, 'label' => 'User Email' ),
            'register_password'                 => array( 'show' => 1, 'mandatory' => 0, 'label' => 'Password' ),
            'register_repeat_password'          => array( 'show' => 1, 'mandatory' => 0, 'label' => 'Repeat Password' ),
            'register_dob'                      => array( 'show' => 0, 'mandatory' => 0, 'label' => 'Date of Birth' ),
            'register_phone'                    => array( 'show' => 1, 'mandatory' => 0, 'label' => 'Phone' ),
            'register_timezone'                 => array( 'show' => 0, 'mandatory' => 0, 'label' => 'Timezone' ),
        );
        $this->setting_options = array_merge( $this->setting_options, $register_options );
    }
    
    /**
     * Merge all checkout register form related settings
     */
    public function get_checkout_register_form_setting_options() {
        $register_options = array(
            'checkout_register_fname'       => array( 'label'=> 'First Name' ),
            'checkout_register_lname'       => array( 'label'=> 'Last Name' ),
            'checkout_register_username'    => array( 'label'=> 'User Name' ),
            'checkout_register_email'       => array( 'label'=> 'Email' ),
            'checkout_register_password'    => array( 'label'=> 'Password' ),
            'checkout_reg_google_recaptcha' => 0,
        );
        $this->setting_options = array_merge( $this->setting_options, $register_options );
    }
    
     /**
     * Merge license related settings
    */
    public function get_license_setting_options() {
        $license_options = array(
            'ep_premium_license_option_value' => '',
            'ep_free_license_item_id'    => 23935,
            'ep_free_license_item_name'  => 'EventPrime Free',
            'ep_premium_license_item_id'    => 19088,
            'ep_premium_license_item_name'  => 'EventPrime Business',
            'ep_premium_license_key'        => '',
            'ep_premium_license_status'     => '',
            'ep_premium_license_response'   => '',
            'ep_professional_license_item_id'   => 23912,
            'ep_professional_license_item_name' => 'EventPrime Professional',
            'ep_essential_license_item_id'   => 23902,
            'ep_essential_license_item_name' => 'EventPrime Essential',
            'ep_premium_plus_license_item_id'   => 21789,
            'ep_premium_plus_license_item_name' => 'EventPrime Premium+',
            'ep_metabundle_license_item_id'   => 22462,
            'ep_metabundle_license_item_name' => 'EventPrime for MetaBundle',
            'ep_metabundle_plus_license_item_id'   => 21790,
            'ep_metabundle_plus_license_item_name' => 'EventPrime for MetaBundle+',
            'ep_license_email' => '',
            'ep_license_key' => ''
        );
        
        $this->setting_options = array_merge( $this->setting_options, $license_options );
    }
    

    public function ep_get_settings( $option_name = null ) { 
        $options = get_option('em_global_settings');
        if( ! empty( $option_name ) ) {
            // if option name passed then call option setting method
            $setting_call = 'get_'.$option_name.'_setting_options';
            $this->setting_options = $this->$setting_call( TRUE );
        }
        $settings = (object)$this->setting_options;
        if(isset($options) && !empty($options))
        {
            foreach ( $options as $key => $val ) {
                if ( property_exists( $settings, $key ) ) {
                   $settings->{ $key } = maybe_unserialize( $val );
                }
            }
        }
        //print_r($settings);die;
        return apply_filters( 'ep_add_global_setting_options', $settings, $options );
    }
    
    /**
     * Save global settings
     */
    public function ep_save_settings( $global_settings ) {
        if( ! current_user_can( 'manage_options' ) ) return;
        
        $options = (object)get_option('em_global_settings');
        foreach( $global_settings as $key => $val ){
            $options->$key = $val;
        }
        update_option( 'em_global_settings', $options );
    }
    
    /**
     * EventPrime setting tabs
     */
    
    public function ep_get_settings_html()
    {
            $extension_setting = 0;
            $tab = filter_input(INPUT_GET, 'tab');
            $setting_tabs = $this->ep_get_settings_tabs();
            if(isset( $tab) && array_key_exists( $tab, $setting_tabs['extension'] )){
                $extension_setting = 1;
                $active_tab = $tab;
            }else{
                $active_tab = isset( $tab ) && array_key_exists( $tab, $setting_tabs['core'] ) ? $tab : 'general';
            }?>
            <div class="wrap ep-admin-setting-tabs">
                <form method="post" id="ep_setting_form" action="<?php echo esc_url(admin_url( 'admin-post.php' )); ?>" enctype="multipart/form-data">
                    <h2 class="nav-tab-wrapper">
                        <?php
                        $tab_url = remove_query_arg( array( 'section', 'sub_tab' ) );
                        $nonce = wp_create_nonce('ep_settings_tab');
                        foreach ( $setting_tabs['core'] as $tab_id => $tab_name ) {
                            $tab_url = add_query_arg( 
                                array( 'tab' => $tab_id,'tab_nonce' => $nonce),
                                $tab_url
                            );
                            $active = $active_tab == $tab_id ? ' nav-tab-active' : '';
                            echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . esc_attr($active) . '">';
                                echo esc_html( $tab_name );
                            echo '</a>';
                        }
                        if($extension_setting){
                            $tab_url = add_query_arg( 
                                array( 'tab' => $active_tab),
                                $tab_url
                            );
                            $active =' nav-tab-active';
                            echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $setting_tabs['extension'][$active_tab] ) . '" class="nav-tab' . esc_attr($active) . '">';
                                echo esc_html( $setting_tabs['extension'][$active_tab] );
                            echo '</a>';
                        }?>
                    </h2>
                    <?php $this->ep_get_settings_tabs_content( $active_tab );

                    do_action( 'ep_setting_submit_button' );?>
                </form>
            </div><?php
    }
    public function ep_get_settings_tabs() {
        $tabs = array();
        $tabs['general']        = esc_html__( 'General', 'eventprime-event-calendar-management' );
        $tabs['payments']       = esc_html__( 'Payments', 'eventprime-event-calendar-management' );
        $tabs['pages']          = esc_html__( 'Pages', 'eventprime-event-calendar-management' );
        $tabs['emails']         = esc_html__( 'Emails', 'eventprime-event-calendar-management' );
        $tabs['checkoutfields'] = esc_html__( 'Checkout Fields', 'eventprime-event-calendar-management' );
        $tabs['customcss']      = esc_html__( 'Custom CSS', 'eventprime-event-calendar-management' );
        $tabs['buttonlabels']   = esc_html__( 'Language', 'eventprime-event-calendar-management' );
        $tabs['frontviews']     = esc_html__( 'Frontend Views', 'eventprime-event-calendar-management' );
        $tabs['forms']          = esc_html__( 'Forms', 'eventprime-event-calendar-management' );
        $tabs['license']        = esc_html__( 'Licenses', 'eventprime-event-calendar-management' );
        $tabs['extensions']     = esc_html__( 'Extensions', 'eventprime-event-calendar-management' );
        $tabs['gdpr']     = esc_html__( 'GDPR', 'eventprime-event-calendar-management' );
        
        $this->ep_setting_tabs = array_keys( $tabs );
        $tabs_list['core'] = $tabs;
        $tabs_list['extension'] = apply_filters( 'ep_admin_settings_tabs', array() );
        
        return $tabs_list;
    }

    /**
     * Return setting tabs content
     */
    public function ep_get_settings_tabs_content( $active_tab ) {
       // global $wpdb, $wp_roles;
        
        global $wpdb, $wp_roles;
        $options = array();
        $ep_functions = new Eventprime_Basic_Functions;
        $options['global']               = $this->ep_get_settings();
        $options['payments']             = $this->ep_payment_gateways_order();
        $options['payments_settings']    = $this->ep_payments_gateways_setting();
        $options['emailers']             = $this->ep_emailer_list();
        $options['emailers_settings']    = $this->ep_emailer_setting();
        $options['checkout_field_types'] = $ep_functions->ep_checkout_field_types();
        $options['checkout_fields_data'] = $ep_functions->ep_get_checkout_fields_data();
        $options['pages']                = $ep_functions->ep_get_all_pages_list();
        $options['buttonsections']       = $this->get_button_section_lists();
        $options['labelsections']        = $this->get_label_section_lists();
        $options['buttons_help_text']    = $this->get_label_section_help_text_lists();
        $options['form_list']            = $this->ep_settings_forms_list();
        $options['extensions']           = $this->ep_setting_extensions_list();
        $options['allowed_html']         = $ep_functions->eventprime_get_allowed_wpkses_html();
        $options['gdpr']                 = $this->ep_get_gdpr_settings_content();
        if( in_array( $active_tab, $this->ep_setting_tabs ) ){
            include plugin_dir_path( __DIR__ ) .'/admin/partials/settings/settings-tab-'. $active_tab .'.php';
        }else{
            do_action( 'ep_get_extended_settings_tabs_content', $active_tab );
        }
    }
    
    /**
     * Button sections
     */
    public function get_button_section_lists() {
        $buttonsections = array( 'Buy Tickets', 'Booking closed', 'Booking start on', 'Free', 'View Details', 'Get Tickets Now', 'Checkout', 'Register', 'Add Details & Checkout', 'Submit Payment', 'Sold Out' );
        return apply_filters( 'ep_settings_language_buttons', $buttonsections );
    }

    /**
     * Label sections
     */
    public function get_label_section_lists() {
        $labelsections = array( 'Event-Type', 'Event-Types', 'Venue', 'Venues', 'Performer', 'Performers', 'Organizer', 'Organizers', 'Add To Wishlist', 'Remove From Wishlist', 'Ticket', 'Tickets Left', 'Organized by' );
        return apply_filters( 'ep_settings_language_labels', $labelsections );
    }
    
    /**
     * Label help text
     */
    public function get_label_section_help_text_lists() {
        $label_help_text['Event-Type']           = 'Label representing singular word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Event-Type\' will be used across EventPrime.';
        $label_help_text['Event-Types']          = 'Label representing plural word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Event-Types\' will be used across EventPrime.';
        $label_help_text['Venue']                = 'Label representing singular word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Venue\' will be used across EventPrime.';
        $label_help_text['Venues']               = 'Label representing plural word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Venues\' will be used across EventPrime.';
        $label_help_text['Performer']            = 'Label representing singular word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Performer\' will be used across EventPrime.';
        $label_help_text['Performers']           = 'Label representing plural word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Performers\' will be used across EventPrime.';
        $label_help_text['Organizer']            = 'Label representing singular word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Organizer\' will be used across EventPrime.';
        $label_help_text['Organizers']           = 'Label representing plural word for event participants based on your industry. For example: Speaker, Actor, Player etc. If you choose to leave it blank, the word \'Organizers\' will be used across EventPrime.';
        $label_help_text['Add To Wishlist']      = 'Appears when hovering above the wishlist icon.';
        $label_help_text['Remove From Wishlist'] = 'Appears in the Wishlist section of the user area.';
        $label_help_text['Ticket']               = 'Appears as heading while adding attendees during the first step of checkout.';
        $label_help_text['Tickets Left']         = 'Appears on event listings and inside the tickets selection pop-up.';
        return $label_help_text;
    }

    
    /**
     * EventPrime general settings sub tabs
     */
    public function ep_get_general_settings_sub_tabs() {
        $sub_tabs = array();
        $sub_tabs['regular']  = esc_html__( 'Setup', 'eventprime-event-calendar-management' );
        //$sub_tabs['timezone'] = esc_html__( 'Timezone', 'eventprime-event-calendar-management' );
        $sub_tabs['external'] = esc_html__( 'Third-Party', 'eventprime-event-calendar-management' );
        $sub_tabs['seo']      = esc_html__( 'SEO', 'eventprime-event-calendar-management' );
        
        return apply_filters( 'ep_admin_general_settings_sub_tabs', $sub_tabs );
    }
    
    /**
     * General settings tabs content
     */
    public function ep_get_settings_general_tabs_content( $active_sub_tab ) {
        include plugin_dir_path( __DIR__ ) .'admin/partials/settings/settings-tab-'. $active_sub_tab .'.php';
    }
    public function ep_payment_gateways_order(){
        //$global_settings = EventM_Factory_Service::ep_get_instance( 'EventM_Admin_Model_Settings' );
        //$global_options = $global_settings->ep_get_settings();
        $global_options = new stdClass();
        $gateways = $this->ep_payments_gateways_list();
        $ordered_gateways = array();
        if( isset( $global_options->payment_order ) && ! empty( $global_options->payment_order ) ) {
            foreach( $global_options->payment_order as $payment_order ) {
                if( isset( $gateways[$payment_order] ) ) {
                    $ordered_gateways[$payment_order] = $gateways[$payment_order];
                }
            }
            foreach ( $gateways as $key => $method ) {
                if( ! isset( $ordered_gateways[$key] ) ) {
                    $ordered_gateways[$key] = $method;
                }
            }
            return $ordered_gateways;
        }
        else{
            return $gateways;
        }
    }
    
    //Register payment gateways
    public function ep_payments_gateways_list(){
        $gateways = array();
        $gateways['none'] = array(
            'method'       => esc_html__( 'None', 'eventprime-event-calendar-management' ),
            'description'  => '',
            'icon_url'     => '',
            'enable_key'   => '',
            'show_in_list' => 0
        );
        $gateways['paypal'] = array(
            'method'       => esc_html__( 'Paypal', 'eventprime-event-calendar-management' ),
            'description'  => esc_html__( 'Accept payments using PayPal checkout.', 'eventprime-event-calendar-management' ),
            'icon_url'     => esc_url( plugin_dir_url( __DIR__ ).'admin/partials/images/payment-paypal.png' ),
            'enable_key'   => 'paypal_processor',
            'show_in_list' => 1
        );
        return apply_filters('ep_payments_gateways_list_add',$gateways);
    }
    
    public function ep_payments_gateways_setting(){
        //$global_settings = EventM_Factory_Service::ep_get_instance( 'EventM_Admin_Model_Settings' );
        //$global_options = $global_settings->ep_get_settings();
        $gateway_settings = array();
        ob_start();
        include plugin_dir_path( __DIR__ ) .'admin/partials/settings/payments/payment-paypal.php';
        $gateway_settings_content = ob_get_clean();
        $gateway_settings['paypal'] = $gateway_settings_content;
        return apply_filters('ep_payments_gateways_setting_add', $gateway_settings);
    }
    public function ep_emailer_list(){
        $emailers = array();
        $emailers['registration'] = array(
            'title'=>__('Registration Email','eventprime-event-calendar-management'),
            'description'=>__('Sends confirmation to the users upon successful registration through EventPrime registration form.','eventprime-event-calendar-management'),
            'enable_key'=> '',
            'recipient'=>__('User','eventprime-event-calendar-management')
            );
        $emailers['reset_password'] = array(
            'title'=>__('Reset User Password','eventprime-event-calendar-management'),
            'description'=>__('Sends new password to the users on password reset request.','eventprime-event-calendar-management'),
            'enable_key'=> '',
            'recipient'=>__('User','eventprime-event-calendar-management')
        );
        $emailers['booking_pending'] = array(
            'title'=>__('Booking Pending Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the users when their booking is in pending state.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_booking_pending_email',
            'recipient'=>__('User','eventprime-event-calendar-management')
        );
        $emailers['booking_confirm'] = array(
            'title'=>__('Booking Confirmation Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the users when their booking is confirmed.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_booking_confirm_email',
            'recipient'=>__('User','eventprime-event-calendar-management')
        );
        $emailers['booking_canceled'] = array(
            'title'=>__('Booking Cancellation Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the users when their booking is cancelled.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_booking_cancellation_email',
            'recipient'=>__('User','eventprime-event-calendar-management')
        );
        $emailers['booking_refund'] = array(
            'title'=>__('Booking Refund Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the users when their booking is refunded.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_booking_refund_email',
            'recipient'=>__('User','eventprime-event-calendar-management')
        );
        $emailers['event_submitted'] = array(
            'title'=>__('Event Submitted Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the admin on successfully submitting an event from the frontend form on this website.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_event_submitted_email',
            'recipient'=>__('Admin','eventprime-event-calendar-management')
        );
        $emailers['event_approval'] = array(
            'title'=>__('Event Approval Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the users when their submitted event has been approved by the admin.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_event_approved_email',
            'recipient'=>__('User','eventprime-event-calendar-management')
        );
        $emailers['booking_confirmed_admin'] = array(
            'title'=>__('Admin Booking Confirmation Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the admin when a new booking is created by a user, for any event.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_admin_booking_confirm_email',
            'recipient'=>__('Admin','eventprime-event-calendar-management')
        );
        $emailers['booking_pending_admin'] = array(
            'title'=>__('Admin Booking Pending Email','eventprime-event-calendar-management'),
            'description'=>__('Informs the admin when a booking is pending, for any event.','eventprime-event-calendar-management'),
            'enable_key'=> 'send_booking_pending_admin_email',
            'recipient'=>__('Admin','eventprime-event-calendar-management')
        );
        return apply_filters('ep_emailer_list_add', $emailers);
    }
    public function ep_emailer_setting(){
        if( isset( $_GET['section'] ) && isset( $_GET['tab'] ) && 'emails' == sanitize_text_field( $_GET['tab'] ) ){
            $emailer_settings = array();
            $section = sanitize_text_field( $_GET['section'] );
            if( array_key_exists( $section, $this->ep_emailer_list() ) ) {
                $email_file_path = plugin_dir_path( __DIR__ ) .'admin/partials/settings/emailers/emailer-'.$section.'.php';
                if( file_exists( $email_file_path ) ) {
                    ob_start();
                    include plugin_dir_path( __DIR__ ) .'admin/partials/settings/emailers/emailer-'.$section.'.php';
                    $emailer_settings_content = ob_get_clean();
                    $emailer_settings[$section] = $emailer_settings_content;
                }
            }
            return apply_filters( 'ep_emailer_setting_add', $emailer_settings, $section );
        }
    }
    
   
    
    public function ep_get_gdpr_settings_content(){
        return '';
    }
    
    public function ep_get_front_view_settings_sub_tabs() {
        $sub_tabs = array();
        $sub_tabs['events']       = esc_html__( 'Event Listings', 'eventprime-event-calendar-management' );
        $sub_tabs['eventdetails'] = esc_html__( 'Event', 'eventprime-event-calendar-management' );
        $sub_tabs['eventtypes']   = esc_html__( 'Event-Types', 'eventprime-event-calendar-management' );
        $sub_tabs['performers']   = esc_html__( 'Performers', 'eventprime-event-calendar-management' );
        $sub_tabs['venues']       = esc_html__( 'Venues', 'eventprime-event-calendar-management' );
        $sub_tabs['organizers']   = esc_html__( 'Organizers', 'eventprime-event-calendar-management' );
        
        return apply_filters( 'ep_admin_front_view_settings_sub_tabs', $sub_tabs );
    }
    
    public function ep_get_front_views_content_settings()
    {
        $sub_options = $options = array();
        $basic_functions = new Eventprime_Basic_Functions();
        
        $core_tabs = array('events','eventdetails','eventtypes','performers','venues','organizers');
        $sub_options['front_view_list_styles'] = $basic_functions->ep_frontend_views_list_styles();
        $sub_options['front_view_event_styles'] = $basic_functions->ep_frontend_views_event_styles();
        $sub_options['default_cal_view'] = $basic_functions->get_event_views();
        $sub_options['events_per_page'] = array( 10, 20, 30, 50, 'all', 'custom' );
        $sub_options['time_format'] = array( 'h:mmt' => '12-hour', 'HH:mm' => '24-hour' );
        $sub_options['calendar_title_format'] = array(
            'DD MMMM, YYYY' => gmdate("d F, Y"),
            'MMMM DD, YYYY' => gmdate("F d, Y"),
            'DD-MMMM-YYYY'  => gmdate("d-F-Y"),
            'MMMM-DD-YYYY'  => gmdate('F-d-Y'),
            'DD/MMMM/YYYY'  => gmdate('d/F/Y'),
            'MMMM/DD/YYYY'  => gmdate("F/d/Y"),
            'MMMM YYYY'     => gmdate("F Y"),
            'MMMM, YYYY'    => gmdate("F, Y"),
        );

        $sub_options['calendar_header_format'] = array(
            'ddd'     => gmdate("D"),
            'dddd'    => gmdate("l"),
            /* 'ddd D/M' => gmdate("D j/m"),
            'ddd M/D' => gmdate("D m/j"), */
        );
        $sub_options['datepicker_format'] = array(
            'dd-mm-yy&d-m-Y' => gmdate('d-m-Y') .' (d-m-Y)',
            'mm-dd-yy&m-d-Y' => gmdate('m-d-Y') .' (m-d-Y)',
            'yy-mm-dd&Y-m-d' => gmdate('Y-m-d') .' (Y-m-d)',
            'dd/mm/yy&d/m/Y' => gmdate('d/m/Y') .' (d/m/Y)',
            'mm/dd/yy&m/d/Y' => gmdate('m/d/Y') .' (m/d/Y)',
            'yy/mm/dd&Y/m/d' => gmdate('Y/m/d') .' (Y/m/d)',
            'dd.mm.yy&d.m.Y' => gmdate('d.m.Y') .' (d.m.Y)',
            'mm.dd.yy&m.d.Y' => gmdate('m.d.Y') .' (m.d.Y)',
            'yy.mm.dd&Y.m.d' => gmdate('Y.m.d') .' (Y.m.d)',
        );
        
        $sub_options['image_visibility_options'] = $basic_functions->get_image_visibility_options();
        $this->sub_options = array_merge( $this->sub_options, $sub_options );
    }
    
    public function ep_get_settings_front_views_content( $active_sub_tab ) {
        $sub_options = $options = array();
        $basic_functions = new Eventprime_Basic_Functions();
        
        $core_tabs = array('events','eventdetails','eventtypes','performers','venues','organizers');
        $sub_options['front_view_list_styles'] = $basic_functions->ep_frontend_views_list_styles();
        $sub_options['front_view_event_styles'] = $basic_functions->ep_frontend_views_event_styles();
        $sub_options['default_cal_view'] = $basic_functions->get_event_views();
        $sub_options['events_per_page'] = array( 10, 20, 30, 50, 'all', 'custom' );
        $sub_options['time_format'] = array( 'h:mmt' => '12-hour', 'HH:mm' => '24-hour' );
        $sub_options['calendar_title_format'] = array(
            'DD MMMM, YYYY' => gmdate("d F, Y"),
            'MMMM DD, YYYY' => gmdate("F d, Y"),
            'DD-MMMM-YYYY'  => gmdate("d-F-Y"),
            'MMMM-DD-YYYY'  => gmdate('F-d-Y'),
            'DD/MMMM/YYYY'  => gmdate('d/F/Y'),
            'MMMM/DD/YYYY'  => gmdate("F/d/Y"),
            'MMMM YYYY'     => gmdate("F Y"),
            'MMMM, YYYY'    => gmdate("F, Y"),
        );

        $sub_options['calendar_header_format'] = array(
            'ddd'     => gmdate("D"),
            'dddd'    => gmdate("l"),
            /* 'ddd D/M' => gmdate("D j/m"),
            'ddd M/D' => gmdate("D m/j"), */
        );
        $sub_options['datepicker_format'] = array(
            'dd-mm-yy&d-m-Y' => gmdate('d-m-Y') .' (d-m-Y)',
            'mm-dd-yy&m-d-Y' => gmdate('m-d-Y') .' (m-d-Y)',
            'yy-mm-dd&Y-m-d' => gmdate('Y-m-d') .' (Y-m-d)',
            'dd/mm/yy&d/m/Y' => gmdate('d/m/Y') .' (d/m/Y)',
            'mm/dd/yy&m/d/Y' => gmdate('m/d/Y') .' (m/d/Y)',
            'yy/mm/dd&Y/m/d' => gmdate('Y/m/d') .' (Y/m/d)',
            'dd.mm.yy&d.m.Y' => gmdate('d.m.Y') .' (d.m.Y)',
            'mm.dd.yy&m.d.Y' => gmdate('m.d.Y') .' (m.d.Y)',
            'yy.mm.dd&Y.m.d' => gmdate('Y.m.d') .' (Y.m.d)',
        );
        
        $sub_options['image_visibility_options'] = $basic_functions->get_image_visibility_options();

        if(in_array($active_sub_tab, $core_tabs)){
            include plugin_dir_path( __DIR__ ) .'admin/partials/settings/settings-tab-'. $active_sub_tab .'.php';
        }else{
            do_action( 'ep_get_settings_sub_tab_content', $active_sub_tab, $sub_options );
        }
        
    }
    
    public function get_form_settings_html( $section ) {
        $basic_functions = new Eventprime_Basic_Functions();
        $options = array();
        //$global_settings = EventM_Factory_Service::ep_get_instance( 'EventM_Admin_Model_Settings' );
        $options['global'] = new stdClass();
        
        $options['fes_sections'] = $this->get_fes_section_lists();
        $options['fes_required'] = $this->get_fes_required_lists();
        if( empty( $options['global']->em_ues_confirm_message ) ) {
            $options['global']->em_ues_confirm_message = esc_html__( 'Thank you for submitting your event. We will review and publish it soon.', 'eventprime-event-calendar-management' );
        }
        if( empty( $options['global']->em_ues_login_message ) ) {
            $options['global']->em_ues_login_message = esc_html__( 'Please login to submit your event.', 'eventprime-event-calendar-management' );
        }
        if( empty( $options['global']->em_ues_restricted_submission_message ) ) {
            $options['global']->em_ues_restricted_submission_message = esc_html__( 'You are not authorised to access this page. Please contact with your administrator.', 'eventprime-event-calendar-management' );
        }
        $options['status_list'] = array(
            "publish" => esc_html__( 'Active','eventprime-event-calendar-management' ),
            "draft"   => esc_html__( 'Draft','eventprime-event-calendar-management' )
        );
        $registration_forms_list = array(
            'ep' => 'EventPrime',
            'rm' => 'RegistrationMagic',
            'wp' => 'WordPress Core',
        );
        $options['registration_forms_list'] = apply_filters( 'ep_settings_registration_forms_list', $registration_forms_list );
        $options['rm_forms'] = $basic_functions->ep_get_rm_forms();
        // define core forms
        $default_core_forms = array( 'fes', 'login', 'register', 'checkout_registration' );
        //$options['global']->frontend_submission_roles = $options['global']->frontend_submission_sections = $options['global']->frontend_submission_required = array();
        $manage_form_data = '';
        //ob_start();
        // if section is in the core form then include the file else call hook
        if( in_array( $section, $default_core_forms ) ) {
            include plugin_dir_path( __DIR__ ) .'admin/partials/settings/forms/form-'.$section.'.php';
        } else{
            do_action( 'ep_get_extended_form_settings_content', $section, $options );
        }
        //$manage_form_data = ob_get_clean();
        //echo $manage_form_data;
    }
    
    public function ep_settings_forms_list(){
        $forms = array();
        $forms['fes'] = array(
            'title'       => esc_html__( 'Frontend Event Submission', 'eventprime-event-calendar-management' ),
            'description' => esc_html__( 'Form used by your users to submit events on your website.','eventprime-event-calendar-management' ),
        );

        $forms['login'] = array(
            'title'       => esc_html__( 'Login Form', 'eventprime-event-calendar-management' ),
            'description' => esc_html__( 'EventPrime\'s in-built login form.','eventprime-event-calendar-management' ),
        );

        $forms['register'] = array(
            'title'       => esc_html__( 'Registration Form', 'eventprime-event-calendar-management' ),
            'description' => esc_html__( 'EventPrime\'s in-build registration form.','eventprime-event-calendar-management' ),
        );
        
        $forms['checkout_registration'] = array(
            'title'       => esc_html__( 'Checkout Registration Form', 'eventprime-event-calendar-management' ),
            'description' => esc_html__( 'A form that appears during checkout for guest users allowing them to register while booking their first event.','eventprime-event-calendar-management' ),
        );
        
        return apply_filters( 'ep_settings_forms_list_add', $forms );
    }
    public function ep_setting_extensions_list(){
        $extension_settings = array();
        return apply_filters( 'ep_extensions_settings', $extension_settings );
    }
    
    public function get_fes_section_lists() {
        $fesSections = array(
            //'fes_event_text_color'     => esc_html__( 'Event Text Color','eventprime-event-calendar-management' ),
            'fes_event_featured_image' => esc_html__( 'Event Featured Image','eventprime-event-calendar-management' ),
            'fes_event_booking'        => esc_html__( 'Event Booking','eventprime-event-calendar-management' ),
            'fes_event_link'           => esc_html__( 'Event Link','eventprime-event-calendar-management' ),
            'fes_event_type'           => esc_html__( 'Event-Type','eventprime-event-calendar-management' ),
            'fes_new_event_type'       => esc_html__( 'Add New Event-Type','eventprime-event-calendar-management' ),
            'fes_event_location'       => esc_html__( 'Venues','eventprime-event-calendar-management' ),
            'fes_new_event_location'   => esc_html__( 'Add New Venues','eventprime-event-calendar-management' ),
            'fes_event_performer'      => esc_html__( 'Event Performer','eventprime-event-calendar-management' ),
            'fes_new_event_performer'  => esc_html__( 'Add New Event Performer','eventprime-event-calendar-management' ),
            'fes_event_organizer'      => esc_html__( 'Event Organizer','eventprime-event-calendar-management' ),
            'fes_new_event_organizer'  => esc_html__( 'Add New Event Organizer','eventprime-event-calendar-management' ),
            //'fes_event_more_options'   => esc_html__( 'Event More Options','eventprime-event-calendar-management ')
        );
        return $fesSections;
    }

    /**
     * Frontend event submission required list
     */
    public function get_fes_required_lists() {
        $fesRequired = array(
            'fes_event_description' => esc_html__( 'Event Description','eventprime-event-calendar-management' ),
            //'fes_event_booking'     => esc_html__( 'Event Booking','eventprime-event-calendar-management' ),
            //'fes_booking_price'     => esc_html__( 'Event Booking Price','eventprime-event-calendar-management' ),
            //'fes_event_link'        => esc_html__( 'Event Link','eventprime-event-calendar-management' ),
            'fes_event_type'        => esc_html__( 'Event-Type','eventprime-event-calendar-management' ),
            'fes_event_location'    => esc_html__( 'Venues','eventprime-event-calendar-management' ),
            'fes_event_performer'   => esc_html__( 'Event Performer','eventprime-event-calendar-management' ),
            'fes_event_organizer'   => esc_html__( 'Event Organizer','eventprime-event-calendar-management ')
        );
        return $fesRequired;
    }
    
    public function filer_eventmanager_post($array) {
        $array['query_var'] = false;
        return $array;
    }
    
    public function ep_fields_list_for_email() {
        echo '<select name="ep_field_list" class="ep_field_list" onchange="ep_insert_field_in_email(this.value)">';
        echo '<option value="">' . esc_html__( 'Select A Field', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<optgroup label="' . esc_attr__( 'Organizer Fields', 'eventprime-event-calendar-management' ) . '" >';
        echo '<option value="{{organizer_name}}">' . esc_html__( 'Name', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_description}}">' . esc_html__( 'Description', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_url}}">' . esc_html__( 'URL', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_image}}">' . esc_html__( 'Image', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_phone}}">' . esc_html__( 'phones', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_email}}">' . esc_html__( 'emails', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_websites}}">' . esc_html__( 'Websites', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{organizer_social_links}}">' . esc_html__( 'social Links', 'eventprime-event-calendar-management' ) . '</option>';
        echo '</optgroup>';
        echo '<optgroup label="' . esc_attr__( 'Event Type Fields', 'eventprime-event-calendar-management' ) . '" >';
        echo '<option value="{{event_type_name}}">' . esc_html__( 'Name', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{event_type_description}}">' . esc_html__( 'Description', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{event_type_url}}">' . esc_html__( 'URL', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{event_type_image}}">' . esc_html__( 'Image', 'eventprime-event-calendar-management' ) . '</option>';
        echo '</optgroup>';
        echo '<optgroup label="' . esc_attr__( 'Venue Fields', 'eventprime-event-calendar-management' ) . '" >';
        echo '<option value="{{venue_name}}">' . esc_html__( 'Name', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{venue_description}}">' . esc_html__( 'Description', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{venue_url}}">' . esc_html__( 'URL', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{venue_image}}">' . esc_html__( 'Image', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{venue_address}}">' . esc_html__( 'Address', 'eventprime-event-calendar-management' ) . '</option>';
        echo '</optgroup>';
         echo '<optgroup label="' . esc_attr__( 'Event Fields', 'eventprime-event-calendar-management' ) . '" >';
        echo '<option value="{{name}}">' . esc_html__( 'Name', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{event_url}}">' . esc_html__( 'URL', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{em_start_date_formated}}">' . esc_html__( 'Event Start Date', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{em_end_date_formated}}">' . esc_html__( 'Event End Date', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{em_start_time_formated}}">' . esc_html__( 'Event Start Time', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{em_end_time_formated}}">' . esc_html__( 'Event End Time', 'eventprime-event-calendar-management' ) . '</option>';
        
        echo '</optgroup>';
        
        do_action( 'ep_extend_emailer_fields_list' );

        echo '</select>';
    }
    
    public function ep_fields_list_for_data_deletion_email()
    {
        echo '<select name="ep_field_list" class="ep_field_list" onchange="ep_insert_field_in_email(this.value)">';
        echo '<option value="">' . esc_html__( 'Select A Field', 'eventprime-event-calendar-management' ) . '</option>';
        
        echo '<option value="{{user_name}}">' . esc_html__( 'User Name', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{user_email}}">' . esc_html__( 'User Email', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{site_name}}">' . esc_html__( 'Site Name', 'eventprime-event-calendar-management' ) . '</option>';
        echo '<option value="{{site_url}}">' . esc_html__( 'Site URL', 'eventprime-event-calendar-management' ) . '</option>';
        
        echo '</select>';
    }
}