<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_component_login_2fa_email extends WPH_module_component
        {            
            function get_component_title()
                {
                    return "2FA - Email";
                }
                                        
            function get_module_component_settings()
                {
                    
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  '2fa_email',
                                                                                                                          
                                                                    'input_type'    =>  'radio',
                                                           
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  70
                                                                    );
    
                    
                                                                    
                    return $this->component_settings;  
                     
                }
                
            
            function set_module_components_description( $component_settings )
                {
                    
                    
                    foreach ( $component_settings   as  $component_key  =>  $component_setting )
                        {
                            if ( ! isset ( $component_setting['id'] ) )
                                continue;
                                
                            switch ( $component_setting['id'] )
                                {
                                    case '2fa_email' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Activate Email',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>   __('Authentication codes will be sent to user e-mail address.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                                    'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Activate Email',    'wp-hide-security-enhancer'),
                                                                                                                                                                    'description'               =>  "<b>" . __('Authentication codes will be sent directly to the user\'s registered email address as part of the Two-Factor Authentication (2FA) process.', 'wp-hide-security-enhancer') . "</b>" . 
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("Upon reaching the 2FA step during login, the user will receive a unique, time-sensitive code in their inbox. This code must be entered promptly to verify the user's identity and gain access to their account. The email-based authentication method offers an added layer of security by ensuring that only users with access to the registered email can proceed. It is a reliable method for users who prefer not to rely on third-party applications for authentication.",    'wp-hide-security-enhancer').
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("For the best experience with the Two-Factor Authentication (2FA) - Email option, it is highly recommended to use an SMTP plugin. An SMTP (Simple Mail Transfer Protocol) plugin ensures that authentication messages are sent securely and reliably from your server, helping to avoid issues with email delivery. By configuring an SMTP plugin, you can improve the chances of the 2FA email reaching the user's inbox instead of being mistakenly filtered into the spam or bulk folders. .",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                    "<br /><br />" . __("Users can manage this option in the Profile section of their account.",    'wp-hide-security-enhancer'),
                                                                                                                                                                    'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-email/'
                                                                                                                                                                    ),
                                                                                                                                
                                                                                                                                'input_type'    =>  'radio',
                                                                                                                                'options'       =>  array(
                                                                                                                                                            'no'                        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                            'yes'                       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                            ),
                                                                                                                                ) );
                                                                break;
                                                       }
                                
                            $component_settings[ $component_key ]   =   $component_setting;
                        }
                    
                    return $component_settings;
                    
                }
                
  
            function get_label()
                {
                    return __( 'Email', 'wp-hide-security-enhancer' );   
                }
  
  
            /**
            * Return a text title for other available methods
            * 
            */
            function get_other_label() 
                {
                    return __( 'Send a code to your email', 'wp-hide-security-enhancer' );
                }

                
            /**
            * Generate a code and send to the user e-mail
            * 
            * @param mixed $user
            */
            function generate_and_send_code( $user ) 
                {
                    $code = $this->generate_token( $user->ID );

                    $subject = sprintf( __( 'Here is your login confirmation code for %s', 'wp-hide-security-enhancer' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
                    $message = sprintf( __( 'Use the following code to securely log in to your account: %s ' . "\n" . 'Please ensure you enter this code correctly to access your account. ' . "\n \n\n" . 'If you did not request this code, please contact support for assistance.', 'wp-hide-security-enhancer' ), $code );

                    $subject = apply_filters( 'wp-hide/2fa/email/email_subject', $subject, $user->ID );
                    $message = apply_filters( 'wp-hide/2fa/email/email_message', $message, $code, $user->ID );

                    $status =   wp_mail( $user->user_email, $subject, $message );
                    
                    return $status;
                }

  
  
            /**
            * Output the default HTML for the option
            * 
            * @param mixed $user
            * @return mixed
            */
            function login_page_HTML( $user, $args ) 
                {
                    if ( ! $this->user_has_token( $user->ID ) || $this->user_token_has_expired( $user->ID ) ) {
                        $this->generate_and_send_code( $user );
                    }

                    ?>
                        <p class="_2fa-info"><b><?php esc_html_e( 'Email security Code', 'wp-hide-security-enhancer' ); ?></b></p>
                        <p class="_2fa-info"><?php esc_html_e( 'A verification code has been sent to the email address linked to your account. Please check your inbox and enter the code to proceed.', 'wp-hide-security-enhancer' ); ?></p>
                        <p>
                            <strong><label for="authentication_code"><?php esc_html_e( 'Authentication Code:', 'wp-hide-security-enhancer' ); ?></label></strong>
                            
                            <input type="text" inputmode="numeric" name="2fa_email_code" id="authentication_code" class="input" value="" size="20" pattern="[0-9 ]*" placeholder="12345678" data-digits="8" />
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Verify', 'wp-hide-security-enhancer' ) ?>">
                            <input type="submit" class="button" name="2fa_email_code_resend" value="<?php _e( 'Resend Code', 'wp-hide-security-enhancer' ); ?>" />
                        </p>
                    <?php
                    
                    $this->HTML_dependencies();
                }
            
            
            /**
            * Add the required CSS and JavaScript dependencies
            * 
            */
            function HTML_dependencies()
                {
                    ?>
                    <style>
                        #loginform ._2fa-info {padding-bottom: 10px;}
                        #loginform #authentication_code {letter-spacing: .60em; padding-left: 20px; padding-right: 20px}
                        #loginform #authentication_code::placeholder {color:#eaeaea; font-weight: lighter;}
                    </style>
                    <script type="text/javascript">
                        setTimeout( function(){ var auth_input;
                                                auth_input = document.querySelector('#authentication_code');
                                                auth_input.value = '';
                                                auth_input.focus()}, 200);
                    
                        (function() {
                            const loginForm = document.querySelector('#loginform');
                            const numericInput = document.querySelector('input#authentication_code[inputmode="numeric"]');
         
                                numericInput.addEventListener('input', function(event) {
                                    let inputValue = event.target.value.replace(/[^0-9]+/g, '').trimStart();
                                    inputValue = inputValue.slice(0, 8);
                                    event.target.value = inputValue;
                                    const cleanedValue = inputValue.replace(/ /g, '');
                                    if (cleanedValue.length >= 8)
                                        event.target.blur();
                                    if ( cleanedValue.length === 8 ) {
                                        if (typeof loginForm.requestSubmit === 'function') {
                                            loginForm.requestSubmit();
                                            loginForm.submit.disabled = true;
                                        }
                                    }
                                });
                         
                        })();
                    </script>
                    <?php
                }
            
            
            /**
            * Check if the user has a token saved
            * 
            * @param mixed $user_id
            */
            function user_has_token( $user_id ) 
                {
                    $hashed_token = $this->get_user_token( $user_id );

                    if ( ! empty( $hashed_token ) )
                        return true;

                    return FALSE;
                }
                
                

            /**
            * Check if the user token has expired
            * 
            * @param mixed $user_id
            */
            function user_token_has_expired( $user_id ) 
                {
                    $token_timestamp = intval( get_user_meta( $user_id, '_2fa_email_token_timestamp', true ) );

                    if ( empty( $token_timestamp ) )
                        return TRUE;
                    
                    $token_expire      = 10 * MINUTE_IN_SECONDS;

                    if ( ( time() - $token_timestamp ) >= $token_expire )
                        return TRUE;

                    return FALSE;
                }
            
            
            /**
            * Retrieve a user token
            * 
            * @param mixed $user_id
            */
            function get_user_token( $user_id ) 
                {
                    $hashed_token = get_user_meta( $user_id, '_2fa_email_token', true );

                    if ( ! empty( $hashed_token ) && is_string( $hashed_token ) )
                        return $hashed_token;

                    return FALSE;
                }
                
                
  
            
            /**
            * Generate a token code which will be sent to the user e-mail
            * 
            * @param mixed $user_id
            */
            function generate_token( $user_id ) 
                {
                    $token              =   '';
                    $available_chars    =   '1234567890';
                    $length             =   8;
    
                    for ( $i = 0; $i < $length; $i++ )
                        $token .= substr( $available_chars, wp_rand( 0, strlen( $available_chars ) - 1 ), 1 );

                    update_user_meta( $user_id, '_2fa_email_token_timestamp', time() );
                    update_user_meta( $user_id, '_2fa_email_token', wp_hash( $token ) );

                    return $token;
                }
    
    
            /**
            * Before the processing authentication
            * 
            * @param mixed $user
            * @return WP_Error
            */
            function before_process_authentication( $user )
                {
                    if ( isset( $user->ID ) && isset( $_REQUEST[ '2fa_email_code_resend' ] ) ) 
                        {
                            $this->generate_and_send_code( $user );
                            return new WP_Error( 'notice', __( 'A new code has been sent. Please check your email inbox, including the spam or junk folder.', 'wp-hide-security-enhancer' ));
                        }

                    return TRUE;    
                }
    
    
    

            /**
            * Process the Email Code submit
            * 
            * @param mixed $user
            * @return WP_Error
            */
            function process_authentication( $user )
                {
                    $field  =  '2fa_email_code'; 
                    if ( empty( $_REQUEST[ $field ] ) )
                        return new WP_Error( 'error', __( 'ERROR: Invalid inpput code.', 'wp-hide-security-enhancer' ));

                    $code = preg_replace( '/0-9/' , "",       $_REQUEST[ $field ] );
                          
                    if ( ! isset( $user->ID ) || ! $code )
                        return new WP_Error( 'error', __( 'ERROR: Invalid user or empty code.', 'wp-hide-security-enhancer' ));
                    
                    $hashed_token = $this->get_user_token( $user->ID );

                    if ( empty( $hashed_token ) || ! hash_equals( wp_hash( $code ), $hashed_token ) )
                        return new WP_Error( 'error', __( 'ERROR: Invalid verification code.', 'wp-hide-security-enhancer' ));

                    if ( $this->user_token_has_expired( $user->ID ) )
                        return new WP_Error( 'error', __( 'ERROR: Expired verification code.', 'wp-hide-security-enhancer' ));

                    delete_user_meta( $user->ID, '_2fa_email_token' );
                    delete_user_meta( $user->ID, '_2fa_email_token_timestamp' );

                    return true;
                    
                }
                
            
            /**
            * Check if the user require setup for the APP
            * 
            * @param mixed $user_id
            */
            function user_require_setup( $user_id )
                {
                            
                    return FALSE;
                }
            
            
 
            /**
            * Output dashboard option html
            * 
            * @param mixed $user
            */
            function interface_option_html( $user ) 
                {
                    ?>
                        <p><?php echo esc_html( sprintf( __( 'Authentication codes will be securely sent to the email address ( %s ) associated with your account. This added layer of security ensures that only you can access your account, even if your password is compromised. Make sure your email address is up-to-date to receive these important codes promptly.', 'wp-hide-security-enhancer' ), $user->user_email )); ?></p>
                    <?php
                }

        }
?>