<?php 
class WCMCA_Customer
{
	public function __construct()
	{
	} 
	public function sort_by_name($result)
	{
		$address_internal_name = array_column($result, 'address_internal_name');
		try {
			@array_multisort($address_internal_name, SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $result);
		}catch(ValueError $e){}
		
		return $result;
	}
	public function get_customer_by_email($email)
	{
		$user = get_user_by( 'email', $email );
		if(!$user)
			return false;
		
		else return new WC_Customer($user->ID);
	}
	public function get_last_used_vat($user_id)
	{
		if(!isset($user_id) || !is_numeric($user_id))
			return "";
		
		$customer = new WC_Customer($user_id);
		return $customer->get_meta('billing_vat_number');
	}
	public function get_last_used_address_detail($user_id, $type = "billing", $detail = "name")
	{
		if(!isset($user_id) || !is_numeric($user_id))
			return "";
		
		$customer = new WC_Customer($user_id);
		$method = "get_".$type."_".$detail;
		$result = is_callable(array($customer, $method)) ? $customer->$method() : "";
		return $result;
	}
	
	private function unset_old_addresses_default($type, $old_addresses)
	{
		foreach($old_addresses as $key => $old_address)
			if(isset($old_address[$type."_is_default_address"]))
			{
				unset($old_addresses[$key][$type."_is_default_address"]);
			}
			
		return $old_addresses;
	}
	public function update_addresses($user_id, $address_id, $new_address)
	{
		$new_address = apply_filters('wcmca_before_updating_user_address', $new_address, $user_id, $address_id);
		$old_addresses = $this->get_addresses($user_id);
		$this->check_address_identifier_field($new_address);
		if($old_addresses)
		{
			//old default reset
			if(isset($new_address[$new_address['type']."_is_default_address"]))
			{
				//wcsts_var_dump($new_address[$new_address['type']."_is_default_address"]);
				$old_addresses = $this->unset_old_addresses_default($new_address['type'],$old_addresses);	
			}
			foreach($old_addresses as $key => $current_address)
					if($current_address['address_id'] == $address_id)
						$old_addresses[$key] = $new_address;	
		}
		else
			$old_addresses = array($address_id => $new_address);
		update_user_meta( $user_id, '_wcmca_additional_addresses', $old_addresses );
		
		do_action('wcmca_after_updating_user_address', $user_id, $address_id, $new_address);
	}
	public function delete_all_addresses($user_id)
	{
		if(!isset($user_id))
			return;
		
		do_action('wcmca_before_deleting_all_user_address', $user_id);
		
		delete_user_meta($user_id, '_wcmca_additional_addresses');
		
		do_action('wcmca_before_deleting_all_user_address', $user_id);
	}
	public function delete_addresses($user_id, $address_ids)
	{
		if(!isset($user_id))
			return;
		
		$ids_to_delete = is_array($address_ids) ? $address_ids : array($address_ids);
		
		foreach($ids_to_delete as $address_id)
		{
			do_action('wcmca_before_deleting_user_address', $user_id, $address_id);
			$old_addresses = $this->get_addresses($user_id);
			if($old_addresses)
				foreach($old_addresses as $key => $current_address)
					if($current_address['address_id'] == $address_id)
						unset($old_addresses[$key]);
			
			update_user_meta($user_id, '_wcmca_additional_addresses', $old_addresses );
			
			do_action('wcmca_after_deleting_user_address', $user_id, $address_id);
		}
	}
	public function duplicate_addresses($user_id, $address_id)
	{
		if(!isset($user_id))
			return;
		$addresses = $this->get_addresses($user_id);
		if(!$addresses)
			return;
		
		$address = array();
		$new_id = -1;
		foreach($addresses as $key => $current_address)
		{
			$new_id = $current_address['address_id'] > $new_id ? $current_address['address_id'] : $new_id;
			if($current_address['address_id'] == $address_id)
			{
				$address = $current_address;
			}
		}
		$new_id += 1;
		$address['address_id'] = $new_id;
		
		if(empty($address))
			return;
		
		if(isset($address[$address["type"]."_is_default_address"]))
			unset($address[$address["type"]."_is_default_address"]);
		
		do_action('wcmca_before_duplicating_user_address', $user_id, $address_id, $address);
		
		$addresses[] = $address;
		//$new_address_id = key( array_slice( $addresses, -1, 1, TRUE ) );
		
		update_user_meta($user_id, '_wcmca_additional_addresses', $addresses );
		
		do_action('wcmca_after_duplicating_user_address', $user_id, $address_id, $new_address_id, $address);
	}
	public function add_addresses($user_id, $new_address)
	{
		$new_address = apply_filters('wcmca_before_adding_new_user_address', $new_address, $user_id);
		$old_addresses = $this->get_addresses($user_id);
		$this->check_address_identifier_field($new_address);
		
		if($old_addresses)
		{
			//old default reset
			$is_default 												= in_array(wcmca_get_value_if_set($new_address, $new_address["type"]."_is_default_address", "no"), ["yes", 1]);
			$new_address[$new_address["type"]."_is_default_address"]	= $is_default ? 1 : 0;
			if($is_default)
			{
				$old_addresses 												= $this->unset_old_addresses_default($new_address['type'],$old_addresses);
			}
			$old_addresses[] = $new_address;
		}
		else
		{
			$old_addresses = array($new_address);
		}
		//Address id management
		end($old_addresses);
		$address_index = key($old_addresses);
		$address_id = uniqid();
		
		//updates the internal id reference
		$old_addresses[$address_index]['address_id'] = $address_id;
		
		if(!add_user_meta( $user_id, '_wcmca_additional_addresses', $old_addresses, true ))
			update_user_meta( $user_id, '_wcmca_additional_addresses', $old_addresses );
		
		do_action('wcmca_after_adding_new_user_address', $user_id, $new_address);
		
		return $address_id;
	}
	
	
	public function delete_guest_product_addresses()
	{
		global $wcmca_session_model;
		$wcmca_session_model->set_checkout_item_addresses(null);
	}
	//end
	private function check_address_identifier_field(&$new_address)
	{
		$type = $new_address['type'];
		$bad_chars = array("/", "\\", "'", '"');
		if(isset($new_address[$type.'_address_internal_name']))
		{
			$new_address['address_internal_name'] = $new_address[$type.'_address_internal_name'];
			unset($new_address[$type.'_address_internal_name']);
		}
		
		if(!isset($new_address['address_internal_name']))
			$new_address['address_internal_name'] = $new_address[$type.'_first_name']." ".
													$new_address[$type.'_last_name']." - ".
													(isset($new_address[$type.'_company']) && $new_address[$type.'_company'] != "" ? $new_address[$type.'_company']." - " : "").
													$new_address[$type.'_address_1']." ".
													//$new_address[$type.'_postcode']." ".
													(isset($new_address[$type.'_address_2']) && $new_address[$type.'_address_2'] != "" ? $new_address[$type.'_address_2']." - " : " - ").
													$new_address[$type.'_city'].
													(isset($new_address[$type.'_state']) && $new_address[$type.'_state'] != "" ? ", ".$new_address[$type.'_state'] : "");
		
		$new_address['address_internal_name'] = str_replace($bad_chars, "", $new_address['address_internal_name']);
	}
	public function get_addresses($user_id)
	{
		if(!isset($user_id) || !is_numeric($user_id))
			return "";
		
		$result = get_user_meta($user_id, '_wcmca_additional_addresses', true);
		if($result)
		{
			$result = $this->sort_by_name($result);
			
		}
		$result = !is_array($result) ? array() : $result;
		return $result;
	}
	public function get_addresses_by_type($user_id)
	{
		$result = array('billing'=>array(), 'shipping'=>array());
		
		$default_address_indexes = array('billing'=> -1, 'shipping'=> -1);
		foreach((array)$this->get_addresses($user_id) as $address_id => $address)
		{
			if(!isset($address['type']))
			{
				$this->delete_addresses($user_id, $address_id);
				continue;
			}
			
			$result[$address['type']][$address_id] = $address;
			
			/* Sets the default address as first address. Uncomment to enable this feature
			if(isset($address[$address['type']."_is_default_address"]))
				$default_address_indexes[$address['type']] = $address_id;*/
		}
		
		//sorting
		ksort($result['billing']);
		ksort($result['shipping']);
		
		foreach($default_address_indexes as $default_address_type => $default_index)
		{
			if($default_index < 0)
				continue;
			
			$elem_to_move = $result[$default_address_type][$default_index];
			unset($result[$default_address_type][$default_index]);
			array_unshift($result[$default_address_type], $elem_to_move);
		}
		
		return $result;
	}
	public function get_address_by_id($user_id, $address_id, $type = "")
	{	
		$result = array();
		if(!isset($user_id))
			return $result;
		
		if($address_id === "last_used_billing" || $address_id === "last_used_shipping" || $address_id === "checkout_data")
		{
			$prefix = $address_id === 'last_used_shipping' ? 'shipping' : 'billing';
			$customer = get_user_meta( $user_id);
			
			if($address_id === "checkout_data")
			{
				$prefix =  isset($_POST['ship_to_different_address']) ? 'shipping' : 'billing';
				$customer = $_POST;
			}
			
			
			//new method
			$result = array('type' => $prefix);
			foreach((array)$customer as $meta_field_name => $meta_field_value)
			{
				if(isset($meta_field_value[0]) && strpos($meta_field_name, $prefix."_") !== false)
				{
					$result[$meta_field_name] = stripslashes(is_array($meta_field_value) ? $meta_field_value[0] : $meta_field_value);
				}
			}
		}
		else
		{
			$user_data = get_user_meta($user_id, '_wcmca_additional_addresses', true);
			 
			if(is_array($user_data))
				foreach($user_data as $address_data)
					if($address_data['address_id'] == $address_id && ($type == "" || $address_data['type']))
						$result = $address_data;
		}
		
		return $result;
	}
	function is_admin_user() 
	{
		$user = wp_get_current_user();
		$allowed_roles = array('shop_manager', 'administrator');
		return !empty(array_intersect($allowed_roles, $user->roles ));
	}	
	public function belongs_to_not_allowed_roles($roles)
	{
		global $current_user;

		$is_logged = is_user_logged_in();
		
		if(!$is_logged && in_array('not_logged', $roles))
			return true;
		
		if($is_logged)
		{
		 $belongs_at_least_to_one_not_allowe_rule = false;
		 foreach($roles as $role)
		 {
			if(in_array($role, $current_user->roles))
				$belongs_at_least_to_one_not_allowe_rule = true;
		 }
		
		 return $belongs_at_least_to_one_not_allowe_rule;
		}
				
		return false;
	}
}
?>