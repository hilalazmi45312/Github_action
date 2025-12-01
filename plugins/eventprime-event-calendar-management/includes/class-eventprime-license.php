<?php
/**
 * Class for license
 */

defined( 'ABSPATH' ) || exit;

class EventPrime_License {
    // activate license
    
    public function ep_activate_extension_license($license_key, $item_id, $url = 'https://theeventprime.com') {
       
        if(is_numeric($item_id))
        {
            $api_params = array(
                'edd_action' => 'activate_license',
                'license'    => $license_key,
                'item_id'    => $item_id,
                'url'        => home_url()
            );
        }
        else 
        {
            $api_params = array(
                'edd_action' => 'activate_license',
                'license'    => $license_key,
                'item_name'    => urlencode($item_id),
                'url'        => home_url()
            );
        }
        

        $response = wp_remote_post($url, array(
            'timeout' => 20,
            'body'    => $api_params
        ));

        if (is_wp_error($response)) {
            return array(
                'status'  => false,
                'type'    => 'server_error',
                'message' => sprintf(
                    __('License activation failed: %s', 'eventprime-event-calendar-management'),
                    $response->get_error_message()
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $license_data = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE || !is_object($license_data)) {
            return array(
                'status'  => false,
                'type'    => 'invalid_json',
                'message' => __('Invalid response from license server.', 'eventprime-event-calendar-management')
            );
        }

        if (!empty($license_data->success) && $license_data->license === 'valid') {
            return array(
                'status'  => true,
                'type'    => 'success',
                'message' => __('License activated successfully.', 'eventprime-event-calendar-management'),
                'data'    => $license_data
            );
        }

        $edd_errors = array(
            'missing'               => __('License key does not exist.', 'eventprime-event-calendar-management'),
            'missing_url'           => __('Site URL is missing from the request.', 'eventprime-event-calendar-management'),
            'license_not_activable' => __('This license cannot be activated directly.', 'eventprime-event-calendar-management'),
            'disabled'              => __('This license has been disabled by the vendor.', 'eventprime-event-calendar-management'),
            'no_activations_left'   => __('No activations left for this license.', 'eventprime-event-calendar-management'),
            'expired'               => !empty($license_data->expires)
                ? sprintf(__('License expired on %s.', 'eventprime-event-calendar-management'), esc_html($license_data->expires))
                : __('License has expired.', 'eventprime-event-calendar-management'),
            'key_mismatch'          => __('License key is not valid for this product.', 'eventprime-event-calendar-management'),
            'invalid_item_id'       => __('Invalid item ID supplied.', 'eventprime-event-calendar-management'),
            'item_name_mismatch'    => __('License key does not match the product.', 'eventprime-event-calendar-management'),
            'site_inactive'         => __('This site is not active for this license.', 'eventprime-event-calendar-management'),
            'invalid'               => __('Invalid license key.', 'eventprime-event-calendar-management'),
            'valid'                 => __('License is already activated.', 'eventprime-event-calendar-management')
        );

        $error_code = $license_data->error ?? 'unknown_error';
        $message    = $edd_errors[$error_code] ?? sprintf(__('Unknown error: %s', 'eventprime-event-calendar-management'), esc_html($error_code));

        return array(
            'status'  => false,
            'type'    => $error_code,
            'message' => $message,
            'data'    => $license_data
        );
    }

    public function ep_deactivate_extension_license($license_key, $item_id, $url = 'https://theeventprime.com') {
       
        if(is_numeric($item_id))
        {
            $api_params = array(
                'edd_action' => 'deactivate_license',
                'license'    => $license_key,
                'item_id'    => $item_id,
                'url'        => home_url()
            );
        }
        else 
        {
            $api_params = array(
                'edd_action' => 'deactivate_license',
                'license'    => $license_key,
                'item_name'    => urlencode($item_id),
                'url'        => home_url()
            );
        }
        

        $response = wp_remote_post($url, array(
            'timeout' => 20,
            'body'    => $api_params
        ));

        if (is_wp_error($response)) {
            return array(
                'status'  => false,
                'type'    => 'server_error',
                'message' => sprintf(
                    __('License deactivation failed: %s', 'eventprime-event-calendar-management'),
                    $response->get_error_message()
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $license_data = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE || !is_object($license_data)) {
            return array(
                'status'  => false,
                'type'    => 'invalid_json',
                'message' => __('Invalid response from license server.', 'eventprime-event-calendar-management')
            );
        }

        if (!empty($license_data->success)) {
            return array(
                'status'  => true,
                'type'    => 'success',
                'message' => __('License deactivated successfully.', 'eventprime-event-calendar-management'),
                'data'    => $license_data
            );
        }

        $edd_errors = array(
            'missing'               => __('License key does not exist.', 'eventprime-event-calendar-management'),
            'missing_url'           => __('Site URL is missing from the request.', 'eventprime-event-calendar-management'),
            'license_not_activable' => __('This license cannot be activated directly.', 'eventprime-event-calendar-management'),
            'disabled'              => __('This license has been disabled by the vendor.', 'eventprime-event-calendar-management'),
            'no_activations_left'   => __('No activations left for this license.', 'eventprime-event-calendar-management'),
            'expired'               => !empty($license_data->expires)
                ? sprintf(__('License expired on %s.', 'eventprime-event-calendar-management'), esc_html($license_data->expires))
                : __('License has expired.', 'eventprime-event-calendar-management'),
            'key_mismatch'          => __('License key is not valid for this product.', 'eventprime-event-calendar-management'),
            'invalid_item_id'       => __('Invalid item ID supplied.', 'eventprime-event-calendar-management'),
            'item_name_mismatch'    => __('License key does not match the product.', 'eventprime-event-calendar-management'),
            'site_inactive'         => __('This site is not active for this license.', 'eventprime-event-calendar-management'),
            'invalid'               => __('Invalid license key.', 'eventprime-event-calendar-management'),
            'valid'                 => __('License is already activated.', 'eventprime-event-calendar-management')
        );

        $error_code = $license_data->error ?? 'unknown_error';
        $message    = $edd_errors[$error_code] ?? sprintf(__('License deactivated successfully.', 'eventprime-event-calendar-management'), esc_html($error_code));

        return array(
            'status'  => false,
            'type'    => $error_code,
            'message' => $message,
            'data'    => $license_data
        );
    }

    
    public function ep_activate_license($license,$item_id,$prefix)
    {
        $return = array();
        $error_status = '';
        $ep_store_url = "https://theeventprime.com/";
        $home_url = home_url();
        // data to send in our API request
           $api_params = array(
               'edd_action' => 'activate_license',
               'license'    => $license,
               'item_id'    => $item_id,
               'url'        => $home_url
           );

           // Call the custom API.
           $response = wp_remote_post( $ep_store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
           
            // make sure the response came back okay
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                $message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : esc_html__( 'An error occurred, please try again.' );
            } else {
                $license_data = json_decode( wp_remote_retrieve_body( $response ) );
                $error_status = (isset($license_data->error))?$license_data->error:'';
                if ( false === $license_data->success ) {
                    if( isset( $license_data->error ) ){
                        switch( $license_data->error ) {
                            case 'expired' :
                                $message = sprintf(
                                    esc_html__( 'Your license key expired on %s.', 'profilegrid-user-profiles-groups-and-communities' ),
                                    wp_date( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ,true) ) )
                                );
                                break;
                            case 'revoked' :
                                $message = esc_html__( 'Your license key has been disabled.' , 'eventprime-event-calendar-management' );
                                break;
                            case 'missing' :
                                $message = esc_html__( 'Your license key is invalid.' , 'eventprime-event-calendar-management' );
                                break;
                            case 'invalid' :
                            case 'site_inactive' :
                                $message = esc_html__( 'Your license is not active for this URL.' , 'eventprime-event-calendar-management' );
                                break;
                            case 'item_name_mismatch' :
                                $message = esc_html__( 'The key you have entered seems to be invalid. Please verify and try again.', 'eventprime-event-calendar-management'  );
                                break;
                            case 'no_activations_left':
                                $message = esc_html__( 'Your license key has reached its activation limit.', 'eventprime-event-calendar-management'  );
                                break;
                            default :
                                $message = esc_html__( 'The key you have entered seems to be invalid. Please verify and try again.', 'eventprime-event-calendar-management'  );
                                break;
                        }
                    }
                }
            }

            // Check if anything passed on a message constituting a failure
            if ( ! empty( $message ) ) {
            }
            
            if( !empty( $license_data ) ){
                // $license_data->license will be either "valid" or "invalid"
                $license_status  = ( isset( $license_data->license ) && ! empty( $license_data->license ) && $license_data->license == 'valid' ) ? $license_data->license : '';
                $license_response  = ( isset( $license_data ) && ! empty( $license_data ) ) ? $license_data : '';
                update_option( $prefix.'_license_status', $license_status );
                update_option( $prefix.'_license_response', $license_response );
                update_option( $prefix.'_item_id', $item_id );
            }
            
            if( isset( $license_data->expires ) && ! empty( $license_data->expires ) ) {
                if( $license_data->expires == 'lifetime' ){
                    $expire_date = esc_html__( 'Your license key is activated for lifetime', 'eventprime-event-calendar-management' );
                }else{
                    $expire_date = sprintf( esc_html__( 'Your license Key expires on %s.', 'eventprime-event-calendar-management' ), gmdate( 'F d, Y', strtotime($license_data->expires) ) );
                }
            }else{
                $expire_date = '';
            }   
            
            ob_start(); ?>
                <?php if( isset( $license_data->license ) && $license_data->license == 'valid' ){ ?>
                    <button type="button" class="button action pg-my-2 pg_license_deactivate" data-prefix="<?php echo esc_attr( $item_id ); ?>" name="<?php echo esc_attr( $prefix); ?>_license_deactivate" id="<?php echo esc_attr( $prefix ); ?>_license_deactivate" value="<?php esc_html_e( 'Deactivate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Deactivate License', 'eventprime-event-calendar-management' );?></button>
                <?php }elseif( isset( $license_data->license ) && $license_data->license == 'invalid' ){ ?>
                    <button type="button" class="button action pg-my-2 pg_license_activate" data-prefix="<?php echo esc_attr( $item_id ); ?>" name="<?php echo esc_attr( $prefix ); ?>_license_activate" id="<?php echo esc_attr( $prefix ); ?>_license_activate" value="<?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?></button>
                <?php }else{ ?>
                    <button type="button" class="button action pg-my-2 pg_license_activate" data-prefix="<?php echo esc_attr($item_id); ?>" name="<?php echo esc_attr( $prefix ); ?>_license_activate" id="<?php echo esc_attr( $prefix ); ?>_license_activate" value="<?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?></button>
                <?php } ?>      
            <?php
            $license_status_block = ob_get_clean();

            if ( empty( $message ) || $license_data->license == 'valid' ) {
                if( isset( $license_data->license ) && $license_data->license == 'valid' ){
                    $message = esc_html__( 'Your License key is activated.', 'eventprime-event-calendar-management'  );
                }
                if( isset( $license_data->license ) && $license_data->license == 'invalid' ){
                    $message = esc_html__( 'Your license key is invalid.', 'eventprime-event-calendar-management'  );
                }
                if( isset( $license_data->license ) && $license_data->license == 'deactivated' ){
                    $message = esc_html__( 'Your License key is deactivated.', 'eventprime-event-calendar-management'  );
                }
                if( isset( $license_data->license ) && $license_data->license == 'failed' ){
                    $message = esc_html__( 'Your License key deactivation failed. Please try after some time.', 'eventprime-event-calendar-management'  );
                }
            }

            $return = array( 'license_data' => $license_data, 'license_status_block' => $license_status_block, 'expire_date' => $expire_date, 'message' => $message );
        
            return $return;
           
    }
      // deactivate license
    public function ep_deactivate_license($license,$item_id,$prefix)
    {
        $return = array();
        $error_status = '';
        $ep_store_url = "https://theeventprime.com/";
        $home_url = home_url();
        // data to send in our API request
           $api_params = array(
               'edd_action' => 'deactivate_license',
               'license'    => $license,
               'item_id'    => $item_id,
               'url'        => $home_url
           );
        
         // Call the custom API.
            $response = wp_remote_post( $ep_store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
            
            // make sure the response came back okay
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                $message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : esc_html__( 'An error occurred, please try again.' );
            } else {
                $license_data = json_decode( wp_remote_retrieve_body( $response ) );
                $error_status = (isset($license_data->error))?$license_data->error:'';
                if ( false === $license_data->success ) {
                    if( isset( $license_data->error ) ){
                        switch( $license_data->error ) {
                            case 'expired' :
                                $message = sprintf(
                                    esc_html__( 'Your license key expired on %s.' ),
                                    wp_date( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp',true ) ) )
                                );
                                break;
                            case 'revoked' :
                                $message = esc_html__( 'Your license key has been disabled.', 'eventprime-event-calendar-management'   );
                                break;
                            case 'missing' :
                                $message = esc_html__( 'Your license key is invalid.', 'eventprime-event-calendar-management'   );
                                break;
                            case 'invalid' :
                            case 'site_inactive' :
                                $message = esc_html__( 'Your license is not active for this URL.', 'eventprime-event-calendar-management'   );
                                break;
                            case 'item_name_mismatch' :
                                $message = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'eventprime-event-calendar-management'   ), $item_name );
                                break;
                            case 'no_activations_left':
                                $message = esc_html__( 'Your license key has reached its activation limit.', 'eventprime-event-calendar-management'   );
                                break;
                            default :
                                $message = esc_html__( 'An error occurred, please try again.', 'eventprime-event-calendar-management'   );
                                break;
                        }
                    }
                }
            }

            // Check if anything passed on a message constituting a failure
            if ( ! empty( $message ) ) {

            }  
            
            if( !empty( $license_data ) ){
                // $license_data->license will be either "valid" or "invalid"
                $license_status  = ( isset( $license_data->license ) && ! empty( $license_data->license ) && $license_data->license == 'valid' ) ? $license_data->license : '';
                $license_response  = ( isset( $license_data ) && ! empty( $license_data ) ) ? $license_data : '';
                update_option( $prefix.'_license_status', $license_status );
                update_option( $prefix.'_license_response', $license_response );
                update_option( $prefix.'_item_id', $item_id );
            }
            
            if( isset( $license_data->expires ) && ! empty( $license_data->expires ) ) {
                if( $license_data->expires == 'lifetime' ){
                    $expire_date = esc_html__( 'Your license key is activated for lifetime', 'eventprime-event-calendar-management' );
                }else{
                    $expire_date = sprintf( esc_html__( 'Your License Key expires on %s.', 'eventprime-event-calendar-management' ), gmdate('F d, Y', strtotime( $license_data->expires ) ) );
                }
            }else{
                $expire_date = '';
            }           
            
            ob_start(); ?>
                <?php if( isset( $license_data->license ) && $license_data->license == 'valid' ){ ?>
                    <button type="button" class="button action ep-my-2 pg_license_deactivate" data-prefix="<?php echo esc_attr( $item_id ); ?>" name="<?php echo esc_attr( $prefix ); ?>_license_deactivate" id="<?php echo esc_attr( $prefix ); ?>_license_deactivate" value="<?php esc_html_e( 'Deactivate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Deactivate License', 'eventprime-event-calendar-management' );?></button>
                <?php }elseif( isset( $license_data->license ) && $license_data->license == 'invalid' ){ ?>
                    <button type="button" class="button action ep-my-2 pg_license_activate" data-prefix="<?php echo esc_attr($item_id ); ?>" name="<?php echo esc_attr( $prefix ); ?>_license_activate" id="<?php echo esc_attr($prefix); ?>_license_activate" value="<?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?></button>
                <?php }elseif( isset( $license_data->license ) && $license_data->license == 'failed' ){ ?>
                    <button type="button" class="button action ep-my-2 pg_license_activate" data-prefix="<?php echo esc_attr( $item_id); ?>" name="<?php echo esc_attr($prefix); ?>_license_activate" id="<?php echo esc_attr( $prefix); ?>_license_activate" value="<?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?></button>
                <?php }else{ ?>
                    <button type="button" class="button action ep-my-2 pg_license_activate" data-prefix="<?php echo esc_attr($item_id); ?>" name="<?php echo esc_attr($prefix); ?>_license_activate" id="<?php echo esc_attr($prefix); ?>_license_activate" value="<?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Activate License', 'eventprime-event-calendar-management' );?></button>
                <?php } ?>    
            <?php
            $license_status_block = ob_get_clean();

            if ( empty( $message ) || $license_data->license == 'valid' ) {
                if( isset( $license_data->license ) && $license_data->license == 'valid' ){
                    $message = esc_html__( 'Your License key is activated.', 'eventprime-event-calendar-management'  );
                }
                if( isset( $license_data->license ) && $license_data->license == 'invalid' ){
                    $message = esc_html__( 'Your license key is invalid.', 'eventprime-event-calendar-management'  );
                }
                if( isset( $license_data->license ) && $license_data->license == 'deactivated' ){
                    $message = esc_html__( 'Your License key is deactivated.', 'eventprime-event-calendar-management'  );
                }
                if( isset( $license_data->license ) && $license_data->license == 'failed' ){
                    $message = esc_html__( 'Your License key deactivation failed. Please try after some time.', 'eventprime-event-calendar-management'  );
                }
            }

            $return = array( 'license_data' => $license_data, 'license_status_block' => $license_status_block, 'expire_date' => $expire_date, 'message' => $message );
          

            return $return;
          
    }
    
    public function ep_get_all_extensions()
    {
        $extensions = array(
            'Eventprime_Event_Import_Export'=>array(849,'Events Import Export','free'),
            'Eventprime_Woocommerce_Integration'=>array(526,'WooCommerce Integration','free'),
            'Eventprime_Elementor_Integration'=>array(22432,'Elementor Integration','free'),
            'Eventprime_Attendees_List'=>array(966,'Attendees List','paid'),
            'Eventprime_Live_Seating'=>array(870,'Live Seating','paid'),
            'Eventprime_Event_Invoices'=>array(867,'Invoices','paid'),
            'Eventprime_Event_Coupons'=>array(846,'Coupon Codes','paid'),
            'Eventprime_Guest_Booking'=>array(864,'Guest Bookings','paid'),
            'Eventprime_Event_Sponsor'=>array(855,'Events Sponsors','paid'),
            'Eventprime_Admin_Attendee_Booking'=>array(858,'Admin Attendee Booking','paid'),
            'Eventprime_List_Widgets'=>array(852,'Events List Widgets','paid'),
            'Eventprime_Event_Tickets'=>array(861,'Events Tickets','paid'),
            'Eventprime_Advanced_Reports'=>array(21781,'Advanced Reports','paid'),
            'Eventprime_Advanced_Checkout_Fields'=>array(22434,'Advanced Checkout Fields','paid'),
            'Eventprime_Ratings_And_Reviews'=>array(25465,'Ratings and Reviews','paid'),
            'Eventprime_Event_Feedback'=>array(22845,'User Feedback','paid'),
            'Eventprime_RSVP'=>array(23282,'RSVP','paid'),
            'Eventprime_Twilio_Text_Notification'=>array(882,'Twilio Text Notifications','paid'),
            'Eventprime_Event_Mailpoet'=>array(873,'MailPoet Integration','paid'),
            'Eventprime_Zoom_Meetings'=>array(888,'Zoom Integration','paid'),
            'Eventprime_Zapier_Integration'=>array(885,'Zapier Integration','free'),
            'Eventprime_Mailchimp_Integration'=>array(22842,'Mailchimp Integration','paid'),
            'Eventprime_Event_Stripe'=>array(879,'Stripe Payment','paid'),
            'Eventprime_Offline'=>array(876,'Offline Payment','paid'),
            'Eventprime_Woocommerce_Checkout_Integration'=>array(23284,'WooCommerce Checkout','paid'),
            'Eventprime_Attendee_Event_Check_In'=>array(30503,'Attendee Event Check In','paid'),
            'EventPrime_Waiting_List'=>array(33631,'Waiting List','paid'),
            'Eventprime_Honeypot_Integration'=>array(35062,'HoneyPot Security','paid'),
            'Eventprime_Turnstile_Antispam'=>array(35065,'Turnstile Antispam Security','paid'),
            'Eventprime_Event_Reminder_Emails'=>array(35178,'Event Reminder Emails','paid'),
            'Square_Payment_Integration'=>array(40850,'Square Payments','paid'),
            'Eventprime_Hcaptcha_Integration'=>array(40856,'hCaptcha Security','paid'),
            'Eventprime_Demo_Data'=>array(35183,'Demo Data','free'),
        );
        return $extensions;
    }
    
    public function ep_get_extension_key_by_download_id($download_id) {
        $extensions = $this->ep_get_all_extensions(); // or wherever your array is defined

        foreach ($extensions as $key => $value) {
            if (isset($value[0]) && $value[0] == $download_id) {
                return $key;
            }
        }

        return false; // Not found
    }
    
     public function ep_get_activate_extensions() {
        
        $extensions = $this->ep_get_all_extensions();
        $activate = array();
                foreach ( $extensions as $key=>$value ) {
			if ( class_exists( $key ) ) {
                            $activate[$key] = $value;
			}
		}
		return $activate;
    }

    public function ep_get_premium_bundle_id($key='ep_free')
    {
        if(empty($key) || $key==null)
        {
            return '';
        }
        $premium = array('ep_free'=>23935,
            'ep_premium'=>19088,
            'ep_professional'=>23912,
            'ep_essential'=>23902,
            'ep_premium_plus'=>21789,
            'ep_metabundle'=>22462,
            'ep_metabundle_plus'=>21790
            );
        
        return $premium[$key];
        
    }
    
    public function ep_get_license_detail($key,$options)
    {
        
        $license_key =$key.'_license_key';
        $license_status = $key.'_license_status';
        $license_response = $key.'_license_response';
        $license_option_value = $key.'_license_option_value';
        $license_item_id = $key.'_item_id';
        $license = new stdClass();
        $license->license_key = get_option($license_key, (!empty($options->$license_key))?$options->$license_key:'');
        $license->license_status = get_option($license_status, (!empty($options->$license_status))?$options->$license_status:'');
        $license->license_response = get_option($license_response, (!empty($options->$license_response))?$options->$license_response:'');
        $license->license_option_value = get_option($license_option_value, (!empty($options->$license_option_value))?$options->$license_option_value:'');
        $license->item_id = get_option($license_item_id, (!empty($options->$license_item_id))?$options->$license_item_id:'');
        return $license;
        
    }
    
    public function ep_get_license_extension_html() {
    $ep_functions = new Eventprime_Basic_Functions;
    
    $ep_license_data = get_option('metagauss_license_data', []);
    $ext_list = $this->ep_get_all_extensions();

    $licensed_extensions = [];
    $other_extensions = [];

    // Categorize extensions
    foreach ($ext_list as $key => $ext) {
        $ext_details = $this->em_get_more_extension_data($ext, $key);
       
        $ext_details['title'] = $ext[1];
        $download_id = $ext[0];
        $ext_details['download_id'] = $download_id;
        $ext_details['meta_key'] = $key;

        $licensed = false;
        foreach ($ep_license_data as $license_key => $license_infos) {
            $license_info = $license_infos['plugins'];
            if (isset($license_info[$download_id])) {
                $ext_details['license_info'] = $license_info[$download_id];
                $licensed = true;
                break;
            }
        }

        if ($licensed) {
            $licensed_extensions[] = $ext_details;
        } else {
            $other_extensions[] = $ext_details;
        }
    }

    // Helper function to print extension cards
    $print_extension_card = function($ext_details,$type) {
        $global_settings = new Eventprime_Global_Settings;
        $options = $global_settings->ep_get_settings();

        
        $plugin_status   = $ext_details['is_activate'] ?? false;
        $plugin_installed = ($ext_details['button']=='Activate' || $ext_details['button']=='Setting' || $ext_details['button']=='') ?true: false;
        $is_licensed     = ! empty( $ext_details['license_info'] );
        $plugin_file     = $ext_details['plugin_file'] ?? '';
        $action_button   = '';
        
        $license_detail  = $this->ep_get_license_detail($ext_details['meta_key'], $options);
        
        
        //$action_button = '';

        if ( $plugin_installed && $plugin_status && $is_licensed ) {
            $license = $ext_details['license_info'];
            // Installed, activated, licensed – show Deactivate buttons
            $action_button = '<a class="button-secondary ep-deactivate-plugin" data-plugin="' . esc_attr( $plugin_file ) . '">' . esc_html__( 'Deactivate Plugin', 'eventprime-event-calendar-management' ) . '</a>';
            if(isset($license_detail) && isset($license_detail->license_response) && isset($license_detail->license_response->license))
            {
                if($license_detail->license_response->license=='valid')
                {
                    $action_button .='<a class="button-secondary ep-deactivate-license" data-license="' . esc_attr( $license['license_key'] ) . '"  data-itemid="' . esc_attr( $ext_details['download_id'] ) . '" data-key="' . esc_attr( $ext_details['meta_key'] ) . '">' . esc_html__( 'Deactivate License', 'eventprime-event-calendar-management' ) . '</a>';
                }
                else
                {
                    $action_button .='<a class="button-primary ep-activate-license" data-license="' . esc_attr( $license['license_key'] ) . '"  data-itemid="' . esc_attr( $ext_details['download_id'] ) . '" data-key="' . esc_attr( $ext_details['meta_key'] ) . '">' . esc_html__( 'Activate License', 'eventprime-event-calendar-management' ) . '</a>';
        
                }
            }
            else
            {
                $action_button .='<a class="button-primary ep-activate-license" data-license="' . esc_attr( $license['license_key'] ) . '"  data-itemid="' . esc_attr( $ext_details['download_id'] ) . '" data-key="' . esc_attr( $ext_details['meta_key'] ) . '">' . esc_html__( 'Activate License', 'eventprime-event-calendar-management' ) . '</a>';
        
            }
              
        } elseif ( $plugin_installed && ! $plugin_status ) {
            // Installed but not activated
            $action_button = '
                <a class="button-primary ep-activate-plugin" data-plugin="' . esc_attr( $plugin_file ) . '">' . esc_html__( 'Activate Plugin', 'eventprime-event-calendar-management' ) . '</a>';
        } elseif ( $is_licensed && ! $plugin_installed ) {
            // Licensed but not installed
            $license = $ext_details['license_info'];
            $action_button = '
                <a class="ep-install-extension button-primary" 
                   data-license="' . esc_attr( $license['license_key'] ) . '" 
                   data-itemid="' . esc_attr( $ext_details['download_id'] ) . '" 
                   data-key="' . esc_attr( $ext_details['meta_key'] ) . '" 
                   data-url="' . esc_url( $license['download_url'] ) . '">' . esc_html__( 'Install & Activate', 'eventprime-event-calendar-management' ) . '</a>';
        } else {
            // Fallback: Buy now or open link
            $action_button = '<a href="' . esc_url( $ext_details['url'] ) . '" class="button-primary" target="_blank">' . esc_html( $ext_details['button'] ) . '</a>';
        }


        ?>
        <div class="ep-box-col-4 ep-box-col-md-4 ep-box-col-sm-4 ep-ext-card <?php echo ($ext_details['is_free'] == 0) ? 'paid-extensions' : 'free-extensions'; ?>">
            <div class="ep-card ep-text-small ep-box-h-100">
                <div class="ep-card-body">
                    <div class="ep-license-card-box ep-d-flex ep-items-start ep-position-relative">
                        <div class="ep-ext-box-icon">
                            <?php if (!empty($ext_details['image'])): ?>
                            <img class="ep-ext-icon ep-img-fluid" alt="<?php echo esc_attr($ext_details['title']); ?>" src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'admin/partials/images/' . $ext_details['image']); ?>">
                            <?php endif; ?>
                        </div>
                        
                            <?php if ($ext_details['is_free'] != 0) : ?>
                                <span class="ep-free-tag">Free</span>
                            <?php endif; ?>

                        <div class="ep-license-card-box-details">
                            <div class="ep-card-title ep-d-flex ep-align-items-center ep-fs-7 ep-mb-2 ep-fw-bold">
                                <span class="ep-extension-title"><?php echo esc_html($ext_details['title']); ?></span><?php if(isset($ext_details['license_info']['version'])){ ?> <span class="ep-extension-version"><?php echo esc_html($ext_details['license_info']['version']); ?></span><?php } ?>
                            </div>
                           
                            <div class="ep-d-flex ep-ext-box-btn-wrap ep-license-card-box-btn-wrap ep-flex-direction-column">
                                    <?php 
                                    ?>
                                    <?php echo wp_kses_post($action_button); ?>
                                    <span class="ep-extension-action-result"></span>
                            </div>
                            
                             <div class="ep-ext-box-description">
                                <p class="ep-col-desc ep-text-muted ep-text-small"><?php 
                                if(!empty($ext_details['version']) && !empty($ext_details['license_info']['version']))
                                {
                                    if($ext_details['license_info']['version']>$ext_details['version'])
                                    {
                                        echo esc_html('A newer version is available for update.','eventprime-event-calendar-management');
                                    }
                                    else
                                    {
                                        echo esc_html('Your plugin is up to date','eventprime-event-calendar-management');
                                    }
                                }
                                ?>
                                </p>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    };

    // Output licensed extensions
    if (!empty($licensed_extensions)) {
        $type = 'licensed';
        echo '<div class="ep-license-box-wrap ep-bg-white ep-rounded ep-shadow ep-p-4 ep-mb-3 ep-licensed-ext-box">';
        echo '<h3 class="ep-mb-4">' . esc_html__('Licensed Extensions', 'eventprime-event-calendar-management') . '</h3>';
        echo '<div class="ep-box-row ep-g-3 ep-mt-2">';
        foreach ($licensed_extensions as $ext) {
            $print_extension_card($ext,$type);
        }
        echo '</div>';
        echo '</div>';
    }
    else
    {
        echo '<div class="ep-license-box-wrap ep-bg-white ep-rounded ep-shadow ep-p-4 ep-mb-3">';
        echo '<h3 class="ep-mb-4">' . esc_html__('Licensed Extensions', 'eventprime-event-calendar-management') . '</h3>';
        echo '<div class="ep-box-row ep-g-3 ep-mt-2">';
        echo '<p>'. __('Read about activating licenses here', 'eventprime-event-calendar-management').'</p>';
        echo '<p><strong>' . __('Note:', 'eventprime-event-calendar-management') . '</strong> ' . __('Extensions will appear here for download and activation once you add and activate your license key. ', 'eventprime-event-calendar-management');
        echo  __('You can download your purchased extensions from your Order History page.', 'eventprime-event-calendar-management').'</p>';
        echo '</div>';
        echo '</div>';
    }

    // Output purchasable extensions
    if (!empty($other_extensions)) {
        $type = 'unlicensed';
        echo '<div class="ep-license-box-wrap ep-bg-white ep-rounded ep-shadow ep-p-4">';
        echo '<h3 class="ep-mb-4">' . esc_html__('Extensions Available for Download', 'eventprime-event-calendar-management') . '</h3>';
        echo '<div class="ep-box-row ep-g-4 ep-unlicensed-ext-row ddd">';
        foreach ($other_extensions as $ext) {
            $print_extension_card($ext,$type);
        }
        echo '</div>';
        echo '</div>';
    }
}


public function ep_get_licenses_details() {
    $ep_license_data = get_option('metagauss_license_data', []);

    if (!empty($ep_license_data)) {
        foreach ($ep_license_data as $license_key => $license_entry) {
            if(empty($license_key))
            {
                continue;
            }
            if (!isset($license_entry['plugins']) || empty($license_entry['plugins'])) {
                continue;
            }

            $first_plugin = reset($license_entry['plugins']);

            // Default to original status
            $status = $first_plugin['status'];
            $status_class = 'ep-license-activated';
            $status_class2 = 'ep-status-active';

            // Override if expired
            if (!empty($first_plugin['expiration']) && is_numeric($first_plugin['expiration'])) {
                if ((int)$first_plugin['expiration'] < time()) {
                    $status = 'Expired';
                    $status_class = 'ep-license-expired';
                    $status_class2 = 'ep-status-expired';
                }
            }


            
            $status_class = ($first_plugin['status'] === 'active' || $first_plugin['status'] === 'inactive' ) ? 'ep-license-activated' : 'ep-license-expired';
            $status = ($first_plugin['status'] === 'active' || $first_plugin['status'] === 'inactive')?'Active':$first_plugin['status'];
            $masked_key = substr($license_key, 0, 5) . str_repeat('*', strlen($license_key) - 10) . substr($license_key, -5);
            //$masked_key = $license_key;
            $expiration = !empty($first_plugin['expire_date']) ? $first_plugin['expire_date'] : 'Lifetime';
            $type = $first_plugin['slug'];
            ?>
            <div class="ep-license-list">
                <div class="ep-license-item ep-shadow <?php echo esc_attr($status_class); ?>">
                    <div class="ep-license-item__details">
                        <div class="ep-license-details__fields">
                            <div class="ep-license-details__field ep-license-key">
                                <span class="ep-license--label">License Key:</span>
                                <span class="ep-license--value"><?php echo esc_html($masked_key); ?></span>
                            </div>
                            <div class="ep-license-details__field ep-license-name">
                                <span class="ep-license--label">Product Name:</span>
                                <span class="ep-license--value"><?php echo esc_html($first_plugin['name']); ?></span>
                            </div>
                            <div class="ep-license-details__field ep-license-status">
                                                <span class="ep-license--label">Status:</span>
                                                <span class="ep-license--value ep-status-badge 
                                                      <?php echo esc_attr($status_class2); ?>">

                                                    <?php if (strtolower($status) === 'active') : ?>
                                                        <!-- Active Icon -->
                                                        <svg class="ep-status-icon" width="14" height="14" viewBox="0 0 12 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M12 0.799997L10.4 0L4.4 5.2L1.6 3.2L0 4.4L4.4 9.2L12 0.8Z" fill="#fff"></path>
                                                        </svg>

                                                    <?php elseif (strtolower($status) === 'expired') : ?>
                                                        <!--  Expired Icon -->
                                                        <svg class="ep-status-icon" width="14" height="14" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6 0L0 12H12L6 0ZM6.6 9.6H5.4V8.4H6.6V9.6ZM6.6 7.2H5.4V4.8H6.6V7.2Z" fill="#fff"/>
                                                        </svg>
                                                    <?php endif; ?>

                                                    <?php echo esc_html($status); ?>
                                                </span>

                                                <span class="ep-extension-action-result"></span>
                                            </div>
                            
                            <div class="ep-license-details__field ep-license-expiration-date ep-fw-bold ep-text-success ep-my-2">
                                <span class="ep-license--label">Expiration Date:</span>
                                <span class="ep-license--value"><?php echo esc_html($expiration); ?></span>
                            </div>

                            <div class="ep-license-details__field ep-license-plugins ep-mt-3 ep-border-top">
                                <span class="ep-label ep-mt-2 ep-fw-bold">Included Plugins:</span>
                                <div class="ep-included-plugin-list ep-mt-2">
                                    <?php foreach ($license_entry['plugins'] as $plugin) { ?>
                                        <div class="ep-included-plugin ep-mb-2">
                                            <svg width="12" height="10" viewBox="0 0 12 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 0.799997L10.4 0L4.39998 5.19998L1.59999 3.19999L0 4.39998L4.39998 9.19996L12 0.799997Z" fill="#198754"></path>
                                            </svg>
                                            <span><?php echo esc_html($plugin['name']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                    
                            <div class="ep-d-flex ep-justify-content-between ep-license-btn-wrap ep-border-top ep-pt-3">
                                <button class="button-secondary ep-deactivate-license-bundle" data-license="<?php echo esc_attr($license_key);?>"><?php esc_html_e('Deactivate','eventprime-event-calendar-management');?></button>
                                <button class="button button-secondary ep_check_license_update" data-licensekey="<?php echo esc_attr($license_key);?>"><?php esc_html_e('Check Updates','eventprime-event-calendar-management');?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div class="ep-license-list ep-license-list__empty-state"><div class="ep-license-item ep-shadow"><div class="ep-no-licenses ep-no-license-error-message" style="display:block;">No licenses found.</div></div></div>';
    }
}
    
    public function em_get_more_extension_data($plugin_name, $class) {
    $data = [
        'is_installed' => 0,
        'is_activate' => 0,
        'button' => 'Download',
        'class_name' => 'ep-install-now-btn',
        'url' => '',
        'image' => '',
        'desc' => '',
        'is_free' => ($plugin_name[2] ?? '') === 'free' ? 1 : 0,
    ];

    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $installed_plugins = get_plugins();
    $installed_plugin_versions = [];
    $installed_plugin_file = [];

    foreach ($installed_plugins as $plugin_path => $plugin_data) {
        $parts = explode('/', $plugin_path);
        $file_name = end($parts);
        $slug = str_replace('.php', '', $file_name);

        $installed_plugin_file[] = $file_name;
        $installed_plugin_versions[$slug] = $plugin_data['Version'] ?? '';
    }

    // Central plugin configuration map
    $extensions_map = [
    'Eventprime_Live_Seating' => [
         'url' => 'https://theeventprime.com/all-extensions/live-seating/',
        'slug' => 'eventprime-live-seating',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=live-seating-settings',
        'image' => 'live_seating_icon.png',
        'desc' => "Add live seat selection on your events and provide seat based tickets to your event attendees. Set a seating arrangement for all your Event Sites with specific rows, columns, and walking aisles using EventPrime's very own Event Site Seating Builder."
    ],
    'Eventprime_Event_Sponsor' => [
         'url' => 'https://theeventprime.com/all-extensions/event-sponsors/',
        'slug' => 'eventprime-events-sponsors',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=sponsors',
        'image' => 'event_sponsors_icon.png',
        'desc' => "Add Sponsor(s) to your events. Upload Sponsor logos and they will appear on the event page alongside all other details of the event."
    ],
    'Eventprime_Event_Stripe' => [
        'url' => 'https://theeventprime.com/all-extensions/stripe-payments/',
        'slug' => 'eventprime-event-stripe',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=payments&section=stripe',
        'image' => 'stripe_payments_icon.png',
        'desc' => "Start accepting Event Booking payments using the Stripe Payment Gateway. By integrating Stripe with EventPrime, event attendees can now pay with their credit cards while you receive the payment in your Stripe account."
    ],
    'Eventprime_Offline' => [
        'url' => 'https://theeventprime.com/all-extensions/offline-payments/',
        'slug' => 'eventprime-offline',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=payments&section=offline',
        'image' => 'offline_payments_icon.png',
        'desc' => "Don't want to use any online payment gateway to collect your event booking payments? Don't worry. With the Offline Payments extension, you can accept event bookings online while you collect booking payments from attendees offline."
    ],
    'Eventprime_Attendees_List' => [
        'url' => 'https://theeventprime.com/all-extensions/attendees-list/',
        'slug' => 'eventprime-attendees-list',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=attendees-list-settings',
        'image' => 'attendee_list_icon.png',
        'desc' => "Display names of your Event Attendees on the Event page. Or within the new Attendees List widget."
    ],
    'Eventprime_Event_Coupons' => [
         'url' => 'https://theeventprime.com/all-extensions/coupon-codes/',
        'slug' => 'eventprime-coupon-codes',
        'admin_url' => 'edit.php?edit.php?post_type=em_coupon',
        'image' => 'coupon_code_icon.png',
        'desc' => "Create and activate coupon codes for allowing Attendees for book for events at a discount. Set discount type and limits on coupon code usage, or deactivate at will."
    ],
    'Eventprime_Guest_Booking' => [
        'url' => 'https://theeventprime.com/all-extensions/guest-bookings/',
        'slug' => 'eventprime-guest-bookings',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=forms&section=guest_booking',
        'image' => 'guest_bookings_icon.png',
        'desc' => "Allow attendees to complete their event bookings without registering or logging in."
    ],
    'Eventprime_List_Widgets' => [
        'url' => 'https://theeventprime.com/all-extensions/event-list-widgets/',
        'slug' => 'eventprime-list-widgets',
        'admin_url' => 'admin.php?page=em_global_settings',
        'image' => 'event_list_widgets_icon.png',
        'desc' => "Add 3 new Event Listing widgets to your website. These are the Popular Events list, Featured Events list, and Related Events list widgets."
    ],
    'Eventprime_Admin_Attendee_Booking' => [
        'url' => 'https://theeventprime.com/all-extensions/admin-attendee-bookings/',
        'slug' => 'eventprime-admin-attendee-booking',
        'admin_url' => 'admin.php?page=em_bookings',
        'image' => 'admin_attendee_booking_icon.png',
        'desc' => "Admins can now create custom attendee bookings from the backend EventPrime dashboard."
    ],
    'Eventprime_Event_Import_Export' => [
        'url' => 'https://theeventprime.com/all-extensions/events-import-export/',
        'slug' => 'eventprime-event-import-export',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-import-export',
        'image' => 'event_import_export_icon.png',
        'desc' => "Import or export events in popular file formats like CSV, ICS, XML and JSON."
    ],
    'Eventprime_Event_Mailpoet' => [
        'url' => 'https://theeventprime.com/all-extensions/mailpoet-integration/',
        'slug' => 'eventprime-event-mailpoet',
        'admin_url' => 'admin.php?page=em_mailpoet',
        'image' => 'mailpoet_icon.png',
        'desc' => "Connect and engage with your users by subscribing event attendees to MailPoet lists. Users can opt-in multiple newsletters during checkout and can also manage subscriptions in user account area."
    ],
    'Eventprime_Woocommerce_Integration' => [
        'url' => 'https://theeventprime.com/all-extensions/woocommerce-integration/',
        'slug' => 'eventprime-woocommerce-integration',
        'admin_url' => 'admin.php?page=em_global_settings',
        'image' => 'woocommerce_integration_icon.png',
        'desc' => "This extension allows you to add optional and/ or mandatory products to your events. You can define quantity or let users chose it themselves. Fully integrates with EventPrime checkout experience and WooCommerce order management."
    ],
    'Eventprime_Zoom_Meetings' => [
         'url' => 'https://theeventprime.com/all-extensions/zoom-integration/',
        'slug' => 'eventprime-zoom-meetings',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=zoom-meetings',
        'image' => 'zoom_integration_icon.png',
        'desc' => "This extension seamlessly creates virtual events to be conducted on Zoom through the EventPrime plugin. The extension provides easy linking of your website to that of Zoom. Commence and let the attendees join the event with a single click."
    ],
    'Eventprime_Zapier_Integration' => [
        'url' => 'https://theeventprime.com/all-extensions/zapier-integration/',
        'slug' => 'eventprime-zapier-integration',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=zapier-settings',
        'image' => 'zapier_integration_icon.png',
        'desc' => "Extend the power of EventPrime using Zapier's powerful automation tools! Connect with over 3000 apps by building custom templates using EventPrime triggers."
    ],
    'Eventprime_Event_Invoices' => [
        'url' => 'https://theeventprime.com/all-extensions/invoices/',
        'slug' => 'eventprime-invoices',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=invoice',
        'image' => 'event_invoices_icon.png',
        'desc' => "Allows fully customizable PDF invoices, complete with your company branding, to be generated and emailed with booking details to your users."
    ],
    'Eventprime_Twilio_Text_Notification' => [
        'url' => 'https://theeventprime.com/all-extensions/twilio-text-notifications/',
        'slug' => 'eventprime-twilio-text-notification',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=sms-settings',
        'image' => 'twilio_icon.png',
        'desc' => "Keep your users engaged with text/ SMS notification system. Creating Twilio account is quick and easy. With this extension installed, you will be able to configure admin and user notifications separately, with personalized content."
    ],
    'Eventprime_Event_Tickets' => [
         'url' => 'https://theeventprime.com/all-extensions/event-tickets/',
        'slug' => 'eventprime-event-tickets',
        'admin_url' => 'edit.php?post_type=em_ticket',
        'image' => 'event_tickets_icon.png',
        'desc' => "An EventPrime extension that generate events tickets."
    ],
    'Eventprime_Advanced_Reports' => [
        'url' => 'https://theeventprime.com/all-extensions/advanced-reports-events/',
        'slug' => 'eventprime-advanced-reports',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-events-reports',
        'image' => 'advanced-reports.png',
        'desc' => "Stay updated on all the Revenue and Bookings coming your way through EventPrime. The Advanced Reports extension empowers you with data and graphs that you need to know how much your events are connecting with their audience."
    ],
    'Eventprime_Advanced_Checkout_Fields' => [
        'url' => 'https://theeventprime.com/all-extensions/advanced-checkout-fields/',
        'slug' => 'eventprime-advanced-checkout-fields',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=checkoutfields',
        'image' => 'advanced-chckout-fields.png',
        'desc' => "Capture additional data by adding more field types to your checkout forms, like dropdown, checkbox and radio fields."
    ],
    'Eventprime_Elementor_Integration' => [
         'url' => 'https://theeventprime.com/all-extensions/elementor-integration-extension/',
        'slug' => 'eventprime-elementor-integration',
        'admin_url' => 'admin.php?page=em_global_settings',
        'image' => 'elementor-integration.png',
        'desc' => "Effortlessly create stunning and interactive event pages, calendars, and listings using Elementor’s powerful drag-and-drop interface, without the need for any coding expertise."
    ],
    'Eventprime_Mailchimp_Integration' => [
         'url' => 'https://theeventprime.com/all-extensions/mailchimp-integration/',
        'slug' => 'eventprime-mailchimp-integration',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=mailchimp-integration',
        'image' => 'mailchimp-integration.png',
        'desc' => "Elevate engagement with MailChimp Extension. Seamlessly integrate, automate emails, and connect personally for targeted subscriber interaction."
    ],
    'Eventprime_Event_Feedback' => [
        'url' => 'https://theeventprime.com/all-extensions/user-feedback/',
        'slug' => 'eventprime-event-feedback',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=feedback',
        'image' => 'user-feedback.png',
        'desc' => "Elevate your event experience with EventPrime's Feedback Extension. It allows attendees to share their invaluable insights through multiple submissions."
    ],
    'Eventprime_RSVP' => [
         'url' => 'https://theeventprime.com/all-extensions/rsvp/',
        'slug' => 'eventprime-event-rsvp',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=rsvp',
        'image' => 'rsvp.png',
        'desc' => "Create invitational events, allowing you to send individual or bulk invites, receive and track RSVPs, manage guest lists and more!"
    ],
    'Eventprime_Woocommerce_Checkout_Integration' => [
        'url' => 'https://theeventprime.com/all-extensions/woocommerce-checkout/',
        'slug' => 'eventprime-woocommerce-checkout-integration',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=wc-checkout',
        'image' => 'woocommerce checkout.png',
        'desc' => "Delegate your event booking checkout process to WooCommerce, and use any compatible WooCommerce payment gateway!"
    ],
    'Eventprime_Ratings_And_Reviews' => [
        'url' => 'https://theeventprime.com/all-extensions/ratings-and-reviews/',
        'slug' => 'eventprime-EventPrime-User-Reviews',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=reviews',
        'image' => 'review-icon.png',
        'desc' => "Allow users to post reviews and rate events using star ratings. Supports multiple options including review likes and dislikes, frontend scorecard, and a robust admin area configuration!"
    ],
    'Eventprime_Attendee_Event_Check_In' => [
        'url' => 'https://theeventprime.com/all-extensions/attendee-event-check-in/',
        'slug' => 'eventprime-attendee-event-check-in',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=attendee-check-in-settings',
        'image' => 'attendee-check-in.png',
        'desc' => "Enable attendee check-in system for your events. Authorize your check-in staff to manage attendee tracking efficiently for a smooth and organized event experience."
    ],
    'EventPrime_Waiting_List' => [
        'url' => 'https://theeventprime.com/all-extensions/waiting-list/',
        'slug' => 'eventprime-waiting-list',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=wt',
        'image' => 'ep-waiting-list-icon.png',
        'desc' => "Allow users to join a waiting list when events are full and get notified if spots open up. Manage priorities, send alerts, and handle bookings efficiently."
    ],
    'Eventprime_Honeypot_Integration' => [
        'url' => 'https://theeventprime.com/all-extensions/honeypot-security/',
        'slug' => 'eventprime-honeypot-security',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=honeypot',
        'image' => 'honeypot-icon.png',
        'desc' => "The HoneyPot Security extension for EventPrime adds an invisible anti-spam trap to your event forms, preventing bots from submitting fake data while ensuring a smooth experience for real users."
    ],
    'Eventprime_Turnstile_Antispam' => [
         'url' => 'https://theeventprime.com/all-extensions/turnstile-antispam-security/',
        'slug' => 'eventprime-turnstile-antispam-security',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=turnstile-security-settings',
        'image' => 'ep-turnstile-icon.png',
        'desc' => "EventPrime Turnstile Antispam Security enhances the protection of your event forms by integrating Cloudflare's advanced Turnstile CAPTCHA."
    ],
    'Eventprime_Event_Reminder_Emails' => [
        'url' => 'https://theeventprime.com/all-extensions/event-reminder-emails/',
        'slug' => 'eventprime-event-reminder-emails',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=email-reminder-settings',
        'image' => 'ep-event-reminder-emails-icon.png',
        'desc' => "The Event Reminder Emails extension for EventPrime automatically sends reminder emails to attendees before an event starts."
    ],
    'Square_Payment_Integration' => [
        'url' => 'https://theeventprime.com/all-extensions/square-payments/',
        'slug' => 'eventprime-square-payments',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=payments&section=square',
        'image' => 'ep-square-icon.png',
        'desc' => "Enable secure and seamless event payments with Square. This extension integrates Square with EventPrime, providing a smooth checkout experience for your attendees."
    ],
    'Eventprime_Hcaptcha_Integration' => [
        'url' => 'https://theeventprime.com/all-extensions/hcaptcha-security/',
        'slug' => 'eventprime-hcaptcha-security',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-settings&tab=hcaptcha-security-settings',
        'image' => 'hcaptcha-integration.png',
        'desc' => "This extension adds hCaptcha to login, registration, and event booking forms, securing them against bots and automated abuse."
    ],
        
    'Eventprime_Demo_Data' => [
        'url' => 'https://theeventprime.com/all-extensions/demo-data/',
        'slug' => 'eventprime-demo-data',
        'admin_url' => 'edit.php?post_type=em_event&page=ep-demo-data',
        'image' => 'ep-demo-data-icon.png',
        'desc' => "The purpose of this extension is to help users quickly set up their EventPrime installation with demo events to showcase the plugin’s features. The extension will allow users to generate demo events, with the option to include demo user accounts to show booking details."
    ]
];


    if (isset($extensions_map[$class])) {
        $config = $extensions_map[$class];
        $slug = $config['slug'];
        $file = $slug . '.php';

        $data['version'] = $installed_plugin_versions[$slug] ?? '';
        $data['is_installed'] = in_array($file, $installed_plugin_file) ? 1 : 0;
        $data['is_activate'] = class_exists($class);
        if($data['is_installed'])
        {
            $data['button'] = $data['is_activate'] ? 'Setting' : 'Activate';
        }
        $data['class_name'] = $data['is_activate'] ? 'ep-option-now-btn' : 'ep-activate-now-btn';
        $data['url'] = $data['is_activate'] ? admin_url($config['admin_url']) : $config['url'];
        $data['image'] = $config['image'];
        $data['desc'] = $config['desc'];
        $data['is_free'] = isset($plugin_name[2]) && $plugin_name[2] === 'free' ? 1 : 0;
        $data['slug'] = $slug;
        $data['plugin_file'] = $file;

    }

    return $data;
}

    
}
