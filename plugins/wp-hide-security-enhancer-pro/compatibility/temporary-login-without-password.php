<?php


    /**
    * Compatibility     :   Temporary Login Without Password
    * Introduced at     :   1.9.0
    * Last checked on   :   1.9.0
    */

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_conflict_handle_tlwp
        {
            var $wph;
            
            function __construct()
                {
                    if( !   $this->is_plugin_active() )
                        return FALSE;
                        
                    global $wph;
                    $this->wph  =   $wph;
                              
                    add_action ( 'wp-hide/2fa/process_wp_login' , array ( $this, 'process_wp_login' ), 10, 2 );

                }                        
            
            function is_plugin_active()
                {
                    
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    
                    if ( is_plugin_active ( 'temporary-login-without-password/temporary-login-without-password.php' ) )
                        return TRUE;
                        else
                        return FALSE;
                }
                
                
            function process_wp_login( $status, $user )
                {
                    $temporary_login_2fa_bypass  =   $this->wph->functions->get_site_module_saved_value('temporary_login_2fa_bypass',  $this->wph->functions->get_blog_id_setting_to_use());
                    if ( $temporary_login_2fa_bypass !== 'yes' ||   ! isset ( $_GET['wtlwp_token'] ) )
                        return $status;
                    
                    return FALSE;
                    
                }
            
           
        }
        
        
    new WPH_conflict_handle_tlwp();


?>