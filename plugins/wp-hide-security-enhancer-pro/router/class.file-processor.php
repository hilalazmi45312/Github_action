<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    class WPH_File_Processor
        {
            var $action;
            var $file_path;
            var $blog_id;
            
            var $full_file_path;
            
            var $allowed_file_type  =   array('css');
            
            var $allowed_paths      =   array();
            
            var $environment        =   array();
            
            function __construct( $action, $file_path, $blog_id )
                {
                    
                    $this->action           =   $action;
                    $this->file_path        =   preg_replace('/\.+[\\/]+/', '', $file_path );
                    $this->blog_id          =   $blog_id;
                    
                    //append doc root to path 
                    $this->full_file_path   =   $this->normalize_path ( realpath ( $_SERVER['DOCUMENT_ROOT'] ) ) .   $this->file_path; 
                    $this->full_file_path   =   str_replace( '\\', '/', $this->full_file_path);


                    //check if file exists
                    if (!file_exists($this->full_file_path))
                        die();
                        
                    //allow only style files
                    $pathinfo   =   pathinfo($this->full_file_path);
                    if(!isset($pathinfo['extension'])   ||  !in_array(strtolower($pathinfo['extension']), $this->allowed_file_type))
                        die();
                                        
                    $this->load_environment();
                        
                    //check if the file is in allowed path
                    $found  =   FALSE;
                    foreach($this->environment->allowed_paths   as  $allowed_path)
                        {
                            $result     =   stripos($this->full_file_path, $allowed_path);
                            if($result  !== FALSE   &&  $result === 0)
                                {
                                    $found  =   TRUE;
                                    break;
                                }
                        }
                    
                    if(! $found )
                        die();
                        
                        
                    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
                        {
                            if  ( function_exists('ob_gzhandler')  && ini_get('zlib.output_compression'))
                                ob_start();    
                                else
                                {
                                    ob_start('ob_gzhandler'); ob_start();
                                }
                        }
                        else
                        {
                            ob_start();
                        }
                    
                    
                }
            
            function __destruct()
                {
                    
                    if(ob_get_level()   <   1)
                        return;
                        
                    $out = ob_get_contents();
                    ob_end_clean();
                    
                    echo $out;
                }
            
            
            
            /**
            * Load environment
            * 
            */
            function load_environment()
                {
                    //require_once( ABSPATH . 'environment.php');
                    require_once( '../../../uploads/wph/environment.php');
                    
                    $this->environment  =   json_decode($environment_variable);
                    
                }
               
            
            /**
            * Process the action
            *     
            */
            function run()
                {

                    switch($this->action)
                        {
                            case 'style-clean'  :   
                                                    $this->style_clean();
                                                    break;
                            
                        }
                        
                }
                
            
            /**
            * Clean the file
            *     
            */
            function style_clean()
                {
                    //output headers
                    $expires_offset = 31536000;                    
                    
                    header('Content-Type: text/css; charset=UTF-8');
                    header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
                    header("Cache-Control: public, max-age=$expires_offset");
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($this->full_file_path)).' GMT', true);
                    
                    $handle         = fopen($this->full_file_path, "r");
                    $file_data      = fread($handle, filesize($this->full_file_path));
                    fclose($handle);
                    
                    $file_data  =   preg_replace('!/\*.*?\*/!s', '', $file_data);
                    $file_data  =   preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $file_data);
       
                    $blog_theme_data    =   $this->environment->themes->{$this->blog_id};         
                    
                    $file_data  =   str_replace('../' . $blog_theme_data->main->folder_name .'/', '../' . $blog_theme_data->main->mapped_name .'/', $file_data);   
                    
                    if( $blog_theme_data->use_child_theme   === TRUE )
                        $file_data  =   str_replace('../' . $blog_theme_data->child->folder_name .'/', '../' . $blog_theme_data->child->mapped_name .'/', $file_data);   
                     
                    echo $file_data;
                    
                }
                
                
            /**
             * Normalizes a filesystem path.
             *
             * On windows systems, replaces backslashes with forward slashes
             * and forces upper-case drive letters.
             * Allows for two leading slashes for Windows network shares, but
             * ensures that all other duplicate slashes are reduced to a single.
             *
             * @param string $path Path to normalize.
             * @return string Normalized path.
             */
            function normalize_path( $path ) 
                {
                    $wrapper = '';

                    if ( $this->is_stream( $path ) ) 
                        {
                            list( $wrapper, $path ) = explode( '://', $path, 2 );

                            $wrapper .= '://';
                        }

                    // Standardize all paths to use '/'.
                    $path = str_replace( '\\', '/', $path );

                    // Replace multiple slashes down to a singular, allowing for network shares having two slashes.
                    $path = preg_replace( '|(?<=.)/+|', '/', $path );

                    // Windows paths should uppercase the drive letter.
                    if ( ':' === substr( $path, 1, 1 ) ) 
                        {
                            $path = ucfirst( $path );
                        }

                    return $wrapper . $path;
                }
                
                
                
            /**
             * Tests if a given path is a stream URL
             *
             *
             * @param string $path The resource path or URL.
             * @return bool True if the path is a stream URL.
             */
            function is_stream( $path ) 
                {
                    $scheme_separator = strpos( $path, '://' );

                    if ( false === $scheme_separator ) {
                        // $path isn't a stream.
                        return false;
                    }

                    $stream = substr( $path, 0, $scheme_separator );

                    return in_array( $stream, stream_get_wrappers(), true );
                }
              
       
        }

?>