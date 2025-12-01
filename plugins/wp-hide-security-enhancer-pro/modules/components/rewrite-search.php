<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_rewrite_search extends WPH_module_component
        {
            
            function get_component_title()
                {
                    return "Search";
                }
                                                
            function get_module_component_settings()
                {
                    $this->component_settings[] = array(
                        'id'              => 'search',
                        'input_type'      => 'text',
                        'sanitize_type'   => array(array($this->wph->functions, 'sanitize_file_path_name')),
                        'processing_order'=> 60
                    );

                    $this->component_settings[] = array(
                        'id'              => 'search_block_default',
                        'input_type'      => 'radio',
                        'default_value'   => 'no',
                        'sanitize_type'   => array('sanitize_title', 'strtolower'),
                        'processing_order'=> 61
                    );

                    return $this->component_settings;
                }

            function set_module_components_description($component_settings)
                {
                    foreach ($component_settings as $component_key => $component_setting) 
                        {
                            
                            if ( ! isset ( $component_setting['id'] ) )
                                                            continue;
                            switch ($component_setting['id']) 
                                {
                                    case 'search':
                                                                $component_setting = array_merge($component_setting, array(
                                                                    'label'         => __('New Search Path', 'wp-hide-security-enhancer'),
                                                                    'description'   => __('The default path is set to /search/', 'wp-hide-security-enhancer'),
                                                                    'value_description' => 'e.g. find',
                                                                ));
                                        break;

                                    case 'search_block_default':
                                                                $component_setting = array_merge($component_setting, array(
                                                                    'label'         => __('Block default', 'wp-hide-security-enhancer'),
                                                                    'description'   => __('Block default /search/ when using custom one.', 'wp-hide-security-enhancer') . '<br />' . __('Apply only if ', 'wp-hide-security-enhancer') . '<b>New Search Path</b> ' . __('is not empty.', 'wp-hide-security-enhancer'),
                                                                    'help'          => array(
                                                                                            'title'         => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('Block default', 'wp-hide-security-enhancer'),
                                                                                            'description'   => __("This option blocks the default /search/ URL from being accessed when a custom search path is in use. This applies only when <b>New Search Path</b> is set.", 'wp-hide-security-enhancer'),
                                                                                            'option_documentation_url' => 'https://www.wp-hide.com/documentation/rewrite-search-paths/'
                                                                                        ),
                                                                    'options'       => array(
                                                                                            'no'  => __('No', 'wp-hide-security-enhancer'),
                                                                                            'yes' => __('Yes', 'wp-hide-security-enhancer'),
                                                                                        ),
                                                                ));
                                        break;
                                }

                            $component_settings[$component_key] = $component_setting;
                        }

                    return $component_settings;
                }

                
            
            
            function _init_search( $saved_field_data )
                {
                    if ( ! $this->ReInit )
                        add_filter('search_rewrite_rules',      array( $this, 'search_rewrite_rules'), 999);
                    
                    if(empty($saved_field_data))
                        return FALSE;
                        
                    if ( ! $this->ReInit )
                        add_action( 'template_redirect', array( $this, 'template_redirect' ), -1);
                    
                    //add default plugin path replacement
                    $url            =   trailingslashit(    site_url()  ) .  'search';
                    $replacement    =   trailingslashit(    home_url()  ) .  $saved_field_data;
                    $this->wph->functions->add_replacement( $url , $replacement );
                    
                    return TRUE;
                }
                
            
            /**
            * Rewrite the default Search url
            * 
            * @param mixed $search_rewrite
            */
            function search_rewrite_rules( $search_rewrite )
                {
                    $new_search_path        =   $this->wph->functions->get_site_module_saved_value( 'search',  $this->wph->functions->get_blog_id_setting_to_use() );
                    
                    if( empty( $new_search_path ) )
                        return $search_rewrite;
                    
                    $search_block_default   =   $this->wph->functions->get_site_module_saved_value( 'search_block_default',  $this->wph->functions->get_blog_id_setting_to_use() );
                    
                    $new_rules              =   array();
                    foreach ( $search_rewrite   as  $key    =>  $value )
                        {
                            $new_rules[ str_replace( 'search/', $new_search_path .'/' , $key ) ]    =   $value;    
                        }
                        
                    if  ( $search_block_default ==  'yes')
                        $search_rewrite =   $new_rules;
                        else
                        $search_rewrite =   array_merge ( $search_rewrite, $new_rules );
                    
                    return $search_rewrite;
                      
                }
                
            
            /**
            * Redirect to new slug url
            *     
            */
            function template_redirect() 
                {
                    if ( is_search() && ! empty( $_GET['s'] ) ) 
                        {
                            $new_search_path        =   $this->wph->functions->get_site_module_saved_value( 'search',  $this->wph->functions->get_blog_id_setting_to_use() );
                            
                            wp_redirect( home_url( "/" . $new_search_path . "/" ) . urlencode( get_query_var( 's' ) ) );
                            exit();
                        }  
                }
                
            
        }
?>