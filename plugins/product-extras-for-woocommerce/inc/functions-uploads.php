<?php
/**
 * Functions for uploading files
 * @since 3.7.6
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pewc_ajax_upload_script( $id, $field, $multiply_price ) {

	$accepted_files = pewc_get_accepted_files();
	$max_file_size = pewc_get_max_upload();
	$max_files = ! empty( $field['max_files'] ) ? absint( $field['max_files'] ) : 1; ?>

	<script>
		Dropzone.autoDiscover = false;
		jQuery(document).ready(function( $ ) {

			<?php do_action( 'pewc_start_upload_script', $id, $field ); ?>

			var ajaxUrl = pewc_vars.ajaxurl;
			var dropzone_<?php echo esc_attr( $id ); ?> = new Dropzone( "#dz_<?php echo esc_attr( $id ); ?>", {

			dictDefaultMessage: "<?php echo apply_filters( 'pewc_filter_dictDefaultMessage_message', __( 'Drop files here to upload', 'pewc' ) ); ?>",
			dictFallbackMessage: "<?php echo apply_filters( 'pewc_filter_dictFallbackMessage_message', __( 'Your browser does not support drag and drop file uploads', 'pewc' ) ); ?>",
			dictFallbackText: "<?php echo apply_filters( 'pewc_filter_dictFallbackText_message', __( 'Please use the fallback form below to upload your files like in the olden days', 'pewc' ) ); ?>",
			dictFileTooBig: "<?php echo apply_filters( 'pewc_filter_dictFileTooBig_message', __( 'The file is too big', 'pewc' ) ); ?>",
			dictInvalidFileType: "<?php echo apply_filters( 'pewc_filter_dictInvalidFileType_message', __( 'You cannot upload files of this type', 'pewc' ) ); ?>",
			dictCancelUpload: "<?php echo apply_filters( 'pewc_filter_dictCancelUpload_message', __( 'Cancel upload', 'pewc' ) ); ?>",
			dictUploadCanceled: "<?php echo apply_filters( 'pewc_filter_dictUploadCanceled_message', __( 'Upload cancelled.', 'pewc' ) ); ?>",
			dictCancelUploadConfirmation: "<?php echo apply_filters( 'pewc_filter_dictCancelUploadConfirmation_message', __( 'Are you sure you want to cancel this upload?', 'pewc' ) ); ?>",
			dictRemoveFile: "<?php echo apply_filters( 'pewc_filter_dictRemoveFile_message', __( 'Remove file', 'pewc' ) ); ?>",
			dictMaxFilesExceeded: "<?php echo apply_filters( 'pewc_filter_dictMaxFilesExceeded_message', __( 'You cannot upload any more files.', 'pewc' ) ); ?>",

				previewTemplate: document.querySelector('#tpl').innerHTML,
				url: ajaxUrl,
				acceptedFiles: "<?php echo esc_attr( $accepted_files ); ?>",
				maxFiles: <?php echo absint( $max_files ); ?>,
				maxFilesize: <?php echo esc_attr( $max_file_size ); ?>,
				thumbnailWidth: <?php echo apply_filters( 'pewc_dropzone_thumbnail_width', 1000, $id, $field ); ?>,
				thumbnailHeight: <?php echo apply_filters( 'pewc_dropzone_thumbnail_height', 1000, $id, $field ); ?>,
				addRemoveLinks: true,
				uploadMultiple: true,
				maxThumbnailFilesize: <?php echo apply_filters( 'pewc_dropzone_max_thumbnail_size', 10, $id ); ?>,
				timeout: <?php echo apply_filters( 'pewc_dropzone_timeout', 30000, $id ); ?>,
				<?php do_action( 'pewc_end_upload_options', $id, $field ); ?>
				init: function() {
					<?php do_action( 'pewc_start_upload_script_init', $id, $field ); ?>

					this.on( 'sendingmultiple', function( file, xhr, formData ) {
						var field_id = <?php echo $field['field_id']; ?>;
						$( '#field_' + field_id + '_pdf_count' ).val( 0 );
						<?php if( pewc_disable_add_to_cart_upload() ) { ?>
							$( 'body' ).trigger( 'pewc_toggle_add_to_cart_button', [ true, 'upload' ] ); // since 3.11.9
						<?php } ?>
						formData.append( 'action', 'pewc_dropzone_upload' );
						formData.append( 'pewc_file_upload', $( '#pewc_file_upload' ).val() );
						formData.append( 'field_id', '<?php echo $field['field_id']; ?>' );
						formData.append( 'pewc_product_id', $( '#pewc_product_id' ).val() );
						formData.append( 'file_data', $( '#<?php echo esc_attr( $id ); ?>_file_data' ).val() );
						formData.append( 'pewc_item_id', '<?php echo esc_attr( $id ); ?>' ); // 3.27.2
						// Safari seems to have issues with special characters, we pass the encoded filename for now, to be used later
						for ( k in file ) {
							formData.append( 'filename_encoded['+k+']', encodeURIComponent( file[k].name ) );
						}
					});
					this.on( 'successmultiple', function( file, response ) {
						return;
					});
					this.on( 'complete', function( file ) {
						return;
					});
					this.on( 'queuecomplete', function() {
						// We use this method because successmultiple was overwriting some files when used with Advanced Uploads
						var files = dropzone_<?php echo esc_attr( $id ); ?>.files;
						var num_files = dropzone_<?php echo esc_attr( $id ); ?>.files.length;
						var all_files = [];
						var uploaded_files = [];
						var page_counts = [];

						if ( num_files > 0 && $( '#<?php echo esc_attr( $id ); ?>_file_data' ).val() != '' ) {
							// on 3.9.7, we regenerate the dropzone area if files were previously uploaded
							uploaded_files = JSON.parse( $( '#<?php echo esc_attr( $id ); ?>_file_data' ).val() );
						}

						// Ensure we have a list of the currently uploaded files, excluding any that may have been removed
						if( files ) {
							for( k in files ) {
								var file = files[k];
								if( file.xhr === undefined) {
									if ( uploaded_files.length > 0 && uploaded_files[k] ) {
										// use the already uploaded files instead
										all_files.push( uploaded_files[k]) ;
									}
									continue; // if we're regenerating the dropzone, this is undefined, so skip the rest of the loop
								}
								var response = JSON.parse( file.xhr.response );
								var received_files = response.data.files;
								if( received_files ) {
									<?php do_action( 'pewc_after_upload_queuecomplete', $id, $field ); ?>
									for( f in received_files ) {
										if( file.name === received_files[f].name || file.name === decodeURIComponent( received_files[f].name_encoded ) ) {
											// If this is a PDF and the option is enabled, count the pages
											if( pewc_vars.pdf_count == 'yes' && received_files[f].type == 'application/pdf' ) {
												$( file.previewElement ).find( '.dz-success-mark' ).append( '<div class="pewc-counting-pdf-pages-text">' + pewc_vars.counting_pages_text + '</div>' ); // 3.26.5
												$.ajax({
													type: 'POST',
													url: pewc_vars.ajaxurl,
													data: {
														action: 'wcpauau_get_pdf_page_count',
														path: received_files[f].file,
														name: received_files[f].name
													},
													success: function( response ) {
														$( file.previewElement ).find( '.pewc-counting-pdf-pages-text' ).remove(); // 3.26.5
														var field_id = <?php echo $field['field_id']; ?>;
														if( ! $( '#field_' + field_id + '_pdf_count' ).val() ) {
															current_count = 0;
														} else {
															current_count = parseInt( $( '#field_' + field_id + '_pdf_count' ).val() );
														}
														file.pageCount = parseInt( response.data.count );
														var element = {};
														element.name = response.data.name;
														element.count = response.data.count;
														page_counts.push( element );
														$( '#field_' + field_id + '_pdf_count' ).attr( 'data-counts', JSON.stringify( page_counts ) );
														current_count += parseInt( response.data.count );
														$( '#field_' + field_id + '_pdf_count' ).val( current_count );
														// 3.25.5, trigger calculation, so that the calc field that is using the pdf page count updates its value
														$( 'body' ).trigger( 'pewc_trigger_calculations' );
														if ( response.data.error != '' ) {
															alert( 'There was an error in getting the PDF page count: ' + response.data.error );
														}
													}
												});
											}
											// Identify the file from the response data
											all_files.push( received_files[f] );
											if ( received_files[f].pdf_thumb ) {
												// the uploaded file was a PDF, get the generated PDF thumb if it exists and use it
												$(file.previewElement).find(".pewc-dz-image-wrapper img").attr("src", received_files[f].pdf_thumb + '?' + Math.random());
											}
											break;
										}
									}
								}

								

							}
						}

						$( '#<?php echo esc_attr( $id ); ?>_file_data' ).val( JSON.stringify( all_files ) );
						var num_all_files = all_files.length; // 3.10.5, maybe this is more accurate, because this can detect failed uploads

						var upload_delay = setTimeout(
							function() {
								$( '#<?php echo esc_attr( $id ); ?>_number_uploads' ).val( JSON.stringify( num_all_files ) ).trigger( 'change' );
								var pewc_item = $( '#dz_<?php echo esc_attr( $id ); ?>' ).closest( '.pewc-item' );

								if ( num_all_files > 0 ) {
									var price = $( '#<?php echo esc_attr( $id ); ?>_base_price' ).val();
									<?php if( $multiply_price ) { ?>
									if ( pewc_item.hasClass( 'quantity-per-upload' ) && pewc_item.hasClass( 'price-quantity-per-upload' ) ) {
										// 3.22.1, multiply price with quantity per upload
										var total_quantity_per_upload = parseFloat( pewc_item.attr( 'data-total-quantity-per-upload' ) );
										price = total_quantity_per_upload * parseFloat( price );
									} else {
										price = parseFloat( num_all_files ) * parseFloat( price );
									}
									<?php } ?>
									pewc_item.attr( 'data-price', price );
								}
								else {
									pewc_item.attr( 'data-price', 0 );
								}

								$( 'body' ).trigger( 'pewc_force_update_total_js' );
								$( 'body' ).trigger( 'pewc_check_conditions' );
								$( 'body' ).trigger( 'pewc_trigger_calculations' );
								$( 'body' ).trigger( 'pewc_image_uploaded', [ '<?php echo esc_attr( $id ); ?>', num_all_files ] );
							},
							pewc_vars.pdf_count_timer
						);

						<?php if( pewc_disable_add_to_cart_upload() ) { ?>
							$( 'body' ).trigger( 'pewc_toggle_add_to_cart_button', [ false, 'upload' ] ); // since 3.11.9
						<?php } ?>

					});
					this.on( 'removedfile', function( file, response ) {
						$( '.dropzone.dz-clickable' ).block({
							message: null,
							overlayCSS:  {
								backgroundColor: '#fff',
								opacity:         0.6,
								cursor:          'wait'
					    	},
						});
						// Delete pdf count value
						var field_id = <?php echo $field['field_id']; ?>;
						if( pewc_vars.pdf_count == 'yes' && file.type == 'application/pdf' ) {
							var page_counts = $( '#field_' + field_id + '_pdf_count' ).attr( 'data-counts' );
							page_counts = JSON.parse( page_counts );
							var page_count = 0;
							for( p in page_counts ) {
								var element = page_counts[p];
								if( element.name == file.name ) {
									page_count = parseInt( element.count );
								}
							}
							if( isNaN( page_count ) ) page_count = 0;
							var current_count = parseInt( $( '#field_' + field_id + '_pdf_count' ).val() );
							var new_count = current_count - page_count;
							$( '#field_' + field_id + '_pdf_count' ).val( new_count ).trigger( 'change' );
							// $( 'body' ).trigger( 'pewc_trigger_calculations' );
						}
						var remove_data = {
							action: 'pewc_dropzone_remove',
							file: file.name,
							pewc_file_upload: $( '#pewc_file_upload' ).val(),
							file_data: $( '#<?php echo esc_attr( $id ); ?>_file_data' ).val()
						};
						if ( file.wcpauau_from_cropper != undefined && file.wcpauau_from_cropper == 'yes' ) {
							remove_data['wcpauau_from_cropper'] = 'yes'; // 3.18.2
						}
						$.ajax({
							type: 'POST',
							url: pewc_vars.ajaxurl,
							data: remove_data,
							success: function( response ) {
								$( '.dropzone.dz-clickable' ).unblock();
								$( '#<?php echo esc_attr( $id ); ?>_file_data' ).val( JSON.stringify( response.data.files ) );
								var num_files = response.data.count;
								if( num_files === 0 ) {
									$( '#<?php echo esc_attr( $id ); ?>_file_data' ).val( '' );
									$( '#field_' + field_id + '_pdf_count' ).val( 0 ).trigger( 'change' );
								}
								$( '#<?php echo esc_attr( $id ); ?>_number_uploads' ).val( JSON.stringify( num_files ) ).trigger( 'change' );
								<?php if( $multiply_price ) { ?>
									var price = $( '#<?php echo esc_attr( $id ); ?>_base_price' ).val();
									price = parseFloat( num_files ) * parseFloat( price );
									$( '#dz_<?php echo esc_attr( $id ); ?>' ).closest( '.pewc-item' ).attr( 'data-price', price );
									$( 'body' ).trigger( 'pewc_force_update_total_js' );
								<?php } ?>
								$( '#dz_<?php echo esc_attr( $id ); ?>' ).closest( '.pewc-item' ).find( '.aouau-quantity-field' ).trigger( 'wcaouau-update-quantity-field' );
								$( 'body' ).trigger( 'pewc_check_conditions' );
								$( 'body' ).trigger( 'pewc_trigger_calculations' );
								$( 'body' ).trigger( 'pewc_image_removed', [ '<?php echo esc_attr( $id ); ?>' ]);
							}
						});
					});
					this.on( 'error', function( file, response ) {
						console.log( 'error' );
					});

				},

				<?php do_action( 'pewc_after_upload_script_init', $id, $field ); ?>

			});


			// if the product page has been submitted but there's an error, we'll try to re-build the dropzone area with previously uploaded files, so that they won't have to re-upload again
			var pewc_file_data = $( '#<?php echo esc_attr( $id ); ?>_file_data' ).val();
			if ( pewc_file_data != '') {
				// convert to JSON
				var pewc_file_data_json = JSON.parse( pewc_file_data );
				// loop through each file
				$.each(pewc_file_data_json, function(key, value){
					var existingFile = value;

					// add other elements needed by Advanced Uploads
					var new_uuid = Dropzone.uuidv4();
					existingFile.upload = { uuid : new_uuid };
					existingFile.accepted = true;

					dropzone_<?php echo esc_attr( $id ); ?>.files.push( existingFile );

					dropzone_<?php echo esc_attr( $id ); ?>.emit( 'addedfile', existingFile );
					if ( existingFile.pdf_thumb ) {
						dropzone_<?php echo esc_attr( $id ); ?>.options.thumbnail.call(dropzone_<?php echo esc_attr( $id ); ?>, existingFile, existingFile.pdf_thumb );
					} else {
						dropzone_<?php echo esc_attr( $id ); ?>.options.thumbnail.call(dropzone_<?php echo esc_attr( $id ); ?>, existingFile, '<?php echo site_url() ?>' + existingFile.url );
					}
					dropzone_<?php echo esc_attr( $id ); ?>.emit( 'success', existingFile ); // shows the "Uploaded" text
					dropzone_<?php echo esc_attr( $id ); ?>.emit( 'complete', existingFile ); // this needs to be called, or the upload bar will appear
					dropzone_<?php echo esc_attr( $id ); ?>._updateMaxFilesReachedClass();

					<?php if ( ! empty( $field['quantity_per_upload'] ) ) { ?>
						// adjust quantity per field for Advanced Uploads
						if (typeof existingFile.quantity !== 'undefined') {
							$( 'input[name="<?php echo esc_attr( $id ); ?>_extra_fields[quantity]\['+key+'\]"]' ).val( existingFile.quantity );
						}
					<?php } ?>
				});

			}

			<?php do_action( 'pewc_end_upload_script', $id, $field ); ?>

		});
	</script>

	<?php
}
add_action( 'pewc_do_ajax_upload_script', 'pewc_ajax_upload_script', 10, 3 );

/**
 * Get the accepted file types for our upload
 * @since 3.7.6
 */
