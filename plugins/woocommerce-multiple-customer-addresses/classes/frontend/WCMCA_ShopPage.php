<?php 
class WCMCA_ShopPage
{
	public function __construct()
	{
		add_action('wp_enqueue_scripts', array(&$this, 'reload_page_after_item_added_to_cart'));
	}
	
	public function reload_page_after_item_added_to_cart()
	{
		global $wcmca_option_model;
		if(@is_shop() && $wcmca_option_model->add_product_distinctly_to_cart() && !$wcmca_option_model->disable_shop_page_reloading_on_product_add())
		{
			wp_enqueue_script('wcmca-shop-page-utilities', WCMCA_PLUGIN_PATH.'/js/frontend-shop-page-utilities.js', array('jquery'));
		}
	}
}