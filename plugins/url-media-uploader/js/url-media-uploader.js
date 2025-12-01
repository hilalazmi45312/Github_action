jQuery(document).ready(function($) {

    function urlMediaUploaderAddUrlInputIfNeeded() {
        var uploaderSection = $('.media-frame-content .upload-ui');
        if ($('#url-media-uploader-section').length === 0) {
            var urlUploadHtml = '<div id="url-media-uploader-section">' +
                                '<p class="upload-instructions drop-instructions">or</p>' +
                                '<label for="url-media-uploader-input">Upload from URL:</label>' +
                                '<div class="url-media-uploader-input-wrapper">' +
                                '<input type="text" id="url-media-uploader-input" autocomplete="off" style="width: 100%;" placeholder="Enter media URL here">' +
                                '<button id="url-media-uploader-button" class="button">Upload</button>' +
                                '</div>' +
                                '</div>';
            uploaderSection.append(urlUploadHtml);
        }
    }

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                var target = $(mutation.target);
                if (target.find('.upload-ui')) {
                    $("#url-media-uploader-section").remove();
                    urlMediaUploaderAddUrlInputIfNeeded();
                }
            }
        });
    });

    var config = { attributes: true, subtree: true, attributeFilter: ['class'] };
    observer.observe(document.body, config);

    $(window).on('load', urlMediaUploaderAddUrlInputIfNeeded);
    
    $('body').on('click', '#url-media-uploader-button', function() {
        var button = $(this);
        var mediaUrlInput = button.closest("#url-media-uploader-section").find('#url-media-uploader-input');
        var mediaUrl = mediaUrlInput.val();
        
        mediaUrlInput.prop('disabled', true);
        button.attr('disabled', true).append('<span class="spinner is-active" style="float: none;"></span>');
        
        $.ajax({
            url: urlMediaUploader.ajax_url,
            type: 'POST',
            data: {
                action: 'url_media_uploader_url_upload',
                url: mediaUrl,
                nonce: urlMediaUploader.nonce
            },
            success: function(response) {
                if (response.success) {
                    var attachmentId = response.data.attachment_id;
                    wp.media.attachment(attachmentId).fetch().then(function() {
						let frame = (wp.media.frame === undefined) ? wp.media.frames.file_frame : wp.media.frame;
						var selection = frame.state().get('selection');
                        var attachment = wp.media.attachment(attachmentId);
                        selection.add(attachment);
                        
                        if(frame.content.get() !== null) {
                            frame.content.get().collection.props.set({ignore: (+ new Date())});
                        } else {
                            frame.library.props.set ({ignore: (+ new Date())});
                        }
                    });
                    button.closest('.media-modal-content').find('#menu-item-browse').click(); 
                    
                } else {
                    alert(response.data.message);
                }
            },
            error: function(response) {
                alert('Failed to upload media.');
            },
            complete: function() {
                mediaUrlInput.prop('disabled', false);
                button.attr('disabled', false).find('.spinner').remove();
            }
        });
    });
    
});