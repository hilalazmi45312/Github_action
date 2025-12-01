<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_general_block_detectors extends WPH_module_component
        {
            function get_component_title()
                {
                    return "Block Detectors";
                }
                                    
            function get_module_component_settings()
                {
                    
                    $this->component_settings[]                  =   array(
                                                                    'id'            =>  'block_detectors',
                                                                    
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
                            switch ( $component_setting['id'] )
                                {
                                    case 'block_detectors' :
                                                                $component_setting =   array_merge ( $component_setting , array(
                                                                                                                                'label'         =>  __('Block Theme / Plugin detectors.',    'wp-hide-security-enhancer'),
                                                                                                                                'description'   =>  __('Block common Theme / Plugin detectors and canners.', 'wp-hide-security-enhancer'),
                                                                                                                                
                                                                                                                                'help'          =>  array(
                                                                                                                                                            'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Block Theme / Plugin detectors',    'wp-hide-security-enhancer'),
                                                                                                                                                            'description'               =>  __("Enhance your website's privacy and security with the Block Theme Detectors feature. This tool prevents known user agents and IP addresses associated with popular theme detectors from accessing your site's design and theme-related information. By doing so, it keeps your creative choices private and reduces the risk of targeted attacks exploiting specific theme vulnerabilities." , 'wp-hide-security-enhancer')
                                                                                                                                                                                            . "<br /><strong>" . __( "Key Benefits", 'wp-hide-security-enhancer') . ":</strong>
                                                                                                                                                                                                    <ul>
                                                                                                                                                                                                         <li>" . __(  "Privacy: Protect your website's theme details from being copied or analyzed", 'wp-hide-security-enhancer') . "</li>
                                                                                                                                                                                                         <li>" . __('Security: Minimize exposure to potential threats',    'wp-hide-security-enhancer') .".</li>
                                                                                                                                                                                                         <li>" . __('Performance: Optimize server resources by blocking unnecessary traffic',    'wp-hide-security-enhancer') .".</li>
                                                                                                                                                                                                         <li>" . __('Competitive Edge: Maintain a unique brand identity',    'wp-hide-security-enhancer') .".</li>
                                                                                                                                                                                                    </ul>",
                                                                                                                                                            'option_documentation_url'  =>  'https://wp-hide.com/documentation/block-theme-plugin-detectors/'
                                                                                                                                                            ),
                                                                                                                                
                                                                                                                                'options'       =>  array(
                                                                                                                                                            'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                                                                            'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                                                                            ),
                                                                                                                                ) );
                                                                break;
              
                                
                                }
                                
                                
                            $component_settings[ $component_key ]   =   $component_setting;
                                
                        }
                    
                    return $component_settings;
                    
                }
                
            function _callback_saved_block_detectors ( $saved_field_data )
                {
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE; 
                        
                    $empty_file =   $this->wph->default_variables['plugins_directory'];
                    $empty_file =   trailingslashit( $empty_file ) . 'wp-hide-security-enhancer-pro/router/empty.html';    
                    
                    $rewrite_to     =   $this->wph->functions->get_rewrite_path( $empty_file, [ 'left_slash'  =>  TRUE, 'right_slash' =>  FALSE, 'include_full_path' =>  TRUE ] );
                        
                    $processing_response    =   array();
                          
                    if($this->wph->server_htaccess_config   === TRUE)
                        {                               
                            $processing_response['rewrite'] = '
RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in.*$ [NC]
RewriteCond %{REMOTE_ADDR} ^167\.99\.233\.\d+$ [NC,OR]
RewriteCond %{REMOTE_ADDR} ^35\.214\.130\.87$ [NC,OR]
RewriteCond %{HTTP_USER_AGENT} (builtwith|gochyu|isitwp|mshots|scanwp|wpthemedetector|whatcms|wapalyzer|wpdetector) [NC]
RewriteRule ^ ' . $rewrite_to . ' [END]';
           
                        }
                            
                    if($this->wph->server_web_config   === TRUE)
                        {

                        }
                    
                    if($this->wph->server_nginx_config   === TRUE)           
                        {
                            
                            $global_settings    =   $this->wph->functions->get_global_settings ( );
                            
                            $home_root_path =   $this->wph->functions->get_home_root();
                            
                            if ( $global_settings['nginx_generate_simple_rewrite']   ==  'yes' )
                                {
                                    $rewrite        =   array();    
                                    $rewrite_list   =   array();
                                    $rewrite_rules  =   array();
                                    
                                    $rewrite_list['blog_id']        =   'network';
                                    $rewrite_list['type']           =   'location';
                                    $rewrite_list['description']    =   '~ ^__WPH_SITES_SLUG__/block_detectors'; 

                                    $rewrite_data               =   'if ($http_user_agent ~* "(builtwith|gochyu|isitwp|mshots|scanwp|wpthemedetector|whatcms|wapalyzer|wpdetector)") {
                                                                        rewrite ^ ' . $rewrite_to  .' '.  $this->wph->functions->get_nginx_flag_type() . ';
                                                                        }' . "\n";
                                    $rewrite_data               .=   'if ($remote_addr ~ "^(167\.99\.233\.\d+|35\.214\.130\.87)$") {
                                                                        rewrite ^ ' . $rewrite_to  .' '.  $this->wph->functions->get_nginx_flag_type() . ';
                                                                        }';
                                    
                                    
                                    $rewrite_rules[]            =   $rewrite_data;
                                    $rewrite_list['data']       =   $rewrite_rules; 
                                    
                                    $rewrite[]                  =   $rewrite_list;
                                    
                                    
                                    $processing_response['rewrite'] = $rewrite;            
                                    return  $processing_response;    
                                }
                                
                            $processing_response['rewrite'] = $rewrite;            
                                                            
                        }
                                
                    return  $processing_response;   
                    
                }
                
            
        
                
        }
?>