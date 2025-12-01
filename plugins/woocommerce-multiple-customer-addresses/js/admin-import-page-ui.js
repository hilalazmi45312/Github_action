"use strict";
function wcmca_importing_data_transition_in()
{
	 jQuery('#wcmca_instruction').fadeOut(500, function(){
		 jQuery('#wcmca_loader_container').fadeIn();
	 });
}
function wcmca_importing_data_transition_out()
{
 jQuery('#wcmca_notice_box').append("<p>"+wcmca.upload_complete_message+"</p>");
 jQuery('#wcmca_import_another_button').fadeIn();
}
function wcmca_append_status_text(text)
{
 
 if(typeof text == 'object')
  {
	  for(let i = 0; i<text.length; i++)
		  jQuery('#wcmca_notice_box').append("<p class='error_message'>"+text[i]+"</p>");
  }
  else
	jQuery('#wcmca_notice_box').append("<p class='error_message'>"+text+"</p>");
}
function wcmca_set_progress_bar_level(perc)
{
 jQuery( "#wcmca_progress_bar" ).animate({'width':perc+"%"});
}