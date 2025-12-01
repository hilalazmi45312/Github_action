"use strict";

var wcmca_force_state_update = false;
var wcmca_preselected_state = "";
var wcmca_preselected_type = "";
jQuery(document).ready(function()
{
	wcmca_add_load_from_multiple_addresses_list_buttons();
	
	jQuery(document).on('click', 'div.order_data_column:nth(1) h3 a.edit_address', wcmca_on_edit_billing_details);
	jQuery(document).on('click', 'div.order_data_column:nth(2) h3 a.edit_address', wcmca_on_edit_shipping_details);
	
	jQuery(document).on('click', '.wcmca_load_additionl_addresses_button', wcmca_load_additional_addresses);
	jQuery(document).on('click','.wcmca_load_address_button' , wcmca_load_address);
	
	jQuery(document).on('click','.wcmca_make_order_item_editable' , wcmca_make_order_item_editable);
	
	jQuery(document).on('country_to_state_changed', wcmca_country_has_changed);
	jQuery(document).on('change','#_billing_country, #_shipping_country' , wcmca_country_has_changed);
});
function wcmca_make_order_item_editable(event)
{
	event.preventDefault();
	event.stopPropagation();
	
	if(confirm(wcmca_options.reload_page_alert_message))
	{
		var url = window.location.href;    
			if (url.indexOf('?') > -1){
			   url += '&wcmca_edit_order_item=1'
			}else{
			   url += '?wcmca_edit_order_item=1'
			}
			window.location.href = url;
	}
	
	return false;
}
function wcmca_on_edit_billing_details(event)
{
	jQuery('#wcmca_load_billing_additionl_addresses_button').show();
}
function wcmca_on_edit_shipping_details(event)
{
	jQuery('#wcmca_load_shipping_additionl_addresses_button').show();
}
function wcmca_country_has_changed(event)
{
	if(wcmca_force_state_update && wcmca_preselected_state != "" && wcmca_preselected_type != "")
	{
		if(jQuery('#_'+wcmca_preselected_type+'_state'))
		{
			jQuery('#_'+wcmca_preselected_type+'_state').val(wcmca_preselected_state); 
			try{
				var $generic_select2 = jQuery('#_'+wcmca_preselected_type+'_state').select2();
				$generic_select2.val(wcmca_preselected_state);  
			}catch(error){}
		}
		//reset data
		wcmca_force_state_update = false;
		wcmca_preselected_type = "";
		wcmca_preselected_state = "";
	}
}

function wcmca_load_additional_addresses(event)
{
	var type = jQuery(event.currentTarget).data('type');
	
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	var formType = jQuery(event.currentTarget).data('type');
	var user_id = jQuery('#customer_user').val();
	formData.append('action', 'wcmca_get_addresses_html_popup_by_user_id'); 
	formData.append('user_id', user_id); 
	formData.append('type', formType); 
	
	jQuery.ajax({
		url: ajaxurl +"?nocache="+random,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
			//UI	
			wcmca_show_addresses_list(data);
						
		},
		error: function (data) 
		{
			//console.log(data);
			//alert("Error: "+data);
		},
		cache: false,
		contentType: false,
		processData: false
	});	
}
function wcmca_load_address(event)
{
	wcmca_hide_additional_addresses_container(event);
	var id = jQuery(event.currentTarget).data('id');
	var type = jQuery(event.currentTarget).data('type');
	
	jQuery('#wcmca_address_details_'+id+' span').each(function(index, element)
	{
		//var value = jQuery(element).html();
		var value = jQuery(element).text().trim();
		var data = String(jQuery(element).data('code'));
		//var field_name = jQuery(element).attr('id').replace('wcmca_','');
		var field_name = jQuery(element).data('name');
		data = data !== 'undefined' && data.indexOf("-||-") !== -1 ? data.split("-||-") : data;
		
		//Checkbox
		if(jQuery("#_"+field_name).attr('type') == 'checkbox')
			jQuery("#_"+field_name).prop('checked', value != "" ? 'checked' : false);
		
		//Radio
		else if( data !== 'undefined' && typeof data.constructor !== 'Array' && jQuery("#_"+field_name+'_field input').first().attr('type') == 'radio' /*  jQuery("#_"+field_name+'_'+data).attr('type') == 'radio' */)
		{
			//console.log(jQuery("#wcmca_"+field_name+'_'+data));
			jQuery("#_"+field_name+'_'+data).prop('checked', 'checked');
		}
		//Text and select
		else	
			jQuery("#_"+field_name).val(data === 'undefined' ? value : data);
		
		if(field_name == 'billing_state' || field_name == 'shipping_state') //Doesn't work, need to update firing 'change' event
		{
			wcmca_preselected_state = data ;
			wcmca_preselected_type = type;
			jQuery('#_'+field_name).val(data);
			try{
				var $generic_select2 = jQuery('#_'+field_name).select2();
				$generic_select2.val(data);  
			}catch(error){}
		}
		else if(field_name == 'billing_country' || field_name == 'shipping_country')
		{
			//COUNTRY
			try{
				var $country_select2 = jQuery('#'+type+'_country').select2();
				$country_select2.val(data);  
			}catch(error){}
		} 
	});
	
	
	//1. set state select box
	wcmca_force_state_update = true;
	if(type == 'billing')
		jQuery('#_billing_country').trigger('change');
	else
		jQuery('#_shipping_country').trigger('change');
	
	return false;
}
