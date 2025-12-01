<?php

class BenefitBoxController
{
    const OPTION_KEY_ENABLED = 'senheng_benefit_box_enabled';

    public static function enqueueAssets(): void
    {
        // Only enqueue assets if benefit box is enabled
        if (!self::isEnabled()) {
            return;
        }

        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        wp_enqueue_style(
            'benefit-box-frontend',
            SENHENG_CORE_URL . 'assets/css/benefit-box/frontend.css'
        );

        wp_enqueue_script(
            'benefit-box-frontend-js',
            SENHENG_CORE_URL . 'assets/js/benefit-box/frontend.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public static function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'benefit-box-admin',
            SENHENG_CORE_URL . 'assets/css/benefit-box/admin.css'
        );

        wp_enqueue_script(
            'benefit-box-admin-js',
            SENHENG_CORE_URL . 'assets/js/benefit-box/admin.js',
            ['jquery', 'wp-util'],
            '1.0.0',
            true
        );

        // Localize script with nonces
        wp_localize_script('benefit-box-admin-js', 'benefitBoxAdmin', [
            'saveNonce' => wp_create_nonce('save_benefit_box_data'),
            'deleteNonce' => wp_create_nonce('delete_benefit_box_data'),
            'updateStatusNonce' => wp_create_nonce('update_benefit_box_status'),
            'bulkDeleteNonce' => wp_create_nonce('bulk_delete_benefit_boxes')
        ]);

        // Enqueue WordPress Media Library
        wp_enqueue_media();
    }

    public static function registerShortcode(): void
    {
        add_shortcode('benefit_box', [self::class, 'renderShortcode']);
    }

    public static function renderShortcode(): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        $product_id = get_the_ID();
        $base_url = SENHENG_CORE_URL . 'assets/uploads/';

        // Delivery options mapped to individual ACF fields
        $delivery_options = [
            'delivery_free' => [
                'title' => 'Free delivery',
                'image' => 'free-delivery.png',
                'description' => '',
            ],
            'delivery_normal' => [
                'title' => 'Normal delivery',
                'image' => 'free-delivery.png',
                'description' => 'West Malaysia: 1-3 working days<br>East Malaysia: 3-5 working days',
            ],
            'delivery_pickup' => [
                'title' => 'Pickup in store',
                'image' => 'pickup.png',
                'description' => '3-5 working days',
            ],
        ];

        $cards = [];

        // Check each independent field
        foreach ($delivery_options as $acf_field => $option) {
            $enabled = get_field($acf_field, $product_id);
            error_log("Delivery option check - Field: $acf_field, Enabled: " . ($enabled ? 'true' : 'false'));
            if ($enabled) {
                $cards[] = [
                    'icon' => $base_url . $option['image'],
                    'title' => $option['title'],
                    'subtitle' => $option['description'],
                ];
            }
        }

        // Get benefit box settings from database
        $benefit_settings = BenefitBox::getActiveSettings();
        foreach ($benefit_settings as $setting) {
            $card = [
                'icon' => $setting['icon'],
                'title' => $setting['title'],
                'subtitle' => $setting['subtitle'],
            ];

            if ($setting['type'] === 'whatsapp') {
                $card['whatsapp_number'] = $setting['whatsapp_number'];
                $card['predefined_text'] = $setting['predefined_text'];
                $card['is_whatsapp'] = true;
            } elseif ($setting['type'] === 'installment') {
                $card['is_installment'] = true;
            } elseif ($setting['type'] === 'regular') {
                $card['is_regular'] = true;
            }

            $cards[] = $card;
        }