function pewc_get_accepted_files() {

	$accepted_files = array();
	$permitted_mimes = pewc_get_pretty_permitted_mimes();
	$permitted_mimes = explode( ' ', $permitted_mimes );
	foreach( $permitted_mimes as $file_type ) {
		$accepted_files[] = '.' . $file_type;
	}
	$accepted_files = join( ', ', $accepted_files );

	return $accepted_files;
}

/**
 * Convert the AJAX uploaded files object into a $_FILES type array
 * @param $pewc_file_data		The files object uploaded via jQuery
 * @param $id 							The field ID
 */
function pewc_get_files_array( $pewc_file_data, $id, $product_id ) {

	$files[$id] = array();
	$pewc_file_data = apply_filters(
		'pewc_file_data',
		json_decode( stripslashes( $pewc_file_data ) ),
		$id,
		$product_id
	);

	$index = 0;

	if( $pewc_file_data ) {

		foreach( $pewc_file_data as $upload ) {

			if ( ! is_object( $upload ) )
				continue;
			$files[$id]['file'][$index] = $upload->file;
			$files[$id]['name'][$index] = $upload->name;
			$files[$id]['type'][$index] = isset( $upload->filetype->type ) ? $upload->filetype->type : $upload->type;
			$files[$id]['error'][$index] = $upload->error;
			$files[$id]['size'][$index] = $upload->size;
			$files[$id]['url'][$index] = $upload->url;
			$files[$id]['tmp_name'][$index] = $upload->tmp_name;
			if ( ! empty( $upload->pdf_thumb ) ) {
				$files[$id]['pdf_thumb'][$index] = $upload->pdf_thumb;
			}
			$index++;

		}

	}

	return apply_filters( 'pewc_files_array', $files, $pewc_file_data, $id, $product_id );

}

