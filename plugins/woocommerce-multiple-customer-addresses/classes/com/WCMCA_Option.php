<?php 
class WCMCA_Option
{
	var $fee_cache;
	var $shipping_per_product_related_options_cache;
	public function __construct()
	{
	}
	public function get_product_shipping_fee_options()
	{
		if(isset($this->fee_cache))
			return $this->fee_cache;
		$result = array();
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$result['handling_product_shipping_fee'] = get_field('wcmca_handling_product_shipping_fee', 'option');
		$result['handling_product_shipping_fee'] = isset($result['handling_product_shipping_fee']) ? $result['handling_product_shipping_fee'] : false;
		
		$result['fee_taxable'] = get_field('wcmca_fee_taxable', 'option');
		$result['fee_taxable'] = isset($result['fee_taxable']) ? $result['fee_taxable'] : false;
		
		$fee_ranges = array();
		if( have_rows('wcmca_fee_ranges', 'option') )
			while ( have_rows('wcmca_fee_ranges', 'option') ) 
			{
				the_row();
				$row = array();
				$row['min'] = get_sub_field('wcmca_min');
				$row['min'] = $row['min'] && $row['min'] != "" ? $row['min'] : 0;
				
				$row['max'] = get_sub_field('wcmca_max');
				$row['max'] = $row['max'] && $row['max'] != "" ? $row['max'] : 0; //0 -> no limit
				
				$row['fee'] = get_sub_field('wcmca_fee');
				
				$fee_ranges[] = $row;
			}
		$result['fee_ranges'] = $fee_ranges;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		$this->fee_cache = $result;
		return $result;
	}
	public function shipping_per_product_related_options()
	{
		$result = array();
		if(isset($this->shipping_per_product_related_options_cache))
			return $this->shipping_per_product_related_options_cache;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$result['shipping_per_product'] = get_field('wcmca_shipping_per_product', 'option');
		$result['shipping_per_product'] = isset($result['shipping_per_product']) ? $result['shipping_per_product'] : false;
		
		$result['multiple_addresses_shipping'] = get_field('wcmca_multiple_addresses_shipping', 'option');
		$result['multiple_addresses_shipping'] = isset($result['multiple_addresses_shipping']) && $result['shipping_per_product'] ? $result['multiple_addresses_shipping'] : false;
		
		$result['add_product_distinctly_to_cart'] = get_field('wcmca_add_product_distinctly_to_cart', 'option');
		$result['add_product_distinctly_to_cart'] = isset($result['add_product_distinctly_to_cart']) && $result['add_product_distinctly_to_cart'] ? true : false;
		
		$result['shipping_per_product_excluded_prod'] = get_field('wcmca_shipping_per_product_excluded_prod', 'option');
		$result['shipping_per_product_excluded_prod'] = isset($result['shipping_per_product_excluded_prod'])  && is_array($result['shipping_per_product_excluded_prod']) ? $result['shipping_per_product_excluded_prod'] : array();
		
		$result['shipping_per_product_excluded_cat'] = get_field('wcmca_shipping_per_product_excluded_cat', 'option');
		$result['shipping_per_product_excluded_cat'] = isset($result['shipping_per_product_excluded_cat']) && is_array($result['shipping_per_product_excluded_cat']) ? $result['shipping_per_product_excluded_cat'] : array();
		
		$result['add_shipping_email_field_to_shipping_addresses'] = get_field('wcmca_add_shipping_email_field_to_shipping_addresses', 'option');
		$result['add_shipping_email_field_to_shipping_addresses'] = isset($result['add_shipping_email_field_to_shipping_addresses']) && $result['add_shipping_email_field_to_shipping_addresses'] ? true : false;
		
		$result['is_shipping_email_required'] = get_field('wcmca_is_shipping_email_required', 'option');
		$result['is_shipping_email_required'] = isset($result['is_shipping_email_required']) && $result['is_shipping_email_required'] ? true : false;
		
		$result['add_shipping_phone_field_to_shipping_addresses'] = get_field('wcmca_add_shipping_phone_field_to_shipping_addresses', 'option');
		$result['add_shipping_phone_field_to_shipping_addresses'] = isset($result['add_shipping_phone_field_to_shipping_addresses']) && $result['add_shipping_phone_field_to_shipping_addresses'] ? true : false;
		
		$result['is_shipping_phone_required'] = get_field('wcmca_is_shipping_phone_required', 'option');
		$result['is_shipping_phone_required'] = isset($result['is_shipping_phone_required']) && $result['is_shipping_phone_required'] ? true : false;
		
		$result['display_notes_field'] = get_field('wcmca_display_notes_field', 'option');
		$result['display_notes_field'] = isset($result['display_notes_field']) && $result['display_notes_field'] ? true : false;
		
		$result['is_notes_field_required'] = get_field('wcmca_is_notes_field_required', 'option');
		$result['is_notes_field_required'] = isset($result['is_notes_field_required']) && $result['is_notes_field_required'] ? true : false;
		
		$result['display_add_billing_address_button'] = get_field('wcmca_display_add_billing_address_button', 'option');
		$result['display_add_billing_address_button'] = isset($result['display_add_billing_address_button']) && $result['display_add_billing_address_button'] ? true : false;
		
		$result['display_add_shipping_address_button'] = get_field('wcmca_display_add_shipping_address_button', 'option');
		$result['display_add_shipping_address_button'] = isset($result['display_add_shipping_address_button']) && $result['display_add_shipping_address_button'] ? true : false;
		
		$result['display_pick_up_option'] = get_field('wcmca_display_pick_up_option', 'option');
		$result['display_pick_up_option'] = isset($result['display_pick_up_option']) && $result['display_pick_up_option'] ? true : false;
		
		$result['product_address_show_selector_even_for_one_item'] = get_field('wcmca_product_address_show_selector_even_for_one_item', 'option');
		$result['product_address_show_selector_even_for_one_item'] = isset($result['product_address_show_selector_even_for_one_item']) && $result['product_address_show_selector_even_for_one_item'] ? true : false;
		
		$result['product_address_type_for_guests'] = get_field('wcmca_product_address_type_for_guests', 'option');
		$result['product_address_type_for_guests'] = isset($result['product_address_type_for_guests']) && $result['product_address_type_for_guests'] ? 'shipping' : 'billing';
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		$this->shipping_per_product_related_options_cache = $result;
		
		return $result;
	}
	public function shipping_per_product()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$wcmca_shipping_per_product = get_field('wcmca_shipping_per_product', 'option');
		$wcmca_shipping_per_product = $wcmca_shipping_per_product != null ? $wcmca_shipping_per_product: false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $wcmca_shipping_per_product;
	}
	public function disable_last_used_address()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$disable_last_used_address = get_field('wcmca_checkout_page_disable_last_used_address_option', 'option');
		$disable_last_used_address = $disable_last_used_address != null ? (boolean)$disable_last_used_address : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $disable_last_used_address;
	}
	public function disable_smooth_scroll()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$disable_smooth_scroll = get_field('wcmca_checkout_page_disable_smooth_scroll', 'option');
		$disable_smooth_scroll = $disable_smooth_scroll != null ? (boolean)$disable_smooth_scroll : false;
	
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $disable_smooth_scroll;
	}
	public function checkout_form_can_edit()
	{
		global $wcmca_customer_model;
		$result = array('billing' => false , 'shipping' => false);
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$result['billing'] = get_field('wcmca_disable_checkout_billing_form', 'option');
		$result['billing'] = $result['billing'] ? true : false;
		$result['shipping'] = get_field('wcmca_disable_checkout_shipping_form', 'option');
		$result['shipping'] = $result['shipping'] ? true : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	public function disable_addresses_access_by_user_role()
	{
		global $wcmca_customer_model;
		$result = array('billing' => false , 'shipping' => false);
		//no need: $addresseses_managment = $this->get_addresseses_managment_settings();
		
		$billing_addresses_disable_for_role = get_field('wcmca_billing_addresses_disable_for_roles', 'option');
		$billing_addresses_disable_for_role  = $billing_addresses_disable_for_role ? $billing_addresses_disable_for_role : array();
		
		$shipping_addresses_disable_for_role = get_field('wcmca_shipping_addresses_disable_for_roles', 'option');
		$shipping_addresses_disable_for_role = $shipping_addresses_disable_for_role ? $shipping_addresses_disable_for_role : array();
		
		$result['billing'] =  $wcmca_customer_model->belongs_to_not_allowed_roles($billing_addresses_disable_for_role);
	   
		$result['shipping'] = $wcmca_customer_model->belongs_to_not_allowed_roles($shipping_addresses_disable_for_role); 
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
		
	}
	public function is_vat_identification_number_enabled()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$is_vat_identification_number_enabled = get_field('wcmca_vat_idetification_field', 'option');
		$is_vat_identification_number_enabled = $is_vat_identification_number_enabled != null ? (boolean)$is_vat_identification_number_enabled : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $is_vat_identification_number_enabled;
	}
	public function is_vat_identification_number_required()
	{
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$is_vat_identification_number_required = get_field('wcmca_vat_identification_enable_required', 'option');
		$is_vat_identification_number_required = $is_vat_identification_number_required != null && $is_vat_identification_number_required == 'yes'? true : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $is_vat_identification_number_required;
	}
	public function get_required_fields()
	{
		$fields = array();
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$fields['billing_first_and_last_name_disable_required'] = get_field('wcmca_billing_first_and_last_name_disable_required', 'option');
		$fields['billing_first_and_last_name_disable_required'] = $fields['billing_first_and_last_name_disable_required'] != null && $fields['billing_first_and_last_name_disable_required'] == 'yes' ? true : false;
		
		$fields['shipping_first_and_last_name_disable_required'] = get_field('wcmca_shipping_first_and_last_name_disable_required', 'option');
		$fields['shipping_first_and_last_name_disable_required'] = $fields['shipping_first_and_last_name_disable_required'] != null && $fields['shipping_first_and_last_name_disable_required'] == 'yes' ? true : false;
		
		$fields['billing_company_name_enable_required'] = get_field('wcmca_billing_company_name_enable_required', 'option');
		$fields['billing_company_name_enable_required'] = $fields['billing_company_name_enable_required'] != null && $fields['billing_company_name_enable_required'] == 'yes' ? true : false;
		
		$fields['shipping_company_name_enable_required'] = get_field('wcmca_shipping_company_name_enable_required', 'option');
		$fields['shipping_company_name_enable_required'] = $fields['shipping_company_name_enable_required'] != null && $fields['shipping_company_name_enable_required'] == 'yes' ? true : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		return $fields;
	}
	public function add_product_distinctly_to_cart()
	{
		$result = true;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$result = get_field('wcmca_add_product_distinctly_to_cart', 'option');
		$result2 = get_field('wcmca_shipping_per_product', 'option');
		$result = isset($result) && $result ? true : false;
		$result2 = isset($result2) && $result2 ? true : false;
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result && $result2;
	}
	public function automatically_split_product_by_cart_quantity()
	{
		$result = true;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$result = get_field('wcmca_add_product_distinctly_to_cart', 'option');
		$result2 = get_field('wcmca_automatically_split_product_by_cart_quantity', 'option');
		$result3 = get_field('wcmca_shipping_per_product', 'option');
		$result = isset($result) && $result ? true : false;
		$result2 = isset($result2) && $result2 ? true : false;
		$result3 = isset($result3) && $result3 ? true : false;
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result && $result2 && $result3;
	}
	public function disable_shop_page_reloading_on_product_add()
	{
		$result = false;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$result = get_field('wcmca_disable_shop_page_reloading_if_a_product_is_added_to_cart', 'option');
		$result = isset($result) && $result ? true : false;
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	public function is_identifier_field_disabled()
	{
		$result = true;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$result = get_field('wcmca_disable_identifier_field', 'option');
		$result = $result != null && $result == 'yes' ? true : false;
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	public function display_fields_labels()
	{
		$result = true;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$result = get_field('wcmca_my_account_page_display_fields_labels', 'option');
		$result = $result != null && $result == 'yes' ? true : false;
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	public function which_addresses_type_are_disabled()
	{
		$addresses = array();
		
		$disable_by_role = $this->disable_addresses_access_by_user_role();

		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$addresses['billing'] = get_field('wcmca_disable_billing_multiple_addresses', 'option');
		$addresses['billing'] = $addresses['billing'] != null ? (boolean)$addresses['billing'] : false;
		
		$addresses['shipping'] = get_field('wcmca_disable_shipping_multiple_addresses', 'option');
		$addresses['shipping'] = $addresses['shipping'] != null ? (boolean)$addresses['shipping'] : false;
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		//roles check
		$addresses['billing'] = !$addresses['billing'] ? $disable_by_role['billing'] : $addresses['billing'] ;
		$addresses['shipping'] = !$addresses['shipping'] ? $disable_by_role['shipping'] : $addresses['shipping'] ;
		
		return $addresses;
	}
	public function can_curent_user_select_product_address()
	{
		global $wcmca_customer_model;
		$can = true;
		
		$disable_by_role = $this->disable_addresses_access_by_user_role();

		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$product_address_disable_for_roles = get_field('wcmca_product_address_disable_for_roles', 'option');
		$product_address_disable_for_roles = $product_address_disable_for_roles ? $product_address_disable_for_roles : array();
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		$can = !$wcmca_customer_model->belongs_to_not_allowed_roles($product_address_disable_for_roles);
		
		return $can;
	}
	public function get_addresseses_managment_settings()
	{
		$options = array();
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$options['max_number_of_billing_addresses'] = get_field('wcmca_max_number_of_billing_addresses', 'option');
		$options['max_number_of_billing_addresses'] = $options['max_number_of_billing_addresses'] ? $options['max_number_of_billing_addresses'] : 0; //0 = no limits
		
		$options['disable_user_billing_addresses_editing_capabilities'] = get_field('wcmca_disable_user_billing_addresses_editing_capabilities', 'option');
		$options['disable_user_billing_addresses_editing_capabilities'] = $options['disable_user_billing_addresses_editing_capabilities'] ? $options['disable_user_billing_addresses_editing_capabilities'] : false;
			
		$options['billing_addresses_disable_for_role'] = get_field('wcmca_billing_addresses_disable_for_roles', 'option');
		$options['billing_addresses_disable_for_role'] = $options['billing_addresses_disable_for_role'] ? $options['billing_addresses_disable_for_role'] : array();
		
		$options['shipping_addresses_disable_for_role'] = get_field('wcmca_shipping_addresses_disable_for_role', 'option');
		$options['shipping_addresses_disable_for_role'] = $options['shipping_addresses_disable_for_role'] ? $options['shipping_addresses_disable_for_role'] : array();
		
		$options['max_number_of_shipping_addresses'] = get_field('wcmca_max_number_of_shipping_addresses', 'option');
		$options['max_number_of_shipping_addresses'] = $options['max_number_of_shipping_addresses'] ? $options['max_number_of_shipping_addresses'] : 0; //0 = no limits
		
		$options['disable_user_shipping_addresses_editing_capabilities'] = get_field('wcmca_disable_user_shipping_addresses_editing_capabilities', 'option');
		$options['disable_user_shipping_addresses_editing_capabilities'] = $options['disable_user_shipping_addresses_editing_capabilities'] ? $options['disable_user_shipping_addresses_editing_capabilities'] : false;
		
		$options['default_country'] = get_field('wcmca_default_country', 'option');
		$options['default_country'] = $options['default_country'] ? $options['default_country'] : "no";
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $options;
	}
	public function get_custom_css_rules()
	{
		$css = array();
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$css['my_account_page'] = get_field('wcmca_custom_css_rules_my_account_page', 'option');
		$css['my_account_page'] = $css['my_account_page'] != null ? $css['my_account_page'] : "";
		
		$css['checkout_page'] = get_field('wcmca_custom_css_rules_checkout_page', 'option');
		$css['checkout_page'] = $css['checkout_page'] != null ? $css['checkout_page'] : "";
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $css;
	}
	public function get_style_options()
	{
		$css = array();
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		$css['default_badge_backgroud_color'] = get_field('wcmca_default_badge_backgroud_color', 'option');
		$css['default_badge_backgroud_color'] = $css['default_badge_backgroud_color'] != null ? $css['default_badge_backgroud_color'] : "#000000";
		
		$css['default_badge_text_color'] = get_field('wcmca_default_badge_text_color', 'option');
		$css['default_badge_text_color'] = $css['default_badge_text_color'] != null ? $css['default_badge_text_color'] : "#FFFFFF";
		
		$css['my_account_page_addresses_title_tag'] = get_field('wcmca_my_account_page_addresses_title_tag', 'option');
		$css['my_account_page_addresses_title_tag'] = $css['my_account_page_addresses_title_tag'] != null  ? $css['my_account_page_addresses_title_tag'] : 'h3';
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $css;
	}
	public function get_orders_list_options()
	{
		$options = array();
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$options['display_billing_address_column'] = get_field('wcmca_orders_list_display_billing_address_column', 'option');
		$options['display_billing_address_column'] = $options['display_billing_address_column'] ? $options['display_billing_address_column'] : false;
		
		$options['display_shipping_address_column'] = get_field('wcmca_orders_list_display_shipping_address_column', 'option');
		$options['display_shipping_address_column'] = $options['display_shipping_address_column'] ? $options['display_shipping_address_column'] : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $options;
	}
	public function send_notification_to_shipping_email()
	{
		$options = $this->shipping_per_product_related_options();
		
		if(!$options['add_shipping_email_field_to_shipping_addresses'])
			return false;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$result = get_field('wcmca_send_woocommerce_notification_to_shipping_email', 'option');
		$result = $result != null ? $result : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	
	public function send_notification_copty_to_billing_email()
	{
		$options = $this->shipping_per_product_related_options();
		
		if(!$options['add_shipping_email_field_to_shipping_addresses'])
			return false;
		
		add_filter('acf/settings/current_language',  array(&$this, 'cl_acf_set_language'), 100);
		
		$result = get_field('wcmca_send_woocommerce_notification_copty_to_billing_email', 'option');
		$result = $result != null ? $result : false;
		
		remove_filter('acf/settings/current_language', array(&$this,'cl_acf_set_language'), 100);
		
		return $result;
	}
	function cl_acf_set_language() 
	{
	  return acf_get_setting('default_language');
	}
}
?>