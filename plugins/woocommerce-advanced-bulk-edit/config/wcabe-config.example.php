<?php
/**
 * WooCommerce Advanced Bulk Edit Configuration
 *
 * This file provides configuration overrides for WooCommerce Advanced Bulk Edit.
 * When present, settings in this file will override database settings.
 *
 * To use this configuration:
 * 1. Copy this file to 'wcabe-config.php' in the same directory
 * 2. Modify the settings as needed
 * 3. The plugin will automatically detect and use these settings
 *
 * Note: This file is meant for advanced users and deployment scenarios
 * where you want to enforce specific settings regardless of UI configuration.
 */

return [
    /**
     * Failsafe Filtering Configuration
     *
     * The failsafe filter provides a secondary validation layer to ensure
     * all filters are properly applied to product results.
     */
    'failsafe_filtering' => [
        /**
         * Enable or disable failsafe filtering
         * When enabled, applies additional validation to filtered results
         *
         * @var bool Default: true
         */
        'enabled' => true,

        /**
         * Enable debug mode for failsafe filtering
         * When enabled, logs discrepancies and debugging information
         *
         * @var bool Default: false
         */
        'debug' => false,

        /**
         * Include parent products for matched variations
         * When enabled, parent products are automatically included
         * when their variations match the filter criteria
         *
         * @var bool Default: true
         */
        'include_parents' => true,

        /**
         * Show admin notices for filter discrepancies
         * When enabled, displays notices in admin area when filters
         * produce different results than expected
         *
         * @var bool Default: false
         */
        'show_notices' => false,
    ],

    /**
     * Logging Configuration
     *
     * Control logging behavior for different channels/components.
     * This allows you to enable/disable specific types of logs.
     */
    'logging' => [
        /**
         * Master logging switch
         * When disabled, no logs will be written regardless of channel settings
         *
         * @var bool Default: true
         */
        'enabled' => true,

        /**
         * Log file configuration
         *
         * @var string Default: 'wcabe.log'
         */
        'file_path' => 'wcabe.log',

        /**
         * Maximum log file size in bytes before rotation
         * When exceeded, the log file will be renamed with a timestamp
         * and a new log file will be created
         *
         * @var int Default: 10485760 (10MB)
         */
        'max_file_size' => 10485760, // 10MB

        /**
         * Channel-specific logging controls
         * Set to true to enable, false to disable specific log types
         */
        'channels' => [
            /**
             * License/update server connection logs
             * Includes connection attempts, response times, version checks
             *
             * @var bool Default: true
             */
            'connection' => true,

            /**
             * Update check error logs
             * Logs failures in update check processes
             *
             * @var bool Default: true
             */
            'update_error' => true,

            /**
             * Failsafe filtering debug logs
             * Logs filter discrepancies and validation results
             *
             * @var bool Default: false
             */
            'failsafe' => false,

            /**
             * GTIN/UPC/EAN/ISBN duplicate detection logs
             * Logs when duplicate product identifiers are detected
             *
             * @var bool Default: false
             */
            'gtin' => false,

            /**
             * AJAX operation logs
             * Logs AJAX requests and responses
             *
             * @var bool Default: false
             */
            'ajax' => false,

            /**
             * Product save operation logs
             * Logs individual product save operations
             *
             * @var bool Default: false
             */
            'product_save' => false,

            /**
             * General debugging logs
             * Miscellaneous logs that don't fit other categories
             *
             * @var bool Default: true
             */
            'general' => true,
        ],
    ],

    /**
     * Future Configuration Sections
     *
     * This configuration file is designed to be extensible.
     * Additional sections can be added here for other plugin features.
     *
     * Examples:
     *
     * 'performance' => [
     *     'batch_size' => 100,
     *     'ajax_timeout' => 30000,
     * ],
     *
     * 'features' => [
     *     'enable_advanced_search' => true,
     *     'enable_bulk_variations' => true,
     * ],
     *
     * 'api' => [
     *     'rate_limit' => 100,
     *     'cache_ttl' => 3600,
     * ],
     */
];
