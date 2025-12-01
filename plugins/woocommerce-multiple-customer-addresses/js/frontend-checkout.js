"use strict";

var wcmca_force_state_change = false;
var wcmca_state_forced_value = "";
var wcmca_state_forced_value_type = "";
var wcmca_loading_in_progress = 0;
jQuery(document).ready(function()
{
	jQuery(document).on('change','.wcmca_address_select_menu',wcmca_on_address_select);
	jQuery(document).on('country_to_state_changed',wcmca_refresh_state_select); //no need to use
	wcmca_load_default_addresses();
	
	//Used for testing purpose
	
	//init 
	jQuery(document).on('updated_checkout', function() //needed for product address selector: on selection they have to be re-inited
	{		
		jQuery('.wcmca_product_address_select_menu').selectWoo(
			{ 
				width: '98%',
				containerCssClass: "wcmca-address-select-menu-container",
				dropdownCssClass: "wcmca-address-select-menu-dropdown" 
				
			});
	});
	jQuery('.wcmca_address_select_menu').selectWoo(
		{ 
			width: 'resolve',
			containerCssClass: "wcmca-address-select-menu-container",
			dropdownCssClass: "wcmca-address-select-menu-dropdown"
		}
	);
	jQuery( document.body ).trigger( 'update_checkout' ); 	
	
	wcmca_check_if_disable_forms();
});
function wcmca_check_if_disable_forms()
{
	if(wcmca_additional_address.disable_billing_form)
	{
		jQuery('.woocommerce-billing-fields__field-wrapper input, .woocommerce-billing-fields__field-wrapper span, .woocommerce-billing-fields__field-wrapper select').css('pointer-events','none');
		jQuery('.woocommerce-billing-fields__field-wrapper input, .woocommerce-billing-fields__field-wrapper select').css('background','#f7f7fc');
	}
	
	if(wcmca_additional_address.disable_shipping_form)
	{
		jQuery('.woocommerce-shipping-fields__field-wrapper input, .woocommerce-shipping-fields__field-wrapper span, .woocommerce-shipping-fields__field-wrapper select').css('pointer-events','none');
		jQuery('.woocommerce-shipping-fields__field-wrapper input, .woocommerce-shipping-fields__field-wrapper select').css('background','#f7f7fc');
	}
	
}
function wcmca_load_default_addresses()
{
	var type = new Array('billing', 'shipping');
	for(var i=0; i<type.length; i++)
	{
		if(jQuery('#wcmca_address_select_menu_'+type[i]).val() != null)
		{
			jQuery('#wcmca_address_select_menu_'+type[i]).trigger('change');
		}
	}
}
function wcmca_on_address_select(event)
{
	if(event.target.value == 'none')
		return;
	
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	var formType = jQuery(event.currentTarget).data('type');
	formData.append('action', 'wcmca_get_address_by_id'); 
	formData.append('address_id', event.target.value); 
	formData.append('wcmca_security_token', wcmca_additional_address.security_token); 
	
	
	//1. load ajax fields: call the ajax_get_address_by_id
	
	//UI
	wcmca_loading_address_start(formType);
	++wcmca_loading_in_progress;
	 //it shows only for the first load //if(wcmca_loading_in_progress == 1)
		jQuery( document.body ).trigger( 'update_checkout' );
	
	jQuery.ajax({
		url: wcmca_additional_address.ajax_url+"?nocache="+random,
		type: 'POST',
		data: formData,
		async: true,
		success: function (data) 
		{
			//UI	
			wcmca_loading_address_end(formType);
			//2. populate fields
			wcmca_fill_form_fields(data, formType);	
						
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
function wcmca_refresh_state_select(event)
{
	
	
	if(wcmca_force_state_change)
	{
		wcmca_force_state_change = false;
		if(jQuery("#"+wcmca_state_forced_value_type+"_state").is("select")) //is the select2
		{
			try{
				var $state_select2 = jQuery('#'+wcmca_state_forced_value_type+'_state').select2();
					$state_select2.val(wcmca_state_forced_value).trigger("change");
			}catch(error){}
		}
		else
			jQuery('#'+wcmca_state_forced_value_type+'_state').val(wcmca_state_forced_value);			
	}
	
	//Forced: happened that country shipping select was 100px (don't know why)
	jQuery('.select2-container').width('100%');
}
function wcmca_reset_checkout_input_text_fields(type)
{
	jQuery('.woocommerce-'+type+'-fields').find('input').each(function(index, element)
	{
		if(jQuery(element).attr('type') == 'checkbox' || jQuery(element).attr('type') == 'radio')
			jQuery(element).prop('checked', false);
		else
			jQuery(element).val("");
	});
}
function wcmca_fill_form_fields(data, formType) //billing || shipping
{
	if(!data)
		return;
	
	var result =  JSON.parse(data);
	
	jQuery.each(result, function(element_name, value)
	{
		
		if(typeof value === 'string')
		{
			value = value.indexOf("-||-") !== -1 ? value.split("-||-") : value;
			//Checkbox
			if(jQuery("#"+element_name).attr('type') == 'checkbox')
				jQuery("#"+element_name).prop('checked', 'checked');
			//Radio
			else if( value !== 'undefined' && typeof value.constructor !== 'Array' && jQuery("#"+element_name+'_field input').first().attr('type') == 'radio')
			{
				jQuery("#"+element_name+'_'+value).prop('checked', 'checked');
			}
			//Text and select
			else
			{
				jQuery('#'+element_name).val(value);
				try{
					if(jQuery("#"+element_name).prop("tagName").toLowerCase() == 'select')
					{
						var $generic_select2 = jQuery('#'+element_name).select2();
						$generic_select2.val(value);  
					}
				}catch(error){}
			}				
		}
	});
	
	//STATE
	wcmca_force_state_change = true;
	wcmca_state_forced_value = formType == 'billing' ? result.billing_state : result.shipping_state;
	wcmca_state_forced_value_type = formType;
	
	//COUNTRY
	try{
		if(jQuery('#'+formType+'_country').is('select'))
		{
			var $country_select2 = jQuery('#'+formType+'_country').select2();
			$country_select2.val(formType == 'billing' ? result.billing_country : result.shipping_country).trigger("change"); 
		}
		else 
			wcmca_refresh_state_select(null);
	}catch(error){}
	
}

function wcmca_repopulate_addresses_selectors(new_address_id)
{
	
	var random = Math.floor((Math.random() * 1000000) + 999);
	var formData = new FormData();
	var error = false;
	var data_to_send = new Array();
	formData.append('action', 'wcmca_reload_address_selectors_data');
	
	jQuery.ajax({
		url: wcmca_additional_address.ajax_url+"?nocache="+random,
		type: 'POST',
		data:formData,
		async: true,
		success: function (data) 
		{
			
			//UI	
			wcmca_hide_saving_loader();
			if(data === 'no')
				return;
			
			var result = JSON.parse(data);
			var prev_selected = "";
			
			//main billing: #wcmca_address_select_menu_billing
			if(jQuery('#wcmca_address_select_menu_billing').length > 0)
			{
				prev_selected = jQuery('#wcmca_address_select_menu_billing').val();
				jQuery('#wcmca_address_select_menu_billing').html(result.billing);
				jQuery('#wcmca_address_select_menu_billing').val(prev_selected);
			}
			
			//main shipping: #wcmca_address_select_menu_shipping
			if(jQuery('#wcmca_address_select_menu_shipping').length > 0)
			{
				prev_selected = jQuery('#wcmca_address_select_menu_shipping').val();
				jQuery('#wcmca_address_select_menu_shipping').html(result.shipping);
				jQuery('#wcmca_address_select_menu_shipping').val(prev_selected);
			}
			//item selectors: .wcmca_product_address_select_menu (id: data-unique-id)
			if(jQuery('.wcmca_product_address_select_menu').length > 0)
				jQuery('.wcmca_product_address_select_menu').each(function( index, item )
				{
					var unique_id = jQuery(this).data('unique-id');
					//var current_selector = jQuery(this);
					prev_selected = jQuery(this).val();
					jQuery.each(result.cart_items,  function( key, value )
					{
						if(key == unique_id)
						{
							jQuery(item).html(value);
							jQuery(item).val(prev_selected);
						}
					});
				});
				
			wcmca_popupate_address_form_with_last_added_address(new_address_id);	
								
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

function wcmca_popupate_address_form_with_last_added_address(new_address_id)
{
	jQuery(wcmca_selector_associated_to_last_add_address_button_clicked+" option").each(function()
	{
		//product address selector -> value="'.$item_cart_id."-||-".$address_id.'-||-billing"
		var splitted_string = jQuery(this).val().split("-||-");
		
		if(splitted_string.length == 1)
		{
			if(jQuery(this).val() == new_address_id)
				jQuery(wcmca_selector_associated_to_last_add_address_button_clicked).val(jQuery(this).val());
		}
		else //product address selector
		{
			if(splitted_string[1] == new_address_id)
				jQuery(wcmca_selector_associated_to_last_add_address_button_clicked).val(jQuery(this).val());
		}
		
		//ToDo: product address selector -> split value
	});
	
	jQuery(wcmca_selector_associated_to_last_add_address_button_clicked).trigger('change');
}