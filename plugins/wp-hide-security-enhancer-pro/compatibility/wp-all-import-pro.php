<?php

    
    /**
    * Compatibility     : WP All Import Pro
    * Introduced at     : 4.8.7
    */

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_conflict_handle_wpallip
        {
            
            var $wph;
                           
            function __construct()
                {
                    if( !   $this->is_plugin_active())
                        return FALSE;
                    
                    global $wph;
                    
                    $this->wph  =   $wph;

                           
                }                        
            
            function is_plugin_active()
                {
                    
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    
                    if ( is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) )
                        return TRUE;
                        else
                        return FALSE;
                }
                            
        }
        
    new WPH_conflict_handle_wpallip();    
        
?>