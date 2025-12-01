"use strict";

jQuery(document).ready(function() {

	 
});
function wcmca_init_custom_select2(type) // state || country
{
	if(typeof type === 'undefined')
		return;
	try{
		
		jQuery('.wcmca-'+type+'-select2').each(function(index, obj)
		{
			//console.log(jQuery(obj).attr('id'));
			if(jQuery(obj).is('select'))
				jQuery(obj).select2({ 
				width: 'resolve',
				allowClear: true,
				id: jQuery(obj).attr('id'),
				dropdownCssClass: "wcmca-increase-z-index"
			   });
		});
	}catch(Error){};
}