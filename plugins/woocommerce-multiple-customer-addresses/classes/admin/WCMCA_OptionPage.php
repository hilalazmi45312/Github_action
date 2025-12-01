<?php 
class WCMCA_OptionPage
{
	public function __construct()
	{
		//dd_filter('acf/init', array(&$this,'init_options_menu'));
		$this->init_options_menu();
	}
	function init_options_menu()
	{
		if( function_exists('acf_add_options_page') ) 
		{
			/*acf_add_options_page(array(
				'page_title' 	=> 'Menu name',
				'menu_title'	=> 'Menu name',
				'menu_slug' 	=> 'wcmca-option-menu',
				'capability'	=> 'edit_posts',
				'icon_url'      => 'dashicons-upload',
				'redirect'		=> false
			));*/
			
			 acf_add_options_sub_page(array(
				'page_title' 	=> 'Multiple Customer Addresses - Options',
				'menu_title'	=> 'Options',
				'menu_slug' 	=> 'wcmca-option-menu',
				'parent_slug'	=> 'wcmca-woocommerce-multiple-customer-addresses',
				'position' => '0',
				//'parent_slug'	=> 'woocommerce',
			));
			
			
			
			add_action( 'current_screen', array($this, 'cl_set_global_options_pages') );
			add_action( 'plugins_loaded', array($this, 'switch_language') );
		}
	}
	function switch_language()
	{
		if(wcmca_get_value_if_set($_GET, 'page', "") == 'acf-options-multiple-customer-addresses-options')
		{
			global $wcmca_wpml_helper;
			$wcmca_wpml_helper->switch_to_default_language();
		}
	}
	/**
	 * Force ACF to use only the default language on some options pages
	 */
	function cl_set_global_options_pages($current_screen) 
	{
	  if(!is_admin())
		  return;
	  
	 //debug: wcmca_var_dump($current_screen->id);
	  
	  $page_ids = array(
		"multiple-customer-addresses_page_wcmca-option-menu"
	  );
	  //wcmca_var_dump($current_screen->id);
	  if (in_array($current_screen->id, $page_ids)) 
	  {
		global $wcmca_wpml_helper;
		$wcmca_wpml_helper->switch_to_default_language();
		add_filter('acf/settings/current_language', array(&$this, 'cl_acf_set_language'), 100);
	  }
	}
	

	function cl_acf_set_language() 
	{
	  return acf_get_setting('default_language');
	}

	/**
	 * Wrapper around get_field() to get the "global" option values.
	 * This is the function you'll want to use in your templates instead of get_field() for "global" options.
	 */
	/* function get_global_option($name) 
	{
	  add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
	  $option = get_field($name, 'option');
	  remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
	  return $option;
	} */
}
?>