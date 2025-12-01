<?php

class BenefitBox
{
    const OPTION_KEY = 'senheng_benefit_boxes';
    const OPTION_KEY_COUNTER = 'senheng_benefit_boxes_counter';
    const OPTION_KEY_ENABLED = 'senheng_benefit_box_enabled';

    public static function all()
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            $boxes = [];
        }
        
        return self::sortBoxes($boxes);
    }

    public static function find($id)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            return null;
        }
        
        return isset($boxes[$id]) ? $boxes[$id] : null;
    }

    public static function findByType($type)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            return null;
        }
        
        foreach ($boxes as $box) {
            if (is_array($box) && isset($box['type']) && $box['type'] === $type && isset($box['is_active']) && $box['is_active'] == 1) {
                return $box;
            }
        }
        return null;
    }

    public static function getActiveSettings()
    {
        // Check if benefit box feature is enabled globally
        if (!self::isFeatureEnabled()) {
            return [];
        }

        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            return [];
        }
        
        $active_boxes = [];
        
        foreach ($boxes as $id => $box) {
            if (is_array($box) && isset($box['is_active']) && $box['is_active'] == 1) {
                $box['id'] = $id; // Add ID to the box data
                $active_boxes[] = $box;
            }
        }
        
        return self::sortBoxes($active_boxes);
    }

    /**
     * Check if benefit box feature is enabled globally
     */
    public static function isFeatureEnabled(): bool
    {
        return (bool) get_option(self::OPTION_KEY_ENABLED, 1); // Default to enabled
    }

    public static function create($data)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            $boxes = [];
        }
        
        $counter = get_option(self::OPTION_KEY_COUNTER, 0);
        $counter++;
        
        // Set default values for optional fields
        $data['whatsapp_number'] = $data['whatsapp_number'] ?? '';
        $data['predefined_text'] = $data['predefined_text'] ?? '';
        $data['always_online'] = $data['always_online'] ?? 0;
        $data['availability_schedule'] = $data['availability_schedule'] ?? '';
        $data['working_days_message'] = $data['working_days_message'] ?? '';
        $data['non_working_days_message'] = $data['non_working_days_message'] ?? '';
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        $boxes[$counter] = $data;
        
        update_option(self::OPTION_KEY, $boxes);
        update_option(self::OPTION_KEY_COUNTER, $counter);
        
        return $counter;
    }

    public static function update($id, $data)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            $boxes = [];
        }
        
        if (!isset($boxes[$id])) {
            return false;
        }
        
        // Set default values for optional fields
        $data['whatsapp_number'] = $data['whatsapp_number'] ?? '';
        $data['predefined_text'] = $data['predefined_text'] ?? '';
        $data['always_online'] = $data['always_online'] ?? 0;
        $data['availability_schedule'] = $data['availability_schedule'] ?? '';
        $data['working_days_message'] = $data['working_days_message'] ?? '';
        $data['non_working_days_message'] = $data['non_working_days_message'] ?? '';
        $data['updated_at'] = current_time('mysql');
        
        // Preserve created_at
        $data['created_at'] = $boxes[$id]['created_at'] ?? current_time('mysql');
        
        $boxes[$id] = $data;
        
        $result = update_option(self::OPTION_KEY, $boxes);
        return $result;
    }

    public static function delete($id)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            $boxes = [];
        }
        
        if (!isset($boxes[$id])) {
            return false;
        }
        
        unset($boxes[$id]);
        
        return update_option(self::OPTION_KEY, $boxes);
    }

    public static function updateStatus($id, $status)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            $boxes = [];
        }
        
        if (!isset($boxes[$id])) {
            return false;
        }
        
        $boxes[$id]['is_active'] = $status;
        $boxes[$id]['updated_at'] = current_time('mysql');
        
        return update_option(self::OPTION_KEY, $boxes);
    }

    public static function updateSortOrder($id, $sort_order)
    {
        $boxes = get_option(self::OPTION_KEY, []);
        
        // Ensure we have an array
        if (!is_array($boxes)) {
            $boxes = [];
        }
        
        if (!isset($boxes[$id])) {
            return false;
        }
        
        $boxes[$id]['sort_order'] = $sort_order;
        $boxes[$id]['updated_at'] = current_time('mysql');
        
        return update_option(self::OPTION_KEY, $boxes);
    }

    /**
     * Get available box types
     */
    public static function getBoxTypes()
    {
        return [
            'regular' => [
                'label' => 'Regular Box',
                'description' => 'Simple box with image, title and description'
            ],
            'whatsapp' => [
                'label' => 'WhatsApp Box',
                'description' => 'Box with WhatsApp integration'
            ],
            'installment' => [
                'label' => 'Installment Box',
                'description' => 'Box for installment information'
            ]
        ];
    }

    /**
     * Sort boxes by sort_order
     */
    private static function sortBoxes($boxes)
    {
        // Ensure we have an array
        if (!is_array($boxes)) {
            return [];
        }
        
        // If empty array, return as is
        if (empty($boxes)) {
            return $boxes;
        }
        
        usort($boxes, function($a, $b) {
            $order_a = isset($a['sort_order']) ? intval($a['sort_order']) : 0;
            $order_b = isset($b['sort_order']) ? intval($b['sort_order']) : 0;
            return $order_a - $order_b;
        });
        
        return $boxes;
    }

    /**
     * Get default availability schedule
     */
    public static function getDefaultSchedule()
    {
        $schedule = [];
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        
        foreach ($days as $day) {
            $schedule[$day] = [
                'enabled' => true,
                'start' => '10:00',
                'end' => '21:00'
            ];
        }
        
        return $schedule;
    }

    /**
     * Save data from request (create or update)
     */
    public static function saveFromRequest($request_data, $id = 0)
    {
        // Initialize all fields with default values first
        $data = [
            'type' => sanitize_text_field($request_data['type']),
            'title' => sanitize_text_field($request_data['title']),
            'subtitle' => sanitize_textarea_field($request_data['subtitle']),
            'icon' => sanitize_text_field($request_data['icon']),
            'is_active' => isset($request_data['is_active']) ? 1 : 0,
            'sort_order' => intval($request_data['sort_order']),
            // Initialize WhatsApp fields with defaults
            'whatsapp_number' => '',
            'predefined_text' => '',
            'always_online' => 0,
            'working_days_message' => '',
            'non_working_days_message' => '',
            'availability_schedule' => ''
        ];

        // Handle type-specific fields
        if ($request_data['type'] === 'whatsapp') {
            $data['whatsapp_number'] = sanitize_text_field($request_data['whatsapp_number'] ?? '');
            $data['predefined_text'] = sanitize_textarea_field($request_data['predefined_text'] ?? '');
            $data['always_online'] = isset($request_data['always_online']) ? 1 : 0;
            $data['working_days_message'] = sanitize_textarea_field($request_data['working_days_message'] ?? '');
            $data['non_working_days_message'] = sanitize_textarea_field($request_data['non_working_days_message'] ?? '');
            
            // Process availability schedule
            $availability_schedule = [];
            if (isset($request_data['availability_schedule']) && is_array($request_data['availability_schedule'])) {
                $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                foreach ($days as $day) {
                    if (isset($request_data['availability_schedule'][$day])) {
                        $day_data = $request_data['availability_schedule'][$day];
                        $availability_schedule[$day] = [
                            'enabled' => isset($day_data['enabled']) ? true : false,
                            'start' => sanitize_text_field($day_data['start'] ?? '10:00'),
                            'end' => sanitize_text_field($day_data['end'] ?? '21:00')
                        ];
                    } else {
                        // Default values if not provided
                        $availability_schedule[$day] = [
                            'enabled' => true,
                            'start' => '10:00',
                            'end' => '21:00'
                        ];
                    }
                }
            } else {
                // Default schedule if no data provided
                $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                foreach ($days as $day) {
                    $availability_schedule[$day] = [
                        'enabled' => true,
                        'start' => '10:00',
                        'end' => '21:00'
                    ];
                }
            }
            $data['availability_schedule'] = json_encode($availability_schedule);
        } else {
            // For regular and installment types, set a default JSON schedule
            $default_schedule = [];
            $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            foreach ($days as $day) {
                $default_schedule[$day] = [
                    'enabled' => true,
                    'start' => '10:00',
                    'end' => '21:00'
                ];
            }
            $data['availability_schedule'] = json_encode($default_schedule);
        }

        if ($id > 0) {
            return self::update($id, $data);
        } else {
            return self::create($data);
        }
    }

    /**
     * Migrate data from old table to wp_options (if needed)
     */
    public static function migrateFromTable()
    {
        global $wpdb;
        
        // Check if migration is already done
        if (get_option('senheng_benefit_boxes_migrated', false)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'c_benefit_box_settings';
        
        // Check if old table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            update_option('senheng_benefit_boxes_migrated', true);
            return;
        }
        
        $old_data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC", ARRAY_A);
        
        if (!empty($old_data)) {
            $boxes = [];
            $counter = 0;
            
            foreach ($old_data as $row) {
                $counter++;
                $boxes[$counter] = $row;
            }
            
            update_option(self::OPTION_KEY, $boxes);
            update_option(self::OPTION_KEY_COUNTER, $counter);
        }
        
        update_option('senheng_benefit_boxes_migrated', true);
    }
}
