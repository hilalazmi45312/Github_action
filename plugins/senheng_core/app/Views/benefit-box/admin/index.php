<?php

BenefitBoxController::enqueueAdminAssets();

/** @var array $settings */
/** @var object|null $editing_setting */
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
$is_editing = isset($editing_setting) && $editing_setting;
$is_creating = isset($_GET['action']) && $_GET['action'] === 'new';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Benefit Box</h1>
    <?php if (!$is_editing && !$is_creating): ?>
        <a href="<?php echo admin_url('admin.php?page=senheng-benefit-box-settings&action=new'); ?>" class="page-title-action">Add New</a>
    <?php endif; ?>
    
    <!-- Simple Explanation -->
    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <p style="margin: 0; color: #666;">
            <strong>Where it displays:</strong> The benefit box appears on product pages, category pages, shop pages, and any page using the shortcode <code>[benefit_box]</code>. On desktop it shows as a grid layout, while on mobile/tablet it displays as a horizontal slider.
        </p>
    </div>
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($message) {
                    case 'created':
                        echo 'Benefit box setting created successfully.';
                        break;
                    case 'updated':
                        echo 'Benefit box setting updated successfully.';
                        break;
                    case 'deleted':
                        echo 'Benefit box setting deleted successfully.';
                        break;
                    case 'status_updated':
                        echo 'Status updated successfully.';
                        break;

                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Simple Enable/Disable Toggle -->
    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <form method="post" action="" style="margin: 0;">
            <?php wp_nonce_field('toggle_benefit_box'); ?>
            <input type="hidden" name="action" value="toggle_benefit_box">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" 
                       name="benefit_box_enabled" 
                       value="1" 
                       <?php checked($is_enabled, true); ?>
                       onchange="this.form.submit()">
                <strong>Enable Benefit Box Feature</strong>
                <?php if ($is_enabled): ?>
                    <span style="color: #46b450;">✓ Active</span>
                <?php else: ?>
                    <span style="color: #dc3232;">✗ Disabled</span>
                <?php endif; ?>
            </label>
        </form>
    </div>

    <!-- Settings List -->
    
        <div class="tablenav top">
            <div class="alignleft actions">
                <button type="button" class="button button-primary" id="add-new-benefit-box">Add New Benefit Box</button>
            </div>
            <div class="alignright actions">
                <select id="bulk-action-selector-top">
                    <option value="-1">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="button" class="button" id="doaction" disabled>Apply</button>
            </div>
            <div class="clear"></div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </th>
                    <th scope="col" class="manage-column column-type">Type</th>
                    <th scope="col" class="manage-column column-title">Title</th>
                    <th scope="col" class="manage-column column-subtitle">Subtitle</th>
                    <th scope="col" class="manage-column column-icon">Icon</th>
                    <th scope="col" class="manage-column column-status">Status</th>
                    <th scope="col" class="manage-column column-sort">Sort Order</th>
                    <th scope="col" class="manage-column column-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($settings)): ?>
                    <tr>
                        <td colspan="8">No benefit box settings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($settings as $id => $setting): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="post[]" value="<?php echo $id; ?>" class="benefit-box-checkbox">
                            </th>
                            <td class="column-type">
                                <span class="badge badge-<?php echo $setting['type'] === 'whatsapp' ? 'success' : ($setting['type'] === 'regular' ? 'primary' : 'info'); ?>">
                                    <?php echo ucfirst($setting['type']); ?>
                                </span>
                            </td>
                            <td class="column-title">
                                <strong><?php echo esc_html($setting['title']); ?></strong>
                            </td>
                            <td class="column-subtitle">
                                <?php echo esc_html(wp_trim_words($setting['subtitle'], 10)); ?>
                            </td>
                            <td class="column-icon">
                                <?php if ($setting['icon']): ?>
                                    <img src="<?php echo esc_url($setting['icon']); ?>" 
                                         alt="<?php echo esc_attr($setting['title']); ?>" 
                                         style="width: 30px; height: 30px; object-fit: contain;">
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <button type="button" class="button button-link status-toggle-btn" 
                                        data-id="<?php echo $id; ?>" 
                                        data-current-status="<?php echo $setting['is_active']; ?>">
                                    <span class="badge badge-<?php echo $setting['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $setting['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </button>
                            </td>
                            <td class="column-sort">
                                <?php echo esc_html($setting['sort_order']); ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button button-small edit-benefit-box" 
                                        data-id="<?php echo $id; ?>"
                                        data-type="<?php echo esc_attr($setting['type']); ?>"
                                        data-title="<?php echo esc_attr($setting['title']); ?>"
                                        data-subtitle="<?php echo esc_attr($setting['subtitle']); ?>"
                                        data-icon="<?php echo esc_attr($setting['icon']); ?>"
                                        data-sort-order="<?php echo esc_attr($setting['sort_order']); ?>"
                                        data-is-active="<?php echo esc_attr($setting['is_active']); ?>"
                                        data-whatsapp-number="<?php echo esc_attr($setting['whatsapp_number'] ?? ''); ?>"
                                        data-predefined-text="<?php echo esc_attr($setting['predefined_text'] ?? ''); ?>"
                                        data-always-online="<?php echo esc_attr($setting['always_online'] ?? '0'); ?>"
                                        data-working-days-message="<?php echo esc_attr($setting['working_days_message'] ?? ''); ?>"
                                        data-non-working-days-message="<?php echo esc_attr($setting['non_working_days_message'] ?? ''); ?>"
                                        data-availability-schedule="<?php echo esc_attr($setting['availability_schedule'] ?? ''); ?>">Edit</button>
                                <button type="button" class="button button-small button-link-delete delete-benefit-box" 
                                        data-id="<?php echo $id; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <!-- Popup Modal for Edit/Create -->
    <div id="benefit-box-modal" class="benefit-box-modal" style="display: none;">
        <div class="benefit-box-modal-content">
            <div class="benefit-box-modal-header">
                <h2 id="modal-title">Edit Benefit Box</h2>
                <span class="benefit-box-modal-close">&times;</span>
            </div>
            <div class="benefit-box-modal-body">
                <form id="benefit-box-form">
                    <input type="hidden" name="id" id="benefit-box-id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="modal-type">Type</label></th>
                            <td>
                                <select name="type" id="modal-type" required>
                                    <option value="">Select Type</option>
                                    <option value="regular">Regular Box</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="installment">Installment</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="modal-title-input">Title</label></th>
                            <td>
                                <input type="text" name="title" id="modal-title-input" class="regular-text" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="modal-subtitle">Subtitle/Description</label></th>
                            <td>
                                <textarea name="subtitle" id="modal-subtitle" rows="3" class="large-text"></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="modal-icon">Icon</label></th>
                            <td>
                                <div class="icon-selector">
                                    <input type="hidden" name="icon" id="modal-icon" value="">
                                    <div id="modal-icon-preview" class="icon-preview">
                                        <span class="no-icon">No icon selected</span>
                                    </div>
                                    <button type="button" id="modal-select-icon" class="button">Select Icon</button>
                                    <button type="button" id="modal-remove-icon" class="button button-secondary" style="display:none;">Remove Icon</button>
                                </div>
                                <p class="description">Select an icon from the WordPress Media Library</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="modal-sort-order">Sort Order</label></th>
                            <td>
                                <input type="number" name="sort_order" id="modal-sort-order" class="small-text" min="0" value="0">
                                <p class="description">Lower numbers appear first</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Active</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_active" id="modal-is-active" value="1" checked>
                                    Active
                                </label>
                            </td>
                        </tr>

                        <!-- WhatsApp specific fields -->
                        <tr id="modal-whatsapp-fields" class="whatsapp-specific" style="display: none;">
                            <th scope="row" colspan="2">
                                <h3>WhatsApp Settings</h3>
                            </th>
                        </tr>
                        
                        <tr class="modal-whatsapp-field" style="display: none;">
                            <th scope="row"><label for="modal-whatsapp-number">WhatsApp Number</label></th>
                            <td>
                                <input type="text" name="whatsapp_number" id="modal-whatsapp-number" class="regular-text">
                                <p class="description">Refer to <a href="https://faq.whatsapp.com/en/general/21016748" target="_blank">WhatsApp FAQ</a> for detailed explanation.</p>
                            </td>
                        </tr>
                        
                        <tr class="modal-whatsapp-field" style="display: none;">
                            <th scope="row"><label for="modal-predefined-text">Predefined Text</label></th>
                            <td>
                                <textarea name="predefined_text" id="modal-predefined-text" rows="4" class="large-text"></textarea>
                                <p class="description">Use [senheng_page_title] and [senheng_page_url] shortcodes.</p>
                            </td>
                        </tr>

                        <tr class="modal-whatsapp-field" style="display: none;">
                            <th scope="row">Always Available Online</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="always_online" id="modal-always-online" value="1">
                                    Enable to show always online status
                                </label>
                                <p class="description">When enabled, WhatsApp support will always show as online.</p>
                            </td>
                        </tr>

                        <tr class="modal-whatsapp-field" style="display: none;">
                            <th scope="row">Custom Availability</th>
                            <td>
                                <div class="custom-availability">
                                    <div class="availability-header">
                                        <button type="button" id="apply-to-all-days" class="button button-secondary" style="float: right;">Apply to All Days</button>
                                        <div style="clear: both;"></div>
                                    </div>
                                    
                                    <div class="availability-schedule">
                                        <?php
                                        $days = ['sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 
                                                'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'];
                                        foreach ($days as $day_key => $day_name): ?>
                                            <div class="day-schedule">
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="availability_schedule[<?php echo $day_key; ?>][enabled]" 
                                                           id="modal-<?php echo $day_key; ?>-enabled" value="1" checked>
                                                    <?php echo $day_name; ?>
                                                </label>
                                                
                                                <select name="availability_schedule[<?php echo $day_key; ?>][start]" 
                                                        id="modal-<?php echo $day_key; ?>-start" class="time-select">
                                                    <?php for ($hour = 0; $hour < 24; $hour++): 
                                                        for ($minute = 0; $minute < 60; $minute += 30): ?>
                                                            <option value="<?php echo sprintf('%02d:%02d', $hour, $minute); ?>" 
                                                                    <?php echo ($hour == 10 && $minute == 0) ? 'selected' : ''; ?>>
                                                                <?php echo sprintf('%02d:%02d', $hour, $minute); ?>
                                                            </option>
                                                    <?php endfor; endfor; ?>
                                                </select>
                                                
                                                <select name="availability_schedule[<?php echo $day_key; ?>][end]" 
                                                        id="modal-<?php echo $day_key; ?>-end" class="time-select">
                                                    <?php for ($hour = 0; $hour < 24; $hour++): 
                                                        for ($minute = 0; $minute < 60; $minute += 30): ?>
                                                            <option value="<?php echo sprintf('%02d:%02d', $hour, $minute); ?>" 
                                                                    <?php echo ($hour == 21 && $minute == 0) ? 'selected' : ''; ?>>
                                                                <?php echo sprintf('%02d:%02d', $hour, $minute); ?>
                                                            </option>
                                                    <?php endfor; endfor; ?>
                                                </select>
                                                
                                                <button type="button" class="button button-small add-time-slot">Add</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr class="modal-whatsapp-field" style="display: none;">
                            <th scope="row">Description Text When Offline</th>
                            <td>
                                <div class="offline-messages">
                                    <div class="message-group">
                                        <label for="modal-working-days-message"><strong>Working days message:</strong></label>
                                        <textarea name="working_days_message" id="modal-working-days-message" rows="2" class="large-text"></textarea>
                                        <p class="description">Use [senheng_time_work] shortcode for exact time.</p>
                                    </div>
                                    
                                    <div class="message-group">
                                        <label for="modal-non-working-days-message"><strong>Non-working days message:</strong></label>
                                        <textarea name="non_working_days_message" id="modal-non-working-days-message" rows="2" class="large-text"></textarea>
                                        <p class="description">Text to display on non-working days.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            <div class="benefit-box-modal-footer">
                <button type="button" id="save-benefit-box" class="button button-primary">Save</button>
                <button type="button" id="cancel-benefit-box" class="button">Cancel</button>
            </div>
        </div>
    </div>
</div>


