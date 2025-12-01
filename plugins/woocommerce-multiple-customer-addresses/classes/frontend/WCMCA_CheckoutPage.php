<?php 
class WCMCA_CheckoutPage
{
	var $popup_already_rendered = false;
	public function __construct()
	{
		add_filter('plugins_loaded', array(&$this,'init'));
		add_action('wp_footer', array( &$this,'add_custom_css'),99);
		add_action('wp_head', array( &$this,'init_page'),99); //former wp_head
		add_action('woocommerce_after_checkout_form', array(&$this, 'add_popup_html'));
		add_action('woocommerce_before_checkout_billing_form', array(&$this, 'add_billing_address_select_menu'));
		add_action('woocommerce_before_checkout_shipping_form', array(&$this, 'add_shipping_address_select_menu'));
		
		add_action('woocommerce_checkout_update_order_meta', array( &$this, 'save_checkout_extra_field' ));
		
		//Shipping per product woocommerce_checkout_cart_item_quantity
		add_filter('woocommerce_checkout_cart_item_quantity', array(&$this,'add_product_shipping_dropdown_menu'), 10, 3); //woocommerce_cart_item_name
		
		
		add_filter('woocommerce_get_cart_item_from_session', array( &$this, 'retrieve_address_data_from_posted_data' ),10,3); 
		
		add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'add_shipping_address_meta_data_on_shipping_item' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ) );
	}
	public function add_shipping_address_meta_data_on_shipping_item( $item, $package_key, $package, $order ) 
	{
		global $wcmca_option_model;
		$options = $wcmca_option_model->shipping_per_product_related_options();
		if(!$options['multiple_addresses_shipping'])
			return;
		
		$item->add_meta_data( 'wcmca_shipping_destination', $package['destination'] );
		$item->add_meta_data( 'wcmca_shipping_contents', $package['contents'] );
		
	}
	public function init()
	{
		add_action('woocommerce_checkout_create_order_line_item', array( &$this, 'update_order_item_meta' ),10,4);
	}
	public function add_custom_css()
	{	
		global $wcmca_html_helper, $wcmca_cart_model, $wcmca_session_model;
		if(@is_checkout())
		{
			$wcmca_html_helper->render_custom_css('my_account_page');
			if(!wp_doing_ajax())
				$wcmca_session_model->reset_all_cart_session_data();
		}
	}
	public function checkout_order_processed()
	{
		global $wcmca_session_model;
		$wcmca_session_model->reset_all_cart_session_data();
	}
	public function init_page()
	{
		global $wcmca_cart_model, $wcmca_session_model;
		$wcmca_session_model->reset_shipping_product_handling_fee_counter();
	}
	function add_popup_html($checkout)
	{
		global $wcmca_html_helper,$wcmca_address_model, $wcmca_session_model;
		if($this->popup_already_rendered)
			return;
		$this->popup_already_rendered = true;
		$wcmca_html_helper->render_address_form_popup();
		$wcmca_html_helper->render_custom_css('checkout_page');
	}
	function add_billing_address_select_menu($checkout)
	{
		global $wcmca_html_helper,$wcmca_address_model,$wcmca_option_model;
		
		if(!get_current_user_id())
			return;
		$wcmca_html_helper->render_address_select_menu();
	}
	function add_shipping_address_select_menu($checkout)
	{
		global $wcmca_html_helper,$wcmca_option_model;
		
		if(!get_current_user_id())
			return;
		$wcmca_html_helper->render_address_select_menu('shipping');
	}
	public function save_checkout_extra_field($order_id)
	{
		global $wcmca_option_model, $wcev_order_model, $wcmca_order_model;
		if(!$wcmca_option_model->is_vat_identification_number_enabled())
			return;
		
		if(!isset($wcev_order_model) && isset($_POST['billing_vat_number']))
			$wcmca_order_model->save_vat_field($order_id, $_POST['billing_vat_number']);
	}
	
	public function add_product_shipping_dropdown_menu($text, $cart_item, $cart_item_key)
	{
		global $wcmca_html_helper, $wcmca_option_model;
			
		$options = $wcmca_option_model->shipping_per_product_related_options();	
		if((!$options['product_address_show_selector_even_for_one_item'] && WC()->cart->get_cart_contents_count() < 2) || !$wcmca_option_model->shipping_per_product())
			return $text;
		
		if(!$wcmca_option_model->can_curent_user_select_product_address())
			return $text;
		
		echo $text."<br/>";
		if(get_current_user_id() > 0)
			$wcmca_html_helper->render_address_select_menu_for_product($cart_item_key, $cart_item);
		else 
			$wcmca_html_helper->render_add_product_address_for_guest_user($cart_item_key, $cart_item);
	}
	
	public function check_guest_item_addresses($checkout_fields)
	{
		global $wcmca_customer_model, $wcmca_session_model;
		wcmca_var_dump($wcmca_session_model->get_guest_cart_product_addresses());
		wc_add_notice( "WCMCA stop" ,'error');					
	}
	public function retrieve_address_data_from_posted_data($session_data, $values, $key)
	//public function retrieve_address_data_from_posted_data($posted_data)
	{
		global $wcmca_customer_model, $wcmca_session_model;
		if(!isset($_POST) || empty($_POST))
			return $session_data;
		
		$posted_data = $_POST;
		if(isset($posted_data['wcmca_product_address']))
		{
			foreach($posted_data['wcmca_product_address'] as $product_address)
			{
				$ids = explode("-||-",$product_address); //[0] = cart_item_key; [1] = address_id; [2] = address_type
				//[2]: not used. The [1] is an unique ID so, it will be lately used to load the right one without the need if it the address is a shipping or billing type.
				
				if($key == $ids[0] && $ids[2] == 'collect_from_store')
					 $session_data['wcmca_shipping_address'] = 'collect_from_store'; 
				else if($key == $ids[0] && $ids[2] != 'same_as_billing')
					 $session_data['wcmca_shipping_address'] = $ids[1]; 
			}
		}
		if(isset($posted_data['wcmca_product_fields'])) //used for extra fields. For now only for notes
		{
			foreach($posted_data['wcmca_product_fields'] as $item_key => $field_array)
			{
				if($key == $item_key)
				 {
					 if(!isset($session_data['wcmca_shipping_fields']))
						 $session_data['wcmca_shipping_fields'] = array();
					  
					 foreach($field_array as $field_name => $field_value )
						$session_data['wcmca_shipping_fields'][$field_name] = $field_value;
				 }
			}
		}
		if(isset($posted_data['wcmca_product_address_for_guest_user']))
		{
			/* To iterate the guest product data, you can use the following code: 
			foreach($posted_data['wcmca_product_address_for_guest_user'] as $guest_item_key => $guest_item_value)
			{
				 if($guest_item_value != "same_as_billing" && $key == $item_key)
				{
					$address = $wcmca_session_model->get_guest_cart_product_address($key);
					foreach($address as $field_name => $field_value )
						$session_data['wcmca_shipping_fields'][$field_name] = $field_value;
				} 
			}*/
			$session_data['wcmca_product_address_for_guest_user'] = $posted_data['wcmca_product_address_for_guest_user'];
		}
		
		//To stop the checkout process, use the following function: wc_add_notice( esc_html__('Stop test','woocommerce-multiple-customer-addresses') ,'error');  
		return $session_data; 
	}
	//Product address data
	function update_order_item_meta($order_item_product, $cart_item_key, $item_values, $order  )									
	{
		$item_id = $order_item_product->get_id();
		global $wcmca_customer_model, $wcmca_address_model, $wcmca_session_model;
		
		$values = $this->retrieve_address_data_from_posted_data($_POST, $item_values, $cart_item_key);	
		//Registered users
		if(isset($values['wcmca_shipping_address']))
		{
			
			if( $values['wcmca_shipping_address'] == 'collect_from_store')
			{
				//old method: wc_add_order_item_meta($item_id, '_wcmca_collect_from_store', true, true);
				$order_item_product->add_meta_data('_wcmca_collect_from_store', true, true);
			}
			else
			{
				//Note the "user the current shipping address" option has the following id "last_used_billing"
				$address = $wcmca_address_model->get_address_by_id($values['wcmca_shipping_address']);
				
				if(!empty($address))
					foreach($address as $key => $field)
					{
						//old method: wc_add_order_item_meta($item_id, '_wcmca_'.$key, $field, true);
						$order_item_product->add_meta_data('_wcmca_'.$key, $field, true);
					}
			}
		}
		
		//Only for special field "Notes" (but for now reads and stores all the fields)
		if(isset($values['wcmca_shipping_fields']))
		{
			foreach($values['wcmca_shipping_fields'] as  $field_name => $field_value)
			{
				//old method: wc_add_order_item_meta($item_id, '_wcmca_'.$field_name, $field_value, true);
				$order_item_product->add_meta_data('_wcmca_'.$field_name, $field_value, true);
			}
		}
		//Guest users
		if(isset($values['wcmca_product_address_for_guest_user']))
		{
			$none_was_found = true;
			foreach($values['wcmca_product_address_for_guest_user'] as $guest_item_key => $guest_item_value)
			{
				if($guest_item_key == $cart_item_key)
				{
					if( $guest_item_value == 'collect_from_store')
					{
						$none_was_found = false;				
						//old method: wc_add_order_item_meta($item_id, '_wcmca_collect_from_store', true, true);
						$order_item_product->add_meta_data('_wcmca_collect_from_store', true, true);
					}
					else if( $guest_item_value != 'same_as_billing')
					{
						$none_was_found = false;					
						$address = $wcmca_session_model->get_guest_cart_product_address($guest_item_key);
						foreach($address as $field_name => $field_value )
						{
							//old method: wc_add_order_item_meta($item_id, '_wcmca_'.$field_name, $field_value, true);
							$order_item_product->add_meta_data('_wcmca_'.$field_name, $field_value, true);
						}
					}
				}
			}
			if($none_was_found)
			{
				$address = $wcmca_customer_model->get_address_by_id(0, 'checkout_data');
				foreach($address as $field_name => $field_value )
				{
					wc_add_order_item_meta($item_id, '_wcmca_'.$field_name, $field_value, true);
					$order_item_product->add_meta_data($item_id, '_wcmca_'.$field_name, $field_value, true);
				}
			}
		}
		
	}

}
?>