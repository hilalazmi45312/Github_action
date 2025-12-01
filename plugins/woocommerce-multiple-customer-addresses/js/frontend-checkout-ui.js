"use strict";

function wcmca_loading_address_start(formType)
{
	jQuery('footer, .fusion-header-wrapper').fadeOut();
	jQuery('#wcmca_loader_image_'+formType).fadeIn();
		
	if(wcmca_address_form.disable_smooth_scroll == 'false')
	jQuery('html, body').animate({
          scrollTop: jQuery('#wcmca_form_popup_container_billing').offset().top
        }, 1000)
}
function wcmca_loading_address_end(formType)
{
	jQuery('footer, .fusion-header-wrapper').fadeIn();
	jQuery('#wcmca_loader_image_'+formType).fadeOut();
}