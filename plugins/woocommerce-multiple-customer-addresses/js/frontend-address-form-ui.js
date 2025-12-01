"use strict";

jQuery(document).ready(function()
{
	//bulk select controllers managment
	jQuery('.wcmca_bulk_delete_button').prop('disabled', true);	
	jQuery('.wcmca_address_title_checkbox').prop('checked', false);		
	jQuery(document).on('click','.wcmca_address_title_checkbox' , wcmca_manage_bulk_delete_display);
	jQuery(document).on('click','.mfp-container' , wcmca_close_popup);
});
function wcmca_close_popup(event)
{
	if(jQuery(event.target).hasClass("mfp-container"))
	{
		wcmca_reset_input_text_fields();
		jQuery.magnificPopup.close(); 
	}
}
function wcmca_manage_bulk_delete_display(event)
{
	const type = jQuery(event.currentTarget).data('type');
	let exists = false;
	jQuery('.wcmca_address_'+type+'_title_checkbox').each(function(index, elem)
	{
		if(elem.checked)
			exists = true;
	});
	jQuery('#wcmca_bulk_'+type+'_delete_button').prop('disabled', !exists);	
	
}
function wcmca_remove_state_field(type)
{
	jQuery('#wcmca_country_field_container_'+type).empty();
}
function wcmca_start_loading_state_field(type)
{
	jQuery('#wcmca_country_field_container_'+type).fadeOut(200, function(){jQuery('#wcmca_country_field_container_'+type).empty(); jQuery('.wcmca_preloader_image').fadeIn(); });
	jQuery('#wcmca_save_address_button_'+type).fadeOut();
}
function wcmca_end_loading_state_field(type)
{
	jQuery('.wcmca_preloader_image, #wcmca_save_address_button_'+type).stop();
	jQuery('.wcmca_preloader_image').fadeOut(0, function(){ jQuery('#wcmca_country_field_container_'+type).fadeIn(0, function(){jQuery('#wcmca_country_field_container_'+type).css("display", "block");}); });
	jQuery('#wcmca_save_address_button_'+type).fadeIn();
}
function wcmca_validation_fields_start(type)
{
	jQuery('#wcmca_save_address_button_'+type+", #wcmca_close_address_form_button_"+type).fadeOut(0);
	jQuery('#wcmca_validation_loader_'+type).fadeIn();
}
function wcmca_validation_fields_end(type)
{
	jQuery('#wcmca_save_address_button_'+type+", #wcmca_close_address_form_button_"+type).fadeIn();
	jQuery('#wcmca_validation_loader_'+type).fadeOut(0);
}
function wcmca_show_saving_loader(type)
{
	jQuery('.wcmca_add_new_address_button, .wcmca_remove_address_button').fadeOut(0);
	jQuery('.wcmca_saving_loader_image, .wcmca_loader_image, .wcmca_product_address_loader').fadeIn();
	jQuery('.wcmca_add_new_address_button, #wcmca_add_new_address_button_billing, #wcmca_add_new_address_button_shipping').prop('disabled', true);
	jQuery('.wcmca_edit_address_button, .class_action_sparator, .wcmca_delete_address_button, .wcmca_duplicate_address_button').fadeOut();
	
	var html_elem_to_use = document.getElementById("wcmca_address_form_container_"+type) != null ? jQuery('#wcmca_address_form_container_'+type).offset().top : jQuery('#wcmca_custom_addresses').offset().top;
	try{
	 jQuery('html, body').animate({
          scrollTop: html_elem_to_use.offset().top - 60 //#wcmca_address_form_container ?
        }, 1000);
	}catch(error){}
	 return false;
}
function wcmca_hide_saving_loader()
{
	jQuery('.wcmca_add_new_address_button').fadeIn();
	jQuery('.wcmca_saving_loader_image, .wcmca_loader_image, .wcmca_product_address_loader').fadeOut();
	jQuery('.wcmca_add_new_address_button, #wcmca_add_new_address_button_billing, #wcmca_add_new_address_button_shipping').prop('enabled', true);
	jQuery('.wcmca_edit_address_button, .class_action_sparator, .wcmca_delete_address_button, .wcmca_duplicate_address_button').fadeIn();
	
	 return false;
}
function toggle_reset_product_address_for_guest_button(id)
{
	jQuery('#wcmca_remove_address_button_'+id).toggle();
}
function wcma_highlight_empty_field(field)
{
	var original_border = jQuery(field).css('border');
	var original_border_width = jQuery(field).css('border-width');
	
	if(field.name === "wcmca_billing_country" || 
	   field.name === "wcmca_billing_state" || 
	   field.name === "wcmca_shipping_country" || 
	   field.name === "wcmca_shipping_state"  )
	   {
		jQuery("#s2id_"+field.name).css({ 'border': "1px #FF0000 solid " });
	   }
	jQuery(field).css({ 'border': "1px #FF0000 solid " });
	
	//Restores the original boder after 3 seconds
}
function wcmca_show_address_form()
{
	jQuery('footer, .fusion-header-wrapper').fadeOut();
	jQuery('html, body').animate({
          scrollTop: jQuery('#wcmca_form_popup_container').offset().top
        }, 1000);
}
function wcmca_hide_address_form()
{
	jQuery('footer, .fusion-header-wrapper').fadeIn();
	jQuery.magnificPopup.instance.close();
}
function wcmca_update_fields_options_and_attributes(options_and_attributes, type)
{
	var required_label_extra_html = ' <abbr title="required" class="required">*</abbr>';
	//add the default required classes to html elements
	jQuery('#wcmca_'+type+'_state, #wcmca_'+type+'_city, #wcmca_'+type+'_postcode').addClass('not_empty');
	jQuery('#wcmca_'+type+'_state_field, #wcmca_'+type+'_city_field, #wcmca_'+type+'_postcode_field').addClass('validate-required');
	jQuery('#wcmca_'+type+'_state, #wcmca_'+type+'_city, #wcmca_'+type+'_postcode').prop('required', 'required');
	//string
	jQuery('#wcmca_'+type+'_state_field label.wcmca_form_label').html(wcmca_address_form_ui.state_string+required_label_extra_html);
	jQuery('#wcmca_'+type+'_postcode_field label.wcmca_form_label').html(wcmca_address_form_ui.postcode_string+required_label_extra_html);
	jQuery('#wcmca_'+type+'_city_field label.wcmca_form_label').html(wcmca_address_form_ui.city_string+required_label_extra_html);
	
	if(options_and_attributes == null)
		return;
	
	for(var option in options_and_attributes)
	{
		var current_field = null;
		var current_element = null;
		switch(option)
		{
			case 'state': current_element = options_and_attributes.state; break;
			case 'postcode': current_element = options_and_attributes.postcode; break;
			case 'city': current_element = options_and_attributes.city; break;
		}
		if(current_element != null)
		{
			if(typeof current_element.required !== 'undefined' && current_element.required == false)
			{
				jQuery('#wcmca_'+type+'_'+option+'_field').removeClass('validate-required');
				jQuery('#wcmca_'+type+'_'+option).removeClass('not_empty');
				jQuery('#wcmca_'+type+'_'+option).removeProp('required');
				//Text without the "*" html
				if(typeof jQuery('#wcmca_'+type+'_'+option+'_field label.wcmca_form_label').html() !== 'undefined')
					jQuery('#wcmca_'+type+'_'+option+'_field label.wcmca_form_label').html(jQuery('#wcmca_'+type+'_'+option+'_field label.wcmca_form_label').html().replace(required_label_extra_html, ""));
			}
			if(typeof current_element.label !== 'undefined')
			{
				jQuery('#wcmca_'+type+'_'+option+'_field label.wcmca_form_label').html(current_element.label);
				if(typeof current_element.required === 'undefined' || current_element.required != false)
					jQuery('#wcmca_'+type+'_'+option+"_field label").html(current_element.label+required_label_extra_html);
			}
			if(typeof current_element.hidden !== 'undefined' && current_element.hidden == true)
			{
				jQuery('#wcmca_'+type+'_'+option+"_field").hide();
			}
			else
			{
				jQuery('#wcmca_'+type+'_'+option+"_field").show();
			}
		}
	}
}
