<?php
/**
 * Class for global settings
 */

defined( 'ABSPATH' ) || exit;

class EventPrime_Admin_settings{

    public function save_settings() {
         $admin_notices = new EventM_Admin_Notices;
        if ( !empty( $_POST['ep_global_settings_nonce'] ) && wp_verify_nonce(sanitize_text_field(wp_unslash( $_POST['ep_global_settings_nonce'] )), 'ep_save_global_settings' ) ) 
        {
            if( current_user_can( 'manage_options' ) ) {
                // setting type
                $ep_sanitizer = new EventPrime_sanitizer;
                $form_data = $ep_sanitizer->sanitize($_POST);
                if( isset( $form_data['em_setting_type'] ) && ! empty( $form_data['em_setting_type'] ) ) {
                    $setting_type = $form_data['em_setting_type'];
                    if( $setting_type == 'regular_settings' ) {
                        $this->save_regular_settings($form_data);
                    }else if($setting_type == 'timezone_settings'){
                        $this->save_timezone_settings($form_data);
                    }else if($setting_type == 'external_settings'){
                        $this->save_external_settings($form_data);
                    }else if($setting_type == 'seo_settings'){
                        $this->save_seo_settings($form_data);
                    }else if($setting_type == 'payment_settings'){
                        $this->save_payment_settings($form_data);
                    }else if ($setting_type == 'page_settings'){
                        $this->save_page_settings($form_data);
                    } elseif( $setting_type == 'customcss_settings' ) {
                        $this->save_custom_css_settings($form_data);
                    }else if ($setting_type == 'email_settings'){
                        $this->save_email_settings($form_data);
                    } elseif( $setting_type == 'front_events_settings' ){
                        $this->save_front_events_settings($form_data);
                    } elseif( $setting_type == 'event_type_settings' ){
                        $this->save_event_type_settings($form_data);
                    } elseif( $setting_type == 'performer_settings' ){
                        $this->save_event_performer_settings($form_data);
                    } elseif( $setting_type == 'organizer_settings' ){
                        $this->save_event_organizer_settings($form_data);
                    } elseif( $setting_type == 'venue_settings' ){
                        $this->save_event_venue_settings($form_data);
                    } elseif( $setting_type == 'login_form_settings' ){
                        $this->save_login_form_settings($form_data);
                    } elseif( $setting_type == 'register_form_settings' ){
                        $this->save_register_form_settings($form_data);
                    } elseif( $setting_type == 'front_event_submission_settings' ){
                        $this->save_frontend_sub_form_settings($form_data);
                    } elseif( $setting_type == 'button_labels_settings' ){
                        $this->save_button_labels_settings($form_data);
                    } elseif( $setting_type == 'checkout_registration_form_settings'){
                        $this->save_checkout_registration_form_settings($form_data);
                    } elseif( $setting_type == 'front_event_details_settings'){
                        $this->save_front_event_details_settings($form_data);
                    } elseif ( $setting_type == 'gdpr_settings' ) {
                        $this->save_gdpr_settings( $form_data );
                    }
                    

                    // hook for save global settings from extensions
                    do_action( 'ep_submit_global_setting' );
                }
            } else{
                $admin_notices->ep_add_notice( 'error', esc_html__('You don\'t have permission to update settings.', 'eventprime-event-calendar-management' ) );
                echo "<script type='text/javascript'>
                    window.location=document.location.href;
                    </script>";
            }
        }
        else
        {
            $admin_notices->ep_add_notice( 'error', esc_html__('Failed security check', 'eventprime-event-calendar-management' ) );
            $tab_param = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
            // Construct the redirect URL dynamically
            $redirect_url = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=' . $tab_param);
            // Redirect after saving
            $nonce = wp_create_nonce('ep_settings_tab');
            $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
            wp_redirect($redirect_url);
            exit;
        }
        
    }
    
    /**
     * Save GDPR settings
     *
     * @param array $form_data Sanitized POST data.
    */
    public function save_gdpr_settings( $form_data ) {

       $global_settings      = new Eventprime_Global_Settings;
       $admin_notices        = new EventM_Admin_Notices;
       $global_settings_data = $global_settings->ep_get_settings();
       $global_settings_data->enable_gdpr_tools          = isset( $form_data['enable_gdpr_tools'] )          ? 1 : 0;
       $global_settings_data->enable_gdpr_download       = isset( $form_data['enable_gdpr_download'] )       ? 1 : 0;
       $global_settings_data->enable_gdpr_delete         = isset( $form_data['enable_gdpr_delete'] )         ? 1 : 0;
       $global_settings_data->enable_gdpr_download_request      = isset( $form_data['enable_gdpr_download_request'] )       ? 1 : 0;
       $global_settings_data->enable_gdpr_delete_request         = isset( $form_data['enable_gdpr_delete_request'] )         ? 1 : 0;
       
       $global_settings_data->show_gdpr_consent_checkbox = isset( $form_data['show_gdpr_consent_checkbox'] ) ? 1 : 0;
       $global_settings_data->show_gdpr_badge            = isset( $form_data['show_gdpr_badge'] )            ? 1 : 0;
       $global_settings_data->gdpr_consent_text = ! empty( $form_data['gdpr_consent_text'] )
           ? sanitize_text_field( $form_data['gdpr_consent_text'] )
           : esc_html__( "I agree to the site's Privacy Policy.", 'eventprime-event-calendar-management' );

       $global_settings_data->gdpr_privacy_policy_url = ! empty( $form_data['gdpr_privacy_policy_url'] )
           ? esc_url_raw( $form_data['gdpr_privacy_policy_url'] )
           : esc_url_raw( function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : home_url( '/privacy-policy' ) );

       $global_settings_data->gdpr_retention_period = ( $form_data['gdpr_retention_period'] !== '' )
           ? absint( $form_data['gdpr_retention_period'] )
           : '';
       
       // Save Cookie Consent Settings
        $global_settings_data->enable_cookie_consent_banner = isset( $form_data['enable_cookie_consent_banner'] ) ? 1 : 0;
        $global_settings_data->cookie_consent_message = ! empty( $form_data['cookie_consent_message'] )
            ? sanitize_textarea_field( $form_data['cookie_consent_message'] )
            : esc_html__( 'We use cookies to ensure you get the best experience on our website.', 'eventprime-event-calendar-management' );

        $global_settings_data->cookie_consent_button_text = ! empty( $form_data['cookie_consent_button_text'] )
            ? sanitize_text_field( $form_data['cookie_consent_button_text'] )
            : esc_html__( 'Accept', 'eventprime-event-calendar-management' );


      

       /*--------------------------------------------------------------
       | Save + admin notice + redirect
       --------------------------------------------------------------*/
       $global_settings->ep_save_settings( $global_settings_data );
       
       $admin_notices->ep_add_notice(
           'success',
           esc_html__( 'GDPR settings saved successfully.', 'eventprime-event-calendar-management' )
       );
       
       do_action( 'ep_update_retention_cron_schedule' );

       $redirect_url = admin_url( 'edit.php?post_type=em_event&page=ep-settings&tab=gdpr' );
       $redirect_url = add_query_arg( array(
           'tab_nonce' => wp_create_nonce( 'ep_settings_tab' ),
       ), $redirect_url );

       wp_redirect( $redirect_url );
       exit;
   }


