<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_component_login_2fa
        {            
            
            var $active_2fa_options     =   array();
            
            private $core_auth_tokens   =   array();
            
            var $wph;
            
            function __construct()
                {
                    global $wph;
                    $this->wph  =   $wph;
                    
                    add_action( 'init',                             array( $this, 'set_active_options' ) );
                    
                    add_action( 'show_user_profile',                array( $this, 'profile_2fa_options' ) );
                    add_action( 'edit_user_profile',                array( $this, 'profile_2fa_options' ) );
                    add_action( 'woocommerce_edit_account_form',    array( $this, 'profile_2fa_options' ) );
                    add_action( 'personal_options_update',          array( $this, 'profile_2fa_options_update' ) );
                    add_action( 'edit_user_profile_update',         array( $this, 'profile_2fa_options_update' ) );
                    add_action( 'woocommerce_save_account_details', array( $this, 'profile_2fa_options_update' ) );
                           
                    add_action( 'wp_login',                         array( $this, 'wp_login' ),                     10, 2 );
                    
                    add_action( 'set_auth_cookie',                  array( $this, 'store_auth_token' ) );
                    add_action( 'set_logged_in_cookie',             array( $this, 'store_auth_token' ) );

                    add_filter( 'authenticate',                     array( $this, 'authenticate_block_cookies' ),   9999 );
                    
                    add_action( 'login_form_validate_2fa',          array( $this, 'login_form_validate_2fa' ) );
                    
                    add_shortcode( 'wph-2fa-user-settings',         array( $this, 'shortcode_2fa_user_settings') );

                }
                
            
            function set_active_options()
                {
                    $TFA_option_2fa_email           =   $this->wph->functions->get_site_module_saved_value('2fa_email',             $this->wph->functions->get_blog_id_setting_to_use());
                    if ( $TFA_option_2fa_email  ==  'yes' )
                        $this->active_2fa_options['2fa_email'] =   new WPH_module_component_login_2fa_email();
                    
                    $TFA_option_2fa_app             =   $this->wph->functions->get_site_module_saved_value('2fa_app',               $this->wph->functions->get_blog_id_setting_to_use());
                    if ( $TFA_option_2fa_app  ==  'yes' )
                        $this->active_2fa_options['2fa_app'] =   new WPH_module_component_login_2fa_app();
                        
                    $TFA_option_2fa_recovery_codes  =   $this->wph->functions->get_site_module_saved_value('2fa_recovery_codes',    $this->wph->functions->get_blog_id_setting_to_use());
                    if ( $TFA_option_2fa_recovery_codes  ==  'yes' )
                        $this->active_2fa_options['2fa_recovery_codes'] =   new WPH_module_component_login_2fa_recovery_codes();
                    
                    $TFA_option_2fa_primary         =   $this->wph->functions->get_site_module_saved_value('2fa_primary',           $this->wph->functions->get_blog_id_setting_to_use());
                    if ( isset ( $this->active_2fa_options[ $TFA_option_2fa_primary ] ) )
                        {
                            $_option    =   $this->active_2fa_options[ $TFA_option_2fa_primary ];
                            unset ( $this->active_2fa_options[ $TFA_option_2fa_primary ] );
                            $this->active_2fa_options   =   array_merge ( array ( $TFA_option_2fa_primary   =>  $_option ), $this->active_2fa_options );
                        }
                }
            
            
            /**
            * Check if using a 2FA
            * 
            */
            function is_using_2fa()
                {
                    $_2fa_enabled           =   $this->wph->functions->get_site_module_saved_value('2fa_enabled',  $this->wph->functions->get_blog_id_setting_to_use());
                    if ( $_2fa_enabled !== 'yes' )
                        return FALSE;
                        
                    return TRUE;
                    
                }
            
            
            
            /**
            * Check if using a 2FA option
            * 
            */
            function is_using_2fa_option()
                {
                    if  ( count ( $this->active_2fa_options ) > 0 )
                        return TRUE;
                        
                    return FALSE;
                    
                }
            
            
            
            /**
            * Check if the 2fa is active for the current user role
            * 
            */
            function is_active_for_role( $user  )
                {
                    $_2fa_enable_for_roles  =   $this->wph->functions->get_site_module_saved_value('2fa_enable_for_roles',  $this->wph->functions->get_blog_id_setting_to_use());
                    
                    if ( ! $user instanceof WP_User )
                        return FALSE;
                    
                    if ( ! is_array ( $_2fa_enable_for_roles ) )
                        return FALSE;
                    
                    $user_roles =   $user->roles;
                    
                    if ( empty ( $user_roles ) )
                        $user_roles[]   =   'no-role';
                    
                    foreach ( $user_roles   as  $user_role )     
                        {
                            if ( array_search ( $user_role, $_2fa_enable_for_roles )    !== FALSE )
                                return TRUE;
                        }
                    
                    return FALSE;   
                }
                
                
            
            /**
            * Interact with the login action
            *     
            */
            function wp_login( $user_login, $user )
                {
                    if ( ! $this->is_using_2fa() ||  ! $this->is_using_2fa_option() || ! $this->is_active_for_role( $user ) )   
                        return;
                        
                    //allow other codes to bypass (e.g. Temporary Login Without Password)
                    if ( ! apply_filters( 'wp-hide/2fa/process_wp_login', TRUE, $user ) )
                        return;
                    
                    $this->remove_user_session( $user );

                    $this->prepare_HTML( $user );
                    
                    exit();
                }
            
                
            function prepare_HTML( $user )
                {
                    if ( ! $user )
                        $user = wp_get_current_user();
                    
                    $login_nonce = $this->create_login_nonce( $user->ID );
                    if ( ! $login_nonce )
                        wp_die( esc_html__( 'Unable to create a login nonce.', 'wp-hide-security-enhancer' ) );

                    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $redirect_to = isset( $_REQUEST['redirect_to'] ) ? wp_unslash ( $_REQUEST['redirect_to'] ) : admin_url();

                    $this->HTML( $user, $login_nonce['key'], $redirect_to );    
                }
                
                
            function HTML( $user, $nonce_key, $redirect_to, $error_msg = array(), $use_2fa_id = '' )
                {
                    
                    $_2fa_require_setup =   $this->wph->functions->get_site_module_saved_value('2fa_require_setup',  $this->wph->functions->get_blog_id_setting_to_use());   
                    
                    if ( empty ( $use_2fa_id ) )
                        {
                            reset ( $this->active_2fa_options );
                            $use_2fa_id     =   key ( $this->active_2fa_options );
                            
                            //check if required to set-up
                            if ( $_2fa_require_setup    ==  'yes' )
                                {
                                    $other_2fa_option_require_setup =   $this->_2fa_option_require_setup( $user->ID );
                                    if ( $other_2fa_option_require_setup    !== FALSE )
                                        $use_2fa_id =   $other_2fa_option_require_setup;
                                }
                            
                        }

                    $action =   'validate_2fa';
                        
                    $use_2fa_option =   $this->active_2fa_options[ $use_2fa_id ];

                    $additional_2fa_options    = array_diff_key( $this->active_2fa_options, array( $use_2fa_id => false ) );
                    $rememberme = isset( $_POST['rememberme'] ) && '' !== wp_unslash( $_POST['rememberme'] );

                    if ( ! function_exists( 'login_header' ) )
                        require_once WPH_PATH . 'modules/components/login_2fa_template_login_header.php';
                    
                    login_header();
                    
                    if ( ! empty( $error_msg ) )
                        {
                            reset ( $error_msg );
                            
                            $error_type     =   key ( $error_msg );
                            $error_message  =   current ( $error_msg );
                            $notice_strong  =   '';
                            if ( $error_type    ==  'error' )
                                $notice_strong  =   'Error:';
                            
                            echo '<div id="login_error" class="notice notice-' . $error_type .'"><p><strong>' .  $notice_strong .'</strong> ' . esc_html( $error_message ) . '</p></div>';
                        }

                    ?>
                        <form name="validate_2fa_form" id="loginform" action="<?php echo esc_url( $this->login_url( array( 'action' => $action ), 'login_post' ) ); ?>" method="post" autocomplete="off">
                            <input type="hidden" name="2fa_id"              id="2fa_id"         value="<?php echo esc_attr( $use_2fa_id ); ?>" />
                            <input type="hidden" name="2fa_user_id"         id="2fa_user_id"     value="<?php echo esc_attr( $user->ID ); ?>" />
                            <input type="hidden" name="2fa_nonce"           id="2fa_nonce"  value="<?php echo esc_attr( $nonce_key ); ?>" />
                            <input type="hidden" name="redirect_to"                         value="<?php echo esc_attr( $redirect_to ); ?>" />
                            <input type="hidden" name="rememberme"          id="rememberme"     value="<?php echo esc_attr( $rememberme ); ?>" />

                            <?php 
                                
                                $args   =   array ( 
                                                    '2fa_nonce'     =>  $nonce_key,
                                                    'redirect_to'   =>  $redirect_to
                                                    );
                                $use_2fa_option->login_page_HTML( $user, $args );
                                
                           
                                if ( $_2fa_require_setup == 'no'    &&  $use_2fa_option->user_require_setup( $user->ID ) ) 
                                    {
                                        if ( $use_2fa_id === '2fa_recovery_codes' )
                                            echo '<br />';
                                            
                                        echo '<input type="submit" name="submit" id="submit" class="button button-primary red leftalign" value="Setup Later">';
                                    }
                           
                            ?>
                            
                        </form>
                    <?php 
                    
                    if ( count ( $additional_2fa_options )  >   0 ) 
                        {
                            $link_args = array(
                                'action'        => $action,
                                '2fa_user_id'   => $user->ID,
                                '2fa_nonce'     => $nonce_key,
                            );
                            if ( $rememberme )
                                $link_args['rememberme'] = $rememberme;

                            if ( $redirect_to )
                                $link_args['redirect_to'] = $redirect_to;
                     
                            $_2fa_require_setup =   $this->wph->functions->get_site_module_saved_value('2fa_require_setup',  $this->wph->functions->get_blog_id_setting_to_use());
                            if ( $_2fa_require_setup == 'yes'   &&  $this->_2fa_option_require_setup( $user->ID )   !== FALSE )
                                {
                                    if ( $this->_2fa_option_require_setup( $user->ID, array ( $use_2fa_id ) )   !== FALSE )
                                        {
                                            ?>
                                            <div class="other_2fa_options">
                                                <p>
                                                    <?php esc_html_e( 'You must also complete the setup for the following options before logging in:', 'wp-hide-security-enhancer' ); ?>
                                                </p>
                                                <ul>
                                                    <?php
                                                    
                                                    foreach ( $this->active_2fa_options as  $active_2fa_option_id  =>  $_2fa_option )
                                                        {
                                                            if ( $_2fa_option->user_require_setup ( $user->ID ) &&  $use_2fa_id != $active_2fa_option_id )
                                                                {
                                                                    $link_args['2fa_id'] = $active_2fa_option_id;
                                                                    
                                                                    ?>
                                                                    <li>
                                                                        <a href="<?php echo esc_url( $this->login_url( $link_args ) ); ?>"><?php echo esc_html( $_2fa_option->get_other_label() ); ?></a>
                                                                    </li>
                                                                    <?php
                                                                }
                                                        }
                                                    ?>
                                                </ul>
                                            </div><?php   
                                        }
                                }
                                else
                                {
                                    ?>
                                    <div class="other_2fa_options">
                                        <p>
                                            <?php esc_html_e( 'Or use other available options:', 'wp-hide-security-enhancer' ); ?>
                                        </p>
                                        <ul>
                                            <?php
                                            foreach ( $this->active_2fa_options as $_2fa_key => $_2fa_option ) 
                                                {
                                                    if ( $_2fa_key   ==  $use_2fa_id )
                                                        continue;
                                                        
                                                    $link_args['2fa_id'] = $_2fa_key;
                                                ?>
                                                <li>
                                                    <a href="<?php echo esc_url( $this->login_url( $link_args ) ); ?>"><?php echo esc_html( $_2fa_option->get_other_label() ); ?></a>
                                                </li><?php 
                                                } 
                                            ?>
                                        </ul>
                                    </div><?php
                                }
                        }
                        
                    $this->HTML_dependencies(); 
                    
                    if ( ! function_exists( 'login_footer' ) )
                        require_once WPH_PATH . 'modules/components/login_2fa_template_login_footer.php';

                    login_footer();
                    
                    exit;
                    
                }
      
                
            
            /**
            * Output the page CSS and JavaScript dependencies
            *     
            */
            function HTML_dependencies()
                {
                    ?>

                    <style>
                        .other_2fa_options { margin-top: 16px;  padding: 0 24px;font-size: 13px;}
                        .other_2fa_options a {text-decoration: none;}
                        .other_2fa_options ul {padding-top: 10px; list-style-position: inside;}
                    </style>
                    <?php   
                }
            
            
            
            /**
            * Validate the login form entires fields
            * 
            */
            function login_form_validate_2fa() 
                {
                    if ( ! $this->is_using_2fa() )
                        return;
                    
                    $_2fa_user_id       = ! empty( $_REQUEST['2fa_user_id'] )   ? preg_replace( '/[^0-9a-zA-Z]/' , "",       wp_unslash( $_REQUEST['2fa_user_id'] ) )          : 0;
                    $_2fa_nonce         = ! empty( $_REQUEST['2fa_nonce'] )     ? preg_replace( '/[^0-9a-zA-Z]/' , "",      wp_unslash( $_REQUEST['2fa_nonce'] ) )            : '';
                    $_2fa_id            = ! empty( $_REQUEST['2fa_id'] )        ? preg_replace( '/[^0-9a-zA-Z_]/' , "",     wp_unslash( $_REQUEST['2fa_id'] ) )              : '';
                    $redirect_to        = ! empty( $_REQUEST['redirect_to'] )   ? wp_unslash( $_REQUEST['redirect_to'] )                                : '';
                    $is_post_request    = ( 'POST' === strtoupper( wp_unslash ( $_SERVER['REQUEST_METHOD'] ) ) );
                    $user               = get_user_by( 'id', $_2fa_user_id );
                    $submit             = ! empty( $_REQUEST['submit'] )   ? preg_replace( '/[^0-9a-zA-Z]/' , "",      wp_unslash( $_REQUEST['submit'] ) )     : '';  
                    
                    $rememberme = false;
                    if ( isset( $_REQUEST['rememberme'] ) && wp_unslash ( $_REQUEST['rememberme'] ) )
                        $rememberme = true;

                    if ( ! $_2fa_user_id || ! $_2fa_nonce ||    !   $_2fa_id    || ! $user )
                        return;

                    if ( true !== $this->verify_login_nonce( $user->ID, $_2fa_nonce ) ) 
                        {
                            wp_safe_redirect( $this->login_url() );
                            exit();
                        }
                        
                    //check if other 2fa options which require setup
                    $_2fa_require_setup             =   $this->wph->functions->get_site_module_saved_value('2fa_require_setup',  $this->wph->functions->get_blog_id_setting_to_use());
                    $other_2fa_option_require_setup =   $this->_2fa_option_require_setup( $user->ID ); 

                    if ( strtolower ( $submit )    ==  'setuplater' &&  $_2fa_require_setup !== 'yes'  )
                        {
                            //If there is an option with completed set-up ( except 2fa_recovery_codes ), use that
                            foreach ( $this->active_2fa_options as  $active_2fa_option_id  =>  $_2fa_option )
                                {
                                    if ( $active_2fa_option_id  ===  '2fa_recovery_codes' )
                                        continue;
                                        
                                    if ( $_2fa_option->user_require_setup ( $user->ID ) === FALSE   &&  $_2fa_id != $active_2fa_option_id )
                                        {
                                            $link_args = array(
                                                                'action'        =>  'validate_2fa',
                                                                '2fa_user_id'   =>  $user->ID,
                                                                '2fa_nonce'     =>  $_2fa_nonce,
                                                                'rememberme'    =>  $rememberme,
                                                                '2fa_id'        =>  $active_2fa_option_id
                                                            );
                                                    
                                            wp_safe_redirect( $this->login_url( $link_args ) );
                                            exit();   
                                        }
                                }
                            
                            //There are no other options with completed set-up, just continue to the dashboard. 
                            $args   =   array(
                                                'user_id'       =>  $user->ID,
                                                'rememberme'    =>  $rememberme,
                                                '2fa_id'        =>  $other_2fa_option_require_setup,
                                                'redirect_to'  =>  $redirect_to
                                            );
                            
                            $this->login_success( $args );
                            
                            exit;
                        }
                        
                    $use_2fa_option   =   isset ( $this->active_2fa_options[ $_2fa_id ] ) ?  $this->active_2fa_options[ $_2fa_id ]  :   FALSE;
                    if ( ! $use_2fa_option )
                        wp_die( __( 'Failed to locate the 2FA Option.', 'wp-hide-security-enhancer' ) );


                    $result = $this->process_2fa_option( $use_2fa_option, $user, $is_post_request );
                    if ( $result   !== TRUE )
                        {
                            $error = array();
                            if ( is_wp_error( $result ) ) 
                                {
                                    do_action( 'wp_login_failed', $user->user_login, $result );

                                    $error_type     =   $result->get_error_code();
                                    $error_message  =   $result->get_error_message();
                                    
                                    $error[ $error_type ]   =   $error_message;
                                }

                            $login_nonce = $this->create_login_nonce( $user->ID );
                            if ( ! $login_nonce )
                                wp_die( esc_html__( 'Failed to create a login nonce.', 'wp-hide-security-enhancer' ) );

                            $this->HTML( $user, $login_nonce['key'], $redirect_to, $error, $_2fa_id );
                            return;
                        }
                    
                    if ( $_2fa_require_setup    ==  'yes'   &&  $other_2fa_option_require_setup    !== FALSE )
                        {
                            $link_args = array(
                                                'action'        =>  'validate_2fa',
                                                '2fa_user_id'   =>  $user->ID,
                                                '2fa_nonce'     =>  $_2fa_nonce,
                                                'rememberme'    =>  $rememberme,
                                                '2fa_id'        =>  $other_2fa_option_require_setup
                                            );
                                    
                            wp_safe_redirect( $this->login_url( $link_args ) );
                            exit();
                        }
                    
                    $args   =   array(
                                                'user_id'       =>  $user->ID,
                                                'rememberme'    =>  $rememberme,
                                                '2fa_id'        =>  $other_2fa_option_require_setup,
                                                'redirect_to'  =>  $redirect_to
                                            );
                        
                    $this->login_success( $args );
                        
                    exit;
                }
                
                
            
            /**
            * The login successed
            * 
            */
            private function login_success( $args )
                {
                    delete_user_meta( $args['user_id'], '_2fa_nonce' );

                    wp_set_auth_cookie( $args['user_id'], $args['rememberme'] );

                    do_action( 'wp-hide/2fa/user_authenticated', $args['user_id'], $args['2fa_id'] );

                    $redirect_to    =   $args['redirect_to'];
                    
                    if ( empty ( $redirect_to ) )
                        $redirect_to    =   get_admin_url();
                    
                    wp_safe_redirect( $redirect_to );   
                }
          
    
    
            /**
            * Pocess the authentication on the 2fa option
            * 
            * @param mixed $_2fa_option
            * @param mixed $user
            * @param mixed $is_post_request
            * @return WP_Error
            */
            function process_2fa_option( $_2fa_option, $user, $is_post_request )
                {
                    if ( ! $_2fa_option ) 
                        return new WP_Error( 'error', __( 'Cheatin&#8217; uh?', 'wp-hide-security-enhancer' ));
                    
                    $response   =   $_2fa_option->before_process_authentication( $user );
                    if ( $response !==  TRUE )
                        return $response;

                    if ( ! $is_post_request )
                        return false;
 
                    $response   =   $_2fa_option->process_authentication( $user );
                    if ( $response !==     TRUE  )
                        return $response;

                    return TRUE;
                }
                
       
            /**
            * Return the first 2fa option id which require setup
            * 
            */
            function _2fa_option_require_setup( $user_ID, $exclude  =   array() )
                {
                    $use_2fa_id =   FALSE;
                    
                    foreach ( $this->active_2fa_options as  $active_2fa_option_id  =>  $active_2fa_option )
                        {
                            if ( array_search ( $active_2fa_option_id, $exclude )   === FALSE &&  $active_2fa_option->user_require_setup ( $user_ID ) )
                                {
                                    $use_2fa_id =   $active_2fa_option_id;
                                    break;
                                }
                        }
                        
                    return $use_2fa_id;
                }
       

            /**
            * Verify the login nonce
            * 
            * @param mixed $user_id
            * @param mixed $nonce
            */
            function verify_login_nonce( $user_id, $nonce ) 
                {
                    $login_nonce = get_user_meta( $user_id, '_2fa_nonce', true );

                    if ( ! $login_nonce || empty( $login_nonce['key'] ) || empty( $login_nonce['expiration'] ) )
                        return false;


                    $unverified_nonce = array(
                                                'user_id'    => $user_id,
                                                'expiration' => $login_nonce['expiration'],
                                                'key'        => $nonce,
                                            );

                    $unverified_hash = $this->hash_nonce( $unverified_nonce );
                    $hashes_match    = $unverified_hash && hash_equals( $login_nonce['key'], $unverified_hash );

                    if ( $hashes_match && time() < $login_nonce['expiration'] )
                        return true;


                    // Require a fresh nonce if verification fails.
                    delete_user_meta( $user_id, '_2fa_nonce' );

                    return false;
                }
       

       
            /**
            * Record the session token for later usage
            *     
            * @param mixed $cookie
            */
            function store_auth_token( $cookie ) 
                {
                    if ( ! $this->is_using_2fa() )
                        return;
                    
                    $parsed = wp_parse_auth_cookie( $cookie );

                    if ( ! empty( $parsed['token'] )    &&  array_search ( $parsed['token'], $this->core_auth_tokens ) ===  FALSE ) 
                        {
                            $this->core_auth_tokens[] = $parsed['token'];
                        }
                }
                
                
            function authenticate_block_cookies( $user ) 
                {
                    if ( $user instanceof WP_User && $this->is_using_2fa() && $this->is_using_2fa_option() && $this->is_active_for_role( $user  ) && did_action( 'login_init' ) )
                        add_filter( 'send_auth_cookies', '__return_false', PHP_INT_MAX );

                    return $user;
                }
                
            
            /**
            * Remove the user sessions
            * 
            * @param mixed $user
            */
            function remove_user_session ( $user ) 
                {
                    $session_manager = WP_Session_Tokens::get_instance( $user->ID );

                    foreach ( $this->core_auth_tokens as $auth_token )
                        $session_manager->destroy( $auth_token );
                        
                    wp_clear_auth_cookie();

                }
                
                
            
            /**
            * Create a login nonce for the html
            *     
            * @param mixed $user_id
            */
            function create_login_nonce( $user_id ) 
                {
                    $nonce = array(
                                                'user_id'    => $user_id,
                                                'expiration' => time() + ( 5 * MINUTE_IN_SECONDS ),
                                            );

                    try {
                        $nonce['key'] = bin2hex( random_bytes( 24 ) );
                    } catch ( Exception $ex ) {
                        $nonce['key'] = wp_hash( $user_id . wp_rand() . microtime(), 'nonce' );
                    }

                    $hashed_key = $this->hash_nonce( $nonce );

                    if ( $hashed_key ) 
                        {
                            $nonce_stored = array(
                                                    'expiration' => $nonce['expiration'],
                                                    'key'        => $hashed_key,
                                                );

                            if ( update_user_meta( $user_id, '_2fa_nonce', $nonce_stored ) ) {
                                return $nonce;
                            }
                        }

                    return false;
                }
                
            
            
            /**
            * Create a hash on the nonce
            *     
            * @param mixed $nonce
            */
            function hash_nonce( $nonce ) 
                {
                    $message = wp_json_encode( $nonce );

                    if ( $message   === FALSE )
                        return FALSE;

                    return wp_hash( $message, 'nonce' );
                }
                
            
            
            /**
            * Construct a login url, including query arguments
            *     
            * @param mixed $params
            * @param mixed $scheme
            */
            static public function login_url( $params = array(), $scheme = 'login' ) 
                {
    
                    $params = urlencode_deep( $params );

                    if ( isset( $params['action'] ) )
                        $url = site_url( 'wp-login.php?action=' . $params['action'], $scheme );
                        else
                        $url = site_url( 'wp-login.php', $scheme );

                    if ( ! empty ( $params ) )
                        $url = add_query_arg( $params, $url );

                    return $url;
                }

                
                
                
            
            /**
            * Output the Options HTML within the Profile user interface
            *     
            * @param mixed $user
            */
            function profile_2fa_options( $user ) 
                {
                    
                    if ( ! is_object ( $user ) )
                        {
                            global $userdata;
                            
                            $user   =   $userdata;
                        }
                    
                    wp_enqueue_style( '2fa-dashboard', WPH_URL . '/assets/css/wph-2fa-dashboard.css');
                    wp_enqueue_script( '2fa-dashboard', WPH_URL . '/assets/js/wph-2fa-dashboard.js', array('jquery'), null, true );

                    wp_localize_script('2fa-dashboard', 'wph_2fa_ajax_obj', array(
                                                                                'ajax_url'  => admin_url('admin-ajax.php'),
                                                                                'nonce'     => wp_create_nonce('wph_2fa_nonce')
                                                                            ));
                    
                    wp_nonce_field( 'user_2fa_options', '_nonce_user_2fa_options', false );
                    
                    ?>
                    <h2><?php esc_html_e( '2FA Available Options', 'wp-hide-security-enhancer' ); ?></h2>
                    <input type="hidden" name="_2fa_enabled_providers[]" value="<?php /* Dummy input so $_POST value is passed when no providers are enabled. */ ?>" />
                    <table class="wp-list-table widefat fixed striped table-view-list _2fa-options-table">
                        <thead>
                            <tr>
                                <th class="col-primary column-author" scope="col"><?php esc_html_e( 'Primary', 'wp-hide-security-enhancer' ); ?></th>
                                <th class="col-name" scope="col"><?php esc_html_e( 'Option Name', 'wp-hide-security-enhancer' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        
                            $first  =   TRUE;
                            
                            foreach ( $this->active_2fa_options as $_2fa_key => $_2fa_option ) { ?>
                            <tr>
                                <th scope="row"><input type="checkbox" disabled="disabled" name="2fa_primary" value="" <?php 
                                
                                if ( $first )
                                    {
                                        $first  =   FALSE;
                                        echo 'checked="checked"';
                                    }
                                
                                ?> /></th>
                                <td>
                                    <label class="two-factor-method-label"><?php echo esc_html( $_2fa_option->get_label() ); ?></label>
                                    <?php $_2fa_option->interface_option_html( $user ); ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="col-primary column-author" scope="col"><?php esc_html_e( 'Primary', 'wp-hide-security-enhancer' ); ?></th>
                                <th class="col-name" scope="col"><?php esc_html_e( 'Option Name', 'wp-hide-security-enhancer' ); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    <?php
                }
    
    
            function shortcode_2fa_user_settings()
                {
                    
                    if ( ! is_user_logged_in() )
                        return;
                    
                    global $current_user;
                    
                    ob_start();
                    
                    wp_enqueue_style( '2fa-front', WPH_URL . '/assets/css/2fa-front.css');
                    
                    $this->profile_2fa_options( $current_user );
                    
                    $html   =   ob_get_contents();
                    ob_end_clean();
                    
                    return $html;
                        
                }
    
    
            function profile_2fa_options_update( $user_id ) 
                {
                    if ( ! isset( $_POST['_nonce_user_2fa_options'] ) )
                        return;
                    
                    check_admin_referer( 'user_2fa_options', '_nonce_user_2fa_options' );
                    
                }
               
                
                
                
        }
        
?>