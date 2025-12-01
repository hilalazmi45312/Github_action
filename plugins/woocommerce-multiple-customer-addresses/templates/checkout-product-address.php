<?php 			
foreach($address_fields as $field_name => $woocommerce_address_field): 
		$woocommerce_address_field['type'] = !isset($woocommerce_address_field['type']) ? "text" : $woocommerce_address_field['type'];
		$select_field_data =  $field_value_to_show = "";
		
		if(isset($address[$field_name]))
		{
			//wcmca_var_dump($woocommerce_address_field);
			//Value to show check
			//$data_code = is_array($address[$field_name]) ? implode("-||-",$address[$field_name]) : $address[$field_name];
			/* $field_metadata = $woocommerce_address_field['type'] == 'select' ||  
							  $woocommerce_address_field['type'] == 'multiselect' || 
							  $woocommerce_address_field['type'] == 'checkbox' || 
							  $woocommerce_address_field['type'] == 'radio'  ? 'data-code="'.$data_code.'"' : ""; */
			
			//Support for Checkout Field Editor Pro
			$field_value_to_show = $woocommerce_address_field['type'] == 'select' && isset($woocommerce_address_field['options'][$address[$field_name]]) ? $woocommerce_address_field['options'][$address[$field_name]] : $address[$field_name];
			$values_to_check = is_array($address[$field_name]) ? $address[$field_name] : array($address[$field_name]);
			
			//Support for Checkout Field Editor Pro Advanced
			if(isset($woocommerce_address_field['options_object']))
			{
				$field_value_to_show_temp = array();
				foreach($woocommerce_address_field['options_object'] as $option_object)
						foreach($values_to_check as $value_to_check)
						if($option_object["key"] == $value_to_check)
								$field_value_to_show_temp[] = $option_object["text"];
							
				$field_value_to_show = count($field_value_to_show_temp) > 0 ? $field_value_to_show_temp : $field_value_to_show;
				
				
			}
			
			//Country field
			if($field_name == 'billing_country' || $field_name == 'shipping_country')
			{
				//$field_metadata = 'data-code="'.$address[$field_name].'"';
				$field_value_to_show = $wcmca_address_model->country_code_to_name($address[$field_name]);
			}
			//Country field
			elseif($field_name == 'billing_state' || $field_name == 'shipping_state')
			{
				//$field_metadata = 'data-code="'.$address[$field_name].'"';
				$field_value_to_show = $wcmca_address_model->state_code_to_name($address[$type.'_country'], $address[$field_name]);
				$field_value_to_show  = $field_value_to_show ? $field_value_to_show : $address[$field_name];
			}
			//Checkbox
			if($woocommerce_address_field['type'] == 'checkbox' )
			{
				$field_value_to_show = $field_value_to_show == 1 ? esc_html__('Yes','woocommerce-multiple-customer-addresses') : esc_html__('No','woocommerce-multiple-customer-addresses');
			}
		
			$content_class = 'wcmca_clear_both';
			if($field_name == 'billing_first_name' || $field_name == 'shipping_first_name' || $field_name == 'billing_last_name' || $field_name == 'shipping_last_name')
			{
				$content_class = $field_name;
			}
		?>
			<span class=" wcmca_product_address_content <?php echo $content_class;?>" id="wcmca_product_address_content_<?php echo $field_name; ?>">
				<?php echo is_array($field_value_to_show) ? implode(", ",$field_value_to_show) : $field_value_to_show; ?>
			</span>
		<?php 
		}
endforeach; 
?>