        ob_start();
        $data = [
            'cards' => $cards,
        ];
        include SENHENG_CORE_VIEW_PATH . 'benefit-box/index.php';
        return (string) ob_get_clean();
    }

    // Admin methods
    public static function adminIndex()
    {
        // Handle simple toggle
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_benefit_box') {
            if (wp_verify_nonce($_POST['_wpnonce'], 'toggle_benefit_box')) {
                $enabled = isset($_POST['benefit_box_enabled']) ? 1 : 0;
                update_option(self::OPTION_KEY_ENABLED, $enabled);
            }
        }

        // Handle actions
        if (isset($_GET['action'])) {
            self::handleAdminActions();
        }

        $settings = BenefitBox::all();
        $is_enabled = self::isEnabled();

        include SENHENG_CORE_VIEW_PATH . 'benefit-box/admin/index.php';
    }

    private static function handleAdminActions()
    {
        $action = $_GET['action'];

        switch ($action) {
            case 'delete':
                if (wp_verify_nonce($_GET['_wpnonce'], 'delete_benefit_box')) {
                    $id = intval($_GET['id']);
                    BenefitBox::delete($id);
                    wp_redirect(admin_url('admin.php?page=senheng-benefit-box-settings&message=deleted'));
                    exit;
                }
                break;

            case 'toggle':
                if (wp_verify_nonce($_GET['_wpnonce'], 'toggle_benefit_box_status')) {
                    $id = intval($_GET['id']);
                    $status = intval($_GET['status']);
                    BenefitBox::updateStatus($id, $status);
                    wp_redirect(admin_url('admin.php?page=senheng-benefit-box-settings&message=status_updated'));
                    exit;
                }
                break;
        }
    }



    // AJAX handler to save benefit box data
    public static function handleSaveBenefitBoxData()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_benefit_box_data')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            // Debug logging
            error_log('Save request - Raw ID: ' . ($_POST['id'] ?? 'not set') . ', Parsed ID: ' . $id . ', Is new: ' . ($id === 0 ? 'true' : 'false'));
            error_log('Save request - POST data: ' . print_r($_POST, true));

            // Validate required fields
            if (empty($_POST['type']) || empty($_POST['title'])) {
                wp_send_json_error('Required fields missing: type and title are required');
            }

            $result = BenefitBox::saveFromRequest($_POST, $id);

            if ($result === false) {
                wp_send_json_error('Failed to save benefit box - database operation failed');
            }

            // For new items, return the created data
            if ($id === 0) {
                $new_box = BenefitBox::find($result);
                if (!$new_box) {
                    wp_send_json_error('Benefit box was created but could not be retrieved');
                }
                wp_send_json_success([
                    'message' => 'Benefit box saved successfully',
                    'is_new' => true,
                    'id' => $result,
                    'data' => $new_box
                ]);
            } else {
                $updated_box = BenefitBox::find($id);
                if (!$updated_box) {
                    wp_send_json_error('Benefit box was updated but could not be retrieved');
                }
                wp_send_json_success([
                    'message' => 'Benefit box saved successfully',
                    'is_new' => false,
                    'id' => $id,
                    'data' => $updated_box
                ]);
            }
        } catch (Exception $e) {
            error_log('Benefit box save error: ' . $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    // AJAX handler to delete benefit box
    public static function handleDeleteBenefitBoxData()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_benefit_box_data')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            $id = intval($_POST['id']);

            // Debug logging
            error_log('Delete request - Raw ID: ' . $_POST['id'] . ', Parsed ID: ' . $id);

            if ($id <= 0) {
                wp_send_json_error('Invalid benefit box ID: ' . $id);
            }

            $result = BenefitBox::delete($id);

            if ($result === false) {
                wp_send_json_error('Failed to delete benefit box');
            }

            wp_send_json_success([
                'message' => 'Benefit box deleted successfully',
                'id' => $id
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    // AJAX handler to update benefit box status
    public static function handleUpdateBenefitBoxStatus()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'update_benefit_box_status')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);

            if ($id <= 0) {
                wp_send_json_error('Invalid benefit box ID');
            }

            if (!in_array($status, [0, 1])) {
                wp_send_json_error('Invalid status value');
            }

            $result = BenefitBox::updateStatus($id, $status);

            if ($result === false) {
                wp_send_json_error('Failed to update benefit box status');
            }

            wp_send_json_success([
                'message' => 'Benefit box status updated successfully',
                'id' => $id,
                'status' => $status
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    // AJAX handler to bulk delete benefit boxes
    public static function handleBulkDeleteBenefitBoxes()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk_delete_benefit_boxes')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            $raw_ids = isset($_POST['ids']) ? $_POST['ids'] : '';

            // Decode JSON if it's a string
            if (is_string($raw_ids)) {
                $ids = json_decode($raw_ids, true);
            } else {
                $ids = $raw_ids;
            }

            if (empty($ids) || !is_array($ids)) {
                wp_send_json_error('No benefit boxes selected for deletion. Received: ' . $raw_ids);
            }

            $deleted_count = 0;
            $failed_ids = [];

            foreach ($ids as $id) {
                $numeric_id = intval($id);
                if ($numeric_id > 0) {
                    $result = BenefitBox::delete($numeric_id);
                    if ($result) {
                        $deleted_count++;
                    } else {
                        $failed_ids[] = $numeric_id;
                    }
                }
            }

            if ($deleted_count > 0) {
                $message = sprintf(
                    'Successfully deleted %d benefit box(es).',
                    $deleted_count
                );

                if (!empty($failed_ids)) {
                    $message .= sprintf(
                        ' Failed to delete %d item(s): %s',
                        count($failed_ids),
                        implode(', ', $failed_ids)
                    );
                }

                wp_send_json_success([
                    'message' => $message,
                    'deleted_count' => $deleted_count,
                    'failed_ids' => $failed_ids
                ]);
            } else {
                wp_send_json_error('No benefit boxes were deleted');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if benefit box feature is enabled
     */
    public static function isEnabled(): bool
    {
        return (bool) get_option(self::OPTION_KEY_ENABLED, 1); // Default to enabled
    }

    /**
     * Toggle benefit box feature
     */
    public static function toggleEnabled(bool $enabled): bool
    {
        return update_option(self::OPTION_KEY_ENABLED, $enabled ? 1 : 0);
    }
}
