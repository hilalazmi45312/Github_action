<?php 
/*
Plugin Name: WooCommerce Multiple Customer Addresses & Shipping
Description: Manage multiple customers shipping and billing addresses and multiple shippings
Author: Lagudi Domenico
Text Domain: woocommerce-multiple-customer-addresses
Domain Path: /languages/
Version: 25.6
*/

/* 
Copyright: WooCommerce Multiple Customer Addresses & Shippings uses the ACF PRO plugin. ACF PRO files are not to be used or distributed outside of the WooCommerce Multiple Customer Addresses plugin.
*/

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

define('WCMCA_PLUGIN_PATH', rtrim(plugin_dir_url(__FILE__), "/") )  ;
define('WCMCA_PLUGIN_ABS_PATH', plugin_dir_path( __FILE__ ) );

if ( !defined('WP_CLI') && ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ||
					   (is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins') ))
					 )	
	)
{
	//For some reasins the theme editor in some installtion won't work. This directive will prevent that.
	if(isset($_POST['action']) && $_POST['action'] == 'edit-theme-plugin-file')
		return;
	
	if(isset($_REQUEST ['context']) && $_REQUEST['context'] == 'edit') //rest api
		return;
		
	if(isset($_POST['action']) && strpos($_POST['action'], 'health-check') !== false) //health check
		return;
		
	//com
	$wcmca_id = 16127030;
	$wcmca_name = "WooCommerce Multiple Customer Addresses";
	$wcmca_activator_slug = "wcmca-activator";
	
	include_once( "classes/com/WCMCA_Acf.php"); 
	include_once( "classes/com/WCMCA_Global.php"); 
	require_once('classes/vendor/vanquish/admin/ActivationPage.php');
	
	add_action('init', 'wcmca_init');
	add_action('admin_notices', 'wcmca_admin_notices' );
	add_action('admin_menu', 'wcmca_init_act');
	if(defined('DOING_AJAX') && DOING_AJAX)
		wcmca_init_act();
}
function wcmca_admin_notices()
{
	global $lmca2, $wcmca_name, $wcmca_activator_slug;
	if($lmca2 && (!isset($_GET['page']) || $_GET['page'] != $wcmca_activator_slug))
	{
		 ?>
		<div class="notice notice-success">
			<p><?php wcmca_html_escape_allowing_special_tags(sprintf(__( 'To complete the <span style="color:#96588a; font-weight:bold;">%s</span> plugin activation, you must verify your purchase license. Click <a href="%s">here</a> to verify it.', 'woocommerce-multiple-customer-addresses' ), $wcmca_name, get_admin_url()."admin.php?page=".$wcmca_activator_slug)); ?></p>
		</div>
		<?php
	}
}
function wcmca_init()
{
	global $wcmca_id;
	if(current_user_can('administrator') && isset($_GET['wcmca_reset_license']))
	{
		delete_option("_".$wcmca_id); 
		delete_option("_".$wcmca_id."_purchase_code"); 
	}
	
	load_plugin_textdomain('woocommerce-multiple-customer-addresses', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
function wcmca_init_act()
{
	global $wcmca_activator_slug, $wcmca_name, $wcmca_id;
	new WCMCA\vendor\vanquish\admin\ActivationPage($wcmca_activator_slug, $wcmca_name, 'wcmca-woocommerce-multiple-customer-addresses', $wcmca_id, WCMCA_PLUGIN_PATH);
}
function wcmca_eu()
{
	global $wcmca_wpml_helper, $wcmca_html_helper, $wcmca_customer_model, $wcmca_address_model, $wcmca_order_model, $wcmca_email_model,
		   $wcmca_option_model, $wcmca_cart_model, $wcmca_session_model, $wcmca_my_account_page_addon, $wcmca_checkout_page_addon, 
		   $wcmca_frontend_order_details_page_addon, $wcmca_emails_addon, $wcmca_shop_page_addon, $wcmca_admin_order_details_page_addon,
		   $wcmca_user_profile_page;
		
	//com 
	require_once('classes/vendor/vanquish/com/Updater.php'); 
	new WCMCA\vendor\vanquish\com\Updater(); 	
	if(!class_exists('WCMCA_Wpml'))
	{
		require_once('classes/com/WCMCA_Wpml.php');
		$wcmca_wpml_helper = new WCMCA_Wpml();
	}
	if(!class_exists('WCMCA_Html'))
	{
		require_once('classes/com/WCMCA_Html.php');
		$wcmca_html_helper = new WCMCA_Html();
	}
	if(!class_exists('WCMCA_Customer'))
	{
		require_once('classes/com/WCMCA_Customer.php');
		$wcmca_customer_model = new WCMCA_Customer();
	}
	if(!class_exists('WCMCA_Address'))
	{
		require_once('classes/com/WCMCA_Address.php');
		$wcmca_address_model = new WCMCA_Address();
	}
	if(!class_exists('WCMCA_Order'))
	{
		require_once('classes/com/WCMCA_Order.php');
		$wcmca_order_model = new WCMCA_Order();
	}
	if(!class_exists('WCMCA_Email'))
	{
		require_once('classes/com/WCMCA_Email.php');
		$wcmca_email_model = new WCMCA_Email();
	}
	if(!class_exists('WCMCA_Option'))
	{
		require_once('classes/com/WCMCA_Option.php');
		$wcmca_option_model = new WCMCA_Option();
	}
	if(!class_exists('WCMCA_Cart'))
	{
		require_once('classes/com/WCMCA_Cart.php');
		$wcmca_cart_model = new WCMCA_Cart();
	}
	if(!class_exists('WCMCA_Session.php'))
	{
		require_once('classes/com/WCMCA_Session.php');
		$wcmca_session_model = new WCMCA_Session();
	}
	//frontend
	if(!class_exists('WCMCA_MyAccountPage'))
	{
		require_once('classes/frontend/WCMCA_MyAccountPage.php');
		$wcmca_my_account_page_addon = new WCMCA_MyAccountPage();
	}
	if(!class_exists('WCMCA_CheckoutPage'))
	{
		require_once('classes/frontend/WCMCA_CheckoutPage.php');
		$wcmca_checkout_page_addon = new WCMCA_CheckoutPage();
	}
	if(!class_exists('WCMCA_OrderDetailsPage'))
	{
		require_once('classes/frontend/WCMCA_OrderDetailsPage.php');
		$wcmca_frontend_order_details_page_addon = new WCMCA_OrderDetailsPage();
	}
	if(!class_exists('WCMCA_Emails'))
	{
		require_once('classes/frontend/WCMCA_Emails.php');
		$wcmca_emails_addon = new WCMCA_Emails();
	}
	if(!class_exists('WCMCA_ShopPage'))
	{
		require_once('classes/frontend/WCMCA_ShopPage.php');
		$wcmca_shop_page_addon = new WCMCA_ShopPage();
	}
	if(!class_exists('WCMCA_OrdersListPage'))
	{
		require_once('classes/frontend/WCMCA_OrdersListPage.php');
		new WCMCA_OrdersListPage();
	}
	//admin
	if(!class_exists('WCMCA_AdminOrderDetailsPage'))
	{
		require_once('classes/admin/WCMCA_AdminOrderDetailsPage.php');
		$wcmca_admin_order_details_page_addon = new WCMCA_AdminOrderDetailsPage();
	}
	if(!class_exists('WCMCA_OptionPage'))
	{
		require_once('classes/admin/WCMCA_OptionPage.php');
		new WCMCA_OptionPage();
	}
	if(!class_exists('WCMCA_UserProfilePage'))
	{
		require_once('classes/admin/WCMCA_UserProfilePage.php');
		$wcmca_user_profile_page = new WCMCA_UserProfilePage();
	}
	if(!class_exists('WCMCA_ImportPage'))
	{
		require_once('classes/admin/WCMCA_ImportPage.php');
	}
	
	//ACF custom fields init: For some reasons, they are not properly initialized via the WCMCA_Acf.php component
	wcmca_acf_init();
	
	//actions 
	add_action('admin_menu', 'wcmca_init_admin_panel');
}
function wcmca_admin_init()
{
	$remove = remove_submenu_page( 'wcmca-woocommerce-multiple-customer-addresses', 'wcmca-woocommerce-multiple-customer-addresses');
	$remove = remove_submenu_page( 'wcmca-woocommerce-multiple-customer-addresses', 'wcmca-woocommerce-multiple-customer-addresses-edit-user');
	
}	
function wcmca_init_admin_panel()
{
	global $wcmca_html_helper;
	$place = wcmca_get_free_menu_position(55, 0.1);
	
	$cap = 'manage_woocommerce';
	
	add_menu_page( 'Multiple Customer Addresses',  esc_html__('Multiple Customer Addresses', 'woocommerce-multiple-customer-addresses'), $cap, 'wcmca-woocommerce-multiple-customer-addresses', null,  "dashicons-book-alt" , (string)$place);
	add_submenu_page( 'wcmca-woocommerce-multiple-customer-addresses',  esc_html__('Import', 'woocommerce-multiple-customer-addresses'),   esc_html__('Import', 'woocommerce-multiple-customer-addresses'), $cap, 'woocommerce-multiple-customer-addresses-import', 'wcmca_render_admin_page' );	
	add_filter('plugin_action_links_woocommerce-multiple-customer-addresses/woocommerce-multiple-customer-addresses.php', 'wcmca_manage_plugin_actions_links');
	wcmca_admin_init();
}
function wcmca_manage_plugin_actions_links($links)
{
	$url = esc_url( add_query_arg(
		'wcmca_reset_license',
		'true',
		get_admin_url()
	) );
	// Create the link.
	$settings_link = "<a href='$url' onclick='return confirm(\"".esc_html__('Are you sure?','woocommerce-multiple-customer-addresses')."\");'>" . esc_html__('Reset activation','woocommerce-multiple-customer-addresses') . '</a>';
	// Adds the link to the end of the array.
	array_push(
		$links,
		$settings_link
	);
	return $links;
}
function wcmca_render_admin_page()
{
	if(!isset($_REQUEST['page']))
		return;
	switch($_REQUEST['page'])
	{
		case 'woocommerce-multiple-customer-addresses-import':
		
			$settings_page = new WCMCA_ImportPage();
			$settings_page->render_page();
		break;
	}
}
function wcmca_get_free_menu_position($start, $increment = 0.1)
{
	foreach ($GLOBALS['menu'] as $key => $menu) {
		$menus_positions[] = $key;
	}
	
	if (!in_array($start, $menus_positions)) return $start;

	/* the position is already reserved find the closet one */
	while (in_array($start, $menus_positions)) 
	{
		$start += $increment;
	}
	return $start;
}
?>