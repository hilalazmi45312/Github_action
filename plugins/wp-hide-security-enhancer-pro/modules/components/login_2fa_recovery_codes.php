<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_component_login_2fa_recovery_codes extends WPH_module_component
        {            
            function get_component_title()
                {
                    return "2FA - Recovery Codes";
                }
                                        
            function get_module_component_settings()
                {
                    
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  '2fa_recovery_codes',
                                                                                                                          
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
                                    case '2fa_recovery_codes' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Activate Recovery Codes',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>   __('Recovery Codes.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                                    'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Activate Recovery Codes',    'wp-hide-security-enhancer'),
                                                                                                                                                                    'description'               =>  "<b>" . __('Generate 10 one-time-use recovery codes as a backup for Two-Factor Authentication (2FA) to securely access your dashboard.', 'wp-hide-security-enhancer') . "</b>" . 
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("The 2FA Recovery Codes method generates 10 unique, one-time-use codes that act as a secure backup for accessing your dashboard. These codes provide an alternative way to authenticate your login if you are unable to use your primary 2FA method, such as an email code or authenticator app. Each code can only be used once, ensuring added security and preventing unauthorized access. It is crucial to store these recovery codes in a safe and secure location, such as an encrypted password manager or physical safe, to avoid loss or misuse.",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("While this method is primarily intended as a backup solution, it can also be used as the primary 2FA method if necessary. Recovery codes offer a reliable and easy-to-use fallback, ensuring you can always regain access to your account when needed, even if other 2FA options are unavailable.",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                    "<br /><br />" . __("Users can manage this option in the Profile section of their account.",    'wp-hide-security-enhancer'),
                                                                                                                                                                    'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-recovery-codes/'
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
            
            
            function _init_2fa_recovery_codes( $saved_field_data )
                {
                    if ( empty ( $saved_field_data ) ||  $saved_field_data   ==  'no' )
                        return FALSE;
                        
                    add_action( 'wp_ajax_2fa_rc_regenerate', array( $this, 'ajax_regenerate' ) );
                }
            
            
            
            function get_label()
                {
                    return __( 'Recovery Codes', 'wp-hide-security-enhancer' );   
                }
            
                
            public function get_other_label() 
                {
                    return __( 'Use a pre-saved recovery code', 'wp-hide-security-enhancer' );
                }
                
                
            function login_page_HTML( $user, $args  )
                {
                        
                    if ( $this->user_require_setup( $user->ID ) ||   $this->get_available_codes( $user->ID ) === FALSE )
                        {
                                                                                    
                                if ( isset( $user->ID ) && isset( $_REQUEST[ '2fa_generate_list' ] ) ) 
                                    {
                                        ?>
                                        <p class="_2fa-info"><?php esc_html_e( 'The setup process generated 10 Recovery Codes, which serve as a secure backup method to access your dashboard. Each code can only be used once.', 'wp-hide-security-enhancer' ); ?></p>
                                        <?php
                                        
                                        $recovery_codes =   $this->generate_recovery_codes( $user->ID );
                                        ?>
                                        <pre id="recovery-code-list" class="recovery-code-list" onclick="selectAndCopyText();"><?php
                                        
                                        foreach ( $recovery_codes   as  $recovery_code )
                                            echo '<p>' . $recovery_code . '</p>';
                                        
                                        ?></pre>
                                        
                                        <p class="_2fa-info"><?php esc_html_e( 'Be sure to store these codes in a safe location to prevent loss or unauthorized access.', 'wp-hide-security-enhancer' ); ?></p>
                                        <p>&nbsp;</p>
                                        <p>
                                            <?php
                                                
                                                $_2fa_require_setup             =   $this->wph->functions->get_site_module_saved_value('2fa_require_setup',  $this->wph->functions->get_blog_id_setting_to_use());
                                                $other_2fa_option_require_setup =   $this->wph->_2fa->_2fa_option_require_setup( $user->ID, array ( '2fa_recovery_codes' ) );
                                                if ( $_2fa_require_setup    ==  'yes'   &&  $other_2fa_option_require_setup    !== FALSE )
                                                    {
                                                        $link_args = array(
                                                                            'action'        =>  'validate_2fa',
                                                                            '2fa_user_id'   =>  $user->ID,
                                                                            '2fa_nonce'     =>  $args['2fa_nonce'],
                                                                            '2fa_id'        =>  $other_2fa_option_require_setup,
                                                                            'redirect_to'   =>  $args['redirect_to']
                                                                        );
                                                        $continue_url   =   WPH_module_component_login_2fa::login_url( $link_args );
                                                    }
                                                    else
                                                    {
                                                        reset ( $this->wph->_2fa->active_2fa_options );
                                                        $use_2fa_id     =   key ( $this->wph->_2fa->active_2fa_options );
                                                        
                                                        //redirect to first available
                                                        $link_args = array(
                                                                            'action'        =>  'validate_2fa',
                                                                            '2fa_user_id'   =>  $user->ID,
                                                                            '2fa_nonce'     =>  $args['2fa_nonce'],
                                                                            '2fa_id'        =>  $use_2fa_id,
                                                                            'redirect_to'   =>  $args['redirect_to']
                                                                        );
                                                        $continue_url   =   WPH_module_component_login_2fa::login_url( $link_args );
                                                    }
                                                
                                            ?>
                                            <a class="button" name="2fa_generate_list" href="<?php echo esc_url( $continue_url ) ?>"><?php esc_attr_e( 'Continue', 'wp-hide-security-enhancer' ); ?></a>
                                        </p>
                                        
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                        <p class="_2fa-info"><b><?php esc_html_e( 'The Recovery Code setup is not yet completed.', 'wp-hide-security-enhancer' ); ?></b></p>
                                        <p class="_2fa-info"><?php esc_html_e( 'The setup process will generate 10 Recovery Codes, which serve as a secure backup method to access your dashboard. Each code can only be used once.', 'wp-hide-security-enhancer' ); ?></p>
                                        <p>&nbsp;</p>
                                        <p>
                                            <input type="submit" class="button" name="2fa_generate_list" value="<?php esc_attr_e( 'Generate Recovery Codes', 'wp-hide-security-enhancer' ); ?>" />
                                        </p>
                                        <?php
                                    }

                            $this->HTML_dependencies();
                            return;
                        }
                        
                        
                    ?>
                        <p class="_2fa-info"><b><?php esc_html_e( 'Recovery Code', 'wp-hide-security-enhancer' ); ?></b></p>
                        <p class="_2fa-info"><?php esc_html_e( 'Enter a recovery code from the list you saved when setup the option.', 'wp-hide-security-enhancer' ); ?>
                            <br /><?php esc_html_e( 'Once entered, click Verify to proceed.', 'wp-hide-security-enhancer' ); ?>
                        </p>
                                                
                        <p>&nbsp;</p>
                        <p>
                            <strong><label for="authentication_code"><?php esc_html_e( 'Verification Code:', 'wp-hide-security-enhancer' ); ?></label></strong>
                            
                            <input type="text" inputmode="numeric" name="2fa_recovery_code" id="authentication_code" class="input" value="" size="20" pattern="[0-9 ]*" placeholder="12345678" data-digits="8" />
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Verify', 'wp-hide-security-enhancer' ) ?>">
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
                        #loginform .leftalign {float: left}
                        #loginform .button-primary.red {background: #ac0f0f; border-color: #9b1313;}
                        #loginform .button-primary.red:hover {background: #550808}
                        #loginform .recovery-code-list {background-color: #f6f7f7;  padding: 10px 20px;  font-weight: bold; margin-bottom: 10px}
                        #loginform .aligncenter {text-align: center;}
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

                        function selectAndCopyText() {
                            const preElement = document.querySelector('#recovery-code-list');

                            const range = document.createRange();
                            range.selectNodeContents(preElement);

                            const selection = window.getSelection();
                            selection.removeAllRanges();
                            selection.addRange(range);

                            // Copy the selected text to the clipboard
                            try {
                              navigator.clipboard.writeText(preElement.innerText).then(() => {
                              }).catch(() => {
                                document.execCommand('copy');
                              });
                            } catch (err) {}
                          }
                        
                    </script>
                    <?php
                }
                           
                
            /**
            * Before the processing authentication
            * 
            * @param mixed $user
            * @return WP_Error
            */
            function before_process_authentication( $user )
                {
                    if ( isset( $_REQUEST[ '2fa_generate_list' ] ) )
                        return FALSE;
                             
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
                    $field  =  '2fa_recovery_code'; 
                    if ( empty( $_REQUEST[ $field ] ) )
                        return new WP_Error( 'error', __( 'Error: Invalid inpput code.', 'wp-hide-security-enhancer' ));

                    $code   =   preg_replace( '/0-9/' , "",       $_REQUEST[ $field ] );
                                              
                    if ( ! isset( $user->ID ) || ! $code )
                        return new WP_Error( 'error', __( 'Error: Invalid user or empty code.', 'wp-hide-security-enhancer' ));
                    
                    $recovery_code_list =   $this->get_available_codes( $user->ID );
                    if ( $recovery_code_list === FALSE )
                        return new WP_Error( 'error', __( 'Error: This option is unavailable as the internal list is exhausted. Please choose a different option and visit your Profile to generate a new Recovery Codes list. ', 'wp-hide-security-enhancer' ));    
                    
                    $code   =   md5 ( $code );
                    if ( in_array ( $code, $recovery_code_list ) )
                        {
                            unset ( $recovery_code_list [  array_search ( $code, $recovery_code_list ) ] );
                            
                            $this->update_recovery_list ( $user->ID, $recovery_code_list );
                            
                            return TRUE;
                        }

                    return new WP_Error( 'error', __( 'Error: Invalid verification code.', 'wp-hide-security-enhancer' ));                     
                }
                
            
            
            /**
            * Check if the user require setup for Recovery Codes
            * 
            * @param mixed $user_id
            */
            function user_require_setup( $user_id )
                {
                    $setup_completed = get_user_meta( $user_id, '_2fa_rc_setup_completed', true );
                    
                    if (    $setup_completed   !==  'true' )
                        return TRUE;
                        
                    return FALSE;
                }
                
            
            /**
            * Return the available recovery codes for the user
            * 
            */
            function get_available_codes( $user_id )
                {
                    $recovery_code_list = get_user_meta( $user_id, '_2fa_rc_list', true );
                    if ( ! is_array ( $recovery_code_list ) ||  count ( $recovery_code_list )   <   1 )
                        return FALSE;
                        
                    return $recovery_code_list;
                }
            
         
            /**
            * Generate a list of recovery codes
            * 
            * @param mixed $user_id
            */
            function generate_recovery_codes( $user_id )
                {
                    $codes      =   array();
                    $length     =   8;
                    $quantity   =   10;
                    
                    for ($i = 0; $i < $quantity; $i++) 
                        {
                            $codes[] = str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
                        }
                    
                    $hash_list  =   array();
                    
                    //create a hash of the list to save internally
                    foreach ( $codes    as  $code )
                        {
                            $hash_list[]    =   md5 ( $code );
                        }
                        
                    update_user_meta    (   $user_id, '_2fa_rc_list', $hash_list );
                    update_user_meta    (   $user_id, '_2fa_rc_setup_completed', 'true' );
                    
                    return $codes;    
                }
                
                
            /**
            * Update the recovery code list
            * 
            * @param mixed $user_id
            * @param mixed $recovery_code_list
            */
            function update_recovery_list( $user_id, $codes )
                {
                    update_user_meta    (   $user_id, '_2fa_rc_list', $codes );    
                } 

                
                
            /**
            * Output dashboard option html
            * 
            * @param mixed $user
            */
            function interface_option_html( $user ) 
                {
                    if ( $this->user_require_setup( $user->ID ) ||   $this->get_available_codes( $user->ID ) === FALSE )
                        {
                            ?>
                            <p class="_2fa-info important"><b><?php esc_html_e( 'The Recovery Code setup is not yet complete.', 'wp-hide-security-enhancer' ); ?></b></p>
                            <?php    
                            
                        }
                        else
                        {
                    
                            $recovery_code_list =   $this->get_available_codes( $user->ID );
                            $count  =   0;
                            if ( $recovery_code_list !== FALSE )
                                $count  =   count ( $recovery_code_list );
                            
                            ?>
                            <p><?php printf( __( 'You have %s recovery codes remaining, each of which can be used once.', 'wp-hide-security-enhancer' ), $count ); ?></p>
                            <?php
                        }
                    
                    ?>
                        <p><?php _e( 'To generate a new list of recovery codes, click the button below.', 'wp-hide-security-enhancer' ); ?></p>
                        <input type="submit" name="generate_recovery_codes" id="wph_2fa_rc_regenerate" class="button action" value="<?php _e( 'Generate Recovery Codes', 'wp-hide-security-enhancer' ); ?>">
                    <?php
                }
                
                
                
            /**
            * Process the ajax call 
            * 
            */
            function ajax_regenerate() 
                {
                    
                    if( !isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'wph_2fa_nonce') )
                        wp_die('Permission denied');

                    $current_user = wp_get_current_user();
                    if (    ! $current_user instanceof WP_User )
                        wp_die('Permission denied');   
                    
                    $recovery_codes =   $this->generate_recovery_codes( $current_user->ID );
                    
                    ?>
                        <pre id="recovery-code-list" class="recovery-code-list"><?php
                        
                        foreach ( $recovery_codes   as  $recovery_code )
                            echo '<p>' . $recovery_code . '</p>';
                        
                        ?></pre>
                        <p class="_2fa-info"><?php esc_html_e( 'After leaving this page, you will no longer be able to view these codes !', 'wp-hide-security-enhancer' ); ?> <?php esc_html_e( 'Be sure to store these codes in a safe location to prevent loss or unauthorized access.', 'wp-hide-security-enhancer' ); ?></p>
                        <p class="_2fa-info">&nbsp;</p>
                    <?php
                    
                    ob_start();
                    
                    $this->interface_option_html( $current_user );
                    
                    $html   =   ob_get_contents();
                    ob_end_clean();

                    echo $html;
                    
                    wp_die();
                    
                }
                
        }
?>