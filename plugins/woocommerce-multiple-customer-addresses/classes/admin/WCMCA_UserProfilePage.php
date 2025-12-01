<?php 
class WCMCA_UserProfilePage
{
	public function __construct()
	{
		add_action ( 'edit_user_profile', array( &$this,'add_multiple_addresses_link_user_profile_page') ); //Current user is editing other user profile page
		add_action ( 'show_user_profile', array( &$this,'add_multiple_addresses_link_user_profile_page') ); //Current user is editing his page
	}
	
	public function add_multiple_addresses_link_user_profile_page($user)
	{
		global $wcmca_html_helper;
		$wcmca_html_helper->add_multiple_address_link_to_user_admin_profile_page($user);
	}
}
?>