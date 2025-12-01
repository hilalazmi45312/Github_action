<?php 
class WCMCA_Cart
{
	function __construct()
	{
		add_filter( 'woocommerce_add_cart_item_data', array($this, 'check_if_force_individual_cart_item_add_method'), 10, 3 ); 
		add_filter( 'woocommerce_add_cart_item', array($this, 'automatically_split_products_added_to_cart'), 10, 2 ); 
		add_filter( 'woocommerce_cart_calculate_fees', array($this, 'add_shipping_product_handling_fees') ); 
		add_action('wp_ajax_wcmca_update_product_handling_fee_counter', array($this, 'update_shipping_product_handling_fee_counter'));
		add_action('wp_ajax_nopriv_wcmca_update_product_handling_fee_counter', array($this, 'update_shipping_product_handling_fee_counter'));
	    add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'generate_packages' ) );
	    //add_filter( 'template_redirect', array( $this, 'init' ) ); //no regular output and hence no header sent before "template_redirect" on the front end. For sessions on the back end too, use the action "wp_loaded" to cover both.
		
	}
	function init()
	{
		if (session_status() == PHP_SESSION_NONE) 
			session_start();

	}
	function update_shipping_product_handling_fee_counter()
	{	
		global $wcmca_session_model;
		if(isset($_POST['num_of_fees']))
		{
			if($_POST['num_of_fees'] == 0) 
			{
				/* error_log("Ajax: resetting"); */
				$wcmca_session_model->reset_shipping_product_handling_fee_counter();
			}
			else 
			{
				$product_handling_fee_counter = intval($_POST['num_of_fees']);
				$product_handling_fee_counter = $product_handling_fee_counter < 0 ? 0 : $product_handling_fee_counter;
				$wcmca_session_model->set_shipping_product_handling_fee_counter($product_handling_fee_counter);
			}
		}
		wp_die();
		 
	}
	
	
	function check_if_force_individual_cart_item_add_method($cart_item_data, $product_id, $variation_id)
	{
		global $wcmca_option_model;
		
		if($wcmca_option_model->add_product_distinctly_to_cart())
		{
		
			$cart_item_data['wcmca_unique_id'] = wcmca_random_string();
		}
		return $cart_item_data;
	}
	function automatically_split_products_added_to_cart($product_data, $cart_item_key)
	{
		global $wcmca_option_model;
		
		if(!$wcmca_option_model->automatically_split_product_by_cart_quantity() )
			return $product_data;
				
		$product_id = $product_data['variation_id'] == 0 ? $product_data['product_id'] : $product_data['variation_id'];
		if($product_data['quantity'] > 1)
		{
			for($i = 1; $i < $product_data['quantity']; $i++)
			{
				WC()->cart->add_to_cart( $product_id );
			}
			$product_data['quantity'] = 1;
		}
		return $product_data;
	}
	function add_shipping_product_handling_fees()
	{
		global $woocommerce, $wcmca_option_model, $wcmca_session_model;
		
		if ( !defined( 'DOING_AJAX' ) && !@is_checkout())
		{
			$wcmca_session_model->reset_shipping_product_handling_fee_counter();
			return;
		} 
		$fee_data = $wcmca_option_model->get_product_shipping_fee_options();
		
		if(!$fee_data['handling_product_shipping_fee'])
			return;
		
		$fee_value = 0;
		$product_handling_fee_counter = $wcmca_session_model->get_shipping_product_handling_fee_counter();
		
		foreach($fee_data['fee_ranges'] as $range)
			if( $product_handling_fee_counter >= $range['min'] && ($range['max'] == 0 || $product_handling_fee_counter  <= $range['max']))
				$fee_value = $range['fee'];
		
		$fee_value *= $product_handling_fee_counter; 
		
		if($fee_value != 0)
			$woocommerce->cart->add_fee( esc_html__('Handling fee', 'woocommerce-multiple-customer-addresses'), $fee_value, $fee_data['fee_taxable']);
	}
	function generate_packages($original_packages)
	{
		global $wcmca_session_model, $wcmca_address_model, $wcmca_customer_model, $wcmca_option_model;
		$packages = array();
		$collect_from_store = false;
		$options = $wcmca_option_model->shipping_per_product_related_options();
		if(!$options['multiple_addresses_shipping'])
			return $original_packages;
		$is_guest = get_current_user_id() > 0 ? false : true;
		
		
		$addresses = !$is_guest ? $wcmca_session_model->get_cart_item_address() : $wcmca_session_model->get_guest_cart_addresses();
		$items = WC()->cart->get_cart();
		$address_sorted = !$is_guest ? array() : array('last_used_shipping'=>array(),'collect_from_store'=>array());
		
		
		/*
			Note: the registered user address array has this format: array('id' => $address_id, 'type' => $type), where the "id" is the id of the address or 'last_used_shipping' or 'collect_from_store'
				  the guest user the following: array({address_data}) (if any address) or 'last_used_shipping' / 'collect_from_store' (if no address associated with the current profile)
		*/
		
		/*** Init ***/
		//Fill with missing items 
		foreach($items as $item_key => $values)
		{
			if(!isset($addresses[$item_key]))
				$addresses[$item_key] =  !$is_guest ? array('id' => 'last_used_shipping', 'type' => 'shipping') : 'last_used_shipping'; //See the note
		}
		foreach ( $addresses as $cart_item_key => $address_data ) 
		{				
			if(!$is_guest)
			{
				if(!isset($address_sorted[$address_data['id']]))
					$address_sorted[$address_data['id']] = array();
				$address_sorted[$address_data['id']][] = array('address_type' =>$address_data['type'], 'cart_item_key' => $cart_item_key );
			}
			else //Guest
			{
				//$address_sorted should use as index the address id. In case of guest, the address has no id, so it is just an array index
				if(is_string($address_data)) //collect_from_store || last_used_shipping
					$address_sorted[$address_data][] = array('address_type' =>'shipping', 'cart_item_key' => $cart_item_key, 'address' => '');
				else
				{
					$already_existing = false;
					$value_to_insert = array('address_type' => "billing", 'cart_item_key' => $cart_item_key, 'address' => $address_data );
					
					foreach($address_sorted as $key => $value) //check if the address already existis
					{
						if(isset($value[0]) && $wcmca_address_model->compare_address($value[0]['address_type'], $value[0]['address'], $value_to_insert['address']))
						{
								$address_sorted[$key][] = $value_to_insert;
								$already_existing = true;
						}
					}
					
					if(!$already_existing)
						$address_sorted[] = array(0 => $value_to_insert);
				}
				
				/* Format of the array stored in session ($address_data)
				 [0] => Array
					(
						[address_type] => shipping
						[cart_item_key] => b3e0e0c325b75092af7bf3488402a644
						[address] => Array
							(
								[type] => billing
								[cart_item_id] => b3e0e0c325b75092af7bf3488402a644
								[address_id] => -1
								[user_id] => 0
								[billing_first_name] => John
								[billing_last_name] => Doe
								[billing_company] => 
								[billing_country] => IT
								[billing_state] => PI
								[billing_address_1] => Via 
								[billing_address_2] => 
								[billing_postcode] => 223333
								[billing_city] => Pisa
								[billing_phone] => 5555
								[billing_email] => xxxxxx@gmail.com
							)

					)
				*/
				
			}
		}
		if($is_guest)
		{
			if(!$address_sorted['collect_from_store'])
				unset($address_sorted['collect_from_store']);
			if(!$address_sorted['last_used_shipping'])
				unset($address_sorted['last_used_shipping']);
		}
		/*** End ***/
		foreach ( $address_sorted as $address_id => $address_data ) 
		{
			
			$address_fields_names = $wcmca_address_model->get_address_field_names();
			
			if($address_id != 'last_used_shipping' && $address_id != 'collect_from_store')
			{
				$shipping_address = !$is_guest ? $wcmca_address_model->get_address_by_id($address_id) : $address_data[0]['address'];
				if(!$shipping_address)
					continue;
				
				foreach($address_fields_names as $field_name)
						$address_fields_names[$field_name] = wcmca_get_value_if_set($shipping_address, $shipping_address['type']."_" . $field_name, "");
						
				/* To manually perform the assignement
					$prefix 	= $shipping_address['type']."_";
					$country    = $shipping_address[$prefix . 'country'];
					$state      = wcmca_get_value_if_set($shipping_address, $prefix . 'state', "");
					$postcode   = $shipping_address[$prefix . 'postcode'];
					$city       = $shipping_address[$prefix . 'city'];
					$address_1  = $shipping_address[$prefix . 'address_1'];
					$address_2  = wcmca_get_value_if_set($shipping_address, $prefix . 'address_2', "");
					$first_name = $shipping_address[$prefix . 'first_name'];
					$last_name  = $shipping_address[$prefix . 'last_name'];
					$company    = $shipping_address[$prefix . 'company']; 
				*/
			}
			else if($address_id == 'collect_from_store')
			{
				$collect_from_store = true;
				continue;
			}
			else if($address_id == 'last_used_shipping')
			{
				/* Decomment in order to use last shipping address stored in the user profile, otherwise it will be used the one just posted via the checkout form 
				if(!$is_guest)
				{
					$customer = new WC_Customer(get_current_user_id());
					foreach($address_fields_names as $field_name => $value)
					{
						$method_name = "get_shipping_{$field_name}";
						if ( is_callable( array( $customer,  $method_name) )  ) 
							$address_fields_names[$field_name] = $customer->$method_name();
					}
				}
				else */
				{
					//Data posted via the checkout form
					$address_array_to_process = $_POST; //$_POST['billing_{field}'] is instead used after the checkout competes
					$prefix = isset($_POST['ship_to_different_address']) ? "shipping_" : "billing_";	
					if(isset($_POST['post_data'])) //When performing the checkout it is empty and returns no data, So pefore compelting must be used "post_data" inde and userialize it
					{
						parse_str($_POST['post_data'], $address_array_to_process);	
						$prefix = isset($address_array_to_process['ship_to_different_address']) ? "shipping_" : "billing_";	

											
					} 
					
					foreach($address_fields_names as $field_name => $value)
							$address_fields_names[$field_name] = wcmca_get_value_if_set($address_array_to_process,  $prefix.$field_name, "");
				}
			}
			
			
			
			$contents = array();
			foreach($address_data as $cart_item_data)
			{
				$cart_item    = WC()->cart->get_cart_item( $cart_item_data['cart_item_key'] );
				$wc_product = wcmca_get_value_if_set($cart_item, 'data', false);
				if ( ! $cart_item || $wc_product->get_virtual() || $wc_product->get_downloadable())
					continue;
				$product_id   = $cart_item['product_id'];
				$variation_id = $cart_item['variation_id'];
				$variation    = $cart_item['variation'];
				$quantity     = $cart_item['quantity'];
				$line_total   = $cart_item['line_total'];
				$product_data = $cart_item['data'];

				$contents[$cart_item_data['cart_item_key'] ] = array(
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'variation'    => $variation,
					'quantity'     => $quantity,
					'line_total'   => $line_total,
					'data'         => $product_data
				);
			}
			
			$packages[] = array(
				'contents'        => $contents,
				'contents_cost'   => array_sum( wp_list_pluck( $contents, 'line_total' ) ),
				'applied_coupons' => WC()->cart->applied_coupons,
				'user'            => array( 'ID' => get_current_user_id() ),
				'destination'     => array(
					'country'    => $address_fields_names['country'],
					'state'      => $address_fields_names['state'],
					'postcode'   => $address_fields_names['postcode'],
					'city'       => $address_fields_names['city'],
					'address'    => $address_fields_names['address_1'],
					'address_2'  => $address_fields_names['address_2'],
					'first_name' => $address_fields_names['first_name'],
					'last_name'  => $address_fields_names['last_name'],
					'company'    => $address_fields_names['company']
				)
			);
			
		}
		
		
		//debug: 
		/* wcmca_var_debug_dump(empty($packages));  */
		return empty($packages) && !$collect_from_store ? $original_packages : $packages;
	}
}
?>