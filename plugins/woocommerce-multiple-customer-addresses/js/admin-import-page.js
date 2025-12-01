"use strict";
let wcmca_csv_data;
let wcmca_chunk_size = 50; //min must be 2
let wcmca_last_row_chunk;
let wcmca_current_row_chunk;
let wcmca_total_data_to_send;
jQuery(document).ready(function()
{
	jQuery(document).on('change', '#csv_file_input', wcmca_on_file_selection);
	jQuery(document).on('click', '#wcmca_import_button', wcmca_start_import_process);
	jQuery(document).on('click', '#wcmca_import_another_button', wcmca_reload_page);
});
function wcmca_reload_page(event)
{
	location.reload();
}
function wcmca_on_file_selection(event)
{
	const files = event.target.files;
	if(!wcmca_browserSupportFileUpload())
	{
		alert(wcmca.not_compliant_browser_error);
		return;
	}
	Papa.parse(files[0], 
	{
		worker: true,
		complete: function(results) 
		{
			/* console.log(results.data);
			console.log(results); */
			if(results.errors.length > 0)
			{
				alert(wcmca.csv_file_format_error);
				return;
			}
			wcmca_total_data_to_send = results.data.length;
			wcmca_csv_data = results.data; //wcmca_csv_data[0] -> titles
			
		}
	});
	return false;
}
//1
function wcmca_start_import_process(event)
{
	if(wcmca_csv_data == null)
	{
		alert(wcmca.file_selection_error);
		return;
	}
	wcmca_setup_csv_data_to_send({first_run: true});
	return false;
}
//2
function wcmca_setup_csv_data_to_send(options)
{
	if(options != null && options.first_run)
	{
		wcmca_last_row_chunk = 1;
		wcmca_current_row_chunk = wcmca_chunk_size < wcmca_total_data_to_send ? wcmca_chunk_size : wcmca_total_data_to_send;
	}	
	
	var dataToSend =  [];
	dataToSend.push(wcmca_csv_data[0]);
	for(var i = wcmca_last_row_chunk;  i < wcmca_current_row_chunk; i++)
	{
		//console.log("Row: "+i);
		dataToSend.push(wcmca_csv_data[i]);
	}
	
	//UI
	wcmca_importing_data_transition_in();
	
	setTimeout(function(){wcmca_upload_csv(dataToSend)}, 1000);;
}
//3
function wcmca_upload_csv(dataToSend)
{
	var formData = new FormData();
	formData.append('action', 'wcmca_csv_import');  
	formData.append('merge_data', jQuery('#merge_data_selector').val());  
	formData.append('csv', JSON.stringify(dataToSend)); 
	formData.append('security', wcmca.security); 
	var perc_num = ((wcmca_current_row_chunk/wcmca_total_data_to_send)*100);
	perc_num = perc_num > 100 ? 100:perc_num;
	
	var perc = Math.floor(perc_num);
	jQuery('#ajax-progress').html("<p>computing data, please wait...<strong>"+perc+"% done</strong></p>");
	//UI
	wcmca_set_progress_bar_level(perc);
				
	jQuery.ajax({
		url: ajaxurl, //defined in php
		type: 'POST',
		data: formData,//{action: 'upload_csv', csv: data_to_send},
		async: true,
		success: function (data) {
			//alert(data);
			wcmca_check_response(JSON.parse(data));
		},
		error: function (data) {
			//alert("error: "+data);
			wcmca_check_response(JSON.parse(data));
		},
		cache: false,
		contentType: false,
		processData: false
	});
		
}
//4
function wcmca_check_response(data)
{
	//UI
	wcmca_append_status_text(data.message); 
	
	if(data.error_code == 0 && wcmca_current_row_chunk < wcmca_total_data_to_send)
	{
		wcmca_last_row_chunk = wcmca_current_row_chunk;
		wcmca_current_row_chunk += wcmca_chunk_size;
		if(wcmca_current_row_chunk > wcmca_total_data_to_send)
			wcmca_current_row_chunk = wcmca_total_data_to_send;
		
		setTimeout(wcmca_setup_csv_data_to_send, 1000);
	}
	else
	{
		wcmca_set_progress_bar_level(100);
		wcmca_importing_data_transition_out();
	}
}
function wcmca_browserSupportFileUpload() 
{
	var isCompatible = false;
	if (window.File && window.FileReader && window.FileList && window.Blob) {
	isCompatible = true;
	}
	return isCompatible;
}