function pewc_disable_add_to_cart_upload() {
	$disable = get_option( 'pewc_disable_add_to_cart', 'no' );
	return $disable == 'yes' ? true : false;
}

/*
 * Save uploaded files to session, so that they are not lost if there was an error in validation
 * @since 3.9.7
 */
function pewc_save_uploaded_files_to_session( $uploaded_files, $field_id ) {
	// Make sure WooCommerce session is already set
	if ( isset(WC()->session) && WC()->session->has_session() ) {
		$field_id = @floor( $field_id ); // integer only
		if ( ! empty( $uploaded_files ) ) {
			// save
			// if AJAX upload is enabled, $uploaded_files is a JSON string (pewc_file_data). Else, $uploaded_files is an array of $_FILES
			WC()->session->set( 'uploaded_files_'.$field_id, $uploaded_files );
		}
		else {
			// remove from session
			WC()->session->__unset( 'uploaded_files_'.$field_id );
		}
	}
}

/*
 * Get uploaded files from session
 * @since 3.9.7
 */
function pewc_get_uploaded_files_from_session( $field_id, $item, $cart_item ) {
	if( pewc_enable_ajax_upload() == 'yes' ) {
		// AJAX upload, JSON string
		$files = '';
	}
	else {
		// standard
		$files = array();
	}

	$files = WC()->session->get( 'uploaded_files_'.$field_id, $files );

	if ( empty( $files ) && ! empty( $_GET[ 'pewc_key' ]) && pewc_user_can_edit_products() ) {
		// retrieve from cart instead
		if ( ! empty( $cart_item['product_extras']['groups'][$item['group_id']][$field_id]['files'] ) ) {
			$uploaded_files = $cart_item['product_extras']['groups'][$item['group_id']][$field_id]['files'];
			if ( is_array( $uploaded_files ) ) {
				$tmp = array();
				foreach ( $uploaded_files as $uf ) {
					if ( isset( $uf['url'] ) ) {
						// fix URL for displaying in Dropzone
						$uf['url'] = str_replace( site_url().'/', '/', $uf['url'] );
					}
					$tmp[] = $uf;
				}
				$files = json_encode( $tmp );
			}
		}
	}

	return $files;
}

