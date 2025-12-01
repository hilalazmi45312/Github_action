"use strict";

var wcmca_is_edit_first_open = false;
var wcmca_preselected_state = "";
var select_country_ajax_request = 0;
var wcmca_ajax_loader;
var wcmca_selector_associated_to_last_add_address_button_clicked = null;
var init_add_new_addresses_button = true;
jQuery(document).ready(function()
{
	jQuery(document).on('click','#wcmca_close_address_form_button_billing, #wcmca_close_address_form_button_shipping' , wcmca_on_hide_address_form);
	jQuery(document).on('click','.wcmca_delete_address_button' , wcmca_delete_address);
	jQuery(document).on('click','.wcmca_bulk_delete_button' , wcmca_bulk_delete_address);
	jQuery(document).on('click','.wcmca_delete_all_button' , wcmca_delete_all_button);
	jQuery(document).on('click','.wcmca_duplicate_address_button' , wcmca_duplicate_address);
	try{
		//More compatible?
		document.getElementById("wcmca_save_address_button_shipping").addEventListener("click",wcmca_save_address_shipping); 
		document.getElementById("wcmca_save_address_button_billing").addEventListener("click", wcmca_save_address_billing); 
		var elements_by_class = document.getElementsByClassName("wcmca_edit_address_button"); 
		for (var i = 0; i < elements_by_class.length; i++) 
			elements_by_class[i].addEventListener('click', wcmca_edit_address, false);
		
	}
	catch(err)
	{
		jQuery(document).on('click','button#wcmca_save_address_button_billing' , wcmca_save_address_billing);
		jQuery(document).on('click','button#wcmca_save_address_button_shipping' , wcmca_save_address_shipping);
		jQuery(document).on('click','.wcmca_edit_address_button' , wcmca_edit_address);
	}
	jQuery(document).on('change','#wcmca_billing_country' , wcmca_on_billing_country_selection);
	jQuery(document).on('change','#wcmca_shipping_country' , wcmca_on_shipping_country_selection);
	jQuery('#wcmca_billing_country, #wcmca_shipping_country').trigger('change');
	
	wcmca_init_add_new_addresses_button();
	jQuery(document).on('updated_checkout', wcmca_init_add_new_addresses_button);
	
    //UI	
	if (typeof wcmca_init_custom_select2 == 'function')
		wcmca_init_custom_select2('country');
	
	//Focus on select2 search input fix
	jQuery.magnificPopup.instance._onFocusIn = function(e) 
	{
		// Do nothing if target element is select2 input
		if( jQuery(e.target).hasClass('select2-search__field') ) 
		{
		   return true;
		} 
		// Else call parent method
		jQuery.magnificPopup.proto._onFocusIn.call(this,e);
	}
});
function wcmca_create_popup_opened_to_edit_event()
{
	 var event; // The custom event that will be created

	  if (document.createEvent) {
		event = document.createEvent("HTMLEvents");
		event.initEvent("wcmca_address_popup_opened_to_edit", true, true);
	  } else {
		event = document.createEventObject();
		event.eventType = "wcmca_address_popup_opened_to_edit";
	  }

	  event.eventName = "wcmca_address_popup_opened_to_edit";

	  if (document.createEvent) {
		document.dispatchEvent(event);
	  } else {
		document.fireEvent("on" + event.eventType, event);
	  }
	 
}
function wcmca_init_add_new_addresses_button()
{
	/* if(!init_add_new_addresses_button)
		return; */
	
	init_add_new_addresses_button = false;
	
	//Some themes adds fancy handlers to buttons. This assures they are removed
	jQuery(".wcmca_add_new_address_button, #wcmca_add_new_address_button_billing, #wcmca_add_new_address_button_shipping").off();
	
	
	jQuery('a.wcmca_add_new_address_button, #wcmca_add_new_address_button_billing, #wcmca_add_new_address_button_shipping, .wcmca_edit_address_button').magnificPopup({
          type: 'inline',
		  showCloseBtn:false,
          preloader: false,
		  closeOnBgClick: false,
		  enableEscapeKey: false,
            callbacks: {
            open: function()
			{
				jQuery(document).trigger('wcmca_new_address_popup_open', this.st.el);
			},
			//This causes in some installation the edit address function to not properly work.
			close: function() 
			{
			   wcmca_reset_input_text_fields();
			  
            }
          } 
        });
	
	//Used on checkout to automatically select the just added address
	jQuery(document).on('click', '.wcmca_add_new_address_button, #wcmca_add_new_address_button_billing, #wcmca_add_new_address_button_shipping', function(event)
	{	
		if(jQuery(event.currentTarget).data('associated-selector') !== 'undefined')
			wcmca_selector_associated_to_last_add_address_button_clicked = "#"+jQuery(event.currentTarget).data('associated-selector');
		
		return true;
	});
}
function wcmca_reset_input_text_fields()
{
	jQuery("#wcmca_billing_country").val("");
	
	wcmca_end_loading_state_field('billing');
	wcmca_end_loading_state_field('shipping'); 
	
	jQuery('input.wcmca_input_field.input-text').val("");
	jQuery('input.input-checkbox:not(#ship-to-different-address-checkbox)').prop('checked', false);
	//only for my account, sets checkboxes according to span values
	jQuery('#wcmca_address_details_billing span, #wcmca_address_details_shipping span').each(function(index, element)
	{
		if(jQuery("#wcmca_"+field_name).attr('type') == 'checkbox' && jQuery("#wcmca_"+field_name).attr('default') == 1)
			jQuery("#wcmca_"+field_name).prop('checked', 'checked');
		else if(jQuery("#wcmca_"+field_name).attr('type') == 'checkbox')
			jQuery("#wcmca_"+field_name).prop('checked', false)
	});
	jQuery('#wcmca_address_id_billing').val(-1);
	jQuery('#wcmca_address_id_shipping').val(-1);
	
	//Reset the country selector 
	try{
		jQuery('#wcmca_billing_country').val(jQuery('#wcmca_billing_country option:first-child').val());
		jQuery('#wcmca_shipping_country').val(jQuery('#wcmca_shipping_country option:first-child').val());
	}catch(err){};
	jQuery('#wcmca_billing_country, #wcmca_shipping_country').trigger('change');
};
function wcmca_on_hide_address_form(event)
{
	if(typeof event !== 'undefined' && event != null)
	{
		event.stopImmediatePropagation();
		event.preventDefault();
	}
	//UI
	jQuery(".wcmca_error").hide();
	
	wcmca_hide_address_form();
	return false;
}
function wcmca_on_billing_country_selection(event)
{
	wcmca_on_country_selection('billing', event.target.value);
}
function wcmca_on_shipping_country_selection(event)
{
	wcmca_on_country_selection('shipping', event.target.value);
}
function wcmca_on_country_selection(type, id)
{
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	formData.append('action', 'wcmca_get_state_dropmenu'); 
	formData.append('type', type); 
	formData.append('country_id', id);
	
	if(id == "")
	{
		jQuery("#wcmca_billing_country").val("");
		//UI
		if(typeof wcmca_ajax_loader !== 'undefined')
			try{
				wcmca_ajax_loader.abort();
			}
			catch(e){};
		wcmca_end_loading_state_field(type);
		wcmca_remove_state_field(type);
		return;
	}
	
	//UI	
	wcmca_start_loading_state_field(type);
	select_country_ajax_request++;
	wcmca_ajax_loader = jQuery.ajax({
		url: wcmca_address_form.ajax_url+"?nocache="+random,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
			
			select_country_ajax_request--;
			var result = jQuery.parseJSON(data);
			jQuery('#wcmca_country_field_container_'+type).html(result.html);
			
			//UI
			wcmca_update_fields_options_and_attributes(result.field_attributes_and_options, type);
			if (typeof wcmca_init_custom_select2 == 'function')
				wcmca_init_custom_select2('state');
			
			if(select_country_ajax_request == 0  && wcmca_is_edit_first_open)
			{
				wcmca_is_edit_first_open = false;
				if(wcmca_preselected_state != "")
				{
					jQuery('#wcmca_'+type+'_state').val(wcmca_preselected_state);
					if(jQuery('#wcmca_'+type+'_state').is('select'))
					{
						try{
							var $state_select2 = jQuery('#wcmca_'+type+'_state').select2();
								$state_select2.val(wcmca_preselected_state).trigger("change");
								if (typeof wcmca_init_custom_select2 == 'function')
									wcmca_init_custom_select2('state');
						}catch(error){}
					}
				}
			}
			
			//UI	
			wcmca_end_loading_state_field(type);
						
		},
		error: function (data) 
		{
			select_country_ajax_request--;
			
		},
		cache: false,
		contentType: false,
		processData: false
	});
}
function wcmca_bulk_delete_address(event)
{
	const type = jQuery(event.currentTarget).data('type');
	let elem_to_delete = new Array();
	let user_id = 'none';
	jQuery('.wcmca_address_'+type+'_title_checkbox').each(function(index, elem)
	{
		if(elem.checked)
		{
			elem_to_delete.push(jQuery(elem).data('id'));
			user_id = user_id == 'none' && jQuery(elem).data('user-id') ? jQuery(elem).data('user-id') : user_id;
		}
	});
	
	if(elem_to_delete.length > 0 && confirm(wcmca_address_form.confirm_delete_message))
	{
		wcmca_send_delete_request(elem_to_delete, user_id);
	}
}
function wcmca_delete_all_button(event)
{
	const type = jQuery(event.currentTarget).data('type');
	const msg = wcmca_address_form.confirm_delete_all_message.replace("%s", type);
	let elem_to_delete = new Array();
	let user_id = 'none';
	if(!confirm(msg))
		return false;
	
	jQuery('.wcmca_address_'+type+'_title_checkbox').each(function(index, elem)
	{
		elem_to_delete.push(jQuery(elem).data('id'));
		user_id = user_id == 'none' && jQuery(elem).data('user-id') ? jQuery(elem).data('user-id') : user_id;
	});
	wcmca_send_delete_request(elem_to_delete, user_id);
}
function wcmca_delete_address(event)
{
	event.stopImmediatePropagation();
	event.preventDefault();
	
	wcmca_send_delete_request(jQuery(event.currentTarget).data('id'), jQuery(event.currentTarget).data('user-id') ? jQuery(event.currentTarget).data('user-id') : 'none')
	
	return false;
}

