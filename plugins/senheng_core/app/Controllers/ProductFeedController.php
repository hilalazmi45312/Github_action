<?php

/**
 * ProductFeedController
 * 
 * Handles WPVIP compatibility for the Product Feed for WooCommerce plugin.
 * Redirects feed file generation to /tmp directory.
 * 
 * WARNING: Files in /tmp are ephemeral on WPVIP and the plugin's download
 * URLs will not work since they point to wp-content/uploads.
 * 
 * If you need working download URLs, enable COPY_TO_UPLOADS = true
 */
class ProductFeedController
{
    /**
     * Set to true to copy files from /tmp to wp-content/uploads after export
     * Set to false to keep files only in /tmp (download URLs may not work)
     */
    const COPY_TO_UPLOADS = true;

    /**
     * Initialize the controller - only runs if product feed plugin is active
     */
    public static function init()
    {
        add_action('plugins_loaded', [self::class, 'senhengsenq_override_export_dir'], 20);
    }

    /**
     * Override the export directory only if the product feed plugin is active
     */
    public static function senhengsenq_override_export_dir()
    {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (!is_plugin_active('product-feed-woocommerce/product-feed-woocommerce.php')) {
            return;
        }

        // Override on multiple hooks
        add_action('init', [self::class, 'override_export_dir'], 1);
        add_action('admin_init', [self::class, 'override_export_dir'], 1);
        add_action('wp_ajax_pf_export_ajax_pro', [self::class, 'override_export_dir'], 1);
        
        // Only add copy logic if enabled
        if (self::COPY_TO_UPLOADS) {
            add_filter('wt_pf_alter_export_data_basic', [self::class, 'handle_export_data'], 999, 6);
        }
    }

    /**
     * Override the static export directory to use /tmp
     */
    public static function override_export_dir()
    {
        if (!class_exists('Webtoffee_Product_Feed_Sync_Pro_Export')) {
            return;
        }

        $tmp_dir = self::get_tmp_export_dir();
        
        if (!is_dir($tmp_dir)) {
            wp_mkdir_p($tmp_dir);
        }
        
        Webtoffee_Product_Feed_Sync_Pro_Export::$export_dir = $tmp_dir;
    }

    /**
     * Handle export completion - copy files to uploads (only if COPY_TO_UPLOADS = true)
     */
    public static function handle_export_data($export_data, $offset, $is_last_offset, $file_as, $to_export, $csv_delimiter)
    {
        if ($is_last_offset) {
            add_action('shutdown', [self::class, 'copy_files_to_uploads']);
        }
        return $export_data;
    }

    /**
     * Copy all feed files from /tmp to wp-content/uploads
     */
    public static function copy_files_to_uploads()
    {
        $tmp_dir = self::get_tmp_export_dir();
        $upload_dir = self::get_uploads_export_dir();

        if (!is_dir($tmp_dir)) {
            return;
        }

        if (!is_dir($upload_dir)) {
            wp_mkdir_p($upload_dir);
            file_put_contents($upload_dir . '/index.php', '<?php // Silence is golden');
        }

        $files = scandir($tmp_dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $tmp_file = $tmp_dir . '/' . $file;
            $upload_file = $upload_dir . '/' . $file;

            if (is_file($tmp_file) && copy($tmp_file, $upload_file)) {
                @unlink($tmp_file);
            }
        }
    }

    /**
     * Get /tmp export directory path
     */
    public static function get_tmp_export_dir()
    {
        return rtrim(get_temp_dir(), '/') . '/webtoffee_product_feed';
    }

    /**
     * Get wp-content/uploads export directory path
     */
    public static function get_uploads_export_dir()
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/webtoffee_product_feed';
    }
}
