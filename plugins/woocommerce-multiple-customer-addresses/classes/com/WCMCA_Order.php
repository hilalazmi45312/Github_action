<?php 
class WCMCA_Order
{
	public function __construct()
	{
		add_filter('woocommerce_order_formatted_shipping_address', array(&$this, 'add_shipping_extra_fields_to_order_formatted_shipping_address'), 10, 2); //WC_Order -> get_formatted_shipping_address
		add_filter('woocommerce_formatted_address_replacements', array(&$this, 'add_shipping_extra_fields_to_country_formatted_shipping_address'), 10, 2); //WC_Countris -> get_formatted_address
		add_filter('woocommerce_localisation_address_formats', array(&$this, 'add_shipping_extra_to_localisation_address_formats'), 10); //WC_Countris -> get_address_formats
		
	}
	public static function get_id($order)
	{
		if(version_compare( WC_VERSION, '2.7', '<' ))
			return $order->id;
		
		return $order->get_id();
	}
	public function get_vat_meta_field($order_id)
	{
		global $wcev_order_model;
		$billing_vat_number = /* isset($wcev_order_model) ? $wcev_order_model->get_vat_number($order_id) : */ get_post_meta($order_id, 'billing_vat_number',true);
	
		
		$billing_vat_number = $billing_vat_number ? $billing_vat_number : "";
		return $billing_vat_number;
	}
	public function save_vat_field($order_id, $value)
	{
		update_post_meta($order_id,'billing_vat_number', $value);
	}
	public function get_order_statuses($remove_prefix = true)
	{
		$statuses = wc_get_order_statuses();
		$result = array();
		if($remove_prefix)
		{
			foreach($statuses as $status_id => $status_label)
				$result[str_replace("wc-", "", $status_id)] = $status_label;
		}
		else 
			$result = $statuses;
		
		return $result;
	}
	 
	public function get_formatted_item_shipping_address($item, $is_html = true, $get_only_local_pickup_text = false)
	{
		global $wcmca_address_model,$wcmca_html_helper ;
		
		$type = 'shipping';
		$value = "";
		$address = array();
		$notes_field = "";
		if(is_object($item) && get_class($item) == 'WC_Order_Item_Meta')
			$meta_data = $item->meta;	
		else 
			$meta_data = version_compare( WC_VERSION, '2.7', '<' ) ? $item["item_meta"]  : $item->get_meta_data();
		
		if(!isset($meta_data))
			return "";
		
		if($item->get_meta('_wcmca_collect_from_store'))
			return '<br/><strong style="display:block; clear:both; margin-top: 0px;">'.esc_html__('Collect from store','woocommerce-multiple-customer-addresses').'</strong>';
		
		
		foreach($meta_data as $key => $single_meta)
		{
			if(!is_object($single_meta))
			{
				$type = $key == '_wcmca_type' ? $single_meta[0] : $type;
				if(strpos($key, '_wcmca_shipping_') !== false || strpos($key, '_wcmca_billing_') !== false)
					$address[str_replace('_wcmca_', "", $key)] = $single_meta[0];
				//Note field 
				if($key == '_wcmca_notes')
					$notes_field = $single_meta[0];
			}
			else // > 3.0
			{
				$type = $single_meta->key == '_wcmca_type' ? $single_meta->value : $type;
				if(strpos($single_meta->key, '_wcmca_shipping_') !== false || strpos($single_meta->key, '_wcmca_billing_') !== false)
					$address[str_replace('_wcmca_', "", $single_meta->key)] = $single_meta->value;
				//Note field 
				if($single_meta->key == '_wcmca_notes')
					$notes_field =  $single_meta->value;
				
			}
		}
		//Note field 
		$address['notes'] = $notes_field;
		
		if($get_only_local_pickup_text)
			return $notes_field ? '<br/><strong style="display:block; clear:both; margin-top: 0px;">'.esc_html__('Notes','woocommerce-multiple-customer-addresses').'</strong><br>'.$notes_field : "";
		
		if(!isset($address[$type.'_country']))
			return "";
		
		$address_fields = $wcmca_address_model->get_woocommerce_address_fields_by_type($type, $address[$type.'_country']);
		
		return $wcmca_html_helper->get_formatted_order_item_shipping_address($address, $address_fields, $type, $is_html);
	}
	//1. adds shipping and phone to the order shipping address (if any)
	function add_shipping_extra_fields_to_order_formatted_shipping_address($address, $order)
	{
		global $wcmca_option_model;
		$shipping_per_product_related_options = $wcmca_option_model->shipping_per_product_related_options();
		
		if($shipping_per_product_related_options['add_shipping_email_field_to_shipping_addresses'])
		{
			$shipping_email = $order->get_meta('_shipping_email');
			$address["shipping_email"] = $shipping_email;
		}
		
		if($shipping_per_product_related_options['add_shipping_phone_field_to_shipping_addresses'])
		{
			$shipping_phone = method_exists($order,'get_shipping_phone') ? $order->get_shipping_phone() : $order->get_meta('_shipping_phone');
			$address["shipping_phone"] = $shipping_phone;
		}
		
		return $address;
	}
	//2. modifies the format per each country
	function add_shipping_extra_to_localisation_address_formats($format_per_country_array)
	{
		global $wcmca_option_model;
		$shipping_per_product_related_options = $wcmca_option_model->shipping_per_product_related_options();
		
		//https://docs.woocommerce.com/wp-content/images/wc-apidocs/source-class-WC_Countries.html#464-513
		foreach($format_per_country_array as $key => $value)
		{
			//if($shipping_per_product_related_options['add_shipping_phone_field_to_shipping_addresses'])
				$format_per_country_array[$key] .= "\n{shipping_phone}";
			//if($shipping_per_product_related_options['add_shipping_email_field_to_shipping_addresses'])
				$format_per_country_array[$key] .= "\n{shipping_email}";
		}
		
		return $format_per_country_array;
	}
	//3. add the shipping info to the countries method that retrieves from order and displayes according the format in 2.
	function add_shipping_extra_fields_to_country_formatted_shipping_address($args, $order_passed_args)
	{
		//if(isset($order_passed_args['shipping_phone']))
		{
			$args['{shipping_phone}'] = isset($order_passed_args['shipping_phone']) ? $order_passed_args['shipping_phone'] : "";
		}
		//if(isset($order_passed_args['shipping_email']))
		{
			$args['{shipping_email}'] = isset($order_passed_args['shipping_email']) ? $order_passed_args['shipping_email'] : "";
		}
		
		return $args;
	}
}
?>