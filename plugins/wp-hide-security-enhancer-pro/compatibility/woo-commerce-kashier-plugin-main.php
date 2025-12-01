<?php

    /**
    * Compatibility     : WooCommerce Kashier Gateway 
    * Introduced at     : 1.3.0
    */

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_conflict_handle_woo_commerce_kashier
        {
                                                   
            function __construct()
                {
                    if( !   $this->is_plugin_active())
                        return FALSE;
                        
                    add_filter( 'wph/module/general_scripts/remove_id_attribute/ignore_ids',     array ( $this, 'wph_module_general_scripts_remove_id_attribute_ignore_ids' ) );
                }                        
            
            function is_plugin_active()
                {
                    
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    
                    if(is_plugin_active( 'woo-commerce-kashier-plugin-main/kashier-gateway.php' ))
                        return TRUE;
                        else
                        return FALSE;
                }
            
            function  wph_module_general_scripts_remove_id_attribute_ignore_ids( $ignores )
                {
                    $ignores[]  =   'kashier-iFrame';
                    
                    return $ignores;   
                }
   
        }
        
    new WPH_conflict_handle_woo_commerce_kashier();


?>