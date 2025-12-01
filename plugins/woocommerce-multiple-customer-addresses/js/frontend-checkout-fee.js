"use strict";

function wcmca_update_product_handling_fee_counter(selected_values, type)
{
	/* Counting the number of fees */
	var num_of_fees = 0;
	if(type == 'registered')
	{
		for(var id in selected_values)
		{ 
			var values = selected_values[id].split("-||-"); //[0] = cart_item_key; [1] = address_id; [2] = address_type;
			if( values[2] != 'last_used_shipping' && values[2] != 'collect_from_store' )
				num_of_fees++;
		}
	}
	else
	{
		console.log("wcmca_update_product_handling_fee_counter");
		console.log(selected_values);
		
		for(var id in selected_values)
		{
			num_of_fees++;
		}
	}
	var formData = new FormData();
	formData.append('action', 'wcmca_update_product_handling_fee_counter');
	formData.append('num_of_fees', num_of_fees);
	
	
	
	jQuery.ajax({
		url: wcmca_fee.ajax_url,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
			jQuery( document.body ).trigger( 'update_checkout' );		
		},
		error: function (data) 
		{
			
		},
		cache: false,
		contentType: false,
		processData: false
	});
}