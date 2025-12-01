<?php


    /**
    * Compatibility     : CompressX
    * Introduced at     : 0.9.28
    * Last checked      : 0.9.28
    */
    
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    class WPH_conflict_handle_compressx
        {
            var $wph;
                            
            function __construct()
                {
                    if( !   $this->is_plugin_active() )
                        return FALSE;
           
                    add_filter ( 'wp-hide/mod_rewrite_rules', array ( $this, 'mod_rewrite_rules' ), 999, 2 );
                    
                }                        
            
            function is_plugin_active()
                {
                    
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    
                    if( is_plugin_active( 'compressx/compressx.php' ) )
                        return TRUE;
                        else
                        return FALSE;
                }
   

            function mod_rewrite_rules( $rules, $type )
                {
                    if ( $type  !== 'apache' )
                        return $rules;
                                            
                    /**
                    * Replace the END flag, as the rewrites on /wp-content/ do not trigger anymore
                    * 
                    */
                    $rules  =   str_replace( ' [END,QSA]', ' [L,QSA]', $rules );
                    
                    return $rules;
                    
                }
                            
        }

        new WPH_conflict_handle_compressx();
        
?>