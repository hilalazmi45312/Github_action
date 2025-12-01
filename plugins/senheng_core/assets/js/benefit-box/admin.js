/**
 * Benefit Box Admin Interface JavaScript
 * Handles modal functionality, AJAX saving, and admin UI interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const modal = document.getElementById('benefit-box-modal');
    const modalTitle = document.getElementById('modal-title');
    const closeBtn = document.querySelector('.benefit-box-modal-close');
    const cancelBtn = document.getElementById('cancel-benefit-box');
    const saveBtn = document.getElementById('save-benefit-box');
    const addNewBtn = document.getElementById('add-new-benefit-box');
    const editBtns = document.querySelectorAll('.edit-benefit-box');
    const deleteBtns = document.querySelectorAll('.delete-benefit-box');
    const statusToggleBtns = document.querySelectorAll('.status-toggle-btn');
    
    // Bulk actions elements
    const selectAllCheckbox = document.getElementById('cb-select-all-1');
    const bulkActionSelector = document.getElementById('bulk-action-selector-top');
    const doActionBtn = document.getElementById('doaction');

    // Modal form elements
    const modalTypeSelect = document.getElementById('modal-type');
    const modalWhatsAppFields = document.querySelectorAll('.modal-whatsapp-field');
    const modalWhatsAppSection = document.getElementById('modal-whatsapp-fields');

    // Icon elements
    const modalSelectIconBtn = document.getElementById('modal-select-icon');
    const modalRemoveIconBtn = document.getElementById('modal-remove-icon');
    const modalIconInput = document.getElementById('modal-icon');
    const modalIconPreview = document.getElementById('modal-icon-preview');

    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `benefit-box-notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }

    // Toggle WhatsApp fields
    function toggleModalWhatsAppFields() {
        if (modalTypeSelect.value === 'whatsapp') {
            modalWhatsAppFields.forEach(field => field.style.display = 'table-row');
            modalWhatsAppSection.style.display = 'table-row';
        } else {
            modalWhatsAppFields.forEach(field => field.style.display = 'none');
            modalWhatsAppSection.style.display = 'none';
        }
    }

    // Open modal for new benefit box
    if (addNewBtn) {
        addNewBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New Benefit Box';
            document.getElementById('benefit-box-form').reset();
            document.getElementById('benefit-box-id').value = '0'; // Set to '0' to ensure it's treated as new
            clearModalIcon();
            toggleModalWhatsAppFields();
            modal.style.display = 'block';
        });
    }

    // Open modal for editing
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            modalTitle.textContent = 'Edit Benefit Box';
            document.getElementById('benefit-box-id').value = id;
            
            // Load data directly from button attributes (no AJAX needed)
            loadBenefitBoxDataFromButton(this);
            modal.style.display = 'block';
        });
    });

    // Handle delete buttons
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            handleDeleteBenefitBox(id, this);
        });
    });

    // Handle status toggle buttons
    statusToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const currentStatus = parseInt(this.getAttribute('data-current-status'));
            const newStatus = currentStatus === 1 ? 0 : 1;
            handleUpdateBenefitBoxStatus(id, newStatus, this);
        });
    });

    // Handle bulk actions
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.benefit-box-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButton();
        });
    }



    // Handle individual checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('benefit-box-checkbox')) {
            updateBulkActionButton();
            updateSelectAllCheckbox();
        }
    });

    if (doActionBtn) {
        doActionBtn.addEventListener('click', function() {
            const action = bulkActionSelector.value;
            if (action === 'delete') {
                handleBulkDelete();
            }
        });
    }

    // Close modal
    function closeModal() {
        modal.style.display = 'none';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Type select change
    if (modalTypeSelect) {
        modalTypeSelect.addEventListener('change', toggleModalWhatsAppFields);
    }

    // Media Library Integration
    if (modalSelectIconBtn) {
        modalSelectIconBtn.addEventListener('click', function() {
            const mediaUploader = wp.media({
                title: 'Select Icon',
                button: { text: 'Use this icon' },
                multiple: false,
                library: { type: 'image' }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                modalIconInput.value = attachment.url;
                modalIconPreview.innerHTML = `<img src="${attachment.url}" alt="Icon Preview" style="width: 50px; height: 50px; object-fit: contain;">`;
                modalRemoveIconBtn.style.display = 'inline-block';
            });

            mediaUploader.open();
        });
    }

    if (modalRemoveIconBtn) {
        modalRemoveIconBtn.addEventListener('click', function() {
            clearModalIcon();
        });
    }

    function clearModalIcon() {
        modalIconInput.value = '';
        modalIconPreview.innerHTML = '<span class="no-icon">No icon selected</span>';
        modalRemoveIconBtn.style.display = 'none';
    }

    // Load benefit box data from button attributes (instant loading)
    function loadBenefitBoxDataFromButton(button) {
        // Get data from button attributes
        const type = button.getAttribute('data-type');
        const title = button.getAttribute('data-title');
        const subtitle = button.getAttribute('data-subtitle');
        const icon = button.getAttribute('data-icon');
        const sortOrder = button.getAttribute('data-sort-order');
        const isActive = button.getAttribute('data-is-active');
        const whatsappNumber = button.getAttribute('data-whatsapp-number');
        const predefinedText = button.getAttribute('data-predefined-text');
        const alwaysOnline = button.getAttribute('data-always-online');
        const workingDaysMessage = button.getAttribute('data-working-days-message');
        const nonWorkingDaysMessage = button.getAttribute('data-non-working-days-message');
        const availabilitySchedule = button.getAttribute('data-availability-schedule');

        // Fill form fields
        document.getElementById('modal-type').value = type;
        document.getElementById('modal-title-input').value = title;
        document.getElementById('modal-subtitle').value = subtitle;
        document.getElementById('modal-sort-order').value = sortOrder;
        document.getElementById('modal-is-active').checked = isActive == 1;
        
        // Handle icon
        if (icon) {
            modalIconInput.value = icon;
            modalIconPreview.innerHTML = `<img src="${icon}" alt="Icon Preview" style="width: 50px; height: 50px; object-fit: contain;">`;
            modalRemoveIconBtn.style.display = 'inline-block';
        } else {
            clearModalIcon();
        }

        // Fill WhatsApp fields
        if (type === 'whatsapp') {
            document.getElementById('modal-whatsapp-number').value = whatsappNumber || '';
            document.getElementById('modal-predefined-text').value = predefinedText || '';
            document.getElementById('modal-always-online').checked = alwaysOnline == 1;
            document.getElementById('modal-working-days-message').value = workingDaysMessage || '';
            document.getElementById('modal-non-working-days-message').value = nonWorkingDaysMessage || '';
            
            // Load availability schedule
            if (availabilitySchedule) {
                try {
                    const schedule = JSON.parse(availabilitySchedule);
                    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                    days.forEach(day => {
                        if (schedule[day]) {
                            document.getElementById(`modal-${day}-enabled`).checked = schedule[day].enabled || false;
                            document.getElementById(`modal-${day}-start`).value = schedule[day].start || '10:00';
                            document.getElementById(`modal-${day}-end`).value = schedule[day].end || '21:00';
                        }
                    });
                } catch (e) {
                    // Use default values if parsing fails
                    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                    days.forEach(day => {
                        document.getElementById(`modal-${day}-enabled`).checked = true;
                        document.getElementById(`modal-${day}-start`).value = '10:00';
                        document.getElementById(`modal-${day}-end`).value = '21:00';
                    });
                }
            }
        }
        
        toggleModalWhatsAppFields();
    }

    // Function to update table row display
    function updateTableRow(editButton, formData) {
        const row = editButton.closest('tr');
        if (!row) return;
        
        const type = formData.get('type');
        const title = formData.get('title');
        const subtitle = formData.get('subtitle');
        const icon = formData.get('icon');
        const sortOrder = formData.get('sort_order');
        const isActive = formData.get('is_active') ? '1' : '0';
        
        // Update type column
        const typeCell = row.querySelector('.column-type');
        if (typeCell) {
            let badgeClass = 'info';
            if (type === 'whatsapp') {
                badgeClass = 'success';
            } else if (type === 'regular') {
                badgeClass = 'primary';
            }
            typeCell.innerHTML = `<span class="badge badge-${badgeClass}">${type.charAt(0).toUpperCase() + type.slice(1)}</span>`;
        }
        
        // Update title column
        const titleCell = row.querySelector('.column-title');
        if (titleCell) {
            titleCell.innerHTML = `<strong>${title}</strong>`;
        }
        
        // Update subtitle column
        const subtitleCell = row.querySelector('.column-subtitle');
        if (subtitleCell) {
            subtitleCell.textContent = subtitle;
        }
        
        // Update icon column
        const iconCell = row.querySelector('.column-icon');
        if (iconCell) {
            if (icon) {
                iconCell.innerHTML = `<img src="${icon}" alt="${title}" style="width: 30px; height: 30px; object-fit: contain;">`;
            } else {
                iconCell.innerHTML = '';
            }
        }
        
        // Update sort order column
        const sortCell = row.querySelector('.column-sort');
        if (sortCell) {
            sortCell.textContent = sortOrder;
        }
        
        // Update status column (keep the button structure)
        const statusCell = row.querySelector('.column-status');
        if (statusCell) {
            const statusButton = statusCell.querySelector('.status-toggle-btn');
            if (statusButton) {
                const badge = statusButton.querySelector('.badge');
                if (badge) {
                    badge.className = `badge badge-${isActive === '1' ? 'success' : 'secondary'}`;
                    badge.textContent = isActive === '1' ? 'Active' : 'Inactive';
                }
                statusButton.setAttribute('data-current-status', isActive);
            }
        }
        
        // Update edit button data attributes
        editButton.setAttribute('data-type', type);
        editButton.setAttribute('data-title', title);
        editButton.setAttribute('data-subtitle', subtitle);
        editButton.setAttribute('data-icon', icon);
        editButton.setAttribute('data-sort-order', sortOrder);
        editButton.setAttribute('data-is-active', isActive);
        
        // Update WhatsApp-specific data attributes if needed
        if (type === 'whatsapp') {
            editButton.setAttribute('data-whatsapp-number', formData.get('whatsapp_number') || '');
            editButton.setAttribute('data-predefined-text', formData.get('predefined_text') || '');
            editButton.setAttribute('data-always-online', formData.get('always_online') ? '1' : '0');
            editButton.setAttribute('data-working-days-message', formData.get('working_days_message') || '');
            editButton.setAttribute('data-non-working-days-message', formData.get('non_working_days_message') || '');
            
            // Update availability schedule
            const availabilitySchedule = {};
            const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            days.forEach(day => {
                availabilitySchedule[day] = {
                    enabled: formData.get(`availability_schedule[${day}][enabled]`) ? true : false,
                    start: formData.get(`availability_schedule[${day}][start]`) || '10:00',
                    end: formData.get(`availability_schedule[${day}][end]`) || '21:00'
                };
            });
            editButton.setAttribute('data-availability-schedule', JSON.stringify(availabilitySchedule));
        } else {
            // Remove WhatsApp-specific attributes for non-WhatsApp types
            editButton.removeAttribute('data-whatsapp-number');
            editButton.removeAttribute('data-predefined-text');
            editButton.removeAttribute('data-always-online');
            editButton.removeAttribute('data-working-days-message');
            editButton.removeAttribute('data-non-working-days-message');
            editButton.removeAttribute('data-availability-schedule');
        }
    }

    // Function to create new table row
    function createNewTableRow(id, data) {
        const tableBody = document.querySelector('.wp-list-table tbody');
        if (!tableBody) return;

        // Remove "No benefit box settings found" row if it exists
        const noDataRow = tableBody.querySelector('tr td[colspan="8"]');
        if (noDataRow) {
            noDataRow.closest('tr').remove();
        }

        // Create new row
        const newRow = document.createElement('tr');
        
        // Determine badge class based on type
        let badgeClass = 'info';
        if (data.type === 'whatsapp') {
            badgeClass = 'success';
        } else if (data.type === 'regular') {
            badgeClass = 'primary';
        }

        // Create icon HTML
        const iconHtml = data.icon ? 
            `<img src="${data.icon}" alt="${data.title}" style="width: 30px; height: 30px; object-fit: contain;">` : 
            '';

        // Create status HTML
        const statusHtml = `<button type="button" class="button button-link status-toggle-btn" data-id="${id}" data-current-status="${data.is_active}">
            <span class="badge badge-${data.is_active ? 'success' : 'secondary'}">${data.is_active ? 'Active' : 'Inactive'}</span>
        </button>`;

        // Create edit button data attributes
        let editButtonData = `data-id="${id}" data-type="${data.type}" data-title="${data.title}" data-subtitle="${data.subtitle}" data-icon="${data.icon || ''}" data-sort-order="${data.sort_order}" data-is-active="${data.is_active}"`;
        
        if (data.type === 'whatsapp') {
            editButtonData += ` data-whatsapp-number="${data.whatsapp_number || ''}" data-predefined-text="${data.predefined_text || ''}" data-always-online="${data.always_online || '0'}" data-working-days-message="${data.working_days_message || ''}" data-non-working-days-message="${data.non_working_days_message || ''}" data-availability-schedule="${data.availability_schedule || ''}"`;
        }

        newRow.innerHTML = `
            <th scope="row" class="check-column">
                <input type="checkbox" name="post[]" value="${id}" class="benefit-box-checkbox">
            </th>
            <td class="column-type">
                <span class="badge badge-${badgeClass}">${data.type.charAt(0).toUpperCase() + data.type.slice(1)}</span>
            </td>
            <td class="column-title">
                <strong>${data.title}</strong>
            </td>
            <td class="column-subtitle">
                ${data.subtitle}
            </td>
            <td class="column-icon">
                ${iconHtml}
            </td>
            <td class="column-status">
                ${statusHtml}
            </td>
            <td class="column-sort">
                ${data.sort_order}
            </td>
            <td class="column-actions">
                <button type="button" class="button button-small edit-benefit-box" ${editButtonData}>Edit</button>
                <button type="button" class="button button-small button-link-delete delete-benefit-box" data-id="${id}">Delete</button>
            </td>
        `;

        // Add event listener to the new edit button
        const newEditButton = newRow.querySelector('.edit-benefit-box');
        if (newEditButton) {
            newEditButton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                modalTitle.textContent = 'Edit Benefit Box';
                document.getElementById('benefit-box-id').value = id;
                loadBenefitBoxDataFromButton(this);
                modal.style.display = 'block';
            });
        }

        // Add event listener to the new delete button
        const newDeleteButton = newRow.querySelector('.delete-benefit-box');
        if (newDeleteButton) {
            newDeleteButton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                handleDeleteBenefitBox(id, this);
            });
        }

        // Add event listener to the new status toggle button
        const newStatusToggleButton = newRow.querySelector('.status-toggle-btn');
        if (newStatusToggleButton) {
            newStatusToggleButton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const currentStatus = parseInt(this.getAttribute('data-current-status'));
                const newStatus = currentStatus === 1 ? 0 : 1;
                handleUpdateBenefitBoxStatus(id, newStatus, this);
            });
        }

        // Add the new row to the table
        tableBody.appendChild(newRow);
    }

    // Function to handle delete benefit box
    function handleDeleteBenefitBox(id, button) {
        if (!confirm('Are you sure you want to delete this benefit box?')) {
            return;
        }

        // Ensure id is a valid number
        const numericId = parseInt(id);
        
        if (isNaN(numericId) || numericId <= 0) {
            showNotification('Error: Invalid benefit box ID', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete_benefit_box_data');
        formData.append('id', numericId);
        formData.append('_wpnonce', benefitBoxAdmin.deleteNonce);

        // Disable the button during deletion
        button.disabled = true;
        button.textContent = 'Deleting...';

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Benefit box deleted successfully!', 'success');
                
                // Remove the table row
                const row = button.closest('tr');
                if (row) {
                    row.remove();
                    
                    // Check if table is empty and show "No data" message
                    const tableBody = document.querySelector('.wp-list-table tbody');
                    if (tableBody && tableBody.children.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="7">No benefit box settings found.</td></tr>';
                    }
                }
            } else {
                showNotification('Error: ' + (data.data || 'Unknown error'), 'error');
                // Re-enable the button on error
                button.disabled = false;
                button.textContent = 'Delete';
            }
        })
        .catch(error => {
            showNotification('Error deleting: ' + error.message, 'error');
            // Re-enable the button on error
            button.disabled = false;
            button.textContent = 'Delete';
        });
    }

    // Function to handle update benefit box status
    function handleUpdateBenefitBoxStatus(id, newStatus, button) {
        const formData = new FormData();
        formData.append('action', 'update_benefit_box_status');
        formData.append('id', id);
        formData.append('status', newStatus);
        formData.append('_wpnonce', benefitBoxAdmin.updateStatusNonce);

        // Disable the button during update
        button.disabled = true;

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Status updated successfully!', 'success');
                
                // Update the button appearance
                const badge = button.querySelector('.badge');
                if (badge) {
                    badge.className = `badge badge-${newStatus === 1 ? 'success' : 'secondary'}`;
                    badge.textContent = newStatus === 1 ? 'Active' : 'Inactive';
                }
                
                // Update the data attribute
                button.setAttribute('data-current-status', newStatus);
            } else {
                showNotification('Error: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showNotification('Error updating status: ' + error.message, 'error');
        })
        .finally(() => {
            // Re-enable the button
            button.disabled = false;
        });
    }

    // Helper functions for bulk actions
    function updateBulkActionButton() {
        const checkedBoxes = document.querySelectorAll('.benefit-box-checkbox:checked');
        const action = bulkActionSelector.value;
        
        if (checkedBoxes.length > 0 && action !== '-1') {
            doActionBtn.disabled = false;
            doActionBtn.textContent = `Apply to ${checkedBoxes.length} item(s)`;
        } else {
            doActionBtn.disabled = true;
            doActionBtn.textContent = 'Apply';
        }
    }

    function updateSelectAllCheckbox() {
        const checkboxes = document.querySelectorAll('.benefit-box-checkbox');
        const checkedBoxes = document.querySelectorAll('.benefit-box-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }

    function handleBulkDelete() {
        const checkedBoxes = document.querySelectorAll('.benefit-box-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(checkbox => checkbox.value);
        
        if (ids.length === 0) {
            showNotification('No items selected for deletion', 'error');
            return;
        }

        if (!confirm(`Are you sure you want to delete ${ids.length} benefit box(es)? This action cannot be undone.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'bulk_delete_benefit_boxes');
        formData.append('ids', JSON.stringify(ids));
        formData.append('_wpnonce', benefitBoxAdmin.bulkDeleteNonce);

        // Disable the button during deletion
        doActionBtn.disabled = true;
        doActionBtn.textContent = 'Deleting...';

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.data.message, 'success');
                
                // Remove the selected rows
                checkedBoxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (row) {
                        row.remove();
                    }
                });
                
                // Check if table is empty and show "No data" message
                const tableBody = document.querySelector('.wp-list-table tbody');
                if (tableBody && tableBody.children.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8">No benefit box settings found.</td></tr>';
                }
                
                // Reset bulk action controls
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
                bulkActionSelector.value = '-1';
                updateBulkActionButton();
            } else {
                showNotification('Error: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting items: ' + error.message, 'error');
        })
        .finally(() => {
            // Re-enable the button
            doActionBtn.disabled = false;
            doActionBtn.textContent = 'Apply';
        });
    }

    // Apply to All Days functionality
    const applyToAllDaysBtn = document.getElementById('apply-to-all-days');
    if (applyToAllDaysBtn) {
        applyToAllDaysBtn.addEventListener('click', function() {
            // Get the values from Sunday (first day)
            const sundayStart = document.getElementById('modal-sunday-start').value;
            const sundayEnd = document.getElementById('modal-sunday-end').value;
            const sundayEnabled = document.getElementById('modal-sunday-enabled').checked;
            
            // Apply to all other days
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            days.forEach(day => {
                document.getElementById(`modal-${day}-start`).value = sundayStart;
                document.getElementById(`modal-${day}-end`).value = sundayEnd;
                document.getElementById(`modal-${day}-enabled`).checked = sundayEnabled;
            });
            
            showNotification('Applied Sunday settings to all days', 'success');
        });
    }

    // Save benefit box
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const form = document.getElementById('benefit-box-form');
            const formData = new FormData(form);
            const benefitBoxId = formData.get('id') || document.getElementById('benefit-box-id').value;
            
            formData.append('action', 'save_benefit_box_data');
            formData.append('id', benefitBoxId);
            formData.append('_wpnonce', benefitBoxAdmin.saveNonce);
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.data.message || 'Benefit box saved successfully!', 'success');
                    closeModal();
                    
                    const benefitBoxId = document.getElementById('benefit-box-id').value;
                    const isNewItem = data.data.is_new; // Use server response to determine if it's new
                    
                    if (isNewItem) {
                        // Create new table row for new benefit box
                        createNewTableRow(data.data.id, data.data.data);
                    } else {
                        // Update existing row
                        const editButton = document.querySelector(`.edit-benefit-box[data-id="${data.data.id}"]`);
                        if (editButton) {
                            // Update button data attributes with new values
                            const form = document.getElementById('benefit-box-form');
                            const formData = new FormData(form);
                            
                            // Update basic attributes
                            editButton.setAttribute('data-type', formData.get('type'));
                            editButton.setAttribute('data-title', formData.get('title'));
                            editButton.setAttribute('data-subtitle', formData.get('subtitle'));
                            editButton.setAttribute('data-icon', formData.get('icon'));
                            editButton.setAttribute('data-sort-order', formData.get('sort_order'));
                            editButton.setAttribute('data-is-active', formData.get('is_active') ? '1' : '0');
                            
                            // Update WhatsApp-specific attributes
                            if (formData.get('type') === 'whatsapp') {
                                editButton.setAttribute('data-whatsapp-number', formData.get('whatsapp_number') || '');
                                editButton.setAttribute('data-predefined-text', formData.get('predefined_text') || '');
                                editButton.setAttribute('data-always-online', formData.get('always_online') ? '1' : '0');
                                editButton.setAttribute('data-working-days-message', formData.get('working_days_message') || '');
                                editButton.setAttribute('data-non-working-days-message', formData.get('non_working_days_message') || '');
                                
                                // Update availability schedule
                                const availabilitySchedule = {};
                                const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                                days.forEach(day => {
                                    availabilitySchedule[day] = {
                                        enabled: formData.get(`availability_schedule[${day}][enabled]`) ? true : false,
                                        start: formData.get(`availability_schedule[${day}][start]`) || '10:00',
                                        end: formData.get(`availability_schedule[${day}][end]`) || '21:00'
                                    };
                                });
                                editButton.setAttribute('data-availability-schedule', JSON.stringify(availabilitySchedule));
                            } else {
                                // Remove WhatsApp-specific attributes for non-WhatsApp types
                                editButton.removeAttribute('data-whatsapp-number');
                                editButton.removeAttribute('data-predefined-text');
                                editButton.removeAttribute('data-always-online');
                                editButton.removeAttribute('data-working-days-message');
                                editButton.removeAttribute('data-non-working-days-message');
                                editButton.removeAttribute('data-availability-schedule');
                            }
                            
                            // Update the table row display
                            updateTableRow(editButton, formData);
                        } else {
                            // Fallback: reload the page to show updated data
                            location.reload();
                        }
                    }
                } else {
                    showNotification('Error: ' + (data.data || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving: ' + error.message, 'error');
            })
            .finally(() => {
                saveBtn.textContent = 'Save';
                saveBtn.disabled = false;
            });
        });
    }
});