    /**
     * Save general settings - regular setting tab
     */
    public function save_regular_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->eventprime_theme     = isset( $form_data['eventprime_theme'] ) ? $form_data['eventprime_theme'] : 'default';
        $global_settings_data->time_format                    = ( ! empty( $form_data['time_format'] ) ? sanitize_text_field( $form_data['time_format'] ) : 'h:mmt' );
        $global_settings_data->required_booking_attendee_name = isset($form_data['required_booking_attendee_name']) ? (int) $form_data['required_booking_attendee_name'] : 0;
        $global_settings_data->hide_0_price_from_frontend     = isset($form_data['hide_0_price_from_frontend']) ? (int) $form_data['hide_0_price_from_frontend'] : 0;
        $global_settings_data->datepicker_format              = sanitize_text_field($form_data['datepicker_format']);
        $global_settings_data->show_qr_code_on_ticket         = isset($form_data['show_qr_code_on_ticket']) ? (int) $form_data['show_qr_code_on_ticket'] : 0;
        $global_settings_data->checkout_page_timer            = isset( $form_data['checkout_page_timer'] ) ? absint( $form_data['checkout_page_timer'] ) : 4;
        $global_settings_data->ep_frontend_font_size          = isset( $form_data['ep_frontend_font_size'] ) ? absint( $form_data['ep_frontend_font_size'] ) : 14;
        $global_settings_data->hide_wishlist_icon             = isset( $form_data['hide_wishlist_icon'] ) ? absint( $form_data['hide_wishlist_icon'] ) : 0;
        $global_settings_data->enable_dark_mode               = isset( $form_data['enable_dark_mode'] ) ? absint( $form_data['enable_dark_mode'] ) : 0;
        $global_settings->ep_save_settings( $global_settings_data );

        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=general" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();   
    }
    
    /*
     * Save general settings - timezone setting tab
     */
    
    public function save_timezone_settings($form_data)
    {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->enable_event_time_to_user_timezone  = isset( $form_data['enable_event_time_to_user_timezone'] ) ? absint( $form_data['enable_event_time_to_user_timezone'] ) : 0;
        $global_settings_data->show_timezone_message_on_event_page = isset( $form_data['show_timezone_message_on_event_page'] ) ? absint( $form_data['show_timezone_message_on_event_page'] ) : 0;
        $global_settings_data->timezone_related_message            = isset( $form_data['timezone_related_message'] ) ? $form_data['timezone_related_message'] : '';
        $global_settings->ep_save_settings( $global_settings_data );

        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=general&sub_tab=timezone" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();  
    }
     
    /**
     * Save general settings - external setting tab
     */
    public function save_external_settings($form_data) {
        $error = array();
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->gmap_api_key                = sanitize_text_field($form_data['gmap_api_key']);
        $global_settings_data->weather_api_key                = sanitize_text_field($form_data['weather_api_key']);
        $global_settings_data->social_sharing              = isset($form_data['social_sharing']) ? (int) $form_data['social_sharing'] : 0;
        $global_settings_data->gcal_sharing                = isset($form_data['gcal_sharing']) ? (int) $form_data['gcal_sharing'] : 0;
        $global_settings_data->google_cal_client_id        = sanitize_text_field($form_data['google_cal_client_id']);
        $global_settings_data->google_cal_api_key          = sanitize_text_field($form_data['google_cal_api_key']);
        $global_settings_data->google_recaptcha            = isset($form_data['google_recaptcha']) && !empty($form_data['google_recaptcha']) ? 1 : 0;
        $global_settings_data->google_recaptcha_site_key   = sanitize_text_field(trim($form_data['google_recaptcha_site_key']));
        $global_settings_data->google_recaptcha_secret_key = sanitize_text_field(trim($form_data['google_recaptcha_secret_key']));
        if($global_settings_data->google_recaptcha==1)
        {
            if(empty($global_settings_data->google_recaptcha_site_key))
            {
                $error[] = esc_html__( 'reCAPTCHA site Key is required field.', 'eventprime-event-calendar-management' );
                
            }
            if(empty($global_settings_data->google_recaptcha_secret_key))
            {
                $error[] = esc_html__( 'reCAPTCHA secret Key is required field.', 'eventprime-event-calendar-management' );
                
            }
            
        }
        if(!empty($error))
        {
            $all_error = implode(' ', $error);
            $admin_notices->ep_add_notice( 'error',$all_error);
            $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=general&sub_tab=external" );
            $nonce = wp_create_nonce('ep_settings_tab');
            $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
            wp_redirect( $redirect_url );
            exit();
        }
        else
        {
            $global_settings->ep_save_settings( $global_settings_data );
            $admin_notices->ep_add_notice( 'success', esc_html__( 'Setting saved successfully', 'eventprime-event-calendar-management' ) );
            $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=general&sub_tab=external" );
            $nonce = wp_create_nonce('ep_settings_tab');
            $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
            wp_redirect( $redirect_url );
            exit();
        }
    }

    /**
     * Save general settings - seo setting tab
     */
    public function save_seo_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $seo_settings                          = new stdClass();
        $seo_settings->event_page_type_url     = ( ! empty( $form_data['event_page_type_url'] ) ) ? sanitize_text_field( $form_data['event_page_type_url'] ) : '';
        $seo_settings->performer_page_type_url = ( ! empty( $form_data['performer_page_type_url'] ) ) ? sanitize_text_field( $form_data['performer_page_type_url'] ) : '';
        $seo_settings->organizer_page_type_url = ( ! empty( $form_data['organizer_page_type_url'] ) ) ? sanitize_text_field( $form_data['organizer_page_type_url'] ) : '';
        $seo_settings->venues_page_type_url    = ( ! empty( $form_data['venues_page_type_url'] ) ) ? sanitize_text_field( $form_data['venues_page_type_url'] ) : '';
        $seo_settings->types_page_type_url     = ( ! empty( $form_data['types_page_type_url'] ) ) ? sanitize_text_field( $form_data['types_page_type_url'] ) : '';
        $seo_settings->sponsor_page_type_url   = ( ! empty( $form_data['sponsor_page_type_url'] ) ) ? sanitize_text_field( $form_data['sponsor_page_type_url'] ) : '';
        //$ep_desk_normal_screen               = sanitize_text_field( $form_data['ep_desk_normal_screen'] );
        //$ep_desk_large_screen                = sanitize_text_field( $form_data['ep_desk_large_screen'] );
        
        $global_settings_data->enable_seo_urls = isset( $form_data['enable_seo_urls'] ) ? (int)$form_data['enable_seo_urls'] : 0;        
        $global_settings_data->seo_urls        = $seo_settings;
        //$global_settings_data->ep_desk_normal_screen = isset( $ep_desk_normal_screen ) ? $ep_desk_normal_screen : '';
        //$global_settings_data->ep_desk_large_screen = isset( $ep_desk_large_screen ) ? $ep_desk_large_screen : '';        

        $global_settings->ep_save_settings( $global_settings_data );
        $admin_notices->ep_add_notice( 'success', esc_html__( 'Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=general&sub_tab=seo" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }
    
    /**
     * Save Payment Setting tab
     */
    public function save_payment_settings($form_data){
        $payment_gateway = apply_filters('ep_payments_gateways_list', array());
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        if(isset($form_data) && isset($form_data['em_payment_type'])){
            if($form_data['em_payment_type'] == 'basic'){               
                foreach ($payment_gateway as $key => $method){
                    $enable_key = $method['enable_key'];
                    if(isset($form_data[$enable_key])){
                       $global_settings_data->$enable_key = 1;
                    }else{
                       $global_settings_data->$enable_key = 0;
                    }
                }
               //$global_settings_data->payment_order = $form_data['payment_order'];
               $global_settings_data->currency = sanitize_text_field($form_data['currency']);
               $global_settings_data->currency_position = sanitize_text_field($form_data['currency_position']);
            }
            if($form_data['em_payment_type'] == 'paypal'){
                $global_settings_data->paypal_processor = isset($form_data['paypal_processor']) ? (int) $form_data['paypal_processor'] : 0;
                $global_settings_data->paypal_client_id = sanitize_text_field($form_data['paypal_client_id']);
                
            }
            $global_settings->ep_save_settings( $global_settings_data );
            //update_option(EM_GLOBAL_SETTINGS, $global_settings_data, true);
        }
        do_action('ep_save_payments_setting', $form_data);
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        
        //also sent on _wp_http_referer
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=payments" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }
    
    public function save_page_settings($form_data){
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->performers_page = isset($form_data['performers_page']) ? sanitize_text_field($form_data['performers_page']) : 0;
        $global_settings_data->venues_page = isset($form_data['venues_page']) ? sanitize_text_field($form_data['venues_page']) : 0;
        $global_settings_data->events_page = isset($form_data['events_page']) ? sanitize_text_field($form_data['events_page']) : 0;
        $global_settings_data->booking_page = isset($form_data['booking_page']) ? sanitize_text_field($form_data['booking_page']) : 0;
        $global_settings_data->profile_page = isset($form_data['profile_page']) ? sanitize_text_field($form_data['profile_page']) : 0;
        $global_settings_data->event_types = isset($form_data['event_types']) ? sanitize_text_field($form_data['event_types']) : 0;
        $global_settings_data->event_submit_form = isset($form_data['event_submit_form']) ? sanitize_text_field($form_data['event_submit_form']) : 0;
        $global_settings_data->booking_details_page = isset($form_data['booking_details_page']) ? sanitize_text_field($form_data['booking_details_page']) : 0;
        $global_settings_data->event_organizers = isset($form_data['event_organizers']) ? sanitize_text_field($form_data['event_organizers']) : 0;
        $global_settings_data->login_page = isset($form_data['login_page']) ? sanitize_text_field($form_data['login_page']) : 0;
        $global_settings_data->register_page = isset($form_data['register_page']) ? sanitize_text_field($form_data['register_page']) : 0;
        
        $global_settings->ep_save_settings( $global_settings_data );
        do_action('ep_save_pages_setting', $form_data);
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=pages" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }
    
    public function save_email_settings($form_data){
        global $wpdb, $wp_roles;
        
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        if(isset($form_data) && isset($form_data['em_emailer_type'])){
            if($form_data['em_emailer_type'] == 'basic'){
                $global_settings_data->disable_admin_email = isset($form_data['disable_admin_email']) ? (int) $form_data['disable_admin_email'] : 0;
                $global_settings_data->disable_frontend_email = isset($form_data['disable_frontend_email']) ? (int) $form_data['disable_frontend_email'] : 0;
            }
            if($form_data['em_emailer_type'] == 'registration'){
                $global_settings_data->registration_email_subject = sanitize_text_field($form_data['registration_email_subject']);
                $global_settings_data->registration_email_content = wp_kses_post($form_data['registration_email_content']);
            }
            if($form_data['em_emailer_type'] == 'reset_password'){
                $global_settings_data->reset_password_mail_subject = sanitize_text_field($form_data['reset_password_mail_subject']);
                $global_settings_data->reset_password_mail = wp_kses_post($form_data['reset_password_mail']);
            }
            if($form_data['em_emailer_type'] == 'booking_pending'){
                $global_settings_data->send_booking_pending_email = isset($form_data['send_booking_pending_email']) ? (int) $form_data['send_booking_pending_email'] : 0;
                $global_settings_data->booking_pending_email_subject = sanitize_text_field($form_data['booking_pending_email_subject']);
                $global_settings_data->booking_pending_email = wp_kses_post($form_data['booking_pending_email']);
                $global_settings_data->booking_pending_email_cc = wp_kses_post($form_data['booking_pending_email_cc']);
            
            }
            if($form_data['em_emailer_type'] == 'booking_pending_admin'){
                $global_settings_data->send_booking_pending_admin_email = isset($form_data['send_booking_pending_admin_email']) ? (int) $form_data['send_booking_pending_admin_email'] : 0;
                $global_settings_data->booking_pending_admin_email_subject = sanitize_text_field($form_data['booking_pending_admin_email_subject']);
                $global_settings_data->booking_pending_admin_email = wp_kses_post($form_data['booking_pending_admin_email']);
                $global_settings_data->booking_pending_admin_email_cc = wp_kses_post($form_data['booking_pending_admin_email_cc']);
            }
            if($form_data['em_emailer_type'] == 'booking_confirm'){
                $global_settings_data->send_booking_confirm_email = isset($form_data['send_booking_confirm_email']) ? (int) $form_data['send_booking_confirm_email'] : 0;
                $global_settings_data->booking_confirm_email_subject = sanitize_text_field($form_data['booking_confirm_email_subject']);
                $global_settings_data->booking_confirmed_email = wp_kses_post($form_data['booking_confirmed_email']);
                //->booking_confirmed_email_cc = wp_kses_post($form_data['booking_confirmed_email_cc']);
            
            }
            if($form_data['em_emailer_type'] == 'booking_canceled'){
                $global_settings_data->send_booking_cancellation_email = isset($form_data['send_booking_cancellation_email']) ? (int) $form_data['send_booking_cancellation_email'] : 0;
                $global_settings_data->booking_cancelation_email_subject = sanitize_text_field($form_data['booking_cancelation_email_subject']);
                $global_settings_data->booking_cancelation_email = wp_kses_post($form_data['booking_cancelation_email']);
                $global_settings_data->booking_cancelation_email_cc = wp_kses_post($form_data['booking_cancelation_email_cc']);
            }
            if($form_data['em_emailer_type'] == 'booking_refund'){
                $global_settings_data->send_booking_refund_email = isset($form_data['send_booking_refund_email']) ? (int) $form_data['send_booking_refund_email'] : 0;
                $global_settings_data->booking_refund_email_subject = sanitize_text_field($form_data['booking_refund_email_subject']);
                $global_settings_data->booking_refund_email = wp_kses_post($form_data['booking_refund_email']);
                $global_settings_data->booking_refund_email_cc = wp_kses_post($form_data['booking_refund_email_cc']);
            }
            if($form_data['em_emailer_type'] == 'event_submitted'){
                $global_settings_data->send_event_submitted_email = isset($form_data['send_event_submitted_email']) ? (int) $form_data['send_event_submitted_email'] : 0;
                $global_settings_data->event_submitted_email_subject = sanitize_text_field($form_data['event_submitted_email_subject']);
                $global_settings_data->event_submitted_email = wp_kses_post($form_data['event_submitted_email']);
                $global_settings_data->event_submitted_email_cc = wp_kses_post($form_data['event_submitted_email_cc']);
            }
            if($form_data['em_emailer_type'] == 'event_approval'){
                $global_settings_data->send_event_approved_email = isset($form_data['send_event_approved_email']) ? (int) $form_data['send_event_approved_email'] : 0;
                $global_settings_data->event_approved_email_subject = sanitize_text_field($form_data['event_approved_email_subject']);
                $global_settings_data->event_approved_email = wp_kses_post($form_data['event_approved_email']);
            }
            if($form_data['em_emailer_type'] == 'booking_confirmed_admin'){
                $global_settings_data->send_admin_booking_confirm_email = isset($form_data['send_admin_booking_confirm_email']) ? (int) $form_data['send_admin_booking_confirm_email'] : 0;
                $global_settings_data->admin_booking_confirmed_email_subject = sanitize_text_field($form_data['admin_booking_confirmed_email_subject']);
                $global_settings_data->admin_booking_confirmed_email = wp_kses_post($form_data['admin_booking_confirmed_email']);
                $global_settings_data->admin_booking_confirmed_email_cc = wp_kses_post($form_data['admin_booking_confirmed_email_cc']);
                $global_settings_data->admin_booking_confirm_email_attendees = isset($form_data['admin_booking_confirm_email_attendees']) ? 1 : 0;
            }
            if( isset( $form_data['ep_admin_email_to'] ) && ! empty( $form_data['ep_admin_email_to'] ) ){
                $global_settings_data->ep_admin_email_to = sanitize_email( $form_data['ep_admin_email_to'] );
            }
            if( isset( $form_data['ep_admin_email_from'] ) && ! empty( $form_data['ep_admin_email_from'] ) ){
                $global_settings_data->ep_admin_email_from = sanitize_email( $form_data['ep_admin_email_from'] );
            }
        }
        $global_settings->ep_save_settings( $global_settings_data );
        //update_option(EM_GLOBAL_SETTINGS, $global_settings_data, true);
        
        do_action('ep_save_emailer_setting', $form_data);
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=emails" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }

    /**
     * Save custom css
     */
    public function save_custom_css_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->custom_css = isset( $form_data['custom_css']) ? sanitize_text_field( $form_data['custom_css'] ) : '';
        $global_settings->ep_save_settings( $global_settings_data );
        
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=customcss" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }
    
    /*
     * Save Events Settings
     */
    
    public function save_front_events_settings($form_data){
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->event_listings_date_format_std_option                = sanitize_text_field( $form_data['event_listings_date_format_std_option'] );
        if ( isset( $form_data['event_listings_date_format_std_option'] ) && !empty( $form_data['event_listings_date_format_std_option']  ) && ( $form_data['event_listings_date_format_std_option'] == 'custom' ) ) {
            $global_settings_data->event_listings_date_format_val      = ( isset($form_data['event_listings_date_format_custom']) && !empty($form_data['event_listings_date_format_custom']) ) ? sanitize_text_field( $form_data['event_listings_date_format_custom'] ) : 'F j, Y';
        } else {
            $global_settings_data->event_listings_date_format_val      = ( isset($form_data['event_listings_date_format_std_option']) ) ? sanitize_text_field( $form_data['event_listings_date_format_std_option'] ) : '';
        }
        $global_settings_data->default_cal_view                = sanitize_text_field( $form_data['default_cal_view'] );
        $global_settings_data->enable_default_calendar_date    = isset( $form_data['enable_default_calendar_date'] ) ? (int) $form_data['enable_default_calendar_date'] : 0;
        $global_settings_data->default_calendar_date           = isset( $form_data['default_calendar_date'] ) ? sanitize_text_field( $form_data['default_calendar_date'] ) : '';
        $global_settings_data->calendar_title_format           = sanitize_text_field( $form_data['calendar_title_format'] );
        $global_settings_data->hide_calendar_rows              = isset( $form_data['hide_calendar_rows'] ) ? (int) $form_data['hide_calendar_rows'] : 0;
        $global_settings_data->hide_time_on_front_calendar     = isset( $form_data['hide_time_on_front_calendar'] ) ? (int) $form_data['hide_time_on_front_calendar'] : 0;
        $global_settings_data->show_event_types_on_calendar     = isset( $form_data['show_event_types_on_calendar'] ) ? (int) $form_data['show_event_types_on_calendar'] : 0;
        $global_settings_data->front_switch_view_option        = $form_data['front_switch_view_option'];
        $global_settings_data->hide_past_events                = isset($form_data['hide_past_events']) ? (int) $form_data['hide_past_events'] : 0;
        $global_settings_data->show_no_of_events_card          = sanitize_text_field( $form_data['show_no_of_events_card'] );
        $global_settings_data->card_view_custom_value          = isset( $form_data['card_view_custom_value'] ) ? (int) $form_data['card_view_custom_value'] : 1;
        $global_settings_data->disable_filter_options          = isset( $form_data['disable_filter_options'] ) ? (int) $form_data['disable_filter_options'] : 0;
        $global_settings_data->hide_old_bookings               = isset( $form_data['hide_old_bookings'] ) ? (int) $form_data['hide_old_bookings'] : 0;
        $global_settings_data->calendar_column_header_format   = sanitize_text_field( $form_data['calendar_column_header_format'] );
        $global_settings_data->shortcode_hide_upcoming_events  = isset( $form_data['shortcode_hide_upcoming_events'] ) ? (int) $form_data['shortcode_hide_upcoming_events'] : 0;
        $global_settings_data->redirect_third_party            = isset( $form_data['redirect_third_party'] ) ? (int) $form_data['redirect_third_party'] : 0;
        $global_settings_data->hide_event_custom_link          = isset( $form_data['hide_event_custom_link'] ) ? (int) $form_data['hide_event_custom_link'] : 0;
        $global_settings_data->show_max_event_on_calendar_date = isset( $form_data['show_max_event_on_calendar_date'] ) ? (int) $form_data['show_max_event_on_calendar_date'] : 2;
        $global_settings_data->event_booking_status_option     = isset( $form_data['event_booking_status_option'] ) ? $form_data['event_booking_status_option'] : '';
        $global_settings_data->open_detail_page_in_new_tab     = isset( $form_data['open_detail_page_in_new_tab'] ) ? $form_data['open_detail_page_in_new_tab'] : 0;
        $global_settings_data->events_no_of_columns            = ( ! empty( $form_data['events_no_of_columns'] ) ) ? absint( $form_data['events_no_of_columns'] ) : '';
        $global_settings_data->events_image_visibility_options = ( ! empty( $form_data['events_image_visibility_options'] ) ) ? sanitize_text_field( $form_data['events_image_visibility_options'] ) : '';
        $global_settings_data->events_image_height             = ( ! empty( $form_data['events_image_height'] ) ) ? absint( $form_data['events_image_height'] ) : '';
        // trending event type settings
        $global_settings_data->show_trending_event_types       = isset( $form_data['show_trending_event_types'] ) ? (int) $form_data['show_trending_event_types'] : 0;
        $global_settings_data->no_of_event_types_displayed     = ( ! empty( $form_data['no_of_event_types_displayed'] ) ) ? (int) $form_data['no_of_event_types_displayed'] : 5;
        $global_settings_data->show_events_per_event_type      = isset( $form_data['show_events_per_event_type'] ) ? (int) $form_data['show_events_per_event_type'] : 0;
        $global_settings_data->sort_by_events_or_bookings      = isset( $form_data['sort_by_events_or_bookings'] ) ? $form_data['sort_by_events_or_bookings'] : '';

        $global_settings->ep_save_settings( $global_settings_data );
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=events" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();   
    }
    
    /**
     * Save Event Type settings
     */
    public function save_event_type_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->type_display_view              = isset( $form_data['type_display_view'] ) ? sanitize_text_field( $form_data['type_display_view'] ) : 'card';
        $global_settings_data->type_box_color                 = isset( $form_data['type_box_color'] ) ? array_map( 'sanitize_text_field', $form_data['type_box_color'] ) : '';
        $global_settings_data->type_limit                     = isset( $form_data['type_limit'] ) ? absint( $form_data['type_limit'] ) : 0;
        $global_settings_data->type_no_of_columns             = isset( $form_data['type_no_of_columns'] ) ? absint( $form_data['type_no_of_columns'] ) : 4;
        $global_settings_data->type_load_more                 = isset( $form_data['type_load_more'] ) ? 1 : 0;
        $global_settings_data->type_search                    = isset( $form_data['type_search'] ) ? 1 : 0;
        $global_settings_data->single_type_show_events        = isset( $form_data['single_type_show_events'] ) ? 1 : 0;
        $global_settings_data->single_type_event_display_view = isset( $form_data['single_type_event_display_view'] ) ? sanitize_text_field( $form_data['single_type_event_display_view'] ) : 'card';
        $global_settings_data->single_type_event_limit        = isset( $form_data['single_type_event_limit'] ) ? absint( $form_data['single_type_event_limit'] ) : 0;
        $global_settings_data->single_type_event_column       = isset( $form_data['single_type_event_column'] ) ? absint( $form_data['single_type_event_column'] ) : 4;
        $global_settings_data->single_type_event_load_more    = isset( $form_data['single_type_event_load_more'] ) ? 1 : 0;
        $global_settings_data->single_type_hide_past_events   = isset( $form_data['single_type_hide_past_events'] ) ? 1 : 0;
        $global_settings_data->single_type_event_order    = isset( $form_data['single_type_event_order'] ) ? sanitize_text_field( $form_data['single_type_event_order'] ) : 'asc';
        $global_settings_data->single_type_event_orderby   = isset( $form_data['single_type_event_orderby'] )? sanitize_text_field( $form_data['single_type_event_orderby'] ) : 'em_start_date_time';
        $global_settings_data->single_type_event_section_title   = isset( $form_data['single_type_event_section_title'] ) && !empty($form_data['single_type_event_section_title']) ? sanitize_text_field(wp_unslash($form_data['single_type_event_section_title'])) : esc_html__("Upcoming Events", "eventprime-event-calendar-management");
        $global_settings->ep_save_settings( $global_settings_data );
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=eventtypes" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }

    /**
     * Save Event Performer settings
     */
    public function save_event_performer_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->performer_display_view              = isset( $form_data['performer_display_view'] ) ? sanitize_text_field( $form_data['performer_display_view'] ) : 'card';
        $global_settings_data->performer_box_color                 = isset( $form_data['performer_box_color'] ) ? array_map( 'sanitize_text_field', $form_data['performer_box_color'] ) : '';
        $global_settings_data->performer_limit                     = isset( $form_data['performer_limit'] ) ? absint( $form_data['performer_limit'] ) : 0;
        $global_settings_data->performer_no_of_columns             = isset( $form_data['performer_no_of_columns'] ) ? absint( $form_data['performer_no_of_columns'] ) : 4;
        $global_settings_data->performer_load_more                 = isset( $form_data['performer_load_more'] ) ? 1 : 0;
        $global_settings_data->performer_search                    = isset( $form_data['performer_search'] ) ? 1 : 0;
        $global_settings_data->single_performer_show_events        = isset( $form_data['single_performer_show_events'] ) ? 1 : 0;
        $global_settings_data->single_performer_event_display_view = isset( $form_data['single_performer_event_display_view'] ) ? sanitize_text_field( $form_data['single_performer_event_display_view'] ) : 'card';
        $global_settings_data->single_performer_event_limit        = isset( $form_data['single_performer_event_limit'] ) ? absint( $form_data['single_performer_event_limit'] ) : 0;
        $global_settings_data->single_performer_event_column       = isset( $form_data['single_performer_event_column'] ) ? absint( $form_data['single_performer_event_column'] ) : 4;
        $global_settings_data->single_performer_event_load_more    = isset( $form_data['single_performer_event_load_more'] ) ? 1 : 0;
        $global_settings_data->single_performer_hide_past_events   = isset( $form_data['single_performer_hide_past_events'] ) ? 1 : 0;
        $global_settings_data->single_performer_event_section_title   = isset( $form_data['single_performer_event_section_title'] ) && !empty($form_data['single_performer_event_section_title']) ? sanitize_text_field(wp_unslash($form_data['single_performer_event_section_title'])) : esc_html__("Upcoming Events", "eventprime-event-calendar-management");
        $global_settings->ep_save_settings( $global_settings_data );
        
        do_action('ep_save_performer_setting', $form_data);
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=performers" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }
    
    /**
     * Save Event Organizer settings
     */
    public function save_event_organizer_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->organizer_display_view              = isset( $form_data['organizer_display_view'] ) ? sanitize_text_field( $form_data['organizer_display_view'] ) : 'card';
        $global_settings_data->organizer_box_color                 = isset( $form_data['organizer_box_color'] ) ? array_map( 'sanitize_text_field', $form_data['organizer_box_color'] ) : '';
        $global_settings_data->organizer_limit                     = isset( $form_data['organizer_limit'] ) ? absint( $form_data['organizer_limit'] ) : 0;
        $global_settings_data->organizer_no_of_columns             = isset( $form_data['organizer_no_of_columns'] ) ? absint( $form_data['organizer_no_of_columns'] ) : 4;
        $global_settings_data->organizer_load_more                 = isset( $form_data['organizer_load_more'] ) ? 1 : 0;
        $global_settings_data->organizer_search                    = isset( $form_data['organizer_search'] ) ? 1 : 0;
        $global_settings_data->single_organizer_show_events        = isset( $form_data['single_organizer_show_events'] ) ? 1 : 0;
        $global_settings_data->single_organizer_event_display_view = isset( $form_data['single_organizer_event_display_view'] ) ? sanitize_text_field( $form_data['single_organizer_event_display_view'] ) : 'card';
        $global_settings_data->single_organizer_event_limit        = isset( $form_data['single_organizer_event_limit'] ) ? absint( $form_data['single_organizer_event_limit'] ) : 0;
        $global_settings_data->single_organizer_event_column       = isset( $form_data['single_organizer_event_column'] ) ? absint( $form_data['single_organizer_event_column'] ) : 4;
        $global_settings_data->single_organizer_event_load_more    = isset( $form_data['single_organizer_event_load_more'] ) ? 1 : 0;
        $global_settings_data->single_organizer_hide_past_events   = isset( $form_data['single_organizer_hide_past_events'] ) ? 1 : 0;
        $global_settings_data->single_organizer_event_section_title   = isset( $form_data['single_organizer_event_section_title'] ) && !empty($form_data['single_organizer_event_section_title']) ? sanitize_text_field(wp_unslash($form_data['single_organizer_event_section_title'])) : esc_html__("Upcoming Events", "eventprime-event-calendar-management");
        $global_settings->ep_save_settings( $global_settings_data );
        do_action('ep_save_organizer_setting', $form_data);
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=organizers" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }
    
    /**
     * Save Event Venue settings
     */
    public function save_event_venue_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->venue_display_view              = isset( $form_data['venue_display_view'] ) ? sanitize_text_field( $form_data['venue_display_view'] ) : 'card';
        $global_settings_data->venue_box_color                 = isset( $form_data['venue_box_color'] ) ? array_map( 'sanitize_text_field', $form_data['venue_box_color'] ) : '';
        $global_settings_data->venue_limit                     = isset( $form_data['venue_limit'] ) ? absint( $form_data['venue_limit'] ) : 0;
        $global_settings_data->venue_no_of_columns             = isset( $form_data['venue_no_of_columns'] ) ? absint( $form_data['venue_no_of_columns'] ) : 4;
        $global_settings_data->venue_load_more                 = isset( $form_data['venue_load_more'] ) ? 1 : 0;
        $global_settings_data->venue_search                    = isset( $form_data['venue_search'] ) ? 1 : 0;
        $global_settings_data->single_venue_show_events        = isset( $form_data['single_venue_show_events'] ) ? 1 : 0;
        $global_settings_data->single_venue_event_display_view = isset( $form_data['single_venue_event_display_view'] ) ? sanitize_text_field( $form_data['single_venue_event_display_view'] ) : 'card';
        $global_settings_data->single_venue_event_limit        = isset( $form_data['single_venue_event_limit'] ) ? absint( $form_data['single_venue_event_limit'] ) : 0;
        $global_settings_data->single_venue_event_column       = isset( $form_data['single_venue_event_column'] ) ? absint( $form_data['single_venue_event_column'] ) : 4;
        $global_settings_data->single_venue_event_load_more    = isset( $form_data['single_venue_event_load_more'] ) ? 1 : 0;
        $global_settings_data->single_venue_hide_past_events   = isset( $form_data['single_venue_hide_past_events'] ) ? 1 : 0;
        $global_settings_data->single_venue_event_section_title   = isset( $form_data['single_venue_event_section_title'] ) && !empty($form_data['single_venue_event_section_title']) ? sanitize_text_field(wp_unslash($form_data['single_venue_event_section_title'])) : esc_html__("Upcoming Events", "eventprime-event-calendar-management");
        $global_settings->ep_save_settings( $global_settings_data );
        do_action('ep_save_venue_setting', $form_data);
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=venues" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }

    /**
     * Save Login form settings
     */
    public function save_login_form_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->login_id_field                  = isset( $form_data['login_id_field'] ) ? sanitize_text_field( $form_data['login_id_field'] ) : 'username';
        $global_settings_data->login_id_field_label_setting    = isset( $form_data['login_id_field_label_setting'] ) ? sanitize_text_field( $form_data['login_id_field_label_setting'] ) : '';
        $global_settings_data->login_show_rememberme           = isset( $form_data['login_show_rememberme'] ) ? 1 : 0;
        $global_settings_data->login_show_rememberme_label     = isset( $form_data['login_show_rememberme_label'] ) ? sanitize_text_field( $form_data['login_show_rememberme_label'] ) : '';
        $global_settings_data->login_show_forgotpassword       = isset( $form_data['login_show_forgotpassword'] ) ? 1 : 0;
        $global_settings_data->login_show_forgotpassword_label = isset( $form_data['login_show_forgotpassword_label'] ) ? sanitize_text_field( $form_data['login_show_forgotpassword_label'] ) : '';
        $global_settings_data->login_google_recaptcha       = isset( $form_data['login_google_recaptcha'] ) ? 1 : 0;
        //$global_settings_data->login_google_recaptcha_label = isset( $form_data['login_google_recaptcha_label'] ) ? sanitize_text_field( $form_data['login_google_recaptcha_label'] ) : '';
        
        $global_settings_data->login_password_label            = isset( $form_data['login_password_label'] ) ? sanitize_text_field( $form_data['login_password_label'] ) : '';
        $global_settings_data->login_heading_text              = isset( $form_data['login_heading_text'] ) ? sanitize_text_field( $form_data['login_heading_text'] ) : '';
        $global_settings_data->login_subheading_text           = isset( $form_data['login_subheading_text'] ) ? sanitize_text_field( $form_data['login_subheading_text'] ) : '';
        $global_settings_data->login_button_label              = isset( $form_data['login_button_label'] ) ? sanitize_text_field( $form_data['login_button_label'] ) : '';
        $global_settings_data->login_redirect_after_login      = isset( $form_data['login_redirect_after_login'] ) ? sanitize_text_field( $form_data['login_redirect_after_login'] ) : '';
        $global_settings_data->login_show_registerlink       = isset( $form_data['login_show_registerlink'] ) ? 1 : 0;
        $global_settings_data->login_show_registerlink_label = isset( $form_data['login_show_registerlink_label'] ) ? sanitize_text_field( $form_data['login_show_registerlink_label'] ) : '';
        
        $global_settings->ep_save_settings( $global_settings_data );
        
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=forms&section=login" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }

    /**
     * Save Register form settings
     */
    public function save_register_form_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->login_registration_form       = isset( $form_data['login_registration_form'] ) ? sanitize_text_field( $form_data['login_registration_form'] ) : '';
        $global_settings_data->login_rm_registration_form    = isset( $form_data['login_rm_registration_form'] ) ? sanitize_text_field( $form_data['login_rm_registration_form'] ) : '';
        $global_settings_data->register_google_recaptcha     = isset( $form_data['register_google_recaptcha']) ? 1 : 0;
        $global_settings_data->register_username             = isset( $form_data['register_username'] ) ? $form_data['register_username'] : array();
        $global_settings_data->register_email                = isset( $form_data['register_email'] ) ? $form_data['register_email'] : array();
        $global_settings_data->register_password             = isset( $form_data['register_password'] ) ? $form_data['register_password'] : array();
        $global_settings_data->register_repeat_password      = isset( $form_data['register_repeat_password'] ) ? $form_data['register_repeat_password'] : array();
        $global_settings_data->register_dob                  = isset( $form_data['register_dob'] ) ? $form_data['register_dob'] : array();
        $global_settings_data->register_phone                = isset( $form_data['register_phone'] ) ? $form_data['register_phone'] : array();
        $global_settings_data->register_timezone             = isset( $form_data['register_timezone'] ) ? $form_data['register_timezone'] : array();
        
        $global_settings->ep_save_settings( $global_settings_data );
        
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=forms&section=register" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }

    /*
     * Save Checkout Registration Form
     */
    
    public function save_checkout_registration_form_settings($form_data){
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->checkout_register_fname             = isset( $form_data['checkout_register_fname'] ) ? $form_data['checkout_register_fname'] : array();
        $global_settings_data->checkout_register_lname             = isset( $form_data['checkout_register_lname'] ) ? $form_data['checkout_register_lname'] : array();
        $global_settings_data->checkout_register_username             = isset( $form_data['checkout_register_username'] ) ? $form_data['checkout_register_username'] : array();
        $global_settings_data->checkout_register_email                = isset( $form_data['checkout_register_email'] ) ? $form_data['checkout_register_email'] : array();
        $global_settings_data->checkout_register_password             = isset( $form_data['checkout_register_password'] ) ? $form_data['checkout_register_password'] : array();
        $global_settings_data->checkout_reg_google_recaptcha = isset($form_data['checkout_reg_google_recaptcha']) && !empty($form_data['checkout_reg_google_recaptcha']) ? 1 : 0;
        $global_settings->ep_save_settings( $global_settings_data );
        
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=forms&section=checkout_registration" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }
    
    /*
     * Save Frontend Submission Form
     */
    public function save_frontend_sub_form_settings($form_data){
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->ues_confirm_message                  = isset( $form_data['ues_confirm_message'] ) ? sanitize_text_field(wp_unslash($form_data['ues_confirm_message'])) : '';
        $global_settings_data->allow_submission_by_anonymous_user   = isset( $form_data['allow_submission_by_anonymous_user'] ) ? 1 : 0;
        $global_settings_data->ues_login_message                    = isset( $form_data['ues_login_message'] ) ? sanitize_text_field( wp_unslash( $form_data['ues_login_message'] )) : '';
        $global_settings_data->ues_default_status                   = isset( $form_data['ues_default_status'] ) ? sanitize_text_field( wp_unslash($form_data['ues_default_status'] )) : '';
        $global_settings_data->frontend_submission_roles            = isset( $form_data['frontend_submission_roles']) ? array_map('sanitize_text_field',  wp_unslash($form_data['frontend_submission_roles'])) : array();
        $global_settings_data->ues_restricted_submission_message    = isset( $form_data['ues_restricted_submission_message'] ) ? sanitize_text_field( wp_unslash($form_data['ues_restricted_submission_message'])) : '';
        $global_settings_data->frontend_submission_sections         = isset( $form_data['frontend_submission_sections'] ) ? array_map('sanitize_text_field',  wp_unslash($form_data['frontend_submission_sections'])) : array();
        $global_settings_data->frontend_submission_required         = isset( $form_data['frontend_submission_required'] ) ? array_map('sanitize_text_field',  wp_unslash($form_data['frontend_submission_required'])) : array();
        $global_settings_data->fes_allow_media_library              = isset( $form_data['fes_allow_media_library'] ) ? 1 : 0;
        $global_settings_data->fes_allow_user_to_delete_event       = isset( $form_data['fes_allow_user_to_delete_event'] ) ? 1 : 0;
        $global_settings_data->fes_show_add_event_in_profile        = isset( $form_data['fes_show_add_event_in_profile'] ) ? 1 : 0;
        
        $global_settings->ep_save_settings( $global_settings_data );
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=forms&section=fes" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect($redirect_url);
        exit();
    }

    /**
     * Save button labels settings
     */
    public function save_button_labels_settings($form_data) {
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $button_titles = array();
        if( isset( $form_data['button_titles'] ) && ! empty( $form_data['button_titles'] ) ) {
            foreach( $form_data['button_titles'] as $bt_key => $bt ) {
                $button_titles[$bt_key] = sanitize_text_field( $bt );
            }
        }
        $global_settings_data->button_titles = $button_titles;
        // save global settings
        $global_settings->ep_save_settings( $global_settings_data );
        // redirect and show message
        $admin_notices->ep_add_notice( 'success', esc_html__('Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=buttonlabels" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }
    
    /**
     * Get checkout fields data
     */
    public function ep_get_checkout_fields_data() {
        $dbhander = new EP_DBhandler;
        $get_field_data = $dbhander->get_all_result('CHECKOUT_FIELDS','*', 1,'results', 0,false,'id','DESC','','OBJECT_K');
        if(empty($get_field_data))
        {
            $get_field_data = array();
        }
        return $get_field_data;
    }
    
    public function save_front_event_details_settings($form_data){
        $global_settings = new Eventprime_Global_Settings;
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->hide_weather_tab = ( ! empty( $form_data['hide_weather_tab'] ) ? 1 : 0 );
        $global_settings_data->expand_venue_container = ( ! empty( $form_data['expand_venue_container'] ) ? 1 : 0 );
        if( empty( $global_settings_data->hide_weather_tab ) ) {
            $global_settings_data->weather_unit_fahrenheit  = ( ! empty( $form_data['weather_unit_fahrenheit'] ) ? 1 : 0 );
        } else{
            $global_settings_data->weather_unit_fahrenheit  = 0;
        }
        $global_settings_data->single_event_date_format_std_option                = sanitize_text_field( $form_data['single_event_date_format_std_option'] );
        if ( isset( $form_data['single_event_date_format_std_option'] ) && !empty( $form_data['single_event_date_format_std_option']  ) && ( $form_data['single_event_date_format_std_option'] == 'custom' ) ) {
            $global_settings_data->single_event_date_format_val      = ( isset($form_data['single_event_date_format_custom']) && !empty($form_data['single_event_date_format_custom']) ) ? sanitize_text_field( $form_data['single_event_date_format_custom'] ) : 'F j, Y';
        } else {
            $global_settings_data->single_event_date_format_val      = ( isset($form_data['single_event_date_format_std_option']) ) ? sanitize_text_field( $form_data['single_event_date_format_std_option'] ) : '';
        }
        $global_settings_data->hide_map_tab                       = ( ! empty( $form_data['hide_map_tab'] ) ? 1 : 0 );
        $global_settings_data->hide_other_event_tab               = ( ! empty( $form_data['hide_other_event_tab'] ) ? 1 : 0 );
        $global_settings_data->hide_age_group_section             = ( ! empty( $form_data['hide_age_group_section'] ) ? 1 : 0 );
        $global_settings_data->hide_note_section                  = ( ! empty( $form_data['hide_note_section'] ) ? 1 : 0 );
        $global_settings_data->show_qr_code_on_single_event       = ( ! empty( $form_data['show_qr_code_on_single_event'] ) ? 1 : 0 );
        $global_settings_data->hide_performers_section            = ( ! empty( $form_data['hide_performers_section'] ) ? 1 : 0 );
        $global_settings_data->hide_organizers_section            = ( ! empty( $form_data['hide_organizers_section'] ) ? 1 : 0 );
        $global_settings_data->show_print_icon                    = ( ! empty( $form_data['show_print_icon'] ) ? 1 : 0 );
        $global_settings_data->event_detail_image_width           = ( ! empty( $form_data['event_detail_image_width'] ) ? $form_data['event_detail_image_width'] : '' );
        $global_settings_data->event_detail_image_height          = ( ! empty( $form_data['event_detail_image_height'] ) ? $form_data['event_detail_image_height'] : 'auto' );
        $global_settings_data->event_detail_image_height_custom   = ( ! empty( $form_data['event_detail_image_height_custom'] ) ? $form_data['event_detail_image_height_custom'] : '' );
        $global_settings_data->event_detail_image_align           = ( ! empty( $form_data['event_detail_image_align'] ) ? $form_data['event_detail_image_align'] : '' );
        $global_settings_data->event_detail_image_auto_scroll     = ( ! empty( $form_data['event_detail_image_auto_scroll'] ) ? $form_data['event_detail_image_auto_scroll'] : 0 );
        $global_settings_data->event_detail_image_slider_duration = ( ! empty( $form_data['event_detail_image_slider_duration'] ) ? $form_data['event_detail_image_slider_duration'] : 4 );
        $global_settings_data->event_detail_message_for_recap     = ( ! empty( $form_data['event_detail_message_for_recap'] ) ) ? sanitize_text_field( $form_data['event_detail_message_for_recap'] ) : '';
        
        $global_settings->ep_save_settings( $global_settings_data );
        $admin_notices->ep_add_notice( 'success', esc_html__( 'Setting saved successfully', 'eventprime-event-calendar-management' ) );
        $redirect_url = admin_url( "edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=eventdetails" );
        $nonce = wp_create_nonce('ep_settings_tab');
        $redirect_url = add_query_arg( array('tab_nonce'=>$nonce ),$redirect_url);
        wp_redirect( $redirect_url );
        exit();
    }
}
