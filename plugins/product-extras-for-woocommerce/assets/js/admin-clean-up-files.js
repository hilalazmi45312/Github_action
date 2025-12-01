/**
 * Used by Clean Up Uploaded Files
 * @since 3.23.1
 */
const pewc_clean_up_files = {

	directories: [],
	scanning_text: '',
	deleting_text: '',
	done_text: '',
	done_scanning_text: '',
	done_button: '',
	current_process: '',
	total_deleted: 0,
	files_to_delete: 0,
	confirm_before_delete: true,

	init: function() {

		jQuery( document ).ready( function(){

			window.onbeforeunload = pewc_clean_up_files.warn_exit_if_processing;
			pewc_clean_up_files.confirm_before_delete = pewc_admin_clean_up_files_vars.confirm_before_delete === 'yes' ? true : false;

			// Delete files before date
			jQuery( '#pewc_clean_up_before_date_delete' ).on( 'click', function( e ){
				pewc_clean_up_files.delete_before_date_start();
			});

			// get a list of files to delete
			jQuery( 'body' ).on( 'pewc_clean_up_get_files_in_dir', function( object, current_index ){
				pewc_clean_up_files.get_files_to_delete( current_index );
			});

			jQuery( '.pewc-clean-up-files-confirm' ).on( 'click', function( e ){
				// confirm delete after scanning
				e.preventDefault();
				pewc_clean_up_files.confirm_after_scan();
			});

			jQuery( '.pewc-clean-up-files-cancel, .pewc-clean-up-files-done-button' ).on( 'click', function( e ){
				// cancel/Complete process
				e.preventDefault();
				pewc_clean_up_files.cancel_process();
			});

			// deletes files via trigger
			jQuery( 'body' ).on( 'pewc_clean_up_files_in_dir', function( object, current_index ) {
				pewc_clean_up_files.delete_files_in_dir( current_index );
			});

			// Setup datepicker
			jQuery( 'input.pewc-clean-up-before-date' ).datepicker({
				maxDate: pewc_admin_clean_up_files_vars.maxDate
			});
			jQuery( 'input.pewc-clean-up-before-date' ).on( 'change', function(){
				if ( jQuery( this ).val() != '' ) {
					jQuery( '#pewc_clean_up_before_date_delete' ).show();
				} else {
					jQuery( '#pewc_clean_up_before_date_delete' ).hide();
				}
			});
		});

	},

	delete_before_date_start() {

		pewc_clean_up_files.current_process = 'scanning directories';
		pewc_clean_up_files.total_deleted = 0;
		pewc_clean_up_files.files_to_delete = 0;
		pewc_clean_up_files.hide_notification();
		pewc_clean_up_files.reset_files_list();
		pewc_clean_up_files.show_one_cancel_button();
		jQuery( '.pewc-clean-up-files-done' ).hide();
		jQuery( '.pewc-clean-up-files-done-scanning' ).hide();
		jQuery( '.pewc-clean-up-completed' ).hide();
		jQuery( '.pewc-clean-up-before-date-container' ).hide();
		jQuery( '.pewc-clean-up-files-confirm' ).hide();
		jQuery( '.pewc-clean-up-files-scanning' ).show();

		// Send a signal to the server to scan the product-extras directory for all subdirectories?
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'pewc_clean_up_files_scandir',
				security: jQuery( '#pewc_clean_up_files_nonce' ).val(),
			},
			success: function( response ) {
				if ( response.length > 0 && pewc_clean_up_files.current_process === 'scanning directories' ) {
					jQuery( '.pewc-clean-up-files-scanning' ).hide();
					pewc_clean_up_files.directories = response;
					if ( pewc_clean_up_files.confirm_before_delete ) {
						jQuery( 'body' ).trigger( 'pewc_clean_up_get_files_in_dir', [ 0 ] );
					} else {
						pewc_clean_up_files.reset_files_list();
						jQuery( 'body' ).trigger( 'pewc_clean_up_files_in_dir', [ 0 ] );
					}
				} else {
					if ( pewc_clean_up_files.current_process === 'scanning directories' ) {
						jQuery( '.pewc-clean-up-files-no-directories' ).show();
					}
					jQuery( '.pewc-clean-up-completed' ).show();
					jQuery( '.pewc-clean-up-before-date-container' ).show();
					jQuery( '.pewc-clean-up-files-scanning' ).hide();
					pewc_clean_up_files.current_process = '';
				}
			},
			error: function( response ) {
				alert( 'Error! ' + response.status + ' ' + response.statusText );
				jQuery( '.pewc-clean-up-completed' ).show();
				jQuery( '.pewc-clean-up-before-date-container' ).show();
				jQuery( '.pewc-clean-up-files-scanning' ).hide();
				pewc_clean_up_files.current_process = '';
			}
		});

	},

	reset_files_list: function() {
		var delete_button = jQuery( '.pewc-clean-up-files-list' ).find( 'p.deleted' );
		jQuery( '.pewc-clean-up-files-list' ).html( '' ).append( delete_button ).hide();
		jQuery( '.pewc-clean-up-files-list' ).find( 'p.deleted' ).show();
	},

	get_before_date: function() {

		var datestr = jQuery( 'input.pewc-clean-up-before-date' ).val();
		var date = jQuery( 'input.pewc-clean-up-before-date' ).datepicker( 'getDate' );
		var mm = date.getMonth() + 1; // getMonth() is zero-based
		var dd = date.getDate();
		var ymd = [
			date.getFullYear(),
			( mm > 9 ? '' : '0' ) + mm,
			( dd > 9 ? '' : '0' ) + dd
		].join( '-' );

		return [ datestr, ymd ];

	},

	get_files_to_delete: function( index ) {

		pewc_clean_up_files.current_process = 'scanning files';
		var dirs = pewc_clean_up_files.directories;
		if ( index < dirs.length ) {
			var beforeDate = pewc_clean_up_files.get_before_date();
			if ( pewc_clean_up_files.scanning_text == '' ) {
				// save the original text
				pewc_clean_up_files.scanning_text = jQuery( '.pewc-clean-up-files-scanning-files' ).text();
			}
			var scanning_text = pewc_clean_up_files.scanning_text;
			scanning_text = scanning_text.replace( '[before_date]', beforeDate[0] );
			var percentage = Math.round(((index / dirs.length) * 100)) +"%";
			scanning_text = scanning_text.replace( '[percentage]', ' (' + percentage + ')' );
			jQuery( '.pewc-clean-up-files-scanning-files' ).text( scanning_text ).show();

			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'pewc_clean_up_files_get_files_to_delete',
					security: jQuery( '#pewc_clean_up_files_nonce' ).val(),
					before_date: beforeDate[1],
					directory: dirs[index]
				},
				success: function( response ) {
					if ( response.success && pewc_clean_up_files.current_process === 'scanning files' ) {
						if ( response.data.files.length > 0 ) {
							pewc_clean_up_files.files_to_delete += parseInt( response.data.files.length );
							jQuery( '.pewc-clean-up-files-list' ).find( 'p.deleted' ).hide(); // hide this paragraph because this is used when deleting files
							for ( var i in response.data.files ) {
								jQuery( '.pewc-clean-up-files-list' ).append( '<p>' + response.data.files[i] + '</p>' );
							}
							jQuery( '.pewc-clean-up-files-list' ).show();
						}
						jQuery( 'body' ).trigger( 'pewc_clean_up_get_files_in_dir', [ index+1 ] );
					} else {
						if ( pewc_clean_up_files.current_process === 'scanning files' ) {
							pewc_clean_up_files.show_notification( response.data.message );
						}
						jQuery( '.pewc-clean-up-files-scanning-files' ).hide();
						jQuery( '.pewc-clean-up-completed' ).show();
						jQuery( '.pewc-clean-up-before-date-container' ).show();
						jQuery( 'input.pewc-clean-up-before-date' ).val( '' ).trigger( 'change' );
						pewc_clean_up_files.current_process = '';
					}
				},
				error: function( response ) {
					alert( 'Error! ' + response.status + ' ' + response.statusText );
					jQuery( '.pewc-clean-up-files-scanning-files' ).hide();
					jQuery( '.pewc-clean-up-completed' ).show();
					jQuery( '.pewc-clean-up-before-date-container' ).show();
					pewc_clean_up_files.current_process = '';
				}
			});
		} else {
			// Done scanning files
			var beforeDate = pewc_clean_up_files.get_before_date();
			if ( pewc_clean_up_files.done_scanning_text == '' ) {
				// save the original pattern
				pewc_clean_up_files.done_scanning_text = jQuery( '.pewc-clean-up-files-done-scanning' ).text();
			}
			var done_scanning_text = pewc_clean_up_files.done_scanning_text;
			done_scanning_text = done_scanning_text.replace( '[files_to_delete]', pewc_clean_up_files.files_to_delete );
			done_scanning_text = done_scanning_text.replace( '[before_date]', beforeDate[0] );
			jQuery( '.pewc-clean-up-files-done-scanning' ).text( done_scanning_text ).show();
			jQuery( '.pewc-clean-up-files-scanning-files' ).hide();

			if ( pewc_clean_up_files.files_to_delete > 0 ) {
				// show confirm button
				jQuery( '.pewc-clean-up-files-confirm' ).show();
				jQuery( '.pewc-clean-up-files-cancel' ).show();
			} else {
				// no files to delete, show original screen
				jQuery( '.pewc-clean-up-files-cancel' ).hide();
				jQuery( '.pewc-clean-up-completed' ).show();
				jQuery( '.pewc-clean-up-before-date-container' ).show();
				jQuery( 'input.pewc-clean-up-before-date' ).val( '' ).trigger( 'change' );
			}
			pewc_clean_up_files.current_process = '';
		}

	},

	delete_files_in_dir: function( index ) {

		pewc_clean_up_files.current_process = 'deleting files';
		var dirs = pewc_clean_up_files.directories;
		if ( index < dirs.length ) {
			var beforeDate = pewc_clean_up_files.get_before_date();
			if ( pewc_clean_up_files.deleting_text == '' ) {
				// save the original text
				pewc_clean_up_files.deleting_text = jQuery( '.pewc-clean-up-files-deleting' ).text();
			}
			var deleting_text = pewc_clean_up_files.deleting_text;
			deleting_text = deleting_text.replace( '[before_date]', beforeDate[0] );
			var percentage = Math.round(((index / dirs.length) * 100)) +"%";
			deleting_text = deleting_text.replace( '[percentage]', ' (' + percentage + ')' );
			jQuery( '.pewc-clean-up-files-deleting' ).text( deleting_text ).show();

			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'pewc_clean_up_files_delete_in_dir',
					security: jQuery( '#pewc_clean_up_files_nonce' ).val(),
					before_date: beforeDate[1],
					directory: dirs[index]
				},
				success: function( response ) {
					if ( response.success && pewc_clean_up_files.current_process === 'deleting files' ) {
						//if ( ! isNaN( response.data.total_deleted ) ) {
						//	pewc_clean_up_files.total_deleted += parseInt( response.data.total_deleted );
						//}
						if ( response.data.files.length > 0 ) {
							pewc_clean_up_files.total_deleted += parseInt( response.data.files.length );
							for ( var i in response.data.files ) {
								jQuery( '.pewc-clean-up-files-list' ).append( '<p>' + response.data.files[i] + '</p>' );
							}
							jQuery( '.pewc-clean-up-files-list' ).show();
						}
						jQuery( 'body' ).trigger( 'pewc_clean_up_files_in_dir', [ index+1 ] );
					} else {
						if ( pewc_clean_up_files.current_process === 'deleting files' ) {
							pewc_clean_up_files.show_notification( response.data.message );
						}
						jQuery( '.pewc-clean-up-files-deleting' ).hide();
						jQuery( '.pewc-clean-up-completed' ).show();
						jQuery( '.pewc-clean-up-before-date-container' ).show();
						jQuery( '.pewc-clean-up-files-scanning' ).hide();
						jQuery( 'input.pewc-clean-up-before-date' ).val( '' ).trigger( 'change' );
						pewc_clean_up_files.current_process = '';
					}
				},
				error: function( response ) {
					alert( 'Error! ' + response.status + ' ' + response.statusText );
					jQuery( '.pewc-clean-up-files-deleting' ).hide();
					jQuery( '.pewc-clean-up-completed' ).show();
					jQuery( '.pewc-clean-up-before-date-container' ).show();
					jQuery( '.pewc-clean-up-files-scanning' ).hide();
					pewc_clean_up_files.current_process = '';
				}
			});
		} else {
			var beforeDate = pewc_clean_up_files.get_before_date();
			if ( pewc_clean_up_files.done_text == '' ) {
				// save the original pattern
				pewc_clean_up_files.done_text = jQuery( '.pewc-clean-up-files-done span' ).text();
			}
			var done_text = pewc_clean_up_files.done_text;
			done_text = done_text.replace( '[total_deleted]', pewc_clean_up_files.total_deleted );
			done_text = done_text.replace( '[before_date]', beforeDate[0] );
			jQuery( '.pewc-clean-up-files-done span' ).text( done_text )
			jQuery( '.pewc-clean-up-files-done' ).show();
			jQuery( '.pewc-clean-up-files-deleting' ).hide();
			jQuery( '.pewc-clean-up-files-cancel' ).hide();
			//jQuery( '.pewc-clean-up-completed' ).show();
			//jQuery( '.pewc-clean-up-before-date-container' ).show();
			jQuery( '.pewc-clean-up-files-scanning' ).hide();
			jQuery( 'input.pewc-clean-up-before-date' ).val( '' ).trigger( 'change' );
			pewc_clean_up_files.current_process = '';
		}

	},

	confirm_after_scan: function() {

		jQuery( '.pewc-clean-up-files-done-scanning' ).hide();
		jQuery( '.pewc-clean-up-files-confirm' ).hide();
		jQuery( '.pewc-clean-up-files-cancel' ).hide();
		jQuery( '.pewc-clean-up-files-list' ).hide();
		pewc_clean_up_files.show_one_cancel_button();
		pewc_clean_up_files.reset_files_list();
		jQuery( 'body' ).trigger( 'pewc_clean_up_files_in_dir', [ 0 ] );

	},

	cancel_process: function() {

		if ( pewc_clean_up_files.current_process === 'scanning directories' ) {
			jQuery( '.pewc-clean-up-files-scanning' ).hide();
		} else if ( pewc_clean_up_files.current_process === 'scanning files' ) {
			jQuery( '.pewc-clean-up-files-scanning-files' ).hide();
		} else if ( pewc_clean_up_files.current_process === 'deleting files' ) {
			jQuery( '.pewc-clean-up-files-deleting' ).hide();
		}
		jQuery( '.pewc-clean-up-files-done-scanning' ).hide();
		jQuery( '.pewc-clean-up-files-confirm' ).hide();
		jQuery( '.pewc-clean-up-files-cancel' ).hide();
		jQuery( '.pewc-clean-up-files-done' ).hide();
		jQuery( '.pewc-clean-up-files-list' ).hide();
		jQuery( '.pewc-clean-up-completed' ).show();
		jQuery( '.pewc-clean-up-before-date-container' ).show();
		jQuery( 'input.pewc-clean-up-before-date' ).val( '' ).trigger( 'change' );
		pewc_clean_up_files.current_process = '';

	},

	show_one_cancel_button: function() {

		jQuery( '.pewc-clean-up-files-cancel' ).each( function( element ){
			jQuery( this ).show();
			return false; // stop the loop?
		});

	},

	show_notification: function( message ) {

		jQuery( '.pewc-clean-up-files-notification' ).text( message ).show();

	},

	hide_notification: function() {

		jQuery( '.pewc-clean-up-files-notification' ).text( '' ).hide();

	},

	warn_exit_if_processing: function() {

		if ( pewc_clean_up_files.current_process != '' ) {
			return "You are currently deleting files. Closing this window will stop the process.";
		}

	}

}

pewc_clean_up_files.init();