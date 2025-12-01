"use strict";

var wcmca_address_preview_value = {};
var wcmca_address_pick_up_value = {};
var wcmca_address_note_value = {};
var wcmca_current_guest_product_id = 0;
var wcmca_product_address_has_been_set = {};
jQuery(document).ready(function()
{
	jQuery(document).on('update_checkout', wmca_checkout_updated);
	jQuery(document).on('updated_checkout', wmca_restore_selected);
	jQuery(document).on('wcmca_new_address_popup_open', wcmca_save_current_guest_product_id); //triggered by wcmca_init_add_new_addresses_button()
	jQuery(document).on('click', '.wcmca_remove_address_button', wcmca_reset_address_for_product_guest);
	jQuery(document).on('keyup', '.wcmca_product_field_note', wcmca_save_note_value);
	jQuery(document).on('click', '.wcmca_collect_from_store', wcmca_collect_from_store);
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
	wcmca_update_product_handling_fee_counter(wcmca_product_address_has_been_set, "guest"); //no additional addresses
}
function wcmca_save_current_guest_product_id(event, element)
{
	wcmca_current_guest_product_id = jQuery(element).data('cart-item-id');
	
	return true;
}
function wmca_restore_selected()
{
	jQuery(".wcmca_collect_from_store").prop('disabled', false);
	jQuery('.wcmca_product_address').each(function(index,elem)
	{
		var curr_elem_id = jQuery(elem).data('unique-id');
		if(wcmca_address_preview_value[curr_elem_id])
		{
			//toggle_reset_product_address_for_guest_button(curr_elem_id);
			jQuery(elem).html(wcmca_address_preview_value[curr_elem_id]);
			jQuery('#product_address_for_guest_'+curr_elem_id).val(curr_elem_id);
			//is pick up from store?
			if(wcmca_address_pick_up_value.hasOwnProperty(curr_elem_id))
			{
				jQuery('#product_address_for_guest_'+curr_elem_id).val('collect_from_store');
				jQuery("#wcmca_actions_container_"+curr_elem_id).fadeOut(0);
				jQuery("#wcmca_collect_from_store_checkbox_"+curr_elem_id).prop('checked', true);
			}
		}
		else 
		{
			jQuery('#product_address_for_guest_'+curr_elem_id).val('same_as_billing');
			jQuery("#wcmca_collect_from_store_checkbox_"+curr_elem_id).removeAttr('checked');
		}
		
	});
	
	jQuery('.wcmca_product_field_note').each(function(index,elem)
	{
		if(wcmca_address_note_value[jQuery(elem).attr('id')])
			jQuery(elem).val(wcmca_address_note_value[jQuery(elem).attr('id')]);
	});
} 
function wcmca_load_guest_address_preview(data)
{
	
	//UI	
	wcmca_hide_saving_loader();
	toggle_reset_product_address_for_guest_button(wcmca_current_guest_product_id);

	jQuery('#product_address_for_guest_'+wcmca_current_guest_product_id).val(wcmca_current_guest_product_id);
	
	jQuery('#wcmca_product_address_'+wcmca_current_guest_product_id).html(data);
	wcmca_address_preview_value[wcmca_current_guest_product_id] = data;
	wcmca_product_address_has_been_set[wcmca_current_guest_product_id] = true;
	wcmca_update_product_handling_fee_counter(wcmca_product_address_has_been_set, "guest");
}
function wcmca_final_action_to_reset_address(event, cart_item_key, action_type)
{
	event.stopImmediatePropagation();
	event.preventDefault();
	
	var id = jQuery(event.currentTarget).data('cart-item-id');
	wcmca_update_product_handling_fee_counter(wcmca_product_address_has_been_set, "guest");

	
	jQuery('#product_address_for_guest_'+id).val('same_as_billing');
	
	//UI
	toggle_reset_product_address_for_guest_button(id);	
	
	jQuery('#wcmca_product_address_'+id).html(wcmca_guest.default_address_message);
	delete wcmca_address_preview_value[id];
	
	
	//Remote update 
	var formData = new FormData();
	formData.append('action', 'wcmca_update_guest_user_cart_item_address'); //WCMCA_Address.php: it also stores the data on session to be used for the shipping packages computation
	formData.append('cart_item_key', cart_item_key);
	formData.append('type', action_type);
	formData.append('security', wcmca_guest.security);
	
	var random = Math.floor((Math.random() * 1000000) + 999);
	
	//Sends a silent update
	jQuery.ajax({
		url: wcmca_guest.ajax_url+"?nocache="+random,
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
function wcmca_reset_address_for_product_guest(event)
{
	var id = jQuery(event.currentTarget).data('cart-item-id');
	delete wcmca_product_address_has_been_set[id];
	wcmca_final_action_to_reset_address(event, id, "last_used_shipping");
	return false;
}
function wcmca_collect_from_store(event)
{
	var id = jQuery(event.currentTarget).data('cart-item-id');
	
	//Force UI
	jQuery(".wcmca_collect_from_store").prop('disabled', true);
	if(event.currentTarget.checked)
	{
		delete wcmca_product_address_has_been_set[id];
		wcmca_final_action_to_reset_address(event, id, "collect_from_store");
		jQuery("#wcmca_actions_container_"+id).fadeOut();
		jQuery('#product_address_for_guest_'+id).val('collect_from_store');
		jQuery('#wcmca_product_address_'+id).html(wcmca_guest.collect_from_store_message);
		wcmca_address_preview_value[id] = wcmca_guest.collect_from_store_message;
		wcmca_address_pick_up_value[id] = true;
	}
	else 
	{
		wcmca_product_address_has_been_set[id] = true;
		delete wcmca_address_pick_up_value[id];
		jQuery("#wcmca_actions_container_"+id).fadeIn();
		wcmca_final_action_to_reset_address(event, id, "last_used_shipping");
	}
	
	//ToDo: trigger action to update (store collection) shipping package (guest)
	
	return false;
}