/*
 * Generate a thumbnail from a PDF file
 * @since 3.11.4
 */
function pewc_generate_pdf_thumb ( $uploaded_bits ) {

	if ( 'yes' !== pewc_enable_pdf_thumb() ) {
		return false;
	}

	extract( $uploaded_bits );

	if ( empty( $file ) || ! file_exists( $file ) ) {
		return false;
	} else if ( ! extension_loaded( 'imagick' ) ) {
		error_log('AOU: Imagick not loaded');
		return false; // default image
	}

	try {

		$pdfThumb = new imagick();
		//$pdfThumb->setResolution(300, 300); // commented out on 3.13.0 because it causes this process to hang if a PDF file has a very large resolution (e.g. 8663 x 2994)
		$pdfThumb->setColorspace(Imagick::COLORSPACE_SRGB); // 3.13.5, fix for CMYK PDF files generating thumbnails with inverted colors when mergeImageLayers is called
		$pdfThumb->readImage( $file . '[0]' );
		$pdfThumb = $pdfThumb->mergeImageLayers(imagick::LAYERMETHOD_FLATTEN);
		//$pdfThumb->setImageColorspace(255); // for JPG only? // this seems to result in an incorrect color in some PDF I uploaded
		$pdfThumb->scaleImage(100, 100, imagick::FILTER_POINT, false); // https://www.php.net/manual/en/imagick.resizeimage.php#94493
		$pdfThumb->setImageFormat('jpg');
		$pdfThumb->setImageCompression(imagick::COMPRESSION_JPEG);
		$pdfThumb->setImageCompressionQuality(90);
		$fp = $file . '.jpg';
		$pdfThumb->writeImage($fp);
		if ( is_file( $fp ) ) {
			return $url.'.jpg';
		}
		else return false;
		//$blob = $pdfThumb->getImageBlob();
		//return base64_encode( $blob );
		//https://stackoverflow.com/questions/11957405/php-imagick-read-image-from-base64

	} catch ( Exception $e ) {
		error_log('AOU: Error in generating PDF thumbnail:' . $file .', message: '.$e->getMessage());
		return false;
	}
}

