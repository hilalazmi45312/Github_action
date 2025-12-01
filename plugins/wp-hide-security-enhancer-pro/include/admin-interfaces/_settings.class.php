<?php   
        
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    class WPH_interface_settings
        {
            var $wph;
            var $functions;
            var $current_tab_slug               =   FALSE;
            
            var $menu_items                     =   array();
                
            function __construct()
                {
                    global $wph;
                    
                    $this->wph  =   $wph;
                    $this->functions    =   $wph->functions;
                    
                    $this->current_tab_slug     =   isset( $_GET['option'] )   ?   preg_replace( '/[^a-zA-Z0-9\-\_$]/m' , "", $_GET['option'] )  :   'license';
                    
                    if(  ! $wph->licence->licence_key_verify() )
                        $this->current_tab_slug =   'license';
                    
                    $this->menu_items           =   array ( 
                                                            'license'       =>  array (
                                                                                        'title' =>  __('License', 'wp-hide-security-enhancer'),
                                                                                        'html'  =>  '_html_license'
                                                                                    ), 
                                                            'general'       =>  array (
                                                                                        'title' =>  __('General', 'wp-hide-security-enhancer'),
                                                                                        'html'  =>  'html_general'
                                                                                    ),
                                                            
                                                            /*
                                                            'sample-setup' =>  array (
                                                                                        'title' =>  __('Sample Setup', 'wp-hide-security-enhancer'),
                                                                                        'html'  =>  'html_sample_setup'
                                                                                        ),
                                                            */
                                                            'import-export' =>  array (
                                                                                        'title' =>  __('Import / Export', 'wp-hide-security-enhancer'),
                                                                                        'html'  =>  'html_import_export'
                                                                                        ),
                                                            'recovery'      =>  array (
                                                                                        'title' =>  __('Recovery', 'wp-hide-security-enhancer'),
                                                                                        'html'  =>  'html_recovery'
                                                                                        ),
                                                            'data-collection' =>  array (
                                                                                        'title' =>  __('Data Collection', 'wp-hide-security-enhancer'),
                                                                                        'html'  =>  'html_data_collection'
                                                                                        ) 
                                                            );
                        
                }
                
                
            /**
            * Process the interface fields after Save
            * 
            */
            function interface_process()
                {
                    
                    $nonce  =   $_POST['wph-interface-nonce'];
                    if ( ! wp_verify_nonce( $nonce, 'wph/interface_options' ) )
                        return FALSE;
                    
                    //only for admins
                    If ( !  current_user_can ( 'manage_options' ) )
                        return FALSE;
                        
                    $screen_page_slug  =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m' , "", $_GET['page'] );
                    if( empty ( $screen_page_slug ) )
                        return FALSE;
                    
                    $global_settings    =   $this->functions->get_global_settings ( );
                    
                    if ( is_multisite() &&  is_network_admin() )
                        {
                            $global_settings['self_setup']                      =   isset($_POST['self_setup'])   &&   $_POST['self_setup']   ==  'yes'  ?   'yes'  :   'no';
                            $global_settings['covert_relative_urls_to_absolute']=   isset($_POST['covert_relative_urls_to_absolute'])   &&   $_POST['covert_relative_urls_to_absolute']   ==  'yes'  ?   'yes'  :   'no';
                                                                                
                            $settings           =   $this->functions->get_site_settings ( 'network' );
                            $previous_settings  =   $settings;
                            
                            $global_settings['nginx_generate_simple_rewrite']       =   isset($_POST['nginx_generate_simple_rewrite'])   &&   $_POST['nginx_generate_simple_rewrite']   ==  'yes'  ?   'yes'  :   'no';
                            $global_settings['nginx_save_rewrite_to_file']          =   isset($_POST['nginx_save_rewrite_to_file'])   &&   $_POST['nginx_save_rewrite_to_file']   ==  'yes'  ?   'yes'  :   'no';
                            $global_settings['nginx_headers_save_rewrite_to_file']  =   isset($_POST['nginx_headers_save_rewrite_to_file'])   &&   $_POST['nginx_headers_save_rewrite_to_file']   ==  'yes'  ?   'yes'  :   'no';
                            delete_site_option( 'wph-errors-nginx_rewrites_to_file' );
                            
                            $this->functions->update_site_settings( $settings, 'network' );
                            $this->functions->update_global_settings( $global_settings ); 
                        }
                        else
                        {
                            $global_settings['self_setup']                      =   isset($_POST['self_setup'])   &&   $_POST['self_setup']   ==  'yes'  ?   'yes'  :   'no';
                            
                            $global_settings['covert_relative_urls_to_absolute']=   isset($_POST['covert_relative_urls_to_absolute'])   &&   $_POST['covert_relative_urls_to_absolute']   ==  'yes'  ?   'yes'  :   'no';
                            
                            $global_settings['nginx_generate_simple_rewrite']       =   isset($_POST['nginx_generate_simple_rewrite'])   &&   $_POST['nginx_generate_simple_rewrite']   ==  'yes'  ?   'yes'  :   'no';
                            $global_settings['nginx_save_rewrite_to_file']          =   isset($_POST['nginx_save_rewrite_to_file'])   &&   $_POST['nginx_save_rewrite_to_file']   ==  'yes'  ?   'yes'  :   'no';
                            $global_settings['nginx_headers_save_rewrite_to_file']  =   isset($_POST['nginx_headers_save_rewrite_to_file'])   &&   $_POST['nginx_headers_save_rewrite_to_file']   ==  'yes'  ?   'yes'  :   'no';
                            delete_site_option( 'wph-errors-nginx_rewrites_to_file' );
                            
                            $this->functions->update_global_settings( $global_settings ); 
                        }
                        
                    $this->wph->admin_interface->interface_save_redirect( $screen_page_slug, '', 'option=' .  $this->current_tab_slug .'&settings_updated=true');
                    
                }
                
                
            function interface_import_process()
                {
                    $nonce  =   $_POST['wph-interface-nonce'];
                    if ( ! wp_verify_nonce( $nonce, 'wph/interface_options' ) )
                        return FALSE;
                    
                    //only for admins
                    If ( !  current_user_can ( 'manage_options' ) )
                        return FALSE;
                        
                    $screen_page_slug  =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m' , "", $_GET['page'] );
                    if( empty ( $screen_page_slug ) )
                        return FALSE;
                        
                    $this->_process_import();   
                    
                    $this->wph->admin_interface->interface_save_redirect( $screen_page_slug, '', 'option=' .  $this->current_tab_slug .'&settings_updated=true');
                }
                
                
            function interface_recovery_reset_process()
                {
                    $nonce  =   $_POST['wph-interface-nonce'];
                    if ( ! wp_verify_nonce( $nonce, 'wph/interface_options/recovery_reset' ) )
                        return FALSE;
                    
                    //only for admins
                    If ( !  current_user_can ( 'manage_options' ) )
                        return FALSE;
                        
                    $screen_page_slug  =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m' , "", $_GET['page'] );
                    if( empty ( $screen_page_slug ) )
                        return FALSE;
                        
                    
                    $this->wph->functions->reset_recovery_code();
                    
                    
                    $to         =   get_site_option('admin_email');  
                        
                    $subject    =   'New recovery link for your WordPress - ' .get_option('blogname');
                    $message    =   __('Hello',  'wp-hide-security-enhancer') . ", \n\n" 
                                    . __('This is an automated notification to let you know that your recovery URL has been successfully changed.',  'wp-hide-security-enhancer') . "\n"
                                    . __('You can access your recovery page using the following link: ',  'wp-hide-security-enhancer') .  trailingslashit ( home_url() ) . '?wph-recovery='.  $this->wph->functions->get_recovery_code() . "\n\n"
                                    . "\n\n"
                                    . __('Have a great day!.',  'wp-hide-security-enhancer') . "\n";
                    $headers = 'From: '.  get_option('blogname') .' <'.  get_option('admin_email')  .'>' . "\r\n"; 
                    
                    if ( ! function_exists( 'wp_mail' ) ) 
                        require_once ABSPATH . WPINC . '/pluggable.php';
                        
                    wp_mail( $to, $subject, $message, $headers ); 
                        
                    $process_interface_save_errors[]    =   array(
                                                                                        'type'      =>  'success',
                                                                                        'message'   =>  __('Recovery Code Successfully Reset.', 'wp-hide-security-enhancer')
                                                                                        );
     
                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                    
                    $this->wph->admin_interface->interface_save_redirect( $screen_page_slug, '', 'option=' .  $this->current_tab_slug .'&settings_updated=true'); 
                }
                
                
            /**
            * Process the license form
            *     
            */
            function interface_process_license_save()
                {

                    $nonce  =   $_POST['wph_license_nonce'];
                    if ( ! wp_verify_nonce( $nonce, 'wph_licence' ) )
                        return FALSE;
                    
                    //only for admins
                    If ( !  current_user_can ( 'manage_options' ) )
                        return FALSE;
                        
                    $screen_slug  =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m' , "", $_GET['page'] );
                    if(empty($screen_slug))
                        return FALSE;
                    
                    $this->_process_license( );
                    
                    $this->wph->admin_interface->interface_save_redirect( $screen_slug, '', 'settings_updated=true');
                }
                
                
            private function _process_license()
                {
                    
                    //check for de-activation
                    if ( isset($_POST['wph_licence_deactivate']) && wp_verify_nonce($_POST['wph_license_nonce'],'wph_licence'))
                        {
                            $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                            
                            $licence_data   =   $this->wph->licence->get_licence_data();
                            $licence_key    =   $licence_data['key'];

                            //build the request query
                            $args = array(
                                                'woo_sl_action'         =>  'deactivate',
                                                'licence_key'           =>  $licence_key,
                                                'product_unique_id'     =>  WPH_PRODUCT_ID,
                                                'domain'                =>  WPH_INSTANCE
                                            );
                            $request_uri    = WPH_UPDATE_API_URL . '?' . http_build_query( $args , '', '&');
                            $data           = wp_remote_get( $request_uri );
                            
                            if(is_wp_error( $data ) || $data['response']['code'] != 200)
                                {
                                    $process_interface_save_errors[] =   array(
                                                                                'type'  =>  'error',
                                                                                'message'  =>  __('There was a problem connecting to ', 'wp-hide-security-enhancer') . WPH_UPDATE_API_URL);
                                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                    
                                    return;  
                                }
                                
                            $response_block = json_decode($data['body']);
                            $response_block = $response_block[count($response_block) - 1];
                            $response = $response_block->message;
                            
                            if(isset($response_block->status))
                                {
                                    if($response_block->status == 'success' && $response_block->status_code == 's201')
                                        {
                                            //the license is active and the software is active
                                            $process_interface_save_errors[] = array(
                                                                                    'type'  =>  'updated',
                                                                                    'message'  =>  $response_block->message);
                                            update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                                                                        
                                            //save the license
                                            $licence_data   =   $this->wph->licence->reset_licence_data( $licence_data );
                                            $licence_data['last_check']   = time();
                                            
                                            $this->wph->licence->update_licence_data( $licence_data );
                                        }
                                        
                                    else //if message code is e104  force de-activation
                                            if ( $response_block->status_code == 'e001' || $response_block->status_code == 'e002' || $response_block->status_code == 'e004' || $response_block->status_code == 'e104' || $response_block->status_code == 'e110')
                                                {                                           
                                                    //save the license
                                                    $licence_data   =   $this->wph->licence->reset_licence_data( $licence_data );
                                                    $licence_data['last_check']   = time();
                                                    
                                                    $this->wph->licence->update_licence_data( $licence_data );
                                                    
                                                    //delete the update transient
                                                    $this->delete_plugin_update_transient();
                                                }
                                        else
                                        {
                                            $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'message'  =>  __('There was a problem deactivating the licence: ', 'wp-hide-security-enhancer') . $response_block->message);
                                            update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                            
                                            return;
                                        }   
                                }
                                else
                                {
                                    $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'message'  => __('There was a problem with the data block received from ' . WPH_UPDATE_API_URL, 'wp-hide-security-enhancer'));
                                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                    return;
                                }
                                       
                        }   
                    
                    if ( isset($_POST['wph_licence_activate']) && wp_verify_nonce($_POST['wph_license_nonce'],'wph_licence'))
                        {
                            
                            $licence_key = isset($_POST['licence_key'])? sanitize_key(trim($_POST['licence_key'])) : '';

                            if($licence_key == '')
                                {
                                    $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                                    $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'message'  =>  __("Licence Key can't be empty", 'wp-hide-security-enhancer'));
                                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                    
                                    return;
                                }
                                
                            //build the request query
                            $args = array(
                                                'woo_sl_action'         => 'activate',
                                                'licence_key'           => $licence_key,
                                                'product_unique_id'     => WPH_PRODUCT_ID,
                                                'domain'                => WPH_INSTANCE
                                            );
                            $request_uri    = WPH_UPDATE_API_URL . '?' . http_build_query( $args , '', '&');
                            $data           = wp_remote_get( $request_uri );
                            if(is_wp_error( $data ) || $data['response']['code'] != 200)
                                {
                                    $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                                    $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'message'  =>  __('There was a problem connecting to ', 'wp-hide-security-enhancer') . WPH_UPDATE_API_URL);
                                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                    
                                    return;  
                                }
                                
                            $response_block = json_decode($data['body']);
                            //retrieve the last message within the $response_block
                            $response_block = $response_block[count($response_block) - 1];
                            $response = $response_block->message;
                            
                            if(isset($response_block->status))
                                {
                                    if( $response_block->status == 'success' && ( $response_block->status_code == 's100' || $response_block->status_code == 's101' ) )
                                        {
                                            $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                                            //the license is active and the software is active
                                            $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'updated',
                                                                                    'message'  =>  $response_block->message);
                                            update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                            
                                            $licence_data   =   $this->wph->licence->get_licence_data();
                                            
                                            //save the license
                                            $licence_data['key']                = $licence_key;
                                            $licence_data['last_check']         = time();
                                            $licence_data['licence_status']     = isset( $response_block->licence_status ) ?    $response_block->licence_status :   ''  ;
                                            $licence_data['licence_expire']     = isset( $response_block->licence_expire ) ?    $response_block->licence_expire :   ''  ;
                                            
                                            //delete the update transient
                                            $this->delete_plugin_update_transient();
                                            
                                            $licence_data['activated']          = md5 ( $licence_key );
                                            
                                            $this->wph->licence->update_licence_data( $licence_data );
                                            
                                            delete_site_option(base64_decode('d3BoX2V4cGFuZA==' ));
                                        }
                                        else
                                        {
                                            $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                                            $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'message'  =>  __('There was a problem activating the licence: ', 'wp-hide-security-enhancer') . $response_block->message);
                                            update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                            
                                            return;
                                        }   
                                }
                                else
                                {
                                    $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                                    $process_interface_save_errors[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'message'  =>  __('There was a problem with the data block received from ' . WPH_UPDATE_API_URL, 'wp-hide-security-enhancer'));
                                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );
                                    
                                    return;
                                }
            
                        }   
                    
                }
                
                
            function delete_plugin_update_transient()
                {
                    global $wpdb;
                    
                    if ( is_multisite() )
                        $mysl_query =   "DELETE FROM " . $wpdb->sitemeta . " WHERE `meta_key` LIKE '%wphide-pro-check_for_plugin_update_%'";
                        else
                        $mysl_query =   "DELETE FROM " . $wpdb->options . " WHERE `option_name` LIKE '%wphide-pro-check_for_plugin_update_%'";
                        
                    $results    =   $wpdb->query( $mysl_query );
                    
                }
                
                
            private function _process_import()
                {
                    if  ( !isset($_POST['import_settings'])  ||  empty($_POST['import_settings']))
                        return;
                        
                    $import_data   =    json_decode(  stripslashes ( $_POST['import_settings'] ) , TRUE );
                    
                    if ( ! is_array ( $import_data ) )
                        {
                            $process_interface_save_errors[]    =   array(
                                                                        'type'      =>  'error',
                                                                        'message'   =>  __('Invalid import data.', 'wp-hide-security-enhancer')
                                                                        );
                        }    
                        else
                        {
                            array_walk_recursive($import_data, array($this->functions, "filter_htmlspecialchars_decode"));
                            
                            $process_interface_save_errors  =   (array)get_option( 'wph-interface-save-errors');
                            
                            if ( $import_data   ==  FALSE )
                                {
                                    $process_interface_save_errors[]    =   array(
                                                                                'type'      =>  'error',
                                                                                'message'   =>  __('Invalid import data.', 'wp-hide-security-enhancer')
                                                                                ); 

                                }
                                else
                                {
                                    
                                    /**
                                    * Make certain adjustments to the options to ensure they are not causing any issue after importing
                                    * 
                                    */
                                    //disable captcha
                                    $import_data['captcha_type']    =   array();
                                    $import_data['captcha_type']['captcha_type']    =   'disabled';
                                    
                                    if ( isset ( $import_data['document_loaded_assets_postprocessing'] ) )
                                        unset ( $import_data['document_loaded_assets_postprocessing'] );
                                    
                                    $import_data    =   apply_filters('wph/import/settings', $import_data );
                                    
                                    $blog_id_settings   =   $this->functions->get_blog_id();
                                    $settings           =   $this->functions->get_site_settings ( $blog_id_settings );
                                    
                                    foreach ( $import_data as $key  =>  $value )
                                        {
                                            $key    =   trim ( $key );
                                            if ( ! array ( $value ))
                                                $value  =   trim ( $value );
                                            $settings['module_settings'][$key]    =   $value;
                                        }
                                    $this->functions->update_site_settings($settings, $blog_id_settings);
                                    
                                    //trigger the settings changed action
                                    do_action('wph/settings_changed');
                                    
                                    $process_interface_save_errors[]    =   array(
                                                                                        'type'      =>  'warning',
                                                                                        'message'   =>  __('Import data sucesfully processed.', 'wp-hide-security-enhancer')
                                                                                        );
                                } 
                        }
                    
                    update_option( 'wph-interface-save-errors', $process_interface_save_errors );                    
                    
                }    
            
                
            function html_interface()
                {
                    global $wph;

                    
                                        
                    if ( is_multisite() )
                        $title =    __( "Network Settings", 'wp-hide-security-enhancer' );
                        else
                        $title =    __( "Settings", 'wp-hide-security-enhancer' );
                                    
                    ?>
                    <div id="wph"> 
                        <div id="wph-header">
                            <h1><img src="<?php echo WPH_URL ?>/assets/images/wph-dashboard-icon.png" />WP Hide & Security Enhancer <span class="plugin-mark">PRO</span> - <?php echo $title; ?></h1>
                        </div>
                        
                        <h2 class="nav-tab-wrapper">
                            <?php
                                
                                $admin_slug     =   is_multisite()  ?   'network-wp-hide'   :   'wp-hide-pro';
                                    
                                //output all module components as tabs
                                foreach( $this->menu_items   as  $menu_slug   =>  $menu_item )
                                    {                            
                                        $class  =   '';
                                        if( $menu_slug    ==  $this->current_tab_slug )
                                            $class  =   'wph-nav-tab-active';
                                            
                                        $class  .=   ' option-' . $menu_slug;
                                        
                                        $menu_option_link =   esc_url ( network_admin_url ( 'admin.php?page=' . $admin_slug . '&option=' . $menu_slug ) );
                                                     
                                        ?>   
                                        <a href="<?php echo $menu_option_link ?>" class="wph-nav-tab <?php echo $class ?>"><?php echo $menu_item['title'] ?></a>
                                        <?php                                    
                                    }
                            
                            ?>
                        </h2>
                        
                        <div id="wph-notices" class="no-wrap"></div>
                        
                        <div class="wrap">
                            <p>&nbsp</p>
                            <?php 
                            
                            $current_menu_item  =   $this->menu_items[ $this->current_tab_slug ];
                            call_user_func( array( $this, $current_menu_item['html'] ) );
                            
                            ?>
                        </div>
                    </div>
                    <?php   
                }
                
                
            function _html_license()
                {
                    if( !   $this->wph->licence->licence_key_verify() || $this->wph->expanded() )
                        include( WPH_PATH . 'include/admin-interfaces/_licence.php' );
                        else
                        include( WPH_PATH . 'include/admin-interfaces/_licence_deactivate.php' );
                        
                    ?>
                    
                    <div class="help-box">
                        <p><span class="dashicons dashicons-editor-help"></span> The license key is crucial for ensuring access to updates, support, and premium features. By entering the license key, the plugin verifies its authenticity with the developer's server, allowing it to function fully. </p>
                        <p>Once licensed, the plugin receives regular updates, including security patches and new features, which are essential for maintaining compatibility and performance. Additionally, the license grants access to technical support, ensuring users can resolve issues efficiently. </p>
                        <p>Deactivating the license key, typically used when moving the license to a new site or installation, disable updates and support for previous domain.</p>
                    </div>
                    <?php    
                }
                
                
            function html_general()    
                {
                    $global_settings    =   $this->wph->functions->get_global_settings ( );
                    
                    ?>
                    <h3><?php _e( "General Settings", 'wp-hide-security-enhancer' ) ?></h3>
                    <form id="form_data" name="form" method="post">
                        <input type="hidden" name="wph-interface-options" value="true" />
                        <?php wp_nonce_field( 'wph/interface_options', 'wph-interface-nonce' ); ?>   
                        <table class="form-table">
                            <tbody>
                                
                                <?php  if ( $this->wph->server_nginx_config   === TRUE ) {   ?>
                                <tr valign="top">
                                    <th scope="row">
                                        <select id="nginx_generate_simple_rewrite"  name="nginx_generate_simple_rewrite" <?php if ( $this->wph->functions->server_is_wpengine() ||   $this->wph->functions->server_is_kinsta() )  { ?>disabled="disabled"<?php } ?>>
                                            <option value="no" <?php selected('no', $global_settings['nginx_generate_simple_rewrite']); ?>><?php _e( "No", 'wp-hide-security-enhancer' ) ?></option>
                                            <option value="yes" <?php selected('yes', $global_settings['nginx_generate_simple_rewrite']); ?>><?php _e( "Yes", 'wp-hide-security-enhancer' ) ?></option>
                                        </select>
                                    </th>
                                    <td>
                                        <label for="nginx_generate_simple_rewrite"><b><?php _e( "Generate simple Rewrite Rules for Nginx.", 'wp-hide-security-enhancer' ) ?> <?php if ( $this->wph->functions->server_is_wpengine()  ||  $this->wph->functions->server_is_kinsta())  { ?><span class="warning">You use <?php if ( $this->wph->functions->server_is_wpengine() ) { echo 'WPEngine';} if ( $this->wph->functions->server_is_kinsta() ) { echo 'Kinsta';} ?> which require simple rewrite.</span><?php } ?></b></label>
                                        <p><?php _e( "Not all Nginx servers are configured to handle the full complexity of rewrite rules recommended by developers, as outlined in the guide at ", 'wp-hide-security-enhancer' ) ?> <a href="https://www.nginx.com/blog/creating-nginx-rewrite-rules/" target="_blank"><?php _e( "NGINX blog", 'wp-hide-security-enhancer' ) ?></a>.  <?php _e( "In scenarios where servers have limitations or custom configurations, this option enables the generation of a simplified version of rewrite rules.", 'wp-hide-security-enhancer' ) ?></p>
                                        <p><?php _e( "These simplified rules are less resource-intensive and easier to process, ensuring compatibility across a broader range of server environments. It's important to note that few servers usually support full rewrite format. This option is ideal for maintaining performance on lightweight or restricted servers.", 'wp-hide-security-enhancer' ) ?></p>
                                    </td>
                                </tr>
                                <?php } $this->wph->interface_expand(); ?>
                                
                                <?php  if ( $this->wph->server_nginx_config   === TRUE ) {   ?>
                                <tr valign="top">
                                    <th scope="row">
                                        <select name="nginx_save_rewrite_to_file" id="nginx_save_rewrite_to_file">
                                            <option value="no" <?php selected('no', $global_settings['nginx_save_rewrite_to_file']); ?>><?php _e( "No", 'wp-hide-security-enhancer' ) ?></option>
                                            <option value="yes" <?php selected('yes', $global_settings['nginx_save_rewrite_to_file']); ?>><?php _e( "Yes", 'wp-hide-security-enhancer' ) ?></option>
                                        </select>
                                    </th>
                                    <td>
                                        <label for="nginx_save_rewrite_to_file"><b><?php _e( "Save the Nginx Rewrites to local configuration files.", 'wp-hide-security-enhancer' ) ?></b></label>
                                        <p><?php _e( "This option allows you to automatically save the dynamically generated Nginx rewrite rules into two dedicated files: <code>wphide-nginx.conf</code> and <code>wphide-firewall-nginx.conf</code>. These files will be stored directly inside the site's root directory, specifically designed to handle URL rewriting and firewall rule management.", 'wp-hide-security-enhancer' ) ?></p>
                                        <p><?php _e( "By enabling this, the Nginx configuration process becomes streamlined and more organized, as the rules are neatly encapsulated in separate files. This setup enhances security by ensuring firewall rules are properly managed and also allows for easier troubleshooting and maintenance of Nginx server configurations.", 'wp-hide-security-enhancer' ) ?></p>
                                    </td>
                                </tr>
                                <!--
                                <tr valign="top">
                                    <th scope="row">
                                        <select name="nginx_headers_save_rewrite_to_file" id="nginx_headers_save_rewrite_to_file">
                                            <option value="no" <?php selected('no', $global_settings['nginx_headers_save_rewrite_to_file']); ?>><?php _e( "No", 'wp-hide-security-enhancer' ) ?></option>
                                            <option value="yes" <?php selected('yes', $global_settings['nginx_headers_save_rewrite_to_file']); ?>><?php _e( "Yes", 'wp-hide-security-enhancer' ) ?></option>
                                        </select>
                                    </th>
                                    <td>
                                        <label for="nginx_headers_save_rewrite_to_file"><b><?php _e( "Save the Nginx Rewrite Headers to a wphide-nginx-headers.conf file inside the site root ( Nginx ).", 'wp-hide-security-enhancer' ) ?></b></label>
                                        <p><?php _e( "When enabled, dynamically generated Nginx rewrite headers will be automatically inserted into a file named wphide-nginx-headers.conf located in the root directory of the site. If this option is disabled, the rewrite rules will instead be incorporated into the default wphide-nginx.conf file.", 'wp-hide-security-enhancer' ) ?></p>
                                    </td>
                                </tr>
                                -->
                                <?php } ?>
                     
                                <tr valign="top">
                                    <th scope="row">
                                        <select name="self_setup" id="self_setup">
                                            <option value="no" <?php selected('no', $global_settings['self_setup']); ?>><?php _e( "No", 'wp-hide-security-enhancer' ) ?></option>
                                            <option value="yes" <?php selected('yes', $global_settings['self_setup']); ?>><?php _e( "Yes", 'wp-hide-security-enhancer' ) ?></option>
                                        </select>
                                    </th>
                                    <td>
                                        <label for="self_setup"><b><?php _e( "I'll set-up the rewrite data myself ( Apache ).", 'wp-hide-security-enhancer' ) ?></b></label>
                                        <p><?php _e( "This option allows you full control over the rewrite rules by configuring them manually on your Apache server. Selecting this option prevents the application from making any automatic changes to your server's rewrite rules. This is ideal if you have a custom server configuration or specific rewrite rules you want to manage yourself.", 'wp-hide-security-enhancer' ) ?></p>
                                        <p><?php _e( "Typically, the plugin will attempt to handle rewrite rules automatically when using mod_rewrite for Apache or IIS rewrite for Windows servers, but this option is for advanced users who prefer manual intervention. Make sure you have experience managing server configurations before proceeding.", 'wp-hide-security-enhancer' ) ?></p>
                                    </td>
                                </tr>
                                
                                <tr valign="top">
                                    <th scope="row">
                                        <select name="covert_relative_urls_to_absolute" id="covert_relative_urls_to_absolute">
                                            <option value="no" <?php selected('no', $global_settings['covert_relative_urls_to_absolute']); ?>><?php _e( "No", 'wp-hide-security-enhancer' ) ?></option>
                                            <option value="yes" <?php selected('yes', $global_settings['covert_relative_urls_to_absolute']); ?>><?php _e( "Yes", 'wp-hide-security-enhancer' ) ?></option>
                                        </select>
                                    </th>
                                    <td>
                                        <label for="covert_relative_urls_to_absolute"><b><?php _e( "Convert Relative URLs to Absolute URLs.", 'wp-hide-security-enhancer' ) ?></b></label>
                                        <p><?php _e( "Enabling this option converts all relative URLs within your site to absolute URLs. Relative URLs, which are often used to reference files or pages relative to the current domain or path, may sometimes lead to inconsistencies in URL changes or conflicts with plugins and redirects. By converting them to absolute URLs, you ensure that all references are fully qualified and consistent, which can help avoid potential issues with link structure and content delivery.", 'wp-hide-security-enhancer' ) ?></p>
                                        <p><?php _e( "However, if your site is functioning properly with relative URLs, it's recommended to leave this option off to avoid unnecessary changes. This option should be used if you're encountering URL-related issues, otherwise, it's best to keep it disabled.", 'wp-hide-security-enhancer' ) ?></p>
                                    </td>
                                </tr>
                            
                            </tbody>
                        </table>
                                        
                        <p class="submit">
                            <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Settings', 'wp-hide-security-enhancer') ?>">
                        </p>
                        
                    </form>   
                    <?php
                }
            
            
            
            function html_sample_setup()    
                {
                    $blog_id_settings   =   $this->wph->functions->get_blog_id();
                    $settings           =   $this->wph->functions->get_site_settings ( $blog_id_settings );
                    
                    ?>
                    <h3><?php _e( "Sample Setup", 'wp-hide-security-enhancer' ) ?></h3>
                    <form id="form_data" name="form" method="post">
                        <input type="hidden" name="wph-interface-options" value="true" />
                        <?php wp_nonce_field( 'wph/interface_options', 'wph-interface-nonce' ); ?>   
                        <table class="form-table">
                            <tbody>
                                
         
                            
                            </tbody>
                        </table>
                                        
                        <p class="submit">
                            <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Settings', 'wp-hide-security-enhancer') ?>">
                        </p>
                        
                    </form>   
                    <?php
                }
                
                
            
            function html_import_export()    
                {
                    $blog_id_settings   =   $this->wph->functions->get_blog_id();
                    $settings           =   $this->wph->functions->get_site_settings ( $blog_id_settings );
                    
                    ?>
                    <h3><?php _e( "Import / Export", 'wp-hide-security-enhancer' ) ?></h3>
                    
                        <table class="form-table">
                            <tbody>
                                
                                <tr valign="top">
                                    <th scope="row">
                                        <a class="button" href="javascript: void(0)" onClick="jQuery('#export_settings').toggle('fast')"><?php _e( "Export Settings", 'wp-hide-security-enhancer' ) ?></a>
                                    </th>
                                    <td>
                                        <p><label><b><?php _e( "Export current settings", 'wp-hide-security-enhancer' ) ?></b></label></p>
                                        <p><?php _e( "This option allows you to export your current configuration settings. By selecting this, you can generate and copy to save on a local file, all the customized settings and preferences. This is useful for backing up your settings or transferring them to another installation.", 'wp-hide-security-enhancer' ) ?></p>
                                        <!-- WPH Preserve - Start -->
                                        <p><textarea onclick="this.focus();this.select()" id="export_settings" class="code" readonly="readonly" style="width: 100%; display: none" rows="12"><?php  
                                            
                                            $output_settings    =   $this->wph->functions->prepare_options_for_export ( $settings['module_settings'] );
                                            
                                            echo htmlspecialchars( json_encode( $output_settings ) )  
                                            
                                            ?></textarea></p>
                                        <!-- WPH Preserve - Stop -->
                                    </td>
                                </tr>
                        
                                <tr valign="top">
                                    <th scope="row">
                                        <a class="button" href="javascript: void(0)" onClick="jQuery('#import_settings').toggle('fast')"><?php _e( "Import Settings", 'wp-hide-security-enhancer' ) ?></a>
                                    </th>
                                    <td>
                                        <p><label><b><?php _e( "Import previously saved settings", 'wp-hide-security-enhancer' ) ?></b></label></p>
                                        <p><?php _e( "Use this option to import the data of a previously saved setup. Simply paste the data content, and it will restore the configuration as per the exported settings. This feature is especially handy when migrating to a new setup or recovering from a previous configuration backup.", 'wp-hide-security-enhancer' ) ?></p>
                                        <div id="import_settings" style="width: 100%; display: none">
                                            <form id="form_data" name="form" method="post">
                                                <input type="hidden" name="wph-interface-options-import-export" value="true" />
                                                <?php wp_nonce_field( 'wph/interface_options', 'wph-interface-nonce' ); ?>   
                                                <p><textarea class="code" name="import_settings"  rows="12" style="width: 100%;"></textarea></p>
                                                <p class="submit">
                                                    <input type="submit" name="Submit" class="button-primary" value="<?php _e('Import Settings', 'wp-hide-security-enhancer') ?>">
                                                </p>
                                            </form>   
                                        </div>
                                    </td>
                                </tr>
                            
                            </tbody>
                        </table>

                    <?php
                }
                
            
            function html_recovery()    
                {
                    $blog_id_settings   =   $this->wph->functions->get_blog_id();
                    $settings           =   $this->wph->functions->get_site_settings ( $blog_id_settings );
                    
                    ?>
                        <h3><?php _e( "Recovery", 'wp-hide-security-enhancer' ) ?></h3>
                        <div class="help-box">
                            <p><span class="dashicons dashicons-editor-help"></span> <?php _e( "To ensure you can restore your plugin settings if something goes wrong, make sure to copy the following link and store it in a secure location. This link provides a way to reset all plugin options to their default state. It's a crucial safety measure to prevent loss of configuration and to facilitate troubleshooting.",    'wp-hide-security-enhancer') ?></p>
                            <p><?php _e( "Keeping this link safe ensures that you can easily revert to the original settings without manual intervention. This recovery link is your backup plan, providing peace of mind and quick restoration in case of any issues with the plugin's functionality.",    'wp-hide-security-enhancer') ?></p>
                        </div>
                        <br /> <b><span id="wph-recovery-link" onClick="WPH.selectText( 'wph-recovery-link' )"><?php echo trailingslashit ( site_url() ) ?>?wph-recovery=<?php  echo $this->wph->functions->get_recovery_code() ?></span></b></p>
                        <form id="form_data" name="form" method="post">
                            <input type="hidden" name="wph-interface-options-recovery-reset" value="true" />
                            <?php wp_nonce_field( 'wph/interface_options/recovery_reset', 'wph-interface-nonce' ); ?>   
                            <p class="submit">
                                <a href="javascript: void(0);" onclick="wph_setting_page_recovery_reset_confirmation ();" class="button-primary button-red"><?php _e('Reset Recovery Code', 'wp-hide-security-enhancer') ?></a>
                            </p>
                            <script type='text/javascript'>
                                function wph_setting_page_recovery_reset_confirmation () 
                                    {
                                        var agree   =   confirm ( '<?php _e('Are you sure you want to reset the recovery code? A new code will be generated, and the previous link will no longer be valid.',    'wp-hide-security-enhancer') ?>' );
                                        if (!agree)
                                            return false;
                                            
                                        jQuery ('form#form_data' ).submit();
                                    }
                                
                            </script>
                        </form>
                           
                    <?php
                }
                
                
            function html_data_collection()    
                {
                    
                    ?>
                    <form id="form_data" name="form" method="post">
                        <?php wp_nonce_field( 'wp-hide-cache-clear', '_wpnonce' ); ?>
                        <input type="hidden" name="wph-cache-clear" value="true" />
                         
                        <h3><?php _e( "Data Collection Status", 'wp-hide-security-enhancer' ) ?></h3>
                        
                        <div class="help-box">
                        <p><span class="dashicons dashicons-editor-help"></span><?php _e( "The Data Collection represents a compilation of post-processed assets that are utilized internally within your system. These assets are generated when you enable CSS and/or JavaScript Post-Processing options.", 'wp-hide-security-enhancer' ) ?></p>
                        <p><?php _e( "The purpose of Data Collection is to optimize and enhance performance by storing processed assets, which helps in speeding up subsequent loading times and ensuring consistent behavior of your site's styling and functionality.", 'wp-hide-security-enhancer' ) ?></p>
                        <p><?php _e( "Important Note:", 'wp-hide-security-enhancer' ) ?></p>
                        

                            <ul>
                                <li><?php _e( "<b>Clearing Data Collection:</b> It is generally not necessary to clear the Data Collection. However, if you encounter any issues such as layout inconsistencies or broken design elements on your site, clearing this data might resolve such problems.", 'wp-hide-security-enhancer' ) ?></li>

                                <li><?php _e( "<b>Action Steps:</b> If you suspect that outdated or corrupted assets are affecting your site's appearance or functionality, you can clear the Data Collection from this panel. After clearing, the system will regenerate the necessary assets, which might resolve any issues related to layout or performance.", 'wp-hide-security-enhancer' ) ?></li>
                            </ul>
                        <p><?php _e( "For ongoing maintenance or troubleshooting, refer to our documentation.", 'wp-hide-security-enhancer' ) ?></p>
                        </div>
                        <p>&nbsp;</p>
                        <p><?php _e( "Data Collection size is", 'wp-hide-security-enhancer' ) ?> <b><?php printf( _n( '%s file', '%s files', $this->wph->functions->get_cache_size(), 'wp-hide-security-enhancer' ), number_format_i18n( $this->wph->functions->get_cache_size() ) ); ?></b></p>
                        <a class="button" href="javascript: void(0)" onclick="jQuery(this).closest('form').submit();"><?php _e( "Data Collection Clear", 'wp-hide-security-enhancer' ) ?></a>
                    </form>
                           
                    <?php
                }
            
        }
    
       