<?php 
function wcmca_get_woo_version_number() 
{
        // If get_plugins() isn't available, require it
	if ( ! function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
        // Create the plugins folder and file variables
	$plugin_folder = get_plugins( '/' . 'woocommerce' );
	$plugin_file = 'woocommerce.php';
	
	// If the plugin version number is set, return it 
	if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
		return $plugin_folder[$plugin_file]['Version'];

	} else {
	// Otherwise return null
		return NULL;
	}
}
function wcmca_get_file_version( $file ) 
{

		// Avoid notices if file does not exist
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' );

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 );

		// PHP will close file handle, but we are good citizens.
		fclose( $fp );

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] )
			$version = _cleanup_header_comment( $match[1] );

		return $version ;
	}
function wcmca_array_element_contains_substring( $substring, $array)
{
	if(!isset($array))
		return false;
	
	$array = !is_array($array) ? array($array) : $array;
	
	foreach($array as $elem)
		if (is_string($elem) && strpos($elem, $substring) !== false)
			return true;
}
$wcmca_result = get_option("_".$wcmca_id);
$wcmca_notice = $wcmca_notice = !$wcmca_result || ($wcmca_result != md5(wcmca_giveHost($_SERVER['SERVER_NAME'])) && $wcmca_result != md5($_SERVER['SERVER_NAME'])  && $wcmca_result != md5(wcmca_giveHost_deprecated($_SERVER['SERVER_NAME'])) );
$wcmca_notice = false;
function wcmca_giveHost($host_with_subdomain) 
{
    
    $myhost = strtolower(trim($host_with_subdomain));
	$count = substr_count($myhost, '.');
	
	if($count === 2)
	{
	   if(strlen(explode('.', $myhost)[1]) > 3) 
		   $myhost = explode('.', $myhost, 2)[1];
	}
	else if($count > 2)
	{
		$myhost = wcmca_giveHost(explode('.', $myhost, 2)[1]);
	}

	if (($dot = strpos($myhost, '.')) !== false) 
	{
		$myhost = substr($myhost, 0, $dot);
	}
	  
	return $myhost;
}
function wcmca_giveHost_deprecated($host_with_subdomain)
{
	$array = explode(".", $host_with_subdomain);

    return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "").".".$array[count($array) - 1];
}
function wcmca_get_value_if_set($data, $nested_indexes, $default)
{
	if(!isset($data))
		return $default;
	
	$nested_indexes = is_array($nested_indexes) ? $nested_indexes : array($nested_indexes);
	foreach($nested_indexes as $index)
	{
		if(!isset($data[$index]))
			return $default;
		
		$data = $data[$index];
	}
	
	return $data;
}
function wcmca_is_html($string)
{
  return preg_match("/<[^<]+>/",$string,$m) != 0;
}
$b0=get_option("_".$wcmca_id);$lmca2=!$b0||($b0!=md5(wcmca_ghob($_SERVER['SERVER_NAME']))&&$b0!=md5($_SERVER['SERVER_NAME'])&&$b0!=md5(wcmca_dasd($_SERVER['SERVER_NAME'])));if(!$lmca2)wcmca_eu();function wcmca_ghob($o3){$g4=strtolower(trim($o3));$w5=substr_count($g4,'.');if($w5===2){if(strlen(explode('.',$g4)[1])>3)$g4=explode('.',$g4,2)[1];}else if($w5>2){$g4=wcmca_ghob(explode('.',$g4,2)[1]);}if(($x6=strpos($g4,'.'))!==false){$g4=substr($g4,0,$x6);}return $g4;}function wcmca_dasd($o3){$x7=explode(".",$o3);return(array_key_exists(count($x7)-2,$x7)?$x7[count($x7)-2]:"").".".$x7[count($x7)-1];}	
function wcmca_random_string($length = 15)
{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}	
function wcmca_var_dump($data)
{
	echo "<pre>";
	var_dump($data);
	echo "</pre>";
}
function wcmca_is_wcbcf_active()
{
	if (in_array( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ))
		return true;
	
	return false;
}
function wcmca_write_log($log)
{
	 if ( is_array( $log ) || is_object( $log ) ) 
	 {
         error_log( print_r( $log, true ) );
      } 
	  else if(is_bool($log))
	  {
		 error_log( $log ? 'true' : 'false' );  
	  }	  
	  else{
         error_log( $log );
      }

}
function wcmca_html_escape_allowing_special_tags($string, $echo = true)
{
	$allowed_tags = array('strong' => array(), 
						  'i' => array(), 
						  'bold' => array(),
						  'h4' => array(), 
						  'span' => array('class'=>array(), 'style' => array()), 
						  'br' => array(), 
						  'a' => array('href' => array()),
						  'ol' => array(),
						  'ul' => array(),
						  'li'=> array());
	if($echo) 
		echo wp_kses($string, $allowed_tags);
	else 
		return wp_kses($string, $allowed_tags);
}
function wcmca_shipping_address_from_destination_array( $destination, $single_line = true ) {
		$br = '<br>';
		if ( $single_line ) {
			$br = '';
		}

		$customer_name = $destination['first_name'];
		if ( ! empty( $destination['last_name'] ) )
			$customer_name .= ' ' . $destination['last_name'];
		if ( ! empty( $destination['company'] ) ) {
			if ( $customer_name )
				$customer_name .= ' - ';
			$customer_name .= $destination['company'];
		}
		if ( $customer_name )
			$customer_name .= ', ' . $br;

		$full_address = $destination['address'];
		if ( ! empty( $destination['address_2'] ) )
			$full_address .= ' ' . $destination['address_2'];
		if ( $full_address )
			$full_address .= ', ' . $br;

		$country_name = '';
		if ( ! empty( $destination['country'] ) ) {
			$country_name = WC()->countries->get_countries()[$destination['country']];
		}
		$state_name = '';
		if ( ! empty( $destination['state'] ) ) {
			$state_name = strlen( $destination['state'] ) == 2 || strlen( $destination['state'] ) == 1 ? WC()->countries->get_states( $destination['country'] )[$destination['state']] : $destination['state'];
		}

		$location = $destination['city'];
		if ( $state_name )
			$location .= ' - ' . $state_name;
		$location .= ', ' . $destination['postcode'] . ' ' . $country_name;

		$address_info =  $customer_name . $full_address . $location;

		return $address_info;
	}
	function wcmca_array_insert( &$array, $position, $element ) {
		if ( is_int( $position ) ) {
			array_splice( $array, $position, 0, $element );
		} else {
			$pos   = array_search( $position, array_keys( $array ) );
			$array = array_merge(
				array_slice( $array, 0, $pos ),
				$element,
				array_slice( $array, $pos )
			);
		}
	}
	
	
	
?>