/*
 * Set a default thumb for PDF uploads
 * @since 3.11.4
 */
function pewc_default_thumb_for_nonimages( $id, $field ) {
	if ( 'yes' !== pewc_enable_pdf_uploads() ) {
		return false; // don't do anything if PDF upload is not enabled
	}
	$default = site_url().'/wp-includes/images/media/document.png';
?>
	this.on( 'addedfile', function( file ){
		if ( 'application/pdf' == file.type ) {
			$(file.previewElement).find(".pewc-dz-image-wrapper img").attr("src", "<?php echo esc_attr( $default ); ?>");
		}
	});
<?php
}
add_action( 'pewc_start_upload_script_init', 'pewc_default_thumb_for_nonimages', 10, 2 );

/*
 * Check if PDF thumbs are enabled
 * @since 3.11.4
 */
function pewc_enable_pdf_thumb() {
	if ( apply_filters( 'pewc_enable_pdf_thumb', true ) )
		return 'yes';
	else return 'no';
}

/**
 * Load the original image as a thumbnail if uploaded image is too large for Dropzone
 * @since 3.13.5
 */
function pewc_create_thumb_callback( $id, $field ) {
	if ( apply_filters( 'pewc_use_original_image_for_thumbnail', false ) ) {
?>
	this.on ( 'success', function( file, response, e ) { <?php
		if ( function_exists( 'wcpauau_image_editing_enabled' ) && wcpauau_image_editing_enabled() == 'yes' ) {
			$image_editing_enabled = true;
		} else {
			$image_editing_enabled = false;
		} ?>

		var received_files = response.data.files;

		if( received_files ) {
			for( f in received_files ) {
				var preview_img = $( file.previewElement ).find( ".pewc-dz-image-wrapper img" );
				if(
					( file.name === received_files[f].name || file.name === decodeURIComponent( received_files[f].name_encoded ) ) && 
					/*preview_img.attr( 'src' ).indexOf( 'data:image/' ) < 0 && */
					typeof preview_img !== undefined && 
					received_files[f].type.indexOf( 'image/' ) > -1 && 
					typeof received_files[f].use_original !== undefined
				) {
					var original_image = '<?php echo site_url(); ?>' + received_files[f].url;
					// add extra message that we're generating the thumbnail
					$( file.previewElement ).append( '<tr class="pewc-generating-thumbnail"><td></td><td colspan="2"><?php _e( apply_filters( 'pewc_loading_original_image_text', __( 'Loading original image...', 'pewc' ) ) ); ?></td></tr>' );
		<?php if ( $image_editing_enabled ) { ?>
					// hide Edit Image for now until the thumbnail is generated
					$( file.previewElement ).find( '.wcpauau-edit' ).hide();
					preview_img.on( 'load', function() {
						$( file.previewElement ).find( '.wcpauau-edit' ).show();
						$( file.previewElement ).find( '.pewc-generating-thumbnail' ).remove();
					});
		<?php } else { ?>
					preview_img.on( 'load', function() {
						$( file.previewElement ).find( '.pewc-generating-thumbnail' ).remove();
					});
		<?php } ?>
					preview_img.attr( "src", original_image + '?' + Math.random() );
				}
			}
		}
	})
<?php
	}
}
add_action( 'pewc_start_upload_script_init', 'pewc_create_thumb_callback', 11, 2 );
