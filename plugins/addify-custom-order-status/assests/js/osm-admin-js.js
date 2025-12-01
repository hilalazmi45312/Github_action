jQuery(document).ready(function () {




	var selectGraphic = jQuery('.status-select').val();

	if (selectGraphic == 'icon') {
		jQuery('.status-icon').show();
	} else {
		jQuery('.status-icon').hide();
	}
	//onchange check if icon selected or text then show accordingly
	jQuery('.status-select').change(function () {
		if (this.value == 'icon') {
			jQuery('.status-icon').show();
		} else {
			jQuery('.status-icon').hide();
		}
	});
	// Check for duplicate status slug
	jQuery('#osm-slug').keyup(function () {
		var get_slug     = jQuery('#osm-slug').val();
		var $status_slug = osm_vars.status_slug;
		jQuery('.slug_warning').remove();
		if ($status_slug.includes(get_slug)) {
			jQuery('.slug_warning').remove();
			jQuery('#osm-slug').after('<span class="slug_warning" style="color: red; margin-left: 0px;"><br>Slug Already Exists!</span>');

		} else {
			jQuery('.slug_warning').remove();

		}
	});
	// Check for duplicate status name
	jQuery('#osm-name').keyup(function () {
		var get_name     = jQuery('#osm-name').val();
		var $status_name = osm_vars.status_name;
		jQuery('.slug_warning').remove();
		if ($status_name.includes(get_name)) {
			jQuery('.slug_warning').remove();
			jQuery('#osm-name').after('<span class="slug_warning" style="color: red; margin-left: 0px;"><br>Name Already Exists!</span>');

		} else {
			jQuery('.slug_warning').remove();

		}
	});
	// On clicking trash create an ajax response
	$ajax_url = my_ajax_delete.ajax_url;
	// $delete_nonce = my_ajax_delete.my_ajax_delete;
	jQuery('.osm_name a.submitdelete,.column-osm_name a.submitdelete').click(function (event) {

		event.preventDefault();
		var post_id = jQuery(this).parents('tr').attr('id');
		jQuery.ajax({
			type: "POST",
			url: $ajax_url,
			data: {
				action: 'delete_ajax_request',
				delete_nonce:my_ajax_delete.delete_nonce,
				post_id: post_id
			},
			success: function (data) {
			  
				if (data == 'status_not_found') {
					window.location.reload();
					jQuery(jQuery(".wrap h1")[0]).before('<p style="color:#a00" >Order Status has been move to trash successfully!</p>');
				} else {
					// This outputs the result of the ajax request (The Callback)
					jQuery(jQuery(".wrap h1")[0]).append(data);
			 
				} 
			},
			error: function (errorThrown) {
				window.alert(errorThrown);
			}
		});

	

	});

	// Clicking Close button Modal
	jQuery(document).on('click', 'span.delete-close', function () {
		jQuery('.modal').css("display", "none");
	});
	// Add the export button after first anchor
	jQuery(jQuery(".wrap h1")[0]).after(jQuery('#export-csv'));
	  // Add the import button after first anchor
	  jQuery(jQuery(".wrap h1")[0]).after(jQuery('#import-csv'));
	//file upload modal
	jQuery(document).on('click', '#import-csv', function () {
		jQuery('#fileModal').css("display", "block");
	});
	// Validation for csv file


});
