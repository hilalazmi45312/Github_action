<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_2fa extends WPH_module
        {
      
            function load_components()
                {   
                    //add components                    
                    include(WPH_PATH . "/modules/components/login_2fa_defaults.php");
                    $this->components[]  =   new WPH_module_component_login_2fa_defaults();
                    
                    include(WPH_PATH . "/modules/components/login_2fa_email.php");
                    $this->components[]  =   new WPH_module_component_login_2fa_email();
                    
                    include(WPH_PATH . "/modules/components/login_2fa_app.php");
                    $this->components[]  =   new WPH_module_component_login_2fa_app();
                    
                    include(WPH_PATH . "/modules/components/login_2fa_recovery_codes.php");
                    $this->components[]  =   new WPH_module_component_login_2fa_recovery_codes();
     
                    //Load the default 2fa code
                    include(WPH_PATH . "/modules/components/login_2fa.php");
                    $this->wph->_2fa    =   new WPH_module_component_login_2fa();
                    
                    //action available for mu-plugins
                    do_action('wp-hide/module_load_components', $this);
                    
                }
            
            function use_tabs()
                {
                    
                    return TRUE;
                }
            
            function get_module_id()
                {
                    
                    return '2fa';
                }
                
            function get_module_slug()
                {
                    
                    return 'wp-hide-2fa';   
                }
    
            function get_interface_menu_data()
                {
                    $interface_data                     =   array();
                    
                    $interface_data['menu_title']       =   __('<span class="wph-info">Security&rarr;</span> 2FA',    'wp-hide-security-enhancer');
                    $interface_data['menu_slug']        =   self::get_module_slug();
                    $interface_data['menu_position']    =   50;
                    
                    return $interface_data;
                }
    
            function get_interface_data()
                {
      
                    $interface_data                     =   array();
                    
                    $interface_data['title']              =   __('WP Hide & Security Enhancer - Login 2FA',    'wp-hide-security-enhancer');
                    $interface_data['description']        =   '';
                    $interface_data['handle_title']       =   '';
                    
                    return $interface_data;
                    
                }
                
                       
        }
    
 
?>