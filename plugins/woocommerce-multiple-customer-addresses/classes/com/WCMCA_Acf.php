<?php 
$wcmca_active_plugins = get_option('active_plugins');
$wcmca_acf_pro = 'advanced-custom-fields-pro/acf.php';
$wcmca_acf_pro_is_aleady_active = in_array($wcmca_acf_pro, $wcmca_active_plugins) || class_exists('acf') ? true : false;
if(!$wcmca_acf_pro_is_aleady_active)
	include_once( WCMCA_PLUGIN_ABS_PATH . '/classes/acf/acf.php' );
$wcmca_hide_menu = true;

add_action('admin_init', 'wcmca_acf_settings_init');
function wcmca_acf_settings_init()
{
	/* if(version_compare( WC_VERSION, '2.7', '>=' ))
		acf_update_setting('select2_version', 4); */
}

if ( ! function_exists( 'is_plugin_active' ) ) 
{
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
}
/* Checks to see if the acf pro plugin is activated  */
if ( is_plugin_active('advanced-custom-fields-pro/acf.php') )  {
	$wcmca_hide_menu = false;
}

/* Checks to see if the acf plugin is activated  */
if ( is_plugin_active('advanced-custom-fields/acf.php') ) 
{
	add_action('plugins_loaded', 'wcmca_load_acf_standard_last', 10, 2 ); //activated_plugin
	add_action('deactivated_plugin', 'wcmca_detect_plugin_deactivation', 10, 2 ); //activated_plugin
	$wcmca_hide_menu = false;
}
function wcmca_detect_plugin_deactivation(  $plugin, $network_activation ) { //after
   // $plugin == 'advanced-custom-fields/acf.php'
	//wcmca_var_dump("wcmca_detect_plugin_deactivation");
	$acf_standard = 'advanced-custom-fields/acf.php';
	if($plugin == $acf_standard)
	{
		$active_plugins = get_option('active_plugins');
		$this_plugin_key = array_keys($active_plugins, $acf_standard);
		if (!empty($this_plugin_key)) 
		{
			foreach($this_plugin_key as $index)
				unset($active_plugins[$index]);
			update_option('active_plugins', $active_plugins);
			//forcing
			deactivate_plugins( plugin_basename( WP_PLUGIN_DIR.'/advanced-custom-fields/acf.php') );
		}
	}
} 
function wcmca_load_acf_standard_last($plugin, $network_activation = null) { //before
	$acf_standard = 'advanced-custom-fields/acf.php';
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_keys($active_plugins, $acf_standard);
	if (!empty($this_plugin_key)) 
	{ 
		foreach($this_plugin_key as $index)
			//array_splice($active_plugins, $index, 1);
			unset($active_plugins[$index]);
		//array_unshift($active_plugins, $acf_standard); //first
		array_push($active_plugins, $acf_standard); //last
		update_option('active_plugins', $active_plugins);
	} 
}


if(!$wcmca_acf_pro_is_aleady_active)
	add_filter('acf/settings/path', 'wcmca_acf_settings_path');
function wcmca_acf_settings_path( $path ) 
{
 
    // update path
    $path = WCMCA_PLUGIN_ABS_PATH. '/classes/acf/';
    
    // return
    return $path;
    
}
if(!$wcmca_acf_pro_is_aleady_active)
	add_filter('acf/settings/dir', 'wcmca_acf_settings_dir');
function wcmca_acf_settings_dir( $dir ) {
 
    // update path
    $dir = WCMCA_PLUGIN_PATH . '/classes/acf/';
    
    // return
    return $dir;
    
}

function wcmca_acf_init() {
    
    include WCMCA_PLUGIN_ABS_PATH . "/assets/fields.php";
    
}
add_action('acf/init', 'wcmca_acf_init');

//hide acf menu
if($wcmca_hide_menu)	
	add_filter('acf/settings/show_admin', '__return_false');

//Avoid custom fields metabox removed by pages
add_filter('acf/settings/remove_wp_meta_box', '__return_false');

//Custom components
function wcmca_add_acf_custom_fields( $version ) 
{
	if(!class_exists('acf_field_divider'))
		include_once(WCMCA_PLUGIN_ABS_PATH.'/classes/com/vendor/acf-divider-field-master/acf-divider-v5.php');
	
	if(!class_exists('acf_field_role_selector'))
		include_once(WCMCA_PLUGIN_ABS_PATH.'/classes/com/vendor/acf-role-selector-field/acf-role_selector-v5.php');
	
	if(!class_exists('acf_field_role_selector_with_guest'))
		include_once(WCMCA_PLUGIN_ABS_PATH.'/classes/com/vendor/acf-role-selector-with-guest-field/acf-role_selector_with_guest-v5.php');

}
add_action('acf/include_field_types', 'wcmca_add_acf_custom_fields');
?>