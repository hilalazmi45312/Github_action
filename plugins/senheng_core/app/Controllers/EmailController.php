<?php

defined('ABSPATH') || exit;

if (!class_exists('EmailController')) {
    class EmailController
    {
        public static function init()
        {
            add_filter('bwfan_cart_items_template_path', [__CLASS__, 'override_email_template_path'], 10, 4);

            // If the action has already fired, trigger our callback immediately
            if (did_action('bwfan_merge_tags_loaded')) {
                self::register_custom_order_items_merge_tag();
                self::register_custom_p1no_merge_tag();
            } else {
                add_action('bwfan_merge_tags_loaded', [__CLASS__, 'register_custom_order_items_merge_tag']);
                add_action('bwfan_merge_tags_loaded', [__CLASS__, 'register_custom_p1no_merge_tag']);
            }
        }

        public static function register_custom_order_items_merge_tag()
        {
            if (!class_exists('Senheng_WC_Order_Items')) {
                require __FILE__;
            }

            if (class_exists('Senheng_WC_Order_Items') && class_exists('BWFAN_Merge_Tag_Loader')) {
                BWFAN_Merge_Tag_Loader::register('wc_order', 'Senheng_WC_Order_Items', null, __('Order', 'wp-marketing-automations'));
            }
        }

        /**
         * Register the Customer P1 Number merge tag
         */
        public static function register_custom_p1no_merge_tag()
        {
            if (!class_exists('Senheng_Customer_P1No')) {
                require __FILE__;
            }

            if (class_exists('Senheng_Customer_P1No') && class_exists('BWFAN_Merge_Tag_Loader')) {
                BWFAN_Merge_Tag_Loader::register('bwf_contact', 'Senheng_Customer_P1No', null, __('Contact', 'wp-marketing-automations'));
            }
        }

        public static function override_email_template_path($file_path, $cart, $data, $attr)
        {
            // Log entry into the function
            // error_log('EmailController::override_email_template_path called. Template attr: ' . (isset($attr['template']) ? $attr['template'] : 'not set'));

            // Target custom senheng-product-rows template
            $target_templates = ['senheng-product-rows'];

            // Keep backward compatibility for the existing override if needed
            if (isset($attr['template']) && 'product-rows' === $attr['template']) {
                $target_templates[] = 'product-rows';
            }

            if (!isset($attr['template']) || !in_array($attr['template'], $target_templates)) {
                return $file_path;
            }

            $custom_template = SENHENG_CORE_VIEW_PATH . 'email/product-rows.php';
            
            // error_log('Trying to load custom template: ' . $custom_template);

            if ( file_exists( $custom_template ) && is_readable( $custom_template ) ) {
                // error_log('Custom template found and returned.');
                return $custom_template;
            } else {
                error_log('Custom template not found or not readable: ' . $custom_template);
            }

            return $file_path;
        }
    }

    EmailController::init();
}

/**
 * Conditional class definition.
 * This block will be skipped if BWFAN_WC_Order_Items is not loaded yet.
 * But when register_custom_order_items_merge_tag() calls require __FILE__,
 * this block will execute because we are inside the 'bwfan_merge_tags_loaded' hook,
 * so BWFAN_WC_Order_Items should be available.
 */
if (class_exists('BWFAN_WC_Order_Items') && !class_exists('Senheng_WC_Order_Items')) {
    class Senheng_WC_Order_Items extends BWFAN_WC_Order_Items
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function get_setting_schema()
        {
            $schema = parent::get_setting_schema();

            // Check if we already added it to avoid duplicates if initialized multiple times
            $found = false;
            foreach ($schema as $field) {
                if (isset($field['id']) && 'template' === $field['id']) {
                    if (isset($field['options'])) {
                        foreach ($field['options'] as $option) {
                            if ($option['value'] === 'senheng-product-rows') {
                                $found = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            if (!$found) {
                foreach ($schema as &$field) {
                    if (isset($field['id']) && 'template' === $field['id']) {
                        $field['options'][] = [
                            'value' => 'senheng-product-rows',
                            'label' => __('Senheng Product Rows', 'senheng-core'),
                        ];
                        break;
                    }
                }
            }

            return $schema;
        }

        public function get_slug()
        {
            return sanitize_title('BWFAN_WC_Order_Items');
        }
    }
}

/**
 * Customer P1 Number Merge Tag
 * Usage: {{customer_p1no}} or [bwfan_customer_p1no]
 * With fallback: {{customer_p1no fallback="N/A"}}
 */
if (class_exists('BWFAN_Merge_Tag') && !class_exists('Senheng_Customer_P1No')) {
    class Senheng_Customer_P1No extends BWFAN_Merge_Tag
    {
        private static $instance = null;

        public function __construct()
        {
            $this->tag_name        = 'customer_p1no';
            $this->tag_description = __('Customer P1 Number', 'senheng-core');
            add_shortcode('bwfan_customer_p1no', [$this, 'parse_shortcode']);
            $this->support_fallback = true;
            $this->priority         = 21;
            $this->is_crm_broadcast = true;  // Makes it appear in email block editor
        }

        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Parse the merge tag and return its value.
         *
         * @param array $attr Shortcode attributes
         * @return string
         */
        public function parse_shortcode($attr)
        {
            $get_data = BWFAN_Merge_Tag_Loader::get_data();

            if (true === $get_data['is_preview']) {
                return $this->parse_shortcode_output($this->get_dummy_preview(), $attr);
            }

            // Try to get user ID from various sources
            $user_id = 0;

            // If order is available
            $order = $this->get_order_object($get_data);
            if (!empty($order)) {
                $user_id = $order->get_user_id();
            }

            // If user_id is available directly
            if (empty($user_id) && isset($get_data['user_id'])) {
                $user_id = absint($get_data['user_id']);
            }

            // If wp_user is available
            if (empty($user_id) && isset($get_data['wp_user']) && $get_data['wp_user'] instanceof WP_User) {
                $user_id = $get_data['wp_user']->ID;
            }

            // If email is available, get user by email
            if (empty($user_id) && isset($get_data['email']) && !empty($get_data['email'])) {
                $user = get_user_by('email', $get_data['email']);
                if ($user instanceof WP_User) {
                    $user_id = $user->ID;
                }
            }

            // Get the cust_p1no meta value
            if ($user_id > 0) {
                $p1no = get_user_meta($user_id, 'cust_p1no', true);
                return $this->parse_shortcode_output($p1no, $attr);
            }

            return $this->parse_shortcode_output('', $attr);
        }

        /**
         * Show dummy value for preview.
         *
         * @return string
         */
        public function get_dummy_preview()
        {
            // Check if current user has a P1 number
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $p1no = get_user_meta($user_id, 'cust_p1no', true);
                if (!empty($p1no)) {
                    return $p1no;
                }
            }
            return 'P1-12345678';  // Fallback preview value
        }
    }
}
