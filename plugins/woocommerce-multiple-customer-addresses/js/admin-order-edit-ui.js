"use strict";

function wcmca_add_load_from_multiple_addresses_list_buttons()
{
	if(wcmca_options.hide_billing_addresses_selection == 'false')
		jQuery('div.order_data_column:nth(1) h3').append('<a href="#wcmca_additional_addresses_container" id="wcmca_load_billing_additionl_addresses_button" class="wcmca_load_additionl_addresses_button tips" data-type="billing" title="'+wcmca_options.load_additional_addresses_text_button+'" >'+wcmca_options.load_additional_addresses_text_button+'</a>');
	if(wcmca_options.hide_shipping_addresses_selection == 'false')
		jQuery('div.order_data_column:nth(2) h3').append('<a href="#wcmca_additional_addresses_container" id="wcmca_load_shipping_additionl_addresses_button" class="wcmca_load_additionl_addresses_button tips" data-type="shipping" title="'+wcmca_options.load_additional_addresses_text_button+'">'+wcmca_options.load_additional_addresses_text_button+'</a>');
	jQuery(document).on('click','#wcmca_close_button' , wcmca_hide_additional_addresses_container);
	
	
	jQuery( document ).tooltip(); 
	jQuery('.wcmca_load_additionl_addresses_button').magnificPopup({
          type: 'inline',
		  showCloseBtn:false,
          preloader: false,
            callbacks: {
            
			
			beforeOpen: function() {
              wcmca_reset_multiple_addresses_container();
            }
			
          } 
        });
		
}
function wcmca_reset_multiple_addresses_container()
{
	jQuery('#wcmca_additional_addresses_container').empty();
	jQuery('#wcmca_additional_addresses_container').html(wcmca_options.loader_html);
}
function wcmca_hide_additional_addresses_container(event)
{
	if(typeof event !== 'undefined' && event != null)
	{
		event.stopImmediatePropagation();
		event.preventDefault();
	}
	jQuery.magnificPopup.instance.close();
	return false;
}
function wcmca_show_addresses_list(data)
{
	jQuery('#wcmca_additional_addresses_container').html(data);
}
