<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_admin_login_php extends WPH_module_component
        {
            
            function __construct()
                {
                    parent::__construct();
                    
                    if ( isset ( $_GET['page'] ) && $_GET['page']   ==  'wp-hide-admin' )
                        add_action ( 'admin_enqueue_scripts', 'wp_enqueue_media' ); 
                    
                    add_action( 'login_footer', array ( $this, 'custom_login_logo' ) );
                }
            
            function get_component_title()
                {
                    return "Wp-login.php";
                }
                                    
            function get_module_component_settings()
                {
                            
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'new_wp_login_php',
                                                                    
                                                                    'input_type'    =>  'text',
                                                                    
                                                                    'sanitize_type' =>  array ( array( $this->wph->functions, 'sanitize_file_path_name') ),
                                                                    'processing_order'  =>  50
                                                                    
                                                                    );
                    
                    $this->component_settings[]                  =   array(
                                                                                'type'            =>  'split'
                                                                                
                                                                                );
                    
                                                                                        
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'block_default_wp_login_php',
                                                                    
                                                                    'input_type'    =>  'radio',

                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  55
                                                                    
                                                                    );
                                                                    
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'new_wp_login_rewrite_mere',
                                                                                                            
                                                                    'input_type'    =>  'radio',

                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  55
                                                                    
                                                                    );
                                                                    
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'custom_login_logo',
                                                                                                                                                         
                                                                    'input_type'    =>  'custom',
                                                                    
                                                                    'module_option_html_render' =>  array( $this, '_custom_login_logo_module_option_html' ),
                                                                    'module_option_processing'  =>  array( $this, '_custom_login_logo_module_option_processing' ),
                                                                    
                                                                    
                                                                    'processing_order'  =>  50
                                                                    
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
                                    case 'new_wp_login_php' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                    'label'         =>  __('New wp-login.php',    'wp-hide-security-enhancer'),
                                                                                                                                    'description'   =>  __('Map a new wp-login.php instead default. This also need to include <i>.php</i> extension.',  'wp-hide-security-enhancer'),
                                                                                                                                                                                               
                                                                                                                                    'help'          =>  array(
                                                                                                                                                                        'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('New wp-login.php',    'wp-hide-security-enhancer'),
                                                                                                                                                                        'description'               =>  __("There are a lot of security issues that come from having your login page open to the public. Most specifically, brute force attacks. Because of the ubiquity of WordPress, these kinds of attacks are becoming more and more common.",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                            "<br /><br />" . __("Map a new wp-login.php instead default prevent hackers boot to attempt to brute force a site login. Being known only by the site owner, the url itself becomes private.",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                            "<br /><br /><span class='important'>" . __("Make sure you keep the new login url to a safe place, in case to forget.",    'wp-hide-security-enhancer') . "</span>",
                                                                                                                                                                        'input_value_extension'     =>  'php',
                                                                                                                                                                        'option_documentation_url'  =>  'https://www.wp-hide.com/documentation/admin-change-wp-login-php/'
                                                                                                                                                                        ),
                                                                                                                                    
                                                                                                                                    
                                                                                                                                    'options_pre'   =>  '<div class="icon">
                                                                                                                                                                <img src="' . WPH_URL . '/assets/images/warning.png" />
                                                                                                                                                            </div>
                                                                                                                                                            <div class="text">
                                                                                                                                                                <p>' . __('Make sure your log-in url is not already modified by another plugin or theme. In such case, you should disable other code and take advantage of these features.',  'wp-hide-security-enhancer') .'</p>
                                                                                                                                                            </div>' ,
                                                                                                                                ) );
                                                                break;
                                                                
                                    case 'block_default_wp_login_php' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                    'label'         =>  __('Block default wp-login.php',    'wp-hide-security-enhancer'),
                                                                                                                                    'description'   =>  __('Block default wp-login.php file from being accesible.',  'wp-hide-security-enhancer'),
                                                                                                                                    
                                                                                                                                    'help'          =>  array(
                                                                                                                                                                        'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Block default wp-login.php',    'wp-hide-security-enhancer'),
                                                                                                                                                                        'description'               =>  __("If set to Yes, the old login url will be blocked and a default theme 404 error page will be returned.",    'wp-hide-security-enhancer') .
                                                                                                                                                                                                         "<br /><br /><span class='important'>" . __('Ensure the New wp-login.php option works correctly on your server before activate this.',    'wp-hide-security-enhancer') . '</span>',
                                                                                                                                                                        'option_documentation_url'  =>  'https://www.wp-hide.com/documentation/admin-change-wp-login-php/'
                                                                                                                                                                        ),
                                                                                                                                    
                                                                                                                                    'options'       =>  array(
                                                                                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                                ),
                                                                                                                                ) );
                                                                break;
                                                                
                                    case 'new_wp_login_rewrite_mere' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                    'label'         =>  __('Use mere rewrite for Block Default',    'wp-hide-security-enhancer'),
                                                                                                                                    'description'   =>  __('On specific servers, blocking might not work, trigger this setting to make it compatible.',  'wp-hide-security-enhancer'),
                                                                                                                                    
                                                                                                                                    'help'          =>  array(
                                                                                                                                                                        'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Use mere rewrite for Block Default',    'wp-hide-security-enhancer'),
                                                                                                                                                                        'description'               =>  __('When accessing the default login url, even if blocked, on certain servers the link might still be available. This option create an extra rewrite rule which might fix the blocking.',    'wp-hide-security-enhancer') . '</span>',
                                                                                                                                                                        'option_documentation_url'  =>  'https://www.wp-hide.com/documentation/admin-change-wp-login-php/'
                                                                                                                                                                        ),
                                                                                                                                    
                                                                                                                                    'input_type'    =>  'radio',
                                                                                                                                    'options'       =>  array(
                                                                                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                                ),
                                                                                                                                ) );
                                                                break;
                                                                
                                    case 'custom_login_logo' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                    'label'         =>  __('Custom Login Logo',    'wp-hide-security-enhancer'),
                                                                                                                                    'description'   =>  array(
                                                                                                                                                                __('Change the default WordPress login Logo.',  'wp-hide-security-enhancer')
                                                                                                                                                                ),
                                                                                                                                    
                                                                                                                                    'help'          =>  array(
                                                                                                                                                                        'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Custom Login Logo',    'wp-hide-security-enhancer'),
                                                                                                                                                                        'description'               =>  __("The feature in the WP Hide plugin allows you to replace the standard WordPress logo with a custom image of your choice. This customization enhances your site's branding by displaying your logo on the login page, admin dashboard, and other areas where the default logo appears. To use this feature, simply upload your desired logo in the WP Hide settings and save the changes. Your new logo will be displayed immediately, providing a more personalized and professional appearance for your WordPress site.",    'wp-hide-security-enhancer') . "</span>" .
                                                                                                                                                                                                        "<br />" . __("Recommended width size is 320px.",    'wp-hide-security-enhancer') . "</span>",
                                                                                                                                                                        'option_documentation_url'  =>  'https://wp-hide.com/documentation/admin-change-wp-login-php/'
                                                                                                                                                                        ), 
                                                                                                                                ) );
                                                                break;
                                }
                                
                            $component_settings[ $component_key ]   =   $component_setting;
                        }
                    
                    return $component_settings;
                    
                }
        
                
                
                
            function _init_new_wp_login_php( $saved_field_data )
                {
                    
                    $saved_field_data   =   (string)$saved_field_data;
                    
                    //check if the value has changed, e-mail the new url to site administrator                    
                    $previous_url   =   get_option('wph-previous-login-url');
                    if( $saved_field_data    !=  $previous_url )
                        {
                            update_option( 'wph-login-changed-send-email', time() + 5 );                           
                            wp_cache_flush();
                            update_option('wph-previous-login-url', $saved_field_data );  
                        }
                    
                    if ( empty  ( $saved_field_data ) ||  $saved_field_data   ==  'no' )
                        return FALSE;
  
                    if ( ! $this->ReInit )
                        {
                            add_filter('login_url',             array(  $this,'login_url'), 999, 3 );
                            
                            add_filter('site_url',              array(  $this,'site_url'), 999, 3 );
                            add_filter('network_site_url',      array(  $this,'network_site_url'), 999, 3 );
                            
                            //Some plugins (BuddyBoss) when run on MultiSite may not use correct login url
                            add_filter('lostpassword_url',      array(  $this,'lostpassword_url'), 999, 2 );
                        }
  
                    //add replacement
                    $this->wph->functions->add_replacement( trailingslashit(    site_url()  ) .  'wp-login.php',  trailingslashit(    $this->wph->default_variables['root_url']  ) .  $saved_field_data );
                               
                }
            
            
            function login_url( $login_url, $redirect, $force_reauth )
                {
                    //ensure there is no loop with another plugin
                    $backtrace  =   debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    foreach ( $backtrace   as $key  =>  $backtrace )
                        {
                            if ( $key < 1 )
                                continue;
                                
                            if ( isset ( $backtrace['file'] )   &&  strpos( wp_normalize_path( $backtrace['file'] ), 'modules/components/admin-login_php.php' ) )
                                return $login_url;
                        }
                        
                    //Gravity Forms
                    if ( isset ( $_GET['gf_page'] ))
                        {
                            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                            if ( is_plugin_active( 'gravityforms/gravityforms.php' ))
                                {
                                    return home_url( );   
                                }
                        }
                    
                    $parse_login_url        =   parse_url ( $login_url );
                    $new_wp_login_php       =   $this->wph->functions->get_site_module_saved_value( 'new_wp_login_php',  $this->wph->functions->get_blog_id_setting_to_use() );
                    
                    //avoid looping
                    static $wph_home_url;
                    if ( is_null ( $wph_home_url ) )
                        {
                            if ( $this->wph->default_variables['site_relative_path'] !== '/' )
                                $wph_home_url   =   $this->wph->default_variables['home_url'] . '/' . $new_wp_login_php;
                                else
                                $wph_home_url   =   $this->wph->default_variables['home_url'] . '/' . $new_wp_login_php;
                        }
                    
                    $login_url          =   $wph_home_url;
                   
                    if ( isset ( $parse_login_url['query'] )    &&   ! empty ( $parse_login_url['query'] ) )
                        $login_url .=   '?' .   $parse_login_url['query'];
                    
                    return $login_url;   
                }
                
                
            function site_url( $url, $path, $scheme )
                {
                    if ( ! in_array ( $scheme, array ( 'login', 'login_post' ) ) )
                        return $url;
                    
                    $new_wp_login_php       =   $this->wph->functions->get_site_module_saved_value( 'new_wp_login_php',  $this->wph->functions->get_blog_id_setting_to_use() );    
                    if ( empty ( $new_wp_login_php ) )
                        return $url;
                        
                    if ( $this->wph->default_variables['site_relative_path'] === '/' )
                        {
                            $new_wp_login_php   =   ltrim ( trailingslashit ( $this->wph->default_variables['site_relative_path'] )  .   $new_wp_login_php,  '/' );

                            if ( $this->wph->default_variables['wordpress_directory'] !== '/' )
                                $url    =   str_replace ( $this->wph->default_variables['home_url'] . '/wp-login.php', $new_wp_login_php, $url );
                                else
                                $url    =   str_replace ( 'wp-login.php', $new_wp_login_php, $url );

                        }
                        else
                        {
                            $url    =   str_replace ( 'wp-login.php', $new_wp_login_php, $url );   
                        }
                    
                        
                    return $url;
                }
            
            function network_site_url( $url, $path, $scheme )
                {
                    if ( ! in_array ( $scheme, array ( 'login', 'login_post' ) ) )
                        return $url;
                    
                    $new_wp_login_php       =   $this->wph->functions->get_site_module_saved_value( 'new_wp_login_php',  $this->wph->functions->get_blog_id_setting_to_use() );    
                    if ( empty ( $new_wp_login_php ) )
                        return $url;
                        
                    if ( $this->wph->default_variables['site_relative_path'] === '/' )
                        {
                            $new_wp_login_php   =   ltrim ( trailingslashit ( $this->wph->default_variables['site_relative_path'] )  .   $new_wp_login_php,  '/' );

                            if ( $this->wph->default_variables['wordpress_directory'] !== '/' )
                                $url    =   str_replace ( $this->wph->default_variables['home_url'] . '/wp-login.php', $new_wp_login_php, $url );
                                else
                                $url    =   str_replace ( 'wp-login.php', $new_wp_login_php, $url );

                        }
                        else
                        {
                            $url    =   str_replace ( 'wp-login.php', $new_wp_login_php, $url );   
                        }
                    
                        
                    return $url;
                }
                
            
            function lostpassword_url( $lostpassword_url, $redirect )
                {
                    $new_wp_login_php       =   $this->wph->functions->get_site_module_saved_value( 'new_wp_login_php',  $this->wph->functions->get_blog_id_setting_to_use() );    
                    if ( empty ( $new_wp_login_php ) )
                        return $lostpassword_url;
                        
                    if ( $this->wph->default_variables['site_relative_path'] === '/' )
                        {
                            if ( ! empty ( $this->wph->default_variables['site_relative_path'] ) )
                                $new_wp_login_php   =   ltrim ( trailingslashit ( $this->wph->default_variables['site_relative_path'] )  .   $new_wp_login_php,  '/' );

                            if ( ! empty ( $this->wph->default_variables['wordpress_directory'] ) )
                                $lostpassword_url    =   str_replace ( $this->wph->default_variables['root_url'] . '/wp-login.php', $new_wp_login_php, $lostpassword_url );
                                else
                                $lostpassword_url    =   str_replace ( 'wp-login.php', $new_wp_login_php, $lostpassword_url );

                        }
                        else
                        {
                            $lostpassword_url    =   str_replace ( 'wp-login.php', $new_wp_login_php, $lostpassword_url );   
                        }
                    
                    return $lostpassword_url;   
                }
                
            function _callback_saved_new_wp_login_php($saved_field_data)
                {
                    
                    if(empty($saved_field_data))
                        return  FALSE;
                        
                    $processing_response    =   array();
                    
                    global $blog_id;
                    if(is_multisite())
                        {
                            $blog_details   =   get_blog_details( $blog_id );
                            $ms_settings    =   $this->wph->functions->get_site_settings('network');
                        }
                        
                    $rewrite                            =  '';

                    $rewrite_base   =   $this->wph->functions->get_rewrite_path( $saved_field_data, [ 'left_slash'  =>  FALSE, 'right_slash' =>  FALSE, 'exclude_wp_dir'    =>  TRUE ] );
                    $rewrite_to     =   $this->wph->functions->get_rewrite_path( 'wp-login.php', [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE ] );
                               
                    if($this->wph->server_htaccess_config   === TRUE)
                        {
                            if( !is_multisite() )
                                {
                                    $rewrite    .=  "\nRewriteRule ^([_0-9a-zA-Z-]+/)?"    .   $rewrite_base     .   '(.*) '. $rewrite_to .'$2 [END,QSA]';
                                }
                                else
                                {
                                    $rewrite    .=  "\nRewriteRule ^([_0-9a-zA-Z-]+/)?"    .   $rewrite_base     .   '(.*) '. $rewrite_to .'$2 [END,QSA]';
                                }
                            
                        }
                    
                    if($this->wph->server_web_config   === TRUE)
                        {
                            $rewrite    =   "\n" . '<rule name="wph-new_wp_login_php" stopProcessing="true">';
                            
                            if(!is_multisite() )
                                {
                                    $rewrite .=  "\n"  .    '    <match url="^'.  $rewrite_base   .'(.*)"  />';
                                    $rewrite .=   "\n" .    '    <action type="Rewrite" url="'.  $rewrite_to .'{R:1}"  appendQueryString="true" />';
                                }
                                else
                                {
                                    $rewrite .=  "\n"  .    '    <match url="^([_0-9a-zA-Z-]+/)?'.  $rewrite_base   .'(.*)"  />';
                                    $rewrite .=   "\n" .    '    <action type="Rewrite" url="'.  $rewrite_to .'{R:2}"  appendQueryString="true" />';
                                }
                            
                            $rewrite .=  "\n" . '</rule>';
            
                        }
                        
                    if($this->wph->server_nginx_config   === TRUE)           
                        {
                            $rewrite        =   array();
                            $rewrite_list   =   array();
                            $rewrite_rules  =   array();
                            
                            $global_settings    =   $this->wph->functions->get_global_settings ( );
                            
                            $rewrite_base   =   $this->wph->functions->get_rewrite_path( $saved_field_data, [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE, 'exclude_wp_dir'    =>  TRUE, 'type' =>  'nginx' ] );
                               
                            if(!is_multisite() )
                                $rewrite_list['blog_id'] =   $blog_id;
                                else
                                $rewrite_list['blog_id'] =   'network';
                                
                            $rewrite_list['type']        =   'location';
                            $rewrite_list['description'] =   '~ ^__WPH_SITES_SLUG__/' . untrailingslashit($rewrite_base) ;
                            
                            if( $global_settings['nginx_generate_simple_rewrite']   !=  'yes' )
                                {
                                    $rewrite_rules[]  =   '         set $wph_remap  "${wph_remap}new_wp_login__";';
                                }
                            
                            $rewrite_data   =   '';
                                 
                            $rewrite_data .= "\n         rewrite \"^". $rewrite_base ."(.*)\" ". $rewrite_to .'$__WPH_REGEX_MATCH_2__ '.  $this->wph->functions->get_nginx_flag_type() .';';
                                               
                            $rewrite_rules[]            =   $rewrite_data;
                            $rewrite_list['data']       =   $rewrite_rules;
                            
                            $rewrite[]  =   $rewrite_list;
                        }
                    
                    $processing_response['rewrite'] = $rewrite;
                                
                    return  $processing_response;   
                }
                
            
            static public function check_new_url_email_notice()
                {
                    $wph_login_changed_send_email   =   get_option ( 'wph-login-changed-send-email' );
                    if ( empty ( $wph_login_changed_send_email ) )
                        return;
                    
                    $wph_login_changed_send_email   =   (int) $wph_login_changed_send_email ;
                    if ( empty ( $wph_login_changed_send_email ) )
                        return;
                                            
                    if ( $wph_login_changed_send_email < time ( ) )
                        {
                            delete_option ( 'wph-login-changed-send-email' );
                            wp_cache_flush();
                            self::new_url_email_notice();
                        }
                    
                }
            
            static public function new_url_email_notice()
                {
                    
                    global $wph;
                    
                    $to         =   get_site_option('admin_email');  
                        
                    $subject    =   'New Login Url for your WordPress - ' .get_option('blogname');
                    $message    =   __('Hello',  'wp-hide-security-enhancer') . ", \n\n" 
                                    . __('This is an automated message to inform that your login url has been changed at',  'wp-hide-security-enhancer') . " " .  trailingslashit( home_url() ) . "\n"
                                    . __('The new login url is',  'wp-hide-security-enhancer') .  ": " . wp_login_url() . "\n\n"
                                    . __('Additionally, you can use the following link to recover the default login/admin access: ',  'wp-hide-security-enhancer') .  ": " . trailingslashit ( home_url() ) . '?wph-recovery='.  $wph->functions->get_recovery_code() . "\n\n"
                                    . __('Please ensure the safety of this URL for potential recovery in case of forgetfulness.',  'wp-hide-security-enhancer') . ".";
                    $headers = 'From: '.  get_option('blogname') .' <'.  get_option('admin_email')  .'>' . "\r\n"; 
                    
                    if ( ! function_exists( 'wp_mail' ) ) 
                        require_once ABSPATH . WPINC . '/pluggable.php';
                        
                    wp_mail( $to, $subject, $message, $headers ); 
                }
            
            function _init_block_default_wp_login_php($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                        
  
                }
                
            function _callback_saved_block_default_wp_login_php($saved_field_data)
                {
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return  FALSE;
                        
                    $processing_response    =   array();
                    
                    global $blog_id;
                    
                    if ( is_multisite() )
                        {
                            $blog_details   =   get_blog_details( $blog_id );
                            $ms_settings    =   $this->wph->functions->get_site_settings('network'); 
                        }
                    
                    //prevent from blocking if the new_wp_login_php is not modified
                    $new_path       =   $this->wph->functions->get_site_module_saved_value('new_wp_login_php',  $this->wph->functions->get_blog_id_setting_to_use(), 'display');                  
                    if (empty(  $new_path ))
                        return FALSE;
                        
                    $mere_rewrite       =   $this->wph->functions->get_site_module_saved_value('new_wp_login_rewrite_mere',  $this->wph->functions->get_blog_id_setting_to_use(), 'display');
                        
                    $rewrite                            =  '';

                    $rewrite_base   =   $this->wph->functions->get_rewrite_path( 'wp-login.php', [ 'left_slash'  =>  FALSE, 'right_slash' =>  FALSE,  ] );
                    $rewrite_to     =   $this->wph->functions->get_rewrite_path( 'index.php?wph-throw-404', [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE, 'exclude_wp_dir'    =>  TRUE  ]  );
                      
                    if($this->wph->server_htaccess_config   === TRUE)
                        {           
                            
                            if(!is_multisite() )
                                {
                                    $rewrite   .=       "\nRewriteCond %{ENV:REDIRECT_STATUS} ^$";
                                    $rewrite   .=       "\nRewriteRule ^([_0-9a-zA-Z-]+/)?" . $rewrite_base ." ".  $rewrite_to ." [END]";
                                }
                                else
                                {
                                    $rewrite   .=       "\nRewriteCond %{ENV:REDIRECT_STATUS} ^$";
                                    $rewrite   .=       "\nRewriteRule ^([_0-9a-zA-Z-]+/)?" . $rewrite_base ." ".  $rewrite_to ." [END]";
                                }

                        }
                        
                    if($this->wph->server_web_config   === TRUE)
                        {
                            $rewrite    =   "\n" . '<rule name="wph-block_default_wp_login_php" stopProcessing="true">';
                             
                            if(!is_multisite() )
                                {
                                    $rewrite .=  "\n"  .    '    <match url="^'.  $rewrite_base   .'"  />';
                                    $rewrite .=   "\n" .    '    <action type="Rewrite" url="'.  $rewrite_to .'"  appendQueryString="true" />';
                                }
                                else
                                {
                                    $rewrite .=  "\n"  .    '    <match url="^([_0-9a-zA-Z-]+/)?'.  $rewrite_base   .'"  />';
                                    $rewrite .=   "\n" .    '    <action type="Rewrite" url="'.  $rewrite_to .'"  appendQueryString="true" />';
                                }
                            
                            $rewrite .=  "\n" . '</rule>';
    
                        }
                        
                        
                    if($this->wph->server_nginx_config   === TRUE)           
                        {
                            $global_settings    =   $this->wph->functions->get_global_settings ( );
                            
                            $rewrite_base   =   $this->wph->functions->get_rewrite_path( 'wp-login.php', [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE, 'type' =>  'nginx' ] );
                            
                            if ( $global_settings['nginx_generate_simple_rewrite']   ==  'yes' )
                                {
                                    
                                    if ( ! is_multisite() )
                                        {
                                            $rewrite        =   array();    
                                            $rewrite_list   =   array();
                                            $rewrite_rules  =   array();
                                            
                                            $rewrite_list['blog_id']        =   'network';
                                            $rewrite_list['type']           =   'location';
                                            $rewrite_list['description']    =   '~ ^__WPH_SITES_SLUG__/' . untrailingslashit($rewrite_base) . '(/.*\.php)'; 
                                            
                                            if  (  $mere_rewrite    !=  'yes' )
                                                $rewrite_data               =   "rewrite \"^". $rewrite_base ."(.+)?\" ". $rewrite_to .' '.  $this->wph->functions->get_nginx_flag_type() .';';
                                                else
                                                $rewrite_data               =   "rewrite \"^". $rewrite_base ."\" ". $rewrite_to .' '.  $this->wph->functions->get_nginx_flag_type() .';';
                                            
                                            $rewrite_rules[]            =   $rewrite_data;
                                            $rewrite_list['data']       =   $rewrite_rules; 
                                            
                                            $rewrite[]                  =   $rewrite_list; 
                                        }
                                    
                                    $processing_response['rewrite'] = $rewrite;            
                                    return  $processing_response;    
                                }
                                                               
                            $rewrite        =   array();    
                            $rewrite_list   =   array();
                            $rewrite_rules  =   array();
                            
                            if(!is_multisite() )
                                $rewrite_list['blog_id'] =   $blog_id;
                                else
                                $rewrite_list['blog_id'] =   'network';
                            
                            $rewrite_list   =   array();
                            $rewrite_rules  =   array();
                                                   
                            $rewrite_list['type']        =   'location';
                            $rewrite_list['description'] =   '~ ^__WPH_SITES_SLUG__/' . untrailingslashit($rewrite_base) . '';
                                                        
                            $rewrite_data   =   '';
  
                            $rewrite_data  .=    "\n".'         if ( $wph_remap = "" ) {';
                            $rewrite_data  .= "\n             rewrite ^__WPH_SITES_SLUG__/". $rewrite_base ." ". $rewrite_to .' last;';
                            $rewrite_data  .=    "\n         }";
                            $rewrite_data  .=    "\n\n         #" . __('REPLACE THE FOLLOWING LINE WITH YOUR OWN INCLUDE! This can be found within block', 'wp-hide-security-enhancer') ."  location ~ \.php$";
                            $rewrite_data  .=    "\n" .'         include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php7.0-fpm.sock;';
                            
                            $rewrite_rules[]            =   $rewrite_data;
                            $rewrite_list['data']       =   $rewrite_rules;
                            
                            $rewrite[]  =   $rewrite_list;    
                        }
                               
                    $processing_response['rewrite'] = $rewrite;    
                                                    
                    return  $processing_response; 
                    
                }
                  
            function _custom_login_logo_module_option_html()
                {
                
                    $custom_logo_image_id =   $this->wph->functions->get_site_module_saved_value('custom_login_logo',  $this->wph->functions->get_blog_id_setting_to_use(), 'display' );
                    
                    ?>
                    <div id="image_preview" style="margin-top: 10px;">
                        <img src="<?php
                        
                        if ( ! empty ( $custom_logo_image_id ) )
                            {
                                $image_url = wp_get_attachment_url( $custom_logo_image_id );
                                if ( $image_url )
                                    echo esc_url($image_url);
                            }
                        
                        ?>" id="image_thumbnail" style="max-width: 320px;<?php  if ( empty ( $custom_logo_image_id ) ) { echo 'display: none'; }  ?>">
                    </div>

                    <p>
                        <input type="hidden" id="custom_logo_image_id" name="custom_logo_image_id" value="<?php echo $custom_logo_image_id ?>" >
                        <button type="button" class="set_custom_images button-primary">Set Logo</button>  &nbsp; <button type="button" id="remove_custom_image" class="button" style="<?php  if ( empty ( $custom_logo_image_id ) ) { echo 'display: none'; }  ?>">Remove Logo</button>
                    </p>


                    <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            
                            jQuery('#remove_custom_image').on( 'click', function() {
                                jQuery( '#image_thumbnail' ).css ( 'display', 'none');
                                jQuery( '#image_thumbnail' ).attr ( 'src', '');
                                jQuery( '#custom_logo_image_id' ).val( '' );
                                jQuery( this ).css ( 'display', 'none');
                            })
                            
                            var mediaUploader;
                            jQuery('.set_custom_images').on('click', function(e) {
                                e.preventDefault();
                                var button = jQuery(this);
                                var inputField = button.prev();
                                // If the media uploader already exists, reopen it
                                if (mediaUploader) {
                                    mediaUploader.open();
                                    return;
                                }
                                // Create the media uploader
                                mediaUploader = wp.media({
                                    title: 'Choose Image',
                                    button: {
                                        text: 'Set Custom Logo'
                                    },
                                    multiple: false
                                });
                                // When an image is selected, run a callback
                                mediaUploader.on('select', function() {
                                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                                    inputField.val(attachment.id);
                                    var thumbnailUrl = attachment.sizes && attachment.sizes.full ? attachment.sizes.full.url : attachment.url;
                                    jQuery('#image_thumbnail').attr('src', thumbnailUrl).show();
                                    jQuery('#remove_custom_image').css ( 'display', 'inline-block');
                                });
                                // Open the uploader dialog
                                mediaUploader.open();
                            });
                        });
                    </script>
                    <?php
   
                }
                
            function _custom_login_logo_module_option_processing()
                {
                    $results            =   array();
                    
                    $custom_logo_image_id  =   preg_replace("/[^0-9]/", '', $_POST['custom_logo_image_id'] );
                    
                    $results['value']   =   $custom_logo_image_id;
                    
                    return $results;
                }
                
            
            function custom_login_logo()
                {
                    $custom_logo_image_id =   $this->wph->functions->get_site_module_saved_value('custom_login_logo',  $this->wph->functions->get_blog_id_setting_to_use(), 'display' );
                    if ( empty ( $custom_logo_image_id ) )
                        return;                        
                        
                    $image_url = wp_get_attachment_url( $custom_logo_image_id );
                    if ( empty ( $image_url ) )
                        return;
                        
                    ?>
                    <style>
                        #login h1 img, .login h1 img {display: block; max-width: 100%; max-height: 100%}
                    </style>
                    <script type="text/javascript">

                        var elementToUpdate = document.querySelector('#login h1');
                        elementToUpdate.innerHTML = '';
                        var newElement = document.createElement('img');
                        newElement.setAttribute('src', '<?php echo esc_url($image_url); ?>');
                        elementToUpdate.appendChild(newElement);
                    
                    </script>
      
                    <?php   
                }
                
                
                
            /**
            * Return the default admin slug, depending on the WordPress install folder
            *     
            */
            static function get_default_login_slug()
                {
                    global $wph;
                    
                    $default_admin_slug =   'wp-login.php';
                    if ( $wph->default_variables['wordpress_directory'] !== '/' )
                        $default_admin_slug =   $wph->default_variables['wordpress_directory']  .   '/' .   $default_admin_slug;
                    
                    $default_admin_slug =   ltrim ( $default_admin_slug, '/' );
                    $default_admin_slug =   rtrim ( $default_admin_slug, '/' );
                        
                    return $default_admin_slug;
                }
                                          

            }
 
?>