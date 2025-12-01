<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_general_wpemoji extends WPH_module_component
        {
            function get_component_title()
                {
                    return "Emoji";
                }
                                        
            function get_module_component_settings()
                {
                    $this->component_settings[] = array(
                        'id'            => 'disable_wpemojia',
                        'input_type'    => 'radio',
                        'default_value' => 'no',
                        'sanitize_type' => array('sanitize_title', 'strtolower')
                    );

                    $this->component_settings[] = array(
                        'id'            => 'disable_tinymce_wpemojia',
                        'input_type'    => 'radio',
                        'default_value' => 'no',
                        'sanitize_type' => array('sanitize_title', 'strtolower')
                    );

                    return $this->component_settings;
                }

            function set_module_components_description( $component_settings )
                {
                    foreach ( $component_settings as $component_key => $component_setting )
                        {
                            if ( ! isset ( $component_setting['id'] ) )
                                continue;
                            
                            switch ( $component_setting['id'] )
                                {
                                    case 'disable_wpemojia' :
                                        $component_setting = array_merge( $component_setting, array(
                                            'label'         => __('Disable Emoji', 'wp-hide-security-enhancer'),
                                            'description'   => __('Disable the Emoji icon library from being loaded.', 'wp-hide-security-enhancer'),
                                            'help'          => array(
                                                'title'                     => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('Disable Emoji', 'wp-hide-security-enhancer'),
                                                'description'               => __("Not everyone uses Emoji. Since WordPress loads the dependencies by default, it decreases the overall site speed. Disabling this will remove any code and related resources from being loaded on the front side.", 'wp-hide-security-enhancer'),
                                                'option_documentation_url'  => 'https://www.wp-hide.com/documentation/general-html-emoji/'
                                            ),
                                            'options'       => array(
                                                'no'  => __('No', 'wp-hide-security-enhancer'),
                                                'yes' => __('Yes', 'wp-hide-security-enhancer')
                                            )
                                        ) );
                                        break;

                                    case 'disable_tinymce_wpemojia' :
                                        $component_setting = array_merge( $component_setting, array(
                                            'label'         => __('Disable TinyMCE Emoji', 'wp-hide-security-enhancer'),
                                            'description'   => __('Disable the TinyMCE Emoji icons library from being loaded into TinyMCE.', 'wp-hide-security-enhancer'),
                                            'help'          => array(
                                                'title'                     => __('Help', 'wp-hide-security-enhancer') . ' - ' . __('Disable TinyMCE Emoji', 'wp-hide-security-enhancer'),
                                                'description'               => __("Disable TinyMCE Emoji. This is also loaded along with the WordPress default TinyMCE editor, but it can be disabled through this option.", 'wp-hide-security-enhancer'),
                                                'option_documentation_url'  => 'https://www.wp-hide.com/documentation/general-html-emoji/'
                                            ),
                                            'options'       => array(
                                                'no'  => __('No', 'wp-hide-security-enhancer'),
                                                'yes' => __('Yes', 'wp-hide-security-enhancer')
                                            )
                                        ) );
                                        break;
                                }

                            $component_settings[ $component_key ] = $component_setting;
                        }

                    return $component_settings;
                }

                
                
                
            function _init_disable_wpemojia($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    if ( ! $this->ReInit )    
                        add_action( 'init', array($this, 'disable_emojicons' ));
                }
                
            
            function disable_emojicons()
                {
                    remove_action( 'admin_print_styles', 'print_emoji_styles' );
                    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
                    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
                    remove_action( 'wp_print_styles', 'print_emoji_styles' );
                    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
                    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
                    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
                }
                    

            function _init_disable_tinymce_wpemojia($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    if ( ! $this->ReInit )    
                        add_action( 'init', array($this, 'disable_tinymce_emojicons' )); 
                }
            
            
            function disable_tinymce_emojicons()    
                {
                    add_filter( 'tiny_mce_plugins', array($this, 'disable_emojicons_tiny_mce_plugins') );   
                }
                
            
            function disable_emojicons_tiny_mce_plugins( $plugins )
                {
                    return array_diff( $plugins, array( 'wpemoji' ) );
                }
        }
?>