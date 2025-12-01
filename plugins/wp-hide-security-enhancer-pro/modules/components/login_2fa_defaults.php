<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_component_login_2fa_defaults extends WPH_module_component
        {            
            function get_component_title()
                {
                    return "General";
                }
                                        
            function get_module_component_settings()
                {
                    add_filter( 'wp-hide/get_site_module_saved_value', array ( $this, '_2fa_enable_for_roles_module_saved_value'), 99, 2 );

                    $option_roles   =   $this->get_site_roles();

                                                
                    $this->component_settings[]                  =   array(
                                                                            'id'            =>  '2fa_enabled',
                                                                                                                                  
                                                                            'input_type'    =>  'radio',
                                                                   
                                                                            'default_value' =>  'no',
                                                                            
                                                                            'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                            'processing_order'  =>  70
                                                                            );
                                                                    
                    $this->component_settings[]                  =   array(
                                                                            'id'                        =>  '2fa_enable_for_roles',
                                                                                                                                     
                                                                            'input_type'                =>  'checkbox',
                                                                            'options'                   =>  $option_roles,
                                                                            'default_value'             =>  array ( ),
                                                                            
                                                                            'module_option_html_render' =>  array( $this, '_2fa_enable_for_roles_option_html'),
                                                                            'module_option_processing'  =>  array( $this, '_2fa_enable_for_roles_option_processing' ),
                                                                            
                                                                            'sanitize_type'             =>  array( array ( $this->wph->functions, 'sanitize_array' ) ),
                                                                            'processing_order'          =>  80
                                                                            );
                                                                    
                    
                    $this->component_settings[]                  =   array(
                                                                            'id'            =>  '2fa_require_setup',
                                                                                                                                  
                                                                            'input_type'    =>  'radio',
                                                                   
                                                                            'default_value' =>  'no',
                                                                            
                                                                            'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                            'processing_order'  =>  70
                                                                            );
                                                                    
                    $this->component_settings[]                  =   array(
                                                                            'id'            =>  '2fa_primary',
                                                                                                                                  
                                                                            'input_type'    =>  'radio',
                                                                   
                                                                            'default_value' =>  '2fa_app',
                                                                            
                                                                            'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                            'processing_order'  =>  70
                                                                            );
                                                                            
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'temporary_login_2fa_bypass',
                                                                                                                          
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
                                    
                                    case '2fa_enabled' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Enable 2FA',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>   __('Enable 2FA for extra account protection.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                                    'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Enable 2FA',    'wp-hide-security-enhancer'),
                                                                                                                                                                    'description'               =>  "<b>" . __('Two-factor authentication (2FA) adds an extra layer of security by requiring two different forms of verification to access your accounts. This means even if someone steals your password, they\'ll still need a second factor, like a code from your phone, to gain entry.', 'wp-hide-security-enhancer') . "</b>" . 
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("Two-Factor Authentication (2FA) enhances account security by requiring a second authentication method in addition to the primary password. This extra layer of protection helps safeguard accounts from unauthorized access, even if the primary password is compromised. To activate 2FA, users can enable one or more additional authentication methods for enhanced security. Three supported options can be used selectively or combined for maximum protection:",    'wp-hide-security-enhancer').
                                                                                                                                                                                                    "<ul>
                                                                                                                                                                                                         <li><a href='admin.php?page=wp-hide-2fa&component=2fa-email'>"     . __("<b>Email Verification Code</b>",    'wp-hide-security-enhancer') . '</a>' . __(": A unique code is sent to the user's registered email, required during the login process.",    'wp-hide-security-enhancer'). "</li>
                                                                                                                                                                                                         <li><a href='admin.php?page=wp-hide-2fa&component=2fa-auth-app'>"  . __("<b>Authenticator App</b>",    'wp-hide-security-enhancer') . '</a>' . __(": Applications like Google Authenticator or Microsoft Authenticator, available on iOS and Android, generate time-based, one-time passcodes (TOTP) for secure login.",    'wp-hide-security-enhancer'). "</li>
                                                                                                                                                                                                         <li><a href='admin.php?page=wp-hide-2fa&component=2fa-recovery-codes'>"  . __("<b>Recovery Codes</b>",    'wp-hide-security-enhancer') . '</a>' . __(": Pre-generated one-time-use codes provided during the 2FA setup. These can be securely stored and used as a backup when other methods are unavailable.",    'wp-hide-security-enhancer'). "</li>
                                                                                                                                                                                                    </ul><br />" . __("Enabling 2FA significantly reduces the risk of unauthorized access by adding a robust secondary verification step.",    'wp-hide-security-enhancer'),
                                                                                                                                                                    'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-two-factor-authentication/'
                                                                                                                                                                    ),
                                                                                                                                
                                                                                                                                'input_type'    =>  'radio',
                                                                                                                                'options'       =>  array(
                                                                                                                                                            'no'                        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                            'yes'                       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                            ),
                                                                                                                                ) );
                                                                break;
                                    
                                    case '2fa_enable_for_roles' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Enable the 2FA for specific roles',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>  __('Enable the 2FA functions for the specified roles.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                            'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Enable the 2FA for specific roles',    'wp-hide-security-enhancer'),
                                                                                                                                                            'description'               =>  __("This option allows you to enforce Two-Factor Authentication (2FA) for selected user roles within your WordPress site. By enabling this feature, users assigned to the specified roles must authenticate using an additional verification step, enhancing account security. You can define which roles require 2FA, ensuring an extra layer of protection for administrators, editors, or other user groups as needed.",    'wp-hide-security-enhancer'),
                                                                                                                                                            'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-two-factor-authentication/'
                                                                                                                                                            ),
                                                                                                                                ) );
                                                                break;
                                                                
                                                                
                                    case '2fa_require_setup' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Enforce User to Configure 2FA',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>   __('Mandate the user to configure Two-Factor Authentication (2FA) during their first login.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                                    'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Enforce User to Configure 2FA',    'wp-hide-security-enhancer'),
                                                                                                                                                                    'description'               =>  "<b>" . __('Mandate the user to configure Two-Factor Authentication (2FA) during their first login.', 'wp-hide-security-enhancer') . "</b>" . 
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("Require users to set up Two-Factor Authentication (2FA) during their initial login for enhanced security. Until the setup is completed, access to the dashboard will be restricted.  Users can configure the active 2FA methods, such as email verification codes, authenticator apps, or recovery codes. Once 2FA is successfully enabled, the user can access the dashboard and manage their account securely. ",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                    "<br />" . __("If needed, users can update or modify their 2FA settings anytime from their account settings within the dashboard.",    'wp-hide-security-enhancer').
                                                                                                                                                                                                    "<br />" . __("Activating this option ensures straightforward stronger protection against unauthorized access by requiring users to establish an additional verification layer before accessing their account. This proactive approach enhances security and minimizes risks associated with compromised passwords.",    'wp-hide-security-enhancer'),
                                                                                                                                                                    'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-two-factor-authentication/'
                                                                                                                                                                    ),
                                                                                                                                
                                                                                                                                'input_type'    =>  'radio',
                                                                                                                                'options'       =>  array(
                                                                                                                                                            'no'                        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                            'yes'                       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                            ),
                                                                                                                                ) );
                                                                break;
                                                                
                                    case '2fa_primary' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Primary Two-Factor option',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>   __('Primary Two-Factor Authentication Method.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                                    'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Primary Two-Factor option',    'wp-hide-security-enhancer'),
                                                                                                                                                                    'description'               =>  "<b>" . __('Select the Primary Two-Factor Authentication Method, which will be presented to the user when they reach the 2FA step during login. This method will be the first authentication option, the user can also pick others.', 'wp-hide-security-enhancer') . "</b>" . 
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("The available 2FA methods include:",    'wp-hide-security-enhancer').
                                                                                                                                                                                                    "<ul>
                                                                                                                                                                                                         <li>" . __("<b>Email Verification</b>: A unique code is sent to the user's registered email address, which must be entered during login for authentication.",    'wp-hide-security-enhancer'). "</li>
                                                                                                                                                                                                         <li>" . __("<b>Authenticator App</b>: Use apps like Google Authenticator or Microsoft Authenticator to generate time-based one-time passcodes (TOTP) that the user will need to enter during login.",    'wp-hide-security-enhancer'). "</li>
                                                                                                                                                                                                         <li>" . __("<b>Recovery Codes</b>: Pre-generated one-time-use codes provided during 2FA setup, which can be used as a backup if other methods are unavailable or inaccessible.",    'wp-hide-security-enhancer'). "</li>
                                                                                                                                                                                                    </ul><br />" . __("You can choose the most convenient or secure method for your users, ensuring an additional layer of protection for their accounts.",    'wp-hide-security-enhancer'),
                                                                                                                                                                    'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-two-factor-authentication/'
                                                                                                                                                                    ),
                                                                                                                                
                                                                                                                                'input_type'    =>  'radio',
                                                                                                                                'options'       =>  array(
                                                                                                                                                            '2fa_email'                     =>  __('Email',     'wp-hide-security-enhancer'),
                                                                                                                                                            '2fa_app'                       =>  __('Auth APP',    'wp-hide-security-enhancer'),
                                                                                                                                                            '2fa_recovery_codes'            =>  __('Recovery Codes',    'wp-hide-security-enhancer'),
                                                                                                                                                            ),
                                                                                                                                ) );
                                                                break;
                                                                
                                    case 'temporary_login_2fa_bypass' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Disable 2FA when using Temporary Login',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>   __('Disable 2FA when using wordpress plugins like Temporary Login Without Password.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                                    'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Disable 2FA when using Temporary Login',    'wp-hide-security-enhancer'),
                                                                                                                                                                    'description'               =>  "<b>" . __('When enabled, this option bypasses Two-Factor Authentication (2FA) for users accessing the site via a temporary login URL, such as those generated by the Temporary Login Without Password plugin. This ensures seamless access without additional authentication steps.', 'wp-hide-security-enhancer') . "</b>" . 
                                                                                                                                                                                                    "<br />&nbsp;".
                                                                                                                                                                                                    "<br />" . __("The new 'Disable 2FA when using Temporary Login' option in the WP Hide plugin enhances site accessibility by allowing temporary bypassing of Two-Factor Authentication (2FA) for specific login scenarios. When activated, if 2FA is enabled, users who access the site via temporary login URLs, generated by plugins such as Temporary Login Without Password, can log in without undergoing the usual 2FA process. This feature simplifies the user experience during temporary access, ensuring a seamless transition while maintaining robust security measures for standard login procedures. It is an ideal solution for administrators who require controlled, temporary access without compromising overall security.",    'wp-hide-security-enhancer'),
                                                                                                                                                                    'option_documentation_url'  =>  'https://wp-hide.com/documentation/2fa-two-factor-authentication/'
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
                
            
            function _2fa_enable_for_roles_option_html( $module_setting )
                {
                    $values =   $this->wph->functions->get_site_module_saved_value( $module_setting['id'],  $this->wph->functions->get_blog_id_setting_to_use(), 'display');
                       
                    ?>
                        <fieldset>
                            <?php  
                            
                                foreach($module_setting['options']  as  $option_value  =>  $option_title )
                                    {
                                        ?><label><input type="checkbox" class="setting-value checkbox" <?php 
                                            if ( array_search ( $option_value, $values )    !== FALSE )
                                                echo 'checked="checked"';
                                            ?> value="<?php echo $option_value ?>" name="<?php echo $module_setting['id'] ?>[]"> <span><?php echo esc_html( $option_title ) ?></span></label><?php
                                    }
                            
                            ?>
                            <br />
                            <label><input type="checkbox" class="setting-value checkbox" <?php 
                                            if ( array_search ( 'no-role', $values )    !== FALSE )
                                                echo 'checked="checked"';
                                            ?> value="no-role" name="<?php echo esc_attr ( $module_setting['id'] ) ?>[]"> <span><i><?php esc_html_e( 'No Role', 'wp-hide-security-enhancer' ) ?></i></span></label>

                            <input style="display: none" type="checkbox" class="setting-value checkbox" checked="checked" value="--none--" name="<?php echo $module_setting['id'] ?>[]">

                        </fieldset>
                    <?php    
                }
                
                
            function _2fa_enable_for_roles_option_processing( $component_item_settings )
                {
                    $results     =   array();
                    
                    $values      =   array();
                    if ( isset ( $_POST[ $component_item_settings['id'] ] ) )
                        {
                            $data  =   $_POST[ $component_item_settings['id'] ];
                            foreach ( $data    as  $key    =>  $role_name ) 
                                $values[ $key ]  =   preg_replace( '/[^0-9a-zA-Z-_]/' , "", $role_name );
                        }
                    
                    if ( is_array ( $values ) )
                        $values =   array_filter ( $values );
                        else
                        $values[] =   '--none--';
                    
                    $results['value']   =   $values;  
                    
                    return $results;    
                }
                
            function _2fa_enable_for_roles_module_saved_value( $value, $option_id )
                {
                    if ( $option_id !== '2fa_enable_for_roles' )
                        return $value;
                    
                    if ( empty ( $value ) )
                        {
                            $value      =   $this->get_site_roles();
                            $value      =   array_keys ( $value );
                        }
                        
                    return $value;
                }
                
            
            /**
            * Retrieve the site roles
            *     
            */
            private function get_site_roles()
                {
                    global $wpdb;
                    
                    $wp_roles       =   get_option( $wpdb->prefix . 'user_roles');
                    $option_roles   =   array ();
                    foreach  ( $wp_roles     as  $role_slug  =>  $role )
                        $option_roles[$role_slug]   =   $role['name'];
                        
                    return $option_roles;
                }

        }
?>