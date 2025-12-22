/**
 * Gift Card Image Upload Handler
 *
 * @package WooCommerce Smart Coupons
 */

document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	/**
	 * Custom Upload Handler (without Media Library)
	 */
	var WCSmartCouponsCustomUpload = {

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			var self = this;

			// Handle file selection button click
			document.addEventListener('click', function(e) {
				if (e.target && e.target.id === 'wc_sc_gift_image_button') {
					e.preventDefault();
					var fileInput = document.getElementById('wc_sc_gift_image_file');
					if (fileInput) {
						fileInput.click();
					}
				}
			});

			// Handle file selection change
			document.addEventListener('change', function(e) {
				if (e.target && e.target.id === 'wc_sc_gift_image_file') {
					e.preventDefault();
					var file = e.target.files[0];
					if (file) {
						self.validateAndUploadFile(file);
					}
				}
			});

			// Handle remove image
			document.addEventListener('click', function(e) {
				if (e.target && e.target.classList.contains('wc-sc-remove-image')) {
					e.preventDefault();
					self.removeImage();
				}
			});

			// Reset form on variation change
			var variationForm = document.querySelector('.variations_form');
			if (variationForm) {
				variationForm.addEventListener('reset_data', function() {
					self.resetUploadField();
				});
			}
		},

		/**
		 * Validate and upload file
		 */
		validateAndUploadFile: function(file) {
			var self = this;

			// Validate file type
			var validTypes = wc_sc_custom_gift.allowed_types;
			if (validTypes.indexOf(file.type) === -1) {
				alert(wc_sc_custom_gift.messages.error_type);
				var fileInput = document.getElementById('wc_sc_gift_image_file');
				if (fileInput) fileInput.value = '';
				return;
			}

			// Validate file size
			if (file.size > wc_sc_custom_gift.max_size) {
				alert(wc_sc_custom_gift.messages.error_size);
				var fileInput = document.getElementById('wc_sc_gift_image_file');
				if (fileInput) fileInput.value = '';
				return;
			}

			// Show uploading message
			var button = document.getElementById('wc_sc_gift_image_button');
			if (button) {
				var originalText = button.textContent;
				button.textContent = wc_sc_custom_gift.messages.uploading;
				button.disabled = true;

				// Prepare form data
				var formData = new FormData();
				formData.append('action', 'wc_sc_upload_gift_image');
				formData.append('nonce', wc_sc_custom_gift.upload_nonce);
				formData.append('gift_image', file);

				// Upload file
				var xhr = new XMLHttpRequest();
				xhr.open('POST', wc_sc_custom_gift.ajax_url);

				xhr.onload = function() {
					button.textContent = originalText;
					button.disabled = false;

					if (xhr.status === 200) {
						try {
							var response = JSON.parse(xhr.responseText);
							if (response.success) {
								self.displayUploadedImage(response.data);
							} else {
								alert(response.data.message || 'Upload failed');
								self.resetUploadField();
							}
						} catch (e) {
							alert('Upload failed. Invalid response.');
							self.resetUploadField();
						}
					} else {
						alert('Upload failed. Please try again.');
						self.resetUploadField();
					}
				};

				xhr.onerror = function() {
					button.textContent = originalText;
					button.disabled = false;
					alert('Upload failed. Please try again.');
					self.resetUploadField();
				};

				xhr.send(formData);
			}
		},

		/**
		 * Display uploaded image
		 */
		displayUploadedImage: function(data) {
			// Store upload data in hidden fields
			var tokenField = document.getElementById('wc_sc_gift_image_token');
			var filenameField = document.getElementById('wc_sc_gift_image_filename');

			if (tokenField) tokenField.value = data.token;
			if (filenameField) filenameField.value = data.filename;

			// Show preview
			var preview = document.querySelector('.wc-sc-gift-image-preview');
			if (preview) {
				var img = preview.querySelector('img');
				if (img) {
					img.src = data.preview_url;
				}
				preview.style.display = 'block';
			}

			// Update button text
			var button = document.getElementById('wc_sc_gift_image_button');
			if (button) {
				button.textContent = 'Change Image';
			}
		},

		/**
		 * Remove uploaded image
		 */
		removeImage: function() {
			var tokenField = document.getElementById('wc_sc_gift_image_token');
			var token = tokenField ? tokenField.value : '';

			if (token) {
				// Send AJAX request to remove temporary file
				var xhr = new XMLHttpRequest();
				xhr.open('POST', wc_sc_custom_gift.ajax_url);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

				var params = 'action=wc_sc_remove_gift_image' +
						'&nonce=' + encodeURIComponent(wc_sc_custom_gift.upload_nonce) +
						'&token=' + encodeURIComponent(token);

				xhr.send(params);
			}

			this.resetUploadField();
		},

		/**
		 * Reset upload field
		 */
		resetUploadField: function() {
			// Clear hidden fields
			var tokenField = document.getElementById('wc_sc_gift_image_token');
			var filenameField = document.getElementById('wc_sc_gift_image_filename');
			var fileField = document.getElementById('wc_sc_gift_image_file');

			if (tokenField) tokenField.value = '';
			if (filenameField) filenameField.value = '';
			if (fileField) fileField.value = '';

			// Hide preview
			var preview = document.querySelector('.wc-sc-gift-image-preview');
			if (preview) {
				preview.style.display = 'none';
				var img = preview.querySelector('img');
				if (img) {
					img.src = '';
				}
			}

			// Reset button text
			var button = document.getElementById('wc_sc_gift_image_button');
			if (button) {
				button.textContent = 'Choose Image';
				button.disabled = false;
			}
		}
	};

	// Initialize
	WCSmartCouponsCustomUpload.init();

	/**
	 * Handle form submission validation
	 */
	document.addEventListener('submit', function(e) {
		if (e.target && e.target.classList.contains('cart')) {
			var uploadField = document.querySelector('.wc-sc-gift-card-image-upload');
			if (uploadField) {
				var tokenField = document.getElementById('wc_sc_gift_image_token');
				var fileInput = document.getElementById('wc_sc_gift_image_file');

				var token = tokenField ? tokenField.value : '';

				// If file was selected but not uploaded, prevent submission
				if (fileInput && fileInput.files.length > 0 && !token) {
					alert('Please wait for the image to finish uploading.');
					e.preventDefault();
					return false;
				}
			}
		}
	});

	/**
	 * Handle drag and drop (optional enhancement)
	 */
	var setupDragDrop = function() {
		var dropZone = document.querySelector('.wc-sc-gift-card-image-upload');

		if (!dropZone) {
			return;
		}

		// Prevent default drag behaviors
		document.addEventListener('dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();
		});

		document.addEventListener('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
		});

		document.addEventListener('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
		});

		// Handle drag over upload area
		dropZone.addEventListener('dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.add('drag-over');
		});

		dropZone.addEventListener('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.add('drag-over');
		});

		dropZone.addEventListener('dragleave', function(e) {
			e.preventDefault();
			e.stopPropagation();

			// Check if we're still within the drop zone
			var rect = this.getBoundingClientRect();
			if (e.clientX < rect.left || e.clientX >= rect.right ||
				e.clientY < rect.top || e.clientY >= rect.bottom) {
				this.classList.remove('drag-over');
			}
		});

		// Handle file drop
		dropZone.addEventListener('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.remove('drag-over');

			var files = e.dataTransfer.files;
			if (files.length > 0) {
				// Set file to input and trigger change event
				var fileInput = document.getElementById('wc_sc_gift_image_file');
				if (fileInput) {
					fileInput.files = files;
					var changeEvent = new Event('change', { bubbles: true });
					fileInput.dispatchEvent(changeEvent);
				}
			}
		});
	};

	// Setup drag and drop
	setupDragDrop();

	/**
	 * Add visual feedback styles
	 */
	var addDragDropStyles = function() {
		if (!document.getElementById('wc-sc-drag-drop-styles')) {
			var style = document.createElement('style');
			style.id = 'wc-sc-drag-drop-styles';
			style.textContent =
				'.wc-sc-gift-card-image-upload.drag-over {' +
				'    background: #e8f5e9;' +
				'    border-color: #4caf50;' +
				'    box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);' +
				'    transition: all 0.3s ease;' +
				'}' +
				'.wc-sc-gift-card-image-upload {' +
				'    transition: all 0.3s ease;' +
				'}';

			document.head.appendChild(style);
		}
	};

	addDragDropStyles();

	/**
	 * Image preview on hover (enhancement)
	 */
	var previewImages = document.querySelectorAll('.wc-sc-gift-image-preview img');
	previewImages.forEach(function(img) {
		img.addEventListener('mouseenter', function() {
			this.style.transform = 'scale(1.05)';
		});

		img.addEventListener('mouseleave', function() {
			this.style.transform = 'scale(1)';
		});
	});

	document.addEventListener('mouseenter', function(e) {
		if (e.target instanceof Element && e.target.matches('.wc-sc-gift-image-preview img')) {
			e.target.style.transform = 'scale(1.05)';
		}
	}, true);

	document.addEventListener('mouseleave', function(e) {
		if (e.target instanceof Element && e.target.matches('.wc-sc-gift-image-preview img')) {
			e.target.style.transform = 'scale(1)';
		}
	}, true);

});
