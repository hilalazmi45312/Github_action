<?php
/**
 * Configuration Loader for WooCommerce Advanced Bulk Edit
 *
 * Handles loading and merging configuration from file and database
 */
class W3ExABulkEdit_ConfigLoader {

    /**
     * Cached configuration data
     * @var array|null
     */
    private static $config_cache = null;

    /**
     * Path to the configuration file
     * @var string
     */
    private static $config_file_path = null;

    /**
     * Initialize the config loader
     */
    public static function init() {
        if (self::$config_file_path === null) {
            self::$config_file_path = WCABE_PLUGIN_PATH . 'config/wcabe-config.php';
        }
    }

    /**
     * Get a configuration value
     *
     * @param string $key Dot-notation key (e.g., 'failsafe_filtering.enabled')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        self::init();

        // Load config if not cached
        if (self::$config_cache === null) {
            self::load_config();
        }

        // Parse dot notation
        $keys = explode('.', $key);
        $value = self::$config_cache;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Check if failsafe filtering is enabled
     *
     * @return bool
     */
    public static function is_failsafe_filtering_enabled() {
        self::init();

        // First check config file
        $config_value = self::get('failsafe_filtering.enabled');
        if ($config_value !== null) {
            return (bool) $config_value;
        }

        // Then check database settings
        $settings = get_option('w3exabe_settings', array());
        if (isset($settings['failsafe_filtering_enabled'])) {
            return (bool) $settings['failsafe_filtering_enabled'];
        }

        // Default to disabled
        return false;
    }

    /**
     * Get failsafe filter debug mode
     *
     * @return bool
     */
    public static function is_failsafe_debug_enabled() {
        return (bool) self::get('failsafe_filtering.debug', false);
    }

    /**
     * Check if parent products should be included for variations
     *
     * @return bool
     */
    public static function should_include_parents() {
        $config_value = self::get('failsafe_filtering.include_parents');
        if ($config_value !== null) {
            return (bool) $config_value;
        }

        // Check if constant is defined (backward compatibility)
        if (defined('WCABE_FAILSAFE_INCLUDE_PARENTS')) {
            return WCABE_FAILSAFE_INCLUDE_PARENTS;
        }

        return true; // Default to true
    }

    /**
     * Check if notices should be shown for discrepancies
     *
     * @return bool
     */
    public static function should_show_notices() {
        $config_value = self::get('failsafe_filtering.show_notices');
        if ($config_value !== null) {
            return (bool) $config_value;
        }

        // Check if constant is defined (backward compatibility)
        if (defined('WCABE_FAILSAFE_SHOW_NOTICES')) {
            return WCABE_FAILSAFE_SHOW_NOTICES;
        }

        return false; // Default to false
    }

    /**
     * Check if configuration file exists
     *
     * @return bool
     */
    public static function config_file_exists() {
        self::init();
        return file_exists(self::$config_file_path);
    }

    /**
     * Get information about config override status
     *
     * @return array
     */
    public static function get_override_info() {
        self::init();

        $info = array(
            'has_config_file' => self::config_file_exists(),
            'config_file_path' => self::$config_file_path,
            'overridden_settings' => array()
        );

        if ($info['has_config_file']) {
            // Load raw config to check what's being overridden
            $file_config = self::load_file_config();
            if (!empty($file_config['failsafe_filtering'])) {
                if (isset($file_config['failsafe_filtering']['enabled'])) {
                    $info['overridden_settings'][] = 'failsafe_filtering_enabled';
                }
            }
        }

        return $info;
    }

    /**
     * Load configuration from file and database
     */
    private static function load_config() {
        // Start with empty config
        self::$config_cache = array();

        // Load from file if exists
        $file_config = self::load_file_config();
        if (is_array($file_config)) {
            self::$config_cache = $file_config;
        }

        // Merge with database settings (file takes precedence)
        // Database settings are accessed directly when needed via get_option()
    }

    /**
     * Load configuration from file
     *
     * @return array|false
     */
    private static function load_file_config() {
        if (!file_exists(self::$config_file_path)) {
            return false;
        }

        try {
            $config = include self::$config_file_path;

            // Validate that the file returns an array
            if (!is_array($config)) {
                error_log('WCABE Config Loader: Configuration file must return an array');
                return false;
            }

            return $config;
        } catch (Exception $e) {
            error_log('WCABE Config Loader: Error loading configuration file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear the configuration cache
     * Useful for testing or after configuration changes
     */
    public static function clear_cache() {
        self::$config_cache = null;
    }

    /**
     * Check if logging is enabled globally
     *
     * @return bool
     */
    public static function is_logging_enabled() {
        return (bool) self::get('logging.enabled', true);
    }

    /**
     * Check if a specific log channel is enabled
     *
     * @param string $channel The channel name to check
     * @return bool
     */
    public static function is_log_channel_enabled($channel) {
        // If logging is disabled globally, all channels are disabled
        if (!self::is_logging_enabled()) {
            return false;
        }

        // Check if the specific channel is enabled (default to true if not configured)
        return (bool) self::get("logging.channels.{$channel}", true);
    }

    /**
     * Get the log file path
     *
     * @return string
     */
    public static function get_log_file_path() {
        return WCABE_PLUGIN_PATH . self::get('logging.file_path', 'wcabe.log');
    }

    /**
     * Get the maximum log file size for rotation
     *
     * @return int Size in bytes
     */
    public static function get_log_max_file_size() {
        return (int) self::get('logging.max_file_size', 10485760); // Default 10MB
    }

    /**
     * Get all configured log channels
     *
     * @return array Associative array of channel => enabled status
     */
    public static function get_log_channels() {
        $channels = self::get('logging.channels', []);
        if (!is_array($channels)) {
            return [];
        }
        return $channels;
    }
}
