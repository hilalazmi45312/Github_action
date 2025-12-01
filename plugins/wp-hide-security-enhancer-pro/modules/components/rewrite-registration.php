<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_rewrite_registration extends WPH_module_component
        {
            
            function get_component_title()
                {
                    return "Registration";
                }
                                                
            function get_module_component_settings()
                {
                    $this->component_settings[] = array(
                        'id'              => 'new_wp_signup_php',
                        'input_type'      => 'text',
                        'sanitize_type'   => array(array($this->wph->functions, 'sanitize_file_path_name')),
                        'processing_order'=> 50
                    );

                    $this->component_settings[] = array(
                        'id'              => 'block_default_wp_signup_php',
                        'input_type'      => 'radio',
                        'default_value'   => 'no',
                        'sanitize_type'   => array('sanitize_title', 'strtolower'),
                        'processing_order'=> 50
                    );

                    $this->component_settings[] = array(
                        'type'            => 'split'
                    );

                    $this->component_settings[] = array(
                        'id'              => 'new_wp_activate_php',
                        'input_type'      => 'text',
                        'sanitize_type'   => array(array($this->wph->functions, 'sanitize_file_path_name')),
                        'processing_order'=> 50
                    );

                    $this->component_settings[] = array(
                        'id'              => 'block_wp_activate_php',
                        'input_type'      => 'radio',
                        'default_value'   => 'no',
                        'sanitize_type'   => array('sanitize_title', 'strtolower'),
                        'processing_order'=> 50
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
                                    case 'new_wp_signup_php':
                                                                $component_setting = array_merge($component_setting, array(
                                                                    'label'         => __('New wp-signup·php', 'wp-hide-security-enhancer'),
                                                                    'description'   => __('Change default sign-up url.', 'wp-hide-security-enhancer'),
                                                                    'help'          => array(
                                                                        'title'         => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('New wp-signup·php', 'wp-hide-security-enhancer'),
                                                                        'description'   => __("This is the url through which users can register a site or / and a username.", 'wp-hide-security-enhancer') . " <br />  <br />
                                                                                            <br /><br /> " . __("The registration status can be controlled through the network super admin interface:", 'wp-hide-security-enhancer') . " <br />  <br /> 
                                                                                            <img src='" . WPH_URL . "/assets/images/help/network-registration-status.jpg' />
                                                                                            <br /> " . __("If being active, it appears like the following URL:", 'wp-hide-security-enhancer') . " <br />  <br /> 
                                                                                            <code>-domain-name-/wp-signup·php</code>",
                                                                        'option_documentation_url' => 'https://www.wp-hide.com/documentation/rewrite-registration/'
                                                                    ),
                                                                    'value_description' => __('e.g. register', 'wp-hide-security-enhancer'),
                                                                ));
                                        break;

                                    case 'block_default_wp_signup_php':
                                                                $component_setting = array_merge($component_setting, array(
                                                                    'label'         => __('Block wp-signup·php URL', 'wp-hide-security-enhancer'),
                                                                    'description'   => __('Block default wp-signup·php file.', 'wp-hide-security-enhancer'),
                                                                    'help'          => array(
                                                                        'title'         => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('Block wp-signup·php', 'wp-hide-security-enhancer'),
                                                                        'description'   => __("Block the default wp-signup·php file. If <b>New wp-signup·php</b> is being used, it is safe to block the default, and the registration process will continue to work.", 'wp-hide-security-enhancer'),
                                                                        'option_documentation_url' => 'https://www.wp-hide.com/documentation/rewrite-registration/'
                                                                    ),
                                                                    'options'       => array(
                                                                        'no'  => __('No', 'wp-hide-security-enhancer'),
                                                                        'yes' => __('Yes', 'wp-hide-security-enhancer'),
                                                                    ),
                                                                ));
                                        break;

                                    case 'new_wp_activate_php':
                                                                $component_setting = array_merge($component_setting, array(
                                                                    'label'         => __('New wp-activate·php', 'wp-hide-security-enhancer'),
                                                                    'description'   => __('Change default blog activation url.', 'wp-hide-security-enhancer'),
                                                                    'help'          => array(
                                                                        'title'         => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('New wp-activate·php', 'wp-hide-security-enhancer'),
                                                                        'description'   => __("This is the URL through which a user can activate a registered blog.", 'wp-hide-security-enhancer') . " <br />  <br />
                                                                                            <br /> " . __("The URL appears like the following:", 'wp-hide-security-enhancer') . " <br />  <br /> 
                                                                                            <code>-domain-name-/wp-activate·php?key=2d857216c129e009</code>",
                                                                        'option_documentation_url' => 'https://www.wp-hide.com/documentation/rewrite-registration/'
                                                                    ),
                                                                    'value_description' => __('e.g. account-activate', 'wp-hide-security-enhancer'),
                                                                ));
                                        break;

                                    case 'block_wp_activate_php':
                                                                $component_setting = array_merge($component_setting, array(
                                                                    'label'         => __('Block wp-activate·php', 'wp-hide-security-enhancer'),
                                                                    'description'   => __('Block access to default wp-activate·php file.', 'wp-hide-security-enhancer'),
                                                                    'help'          => array(
                                                                        'title'         => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('Block wp-activate·php', 'wp-hide-security-enhancer'),
                                                                        'description'   => __("Block access to wp-activate·php file. Through this file, new users confirm that the activation key they receive in the email after signing up for a new blog matches the key for that user.", 'wp-hide-security-enhancer') . 
                                                                                            "<br />" . __("If <b>New wp-activate·php</b> is being used, it is safe to block the default, and the registration process will continue to work.", 'wp-hide-security-enhancer'),
                                                                        'option_documentation_url' => 'https://www.wp-hide.com/documentation/rewrite-registration/'
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

                
         
            function _init_new_wp_signup_php( $saved_field_data )
                {
                    if(empty($saved_field_data))
                        return FALSE; 
 
                    if ( ! $this->ReInit )
                        add_filter( 'wp_signup_location', array( $this, 'wp_signup_location' ));
                    
                    $old_url    =   'wp-signup.php';
                    $new_url    =   $saved_field_data;
                    $this->wph->functions->add_replacement( $old_url ,  $new_url );
                    
                }
                
                
            function _callback_saved_new_wp_signup_php( $saved_field_data )
                {
                    
                    //check if the field is noe empty
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
                    $rewrite_to     =   $this->wph->functions->get_rewrite_path( 'wp-signup.php', [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE  ] );
                               
                    if($this->wph->server_htaccess_config   === TRUE)
                        {
                            
                            if( !is_multisite() )
                                {
                                    $rewrite  .= "\nRewriteRule ^"    .   $rewrite_base  .   ' '. $rewrite_to .' [END,QSA]';
                                }
                                else
                                {
                                    $rewrite  .= "\nRewriteRule ^([_0-9a-zA-Z-]+/)?"    .   $rewrite_base  .   ' '. $rewrite_to .' [END,QSA]';    
                                }
                        }
                    
                    if($this->wph->server_web_config   === TRUE)
                        {
                            $rewrite    =   "\n" . '<rule name="wph-new_wp_signup_php" stopProcessing="true">';
                                       
                            if( !is_multisite() )
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
                            $rewrite        =   array();
                            $rewrite_list   =   array();
                            $rewrite_rules  =   array();
                            
                            $global_settings    =   $this->wph->functions->get_global_settings ( );
                            
                            $rewrite_base   =   $this->wph->functions->get_rewrite_path( $saved_field_data, [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE, 'exclude_wp_dir'    =>  TRUE, 'type' =>  'nginx' ] );
                               
                            if( ! is_multisite() )
                                {
                                    $rewrite_list['blog_id'] =   $blog_id;
                                }
                                else
                                    $rewrite_list['blog_id'] =   'network';
                                
                            $rewrite_list['type']        =   'location';
                            $rewrite_list['description'] =   '~ ^__WPH_SITES_SLUG__/' . untrailingslashit($rewrite_base) ;
                            
                            if( $global_settings['nginx_generate_simple_rewrite']   !=  'yes' )
                                {
                                    $rewrite_rules[]  =   '         set $wph_remap  "${wph_remap}xml_rpc__";';
                                }
                            
                            $rewrite_data   =   '';
                            
                            $rewrite_data .= "\n         rewrite \"^". $rewrite_base ."\" ". $rewrite_to .' '.  $this->wph->functions->get_nginx_flag_type() .';';
                                
                            $rewrite_rules[]            =   $rewrite_data;
                            $rewrite_list['data']       =   $rewrite_rules;
                            
                            $rewrite[]  =   $rewrite_list;   
                        }
                    
                    $processing_response['rewrite'] =   $rewrite;
                                
                    return  $processing_response;   
                }
            
            
            function wp_signup_location()
                {
                    
                    $new_signup     =   $this->wph->functions->get_site_module_saved_value( 'new_wp_signup_php',  $this->wph->functions->get_blog_id_setting_to_use() );
                    
                    $new_url        =   network_site_url( $new_signup );
                    
                    return $new_url;
                    
                }
                
                
            function _init_new_wp_activate_php( $saved_field_data )
                {
                    if(empty($saved_field_data))
                        return FALSE; 
                    
                    $old_url    =   '/wp-activate.php';
                    $new_url    =   '/' . $saved_field_data;
                    $this->wph->functions->add_replacement( $old_url ,  $new_url );
                    
                }
                
                
            function _callback_saved_new_wp_activate_php( $saved_field_data )
                {
                    
                    //check if the field is noe empty
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
                    $rewrite_to     =   $this->wph->functions->get_rewrite_path( 'wp-activate.php', [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE  ] );
                               
                    if($this->wph->server_htaccess_config   === TRUE)
                        {
                            
                            if( !is_multisite() )
                                {
                                    $rewrite  .= "\nRewriteRule ^"    .   $rewrite_base  .   ' '. $rewrite_to .' [END,QSA]';
                                }
                                else
                                {
                                    $rewrite  .= "\nRewriteRule ^([_0-9a-zA-Z-]+/)?"    .   $rewrite_base  .   ' '. $rewrite_to .' [END,QSA]';    
                                }
                        }
                    
                    if($this->wph->server_web_config   === TRUE)
                        {
                            $rewrite    =   "\n" . '<rule name="wph-new_wp_activate_php" stopProcessing="true">';
                                       
                            if( !is_multisite() )
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
                                    $rewrite_rules[]  =   '         set $wph_remap  "${wph_remap}xml_rpc__";';
                                }
                            
                            $rewrite_data   =   '';
                            
                            $rewrite_data .= "\n         rewrite \"^". $rewrite_base ."\" ". $rewrite_to .' '.  $this->wph->functions->get_nginx_flag_type() .';';
                            
                            $rewrite_rules[]            =   $rewrite_data;
                            $rewrite_list['data']       =   $rewrite_rules;
                            
                            $rewrite[]  =   $rewrite_list;   
                        }
                    
                    $processing_response['rewrite'] =   $rewrite;
                                
                    return  $processing_response;   
                }
                
        }
?>