function wcmca_send_delete_request(ids, user_id)
{
	
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	formData.append('action', 'wcmca_delete_address');
	formData.append('wcmca_delete_id', ids);
	if(user_id !== 'undefined')
		formData.append('wcmca_user_id', user_id);
	formData.append('wcmca_security_token', wcmca_address_form.security_token);

	//UI
	wcmca_show_saving_loader();

	jQuery.ajax({
		url: wcmca_address_form.ajax_url+"?nocache="+random,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
			window.location.href = wcmca_address_form.current_url+'#wcmca_custom_addresses';
			location.reload(true);
						
		},
		error: function (data) 
		{
			
		},
		cache: false,
		contentType: false,
		processData: false
	});
		
	
}

function wcmca_duplicate_address(event)
{
	event.stopImmediatePropagation();
	event.preventDefault();
	
	var elem = jQuery(event.currentTarget);
	var random = Math.floor((Math.random() * 1000000) + 999);
	var id = jQuery(event.currentTarget).data('id');
	var formData = new FormData();
	formData.append('action', 'wcmca_duplicate_address');
	formData.append('wcmca_duplicate_id', id);
	formData.append('wcmca_security_token', wcmca_address_form.security_token);
	if(jQuery(event.currentTarget).data('user-id') && jQuery(event.currentTarget).data('user-id') !== 'undefined')
		formData.append('wcmca_user_id', jQuery(event.currentTarget).data('user-id'));
	
	if(confirm(wcmca_address_form.confirm_duplicate_message))
	{
		//UI
		wcmca_show_saving_loader();
	
		jQuery.ajax({
			url: wcmca_address_form.ajax_url+"?nocache="+random,
			type: 'POST',
			data: formData,
			async: true,
			success: function (data) 
			{
				window.location.href = wcmca_address_form.current_url+'#wcmca_custom_addresses';
				location.reload(true);
							
			},
			error: function (data) 
			{
				
			},
			cache: false,
			contentType: false,
			processData: false
		});
		
	}
	
	return false;
}
function wcmca_save_address_shipping(event)
{
	event.stopImmediatePropagation();
	event.preventDefault();
	wcmca_validate_fields_before_sending('shipping');
}
function wcmca_save_address_billing(event)
{
	event.stopImmediatePropagation();
	event.preventDefault();
	wcmca_validate_fields_before_sending('billing');
}
function wcmca_validate_fields_before_sending(type)
{
	//UI
	jQuery(".wcmca_error").fadeOut();
	var empty_fields_error = false;
	var exists_any_error = false;
	jQuery('div#wcmca_address_form_'+type+' input, div#wcmca_address_form_'+type+' select').each(function(index, obj)
	{
		if(jQuery(this).hasClass('not_empty') && (!this.value || this.value=="") && jQuery(this).is(":visible"))
		{
			wcma_highlight_empty_field(this);
			exists_any_error = empty_fields_error = true;
		}
	});
	
	//multiradio
	 jQuery('.multiradio-container').each(function(index, elem)
	 {
		 var is_checked = false;
		 jQuery(elem).children('input').each(function(index2,elem2)
		 {
			  is_checked = !is_checked ? jQuery(elem2).is(':checked') : is_checked;
		 });
		 if(!is_checked)
		 {
			wcma_highlight_empty_field(elem);
			exists_any_error = empty_fields_error = true;
		 }
	 });
	
	
	 
	//UI 
	wcmca_validation_fields_start(type);
	
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	var fields_to_check = new Array('email', 'postcode', 'phone');
	//Trimming
	for(var value of fields_to_check) //(var i = 0; i<fields_to_check.length; i++)
	{
		jQuery('#wcmca_'+type+'_'+value).val(jQuery.trim(jQuery('#wcmca_'+type+'_'+value).val()));
	}
	
	formData.append('action', 'wcmca_validate_fields');
	formData.append('email', jQuery('#wcmca_'+type+'_email').length != 0 && jQuery('#wcmca_'+type+'_email').prop('required') ? jQuery('#wcmca_'+type+'_email').val() : 'no_validation');
	formData.append('postcode',jQuery('#wcmca_'+type+'_postcode').length != 0? jQuery('#wcmca_'+type+'_postcode').val() : 'no_validation' );
	formData.append('phone',   jQuery('#wcmca_'+type+'_phone').length != 0 && jQuery('#wcmca_'+type+'_phone').prop('required') ? jQuery('#wcmca_'+type+'_phone').val() : 'no_validation');
	formData.append('country', jQuery('#wcmca_'+type+'_country').length != 0  ? jQuery('#wcmca_'+type+'_country').val() : '');
	formData.append('type', type);
	formData.append('wcmca_security_token', wcmca_address_form.security_token);
	
	jQuery.ajax({
		url: wcmca_address_form.ajax_url+"?nocache="+random,
		type: 'POST',
		data:formData,
		async: true,
		success: function (data) 
		{
			var result = jQuery.parseJSON(data);
			
			//UI 
			wcmca_validation_fields_end(type);
			if(empty_fields_error)
			{
				exists_any_error = true;
				jQuery("#wcmca_required_field_error").fadeIn();
			}
			if(result.email == false)
			{
				exists_any_error = true;
				jQuery("#wcmca_email_field_error").fadeIn();
			}
			if(result.postcode == false)
			{
				exists_any_error = true;
				jQuery("#wcmca_postcode_field_error").fadeIn();
			}
			if(result.phone == false)
			{
				exists_any_error = true;
				jQuery("#wcmca_phone_field_error").fadeIn();
			}
			
			if(!exists_any_error)
				wcmca_save_address(type)
						
		},
		error: function (data) 
		{
			
		},
		cache: false,
		contentType: false,
		processData: false
	});
	
	
}
function wcmca_save_address(type)
{ 
	//console.log("saving");
	var type = type;
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	var error = false;
	var data_to_send = new Array();
	formData.append('action', 'wcmca_save_new_address'); //WCMCA_Address.php: in case of guest users, it stores data on the session in order to compute the shipping packages
	formData.append('wcmca_type', type);
	formData.append('wcmca_security_token', wcmca_address_form.security_token);
	if(parseInt(wcmca_address_form.user_id) == 0 ) //Defined in frontend-checkout-product-address-guest.js . If Guest user then we are saving a Temp product address.
		formData.append('wcmca_cart_item_id', wcmca_current_guest_product_id);
	
	
	var serialized_data = jQuery('#wcmca_address_form_fieldset_'+type+' input, #wcmca_address_form_fieldset_'+type+' select').serializeArray();
	jQuery.each(serialized_data,function(key,input)
	{
		if(typeof data_to_send[input.name] === 'undefined')
			data_to_send[input.name] = new Array();
		data_to_send[input.name].push(input.value);
    }); 
	for (var elem_name in data_to_send)
		if(data_to_send[elem_name].length == 1)
		{
			try{
				formData.append(elem_name, data_to_send[elem_name][0]);
			 }catch(error){};
		}
		else
		{
		    try{
				formData.append(elem_name, data_to_send[elem_name].join("-||-"));
		    }catch(error){};
		}
	
	//UI
	wcmca_on_hide_address_form(null);
	wcmca_show_saving_loader(type);
	
	jQuery.ajax({
		url: wcmca_address_form.ajax_url+"?nocache="+random,
		type: 'POST',
		data:formData,
		async: true,
		success: function (data) 
		{
			//UI	
			setTimeout(function(){ 
									if(wcmca_address_form.is_checkout_page == 'no')
									{
										window.location.href = wcmca_address_form.current_url+'#wcmca_custom_addresses'; 
										location.reload(true); 
									}
									else 
									{
										if(parseInt(wcmca_address_form.user_id) > 0)
											wcmca_repopulate_addresses_selectors(data); //defined in frontend-checkout.js
										else 
										{
											wcmca_load_guest_address_preview(data) //defined in frontend-checkout-product-address-guest.js
										}
									}
								 }, 500);
						
		},
		error: function (data) 
		{
			
		},
		cache: false,
		contentType: false,
		processData: false
	});
	return false;
}
function wcmca_edit_address(event)
{
	var id = jQuery(event.currentTarget).data('id');
	var type = jQuery(event.currentTarget).data('type');
	jQuery('#wcmca_address_id_'+type).val(id);
	
	jQuery('#wcmca_address_details_'+id+' span').each(function(index, element)
	{
		var value = jQuery(element).data('value');//jQuery(element).text().trim();
		var data = String(jQuery(element).data('code'));
		var is_multiradio = String(jQuery(element).data('is-multiradio')) == 'yes';
		var field_name = jQuery(element).data('name');
		
		data = data !== 'undefined' && data.indexOf("-||-") !== -1 ? data.split("-||-") : data;
		
		//Checkbox
		if(jQuery("#wcmca_"+field_name).attr('type') == 'checkbox')
			jQuery("#wcmca_"+field_name).prop('checked', value != "" ? 'checked' : false);
		//Radio
		else if( data !== 'undefined' && typeof data.constructor !== 'Array' && !is_multiradio && jQuery("#wcmca_"+field_name+'_field input').first().attr('type') == 'radio' /*  jQuery("#wcmca_"+field_name+'_'+data).attr('type') == 'radio' */)
		{
			jQuery("#wcmca_"+field_name+'_'+data).prop('checked', 'checked');
		}
		//multiradio
		else if(is_multiradio && jQuery("input[name='wcmca_"+field_name+"']").length > 1)
		{
			jQuery("input[name='wcmca_"+field_name+"']").each(function(index, elem)
			{
				if(jQuery(elem).val() == data)
					jQuery(elem).prop('checked', 'checked');
			});
		}
		//Text, select and hidden
		else	
		{
			jQuery("#wcmca_"+field_name).val(data === 'undefined' ? value : data);
		}
		
		if(field_name == 'billing_state' || field_name == 'shipping_state')
		{
			wcmca_preselected_state = data ;
		}
	});
	
	//1. set state select box
	wcmca_is_edit_first_open = true;
	if(type == 'billing')
		jQuery('#wcmca_billing_country').trigger('change');
	else
		jQuery('#wcmca_shipping_country').trigger('change');
	
	wcmca_create_popup_opened_to_edit_event();
	return false;
}