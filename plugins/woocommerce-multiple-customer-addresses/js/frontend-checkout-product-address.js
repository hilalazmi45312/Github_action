"use strict";

var wcmca_selected_value = {};
var wcmca_address_note_value = {};
var wcmca_address_preview_value = {};
var wcmca_address_selection_event_to_ignore = 0;
var wcmca_product_address_before_change_value = {}; //not used
jQuery(document).ready(function()
{
	jQuery(document).on('change', '.wcmca_product_address_select_menu', wcmca_reload_product_address);
	jQuery(document).on('update_checkout', wmca_checkout_updated);
	jQuery(document).on('updated_checkout', wmca_restore_selected);
	jQuery(document).on('keyup', '.wcmca_product_field_note', wcmca_save_note_value);
	wmca_checkout_updated(null);
	wcmca_init_select_menus();
});
function wcmca_save_note_value(event)
{
	wcmca_address_note_value[jQuery(event.currentTarget).attr('id')] = jQuery(event.currentTarget).val();
}
function wmca_checkout_updated(event)
{
	
	setTimeout(wcmca_init_add_new_addresses_button, 2500);
}
function wcmca_init_select_menus()
{
	//Initial state: selected element must be the first of the list (select box)
	jQuery('.wcmca_product_address_select_menu').each(function(index,elem)
	{
		jQuery(elem).val(jQuery(jQuery(elem).attr('id')+" option:first").val());
	});
	wcmca_update_product_handling_fee_counter(wcmca_selected_value, 'registered');
}
function wmca_restore_selected()
{
	jQuery('.wcmca_product_address_select_menu').each(function(index,elem)
	{
		if(wcmca_selected_value[jQuery(elem).attr('id')])
		{
			wcmca_address_selection_event_to_ignore++;
			jQuery(elem).val(wcmca_selected_value[jQuery(elem).attr('id')]).trigger('change'); //SelectWoo: change is triggered to update UI 
		}
	});
	
	jQuery('.wcmca_product_address').each(function(index,elem)
	{
		if(wcmca_address_preview_value[jQuery(elem).attr('id')])
			jQuery(elem).html(wcmca_address_preview_value[jQuery(elem).attr('id')]);
	});
	
	jQuery('.wcmca_product_field_note').each(function(index,elem)
	{
		if(wcmca_address_note_value[jQuery(elem).attr('id')])
			jQuery(elem).val(wcmca_address_note_value[jQuery(elem).attr('id')]);
	});
} 
function wcmca_move_product_shipping_boxes_after_variation()
{
	jQuery('.wcmca_product_shipping_box').each(function(index,elem)
	{
		var result = jQuery(elem).parent();
		var last_index =  jQuery(elem).parent().children().size() - 1;
		if(result.length != 0 && jQuery(elem).index() != last_index)
		{
			jQuery(elem).appendTo(result);
		}
	});
}
//Used only for "collect from store" or "use current shipping address" in case the "multiple shipping addresses" option has been enabled
function wcmca_send_special_address_selection(ids, address_code)
{
	var formData = new FormData();
	formData.append('action', 'wcmca_load_product_address'); //WCMCA_Address.php: it also stores the data on session to be used for the shipping packages computation
	formData.append('cart_item_key', ids[0]);
	formData.append('address_id', address_code);
	var random = Math.floor((Math.random() * 1000000) + 999);
	
	//Sends a silent update
	jQuery.ajax({
		url: wcmca.ajax_url+"?nocache="+random,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
							
		},
		error: function (data) 
		{
			
		},
		cache: false,
		contentType: false,
		processData: false
	});
}
function wcmca_reload_product_address(event)
{
	//SelectWoo: on value restore, change event is manually triggered so handler has to bee ignored
	if(wcmca_address_selection_event_to_ignore > 0)
	{
		wcmca_address_selection_event_to_ignore--;
		return;
	}
	 
	var val = jQuery(event.currentTarget).val();
	var curent_element = jQuery(event.currentTarget)
	var ids = val.split("-||-"); //[0] = cart_item_key; [1] = address_id; [2] = address_type;
	var type = ids[2]; 
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	var id = jQuery(event.currentTarget).attr('id');

	//used to restore selected values once the form is resetted
	wcmca_selected_value[id] = val;
	
	if(type == "last_used_shipping")
	{
		//UI	
		wcmca_update_product_handling_fee_counter(wcmca_selected_value, 'registered');
		wcmca_address_preview_value['wcmca_product_address_'+ids[0]] = wcmca.default_address_message;
		jQuery('#wcmca_product_address_'+ids[0]).html(wcmca.default_address_message);
		wcmca_send_special_address_selection(ids, 'last_used_shipping');
		wcmca_product_address_before_change_value[id] = val;
		return false;
	}
	else if(type == "collect_from_store") 
	{
		//UI	
		wcmca_update_product_handling_fee_counter(wcmca_selected_value, 'registered');
		wcmca_address_preview_value['wcmca_product_address_'+ids[0]] = wcmca.collect_from_store_message;
		jQuery('#wcmca_product_address_'+ids[0]).html(wcmca.collect_from_store_message);
		wcmca_send_special_address_selection(ids, 'collect_from_store');
		wcmca_product_address_before_change_value[id] = val;
		return false;
	}
	
	//UI
	jQuery(curent_element).attr('disabled', 'disabled');
	
	formData.append('action', 'wcmca_load_product_address'); //WCMCA_Address.php: it also stores the data on session to be used for the shipping packages computation
	formData.append('cart_item_key', ids[0]);
	formData.append('address_id', ids[1]);
	formData.append('type', type);

	//UI
	jQuery('#wcmca_product_address_'+ids[0]).html(wcmca.product_address_loading);
	
	jQuery.ajax({
		url: wcmca.ajax_url+"?nocache="+random,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
			//UI	
			jQuery(curent_element).removeAttr('disabled');
			jQuery('#wcmca_product_address_'+ids[0]).html(data);
			wcmca_address_preview_value['wcmca_product_address_'+ids[0]] = data;
			wcmca_update_product_handling_fee_counter(wcmca_selected_value, 'registered');
			wcmca_product_address_before_change_value[id] = val;
		},
		error: function (data) 
		{
			jQuery(curent_element).removeAttr('disabled');
		},
		cache: false,
		contentType: false,
		processData: false
	});
}