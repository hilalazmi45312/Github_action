<?php
    
    /**
    * Theme Compatibility       :   Flatsome
    * Last checked on Version   :   3.19.14
    * 
    * Introduced at version     :   3.19.14 
    */
    
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    
    class WPH_conflict_theme_flatsome
        {
               
            function __construct()
                {
                    $this->init();
                }
                        
            public function init()
                {
                                        
                    if ( ( isset ( $_GET['app'] )  &&  $_GET['app']    ==  'uxbuilder' ) ||  isset ( $_GET['uxb_iframe'] ) )
                        {
                            add_filter ('wph/components/css_combine_code',  '__return_false');
                            add_filter ('wph/components/js_combine_code',   '__return_false' );
                            
                            add_filter ('wph/components/_init/',                array( $this,    'wph_components_init'), 999, 2 );
                        }
                        
                }
                
                
            function wph_components_init( $status, $component )
                {
                    if ( $component ==  'rewrite_default' )
                        return FALSE;
                        
                        
                    return $status;
                    
                }                        
     
                                
        }
        
        
    new WPH_conflict_theme_flatsome();
    

?>